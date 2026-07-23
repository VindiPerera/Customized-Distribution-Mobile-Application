<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-display text-xl font-semibold text-ink leading-tight">Products</h2>
            <a href="{{ route('products.create') }}" class="px-4 py-2 bg-accent text-white text-sm rounded-md hover:bg-accent-hover">+ New Product</a>
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

            <div class="flex justify-between items-center gap-4 flex-wrap">
                <form method="GET" class="flex gap-2">
                    @if (request('filter'))
                        <input type="hidden" name="filter" value="{{ request('filter') }}">
                    @endif
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, SKU, item code, or barcode..."
                           class="border-line rounded-md shadow-sm text-sm w-80 focus:border-accent focus:ring-accent">
                    <button class="px-3 py-1.5 bg-line-soft text-ink text-sm rounded-md hover:bg-line">Search</button>
                </form>

                <div class="flex gap-2 text-sm">
                    <a href="{{ route('products.index', array_filter(['search' => request('search')])) }}"
                       class="px-3 py-1.5 rounded-md {{ !request('filter') ? 'bg-accent text-white' : 'bg-line-soft text-ink hover:bg-line' }}">All</a>
                    <a href="{{ route('products.index', array_filter(['search' => request('search'), 'filter' => 'low_stock'])) }}"
                       class="px-3 py-1.5 rounded-md {{ request('filter') === 'low_stock' ? 'bg-critical text-white' : 'bg-critical-soft text-critical hover:bg-critical/20' }}">Low Stock</a>
                    <a href="{{ route('products.index', array_filter(['search' => request('search'), 'filter' => 'expired'])) }}"
                       class="px-3 py-1.5 rounded-md {{ request('filter') === 'expired' ? 'bg-warn text-white' : 'bg-warn-soft text-warn hover:bg-warn/20' }}">Expired</a>
                </div>
            </div>

            <div class="bg-surface border border-line shadow-sm rounded-lg overflow-hidden">
                <table class="w-full text-sm">
                    <thead class="bg-line-soft">
                        <tr class="text-left text-ink-soft">
                            <th class="px-4 py-3 w-14"></th>
                            <th class="px-4 py-3">SKU</th>
                            <th class="px-4 py-3">Name</th>
                            <th class="px-4 py-3">Category</th>
                            <th class="px-4 py-3">Supplier</th>
                            <th class="px-4 py-3 text-right">Price</th>
                            <th class="px-4 py-3 text-right">Stock</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($products as $product)
                            @php
                                $lowStock = $product->isLowStock();
                                $expired = $product->expiry_date && $product->expiry_date->isPast();
                            @endphp
                            <tr class="border-t border-line {{ $expired ? 'bg-critical-soft/40' : ($lowStock ? 'bg-warn-soft/40' : '') }}">
                                <td class="px-4 py-3">
                                    @if ($product->image_url)
                                        <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="w-9 h-9 rounded-md object-cover border border-line">
                                    @else
                                        <div class="w-9 h-9 rounded-md bg-line-soft border border-line flex items-center justify-center text-ink-soft">
                                            <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3.5 8L12 3.5 20.5 8v8L12 20.5 3.5 16V8z" stroke-linejoin="round"/></svg>
                                        </div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $product->sku }}</td>
                                <td class="px-4 py-3 font-medium text-ink">
                                    {{ $product->name }}
                                    @if ($expired)
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[0.65rem] bg-critical text-white align-middle">Expired</span>
                                    @elseif ($lowStock)
                                        <span class="ml-1 px-1.5 py-0.5 rounded text-[0.65rem] bg-warn text-white align-middle">Low Stock</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-ink-soft">{{ $product->category->name }}</td>
                                <td class="px-4 py-3 text-ink-soft">{{ $product->supplier->name }}</td>
                                <td class="px-4 py-3 text-right">Rs. {{ number_format($product->selling_price, 2) }}</td>
                                <td class="px-4 py-3 text-right {{ $lowStock ? 'text-critical font-semibold' : '' }}">
                                    {{ $product->stock_quantity }} {{ $product->unit }}
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('products.edit', $product) }}" class="text-accent hover:underline">Edit</a>
                                    <form method="POST" action="{{ route('products.destroy', $product) }}" class="inline" onsubmit="return confirm('Delete this product? This cannot be undone.')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-critical hover:underline ml-3">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-ink-soft">No products found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{ $products->links() }}
        </div>
    </div>
</x-app-layout>
