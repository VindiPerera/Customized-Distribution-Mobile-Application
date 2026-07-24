<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportDashboardService
{
    /**
     * Fixed display order so the legend/table never reflows between periods.
     */
    private const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'credit', 'split'];

    public function build(Carbon $from, Carbon $to): array
    {
        $topProducts = $this->topProducts($from, $to);

        return [
            'range' => ['from' => $from, 'to' => $to],
            'cards' => $this->cards($from, $to, $topProducts),
            'salesTrend' => $this->salesTrend($from, $to),
            'paymentBreakdown' => $this->paymentBreakdown($from, $to),
            'topProducts' => $topProducts,
            'customerBalances' => $this->customerBalances(),
            'supplierStock' => $this->supplierStock($from, $to),
        ];
    }

    private function cards(Carbon $from, Carbon $to, Collection $topProducts): array
    {
        $totals = Sale::whereBetween('sale_date', [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total, COUNT(*) as count')
            ->first();

        return [
            'total_sales' => (float) $totals->total,
            'transaction_count' => (int) $totals->count,
            'credit_outstanding' => (float) Customer::where('current_balance', '>', 0)->sum('current_balance'),
            'top_product' => $topProducts->first(),
            'low_stock_count' => Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')
                ->where('is_active', true)
                ->count(),
            'supplier_count' => Supplier::where('is_active', true)->count(),
        ];
    }

    private function salesTrend(Carbon $from, Carbon $to): Collection
    {
        // Group daily ranges up to ~90 days; wider ranges collapse to weekly buckets
        // so a "This Year" filter doesn't render 365 unreadable bars.
        $groupByWeek = $from->diffInDays($to) > 90;

        $bucketExpr = $groupByWeek
            ? "DATE_FORMAT(sale_date, '%x-W%v')"
            : 'DATE(sale_date)';

        return Sale::whereBetween('sale_date', [$from, $to])
            ->where('status', 'completed')
            ->selectRaw("{$bucketExpr} as bucket, SUM(total_amount) as total")
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => ['label' => $row->bucket, 'total' => (float) $row->total]);
    }

    private function paymentBreakdown(Carbon $from, Carbon $to): Collection
    {
        $totals = Sale::whereBetween('sale_date', [$from, $to])
            ->where('status', 'completed')
            ->selectRaw('payment_type, SUM(total_amount) as total')
            ->groupBy('payment_type')
            ->pluck('total', 'payment_type');

        return collect(self::PAYMENT_METHODS)->map(fn ($method) => [
            'method' => $method,
            'total' => (float) ($totals[$method] ?? 0),
        ]);
    }

    private function topProducts(Carbon $from, Carbon $to, int $limit = 10): Collection
    {
        return SaleItem::query()
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->whereBetween('sales.sale_date', [$from, $to])
            ->where('sales.status', 'completed')
            ->selectRaw('products.id, products.name, products.sku, SUM(sale_items.quantity) as qty_sold, SUM(sale_items.line_total) as revenue')
            ->groupBy('products.id', 'products.name', 'products.sku')
            ->orderByDesc('qty_sold')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'id' => $row->id,
                'name' => $row->name,
                'sku' => $row->sku,
                'qty_sold' => (int) $row->qty_sold,
                'revenue' => (float) $row->revenue,
            ]);
    }

    private function customerBalances(int $limit = 25): Collection
    {
        return Customer::where('current_balance', '>', 0)
            ->orderByDesc('current_balance')
            ->select('id', 'name', 'phone', 'credit_limit', 'current_balance')
            ->limit($limit)
            ->get()
            ->map(fn (Customer $customer) => [
                'name' => $customer->name,
                'phone' => $customer->phone,
                'credit_limit' => (float) $customer->credit_limit,
                'current_balance' => (float) $customer->current_balance,
                'available_credit' => $customer->availableCredit(),
            ]);
    }

    private function supplierStock(Carbon $from, Carbon $to): Collection
    {
        $stockValue = Supplier::query()
            ->leftJoin('products', 'products.supplier_id', '=', 'suppliers.id')
            ->selectRaw('suppliers.id, suppliers.name, COALESCE(SUM(products.stock_quantity * products.cost_price), 0) as stock_value')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('stock_value')
            ->get();

        $unitsReceived = StockMovement::query()
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.type', 'purchase_in')
            ->whereBetween('stock_movements.created_at', [$from, $to])
            ->selectRaw('products.supplier_id, SUM(stock_movements.quantity) as units_received')
            ->groupBy('products.supplier_id')
            ->pluck('units_received', 'supplier_id');

        return $stockValue->map(fn ($row) => [
            'name' => $row->name,
            'stock_value' => (float) $row->stock_value,
            'units_received' => (int) ($unitsReceived[$row->id] ?? 0),
        ]);
    }
}
