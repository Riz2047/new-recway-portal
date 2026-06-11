<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

{{-- ============================================================
     FLASH MESSAGES
     ============================================================ --}}
@foreach (['success', 'error'] as $type)
    @if (session($type))
        <div class="mb-4 rounded-md border px-4 py-3 text-sm
            {{ $type === 'success' ? 'border-green-200 bg-green-50 text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400' : 'border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400' }}">
            <pre class="whitespace-pre-wrap text-xs">{{ session($type) }}</pre>
        </div>
    @endif
@endforeach

{{-- ============================================================
     HEADER INFO
     ============================================================ --}}
<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 px-5 py-4 dark:border-blue-800 dark:bg-blue-900/20">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-semibold text-blue-800 dark:text-blue-300">{{ __('Scheduled Jobs Overview') }}</h2>
            <p class="mt-0.5 text-sm text-blue-700 dark:text-blue-400">
                {{ __('All times are in Europe/Stockholm timezone. Jobs run automatically via the Laravel scheduler.') }}
            </p>
            <p class="mt-1 font-mono text-xs text-blue-600 dark:text-blue-500">
                {{ __('Add to crontab:') }}
                <code class="rounded bg-blue-100 px-1.5 py-0.5 dark:bg-blue-900/40">* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1</code>
            </p>
        </div>
        <div class="text-right text-xs text-blue-600 dark:text-blue-400">
            <p>{{ __('Server time') }}: {{ now()->format('d M Y H:i:s') }}</p>
            <p>{{ __('Stockholm') }}: {{ now('Europe/Stockholm')->format('d M Y H:i:s') }}</p>
        </div>
    </div>
</div>

{{-- ============================================================
     FAILED JOBS
     ============================================================ --}}
@php $failedCount = $failedJobs->count(); @endphp

<div class="mb-8">
    <div class="mb-3 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h2 class="text-base font-semibold text-gray-800 dark:text-white">{{ __('Failed Queue Jobs') }}</h2>
            @if ($failedCount > 0)
                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-bold text-red-700 dark:bg-red-900/40 dark:text-red-300">
                    {{ $failedCount }}
                </span>
            @else
                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                    {{ __('All clear') }}
                </span>
            @endif
        </div>

        @if ($failedCount > 0)
            @can('update', App\Models\Customer::class)
                <form method="POST" action="{{ route('admin.cron.failed.retry-all') }}"
                      onsubmit="return confirm('{{ __('Retry all :n failed jobs?', ['n' => $failedCount]) }}')">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                        <iconify-icon icon="lucide:rotate-ccw" width="13"></iconify-icon>
                        {{ __('Retry All') }}
                    </button>
                </form>
            @endcan
        @endif
    </div>

    @if ($failedCount === 0)
        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ __('No failed jobs — the queue is healthy.') }}
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-red-200 bg-white dark:border-red-800 dark:bg-gray-800">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-red-100 bg-red-50 text-left text-xs dark:border-red-800 dark:bg-red-900/20">
                        <th class="px-4 py-3 font-semibold text-red-700 dark:text-red-300">{{ __('ID') }}</th>
                        <th class="px-4 py-3 font-semibold text-red-700 dark:text-red-300">{{ __('Failed At') }}</th>
                        <th class="px-4 py-3 font-semibold text-red-700 dark:text-red-300">{{ __('Error') }}</th>
                        <th class="px-4 py-3 font-semibold text-red-700 dark:text-red-300">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ($failedJobs as $fj)
                        @php
                            // Extract just the first line of the exception for a short summary.
                            $errorLines  = explode("\n", $fj->exception ?? '');
                            $errorSummary = trim($errorLines[0] ?? '');
                            $errorSummary = strlen($errorSummary) > 120
                                ? substr($errorSummary, 0, 120) . '…'
                                : $errorSummary;

                            // Try to pull the email address from the payload.
                            $payloadData = json_decode($fj->payload ?? '{}', true);
                            $cmdData     = json_decode($payloadData['data']['command'] ?? '{}', true);
                        @endphp
                        <tr x-data="{ open: false }" class="hover:bg-gray-50 dark:hover:bg-gray-700/30">
                            <td class="px-4 py-3 font-mono text-xs text-gray-500 dark:text-gray-400">#{{ $fj->id }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-300 whitespace-nowrap">
                                {{ \Carbon\Carbon::parse($fj->failed_at)->timezone('Europe/Stockholm')->format('d M Y H:i:s') }}
                                <br>
                                <span class="text-gray-400">({{ \Carbon\Carbon::parse($fj->failed_at)->diffForHumans() }})</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-xs text-red-600 dark:text-red-400">{{ $errorSummary }}</p>
                                <button type="button" @click="open = !open"
                                    class="mt-1 text-xs text-indigo-500 hover:underline dark:text-indigo-400">
                                    <span x-text="open ? '{{ __('Hide full trace') }}' : '{{ __('Show full trace') }}'"></span>
                                </button>
                                <pre x-show="open" x-cloak
                                    class="mt-2 max-h-48 overflow-auto rounded bg-gray-100 p-2 text-xs text-gray-700 dark:bg-gray-900 dark:text-gray-300">{{ $fj->exception }}</pre>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col gap-1.5">
                                    @can('update', App\Models\Customer::class)
                                        {{-- Retry --}}
                                        <form method="POST"
                                              action="{{ route('admin.cron.failed.retry', $fj->id) }}"
                                              onsubmit="return confirm('{{ __('Retry job #:id?', ['id' => $fj->id]) }}')">
                                            @csrf
                                            <button type="submit"
                                                class="inline-flex w-full items-center justify-center gap-1 rounded border border-indigo-300 bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                                                <iconify-icon icon="lucide:rotate-ccw" width="12"></iconify-icon>
                                                {{ __('Retry') }}
                                            </button>
                                        </form>

                                        {{-- Delete --}}
                                        <form method="POST"
                                              action="{{ route('admin.cron.failed.delete', $fj->id) }}"
                                              onsubmit="return confirm('{{ __('Delete failed job #:id?', ['id' => $fj->id]) }}')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex w-full items-center justify-center gap-1 rounded border border-red-200 bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                                <iconify-icon icon="lucide:trash-2" width="12"></iconify-icon>
                                                {{ __('Dismiss') }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ============================================================
     JOB CARDS
     ============================================================ --}}
<div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
    @foreach ($jobs as $job)
        @php
            $hasLog = !empty($job['log_file']) && file_exists(storage_path('logs/' . $job['log_file']));
        @endphp
        <div class="flex flex-col rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

            {{-- Card header --}}
            <div class="flex items-start justify-between border-b border-gray-100 px-5 py-4 dark:border-gray-700">
                <div class="min-w-0 flex-1">
                    <h3 class="font-semibold text-gray-900 dark:text-white">{{ $job['name'] }}</h3>
                    <p class="mt-0.5 font-mono text-xs text-gray-400 dark:text-gray-500">
                        php artisan {{ $job['command'] }}
                    </p>
                </div>
                <span class="ml-2 shrink-0 rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                    {{ $job['schedule'] }}
                </span>
            </div>

            {{-- Card body --}}
            <div class="flex-1 space-y-3 px-5 py-4">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $job['description'] }}</p>

                {{-- Last run --}}
                <div class="flex items-center gap-2 text-xs">
                    <span class="text-gray-400">{{ __('Last run:') }}</span>
                    @if ($job['last_run'])
                        <span class="font-medium text-gray-700 dark:text-gray-300">
                            {{ \Carbon\Carbon::parse($job['last_run'])->timezone('Europe/Stockholm')->format('d M Y H:i') }}
                        </span>
                        <span class="text-gray-400">({{ \Carbon\Carbon::parse($job['last_run'])->diffForHumans() }})</span>
                    @else
                        <span class="text-gray-400 italic">{{ __('No record found') }}</span>
                    @endif
                </div>

                {{-- Pending count --}}
                @if ($job['pending'] !== null)
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-gray-400">{{ __('Pending:') }}</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold
                            {{ $job['pending'] > 0
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                                : 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' }}">
                            {{ $job['pending'] > 0 ? $job['pending'] . ' ' . __('item(s)') : __('None') }}
                        </span>
                    </div>
                @endif
            </div>

            {{-- Card footer --}}
            <div class="flex items-center justify-between border-t border-gray-100 px-5 py-3 dark:border-gray-700">
                {{-- View log --}}
                @if ($hasLog)
                    <a href="{{ route('admin.cron.log', $job['log_file']) }}"
                        class="text-xs text-indigo-600 hover:underline dark:text-indigo-400">
                        {{ __('View log') }} ↗
                    </a>
                @else
                    <span class="text-xs text-gray-300 dark:text-gray-600">{{ __('No log yet') }}</span>
                @endif

                {{-- Run manually --}}
                @can('update', App\Models\Customer::class)
                    <form method="POST" action="{{ route('admin.cron.run') }}"
                        onsubmit="return confirm('{{ __('Run :cmd now?', ['cmd' => $job['command']]) }}')">
                        @csrf
                        <input type="hidden" name="command" value="{{ $job['command'] }}">
                        <button type="submit"
                            class="inline-flex items-center gap-1.5 rounded border border-indigo-300 bg-indigo-50 px-3 py-1.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100 dark:border-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-300">
                            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            {{ __('Run Now') }}
                        </button>
                    </form>
                @endcan
            </div>
        </div>
    @endforeach
</div>

{{-- ============================================================
     QUICK REFERENCE
     ============================================================ --}}
<div class="mt-8">
    <x-card>
        <x-slot name="header">{{ __('Available Artisan Commands') }}</x-slot>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-gray-200 text-left text-xs dark:border-gray-700">
                        <th class="pb-2 pr-6 font-semibold text-gray-500">{{ __('Command') }}</th>
                        <th class="pb-2 pr-6 font-semibold text-gray-500">{{ __('Description') }}</th>
                        <th class="pb-2 font-semibold text-gray-500">{{ __('Options') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                    @foreach ([
                        ['reminders:all',              __('Run all daily reminder jobs'),                     '--dry-run'],
                        ['reminders:investigation',    __('Investigation reminders to company managers'),      '--dry-run'],
                        ['reminders:staff',            __('Staff reminders for stalled candidates'),           '--dry-run'],
                        ['emails:process-delayed',     __('Send queued delayed emails (email_delay=1)'),       '--dry-run, --limit=N'],
                        ['invoices:generate',          __('Auto-generate customer invoices'),                  '--force, --customer=ID, --dry-run'],
                        ['cleanup:otp',                __('Remove expired OTP records'),                       '—'],
                    ] as [$cmd, $desc, $opts])
                        <tr>
                            <td class="py-2.5 pr-6 font-mono text-xs text-indigo-600 dark:text-indigo-400">
                                php artisan {{ $cmd }}
                            </td>
                            <td class="py-2.5 pr-6 text-gray-700 dark:text-gray-300">{{ $desc }}</td>
                            <td class="py-2.5 font-mono text-xs text-gray-400">{{ $opts }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-card>
</div>

</x-layouts.backend-layout>
