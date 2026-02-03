<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-card>
        <form
            action="{{ route('admin.staff.update', $user->id) }}"
            method="POST"
            class="space-y-6"
            enctype="multipart/form-data"
            data-prevent-unsaved-changes
        >
            @csrf
            @method('PUT')

            @php
                // Load user metadata for additional information
                $userMeta = $user->userMeta()->pluck('meta_value', 'meta_key')->toArray();

                // Load localization data
                $locales = app(\App\Services\LanguageService::class)->getLanguages();
                $timezones = app(\App\Services\TimezoneService::class)->getTimezones();
            @endphp

            {{-- Last Login Display --}}
            @php
                $lastLogin = $userMeta['last_login'] ?? null;
            @endphp
            @if($lastLogin)
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <label class="form-label text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Last Login') }}
                    </label>
                    <p class="text-gray-900 dark:text-white font-semibold">
                        {{ \Carbon\Carbon::parse($lastLogin)->format('Y-m-d H:i:s') }}
                    </p>
                </div>
            @else
                <div class="mb-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <label class="form-label text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Last Login') }}
                    </label>
                    <p class="text-gray-900 dark:text-white font-semibold">
                        {{ __('Never') }}
                    </p>
                </div>
            @endif

            @include('backend.pages.users.partials.form', [
                'user' => $user,
                'roles' => $roles,
                'timezones' => $timezones,
                'locales' => $locales,
                'userMeta' => $userMeta,
                'mode' => 'edit',
                'showUsername' => true,
                'showRoles' => true,
                'showAdditional' => true,
                'showParentStaff' => true,
                'allStaff' => $allStaff ?? [],
                'parentStaffIds' => $parentStaffIds ?? [],
            ])
        </form>
    </x-card>
</x-layouts.backend-layout>


