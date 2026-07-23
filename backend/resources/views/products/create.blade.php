<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">New Product</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data" class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">
                @csrf

                <div>
                    <x-input-label for="image" value="Product Image" />
                    <input type="file" id="image" name="image" accept="image/*" class="mt-1 block w-full text-sm text-ink-soft file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:bg-accent-soft file:text-accent file:text-sm file:font-medium">
                    <x-input-error :messages="$errors->get('image')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="sku" value="SKU" />
                        <x-text-input id="sku" name="sku" value="{{ old('sku') }}" class="mt-1 block w-full" required autofocus />
                        <x-input-error :messages="$errors->get('sku')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="unit" value="Unit" />
                        <x-text-input id="unit" name="unit" value="{{ old('unit', 'pcs') }}" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="item_code" value="Item Code" />
                        <x-text-input id="item_code" name="item_code" value="{{ old('item_code') }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('item_code')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="barcode" value="Barcode" />
                        <x-text-input id="barcode" name="barcode" value="{{ old('barcode') }}" class="mt-1 block w-full" />
                        <x-input-error :messages="$errors->get('barcode')" class="mt-1" />
                    </div>
                </div>

                <div>
                    <x-input-label for="name" value="Name" />
                    <x-text-input id="name" name="name" value="{{ old('name') }}" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-1" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="category_id" value="Category" />
                        <select id="category_id" name="category_id" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent" required>
                            <option value="">Select category...</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ (string) old('category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="supplier_id" value="Supplier" />
                        <select id="supplier_id" name="supplier_id" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm bg-surface text-ink focus:border-accent focus:ring-accent" required>
                            <option value="">Select supplier...</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ (string) old('supplier_id') === (string) $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-1" />
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <x-input-label for="cost_price" value="Cost Price (Rs.)" />
                        <x-text-input id="cost_price" type="number" step="0.01" min="0" name="cost_price" value="{{ old('cost_price', 0) }}" class="mt-1 block w-full" />
                    </div>
                    <div>
                        <x-input-label for="selling_price" value="Selling Price (Rs.)" />
                        <x-text-input id="selling_price" type="number" step="0.01" min="0" name="selling_price" value="{{ old('selling_price') }}" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('selling_price')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="discount" value="Discount (Rs.)" />
                        <x-text-input id="discount" type="number" step="0.01" min="0" name="discount" value="{{ old('discount', 0) }}" class="mt-1 block w-full" />
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="stock_quantity" value="Opening Stock" />
                        <x-text-input id="stock_quantity" type="number" min="0" name="stock_quantity" value="{{ old('stock_quantity', 0) }}" class="mt-1 block w-full" required />
                        <x-input-error :messages="$errors->get('stock_quantity')" class="mt-1" />
                    </div>
                    <div>
                        <x-input-label for="low_stock_alert" value="Low Stock Alert" />
                        <x-text-input id="low_stock_alert" type="number" min="0" name="low_stock_alert" value="{{ old('low_stock_alert', 10) }}" class="mt-1 block w-full" />
                    </div>
                </div>

                <div>
                    <x-input-label for="expiry_date" value="Expiry Date (optional)" />
                    <x-text-input id="expiry_date" type="date" name="expiry_date" value="{{ old('expiry_date') }}" class="mt-1 block w-full" />
                    <x-input-error :messages="$errors->get('expiry_date')" class="mt-1" />
                </div>

                <div class="flex justify-end gap-2">
                    <a href="{{ route('products.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                    <x-primary-button>Create Product</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
