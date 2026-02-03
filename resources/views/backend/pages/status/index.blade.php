<x-layouts.backend-layout :breadcrumbs="$breadcrumbs">
    <div class="mb-6 hidden">
        <!-- Blue gradient header bar -->
        <div x-data="{ dropdownOpen: false }" class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg px-6 py-4 flex items-center justify-between mb-4 relative">
            <h2 class="text-xl font-semibold text-white">
                {{ __('Statuses') }} - {{ $serviceCategory->name }}
            </h2>
            <div class="flex items-center gap-2">
                @can('status.create')
                    <a href="{{ route('admin.status.create', $serviceCategory->id) }}" class="bg-white text-blue-600 hover:bg-gray-100 rounded-md p-2 flex items-center justify-center transition-colors" title="{{ __('New Status') }}">
                        <iconify-icon icon="lucide:plus" class="w-5 h-5"></iconify-icon>
                    </a>
                @endcan
            </div>
        </div>
    </div>

    <x-card>
        @if($statuses->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Status') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Status (Swedish)') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Variable') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Color') }}
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                {{ __('Actions') }}
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($statuses as $status)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $status->status }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    {{ $status->status_sv ?? '-' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <code class="text-xs bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded">{{ $status->variable }}</code>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($status->color)
                                        <div class="w-6 h-6 rounded-full border border-gray-300 dark:border-gray-600" style="background-color: {{ $status->color }}"></div>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        @can('status.edit')
                                            <a href="{{ route('admin.status.edit', [$serviceCategory->id, $status->id]) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300">
                                                <iconify-icon icon="lucide:pencil" class="w-4 h-4"></iconify-icon>
                                            </a>
                                        @endcan
                                        @can('status.delete')
                                            <form action="{{ route('admin.status.destroy', [$serviceCategory->id, $status->id]) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this status?') }}');" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                    <iconify-icon icon="lucide:trash" class="w-4 h-4"></iconify-icon>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12">
                <iconify-icon icon="lucide:inbox" class="w-16 h-16 mx-auto text-gray-400 mb-4"></iconify-icon>
                <p class="text-gray-500 dark:text-gray-400">{{ __('No statuses found.') }}</p>
                @can('status.create')
                    <a href="{{ route('admin.status.create', $serviceCategory->id) }}" class="mt-4 inline-block btn-primary">
                        {{ __('Create First Status') }}
                    </a>
                @endcan
            </div>
        @endif
    </x-card>
</x-layouts.backend-layout>

