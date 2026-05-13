<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

{{-- ============================================================
     TOOLBAR: Filters + Date range
     ============================================================ --}}
<div
    x-data="analyticsApp()"
    x-init="init()"
    @keydown.escape.window="filtersOpen = false"
    class="space-y-6"
>
    {{-- Filter bar --}}
    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex flex-wrap items-center gap-2">
            {{-- Date pickers --}}
            <div class="flex items-center gap-1.5">
                <input type="date" x-model="filters.start_date" @change="refresh()"
                    class="form-control h-8 text-xs w-32" />
                <span class="text-xs text-gray-400">to</span>
                <input type="date" x-model="filters.end_date" @change="refresh()"
                    class="form-control h-8 text-xs w-32" />
            </div>

            {{-- Quick date presets --}}
            <div class="flex gap-1">
                @foreach ([
                    ['7d',  __('7 days')],
                    ['30d', __('30 days')],
                    ['90d', __('90 days')],
                    ['ytd', __('Year')],
                ] as [$val, $lbl])
                    <button @click="setPreset('{{ $val }}')"
                        :class="preset === '{{ $val }}' ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300' : 'border-gray-200 text-gray-500 dark:border-gray-700 dark:text-gray-400'"
                        class="rounded border px-2.5 py-1 text-xs font-medium transition hover:border-indigo-400">
                        {{ $lbl }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="flex items-center gap-2">
            {{-- Advanced filters toggle --}}
            <button @click="filtersOpen = !filtersOpen"
                :class="filtersOpen ? 'border-indigo-400 bg-indigo-50 text-indigo-600 dark:bg-indigo-900/20' : 'border-gray-300 text-gray-600 dark:border-gray-600 dark:text-gray-400'"
                class="flex items-center gap-1.5 rounded border px-3 py-1.5 text-xs font-medium transition hover:bg-gray-50 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:filter" height="13"></iconify-icon>
                {{ __('Filters') }}
                <span x-show="activeFilterCount > 0"
                    class="rounded-full bg-indigo-600 px-1.5 py-0.5 text-xs text-white" x-text="activeFilterCount"></span>
            </button>

            {{-- Loading indicator --}}
            <div x-show="loading" class="flex items-center gap-1.5 text-xs text-indigo-600 dark:text-indigo-400">
                <iconify-icon icon="lucide:loader-circle" class="animate-spin" height="14"></iconify-icon>
                {{ __('Refreshing…') }}
            </div>
        </div>
    </div>

    {{-- Advanced filter panel --}}
    <div x-show="filtersOpen" x-cloak x-transition
        class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Customer') }}</label>
                <select x-model="filters.customer_id" @change="refresh()" class="form-control text-sm">
                    <option value="">{{ __('All Customers') }}</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->user?->name ?? "#$c->id" }}
                            @if ($c->company) — {{ $c->company }} @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Company') }}</label>
                <select x-model="filters.company" @change="refresh()" class="form-control text-sm">
                    <option value="">{{ __('All Companies') }}</option>
                    @foreach ($companies as $co)
                        <option value="{{ $co }}">{{ $co }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Service Category') }}</label>
                <select x-model="filters.service_category_id" @change="refresh()" class="form-control text-sm">
                    <option value="">{{ __('All Services') }}</option>
                    @foreach ($serviceCategories as $sc)
                        <option value="{{ $sc->id }}">{{ $sc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Status') }}</label>
                <select x-model="filters.status_id" @change="refresh()" class="form-control text-sm">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach ($statuses as $s)
                        <option value="{{ $s->id }}">{{ $s->status }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <button @click="resetFilters()"
                class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                {{ __('Reset Filters') }}
            </button>
        </div>
    </div>

    {{-- ================================================================
         ROW 1: Period quick-counts (always live, unfiltered)
         ================================================================ --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $quickCards = [
                ['label' => __('Today'),      'value' => $periodCounts['today'],      'icon' => 'lucide:sun',          'color' => 'text-amber-600 bg-amber-100 dark:bg-amber-900/40'],
                ['label' => __('This Week'),  'value' => $periodCounts['this_week'],  'icon' => 'lucide:calendar',     'color' => 'text-blue-600 bg-blue-100 dark:bg-blue-900/40'],
                ['label' => __('This Month'), 'value' => $periodCounts['this_month'], 'icon' => 'lucide:calendar-days','color' => 'text-purple-600 bg-purple-100 dark:bg-purple-900/40'],
                ['label' => __('Total'),      'value' => $periodCounts['total'],      'icon' => 'lucide:layers',       'color' => 'text-green-600 bg-green-100 dark:bg-green-900/40'],
            ];
        @endphp
        @foreach ($quickCards as $card)
            <div class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full {{ $card['color'] }}">
                    <iconify-icon icon="{{ $card['icon'] }}" height="20"></iconify-icon>
                </div>
                <div>
                    <p class="text-xs text-gray-400 dark:text-gray-500">{{ $card['label'] }}</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ number_format($card['value']) }}</p>
                </div>
            </div>
        @endforeach
    </div>

    {{-- ================================================================
         ROW 2: Filtered summary cards (live-updated)
         ================================================================ --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $summaryCards = [
                ['key' => 'created',  'label' => __('Created'),  'icon' => 'lucide:plus-circle',   'color' => 'text-indigo-600'],
                ['key' => 'booked',   'label' => __('Booked'),   'icon' => 'lucide:calendar-check', 'color' => 'text-blue-600'],
                ['key' => 'approved', 'label' => __('Approved'), 'icon' => 'lucide:check-circle',  'color' => 'text-green-600'],
                ['key' => 'canceled', 'label' => __('Canceled'), 'icon' => 'lucide:x-circle',      'color' => 'text-red-600'],
            ];
        @endphp
        @foreach ($summaryCards as $card)
            <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="flex items-center justify-between">
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</p>
                    <iconify-icon icon="{{ $card['icon'] }}" class="{{ $card['color'] }}" height="18"></iconify-icon>
                </div>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white"
                    x-text="summary.{{ $card['key'] }} !== undefined ? Number(summary.{{ $card['key'] }}).toLocaleString() : '—'">
                    {{ number_format($summary[$card['key']]) }}
                </p>
                <p class="mt-0.5 text-xs text-gray-400">{{ __('In selected range') }}</p>
            </div>
        @endforeach
    </div>

    {{-- ================================================================
         ROW 3: Charts — Orders trend + Status distribution
         ================================================================ --}}
    <div class="grid gap-5 lg:grid-cols-3">

        {{-- Orders over time (line chart) --}}
        <div class="lg:col-span-2 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="mb-4 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('Orders Over Time') }}</h3>
                <div class="flex gap-3 text-xs">
                    @foreach (['created' => ['#6366f1', __('Created')], 'booked' => ['#3b82f6', __('Booked')], 'approved' => ['#22c55e', __('Approved')], 'canceled' => ['#ef4444', __('Canceled')]] as $key => [$color, $label])
                        <span class="flex items-center gap-1">
                            <span class="inline-block h-2.5 w-2.5 rounded-full" style="background:{{ $color }}"></span>
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            </div>
            <div class="relative h-60">
                <canvas id="ordersLineChart"></canvas>
            </div>
        </div>

        {{-- Status distribution (doughnut chart) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 font-semibold text-gray-800 dark:text-white">{{ __('Status Distribution') }}</h3>
            <div class="relative mx-auto h-48 w-48">
                <canvas id="statusDoughnutChart"></canvas>
            </div>
            <div class="mt-4 space-y-1 max-h-32 overflow-y-auto">
                <template x-for="s in statusBreakdown" :key="s.status">
                    <div class="flex items-center justify-between text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="inline-block h-2 w-2 rounded-full" :style="'background:' + (s.color || '#6b7280')"></span>
                            <span class="text-gray-600 dark:text-gray-400" x-text="s.status"></span>
                        </div>
                        <span class="font-medium text-gray-800 dark:text-gray-200" x-text="s.count"></span>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ================================================================
         ROW 4: Top customers + Top companies
         ================================================================ --}}
    <div class="grid gap-5 lg:grid-cols-2">

        {{-- Top customers --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('Customers with Most Orders') }}</h3>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">#</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Customer') }}</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Company') }}</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700" id="customers-table-body">
                        <template x-for="(row, i) in customersWithOrders.slice(0, 20)" :key="i">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                                <td class="px-5 py-2.5 text-xs text-gray-400" x-text="i + 1"></td>
                                <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200" x-text="row.name"></td>
                                <td class="px-5 py-2.5 text-gray-500 dark:text-gray-400" x-text="row.company || '—'"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300"
                                        x-text="row.order_count"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="customersWithOrders.length === 0">
                            <tr><td colspan="4" class="px-5 py-6 text-center text-xs text-gray-400">{{ __('No data.') }}</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Top companies --}}
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('Companies Total Orders') }}</h3>
            </div>
            <div class="max-h-72 overflow-y-auto">
                <table class="w-full border-collapse text-sm">
                    <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/50">
                        <tr>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">#</th>
                            <th class="px-5 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Company') }}</th>
                            <th class="px-5 py-2.5 text-right text-xs font-semibold text-gray-500">{{ __('Orders') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                        <template x-for="(row, i) in companyStats.slice(0, 20)" :key="i">
                            <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                                <td class="px-5 py-2.5 text-xs text-gray-400" x-text="i + 1"></td>
                                <td class="px-5 py-2.5 font-medium text-gray-800 dark:text-gray-200" x-text="row.company"></td>
                                <td class="px-5 py-2.5 text-right">
                                    <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300"
                                        x-text="row.order_count"></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="companyStats.length === 0">
                            <tr><td colspan="3" class="px-5 py-6 text-center text-xs text-gray-400">{{ __('No data.') }}</td></tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================================================================
         ROW 5: Uninvoiced Orders
         ================================================================ --}}
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
            <div class="flex items-center gap-2">
                <h3 class="font-semibold text-gray-800 dark:text-white">{{ __('Uninvoiced Orders') }}</h3>
                <span class="rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                    x-text="uninvoicedOrders.length"></span>
            </div>
            <div class="flex items-center gap-2">
                <input type="text" x-model="uninvoiceSearch" placeholder="{{ __('Search…') }}"
                    class="form-control h-7 text-xs w-40" />
                <a href="{{ route($prefix . '.invoices.pending') }}"
                    class="rounded border border-gray-300 px-3 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                    {{ __('Full View →') }}
                </a>
            </div>
        </div>
        <div class="max-h-80 overflow-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="sticky top-0 bg-gray-50 dark:bg-gray-700/50">
                    <tr>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Order ID') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Customer') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Company') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Service') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Interview Date') }}</th>
                        <th class="px-4 py-2.5 text-left text-xs font-semibold text-gray-500">{{ __('Status') }}</th>
                        <th class="px-4 py-2.5 text-center text-xs font-semibold text-gray-500">{{ __('Invoice Sent') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    <template x-for="order in filteredUninvoiced" :key="order.order_id">
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                            <td class="px-4 py-2.5">
                                <a :href="'{{ url($prefix . '/candidates') }}/' + order.id + '/edit'"
                                    class="font-mono text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400"
                                    x-text="order.order_id"></a>
                            </td>
                            <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300" x-text="order.customer_name"></td>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400" x-text="order.customer_company || '—'"></td>
                            <td class="px-4 py-2.5 text-gray-500 dark:text-gray-400" x-text="order.service_category_name || '—'"></td>
                            <td class="px-4 py-2.5 text-xs text-gray-500" x-text="order.booked ? order.booked.substring(0,10) : '—'"></td>
                            <td class="px-4 py-2.5">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                    :style="'background-color:' + (order.status_color || '#6b7280')"
                                    x-text="order.status_name || '—'"></span>
                            </td>
                            <td class="px-4 py-2.5 text-center">
                                <input type="checkbox" :checked="order.invoice_sent == 1"
                                    @change="toggleInvoiceSent(order, $event.target.checked)"
                                    class="h-4 w-4 cursor-pointer rounded border-gray-300 text-green-600" />
                            </td>
                        </tr>
                    </template>
                    <template x-if="filteredUninvoiced.length === 0">
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-400">
                                {{ __('No uninvoiced orders in this date range.') }}
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

</div>{{-- /x-data --}}

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const analyticsDataUrl  = '{{ route($prefix . '.analytics.data') }}';
const markInvoiceUrl    = '{{ route($prefix . '.analytics.mark-invoice-sent') }}';
const csrfToken         = '{{ csrf_token() }}';

// Server-side initial data (avoids first-load flash)
const initialData = {
    summary:              @json($summary),
    chart_data:           @json($chartData),
    status_breakdown:     @json($statusBreakdown),
    customers_with_orders:@json($customersWithOrders),
    company_stats:        @json($companyStats),
    uninvoiced_orders:    @json($uninvoicedOrders),
};

// Chart instances
let lineChart = null;
let doughnutChart = null;

function analyticsApp() {
    return {
        // ── State ──────────────────────────────────────────────────────────
        loading: false,
        filtersOpen: false,
        preset: '30d',
        filters: {
            start_date:          '{{ $filters['start_date'] }}',
            end_date:            '{{ $filters['end_date'] }}',
            customer_id:         '{{ $filters['customer_id'] ?? '' }}',
            company:             '{{ $filters['company'] ?? '' }}',
            service_category_id: '{{ $filters['service_category_id'] ?? '' }}',
            status_id:           '{{ $filters['status_id'] ?? '' }}',
        },
        // ── Chart / table data ─────────────────────────────────────────────
        summary:              initialData.summary,
        chartData:            initialData.chart_data,
        statusBreakdown:      initialData.status_breakdown,
        customersWithOrders:  initialData.customers_with_orders,
        companyStats:         initialData.company_stats,
        uninvoicedOrders:     initialData.uninvoiced_orders,
        uninvoiceSearch:      '',

        // ── Computed ───────────────────────────────────────────────────────
        get activeFilterCount() {
            return [this.filters.customer_id, this.filters.company,
                    this.filters.service_category_id, this.filters.status_id]
                .filter(Boolean).length;
        },
        get filteredUninvoiced() {
            if (!this.uninvoiceSearch) return this.uninvoicedOrders;
            const q = this.uninvoiceSearch.toLowerCase();
            return this.uninvoicedOrders.filter(o =>
                (o.order_id     || '').toLowerCase().includes(q) ||
                (o.customer_name|| '').toLowerCase().includes(q) ||
                (o.customer_company||'').toLowerCase().includes(q)
            );
        },

        // ── Init ──────────────────────────────────────────────────────────
        init() {
            this.$nextTick(() => {
                this.buildLineChart();
                this.buildDoughnutChart();
            });
        },

        // ── Date presets ──────────────────────────────────────────────────
        setPreset(p) {
            this.preset = p;
            const today = new Date();
            const fmt = d => d.toISOString().substring(0, 10);
            const sub = n => { const d = new Date(today); d.setDate(d.getDate() - n); return d; };
            this.filters.end_date = fmt(today);
            if      (p === '7d')  this.filters.start_date = fmt(sub(6));
            else if (p === '30d') this.filters.start_date = fmt(sub(29));
            else if (p === '90d') this.filters.start_date = fmt(sub(89));
            else if (p === 'ytd') this.filters.start_date = fmt(new Date(today.getFullYear(), 0, 1));
            this.refresh();
        },

        resetFilters() {
            this.filters.customer_id = '';
            this.filters.company = '';
            this.filters.service_category_id = '';
            this.filters.status_id = '';
            this.refresh();
        },

        // ── AJAX refresh ──────────────────────────────────────────────────
        async refresh() {
            this.loading = true;
            const params = new URLSearchParams(
                Object.fromEntries(Object.entries(this.filters).filter(([,v]) => v))
            );
            try {
                const res  = await fetch(`${analyticsDataUrl}?${params}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();
                this.summary             = json.summary;
                this.chartData           = json.chart_data;
                this.statusBreakdown     = json.status_breakdown;
                this.customersWithOrders = json.customers_with_orders;
                this.companyStats        = json.company_stats;
                this.uninvoicedOrders    = json.uninvoiced_orders;
                this.updateLineChart();
                this.updateDoughnutChart();
            } catch (e) {
                console.error('Analytics refresh failed:', e);
            } finally {
                this.loading = false;
            }
        },

        // ── Invoice sent toggle ───────────────────────────────────────────
        async toggleInvoiceSent(order, checked) {
            order.invoice_sent = checked ? 1 : 0;
            await fetch(markInvoiceUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ order_id: order.order_id, invoice_sent: checked }),
            });
            if (checked) {
                this.uninvoicedOrders = this.uninvoicedOrders.filter(o => o.order_id !== order.order_id);
            }
        },

        // ── Chart helpers ─────────────────────────────────────────────────
        buildLineChart() {
            const ctx = document.getElementById('ordersLineChart');
            if (!ctx) return;
            const { labels, created, booked, approved, canceled } = this.parseChartData();
            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels,
                    datasets: [
                        { label: '{{ __("Created") }}',  data: created,  borderColor: '#6366f1', backgroundColor: '#6366f120', tension: 0.4, fill: true },
                        { label: '{{ __("Booked") }}',   data: booked,   borderColor: '#3b82f6', backgroundColor: 'transparent', tension: 0.4 },
                        { label: '{{ __("Approved") }}', data: approved, borderColor: '#22c55e', backgroundColor: 'transparent', tension: 0.4 },
                        { label: '{{ __("Canceled") }}', data: canceled, borderColor: '#ef4444', backgroundColor: 'transparent', tension: 0.4 },
                    ],
                },
                options: {
                    responsive: true, maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { display: false }, ticks: { maxTicksLimit: 8, font: { size: 11 } } },
                        y: { beginAtZero: true, ticks: { font: { size: 11 } } },
                    },
                },
            });
        },

        updateLineChart() {
            if (!lineChart) { this.buildLineChart(); return; }
            const { labels, created, booked, approved, canceled } = this.parseChartData();
            lineChart.data.labels = labels;
            lineChart.data.datasets[0].data = created;
            lineChart.data.datasets[1].data = booked;
            lineChart.data.datasets[2].data = approved;
            lineChart.data.datasets[3].data = canceled;
            lineChart.update();
        },

        parseChartData() {
            const labels   = this.chartData.map(d => d.date.substring(5));
            const created  = this.chartData.map(d => d.created);
            const booked   = this.chartData.map(d => d.booked);
            const approved = this.chartData.map(d => d.approved);
            const canceled = this.chartData.map(d => d.canceled);
            return { labels, created, booked, approved, canceled };
        },

        buildDoughnutChart() {
            const ctx = document.getElementById('statusDoughnutChart');
            if (!ctx) return;
            const { labels, counts, colors } = this.parseDoughnutData();
            doughnutChart = new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{ data: counts, backgroundColor: colors, borderWidth: 2 }],
                },
                options: {
                    responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: {
                        legend: { display: false },
                        tooltip: { callbacks: { label: ctx => ` ${ctx.label}: ${ctx.parsed}` } },
                    },
                },
            });
        },

        updateDoughnutChart() {
            if (!doughnutChart) { this.buildDoughnutChart(); return; }
            const { labels, counts, colors } = this.parseDoughnutData();
            doughnutChart.data.labels = labels;
            doughnutChart.data.datasets[0].data   = counts;
            doughnutChart.data.datasets[0].backgroundColor = colors;
            doughnutChart.update();
        },

        parseDoughnutData() {
            const labels = this.statusBreakdown.map(s => s.status);
            const counts = this.statusBreakdown.map(s => s.count);
            const colors = this.statusBreakdown.map(s => s.color || '#6b7280');
            return { labels, counts, colors };
        },
    };
}
</script>
@endpush

</x-layouts.backend-layout>
