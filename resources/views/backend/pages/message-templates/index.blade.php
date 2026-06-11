<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">

@php
    // $colLabels and $messageCols are passed dynamically from MessageTemplateController.
    // Each entry: ['label' => '...', 'group' => '...', 'code' => '...']
    $groups = collect($messageCols)
        ->groupBy(fn ($col) => $colLabels[$col]['group'] ?? 'Other')
        ->sortKeys();

    $previewUrl = route($prefix . '.message-templates.preview');
    $loadUrl    = route($prefix . '.message-templates.load');
    $saveAllUrl = route($prefix . '.message-templates.save-all');
    $copyUrl    = route($prefix . '.message-templates.copy');
@endphp

<div x-data="messageTemplates()" x-init="init()" class="space-y-5">

    {{-- ================================================================
         SELECTOR PANEL
         ================================================================ --}}
    <div class="rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 items-end">

            {{-- Customer --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Customer') }} <span class="text-red-500">*</span>
                </label>
                <select x-model="sel.cus_id" @change="onSelectionChange()" class="form-control text-sm">
                    <option value="">{{ __('— Select customer —') }}</option>
                    @foreach ($customers as $c)
                        <option value="{{ $c->id }}">
                            {{ $c->user?->name ?? "#$c->id" }}
                            @if($c->company) — {{ $c->company }} @endif
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Service Type --}}
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">
                    {{ __('Service Type') }} <span class="text-red-500">*</span>
                </label>
                <select x-model="sel.interview_id" @change="onSelectionChange()" class="form-control text-sm">
                    <option value="">{{ __('— Select service type —') }}</option>
                    @foreach ($serviceTypes as $st)
                        <option value="{{ $st->id }}">{{ $st->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Status badge + Load button --}}
            <div class="flex items-end gap-2">
                <div class="flex-1">
                    <p class="mb-1 text-xs font-medium text-gray-400">{{ __('Template status') }}</p>
                    <div x-show="sel.cus_id && sel.interview_id">
                        <template x-if="exists">
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-700 dark:bg-green-900/40 dark:text-green-300">
                                ✓ {{ __('Exists') }}
                            </span>
                        </template>
                        <template x-if="!exists && loaded">
                            <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                {{ __('Not created yet') }}
                            </span>
                        </template>
                    </div>
                </div>
                <span x-show="loading" class="text-indigo-600 dark:text-indigo-400">
                    <iconify-icon icon="lucide:loader-circle" class="animate-spin" height="20"></iconify-icon>
                </span>
            </div>

            {{-- Copy from + Save all --}}
            <div class="flex items-end gap-2">
                <button type="button" @click="showCopyModal = true"
                    :disabled="!sel.cus_id || !sel.interview_id"
                    class="rounded border border-gray-300 px-3 py-2 text-xs font-medium text-gray-600 hover:bg-gray-50 disabled:opacity-50 dark:border-gray-600 dark:text-gray-400">
                    <iconify-icon icon="lucide:copy" height="13" class="mr-1"></iconify-icon>
                    {{ __('Copy From') }}
                </button>
                <button type="button" @click="saveAll()"
                    :disabled="!sel.cus_id || !sel.interview_id || saving"
                    class="flex items-center gap-1.5 rounded bg-indigo-600 px-3 py-2 text-xs font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    <iconify-icon :icon="saving ? 'lucide:loader-circle' : 'lucide:save'" :class="saving ? 'animate-spin' : ''" height="13"></iconify-icon>
                    {{ __('Save All') }}
                </button>
            </div>
        </div>

        {{-- Success/Error message --}}
        <template x-if="flashMsg">
            <div class="mt-3 flex items-center gap-2 rounded-md px-3 py-2 text-xs font-medium"
                :class="flashType === 'success'
                    ? 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400'
                    : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400'">
                <iconify-icon :icon="flashType === 'success' ? 'lucide:check-circle' : 'lucide:alert-circle'" height="14"></iconify-icon>
                <span x-text="flashMsg"></span>
            </div>
        </template>
    </div>

    {{-- ================================================================
         VARIABLE PICKER (sticky sidebar)
         ================================================================ --}}
    <div class="grid gap-5 lg:grid-cols-4">

        {{-- Templates: 3/4 --}}
        <div class="lg:col-span-3 space-y-4" x-show="sel.cus_id && sel.interview_id" x-cloak>

            {{-- Empty state --}}
            <template x-if="loaded && !loading && !exists">
                <div class="rounded-xl border border-dashed border-gray-300 py-10 text-center dark:border-gray-700">
                    <iconify-icon icon="lucide:file-plus" class="mx-auto mb-2 text-gray-300 dark:text-gray-600" height="36"></iconify-icon>
                    <p class="text-sm text-gray-500">{{ __('No templates yet for this customer + service combination.') }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ __('Start editing any field below and click Save All.') }}</p>
                </div>
            </template>

            {{-- Groups accordion --}}
            @foreach ($groups as $groupName => $cols)
                <div x-data="{ open: {{ $loop->first ? 'true' : 'false' }} }"
                    class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">

                    {{-- Group header --}}
                    <button type="button" @click="open = !open"
                        class="flex w-full items-center justify-between px-5 py-3 text-left transition hover:bg-gray-50 dark:hover:bg-gray-700/40">
                        <div class="flex items-center gap-2">
                            <span class="font-semibold text-gray-800 dark:text-white">{{ $groupName }}</span>
                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                {{ count($cols) }}
                            </span>
                        </div>
                        <iconify-icon :icon="open ? 'lucide:chevron-up' : 'lucide:chevron-down'" height="16" class="text-gray-400"></iconify-icon>
                    </button>

                    {{-- Template textareas --}}
                    <div x-show="open" x-collapse class="divide-y divide-gray-100 dark:divide-gray-700">
                        @foreach ($cols as $col)
                            @php $label = $colLabels[$col]['label'] ?? str_replace('_', ' ', ucfirst($col)); @endphp
                            <div class="px-5 py-4" x-data="{ preview{{ $loop->index }}: false }">
                                <div class="mb-2 flex items-center justify-between">
                                    <div>
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">{{ $label }}</span>
                                        <code class="ml-2 rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs text-indigo-600 dark:bg-gray-700 dark:text-indigo-400">{{ $colLabels[$col]['code'] ?? $col }}</code>
                                    </div>
                                    <button type="button"
                                        @click="preview{{ $loop->index }} = !preview{{ $loop->index }}; if(preview{{ $loop->index }}) fetchPreview('{{ $col }}')"
                                        :class="preview{{ $loop->index }} ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400'"
                                        class="flex items-center gap-1 text-xs transition hover:text-indigo-600"
                                        title="{{ __('Toggle preview') }}">
                                        <iconify-icon icon="lucide:eye" height="13"></iconify-icon>
                                        {{ __('Preview') }}
                                    </button>
                                </div>

                                {{-- Textarea bound to messages[col] --}}
                                <textarea
                                    rows="4"
                                    class="form-control font-mono text-xs w-full"
                                    x-model="messages['{{ $col }}']"
                                    @focus="activeCol = '{{ $col }}'"
                                    placeholder="{{ __('Enter template body. Use {placeholders} from the panel.') }}"
                                ></textarea>

                                {{-- Preview panel --}}
                                <div x-show="preview{{ $loop->index }}" x-cloak class="mt-2 rounded-lg border border-gray-200 dark:border-gray-700">
                                    <div class="rounded-t-lg bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-500 dark:bg-gray-800">
                                        {{ __('Preview with sample data') }}
                                    </div>
                                    <div class="max-h-48 overflow-y-auto p-4 text-sm leading-relaxed text-gray-800 dark:text-gray-200"
                                        x-html="previews['{{ $col }}'] || '<span class=\'text-gray-400 italic\'>{{ __('Loading preview…') }}</span>'">
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

        </div>

        {{-- Variable picker: 1/4 --}}
        <div class="space-y-4">
            <div class="sticky top-4 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="border-b border-gray-100 px-5 py-3 dark:border-gray-700">
                    <p class="font-semibold text-gray-800 dark:text-white">{{ __('Variables') }}</p>
                    <p class="text-xs text-gray-400">{{ __('Click to insert at cursor') }}</p>
                </div>
                <div class="p-4">
                    <input type="text" x-model="varSearch"
                        placeholder="{{ __('Search…') }}"
                        class="form-control h-7 text-xs w-full mb-3" />

                    <div class="max-h-96 overflow-y-auto space-y-3 pr-1">
                        @foreach (collect($catalogue)->groupBy('group') as $gName => $vars)
                            <div>
                                <p class="mb-1 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                    {{ $gName }}
                                </p>
                                @foreach ($vars as $key => $meta)
                                    <button type="button"
                                        x-show="varSearch === '' || '{{ $meta['placeholder'] }}'.includes(varSearch.toLowerCase()) || '{{ strtolower($meta['description']) }}'.includes(varSearch.toLowerCase())"
                                        @click="insertVariable('{{ $meta['placeholder'] }}')"
                                        title="{{ $meta['description'] }}"
                                        class="group mb-1 flex w-full items-start gap-2 rounded px-2 py-1.5 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-900/20">
                                        <code class="mt-0.5 shrink-0 rounded bg-indigo-100 px-1 py-0.5 font-mono text-xs text-indigo-700 group-hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $meta['placeholder'] }}</code>
                                        <span class="text-xs text-gray-400 leading-snug">{{ $meta['description'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ================================================================
         COPY FROM MODAL
         ================================================================ --}}
    <div x-show="showCopyModal" x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/40 backdrop-blur-sm p-4"
        @click.self="showCopyModal = false"
        @keydown.escape.window="showCopyModal = false">
        <div x-show="showCopyModal"
            x-transition:enter="transition ease-out duration-200 delay-50"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            class="w-full max-w-md rounded-xl bg-white p-6 shadow-2xl dark:bg-gray-800">
            <h2 class="mb-4 font-semibold text-gray-800 dark:text-white">{{ __('Copy Templates From') }}</h2>
            <div class="space-y-3">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Source Customer') }}</label>
                    <select x-model="copy.cus_id" class="form-control text-sm">
                        <option value="">{{ __('— Select customer —') }}</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}">{{ $c->user?->name ?? "#$c->id" }}@if($c->company) — {{ $c->company }} @endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-600 dark:text-gray-400">{{ __('Source Service Type') }}</label>
                    <select x-model="copy.interview_id" class="form-control text-sm">
                        <option value="">{{ __('— Select service type —') }}</option>
                        @foreach ($serviceTypes as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}</option>
                        @endforeach
                    </select>
                </div>
                <p class="text-xs text-gray-400">
                    {{ __('This will overwrite all templates for the currently selected customer + service type.') }}
                </p>
            </div>
            <div class="mt-4 flex justify-end gap-2">
                <button type="button" @click="showCopyModal = false"
                    class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-400">
                    {{ __('Cancel') }}
                </button>
                <button type="button" @click="doCopy()"
                    :disabled="!copy.cus_id || !copy.interview_id || copying"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50">
                    <span x-text="copying ? '{{ __('Copying…') }}' : '{{ __('Copy Templates') }}'"></span>
                </button>
            </div>
        </div>
    </div>

</div>{{-- /x-data --}}

@push('scripts')
<script>
const MSG_LOAD_URL    = @js($loadUrl);
const MSG_SAVE_ALL_URL = @js($saveAllUrl);
const MSG_COPY_URL    = @js($copyUrl);
const MSG_PREVIEW_URL = @js($previewUrl);
const CSRF            = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

function messageTemplates() {
    return {
        sel:           { cus_id: '', interview_id: '' },
        copy:          { cus_id: '', interview_id: '' },
        messages:      {},
        previews:      {},
        exists:        false,
        loaded:        false,
        loading:       false,
        saving:        false,
        copying:       false,
        showCopyModal: false,
        flashMsg:      '',
        flashType:     'success',
        activeCol:     null,
        varSearch:     '',

        init() {
            // Initialise messages object with all known columns = ''.
            const cols = @json($messageCols);
            cols.forEach(c => this.messages[c] = '');
        },

        async onSelectionChange() {
            if (!this.sel.cus_id || !this.sel.interview_id) return;
            this.loading = true;
            this.loaded  = false;
            this.previews = {};

            try {
                const url  = `${MSG_LOAD_URL}?cus_id=${this.sel.cus_id}&interview_id=${this.sel.interview_id}`;
                const res  = await fetch(url, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const json = await res.json();

                this.exists  = json.exists;
                this.loaded  = true;

                // Populate messages — use server values or empty string.
                const cols = @json($messageCols);
                cols.forEach(c => {
                    this.messages[c] = json.messages?.[c] ?? '';
                });
            } finally {
                this.loading = false;
            }
        },

        async saveAll() {
            if (!this.sel.cus_id || !this.sel.interview_id) return;
            this.saving = true;
            try {
                const res  = await fetch(MSG_SAVE_ALL_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        cus_id:       this.sel.cus_id,
                        interview_id: this.sel.interview_id,
                        columns:      this.messages,
                    }),
                });
                const json = await res.json();
                this.flash(json.success ? 'success' : 'error', json.message);
                if (json.success) this.exists = true;
            } catch (e) {
                this.flash('error', 'Save failed. Please try again.');
            } finally {
                this.saving = false;
            }
        },

        async doCopy() {
            if (!this.copy.cus_id || !this.copy.interview_id) return;
            this.copying = true;
            try {
                const res  = await fetch(MSG_COPY_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({
                        from_cus_id:       this.copy.cus_id,
                        from_interview_id: this.copy.interview_id,
                        to_cus_id:         this.sel.cus_id,
                        to_interview_id:   this.sel.interview_id,
                    }),
                });
                const json = await res.json();
                this.flash(json.success ? 'success' : 'error', json.message);
                if (json.success) {
                    this.showCopyModal = false;
                    await this.onSelectionChange(); // Reload from server
                }
            } finally {
                this.copying = false;
            }
        },

        async fetchPreview(col) {
            const body = this.messages[col] || '';
            if (!body) { this.previews[col] = '<span class="text-gray-400 italic">Empty template.</span>'; return; }
            try {
                const res  = await fetch(MSG_PREVIEW_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': CSRF,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ body }),
                });
                const json = await res.json();
                this.previews[col] = json.html;
            } catch {
                this.previews[col] = '<span class="text-red-500">Preview failed.</span>';
            }
        },

        insertVariable(placeholder) {
            if (!this.activeCol) return;
            const ta = document.querySelector(`textarea[x-model="messages['${this.activeCol}']"]`);
            if (ta) {
                const s = ta.selectionStart ?? ta.value.length;
                this.messages[this.activeCol] =
                    ta.value.slice(0, s) + placeholder + ta.value.slice(ta.selectionEnd ?? s);
                this.$nextTick(() => {
                    ta.focus();
                    ta.selectionStart = ta.selectionEnd = s + placeholder.length;
                });
            } else {
                this.messages[this.activeCol] = (this.messages[this.activeCol] || '') + placeholder;
            }
        },

        flash(type, msg) {
            this.flashType = type;
            this.flashMsg  = msg;
            setTimeout(() => this.flashMsg = '', 4000);
        },
    };
}
</script>
@endpush

</x-layouts.backend-layout>
