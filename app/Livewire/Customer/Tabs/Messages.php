<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\Customer;
use App\Models\ServiceType;
use Illuminate\Support\Collection;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Messages extends Component
{
    public int $customerId;

    /** @var Collection<int, object> */
    public Collection $services;
    public ?int $selectedService = null;

    public ?int $copyCustomer = null;
    public ?int $copyService = null;

    /** @var array<int, string> */
    public array $columns = [];

    /** @var array<string, string> */
    public array $messageValues = [];
    public array $copyMessageValues = [];
    public bool $isCopyMode = false;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->services = $this->getCustomerServices($this->customerId);
        $this->selectedService = $this->services->first()?->id;
        $this->loadData();
    }

    public function updatedSelectedService(): void
    {
        $this->isCopyMode = false;
        $this->copyService = null;

        $this->loadData(
            $this->customerId,
            $this->selectedService
        );
    }

    public function updatedCopyCustomer(): void
    {
        $this->copyService = null;
    }

    public function updatedCopyService(): void
    {
        if ($this->copyCustomer && $this->copyService) {
            $this->isCopyMode = true;

            $this->loadData(
                $this->copyCustomer,
                $this->copyService
            );
        }
    }

    public function loadData(?int $customerId = null, ?int $serviceId = null): void
    {
        $customerId = $customerId ?? $this->customerId;
        $serviceId = $serviceId ?? $this->selectedService;

        if (! $serviceId) {
            $this->columns = [];
            $this->messageValues = [];
            return;
        }

        // Get columns
        $columns = DB::table('status_services')
                ->where('service_id', $serviceId)
                ->pluck('msg_col')
                ->filter()
                ->unique()
                ->values()
                ->toArray();

        $this->columns = array_values(array_filter(
            $columns,
            fn ($col) => Schema::hasColumn('messages', $col)
        ));

        if (empty($this->columns)) {
            $this->messageValues = [];
            return;
        }

        $serviceColumn = Schema::hasColumn('messages', 'servicetype_id')
                ? 'servicetype_id'
                : 'interview_id';

        $row = DB::table('messages')
                ->where('cus_id', $customerId)
                ->where($serviceColumn, $serviceId)
                ->first($this->columns);

        $this->messageValues = [];

        foreach ($this->columns as $column) {
            $this->messageValues[$column] = (string) ($row->{$column} ?? '');
        }
    }

    public function saveMessages(): void
    {
        if (! $this->selectedService || empty($this->columns)) {
            return;
        }

        $serviceColumn = Schema::hasColumn('messages', 'servicetype_id')
                ? 'servicetype_id'
                : 'interview_id';

        $payload = [
                'cus_id' => $this->customerId, // ALWAYS original customer
                $serviceColumn => $this->selectedService,
        ];

        foreach ($this->columns as $column) {
            $payload[$column] = $this->messageValues[$column] ?? '';
        }

        DB::table('messages')->updateOrInsert(
            [
                        'cus_id' => $this->customerId,
                        $serviceColumn => $this->selectedService,
                ],
            $payload
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

        $serviceColumn = Schema::hasColumn('messages', 'servicetype_id') ? 'servicetype_id' : 'interview_id';

        $sourceColumnsRaw = DB::table('status_services')
            ->where('service_id', $this->copyService)
            ->pluck('msg_col')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        $sourceColumns = array_values(array_filter($sourceColumnsRaw, fn ($col) => Schema::hasColumn('messages', $col)));

        if (empty($sourceColumns) || empty($this->columns)) {
            $this->dispatch('notify', [
                'variant' => 'error',
                'title' => __('Error'),
                'message' => __('No valid message columns found to copy.'),
            ]);
            return;
        }

        $source = DB::table('messages')
            ->where('cus_id', $this->copyCustomer)
            ->where($serviceColumn, $this->copyService)
            ->first($sourceColumns);

        if (! $source) {
            $this->dispatch('notify', [
                'variant' => 'error',
                'title' => __('Error'),
                'message' => __('Source messages not found for selected customer/service.'),
            ]);
            return;
        }

        foreach ($this->columns as $column) {
            if (in_array($column, $sourceColumns, true)) {
                $this->messageValues[$column] = (string) ($source->{$column} ?? '');
            }
        }

        $this->saveMessages();
        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Messages copied and saved successfully.'),
        ]);
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
        return Customer::query()
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

    private function loadCopyData(): void
    {
        $this->copyMessageValues = [];

        if (! $this->copyCustomer || ! $this->copyService) {
            return;
        }

        $serviceColumn = Schema::hasColumn('messages', 'servicetype_id') ? 'servicetype_id' : 'interview_id';

        // Get columns for COPY service
        $columnsRaw = DB::table('status_services')
                ->where('service_id', $this->copyService)
                ->pluck('msg_col')
                ->filter()
                ->unique()
                ->toArray();

        $columns = array_values(array_filter($columnsRaw, fn ($col) => Schema::hasColumn('messages', $col)));

        if (empty($columns)) {
            return;
        }

        $row = DB::table('messages')
                ->where('cus_id', $this->copyCustomer)
                ->where($serviceColumn, $this->copyService)
                ->first($columns);

        if (! $row) {
            return;
        }

        foreach ($columns as $col) {
            $this->copyMessageValues[$col] = (string) ($row->{$col} ?? '');
        }
    }

    public function render()
    {
        return view('livewire.customer.tabs.messages', [
                'customers' => $this->getCustomersForCopy(),
                'services' => $this->services,
                'copyServices' => $this->copyServices,
                'columns' => $this->columns,
                'messageValues' => $this->messageValues,
        ]);
    }
}
