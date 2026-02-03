<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.place.update', $place->id) }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf
        @method('PUT')

        @include('backend.pages.place.partials.form', [
            'place' => $place,
        ])
    </form>
</x-layouts.backend-layout>

