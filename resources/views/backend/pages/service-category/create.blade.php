<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.service-category.store') }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf

        @include('backend.pages.service-category.partials.form', [
            'serviceCategory' => null,
        ])
    </form>
</x-layouts.backend-layout>

