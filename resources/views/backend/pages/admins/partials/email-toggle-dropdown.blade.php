@php
    /** @var \App\Livewire\Datatable\AdminDatatable $this */
@endphp

<div class="flex items-center justify-center relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        class="btn-default flex items-center justify-center gap-2 text-white bg-white/20 hover:bg-white/30"
        type="button"
    >
        <iconify-icon icon="lucide:chevron-down" class="transition-transform duration-200" :class="{'rotate-180': open}"></iconify-icon>
    </button>

    <div
        x-show="open"
        @click.outside="open = false"
        x-transition
        class="absolute top-10 right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 z-50 p-3 border border-gray-200 dark:border-gray-600"
    >
        <ul class="space-y-2">
            <li class="flex items-center gap-2 cursor-pointer text-sm text-gray-700 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 px-2 py-1.5 rounded transition-colors"
                wire:click="$toggle('showEmail')"
                @click="open = false"
            >
                <input
                    type="checkbox"
                    wire:model="showEmail"
                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                >
                <label class="cursor-pointer select-none">{{ __('Email') }}</label>
            </li>
        </ul>
    </div>
</div>

