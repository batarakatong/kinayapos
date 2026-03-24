<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\PayablePayment;
use Illuminate\Support\Facades\DB;

class PayableService
{
    /**
     * Record a payment instalment on a payable.
     *
     * @param  array{amount: float, method: string, note?: string|null} $data
     * @return array{payment: PayablePayment, payable: Payable}
     */
    public function pay(Payable $payable, array $data): array
    {
        return DB::transaction(function () use ($payable, $data) {
            $amount = min((float) $data['amount'], (float) $payable->balance);

            $payment = PayablePayment::create([
                'payable_id' => $payable->id,
                'amount'     => $amount,
                'method'     => $data['method'],
                'paid_at'    => now(),
                'note'       => $data['note'] ?? null,
            ]);

            $payable->balance -= $amount;
            if ($payable->balance <= 0) {
                $payable->balance = 0;
                $payable->status  = 'paid';
            } elseif ($payable->balance < $payable->amount) {
                $payable->status = 'partial';
            }
            $payable->save();

            // Sync the linked purchase paid amount
            if ($payable->purchase_id) {
                $purchase = $payable->purchase;
                if ($purchase) {
                    $purchase->paid += $amount;
                    if ($purchase->paid >= $purchase->total) {
                        $purchase->paid   = $purchase->total;
                        $purchase->status = 'paid';
                    } else {
                        $purchase->status = 'partial';
                    }
                    $purchase->save();
                }
            }

            return ['payment' => $payment, 'payable' => $payable->refresh()];
        });
    }
}
