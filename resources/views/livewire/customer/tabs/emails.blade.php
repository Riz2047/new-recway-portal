<div class="py-6" x-data="{ preview: null }">

    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Email History') }}
        </h3>
        <span class="text-sm text-gray-500 dark:text-gray-400">
            {{ number_format($totalCount) }} {{ __('total') }}
        </span>
    </div>

    {{-- Type filter tabs --}}
    <div class="mb-4 flex flex-wrap items-center gap-1 border-b border-gray-200 dark:border-gray-700">
        @php
            $types = [
                'all'       => ['label' => __('All'), 'count' => $totalCount],
                'Customer'  => ['label' => __('Customer'), 'count' => $typeCounts['Customer'] ?? 0],
                'Candidate' => ['label' => __('Candidate'), 'count' => $typeCounts['Candidate'] ?? 0],
                'Staff'     => ['label' => __('Staff'), 'count' => $typeCounts['Staff'] ?? 0],
            ];
        @endphp
        @foreach($types as $key => $type)
            <button
                type="button"
                wire:click="$set('filterType', '{{ $key }}')"
                @class([
                    'px-4 py-2.5 text-sm font-medium transition border-b-2 -mb-px',
                    'border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400' => $filterType === $key,
                    'border-transparent text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' => $filterType !== $key,
                ])
            >
                {{ $type['label'] }}
                @if($type['count'] > 0)
                    <span @class([
                        'ml-1.5 rounded-full px-1.5 py-0.5 text-xs font-semibold',
                        'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' => $filterType === $key,
                        'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400' => $filterType !== $key,
                    ])>{{ $type['count'] }}</span>
                @endif
            </button>
        @endforeach
    </div>

    {{-- Search + per-page toolbar --}}
    <div class="mb-4 flex items-center justify-between gap-3">
        <div class="relative flex-1 max-w-xs">
            <svg class="absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.6">
                <circle cx="7" cy="7" r="5"/><path stroke-linecap="round" d="m12 12 3 3"/>
            </svg>
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Search subject, recipient, type…') }}"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 py-1.5 pl-8 pr-3 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
            />
        </div>
        <select
            wire:model.live="perPage"
            class="rounded-lg border border-gray-300 bg-gray-50 px-2 py-1.5 text-xs text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
        >
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="50">50</option>
        </select>
    </div>

    {{-- Email list --}}
    @forelse($emails as $email)
        <div wire:key="email-{{ $email->id }}" class="mb-3 overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

            {{-- Main row --}}
            <div class="flex items-start gap-3 px-4 py-3">

                {{-- Type badge --}}
                <span @class([
                    'mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                    'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'   => $email->user_type === 'Customer',
                    'bg-purple-100 text-purple-700 dark:bg-purple-900 dark:text-purple-300' => $email->user_type === 'Candidate',
                    'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' => $email->user_type === 'Staff',
                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400'   => ! in_array($email->user_type, ['Customer','Candidate','Staff']),
                ])>{{ $email->user_type ?: '—' }}</span>

                {{-- Content --}}
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-gray-800 dark:text-gray-200">
                        {{ $email->subject ?: $email->msg_type ?: '(No subject)' }}
                    </p>
                    <div class="mt-0.5 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-gray-500 dark:text-gray-400">
                        @if($email->email)
                            <span class="flex items-center gap-1">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 14 14" stroke="currentColor" stroke-width="1.5">
                                    <rect x="1" y="3" width="12" height="8" rx="1.5"/><path d="M1 5l6 4 6-4" stroke-linecap="round"/>
                                </svg>
                                {{ $email->email }}
                            </span>
                        @endif
                        @if($email->order_id)
                            <span class="font-mono">#{{ $email->order_id }}</span>
                        @endif
                        @if($email->msg_type)
                            <span class="text-gray-400 dark:text-gray-500">{{ $email->msg_type }}</span>
                        @endif
                        <span>{{ $email->created_at?->format('d M Y, H:i') ?? '—' }}</span>
                        @if($email->user_name)
                            <span>{{ $email->user_name }}</span>
                        @endif
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex shrink-0 items-center gap-1">
                    <button
                        type="button"
                        @click="preview === {{ $email->id }} ? preview = null : preview = {{ $email->id }}"
                        class="rounded-md p-1.5 text-xs text-gray-500 transition hover:bg-gray-100 hover:text-gray-800 dark:hover:bg-gray-700 dark:hover:text-gray-200"
                        title="{{ __('Preview') }}"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                            <path d="M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5Z"/><circle cx="8" cy="8" r="2"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        wire:click="prepareResend({{ $email->id }})"
                        class="rounded-md p-1.5 text-xs text-indigo-500 transition hover:bg-indigo-50 hover:text-indigo-700 dark:hover:bg-indigo-900/30"
                        title="{{ __('Resend') }}"
                    >
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2 8a6 6 0 1 0 1.2-3.6M2 3v4h4"/>
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Preview panel --}}
            <div
                x-show="preview === {{ $email->id }}"
                x-cloak
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="border-t border-gray-100 px-4 py-3 dark:border-gray-700"
            >
                <p class="mb-1.5 text-[10px] font-semibold uppercase tracking-wide text-gray-400">{{ __('Email body') }}</p>
                @if($email->text)
                    <div class="max-h-64 overflow-y-auto rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                        {!! $email->text !!}
                    </div>
                @else
                    <p class="text-xs text-gray-400">{{ __('No body content.') }}</p>
                @endif
            </div>

            {{-- Resend form --}}
            @if($resendEmailId === $email->id)
                <div
                    class="border-t border-indigo-100 bg-indigo-50/50 px-4 py-4 dark:border-indigo-900 dark:bg-indigo-950/20"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0"
                    x-transition:enter-end="opacity-100"
                >
                    <p class="mb-3 text-[10px] font-semibold uppercase tracking-wide text-indigo-500">{{ __('Resend Email') }}</p>

                    @error('resendSubject')
                        <p class="mb-2 rounded border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700">{{ $message }}</p>
                    @enderror
                    @error('resendBody')
                        <p class="mb-2 rounded border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700">{{ $message }}</p>
                    @enderror

                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Subject') }}</label>
                        <input
                            type="text"
                            wire:model.defer="resendSubject"
                            maxlength="500"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        />
                    </div>
                    <div class="mb-3">
                        <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Body') }} <span class="font-normal text-gray-400">(HTML)</span></label>
                        <textarea
                            wire:model.defer="resendBody"
                            rows="5"
                            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 font-mono text-xs text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        ></textarea>
                        <p class="mt-1 text-[10px] text-gray-400">{{ __('A copy will be saved in the email log with "(Resent)" suffix.') }}</p>
                    </div>
                    <div class="flex items-center justify-end gap-2">
                        <button
                            type="button"
                            wire:click="cancelResend"
                            class="rounded-md border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400 dark:hover:bg-gray-800"
                        >{{ __('Cancel') }}</button>
                        <button
                            type="button"
                            wire:click="resend"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center gap-1.5 rounded-md bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700 disabled:opacity-60"
                        >
                            <svg wire:loading wire:target="resend" class="h-3 w-3 animate-spin" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2Z" opacity=".25"/><path d="M14 8a6 6 0 0 0-6-6" stroke-linecap="round"/>
                            </svg>
                            <span wire:loading.remove wire:target="resend">{{ __('Send Email') }}</span>
                            <span wire:loading wire:target="resend">{{ __('Sending…') }}</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-800/30">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" d="M3 8l7.89 5.26a2 2 0 0 0 2.22 0L21 8M5 19h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2Z"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                {{ $search ? __('No emails match your search.') : __('No emails found for this customer.') }}
            </p>
        </div>
    @endforelse

    {{-- Pagination --}}
    @if($emails->hasPages())
        <div class="mt-4">
            {{ $emails->links() }}
        </div>
    @endif
</div>
