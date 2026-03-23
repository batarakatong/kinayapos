<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentCallbackController extends Controller
{
    public function midtrans(Request $request)
    {
        $payload = $request->all();
        $reference = $payload['order_id'] ?? $payload['reference'] ?? null;
        if (!$reference) {
            return response()->json(['message' => 'invalid payload'], 400);
        }

        $payment = Payment::where('reference_id', $reference)->first();
        if (!$payment) {
            return response()->json(['message' => 'payment not found'], 404);
        }

        $status = match ($payload['transaction_status'] ?? null) {
            'capture', 'settlement', 'success' => 'paid',
            'pending' => 'pending',
            default => 'failed',
        };

        $payment->update([
            'status' => $status,
            'payload' => $payload,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        $payment->sale()->update([
            'status' => $status === 'paid' ? 'paid' : $payment->sale->status,
            'paid_at' => $status === 'paid' ? now() : null,
        ]);

        return response()->json(['message' => 'ok']);
    }
}
