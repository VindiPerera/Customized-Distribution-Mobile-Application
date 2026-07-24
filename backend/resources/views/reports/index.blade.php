<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-xl font-semibold text-ink leading-tight">Reports</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 grid grid-cols-1 sm:grid-cols-3 gap-4">
            @if (Auth::user()->isAdmin())
                <a href="{{ route('reports.dashboard') }}" class="sm:col-span-3 bg-surface border border-accent/30 border-t-4 border-t-accent shadow-sm rounded-lg p-6 hover:shadow-md transition">
                    <h3 class="font-semibold text-ink">Reports Dashboard</h3>
                    <p class="text-sm text-ink-soft mt-1">Sales trends, credit exposure, top products, and supplier/stock snapshot for a date range — with PDF and Excel export.</p>
                </a>
            @endif
            <a href="{{ route('reports.receivables') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h3 class="font-semibold text-ink">Receivables / Aging</h3>
                <p class="text-sm text-ink-soft mt-1">Outstanding customer balances by age.</p>
            </a>
            <a href="{{ route('reports.low-stock') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h3 class="font-semibold text-ink">Low Stock</h3>
                <p class="text-sm text-ink-soft mt-1">Products at or below reorder level.</p>
            </a>
            <a href="{{ route('reports.sales-summary') }}" class="bg-surface border border-line shadow-sm rounded-lg p-6 hover:shadow-md transition">
                <h3 class="font-semibold text-ink">Sales Summary</h3>
                <p class="text-sm text-ink-soft mt-1">Cash vs credit totals over a date range.</p>
            </a>
        </div>
    </div>
</x-app-layout>
