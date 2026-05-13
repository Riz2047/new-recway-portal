<div class="py-6">

    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Reminder Emails') }}
        </h3>
    </div>

    <div class="space-y-6">

        {{-- Interview Reminder Email --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

            {{-- Section header with toggle --}}
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-5 py-3.5 dark:border-gray-700 dark:bg-gray-800/50">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Interview Reminder Email') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Sent to the candidate before their interview appointment.') }}</p>
                </div>
                {{-- Toggle switch --}}
                <button
                    type="button"
                    wire:click="$toggle('remainderEmail')"
                    @class([
                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                        'bg-indigo-600' => $remainderEmail,
                        'bg-gray-200 dark:bg-gray-700' => ! $remainderEmail,
                    ])
                    role="switch"
                    :aria-checked="$wire.remainderEmail ? 'true' : 'false'"
                >
                    <span @class([
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200',
                        'translate-x-5' => $remainderEmail,
                        'translate-x-0' => ! $remainderEmail,
                    ])></span>
                </button>
            </div>

            {{-- Template textarea --}}
            <div class="p-5">
                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-400">
                    {{ __('Email Template') }}
                    <span class="ml-1 font-normal normal-case text-gray-400">{{ __('(HTML supported)') }}</span>
                </label>
                <div class="relative">
                    <textarea
                        wire:model.defer="remainderEmailTemplate"
                        rows="7"
                        placeholder="{{ __('Enter HTML email template for interview reminders…') }}"
                        @class([
                            'w-full resize-y rounded-lg border px-3 py-2.5 font-mono text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500',
                            'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200' => $remainderEmail,
                            'border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-600' => ! $remainderEmail,
                        ])
                        {{ ! $remainderEmail ? 'disabled' : '' }}
                    ></textarea>
                    <span class="absolute right-2.5 top-2.5 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-400 dark:bg-gray-700 dark:text-gray-500">HTML</span>
                </div>
                @if(! $remainderEmail)
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-600 italic">{{ __('Enable the toggle to edit this template.') }}</p>
                @endif
            </div>
        </div>

        {{-- Background Check Reminder Email --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

            {{-- Section header with toggle --}}
            <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50/80 px-5 py-3.5 dark:border-gray-700 dark:bg-gray-800/50">
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-200">{{ __('Background Check Reminder Email') }}</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ __('Sent to the candidate as a reminder for their background check.') }}</p>
                </div>
                {{-- Toggle switch --}}
                <button
                    type="button"
                    wire:click="$toggle('bkRemainderEmail')"
                    @class([
                        'relative inline-flex h-6 w-11 shrink-0 cursor-pointer items-center rounded-full border-2 border-transparent transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2',
                        'bg-indigo-600' => $bkRemainderEmail,
                        'bg-gray-200 dark:bg-gray-700' => ! $bkRemainderEmail,
                    ])
                    role="switch"
                    :aria-checked="$wire.bkRemainderEmail ? 'true' : 'false'"
                >
                    <span @class([
                        'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200',
                        'translate-x-5' => $bkRemainderEmail,
                        'translate-x-0' => ! $bkRemainderEmail,
                    ])></span>
                </button>
            </div>

            {{-- Template textarea --}}
            <div class="p-5">
                <label class="mb-2 block text-xs font-medium uppercase tracking-wide text-gray-400">
                    {{ __('Email Template') }}
                    <span class="ml-1 font-normal normal-case text-gray-400">{{ __('(HTML supported)') }}</span>
                </label>
                <div class="relative">
                    <textarea
                        wire:model.defer="bkRemainderEmailTemplate"
                        rows="7"
                        placeholder="{{ __('Enter HTML email template for background check reminders…') }}"
                        @class([
                            'w-full resize-y rounded-lg border px-3 py-2.5 font-mono text-xs leading-relaxed focus:outline-none focus:ring-2 focus:ring-indigo-500',
                            'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200' => $bkRemainderEmail,
                            'border-gray-200 bg-gray-100 text-gray-400 dark:border-gray-700 dark:bg-gray-800/50 dark:text-gray-600' => ! $bkRemainderEmail,
                        ])
                        {{ ! $bkRemainderEmail ? 'disabled' : '' }}
                    ></textarea>
                    <span class="absolute right-2.5 top-2.5 rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-400 dark:bg-gray-700 dark:text-gray-500">HTML</span>
                </div>
                @if(! $bkRemainderEmail)
                    <p class="mt-1.5 text-xs text-gray-400 dark:text-gray-600 italic">{{ __('Enable the toggle to edit this template.') }}</p>
                @endif
            </div>
        </div>

    </div>

    {{-- Save button --}}
    <div class="mt-6 flex justify-end">
        <button
            type="button"
            wire:click="save"
            wire:loading.attr="disabled"
            class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-60"
        >
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2Z" opacity=".25"/>
                <path d="M14 8a6 6 0 0 0-6-6" stroke-linecap="round"/>
            </svg>
            <span wire:loading.remove wire:target="save">{{ __('Save') }}</span>
            <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
        </button>
    </div>
</div>
