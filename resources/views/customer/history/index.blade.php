@extends('customer.layouts.app')

@section('title', __('Archived Orders') . ' | ' . config('app.name'))
@section('page-title', __('Archived Orders'))

@section('content')

<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    x-data="customerTable('history-tbody', { sort:'created', dir:'desc', perPage:25 })">

    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
            {{ __('Archived / Completed Orders') }}
            <span class="ml-2 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                {{ $orders->count() }}
            </span>
        </h2>
        <div class="relative">
            <iconify-icon icon="lucide:search" width="14"
                class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></iconify-icon>
            <input type="text" placeholder="{{ __('Search...') }}"
                oninput="searchHistory(this.value)"
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
                    @if($isManager)
                    <th class="px-5 py-3 dt-th" @click="sortBy('company')" :class="thClass('company')">
                        {{ __('Company') }}<span x-html="sortIcon('company')"></span>
                    </th>
                    @endif
                    <th class="px-5 py-3 dt-th" @click="sortBy('status')" :class="thClass('status')">
                        {{ __('Status') }}<span x-html="sortIcon('status')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('service')" :class="thClass('service')">
                        {{ __('Service') }}<span x-html="sortIcon('service')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('date')" :class="thClass('date')">
                        {{ __('Date') }}<span x-html="sortIcon('date')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('created')" :class="thClass('created')">
                        {{ __('Created') }}<span x-html="sortIcon('created')"></span>
                    </th>
                    <th class="px-5 py-3 text-right">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody id="history-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($orders as $i => $order)
                @php
                    $date    = $order->booked ?? $order->delivery_date ?? null;
                    $created = $order->created_at ?? $order->created ?? null;
                @endphp
                <tr class="history-row hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    data-row
                    data-search="{{ strtolower($order->order_id . ' ' . $order->name . ' ' . $order->surname . ' ' . ($order->status_title ?? '') . ' ' . ($order->service_name ?? '') . ' ' . ($order->company_name ?? '')) }}"
                    data-sort-orderid="{{ $order->order_id }}"
                    data-sort-name="{{ $order->name }} {{ $order->surname }}"
                    data-sort-company="{{ $order->company_name ?? '' }}"
                    data-sort-status="{{ $order->status_title ?? '' }}"
                    data-sort-service="{{ $order->service_name ?? '' }}"
                    data-sort-date="{{ $date ?? '' }}"
                    data-sort-created="{{ $created ?? '' }}">

                    <td class="px-5 py-3 text-gray-400 dark:text-gray-500 dt-num">{{ $i + 1 }}</td>
                    <td class="px-5 py-3">
                        <a href="{{ route('customer.orders.show', $order->id) }}"
                            class="font-mono text-xs font-semibold text-brand-600 hover:underline dark:text-brand-400">
                            {{ $order->order_id }}
                        </a>
                    </td>
                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                        {{ $order->name }} {{ $order->surname }}
                    </td>
                    @if($isManager)
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $order->company_name ?? '—' }}</td>
                    @endif
                    <td class="px-5 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                            style="background-color: {{ $order->status_color ?? '#94a3b8' }}">
                            {{ $order->status_title ?? '—' }}
                        </span>
                    </td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $order->service_name ?? '—' }}</td>
                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">
                        {{ $date ? \Carbon\Carbon::parse($date)->format('d M Y') : '—' }}
                    </td>
                    <td class="px-5 py-3 text-xs text-gray-500 dark:text-gray-400">
                        {{ $created ? \Carbon\Carbon::parse($created)->format('d M Y') : '—' }}
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
                    <td colspan="{{ $isManager ? 9 : 8 }}" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        <iconify-icon icon="lucide:clock" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
                        {{ __('No archived orders found.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div id="no-history-results" class="hidden px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
        <iconify-icon icon="lucide:search-x" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
        {{ __('No archived orders match your search.') }}
    </div>

    @include('customer.partials.dt-footer')
</div>

@endsection

@push('scripts')
<script>
function searchHistory(term) {
    const q = term.toLowerCase();
    document.querySelectorAll('.history-row').forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
    });
}
</script>
@endpush
