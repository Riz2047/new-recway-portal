<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

{{-- ============================================================
     TOOLBAR
     ============================================================ --}}
<div class="mb-5 flex flex-wrap items-center justify-between gap-3">

    {{-- Status filter tabs --}}
    <div class="flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/60">
        @foreach ([
            'all'            => ['label' => __('All'),            'count' => $counts['all']],
            'to_be_invoiced' => ['label' => __('To Be Invoiced'), 'count' => $counts['to_be_invoiced']],
            'sent'           => ['label' => __('Sent'),           'count' => $counts['sent']],
        ] as $value => $meta)
            <a href="{{ route($prefix . '.invoices.index', ['status' => $value, 'period' => $period, 'search' => $search]) }}"
                class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                    {{ $filter === $value
                        ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ $meta['label'] }}
                @if ($meta['count'] > 0)
                    <span class="rounded-full bg-gray-200 px-1.5 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        {{ $meta['count'] }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    <div class="flex flex-wrap items-center gap-2">
        {{-- Period filter --}}
        <div class="flex gap-1">
            @foreach (['all' => __('All Periods'), 'day' => __('Daily'), 'week' => __('Weekly'), 'month' => __('Monthly')] as $val => $lbl)
                <a href="{{ route($prefix . '.invoices.index', ['status' => $filter, 'period' => $val, 'search' => $search]) }}"
                    class="rounded border px-2.5 py-1 text-xs font-medium transition
                        {{ $period === $val
                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300'
                            : 'border-gray-200 text-gray-500 hover:border-gray-400 dark:border-gray-700 dark:text-gray-400' }}">
                    {{ $lbl }}
                </a>
            @endforeach
        </div>

        {{-- Search --}}
        <form method="GET" action="{{ route($prefix . '.invoices.index') }}" class="flex gap-1.5">
            <input type="hidden" name="status" value="{{ $filter }}">
            <input type="hidden" name="period" value="{{ $period }}">
            <input type="text" name="search" value="{{ $search }}"
                placeholder="{{ __('Customer / company…') }}"
                class="form-control h-8 text-xs w-44" />
            <button type="submit" class="h-8 rounded bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">{{ __('Search') }}</button>
            @if ($search)
                <a href="{{ route($prefix . '.invoices.index', ['status' => $filter, 'period' => $period]) }}"
                    class="h-8 flex items-center rounded border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    {{ __('Clear') }}
                </a>
            @endif
        </form>

        {{-- Quick links --}}
        <a href="{{ route($prefix . '.invoices.pending') }}"
            class="flex items-center gap-1.5 rounded border border-amber-300 bg-amber-50 px-3 py-1.5 text-xs font-medium text-amber-700 hover:bg-amber-100 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ __('Pending Candidates') }}
        </a>

        {{-- Manual generate (admin only) --}}
        @can('update', App\Models\Customer::class)
            @if ($prefix === 'admin')
                <button type="button"
                    x-data
                    @click="$dispatch('open-generate-modal')"
                    class="flex items-center gap-1.5 rounded bg-indigo-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    {{ __('Generate Now') }}
                </button>
            @endif
        @endcan
    </div>
</div>

{{-- Flash messages --}}
@foreach (['success', 'error', 'info'] as $type)
    @if (session($type))
        <div class="mb-4 rounded-md border px-4 py-3 text-sm
            {{ $type === 'success' ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400' : '' }}
            {{ $type === 'error'   ? 'border-red-200   bg-red-50   text-red-700   dark:border-red-800   dark:bg-red-900/20   dark:text-red-400'   : '' }}
            {{ $type === 'info'    ? 'border-blue-200  bg-blue-50  text-blue-700  dark:border-blue-800  dark:bg-blue-900/20  dark:text-blue-400'  : '' }}">
            {{ session($type) }}
        </div>
    @endif
@endforeach

{{-- ============================================================
     INVOICE TABLE
     ============================================================ --}}
<x-card>
    @if ($invoices->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left dark:border-gray-700">
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('#') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Customer') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Period') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Amount') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Candidates') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Due') }}</th>
                        <th class="pb-3 pr-4 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Created') }}</th>
                        <th class="pb-3 text-xs font-semibold text-gray-500 dark:text-gray-400">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($invoices as $invoice)
                        <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                            <td class="py-3 pr-4 font-mono text-xs text-gray-500">#{{ $invoice->id }}</td>

                            <td class="py-3 pr-4">
                                <p class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $invoice->customer?->user?->name ?? '—' }}
                                </p>
                                @if ($invoice->customer?->company)
                                    <p class="text-xs text-gray-400">{{ $invoice->customer->company }}</p>
                                @endif
                            </td>

                            <td class="py-3 pr-4">
                                <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $invoice->period_label }}
                                </span>
                            </td>

                            <td class="py-3 pr-4 font-medium text-gray-800 dark:text-gray-200">
                                {{ number_format((float) $invoice->invoice_amount, 2) }} kr
                            </td>

                            <td class="py-3 pr-4 text-center text-gray-600 dark:text-gray-400">
                                {{ $invoice->getCandidateCount() }}
                            </td>

                            <td class="py-3 pr-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $invoice->status_color }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>

                            <td class="py-3 pr-4 text-xs text-gray-500 dark:text-gray-400">
                                {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                            </td>

                            <td class="py-3 pr-4 text-xs text-gray-400">
                                {{ $invoice->created_date?->format('d M Y') ?? $invoice->created_at?->format('d M Y') }}
                            </td>

                            <td class="py-3">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route($prefix . '.invoices.show', $invoice->id) }}"
                                        class="rounded border border-gray-300 px-2.5 py-1 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                                        {{ __('View') }}
                                    </a>

                                    @can('update', App\Models\Customer::class)
                                        @if ($invoice->status === 'to_be_invoiced')
                                            <form method="POST" action="{{ route($prefix . '.invoices.mark-sent', $invoice->id) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded border border-green-300 bg-green-50 px-2.5 py-1 text-xs font-medium text-green-700 hover:bg-green-100 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
                                                    {{ __('Mark Sent') }}
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route($prefix . '.invoices.mark-pending', $invoice->id) }}">
                                                @csrf
                                                <button type="submit"
                                                    class="rounded border border-gray-300 px-2.5 py-1 text-xs text-gray-500 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                                                    {{ __('Revert') }}
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if ($invoices->hasPages())
            <div class="mt-5 border-t border-gray-100 pt-4 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        @endif
    @else
        <div class="flex flex-col items-center justify-center py-16 text-center">
            <svg class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="font-medium text-gray-500">{{ __('No invoices found.') }}</p>
            <p class="mt-1 text-sm text-gray-400">{{ __('Invoices are generated automatically by the scheduler or manually via "Generate Now".') }}</p>
        </div>
    @endif
</x-card>

{{-- ============================================================
     GENERATE NOW MODAL (admin only)
     ============================================================ --}}
@if ($prefix === 'admin')
<div
    x-data="{ open: false }"
    @open-generate-modal.window="open = true"
    @keydown.escape.window="open = false"
>
    <div x-show="open" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 backdrop-blur-sm p-4"
        @click.self="open = false">
        <div x-show="open"
            x-transition:enter="transition ease-out duration-200 delay-50"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-sm rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
            <h2 class="mb-4 font-semibold text-gray-800 dark:text-white">{{ __('Generate Invoice for Customer') }}</h2>
            <form method="POST" action="{{ route($prefix . '.invoices.generate') }}">
                @csrf
                <div class="mb-4">
                    <label class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Select Customer') }} <span class="text-red-500">*</span>
                    </label>
                    <select name="customer_id" class="form-control w-full" required>
                        <option value="">{{ __('— Choose a customer —') }}</option>
                        @foreach (App\Models\Customer::with('user')->whereNotNull('invoice_period')->orderBy('id')->get() as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->user?->name ?? "#{$c->id}" }}
                                ({{ $c->invoice_period }})
                                @if ($c->company) — {{ $c->company }} @endif
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('This will force-generate an invoice for the selected customer regardless of the normal calendar boundary.') }}
                </p>
                <div class="flex justify-end gap-2">
                    <button type="button" @click="open = false"
                        class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                        {{ __('Cancel') }}
                    </button>
                    <button type="submit"
                        class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        {{ __('Generate') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

</x-layouts.backend-layout>
