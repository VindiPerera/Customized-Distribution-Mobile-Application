<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;

class SaleService
{
    public function __construct(
        private CreditService $creditService,
        private InvoiceNumberService $invoiceNumbers,
    ) {
    }

    /**
     * Create a sale (cash or credit) with its line items, deduct stock,
     * and — for credit sales, or a cash sale that wasn't paid in full —
     * post the outstanding amount to the customer's ledger.
     *
     * A customer is always required: bills are always issued to a named
     * customer, never anonymously.
     *
     * $items: array of ['product_id' => int, 'quantity' => int, 'discount_percent' => float|null]
     */
    public function createSale(array $data, array $items): Sale
    {
        return DB::transaction(function () use ($data, $items) {
            $subtotal = 0;
            $lineItems = [];

            foreach ($items as $item) {
                $product = Product::whereKey($item['product_id'])->lockForUpdate()->firstOrFail();

                if ($product->stock_quantity < $item['quantity']) {
                    throw new \RuntimeException("Insufficient stock for product: {$product->name}");
                }

                if (isset($item['discount_amount']) && (float) $item['discount_amount'] > 0) {
                    $discAmt = (float) $item['discount_amount'];
                    $discountedPrice = max(round($product->selling_price - $discAmt, 2), 0);
                    $discountPercent = $product->selling_price > 0 ? round(($discAmt / $product->selling_price) * 100, 2) : 0;
                } else {
                    $discountPercent = min(max((float) ($item['discount_percent'] ?? 0), 0), 100);
                    $discountedPrice = round($product->selling_price * (1 - $discountPercent / 100), 2);
                }
                $lineTotal = $discountedPrice * $item['quantity'];
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'discount_percent' => $discountPercent,
                    'discounted_price' => $discountedPrice,
                    'line_total' => $lineTotal,
                ];
            }

            $totalAmount = $subtotal;
            $paymentType = $data['payment_type'];

            // Credit sales are paid 0 up front. Cash sales are normally paid
            // in full, but the cashier may report a partial "paid_amount"
            // (the customer only handed over part of the total); anything
            // not explicitly reported is assumed paid in full.
            $paidAmount = $paymentType === 'credit'
                ? 0
                : (float) ($data['paid_amount'] ?? $totalAmount);
            $paidAmount = min(max($paidAmount, 0), $totalAmount);
            $balanceDue = round($totalAmount - $paidAmount, 2);

            $sale = Sale::create([
                'invoice_number' => $this->invoiceNumbers->next(),
                'customer_id' => $data['customer_id'],
                'user_id' => $data['user_id'],
                'payment_type' => $paymentType,
                'payment_reference' => $data['payment_reference'] ?? null,
                'subtotal' => $subtotal,
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'sale_date' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($lineItems as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'discount_percent' => $line['discount_percent'],
                    'discounted_price' => $line['discounted_price'],
                    'line_total' => $line['line_total'],
                ]);

                $newStock = $line['product']->stock_quantity - $line['quantity'];

                $line['product']->stockMovements()->create([
                    'type' => 'sale_out',
                    'quantity' => -$line['quantity'],
                    'quantity_after' => $newStock,
                    'reference_type' => Sale::class,
                    'reference_id' => $sale->id,
                    'user_id' => $data['user_id'],
                ]);

                $line['product']->update(['stock_quantity' => $newStock]);
            }

            if ($balanceDue > 0) {
                $customer = Customer::findOrFail($data['customer_id']);
                $this->creditService->recordSaleOnCredit($customer, $sale, $balanceDue);
            }

            return $sale->load('items.product', 'customer');
        });
    }
}
