<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->with('category', 'supplier')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%")
                ->orWhere('sku', 'like', "%{$request->search}%")
                ->orWhere('item_code', 'like', "%{$request->search}%")
                ->orWhere('barcode', 'like', "%{$request->search}%"))
            ->when($request->filter === 'low_stock', fn ($q) => $q->whereColumn('stock_quantity', '<=', 'low_stock_alert'))
            ->when($request->filter === 'expired', fn ($q) => $q->whereNotNull('expiry_date')->where('expiry_date', '<', now()))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('products.create', compact('categories', 'suppliers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'item_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'stock_quantity' => ['required', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        $data['sku'] = $data['sku'] ?: $this->generateSku();
        $data['supplier_id'] = $data['supplier_id'] ?: $this->unspecifiedSupplierId();

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $product = Product::create($data);

        if (($data['stock_quantity'] ?? 0) > 0) {
            $product->stockMovements()->create([
                'type' => 'purchase_in',
                'quantity' => $product->stock_quantity,
                'quantity_after' => $product->stock_quantity,
                'notes' => 'Initial stock on product creation',
                'user_id' => $request->user()->id,
            ]);
        }

        return redirect()->route('products.index')->with('status', 'Product created.');
    }

    public function edit(Product $product)
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        $suppliers = Supplier::where('is_active', true)->orderBy('name')->get();

        return view('products.edit', compact('product', 'categories', 'suppliers'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => ['nullable', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'item_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['sku'] = $data['sku'] ?: $this->generateSku();
        $data['supplier_id'] = $data['supplier_id'] ?: $this->unspecifiedSupplierId();

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        } elseif ($request->boolean('remove_image') && $product->image_path) {
            Storage::disk('public')->delete($product->image_path);
            $data['image_path'] = null;
        }
        unset($data['image'], $data['remove_image']);

        $product->update($data);

        return redirect()->route('products.index')->with('status', 'Product updated.');
    }

    public function destroy(Product $product)
    {
        if ($product->saleItems()->exists()) {
            return back()->withErrors(['product' => 'Cannot delete a product that has sale history. Deactivate it instead.']);
        }

        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }

        $product->delete();

        return redirect()->route('products.index')->with('status', 'Product removed.');
    }

    /**
     * SKU is optional on the form but must exist and be unique in the
     * database — generate one when the admin doesn't provide it.
     */
    private function generateSku(): string
    {
        do {
            $sku = 'SKU-' . strtoupper(Str::random(8));
        } while (Product::where('sku', $sku)->exists());

        return $sku;
    }

    /**
     * Supplier is optional on the form — fall back to the same
     * "Unspecified" supplier used to backfill pre-existing products.
     */
    private function unspecifiedSupplierId(): int
    {
        return Supplier::firstOrCreate(
            ['name' => 'Unspecified'],
            ['is_active' => true],
        )->id;
    }
}
