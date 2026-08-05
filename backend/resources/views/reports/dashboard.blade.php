<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4 flex-wrap">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Reports Dashboard</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('reports.dashboard.export-pdf', request()->query()) }}"
                   class="px-3 py-1.5 bg-line-soft text-ink text-sm rounded-md hover:bg-line">
                    Download PDF
                </a>
                <a href="{{ route('reports.dashboard.export-excel', request()->query()) }}"
                   class="px-3 py-1.5 bg-line-soft text-ink text-sm rounded-md hover:bg-line">
                    Download Excel
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6" x-data="{ preset: '{{ $preset }}' }">

            <form method="GET" class="flex gap-2 items-end flex-wrap bg-surface border border-line shadow-sm rounded-lg p-4">
                <div>
                    <x-input-label for="preset" value="Period" />
                    <select id="preset" name="preset" x-model="preset" class="mt-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                        <option value="today">Today</option>
                        <option value="week">This Week</option>
                        <option value="month">This Month</option>
                        <option value="year">This Year</option>
                        <option value="custom">Custom</option>
                    </select>
                </div>
                <div x-show="preset === 'custom'">
                    <x-input-label for="from" value="From" />
                    <input type="date" id="from" name="from" value="{{ $range['from']->format('Y-m-d') }}" class="mt-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                </div>
                <div x-show="preset === 'custom'">
                    <x-input-label for="to" value="To" />
                    <input type="date" id="to" name="to" value="{{ $range['to']->format('Y-m-d') }}" class="mt-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                </div>
                <button class="px-3 py-1.5 bg-accent text-white text-sm rounded-md hover:bg-accent-hover">Filter</button>
                <span class="text-xs text-ink-soft self-center">
                    Showing {{ $range['from']->format('Y-m-d') }} to {{ $range['to']->format('Y-m-d') }}
                </span>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-3 lg:grid-cols-6 gap-4">
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Total Sales</div>
                    <div class="text-xl font-bold text-ink">Rs. {{ number_format($cards['total_sales'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Transactions</div>
                    <div class="text-xl font-bold text-ink">{{ $cards['transaction_count'] }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Credit Outstanding</div>
                    <div class="text-xl font-bold text-warn">Rs. {{ number_format($cards['credit_outstanding'], 2) }}</div>
                    <div class="text-xs text-ink-soft mt-0.5">as of today</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Top Product</div>
                    @if ($cards['top_product'])
                        <div class="text-base font-bold text-ink truncate" title="{{ $cards['top_product']['name'] }}">{{ $cards['top_product']['name'] }}</div>
                        <div class="text-xs text-ink-soft mt-0.5">{{ $cards['top_product']['qty_sold'] }} units sold</div>
                    @else
                        <div class="text-base font-bold text-ink-soft">N/A</div>
                    @endif
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Low Stock</div>
                    <div class="text-xl font-bold {{ $cards['low_stock_count'] > 0 ? 'text-critical' : 'text-ink' }}">{{ $cards['low_stock_count'] }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Active Suppliers</div>
                    <div class="text-xl font-bold text-ink">{{ $cards['supplier_count'] }}</div>
                </div>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <h3 class="font-semibold text-ink text-sm px-4 pt-4">Payment Method Breakdown</h3>
                <table class="w-full text-sm mt-3">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Method</th>
                            <th class="px-4 py-3 text-right">No. of Sales</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($paymentBreakdown as $row)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ \Illuminate\Support\Str::headline($row['method']) }}</td>
                                <td class="px-4 py-3 text-right">{{ $row['count'] }}</td>
                                <td class="px-4 py-3 text-right font-medium">Rs. {{ number_format($row['total'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-ink-soft">No sales in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <h3 class="font-semibold text-ink text-sm px-4 pt-4">Sales</h3>
                <table class="w-full text-sm mt-3">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Invoice</th>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales as $sale)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3"><a href="{{ route('sales.show', $sale['id']) }}" class="text-accent hover:underline">{{ $sale['invoice_number'] }}</a></td>
                                <td class="px-4 py-3 text-ink-soft">{{ $sale['sale_date']->format('Y-m-d H:i') }}</td>
                                <td class="px-4 py-3">{{ $sale['customer_name'] }}</td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-xs {{ $sale['badge_classes'] }}">
                                        {{ $sale['payment_type_label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right font-medium">Rs. {{ number_format($sale['total_amount'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-ink-soft">No sales in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <h3 class="font-semibold text-ink text-sm px-4 pt-4">Top Selling Products</h3>
                <table class="w-full text-sm mt-3">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Product</th>
                            <th class="px-4 py-3 text-right">Qty Sold</th>
                            <th class="px-4 py-3 text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($topProducts as $product)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 text-ink-soft">{{ $product['sku'] }}</td>
                                <td class="px-4 py-3 font-medium text-ink">{{ $product['name'] }}</td>
                                <td class="px-4 py-3 text-right">{{ $product['qty_sold'] }}</td>
                                <td class="px-4 py-3 text-right font-medium">Rs. {{ number_format($product['revenue'], 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-ink-soft">No sales in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <h3 class="font-semibold text-ink text-sm px-4 pt-4">Customer Credit &amp; Remaining Balances</h3>
                <table class="w-full text-sm mt-3">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3 text-right">Credit Limit</th>
                            <th class="px-4 py-3 text-right">Current Balance</th>
                            <th class="px-4 py-3 text-right">Available Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customerBalances as $customer)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $customer['name'] }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $customer['phone'] }}</td>
                                <td class="px-4 py-3 text-right text-ink-soft">Rs. {{ number_format($customer['credit_limit'], 2) }}</td>
                                <td class="px-4 py-3 text-right text-warn font-semibold">Rs. {{ number_format($customer['current_balance'], 2) }}</td>
                                <td class="px-4 py-3 text-right {{ $customer['available_credit'] < 0 ? 'text-critical font-semibold' : 'text-ink-soft' }}">
                                    Rs. {{ number_format($customer['available_credit'], 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-ink-soft">No outstanding customer balances.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <h3 class="font-semibold text-ink text-sm px-4 pt-4">Supplier Stock &amp; Purchase Activity</h3>
                <p class="text-xs text-ink-soft px-4">Units received reflects purchase stock movements in the selected period; stock value is a current snapshot.</p>
                <table class="w-full text-sm mt-3">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-right">Current Stock Value</th>
                            <th class="px-4 py-3 text-right">Units Received (Period)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($supplierStock as $supplier)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $supplier['name'] }}</td>
                                <td class="px-4 py-3 text-right">Rs. {{ number_format($supplier['stock_value'], 2) }}</td>
                                <td class="px-4 py-3 text-right">{{ $supplier['units_received'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-4 py-8 text-center text-ink-soft">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
