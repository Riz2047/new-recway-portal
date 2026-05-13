@extends('customer.layouts.app')

@section('title', __('Dashboard') . ' | ' . config('app.name'))
@section('page-title', __('Dashboard'))

@section('content')

{{-- ── Stats cards ─────────────────────────────────────────────────────── --}}
<div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

    {{-- Active Orders --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Active Orders') }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $activeCount }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-50 dark:bg-blue-900/20">
                <iconify-icon icon="lucide:clipboard-list" width="22" class="text-blue-600 dark:text-blue-400"></iconify-icon>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
            <iconify-icon icon="lucide:activity" width="12"></iconify-icon>
            {{ __('Non-expired orders') }}
        </div>
    </div>

    {{-- Archived Orders --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Archived Orders') }}</p>
                <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $archivedCount }}</p>
            </div>
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-amber-50 dark:bg-amber-900/20">
                <iconify-icon icon="lucide:archive" width="22" class="text-amber-600 dark:text-amber-400"></iconify-icon>
            </div>
        </div>
        <div class="mt-3 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
            <iconify-icon icon="lucide:clock" width="12"></iconify-icon>
            {{ __('Completed & expired') }}
        </div>
    </div>

    {{-- Per-category counts (dynamic) --}}
    @php
        $catColors = ['emerald','purple','rose','sky'];
        $catIcons  = ['lucide:user-check','lucide:search','lucide:repeat-2','lucide:layers'];
        $ci = 0;
    @endphp
    @foreach($serviceCategories as $cat)
        @if(($catCounts[$cat->id] ?? 0) > 0)
        <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $cat->name }}</p>
                    <p class="mt-1 text-3xl font-bold text-gray-900 dark:text-white">{{ $catCounts[$cat->id] }}</p>
                </div>
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-{{ $catColors[$ci % count($catColors)] }}-50 dark:bg-{{ $catColors[$ci % count($catColors)] }}-900/20">
                    <iconify-icon icon="{{ $catIcons[$ci % count($catIcons)] }}" width="22" class="text-{{ $catColors[$ci % count($catColors)] }}-600 dark:text-{{ $catColors[$ci % count($catColors)] }}-400"></iconify-icon>
                </div>
            </div>
            <div class="mt-3 flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400">
                <iconify-icon icon="lucide:activity" width="12"></iconify-icon>
                {{ __('Active') }}
            </div>
        </div>
        @php $ci++; @endphp
        @endif
    @endforeach

</div>

{{-- ── Chart + Status breakdown ────────────────────────────────────────── --}}
<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

    {{-- Line chart: 12-month trend --}}
    <div class="col-span-2 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Orders — Last 12 Months') }}</h2>
        <div id="dashboard-chart" style="min-height:260px"></div>
    </div>

    {{-- Status breakdown --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">
        <h2 class="mb-4 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Status Breakdown') }}</h2>

        @if(empty($statusBreakdown))
            <p class="text-sm text-gray-400 dark:text-gray-500">{{ __('No active orders.') }}</p>
        @else
            <div class="space-y-5 overflow-y-auto" style="max-height:280px">
                @foreach($statusBreakdown as $catName => $rows)
                    <div>
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $catName }}</p>
                        <ul class="space-y-1.5">
                            @foreach($rows as $row)
                            <li class="flex items-center justify-between text-sm">
                                <span class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full" style="background-color: {{ $row['color'] ?? '#94a3b8' }}"></span>
                                    <span class="text-gray-600 dark:text-gray-300">{{ $row['label'] }}</span>
                                </span>
                                <span class="font-semibold text-gray-800 dark:text-white">{{ $row['count'] }}</span>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

{{-- ── Recent Orders — Filter + Table ──────────────────────────────────── --}}

{{-- Filter toolbar --}}
<div class="mb-3 flex items-center justify-between">
    <h2 class="text-base font-semibold text-gray-800 dark:text-white">{{ __('Recent Orders') }}</h2>
    <div class="flex items-center gap-2">
        <button type="button" id="dash-btn-filter" onclick="dashToggleFilter()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800">
            <iconify-icon icon="lucide:filter" width="14"></iconify-icon>
            {{ __('Filter') }}
        </button>
        <button type="button" id="dash-btn-clear" onclick="dashClearFilter()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800" style="display:none!important">
            <iconify-icon icon="lucide:x" width="14"></iconify-icon>
            {{ __('Clear') }}
        </button>
        @if(Route::has('customer.orders.index'))
        <a href="{{ route('customer.orders.index') }}"
            class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
            {{ __('View all') }} →
        </a>
        @endif
    </div>
</div>

{{-- Collapsible filter section --}}
<div id="dash-filter-section" style="display:none"
    class="mb-4 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">

    <p class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
        {{ __('Service Categories') }}
        <span class="ml-1 font-normal text-xs text-gray-400">({{ __('Click on any of the service categories to see the recent orders of that category') }})</span>
    </p>

    <div class="flex flex-wrap gap-2 mb-4" id="dash-cat-buttons">
        <button type="button" class="filter-cat-btn active" id="dash-cat-all"
            data-cat-id="" onclick="dashSelectCat(this)">
            {{ __('All Orders') }}
            <span class="filter-badge">{{ $recentOrders->count() }}</span>
        </button>
        @foreach($serviceCategories as $cat)
        @php $cnt = $recentOrders->where('service_category_id', $cat->id)->count(); @endphp
        @if($cnt > 0)
        <button type="button" class="filter-cat-btn"
            data-cat-id="{{ $cat->id }}"
            onclick="dashSelectCat(this)">
            {{ $cat->name }}
            <span class="filter-badge">{{ $cnt }}</span>
        </button>
        @endif
        @endforeach
    </div>

    <div id="dash-status-row" style="display:none">
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            ({{ __('Click on any one of them from below statuses to filter recent orders table') }})
        </p>
        <div class="flex flex-wrap gap-2">
            @foreach($serviceCategories as $cat)
            @foreach($statusesWithCounts as $status)
            @if($status->status_type == $cat->id)
            <button type="button"
                class="filter-status-btn dash-status-item"
                data-cat-id="{{ $cat->id }}"
                data-status-id="{{ $status->id }}"
                onclick="dashSelectStatus(this)"
                style="display:none">
                {{ $status->status }}
                <span class="filter-badge">{{ $status->count }}</span>
            </button>
            @endif
            @endforeach
            @endforeach
        </div>
    </div>
</div>

{{-- Orders table --}}
<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    x-data="customerTable('dash-orders-tbody', { sort:'created', dir:'desc', perPage:10 })">
    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-700/40 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3 w-12">#</th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('orderid')" :class="thClass('orderid')">
                        {{ __('Order ID') }}<span x-html="sortIcon('orderid')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('name')" :class="thClass('name')">
                        {{ __('Candidate') }}<span x-html="sortIcon('name')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('status')" :class="thClass('status')">
                        {{ __('Status') }}<span x-html="sortIcon('status')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('service')" :class="thClass('service')">
                        {{ __('Service') }}<span x-html="sortIcon('service')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('interview')" :class="thClass('interview')">
                        {{ __('Interview Date') }}<span x-html="sortIcon('interview')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('delivery')" :class="thClass('delivery')">
                        {{ __('Delivery Date') }}<span x-html="sortIcon('delivery')"></span>
                    </th>
                    @if($isManager)
                    <th class="px-5 py-3 dt-th" @click="sortBy('company')" :class="thClass('company')">
                        {{ __('Customer') }}<span x-html="sortIcon('company')"></span>
                    </th>
                    @endif
                    <th class="px-5 py-3">{{ __('Archive In') }}</th>
                </tr>
            </thead>
            <tbody id="dash-orders-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($recentOrders as $i => $order)
                <tr class="dash-order-row hover:bg-gray-50 dark:hover:bg-gray-700/30"
                    data-row
                    data-cat="{{ $order->service_category_id }}"
                    data-status="{{ $order->status }}"
                    data-sort-orderid="{{ $order->order_id }}"
                    data-sort-name="{{ $order->name }} {{ $order->surname }}"
                    data-sort-status="{{ $order->status_title ?? '' }}"
                    data-sort-service="{{ $order->service_name ?? '' }}"
                    data-sort-interview="{{ $order->booked ?? '' }}"
                    data-sort-delivery="{{ $order->delivery_date ?? '' }}"
                    data-sort-company="{{ $order->company_name ?? '' }}"
                    data-sort-created="{{ $order->created_at ?? '' }}">
                    <td class="px-5 py-3 text-gray-500 dark:text-gray-400 dt-num">{{ $i + 1 }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('customer.orders.show', $order->id) }}"
                            class="font-mono text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            {{ $order->order_id }}
                        </a>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                        {{ $order->name }} {{ $order->surname }}
                    </td>
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                            style="background-color: {{ $order->status_color ?? '#94a3b8' }}">
                            {{ $order->status_title ?? '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $order->service_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                        {{ $order->booked ? \Carbon\Carbon::parse($order->booked)->format('d M Y') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                        {{ $order->delivery_date ? \Carbon\Carbon::parse($order->delivery_date)->format('d M Y') : '—' }}
                    </td>
                    @if($isManager)
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $order->company_name ?? '—' }}</td>
                    @endif
                    <td class="px-5 py-3">
                        @if($order->days_to_archive === 'N/A')
                            <span class="text-gray-400">—</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium text-amber-600 dark:text-amber-400">
                                <iconify-icon icon="lucide:clock" width="12"></iconify-icon>
                                {{ $order->days_to_archive }} {{ __('days') }}
                            </span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isManager ? 9 : 8 }}" class="px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
                        <iconify-icon icon="lucide:inbox" width="32" class="mx-auto mb-2 block opacity-40"></iconify-icon>
                        {{ __('No active orders found.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div id="dash-no-results" class="hidden px-5 py-10 text-center text-sm text-gray-400 dark:text-gray-500">
        <iconify-icon icon="lucide:search-x" width="32" class="mx-auto mb-2 block opacity-40"></iconify-icon>
        {{ __('No orders match your filter.') }}
    </div>

    @include('customer.partials.dt-footer')
</div>

@endsection

@push('styles')
<script src="https://cdn.jsdelivr.net/npm/apexcharts@latest/dist/apexcharts.min.js"></script>
<style>
.filter-cat-btn, .filter-status-btn {
    display: inline-flex; align-items: center; gap: 8px;
    padding: 8px 18px; border-radius: 50px;
    border: 2px solid #8b2b2d; background: transparent;
    color: #8b2b2d; font-size: 13px; font-weight: 500;
    cursor: pointer; transition: all .2s; white-space: nowrap;
}
.filter-cat-btn:hover, .filter-cat-btn.active,
.filter-status-btn:hover, .filter-status-btn.active {
    background: #8b2b2d; color: #fff;
}
.dark .filter-cat-btn, .dark .filter-status-btn { border-color: #c47375; color: #c47375; }
.dark .filter-cat-btn:hover, .dark .filter-cat-btn.active,
.dark .filter-status-btn:hover, .dark .filter-status-btn.active {
    background: #8b2b2d; color: #fff; border-color: #8b2b2d;
}
.filter-badge {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 22px; height: 22px; border-radius: 50%;
    background: #f3e8e8; color: #8b2b2d;
    font-size: 11px; font-weight: 700;
}
.filter-cat-btn.active .filter-badge,
.filter-status-btn.active .filter-badge {
    background: rgba(255,255,255,.25); color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {

    // ── ApexCharts line chart ──────────────────────────────────────────
    const months  = @json($months);
    const series  = @json($chartData);
    const isDark  = document.documentElement.classList.contains('dark')
                    || JSON.parse(localStorage.getItem('darkMode') ?? 'false');

    const chartColors = ['#3b82f6', '#8b5cf6', '#10b981', '#f59e0b', '#ef4444'];

    const options = {
        chart: {
            type: 'line',
            height: 260,
            toolbar: { show: false },
            background: 'transparent',
            animations: { enabled: true, speed: 600 },
        },
        theme: { mode: isDark ? 'dark' : 'light' },
        series: series.length ? series : [{ name: '{{ __("Orders") }}', data: Array(12).fill(0) }],
        xaxis: {
            categories: months,
            labels: { style: { fontSize: '11px' } },
        },
        yaxis: {
            min: 0,
            labels: { formatter: v => Math.round(v) },
        },
        colors: chartColors,
        stroke: { curve: 'smooth', width: 2.5 },
        markers: { size: 3 },
        legend: { position: 'top', fontSize: '12px' },
        tooltip: { shared: true, intersect: false },
        grid: { borderColor: isDark ? '#374151' : '#e5e7eb', strokeDashArray: 4 },
        dataLabels: { enabled: false },
    };

    const chart = new ApexCharts(document.querySelector('#dashboard-chart'), options);
    chart.render();

    // Re-render when dark mode toggles
    document.addEventListener('darkModeChanged', function (e) {
        chart.updateOptions({ theme: { mode: e.detail ? 'dark' : 'light' } });
    });
});
</script>

<script>
// ── Dashboard filter ──────────────────────────────────────────────────────
let dashCatId    = '';
let dashStatusId = '';

function dashToggleFilter() {
    const sec    = document.getElementById('dash-filter-section');
    const isHide = sec.style.display === 'none' || sec.style.display === '';
    sec.style.display = isHide ? 'block' : 'none';
    document.getElementById('dash-btn-clear').style.display = isHide ? 'inline-flex' : 'none';
}

function dashClearFilter() {
    dashCatId = ''; dashStatusId = '';
    document.querySelectorAll('#dash-cat-buttons .filter-cat-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('dash-cat-all').classList.add('active');
    document.querySelectorAll('.dash-status-item').forEach(b => { b.style.display='none'; b.classList.remove('active'); });
    document.getElementById('dash-status-row').style.display = 'none';
    document.getElementById('dash-filter-section').style.display = 'none';
    document.getElementById('dash-btn-clear').style.display = 'none';
    dashApplyFilter();
}

function dashSelectCat(btn) {
    dashCatId = btn.dataset.catId; dashStatusId = '';
    document.querySelectorAll('#dash-cat-buttons .filter-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    const sr = document.getElementById('dash-status-row');
    if (dashCatId) {
        sr.style.display = 'block';
        document.querySelectorAll('.dash-status-item').forEach(b => {
            b.style.display = b.dataset.catId === dashCatId ? 'inline-flex' : 'none';
            b.classList.remove('active');
        });
    } else {
        sr.style.display = 'none';
        document.querySelectorAll('.dash-status-item').forEach(b => { b.style.display='none'; b.classList.remove('active'); });
    }
    dashApplyFilter();
}

function dashSelectStatus(btn) {
    if (btn.classList.contains('active')) {
        dashStatusId = ''; btn.classList.remove('active');
    } else {
        dashStatusId = btn.dataset.statusId;
        document.querySelectorAll('.dash-status-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    dashApplyFilter();
}

function dashApplyFilter() {
    const rows = document.querySelectorAll('.dash-order-row');
    let visible = 0;
    rows.forEach(row => {
        const ok = (!dashCatId    || row.dataset.cat    === dashCatId)
                && (!dashStatusId || row.dataset.status === dashStatusId);
        row.style.display = ok ? '' : 'none';
        if (ok) visible++;
    });
    document.getElementById('dash-no-results').classList.toggle('hidden', visible > 0);
}
</script>
@endpush
