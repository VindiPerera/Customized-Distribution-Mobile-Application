<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerCategory;

class CustomerCategoryController extends Controller
{
    /**
     * Read-only from mobile — categories are created/managed by the admin
     * in the web portal. Returns top-level categories with their
     * subcategories nested under `children`, so the mobile app can offer
     * the same category → subcategory picker as the web portal instead of
     * a flat list.
     */
    public function index()
    {
        return CustomerCategory::where('is_active', true)
            ->whereNull('parent_id')
            ->with(['children' => fn ($q) => $q->where('is_active', true)->orderBy('name')])
            ->orderBy('name')
            ->get();
    }
}
