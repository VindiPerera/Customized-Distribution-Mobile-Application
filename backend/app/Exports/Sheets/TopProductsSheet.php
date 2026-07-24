<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class TopProductsSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [$row['sku'], $row['name'], $row['qty_sold'], $row['revenue']]);
    }

    public function headings(): array
    {
        return ['SKU', 'Product', 'Qty Sold', 'Revenue'];
    }

    public function title(): string
    {
        return 'Top Products';
    }
}
