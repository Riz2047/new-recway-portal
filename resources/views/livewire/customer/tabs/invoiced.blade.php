<div class="py-6">

    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Invoiced') }}
        </h3>
        <a
            href="{{ route('admin.invoices.index') }}?customer={{ $customerId }}"
            class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30"
        >
            {{ __('View All Invoices') }}
        </a>
    </div>

    {{-- Stats cards --}}
    <div class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-4">

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
            <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-gray-400">{{ __('Total') }}</p>
            <p class="text-2xl font-bold text-gray-800 dark:text-gray-100">{{ number_format($stats->total ?? 0) }}</p>
        </div>

        <div class="rounded-xl border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-900 dark:bg-yellow-950/30">
            <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-yellow-500">{{ __('Pending') }}</p>
            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300">{{ number_format($stats->pending ?? 0) }}</p>
        </div>

        <div class="rounded-xl border border-green-200 bg-green-50 p-4 dark:border-green-900 dark:bg-green-950/30">
            <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-green-500">{{ __('Sent') }}</p>
            <p class="text-2xl font-bold text-green-700 dark:text-green-300">{{ number_format($stats->sent ?? 0) }}</p>
        </div>

        <div class="rounded-xl border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-900 dark:bg-indigo-950/30">
            <p class="mb-1 text-[11px] font-medium uppercase tracking-wide text-indigo-500">{{ __('Total Amount') }}</p>
            <p class="text-2xl font-bold text-indigo-700 dark:text-indigo-300">
                {{ number_format((float) ($stats->total_amount ?? 0), 2) }} kr
            </p>
        </div>
    </div>

    {{-- Filter toolbar --}}
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">

        {{-- Status tabs --}}
        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/60">
            @foreach([
                'all'              => __('All'),
                'to_be_invoiced'   => __('Pending'),
                'sent'             => __('Sent'),
            ] as $key => $label)
                <button
                    type="button"
                    wire:click="$set('filterStatus', '{{ $key }}')"
                    @class([
                        'rounded-md px-3 py-1.5 text-xs font-medium transition',
                        'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white' => $filterStatus === $key,
                        'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $filterStatus !== $key,
                    ])
                >{{ $label }}</button>
            @endforeach
        </div>

        {{-- Period filter --}}
        <div class="flex items-center gap-1">
            @foreach(['all' => __('All'), 'day' => __('Daily'), 'week' => __('Weekly'), 'month' => __('Monthly')] as $val => $lbl)
                <button
                    type="button"
                    wire:click="$set('filterPeriod', '{{ $val }}')"
                    @class([
                        'rounded border px-2.5 py-1 text-xs font-medium transition',
                        'border-indigo-500 bg-indigo-50 text-indigo-700 dark:border-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-300' => $filterPeriod === $val,
                        'border-gray-200 text-gray-500 hover:border-gray-400 dark:border-gray-700 dark:text-gray-400' => $filterPeriod !== $val,
                    ])
                >{{ $lbl }}</button>
            @endforeach
        </div>

        {{-- Per-page --}}
        <select
            wire:model.live="perPage"
            class="rounded-lg border border-gray-300 bg-gray-50 px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
        >
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    {{-- Invoices table --}}
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">#</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Period') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-center text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Orders') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Amount') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Status') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Due Date') }}</th>
                        <th class="border-b border-gray-200 px-4 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">{{ __('Created') }}</th>
                        <th class="w-10 border-b border-gray-200 px-2 py-3 dark:border-gray-700"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        <tr wire:key="invoice-{{ $invoice->id }}" class="border-b border-gray-100 last:border-0 dark:border-gray-700 hover:bg-gray-50/60 dark:hover:bg-gray-800/60">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">
                                {{ $invoice->id }}
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $periodClasses = match($invoice->period) {
                                        'day'   => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                        'week'  => 'bg-violet-100 text-violet-700 dark:bg-violet-900/40 dark:text-violet-300',
                                        'month' => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                                        default => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400',
                                    };
                                @endphp
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $periodClasses }}">
                                    {{ $invoice->period_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                    {{ $invoice->getCandidateCount() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-800 dark:text-gray-200">
                                {{ number_format((float) $invoice->invoice_amount, 2) }} kr
                            </td>
                            <td class="px-4 py-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $invoice->status_color }}">
                                    {{ $invoice->status_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $invoice->due_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500 dark:text-gray-400">
                                {{ $invoice->created_date?->format('d M Y') ?? $invoice->created_at?->format('d M Y') ?? '—' }}
                            </td>
                            <td class="px-2 py-3 text-right">
                                <a
                                    href="{{ route('admin.invoices.show', $invoice->id) }}"
                                    class="inline-flex items-center justify-center rounded p-1 text-gray-400 transition hover:bg-indigo-50 hover:text-indigo-600 dark:hover:bg-indigo-900/30"
                                    title="{{ __('View') }}"
                                >
                                    <svg class="h-3.5 w-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                        <path d="M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5Z"/><circle cx="8" cy="8" r="2"/>
                                    </svg>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                                @if($filterStatus !== 'all' || $filterPeriod !== 'all')
                                    {{ __('No invoices match the selected filters.') }}
                                @else
                                    {{ __('No invoices found for this customer.') }}
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($invoices->hasPages())
            <div class="border-t border-gray-100 px-4 py-3 dark:border-gray-700">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
</div>
