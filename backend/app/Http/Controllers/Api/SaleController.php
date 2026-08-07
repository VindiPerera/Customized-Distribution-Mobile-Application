<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private SaleService $saleService)
    {
    }

    public function index(Request $request)
    {
        return Sale::with('customer', 'user')
            ->latest('sale_date')
            ->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'payment_type' => ['required', 'in:cash,credit,cheque'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.discount_type' => ['nullable', 'in:percent,amount'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        $data['user_id'] = $request->user()->id;

        $sale = $this->saleService->createSale($data, $data['items']);

        return response()->json($sale, 201);
    }

    public function show(Sale $sale)
    {
        return $sale->load('items.product', 'customer', 'user', 'payments');
    }
}
