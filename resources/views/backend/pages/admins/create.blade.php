<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-card>
        <form
            action="{{ route('admin.admins.store') }}"
            method="POST"
            enctype="multipart/form-data"
            data-prevent-unsaved-changes
        >
            @csrf

            @include('backend.pages.users.partials.form', [
                'user' => null,
                'roles' => $roles,
                'timezones' => $timezones ?? [],
                'locales' => $locales ?? [],
                'userMeta' => [],
                'mode' => 'create',
                'showUsername' => true,
                'showRoles' => true, // Show roles field - Admin will be auto-assigned, but user can add other roles
                'showAdditional' => false,
                'showImage' => false,
            ])
        </form>
    </x-card>
</x-layouts.backend-layout>

