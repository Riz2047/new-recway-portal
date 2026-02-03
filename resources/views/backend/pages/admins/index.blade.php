<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <x-slot name="breadcrumbsData">
        <x-breadcrumbs :breadcrumbs="$breadcrumbs">
            <x-slot name="title_after">
                @if(request('admin'))
                    <span class="badge">{{ ucfirst(request('admin')) }}</span>
                @endif
            </x-slot>
        </x-breadcrumbs>
    </x-slot>

    {!! Hook::applyFilters(UserFilterHook::USER_AFTER_BREADCRUMBS, '') !!}
        <div class="mb-6 hidden">
            <!-- Blue gradient header bar -->
            <div x-data="{ dropdownOpen: false }" class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg px-6 py-4 flex items-center justify-between mb-4 relative">
                <h2 class="text-xl font-semibold text-white">
                    {{ __('Admins') }}
                </h2>
                <div class="flex items-center gap-2">
                    @can('user.create')
                        <a href="{{ route('admin.admins.create') }}" class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors" title="{{ __('New Admin') }}">
                            <iconify-icon icon="lucide:user-plus" class="w-5 h-5"></iconify-icon>
                        </a>
                    @endcan
                    <button
                        @click="dropdownOpen = !dropdownOpen"
                        class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors"
                        type="button"
                        title="{{ __('Options') }}"
                    >
                        <iconify-icon icon="lucide:chevron-down" class="w-5 h-5 transition-transform duration-200" :class="{'rotate-180': dropdownOpen}"></iconify-icon>
                    </button>
                </div>
                <!-- Full width dropdown -->
                <div
                    x-show="dropdownOpen"
                    @click.outside="dropdownOpen = false"
                    x-transition
                    class="absolute top-full left-0 right-0 mt-2 rounded-md shadow-lg bg-white dark:bg-gray-700 z-50 p-3 border border-gray-200 dark:border-gray-600"
                >
                    <ul class="space-y-2">
                        <li class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 px-2 py-1.5 rounded transition-colors"
                            x-data="{ 
                                emailChecked: false,
                                init() {
                                    window.addEventListener('email-column-updated', (e) => {
                                        this.emailChecked = e.detail.showEmail;
                                    });
                                    this.updateCheckboxState();
                                },
                                updateCheckboxState() {
                                    const component = document.querySelector('[wire\\:id]');
                                    if (component && component.__livewire) {
                                        this.emailChecked = component.__livewire.$wire.showEmail;
                                    }
                                },
                                toggleEmail() {
                                    this.emailChecked = !this.emailChecked;
                                    dropdownOpen = false;
                                    const component = document.querySelector('[wire\\:id]');
                                    if (component && component.__livewire) {
                                        component.__livewire.$wire.call('toggleEmailColumn');
                                    } else {
                                        // Fallback: find by wire:id
                                        const wireId = component?.getAttribute('wire:id');
                                        if (wireId && window.Livewire) {
                                            const livewireComponent = window.Livewire.find(wireId);
                                            if (livewireComponent) {
                                                livewireComponent.call('toggleEmailColumn');
                                            }
                                        }
                                    }
                                }
                            }"
                            @click="toggleEmail()"
                        >
                            <input
                                type="checkbox"
                                x-model="emailChecked"
                                class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                            >
                            <label class="cursor-pointer select-none">{{ __('Email') }}</label>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    @livewire('datatable.admin-datatable', ['lazy' => true], key('admin-datatable'))
</x-layouts.backend-layout>

