<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    @livewire('datatable.candidate-datatable', [
        'lazy' => true,
        'panelPrefix' => request()->routeIs('staff.*') ? 'staff' : 'admin',
    ], key('candidate-datatable'))
</x-layouts.backend-layout>
