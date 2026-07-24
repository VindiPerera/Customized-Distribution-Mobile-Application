<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;

class ReportDateRangeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'preset' => ['nullable', 'in:today,week,month,year,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ];
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: string}
     */
    public function resolveRange(): array
    {
        $preset = $this->validated('preset') ?: 'month';

        if ($preset === 'custom' && ($this->filled('from') || $this->filled('to'))) {
            $from = $this->date('from')?->startOfDay() ?? now()->startOfMonth();
            $to = $this->date('to')?->endOfDay() ?? now()->endOfDay();

            return [$from, $to, 'custom'];
        }

        [$from, $to] = match ($preset) {
            'today' => [now()->startOfDay(), now()->endOfDay()],
            'week' => [now()->startOfWeek(), now()->endOfWeek()],
            'year' => [now()->startOfYear(), now()->endOfYear()],
            default => [now()->startOfMonth(), now()->endOfDay()],
        };

        return [$from, $to, $preset];
    }
}
