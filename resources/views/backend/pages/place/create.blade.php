<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.place.store') }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf

        @include('backend.pages.place.partials.form', [
            'place' => null,
        ])
    </form>
</x-layouts.backend-layout>

