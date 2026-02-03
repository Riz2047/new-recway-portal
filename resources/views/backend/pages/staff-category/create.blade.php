@php
    use App\Support\Facades\Hook;
    use App\Enums\Hooks\RoleFilterHook;
@endphp

<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    {!! Hook::applyFilters(RoleFilterHook::ROLE_CREATE_BEFORE_FORM, '') !!}

    <form
        action="{{ route('admin.staff-category.store') }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf
        @include('backend.pages.roles.partials.form', ['role' => null])
    </form>

    {!! Hook::applyFilters(RoleFilterHook::ROLE_CREATE_AFTER_FORM, '') !!}
</x-layouts.backend-layout>

