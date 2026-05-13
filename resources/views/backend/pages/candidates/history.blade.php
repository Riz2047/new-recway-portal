<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
@php $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin'; @endphp

{{-- ============================================================
     CANDIDATE IDENTITY BANNER
     ============================================================ --}}
<div class="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex items-center gap-3">
        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-sm font-bold uppercase text-white select-none">
            {{ mb_substr($candidate->name ?? '', 0, 1) }}{{ mb_substr($candidate->surname ?? '', 0, 1) }}
        </div>
        <div>
            <h1 class="font-semibold text-gray-900 dark:text-white">
                {{ $candidate->name }} {{ $candidate->surname }}
            </h1>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                <span class="font-mono">#{{ $candidate->order_id }}</span>
                @if ($candidate->statusRelation)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                        style="background-color:{{ $candidate->statusRelation->color ?: '#6b7280' }}">
                        {{ $candidate->statusRelation->status }}
                    </span>
                @endif
                @if ($candidate->customer?->user)
                    <span>{{ $candidate->customer->user->name }}
                        @if($candidate->customer->company) — {{ $candidate->customer->company }} @endif
                    </span>
                @endif
                @if ($candidate->serviceType)
                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        {{ $candidate->serviceType->name }}
                    </span>
                @endif
            </div>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <a href="{{ route($prefix . '.candidates.edit', $candidate->id) }}"
            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            {{ __('Edit Candidate') }}
        </a>
        <a href="{{ route($prefix . '.candidates.index') }}"
            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            ← {{ __('Back to Candidates') }}
        </a>
    </div>
</div>

<div class="grid gap-6 lg:grid-cols-3">

    {{-- ============================================================
         LEFT COLUMN: Timeline (2/3 width)
         ============================================================ --}}
    <div class="lg:col-span-2">
        <x-card>
            <x-slot name="header">
                <div class="flex items-center justify-between">
                    <span>
                        {{ __('Audit Trail') }}
                        <span class="ml-2 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                            {{ $total }}
                        </span>
                    </span>
                    {{-- Search bar --}}
                    <form method="GET" action="{{ route($prefix . '.candidates.history', $candidate->id) }}"
                        class="flex items-center gap-2">
                        <input
                            type="text"
                            name="search"
                            value="{{ $search }}"
                            placeholder="{{ __('Search history…') }}"
                            class="form-control h-8 text-xs w-48"
                        />
                        <button type="submit"
                            class="h-8 rounded bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">
                            {{ __('Search') }}
                        </button>
                        @if ($search)
                            <a href="{{ route($prefix . '.candidates.history', $candidate->id) }}"
                                class="h-8 flex items-center rounded border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                                {{ __('Clear') }}
                            </a>
                        @endif
                    </form>
                </div>
            </x-slot>

            @if ($history->isNotEmpty())
                <ol class="relative border-l border-gray-200 dark:border-gray-700">
                    @foreach ($history as $entry)
                        <li class="group mb-6 ml-6" wire:key="h-{{ $entry->id }}">
                            {{-- Dot --}}
                            <div class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 ring-4 ring-white dark:bg-indigo-900 dark:ring-gray-800">
                                <div class="h-2.5 w-2.5 rounded-full bg-indigo-500 dark:bg-indigo-400"></div>
                            </div>

                            {{-- Content --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0 flex-1">
                                    <time class="mb-1 block text-xs font-medium text-gray-400 dark:text-gray-500">
                                        {{ $entry->date_time?->format('d M Y — H:i') }}
                                        <span class="ml-1 text-gray-300 dark:text-gray-600">
                                            ({{ $entry->date_time?->diffForHumans() }})
                                        </span>
                                    </time>
                                    <p class="font-semibold text-gray-800 dark:text-gray-100">
                                        {{ $entry->desc }}
                                    </p>
                                    @if (!empty($entry->comment))
                                        <p class="mt-1 whitespace-pre-line text-sm text-gray-500 dark:text-gray-400">{{ $entry->comment }}</p>
                                    @endif
                                </div>

                                {{-- Delete button --}}
                                @can('delete', \App\Models\Customer::class)
                                    <form
                                        method="POST"
                                        action="{{ route($prefix . '.candidates.history.destroy', [$candidate->id, $entry->id]) }}"
                                        onsubmit="return confirm('{{ __('Delete this history entry? This cannot be undone.') }}')"
                                        class="shrink-0 opacity-0 group-hover:opacity-100 transition"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1 rounded border border-red-200 px-2 py-1 text-xs text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20"
                                            title="{{ __('Delete') }}"
                                        >
                                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            {{ __('Delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </li>
                    @endforeach
                </ol>

                {{-- Pagination --}}
                @if ($history instanceof \Illuminate\Pagination\LengthAwarePaginator && $history->hasPages())
                    <div class="mt-6 border-t border-gray-100 pt-4 dark:border-gray-700">
                        {{ $history->links() }}
                    </div>
                @endif
            @else
                <div class="flex flex-col items-center justify-center py-16 text-center">
                    <svg class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    @if ($search)
                        <p class="font-medium text-gray-500">{{ __('No history entries match ":q".', ['q' => $search]) }}</p>
                        <a href="{{ route($prefix . '.candidates.history', $candidate->id) }}"
                            class="mt-2 text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                            {{ __('Clear search') }}
                        </a>
                    @else
                        <p class="font-medium text-gray-500">{{ __('No history recorded yet for this candidate.') }}</p>
                    @endif
                </div>
            @endif
        </x-card>
    </div>

    {{-- ============================================================
         RIGHT COLUMN: Add entry + Stats (1/3 width)
         ============================================================ --}}
    <div class="space-y-6">

        {{-- Stats card --}}
        <x-card>
            <x-slot name="header">{{ __('Summary') }}</x-slot>
            <dl class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Total Entries') }}</dt>
                    <dd class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">{{ $total }}</dd>
                </div>
                @if ($history instanceof \Illuminate\Pagination\LengthAwarePaginator && $history->isNotEmpty())
                    <div>
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Latest') }}</dt>
                        <dd class="mt-0.5 text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ $history->first()->date_time?->diffForHumans() }}
                        </dd>
                    </div>
                    <div class="col-span-2">
                        <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ __('Last Entry') }}</dt>
                        <dd class="mt-0.5 text-sm text-gray-700 dark:text-gray-300 leading-snug">
                            {{ $history->first()->desc }}
                        </dd>
                    </div>
                @endif
            </dl>
        </x-card>

        {{-- Add manual entry --}}
        @can('update', \App\Models\Customer::class)
            <x-card>
                <x-slot name="header">{{ __('Add Manual Entry') }}</x-slot>

                @if (session('success'))
                    <div class="mb-4 rounded-md bg-green-50 px-4 py-2 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                @endif

                <form
                    method="POST"
                    action="{{ route($prefix . '.candidates.history.store', $candidate->id) }}"
                    class="space-y-3"
                >
                    @csrf
                    <div>
                        <label for="hist-desc" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Description') }} <span class="text-red-500">*</span>
                        </label>
                        <input
                            id="hist-desc"
                            type="text"
                            name="desc"
                            value="{{ old('desc') }}"
                            placeholder="{{ __('What happened?') }}"
                            class="form-control text-sm w-full"
                            required
                            maxlength="500"
                        />
                        @error('desc')
                            <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="hist-comment" class="mb-1 block text-xs font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Comment') }}
                        </label>
                        <textarea
                            id="hist-comment"
                            name="comment"
                            rows="3"
                            placeholder="{{ __('Additional context or note…') }}"
                            class="form-control text-sm w-full"
                            maxlength="2000"
                        >{{ old('comment') }}</textarea>
                        @error('comment')
                            <p class="mt-0.5 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end">
                        <button type="submit"
                            class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                            {{ __('Add Entry') }}
                        </button>
                    </div>
                </form>
            </x-card>
        @endcan

        {{-- What gets auto-logged info box --}}
        <x-card>
            <x-slot name="header">{{ __('Auto-logged Events') }}</x-slot>
            <ul class="space-y-1.5 text-xs text-gray-600 dark:text-gray-400">
                @foreach ([
                    '🔄 ' . __('Status changes (with date + comment)'),
                    '👤 ' . __('Staff assignments & removals'),
                    '✏️ '  . __('Candidate detail edits'),
                    '🔍 ' . __('Background check result changes'),
                    '📋 ' . __('Invoice sent / reported toggles'),
                    '📎 ' . __('CV & BK document uploads / deletions'),
                    '📄 ' . __('Interview template uploads'),
                ] as $item)
                    <li class="flex items-start gap-1.5">
                        <span>{{ $item }}</span>
                    </li>
                @endforeach
            </ul>
        </x-card>

    </div>
</div>

</x-layouts.backend-layout>
