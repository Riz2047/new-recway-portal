<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route($routePrefix . '.email-templates.store') }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf

        @include('backend.pages.email-templates.partials.form', [
            'template' => null,
            'routePrefix' => $routePrefix,
        ])
    </form>
</x-layouts.backend-layout>
