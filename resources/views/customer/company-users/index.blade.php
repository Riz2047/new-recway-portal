@extends('customer.layouts.app')

@section('title', __('Company Staff') . ' | ' . config('app.name'))
@section('page-title', __('Company Staff'))

@push('styles')
<style>
/* ── Same iOS toggle as account page ── */
.toggle-wrap { display:inline-flex; align-items:center; }
.toggle-input { visibility:hidden; display:none; }
.toggle-label {
    position:relative; display:block; width:42px; height:24px;
    cursor:pointer; -webkit-tap-highlight-color:transparent;
    transform:translate3d(0,0,0);
}
.toggle-label::before {
    content:""; position:relative; top:1px; left:1px;
    width:40px; height:22px; display:block;
    background:#c8ccd4; border-radius:12px; transition:background .2s ease;
}
.toggle-knob {
    position:absolute; top:0; left:0; width:24px; height:24px;
    display:block; background:#fff; border-radius:50%;
    box-shadow:0 2px 6px rgba(154,153,153,.75); transition:all .2s ease;
}
.toggle-input:checked + .toggle-label::before { background:#8b2b2d; }
.toggle-input:checked + .toggle-label .toggle-knob {
    transform:translateX(18px);
    box-shadow:0 2px 6px rgba(139,43,45,.4);
}
.toggle-input:disabled + .toggle-label { opacity:.4; cursor:not-allowed; }
</style>
@endpush

@section('content')

{{-- Flash messages --}}
@if(session('success'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)"
    class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
    <iconify-icon icon="lucide:check-circle" width="16" class="shrink-0"></iconify-icon>
    {{ session('success') }}
</div>
@endif

{{-- ── Company info banner ──────────────────────────────────────────────── --}}
<div class="mb-5 flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-3 shadow-sm dark:border-gray-700 dark:bg-gray-800">
    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-700">
        <iconify-icon icon="lucide:building-2" width="18" class="text-gray-600 dark:text-gray-300"></iconify-icon>
    </div>
    <div>
        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Managing company staff for') }}</p>
        <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $manager->company }}</p>
    </div>
    <div class="ml-auto flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 dark:bg-gray-700">
        <iconify-icon icon="lucide:users" width="13" class="text-gray-500 dark:text-gray-400"></iconify-icon>
        <span class="text-xs font-medium text-gray-600 dark:text-gray-300">
            {{ $staff->count() }} {{ __('member(s)') }}
        </span>
    </div>
</div>

{{-- ── Staff table ──────────────────────────────────────────────────────── --}}
<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    x-data="customerTable('staff-tbody', { sort:'name', perPage:25 })">

    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Company Staff Members') }}</h2>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-700/40 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3 w-12">#</th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('name')" :class="thClass('name')">
                        {{ __('Name') }}<span x-html="sortIcon('name')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('email')" :class="thClass('email')">
                        {{ __('Email') }}<span x-html="sortIcon('email')"></span>
                    </th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('phone')" :class="thClass('phone')">
                        {{ __('Phone') }}<span x-html="sortIcon('phone')"></span>
                    </th>
                    <th class="px-5 py-3">{{ __('Company') }}</th>
                    <th class="px-5 py-3">{{ __('Status') }}</th>
                    <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody id="staff-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($staff as $i => $member)
                <tr id="row-{{ $member->customer_id }}"
                    class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    data-row
                    data-sort-name="{{ $member->name }}"
                    data-sort-email="{{ $member->email }}"
                    data-sort-phone="{{ $member->phone ?? '' }}">

                    <td class="px-5 py-3 text-gray-400 dark:text-gray-500 dt-num">{{ $i + 1 }}</td>

                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">
                        {{ $member->name }}
                        @if($member->is_self)
                        <span class="ml-1 rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">{{ __('You') }}</span>
                        @endif
                    </td>

                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300" id="email-{{ $member->customer_id }}">
                        {{ $member->email }}
                    </td>

                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300" id="phone-{{ $member->customer_id }}">
                        {{ $member->phone ?: '—' }}
                    </td>

                    <td class="px-5 py-3 text-gray-600 dark:text-gray-300">{{ $member->company }}</td>

                    {{-- Status toggle --}}
                    <td class="px-5 py-3">
                        <div class="flex items-center gap-2">
                            <span class="toggle-wrap">
                                <input type="checkbox"
                                    class="toggle-input"
                                    id="toggle-{{ $member->customer_id }}"
                                    {{ $member->is_active ? 'checked' : '' }}
                                    {{ $member->is_self ? 'disabled title="' . __('You cannot deactivate your own account.') . '"' : '' }}
                                    onchange="toggleStatus(this, {{ $member->customer_id }})">
                                <label class="toggle-label" for="toggle-{{ $member->customer_id }}">
                                    <span class="toggle-knob"></span>
                                </label>
                            </span>
                            <span id="status-label-{{ $member->customer_id }}"
                                class="text-xs font-medium {{ $member->is_active ? 'text-green-600 dark:text-green-400' : 'text-gray-400' }}">
                                {{ $member->is_active ? __('Active') : __('Inactive') }}
                            </span>
                        </div>
                    </td>

                    {{-- Edit action --}}
                    <td class="px-5 py-3 text-right">
                        @if(!$member->is_self)
                        <button type="button"
                            onclick="openEditModal({{ $member->customer_id }}, '{{ addslashes($member->email) }}', '{{ addslashes($member->phone ?? '') }}')"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                            <iconify-icon icon="lucide:pencil" width="12"></iconify-icon>
                            {{ __('Edit') }}
                        </button>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        <iconify-icon icon="lucide:users" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
                        {{ __('No company staff members found.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('customer.partials.dt-footer')
</div>

{{-- ── Edit Staff Modal ─────────────────────────────────────────────────── --}}
<div id="edit-modal"
    class="fixed inset-0 z-50 hidden flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm">
    <div class="w-full max-w-md rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">

        <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white">{{ __('Edit Company Staff') }}</h3>
            <button onclick="closeEditModal()"
                class="rounded-lg p-1 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700">
                <iconify-icon icon="lucide:x" width="16"></iconify-icon>
            </button>
        </div>

        {{-- Error area --}}
        <div id="modal-error" class="hidden mx-5 mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400"></div>

        <form id="edit-form" class="p-5 space-y-4">
            <input type="hidden" id="edit-customer-id">
            <div>
                <label class="form-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
                <input type="email" id="edit-email"
                    class="form-control"
                    placeholder="{{ __('Email address') }}" required>
            </div>
            <div>
                <label class="form-label">{{ __('Phone') }}</label>
                <input type="tel" id="edit-phone"
                    class="form-control"
                    placeholder="{{ __('Phone number') }}">
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditModal()"
                    class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                    {{ __('Close') }}
                </button>
                <button type="button" id="edit-submit-btn" onclick="submitEdit()"
                    class="btn-primary inline-flex items-center gap-2">
                    <iconify-icon icon="lucide:save" width="14"></iconify-icon>
                    {{ __('Update') }}
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@push('scripts')
<script>
const CSRF  = document.querySelector('meta[name=csrf-token]').content;
const URLS  = {
    toggle: '{{ route('customer.company-users.toggle') }}',
    update: '{{ route('customer.company-users.update') }}',
};

// ── Status toggle ────────────────────────────────────────────────────────
async function toggleStatus(checkbox, customerId) {
    const isActive = checkbox.checked;

    try {
        const res  = await fetch(URLS.toggle, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify({ customer_id: customerId, is_active: isActive }),
        });
        const data = await res.json();

        if (data.success) {
            const label = document.getElementById('status-label-' + customerId);
            label.textContent = isActive ? '{{ __('Active') }}' : '{{ __('Inactive') }}';
            label.className = 'text-xs font-medium ' + (isActive
                ? 'text-green-600 dark:text-green-400'
                : 'text-gray-400');
        } else {
            // Revert toggle on error
            checkbox.checked = !isActive;
            alert(data.error || '{{ __('An error occurred.') }}');
        }
    } catch (e) {
        checkbox.checked = !isActive;
        alert('{{ __('Network error. Please try again.') }}');
    }
}

// ── Edit modal ───────────────────────────────────────────────────────────
function openEditModal(customerId, email, phone) {
    document.getElementById('edit-customer-id').value = customerId;
    document.getElementById('edit-email').value        = email;
    document.getElementById('edit-phone').value        = phone;
    document.getElementById('modal-error').classList.add('hidden');
    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-email').focus();
}

function closeEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
}

// Close on backdrop click
document.getElementById('edit-modal').addEventListener('click', function (e) {
    if (e.target === this) closeEditModal();
});

async function submitEdit() {
    const customerId = document.getElementById('edit-customer-id').value;
    const email      = document.getElementById('edit-email').value.trim();
    const phone      = document.getElementById('edit-phone').value.trim();
    const errorBox   = document.getElementById('modal-error');
    const btn        = document.getElementById('edit-submit-btn');

    errorBox.classList.add('hidden');
    errorBox.textContent = '';

    if (!email) {
        errorBox.textContent = '{{ __('Email is required.') }}';
        errorBox.classList.remove('hidden');
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<iconify-icon icon="lucide:loader-circle" class="animate-spin" width="14"></iconify-icon> {{ __('Updating...') }}';

    try {
        const res  = await fetch(URLS.update, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body:    JSON.stringify({ customer_id: customerId, email, phone }),
        });
        const data = await res.json();

        if (data.success) {
            // Update table cells directly without page reload
            document.getElementById('email-' + customerId).textContent = data.email;
            document.getElementById('phone-' + customerId).textContent = data.phone || '—';
            closeEditModal();
        } else {
            const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.error || '{{ __('Update failed.') }}');
            errorBox.textContent = msg;
            errorBox.classList.remove('hidden');
        }
    } catch (e) {
        errorBox.textContent = '{{ __('Network error. Please try again.') }}';
        errorBox.classList.remove('hidden');
    }

    btn.disabled = false;
    btn.innerHTML = '<iconify-icon icon="lucide:save" width="14"></iconify-icon> {{ __('Update') }}';
}
</script>
@endpush
