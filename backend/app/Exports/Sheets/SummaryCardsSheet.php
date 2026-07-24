<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SummaryCardsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private array $cards, private array $range) {}

    public function collection(): Collection
    {
        $topProduct = $this->cards['top_product'];

        return collect([
            ['Report period', $this->range['from']->format('Y-m-d').' to '.$this->range['to']->format('Y-m-d')],
            ['Total sales', $this->cards['total_sales']],
            ['Transactions', $this->cards['transaction_count']],
            ['Credit outstanding (as of today)', $this->cards['credit_outstanding']],
            ['Top product', $topProduct['name'] ?? 'N/A'],
            ['Top product quantity sold', $topProduct['qty_sold'] ?? 0],
            ['Low stock products', $this->cards['low_stock_count']],
            ['Active suppliers', $this->cards['supplier_count']],
        ]);
    }

    public function headings(): array
    {
        return ['Metric', 'Value'];
    }

    public function title(): string
    {
        return 'Summary';
    }
}
