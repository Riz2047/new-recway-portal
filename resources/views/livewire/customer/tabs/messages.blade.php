
<div class="py-6">
    {{-- Top bar --}}
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2.5">
            <span class="w-2 h-2 rounded-full bg-indigo-600 inline-block"></span>
            Messages
        </h3>
        <span class="text-xs text-gray-500 bg-gray-100 border border-gray-200 px-3 py-1 rounded-full">
            Service: {{ $selectedService }}
        </span>
    </div>

    {{-- Toolbar --}}
    <div class="bg-white border border-gray-200 rounded-xl p-4 grid grid-cols-4 gap-3 items-end mb-6">
        <div>
            <label class="block text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                Service Type
            </label>
            <select wire:model.live="selectedService" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select Service</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                Copy From Customer
            </label>
            <select wire:model.live="copyCustomer" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($customers as $cus)
                    <option value="{{ $cus->id }}">{{ $cus->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-medium text-gray-500 uppercase tracking-wide mb-1.5">
                Copy From Service
            </label>
            <select wire:model.live="copyService" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 bg-gray-50 text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($copyServices as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <button wire:click="copyMessages"
                class="w-full flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                    <rect x="5" y="5" width="9" height="9" rx="1.5"/>
                    <path d="M3 11V3a1 1 0 0 1 1-1h8"/>
                </svg>
                Copy
            </button>
        </div>
    </div>

    {{-- Messages --}}
    <div wire:key="service-{{ $selectedService }}">
        @if(!empty($columns))

            {{-- Section header --}}
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-medium text-gray-400 uppercase tracking-wider">
                    Message fields
                </span>
                <span class="text-[11px] text-gray-400 bg-gray-100 border border-gray-200 px-2.5 py-0.5 rounded-full">
                    {{ count($columns) }} fields
                </span>
            </div>

            {{-- Grid --}}
            <div class="grid grid-cols-2 gap-3 mb-6">
                @foreach($columns as $col)
                    @php
                        $val        = $messageValues[$col] ?? '';
                        $hasContent = !empty(trim(strip_tags($val)));
                        $preview    = $hasContent ? Str::limit(strip_tags($val), 90) : 'No content yet';
                        $label      = $columnLabels[$col] ?? $col;
                    @endphp

                    <div wire:key="col-{{ $col }}"
                         x-data="{ open: false }"
                         :class="open ? 'border-indigo-300 ring-1 ring-indigo-200' : 'border-gray-200 hover:border-gray-300'"
                         class="bg-white border rounded-xl overflow-hidden transition-all duration-150">

                        {{-- Card header --}}
                        <div class="flex items-center justify-between px-3.5 py-2.5 bg-gray-50 border-b border-gray-200">
                            <span class="flex items-center gap-2 text-xs font-medium text-gray-700">
                                <span class="w-1.5 h-1.5 rounded-full flex-shrink-0 {{ $hasContent ? 'bg-green-500' : 'bg-gray-300' }}"></span>
                                {{ $label }}
                            </span>
                            <button @click="open = !open" type="button"
                                class="flex items-center gap-1.5 text-[11px] text-gray-500 hover:text-gray-900 hover:bg-white px-2 py-1 rounded-md transition-colors">
                                <svg class="w-3 h-3" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                                    <path d="M11 2.5a1.5 1.5 0 0 1 2.5 1.5L5 13l-3 1 1-3 8.5-8.5Z"/>
                                </svg>
                                <span x-text="open ? 'Done' : 'Edit'">Edit</span>
                            </button>
                        </div>

                        {{-- Preview --}}
                        <div x-show="!open" class="px-3.5 py-2.5 text-xs leading-relaxed min-h-[56px] {{ $hasContent ? 'text-gray-500' : 'text-gray-300 italic' }}">
                            {{ $preview }}
                        </div>

                        {{-- Editor --}}
                        <div x-show="open" class="px-3.5 py-2.5">
                            <textarea
                                wire:model.defer="messageValues.{{ $col }}"
                                rows="5"
                                class="w-full text-xs font-mono bg-transparent border-none resize-y focus:outline-none text-gray-800 leading-relaxed"
                            ></textarea>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Footer --}}
            <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                <span class="text-xs text-gray-400">Click any field to edit</span>
                <button wire:click="saveMessages"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path d="M3 8.5 6.5 12 13 5"/>
                    </svg>
                    Save changes
                </button>
            </div>

        @else
            <p class="text-sm text-gray-400 italic">No fields available for this service.</p>
        @endif
    </div>
</div>
