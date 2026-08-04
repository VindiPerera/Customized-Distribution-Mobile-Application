<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\CreditService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    public function index(Request $request)
    {
        return Customer::with('category')
            ->when($request->category_id, fn ($q) => $q->where('customer_category_id', $request->category_id))
            ->orderBy('name')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'customer_category_id' => ['nullable', 'exists:customer_categories,id'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json(Customer::create($data)->load('category'), 201);
    }

    public function show(Customer $customer)
    {
        return $customer->load('category');
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'customer_category_id' => ['nullable', 'exists:customer_categories,id'],
            'credit_limit' => ['sometimes', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $customer->update($data);

        return $customer->load('category');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->noContent();
    }

    public function ledger(Customer $customer)
    {
        return $customer->ledgerEntries()->with('reference')->latest()->paginate(30);
    }

    public function aging(Customer $customer)
    {
        return response()->json($this->creditService->agingSummary($customer));
    }
}
