<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class InvoiceNumberService
{
    /**
     * Atomically claim the next sequential, digits-only invoice number
     * (starting at 1). Must be called from inside the same DB
     * transaction that creates the Sale, so a failed sale never burns a
     * number.
     */
    public function next(): string
    {
        $row = DB::table('invoice_sequences')->lockForUpdate()->first();

        $number = $row ? (int) $row->next_number : 1;

        // Ensure we skip any existing invoice numbers to prevent collisions
        while (DB::table('sales')->where('invoice_number', (string) $number)->exists()) {
            $number++;
        }

        if ($row) {
            DB::table('invoice_sequences')->where('id', $row->id)->update([
                'next_number' => $number + 1,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('invoice_sequences')->insert([
                'next_number' => $number + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (string) $number;
    }
}
