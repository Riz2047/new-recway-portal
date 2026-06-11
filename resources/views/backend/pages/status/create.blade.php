<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.status.store', $serviceCategory->id) }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf

        <x-card>
            <x-slot name="header">
                {{ __('Add New Status') }} - {{ $serviceCategory->name }}
            </x-slot>

            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Status') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="status" 
                            id="status" 
                            required 
                            value="{{ old('status') }}"
                            class="form-control"
                            placeholder="{{ __('Enter status name') }}"
                        >
                        @error('status')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="status_sv" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Status (Swedish)') }}
                        </label>
                        <input 
                            type="text" 
                            name="status_sv" 
                            id="status_sv" 
                            value="{{ old('status_sv') }}"
                            class="form-control"
                            placeholder="{{ __('Enter status name in Swedish') }}"
                        >
                        @error('status_sv')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="variable" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Variable') }}
                            <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text" 
                            name="variable" 
                            id="variable" 
                            required 
                            value="{{ old('variable') }}"
                            class="form-control"
                            placeholder="{{ __('e.g., pending, completed') }}"
                            pattern="[a-zA-Z][a-zA-Z0-9_]*"
                            title="{{ __('Must start with a letter and contain only letters, numbers, and underscores') }}"
                        >
                        <p class="mt-1 text-xs text-gray-500">{{ __('Must start with a letter, only letters, numbers, and underscores allowed') }}</p>
                        @error('variable')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="status_detail" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Status Detail') }}
                        </label>
                        <input 
                            type="text" 
                            name="status_detail" 
                            id="status_detail" 
                            value="{{ old('status_detail') }}"
                            class="form-control"
                            placeholder="{{ __('Enter status detail') }}"
                        >
                        @error('status_detail')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1">
                        <label for="color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Color') }}
                        </label>
                        <input 
                            type="color" 
                            name="color" 
                            id="color" 
                            value="{{ old('color', '#3b82f6') }}"
                            class="h-10 w-full rounded border border-gray-300 dark:border-gray-600"
                        >
                        @error('color')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="space-y-1 relative">
                        <label for="status_icon" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            {{ __('Icon') }}
                        </label>
                        <div class="relative">
                            <input 
                                type="text" 
                                name="status_icon" 
                                id="status_icon" 
                                value="{{ old('status_icon') }}"
                                class="form-control iconpicker"
                                autocomplete="off"
                                placeholder="{{ __('Click to select an icon') }}"
                                aria-label="Icon Picker"
                                readonly
                            >
                        </div>
                        <p class="mt-1 text-xs text-gray-500">{{ __('Click the input field to browse and select an icon') }}</p>
                        @error('status_icon')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                @if($serviceTypes->count() > 0)
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            {{ __('Services') }}
                        </label>
                        <div class="space-y-2 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 rounded p-4">
                            @foreach($serviceTypes as $serviceType)
                                <div class="flex items-center">
                                    <input
                                        type="checkbox"
                                        name="services[]"
                                        id="service_{{ $serviceType->id }}"
                                        value="{{ $serviceType->id }}"
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        checked
                                    >
                                    <label for="service_{{ $serviceType->id }}" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $serviceType->name ?? "Service #{$serviceType->id}" }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="space-y-1">
                    <label for="msg_col" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        {{ __('Message Column') }}
                    </label>
                    <input
                        type="text"
                        name="msg_col"
                        id="msg_col"
                        value="{{ old('msg_col') }}"
                        class="form-control"
                        placeholder="{{ __('e.g., approved_msg') }}"
                        pattern="[a-zA-Z][a-zA-Z0-9_]*"
                        title="{{ __('Must start with a letter, only letters, numbers, and underscores allowed') }}"
                    >
                    <p class="mt-1 text-xs text-gray-500">
                        {{ __('Key used in the templates JSON. Must start with a letter (e.g. approved_msg, pending_msg).') }}
                    </p>
                    @error('msg_col')
                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                    @enderror
                </div>

            </div>

            <x-slot name="footer">
                <x-buttons.submit-buttons cancelUrl="{{ route('admin.status.index', $serviceCategory->id) }}" />
            </x-slot>
        </x-card>
    </form>

    @push('styles')
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
        <style>
            /* Icon Picker Styles */
            .iconpicker-popover {
                position: absolute;
                top: calc(100% + 5px);
                left: 0;
                z-index: 1000;
                display: none;
                min-width: 300px;
                max-width: 400px;
                padding: 15px;
                background-color: #fff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 10px 25px rgba(0,0,0,0.15);
            }
            .dark .iconpicker-popover {
                background-color: #1f2937;
                border-color: #374151;
            }
            .iconpicker-popover.show {
                display: block;
            }
            .iconpicker-icons {
                max-height: 300px;
                overflow-y: auto;
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(45px, 1fr));
                gap: 8px;
                padding-top: 10px;
            }
            .iconpicker-icon {
                padding: 10px;
                text-align: center;
                cursor: pointer;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                transition: all 0.2s;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
            }
            .dark .iconpicker-icon {
                border-color: #374151;
            }
            .iconpicker-icon:hover {
                background-color: #f3f4f6;
                border-color: #3b82f6;
                transform: scale(1.1);
            }
            .dark .iconpicker-icon:hover {
                background-color: #374151;
            }
            .iconpicker-icon.selected {
                background-color: #3b82f6;
                color: white;
                border-color: #3b82f6;
            }
            .iconpicker-search {
                width: 100%;
                padding: 10px;
                margin-bottom: 10px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background-color: #fff;
            }
            .dark .iconpicker-search {
                background-color: #1f2937;
                border-color: #374151;
                color: #fff;
            }
        </style>
    @endpush

    @push('scripts')
        <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
        <script>
            (function() {
                function initIconPicker() {
                    if (typeof jQuery === 'undefined') {
                        setTimeout(initIconPicker, 100);
                        return;
                    }
                    
                    jQuery(document).ready(function($) {
                        // Bootstrap Icons list (common icons)
                        const bootstrapIcons = [
                            'bi-check-circle', 'bi-x-circle', 'bi-info-circle', 'bi-exclamation-circle',
                            'bi-check', 'bi-x', 'bi-plus', 'bi-dash', 'bi-arrow-right', 'bi-arrow-left',
                            'bi-arrow-up', 'bi-arrow-down', 'bi-chevron-right', 'bi-chevron-left',
                            'bi-chevron-up', 'bi-chevron-down', 'bi-star', 'bi-star-fill', 'bi-heart',
                            'bi-heart-fill', 'bi-bookmark', 'bi-bookmark-fill', 'bi-calendar', 'bi-clock',
                            'bi-envelope', 'bi-envelope-fill', 'bi-person', 'bi-person-fill', 'bi-people',
                            'bi-gear', 'bi-gear-fill', 'bi-house', 'bi-house-fill', 'bi-file', 'bi-file-text',
                            'bi-folder', 'bi-folder-fill', 'bi-image', 'bi-image-fill', 'bi-camera',
                            'bi-camera-fill', 'bi-printer', 'bi-printer-fill', 'bi-download', 'bi-upload',
                            'bi-trash', 'bi-trash-fill', 'bi-pencil', 'bi-pencil-fill', 'bi-save',
                            'bi-save-fill', 'bi-search', 'bi-filter', 'bi-list', 'bi-grid', 'bi-menu-button',
                            'bi-three-dots', 'bi-three-dots-vertical', 'bi-eye', 'bi-eye-fill', 'bi-eye-slash',
                            'bi-lock', 'bi-unlock', 'bi-shield', 'bi-shield-fill', 'bi-bell', 'bi-bell-fill',
                            'bi-chat', 'bi-chat-fill', 'bi-phone', 'bi-phone-fill', 'bi-link', 'bi-link-45deg',
                            'bi-box-arrow-up-right', 'bi-external-link', 'bi-download', 'bi-upload',
                            'bi-arrow-repeat', 'bi-arrow-clockwise', 'bi-arrow-counterclockwise'
                        ];
                        
                        $('.iconpicker').each(function() {
                            const $input = $(this);
                            let $popover = $input.next('.iconpicker-popover');
                            
                            if ($popover.length === 0) {
                                $popover = $('<div class="iconpicker-popover"></div>');
                                $input.after($popover);
                                
                                const $search = $('<input type="text" class="iconpicker-search" placeholder="Search icons...">');
                                const $icons = $('<div class="iconpicker-icons"></div>');
                                
                                bootstrapIcons.forEach(function(icon) {
                                    const $icon = $('<div class="iconpicker-icon" data-icon="' + icon + '"><i class="bi ' + icon + '"></i></div>');
                                    $icons.append($icon);
                                });
                                
                                $popover.append($search);
                                $popover.append($icons);
                                
                                // Search functionality
                                $search.on('input', function() {
                                    const searchTerm = $(this).val().toLowerCase();
                                    $icons.find('.iconpicker-icon').each(function() {
                                        const iconName = $(this).data('icon').toLowerCase();
                                        if (iconName.includes(searchTerm)) {
                                            $(this).show();
                                        } else {
                                            $(this).hide();
                                        }
                                    });
                                });
                                
                                // Icon selection
                                $icons.on('click', '.iconpicker-icon', function() {
                                    const icon = $(this).data('icon');
                                    $input.val(icon);
                                    $popover.removeClass('show');
                                    $icons.find('.iconpicker-icon').removeClass('selected');
                                    $(this).addClass('selected');
                                });
                            }
                            
                            // Toggle popover on input click
                            $input.on('click', function(e) {
                                e.preventDefault();
                                $popover.toggleClass('show');
                                
                                // Set selected icon if input has value
                                if ($input.val()) {
                                    $popover.find('.iconpicker-icon').removeClass('selected');
                                    $popover.find('.iconpicker-icon[data-icon="' + $input.val() + '"]').addClass('selected');
                                }
                            });
                            
                            // Close popover when clicking outside
                            $(document).on('click', function(e) {
                                if (!$(e.target).closest('.iconpicker, .iconpicker-popover').length) {
                                    $popover.removeClass('show');
                                }
                            });
                        });
                    });
                }
                
                initIconPicker();
            })();
        </script>
    @endpush
</x-layouts.backend-layout>

