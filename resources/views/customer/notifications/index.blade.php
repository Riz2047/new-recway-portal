@extends('customer.layouts.app')

@section('title', __('Updates & Notifications') . ' | ' . config('app.name'))
@section('page-title', __('Updates & Notifications'))

@section('content')

<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800">

    <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('All Updates') }}</h2>
    </div>

    @forelse($updates as $update)
    <div class="border-b border-gray-100 px-5 py-4 last:border-0 dark:border-gray-700/60 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
        <div class="flex items-start gap-4">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-50 dark:bg-brand-900/20">
                <iconify-icon icon="lucide:bell" width="16" class="text-brand-600 dark:text-brand-400"></iconify-icon>
            </div>
            <div class="flex-1">
                <div class="flex items-center justify-between gap-4">
                    <h3 class="text-sm font-semibold text-gray-800 dark:text-white">
                        {{ $update->title ?: __('Update') }}
                    </h3>
                    <span class="shrink-0 text-xs text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($update->created_at)->format('d M Y') }}
                    </span>
                </div>
                @if($update->content)
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ $update->content }}</p>
                @endif
            </div>
        </div>
    </div>
    @empty
    <div class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
        <iconify-icon icon="lucide:bell-off" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
        {{ __('No updates available.') }}
    </div>
    @endforelse

</div>

@endsection
