@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Forgot Password') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 font-semibold text-gray-700 text-title-sm dark:text-white/90 sm:text-title-md">
            {{ __('Forgot Password?') }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-300">
            {{ __("Enter your email and we'll send you a password reset link.") }}
        </p>
    </div>

    @if (session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif
    @if ($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
            {{ $errors->first() }}
        </div>
    @endif

    <form action="{{ route('customer.password.email') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        <div class="space-y-5">
            <div>
                <label class="form-label" for="email">{{ __('Email Address') }}</label>
                <input
                    autofocus
                    type="email"
                    id="email"
                    name="email"
                    placeholder="{{ __('Enter your email') }}"
                    class="form-control @error('email') border-red-500 @enderror"
                    value="{{ old('email') }}"
                    required
                />
            </div>

            <div>
                <button type="submit" class="btn-primary w-full" :disabled="loading">
                    <span x-text="loading ? '' : '{{ __('Send Reset Link') }}'">{{ __('Send Reset Link') }}</span>
                    <iconify-icon :icon="loading ? 'lucide:loader-circle' : 'lucide:mail'" :class="{ 'animate-spin': loading, 'ml-2': !loading }"></iconify-icon>
                </button>
            </div>

            <div class="text-center">
                <a href="{{ route('customer.login') }}" class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400">
                    {{ __('← Back to login') }}
                </a>
            </div>
        </div>
    </form>
</div>
@endsection
