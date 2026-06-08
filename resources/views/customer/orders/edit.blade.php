@extends('customer.layouts.app')

@section('title', __('Edit Order') . ' #' . $candidate->order_id . ' | ' . config('app.name'))
@section('page-title', __('Edit Order') . ' #' . $candidate->order_id)

@push('styles')
<style>
.wizard-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.wizard-tab  { display:flex; align-items:center; gap:8px; padding:10px 20px;
               border-radius:8px; border:2px solid #e5e7eb; background:#fff;
               cursor:pointer; font-weight:600; font-size:14px; color:#6b7280;
               transition:all .25s; }
.wizard-tab.active { border-color:#8b2b2d; background:#8b2b2d; color:#fff; transform:scale(1.05); }
.dark .wizard-tab  { background:#1f2937; border-color:#374151; color:#9ca3af; }
.dark .wizard-tab.active { background:#8b2b2d; border-color:#8b2b2d; color:#fff; }
.tab-panel { display:none; }
.tab-panel.active { display:block; animation:fadeIn .3s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }
.wizard-label { display:block; font-size:13px; font-weight:600; color:#374151; margin-bottom:4px; }
.dark .wizard-label { color:#d1d5db; }
.wizard-input { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px;
                font-size:14px; background:#fff; color:#111827; outline:none; transition:border-color .2s; }
.wizard-input:focus { border-color:#8b2b2d; box-shadow:0 0 0 3px rgba(139,43,45,.15); }
.dark .wizard-input { background:#1f2937; border-color:#374151; color:#f9fafb; }
.file-item { display:flex; align-items:center; justify-content:space-between;
             background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;
             padding:6px 12px; margin-top:6px; font-size:13px; }
.dark .file-item { background:#374151; border-color:#4b5563; color:#d1d5db; }
.dropzone-area { border:2px dashed #d1d5db; border-radius:10px; padding:24px;
                 text-align:center; cursor:pointer; transition:all .2s; }
.dropzone-area:hover { border-color:#8b2b2d; background:#fdf6f6; }
.dropzone-area i { font-size:28px; color:#8b2b2d; margin-bottom:6px; display:block; }
</style>
@endpush

@section('content')

{{-- Breadcrumb --}}
<nav class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('customer.orders.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('Orders') }}</a>
    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
    <a href="{{ route('customer.orders.show', $candidate->id) }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ $candidate->order_id }}</a>
    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
    <span class="font-medium text-gray-800 dark:text-white">{{ __('Edit') }}</span>
</nav>

<div class="mx-auto max-w-2xl">
<div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">

    {{-- Service info (read-only) --}}
    <div class="mb-5 flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-700/40">
        <iconify-icon icon="lucide:layers" width="16" class="shrink-0 text-brand-600 dark:text-brand-400"></iconify-icon>
        <div>
            <span class="text-xs text-gray-500 dark:text-gray-400">{{ __('Service') }}</span>
            <p class="text-sm font-medium text-gray-800 dark:text-white">{{ $candidate->service_name ?? '—' }}</p>
        </div>
        <span class="ml-auto inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium text-white"
            style="background-color:{{ $candidate->status_color ?? '#94a3b8' }}">
            {{ $candidate->status_title ?? '—' }}
        </span>
    </div>

    {{-- Validation errors --}}
    @if($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">
        <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Tab nav --}}
    <div class="wizard-tabs">
        <button type="button" class="wizard-tab active" onclick="switchTab('tab-contact', this)">
            <i class="fa-solid fa-user"></i> {{ __('Candidate Info') }}
        </button>
        <button type="button" class="wizard-tab" onclick="switchTab('tab-files', this)">
            <i class="fa-solid fa-paperclip"></i> {{ __('Attachment') }}
        </button>
        <button type="button" class="wizard-tab" onclick="switchTab('tab-billing', this)">
            <i class="fa-solid fa-money-bill"></i> {{ __('Billing Details') }}
        </button>
    </div>

    <form action="{{ route('customer.orders.update', $candidate->id) }}" method="POST" enctype="multipart/form-data">
        @csrf @method('PUT')
        <input type="hidden" name="removed_files" id="removed-files-input" value="">

        {{-- ── TAB 1: Candidate Info ──────────────────────────────────── --}}
        <div class="tab-panel active" id="tab-contact">
            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                {{ __('Fields marked with') }} <span class="text-red-500">*</span> {{ __('are mandatory') }}
            </p>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                <div>
                    <label class="wizard-label">{{ __('First Name') }} <span class="text-red-500">*</span></label>
                    <input class="wizard-input @error('name') border-red-500 @enderror"
                        name="name" type="text" value="{{ old('name', $candidate->name) }}" required>
                </div>
                <div>
                    <label class="wizard-label">{{ __('Last Name') }} <span class="text-red-500">*</span></label>
                    <input class="wizard-input @error('surname') border-red-500 @enderror"
                        name="surname" type="text" value="{{ old('surname', $candidate->surname) }}" required>
                </div>
                <div>
                    <label class="wizard-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
                    <input class="wizard-input @error('email') border-red-500 @enderror"
                        name="email" type="email" value="{{ old('email', $candidate->email) }}" required>
                </div>
                <div>
                    <label class="wizard-label">{{ __('Phone') }} <span class="text-red-500">*</span></label>
                    <input class="wizard-input @error('phone') border-red-500 @enderror"
                        name="phone" type="tel" value="{{ old('phone', $candidate->phone) }}" required>
                </div>
                <div style="grid-column:span 2">
                    {{-- PNR toggle --}}
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
                        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
                            <input type="checkbox" id="hasPersonalId"
                                style="accent-color:#8b2b2d;width:16px;height:16px"
                                onchange="togglePnr()"
                                {{ old('hasPersonalId', $candidate->hasPersonalId) == '1' ? 'checked' : '' }}>
                            <span class="dark:text-gray-300">
                                <strong>{{ __('Has Personal ID Number') }}</strong><br>
                                <span class="text-xs text-gray-500">{{ __('Check if the candidate has a Swedish personal ID number (PNR)') }}</span>
                            </span>
                        </label>
                    </div>
                    <label class="wizard-label" id="pnr-label">
                        {{ $candidate->hasPersonalId ? __('Social Security Number') : __('Date of Birth') }}
                        <span class="text-red-500">*</span>
                    </label>
                    {{-- Always rendered as text — flatpickr (a calendar widget) is attached
                         when this represents a date of birth instead of a native date input. --}}
                    <input class="wizard-input" id="ssn" name="security" required type="text"
                        autocomplete="off"
                        placeholder="{{ $candidate->hasPersonalId ? 'YYMMDD-XXXX' : __('Select date of birth') }}"
                        value="{{ old('security', $candidate->security) }}">
                    <input type="hidden" name="hasPersonalId" id="hidden-has-personal-id"
                        value="{{ old('hasPersonalId', $candidate->hasPersonalId ? '1' : '0') }}">
                    <small id="pnrHelp" class="mt-1 block text-xs text-gray-400"></small>
                </div>
                <div style="grid-column:span 2">
                    <label class="wizard-label">{{ __('VASC ID') }}</label>
                    <input class="wizard-input" name="vasc_id" type="text"
                        value="{{ old('vasc_id', $candidate->vasc_id) }}">
                </div>
            </div>

            {{-- Location (if service requires it) --}}
            @if(($candidate->service_place ?? '0') == '1')
            <div class="mt-3">
                <label class="wizard-label">{{ __('Interview Location') }}</label>
                <select name="place" class="wizard-input">
                    @foreach($places as $p)
                    <option value="{{ $p->id }}" {{ $candidate->place == $p->id ? 'selected' : '' }}>
                        {{ $p->name }}
                    </option>
                    @endforeach
                </select>
            </div>
            @endif

            {{-- Custom questions --}}
            @if(!empty($metaData))
            <div class="mt-4 border-t border-gray-100 pt-4 dark:border-gray-700">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ __('Additional Information') }}
                </p>
                @foreach($metaData as $idx => $answer)
                @if(isset($answer['question']))
                <div class="mb-3">
                    <label class="wizard-label">{{ $answer['question'] }}</label>
                    <input class="wizard-input" name="custom_answers[{{ $idx }}][answer]" type="text"
                        value="{{ is_array($answer['answer'] ?? '') ? implode(', ', $answer['answer']) : ($answer['answer'] ?? '') }}">
                    <input type="hidden" name="custom_answers[{{ $idx }}][question]" value="{{ $answer['question'] }}">
                </div>
                @endif
                @endforeach
            </div>
            @endif

            <div class="mt-4 flex justify-end">
                <button type="button" onclick="switchTab('tab-files', document.querySelectorAll('.wizard-tab')[1])"
                    class="btn-primary inline-flex items-center gap-2">
                    {{ __('Next') }} <iconify-icon icon="lucide:arrow-right" width="14"></iconify-icon>
                </button>
            </div>
        </div>

        {{-- ── TAB 2: Attachments ──────────────────────────────────────── --}}
        <div class="tab-panel" id="tab-files">

            {{-- Existing CV files --}}
            @if(!empty($cvFiles))
            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('Current Files') }}
                <span class="ml-1 font-normal normal-case text-gray-400">({{ count($cvFiles) }}/10)</span>
            </p>
            <div id="existing-files-list">
                @foreach($cvFiles as $file)
                <div class="file-item" id="file-{{ md5($file) }}">
                    <span class="flex items-center gap-2">
                        <iconify-icon icon="lucide:file-text" width="14" class="shrink-0 text-brand-500"></iconify-icon>
                        {{ $file }}
                    </span>
                    <button type="button"
                        onclick="markRemove('{{ $file }}', '{{ md5($file) }}')"
                        class="ml-3 text-red-500 hover:text-red-700">
                        <iconify-icon icon="lucide:trash-2" width="14"></iconify-icon>
                    </button>
                </div>
                @endforeach
            </div>
            @endif

            {{-- Upload new files --}}
            <p class="mb-2 mt-4 text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                {{ __('Add More Files') }}
            </p>
            <input type="file" name="cv[]" id="cv-input" multiple accept=".pdf,.doc,.docx"
                style="display:none" onchange="previewNewFiles(this)">
            <div class="dropzone-area" onclick="document.getElementById('cv-input').click()">
                <i class="fa-solid fa-cloud-arrow-up"></i>
                <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Click to upload CV / Documents') }}</h6>
                <span class="text-xs text-gray-400">{{ __('PDF, DOC, DOCX — max 10 MB each') }}</span>
            </div>
            <div id="new-files-preview" class="mt-2"></div>

            <div class="mt-4 flex items-center justify-between">
                <button type="button" onclick="switchTab('tab-contact', document.querySelectorAll('.wizard-tab')[0])"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon> {{ __('Previous') }}
                </button>
                <button type="button" onclick="switchTab('tab-billing', document.querySelectorAll('.wizard-tab')[2])"
                    class="btn-primary inline-flex items-center gap-2">
                    {{ __('Next') }} <iconify-icon icon="lucide:arrow-right" width="14"></iconify-icon>
                </button>
            </div>
        </div>

        {{-- ── TAB 3: Billing Details ──────────────────────────────────── --}}
        <div class="tab-panel" id="tab-billing">
            <div style="display:grid;grid-template-columns:1fr;gap:12px;">
                <div>
                    <label class="wizard-label">{{ __('Invoice Recipient') }}</label>
                    <input class="wizard-input" name="referensperson" type="text"
                        value="{{ old('referensperson', $candidate->referensperson) }}"
                        placeholder="{{ __('Reference person name') }}">
                </div>
                <div>
                    <label class="wizard-label">{{ __('Invoice Reference') }}</label>
                    <input class="wizard-input" name="reference" type="text"
                        value="{{ old('reference', $candidate->reference) }}"
                        placeholder="{{ __('Reference number') }}">
                </div>
                <div>
                    <label class="wizard-label">{{ __('Invoice Comment') }}</label>
                    <input class="wizard-input" name="comment" type="text"
                        value="{{ old('comment', $candidate->comment) }}"
                        placeholder="{{ __('Billing comment') }}">
                </div>
                <div>
                    <label class="wizard-label">{{ __('Note') }}</label>
                    <textarea class="wizard-input" name="note" rows="3"
                        placeholder="{{ __('Internal note') }}">{{ old('note', $candidate->note) }}</textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-between">
                <button type="button" onclick="switchTab('tab-files', document.querySelectorAll('.wizard-tab')[1])"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon> {{ __('Previous') }}
                </button>
                <button type="submit" class="btn-primary inline-flex items-center gap-2">
                    <iconify-icon icon="lucide:save" width="14"></iconify-icon>
                    {{ __('Save Changes') }}
                </button>
            </div>
        </div>

    </form>
</div>
</div>

@endsection

@push('scripts')
<script>
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.wizard-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (btn) btn.classList.add('active');
}

// Attach/detach the flatpickr calendar on the security field depending on whether
// it currently represents a date of birth.
function setSecurityDatePicker(input, enable) {
    if (enable) {
        if (!input._flatpickr) {
            flatpickr(input, {
                dateFormat: 'Y-m-d',
                allowInput: true,
                disableMobile: true,
                static: true,
                locale: { firstDayOfWeek: 1 },
            });
        }
    } else if (input._flatpickr) {
        input._flatpickr.destroy();
    }
}

// PNR toggle
function togglePnr() {
    const cb  = document.getElementById('hasPersonalId');
    const ssn = document.getElementById('ssn');
    const lbl = document.getElementById('pnr-label');
    const hid = document.getElementById('hidden-has-personal-id');
    hid.value = cb.checked ? '1' : '0';
    if (cb.checked) {
        setSecurityDatePicker(ssn, false);
        ssn.placeholder = 'YYMMDD-XXXX'; ssn.value = '';
        lbl.innerHTML = '{{ __('Social Security Number') }} <span class="text-red-500">*</span>';
    } else {
        ssn.value = '';
        ssn.placeholder = '{{ __('Select date of birth') }}';
        setSecurityDatePicker(ssn, true);
        lbl.innerHTML = '{{ __('Date of Birth') }} <span class="text-red-500">*</span>';
    }
}

// Initialize the calendar on page load if the candidate currently has no personal ID.
document.addEventListener('DOMContentLoaded', function () {
    const ssn = document.getElementById('ssn');
    if (ssn && !{{ $candidate->hasPersonalId ? 'true' : 'false' }}) {
        setSecurityDatePicker(ssn, true);
    }
});

// Mark file for removal
let removedFiles = [];
function markRemove(filename, hash) {
    removedFiles.push(filename);
    document.getElementById('removed-files-input').value = removedFiles.join(',');
    const el = document.getElementById('file-' + hash);
    if (el) el.remove();
}

// Preview newly selected files
function previewNewFiles(input) {
    const container = document.getElementById('new-files-preview');
    container.innerHTML = '';
    Array.from(input.files).forEach(f => {
        container.innerHTML += `<div class="file-item">
            <span class="flex items-center gap-2">
                <iconify-icon icon="lucide:file" width="14" class="text-brand-500"></iconify-icon>
                ${f.name}
            </span>
        </div>`;
    });
}
</script>
@endpush
