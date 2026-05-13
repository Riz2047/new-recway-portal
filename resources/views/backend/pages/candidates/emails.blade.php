<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
@php
    $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';
    $types  = ['all' => __('All'), 'Customer' => __('Customer'), 'Candidate' => __('Candidate'), 'Staff' => __('Staff')];
@endphp

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
                <span class="ml-2 font-mono text-sm font-normal text-gray-400">#{{ $candidate->order_id }}</span>
            </h1>
            <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-500 dark:text-gray-400">
                @if ($candidate->statusRelation)
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium text-white"
                        style="background-color:{{ $candidate->statusRelation->color ?: '#6b7280' }}">
                        {{ $candidate->statusRelation->status }}
                    </span>
                @endif
                @if ($candidate->customer?->user)
                    <span>{{ $candidate->customer->user->name }}
                        @if ($candidate->customer->company) — {{ $candidate->customer->company }} @endif
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
        <a href="{{ route($prefix . '.candidates.history', $candidate->id) }}"
            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            {{ __('History') }}
        </a>
        <a href="{{ route($prefix . '.candidates.index') }}"
            class="rounded border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
            ← {{ __('Candidates') }}
        </a>
    </div>
</div>

{{-- ============================================================
     TOOLBAR: Type filter tabs + search
     ============================================================ --}}
<div class="mb-4 flex flex-wrap items-center justify-between gap-3">

    {{-- Type filter tabs --}}
    <div class="flex gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800/60">
        @foreach ($types as $value => $label)
            @php
                $count = $value === 'all'
                    ? array_sum($counts)
                    : ($counts[$value] ?? 0);
                $active = $filter === $value;
            @endphp
            <a href="{{ route($prefix . '.candidates.emails', ['candidate' => $candidate->id, 'type' => $value, 'search' => $search]) }}"
                class="flex items-center gap-1.5 rounded-md px-3 py-1.5 text-xs font-medium transition
                    {{ $active
                        ? 'bg-white text-gray-900 shadow-sm dark:bg-gray-700 dark:text-white'
                        : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' }}">
                {{ $label }}
                @if ($count > 0)
                    <span class="rounded-full {{ $active ? 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300' : 'bg-gray-200 text-gray-500 dark:bg-gray-700 dark:text-gray-400' }} px-1.5 py-0.5 text-xs font-medium">
                        {{ $count }}
                    </span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- Search --}}
    <form method="GET"
        action="{{ route($prefix . '.candidates.emails', $candidate->id) }}"
        class="flex items-center gap-2">
        <input type="hidden" name="type" value="{{ $filter }}">
        <input type="text" name="search" value="{{ $search }}"
            placeholder="{{ __('Search subject, recipient, body…') }}"
            class="form-control h-8 text-xs w-56" />
        <button type="submit"
            class="h-8 rounded bg-indigo-600 px-3 text-xs font-medium text-white hover:bg-indigo-700">
            {{ __('Search') }}
        </button>
        @if ($search)
            <a href="{{ route($prefix . '.candidates.emails', ['candidate' => $candidate->id, 'type' => $filter]) }}"
                class="h-8 flex items-center rounded border border-gray-300 px-3 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                {{ __('Clear') }}
            </a>
        @endif
    </form>
</div>

{{-- ============================================================
     FLASH MESSAGES
     ============================================================ --}}
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
     EMAIL LIST
     ============================================================ --}}
@if ($emails->isNotEmpty())
    <div class="space-y-3" x-data="emailResend()">

        @foreach ($emails as $email)
            @php
                $isResent = str_ends_with($email->msg_type ?? '', '(Resent)');
                $badgeColor = match($email->user_type) {
                    'Customer'  => 'bg-blue-100   text-blue-700   dark:bg-blue-900/40  dark:text-blue-300',
                    'Candidate' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
                    'Staff'     => 'bg-green-100  text-green-700  dark:bg-green-900/40  dark:text-green-300',
                    default     => 'bg-gray-100   text-gray-600   dark:bg-gray-700      dark:text-gray-300',
                };
            @endphp

            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800"
                x-bind:class="activeResend === {{ $email->id }} ? 'ring-2 ring-indigo-400 ring-offset-1 dark:ring-offset-gray-900' : ''"
            >
                {{-- Email header row --}}
                <div class="flex flex-wrap items-center gap-3 px-5 py-3">

                    {{-- Type badge --}}
                    <span class="inline-flex shrink-0 items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeColor }}">
                        {{ $email->user_type }}
                        @if ($isResent)
                            <span class="ml-1 opacity-50 text-xs">↩</span>
                        @endif
                    </span>

                    {{-- Meta --}}
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $email->subject ?: $email->msg_type }}
                        </p>
                        <div class="mt-0.5 flex flex-wrap items-center gap-2 text-xs text-gray-400">
                            <span title="{{ __('Recipient') }}">
                                <svg class="inline h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                {{ $email->email }}
                            </span>
                            <span>·</span>
                            <span title="{{ __('Email type') }}">{{ $email->msg_type }}</span>
                            <span>·</span>
                            <span title="{{ __('Sent at') }}">{{ $email->created_at?->format('d M Y H:i') }}</span>
                            @if ($email->user_name)
                                <span>·</span>
                                <span>{{ $email->user_name }}</span>
                            @endif
                        </div>
                    </div>

                    {{-- Action buttons --}}
                    <div class="flex shrink-0 items-center gap-2">
                        {{-- Preview toggle --}}
                        <button
                            type="button"
                            @click="togglePreview({{ $email->id }})"
                            x-bind:class="activePreview === {{ $email->id }} ? 'bg-gray-100 text-gray-700 dark:bg-gray-700' : 'text-gray-400 hover:text-gray-700 hover:bg-gray-100 dark:hover:bg-gray-700 dark:hover:text-gray-200'"
                            class="rounded p-1.5 transition"
                            title="{{ __('Preview body') }}"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </button>

                        {{-- Resend toggle --}}
                        <button
                            type="button"
                            @click="prepareResend({{ $email->id }}, '{{ addslashes($email->subject) }}', {{ json_encode($email->text) }})"
                            x-bind:class="activeResend === {{ $email->id }} ? 'bg-indigo-50 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400' : 'text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 dark:hover:text-indigo-400'"
                            class="flex items-center gap-1 rounded px-2 py-1.5 text-xs font-medium transition"
                            title="{{ __('Resend') }}"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                            </svg>
                            {{ __('Resend') }}
                        </button>
                    </div>
                </div>

                {{-- Preview panel --}}
                <div x-show="activePreview === {{ $email->id }}" x-cloak
                    class="border-t border-gray-100 dark:border-gray-700">
                    <div class="bg-gray-50 px-5 py-3 dark:bg-gray-900/40">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                            {{ __('Email Body') }}
                        </p>
                        <div class="max-h-80 overflow-auto rounded-lg border border-gray-200 bg-white p-4 text-sm dark:border-gray-700 dark:bg-gray-800">
                            {!! $email->text !!}
                        </div>
                    </div>
                </div>

                {{-- Resend form --}}
                <div x-show="activeResend === {{ $email->id }}" x-cloak
                    class="border-t border-indigo-100 bg-indigo-50/40 px-5 py-4 dark:border-indigo-900/40 dark:bg-indigo-900/10">
                    <p class="mb-3 text-xs font-semibold text-indigo-700 dark:text-indigo-400">
                        {{ __('Edit & Resend to') }}: <span class="font-normal">{{ $email->email }}</span>
                    </p>
                    <form
                        method="POST"
                        action="{{ route($prefix . '.candidates.emails.resend', [$candidate->id, $email->id]) }}"
                        class="space-y-3"
                    >
                        @csrf
                        <div>
                            <label class="mb-0.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ __('Subject') }} <span class="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                name="subject"
                                x-model="resendSubject"
                                class="form-control text-sm w-full"
                                required
                                maxlength="500"
                            />
                        </div>
                        <div>
                            <label class="mb-0.5 block text-xs font-medium text-gray-600 dark:text-gray-400">
                                {{ __('Body') }}
                                <span class="ml-1 font-normal text-gray-400">{{ __('(HTML allowed)') }}</span>
                            </label>
                            <textarea
                                name="body"
                                x-model="resendBody"
                                rows="8"
                                class="form-control font-mono text-xs w-full"
                                required
                            ></textarea>
                        </div>
                        <div class="flex items-center justify-between">
                            <p class="text-xs text-gray-400">
                                {{ __('A copy will be saved in the email log with "(Resent)" suffix.') }}
                            </p>
                            <div class="flex gap-2">
                                <button type="button" @click="activeResend = null"
                                    class="rounded border border-gray-300 px-3 py-1.5 text-xs text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                                    {{ __('Cancel') }}
                                </button>
                                <button type="submit"
                                    class="rounded bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white hover:bg-indigo-700">
                                    {{ __('Send Email') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        @endforeach

        {{-- Pagination --}}
        @if ($emails instanceof \Illuminate\Pagination\LengthAwarePaginator && $emails->hasPages())
            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                {{ $emails->links() }}
            </div>
        @endif
    </div>

@else
    <div class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-300 py-16 text-center dark:border-gray-700">
        <svg class="mb-3 h-12 w-12 text-gray-300 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
        </svg>
        @if ($search)
            <p class="font-medium text-gray-500">{{ __('No emails match ":q".', ['q' => $search]) }}</p>
            <a href="{{ route($prefix . '.candidates.emails', ['candidate' => $candidate->id, 'type' => $filter]) }}"
                class="mt-2 text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                {{ __('Clear search') }}
            </a>
        @else
            <p class="font-medium text-gray-500">{{ __('No emails have been sent to this candidate yet.') }}</p>
            <p class="mt-1 text-sm text-gray-400">
                {{ __('Emails are sent automatically when the candidate status changes.') }}
            </p>
        @endif
    </div>
@endif

@push('scripts')
<script>
function emailResend() {
    return {
        activePreview: null,
        activeResend: null,
        resendSubject: '',
        resendBody: '',

        togglePreview(id) {
            this.activePreview = this.activePreview === id ? null : id;
            if (this.activePreview !== null) this.activeResend = null;
        },

        prepareResend(id, subject, body) {
            if (this.activeResend === id) {
                this.activeResend = null;
                return;
            }
            this.activePreview = null;
            this.activeResend = id;
            this.resendSubject = subject;
            this.resendBody = body;
        },
    };
}
</script>
@endpush

</x-layouts.backend-layout>
