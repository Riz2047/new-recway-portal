@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Two-Factor Verification') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    <div class="mb-6 sm:mb-8">
        <div class="mb-4 flex items-center justify-center">
            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-100 dark:bg-indigo-900/40">
                <svg class="h-7 w-7 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                </svg>
            </div>
        </div>
        <h1 class="mb-2 text-center font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
            {{ __('Two-Factor Verification') }}
        </h1>
        <p class="text-center text-sm text-gray-500 dark:text-gray-400">
            {{ __('We sent a 6-character code to') }}
            <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $maskedEmail }}</span>
        </p>
    </div>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->has('otp'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ $errors->first('otp') }}
        </div>
    @endif

    <form
        method="POST"
        action="{{ route('customer.otp.verify') }}"
        x-data="otpForm()"
        @submit="submitting = true"
        class="space-y-5"
    >
        @csrf

        <div>
            <label class="form-label mb-2 block text-center text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Enter Verification Code') }}
            </label>

            <div class="flex items-center justify-center gap-2 sm:gap-3" x-data>
                @for ($i = 0; $i < 6; $i++)
                    <input
                        type="text"
                        inputmode="text"
                        maxlength="1"
                        autocomplete="one-time-code"
                        class="otp-box h-12 w-10 rounded-lg border-2 border-gray-300 bg-white text-center text-xl font-bold uppercase
                               text-gray-900 transition focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500/30
                               dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:focus:border-indigo-400 sm:h-14 sm:w-12"
                        @keydown="onKey($event)"
                        @paste="onPaste($event)"
                        @input="onInput($event)"
                    />
                @endfor
            </div>

            <input type="hidden" name="otp" x-model="code" />
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="submitting || code.length < 6">
            <span x-text="submitting ? '' : '{{ __('Verify') }}'">{{ __('Verify') }}</span>
            <iconify-icon
                :icon="submitting ? 'lucide:loader-circle' : 'lucide:shield-check'"
                :class="{ 'animate-spin': submitting, 'ml-2': !submitting }"
            ></iconify-icon>
        </button>
    </form>

    <div class="my-5 flex items-center gap-3">
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
        <span class="text-xs text-gray-400">{{ __('or') }}</span>
        <div class="h-px flex-1 bg-gray-200 dark:bg-gray-700"></div>
    </div>

    <div class="space-y-3 text-center">
        <div x-data="resendTimer({{ $cooldownRemaining }})">
            <template x-if="remaining > 0">
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('Resend code in') }}
                    <span class="font-semibold tabular-nums" x-text="remaining + 's'"></span>
                </p>
            </template>
            <template x-if="remaining === 0">
                <form method="POST" action="{{ route('customer.otp.resend') }}">
                    @csrf
                    <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline dark:text-indigo-400">
                        {{ __('Resend verification code') }}
                    </button>
                </form>
            </template>
        </div>

        <a href="{{ route('customer.otp.cancel') }}"
            class="block text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            {{ __('← Back to login') }}
        </a>
    </div>

    <p class="mt-6 text-center text-xs text-gray-400 dark:text-gray-500">
        {{ __("The code expires in 24 hours. Check your spam folder if you don't see it.") }}
    </p>
</div>
@endsection

@push('scripts')
<script>
function otpForm() {
    return {
        code: '',
        submitting: false,

        boxes() { return document.querySelectorAll('.otp-box'); },

        onInput(e) {
            const val = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, '');
            e.target.value = val ? val[0] : '';
            this.syncCode();
            if (val && e.target.nextElementSibling?.classList.contains('otp-box')) {
                e.target.nextElementSibling.focus();
            }
        },

        onKey(e) {
            if (e.key === 'Backspace' && !e.target.value && e.target.previousElementSibling?.classList.contains('otp-box')) {
                e.target.previousElementSibling.focus();
                e.target.previousElementSibling.value = '';
                this.syncCode();
            }
            if (e.key === 'ArrowLeft' && e.target.previousElementSibling?.classList.contains('otp-box')) e.target.previousElementSibling.focus();
            if (e.key === 'ArrowRight' && e.target.nextElementSibling?.classList.contains('otp-box')) e.target.nextElementSibling.focus();
        },

        onPaste(e) {
            e.preventDefault();
            const pasted = (e.clipboardData || window.clipboardData).getData('text')
                .toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 6);
            const boxes = this.boxes();
            [...pasted].forEach((char, i) => { if (boxes[i]) boxes[i].value = char; });
            boxes[Math.min(pasted.length, boxes.length - 1)]?.focus();
            this.syncCode();
            if (pasted.length >= 6) this.$nextTick(() => { if (this.code.length === 6) this.$el.submit(); });
        },

        syncCode() { this.code = [...this.boxes()].map(b => b.value).join(''); },
    };
}

function resendTimer(initial) {
    return {
        remaining: initial,
        init() {
            if (this.remaining > 0) {
                const interval = setInterval(() => {
                    this.remaining = Math.max(0, this.remaining - 1);
                    if (this.remaining === 0) clearInterval(interval);
                }, 1000);
            }
        },
    };
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelector('.otp-box')?.focus();
});
</script>
@endpush
