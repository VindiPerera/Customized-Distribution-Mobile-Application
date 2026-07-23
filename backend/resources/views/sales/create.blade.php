<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">New Sale</h2>
    </x-slot>

    <div class="py-8"
         x-data="posForm({
            products: {{ $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->selling_price, 'stock' => $p->stock_quantity])->values()->toJson() }},
            customers: {{ $customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'available' => (float) $c->availableCredit()])->values()->toJson() }},
         })">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('sales.store') }}" @submit="if (!validate()) $event.preventDefault()">
                @csrf

                @if ($errors->any())
                    <div class="bg-critical-soft text-critical text-sm px-4 py-3 rounded mb-4">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="bg-surface border border-line shadow-sm rounded-lg p-6 space-y-4">

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Payment Type" />
                            <div class="mt-1 flex flex-wrap gap-2">
                                <template x-for="type in paymentTypes" :key="type.value">
                                    <label class="flex items-center gap-1.5 border rounded-md px-3 py-1.5 cursor-pointer" :class="paymentType === type.value ? 'border-accent bg-accent-soft' : 'border-line'">
                                        <input type="radio" name="payment_type" :value="type.value" x-model="paymentType">
                                        <span class="text-sm" x-text="type.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div x-show="paymentType === 'credit'">
                            <x-input-label for="customer_id" value="Customer" />
                            <select id="customer_id" name="customer_id" x-model="customerId" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                <option value="">Select customer...</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" x-text="c.name + ' (Available: Rs. ' + c.available.toFixed(0) + ')'"></option>
                                </template>
                            </select>
                        </div>
                    </div>

                    <div x-show="paymentType === 'split'" class="border border-line rounded-lg p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <x-input-label value="Split Payment" class="mb-0" />
                            <button type="button" @click="addSplitLine()" class="text-xs text-accent hover:underline font-medium">+ Add method</button>
                        </div>
                        <template x-for="(line, index) in splitLines" :key="index">
                            <div class="flex items-center gap-2">
                                <select x-model="line.method" class="flex-1 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                                <input type="number" min="0" step="0.01" x-model.number="line.amount" placeholder="Amount" class="w-32 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                <input type="hidden" :name="'payments['+index+'][method]'" :value="line.method">
                                <input type="hidden" :name="'payments['+index+'][amount]'" :value="line.amount">
                                <button type="button" @click="splitLines.splice(index, 1)" class="text-critical text-xs" x-show="splitLines.length > 2">Remove</button>
                            </div>
                        </template>
                        <div class="text-xs" :class="splitRemaining === 0 ? 'text-good' : 'text-ink-soft'">
                            <span x-text="'Rs. ' + splitAssigned.toFixed(2) + ' of Rs. ' + total.toFixed(2) + ' assigned'"></span>
                            <span x-show="splitRemaining !== 0" x-text="' — Rs. ' + splitRemaining.toFixed(2) + ' remaining'"></span>
                        </div>
                    </div>

                    <div>
                        <x-input-label value="Add Product" />
                        <select @change="addItem($event.target.value); $event.target.value = ''" class="mt-1 block w-full border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                            <option value="">Select a product to add...</option>
                            <template x-for="p in products" :key="p.id">
                                <option :value="p.id" x-text="p.name + ' — Rs. ' + p.price.toFixed(2) + ' (Stock: ' + p.stock + ')'"></option>
                            </template>
                        </select>
                    </div>

                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-ink-soft border-b border-line">
                                <th class="py-2">Product</th>
                                <th class="w-24">Qty</th>
                                <th class="text-right">Line Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in cart" :key="line.id">
                                <tr class="border-b border-line">
                                    <td class="py-2" x-text="line.name"></td>
                                    <td>
                                        <input type="number" min="1" x-model.number="line.qty" class="w-20 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="line.id">
                                        <input type="hidden" :name="'items['+index+'][quantity]'" :value="line.qty">
                                    </td>
                                    <td class="text-right" x-text="'Rs. ' + (line.price * line.qty).toFixed(2)"></td>
                                    <td class="text-right">
                                        <button type="button" @click="cart.splice(index, 1)" class="text-critical text-xs">Remove</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="cart.length === 0">
                                <td colspan="4" class="py-6 text-center text-ink-soft">No items added yet.</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="flex justify-between items-center pt-4 border-t border-line">
                        <div class="text-sm text-critical" x-show="errorMsg" x-text="errorMsg"></div>
                        <div class="ml-auto text-right">
                            <div class="text-sm text-ink-soft">Total</div>
                            <div class="text-2xl font-bold text-ink" x-text="'Rs. ' + total.toFixed(2)"></div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-2">
                        <a href="{{ route('sales.index') }}" class="px-4 py-2 text-sm text-ink-soft">Cancel</a>
                        <x-primary-button>Complete Sale</x-primary-button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        function posForm({ products, customers }) {
            return {
                products,
                customers,
                cart: [],
                paymentTypes: [
                    { value: 'cash', label: 'Cash' },
                    { value: 'card', label: 'Card' },
                    { value: 'bank_transfer', label: 'Bank Transfer' },
                    { value: 'credit', label: 'Credit' },
                    { value: 'split', label: 'Split' },
                ],
                paymentType: 'cash',
                customerId: '',
                splitLines: [
                    { method: 'cash', amount: 0 },
                    { method: 'card', amount: 0 },
                ],
                errorMsg: '',
                get total() {
                    return this.cart.reduce((sum, l) => sum + l.price * l.qty, 0);
                },
                get splitAssigned() {
                    return this.splitLines.reduce((sum, l) => sum + (Number(l.amount) || 0), 0);
                },
                get splitRemaining() {
                    return Math.round((this.total - this.splitAssigned) * 100) / 100;
                },
                addItem(id) {
                    if (!id) return;
                    const product = this.products.find(p => p.id == id);
                    const existing = this.cart.find(l => l.id == id);
                    if (existing) {
                        existing.qty++;
                    } else {
                        this.cart.push({ id: product.id, name: product.name, price: product.price, qty: 1 });
                    }
                },
                addSplitLine() {
                    this.splitLines.push({ method: 'cash', amount: 0 });
                },
                validate() {
                    this.errorMsg = '';
                    if (this.cart.length === 0) {
                        this.errorMsg = 'Add at least one item.';
                        return false;
                    }
                    if (this.paymentType === 'credit' && !this.customerId) {
                        this.errorMsg = 'Select a customer for a credit sale.';
                        return false;
                    }
                    if (this.paymentType === 'split' && this.splitRemaining !== 0) {
                        this.errorMsg = 'Split payment amounts must add up to the total.';
                        return false;
                    }
                    return true;
                },
            };
        }
    </script>
</x-app-layout>
