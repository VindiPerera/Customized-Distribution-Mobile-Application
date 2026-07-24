<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Customers</h2>
            <a href="{{ route('customers.create') }}" class="px-4 py-2 bg-accent text-white text-sm rounded-md hover:bg-accent-hover">+ New Customer</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @if (session('status'))
                <div class="bg-good-soft text-good text-sm px-4 py-2 rounded">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="bg-critical-soft text-critical text-sm px-4 py-2 rounded">{{ $errors->first() }}</div>
            @endif

            <form method="GET" class="flex gap-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name..."
                       class="border-line rounded-md shadow-sm text-sm w-64 focus:border-accent focus:ring-accent">
                <button class="px-3 py-1.5 bg-line-soft text-ink text-sm rounded-md hover:bg-line">Search</button>
            </form>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3 text-right">Balance</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customers as $customer)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $customer->name }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $customer->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-right {{ $customer->current_balance > 0 ? 'text-warn font-semibold' : '' }}">
                                    Rs. {{ number_format($customer->current_balance, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('customers.show', $customer) }}" class="text-accent hover:underline">View</a>
                                    <form method="POST" action="{{ route('customers.destroy', $customer) }}" class="inline" onsubmit="return confirm('Delete this customer? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-critical hover:underline ml-3">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-8 text-center text-ink-soft">No customers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>
