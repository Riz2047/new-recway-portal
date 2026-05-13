@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Reset Password') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 font-semibold text-gray-700 text-title-sm dark:text-white/90 sm:text-title-md">
            {{ __('Reset Password') }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-300">
            {{ __('Enter your new password below.') }}
        </p>
    </div>

    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('customer.password.reset.submit') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div class="space-y-5">
            <div>
                <label class="form-label" for="email">{{ __('Email Address') }}</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control @error('email') border-red-500 @enderror"
                    value="{{ old('email', $email) }}"
                    required
                />
            </div>

            <x-inputs.password
                name="password"
                label="{{ __('New Password') }}"
                placeholder="{{ __('Enter new password') }}"
                required
            />

            <x-inputs.password
                name="password_confirmation"
                label="{{ __('Confirm New Password') }}"
                placeholder="{{ __('Confirm new password') }}"
                required
            />

            <div>
                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-text="loading ? '' : '{{ __('Reset Password') }}'">{{ __('Reset Password') }}</span>
                    <iconify-icon :icon="loading ? 'lucide:loader-circle' : 'lucide:lock'" :class="{ 'animate-spin': loading, 'ml-2': !loading }"></iconify-icon>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
