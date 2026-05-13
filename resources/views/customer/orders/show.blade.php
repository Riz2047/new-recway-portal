@extends('customer.layouts.app')

@section('title', __('Order') . ' #' . $candidate->order_id . ' | ' . config('app.name'))
@section('page-title', __('Order') . ' #' . $candidate->order_id)

@section('content')

{{-- ── Breadcrumb ──────────────────────────────────────────────────────── --}}
<nav class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('customer.orders.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('Orders') }}</a>
    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
    <span class="font-medium text-gray-800 dark:text-white">{{ $candidate->order_id }}</span>
</nav>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-3">

    {{-- ── Left column: tabs ── --}}
    <div class="lg:col-span-2" x-data="{ tab: 'info' }">

        {{-- Tab bar --}}
        <div class="mb-4 flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
            @php
                $tabs = [
                    'info'       => ['icon' => 'lucide:user',        'label' => __('Candidate Info')],
                    'billing'    => ['icon' => 'lucide:receipt',     'label' => __('Billing')],
                    'files'      => ['icon' => 'lucide:paperclip',   'label' => __('Files')],
                    'note'       => ['icon' => 'lucide:sticky-note', 'label' => __('Note')],
                ];
            @endphp
            @foreach($tabs as $key => $t)
            <button @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}'
                    ? 'bg-white shadow text-brand-600 dark:bg-gray-700 dark:text-brand-400'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                class="flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium transition-all">
                <iconify-icon icon="{{ $t['icon'] }}" width="14"></iconify-icon>
                <span class="hidden sm:inline">{{ $t['label'] }}</span>
            </button>
            @endforeach
        </div>

        {{-- ── Tab: Candidate Info ── --}}
        <div x-show="tab === 'info'" class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Candidate Information') }}</h3>
            </div>
            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                @php
                    $rows = [
                        [__('Order ID'),       $candidate->order_id],
                        [__('Status'),         null, 'status'],
                        [__('Name'),           $candidate->name . ' ' . $candidate->surname],
                        [__('Email'),          $candidate->email],
                        [__('Phone'),          $candidate->phone ?: '—'],
                        [__('Service Type'),   $candidate->service_name ?? '—'],
                        [__('Service Category'), $candidate->service_category_name ?? '—'],
                        [__('Interview Date'), $candidate->booked ? \Carbon\Carbon::parse($candidate->booked)->format('d M Y') : '—'],
                        [__('Delivery Date'),  $candidate->delivery_date ? \Carbon\Carbon::parse($candidate->delivery_date)->format('d M Y') : '—'],
                        [__('Location'),       $candidate->place_name ?? '—'],
                        [__('Country'),        $candidate->country ?: '—'],
                        [__('Staff Assigned'), $candidate->staff_name ?? '—'],
                    ];
                    if ($candidate->hasPersonalId && $candidate->security)
                        $rows[] = [__('Personal ID'), $candidate->security];
                    if ($candidate->vasc_id)
                        $rows[] = [__('VASC ID'), $candidate->vasc_id];
                @endphp

                @foreach($rows as $row)
                <div class="flex px-5 py-3">
                    <dt class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $row[0] }}</dt>
                    <dd class="text-sm text-gray-800 dark:text-white">
                        @if(($row[2] ?? '') === 'status')
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
                                style="background-color: {{ $candidate->status_color ?? '#94a3b8' }}">
                                {{ $candidate->status_title ?? '—' }}
                            </span>
                        @else
                            {{ $row[1] }}
                        @endif
                    </dd>
                </div>
                @endforeach

                {{-- Dynamic meta_data fields --}}
                @if(!empty($metaData))
                    @foreach($metaData as $key => $value)
                    <div class="flex px-5 py-3">
                        <dt class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">
                            {{ is_string($key) ? ucwords(str_replace('_', ' ', $key)) : $key }}
                        </dt>
                        <dd class="text-sm text-gray-800 dark:text-white">
                            {{ is_array($value) ? implode(', ', $value) : ($value ?: '—') }}
                        </dd>
                    </div>
                    @endforeach
                @endif
            </dl>
        </div>

        {{-- ── Tab: Billing ── --}}
        <div x-show="tab === 'billing'" x-cloak
            class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Billing Details') }}</h3>
            </div>
            <dl class="divide-y divide-gray-100 dark:divide-gray-700">
                <div class="flex px-5 py-3">
                    <dt class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Invoice Recipient') }}</dt>
                    <dd class="text-sm text-gray-800 dark:text-white">{{ $candidate->referensperson ?: '—' }}</dd>
                </div>
                <div class="flex px-5 py-3">
                    <dt class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Invoice Reference') }}</dt>
                    <dd class="text-sm text-gray-800 dark:text-white">{{ $candidate->reference ?: '—' }}</dd>
                </div>
                <div class="flex px-5 py-3">
                    <dt class="w-44 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Comment') }}</dt>
                    <dd class="text-sm text-gray-800 dark:text-white">{{ $candidate->comment ?: '—' }}</dd>
                </div>
            </dl>
        </div>

        {{-- ── Tab: Files / Attachments ── --}}
        <div x-show="tab === 'files'" x-cloak
            class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Attachments') }}</h3>
            </div>
            <div class="px-5 py-4 space-y-4">
                {{-- CV files --}}
                @if(!empty($cvFiles))
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('CV Files') }}</p>
                    <ul class="space-y-2">
                        @foreach($cvFiles as $file)
                        <li class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-2.5 dark:border-gray-700">
                            <iconify-icon icon="lucide:file-text" width="16" class="shrink-0 text-brand-500"></iconify-icon>
                            <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ trim($file) }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Interview template --}}
                @if($candidate->interview_template)
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ __('Interview Template') }}</p>
                    <div class="flex items-center gap-3 rounded-lg border border-gray-200 px-4 py-2.5 dark:border-gray-700">
                        <iconify-icon icon="lucide:file" width="16" class="shrink-0 text-brand-500"></iconify-icon>
                        <span class="flex-1 truncate text-sm text-gray-700 dark:text-gray-300">{{ $candidate->interview_template }}</span>
                    </div>
                </div>
                @endif

                @if(empty($cvFiles) && !$candidate->interview_template)
                <p class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No files attached.') }}</p>
                @endif
            </div>
        </div>

        {{-- ── Tab: Note ── --}}
        <div x-show="tab === 'note'" x-cloak
            class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Internal Note') }}</h3>
            </div>
            <div class="px-5 py-4">
                @if($candidate->note)
                    <p class="whitespace-pre-wrap text-sm text-gray-700 dark:text-gray-300">{{ $candidate->note }}</p>
                @else
                    <p class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No note added.') }}</p>
                @endif
            </div>
        </div>

        {{-- ── Action buttons ── --}}
        @php $isClosed = in_array($candidate->status, [9, 40, 56]); @endphp
        @if(!$isClosed)
        <div class="mt-4 flex flex-wrap gap-3">

            {{-- Edit Order --}}
            <a href="{{ route('customer.orders.edit', $candidate->id) }}"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:pencil" width="16"></iconify-icon>
                {{ __('Edit Order') }}
            </a>

            {{-- Change Status (if customer has changeable statuses) --}}
            @if($changeableStatuses->isNotEmpty())
            <button onclick="document.getElementById('status-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2 text-sm font-medium text-blue-600 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
                <iconify-icon icon="lucide:refresh-cw" width="16"></iconify-icon>
                {{ __('Change Status') }}
            </button>
            @endif

            {{-- Cancel Order --}}
            <button onclick="document.getElementById('cancel-modal').classList.remove('hidden')"
                class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-600 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30">
                <iconify-icon icon="lucide:x-circle" width="16"></iconify-icon>
                {{ __('Cancel Order') }}
            </button>

        </div>
        @endif
    </div>

    {{-- ── Right column: history timeline + archive card ── --}}
    <div class="space-y-4">

        {{-- Archive countdown card --}}
        @if(is_int($daysToArchive))
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-800 dark:bg-amber-900/20">
            <div class="flex items-center gap-3">
                <iconify-icon icon="lucide:clock" width="20" class="text-amber-600 dark:text-amber-400"></iconify-icon>
                <div>
                    <p class="text-sm font-semibold text-amber-700 dark:text-amber-300">{{ __('Archive In') }}</p>
                    <p class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                        {{ $daysToArchive }} <span class="text-sm font-normal">{{ __('days') }}</span>
                    </p>
                </div>
            </div>
        </div>
        @endif

        {{-- History timeline --}}
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">
            <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Order History') }}</h3>
            </div>

            <div class="overflow-y-auto px-5 py-4" style="max-height: 480px">
                @forelse($history as $h)
                <div class="relative mb-5 pl-6 last:mb-0">
                    {{-- Timeline dot + line --}}
                    <span class="absolute left-0 top-1 h-2.5 w-2.5 rounded-full border-2 border-brand-500 bg-white dark:border-brand-400 dark:bg-gray-800"></span>
                    @if(!$loop->last)
                    <span class="absolute left-[4px] top-4 h-full w-0.5 bg-gray-200 dark:bg-gray-700"></span>
                    @endif

                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">
                        {{ \Carbon\Carbon::parse($h->date_time)->format('d M Y, H:i') }}
                    </p>
                    <p class="mt-0.5 text-sm text-gray-700 dark:text-gray-300">{{ $h->desc }}</p>
                    @if($h->comment)
                    <p class="mt-1 rounded-md bg-gray-50 px-2 py-1 text-xs italic text-gray-500 dark:bg-gray-700/50 dark:text-gray-400">
                        {{ $h->comment }}
                    </p>
                    @endif
                </div>
                @empty
                <p class="py-6 text-center text-sm text-gray-400 dark:text-gray-500">{{ __('No history yet.') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- ── Change Status Modal ─────────────────────────────────────────────── --}}
@if($changeableStatuses->isNotEmpty())
<div id="status-modal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Change Status') }}</h3>
            <button onclick="document.getElementById('status-modal').classList.add('hidden')"
                class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:x" width="16"></iconify-icon>
            </button>
        </div>
        <form action="{{ route('customer.orders.change-status', $candidate->id) }}" method="POST" class="p-5">
            @csrf
            <div class="mb-4">
                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('New Status') }} <span class="text-red-500">*</span>
                </label>
                <select name="status" class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200" required>
                    <option value="" disabled selected>{{ __('Select status') }}</option>
                    @foreach($changeableStatuses as $s)
                    <option value="{{ $s->id }}">{{ $s->status }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-4">
                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Comment (optional)') }}
                </label>
                <textarea name="comment" rows="3"
                    placeholder="{{ __('Add a comment...') }}"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button"
                    onclick="document.getElementById('status-modal').classList.add('hidden')"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Cancel') }}
                </button>
                <button type="submit" class="btn-primary">
                    {{ __('Update Status') }}
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ── Cancel Order Modal ──────────────────────────────────────────────── --}}
<div id="cancel-modal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">

        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Cancel Order') }}</h3>
            <button onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:x" width="16"></iconify-icon>
            </button>
        </div>

        <form action="{{ route('customer.orders.cancel', $candidate->id) }}" method="POST" class="p-5">
            @csrf
            @method('DELETE')
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Are you sure you want to cancel order') }}
                <strong class="text-gray-800 dark:text-white">{{ $candidate->order_id }}</strong>?
                {{ __('This action cannot be undone.') }}
            </p>
            <div class="mb-4">
                <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Reason (optional)') }}
                </label>
                <textarea name="comment" rows="3"
                    placeholder="{{ __('Add a reason for cancellation...') }}"
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-sm focus:border-brand-400 focus:outline-none focus:ring-1 focus:ring-brand-400 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 dark:placeholder-gray-400"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button"
                    onclick="document.getElementById('cancel-modal').classList.add('hidden')"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Go Back') }}
                </button>
                <button type="submit"
                    class="rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700">
                    {{ __('Yes, Cancel Order') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
