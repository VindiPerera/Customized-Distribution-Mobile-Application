<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Sales Summary</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <form method="GET" class="flex gap-2 items-end">
                <div>
                    <x-input-label for="from" value="From" />
                    <input type="date" id="from" name="from" value="{{ $from->format('Y-m-d') }}" class="mt-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                </div>
                <div>
                    <x-input-label for="to" value="To" />
                    <input type="date" id="to" name="to" value="{{ $to->format('Y-m-d') }}" class="mt-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                </div>
                <button class="px-3 py-1.5 bg-line-soft text-ink text-sm rounded-md hover:bg-line">Filter</button>
            </form>

            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Total Sales</div>
                    <div class="text-xl font-bold text-ink">Rs. {{ number_format($summary['total'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Paid</div>
                    <div class="text-xl font-bold text-good">Rs. {{ number_format($summary['paid_total'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Credit</div>
                    <div class="text-xl font-bold text-warn">Rs. {{ number_format($summary['credit_total'], 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Transactions</div>
                    <div class="text-xl font-bold text-ink">{{ $summary['count'] }}</div>
                </div>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($byDay as $day => $amount)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3">{{ $day }}</td>
                                <td class="px-4 py-3 text-right font-medium">Rs. {{ number_format($amount, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="px-4 py-8 text-center text-ink-soft">No sales in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
