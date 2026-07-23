<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\CreditLimitExceededException;
use App\Exceptions\InvalidSplitPaymentException;
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
            'customer_id' => ['nullable', 'required_if:payment_type,credit', 'exists:customers,id'],
            'payment_type' => ['required', 'in:cash,card,bank_transfer,credit,split'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'payments' => ['required_if:payment_type,split', 'array', 'min:2'],
            'payments.*.method' => ['required_with:payments', 'in:cash,card,bank_transfer'],
            'payments.*.amount' => ['required_with:payments', 'numeric', 'min:0.01'],
        ]);

        $data['user_id'] = $request->user()->id;

        try {
            $sale = $this->saleService->createSale($data, $data['items']);
        } catch (CreditLimitExceededException|InvalidSplitPaymentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json($sale, 201);
    }

    public function show(Sale $sale)
    {
        return $sale->load('items.product', 'customer', 'user', 'payments');
    }
}
