<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

{{-- ============================================================
     TOOLBAR
     ============================================================ --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">
    <div class="flex flex-wrap items-center gap-2">
        {{-- Period filter --}}
        <div class="flex gap-1">
            @foreach (['all' => __('All Periods'), 'day' => __('Daily'), 'week' => __('Weekly'), 'month' => __('Monthly')] as $val => $lbl)
                @php $cnt = $val === 'all' ? array_sum($periodCounts) : ($periodCounts[$val] ?? 0); @endphp
                <a href="{{ route($prefix . '.invoices.pending', ['period' => $val, 'search' => $search]) }}"
                    class="flex items-center gap-1.5 rounded border px-2.5 py-1 text-xs font-medium transition
                        {{ $periodFilter === $val
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                            : 'border-gray-200 text-gray-500 hover:border-gray-400 dark:border-gray-700 dark:text-gray-400' }}">
                    {{ $lbl }}
                    @if ($cnt > 0)
                        <span class="rounded-full bg-white/80 px-1 text-xs {{ $periodFilter === $val ? 'text-indigo-700' : 'text-gray-500' }}">
                            {{ $cnt }}
                        </span>
                    @endif
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route($prefix . '.invoices.pending') }}" class="flex gap-1.5">
            <input type="hidden" name="period" value="{{ $periodFilter }}">
            <input type="text" name="search" value="{{ $search }}"
                placeholder="{{ __('Order ID, name…') }}"
                class="form-control h-8 text-xs w-44" />
            <button type="submit" class="h-8 rounded bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Search') }}</button>
            @if ($search)
                <a href="{{ route($prefix . '.invoices.pending', ['period' => $periodFilter]) }}"
                    class="h-8 flex items-center rounded border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    {{ __('Clear') }}
                </a>
            @endif
        </form>
    </div>

    <a href="{{ route($prefix . '.invoices.index') }}"
        class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
        ← {{ __('Invoices') }}
    </a>
</div>

{{-- Flash --}}
@if (session('success'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
        {{ session('success') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
        {{ session('error') }}
    </div>
@endif

{{-- ============================================================
     BULK MARK SENT FORM
     ============================================================ --}}
@if ($candidates->isNotEmpty())
<form method="POST" action="{{ route($prefix . '.invoices.bulk-mark-sent') }}" id="bulk-form"
    x-data="{ selected: [], selectAll: false }">
    @csrf

    <x-card>
        <x-slot name="header">
            <div class="flex items-center justify-between">
                <span>
                    {{ __('Candidates Awaiting Invoice') }}
                    <span class="ml-2 rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        {{ $candidates->total() }}
                    </span>
                </span>
                <button type="submit"
                    x-show="selected.length > 0"
                    x-cloak
                    class="flex items-center gap-1.5 rounded bg-green-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-green-700"
                    onclick="document.querySelectorAll('input[name=\'ids[]\']:checked').forEach(el => { const h = document.createElement('input'); h.type='hidden'; h.name='ids[]'; h.value=el.value; document.getElementById('bulk-form').appendChild(h); })">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span x-text="`${__('Mark')} ${selected.length} {{ __('as Sent') }}`">{{ __('Mark Selected as Sent') }}</span>
                </button>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs dark:border-gray-700">
                        <th class="pb-2 pr-3 w-8">
                            <input type="checkbox"
                                class="h-4 w-4 rounded border-gray-300 text-indigo-600"
                                x-model="selectAll"
                                @change="selected = selectAll
                                    ? [...document.querySelectorAll('.row-check')].map(el => el.value)
                                    : []">
                        </th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Order ID') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Name') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Customer') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Period') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Service') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Status') }}</th>
                        <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Service Cost') }}</th>
                        <th class="pb-2 font-semibold text-gray-500">{{ __('Travel Cost') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($candidates as $c)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                            <td class="py-2.5 pr-3">
                                <input type="checkbox" name="ids[]" value="{{ $c->id }}"
                                    class="row-check h-4 w-4 rounded border-gray-300 text-indigo-600"
                                    x-model="selected"
                                    :value="{{ $c->id }}">
                            </td>
                            <td class="py-2.5 pr-4">
                                <a href="{{ route($prefix . '.candidates.edit', $c->id) }}"
                                    class="font-mono text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                    {{ $c->order_id }}
                                </a>
                            </td>
                            <td class="py-2.5 pr-4 font-medium text-gray-800 dark:text-gray-200">
                                {{ $c->name }} {{ $c->surname }}
                            </td>
                            <td class="py-2.5 pr-4 text-gray-600 dark:text-gray-400">
                                {{ $c->customer?->user?->name ?? '—' }}
                                @if ($c->customer?->company)
                                    <span class="text-xs text-gray-400"> / {{ $c->customer->company }}</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4">
                                @if ($c->customer?->invoice_period)
                                    <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $c->customer->invoice_period }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-gray-500">{{ $c->serviceType?->name ?? '—' }}</td>
                            <td class="py-2.5 pr-4">
                                @if ($c->statusRelation)
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                        style="background-color:{{ $c->statusRelation->color ?: '#6b7280' }}">
                                        {{ $c->statusRelation->status }}
                                    </span>
                                @endif
                            </td>
                            <td class="py-2.5 pr-4 text-gray-700 dark:text-gray-300">
                                {{ $c->service_cost ? number_format((float)$c->service_cost, 2) . ' kr' : '—' }}
                            </td>
                            <td class="py-2.5 text-gray-700 dark:text-gray-300">
                                {{ $c->travel_cost ? number_format((float)$c->travel_cost, 2) . ' kr' : '—' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($candidates->hasPages())
            <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                {{ $candidates->links() }}
            </div>
        @endif
    </x-card>
</form>

@else
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 py-16 text-center dark:border-gray-700">
        <svg class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <p class="font-medium text-gray-500">{{ __('No candidates awaiting invoice.') }}</p>
        <p class="mt-1 text-sm text-gray-400">{{ __('All candidates have been invoiced.') }}</p>
    </div>
@endif

</x-layouts.backend-layout>
