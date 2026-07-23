<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Services\CreditService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(private CreditService $creditService)
    {
    }

    public function index(Request $request)
    {
        return Payment::with('customer', 'user')->latest('paid_at')->paginate(20);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'in:cash,bank_transfer,cheque,other'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::findOrFail($data['customer_id']);
        $data['user_id'] = $request->user()->id;

        $payment = $this->creditService->recordPayment($customer, $data);

        return response()->json($payment, 201);
    }
}
