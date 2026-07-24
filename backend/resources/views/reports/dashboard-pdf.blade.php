<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1c2321; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 6px; border-bottom: 1px solid #e4e2dc; padding-bottom: 4px; }
        .subtitle { color: #4b5450; font-size: 11px; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { padding: 5px 8px; text-align: left; border-bottom: 1px solid #e4e2dc; }
        th { background-color: #f3f3f0; color: #4b5450; font-weight: bold; }
        td.num, th.num { text-align: right; }
        .cards { width: 100%; margin-bottom: 10px; }
        .cards td { border: 1px solid #e4e2dc; padding: 8px; width: 16.6%; }
        .card-label { color: #4b5450; font-size: 9px; text-transform: uppercase; }
        .card-value { font-size: 14px; font-weight: bold; margin-top: 2px; }
    </style>
</head>
<body>
    <h1>Sales Report</h1>
    <div class="subtitle">{{ $range['from']->format('Y-m-d') }} to {{ $range['to']->format('Y-m-d') }}</div>

    <table class="cards">
        <tr>
            <td>
                <div class="card-label">Total Sales</div>
                <div class="card-value">Rs. {{ number_format($cards['total_sales'], 2) }}</div>
            </td>
            <td>
                <div class="card-label">Transactions</div>
                <div class="card-value">{{ $cards['transaction_count'] }}</div>
            </td>
            <td>
                <div class="card-label">Credit Outstanding (today)</div>
                <div class="card-value">Rs. {{ number_format($cards['credit_outstanding'], 2) }}</div>
            </td>
            <td>
                <div class="card-label">Top Product</div>
                <div class="card-value">{{ $cards['top_product']['name'] ?? 'N/A' }}</div>
            </td>
            <td>
                <div class="card-label">Low Stock Products</div>
                <div class="card-value">{{ $cards['low_stock_count'] }}</div>
            </td>
            <td>
                <div class="card-label">Active Suppliers</div>
                <div class="card-value">{{ $cards['supplier_count'] }}</div>
            </td>
        </tr>
    </table>

    <h2>Sales Trend</h2>
    <table>
        <thead><tr><th>Period</th><th class="num">Total Sales</th></tr></thead>
        <tbody>
            @forelse ($salesTrend as $row)
                <tr><td>{{ $row['label'] }}</td><td class="num">Rs. {{ number_format($row['total'], 2) }}</td></tr>
            @empty
                <tr><td colspan="2">No sales in this range.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Payment Method Breakdown</h2>
    <table>
        <thead><tr><th>Method</th><th class="num">Total Sales</th></tr></thead>
        <tbody>
            @foreach ($paymentBreakdown as $row)
                <tr><td>{{ \Illuminate\Support\Str::headline($row['method']) }}</td><td class="num">Rs. {{ number_format($row['total'], 2) }}</td></tr>
            @endforeach
        </tbody>
    </table>

    <h2>Top Selling Products</h2>
    <table>
        <thead><tr><th>SKU</th><th>Product</th><th class="num">Qty Sold</th><th class="num">Revenue</th></tr></thead>
        <tbody>
            @forelse ($topProducts as $product)
                <tr>
                    <td>{{ $product['sku'] }}</td>
                    <td>{{ $product['name'] }}</td>
                    <td class="num">{{ $product['qty_sold'] }}</td>
                    <td class="num">Rs. {{ number_format($product['revenue'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="4">No sales in this range.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Customer Credit &amp; Remaining Balances</h2>
    <table>
        <thead><tr><th>Customer</th><th>Phone</th><th class="num">Credit Limit</th><th class="num">Current Balance</th><th class="num">Available Credit</th></tr></thead>
        <tbody>
            @forelse ($customerBalances as $customer)
                <tr>
                    <td>{{ $customer['name'] }}</td>
                    <td>{{ $customer['phone'] }}</td>
                    <td class="num">Rs. {{ number_format($customer['credit_limit'], 2) }}</td>
                    <td class="num">Rs. {{ number_format($customer['current_balance'], 2) }}</td>
                    <td class="num">Rs. {{ number_format($customer['available_credit'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No outstanding customer balances.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Supplier Stock &amp; Purchase Activity</h2>
    <table>
        <thead><tr><th>Supplier</th><th class="num">Current Stock Value</th><th class="num">Units Received (Period)</th></tr></thead>
        <tbody>
            @forelse ($supplierStock as $supplier)
                <tr>
                    <td>{{ $supplier['name'] }}</td>
                    <td class="num">Rs. {{ number_format($supplier['stock_value'], 2) }}</td>
                    <td class="num">{{ $supplier['units_received'] }}</td>
                </tr>
            @empty
                <tr><td colspan="3">No suppliers found.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
