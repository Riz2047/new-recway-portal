<x-card>
    <x-slot name="header">
        {{ $serviceCategory ? __('Edit Service') : __('Add New Service') }}
    </x-slot>

    <div class="space-y-6">
        <div class="space-y-1">
            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Name') }}
                <span class="text-red-500">*</span>
            </label>
            <input 
                type="text" 
                name="name" 
                id="name" 
                required 
                value="{{ old('name', $serviceCategory ? $serviceCategory->name : '') }}"
                class="form-control"
                placeholder="{{ __('Enter service name') }}"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="space-y-1">
            <label for="name_sv" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Name (Swedish)') }}
            </label>
            <input 
                type="text" 
                name="name_sv" 
                id="name_sv" 
                value="{{ old('name_sv', $serviceCategory ? $serviceCategory->name_sv : '') }}"
                class="form-control"
                placeholder="{{ __('Enter service name in Swedish') }}"
            >
            @error('name_sv')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <x-slot name="footer">
        <x-buttons.submit-buttons cancelUrl="{{ $serviceCategory ? route('admin.service-category.index') : '' }}" />
    </x-slot>
</x-card>

