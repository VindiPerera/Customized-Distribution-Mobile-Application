<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Suppliers</h2>
            <a href="{{ route('suppliers.create') }}" class="px-4 py-2 bg-accent text-white text-sm rounded-md hover:bg-accent-hover">+ New Supplier</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                            <th class="px-4 py-3">Contact Person</th>
                            <th class="px-4 py-3">Phone</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3 text-right">Products</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($suppliers as $supplier)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $supplier->name }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $supplier->contact_person ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $supplier->phone ?? '—' }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $supplier->email ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-ink-soft">{{ $supplier->products_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('suppliers.edit', $supplier) }}" class="text-accent hover:underline">Edit</a>
                                    @if ($supplier->products_count === 0)
                                        <form method="POST" action="{{ route('suppliers.destroy', $supplier) }}" class="inline" onsubmit="return confirm('Delete this supplier?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-critical hover:underline ml-3">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-ink-soft">No suppliers found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $suppliers->links() }}
        </div>
    </div>
</x-app-layout>
