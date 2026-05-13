<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

{{-- Header band --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div>
        <h1 class="text-lg font-semibold text-gray-900 dark:text-white">
            {{ __('Invoice') }} <span class="font-mono text-gray-400">#{{ $invoice->id }}</span>
        </h1>
        <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
            {{ $invoice->customer?->user?->name ?? '—' }}
            @if ($invoice->customer?->company) — {{ $invoice->customer->company }} @endif
        </p>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium {{ $invoice->status_color }}">
            {{ $invoice->status_label }}
        </span>

        @can('update', App\Models\Customer::class)
            @if ($invoice->status === 'to_be_invoiced')
                <form method="POST" action="{{ route($prefix . '.invoices.mark-sent', $invoice->id) }}">
                    @csrf
                    <button type="submit"
                        class="rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700">
                        {{ __('Mark as Sent') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route($prefix . '.invoices.mark-pending', $invoice->id) }}">
                    @csrf
                    <button type="submit"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                        {{ __('Revert to Pending') }}
                    </button>
                </form>
            @endif
        @endcan

        <a href="{{ route($prefix . '.invoices.index') }}"
            class="rounded-md border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
            ← {{ __('Back') }}
        </a>
    </div>
</div>

{{-- Flash --}}
@if (session('success'))
    <div class="mb-4 rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
        {{ session('success') }}
    </div>
@endif

<div class="grid gap-6 lg:grid-cols-3">

    {{-- LEFT: candidate list --}}
    <div class="lg:col-span-2">
        <x-card>
            <x-slot name="header">
                {{ __('Included Candidates') }}
                <span class="ml-2 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    {{ $invoice->getCandidateCount() }}
                </span>
            </x-slot>

            @if ($candidates->isNotEmpty())
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 text-left text-xs dark:border-gray-700">
                                <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Order ID') }}</th>
                                <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Name') }}</th>
                                <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Service') }}</th>
                                <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Status') }}</th>
                                <th class="pb-2 pr-4 font-semibold text-gray-500">{{ __('Service Cost') }}</th>
                                <th class="pb-2 font-semibold text-gray-500">{{ __('Travel Cost') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @foreach ($candidates as $c)
                                <tr class="hover:bg-gray-50/60 dark:hover:bg-gray-700/20">
                                    <td class="py-2.5 pr-4">
                                        <a href="{{ route($prefix . '.candidates.edit', $c->id) }}"
                                            class="font-mono text-xs font-medium text-indigo-600 hover:underline dark:text-indigo-400">
                                            {{ $c->order_id }}
                                        </a>
                                    </td>
                                    <td class="py-2.5 pr-4 font-medium text-gray-800 dark:text-gray-200">
                                        {{ $c->name }} {{ $c->surname }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-gray-500">{{ $c->serviceType?->name ?? '—' }}</td>
                                    <td class="py-2.5 pr-4">
                                        @if ($c->statusRelation)
                                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                                                style="background-color:{{ $c->statusRelation->color ?: '#6b7280' }}">
                                                {{ $c->statusRelation->status }}
                                            </span>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-2.5 pr-4 text-gray-700 dark:text-gray-300">
                                        {{ $c->service_cost ? number_format((float) $c->service_cost, 2) . ' kr' : '—' }}
                                    </td>
                                    <td class="py-2.5 text-gray-700 dark:text-gray-300">
                                        {{ $c->travel_cost ? number_format((float) $c->travel_cost, 2) . ' kr' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-gray-200 dark:border-gray-700">
                                <td colspan="4" class="py-2.5 pr-4 text-right text-xs font-semibold text-gray-500">
                                    {{ __('Total') }}
                                </td>
                                <td colspan="2" class="py-2.5 font-bold text-gray-900 dark:text-white">
                                    {{ number_format((float) $invoice->invoice_amount, 2) }} kr
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="py-6 text-center text-sm text-gray-400">{{ __('No candidate details available.') }}</p>
            @endif
        </x-card>
    </div>

    {{-- RIGHT: invoice meta --}}
    <div class="space-y-5">
        <x-card>
            <x-slot name="header">{{ __('Invoice Details') }}</x-slot>
            <dl class="space-y-3 text-sm">
                @foreach ([
                    [__('Invoice #'),       '#' . $invoice->id],
                    [__('Period'),          $invoice->period_label],
                    [__('Total Amount'),    number_format((float) $invoice->invoice_amount, 2) . ' kr'],
                    [__('Candidates'),      $invoice->getCandidateCount()],
                    [__('Status'),          $invoice->status_label],
                    [__('Due Date'),        $invoice->due_date?->format('d M Y') ?? '—'],
                    [__('Generated'),       $invoice->created_date?->format('d M Y H:i') ?? $invoice->created_at?->format('d M Y H:i')],
                    [__('Sent At'),         $invoice->sent_at?->format('d M Y H:i') ?? '—'],
                ] as [$label, $value])
                    <div class="flex justify-between gap-3">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="text-right text-gray-800 dark:text-gray-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>

        @if ($invoice->notes)
            <x-card>
                <x-slot name="header">{{ __('Notes') }}</x-slot>
                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $invoice->notes }}</p>
            </x-card>
        @endif

        <x-card>
            <x-slot name="header">{{ __('Customer') }}</x-slot>
            <dl class="space-y-2 text-sm">
                @foreach ([
                    [__('Name'),    $invoice->customer?->user?->name ?? '—'],
                    [__('Company'), $invoice->customer?->company ?? '—'],
                    [__('Period'),  $invoice->customer?->invoice_period ?? '—'],
                ] as [$label, $value])
                    <div class="flex justify-between gap-3">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="text-right text-gray-800 dark:text-gray-200">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-card>
    </div>
</div>

</x-layouts.backend-layout>
