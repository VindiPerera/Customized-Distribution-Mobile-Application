<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DashboardReportExport implements WithMultipleSheets
{
    public function __construct(private array $data) {}

    public function sheets(): array
    {
        return [
            new Sheets\SummaryCardsSheet($this->data['cards'], $this->data['range']),
            new Sheets\SalesTrendSheet($this->data['salesTrend']),
            new Sheets\PaymentBreakdownSheet($this->data['paymentBreakdown']),
            new Sheets\TopProductsSheet($this->data['topProducts']),
            new Sheets\CustomerBalancesSheet($this->data['customerBalances']),
            new Sheets\SupplierStockSheet($this->data['supplierStock']),
        ];
    }
}
