<?php

namespace App\Services;

use App\Models\Receivable;
use App\Models\ReceivablePayment;
use Illuminate\Support\Facades\DB;

class ReceivableService
{
    /**
     * Record a payment instalment on a receivable.
     *
     * @param  array{amount: float, method: string, note?: string|null} $data
     * @return array{payment: ReceivablePayment, receivable: Receivable}
     */
    public function pay(Receivable $receivable, array $data): array
    {
        return DB::transaction(function () use ($receivable, $data) {
            // Clamp amount to the remaining balance
            $amount = min((float) $data['amount'], (float) $receivable->balance);

            $payment = ReceivablePayment::create([
                'receivable_id' => $receivable->id,
                'amount'        => $amount,
                'method'        => $data['method'],
                'paid_at'       => now(),
                'note'          => $data['note'] ?? null,
            ]);

            $receivable->balance -= $amount;
            if ($receivable->balance <= 0) {
                $receivable->balance = 0;
                $receivable->status  = 'paid';
            } elseif ($receivable->balance < $receivable->amount) {
                $receivable->status = 'partial';
            }
            $receivable->save();

            return ['payment' => $payment, 'receivable' => $receivable->refresh()];
        });
    }
}
