@extends('customer.layouts.app')

@section('title', $title . ' | ' . config('app.name'))
@section('page-title', $title)

@section('content')
<div class="flex flex-col items-center justify-center py-24 text-center">
    <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-900/20">
        <iconify-icon icon="lucide:hammer" width="28" class="text-brand-600 dark:text-brand-400"></iconify-icon>
    </div>
    <h2 class="mb-2 text-xl font-semibold text-gray-800 dark:text-white">{{ $title }}</h2>
    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('This section is coming soon.') }}</p>
    <a href="{{ route('customer.dashboard') }}"
        class="mt-6 inline-flex items-center gap-2 rounded-lg bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700">
        <iconify-icon icon="lucide:arrow-left" width="14"></iconify-icon>
        {{ __('Back to Dashboard') }}
    </a>
</div>
@endsection
