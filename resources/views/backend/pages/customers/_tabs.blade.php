@props([
    'customer',
])

@php
    $tabs = [
        'profile' => [
            'label' => __('Profile'),
            'route' => route('admin.customers.index') . '?focus=' . $customer->id, // placeholder until dedicated profile page exists
            'enabled' => true,
        ],
        'edit' => [
            'label' => __('Edit Customer'),
            'route' => route('admin.customers.edit', $customer->id),
            'enabled' => true,
        ],
        'status_manager' => [
            'label' => __('Status Manager'),
            'route' => '#',
            'enabled' => true,
        ],
        'billing' => [
            'label' => __('Standard Billing Details'),
            'route' => '#billing-details',
            'enabled' => true,
        ],
        'departments' => [
            'label' => __('Departments'),
            'route' => '#',
            'enabled' => true,
        ],
        'department_users' => [
            'label' => __('Department Users'),
            'route' => '#',
            'enabled' => true,
        ],
        'orders' => [
            'label' => __('Orders'),
            'route' => '#',
            'enabled' => true,
        ],
        'emails' => [
            'label' => __('Emails'),
            'route' => '#',
            'enabled' => true,
        ],
        'messages' => [
            'label' => __('Messages'),
            'route' => '#',
            'enabled' => true,
        ],
        'invoiced' => [
            'label' => __('Invoiced'),
            'route' => '#',
            'enabled' => true,
        ],
        'background_questions' => [
            'label' => __('Background Questions'),
            'route' => '#',
            'enabled' => true,
        ],
        'form_builder' => [
            'label' => __('Form Builder'),
            'route' => '#',
            'enabled' => true,
        ],
        'reports' => [
            'label' => __('Reports'),
            'route' => '#',
            'enabled' => true,
        ],
        'additional_customers' => [
            'label' => __('Additional Customers'),
            'route' => '#',
            'enabled' => true,
        ],
        'reminder_emails' => [
            'label' => __('Reminder Emails'),
            'route' => '#',
            'enabled' => true,
        ],
        'service_cost' => [
            'label' => __('Service Cost'),
            'route' => '#',
            'enabled' => true,
        ],
    ];
@endphp

<div class="mb-6 border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
    <nav class="flex space-x-4 min-w-max" aria-label="Customer tabs">
        @foreach($tabs as $key => $tab)
            @php
                $baseClasses = 'whitespace-nowrap px-4 py-3 text-sm font-medium border-b-2 transition-colors';
                $activeClasses = 'border-primary text-primary';
                $inactiveClasses = 'border-transparent text-gray-600 hover:text-primary hover:border-primary dark:text-gray-300';
                $disabledClasses = 'border-transparent text-gray-400 cursor-not-allowed';
            @endphp

            @if($tab['enabled'])
                <button
                    type="button"
                    @click="activeTab = '{{ $key }}'"
                    :class="activeTab === '{{ $key }}' ? '{{ $baseClasses }} {{ $activeClasses }}' : '{{ $baseClasses }} {{ $inactiveClasses }}'"
                >
                    {{ $tab['label'] }}
                </button>
            @else
                <span
                    class="{{ $baseClasses }} {{ $disabledClasses }}"
                    title="{{ __('Coming soon') }}"
                >
                    {{ $tab['label'] }}
                </span>
            @endif
        @endforeach
    </nav>
</div>


