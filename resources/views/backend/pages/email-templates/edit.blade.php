<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route($routePrefix . '.email-templates.update', $template->id) }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf
        @method('PUT')

        @include('backend.pages.email-templates.partials.form', [
            'template' => $template,
            'routePrefix' => $routePrefix,
        ])
    </form>
</x-layouts.backend-layout>
