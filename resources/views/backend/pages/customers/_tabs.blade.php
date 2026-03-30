@props(['customer'])

@php
    $tabs = [
        'profile' => ['label' => __('Profile')],
        'edit' => ['label' => __('Edit Customer')],
        'status_manager' => ['label' => __('Status Manager')],
        'billing' => ['label' => __('Standard Billing Details')],
        'departments' => ['label' => __('Departments')],
        'department_users' => ['label' => __('Department Users')],
        'orders' => ['label' => __('Orders')],
        'emails' => ['label' => __('Emails')],
        'messages' => ['label' => __('Messages')],
        'invoiced' => ['label' => __('Invoiced')],
        'background_questions' => ['label' => __('Background Questions')],
        'form_builder' => ['label' => __('Form Builder')],
        'reports' => ['label' => __('Reports')],
        'additional_customers' => ['label' => __('Additional Customers')],
        'reminder_emails' => ['label' => __('Reminder Emails')],
        'service_cost' => ['label' => __('Service Cost')],
    ];
@endphp

<div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
    <nav class="flex space-x-4 min-w-max">
        @foreach($tabs as $key => $tab)
            <button
                type="button"
                @click="
											activeTab = '{{ $key }}';
											if (typeof window.loadCustomerTabData === 'function') {
													window.loadCustomerTabData('{{ $key }}');
											}
									"
                :class="activeTab === '{{ $key }}'
                    ? 'px-4 py-3 text-sm font-medium border-b-2 border-primary text-primary'
                    : 'px-4 py-3 text-sm font-medium border-b-2 border-transparent text-gray-600 hover:text-primary'"
            >
                {{ $tab['label'] }}
            </button>
        @endforeach
    </nav>
</div>