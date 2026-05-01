<div class="space-y-4" x-data="{ activeLanguage: 'sv' }">
    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <label class="mb-1.5 block text-sm font-medium text-gray-700">{{ __('Service Type (Background Check)') }}</label>
        <select wire:model.live="selectedService"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            @forelse($services as $service)
                <option value="{{ $service->id }}">{{ $service->name }}</option>
            @empty
                <option value="">{{ __('No background check services found') }}</option>
            @endforelse
        </select>
        <div class="mt-3 rounded-lg border border-dashed border-gray-300 bg-gray-50 p-3 text-xs text-gray-600">
            <p><strong>{{ __('Supported placeholders:') }}</strong> <code>{cus_company}</code>, <code>{serviceTitle}</code>, <code>{can_name}</code></p>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4">
        <div class="mb-4 grid grid-cols-2 gap-2 rounded-lg bg-gray-100 p-1">
            <button type="button" @click="activeLanguage = 'sv'"
                    :class="activeLanguage === 'sv' ? 'bg-indigo-600 text-white' : 'bg-transparent text-gray-700'"
                    class="rounded-md px-3 py-2 text-xs font-semibold transition">
                {{ __('Swedish') }}
            </button>
            <button type="button" @click="activeLanguage = 'en'"
                    :class="activeLanguage === 'en' ? 'bg-indigo-600 text-white' : 'bg-transparent text-gray-700'"
                    class="rounded-md px-3 py-2 text-xs font-semibold transition">
                {{ __('English') }}
            </button>
        </div>

        @foreach(['sv' => __('Swedish'), 'en' => __('English')] as $lang => $languageLabel)
            <div x-show="activeLanguage === '{{ $lang }}'" x-cloak class="space-y-3">
                <div class="flex items-center justify-between">
                    <h4 class="text-base font-semibold text-gray-900">{{ __('Global Template') }} - {{ $languageLabel }}</h4>
                </div>

                <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'introduction')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Introduction') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'background')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Background') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'information')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Information & Facts') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'summary')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Summary') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'profile')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Profile') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'economy')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Economy') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'income')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Income') }}
                    </button>
                    <button type="button" wire:click="addLegacySection('{{ $lang }}', 'legal')"
                            class="rounded bg-slate-700 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800">
                        {{ __('Legal') }}
                    </button>
                </div>

                <div class="grid grid-cols-3 gap-2">
                    <button type="button" wire:click="addTableSection('{{ $lang }}')"
                            class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        {{ __('Add Table') }}
                    </button>
                    <button type="button" wire:click="addPageBreakSection('{{ $lang }}')"
                            class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        {{ __('Add Page Break') }}
                    </button>
                    <button type="button" wire:click="addTextSection('{{ $lang }}')"
                            class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        {{ __('Add Text') }}
                    </button>
                </div>

                <div class="space-y-3">
                    @foreach(($templates[$lang]['sections'] ?? []) as $sectionIndex => $section)
                        <div class="rounded-lg border border-gray-300 bg-gray-50 p-3">
                            <div class="mb-3 flex items-center justify-between">
                                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ str_replace('_', ' ', $section['type']) }}</span>
                                <button type="button" wire:click="removeSection('{{ $lang }}', {{ $sectionIndex }})"
                                        class="rounded bg-indigo-600 px-2 py-1 text-[11px] font-semibold text-white hover:bg-indigo-700">
                                    {{ __('Delete') }}
                                </button>
                            </div>

                            @if(($section['type'] ?? null) === 'text')
                                <div class="space-y-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Heading') }}</label>
                                        <input type="text" wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.heading"
                                               class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm">
                                    </div>

                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Text') }}</label>
                                        <textarea rows="5" wire:model.live.debounce.300ms="templates.{{ $lang }}.sections.{{ $sectionIndex }}.content"
                                                  class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm"></textarea>
                                    </div>

                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Align') }}</label>
                                            <select wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.align"
                                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm">
                                                <option value="left">{{ __('Left') }}</option>
                                                <option value="right">{{ __('Right') }}</option>
                                                <option value="justify">{{ __('Justify') }}</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Select Status') }}</label>
                                            <select wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.status_id"
                                                    class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm">
                                                <option value="">{{ __('No status') }}</option>
                                                @foreach($statuses as $status)
                                                    <option value="{{ $status->id }}">
                                                        {{ $status->status }}@if(!empty($status->status_sv)) / {{ $status->status_sv }}@endif
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            @elseif(($section['type'] ?? null) === 'table')
                                <div class="space-y-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Table Caption') }}</label>
                                        <input type="text" wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.caption"
                                               class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm">
                                    </div>

                                    @php($columns = max(3, min(5, (int)($section['columns'] ?? 3))))
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-700">{{ __('Columns') }}</label>
                                        <select wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.columns"
                                                class="w-full rounded border border-gray-300 px-2.5 py-2 text-sm">
                                            <option value="3">3</option>
                                            <option value="4">4</option>
                                            <option value="5">5</option>
                                        </select>
                                    </div>

                                    <div class="space-y-1 rounded border border-gray-200 bg-white p-2">
                                        <label class="block text-xs font-medium text-gray-700">{{ __('Table Headers') }}</label>
                                        <div class="grid grid-cols-5 gap-2">
                                            @foreach(range(1, 5) as $headerIndex)
                                                @if($headerIndex <= $columns)
                                                    <input type="text"
                                                           wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.headers.{{ $headerIndex - 1 }}"
                                                           class="rounded border border-gray-300 px-2 py-1.5 text-sm"
                                                           placeholder="{{ __('Header') }} {{ $headerIndex }}">
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>

                                    @foreach(($section['rows'] ?? []) as $rowIndex => $row)
                                        <div class="grid grid-cols-12 gap-2">
                                            @foreach(range(1, 5) as $colNum)
                                                @if($colNum <= $columns)
                                                    @php($colKey = 'c' . $colNum)
                                                    <input type="text"
                                                           wire:model.live="templates.{{ $lang }}.sections.{{ $sectionIndex }}.rows.{{ $rowIndex }}.{{ $colKey }}"
                                                           class="col-span-{{ $columns === 3 ? 3 : ($columns === 4 ? 2 : 2) }} rounded border border-gray-300 px-2 py-1.5 text-sm"
                                                           placeholder="{{ __('Col') }} {{ $colNum }}">
                                                @endif
                                            @endforeach
                                            <button type="button" wire:click="removeTableRow('{{ $lang }}', {{ $sectionIndex }}, {{ $rowIndex }})"
                                                    class="col-span-1 rounded border border-red-300 text-xs font-semibold text-red-600 hover:bg-red-50">
                                                X
                                            </button>
                                        </div>
                                    @endforeach

                                    <button type="button" wire:click="addTableRow('{{ $lang }}', {{ $sectionIndex }})"
                                            class="rounded bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                                        {{ __('Add Row') }}
                                    </button>
                                </div>
                            @else
                                <div class="rounded border border-dashed border-gray-300 bg-white p-3 text-sm text-gray-600">
                                    {{ __('Page break inserted.') }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    <div class="flex justify-end">
        <button type="button" wire:click="saveTemplates"
                class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-60"
                @disabled(!$selectedService)>
            {{ __('Save Global Templates') }}
        </button>
    </div>
</div>
