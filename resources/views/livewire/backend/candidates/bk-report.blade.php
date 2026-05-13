<div class="space-y-4" x-data="{ activeLang: $wire.entangle('activeLang') }"
    x-init="window.__bkWire = $wire">

    {{-- Candidate info bar --}}
    <div class="flex flex-wrap items-center gap-4 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm dark:border-gray-700 dark:bg-gray-900">
        @if($candidateName)
            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ $candidateName }}</span>
        @endif
        @if($orderRef)
            <span class="font-mono text-indigo-600 dark:text-indigo-400">{{ $orderRef }}</span>
        @endif
        @if($customerName)
            <span class="text-gray-500 dark:text-gray-400">{{ $customerName }}</span>
        @endif
        @if($serviceName)
            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-medium text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">{{ $serviceName }}</span>
        @endif
        @foreach(['sv' => 'sv', 'en' => 'en'] as $l => $_)
            <span class="ml-auto text-xs {{ $isOverridden[$l] ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-600' }}">
                {{ strtoupper($l) }}: {{ $isOverridden[$l] ? __('Candidate override') : __('Using template') }}
            </span>
        @endforeach
    </div>

    {{-- Action bar --}}
    <div class="flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-3 dark:border-gray-700 dark:bg-gray-900">
        {{-- Language tabs --}}
        <div class="flex items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-0.5 dark:border-gray-700 dark:bg-gray-800">
            <button type="button" @click="activeLang = 'sv'"
                :class="activeLang === 'sv' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'"
                class="rounded-md px-4 py-1.5 text-xs font-semibold transition">Swedish</button>
            <button type="button" @click="activeLang = 'en'"
                :class="activeLang === 'en' ? 'bg-indigo-600 text-white' : 'text-gray-600 hover:text-gray-900 dark:text-gray-400'"
                class="rounded-md px-4 py-1.5 text-xs font-semibold transition">English</button>
        </div>

        <div class="flex flex-wrap items-center gap-2 sm:ml-auto">
            {{-- Reset to template --}}
            <button type="button"
                wire:click="resetToTemplate"
                wire:confirm="{{ __('Reset this candidate\'s report to the customer/global template? All saved changes will be lost.') }}"
                class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 px-4 py-1.5 text-xs font-semibold text-amber-700 transition hover:bg-amber-50 disabled:opacity-50 dark:border-amber-700 dark:text-amber-400"
                {{ ! ($isOverridden['sv'] || $isOverridden['en']) ? 'disabled' : '' }}>
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 14 14" stroke="currentColor" stroke-width="1.6">
                    <path stroke-linecap="round" d="M2 7a5 5 0 1 0 1-2.9M2 2v4h4"/>
                </svg>
                {{ __('Reset to Template') }}
            </button>

            {{-- Preview (modal iframe, current language) --}}
            <button type="button" @click="window.bkGenerate(activeLang, 'preview')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-4 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                    <path d="M1 8s3-5 7-5 7 5 7 5-3 5-7 5-7-5-7-5Z"/><circle cx="8" cy="8" r="2"/>
                </svg>
                {{ __('Preview') }}
            </button>

            {{-- Download PDF (current language) --}}
            <button type="button" @click="window.bkGenerate(activeLang, 'download')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-red-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" d="M8 2v8m0 0-3-3m3 3 3-3M3 13h10"/>
                </svg>
                {{ __('Download PDF') }}
            </button>

            {{-- Submit PDF (upload to server, current language) --}}
            <button type="button" @click="window.bkGenerate(activeLang, 'upload')"
                class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-4 py-1.5 text-xs font-semibold text-white transition hover:bg-green-700">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" d="M8 12V4m0 0L5 7m3-3 3 3M3 13h10"/>
                </svg>
                {{ __('Submit Report') }}
            </button>

            {{-- Save --}}
            <button type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-5 py-1.5 text-xs font-semibold text-white transition hover:bg-indigo-700 disabled:opacity-60">
                <svg wire:loading wire:target="save" class="h-3.5 w-3.5 animate-spin" viewBox="0 0 14 14" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M7 1a6 6 0 1 0 0 12A6 6 0 0 0 7 1Z" opacity=".2"/>
                    <path d="M13 7a6 6 0 0 0-6-6" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">{{ __('Save Report') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    </div>

    {{-- Section editor per language --}}
    @foreach(['sv' => __('Swedish'), 'en' => __('English')] as $lang => $languageLabel)
        <div x-show="activeLang === '{{ $lang }}'" x-cloak class="space-y-3">

            {{-- Preset section buttons --}}
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    <button type="button" wire:click="loadBkPreset('{{ $lang }}', 1)"
                        wire:confirm="{{ __('Replace all sections with BK Level 1 default?') }}"
                        class="rounded bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-800">
                        {{ __('Load BK Level 1') }}
                    </button>
                    <button type="button" wire:click="loadBkPreset('{{ $lang }}', 2)"
                        wire:confirm="{{ __('Replace all sections with BK Level 2 default?') }}"
                        class="rounded bg-green-700 px-3 py-2 text-xs font-semibold text-white hover:bg-green-800">
                        {{ __('Load BK Level 2') }}
                    </button>
                </div>
                <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Add Text Section') }}</p>
                <div class="mt-1 grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                    @foreach(['introduction' => 'Introduction', 'background' => 'Background', 'information' => 'Info & Facts', 'summary' => 'Summary', 'sociala_medier' => 'Social Media', 'kallor' => 'Sources', 'ansvar' => 'Liability', 'metod' => 'Method'] as $preset => $label)
                        <button type="button" wire:click="addLegacySection('{{ $lang }}', '{{ $preset }}')"
                            class="rounded bg-slate-700 px-2 py-1.5 text-xs font-semibold text-white hover:bg-slate-800">{{ $label }}</button>
                    @endforeach
                </div>
                <p class="mt-2 text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ __('Add Table Section') }}</p>
                <div class="mt-1 grid grid-cols-2 gap-1.5 sm:grid-cols-4">
                    @foreach(['profile' => 'Profile', 'economy' => 'Economy', 'income' => 'Income', 'legal' => 'Legal', 'bolagsengagemang' => 'Company', 'korkort' => 'Driving Licence', 'fordonskontroll' => 'Vehicle', 'fastighetsinnehav' => 'Property', 'pep_sanktion' => 'PEP/Sanction', 'cv_arbetsgivare' => 'CV Employer', 'cv_utbildning' => 'CV Education'] as $preset => $label)
                        <button type="button" wire:click="addLegacySection('{{ $lang }}', '{{ $preset }}')"
                            class="rounded bg-slate-600 px-2 py-1.5 text-xs font-semibold text-white hover:bg-slate-700">{{ $label }}</button>
                    @endforeach
                </div>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <button type="button" wire:click="addTableSection('{{ $lang }}')"
                        class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('Add Table') }}</button>
                    <button type="button" wire:click="addPageBreakSection('{{ $lang }}')"
                        class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('Page Break') }}</button>
                    <button type="button" wire:click="addTextSection('{{ $lang }}')"
                        class="rounded bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">{{ __('Add Text') }}</button>
                </div>
            </div>

            {{-- Sections --}}
            <div class="space-y-3">
                @forelse(($templates[$lang]['sections'] ?? []) as $si => $section)
                    <div wire:key="bk-{{ $lang }}-{{ $si }}" class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                        <div class="flex items-center justify-between border-b border-gray-100 bg-gray-50 px-4 py-2 dark:border-gray-700 dark:bg-gray-800">
                            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                {{ str_replace('_', ' ', $section['type']) }}
                                @if(!empty($section['heading']) || !empty($section['caption']))
                                    — <span class="text-gray-700 dark:text-gray-300">{{ $section['heading'] ?? $section['caption'] }}</span>
                                @endif
                            </span>
                            <button type="button" wire:click="removeSection('{{ $lang }}', {{ $si }})"
                                class="rounded px-2 py-0.5 text-[11px] font-semibold text-red-500 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-900/20">
                                {{ __('Delete') }}
                            </button>
                        </div>

                        <div class="p-4">
                            @if(($section['type'] ?? null) === 'text')
                                <div class="space-y-2">
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Heading') }}</label>
                                        <input type="text"
                                            wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.heading"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Content') }}</label>
                                        <textarea rows="5"
                                            wire:model.live.debounce.300ms="templates.{{ $lang }}.sections.{{ $si }}.content"
                                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100"></textarea>
                                    </div>
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Align') }}</label>
                                            <select wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.align"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                <option value="left">{{ __('Left') }}</option>
                                                <option value="justify">{{ __('Justify') }}</option>
                                                <option value="right">{{ __('Right') }}</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            @elseif(($section['type'] ?? null) === 'table')
                                @php $cols = max(3, min(5, (int)($section['columns'] ?? 3))); @endphp
                                <div class="space-y-2">
                                    <div class="grid grid-cols-2 gap-2">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Caption') }}</label>
                                            <input type="text" wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.caption"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-500">{{ __('Columns') }}</label>
                                            <select wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.columns"
                                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm dark:border-gray-600 dark:bg-gray-800 dark:text-gray-100">
                                                <option value="3">3</option><option value="4">4</option><option value="5">5</option>
                                            </select>
                                        </div>
                                    </div>
                                    {{-- Headers --}}
                                    <div class="grid gap-1" style="grid-template-columns: repeat({{ $cols }}, 1fr)">
                                        @foreach(range(0, $cols - 1) as $hi)
                                            <input type="text"
                                                wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.headers.{{ $hi }}"
                                                placeholder="{{ __('Header') }} {{ $hi + 1 }}"
                                                class="rounded border border-gray-200 bg-gray-50 px-2 py-1.5 text-xs font-semibold dark:border-gray-700 dark:bg-gray-700 dark:text-gray-200" />
                                        @endforeach
                                    </div>
                                    {{-- Rows --}}
                                    @foreach(($section['rows'] ?? []) as $ri => $row)
                                        <div wire:key="row-{{ $lang }}-{{ $si }}-{{ $ri }}" class="grid gap-1 items-center" style="grid-template-columns: repeat({{ $cols }}, 1fr) 28px">
                                            @foreach(range(1, $cols) as $ci)
                                                <input type="text"
                                                    wire:model.live="templates.{{ $lang }}.sections.{{ $si }}.rows.{{ $ri }}.c{{ $ci }}"
                                                    placeholder="{{ __('Col') }} {{ $ci }}"
                                                    class="rounded border border-gray-200 px-2 py-1.5 text-xs dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200" />
                                            @endforeach
                                            <button type="button" wire:click="removeTableRow('{{ $lang }}', {{ $si }}, {{ $ri }})"
                                                class="flex h-6 w-6 items-center justify-center rounded border border-red-200 text-xs font-bold text-red-500 hover:bg-red-50">×</button>
                                        </div>
                                    @endforeach
                                    <button type="button" wire:click="addTableRow('{{ $lang }}', {{ $si }})"
                                        class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700">
                                        {{ __('+ Add Row') }}
                                    </button>
                                </div>

                            @else
                                {{-- Page break --}}
                                <div class="flex items-center gap-2 text-sm text-gray-500">
                                    <svg class="h-4 w-4" fill="none" viewBox="0 0 16 16" stroke="currentColor" stroke-width="1.5">
                                        <path stroke-linecap="round" d="M2 8h12"/>
                                    </svg>
                                    {{ __('Page break') }}
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 py-8 text-center text-sm text-gray-400 dark:border-gray-700 dark:bg-gray-800/30">
                        {{ __('No sections yet. Load a BK preset or add sections manually above.') }}
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
