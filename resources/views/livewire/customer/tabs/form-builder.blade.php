<div class="py-6" x-data="{
    showAddField: false,
    dragSection: null,
    dragIndex: null,
    startDrag(section, index) {
        this.dragSection = section;
        this.dragIndex = index;
    },
    clearDrag() {
        this.dragSection = null;
        this.dragIndex = null;
    },
    dropOn(section, index) {
        if (this.dragSection !== section || this.dragIndex === null || this.dragIndex === index) {
            this.clearDrag();
            return;
        }

        $wire.moveFieldTo(section, this.dragIndex, index);
        this.clearDrag();
    }
}">
    <div class="mb-6 flex items-center justify-between">
        <h3 class="flex items-center gap-2.5 text-lg font-medium text-gray-900">
            <span class="inline-block h-2 w-2 rounded-full bg-indigo-600"></span>
            Form Builder
        </h3>
        <button type="button" @click="showAddField = !showAddField"
            class="rounded-lg border border-indigo-300 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-indigo-700 hover:bg-indigo-50">
            Add Field
        </button>
    </div>

    <div class="mb-6 grid grid-cols-3 gap-3 rounded-xl border border-gray-200 bg-white p-4">
        <div>
            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Service Type</label>
            <select wire:model.live="selectedService"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select service</option>
                @foreach($services as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Copy From Customer</label>
            <select wire:model.live="copyCustomer"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1.5 block text-[11px] font-medium uppercase tracking-wide text-gray-500">Copy From Service</label>
            <select wire:model.live="copyService"
                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="">Select</option>
                @foreach($copyServices as $service)
                    <option value="{{ $service->id }}">{{ $service->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div x-show="showAddField" x-cloak class="mb-6 rounded-xl border-2 border-gray-300 bg-white p-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Label</label>
                <input type="text" wire:model.live.debounce.300ms="newField.label"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Type</label>
                <select wire:model.live="newField.type"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="text">Text</option>
                    <option value="email">Email</option>
                    <option value="number">Number</option>
                    <option value="select">Droplist</option>
                </select>
            </div>

            <div class="col-span-2">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Placeholder</label>
                <input type="text" wire:model.live.debounce.300ms="newField.placeholder"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>

            <div class="col-span-2" x-show="$wire.newField.type === 'select'">
                <label class="mb-1.5 block text-sm font-medium text-gray-700">Options (separated by |)</label>
                <input type="text" wire:model.live.debounce.300ms="newField.options"
                    placeholder="Option 1|Option 2|Option 3"
                    class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>

            <div class="col-span-2 flex items-center gap-2">
                <input id="required_field" type="checkbox" wire:model.live="newField.required" class="rounded border-gray-300 text-indigo-600">
                <label for="required_field" class="text-sm text-gray-700">Required</label>
            </div>
        </div>

        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button type="button" wire:click="addDefaultField('note')"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Note</button>
            <button type="button" wire:click="addDefaultField('comment')"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Invoice Comment</button>
            <button type="button" wire:click="addDefaultField('document_file')"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">Document</button>
            <button type="button" wire:click="addDefaultField('vasc_id')"
                class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">VASC ID</button>
            <button type="button" wire:click="addDefaultField('social_security_number')"
                title="Adds a Social Security Number field. When rendered in the candidate form, it shows a 'Has Personal ID' checkbox — unchecked = date of birth picker, checked = PNR text input. Both save to the security column."
                class="rounded-lg border border-green-300 px-3 py-1.5 text-xs font-medium text-green-700 hover:bg-green-50">SSN / Date of Birth</button>
            <button type="button" wire:click="addCustomField('personal_info')"
                class="rounded-lg border border-indigo-300 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Add To Personal Info</button>
            <button type="button" wire:click="addCustomField('billing_info')"
                class="rounded-lg border border-yellow-400 px-3 py-1.5 text-xs font-semibold text-yellow-700 hover:bg-yellow-50">Add To Billing Info</button>
        </div>
    </div>

    <div class="rounded-xl border border-gray-300 bg-white p-4">
        @if(empty($formSections['personal_info']) && empty($formSections['billing_info']))
            <div class="mb-4 rounded-lg border border-dashed border-indigo-300 bg-indigo-50 p-4 text-sm text-indigo-800">
                No form fields found for this customer and service type. Add form fields and save.
            </div>
        @endif

        <div class="mb-4">
            <h5 class="mb-2 bg-gray-200 px-2 py-1 text-sm font-semibold text-gray-700">Personal Info</h5>
            <div class="space-y-2">
                @foreach($formSections['personal_info'] as $index => $field)
                    <div
                        class="rounded-lg border border-gray-200 p-3 transition"
                        draggable="true"
                        @dragstart="startDrag('personal_info', {{ $index }})"
                        @dragend="clearDrag()"
                        @dragover.prevent
                        @drop.prevent="dropOn('personal_info', {{ $index }})"
                        :class="dragSection === 'personal_info' && dragIndex === {{ $index }} ? 'opacity-60 ring-2 ring-indigo-300' : ''"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-800"><span class="mr-1 cursor-move select-none text-gray-400">::</span>{{ $field['label'] }} @if(!empty($field['required']))<span class="text-red-500">*</span>@endif</span>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="moveFieldUp('personal_info', {{ $index }})" class="text-xs text-gray-600 hover:text-indigo-700" @disabled($index === 0)>Up</button>
                                <button type="button" wire:click="moveFieldDown('personal_info', {{ $index }})" class="text-xs text-gray-600 hover:text-indigo-700" @disabled($index === count($formSections['personal_info']) - 1)>Down</button>
                                <button type="button" wire:click="removeField('personal_info', {{ $index }})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                        @if(($field['type'] ?? 'text') === 'select')
                            @php($opts = collect(explode('|', (string)($field['options'] ?? '')))->map(fn ($opt) => trim($opt))->filter())
                            <select disabled class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                                <option>{{ $field['placeholder'] ?: 'Select option' }}</option>
                                @foreach($opts as $opt)
                                    <option>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @else
                            <input disabled type="{{ $field['type'] ?: 'text' }}" value="{{ $field['value'] ?? '' }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        <div>
            <h5 class="mb-2 bg-gray-200 px-2 py-1 text-sm font-semibold text-gray-700">Billing Info</h5>
            <div class="space-y-2">
                @foreach($formSections['billing_info'] as $index => $field)
                    <div
                        class="rounded-lg border border-gray-200 p-3 transition"
                        draggable="true"
                        @dragstart="startDrag('billing_info', {{ $index }})"
                        @dragend="clearDrag()"
                        @dragover.prevent
                        @drop.prevent="dropOn('billing_info', {{ $index }})"
                        :class="dragSection === 'billing_info' && dragIndex === {{ $index }} ? 'opacity-60 ring-2 ring-indigo-300' : ''"
                    >
                        <div class="mb-1 flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-800"><span class="mr-1 cursor-move select-none text-gray-400">::</span>{{ $field['label'] }} @if(!empty($field['required']))<span class="text-red-500">*</span>@endif</span>
                            <div class="flex items-center gap-2">
                                <button type="button" wire:click="moveFieldUp('billing_info', {{ $index }})" class="text-xs text-gray-600 hover:text-indigo-700" @disabled($index === 0)>Up</button>
                                <button type="button" wire:click="moveFieldDown('billing_info', {{ $index }})" class="text-xs text-gray-600 hover:text-indigo-700" @disabled($index === count($formSections['billing_info']) - 1)>Down</button>
                                <button type="button" wire:click="removeField('billing_info', {{ $index }})" class="text-xs text-red-500 hover:text-red-700">Remove</button>
                            </div>
                        </div>
                        @if(($field['type'] ?? 'text') === 'select')
                            @php($opts = collect(explode('|', (string)($field['options'] ?? '')))->map(fn ($opt) => trim($opt))->filter())
                            <select disabled class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                                <option>{{ $field['placeholder'] ?: 'Select option' }}</option>
                                @foreach($opts as $opt)
                                    <option>{{ $opt }}</option>
                                @endforeach
                            </select>
                        @else
                            <input disabled type="{{ $field['type'] ?: 'text' }}" value="{{ $field['value'] ?? '' }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                class="w-full rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm">
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <div class="mt-4 flex justify-end">
        <button type="button" wire:click="saveFormBuilder"
            class="rounded-lg bg-indigo-600 px-5 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            Save
        </button>
    </div>
</div>
