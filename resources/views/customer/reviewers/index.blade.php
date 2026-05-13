@extends('customer.layouts.app')

@section('title', __('Reviewers') . ' | ' . config('app.name'))
@section('page-title', __('Reviewers'))

@section('content')

@if(session('success'))
<div x-data="{show:true}" x-show="show" x-init="setTimeout(()=>show=false,4000)"
    class="mb-4 flex items-center gap-2 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
    <iconify-icon icon="lucide:check-circle" width="16" class="shrink-0"></iconify-icon>
    {{ session('success') }}
</div>
@endif

<div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800"
    x-data="customerTable('reviewers-tbody', { sort:'email', perPage:25 })">

    <div class="flex items-center justify-between border-b border-gray-200 px-5 py-4 dark:border-gray-700">
        <h2 class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ __('Reviewers') }}</h2>
        <a href="{{ route('customer.reviewers.create') }}"
            class="btn-primary inline-flex items-center gap-2 text-sm">
            <iconify-icon icon="lucide:plus" width="14"></iconify-icon>
            {{ __('Add Reviewer') }}
        </a>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wide text-gray-500 dark:bg-gray-700/40 dark:text-gray-400">
                <tr>
                    <th class="px-5 py-3 w-12">#</th>
                    <th class="px-5 py-3 dt-th" @click="sortBy('email')" :class="thClass('email')">
                        {{ __('Email') }}<span x-html="sortIcon('email')"></span>
                    </th>
                    <th class="px-5 py-3 text-right">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody id="reviewers-tbody" class="divide-y divide-gray-100 dark:divide-gray-700">
                @forelse($reviewers as $i => $reviewer)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                    data-row
                    data-sort-email="{{ $reviewer->email }}">
                    <td class="px-5 py-3 text-gray-400 dark:text-gray-500 dt-num">{{ $i + 1 }}</td>
                    <td class="px-5 py-3 font-medium text-gray-800 dark:text-white">{{ $reviewer->email }}</td>
                    <td class="px-5 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <a href="{{ route('customer.reviewers.edit', $reviewer->id) }}"
                                class="inline-flex items-center gap-1 rounded-lg border border-gray-200 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                <iconify-icon icon="lucide:pencil" width="12"></iconify-icon>
                                {{ __('Edit') }}
                            </a>
                            <form action="{{ route('customer.reviewers.destroy', $reviewer->id) }}" method="POST"
                                onsubmit="return confirm('{{ __('Remove this reviewer?') }}')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-900/20">
                                    <iconify-icon icon="lucide:trash-2" width="12"></iconify-icon>
                                    {{ __('Remove') }}
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-5 py-12 text-center text-sm text-gray-400 dark:text-gray-500">
                        <iconify-icon icon="lucide:users" width="36" class="mx-auto mb-2 block opacity-40"></iconify-icon>
                        {{ __('No reviewers added yet.') }}
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @include('customer.partials.dt-footer')
</div>

@endsection
