@extends('customer.layouts.app')

@section('title', __('Orders') . ' | ' . config('app.name'))
@section('page-title', __('Orders'))

@push('styles')
<style>
/* Category & status filter buttons */
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
.dark .filter-cat-btn, .dark .filter-status-btn {
    border-color: #c47375; color: #c47375;
}
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

@section('content')

{{-- ── Top action bar ──────────────────────────────────────────────────── --}}
<div class="mb-4 flex items-center justify-between">
    <p class="text-sm text-gray-500 dark:text-gray-400">
        {{ $orders->count() }} {{ __('active order(s)') }}
    </p>
    <div class="flex items-center gap-2">
        <button type="button" id="btn-filter" onclick="toggleFilter()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800 dark:border-brand-600">
            <iconify-icon icon="lucide:filter" width="14"></iconify-icon>
            {{ __('Filter') }}
        </button>
        <button type="button" id="btn-clear" onclick="clearFilter()"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-brand-700 px-4 py-2 text-sm font-medium text-white hover:bg-brand-800 dark:border-brand-600" style="display:none!important">
            <iconify-icon icon="lucide:x" width="14"></iconify-icon>
            {{ __('Clear') }}
        </button>
        <a href="{{ route('customer.orders.create') }}"
            class="btn-primary inline-flex items-center gap-2">
            <iconify-icon icon="lucide:plus" width="16"></iconify-icon>
            {{ __('Create Order') }}
        </a>
    </div>
</div>

{{-- ── Filter section (hidden by default) ────────────────────────────────── --}}
<div id="filter-section" style="display:none" class="mb-5 rounded-xl border border-gray-200 bg-white p-5 dark:border-gray-700 dark:bg-gray-800">

    {{-- Service Categories --}}
    <p class="mb-3 text-sm font-semibold text-gray-700 dark:text-gray-200">
        {{ __('Service Categories') }}
        <span class="ml-1 font-normal text-xs text-gray-400">
            ({{ __('Click on any of the service categories to see recent orders of that category') }})
        </span>
    </p>

    <div class="flex flex-wrap gap-2 mb-4" id="cat-buttons">
        {{-- All Orders --}}
        <button type="button"
            class="filter-cat-btn active" id="cat-btn-all"
            data-cat-id="" onclick="selectCategory(this)">
            {{ __('All Orders') }}
            <span class="filter-badge">{{ $orders->count() }}</span>
        </button>

        @foreach($serviceCategories as $cat)
        @php $cnt = $catCounts->get($cat->id, 0); @endphp
        @if($cnt > 0 || $orders->count() === 0)
        <button type="button"
            class="filter-cat-btn"
            data-cat-id="{{ $cat->id }}"
            onclick="selectCategory(this)">
            {{ $cat->name }}
            <span class="filter-badge">{{ $cnt }}</span>
        </button>
        @endif
        @endforeach
    </div>

    {{-- Status row (hidden until category selected) --}}
    <div id="status-row" style="display:none">
        <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
            ({{ __('Click on any one of them from below statuses to filter recent orders table') }})
        </p>
        <div class="flex flex-wrap gap-2" id="status-buttons">
            @foreach($serviceCategories as $cat)
            @foreach($statusesWithCounts as $status)
            @if($status->status_type == $cat->id)
            <button type="button"
                class="filter-status-btn status-btn-item"
                data-cat-id="{{ $cat->id }}"
                data-status-id="{{ $status->id }}"
                onclick="selectStatus(this)"
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

{{-- ── Orders table ─────────────────────────────────────────────────────── --}}
<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    x-data="customerTable('orders-tbody', { sort:'name', perPage:25 })">

    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Active Orders') }}</h2>
        <div class="relative">
            <iconify-icon icon="lucide:search" width="14"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></iconify-icon>
            <input id="order-search" type="text"
                placeholder="{{ __('Search orders...') }}"
                oninput="applyFilters()"
                class="rounded-lg border border-gray-200 bg-gray-50 py-2 pl-8 pr-4 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400">
        </div>
    </div>

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
                    <th class="px-5 py-3 dt-th" @click="sortBy('staff')" :class="thClass('staff')">
                        {{ __('Staff') }}<span x-html="sortIcon('staff')"></span>
                    </th>
                    @if($isManager)
                    <th class="px-5 py-3 dt-th" @click="sortBy('company')" :class="thClass('company')">
                        {{ __('Customer') }}<span x-html="sortIcon('company')"></span>
                    </th>
                    @endif
                    <th class="px-5 py-3">{{ __('Archive In') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody id="orders-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($orders as $i => $order)
                <tr class="order-row hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    data-row
                    data-cat="{{ $order->service_category_id }}"
                    data-status="{{ $order->status }}"
                    data-search="{{ strtolower($order->order_id . ' ' . $order->name . ' ' . $order->surname . ' ' . ($order->status_title ?? '') . ' ' . ($order->service_name ?? '')) }}"
                    data-sort-orderid="{{ $order->order_id }}"
                    data-sort-name="{{ $order->name }} {{ $order->surname }}"
                    data-sort-status="{{ $order->status_title ?? '' }}"
                    data-sort-service="{{ $order->service_name ?? '' }}"
                    data-sort-interview="{{ $order->booked ?? '' }}"
                    data-sort-delivery="{{ $order->delivery_date ?? '' }}"
                    data-sort-staff="{{ $order->staff_name ?? '' }}"
                    data-sort-company="{{ $order->company_name ?? '' }}">

                    <td class="px-5 py-3 text-gray-400 dark:text-gray-500 dt-num">{{ $i + 1 }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('customer.orders.show', $order->id) }}"
                            class="font-mono text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            {{ $order->order_id }}
                        </a>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                        <a href="{{ route('customer.orders.show', $order->id) }}"
                            class="hover:text-brand-600 dark:hover:text-brand-400">
                            {{ $order->name }} {{ $order->surname }}
                        </a>
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
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $order->staff_name ?? '—' }}</td>
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
                    <td class="px-5 py-3 text-right">
                        <a href="{{ route('customer.orders.show', $order->id) }}"
                            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            <iconify-icon icon="lucide:eye" width="12"></iconify-icon>
                            {{ __('View') }}
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="{{ $isManager ? 11 : 10 }}" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        <iconify-icon icon="lucide:inbox" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
                        {{ __('No active orders found.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="no-results" class="hidden px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
        <iconify-icon icon="lucide:search-x" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
        {{ __('No orders match your filter.') }}
    </div>

    @include('customer.partials.dt-footer')
</div>

@endsection

@push('scripts')
<script>
let activeCatId   = sessionStorage.getItem('orders_cat')   || '';
let activeStatusId = sessionStorage.getItem('orders_status') || '';

// ── Filter toggle ────────────────────────────────────────────────────────
function toggleFilter() {
    const sec = document.getElementById('filter-section');
    const isHidden = sec.style.display === 'none' || sec.style.display === '';
    sec.style.display = isHidden ? 'block' : 'none';
    document.getElementById('btn-clear').style.display = isHidden ? 'inline-flex' : 'none';
    if (isHidden && activeCatId) restoreFilter();
}

// ── Clear all filters ────────────────────────────────────────────────────
function clearFilter() {
    activeCatId    = '';
    activeStatusId = '';
    sessionStorage.removeItem('orders_cat');
    sessionStorage.removeItem('orders_status');

    // Reset buttons
    document.querySelectorAll('.filter-cat-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('cat-btn-all').classList.add('active');
    document.querySelectorAll('.filter-status-btn').forEach(b => { b.style.display='none'; b.classList.remove('active'); });
    document.getElementById('status-row').style.display = 'none';
    document.getElementById('order-search').value = '';
    document.getElementById('btn-clear').style.display = 'none';
    document.getElementById('filter-section').style.display = 'none';

    applyFilters();
}

// ── Select service category ──────────────────────────────────────────────
function selectCategory(btn) {
    const catId = btn.dataset.catId;
    activeCatId    = catId;
    activeStatusId = '';
    sessionStorage.setItem('orders_cat', catId);
    sessionStorage.removeItem('orders_status');

    // Update active state
    document.querySelectorAll('.filter-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Status row
    const statusRow = document.getElementById('status-row');
    if (catId) {
        statusRow.style.display = 'block';
        // Show only status buttons for this category
        document.querySelectorAll('.status-btn-item').forEach(b => {
            b.style.display = b.dataset.catId === catId ? 'inline-flex' : 'none';
            b.classList.remove('active');
        });
    } else {
        statusRow.style.display = 'none';
        document.querySelectorAll('.status-btn-item').forEach(b => { b.style.display='none'; b.classList.remove('active'); });
    }

    applyFilters();
}

// ── Select status ────────────────────────────────────────────────────────
function selectStatus(btn) {
    const statusId = btn.dataset.statusId;
    // Toggle: clicking active status deselects it
    if (btn.classList.contains('active')) {
        activeStatusId = '';
        sessionStorage.removeItem('orders_status');
        btn.classList.remove('active');
    } else {
        activeStatusId = statusId;
        sessionStorage.setItem('orders_status', statusId);
        document.querySelectorAll('.status-btn-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
    }
    applyFilters();
}

// ── Apply all active filters to table rows ───────────────────────────────
function applyFilters() {
    const search = (document.getElementById('order-search').value || '').toLowerCase();
    const rows   = document.querySelectorAll('.order-row');
    let visible  = 0;

    rows.forEach(row => {
        const matchCat    = !activeCatId    || row.dataset.cat    === activeCatId;
        const matchStatus = !activeStatusId || row.dataset.status === activeStatusId;
        const matchSearch = !search         || row.dataset.search.includes(search);
        const show = matchCat && matchStatus && matchSearch;
        row.style.display = show ? '' : 'none';
        if (show) visible++;
    });

    document.getElementById('no-results').classList.toggle('hidden', visible > 0);
}

// ── Restore filter state from sessionStorage ─────────────────────────────
function restoreFilter() {
    if (activeCatId) {
        const catBtn = document.querySelector(`.filter-cat-btn[data-cat-id="${activeCatId}"]`);
        if (catBtn) selectCategory(catBtn);
    }
    if (activeStatusId) {
        const statusBtn = document.querySelector(`.status-btn-item[data-status-id="${activeStatusId}"]`);
        if (statusBtn) selectStatus(statusBtn);
    }
}

// ── Init on page load ────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    if (activeCatId || activeStatusId) {
        document.getElementById('filter-section').style.display = 'block';
        document.getElementById('btn-clear').style.display = 'inline-flex';
        restoreFilter();
        applyFilters();
    }
});
</script>
@endpush
