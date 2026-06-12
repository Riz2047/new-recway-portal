@php
    $serviceTypeJson = json_encode([
        'id' => $serviceType->id,
        'name' => $serviceType->name,
        'price' => $serviceType->price,
        'description' => $serviceType->description,
        'place' => $serviceType->place,
        'country' => $serviceType->country,
        'delivery_days' => $serviceType->delivery_days,
        'customers' => $serviceType->customers->whereNull('parent_id')->map(fn($c) => ['id' => $c->id, 'name' => $c->name])->values()->toArray(),
    ], JSON_HEX_APOS | JSON_HEX_QUOT);
@endphp

<div class="flex items-center justify-end gap-2">
    <button class="edit-service-type inline-flex items-center justify-center w-9 h-9 text-blue-600 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-gray-800 rounded-lg transition-colors" 
        data-service-type='{{ $serviceTypeJson }}'
        title="{{ __('Edit') }}">
        <iconify-icon icon="lucide:edit" class="w-5 h-5"></iconify-icon>
    </button>
    <button class="delete-service-type inline-flex items-center justify-center w-9 h-9 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-gray-800 rounded-lg transition-colors"
        data-service-type-id="{{ $serviceType->id }}"
        title="{{ __('Delete') }}">
        <iconify-icon icon="lucide:trash-2" class="w-5 h-5"></iconify-icon>
    </button>
</div>
