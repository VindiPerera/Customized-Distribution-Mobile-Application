<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Stock Adjustment</h2>
    </x-slot>

    <div class="py-8"
         x-data="{
            productId: '{{ old('product_id', $selectedProduct?->id) }}',
            direction: '{{ old('direction', 'in') }}',
            products: {{ $products->map(fn ($p) => ['id' => $p->id, 'name' => $p->name, 'sku' => $p->sku, 'unit' => $p->unit, 'stock' => $p->stock_quantity])->values()->toJson() }},
            get selected() { return this.products.find(p => p.id == this.productId) ?? null; },
         }">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('stock-adjustments.store') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="product_id" value="Product" required />
                    <select id="product_id" name="product_id" x-model="productId" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent" required>
                        <option value="">Select product...</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" {{ (string) old('product_id', $selectedProduct?->id) === (string) $product->id ? 'selected' : '' }}>
                                {{ $product->name }} ({{ $product->sku }})
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('product_id')" class="mt-1" />
                    <p class="text-xs text-ink-soft mt-1.5" x-show="selected" x-text="selected ? 'Current stock: ' + selected.stock + ' ' + selected.unit : ''"></p>
                </div>

                <div>
                    <x-input-label value="Direction" required />
                    <div class="mt-1 flex gap-2">
                        <label class="flex-1 flex items-center justify-center gap-1.5 border rounded-md px-3 py-2.5 cursor-pointer text-sm font-medium"
                               :class="direction === 'in' ? 'border-good bg-good-soft text-good' : 'border-line text-ink-soft'">
                            <input type="radio" name="direction" value="in" x-model="direction" class="sr-only">
                            Stock In
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-1.5 border rounded-md px-3 py-2.5 cursor-pointer text-sm font-medium"
                               :class="direction === 'out' ? 'border-critical bg-critical-soft text-critical' : 'border-line text-ink-soft'">
                            <input type="radio" name="direction" value="out" x-model="direction" class="sr-only">
                            Stock Out
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('direction')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="quantity" value="Quantity" required />
                    <x-text-input id="quantity" type="number" min="1" name="quantity" value="{{ old('quantity') }}" class="mt-1 block w-full" required autofocus />
                    <x-input-error :messages="$errors->get('quantity')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="notes" value="Reason (optional)" />
                    <x-text-input id="notes" name="notes" value="{{ old('notes') }}" class="mt-1 block w-full" placeholder="e.g. Purchase received, damaged goods, stock count correction" />
                    <x-input-error :messages="$errors->get('notes')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('stock-adjustments.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Save Adjustment</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
