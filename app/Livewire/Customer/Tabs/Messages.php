<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\CandidateMessage;
use App\Models\ServiceType;
use App\Services\CustomerPropagationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

class Messages extends Component
{
    public int $customerId;

    /** @var Collection<int, object> */
    public Collection $services;
    public ?int $selectedService = null;

    public ?int $copyCustomer = null;
    public ?int $copyService = null;

    /**
     * Template keys for the currently selected service.
     * Each entry is a status_id string (e.g. "15") or a special key ("cus_msg", …).
     *
     * @var string[]
     */
    public array $columns = [];

    /**
     * Human-readable label for each key in $columns.
     *
     * @var array<string, string>
     */
    public array $columnLabels = [];

    /** @var array<string, string> template body per key */
    public array $messageValues = [];

    public bool $isCopyMode = false;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->services = $this->getCustomerServices($customerId);
        $this->selectedService = $this->services->first()?->id;
        $this->loadData();
    }

    public function updatedSelectedService(): void
    {
        $this->isCopyMode = false;
        $this->copyService = null;
        $this->loadData($this->customerId, $this->selectedService);
    }

    public function updatedCopyCustomer(): void
    {
        $this->copyService = null;
    }

    public function updatedCopyService(): void
    {
        if ($this->copyCustomer && $this->copyService) {
            $this->isCopyMode = true;
            $this->loadData($this->copyCustomer, $this->copyService);
        }
    }

    public function loadData(?int $customerId = null, ?int $serviceId = null): void
    {
        $customerId = $customerId ?? $this->customerId;
        $serviceId = $serviceId ?? $this->selectedService;

        if (! $serviceId) {
            $this->columns = [];
            $this->columnLabels = [];
            $this->messageValues = [];
            return;
        }

        // Build ordered list of status keys linked to this service type.
        [$keys, $labels] = $this->keysForService($serviceId);

        $this->columns = $keys;
        $this->columnLabels = $labels;

        if (empty($keys)) {
            $this->messageValues = [];
            return;
        }

        $row = CandidateMessage::where('cus_id', $customerId)
            ->where('interview_id', $serviceId)
            ->first();

        $templates = $row?->templates ?? [];

        $this->messageValues = [];
        foreach ($keys as $key) {
            $this->messageValues[$key] = (string) ($templates[$key] ?? '');
        }
    }

    public function saveMessages(): void
    {
        if (! $this->selectedService || empty($this->columns)) {
            return;
        }

        $row = CandidateMessage::firstOrNew([
            'cus_id' => $this->customerId,
            'interview_id' => $this->selectedService,
        ]);

        // Merge incoming values into the existing JSON (preserve keys from other services).
        $templates = $row->templates ?? [];
        foreach ($this->columns as $key) {
            $val = $this->messageValues[$key] ?? '';
            $templates[$key] = ($val !== '') ? $val : null;
        }
        $row->templates = $templates;
        $row->save();

        // Propagate to child customers that share the same service type.
        app(CustomerPropagationService::class)->propagateMessages(
            $this->customerId,
            $this->selectedService,
            $templates
        );

        $this->isCopyMode = false;

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Messages saved successfully.'),
        ]);
    }

    public function copyMessages(): void
    {
        if (! $this->copyCustomer || ! $this->copyService || ! $this->selectedService) {
            $this->dispatch('notify', [
                'variant' => 'error',
                'title' => __('Error'),
                'message' => __('Please select source customer and service to copy from.'),
            ]);
            return;
        }

        [$sourceKeys] = $this->keysForService($this->copyService);

        if (empty($sourceKeys) || empty($this->columns)) {
            $this->dispatch('notify', [
                'variant' => 'error',
                'title' => __('Error'),
                'message' => __('No valid message columns found to copy.'),
            ]);
            return;
        }

        $source = CandidateMessage::where('cus_id', $this->copyCustomer)
            ->where('interview_id', $this->copyService)
            ->first();

        if (! $source) {
            $this->dispatch('notify', [
                'variant' => 'error',
                'title' => __('Error'),
                'message' => __('Source messages not found for selected customer/service.'),
            ]);
            return;
        }

        $sourceTemplates = $source->templates ?? [];

        foreach ($this->columns as $key) {
            if (in_array($key, $sourceKeys, true)) {
                $this->messageValues[$key] = (string) ($sourceTemplates[$key] ?? '');
            }
        }

        $this->saveMessages();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Messages copied and saved successfully.'),
        ]);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns [keys[], labels[]] for all statuses linked to a service type.
     * Keys are msg_col values (e.g. 'approved_msg'). Labels are status names.
     *
     * @return array{0: string[], 1: array<string, string>}
     */
    private function keysForService(int $serviceId): array
    {
        if (! Schema::hasTable('status_services') || ! Schema::hasTable('statuses')) {
            return [[], []];
        }

        $rows = DB::table('status_services')
            ->join('statuses', 'statuses.id', '=', 'status_services.status_id')
            ->where('status_services.service_id', $serviceId)
            ->whereNotNull('status_services.msg_col')
            ->where('status_services.msg_col', '!=', '')
            ->orderBy('statuses.status')
            ->select('status_services.msg_col', 'statuses.status as name')
            ->get();

        $keys = [];
        $labels = [];

        foreach ($rows as $row) {
            $key = $row->msg_col;
            $keys[] = $key;
            $labels[$key] = $row->name;
        }

        return [$keys, $labels];
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

    private function getCustomersForCopy(): Collection
    {
        return \App\Models\Customer::query()
            ->join('users', 'users.id', '=', 'customers.user_id')
            ->select('customers.id', 'users.name')
            ->orderBy('users.name')
            ->get();
    }

    public function getCopyServicesProperty(): Collection
    {
        if (! $this->copyCustomer) {
            return collect([]);
        }

        return $this->getCustomerServices($this->copyCustomer);
    }

    public function render()
    {
        return view('livewire.customer.tabs.messages', [
            'customers' => $this->getCustomersForCopy(),
            'services' => $this->services,
            'copyServices' => $this->copyServices,
            'columns' => $this->columns,
            'columnLabels' => $this->columnLabels,
            'messageValues' => $this->messageValues,
        ]);
    }
}
