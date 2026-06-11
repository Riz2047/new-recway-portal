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

														<label class="flex items-center gap-2">
															<input 
																type="checkbox" 
																name="ellevio_report" 
																value="1" 
																id="ellevio_report"
																{{ old('ellevio_report') ? 'checked' : '' }}
															>
															<span>{{ __('Ellevio Interview Template') }}</span>
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
                                                @foreach($services->where('service_category_id', 3) as $service)
                                                    <option value="{{ $service->id }}" {{ old('combine_bk_and_security') && in_array($service->id, old('combine_bk_and_security', [])) ? 'selected' : '' }}>
                                                        {{ $service->name }}
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
                                                @foreach($services->where('service_category_id', 1) as $service)
                                                    <option value="{{ $service->id }}" {{ old('combine_interview_service') == $service->id ? 'selected' : '' }}>
                                                        {{ $service->name }}
                                                    </option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800" x-data="{ showBillingDetails: false }">
                            <button
                                type="button"
                                @click="showBillingDetails = !showBillingDetails"
                                class="w-full flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors"
                            >
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-sky-50 dark:bg-sky-900/30">
                                        <iconify-icon icon="lucide:receipt" width="16" class="text-sky-600 dark:text-sky-400"></iconify-icon>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Standard Billing Details') }}</p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Invoice & reference info') }}</p>
                                    </div>
                                </div>
                                <iconify-icon icon="lucide:chevron-down" width="16"
                                    class="text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': showBillingDetails }"></iconify-icon>
                            </button>
                            <div
                                x-show="showBillingDetails"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="bg-white dark:bg-gray-800 p-6 border-t border-gray-200 dark:border-gray-700"
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
                                            class="w-full form-control"
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
                                            class="w-full form-control"
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
                                            class="w-full form-control resize-none"
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

                {{-- ── Billing Details Section (matching screenshot card style) ── --}}
                {{-- (already rendered above in the billing x-data block) --}}

                <!-- ── Permissions & Services ──────────────────────────────────── -->
                <div class="space-y-3">

                    {{-- ── Permissions accordion ── --}}
                    @if($permissions->count() > 0)
                    @php
                        $defaultPermIds = $permissions->where('user_type', 1)->pluck('id')->values()->toJson();
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
                        x-data="{
                            open: false,
                            selectedIds: {{ $defaultPermIds }},
                            toggle(id) {
                                const i = this.selectedIds.indexOf(id);
                                i > -1 ? this.selectedIds.splice(i, 1) : this.selectedIds.push(id);
                            },
                            get checked() { return this.selectedIds.length; }
                        }">
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-purple-50 dark:bg-purple-900/30">
                                    <iconify-icon icon="lucide:lock" width="16" class="text-purple-600 dark:text-purple-400"></iconify-icon>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Permissions') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Manage required permissions') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-purple-100 dark:bg-purple-900/40 px-2.5 py-0.5 text-xs font-semibold text-purple-700 dark:text-purple-300"
                                    x-text="checked + ' {{ __('active') }}'"></span>
                                <iconify-icon icon="lucide:chevron-down" width="16"
                                    class="text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"></iconify-icon>
                            </div>
                        </button>
                        {{-- x-for generates the actual form inputs (always in DOM, not hidden by x-show) --}}
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="permissions[]" :value="id">
                        </template>
                        <div x-show="open" x-transition class="border-t border-gray-100 dark:border-gray-700 px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($permissions as $permission)
                                <span @click="toggle({{ $permission->id }})"
                                    :class="selectedIds.includes({{ $permission->id }})
                                        ? 'bg-purple-600 border-purple-600 text-white shadow-sm'
                                        : 'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-purple-300'"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border-2 px-3 py-1.5 text-xs font-semibold transition-all select-none">
                                    <iconify-icon x-show="selectedIds.includes({{ $permission->id }})" icon="lucide:check" width="11" class="shrink-0"></iconify-icon>
                                    {{ $permission->title }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- ── Status Required per Service Category ── --}}
                    @php
                        $catConfig = [
                            ['icon' => 'lucide:search',    'bg' => 'bg-blue-50 dark:bg-blue-900/30',    'text' => 'text-blue-600 dark:text-blue-400',    'badge' => 'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',    'on' => 'bg-blue-600 border-blue-600 text-white',    'off' => 'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-blue-300'],
                            ['icon' => 'lucide:grid-2x2',  'bg' => 'bg-emerald-50 dark:bg-emerald-900/30','text' => 'text-emerald-600 dark:text-emerald-400','badge' => 'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300','on' => 'bg-emerald-600 border-emerald-600 text-white','off' => 'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-emerald-300'],
                            ['icon' => 'lucide:repeat-2',  'bg' => 'bg-amber-50 dark:bg-amber-900/30',  'text' => 'text-amber-600 dark:text-amber-400',  'badge' => 'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',  'on' => 'bg-amber-500 border-amber-500 text-white',  'off' => 'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-amber-300'],
                            ['icon' => 'lucide:layers',    'bg' => 'bg-rose-50 dark:bg-rose-900/30',    'text' => 'text-rose-600 dark:text-rose-400',    'badge' => 'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',    'on' => 'bg-rose-600 border-rose-600 text-white',    'off' => 'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-rose-300'],
                        ];
                        $ci = 0;
                    @endphp
                    @foreach($serviceCategories as $category)
                    @if(isset($statusesByCategory[$category->id]) && $statusesByCategory[$category->id]->count() > 0)
                    @php
                        $c          = $catConfig[$ci % count($catConfig)];
                        $allIds     = $statusesByCategory[$category->id]->pluck('id')->values()->toJson();
                        $ci++;
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
                        x-data="{
                            open: false,
                            selectedIds: {{ $allIds }},
                            toggle(id) {
                                const i = this.selectedIds.indexOf(id);
                                i > -1 ? this.selectedIds.splice(i, 1) : this.selectedIds.push(id);
                            },
                            get checked() { return this.selectedIds.length; }
                        }">
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $c['bg'] }}">
                                    <iconify-icon icon="{{ $c['icon'] }}" width="16" class="{{ $c['text'] }}"></iconify-icon>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $category->name }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Manage required statuses') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $c['badge'] }}"
                                    x-text="checked + ' {{ __('active') }}'"></span>
                                <iconify-icon icon="lucide:chevron-down" width="16"
                                    class="text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"></iconify-icon>
                            </div>
                        </button>
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="statuses[]" :value="id">
                        </template>
                        <div x-show="open" x-transition class="border-t border-gray-100 dark:border-gray-700 px-4 py-4">
                            <div class="flex flex-wrap gap-2">
                                @foreach($statusesByCategory[$category->id] as $status)
                                <span @click="toggle({{ $status->id }})"
                                    :class="selectedIds.includes({{ $status->id }})
                                        ? '{{ $c['on'] }}'
                                        : '{{ $c['off'] }}'"
                                    class="inline-flex cursor-pointer items-center gap-1.5 rounded-full border-2 px-3 py-1.5 text-xs font-semibold transition-all select-none shadow-sm">
                                    <iconify-icon x-show="selectedIds.includes({{ $status->id }})" icon="lucide:check" width="11" class="shrink-0"></iconify-icon>
                                    {{ $status->status }}
                                </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif
                    @endforeach

                    {{-- ── Allowed Services ── --}}
                    @if($services->count() > 0)
                    @php
                        $defaultSvcIds = $services->where('service_category_id', 1)->pluck('id')->values()->toJson();
                        $totalSvc      = $services->count();
                    @endphp
                    <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
                        x-data="{
                            open: false,
                            selectedIds: {{ $defaultSvcIds }},
                            total: {{ $totalSvc }},
                            toggle(id) {
                                const i = this.selectedIds.indexOf(id);
                                i > -1 ? this.selectedIds.splice(i, 1) : this.selectedIds.push(id);
                            },
                            get checked() { return this.selectedIds.length; }
                        }">
                        <button type="button" @click="open = !open"
                            class="w-full flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 dark:bg-indigo-900/30">
                                    <iconify-icon icon="lucide:package" width="16" class="text-indigo-600 dark:text-indigo-400"></iconify-icon>
                                </div>
                                <div class="text-left">
                                    <p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ __('Allowed Services') }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Physical & video interviews') }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-indigo-100 dark:bg-indigo-900/40 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 dark:text-indigo-300"
                                    x-text="checked + '/' + total"></span>
                                <iconify-icon icon="lucide:chevron-down" width="16"
                                    class="text-gray-400 transition-transform duration-200"
                                    :class="{ 'rotate-180': open }"></iconify-icon>
                            </div>
                        </button>
                        <template x-for="id in selectedIds" :key="id">
                            <input type="hidden" name="services[]" :value="id">
                        </template>
                        <div x-show="open" x-transition class="border-t border-gray-100 dark:border-gray-700 p-3">
                            <div class="space-y-2">
                                @foreach($services->groupBy('serviceCategory.name') as $catName => $catServices)
                                @foreach($catServices as $service)
                                <div @click="toggle({{ $service->id }})"
                                    :class="selectedIds.includes({{ $service->id }})
                                        ? 'border-indigo-400 bg-indigo-50 dark:border-indigo-500 dark:bg-indigo-900/20'
                                        : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800 hover:border-indigo-200 dark:hover:border-gray-600'"
                                    class="flex cursor-pointer items-start gap-3 rounded-lg border-2 p-3 transition-all select-none">
                                    {{-- Visual checkbox square --}}
                                    <div :class="selectedIds.includes({{ $service->id }})
                                            ? 'bg-indigo-600 border-indigo-600'
                                            : 'bg-white border-gray-300 dark:bg-gray-700 dark:border-gray-500'"
                                        class="flex h-5 w-5 shrink-0 items-center justify-center rounded-md border-2 transition-all mt-0.5">
                                        <iconify-icon x-show="selectedIds.includes({{ $service->id }})" icon="lucide:check" width="11" class="text-white"></iconify-icon>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        @if($service->serviceCategory)
                                        <span class="mb-0.5 inline-block rounded bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                            {{ $service->serviceCategory->name }}
                                        </span>
                                        @endif
                                        <p class="text-sm font-medium text-gray-700 dark:text-gray-200 leading-snug">{{ $service->name }}</p>
                                    </div>
                                </div>
                                @endforeach
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                </div>{{-- /space-y-3 --}}

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
                            checkbox.checked = data.services.includes(parseInt(checkbox.value, 10));
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

