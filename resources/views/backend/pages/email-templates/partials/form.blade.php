@props([
    'template'    => null,
    'routePrefix' => 'admin',
    'catalogue'   => [],
    'unknown'     => [],
])

@php
    $isEdit     = $template !== null;
    $previewUrl = route($routePrefix . '.email-templates.preview');

    // Group catalogue by group key for the picker UI.
    $groups = collect($catalogue)->groupBy('group');
@endphp

<div x-data="emailTemplateForm(@js($previewUrl))" class="space-y-5">

    {{-- ── Title + Variable key ──────────────────────────────────────────── --}}
    <x-card>
        <x-slot name="header">
            {{ $isEdit ? __('Edit Email Template') : __('New Email Template') }}
        </x-slot>

        <div class="space-y-5">
            <div>
                <label for="title" class="mb-1 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('Title') }} <span class="text-red-500">*</span>
                </label>
                <input type="text" name="title" id="title" required x-model="title"
                    class="form-control"
                    placeholder="{{ __('e.g. Investigation Reminder') }}"
                    value="{{ old('title', $template?->title ?? '') }}" />
                @error('title')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-400">
                    {{ __('Variable key') }}:
                    <code class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-indigo-700 dark:bg-gray-800 dark:text-indigo-300"
                        x-text="toVariable(title)"></code>
                </p>
            </div>
        </div>

        <x-slot name="footer">
            <x-buttons.submit-buttons cancelUrl="{{ route($routePrefix . '.email-templates.index') }}" />
        </x-slot>
    </x-card>

    {{-- ── Body editor + sidebar ──────────────────────────────────────────── --}}
    <div class="grid gap-5 lg:grid-cols-3">

        {{-- Editor (2/3) --}}
        <div class="lg:col-span-2 space-y-4">
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <span>{{ __('Body') }}</span>
                        <div class="flex items-center gap-2">
                            {{-- Unknown placeholders warning --}}
                            @if (!empty($unknown))
                                <span class="rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                    {{ count($unknown) }} {{ __('unknown placeholder(s)') }}
                                </span>
                            @endif
                            {{-- Live preview toggle --}}
                            <button type="button" @click="togglePreview()"
                                :class="showPreview ? 'bg-indigo-50 text-indigo-700 border-indigo-300 dark:bg-indigo-900/30 dark:text-indigo-300' : 'text-gray-500 border-gray-300 dark:border-gray-600 dark:text-gray-400'"
                                class="flex items-center gap-1.5 rounded border px-3 py-1.5 text-xs font-medium transition hover:opacity-80">
                                <iconify-icon icon="lucide:eye" height="13"></iconify-icon>
                                {{ __('Preview') }}
                                <span x-show="previewLoading" class="ml-1">
                                    <iconify-icon icon="lucide:loader-circle" class="animate-spin" height="12"></iconify-icon>
                                </span>
                            </button>
                        </div>
                    </div>
                </x-slot>

                {{-- Editor (shown when preview is off) --}}
                <div x-show="!showPreview">
                    <textarea id="body" name="body" class="sr-only" rows="6">{{ old('body', $template?->body ?? '') }}</textarea>
                    <x-quill-editor editor-id="body" height="380px" maxHeight="700px" type="full" />
                    @error('body')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Live preview panel --}}
                <div x-show="showPreview" x-cloak class="rounded-lg border border-gray-200 dark:border-gray-700">
                    <div class="rounded-t-lg border-b border-gray-100 bg-gray-50 px-4 py-2 text-xs font-medium text-gray-500 dark:border-gray-700 dark:bg-gray-800/60">
                        {{ __('Preview — rendered with sample data') }}
                    </div>
                    <div class="max-h-[500px] overflow-y-auto p-5 text-sm leading-relaxed text-gray-800 dark:text-gray-200"
                        x-html="previewHtml || '<p class=\'text-gray-400 italic\'>{{ __('Click Preview to render.') }}</p>'">
                    </div>

                    {{-- Unknown placeholder warnings --}}
                    <template x-if="unknownInPreview.length > 0">
                        <div class="border-t border-amber-200 bg-amber-50 px-4 py-2 dark:border-amber-800 dark:bg-amber-900/20">
                            <p class="text-xs font-medium text-amber-700 dark:text-amber-400">
                                {{ __('Unknown placeholders found:') }}
                                <span x-text="unknownInPreview.map(u => '{' + u + '}').join(', ')"></span>
                            </p>
                        </div>
                    </template>
                </div>
            </x-card>
        </div>

        {{-- Sidebar: Variable catalogue (1/3) --}}
        <div class="space-y-4">
            <x-card>
                <x-slot name="header">
                    <div class="flex items-center justify-between">
                        <span>{{ __('Available Variables') }}</span>
                        <span class="text-xs text-gray-400">{{ __('Click to insert') }}</span>
                    </div>
                </x-slot>

                {{-- Search variables --}}
                <div class="mb-3">
                    <input type="text" x-model="varSearch"
                        placeholder="{{ __('Filter variables…') }}"
                        class="form-control text-xs h-8 w-full" />
                </div>

                <div class="space-y-4 max-h-[480px] overflow-y-auto pr-1">
                    @foreach ($groups as $groupName => $vars)
                        <div>
                            <p class="mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">
                                {{ $groupName }}
                            </p>
                            <div class="space-y-1">
                                @foreach ($vars as $key => $meta)
                                    <button
                                        type="button"
                                        x-show="varSearch === '' || '{{ $meta['placeholder'] }}'.includes(varSearch.toLowerCase()) || '{{ strtolower($meta['description']) }}'.includes(varSearch.toLowerCase())"
                                        @click="insertVariable('{{ $meta['placeholder'] }}')"
                                        title="{{ $meta['description'] }}"
                                        class="group flex w-full items-start gap-2 rounded-md px-2.5 py-1.5 text-left transition hover:bg-indigo-50 dark:hover:bg-indigo-900/20"
                                    >
                                        <code class="mt-0.5 shrink-0 rounded bg-indigo-100 px-1.5 py-0.5 font-mono text-xs text-indigo-700 group-hover:bg-indigo-200 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $meta['placeholder'] }}</code>
                                        <span class="text-xs text-gray-500 dark:text-gray-400 leading-snug">{{ $meta['description'] }}</span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>

            {{-- Usage tips --}}
            <x-card>
                <x-slot name="header">{{ __('Tips') }}</x-slot>
                <ul class="space-y-1.5 text-xs text-gray-500 dark:text-gray-400">
                    <li>• {{ __('Click any variable to insert it at the cursor.') }}</li>
                    <li>• {{ __('Preview uses sample data to show how the email looks.') }}</li>
                    <li>• {{ __('Unknown variables (not in the list) are left unchanged.') }}</li>
                    <li>• {{ __('Empty variable values are removed from the output.') }}</li>
                    <li>• {{ __('HTML is supported — use bold, links, lists, etc.') }}</li>
                </ul>
            </x-card>
        </div>
    </div>

</div>{{-- /x-data --}}

@push('scripts')
<script>
function emailTemplateForm(previewUrl) {
    return {
        title:          @js(old('title', $template?->title ?? '')),
        showPreview:    false,
        previewLoading: false,
        previewHtml:    '',
        unknownInPreview: [],
        varSearch:      '',

        toVariable(t) {
            return (t || '').trim() === '' ? '—' : (t || '').trim().replace(/\s+/g, '_');
        },

        getBody() {
            // Try Quill first, fall back to raw textarea.
            const quill = window.QuillEditor?.body;
            if (quill) return quill.root.innerHTML;
            return document.getElementById('body')?.value ?? '';
        },

        insertVariable(placeholder) {
            const quill = window.QuillEditor?.body;
            if (quill) {
                const range = quill.getSelection(true);
                quill.insertText(range ? range.index : quill.getLength() - 1, placeholder, 'user');
                quill.setSelection((range ? range.index : 0) + placeholder.length);
            } else {
                const ta = document.getElementById('body');
                if (ta) {
                    const s = ta.selectionStart;
                    ta.value = ta.value.slice(0, s) + placeholder + ta.value.slice(ta.selectionEnd);
                    ta.selectionStart = ta.selectionEnd = s + placeholder.length;
                    ta.focus();
                }
            }
        },

        async togglePreview() {
            this.showPreview = !this.showPreview;
            if (this.showPreview) await this.fetchPreview();
        },

        async fetchPreview() {
            this.previewLoading = true;
            const body = this.getBody();
            try {
                const res  = await fetch(previewUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify({ body }),
                });
                const json = await res.json();
                this.previewHtml       = json.html ?? '';
                this.unknownInPreview  = json.unknown ?? [];
            } catch (e) {
                this.previewHtml = '<p class="text-red-500">Preview failed.</p>';
            } finally {
                this.previewLoading = false;
            }
        },
    };
}
</script>
@endpush
