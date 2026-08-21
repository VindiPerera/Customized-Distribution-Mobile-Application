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
            ->with('parent')
            ->withCount(['customers', 'children'])
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customer-categories.index', compact('categories'));
    }

    public function create()
    {
        // Only top-level categories can be a parent — subcategories don't
        // nest further, so a category that already has a parent is never
        // offered as one itself.
        $parentOptions = CustomerCategory::whereNull('parent_id')->orderBy('name')->get();

        return view('customer-categories.create', compact('parentOptions'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:customer_categories,id'],
        ]);

        CustomerCategory::create($data);

        return redirect()->route('customer-categories.index')->with('status', 'Category created.');
    }

    public function edit(CustomerCategory $customerCategory)
    {
        $customerCategory->loadCount('children');

        $parentOptions = CustomerCategory::whereNull('parent_id')
            ->where('id', '!=', $customerCategory->id)
            ->orderBy('name')
            ->get();

        return view('customer-categories.edit', ['category' => $customerCategory, 'parentOptions' => $parentOptions]);
    }

    public function update(Request $request, CustomerCategory $customerCategory)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'exists:customer_categories,id', 'not_in:' . $customerCategory->id],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        // A category with its own subcategories can't become a subcategory
        // itself — that would make a three-level chain, which the rest of
        // the app (parent/subcategory picker) doesn't support.
        if (! empty($data['parent_id']) && $customerCategory->children()->exists()) {
            return back()->withErrors(['parent_id' => 'This category has subcategories of its own and cannot be made a subcategory.'])->withInput();
        }

        $customerCategory->update($data);

        return redirect()->route('customer-categories.index')->with('status', 'Category updated.');
    }

    public function destroy(CustomerCategory $customerCategory)
    {
        if ($customerCategory->customers()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has customers assigned to it.']);
        }

        if ($customerCategory->children()->exists()) {
            return back()->withErrors(['category' => 'Cannot delete a category that has subcategories. Delete or reassign them first.']);
        }

        $customerCategory->delete();

        return redirect()->route('customer-categories.index')->with('status', 'Category removed.');
    }
}
