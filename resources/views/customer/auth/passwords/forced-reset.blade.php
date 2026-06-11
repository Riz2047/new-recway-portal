@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Set New Password') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    {{-- Icon --}}
    <div class="mb-6 flex items-center justify-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/40">
            <svg class="h-7 w-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
            </svg>
        </div>
    </div>

    <h1 class="mb-2 text-center font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
        {{ __('Set New Password') }}
    </h1>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('Choose a strong password for') }}
        <span class="font-semibold text-gray-700 dark:text-gray-200">{{ $email }}</span>
    </p>

    @if (session('status'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('status') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            <ul class="list-inside list-disc space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Requirements card --}}
    <div class="mb-5 rounded-lg border border-blue-100 bg-blue-50 px-4 py-3 dark:border-blue-900/40 dark:bg-blue-900/20"
         x-data="passwordChecker()">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-blue-700 dark:text-blue-400">
            {{ __('Password requirements') }}
        </p>
        <ul class="space-y-1 text-xs">
            <li class="flex items-center gap-2" :class="checks.length ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <iconify-icon :icon="checks.length ? 'lucide:check-circle' : 'lucide:circle'" width="13"></iconify-icon>
                {{ __('At least 14 characters') }}
            </li>
            <li class="flex items-center gap-2" :class="checks.upper ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <iconify-icon :icon="checks.upper ? 'lucide:check-circle' : 'lucide:circle'" width="13"></iconify-icon>
                {{ __('One uppercase letter (A–Z)') }}
            </li>
            <li class="flex items-center gap-2" :class="checks.lower ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <iconify-icon :icon="checks.lower ? 'lucide:check-circle' : 'lucide:circle'" width="13"></iconify-icon>
                {{ __('One lowercase letter (a–z)') }}
            </li>
            <li class="flex items-center gap-2" :class="checks.digit ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <iconify-icon :icon="checks.digit ? 'lucide:check-circle' : 'lucide:circle'" width="13"></iconify-icon>
                {{ __('One number (0–9)') }}
            </li>
            <li class="flex items-center gap-2" :class="checks.special ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400'">
                <iconify-icon :icon="checks.special ? 'lucide:check-circle' : 'lucide:circle'" width="13"></iconify-icon>
                {{ __('One special character (@$!%*?&#…)') }}
            </li>
        </ul>

        {{-- The form sits inside x-data scope so it can wire up the password field --}}
        <form action="{{ route('customer.locked.new-password.submit') }}" method="POST"
              class="mt-5 space-y-4"
              x-data="{ loading: false, ...passwordChecker() }"
              @submit="loading = true">
            @csrf

            <div>
                <label class="form-label" for="password">{{ __('New Password') }}</label>
                <div class="relative">
                    <input
                        autofocus
                        :type="showPwd ? 'text' : 'password'"
                        id="password"
                        name="password"
                        autocomplete="new-password"
                        placeholder="{{ __('Enter new password') }}"
                        class="form-control pr-10 @error('password') border-red-500 @enderror"
                        x-model="pwd"
                        @input="evaluate()"
                        required
                    />
                    <button type="button" tabindex="-1"
                        @click="showPwd = !showPwd"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                        <iconify-icon :icon="showPwd ? 'lucide:eye-off' : 'lucide:eye'" width="16"></iconify-icon>
                    </button>
                </div>
            </div>

            <div>
                <label class="form-label" for="password_confirmation">{{ __('Confirm Password') }}</label>
                <div class="relative">
                    <input
                        :type="showConfirm ? 'text' : 'password'"
                        id="password_confirmation"
                        name="password_confirmation"
                        autocomplete="new-password"
                        placeholder="{{ __('Repeat new password') }}"
                        class="form-control pr-10"
                        required
                    />
                    <button type="button" tabindex="-1"
                        @click="showConfirm = !showConfirm"
                        class="absolute inset-y-0 right-3 flex items-center text-gray-400 hover:text-gray-600">
                        <iconify-icon :icon="showConfirm ? 'lucide:eye-off' : 'lucide:eye'" width="16"></iconify-icon>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn-primary w-full" :disabled="loading">
                <span x-text="loading ? '' : '{{ __('Reset Password') }}'">{{ __('Reset Password') }}</span>
                <iconify-icon
                    :icon="loading ? 'lucide:loader-circle' : 'lucide:key-round'"
                    :class="{ 'animate-spin': loading, 'ml-2': !loading }">
                </iconify-icon>
            </button>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function passwordChecker() {
    return {
        pwd: '',
        showPwd: false,
        showConfirm: false,
        checks: { length: false, upper: false, lower: false, digit: false, special: false },
        evaluate() {
            const p = this.pwd;
            this.checks.length  = p.length >= 14;
            this.checks.upper   = /[A-Z]/.test(p);
            this.checks.lower   = /[a-z]/.test(p);
            this.checks.digit   = /[0-9]/.test(p);
            this.checks.special = /[@$!%*?&#^_\-+=\[\]{}|;:,.<>\/]/.test(p);
        },
    };
}
</script>
@endpush
