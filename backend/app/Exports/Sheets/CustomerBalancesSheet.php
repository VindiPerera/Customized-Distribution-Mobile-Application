<?php

namespace App\Exports\Sheets;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CustomerBalancesSheet implements FromCollection, WithHeadings, WithTitle
{
    public function __construct(private Collection $rows) {}

    public function collection(): Collection
    {
        return $this->rows->map(fn ($row) => [
            $row['name'],
            $row['phone'],
            $row['credit_limit'],
            $row['current_balance'],
            $row['available_credit'],
        ]);
    }

    public function headings(): array
    {
        return ['Customer', 'Phone', 'Credit Limit', 'Current Balance', 'Available Credit'];
    }

    public function title(): string
    {
        return 'Customer Credit';
    }
}
