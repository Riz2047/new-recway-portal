<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    @livewire('datatable.status-datatable', [
        'serviceCategoryId' => $serviceCategory->id,
        'lazy' => true,
    ], key('status-datatable-' . $serviceCategory->id))
</x-layouts.backend-layout>

