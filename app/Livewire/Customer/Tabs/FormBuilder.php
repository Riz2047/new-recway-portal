<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\Customer;
use App\Models\ServiceType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class FormBuilder extends Component
{
    public int $customerId;

    /** @var Collection<int, ServiceType> */
    public Collection $services;

    /** @var array<int, array{id:int,name:string}> */
    public array $customers = [];

    public ?int $selectedService = null;
    public ?int $copyCustomer = null;
    public ?int $copyService = null;

    /** @var array<string, array<int, array<string, mixed>>> */
    public array $formSections = [
        'personal_info' => [],
        'billing_info' => [],
    ];

    /** @var array{label: string, type: string, placeholder: string, required: bool, options: string} */
    public array $newField = [
        'label' => '',
        'type' => 'text',
        'placeholder' => '',
        'required' => false,
        'options' => '',
    ];

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->services = $this->getCustomerServices($customerId);
        $this->customers = $this->getCustomersForCopy();
        $this->selectedService = $this->resolveInitialServiceId();

        $this->loadFormBuilder();
    }

    public function updatedSelectedService(): void
    {
        $this->copyService = null;
        $this->loadFormBuilder();
    }

    public function updatedCopyCustomer(): void
    {
        $this->copyService = null;
    }

    public function updatedCopyService(): void
    {
        if (! $this->copyCustomer || ! $this->copyService) {
            return;
        }

        $this->loadFormBuilder($this->copyCustomer, $this->copyService);
    }

    public function addDefaultField(string $field): void
    {
        $default = match ($field) {
            'note' => [
                'section' => 'billing_info',
                'type' => 'text',
                'label' => 'Note',
                'name' => 'note',
                'placeholder' => 'Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.',
                'required' => '',
                'value' => 'Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.',
            ],
            'comment' => [
                'section' => 'billing_info',
                'type' => 'text',
                'label' => 'Invoice Comment',
                'name' => 'comment',
                'placeholder' => 'Enter Invoice Comment',
                'required' => '',
                'value' => 'Enter Invoice Comment',
            ],
            'document_file' => [
                'section' => 'personal_info',
                'type' => 'text',
                'label' => 'Documents',
                'name' => 'document_file',
                'placeholder' => '',
                'required' => '',
                'value' => '',
            ],
            'vasc_id' => [
                'section' => 'personal_info',
                'type' => 'text',
                'label' => 'VASC ID',
                'name' => 'vasc_id',
                'placeholder' => 'Enter Candidate VASC ID',
                'required' => '',
                'value' => 'Enter Candidate VASC ID',
            ],
            default => null,
        };

        if (! $default) {
            return;
        }

        if ($this->fieldNameExists($default['name'])) {
            return;
        }

        $this->formSections[$default['section']][] = $default;
    }

    public function addCustomField(string $section): void
    {
        if (! in_array($section, ['personal_info', 'billing_info'], true)) {
            return;
        }

        $label = trim($this->newField['label']);
        if ($label === '') {
            return;
        }

        $name = Str::of($label)->lower()->snake()->value();
        if ($name === '' || $this->fieldNameExists($name)) {
            return;
        }

        $type = $this->newField['type'];
        $required = $this->newField['required'] ? 'required' : '';
        $placeholder = trim($this->newField['placeholder']);
        $options = trim($this->newField['options']);

        $field = [
            'type' => $type,
            'label' => $label,
            'name' => $name,
            'placeholder' => $placeholder,
            'required' => $required,
            'value' => $placeholder,
        ];

        if ($type === 'select') {
            $field['options'] = $options;
            $field['value'] = '';
        }

        $this->formSections[$section][] = $field;
        $this->resetNewField();
    }

    public function removeField(string $section, int $index): void
    {
        if (! isset($this->formSections[$section][$index])) {
            return;
        }

        unset($this->formSections[$section][$index]);
        $this->formSections[$section] = array_values($this->formSections[$section]);
    }

    public function moveFieldUp(string $section, int $index): void
    {
        $this->moveField($section, $index, -1);
    }

    public function moveFieldDown(string $section, int $index): void
    {
        $this->moveField($section, $index, 1);
    }

    public function moveFieldTo(string $section, int $fromIndex, int $toIndex): void
    {
        if (! in_array($section, ['personal_info', 'billing_info'], true)) {
            return;
        }

        if (! isset($this->formSections[$section][$fromIndex]) || ! isset($this->formSections[$section][$toIndex])) {
            return;
        }

        if ($fromIndex === $toIndex) {
            return;
        }

        $field = $this->formSections[$section][$fromIndex];
        array_splice($this->formSections[$section], $fromIndex, 1);
        array_splice($this->formSections[$section], $toIndex, 0, [$field]);
        $this->formSections[$section] = array_values($this->formSections[$section]);
    }

    public function saveFormBuilder(): void
    {
        if (! $this->selectedService) {
            return;
        }

        DB::table('form_builders')->updateOrInsert(
            [
                'cus_id' => $this->customerId,
                'servicetype_id' => $this->selectedService,
            ],
            [
                'form' => json_encode([
                    'form_builder' => $this->serializeSections(),
                ], JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Form builder saved successfully.'),
        ]);
    }

    public function getCopyServicesProperty(): Collection
    {
        if (! $this->copyCustomer) {
            return collect([]);
        }

        return $this->getCustomerServices($this->copyCustomer);
    }

    private function loadFormBuilder(?int $customerId = null, ?int $serviceId = null): void
    {
        $customerId = $customerId ?? $this->customerId;
        $serviceId = $serviceId ?? $this->selectedService;

        if (! $serviceId) {
            $this->formSections = ['personal_info' => [], 'billing_info' => []];
            return;
        }

        $row = DB::table('form_builders')
            ->where('cus_id', $customerId)
            ->where('servicetype_id', $serviceId)
            ->first();

        if (! $row || empty($row->form)) {
            $this->formSections = ['personal_info' => [], 'billing_info' => []];
            return;
        }

        $builder = $this->extractBuilderPayload((string) $row->form);

        if (! is_array($builder)) {
            $this->formSections = ['personal_info' => [], 'billing_info' => []];
            return;
        }

        $personalInfo = $this->normalizeSection($builder['personal_info'] ?? []);
        $billingInfo = $this->normalizeSection($builder['billing_info'] ?? []);

        if ($personalInfo === [] && $billingInfo === []) {
            $this->formSections = ['personal_info' => [], 'billing_info' => []];
            return;
        }

        $this->formSections = [
            'personal_info' => $personalInfo,
            'billing_info' => $billingInfo,
        ];
    }

    /** @param array<mixed, mixed> $section */
    private function normalizeSection(array $section): array
    {
        $normalized = [];

        // Supports both legacy "metaKey => value" and normalized array shapes.
        foreach ($section as $metaKey => $value) {
            if (is_array($value) && isset($value['name'])) {
                $normalized[] = [
                    'type' => (string) ($value['type'] ?? 'text'),
                    'label' => (string) ($value['label'] ?? ''),
                    'name' => (string) ($value['name'] ?? ''),
                    'placeholder' => (string) ($value['placeholder'] ?? ''),
                    'required' => (string) ($value['required'] ?? ''),
                    'is_tra' => (string) ($value['is_tra'] ?? ''),
                    'is_new' => (string) ($value['is_new'] ?? ''),
                    'options' => (string) ($value['options'] ?? ''),
                    'value' => (string) ($value['value'] ?? ''),
                ];
                continue;
            }

            $parts = explode(',', (string) $metaKey);
            $name = trim((string) ($parts[2] ?? ''));
            if ($name === '') {
                continue;
            }

            $normalized[] = [
                'type' => $parts[0] ?? 'text',
                'label' => $parts[1] ?? '',
                'name' => $name,
                'placeholder' => $parts[3] ?? '',
                'required' => $parts[4] ?? '',
                'is_tra' => $parts[5] ?? '',
                'is_new' => $parts[6] ?? '',
                'options' => $parts[7] ?? '',
                'value' => (string) $value,
            ];
        }

        return $normalized;
    }

    /** @return array<string, array<string, string>> */
    private function serializeSections(): array
    {
        $data = [
            'personal_info' => [],
            'billing_info' => [],
        ];

        foreach (['personal_info', 'billing_info'] as $section) {
            foreach ($this->formSections[$section] as $field) {
                $meta = implode(',', [
                    $field['type'] ?? 'text',
                    $field['label'] ?? '',
                    $field['name'] ?? '',
                    $field['placeholder'] ?? '',
                    $field['required'] ?? '',
                    $field['is_tra'] ?? '',
                    $field['is_new'] ?? 'new_field',
                    $field['options'] ?? '',
                ]);

                $data[$section][$meta] = (string) ($field['value'] ?? $field['placeholder'] ?? '');
            }
        }

        return $data;
    }

    private function fieldNameExists(string $name): bool
    {
        foreach (['personal_info', 'billing_info'] as $section) {
            foreach ($this->formSections[$section] as $field) {
                if (($field['name'] ?? null) === $name) {
                    return true;
                }
            }
        }

        return false;
    }

    private function resetNewField(): void
    {
        $this->newField = [
            'label' => '',
            'type' => 'text',
            'placeholder' => '',
            'required' => false,
            'options' => '',
        ];
    }

    private function getCustomerServices(int $customerId): Collection
    {
        return ServiceType::query()
            ->select('service_types.id', 'service_types.name')
            ->join('service_type_user', 'service_type_user.service_type_id', '=', 'service_types.id')
            ->where('service_type_user.cus_id', $customerId)
            ->orderBy('service_types.name')
            ->get();
    }

    /** @return array<int, array{id:int,name:string}> */
    private function getCustomersForCopy(): array
    {
        return Customer::query()
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.id', 'users.name')
            ->orderBy('users.name')
            ->get()
            ->map(fn (Customer $customer): array => [
                'id' => (int) $customer->id,
                'name' => (string) $customer->name,
            ])
            ->values()
            ->all();
    }

    private function resolveInitialServiceId(): ?int
    {
        $serviceIds = $this->services->pluck('id');
        if ($serviceIds->isEmpty()) {
            return null;
        }

        $savedServiceId = DB::table('form_builders')
            ->where('cus_id', $this->customerId)
            ->whereIn('servicetype_id', $serviceIds)
            ->orderByDesc('updated_at')
            ->value('servicetype_id');

        if ($savedServiceId && $serviceIds->contains((int) $savedServiceId)) {
            return (int) $savedServiceId;
        }

        return $this->services->first()?->id;
    }

    /** @return array<mixed, mixed>|null */
    private function extractBuilderPayload(string $rawForm): ?array
    {
        $decoded = json_decode($rawForm, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        if (! is_array($decoded)) {
            return null;
        }

        $builder = $decoded['form_builder'] ?? $decoded;
        if (is_string($builder)) {
            $builder = json_decode($builder, true);
        }

        return is_array($builder) ? $builder : null;
    }

    private function moveField(string $section, int $index, int $direction): void
    {
        if (! in_array($section, ['personal_info', 'billing_info'], true)) {
            return;
        }

        $targetIndex = $index + $direction;
        if (! isset($this->formSections[$section][$index]) || ! isset($this->formSections[$section][$targetIndex])) {
            return;
        }

        [$this->formSections[$section][$index], $this->formSections[$section][$targetIndex]] = [
            $this->formSections[$section][$targetIndex],
            $this->formSections[$section][$index],
        ];
    }

    public function render()
    {
        return view('livewire.customer.tabs.form-builder', [
            'copyServices' => $this->copyServices,
        ]);
    }
}
