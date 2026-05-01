<x-layouts.backend-layout :breadcrumbs="$breadcrumbs ?? []">
    <div class="space-y-6">
        <div>
            <h2 class="text-xl font-semibold text-gray-900">{{ __('Reports') }}</h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('Manage default Background Check report templates for Swedish and English.') }}
            </p>
        </div>

        <livewire:backend.reports.templates />
    </div>
</x-layouts.backend-layout>
