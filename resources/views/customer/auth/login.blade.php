@extends('customer.auth.layouts.app')

@section('title')
    {{ __('Customer Sign In') }} | {{ config('app.name') }}
@endsection

@section('content')
<div>
    <div class="mb-5 sm:mb-8">
        <h1 class="mb-2 font-semibold text-gray-700 text-title-sm dark:text-white/90 sm:text-title-md">
            {{ __('Customer Portal') }}
        </h1>
        <p class="text-sm text-gray-500 dark:text-gray-300">
            {{ __('Enter your email and password to sign in') }}
        </p>
    </div>

    <div>
        <form action="{{ route('customer.login.submit') }}" method="POST" x-data="{ loading: false }" @submit="loading = true">
            @csrf
            <div class="space-y-5">

                {{-- Status / success messages --}}
                @if (session('status'))
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                        {{ session('status') }}
                    </div>
                @endif
                @if (session('success'))
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                        {{ session('success') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                        {{ $errors->first() }}
                    </div>
                @endif

                <div>
                    <label class="form-label" for="email">{{ __('Email or Username') }}</label>
                    <input
                        autofocus
                        type="text"
                        id="email"
                        name="email"
                        autocomplete="username"
                        placeholder="{{ __('Enter your email') }}"
                        class="form-control @error('email') border-red-500 @enderror"
                        value="{{ old('email') }}"
                        required
                    />
                </div>

                <x-inputs.password
                    name="password"
                    label="{{ __('Password') }}"
                    placeholder="{{ __('Enter your password') }}"
                    required
                />

                <div class="flex items-center justify-between">
                    <label for="remember" class="flex items-center justify-center gap-2 text-sm font-medium has-checked:text-gray-900 dark:has-checked:text-white">
                        <span class="relative flex items-center">
                            <input id="remember" name="remember" type="checkbox"
                                class="before:content[''] peer relative size-4 appearance-none overflow-hidden rounded-sm border border-outline bg-surface-alt before:absolute before:inset-0 checked:border-primary checked:before:bg-primary focus:outline-2 focus:outline-offset-2 focus:outline-outline-strong checked:focus:outline-primary active:outline-offset-0 dark:border-outline-dark dark:bg-surface-dark-alt dark:checked:border-primary-dark dark:checked:before:bg-primary-dark" />
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" aria-hidden="true" stroke="currentColor" fill="none" stroke-width="4"
                                class="pointer-events-none invisible absolute left-1/2 top-1/2 size-3 -translate-x-1/2 -translate-y-1/2 text-white peer-checked:visible">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                            </svg>
                        </span>
                        <span class="form-label mb-0">{{ __('Remember me') }}</span>
                    </label>
                    <a href="{{ route('customer.password.request') }}" class="text-sm text-brand-500 hover:text-brand-600 dark:text-brand-400">
                        {{ __('Forgot password?') }}
                    </a>
                </div>

                <div>
                    <button type="submit" class="btn-primary w-full" :disabled="loading">
                        <span x-text="loading ? '' : '{{ __('Sign In') }}'">{{ __('Sign In') }}</span>
                        <iconify-icon :icon="loading ? 'lucide:loader-circle' : 'lucide:log-in'" :class="{ 'animate-spin': loading, 'ml-2': !loading }"></iconify-icon>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
