@extends('customer.layouts.app')

@section('title', __('Account Settings') . ' | ' . config('app.name'))
@section('page-title', __('Account Settings'))

@section('content')
<div class="mx-auto max-w-3xl" x-data="{ tab: '{{ session('_flash.success') ? (str_contains(request()->fullUrl(), 'billing') ? 'billing' : (str_contains(request()->fullUrl(), 'email') ? 'email' : 'profile')) : 'profile' }}' }">

    {{-- Flash messages --}}
    @if(session('success'))
    <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 4000)"
        class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
        <iconify-icon icon="lucide:check-circle" width="16" class="shrink-0"></iconify-icon>
        {{ session('success') }}
    </div>
    @endif

    @if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
        <ul class="space-y-1">@foreach($errors->all() as $e)<li class="flex items-start gap-2"><iconify-icon icon="lucide:alert-circle" width="14" class="mt-0.5 shrink-0"></iconify-icon>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    {{-- Tab nav --}}
    <div class="mb-5 flex gap-1 rounded-xl border border-gray-200 bg-gray-50 p-1 dark:border-gray-700 dark:bg-gray-800">
        @foreach([
            ['profile',       'lucide:user',        __('Profile')],
            ['billing',       'lucide:receipt',      __('Billing Details')],
            ['email-settings','lucide:bell',          __('Email Settings')],
        ] as [$key, $icon, $label])
        <button @click="tab='{{ $key }}'"
            :class="tab==='{{ $key }}' ? 'bg-white shadow text-brand-600 dark:bg-gray-700 dark:text-brand-400' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
            class="flex flex-1 items-center justify-center gap-1.5 rounded-lg px-4 py-2.5 text-sm font-medium transition-all">
            <iconify-icon icon="{{ $icon }}" width="15"></iconify-icon>
            <span class="hidden sm:inline">{{ $label }}</span>
        </button>
        @endforeach
    </div>

    {{-- ── TAB 1: Profile ──────────────────────────────────────────────── --}}
    <div x-show="tab==='profile'" id="profile">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-5 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Personal Information') }}</h2>

            {{-- Read-only info --}}
            <div class="mb-5 flex items-center gap-3 rounded-lg bg-gray-50 px-4 py-3 dark:bg-gray-700/40">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-brand-100 text-brand-600 dark:bg-brand-900/20 dark:text-brand-400 text-sm font-bold">
                    {{ strtoupper(substr($user->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 dark:text-white">{{ $user->name }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Last login:') }}
                        {{ $customer?->last_login ? \Carbon\Carbon::parse($customer->last_login)->format('d M Y, H:i') : '—' }}
                    </p>
                </div>
            </div>

            <form action="{{ route('customer.account.update') }}" method="POST">
                @csrf @method('PUT')
                <div class="space-y-4">

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">{{ __('Name') }} <span class="text-red-500">*</span></label>
                            <input type="text" name="name" value="{{ old('name', $user->name) }}"
                                class="form-control @error('name') border-red-500 @enderror" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('Username') }}</label>
                            <input type="text" value="{{ $user->username }}"
                                class="form-control bg-gray-100 dark:bg-gray-700 cursor-not-allowed" readonly>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
                            <input type="email" name="email" value="{{ old('email', $user->email) }}"
                                class="form-control @error('email') border-red-500 @enderror" required>
                        </div>
                        <div>
                            <label class="form-label">{{ __('Phone') }} <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" value="{{ old('phone', $customer?->phone) }}"
                                class="form-control @error('phone') border-red-500 @enderror" required>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="form-label">{{ __('Company') }}</label>
                            <input type="text" name="company" value="{{ old('company', $customer?->company) }}"
                                class="form-control">
                        </div>
                        <div>
                            <label class="form-label">{{ __('Organization Number') }}</label>
                            <input type="text" name="org_no" value="{{ old('org_no', $customer?->org_no) }}"
                                class="form-control">
                        </div>
                    </div>

                    {{-- Password change section --}}
                    <div class="border-t border-gray-100 pt-5 dark:border-gray-700"
                        x-data="{ showPwd: false }">
                        <button type="button" @click="showPwd = !showPwd"
                            class="flex items-center gap-2 text-sm font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">
                            <iconify-icon icon="lucide:lock" width="14"></iconify-icon>
                            <span x-text="showPwd ? '{{ __('Cancel password change') }}' : '{{ __('Change Password') }}'">{{ __('Change Password') }}</span>
                        </button>

                        <div x-show="showPwd" x-transition class="mt-4 space-y-4" x-cloak>
                            {{-- Password policy notice --}}
                            <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-800 dark:bg-amber-900/20">
                                <p class="text-xs font-semibold text-amber-700 dark:text-amber-300 mb-1">{{ __('Password Requirements') }}</p>
                                <ul class="text-xs text-amber-600 dark:text-amber-400 space-y-0.5 ml-3 list-disc">
                                    <li>{{ __('Minimum 14 characters') }}</li>
                                    <li>{{ __('At least one uppercase letter (A–Z)') }}</li>
                                    <li>{{ __('At least one lowercase letter (a–z)') }}</li>
                                    <li>{{ __('At least one digit (0–9)') }}</li>
                                    <li>{{ __('At least one special character (e.g. !@#$%)') }}</li>
                                </ul>
                            </div>

                            <div x-data="{ show: false }">
                                <label class="form-label">{{ __('New Password') }}</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="password"
                                        id="password-input"
                                        class="form-control pr-10 @error('password') border-red-500 @enderror"
                                        placeholder="{{ __('Enter new password') }}"
                                        oninput="checkPasswordStrength(this.value)">
                                    <button type="button" @click="show=!show"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <iconify-icon :icon="show ? 'lucide:eye-off' : 'lucide:eye'" width="16"></iconify-icon>
                                    </button>
                                </div>
                                {{-- Strength bar --}}
                                <div class="mt-2">
                                    <div class="h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                        <div id="pwd-strength-bar" class="h-1.5 rounded-full transition-all" style="width:0"></div>
                                    </div>
                                    <p id="pwd-strength-text" class="mt-1 text-xs text-gray-400"></p>
                                </div>
                            </div>

                            <div x-data="{ show: false }">
                                <label class="form-label">{{ __('Confirm New Password') }}</label>
                                <div class="relative">
                                    <input :type="show ? 'text' : 'password'" name="password_confirmation"
                                        class="form-control pr-10"
                                        placeholder="{{ __('Confirm new password') }}">
                                    <button type="button" @click="show=!show"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                        <iconify-icon :icon="show ? 'lucide:eye-off' : 'lucide:eye'" width="16"></iconify-icon>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary inline-flex items-center gap-2">
                            <iconify-icon icon="lucide:save" width="14"></iconify-icon>
                            {{ __('Save Profile') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── TAB 2: Billing Details ───────────────────────────────────────── --}}
    <div x-show="tab==='billing'" x-cloak id="billing">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <h2 class="mb-5 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Billing Details') }}</h2>
            <p class="mb-5 text-xs text-gray-500 dark:text-gray-400">
                {{ __('These details are pre-filled on new orders as billing reference.') }}
            </p>

            <form action="{{ route('customer.account.billing') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="form-label">{{ __('Invoice Recipient') }}</label>
                        <input type="text" name="referenceperson"
                            value="{{ old('referenceperson', $billing?->referenceperson) }}"
                            class="form-control"
                            placeholder="{{ __('Reference person name') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Invoice Reference') }}</label>
                        <input type="text" name="reference"
                            value="{{ old('reference', $billing?->reference) }}"
                            class="form-control"
                            placeholder="{{ __('Reference number') }}">
                    </div>
                    <div>
                        <label class="form-label">{{ __('Invoice Comment') }}</label>
                        <input type="text" name="comment"
                            value="{{ old('comment', $billing?->comment) }}"
                            class="form-control"
                            placeholder="{{ __('Billing comment') }}">
                    </div>
                    <div class="flex justify-end pt-2">
                        <button type="submit" class="btn-primary inline-flex items-center gap-2">
                            <iconify-icon icon="lucide:save" width="14"></iconify-icon>
                            {{ __('Save Billing Details') }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- ── TAB 3: Email Settings ────────────────────────────────────────── --}}
    <div x-show="tab==='email-settings'" x-cloak id="email-settings">
        <div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">

            <div class="mb-4">
                <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">
                    {{ __('Email Settings') }}:
                    <small class="font-normal text-gray-400">&nbsp;({{ __('Select a category to manage notification preferences for each status') }})</small>
                </h2>
            </div>

            {{-- ── Category buttons row (exactly like old portal) ── --}}
            <div class="flex flex-wrap gap-2 mb-5" id="email-cat-buttons">
                @foreach($serviceCategories as $cat)
                @if($cat->statuses->where('status', '!=', 'New Order')->isNotEmpty())
                <button type="button"
                    class="email-cat-btn rounded-lg border-2 border-gray-200 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 transition-all hover:border-brand-500 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 dark:hover:border-brand-400 dark:hover:bg-brand-900/20"
                    data-cat-id="{{ $cat->id }}"
                    onclick="showEmailCategory(this)">
                    {{ $cat->name }}
                </button>
                @endif
                @endforeach
            </div>

            {{-- ── Status toggles (hidden until category selected) ── --}}
            <div id="email-status-row" style="display:none">
                <p class="mb-4 text-xs text-gray-500 dark:text-gray-400">
                    <small>{{ __('Toggle each status to enable or disable email notifications for that status change.') }}</small>
                </p>

                <div class="flex flex-wrap gap-4">
                    @foreach($serviceCategories as $cat)
                    @foreach($cat->statuses as $status)
                    @if($status->status !== 'New Order')
                    <div class="email-status-item w-full sm:w-[calc(50%-8px)] lg:w-[calc(25%-12px)]"
                        data-cat-id="{{ $cat->id }}" style="display:none">

                        <label class="flex cursor-pointer items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 transition hover:bg-white dark:border-gray-700 dark:bg-gray-700/40 dark:hover:bg-gray-700">
                            <span class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 min-w-0 flex-1 pr-3">
                                <span class="h-2 w-2 shrink-0 rounded-full" style="background-color:{{ $status->color ?? '#94a3b8' }}"></span>
                                <span class="truncate">{{ $status->status }}</span>
                            </span>

                            {{-- iOS-style toggle switch (same as old portal .checkbox-wrapper-51) --}}
                            <span class="email-toggle-wrap shrink-0">
                                <input type="checkbox"
                                    id="cbx-status-{{ $status->id }}"
                                    class="email-toggle-input"
                                    {{ in_array($status->id, $allowedStatusIds) ? 'checked' : '' }}
                                    onchange="allowEmailAjax(this, {{ $status->id }})">
                                <label for="cbx-status-{{ $status->id }}" class="email-toggle-label">
                                    <span class="email-toggle-knob"></span>
                                </label>
                            </span>
                        </label>
                    </div>
                    @endif
                    @endforeach
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('styles')
<style>
/* ── iOS-style toggle switch (same as old portal .checkbox-wrapper-51) ── */
.email-toggle-wrap { display: inline-flex; align-items: center; }

.email-toggle-input {
    visibility: hidden;
    display: none;
}

.email-toggle-label {
    position: relative;
    display: block;
    width: 42px;
    height: 24px;
    cursor: pointer;
    -webkit-tap-highlight-color: transparent;
    transform: translate3d(0, 0, 0);
}

.email-toggle-label::before {
    content: "";
    position: relative;
    top: 1px;
    left: 1px;
    width: 40px;
    height: 22px;
    display: block;
    background: #c8ccd4;
    border-radius: 12px;
    transition: background 0.2s ease;
}

.email-toggle-knob {
    position: absolute;
    top: 0;
    left: 0;
    width: 24px;
    height: 24px;
    display: block;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 2px 6px rgba(154, 153, 153, 0.75);
    transition: all 0.2s ease;
}

.email-toggle-input:checked + .email-toggle-label::before {
    background: #8b2b2d;
}

.email-toggle-input:checked + .email-toggle-label .email-toggle-knob {
    transform: translateX(18px);
    box-shadow: 0 2px 6px rgba(139, 43, 45, 0.4);
}

/* Active category button */
.email-cat-btn.active {
    border-color: #8b2b2d;
    background-color: #8b2b2d;
    color: #fff;
}
</style>
@endpush

@push('scripts')
<script>
// ── Password strength checker ────────────────────────────────────────────
function checkPasswordStrength(val) {
    const bar  = document.getElementById('pwd-strength-bar');
    const text = document.getElementById('pwd-strength-text');
    if (!bar || !val) return;

    let score = 0;
    const checks = [
        val.length >= 14,
        /[a-z]/.test(val),
        /[A-Z]/.test(val),
        /\d/.test(val),
        /[^A-Za-z0-9]/.test(val),
    ];
    score = checks.filter(Boolean).length;

    const levels = [
        { w: '0%',   color: 'transparent', label: '' },
        { w: '20%',  color: '#ef4444',     label: '{{ __('Very Weak') }}' },
        { w: '40%',  color: '#f97316',     label: '{{ __('Weak') }}' },
        { w: '60%',  color: '#eab308',     label: '{{ __('Fair') }}' },
        { w: '80%',  color: '#22c55e',     label: '{{ __('Good') }}' },
        { w: '100%', color: '#16a34a',     label: '{{ __('Strong') }}' },
    ];

    bar.style.width           = levels[score].w;
    bar.style.backgroundColor = levels[score].color;
    text.textContent          = levels[score].label;
    text.style.color          = levels[score].color;
}

// ── Email settings: category button click ───────────────────────────────
function showEmailCategory(btn) {
    const catId = btn.dataset.catId;

    // Active state on buttons
    document.querySelectorAll('.email-cat-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');

    // Show status row
    document.getElementById('email-status-row').style.display = 'block';

    // Show only items for this category
    document.querySelectorAll('.email-status-item').forEach(el => {
        el.style.display = el.dataset.catId === catId ? '' : 'none';
    });
}

// ── Email settings: AJAX toggle ──────────────────────────────────────────
const CSRF = document.querySelector('meta[name=csrf-token]').content;

function allowEmailAjax(checkbox, statusId) {
    const checked = checkbox.checked ? 1 : 2;
    fetch('{{ route('customer.account.toggle-email-status') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': CSRF,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ status_id: statusId, checked: checked }),
    });
}
</script>
@endpush
