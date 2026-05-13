@extends('customer.layouts.app')

@section('title', __('Create Order') . ' | ' . config('app.name'))
@section('page-title', __('Create Order'))

@push('styles')
<style>
/* ── Wizard tab pills ─────────────────────────────────────────────────────── */
.wizard-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:20px; }
.wizard-tab  { display:flex; align-items:center; gap:8px; padding:10px 20px;
               border-radius:8px; border:2px solid #e5e7eb; background:#fff;
               cursor:pointer; font-weight:600; font-size:14px; color:#6b7280;
               transition:all .25s; }
.wizard-tab i { font-size:16px; }
.wizard-tab.active { border-color:#8b2b2d; background:#8b2b2d; color:#fff; transform:scale(1.05); }
.dark .wizard-tab  { background:#1f2937; border-color:#374151; color:#9ca3af; }
.dark .wizard-tab.active { background:#8b2b2d; border-color:#8b2b2d; color:#fff; }

/* ── Tab panels ──────────────────────────────────────────────────────────── */
.tab-panel { display:none; }
.tab-panel.active { display:block; animation:fadeIn .3s ease; }
@keyframes fadeIn { from{opacity:0;transform:translateY(4px)} to{opacity:1;transform:none} }

/* ── Service category cards ──────────────────────────────────────────────── */
.service-cat-card { border:2px solid #e5e7eb; border-radius:12px; padding:20px 12px;
                    text-align:center; cursor:pointer; transition:all .2s;
                    background:#fff; }
.service-cat-card:hover,
.service-cat-card.active { background:#8b2b2d; color:#fff; border-color:#8b2b2d; }
.service-cat-card:hover h3,
.service-cat-card.active h3 { color:#fff; }
.dark .service-cat-card { background:#1f2937; border-color:#374151; color:#d1d5db; }
.dark .service-cat-card:hover,
.dark .service-cat-card.active { background:#8b2b2d; border-color:#8b2b2d; }

/* ── Dropzone areas ──────────────────────────────────────────────────────── */
.dropzone-area { border:2px dashed #d1d5db; border-radius:10px; padding:30px;
                 text-align:center; cursor:pointer; transition:all .2s; }
.dropzone-area:hover { border-color:#8b2b2d; background:#fdf6f6; }
.dropzone-area i { font-size:32px; color:#8b2b2d; margin-bottom:8px; display:block; }
.file-list { margin-top:12px; }
.file-item { display:flex; align-items:center; justify-content:space-between;
             background:#f9fafb; border:1px solid #e5e7eb; border-radius:6px;
             padding:6px 12px; margin-top:6px; font-size:13px; }
.file-item button { background:none; border:none; color:#ef4444; cursor:pointer; font-size:16px; }

/* ── Form controls inside wizard ─────────────────────────────────────────── */
.wizard-label { display:block; font-size:13px; font-weight:600; color:#374151;
                margin-bottom:4px; }
.dark .wizard-label { color:#d1d5db; }
.wizard-input { width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px;
                font-size:14px; background:#fff; color:#111827; outline:none;
                transition:border-color .2s; }
.wizard-input:focus { border-color:#8b2b2d; box-shadow:0 0 0 3px rgba(139,43,45,.15); }
.wizard-input.is-invalid { border-color:#ef4444; }
.wizard-input.is-valid   { border-color:#22c55e; }
.dark .wizard-input { background:#1f2937; border-color:#374151; color:#f9fafb; }

/* ── Success screen ──────────────────────────────────────────────────────── */
#success-screen { display:none; text-align:center; padding:40px 20px; }
#success-screen .success-icon { width:64px; height:64px; border-radius:50%;
    background:#dcfce7; display:inline-flex; align-items:center; justify-content:center;
    margin-bottom:16px; }
</style>
@endpush

@section('content')

{{-- ── Page: "Choose service category" ─────────────────────────────────── --}}
<div id="step-category">
    <h2 class="mb-4 text-lg font-semibold text-gray-800 dark:text-white">
        {{ __('Choose Service Category') }}
    </h2>

    @if($serviceCategories->isEmpty())
        <div class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-center dark:border-amber-800 dark:bg-amber-900/20">
            <iconify-icon icon="lucide:alert-circle" width="32" class="mx-auto mb-2 block text-amber-500"></iconify-icon>
            <p class="text-sm text-amber-700 dark:text-amber-300">
                {{ __('No services are assigned to your account. Please contact support.') }}
            </p>
        </div>
    @else
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
        @foreach($serviceCategories as $cat)
        <div class="service-cat-card" data-id="{{ $cat->id }}" onclick="fetchServices(this)">
            <img src="{{ asset('images/site/interview.png') }}"
                 onerror="this.style.display='none'"
                 height="80" class="mx-auto mb-3 block" alt="{{ $cat->name }}">
            <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $cat->name }}</h3>
        </div>
        @endforeach
    </div>
    @endif
</div>

{{-- ── Main form (hidden until category selected) ──────────────────────── --}}
<div id="main-form-div" style="display:none" class="mt-6">
<div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">

    {{-- Service selector --}}
    <div class="mb-5">
        <label class="wizard-label">{{ __('Choose Service') }} <span class="text-red-500">*</span></label>
        <select id="interviewSelect" class="wizard-input" onchange="onServiceChange(this)" required>
            <option value="" disabled selected>{{ __('Choose service') }}</option>
        </select>

        {{-- Service info bar (delivery days) --}}
        <div id="service-info-bar" style="display:none"
            class="mt-3 flex items-center gap-4 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm dark:border-gray-700 dark:bg-gray-700">
            <span class="flex items-center gap-2 text-gray-600 dark:text-gray-300">
                <iconify-icon icon="lucide:clock" width="14"></iconify-icon>
                <span id="delivery-days-text"></span>
            </span>
        </div>
    </div>

    {{-- Wizard (hidden until service selected) --}}
    <div id="wizard-wrapper" style="display:none">

        {{-- Tab nav --}}
        <div class="wizard-tabs">
            <button type="button" class="wizard-tab active" data-tab="wizard-contact" onclick="switchTab('wizard-contact', this)">
                <i class="fa-solid fa-user"></i> {{ __('Candidate Info') }}
            </button>
            <button type="button" class="wizard-tab" data-tab="wizard-cart" id="attachment-tab-btn" onclick="switchTab('wizard-cart', this)">
                <i class="fa-solid fa-paperclip"></i> {{ __('Attachment') }}
            </button>
            <button type="button" class="wizard-tab" data-tab="wizard-banking" onclick="switchTab('wizard-banking', this)">
                <i class="fa-solid fa-money-bill"></i> {{ __('Billing Details') }}
            </button>
        </div>

        <form id="orderForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="service_type_id" id="hidden-service-id">
            <input type="hidden" name="hasPersonalId"   id="hidden-has-personal-id" value="0">

            {{-- ── TAB 1: Candidate Info ──────────────────────────────── --}}
            <div class="tab-panel active" id="wizard-contact">
                <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">
                    {{ __('Fields marked with') }} <span class="text-red-500">*</span> {{ __('are mandatory') }}
                </p>
                <div class="row-grid" id="personal_info_row" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
                    {{-- Populated by JS --}}
                </div>
                <div id="place-field" style="display:none" class="mt-3">
                    <label class="wizard-label">{{ __('Place') }} <span class="text-red-500">*</span></label>
                    <select name="place" class="wizard-input" id="placeSelect">
                        @foreach($places as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div id="country-field" style="display:none" class="mt-3">
                    <label class="wizard-label">{{ __('Country') }} <span class="text-red-500">*</span></label>
                    <select name="country" class="wizard-input" id="countrySelect">
                        @foreach(['Sweden','Norway','Denmark','Finland','Germany','France','United Kingdom','United States','Afghanistan','Albania','Algeria','Andorra','Angola','Argentina','Armenia','Australia','Austria','Azerbaijan','Bahamas','Bahrain','Bangladesh','Belgium','Bolivia','Bosnia and Herzegovina','Brazil','Bulgaria','Cambodia','Canada','Chile','China','Colombia','Croatia','Cuba','Cyprus','Czech Republic','Denmark','Ecuador','Egypt','Estonia','Ethiopia','Faroe Islands','Finland','France','Georgia','Ghana','Greece','Guatemala','Hungary','Iceland','India','Indonesia','Iran','Iraq','Ireland','Israel','Italy','Jamaica','Japan','Jordan','Kazakhstan','Kenya','Kosovo','Latvia','Lebanon','Lithuania','Luxembourg','Malaysia','Malta','Mexico','Moldova','Monaco','Montenegro','Morocco','Netherlands','New Zealand','Nigeria','North Macedonia','Norway','Pakistan','Panama','Peru','Philippines','Poland','Portugal','Romania','Russia','Saudi Arabia','Serbia','Singapore','Slovakia','Slovenia','South Africa','South Korea','Spain','Sri Lanka','Sweden','Switzerland','Tanzania','Thailand','Tunisia','Turkey','Ukraine','United Arab Emirates','United Kingdom','United States','Uruguay','Uzbekistan','Venezuela','Vietnam','Yemen','Zimbabwe'] as $c)
                            <option value="{{ $c }}" {{ $c === 'Sweden' ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mt-4 flex justify-end">
                    <button type="button" onclick="switchTab('wizard-cart', document.querySelector('[data-tab=wizard-cart]'))" class="btn-primary inline-flex items-center gap-2">
                        {{ __('Next') }} <iconify-icon icon="lucide:arrow-right" width="14"></iconify-icon>
                    </button>
                </div>
            </div>

            {{-- ── TAB 2: Attachment ───────────────────────────────────── --}}
            <div class="tab-panel" id="wizard-cart">
                <div id="cv-upload-area">
                    <label class="wizard-label mb-2 block">{{ __('CV / Documents') }}</label>
                    <input type="file" name="cv[]" id="cv-file-input" multiple accept=".pdf,.doc,.docx" style="display:none" onchange="handleCvFiles(this)">
                    <div class="dropzone-area" onclick="document.getElementById('cv-file-input').click()">
                        <i class="fa-solid fa-cloud-arrow-up"></i>
                        <h6 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Upload CV / Documents') }}</h6>
                        <span class="text-xs text-gray-400">({{ __('Click to upload — PDF, DOC, DOCX, max 10 MB') }})</span>
                    </div>
                    <div class="file-list" id="cv-file-list"></div>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" onclick="switchTab('wizard-contact', document.querySelector('[data-tab=wizard-contact]'))" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon> {{ __('Previous') }}
                    </button>
                    <button type="button" onclick="switchTab('wizard-banking', document.querySelector('[data-tab=wizard-banking]'))" class="btn-primary inline-flex items-center gap-2">
                        {{ __('Next') }} <iconify-icon icon="lucide:arrow-right" width="14"></iconify-icon>
                    </button>
                </div>
            </div>

            {{-- ── TAB 3: Billing Details ──────────────────────────────── --}}
            <div class="tab-panel" id="wizard-banking">
                <div style="display:grid;grid-template-columns:1fr;gap:12px;" id="billing_info_row">
                    {{-- Populated by JS --}}
                </div>
                {{-- Integrity policy checkbox --}}
                <div class="mt-5 rounded-lg border border-gray-200 bg-gray-50 p-4 dark:border-gray-700 dark:bg-gray-700/40">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" id="approvedFollowUp" name="agreed" value="1" required
                            class="mt-0.5 h-4 w-4 shrink-0 rounded border-gray-300 text-brand-600">
                        <span class="text-sm text-gray-700 dark:text-gray-300">
                            {{ __('I have read and agree to the') }}
                            <button type="button" onclick="document.getElementById('integrity-modal').classList.remove('hidden')"
                                class="font-semibold text-brand-600 hover:underline dark:text-brand-400">
                                {{ __('integrity policy') }}
                            </button>,
                            {{ __('and confirm the candidate has been informed about this check.') }}
                            <span class="text-red-500">*</span>
                        </span>
                    </label>
                </div>
                <div class="mt-4 flex justify-between">
                    <button type="button" onclick="switchTab('wizard-cart', document.querySelector('[data-tab=wizard-cart]'))" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                        <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon> {{ __('Previous') }}
                    </button>
                    <button type="button" id="submit-btn" onclick="submitOrder(this)" class="btn-primary inline-flex items-center gap-2">
                        <iconify-icon icon="lucide:send" width="14"></iconify-icon>
                        {{ __('Submit Order') }}
                    </button>
                </div>
            </div>

        </form>

        {{-- ── Success screen ─────────────────────────────────────────── --}}
        <div id="success-screen">
            <div class="success-icon mx-auto">
                <iconify-icon icon="lucide:check" width="28" class="text-green-600"></iconify-icon>
            </div>
            <h3 class="mb-2 text-xl font-bold text-gray-800 dark:text-white">{{ __('Order Submitted!') }}</h3>
            <p class="mb-4 text-sm text-gray-500 dark:text-gray-400">{{ __('Your order has been created successfully.') }}</p>
            <p class="mb-6 text-sm text-gray-600 dark:text-gray-300">
                {{ __('Order ID:') }}
                <a id="success-order-link" href="#" class="font-mono font-bold text-brand-600 hover:underline dark:text-brand-400"></a>
            </p>
            <div class="flex justify-center gap-3">
                <a id="view-order-btn" href="#" class="btn-primary inline-flex items-center gap-2">
                    <iconify-icon icon="lucide:eye" width="14"></iconify-icon>
                    {{ __('View Order') }}
                </a>
                <a href="{{ route('customer.orders.create') }}" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300">
                    <iconify-icon icon="lucide:plus" width="14"></iconify-icon>
                    {{ __('New Order') }}
                </a>
            </div>
        </div>

    </div>{{-- /wizard-wrapper --}}
</div>
</div>

{{-- ── Integrity Policy Modal ───────────────────────────────────────────── --}}
<div id="integrity-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="relative w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800" style="max-height:85vh;display:flex;flex-direction:column">
        <div class="flex shrink-0 items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Integrity Policy') }}</h3>
            <button onclick="document.getElementById('integrity-modal').classList.add('hidden')"
                class="rounded p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:x" width="16"></iconify-icon>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-4 text-sm text-gray-700 dark:text-gray-300 space-y-3">
            <p>{{ __('This integrity policy is issued by Recway AB (org.nr 559102-3444). We handle personal data in accordance with GDPR. Background checks may include verification of education, employment history, references, and in some cases credit information.') }}</p>
            <p>{{ __('Data collected during a background check is retained only for as long as required to complete the assignment and fulfill our obligations, then deleted within 14 days of completion.') }}</p>
            <p>{{ __('You have the right to access, correct, or delete your personal data. Contact us at') }} <a href="mailto:dataprotection@recway.se" class="text-brand-600 hover:underline">dataprotection@recway.se</a>.</p>
        </div>
        <div class="shrink-0 flex justify-end border-t border-gray-200 px-5 py-3 dark:border-gray-700">
            <button onclick="document.getElementById('integrity-modal').classList.add('hidden')"
                class="btn-primary text-sm">{{ __('Close') }}</button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF   = document.querySelector('meta[name=csrf-token]').content;
const ROUTES = {
    services:  '{{ route('customer.orders.services') }}',
    fetchForm: '{{ route('customer.orders.fetch-form') }}',
    store:     '{{ route('customer.orders.store') }}',
    showBase:  '{{ url('customer/orders') }}',
};

// Store accumulated CV files
let cvFiles = [];
// Store current service data
let currentService = null;

// ── Category card click ──────────────────────────────────────────────────
async function fetchServices(card) {
    document.querySelectorAll('.service-cat-card').forEach(c => c.classList.remove('active'));
    card.classList.add('active');

    const catId = card.dataset.id;
    const select = document.getElementById('interviewSelect');
    select.innerHTML = '<option value="" disabled selected>{{ __("Loading...") }}</option>';
    document.getElementById('main-form-div').style.display = 'block';
    document.getElementById('wizard-wrapper').style.display = 'none';
    document.getElementById('service-info-bar').style.display = 'none';

    const res  = await fetch(ROUTES.services, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ category_id: catId }),
    });
    const data = await res.json();

    select.innerHTML = '<option value="" disabled selected>{{ __("Choose service") }}</option>';
    data.forEach(s => {
        const o = new Option(s.name, s.id);
        o.dataset.place        = s.place;
        o.dataset.deliveryDays = s.delivery_days ?? '';
        select.add(o);
    });
}

// ── Service dropdown change ──────────────────────────────────────────────
function onServiceChange(sel) {
    const opt = sel.selectedOptions[0];
    if (!opt || !opt.value) return;

    currentService = {
        id:           opt.value,
        place:        opt.dataset.place,
        deliveryDays: opt.dataset.deliveryDays,
    };

    document.getElementById('hidden-service-id').value = opt.value;

    // Delivery days bar
    const bar = document.getElementById('service-info-bar');
    const txt = document.getElementById('delivery-days-text');
    if (currentService.deliveryDays && currentService.deliveryDays !== 'null') {
        txt.textContent = '{{ __('Delivery:') }} ' + currentService.deliveryDays + ' {{ __('days') }}';
        bar.style.display = 'flex';
    } else {
        bar.style.display = 'none';
    }

    // Place / country fields
    document.getElementById('place-field').style.display   = currentService.place == '1' ? 'block' : 'none';
    document.getElementById('country-field').style.display = 'none';

    // Show wizard
    document.getElementById('wizard-wrapper').style.display = 'block';
    document.getElementById('success-screen').style.display = 'none';
    document.getElementById('orderForm').style.display      = '';
    switchTab('wizard-contact', document.querySelector('[data-tab=wizard-contact]'));

    // Fetch custom form (or defaults)
    loadForm(opt.value);
}

// ── Load form fields via AJAX ────────────────────────────────────────────
async function loadForm(serviceId) {
    const res  = await fetch(ROUTES.fetchForm, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
        body: JSON.stringify({ service_id: serviceId }),
    });
    const data = await res.json();

    if (data.form) {
        renderCustomForm(JSON.parse(data.form));
    } else {
        renderDefaultForm();
    }
}

// ── Default form HTML ────────────────────────────────────────────────────
function renderDefaultForm() {
    const T = window.__trans;
    document.getElementById('personal_info_row').innerHTML = `
        <div><label class="wizard-label">${T.name} <span class="text-red-500">*</span></label>
             <input class="wizard-input" name="name" type="text" placeholder="${T.enter_name}" required></div>
        <div><label class="wizard-label">${T.surname} <span class="text-red-500">*</span></label>
             <input class="wizard-input" name="surname" type="text" placeholder="${T.surname}" required></div>
        <div><label class="wizard-label">${T.email} <span class="text-red-500">*</span></label>
             <input class="wizard-input" name="email" type="email" placeholder="example@email.com" required></div>
        <div><label class="wizard-label">${T.phone} <span class="text-red-500">*</span></label>
             <input class="wizard-input" name="phone" type="tel" placeholder="${T.phone}" required></div>
        ${buildPnrBlock()}
        <div style="grid-column:span 2"><label class="wizard-label">${T.vasc_id}</label>
             <input class="wizard-input" name="vasc_id" type="text" placeholder="${T.vasc_id}"></div>
    `;
    document.getElementById('billing_info_row').innerHTML = `
        <div><label class="wizard-label">${T.reference_rec}</label>
             <input class="wizard-input" name="referensperson" type="text" placeholder="${T.reference_rec}"></div>
        <div><label class="wizard-label">${T.reference}</label>
             <input class="wizard-input" name="reference" type="text" placeholder="${T.reference}"></div>
        <div><label class="wizard-label">${T.invoice_comment}</label>
             <input class="wizard-input" name="comment" type="text" placeholder="${T.invoice_comment}"></div>
        <div style="grid-column:span 1"><label class="wizard-label">${T.note}</label>
             <textarea class="wizard-input" name="note" rows="3" placeholder="${T.note}"></textarea></div>
    `;
    initPnrToggle();
}

function buildPnrBlock() {
    const T = window.__trans;
    return `
    <div style="grid-column:span 2">
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:8px;">
        <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer">
          <input type="checkbox" id="hasPersonalId" style="accent-color:#8b2b2d;width:16px;height:16px" onchange="togglePnr()">
          <span class="dark:text-gray-300"><strong>${T.has_pnr_bold}</strong><br>${T.has_pnr_normal}</span>
        </label>
      </div>
      <label class="wizard-label" id="pnr-label">${T.date_of_birth} <span class="text-red-500">*</span></label>
      <input class="wizard-input" id="ssn" name="security" type="date" required>
      <small id="pnrHelp" class="mt-1 block text-xs text-red-500"></small>
    </div>`;
}

function togglePnr() {
    const cb  = document.getElementById('hasPersonalId');
    const ssn = document.getElementById('ssn');
    const lbl = document.getElementById('pnr-label');
    const hlp = document.getElementById('pnrHelp');
    const T   = window.__trans;
    document.getElementById('hidden-has-personal-id').value = cb.checked ? '1' : '0';
    if (cb.checked) {
        ssn.type = 'text'; ssn.placeholder = 'YYMMDD-XXXX'; ssn.value = '';
        lbl.innerHTML = T.social_security + ' <span class="text-red-500">*</span>';
    } else {
        ssn.type = 'date'; ssn.placeholder = ''; ssn.value = '';
        lbl.innerHTML = T.date_of_birth + ' <span class="text-red-500">*</span>';
    }
    ssn.classList.remove('is-valid','is-invalid');
    hlp.textContent = '';
}

function initPnrToggle() {
    const ssn = document.getElementById('ssn');
    if (ssn) {
        ssn.addEventListener('input', function() {
            const cb = document.getElementById('hasPersonalId');
            const hlp = document.getElementById('pnrHelp');
            if (cb && cb.checked) {
                const r = validatePNR(this.value);
                this.classList.toggle('is-valid',   r.isValid);
                this.classList.toggle('is-invalid', !r.isValid);
                hlp.textContent  = r.message;
                hlp.className = 'mt-1 block text-xs ' + (r.isValid ? 'text-green-500' : 'text-red-500');
            }
        });
    }
}

function validatePNR(v) {
    if (!v.trim()) return { isValid:false, message:'{{ __('Personal ID is required') }}' };
    const m = v.match(/^(\d{6})-?(\d{4})$/);
    if (!m) return { isValid:false, message:'{{ __('Format: YYMMDD-XXXX or YYMMDDXXXX') }}' };
    const mo = parseInt(v.replace('-','').substring(2,4));
    if (mo < 1 || mo > 12) return { isValid:false, message:'{{ __('Invalid month') }}' };
    return { isValid:true, message:'{{ __('Personal ID is valid') }}' };
}

// ── Render custom form builder JSON ─────────────────────────────────────
function renderCustomForm(data) {
    const fb = data.form_builder || data;
    let per = '', bil = '';
    if (fb.personal_info) {
        Object.entries(fb.personal_info).forEach(([key, val]) => {
            const p = key.split(',');
            per += buildField(p, val, false);
        });
    }
    if (fb.billing_info) {
        Object.entries(fb.billing_info).forEach(([key, val]) => {
            const p = key.split(',');
            bil += buildField(p, val, false);
        });
    }
    document.getElementById('personal_info_row').innerHTML = per || '';
    document.getElementById('billing_info_row').innerHTML  = bil || '';
    initPnrToggle();
}

function buildField([type, label, name, ph, req,, isNew, defVal], val, billing) {
    const nameAttr = isNew ? `form_builder[${label}]` : name;
    const reqAttr  = req ? 'required' : '';
    if (type === 'select') {
        const opts = (defVal||'').split('|').filter(Boolean)
            .map(o => `<option value="${o}" ${val===o?'selected':''}>${o}</option>`).join('');
        return `<div><label class="wizard-label">${label}${req?'<span class="text-red-500">*</span>':''}</label>
                <select class="wizard-input" name="${nameAttr}" ${reqAttr}><option value="" hidden>${ph}</option>${opts}</select></div>`;
    }
    if (name === 'note') {
        return `<div><label class="wizard-label">${label}</label>
                <textarea class="wizard-input" name="note" rows="3">${val||''}</textarea></div>`;
    }
    return `<div><label class="wizard-label">${label}${req?'<span class="text-red-500">*</span>':''}</label>
            <input class="wizard-input" name="${nameAttr}" type="${type}" placeholder="${ph}" value="${val||''}" ${reqAttr}></div>`;
}

// ── CV file handling ─────────────────────────────────────────────────────
function handleCvFiles(input) {
    Array.from(input.files).forEach(f => {
        if (cvFiles.length < 10) cvFiles.push(f);
    });
    renderCvList();
    // Reset so same files can be re-picked
    input.value = '';
}

function renderCvList() {
    const list = document.getElementById('cv-file-list');
    list.innerHTML = cvFiles.map((f,i) =>
        `<div class="file-item">
            <span><iconify-icon icon="lucide:file" width="14"></iconify-icon> ${f.name}</span>
            <button type="button" onclick="removeFile(${i})">✕</button>
        </div>`).join('');
}

function removeFile(i) { cvFiles.splice(i,1); renderCvList(); }

// ── Tab switching ────────────────────────────────────────────────────────
function switchTab(tabId, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.wizard-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(tabId).classList.add('active');
    if (btn) btn.classList.add('active');
}

// ── Order submission ─────────────────────────────────────────────────────
async function submitOrder(btn) {
    const form = document.getElementById('orderForm');
    const agreed = document.getElementById('approvedFollowUp');
    if (!agreed.checked) {
        alert('{{ __('Please agree to the integrity policy.') }}');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="lucide:loader-circle" class="animate-spin" width="14"></iconify-icon> {{ __('Processing...') }}';

    const fd = new FormData(form);

    // Attach CV files from our tracked array
    cvFiles.forEach(f => fd.append('cv[]', f));

    try {
        const res  = await fetch(ROUTES.store, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept':'application/json' },
            body: fd,
        });
        const data = await res.json();

        if (data.success) {
            form.style.display = 'none';
            document.getElementById('success-screen').style.display = 'block';
            document.getElementById('success-order-link').textContent = data.orderId;
            document.getElementById('success-order-link').href = ROUTES.showBase + '/' + data.keyId;
            document.getElementById('view-order-btn').href     = ROUTES.showBase + '/' + data.keyId;
        } else {
            alert(data.error || data.message || '{{ __('An error occurred. Please try again.') }}');
            btn.disabled = false;
            btn.innerHTML = '<iconify-icon icon="lucide:send" width="14"></iconify-icon> {{ __('Submit Order') }}';
        }
    } catch(e) {
        alert('{{ __('Network error. Please try again.') }}');
        btn.disabled = false;
        btn.innerHTML = '<iconify-icon icon="lucide:send" width="14"></iconify-icon> {{ __('Submit Order') }}';
    }
}

// ── Translation strings for JS ───────────────────────────────────────────
window.__trans = {
    name:             '{{ __('Name') }}',
    enter_name:       '{{ __('Enter name') }}',
    surname:          '{{ __('Surname') }}',
    email:            '{{ __('Email') }}',
    phone:            '{{ __('Phone') }}',
    social_security:  '{{ __('Social Security Number') }}',
    date_of_birth:    '{{ __('Date of Birth') }}',
    vasc_id:          '{{ __('VASC ID') }}',
    reference_rec:    '{{ __('Invoice Recipient') }}',
    reference:        '{{ __('Invoice Reference') }}',
    invoice_comment:  '{{ __('Invoice Comment') }}',
    note:             '{{ __('Note') }}',
    has_pnr_bold:     '{{ __('Has Personal ID Number') }}',
    has_pnr_normal:   '{{ __('Check if the candidate has a Swedish personal ID number (PNR)') }}',
};
</script>
@endpush
