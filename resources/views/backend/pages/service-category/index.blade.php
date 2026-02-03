<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-slot name="breadcrumbsData">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs">
        </x-breadcrumbs>
    </x-slot>
    <div class="mb-6 hidden">
        <!-- Blue gradient header bar -->
        <div x-data="{ dropdownOpen: false }" class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg px-6 py-4 flex items-center justify-between mb-4 relative">
            <h2 class="text-xl font-semibold text-white">
                {{ __('Services') }}
            </h2>
            <div class="flex items-center gap-2">
                @can('service-category.create')
                    <a href="{{ route('admin.service-category.create') }}" class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors" title="{{ __('New Service') }}">
                        <iconify-icon icon="lucide:plus" class="w-5 h-5"></iconify-icon>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @livewire('datatable.service-category-datatable', ['lazy' => true], key('service-category-datatable'))
</x-layouts.backend-layout>

