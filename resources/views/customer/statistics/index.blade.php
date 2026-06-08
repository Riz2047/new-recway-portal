@extends('customer.layouts.app')

@section('title', __('Statistics') . ' | ' . config('app.name'))
@section('page-title', __('Statistics'))

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
@endpush

@section('content')

{{-- ── Summary cards ───────────────────────────────────────────────────── --}}
<div id="summary-cards" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
    @php
        $cards = [
            ['key' => 'total_orders',                     'label' => __('Total Orders'),              'color' => 'blue',   'icon' => 'lucide:clipboard-list'],
            ['key' => 'immediate_approved',               'label' => __('Immediate Approved'),        'color' => 'green',  'icon' => 'lucide:check-circle'],
            ['key' => 'under_investigation_current',      'label' => __('Under Investigation'),       'color' => 'amber',  'icon' => 'lucide:search'],
            ['key' => 'under_investigation_then_approved','label' => __('Investigated → Approved'),   'color' => 'emerald','icon' => 'lucide:check-check'],
            ['key' => 'under_investigation_then_rejected','label' => __('Investigated → Rejected'),   'color' => 'red',    'icon' => 'lucide:x-circle'],
            ['key' => 'cancelled_by_customer',            'label' => __('Cancelled'),                 'color' => 'gray',   'icon' => 'lucide:ban'],
            ['key' => 'deviation',                        'label' => __('Deviation'),                 'color' => 'rose',   'icon' => 'lucide:alert-triangle'],
        ];
    @endphp
    @foreach($cards as $card)
    <div class="summary-card rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-800"
        data-metric="{{ $card['key'] }}">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-tight">{{ $card['label'] }}</p>
                <p class="summary-val mt-1 text-2xl font-bold text-gray-900 dark:text-white" data-metric="{{ $card['key'] }}">—</p>
            </div>
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-{{ $card['color'] }}-50 dark:bg-{{ $card['color'] }}-900/20">
                <iconify-icon icon="{{ $card['icon'] }}" width="16" class="text-{{ $card['color'] }}-600 dark:text-{{ $card['color'] }}-400"></iconify-icon>
            </div>
        </div>
    </div>
    @endforeach
</div>

{{-- ── Main panel ──────────────────────────────────────────────────────── --}}
<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

    {{-- Filter bar --}}
    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <div class="flex flex-wrap items-end gap-3">

            {{-- Date presets --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Period') }}</label>
                <div class="flex gap-1">
                    @foreach([
                        ['3m',  __('3 Months')],
                        ['30d', __('30 Days')],
                        ['7d',  __('7 Days')],
                        ['all', __('All Time')],
                    ] as [$val, $lbl])
                    <button type="button"
                        onclick="setPreset('{{ $val }}')"
                        data-preset="{{ $val }}"
                        class="preset-btn rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:border-brand-400 hover:text-brand-600 dark:border-gray-600 dark:text-gray-400">
                        {{ $lbl }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Date from --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('From') }}</label>
                <x-inputs.date-picker id="filterFrom" placeholder="{{ __('From date') }}"
                    class="!rounded-lg !border-gray-200 !bg-gray-50 !px-3 !py-1.5 !text-sm dark:!border-gray-600 dark:!bg-gray-700 dark:!text-gray-200" />
            </div>

            {{-- Date to --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('To') }}</label>
                <x-inputs.date-picker id="filterTo" placeholder="{{ __('To date') }}"
                    class="!rounded-lg !border-gray-200 !bg-gray-50 !px-3 !py-1.5 !text-sm dark:!border-gray-600 dark:!bg-gray-700 dark:!text-gray-200" />
            </div>

            {{-- Service category --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Service') }}</label>
                <select id="filterService"
                    class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="">{{ __('All services') }}</option>
                    @foreach($serviceCategories as $cat)
                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Department (hidden if empty) --}}
            @if($departments->isNotEmpty())
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Department') }}</label>
                <select id="filterDept"
                    class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200">
                    <option value="">{{ __('All departments') }}</option>
                    @foreach($departments as $dep)
                    <option value="{{ $dep }}">{{ $dep }}</option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Actions --}}
            <div class="flex gap-2">
                <button type="button" onclick="fetchStats()"
                    class="btn-primary inline-flex items-center gap-1.5 text-sm">
                    <iconify-icon icon="lucide:filter" width="14"></iconify-icon>
                    {{ __('Apply') }}
                </button>
                <button type="button" id="btnExportCsv" onclick="exportCsv()"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <iconify-icon icon="lucide:download" width="14"></iconify-icon>
                    {{ __('Export CSV') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Loading indicator --}}
    <div id="stats-loading" class="hidden px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
        <iconify-icon icon="lucide:loader-circle" class="animate-spin mr-2" width="16"></iconify-icon>
        {{ __('Loading...') }}
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-700/40 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3">{{ __('Service Category') }}</th>
                    @foreach([
                        'total_orders'                     => __('Total'),
                        'immediate_approved'               => __('Imm. Approved'),
                        'under_investigation_current'      => __('Under Invest.'),
                        'under_investigation_then_approved'=> __('Invest. → Approved'),
                        'under_investigation_then_rejected'=> __('Invest. → Rejected'),
                        'cancelled_by_customer'            => __('Cancelled'),
                        'deviation'                        => __('Deviation'),
                    ] as $metricKey => $metricLabel)
                    <th class="px-5 py-3 text-center metric-col" data-metric="{{ $metricKey }}">
                        {{ $metricLabel }}
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody id="stats-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                {{-- Populated by JS --}}
            </tbody>
            <tfoot>
                <tr id="summary-row" class="hidden border-t-2 border-gray-300 bg-gray-50 font-semibold dark:border-gray-600 dark:bg-gray-700/40">
                    <td class="px-5 py-3 text-xs uppercase text-gray-500 dark:text-gray-400">{{ __('Total') }}</td>
                    @foreach(['total_orders','immediate_approved','under_investigation_current','under_investigation_then_approved','under_investigation_then_rejected','cancelled_by_customer','deviation'] as $k)
                    <td class="px-5 py-3 text-center metric-col summary-val" data-metric="{{ $k }}">—</td>
                    @endforeach
                </tr>
            </tfoot>
        </table>

        {{-- Empty state --}}
        <div id="stats-empty" class="hidden px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
            <iconify-icon icon="lucide:bar-chart-2" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
            {{ __('No data for the selected period.') }}
        </div>
    </div>
</div>

{{-- ── Charts ───────────────────────────────────────────────────────────── --}}
<div id="charts-section" class="mt-5 hidden grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Orders by Service') }}</h3>
        <div id="chart-by-service" style="min-height:220px"></div>
    </div>
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Status Distribution') }}</h3>
        <div id="chart-by-status" style="min-height:220px"></div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const STATS_ROUTES = {
    data:   '{{ route('customer.statistics.data') }}',
    export: '{{ route('customer.statistics.export') }}',
};
const CSRF = document.querySelector('meta[name=csrf-token]').content;
const isDark = JSON.parse(localStorage.getItem('darkMode') ?? 'false');

let chartByService = null;
let chartByStatus  = null;
let lastResponse   = null;

// ── Date preset logic ────────────────────────────────────────────────────
function setPreset(preset) {
    document.querySelectorAll('.preset-btn').forEach(b => {
        b.classList.toggle('bg-brand-600', b.dataset.preset === preset);
        b.classList.toggle('text-white',   b.dataset.preset === preset);
        b.classList.toggle('border-brand-600', b.dataset.preset === preset);
    });

    const to   = new Date();
    let   from = new Date();

    if (preset === '3m')  { from.setMonth(from.getMonth() - 3); }
    if (preset === '30d') { from.setDate(from.getDate() - 30); }
    if (preset === '7d')  { from.setDate(from.getDate() - 7); }

    setDateInput('filterFrom', preset === 'all' ? '' : from.toISOString().slice(0, 10));
    setDateInput('filterTo',   preset === 'all' ? '' : to.toISOString().slice(0, 10));
}

// Set a date-picker input's value, keeping the flatpickr calendar in sync.
function setDateInput(id, value) {
    const el = document.getElementById(id);
    if (el?._flatpickr) {
        el._flatpickr.setDate(value, true);
    } else if (el) {
        el.value = value;
    }
}

// ── Fetch stats ──────────────────────────────────────────────────────────
async function fetchStats() {
    const tbody = document.getElementById('stats-tbody');
    tbody.innerHTML = '';
    document.getElementById('stats-loading').classList.remove('hidden');
    document.getElementById('stats-empty').classList.add('hidden');
    document.getElementById('summary-row').classList.add('hidden');
    document.getElementById('charts-section').classList.add('hidden');

    const payload = {
        service_id:    document.getElementById('filterService').value,
        department_id: document.getElementById('filterDept')?.value ?? '',
        date_from:     document.getElementById('filterFrom').value,
        date_to:       document.getElementById('filterTo').value,
    };

    try {
        const res  = await fetch(STATS_ROUTES.data, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        lastResponse = data;

        document.getElementById('stats-loading').classList.add('hidden');
        applyVisibility(data.visible_metrics);
        renderTable(data);
        updateSummaryCards(data.summary, data.visible_metrics);
        renderCharts(data);
    } catch (e) {
        document.getElementById('stats-loading').classList.add('hidden');
        document.getElementById('stats-empty').classList.remove('hidden');
    }
}

// ── Apply metric column visibility ───────────────────────────────────────
function applyVisibility(visible) {
    document.querySelectorAll('.metric-col').forEach(el => {
        const show = visible.includes(el.dataset.metric);
        el.style.display = show ? '' : 'none';
    });
    document.querySelectorAll('.summary-card').forEach(el => {
        const show = visible.includes(el.dataset.metric);
        el.style.display = show ? '' : 'none';
    });
}

// ── Render table rows ─────────────────────────────────────────────────────
function renderTable(data) {
    const tbody = document.getElementById('stats-tbody');
    tbody.innerHTML = '';

    if (!data.data || data.data.length === 0) {
        document.getElementById('stats-empty').classList.remove('hidden');
        return;
    }

    const metrics = ['total_orders','immediate_approved','under_investigation_current',
                     'under_investigation_then_approved','under_investigation_then_rejected',
                     'cancelled_by_customer','deviation'];

    data.data.forEach(row => {
        const tr = document.createElement('tr');
        tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors';
        let html = `<td class="px-5 py-3 font-medium text-gray-800 dark:text-white">${row.service_category_name}</td>`;
        metrics.forEach(k => {
            html += `<td class="px-5 py-3 text-center text-gray-600 dark:text-gray-300 metric-col" data-metric="${k}">${row[k] ?? 0}</td>`;
        });
        tr.innerHTML = html;
        tbody.appendChild(tr);
    });

    // Summary row
    const sumRow = document.getElementById('summary-row');
    sumRow.classList.remove('hidden');
    metrics.forEach(k => {
        sumRow.querySelector(`[data-metric="${k}"]`).textContent = data.summary[k] ?? 0;
    });

    // Charts section
    document.getElementById('charts-section').classList.remove('hidden');
    document.getElementById('charts-section').classList.add('grid');
}

// ── Update summary cards ──────────────────────────────────────────────────
function updateSummaryCards(summary, visible) {
    document.querySelectorAll('.summary-val').forEach(el => {
        const metric = el.dataset.metric;
        if (metric && visible.includes(metric)) {
            el.textContent = summary[metric] ?? 0;
        }
    });
}

// ── ApexCharts ────────────────────────────────────────────────────────────
function renderCharts(data) {
    const theme = isDark ? 'dark' : 'light';
    const grid  = isDark ? '#374151' : '#e5e7eb';

    // Bar chart: total orders per service
    const barOpts = {
        chart: { type: 'bar', height: 220, toolbar: { show: false }, background: 'transparent', animations: { speed: 400 } },
        theme: { mode: theme },
        series: [{ name: '{{ __('Total Orders') }}', data: data.data.map(r => r.total_orders) }],
        xaxis: { categories: data.data.map(r => r.service_category_name), labels: { style: { fontSize: '11px' } } },
        colors: ['#3b82f6'],
        dataLabels: { enabled: false },
        grid: { borderColor: grid, strokeDashArray: 4 },
        plotOptions: { bar: { borderRadius: 4 } },
    };

    if (chartByService) {
        chartByService.updateOptions(barOpts);
    } else {
        chartByService = new ApexCharts(document.querySelector('#chart-by-service'), barOpts);
        chartByService.render();
    }

    // Donut chart: status distribution (across all categories, summary row)
    const s = data.summary;
    const donutLabels = [
        '{{ __('Immediate Approved') }}',
        '{{ __('Under Investigation') }}',
        '{{ __('Invest. → Approved') }}',
        '{{ __('Invest. → Rejected') }}',
        '{{ __('Cancelled') }}',
        '{{ __('Deviation') }}',
    ];
    const donutSeries = [
        s.immediate_approved,
        s.under_investigation_current,
        s.under_investigation_then_approved,
        s.under_investigation_then_rejected,
        s.cancelled_by_customer,
        s.deviation,
    ];

    const donutOpts = {
        chart: { type: 'donut', height: 220, background: 'transparent' },
        theme: { mode: theme },
        series: donutSeries,
        labels: donutLabels,
        colors: ['#22c55e','#f59e0b','#10b981','#ef4444','#94a3b8','#f43f5e'],
        legend: { position: 'bottom', fontSize: '11px' },
        dataLabels: { enabled: false },
        plotOptions: { pie: { donut: { size: '60%' } } },
    };

    if (chartByStatus) {
        chartByStatus.updateOptions(donutOpts);
    } else {
        chartByStatus = new ApexCharts(document.querySelector('#chart-by-status'), donutOpts);
        chartByStatus.render();
    }
}

// ── CSV export ────────────────────────────────────────────────────────────
function exportCsv() {
    const params = new URLSearchParams({
        service_id:    document.getElementById('filterService').value,
        department_id: document.getElementById('filterDept')?.value ?? '',
        date_from:     document.getElementById('filterFrom').value,
        date_to:       document.getElementById('filterTo').value,
        _token:        CSRF,
    });
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = STATS_ROUTES.export;
    params.forEach((v, k) => {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = k; input.value = v;
        form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    setPreset('3m');
    fetchStats();
});
</script>
@endpush
