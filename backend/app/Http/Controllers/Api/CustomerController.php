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
        // Same fix as the products endpoint: the mobile customer picker
        // (search/select while billing a sale) needs the full list in one
        // shot. paginate(20) was silently hiding every customer past page 1
        // - alphabetically - from both the sale screen and "Add Customer"
        // flow, which is what customers were reporting as "can't add
        // customer" (the one they wanted just wasn't in the visible list).
        return response()->json([
            'data' => Customer::with('category')
                ->when($request->category_id, fn ($q) => $q->where('customer_category_id', $request->category_id))
                ->orderBy('name')
                ->get(),
        ]);
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
        // Same fix as index() above: the mobile customer detail screen
        // fetches this once with no pagination UI, so paginate(30) was
        // silently hiding older ledger entries for any customer with more
        // than 30.
        return response()->json([
            'data' => $customer->ledgerEntries()->with('reference')->latest()->get(),
        ]);
    }

    public function aging(Customer $customer)
    {
        return response()->json($this->creditService->agingSummary($customer));
    }

    /**
     * Line items from this customer's past sales that still have some
     * quantity left to return (purchased quantity minus whatever was
     * already returned against that same line item in a later sale) —
     * feeds the "return a product" picker on the New Sale / Checkout screen.
     * Sales with nothing left to return are omitted entirely.
     */
    public function returnableItems(Customer $customer)
    {
        $sales = $customer->sales()
            ->with(['items' => fn ($q) => $q->with('product')->withSum('returns', 'quantity')])
            ->latest('sale_date')
            ->get();

        $sales = $sales->map(function ($sale) {
            $returnableItems = $sale->items
                ->map(fn ($item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'product_name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'returned_quantity' => (int) ($item->returns_sum_quantity ?? 0),
                    'returnable_quantity' => $item->quantity - (int) ($item->returns_sum_quantity ?? 0),
                    'unit_price' => $item->discounted_price,
                ])
                ->filter(fn ($item) => $item['returnable_quantity'] > 0)
                ->values();

            return [
                'id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'sale_date' => $sale->sale_date,
                'items' => $returnableItems,
            ];
        })->filter(fn ($sale) => $sale['items']->isNotEmpty())->values();

        return response()->json(['data' => $sales]);
    }
}
