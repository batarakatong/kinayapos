<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function pay(Request $request, Sale $sale)
    {
        $branchId = $request->attributes->get('branch_id');
        abort_unless($sale->branch_id === $branchId, 403);

        $data = $request->validate([
            'amount' => 'required|numeric|min:0',
            'method' => 'required|in:cash,transfer',
        ]);

        DB::transaction(function () use ($sale, $data) {
            Payment::create([
                'sale_id' => $sale->id,
                'method' => $data['method'],
                'status' => 'paid',
                'amount' => $data['amount'],
                'paid_at' => now(),
            ]);
            $sale->update([
                'status' => 'paid',
                'payment_method' => $data['method'],
                'paid_at' => now(),
            ]);
        });

        return response()->json(['message' => 'payment recorded']);
    }

    public function createQris(Request $request)
    {
        $branchId = $request->attributes->get('branch_id');
        $data = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'amount' => 'required|numeric|min:1000',
        ]);

        $sale = Sale::findOrFail($data['sale_id']);
        abort_unless($sale->branch_id === $branchId, 403);

        // Stub: generate reference and pretend QR link (replace with Midtrans/Xendit integration)
        $reference = 'QR-' . Str::uuid();

        $payment = Payment::create([
            'sale_id' => $sale->id,
            'method' => 'qris',
            'status' => 'pending',
            'reference_id' => $reference,
            'provider' => 'midtrans',
            'amount' => $data['amount'],
        ]);

        return response()->json([
            'payment_id' => $payment->id,
            'reference_id' => $reference,
            'qr_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . $reference,
            'status' => 'pending',
        ], 201);
    }
}
