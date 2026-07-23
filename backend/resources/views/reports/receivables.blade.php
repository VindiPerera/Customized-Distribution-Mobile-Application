<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Receivables / Aging Report</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <div class="grid grid-cols-4 gap-4">
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5 text-center">
                    <div class="text-xs text-ink-soft">0-30 days</div>
                    <div class="text-xl font-bold text-ink">Rs. {{ number_format($buckets['0_30'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5 text-center">
                    <div class="text-xs text-ink-soft">31-60 days</div>
                    <div class="text-xl font-bold text-ink">Rs. {{ number_format($buckets['31_60'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5 text-center">
                    <div class="text-xs text-ink-soft">61-90 days</div>
                    <div class="text-xl font-bold text-ink">Rs. {{ number_format($buckets['61_90'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5 text-center">
                    <div class="text-xs text-ink-soft">90+ days</div>
                    <div class="text-xl font-bold text-critical">Rs. {{ number_format($buckets['over_90'], 2) }}</div>
                </div>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Customer</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3 text-right">Credit Limit</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            @php $overLimit = $customer->current_balance > $customer->credit_limit; @endphp
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-right {{ $overLimit ? 'text-critical font-semibold' : '' }}">
                                    Rs. {{ number_format($customer->current_balance, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-ink-soft">Rs. {{ number_format($customer->credit_limit, 2) }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('customers.show', $customer) }}" class="text-accent hover:underline">View</a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-ink-soft">No outstanding balances.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
