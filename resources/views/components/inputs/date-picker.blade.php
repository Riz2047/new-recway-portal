@props([
    'label' => null,
    'name' => null,
    'id' => null,
    'value' => null,
    'placeholder' => 'Select date',
    'hint' => null,
    'required' => false,
    'disabled' => false,
    'min' => null,
    'max' => null,
    'altFormat' => 'F j, Y',
    'showAltFormat' => false,
])

@php
    $id = $id ?? $name;
@endphp

<div
    x-data="{
        picker: null,
        init() {
            // Wait a tick so wire:model / x-model have applied their initial value
            // to the input before flatpickr reads it — keeps the calendar in sync.
            this.$nextTick(() => {
                this.picker = flatpickr(this.$refs.input, {
                    dateFormat: 'Y-m-d',
                    altInput: {{ $showAltFormat ? 'true' : 'false' }},
                    altFormat: '{{ $altFormat }}',
                    allowInput: true,
                    disableMobile: true,
                    static: true,
                    position: 'auto',
                    defaultDate: this.$refs.input.value || null,
                    minDate: {{ $min ? "'" . $min . "'" : 'null' }},
                    maxDate: {{ $max ? "'" . $max . "'" : 'null' }},
                    locale: { firstDayOfWeek: 1 },
                    onChange: (selectedDates, dateStr, instance) => {
                        instance.element.dispatchEvent(new Event('input', { bubbles: true }));
                        instance.element.dispatchEvent(new Event('change', { bubbles: true }));
                    },
                });
            });
        },
        destroy() {
            this.picker?.destroy();
        },
    }"
>
    @if ($label)
        <label @if($id) for="{{ $id }}" @endif class="form-label">
            {{ $label }}
            @if ($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    <div class="relative">
        <div class="pointer-events-none absolute inset-y-0 start-0 z-10 flex items-center ps-3">
            <iconify-icon icon="lucide:calendar" class="text-gray-400 dark:text-gray-500"></iconify-icon>
        </div>
        <input
            x-ref="input"
            type="text"
            @if($name) name="{{ $name }}" @endif
            @if($id) id="{{ $id }}" @endif
            value="{{ $name ? old($name, $value) : $value }}"
            placeholder="{{ $placeholder }}"
            autocomplete="off"
            @if ($required) required @endif
            @if ($disabled) disabled @endif
            {{ $attributes->class(['form-control', '!ps-10']) }}
        />
    </div>

    @if ($hint)
        <div class="mt-1 text-xs text-gray-400">{{ $hint }}</div>
    @endif
</div>
