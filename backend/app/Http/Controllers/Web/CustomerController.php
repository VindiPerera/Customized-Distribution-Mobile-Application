<?php

namespace App\Http\Controllers\Web;

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
        $customers = Customer::query()
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
        ]);

        $customer = Customer::create($data);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(Customer $customer)
    {
        $ledger = $customer->ledgerEntries()->latest()->paginate(20);
        $aging = $this->creditService->agingSummary($customer);

        return view('customers.show', compact('customer', 'ledger', 'aging'));
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        $customer->update($data);

        return redirect()->route('customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function storePayment(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['nullable', 'in:cash,bank_transfer,cheque,other'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
        ]);
        $data['user_id'] = $request->user()->id;

        $this->creditService->recordPayment($customer, $data);

        return redirect()->route('customers.show', $customer)->with('status', 'Payment recorded.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->sales()->exists()) {
            return back()->withErrors(['customer' => 'Cannot delete a customer that has sale history. Deactivate it instead.']);
        }

        $customer->delete();

        return redirect()->route('customers.index')->with('status', 'Customer removed.');
    }
}
