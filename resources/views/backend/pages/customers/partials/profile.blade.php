<div class="mb-6 space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="space-y-2">
            <p class="text-sm text-gray-500">{{ __('Email') }}</p>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->user->email }}</p>

            <p class="mt-4 text-sm text-gray-500">{{ __('Phone') }}</p>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->phone ?? __('N/A') }}</p>

            <p class="mt-4 text-sm text-gray-500">{{ __('Company') }}</p>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->company }}</p>

            <p class="mt-4 text-sm text-gray-500">{{ __('Organization Number') }}</p>
            <p class="font-medium text-gray-900 dark:text-gray-100">{{ $customer->org_no }}</p>

            <p class="mt-4 text-sm text-gray-500">{{ __('Last Login') }}</p>
            <p class="font-medium text-gray-900 dark:text-gray-100">
                {{ $customer->last_login ?? __('Never') }}
            </p>
        </div>

        <div class="flex items-center justify-center">
            <div class="text-center">
                <div
                    class="mx-auto mb-3 flex h-24 w-24 items-center justify-center rounded-full bg-gray-700 text-2xl font-semibold text-white">
                    {{ strtoupper(substr($customer->user->name, 0, 1)) }}
                </div>
                <p class="font-semibold text-gray-900 dark:text-gray-100">{{ $customer->user->name }}</p>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-6">
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Total Invoiced') }}</p>
            <p class="mt-2 text-2xl font-semibold text-primary">0</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Total Approved') }}</p>
            <p class="mt-2 text-2xl font-semibold text-primary">0</p>
        </div>
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ __('Total Canceled') }}</p>
            <p class="mt-2 text-2xl font-semibold text-primary">0</p>
        </div>
    </div>
</div>



