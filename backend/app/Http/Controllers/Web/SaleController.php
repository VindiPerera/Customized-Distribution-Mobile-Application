<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\InvalidSplitPaymentException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
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
        $sales = Sale::with('customer', 'user')
            ->when($request->payment_type, fn ($q) => $q->where('payment_type', $request->payment_type))
            ->latest('sale_date')
            ->paginate(20)
            ->withQueryString();

        return view('sales.index', compact('sales'));
    }

    public function create()
    {
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $customers = Customer::where('is_active', true)->orderBy('name')->get();

        return view('sales.create', compact('products', 'customers'));
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
        } catch (InvalidSplitPaymentException $e) {
            return back()->withErrors(['payments' => $e->getMessage()])->withInput();
        }

        return redirect()->route('sales.show', $sale)->with('status', 'Sale recorded.');
    }

    public function show(Sale $sale)
    {
        $sale->load('items.product', 'customer', 'user', 'payments');

        return view('sales.show', compact('sale'));
    }
}
