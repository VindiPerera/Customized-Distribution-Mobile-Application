<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">{{ $customer->name }}</h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('customers.edit', $customer) }}" class="text-sm text-accent hover:underline">Edit</a>
                <form method="POST" action="{{ route('customers.destroy', $customer) }}" onsubmit="return confirm('Delete this customer? This cannot be undone.')">
                    @csrf
                    @method('DELETE')
                    <button class="text-sm text-critical hover:underline">Delete</button>
                </form>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="bg-good-soft text-good text-sm px-4 py-2 rounded">{{ session('status') }}</div>
            @endif
            @if ($errors->has('customer'))
                <div class="bg-critical-soft text-critical text-sm px-4 py-2 rounded">{{ $errors->first('customer') }}</div>
            @endif

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Current Balance</div>
                    <div class="text-2xl font-bold {{ $customer->current_balance > $customer->credit_limit ? 'text-critical' : 'text-ink' }}">
                        Rs. {{ number_format($customer->current_balance, 2) }}
                    </div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Credit Limit</div>
                    <div class="text-2xl font-bold text-ink">Rs. {{ number_format($customer->credit_limit, 2) }}</div>
                </div>
                <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                    <div class="text-sm text-ink-soft">Available Credit</div>
                    <div class="text-2xl font-bold text-good">Rs. {{ number_format($customer->availableCredit(), 2) }}</div>
                </div>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg p-5">
                <h3 class="font-semibold text-ink mb-3">Aging Summary (Outstanding Credit Sales)</h3>
                <div class="grid grid-cols-4 gap-4 text-center">
                    <div>
                        <div class="text-xs text-ink-soft">0-30 days</div>
                        <div class="font-semibold">Rs. {{ number_format($aging['0_30'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-ink-soft">31-60 days</div>
                        <div class="font-semibold">Rs. {{ number_format($aging['31_60'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-ink-soft">61-90 days</div>
                        <div class="font-semibold">Rs. {{ number_format($aging['61_90'], 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs text-ink-soft">90+ days</div>
                        <div class="font-semibold text-critical">Rs. {{ number_format($aging['over_90'], 2) }}</div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2 bg-surface border border-line shadow-sm rounded-lg p-5">
                    <h3 class="font-semibold text-ink mb-3">Statement / Ledger</h3>
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-ink-soft border-b border-line">
                                <th class="py-2">Date</th>
                                <th>Type</th>
                                <th class="text-right">Amount</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($ledger as $entry)
                                <tr class="border-b border-line last:border-0">
                                    <td class="py-2 text-ink-soft">{{ $entry->created_at->format('Y-m-d H:i') }}</td>
                                    <td>
                                        <span class="px-2 py-0.5 rounded text-xs {{ $entry->type === 'sale' ? 'bg-warn-soft text-warn' : 'bg-good-soft text-good' }}">
                                            {{ ucfirst($entry->type) }}
                                        </span>
                                    </td>
                                    <td class="text-right {{ $entry->amount >= 0 ? 'text-ink' : 'text-good' }}">
                                        {{ $entry->amount >= 0 ? '+' : '' }}{{ number_format($entry->amount, 2) }}
                                    </td>
                                    <td class="text-right font-medium">Rs. {{ number_format($entry->balance_after, 2) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="py-6 text-center text-ink-soft">No transactions yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="mt-3">{{ $ledger->links() }}</div>
                </div>

                <div class="bg-surface border border-line shadow-sm rounded-lg p-5 h-fit">
                    <h3 class="font-semibold text-ink mb-3">Record Payment</h3>
                    <form method="POST" action="{{ route('customers.payments.store', $customer) }}" class="space-y-3">
                        @csrf
                        <div>
                            <x-input-label for="amount" value="Amount (Rs.)" />
                            <x-text-input id="amount" type="number" step="0.01" min="0.01" name="amount" class="mt-1 block w-full" required />
                        </div>
                        <div>
                            <x-input-label for="method" value="Method" />
                            <select id="method" name="method" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="reference_no" value="Reference No. (optional)" />
                            <x-text-input id="reference_no" name="reference_no" class="mt-1 block w-full" />
                        </div>
                        @if ($errors->hasAny(['amount', 'method', 'reference_no', 'notes']))
                            <div class="text-critical text-sm">{{ $errors->first() }}</div>
                        @endif
                        <x-primary-button class="w-full justify-center">Save Payment</x-primary-button>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
