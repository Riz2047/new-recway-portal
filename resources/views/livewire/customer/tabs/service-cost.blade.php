<div class="py-6">

    {{-- Header --}}
    <div class="mb-5 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900 dark:text-gray-100">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            {{ __('Service Cost') }}
            @if(count($services))
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-900 dark:text-indigo-300">
                    {{ count($services) }}
                </span>
            @endif
        </h3>
        <p class="text-xs text-gray-400 dark:text-gray-500">
            {{ __('Override the default price per service for this customer.') }}
        </p>
    </div>

    @if(empty($services))
        {{-- No services assigned --}}
        <div class="rounded-xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-center dark:border-gray-700 dark:bg-gray-800/30">
            <svg class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <p class="text-sm font-medium text-gray-500 dark:text-gray-400">{{ __('No services assigned to this customer.') }}</p>
            <p class="mt-1 text-xs text-gray-400">{{ __('Assign services in the Edit tab first.') }}</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <table class="w-full border-collapse text-sm">
                <thead class="bg-gray-50 dark:bg-gray-800">
                    <tr>
                        <th class="border-b border-gray-200 px-5 py-3 text-left text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ __('Service') }}
                        </th>
                        <th class="border-b border-gray-200 px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ __('Default Price') }}
                        </th>
                        <th class="border-b border-gray-200 px-5 py-3 text-right text-xs font-medium uppercase tracking-wide text-gray-500 dark:border-gray-700 dark:text-gray-400">
                            {{ __('Customer Price') }}
                        </th>
                        <th class="w-10 border-b border-gray-200 px-2 py-3 dark:border-gray-700"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($services as $i => $service)
                        <tr wire:key="svc-{{ $service['id'] }}" class="border-b border-gray-100 last:border-0 dark:border-gray-700">

                            {{-- Service name --}}
                            <td class="px-5 py-3.5">
                                <span class="font-medium text-gray-800 dark:text-gray-200">{{ $service['name'] }}</span>
                            </td>

                            {{-- Default price (read-only) --}}
                            <td class="px-5 py-3.5 text-right">
                                @if($service['default_price'] !== null)
                                    <span class="text-gray-500 dark:text-gray-400">
                                        {{ number_format((float) $service['default_price'], 0) }} kr
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>

                            {{-- Customer price (editable) --}}
                            <td class="px-5 py-3.5 text-right">
                                @php
                                    $isOverridden = $service['default_price'] !== null
                                        && $service['cost'] !== ''
                                        && (float) $service['cost'] !== (float) $service['default_price'];
                                @endphp
                                <div class="inline-flex items-center justify-end gap-1.5">
                                    <div class="relative">
                                        <input
                                            type="number"
                                            wire:model.defer="services.{{ $i }}.cost"
                                            min="0"
                                            step="1"
                                            placeholder="{{ $service['default_price'] ?? '0' }}"
                                            @class([
                                                'w-28 rounded-lg border px-3 py-1.5 text-right text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500',
                                                'border-indigo-400 bg-indigo-50 font-semibold text-indigo-800 dark:border-indigo-600 dark:bg-indigo-950/30 dark:text-indigo-200' => $isOverridden,
                                                'border-gray-300 bg-gray-50 text-gray-800 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200' => ! $isOverridden,
                                            ])
                                        />
                                        <span class="absolute right-2.5 top-1/2 -translate-y-1/2 text-[10px] font-medium text-gray-400 pointer-events-none">kr</span>
                                    </div>

                                    {{-- Overridden badge --}}
                                    @if($isOverridden)
                                        <span class="rounded-full bg-indigo-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wide text-indigo-600 dark:bg-indigo-900 dark:text-indigo-300">
                                            {{ __('custom') }}
                                        </span>
                                    @endif
                                </div>
                                @error("services.{$i}.cost")
                                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                @enderror
                            </td>

                            {{-- Reset to default --}}
                            <td class="px-2 py-3.5 text-center">
                                @if($isOverridden)
                                    <button
                                        type="button"
                                        wire:click="resetCost({{ $i }})"
                                        class="rounded p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300"
                                        title="{{ __('Reset to default') }}"
                                    >
                                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 14 14" stroke="currentColor" stroke-width="1.6">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2 7a5 5 0 1 0 1-2.9M2 2v4h4"/>
                                        </svg>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        {{-- Save button --}}
        <div class="mt-5 flex items-center justify-between">
            <p class="text-xs text-gray-400 dark:text-gray-500">
                {{ __('Highlighted rows have a custom price that overrides the default.') }}
            </p>
            <button
                type="button"
                wire:click="save"
                wire:loading.attr="disabled"
                class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-6 py-2 text-sm font-medium text-white transition hover:bg-indigo-700 disabled:opacity-60"
            >
                <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M8 2a6 6 0 1 0 0 12A6 6 0 0 0 8 2Z" opacity=".25"/>
                    <path d="M14 8a6 6 0 0 0-6-6" stroke-linecap="round"/>
                </svg>
                <span wire:loading.remove wire:target="save">{{ __('Save Prices') }}</span>
                <span wire:loading wire:target="save">{{ __('Saving…') }}</span>
            </button>
        </div>
    @endif
</div>
