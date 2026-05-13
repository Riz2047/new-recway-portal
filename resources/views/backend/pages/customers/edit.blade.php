<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
	<div x-data="{ activeTab: 'profile' }">

		@include('backend.pages.customers._tabs', ['customer' => $customer])

		<div class="mt-4">
			{{-- PROFILE --}}
			<div x-show="activeTab === 'profile'" x-cloak>
				@include('backend.pages.customers.partials.profile')
			</div>

			{{-- EDIT --}}
			<div x-show="activeTab === 'edit'" x-cloak>
				@include('backend.pages.customers.partials.edit')
			</div>

			<div x-show="activeTab === 'billing'" x-cloak>
				<livewire:customer.tabs.billing :customerId="$customer->id" />
			</div>

			<div x-show="activeTab === 'status_manager'" x-cloak>
				<livewire:customer.tabs.status-manager :customerId="$customer->id" />
			</div>

			<div x-show="activeTab === 'messages'" x-cloak>
					<livewire:customer.tabs.messages :customerId="$customer->id" />
			</div>

			<div x-show="activeTab === 'form_builder'" x-cloak>
					<livewire:customer.tabs.form-builder :customerId="$customer->id" />
			</div>

			<div x-show="activeTab === 'reports'" x-cloak>
					<livewire:customer.tabs.reports :customerId="$customer->id" />
			</div>

			<div x-show="activeTab === 'additional_customers'" x-cloak>
					<livewire:customer.tabs.additional-customers :customerId="$customer->id" />
			</div>

			{{-- DEPARTMENTS --}}
			<div x-show="activeTab === 'departments'" x-cloak>
				@include('backend.pages.customers.partials.departments')
			</div>

			{{-- DEPARTMENT USERS --}}
			<div x-show="activeTab === 'department_users'" x-cloak>
				@include('backend.pages.customers.partials.department-users')
			</div>

			{{-- ORDERS --}}
			<div x-show="activeTab === 'orders'" x-cloak>
				<livewire:customer.tabs.order :customerId="$customer->id" />
			</div>

			{{-- EMAILS --}}
			<div x-show="activeTab === 'emails'" x-cloak>
				<livewire:customer.tabs.emails :customerId="$customer->id" />
			</div>

			{{-- INVOICED --}}
			<div x-show="activeTab === 'invoiced'" x-cloak>
				<livewire:customer.tabs.invoiced :customerId="$customer->id" />
			</div>

			{{-- BACKGROUND QUESTIONS --}}
			<div x-show="activeTab === 'background_questions'" x-cloak>
				<livewire:customer.tabs.background-questions :customerId="$customer->id" />
			</div>

			{{-- REMINDER EMAILS --}}
			<div x-show="activeTab === 'reminder_emails'" x-cloak>
				<livewire:customer.tabs.reminder-emails :customerId="$customer->id" />
			</div>

			{{-- SERVICE COST --}}
			<div x-show="activeTab === 'service_cost'" x-cloak>
				<livewire:customer.tabs.service-cost :customerId="$customer->id" />
			</div>

		</div>
	</div>
</x-layouts.backend-layout>

@push('scripts')
<script>
	// ✅ INIT AFTER DOM READY
	document.addEventListener('DOMContentLoaded', function() {
		// Select2
		$('.js-multiselect').select2({
			width: '100%',
			closeOnSelect: false,
			placeholder: '{{ __("Select options") }}'
		});

	});

	// Define function before DOMContentLoaded so it's available
	function toggleCombineServices(show) {
		const section = document.getElementById('combine_services_section');
		if (section) {
			if (show) {
				section.style.display = 'block';
			} else {
				section.style.display = 'none';
				// Clear selections
				const interviewServiceSelect = document.getElementById('combine_interview_service');
				const servicesSelect = document.getElementById('combine_bk_and_security_services');
				const statusesSelect = document.getElementById('combine_statuses');
				if (interviewServiceSelect) interviewServiceSelect.value = '';
				if (servicesSelect) {
					// Clear all selected options in multi-select
					Array.from(servicesSelect.options).forEach(option => {
						option.selected = false;
					});
				}
				if (statusesSelect) {
					// Clear all selected options in multi-select
					Array.from(statusesSelect.options).forEach(option => {
						option.selected = false;
					});
				}
			}
		}
	}

	// Run setup immediately (script is loaded after DOM in layout)
	(function initCustomerEditPage() {
		// Load tab data lazily for better performance.
		// document.querySelectorAll('[data-customer-tab]').forEach((tabButton) => {
		//     tabButton.addEventListener('click', function () {
		//         const tab = this.getAttribute('data-customer-tab');
		//         if (tab) {
		//             loadCustomerTabData(tab);
		//         }
		//     });
		// });

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

		// Toggle Combine Services
		const combineCheckbox = document.getElementById('combine_bk_and_security');
		if (combineCheckbox) {
			// Add event listener for change event
			combineCheckbox.addEventListener('change', function() {
				toggleCombineServices(this.checked);
			});
			// Also add click event as fallback
			combineCheckbox.addEventListener('click', function() {
				toggleCombineServices(this.checked);
			});
			// Initialize on page load
			toggleCombineServices(combineCheckbox.checked);
		}

		// Load parent customer data
		const parentCustomerSelect = document.getElementById('parent_customer');
		if (parentCustomerSelect) {
			parentCustomerSelect.addEventListener('change', function() {
				loadParentCustomerData(this.value);
			});
		}

		// Prefetch billing tab first as it contains editable form inputs.
		loadCustomerTabData('billing');
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
			return;
		}

		fetch(`{{ route('admin.customers.get-parent-data') }}?parent_id=${parentId}`)
			.then(response => response.json())
			.then(data => {
				if (data.success) {
					if (data.customer.invoice_period) {
						const invoicePeriod = document.getElementById('invoice_period');
						if (invoicePeriod) invoicePeriod.value = data.customer.invoice_period;
					}
					if (data.customer.interview_upload_allowed == 1) {
						const interviewUpload = document.getElementById('interview_upload_allowed');
						if (interviewUpload) interviewUpload.checked = true;
					}
					if (data.statuses && data.statuses.length > 0) {
						document.querySelectorAll('input[name="statuses[]"]').forEach(checkbox => {
							checkbox.checked = data.statuses.includes(checkbox.value);
						});
					}
					if (data.services && data.services.length > 0) {
						document.querySelectorAll('.service_checkbox').forEach(checkbox => {
							checkbox.checked = data.services.includes(parseInt(checkbox.value, 10));
						});
					}
					if (data.permissions && data.permissions.length > 0) {
						document.querySelectorAll('input[name="permissions[]"]').forEach(checkbox => {
							checkbox.checked = data.permissions.includes(parseInt(checkbox.value));
						});
					}
				}
			})
			.catch(error => console.error('Error:', error));
	}
</script>
@endpush