@props([
    'template' => null,
    'routePrefix' => 'admin',
])

@php
    $isEdit = $template !== null;
@endphp

<x-card>
    <x-slot name="header">
        {{ $isEdit ? __('Edit Email Template') : __('New Email Template') }}
    </x-slot>

    <div
        class="space-y-6"
        x-data="{ title: @js(old('title', $template?->title ?? '')) }"
    >
        <div class="space-y-1">
            <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Title') }}
                <span class="text-red-500">*</span>
            </label>
            <input
                type="text"
                name="title"
                id="title"
                required
                x-model="title"
                class="form-control"
                placeholder="{{ __('Template title') }}"
            >
            @error('title')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                {{ __('Variable (saved key)') }}:
                <code class="ql-editor rounded bg-gray-100 px-1 py-0.5 text-gray-800 dark:bg-gray-800 dark:text-gray-200" x-text="(title || '').trim() === '' ? '—' : (title || '').trim().replace(/\s+/g, '_')"></code>
            </p>
        </div>

        <div class="space-y-1">
            <span class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                {{ __('Body') }}
            </span>
            <textarea
                id="body"
                name="body"
                class="sr-only"
                rows="6"
            >{{ old('body', $template?->body ?? '') }}</textarea>
            <x-quill-editor editor-id="body" height="280px" maxHeight="600px" type="full" />
            @error('body')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <x-slot name="footer">
        <x-buttons.submit-buttons
            cancelUrl="{{ route($routePrefix . '.email-templates.index') }}"
        />
    </x-slot>
</x-card>
