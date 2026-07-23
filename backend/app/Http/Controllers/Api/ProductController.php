<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with('category', 'supplier')->orderBy('name')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sku' => ['required', 'string', 'max:100', 'unique:products,sku'],
            'item_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['required', 'exists:categories,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'low_stock_alert' => ['nullable', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        return response()->json(Product::create($data), 201);
    }

    public function show(Product $product)
    {
        return $product->load('category', 'supplier');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate([
            'sku' => ['sometimes', 'string', 'max:100', 'unique:products,sku,' . $product->id],
            'item_code' => ['nullable', 'string', 'max:100'],
            'barcode' => ['nullable', 'string', 'max:100'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'supplier_id' => ['sometimes', 'exists:suppliers,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:50'],
            'cost_price' => ['sometimes', 'numeric', 'min:0'],
            'selling_price' => ['sometimes', 'numeric', 'min:0'],
            'discount' => ['sometimes', 'numeric', 'min:0'],
            'low_stock_alert' => ['sometimes', 'integer', 'min:0'],
            'expiry_date' => ['nullable', 'date'],
            'is_active' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'max:4096'],
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }
        unset($data['image']);

        $product->update($data);

        return $product;
    }

    public function destroy(Product $product)
    {
        if ($product->image_path) {
            Storage::disk('public')->delete($product->image_path);
        }
        $product->delete();

        return response()->noContent();
    }
}
