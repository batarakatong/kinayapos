<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentPayRequest;
use App\Http\Requests\PaymentQrisRequest;
use App\Jobs\CreateQrisCharge;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    /**
     * POST /sales/{sale}/pay
     * Record a cash / transfer payment immediately (synchronous).
     */
    public function pay(PaymentPayRequest $request, Sale $sale)
    {
        $branchId = $request->attributes->get('branch_id');
        abort_unless($sale->branch_id === $branchId, 403);
        abort_if(in_array($sale->status, ['paid', 'void']), 422, 'Sale already closed.');

        $data = $request->validated();

        DB::transaction(function () use ($sale, $data) {
            Payment::create([
                'sale_id'  => $sale->id,
                'method'   => $data['method'],
                'status'   => 'paid',
                'amount'   => $data['amount'],
                'paid_at'  => now(),
            ]);

            $sale->update([
                'status'         => 'paid',
                'payment_method' => $data['method'],
                'paid_at'        => now(),
            ]);
        });

        return response()->json(['message' => 'payment recorded']);
    }

    /**
     * POST /payments/qris
     * Create a QRIS charge via Midtrans and immediately return a pending payment.
     * The actual Midtrans /charge call is executed in the background queue (CreateQrisCharge).
     */
    public function createQris(PaymentQrisRequest $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data     = $request->validated();

        $sale = Sale::findOrFail($data['sale_id']);
        abort_unless($sale->branch_id === $branchId, 403);
        abort_if(in_array($sale->status, ['paid', 'void']), 422, 'Sale already closed.');

        // Unique order ID that becomes the Midtrans order_id
        $orderId = 'SALE-' . $sale->uuid . '-' . now()->format('YmdHis');

        $payment = Payment::create([
            'sale_id'      => $sale->id,
            'method'       => 'qris',
            'status'       => 'pending',
            'reference_id' => $orderId,   // overwritten by job after Midtrans responds
            'provider'     => 'midtrans',
            'amount'       => $data['amount'],
            'payload'      => ['order_id' => $orderId],
        ]);

        // Build item_details from sale items for Midtrans
        $items = $sale->items->map(fn ($i) => [
            'id'       => (string) $i->product_id,
            'name'     => $i->product->name ?? 'Item',
            'price'    => (int) $i->price,
            'quantity' => (int) $i->qty,
        ])->toArray();

        $customer = [];
        if ($sale->customer) {
            $customer = [
                'first_name' => $sale->customer->name,
                'email'      => $sale->customer->email ?? null,
                'phone'      => $sale->customer->phone ?? null,
            ];
        }

        // Dispatch to queue – non-blocking
        CreateQrisCharge::dispatch(
            paymentId: $payment->id,
            orderId:   $orderId,
            amount:    (int) $data['amount'],
            items:     $items,
            customer:  $customer,
        );

        return response()->json([
            'payment_id'   => $payment->id,
            'order_id'     => $orderId,
            'status'       => 'pending',
            'message'      => 'QRIS charge queued. Poll GET /payments/{id}/status for QR code.',
        ], 202);
    }

    /**
     * GET /payments/{payment}/status
     * Poll payment status (for mobile to check if QRIS has been paid).
     */
    public function status(Request $request, Payment $payment)
    {
        $branchId = $request->attributes->get('branch_id');
        abort_unless($payment->sale->branch_id === $branchId, 403);

        $qrImageUrl = null;
        if ($payment->method === 'qris' && $payment->payload) {
            $actions = $payment->payload['actions'] ?? [];
            foreach ($actions as $action) {
                if (($action['name'] ?? '') === 'generate-qr-code') {
                    $qrImageUrl = $action['url'] ?? null;
                    break;
                }
            }
        }

        return response()->json([
            'payment_id'    => $payment->id,
            'status'        => $payment->status,
            'method'        => $payment->method,
            'amount'        => $payment->amount,
            'reference_id'  => $payment->reference_id,
            'qr_image_url'  => $qrImageUrl,
            'paid_at'       => $payment->paid_at,
            'expires_at'    => $payment->expires_at,
        ]);
    }
}
