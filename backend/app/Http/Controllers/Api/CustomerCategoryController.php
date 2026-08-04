<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerCategory;

class CustomerCategoryController extends Controller
{
    /**
     * Read-only from mobile — categories are created/managed by the admin
     * in the web portal, POS billing only needs the list to filter by.
     */
    public function index()
    {
        return CustomerCategory::where('is_active', true)->orderBy('name')->get();
    }
}
