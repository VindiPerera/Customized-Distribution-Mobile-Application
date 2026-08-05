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

        $number = $row->next_number;

        DB::table('invoice_sequences')->where('id', $row->id)->update([
            'next_number' => $number + 1,
            'updated_at' => now(),
        ]);

        return (string) $number;
    }
}
