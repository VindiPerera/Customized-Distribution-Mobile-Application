<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerCategory;
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
            ->with('category')
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->when($request->category_id, fn ($q) => $q->where('customer_category_id', $request->category_id))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        $categories = CustomerCategory::where('is_active', true)->orderBy('name')->get();

        return view('customers.index', compact('customers', 'categories'));
    }

    public function create()
    {
        $categories = CustomerCategory::where('is_active', true)->orderBy('name')->get();

        return view('customers.create', compact('categories'));
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
        $categories = CustomerCategory::where('is_active', true)->orderBy('name')->get();

        return view('customers.edit', compact('customer', 'categories'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email'],
            'address' => ['nullable', 'string'],
            'customer_category_id' => ['nullable', 'exists:customer_categories,id'],
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
            'method' => ['nullable', 'in:cash,card,bank_transfer,cheque,other'],
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
