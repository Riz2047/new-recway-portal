<div wire:loading>
	Loading edit...
</div>

<div wire:loading.remove>
	<form
		action="{{ route('admin.customers.update', $customer->id) }}"
		method="POST"
		data-prevent-unsaved-changes>
		@csrf
		@method('PUT')

		<x-card>
			<x-slot name="header">
				{{ __('Edit Customer') }} - {{ $customer->user->name }}
			</x-slot>
			<!-- Basic Information Section (Edit tab) -->
			<div x-show="activeTab === 'edit'" x-cloak class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
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
							value="{{ old('name', $customer->user->name) }}"
							class="form-control">
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
							value="{{ old('email', $customer->user->email) }}"
							class="form-control">
						<input type="hidden" name="old_email" value="{{ $customer->user->email }}">
						@error('email')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>

					<div>
						<label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
							{{ __('Password') }} ({{ __('Leave blank to keep current password') }})
						</label>
						<input
							type="text"
							name="password"
							id="password"
							value="{{ old('password') }}"
							class="form-control"
							placeholder="{{ __('Enter new password') }}">
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
							value="{{ old('phone', $customer->phone) }}"
							class="form-control">
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
							value="{{ old('company', $customer->company) }}"
							class="form-control">
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
							value="{{ old('org_no', $customer->org_no) }}"
							class="form-control">
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
							value="{{ old('client_wish', $customer->client_wish) }}"
							class="form-control">
						@error('client_wish')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>
			</div>

			<!-- Settings Section (Edit tab) -->
			<div x-show="activeTab === 'edit'" x-cloak class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
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
							class="form-control">
							<option value="">{{ __('Select Customer') }}</option>
							@foreach($parentCustomers as $parent)
							<option value="{{ $parent->id }}" {{ old('parent_id', $customer->parent_id) == $parent->id ? 'selected' : '' }}>
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
							class="form-control">
							<option value="">{{ __('Select Invoice Period') }}</option>
							<option value="month" {{ old('invoice_period', $customer->invoice_period) == 'month' ? 'selected' : '' }}>{{ __('Monthly') }}</option>
							<option value="week" {{ old('invoice_period', $customer->invoice_period) == 'week' ? 'selected' : '' }}>{{ __('Weekly') }}</option>
							<option value="day" {{ old('invoice_period', $customer->invoice_period) == 'day' ? 'selected' : '' }}>{{ __('Daily') }}</option>
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
							class="form-control">
							<option value="">{{ __('Select Department') }}</option>
							@foreach($departments as $dept)
							<option value="{{ $dept->dep_id }}" {{ old('cus_department', $customer->dep_id) == $dept->dep_id ? 'selected' : '' }}>
								{{ $dept->dep_name }}
							</option>
							@endforeach
						</select>
						@error('cus_department')
						<p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
						@enderror
					</div>
				</div>
			</div>

			<!-- Email & Report Options Section (Edit tab) -->
			<div x-show="activeTab === 'edit'" x-cloak class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
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
								checked>
							<span>{{ __('Same') }}</span>
						</label>
						<label class="flex items-center gap-2">
							<input
								type="radio"
								name="active_mail"
								value="change"
								id="change_email">
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
							disabled>{{ old('changed_registration_email', $customer->reg_email ?? '') }}</textarea>
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
								{{ old('send_email', $customer->sent_email) ? 'checked' : '' }}>
							<span>{{ __('CC email of customer registration') }}</span>
						</label>

						<label class="flex items-center gap-2">
							<input
								type="checkbox"
								name="company_manager"
								value="1"
								id="company_manager"
								{{ old('company_manager', $companyManager ? 1 : 0) ? 'checked' : '' }}>
							<span>{{ __('Company Manager') }}</span>
						</label>

						<label class="flex items-center gap-2">
							<input
								type="checkbox"
								name="interview_template"
								value="1"
								id="interview_template"
								{{ old('interview_template', $customer->interview_template) ? 'checked' : '' }}>
							<span>{{ __('Interview Template') }}</span>
						</label>

						<label class="flex items-center gap-2">
							<input
								type="checkbox"
								name="send_security_report"
								value="1"
								id="send_security_report"
								{{ old('send_security_report', $customer->send_security_report) ? 'checked' : '' }}>
							<span>{{ __('Send result of the basic investigation') }}</span>
						</label>

						<label class="flex items-center gap-2">
							<input
								type="checkbox"
								name="interview_upload_allowed"
								value="1"
								id="interview_upload_allowed"
								{{ old('interview_upload_allowed', $customer->interview_upload_allowed) ? 'checked' : '' }}>
							<span>{{ __('Interview upload report') }}</span>
						</label>

						<label class="flex items-center gap-2">
							<input
								type="checkbox"
								name="timra_report"
								value="1"
								id="timra_report"
								{{ old('timra_report', $customer->timra_report) ? 'checked' : '' }}>
							<span>{{ __('Timrå Interview Template') }}</span>
						</label>

						<div
							x-data="{ showCombine: {{ old('combine_bk_and_security', $customer->combine_bk_and_security && $customer->combine_bk_and_security != '0') ? 'true' : 'false' }} }">
							<label class="flex items-center gap-2">
								<input
									type="checkbox"
									name="combine_bk_and_security"
									value="1"
									id="combine_bk_and_security"
									x-model="showCombine">
								<span>{{ __('Combine Background Check and Security Interview') }}</span>
							</label>

							<div
								id="combine_services_section"
								class="mt-4"
								x-show="showCombine"
								x-cloak>
								<div class="mb-4">
									<label for="combine_bk_and_security_services" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
										{{ __('Allowed Services to transfer') }}
									</label>
									<select
										name="combine_bk_and_security[]"
										id="combine_bk_and_security_services"
										multiple
										class="form-control js-multiselect"
										style="min-height: 100px;">
										@if(isset($services))
										@php
										$selectedServices = $customer->combine_bk_and_security && $customer->combine_bk_and_security != '0'
										? explode(',', $customer->combine_bk_and_security)
										: [];
										@endphp
										@foreach($services->where('service_category_id', 3) as $service)
										<option value="{{ $service->id }}" {{ in_array((string) $service->id, array_map('strval', $selectedServices), true) ? 'selected' : '' }}>
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
										style="min-height: 100px;">
										@php
										$selectedStatuses = $customer->combine_status && $customer->combine_status != '0'
										? explode(',', $customer->combine_status)
										: [];
										@endphp
										@foreach($allStatuses as $status)
										<option value="{{ $status->id }}" {{ in_array($status->id, $selectedStatuses) ? 'selected' : '' }}>
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
										class="form-control">
										<option value="">{{ __('Select Interview service') }}</option>
										@if(isset($services))
										@foreach($services->where('service_category_id', 1) as $service)
										<option value="{{ $service->id }}" {{ (string) old('combine_interview_service', $customer->combine_interview_service) === (string) $service->id ? 'selected' : '' }}>
											{{ $service->name }}
										</option>
										@endforeach
										@endif
									</select>
								</div>
							</div>
						</div>
					</div>

					<div class="mt-4" x-show="activeTab === 'billing'" x-cloak>
						<div id="billing-tab-content" class="text-sm text-gray-500 dark:text-gray-400">
							{{ __('Loading billing details...') }}
						</div>
					</div>
				</div>
			</div>

			<!-- Permissions & Services Section (Edit tab) -->
			<div x-show="activeTab === 'edit'" x-cloak class="space-y-3">

				{{-- ── Permissions ── --}}
				@if($permissions->count() > 0)
				@php
					$savedPerms  = collect(old('permissions', $customerPermissions))->map(fn($id) => (int) $id)->values();
				// If no permissions saved yet, default to the same set as create (user_type == 1)
				$editPermIds = $savedPerms->isEmpty()
					? $permissions->where('user_type', 1)->pluck('id')->values()->toJson()
					: $savedPerms->toJson();
				@endphp
				<div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
					x-data="{
						open: false,
						selectedIds: {{ $editPermIds }},
						toggle(id) { const i=this.selectedIds.indexOf(id); i>-1?this.selectedIds.splice(i,1):this.selectedIds.push(id); },
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
					$editCatConfig = [
						['icon'=>'lucide:search',   'bg'=>'bg-blue-50 dark:bg-blue-900/30',    'text'=>'text-blue-600 dark:text-blue-400',    'badge'=>'bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300',    'on'=>'bg-blue-600 border-blue-600 text-white',    'off'=>'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-blue-300'],
						['icon'=>'lucide:grid-2x2', 'bg'=>'bg-emerald-50 dark:bg-emerald-900/30','text'=>'text-emerald-600 dark:text-emerald-400','badge'=>'bg-emerald-100 dark:bg-emerald-900/40 text-emerald-700 dark:text-emerald-300','on'=>'bg-emerald-600 border-emerald-600 text-white','off'=>'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-emerald-300'],
						['icon'=>'lucide:repeat-2', 'bg'=>'bg-amber-50 dark:bg-amber-900/30',  'text'=>'text-amber-600 dark:text-amber-400',  'badge'=>'bg-amber-100 dark:bg-amber-900/40 text-amber-700 dark:text-amber-300',  'on'=>'bg-amber-500 border-amber-500 text-white',  'off'=>'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-amber-300'],
						['icon'=>'lucide:layers',   'bg'=>'bg-rose-50 dark:bg-rose-900/30',    'text'=>'text-rose-600 dark:text-rose-400',    'badge'=>'bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300',    'on'=>'bg-rose-600 border-rose-600 text-white',    'off'=>'bg-white border-gray-300 text-gray-600 dark:bg-gray-800 dark:border-gray-500 dark:text-gray-400 hover:border-rose-300'],
					];
					$eci = 0;
				@endphp
				@foreach($serviceCategories as $category)
				@if(isset($statusesByCategory[$category->id]) && $statusesByCategory[$category->id]->count() > 0)
				@php
					$ec          = $editCatConfig[$eci % count($editCatConfig)];
					$allCatIds   = $statusesByCategory[$category->id]->pluck('id')->values()->toJson();
					$catStatusIds  = $statusesByCategory[$category->id]->pluck('id');
					$savedForCat   = collect(old('statuses', $customerStatuses))
						->map(fn($id) => (int) $id)
						->intersect($catStatusIds)
						->values();
					// If this customer has NO saved statuses for this category, default to ALL (same as create)
					$selCatIds = $savedForCat->isEmpty() ? $catStatusIds->values()->toJson() : $savedForCat->toJson();
					$eci++;
				@endphp
				<div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
					x-data="{
						open: false,
						selectedIds: {{ $selCatIds }},
						toggle(id) { const i=this.selectedIds.indexOf(id); i>-1?this.selectedIds.splice(i,1):this.selectedIds.push(id); },
						get checked() { return this.selectedIds.length; }
					}">
					<button type="button" @click="open = !open"
						class="w-full flex items-center justify-between px-4 py-3.5 hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors">
						<div class="flex items-center gap-3">
							<div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $ec['bg'] }}">
								<iconify-icon icon="{{ $ec['icon'] }}" width="16" class="{{ $ec['text'] }}"></iconify-icon>
							</div>
							<div class="text-left">
								<p class="text-sm font-semibold text-gray-800 dark:text-gray-100">{{ $category->name }}</p>
								<p class="text-xs text-gray-500 dark:text-gray-400">{{ __('Manage required statuses') }}</p>
							</div>
						</div>
						<div class="flex items-center gap-3">
							<span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $ec['badge'] }}"
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
									? '{{ $ec['on'] }}'
									: '{{ $ec['off'] }}'"
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
					$savedSvcs     = collect(old('services', $customerServices))->map(fn($id) => (int) $id)->values();
				// If no services saved yet, default to category 1 (same as create)
				$editSelSvcIds = $savedSvcs->isEmpty()
					? $services->where('service_category_id', 1)->pluck('id')->values()->toJson()
					: $savedSvcs->toJson();
					$editTotalSvc  = $services->count();
				@endphp
				<div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden bg-white dark:bg-gray-800"
					x-data="{
						open: false,
						selectedIds: {{ $editSelSvcIds }},
						total: {{ $editTotalSvc }},
						toggle(id) { const i=this.selectedIds.indexOf(id); i>-1?this.selectedIds.splice(i,1):this.selectedIds.push(id); },
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

			</div>{{-- /activeTab === edit --}}

			<!-- Submit Button -->
			<div class="flex justify-end gap-4">
				<a href="{{ route('admin.customers.index') }}" class="btn btn-secondary">
					{{ __('Cancel') }}
				</a>
				<button type="submit" class="btn btn-primary">
					{{ __('Update Customer') }}
				</button>
			</div>
		</x-card>
	</form>
</div>