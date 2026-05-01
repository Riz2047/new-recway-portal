<div class="flex flex-wrap gap-2">
    @forelse($serviceType->customers as $customer)
        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
            {{ $customer->name }}
        </span>
    @empty
        <span class="text-xs text-gray-400 dark:text-gray-500 italic">{{ __('None') }}</span>
    @endforelse
</div>
