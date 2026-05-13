<div class="py-6">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Orders') }}
        </h3>
        <a
            href="{{ route('admin.candidates.index') }}?cus_id={{ $customerId }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30"
        >
            {{ __('View All Orders') }}
        </a>
    </div>

    {{-- Stats row --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">

        {{-- Total orders card --}}
        <div class="flex items-center gap-4 rounded-xl border border-indigo-100 bg-indigo-50 p-5 dark:border-indigo-900 dark:bg-indigo-950/30">
            <div class="flex h-12 w-12 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900">
                <svg class="h-6 w-6 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wide text-indigo-500 dark:text-indigo-400">{{ __('Total Orders') }}</p>
                <p class="text-3xl font-bold text-indigo-700 dark:text-indigo-300">{{ number_format($totalCount) }}</p>
            </div>
        </div>

        {{-- Per-status counts --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            @if($statusCounts->isEmpty())
                <p class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No orders found.') }}</p>
            @else
                <table class="w-full border-collapse text-sm">
                    <tbody>
                        @foreach($statusCounts as $row)
                            <tr class="border-b border-gray-100 last:border-0 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-800">
                                <td class="px-4 py-2.5">
                                    <span class="inline-flex items-center gap-1.5">
                                        @if($row->color)
                                            <span class="inline-block h-2 w-2 rounded-full" style="background-color: {{ $row->color }}"></span>
                                        @endif
                                        <span class="font-medium text-gray-800 dark:text-gray-200">{{ $row->status_name }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-right">
                                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $row->cnt }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

    {{-- Order history --}}
    <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

        {{-- Table toolbar --}}
        <div class="flex items-center justify-between border-b border-gray-100 px-4 py-3 dark:border-gray-700">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">{{ __('Order History') }}</h4>
            <div class="flex items-center gap-3">
                <select
                    wire:model.live="perPage"
                    class="rounded-lg border border-gray-300 bg-gray-50 px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                >
                    <option value="15">15</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>
                <div class="relative">
                    <svg class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6">
                        <circle cx="7" cy="7" r="5"/><path stroke-linecap="round" d="m12 12 3 3"/>
                    </svg>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="{{ __('Search order ID or name…') }}"
                        class="w-52 rounded-lg border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                    />
                </div>
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Order ID') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Name') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Service') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Invoice') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Date') }}</th>
                        <th class="w-10 border-b border-gray-200 px-2 py-3 dark:border-gray-700"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="border-b border-gray-100 last:border-0 dark:border-gray-700 hover:bg-gray-50/60 dark:hover:bg-gray-800/60 {{ $order->expired ? 'opacity-50' : '' }}">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 dark:text-gray-400">
                                {{ $order->order_id ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ trim($order->name . ' ' . $order->surname) ?: '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">
                                {{ $order->service_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($order->status_name)
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        style="{{ $order->status_color ? 'background-color:' . $order->status_color . '22; color:' . $order->status_color : 'background-color:#f3f4f6; color:#6b7280' }}"
                                    >
                                        @if($order->status_color)
                                            <span class="inline-block h-1.5 w-1.5 rounded-full" style="background-color:{{ $order->status_color }}"></span>
                                        @endif
                                        {{ $order->status_name }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($order->invoice_sent)
                                    <svg class="mx-auto h-4 w-4 text-green-500" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l3.5 3.5L13 4.5"/>
                                    </svg>
                                @else
                                    <span class="text-xs text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                @php
                                    $displayDate = $order->delivery_date ?? ($order->booked ? \Carbon\Carbon::parse($order->booked) : null);
                                @endphp
                                {{ $displayDate ? \Carbon\Carbon::parse($displayDate)->format('d M Y') : '—' }}
                            </td>
                            <td class="px-2 py-3 text-right">
                                <a
                                    href="{{ route('admin.candidates.edit', $order->id) }}"
                                    class="inline-flex items-center justify-center rounded p-1 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30"
                                    title="{{ __('Edit') }}"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M11 2.5a1.5 1.5 0 0 1 2.5 1.5L5 13l-3 1 1-3 8.5-8.5Z"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                {{ $search ? __('No orders match your search.') : __('No orders found for this customer.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($orders->hasPages())
            <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
