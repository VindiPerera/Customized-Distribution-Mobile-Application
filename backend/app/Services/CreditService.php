<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Payment;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class CreditService
{
    /**
     * Record a credit sale against a customer's account.
     */
    public function recordSaleOnCredit(Customer $customer, Sale $sale): CustomerLedgerEntry
    {
        return DB::transaction(function () use ($customer, $sale) {
            $lockedCustomer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $newBalance = (float) $lockedCustomer->current_balance + (float) $sale->total_amount;

            $entry = $lockedCustomer->ledgerEntries()->create([
                'type' => 'sale',
                'amount' => $sale->total_amount,
                'balance_after' => $newBalance,
                'reference_type' => Sale::class,
                'reference_id' => $sale->id,
            ]);

            $lockedCustomer->update(['current_balance' => $newBalance]);

            return $entry;
        });
    }

    /**
     * Record a payment from a customer, reducing their outstanding balance.
     */
    public function recordPayment(Customer $customer, array $data): Payment
    {
        return DB::transaction(function () use ($customer, $data) {
            $lockedCustomer = Customer::whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $payment = $lockedCustomer->payments()->create([
                'user_id' => $data['user_id'],
                'amount' => $data['amount'],
                'method' => $data['method'] ?? 'cash',
                'reference_no' => $data['reference_no'] ?? null,
                'paid_at' => $data['paid_at'] ?? now(),
                'notes' => $data['notes'] ?? null,
            ]);

            $newBalance = (float) $lockedCustomer->current_balance - (float) $payment->amount;

            $lockedCustomer->ledgerEntries()->create([
                'type' => 'payment',
                'amount' => -$payment->amount,
                'balance_after' => $newBalance,
                'reference_type' => Payment::class,
                'reference_id' => $payment->id,
            ]);

            $lockedCustomer->update(['current_balance' => $newBalance]);

            return $payment;
        });
    }

    /**
     * Aging buckets (0-30 / 31-60 / 61-90 / 90+ days) for a customer's
     * outstanding credit sales, based on unpaid sale ledger entries.
     */
    public function agingSummary(Customer $customer): array
    {
        $buckets = ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];

        $customer->ledgerEntries()
            ->where('type', 'sale')
            ->orderBy('created_at')
            ->get()
            ->each(function (CustomerLedgerEntry $entry) use (&$buckets) {
                $days = $entry->created_at->diffInDays(now());
                $bucket = match (true) {
                    $days <= 30 => '0_30',
                    $days <= 60 => '31_60',
                    $days <= 90 => '61_90',
                    default => 'over_90',
                };
                $buckets[$bucket] += (float) $entry->amount;
            });

        return $buckets;
    }
}
