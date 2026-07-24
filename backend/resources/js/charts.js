import { Chart, LineController, BarController, LineElement, BarElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend } from 'chart.js';

Chart.register(LineController, BarController, LineElement, BarElement, PointElement, LinearScale, CategoryScale, Tooltip, Legend);

const ACCENT = '#0f6e5c';

// Validated categorical palette (dataviz skill reference order): blue, orange, aqua, yellow, magenta.
const PAYMENT_METHOD_COLORS = {
    cash: '#2a78d6',
    card: '#eb6834',
    bank_transfer: '#1baf7a',
    credit: '#eda100',
    split: '#e87ba4',
};

const PAYMENT_METHOD_LABELS = {
    cash: 'Cash',
    card: 'Card',
    bank_transfer: 'Bank Transfer',
    credit: 'Credit',
    split: 'Split',
};

function readData(elementId) {
    const el = document.getElementById(elementId);
    if (! el) return null;
    return JSON.parse(el.textContent);
}

function initSalesTrendChart() {
    const data = readData('sales-trend-data');
    const canvas = document.getElementById('sales-trend-chart');
    if (! data || ! canvas || data.length === 0) return;

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: data.map((row) => row.label),
            datasets: [{
                data: data.map((row) => row.total),
                borderColor: ACCENT,
                backgroundColor: ACCENT,
                borderWidth: 2,
                pointRadius: 3,
                pointBackgroundColor: ACCENT,
                tension: 0.15,
                fill: false,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => 'Rs. ' + Number(ctx.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 }),
                    },
                },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e4e2dc' } },
                x: { grid: { display: false } },
            },
        },
    });
}

function initPaymentBreakdownChart() {
    const data = readData('payment-breakdown-data');
    const canvas = document.getElementById('payment-breakdown-chart');
    if (! data || ! canvas || data.length === 0) return;

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: data.map((row) => PAYMENT_METHOD_LABELS[row.method] ?? row.method),
            datasets: [{
                data: data.map((row) => row.total),
                backgroundColor: data.map((row) => PAYMENT_METHOD_COLORS[row.method] ?? '#898781'),
                borderRadius: 4,
                maxBarThickness: 48,
            }],
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: (ctx) => 'Rs. ' + Number(ctx.parsed.y).toLocaleString(undefined, { minimumFractionDigits: 2 }),
                    },
                },
            },
            scales: {
                y: { beginAtZero: true, grid: { color: '#e4e2dc' } },
                x: { grid: { display: false } },
            },
        },
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initSalesTrendChart();
    initPaymentBreakdownChart();
});
