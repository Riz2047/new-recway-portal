@extends('customer.layouts.app')

@section('title', __('Edit Reviewer') . ' | ' . config('app.name'))
@section('page-title', __('Edit Reviewer'))

@section('content')

<nav class="mb-4 flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
    <a href="{{ route('customer.reviewers.index') }}" class="hover:text-brand-600 dark:hover:text-brand-400">{{ __('Reviewers') }}</a>
    <iconify-icon icon="lucide:chevron-right" width="14"></iconify-icon>
    <span class="font-medium text-gray-800 dark:text-white">{{ __('Edit') }}</span>
</nav>

<div class="mx-auto max-w-md">
<div class="rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
    <h2 class="mb-5 text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Edit Reviewer') }}</h2>

    @if($errors->any())
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
        <ul class="space-y-1">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
    @endif

    <form action="{{ route('customer.reviewers.update', $reviewer->id) }}" method="POST" class="space-y-4">
        @csrf @method('PUT')
        <div>
            <label class="form-label">{{ __('Email') }} <span class="text-red-500">*</span></label>
            <input type="email" name="email" value="{{ old('email', $reviewer->email) }}"
                class="form-control @error('email') border-red-500 @enderror"
                placeholder="{{ __('reviewer@example.com') }}" required autofocus>
        </div>
        <div x-data="{ show: false }">
            <label class="form-label">{{ __('New Password') }}</label>
            <div class="relative">
                <input :type="show ? 'text' : 'password'" name="password"
                    class="form-control pr-10 @error('password') border-red-500 @enderror"
                    placeholder="{{ __('Leave blank to keep current password') }}">
                <button type="button" @click="show=!show"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                    <iconify-icon :icon="show ? 'lucide:eye-off' : 'lucide:eye'" width="16"></iconify-icon>
                </button>
            </div>
            <p class="mt-1 text-xs text-gray-400">{{ __('Min. 6 characters. Leave blank to keep current password.') }}</p>
        </div>
        <div class="flex justify-end gap-3 pt-2">
            <a href="{{ route('customer.reviewers.index') }}"
                class="rounded-lg border border-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                {{ __('Cancel') }}
            </a>
            <button type="submit" class="btn-primary inline-flex items-center gap-2">
                <iconify-icon icon="lucide:save" width="14"></iconify-icon>
                {{ __('Save Changes') }}
            </button>
        </div>
    </form>
</div>
</div>

@endsection
