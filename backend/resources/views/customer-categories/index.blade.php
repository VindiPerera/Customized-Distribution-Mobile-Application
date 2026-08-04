<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Customer Categories</h2>
            <a href="{{ route('customer-categories.create') }}" class="px-4 py-2 bg-accent text-white text-sm rounded-md hover:bg-accent-hover">+ New Category</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-4">

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
                            <th class="px-4 py-3">Description</th>
                            <th class="px-4 py-3 text-right">Customers</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr class="border-t border-line">
                                <td class="px-4 py-3 font-medium text-ink">{{ $category->name }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $category->description ?? '—' }}</td>
                                <td class="px-4 py-3 text-right text-ink-soft">{{ $category->customers_count }}</td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('customer-categories.edit', $category) }}" class="text-accent hover:underline">Edit</a>
                                    @if ($category->customers_count === 0)
                                        <form method="POST" action="{{ route('customer-categories.destroy', $category) }}" class="inline" onsubmit="return confirm('Delete this category?')">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-critical hover:underline ml-3">Delete</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-8 text-center text-ink-soft">No categories found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $categories->links() }}
        </div>
    </div>
</x-app-layout>
