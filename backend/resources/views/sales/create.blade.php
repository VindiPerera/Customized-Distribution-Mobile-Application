<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">New Sale</h2>
    </x-slot>

    <div class="py-8"
         x-data="posForm({
            products: {{ $products->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'price' => (float) $p->selling_price, 'stock' => $p->stock_quantity])->values()->toJson() }},
            customers: {{ $customers->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'balance' => (float) $c->current_balance])->values()->toJson() }},
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
                            <x-input-label value="Payment Type" required />
                            <div class="mt-1 flex flex-wrap gap-2">
                                <template x-for="type in paymentTypes" :key="type.value">
                                    <label class="flex items-center gap-1.5 border rounded-md px-3 py-1.5 cursor-pointer" :class="paymentType === type.value ? 'border-accent bg-accent-soft' : 'border-line'">
                                        <input type="radio" name="payment_type" :value="type.value" x-model="paymentType">
                                        <span class="text-sm" x-text="type.label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        <div>
                            <x-input-label for="customer_id" value="Customer" required />
                            <div class="mt-1 flex items-center gap-2">
                                <select id="customer_id" name="customer_id" x-model="customerId" class="block w-full border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent" required>
                                    <option value="">Select customer...</option>
                                    <template x-for="c in customers" :key="c.id">
                                        <option :value="c.id" x-text="c.balance > 0 ? c.name + ' (Owes Rs. ' + c.balance.toFixed(0) + ')' : c.name"></option>
                                    </template>
                                </select>
                                <a :href="customerId ? '/customers/' + customerId : '#'"
                                   x-show="customerId"
                                   target="_blank"
                                   class="shrink-0 text-xs text-accent hover:underline whitespace-nowrap">Settle credit</a>
                            </div>
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
                                <th class="w-28 text-right">Discount %</th>
                                <th class="w-28 text-right">Price</th>
                                <th class="w-20">Qty</th>
                                <th class="text-right">Total</th>
                                <th class="w-10"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(line, index) in cart" :key="line.id">
                                <tr class="border-b border-line">
                                    <td class="py-2">
                                        <div x-text="line.name"></div>
                                        <div class="text-xs text-ink-soft" x-text="'Rs. ' + line.price.toFixed(2) + ' each'"></div>
                                    </td>
                                    <td>
                                        <input type="number" min="0" max="100" step="0.01" x-model.number="line.discountPercent" class="w-24 border-line rounded-md shadow-sm text-sm text-right focus:border-accent focus:ring-accent">
                                        <input type="hidden" :name="'items['+index+'][discount_percent]'" :value="line.discountPercent">
                                    </td>
                                    <td class="text-right" x-text="'Rs. ' + discountedPrice(line).toFixed(2)"></td>
                                    <td>
                                        <input type="number" min="1" x-model.number="line.qty" class="w-20 border-line rounded-md shadow-sm text-sm focus:border-accent focus:ring-accent">
                                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="line.id">
                                        <input type="hidden" :name="'items['+index+'][quantity]'" :value="line.qty">
                                    </td>
                                    <td class="text-right" x-text="'Rs. ' + (discountedPrice(line) * line.qty).toFixed(2)"></td>
                                    <td class="text-right">
                                        <button type="button" @click="cart.splice(index, 1)" class="text-critical text-xs">Remove</button>
                                    </td>
                                </tr>
                            </template>
                            <tr x-show="cart.length === 0">
                                <td colspan="6" class="py-6 text-center text-ink-soft">No items added yet.</td>
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
                    { value: 'credit', label: 'Credit' },
                ],
                paymentType: 'cash',
                customerId: '',
                errorMsg: '',
                discountedPrice(line) {
                    const pct = Number(line.discountPercent) || 0;
                    return line.price * (1 - Math.min(Math.max(pct, 0), 100) / 100);
                },
                get total() {
                    return this.cart.reduce((sum, l) => sum + this.discountedPrice(l) * l.qty, 0);
                },
                addItem(id) {
                    if (!id) return;
                    const product = this.products.find(p => p.id == id);
                    const existing = this.cart.find(l => l.id == id);
                    if (existing) {
                        existing.qty++;
                    } else {
                        this.cart.push({ id: product.id, name: product.name, price: product.price, qty: 1, discountPercent: 0 });
                    }
                },
                validate() {
                    this.errorMsg = '';
                    if (this.cart.length === 0) {
                        this.errorMsg = 'Add at least one item.';
                        return false;
                    }
                    if (!this.customerId) {
                        this.errorMsg = 'Select a customer to issue the bill.';
                        return false;
                    }
                    return true;
                },
            };
        }
    </script>
</x-app-layout>
