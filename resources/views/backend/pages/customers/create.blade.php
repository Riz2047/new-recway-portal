<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <form
        action="{{ route('admin.customers.store') }}"
        method="POST"
        data-prevent-unsaved-changes
    >
        @csrf

        <x-card>
            <x-slot name="header">
                {{ __('Add New Customer') }}
            </x-slot>

            <div class="space-y-6">
                <!-- Basic Information Section -->
                <div class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <iconify-icon icon="lucide:user-circle" class="w-5 h-5"></iconify-icon>
                        {{ __('Basic Information') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Name') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="name" 
                                id="name" 
                                required 
                                value="{{ old('name') }}"
                                class="form-control"
                                placeholder="{{ __('Enter customer name') }}"
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Email') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email" 
                                name="email" 
                                id="email" 
                                required 
                                value="{{ old('email') }}"
                                class="form-control"
                                placeholder="{{ __('Enter email address') }}"
                            >
                            @error('email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Password') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="password" 
                                id="password" 
                                required 
                                value="{{ old('password', \Illuminate\Support\Str::random(7)) }}"
                                class="form-control"
                                placeholder="{{ __('Enter password') }}"
                            >
                            @error('password')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Phone') }}
                            </label>
                            <input 
                                type="text" 
                                name="phone" 
                                id="phone" 
                                value="{{ old('phone') }}"
                                class="form-control"
                                placeholder="{{ __('Enter phone number') }}"
                            >
                            @error('phone')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="company" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Company') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="company" 
                                id="company" 
                                required 
                                value="{{ old('company') }}"
                                class="form-control"
                                placeholder="{{ __('Enter company name') }}"
                            >
                            @error('company')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="org_no" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Organization Number') }} <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text" 
                                name="org_no" 
                                id="org_no" 
                                required 
                                value="{{ old('org_no') }}"
                                class="form-control"
                                placeholder="{{ __('Enter organization number') }}"
                            >
                            @error('org_no')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="client_wish" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('The client wishes the interview material to be sent to an external party') }}
                            </label>
                            <input 
                                type="text" 
                                name="client_wish" 
                                id="client_wish" 
                                value="{{ old('client_wish') }}"
                                class="form-control"
                                placeholder="{{ __('Enter external party details') }}"
                            >
                            @error('client_wish')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Settings Section -->
                <div class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <iconify-icon icon="lucide:settings" class="w-5 h-5"></iconify-icon>
                        {{ __('Settings') }}
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Parent Customer') }}
                            </label>
                            <select 
                                name="parent_id" 
                                id="parent_customer" 
                                class="form-control"
                            >
                                <option value="">{{ __('Select Customer') }}</option>
                                @foreach($parentCustomers as $parent)
                                    <option value="{{ $parent->id }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->user->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="invoice_period" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Invoice Period') }}
                            </label>
                            <select 
                                name="invoice_period" 
                                id="invoice_period" 
                                class="form-control"
                            >
                                <option value="">{{ __('Select Invoice Period') }}</option>
                                <option value="month" {{ old('invoice_period') == 'month' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
                                <option value="week" {{ old('invoice_period') == 'week' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
                                <option value="day" {{ old('invoice_period') == 'day' ? 'selected' : '' }}>{{ __('Daily') }}</option>
                            </select>
                            @error('invoice_period')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="cus_department" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Department') }}
                            </label>
                            <select 
                                name="cus_department" 
                                id="cus_department" 
                                class="form-control"
                            >
                                <option value="">{{ __('Select Department') }}</option>
                            </select>
                            @error('cus_department')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Email & Report Options Section -->
                <div class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <iconify-icon icon="lucide:mail" class="w-5 h-5"></iconify-icon>
                        {{ __('Email & Report Options') }}
                    </h3>
                    <div class="space-y-4">
                            <div class="flex items-center gap-4">
                            <label class="flex items-center gap-2">
                                <input 
                                    type="radio" 
                                    name="active_mail" 
                                    value="same" 
                                    id="same_email" 
                                    checked
                                >
                                <span>{{ __('Same') }}</span>
                            </label>
                            <label class="flex items-center gap-2">
                                <input 
                                    type="radio" 
                                    name="active_mail" 
                                    value="change" 
                                    id="change_email"
                                >
                                <span>{{ __('Change Email') }}</span>
                            </label>
                        </div>

                        <div id="row_of_email" style="display: none;">
                            <label for="changed_registration_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {{ __('Registration Email Template') }}
                            </label>
                            <textarea 
                                name="changed_registration_email" 
                                id="changed_registration_email" 
                                rows="10" 
                                class="form-control"
                                disabled
                            >{{ old('changed_registration_email', $defaultRegEmail ?? '') }}</textarea>
                            @error('changed_registration_email')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="send_email" 
                                    value="1" 
                                    id="send_email"
                                    {{ old('send_email') ? 'checked' : '' }}
                                >
                                <span>{{ __('CC email of customer registration') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="company_manager" 
                                    value="1" 
                                    id="company_manager"
                                    {{ old('company_manager') ? 'checked' : '' }}
                                >
                                <span>{{ __('Company Manager') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="interview_template" 
                                    value="1" 
                                    id="interview_template"
                                    {{ old('interview_template') ? 'checked' : '' }}
                                >
                                <span>{{ __('Interview Template') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="send_security_report" 
                                    value="1" 
                                    id="send_security_report"
                                    {{ old('send_security_report') ? 'checked' : '' }}
                                >
                                <span>{{ __('Send result of the basic investigation') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="interview_upload_allowed" 
                                    value="1" 
                                    id="interview_upload_allowed"
                                    {{ old('interview_upload_allowed') ? 'checked' : '' }}
                                >
                                <span>{{ __('Interview upload report') }}</span>
                            </label>

                            <label class="flex items-center gap-2">
                                <input 
                                    type="checkbox" 
                                    name="timra_report" 
                                    value="1" 
                                    id="timra_report"
                                    {{ old('timra_report') ? 'checked' : '' }}
                                >
                                <span>{{ __('Timrå Interview Template') }}</span>
                            </label>

                            <div 
                                x-data="{ showCombine: {{ old('combine_bk_and_security') ? 'true' : 'false' }} }"
                            >
                                <label class="flex items-center gap-2">
                                    <input 
                                        type="checkbox" 
                                        name="combine_bk_and_security" 
                                        value="1" 
                                        id="combine_bk_and_security"
                                        x-model="showCombine"
                                    >
                                    <span>{{ __('Combine Background Check and Security Interview') }}</span>
                                </label>

                                <div 
                                    id="combine_services_section" 
                                    class="mt-4"
                                    x-show="showCombine"
                                    x-cloak
                                >
                                    <div class="mb-4">
                                        <label for="combine_bk_and_security_services" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('Allowed Services to transfer') }}
                                        </label>
                                        <select 
                                            name="combine_bk_and_security[]" 
                                            id="combine_bk_and_security_services" 
                                            multiple 
                                            class="form-control js-multiselect"
                                            style="min-height: 100px;"
                                        >
                                            @if(isset($services))
                                                @foreach($services->where('service_cat_id', 3) as $service)
                                                    <option value="{{ $service->id }}" {{ old('combine_bk_and_security') && in_array($service->id, old('combine_bk_and_security', [])) ? 'selected' : '' }}>
                                                        {{ $service->title }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>

                                    <div class="mb-4">
                                        <label for="combine_statuses" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('Allowed Statuses to transfer') }}
                                        </label>
                                        <select 
                                            name="combine_status[]" 
                                            id="combine_statuses" 
                                            multiple 
                                            class="form-control js-multiselect"
                                            style="min-height: 100px;"
                                        >
                                            @foreach($allStatuses as $status)
                                                <option value="{{ $status->id }}" {{ old('combine_status') && in_array($status->id, old('combine_status', [])) ? 'selected' : '' }}>
                                                    {{ $status->status }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label for="combine_interview_service" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                            {{ __('Combine Interview service') }}
                                        </label>
                                        <select 
                                            name="combine_interview_service" 
                                            id="combine_interview_service" 
                                            class="form-control"
                                        >
                                            <option value="">{{ __('Select Interview service') }}</option>
                                            @if(isset($services))
                                                @foreach($services->where('service_cat_id', 1) as $service)
                                                    <option value="{{ $service->id }}" {{ old('combine_interview_service') == $service->id ? 'selected' : '' }}>
                                                        {{ $service->title }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4" x-data="{ showBillingDetails: false }">
                            <button 
                                type="button" 
                                @click="showBillingDetails = !showBillingDetails"
                                :class="showBillingDetails ? 'rounded-b-none' : ''"
                                class="w-full bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 text-white font-semibold py-3 px-6 rounded-lg transition-all duration-200 shadow-md hover:shadow-lg uppercase tracking-wide"
                            >
                                {{ __('Standard Billing Details') }}
                            </button>
                            <div 
                                x-show="showBillingDetails"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="mt-0 bg-white dark:bg-gray-800 p-6 rounded-b-lg border border-gray-200 dark:border-gray-700 border-t-0 shadow-sm"
                            >
                                <div class="space-y-6">
                                    <div>
                                        <label for="pref" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('Reference') }}
                                            <span class="block text-xs font-normal text-gray-500 dark:text-gray-400 mt-1">
                                                {{ __('(Invoice Recipient)') }}
                                            </span>
                                        </label>
                                        <input 
                                            type="text" 
                                            name="pref" 
                                            id="pref" 
                                            value="{{ old('pref') }}"
                                            class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors"
                                            placeholder="{{ __('Enter reference person') }}"
                                        >
                                        @error('pref')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div>
                                        <label for="ref" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('Reference') }}
                                        </label>
                                        <input 
                                            type="text" 
                                            name="ref" 
                                            id="ref" 
                                            value="{{ old('ref') }}"
                                            class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors"
                                            placeholder="{{ __('Enter reference') }}"
                                        >
                                        @error('ref')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    
                                    <div>
                                        <label for="comment" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">
                                            {{ __('Invoice Comment') }}
                                        </label>
                                        <textarea 
                                            name="comment" 
                                            id="comment" 
                                            rows="3"
                                            class="w-full form-control border-2 border-gray-300 dark:border-gray-600 rounded-lg px-4 py-2 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 transition-colors resize-none"
                                            placeholder="{{ __('Enter invoice comment') }}"
                                        >{{ old('comment') }}</textarea>
                                        @error('comment')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Permissions & Services Section -->
                <div class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
                    <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                        <iconify-icon icon="lucide:shield-check" class="w-5 h-5"></iconify-icon>
                        {{ __('Permissions & Services') }}
                    </h3>
                    <div class="space-y-4">
                        <!-- Accordion Container -->
                        <div class="space-y-2">
                            <!-- Permissions Accordion -->
                            @if($permissions->count() > 0)
                                <div 
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                    x-data="{ open: false }"
                                >
                                    <button 
                                        type="button"
                                        @click="open = !open"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 flex items-center justify-between transition-colors"
                                    >
                                        <span>{{ __('Permissions') }}</span>
                                        <iconify-icon 
                                            icon="lucide:chevron-down" 
                                            class="w-5 h-5 transition-transform duration-200"
                                            :class="{ 'rotate-180': open }"
                                        ></iconify-icon>
                                    </button>
                                    <div 
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="bg-white dark:bg-gray-900 p-4"
                                    >
                                        <div class="space-y-0">
                                            @foreach($permissions as $permission)
                                                <div class="form-check py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        name="permissions[]" 
                                                        value="{{ $permission->id }}"
                                                        id="permission_{{ $permission->id }}"
                                                        {{ $permission->user_type == 1 ? 'checked' : '' }}
                                                        {{ old('permissions') && in_array($permission->id, old('permissions')) ? 'checked' : '' }}
                                                    >
                                                    <label class="form-check-label form-label" for="permission_{{ $permission->id }}">
                                                        {{ $permission->title }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Status Required by Service Category Accordions -->
                            @foreach($serviceCategories as $category)
                                @if(isset($statusesByCategory[$category->id]) && $statusesByCategory[$category->id]->count() > 0)
                                    <div 
                                        class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                        x-data="{ open: false }"
                                    >
                                        <button 
                                            type="button"
                                            @click="open = !open"
                                            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 flex items-center justify-between transition-colors"
                                        >
                                            <span>{{ __('Status Required') }} - {{ $category->name }}</span>
                                            <iconify-icon 
                                                icon="lucide:chevron-down" 
                                                class="w-5 h-5 transition-transform duration-200"
                                                :class="{ 'rotate-180': open }"
                                            ></iconify-icon>
                                        </button>
                                        <div 
                                            x-show="open"
                                            x-transition:enter="transition ease-out duration-200"
                                            x-transition:enter-start="opacity-0"
                                            x-transition:enter-end="opacity-100"
                                            x-transition:leave="transition ease-in duration-150"
                                            x-transition:leave-start="opacity-100"
                                            x-transition:leave-end="opacity-0"
                                            class="bg-white dark:bg-gray-900 p-4"
                                        >
                                            <div class="space-y-2 max-h-60 overflow-y-auto">
                                                @foreach($statusesByCategory[$category->id] as $status)
                                                    <label class="flex items-center gap-2 py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                        <input 
                                                            type="checkbox" 
                                                            name="statuses[]" 
                                                            value="{{ $status->id }}"
                                                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                                                            checked
                                                            {{ old('statuses') && in_array($status->id, old('statuses')) ? 'checked' : '' }}
                                                        >
                                                        <span class="text-sm text-gray-700 dark:text-gray-300">{{ $status->status }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @endforeach

                            <!-- Allowed Services Accordion -->
                            @if($services->count() > 0)
                                <div 
                                    class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
                                    x-data="{ open: false }"
                                >
                                    <button 
                                        type="button"
                                        @click="open = !open"
                                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 flex items-center justify-between transition-colors"
                                    >
                                        <span>{{ __('Allowed Services') }}</span>
                                        <iconify-icon 
                                            icon="lucide:chevron-down" 
                                            class="w-5 h-5 transition-transform duration-200"
                                            :class="{ 'rotate-180': open }"
                                        ></iconify-icon>
                                    </button>
                                    <div 
                                        x-show="open"
                                        x-transition:enter="transition ease-out duration-200"
                                        x-transition:enter-start="opacity-0"
                                        x-transition:enter-end="opacity-100"
                                        x-transition:leave="transition ease-in duration-150"
                                        x-transition:leave-start="opacity-100"
                                        x-transition:leave-end="opacity-0"
                                        class="bg-white dark:bg-gray-900 p-4"
                                    >
                                        <div class="space-y-0">
                                            @foreach($services as $service)
                                                <div class="form-check py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
                                                    <input 
                                                        class="form-check-input service_checkbox" 
                                                        type="checkbox" 
                                                        name="services[]" 
                                                        value="{{ $service->id }}"
                                                        id="service_{{ $service->id }}"
                                                        {{ $service->service_cat_id == 1 ? 'checked' : '' }}
                                                        {{ old('services') && in_array($service->id, old('services')) ? 'checked' : '' }}
                                                    >
                                                    <label class="form-check-label form-label" for="service_{{ $service->id }}">
                                                        {{ $service->title }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4">
                    <a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
                        {{ __('Cancel') }}
                    </a>
                    <button type="submit" class="btn btn-primary">
                        {{ __('Save Customer') }}
                    </button>
                </div>
            </div>
        </x-card>
    </form>
</x-layouts.backend-layout>

@push('scripts')
<script>
    // Run setup immediately (script is loaded after DOM in layout)
    (function initCustomerCreatePage() {
        // Toggle Email Template
        const sameEmailRadio = document.getElementById('same_email');
        const changeEmailRadio = document.getElementById('change_email');
        if (sameEmailRadio) {
            sameEmailRadio.addEventListener('change', function() {
                toggleEmailTemplate(false);
            });
        }
        if (changeEmailRadio) {
            changeEmailRadio.addEventListener('change', function() {
                toggleEmailTemplate(true);
            });
        }

        // Load parent customer data
        const parentCustomerSelect = document.getElementById('parent_customer');
        if (parentCustomerSelect) {
            parentCustomerSelect.addEventListener('change', function() {
                loadParentCustomerData(this.value);
            });
        }
    })();

    function toggleEmailTemplate(show) {
        const emailRow = document.getElementById('row_of_email');
        const emailTextarea = document.getElementById('changed_registration_email');
        
        if (emailRow && emailTextarea) {
            if (show) {
                emailRow.style.display = 'block';
                emailTextarea.disabled = false;
            } else {
                emailRow.style.display = 'none';
                emailTextarea.disabled = true;
            }
        }
    }

    function loadParentCustomerData(parentId) {
        if (!parentId) {
            document.getElementById('cus_department').innerHTML = '<option value="">{{ __("Select Department") }}</option>';
            return;
        }

        fetch(`{{ route('admin.customers.get-parent-data') }}?parent_id=${parentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update invoice period
                    if (data.customer.invoice_period) {
                        document.getElementById('invoice_period').value = data.customer.invoice_period;
                    }

                    // Update interview upload allowed
                    if (data.customer.interview_upload_allowed == 1) {
                        document.getElementById('interview_upload_allowed').checked = true;
                    }

                    // Update statuses
                    if (data.statuses && data.statuses.length > 0) {
                        document.querySelectorAll('input[name="statuses[]"]').forEach(checkbox => {
                            checkbox.checked = data.statuses.includes(checkbox.value);
                        });
                    }

                    // Update services
                    if (data.services && data.services.length > 0) {
                        document.querySelectorAll('.service_checkbox').forEach(checkbox => {
                            checkbox.checked = data.services.includes(checkbox.value);
                        });
                    }

                    // Update permissions
                    if (data.permissions && data.permissions.length > 0) {
                        document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
                            checkbox.checked = data.permissions.includes(parseInt(checkbox.value));
                        });
                    }

                    // Update departments
                    const deptSelect = document.getElementById('cus_department');
                    deptSelect.innerHTML = '<option value="">{{ __("Select Department") }}</option>';
                    if (data.departments && data.departments.length > 0) {
                        data.departments.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.dep_id;
                            option.textContent = dept.dep_name;
                            deptSelect.appendChild(option);
                        });
                    }

                    // Update combine services
                    if (data.customer.combine_bk_and_security && data.customer.combine_bk_and_security !== '0') {
                        document.getElementById('combine_bk_and_security').checked = true;
                        toggleCombineServices(true);
                        const services = data.customer.combine_bk_and_security.split(',');
                        services.forEach(serviceId => {
                            const option = document.querySelector(`#combine_bk_and_security_services option[value="${serviceId}"]`);
                            if (option) option.selected = true;
                        });
                    }

                    // Update combine statuses
                    if (data.customer.combine_status && data.customer.combine_status !== '0') {
                        const statuses = data.customer.combine_status.split(',');
                        statuses.forEach(statusId => {
                            const option = document.querySelector(`#combine_statuses option[value="${statusId}"]`);
                            if (option) option.selected = true;
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error loading parent customer data:', error);
            });
    }
</script>
@endpush

