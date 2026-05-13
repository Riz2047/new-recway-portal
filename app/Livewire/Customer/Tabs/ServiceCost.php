<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\ServiceType;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ServiceCost extends Component
{
    public int $customerId;

    /** @var array<int, array{id: int, name: string, default_price: string|null, cost: string}> */
    public array $services = [];

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->loadServices();
    }

    public function save(): void
    {
        $rules = [];
        foreach ($this->services as $i => $service) {
            $rules["services.{$i}.cost"] = ['nullable', 'numeric', 'min:0', 'max:99999999'];
        }

        $this->validate($rules);

        foreach ($this->services as $service) {
            $cost = $service['cost'] !== '' ? (float) $service['cost'] : null;

            DB::table('customer_services')->updateOrInsert(
                ['cus_id' => $this->customerId, 'service_id' => $service['id']],
                ['service_cost' => $cost !== null ? (int) round($cost) : null]
            );
        }

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Saved'),
            'message' => __('Service costs updated successfully.'),
        ]);
    }

    public function resetCost(int $index): void
    {
        if (! isset($this->services[$index])) {
            return;
        }

        $this->services[$index]['cost'] = $this->services[$index]['default_price'] ?? '';
    }

    private function loadServices(): void
    {
        $serviceTypes = ServiceType::query()
            ->join('service_type_user', 'service_type_user.service_type_id', '=', 'service_types.id')
            ->where('service_type_user.cus_id', $this->customerId)
            ->select('service_types.id', 'service_types.name', 'service_types.price')
            ->orderBy('service_types.name')
            ->get();

        $savedCosts = DB::table('customer_services')
            ->where('cus_id', $this->customerId)
            ->pluck('service_cost', 'service_id')
            ->toArray();

        $this->services = $serviceTypes->map(function (ServiceType $s) use ($savedCosts): array {
            $saved = $savedCosts[$s->id] ?? null;

            return [
                'id' => $s->id,
                'name' => $s->name,
                'default_price' => $s->price !== null ? (string) $s->price : null,
                'cost' => $saved !== null ? (string) $saved : ($s->price !== null ? (string) $s->price : ''),
            ];
        })->values()->all();
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.customer.tabs.service-cost');
    }
}
