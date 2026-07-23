<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

            <div>
                <p class="font-display text-2xl text-ink">Welcome back, {{ explode(' ', Auth::user()->name)[0] }}.</p>
                <p class="text-sm text-ink-soft mt-1">Here's what's happening across your business today.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-surface border border-line shadow-sm rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-9 h-9 rounded-lg bg-accent-soft text-accent flex items-center justify-center shrink-0">
                            <x-nav-icon name="receipt" class="w-[18px] h-[18px]" />
                        </span>
                        <div class="text-sm text-ink-soft">Today's Sales</div>
                    </div>
                    <div class="text-2xl font-bold text-ink tabular">Rs. {{ number_format($stats['today_sales_total'], 2) }}</div>
                    <div class="text-xs text-ink-soft mt-1">{{ $stats['today_sales_count'] }} transaction(s)</div>
                </div>

                <div class="bg-surface border border-line shadow-sm rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-9 h-9 rounded-lg bg-accent-soft text-accent flex items-center justify-center shrink-0">
                            <x-nav-icon name="chart" class="w-[18px] h-[18px]" />
                        </span>
                        <div class="text-sm text-ink-soft">Paid vs Credit Today</div>
                    </div>
                    <div class="text-base font-semibold text-good tabular">Paid: Rs. {{ number_format($stats['today_paid_total'], 2) }}</div>
                    <div class="text-base font-semibold text-warn tabular mt-0.5">Credit: Rs. {{ number_format($stats['today_credit_total'], 2) }}</div>
                </div>

                <div class="bg-surface border border-line shadow-sm rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-9 h-9 rounded-lg bg-accent-soft text-accent flex items-center justify-center shrink-0">
                            <x-nav-icon name="users" class="w-[18px] h-[18px]" />
                        </span>
                        <div class="text-sm text-ink-soft">Total Receivables</div>
                    </div>
                    <div class="text-2xl font-bold text-ink tabular">Rs. {{ number_format($stats['total_receivables'], 2) }}</div>
                    <div class="text-xs {{ $stats['customers_over_limit'] > 0 ? 'text-critical font-medium' : 'text-ink-soft' }} mt-1">
                        {{ $stats['customers_over_limit'] }} customer(s) over limit
                    </div>
                </div>

                <div class="bg-surface border border-line shadow-sm rounded-xl p-5">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="w-9 h-9 rounded-lg {{ $stats['low_stock_count'] > 0 ? 'bg-critical-soft text-critical' : 'bg-accent-soft text-accent' }} flex items-center justify-center shrink-0">
                            <x-nav-icon name="box" class="w-[18px] h-[18px]" />
                        </span>
                        <div class="text-sm text-ink-soft">Low Stock Items</div>
                    </div>
                    <div class="text-2xl font-bold {{ $stats['low_stock_count'] > 0 ? 'text-critical' : 'text-ink' }} tabular">
                        {{ $stats['low_stock_count'] }}
                    </div>
                    <a href="{{ route('reports.low-stock') }}" class="text-xs text-accent hover:text-accent-hover font-medium hover:underline">View report &rarr;</a>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-surface border border-line shadow-sm rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                        <h3 class="font-semibold text-ink">Recent Sales</h3>
                        <a href="{{ route('sales.index') }}" class="text-xs text-accent hover:text-accent-hover font-medium hover:underline">View all</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-ink-soft bg-line-soft">
                                <th class="py-2.5 px-5 font-medium">Invoice</th>
                                <th class="font-medium">Customer</th>
                                <th class="font-medium">Type</th>
                                <th class="text-right px-5 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentSales as $sale)
                                <tr class="border-b border-line last:border-0 hover:bg-line-soft/60 transition-colors">
                                    <td class="py-3 px-5"><a href="{{ route('sales.show', $sale) }}" class="text-accent hover:underline font-medium">{{ $sale->invoice_number }}</a></td>
                                    <td class="text-ink">{{ $sale->customer->name ?? 'Walk-in' }}</td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $sale->paymentTypeBadgeClasses() }}">
                                            {{ $sale->paymentTypeLabel() }}
                                        </span>
                                    </td>
                                    <td class="text-right px-5 tabular text-ink">Rs. {{ number_format($sale->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-8 text-center text-ink-soft">No sales yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="bg-surface border border-line shadow-sm rounded-xl overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                        <h3 class="font-semibold text-ink">Low Stock Alerts</h3>
                        <a href="{{ route('reports.low-stock') }}" class="text-xs text-accent hover:text-accent-hover font-medium hover:underline">View all</a>
                    </div>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-ink-soft bg-line-soft">
                                <th class="py-2.5 px-5 font-medium">Product</th>
                                <th class="text-right font-medium">Stock</th>
                                <th class="text-right px-5 font-medium">Low Stock Alert</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($lowStockProducts as $product)
                                <tr class="border-b border-line last:border-0 hover:bg-line-soft/60 transition-colors">
                                    <td class="py-3 px-5 text-ink">{{ $product->name }}</td>
                                    <td class="text-right text-critical font-semibold tabular">{{ $product->stock_quantity }}</td>
                                    <td class="text-right px-5 tabular text-ink-soft">{{ $product->low_stock_alert }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="3" class="py-8 text-center text-ink-soft">All stock levels healthy.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
