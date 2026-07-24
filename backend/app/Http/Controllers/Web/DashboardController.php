<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Services\ReportDashboardService;

class DashboardController extends Controller
{
    public function __invoke(ReportDashboardService $reportDashboardService)
    {
        $today = now()->startOfDay();

        $todaySales = Sale::where('status', 'completed')->where('sale_date', '>=', $today);

        $stats = [
            'today_sales_total' => (clone $todaySales)->sum('total_amount'),
            'today_sales_count' => (clone $todaySales)->count(),
            'today_paid_total' => (clone $todaySales)->sum('paid_amount'),
            'today_credit_total' => (clone $todaySales)->where('payment_type', 'credit')->sum('total_amount'),
            'total_receivables' => Customer::sum('current_balance'),
            'low_stock_count' => Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')->where('is_active', true)->count(),
            'customers_over_limit' => Customer::whereColumn('current_balance', '>', 'credit_limit')->count(),
        ];

        $recentSales = Sale::with('customer', 'user')
            ->latest('sale_date')
            ->limit(8)
            ->get();

        $lowStockProducts = Product::whereColumn('stock_quantity', '<=', 'low_stock_alert')
            ->where('is_active', true)
            ->orderBy('stock_quantity')
            ->limit(5)
            ->get();

        $charts = $reportDashboardService->charts(now()->subDays(29)->startOfDay(), now()->endOfDay());
        $salesTrend = $charts['salesTrend'];
        $paymentBreakdown = $charts['paymentBreakdown'];

        return view('dashboard', compact('stats', 'recentSales', 'lowStockProducts', 'salesTrend', 'paymentBreakdown'));
    }
}
