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
										@foreach($services->where('service_category_id', 2) as $service)
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
			<div x-show="activeTab === 'edit'" x-cloak class="border-l-4 border-blue-500 bg-gray-50 dark:bg-gray-800 p-6 rounded">
				<h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
					<iconify-icon icon="lucide:shield-check" class="w-5 h-5"></iconify-icon>
					{{ __('Permissions & Services') }}
				</h3>
				<div class="space-y-6">
					<!-- Permissions -->
					@if($permissions->count() > 0)
					<div
						class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
						x-data="{ open: false }">
						<button
							type="button"
							@click="open = !open"
							class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 flex items-center justify-between transition-colors">
							<span>{{ __('Permissions') }}</span>
							<iconify-icon
								icon="lucide:chevron-down"
								class="w-5 h-5 transition-transform duration-200"
								:class="{ 'rotate-180': open }"></iconify-icon>
						</button>
						<div
							x-show="open"
							x-transition:enter="transition ease-out duration-200"
							x-transition:enter-start="opacity-0"
							x-transition:enter-end="opacity-100"
							x-transition:leave="transition ease-in duration-150"
							x-transition:leave-start="opacity-100"
							x-transition:leave-end="opacity-0"
							class="bg-white dark:bg-gray-900 p-4">
							<div class="space-y-0">
								@foreach($permissions as $permission)
								<div class="form-check py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
									<input
										class="form-check-input"
										type="checkbox"
										name="permissions[]"
										value="{{ $permission->id }}"
										id="permission_{{ $permission->id }}"
										{{ in_array($permission->id, old('permissions', $customerPermissions)) ? 'checked' : '' }}>
									<label class="form-check-label form-label" for="permission_{{ $permission->id }}">
										{{ $permission->title }}
									</label>
								</div>
								@endforeach
							</div>
						</div>
					</div>
					@endif

					<!-- Status Required by Service Category -->
					@foreach($serviceCategories as $category)
					@if(isset($statusesByCategory[$category->id]) && $statusesByCategory[$category->id]->count() > 0)
					<div>
						<h4 class="text-md font-medium mb-3">{{ __('Status Required') }} - {{ $category->name }}</h4>
						<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3 max-h-60 overflow-y-auto border border-gray-200 dark:border-gray-700 p-4 rounded">
							@foreach($statusesByCategory[$category->id] as $status)
							<label class="flex items-center gap-2">
								<input
									type="checkbox"
									name="statuses[]"
									value="{{ $status->id }}"
									{{ in_array($status->id, old('statuses', $customerStatuses)) ? 'checked' : '' }}>
								<span>{{ $status->status }}</span>
							</label>
							@endforeach
						</div>
					</div>
					@endif
					@endforeach

					<!-- Allowed Services -->
					@if($services->count() > 0)
					<div
						class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden"
						x-data="{ open: false }">
						<button
							type="button"
							@click="open = !open"
							class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 flex items-center justify-between transition-colors">
							<span>{{ __('Allowed Services') }}</span>
							<iconify-icon
								icon="lucide:chevron-down"
								class="w-5 h-5 transition-transform duration-200"
								:class="{ 'rotate-180': open }"></iconify-icon>
						</button>
						<div
							x-show="open"
							x-transition:enter="transition ease-out duration-200"
							x-transition:enter-start="opacity-0"
							x-transition:enter-end="opacity-100"
							x-transition:leave="transition ease-in duration-150"
							x-transition:leave-start="opacity-100"
							x-transition:leave-end="opacity-0"
							class="bg-white dark:bg-gray-900 p-4">
							<div class="space-y-0">
								@foreach($services as $service)
								<div class="form-check py-2 border-b border-gray-100 dark:border-gray-800 last:border-0">
									<input
										class="form-check-input service_checkbox"
										type="checkbox"
										name="services[]"
										value="{{ $service->id }}"
										id="service_{{ $service->id }}"
										{{ in_array($service->id, old('services', $customerServices)) ? 'checked' : '' }}>
									<label class="form-check-label form-label" for="service_{{ $service->id }}">
										{{ $service->name }}
									</label>
								</div>
								@endforeach
							</div>
						</div>
					</div>
					@endif

					{{-- Other tabs, each with its own partial --}}

				</div>
			</div>

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