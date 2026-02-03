@php
    use App\Support\Facades\Hook;
    use App\Enums\Hooks\RoleFilterHook;
@endphp

<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    {!! Hook::applyFilters(RoleFilterHook::ROLE_EDIT_BEFORE_FORM, '') !!}

    <form
        action="{{ route('admin.staff-category.update', $role->id) }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf
        @method('PUT')
        @include('backend.pages.roles.partials.form', ['role' => $role])
    </form>

    {!! Hook::applyFilters(RoleFilterHook::ROLE_EDIT_AFTER_FORM, '') !!}
</x-layouts.backend-layout>

