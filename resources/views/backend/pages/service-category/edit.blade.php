<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.service-category.update', $serviceCategory->id) }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf
        @method('PUT')

        @include('backend.pages.service-category.partials.form', [
            'serviceCategory' => $serviceCategory,
        ])
    </form>
</x-layouts.backend-layout>

