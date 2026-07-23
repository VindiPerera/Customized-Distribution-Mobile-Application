<?php

namespace App\Services;

use App\Exceptions\InvalidSplitPaymentException;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    public function __construct(private CreditService $creditService)
    {
    }

    /**
     * Create a sale (cash or credit) with its line items, deduct stock,
     * and — for credit sales — post the debt to the customer's ledger.
     *
     * $items: array of ['product_id' => int, 'quantity' => int]
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

                $lineTotal = $product->selling_price * $item['quantity'];
                $subtotal += $lineTotal;

                $lineItems[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->selling_price,
                    'line_total' => $lineTotal,
                ];
            }

            $discount = $data['discount'] ?? 0;
            $totalAmount = $subtotal - $discount;
            $paymentType = $data['payment_type'];

            if ($paymentType === 'split') {
                $paymentsSum = array_sum(array_column($data['payments'], 'amount'));
                if (abs($paymentsSum - $totalAmount) > 0.01) {
                    throw new InvalidSplitPaymentException($paymentsSum, $totalAmount);
                }
            }

            $sale = Sale::create([
                'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
                'customer_id' => $data['customer_id'] ?? null,
                'user_id' => $data['user_id'],
                'payment_type' => $paymentType,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $totalAmount,
                'paid_amount' => $paymentType === 'credit' ? 0 : $totalAmount,
                'sale_date' => now(),
                'notes' => $data['notes'] ?? null,
            ]);

            if ($paymentType === 'split') {
                foreach ($data['payments'] as $payment) {
                    $sale->payments()->create([
                        'method' => $payment['method'],
                        'amount' => $payment['amount'],
                    ]);
                }
            }

            foreach ($lineItems as $line) {
                $sale->items()->create([
                    'product_id' => $line['product']->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
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

            if ($paymentType === 'credit') {
                $customer = Customer::findOrFail($data['customer_id']);
                $this->creditService->recordSaleOnCredit($customer, $sale);
            }

            return $sale->load('items.product', 'customer', 'payments');
        });
    }
}
