<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class SupplierStockSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [$row['name'], $row['stock_value'], $row['units_received']]);
    }

    public function headings(): array
    {
        return ['Supplier', 'Current Stock Value', 'Units Received (Period)'];
    }

    public function title(): string
    {
        return 'Supplier Stock';
    }
}
