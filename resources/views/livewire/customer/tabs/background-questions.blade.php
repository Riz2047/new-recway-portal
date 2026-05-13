<div class="py-6" x-data="{ showAdd: false, addType: 'free_text' }">

    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Background Questions') }}
            @if(count($questions))
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                    {{ count($questions) }}
                </span>
            @endif
        </h3>
        <button
            type="button"
            @click="showAdd = !showAdd"
            class="inline-flex items-center gap-1.5 rounded-md border border-indigo-200 px-4 py-1.5 text-xs font-semibold uppercase tracking-wide text-indigo-700 shadow-sm transition hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300 dark:hover:bg-indigo-900/30"
        >
            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 14 14" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" d="M7 2v10M2 7h10"/>
            </svg>
            {{ __('Add Question') }}
        </button>
    </div>

    {{-- Add question type picker --}}
    <div
        x-show="showAdd"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        class="mb-5 rounded-xl border-2 border-indigo-200 bg-indigo-50/50 p-4 dark:border-indigo-800 dark:bg-indigo-950/20"
    >
        <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-indigo-500">{{ __('Question Type') }}</p>
        <div class="flex flex-wrap gap-3">
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 transition hover:border-indigo-400 dark:border-gray-700 dark:bg-gray-800"
                :class="addType === 'free_text' ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-800' : ''">
                <input type="radio" x-model="addType" value="free_text" class="text-indigo-600" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Free Text') }}</span>
                <span class="text-xs text-gray-400">{{ __('Open answer') }}</span>
            </label>
            <label class="flex cursor-pointer items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 transition hover:border-indigo-400 dark:border-gray-700 dark:bg-gray-800"
                :class="addType === 'radio' ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-800' : ''">
                <input type="radio" x-model="addType" value="radio" class="text-indigo-600" />
                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ __('Radio Options') }}</span>
                <span class="text-xs text-gray-400">{{ __('Multiple choice') }}</span>
            </label>
        </div>
        <div class="mt-3 flex justify-end gap-2">
            <button type="button" @click="showAdd = false"
                class="rounded-md border border-gray-300 px-4 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                {{ __('Cancel') }}
            </button>
            <button type="button" @click="$wire.addQuestion(addType); showAdd = false"
                class="rounded-md bg-indigo-600 px-4 py-1.5 text-xs font-medium text-white transition hover:bg-indigo-700">
                {{ __('Add') }}
            </button>
        </div>
    </div>

    {{-- Questions list --}}
    @if(empty($questions))
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-800/30">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('No background questions configured.') }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('Click "Add Question" to create the first question.') }}</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($questions as $qi => $question)
                <div wire:key="question-{{ $qi }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                    {{-- Question header --}}
                    <div class="flex items-center gap-3 border-b border-gray-100 bg-gray-50/80 px-4 py-2.5 dark:border-gray-700 dark:bg-gray-800/50">
                        <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Q{{ $qi + 1 }}</span>
                        <span @class([
                            'rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                            'bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300' => $question['type'] === 'radio',
                            'bg-teal-100 text-teal-700 dark:bg-teal-900 dark:text-teal-300' => $question['type'] === 'free_text',
                        ])>
                            {{ $question['type'] === 'radio' ? __('Radio') : __('Free Text') }}
                        </span>
                        <div class="flex-1"></div>
                        <button
                            type="button"
                            wire:click="removeQuestion({{ $qi }})"
                            wire:confirm="{{ __('Remove this question?') }}"
                            class="rounded p-1 text-red-400 transition hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20"
                            title="{{ __('Remove') }}"
                        >
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                                <path d="M3 4h10M6.5 4V2.8a.8.8 0 0 1 .8-.8h1.4a.8.8 0 0 1 .8.8V4M5.2 4v8.2c0 .44.36.8.8.8h4c.44 0 .8-.36.8-.8V4"/>
                                <path d="M7 6.5v4m2-4v4"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Question text --}}
                    <div class="p-4">
                        <label class="mb-1.5 block text-xs font-medium uppercase tracking-wide text-gray-400">
                            {{ __('Question Text') }}
                        </label>
                        <input
                            type="text"
                            wire:model.defer="questions.{{ $qi }}.qs"
                            placeholder="{{ __('Enter question…') }}"
                            class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200"
                        />
                    </div>

                    {{-- Options (radio only) --}}
                    @if($question['type'] === 'radio')
                        <div class="border-t border-gray-100 px-4 pb-4 dark:border-gray-700">
                            <div class="mb-2 flex items-center justify-between">
                                <label class="text-xs font-medium uppercase tracking-wide text-gray-400">{{ __('Answer Options') }}</label>
                                <button
                                    type="button"
                                    wire:click="addOption({{ $qi }})"
                                    class="inline-flex items-center gap-1 rounded border border-indigo-300 px-2.5 py-1 text-[11px] font-semibold text-indigo-600 transition hover:bg-indigo-50 dark:border-indigo-700 dark:text-indigo-300"
                                >
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" d="M6 2v8M2 6h8"/>
                                    </svg>
                                    {{ __('Add Option') }}
                                </button>
                            </div>

                            @if(empty($question['option']))
                                <p class="text-xs italic text-gray-400">{{ __('No options yet. Add at least one option.') }}</p>
                            @else
                                <div class="space-y-2">
                                    @foreach($question['option'] as $oi => $opt)
                                        <div wire:key="opt-{{ $qi }}-{{ $oi }}" class="flex items-center gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 border-gray-300 dark:border-gray-600">
                                                <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                                            </span>
                                            <input
                                                type="text"
                                                wire:model.defer="questions.{{ $qi }}.option.{{ $oi }}"
                                                placeholder="{{ __('Option') }} {{ $oi + 1 }}"
                                                class="flex-1 rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm text-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-400 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            />
                                            <button
                                                type="button"
                                                wire:click="removeOption({{ $qi }}, {{ $oi }})"
                                                class="rounded p-1 text-gray-400 transition hover:bg-red-50 hover:text-red-500 dark:hover:bg-red-900/20"
                                            >
                                                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 12 12" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" d="M2 2l8 8M10 2 2 10"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Save button --}}
        <div class="mt-5 flex justify-end">
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
                <span wire:loading.remove wire:target="save">{{ __('Save Questions') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    @endif
</div>
