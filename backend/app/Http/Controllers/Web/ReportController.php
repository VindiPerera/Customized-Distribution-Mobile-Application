<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerLedgerEntry;
use App\Models\Product;

class ReportController extends Controller
{
    public function receivables()
    {
        $customers = Customer::where('current_balance', '>', 0)
            ->orderByDesc('current_balance')
            ->get();

        $buckets = ['0_30' => 0.0, '31_60' => 0.0, '61_90' => 0.0, 'over_90' => 0.0];

        CustomerLedgerEntry::where('type', 'sale')
            ->with('customer')
            ->get()
            ->each(function (CustomerLedgerEntry $entry) use (&$buckets) {
                if (! $entry->customer || $entry->customer->current_balance <= 0) {
                    return;
                }
                $days = $entry->created_at->diffInDays(now());
                $bucket = match (true) {
                    $days <= 30 => '0_30',
                    $days <= 60 => '31_60',
                    $days <= 90 => '61_90',
                    default => 'over_90',
                };
                $buckets[$bucket] += (float) $entry->amount;
            });

        return view('reports.receivables', compact('customers', 'buckets'));
    }

    public function lowStock()
    {
        $products = Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->get();

        return view('reports.low-stock', compact('products'));
    }
}
