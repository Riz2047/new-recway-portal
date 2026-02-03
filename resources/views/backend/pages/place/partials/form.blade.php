<x-card>
    <x-slot name="header">
        {{ $place ? __('Edit Place') : __('Add New Place') }}
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
                value="{{ old('name', $place ? $place->name : '') }}"
                class="form-control"
                placeholder="{{ __('Enter place name') }}"
            >
            @error('name')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <x-slot name="footer">
        <x-buttons.submit-buttons cancelUrl="{{ $place ? route('admin.place.index') : '' }}" />
    </x-slot>
</x-card>

