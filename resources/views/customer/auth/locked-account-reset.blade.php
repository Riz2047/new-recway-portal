@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Account Locked') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    {{-- Icon --}}
    <div class="mb-6 flex items-center justify-center">
        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/40">
            <svg class="h-7 w-7 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
            </svg>
        </div>
    </div>

    <h1 class="mb-2 text-center font-semibold text-gray-800 text-title-sm dark:text-white/90 sm:text-title-md">
        {{ __('Account Locked') }}
    </h1>
    <p class="mb-6 text-center text-sm text-gray-500 dark:text-gray-400">
        {{ __('Your account has been locked after too many failed login attempts. We will send a verification code to your email to unlock it.') }}
    </p>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ session('error') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('customer.locked.send-mfa') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <input type="hidden" name="email" value="{{ $email }}">

        <div class="mb-5 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Recovery code will be sent to:') }}</p>
            <p class="mt-0.5 text-sm font-semibold text-gray-800 dark:text-white">{{ $email }}</p>
        </div>

        <button type="submit" class="btn-primary w-full" :disabled="loading">
            <span x-text="loading ? '' : '{{ __('Send Verification Code') }}'">{{ __('Send Verification Code') }}</span>
            <iconify-icon
                :icon="loading ? 'lucide:loader-circle' : 'lucide:send'"
                :class="{ 'animate-spin': loading, 'ml-2': !loading }">
            </iconify-icon>
        </button>
    </form>

    <div class="mt-5 text-center">
        <a href="{{ route('customer.login') }}"
            class="text-sm text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
            {{ __('← Back to sign in') }}
        </a>
    </div>
</div>
@endsection
