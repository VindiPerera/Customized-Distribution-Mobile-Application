<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\CustomerCategory;
use Illuminate\Http\Request;

class CustomerCategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = CustomerCategory::query()
            ->withCount('customers')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customer-categories.index', compact('categories'));
    }

    public function create()
    {
        return view('customer-categories.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        CustomerCategory::create($data);

        return redirect()->route('customer-categories.index')->with('status', 'Category created.');
    }

    public function edit(CustomerCategory $customerCategory)
    {
        return view('customer-categories.edit', ['category' => $customerCategory]);
    }

    public function update(Request $request, CustomerCategory $customerCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $customerCategory->update($data);

        return redirect()->route('customer-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(CustomerCategory $customerCategory)
    {
        if ($customerCategory->customers()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has customers assigned to it.']);
        }

        $customerCategory->delete();

        return redirect()->route('customer-categories.index')->with('status', 'Category removed.');
    }
}
