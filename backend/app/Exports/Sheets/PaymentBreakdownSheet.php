<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PaymentBreakdownSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [ucwords(str_replace('_', ' ', $row['method'])), $row['total']]);
    }

    public function headings(): array
    {
        return ['Payment Method', 'Total Sales'];
    }

    public function title(): string
    {
        return 'Payment Methods';
    }
}
