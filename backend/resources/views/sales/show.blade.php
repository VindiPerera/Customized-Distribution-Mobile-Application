<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center print:hidden">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Invoice {{ $sale->invoice_number }}</h2>
            <button onclick="window.print()" class="px-4 py-2 bg-line-soft text-ink text-sm rounded-md hover:bg-line">Print</button>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-line shadow-sm rounded-lg p-8">

                <div class="flex justify-between items-start mb-6">
                    <div>
                        <h1 class="text-lg font-bold text-ink">{{ $shopName ?? config('app.name') }}</h1>
                        <p class="text-sm text-ink-soft">Invoice #{{ $sale->invoice_number }}</p>
                        <p class="text-sm text-ink-soft">{{ $sale->sale_date->format('Y-m-d H:i') }}</p>
                    </div>
                    <div class="text-right">
                        <span class="px-3 py-1 rounded text-sm font-medium {{ $sale->paymentTypeBadgeClasses() }}">
                            {{ $sale->paymentTypeLabel() }} Sale
                        </span>
                        @if ($sale->payment_reference)
                            <p class="text-sm font-medium text-ink mt-2">Ref: {{ $sale->payment_reference }}</p>
                        @endif
                        <p class="text-sm text-ink-soft mt-1">Sold by: {{ $sale->user->name }}</p>
                    </div>
                </div>

                <div class="mb-6">
                    <div class="text-sm text-ink-soft">Bill To</div>
                    <div class="font-medium text-ink">{{ $sale->customer->name ?? 'Walk-in Customer' }}</div>
                    @if ($sale->customer?->phone)
                        <div class="text-sm text-ink-soft">{{ $sale->customer->phone }}</div>
                    @endif
                </div>

                <table class="w-full text-sm mb-6">
                    <thead>
                        <tr class="text-left text-ink-soft border-b border-line">
                            <th class="py-2">Product</th>
                            <th class="text-right">Discounted Price</th>
                            <th class="text-right">Qty</th>
                            <th class="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr class="border-b border-line last:border-0">
                                <td class="py-2">
                                    {{ $item->product->name }}
                                    <div class="text-xs text-ink-soft">
                                        Rs. {{ number_format($item->unit_price, 2) }} each
                                        @if ($item->discount_type === 'amount' && $item->discount_amount > 0)
                                            &middot; Rs. {{ number_format($item->discount_amount, 2) }} off
                                        @elseif ($item->discount_percent > 0)
                                            &middot; {{ rtrim(rtrim(number_format($item->discount_percent, 2), '0'), '.') }}% off
                                        @endif
                                    </div>
                                </td>
                                <td class="text-right">Rs. {{ number_format($item->discounted_price, 2) }}</td>
                                <td class="text-right">{{ $item->quantity }}</td>
                                <td class="text-right">Rs. {{ number_format($item->line_total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="flex justify-end">
                    <div class="w-64 space-y-1 text-sm">
                        <div class="flex justify-between text-lg font-bold border-t border-line pt-1">
                            <span>Total</span>
                            <span>Rs. {{ number_format($sale->total_amount, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if ($sale->notes)
                    <div class="mt-6 text-sm text-ink-soft">
                        <div class="font-medium text-ink">Notes</div>
                        {{ $sale->notes }}
                    </div>
                @endif

            </div>
        </div>
    </div>
</x-app-layout>
