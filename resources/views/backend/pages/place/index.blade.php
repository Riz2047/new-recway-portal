<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-slot name="breadcrumbsData">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs">
        </x-breadcrumbs>
    </x-slot>
    <div class="mb-6 hidden">
        <!-- Blue gradient header bar -->
        <div x-data="{ dropdownOpen: false }" class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg px-6 py-4 flex items-center justify-between mb-4 relative">
            <h2 class="text-xl font-semibold text-white">
                {{ __('Places') }}
            </h2>
            <div class="flex items-center gap-2">
                @can('place.create')
                    <a href="{{ route('admin.place.create') }}" class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors" title="{{ __('New Place') }}">
                        <iconify-icon icon="lucide:map-pin" class="w-5 h-5"></iconify-icon>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    @livewire('datatable.place-datatable', ['lazy' => true], key('place-datatable'))
</x-layouts.backend-layout>

