<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\Candidate;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;

class Order extends Component
{
    use WithPagination;

    public int $customerId;
    public string $search = '';
    public int $perPage = 15;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $totalCount = Candidate::query()
            ->where('cus_id', $this->customerId)
            ->count();

        $statusCounts = Candidate::query()
            ->where('candidates.cus_id', $this->customerId)
            ->join('statuses', 'statuses.id', '=', 'candidates.status')
            ->selectRaw('statuses.id, statuses.status as status_name, statuses.color, COUNT(candidates.id) as cnt')
            ->groupBy('statuses.id', 'statuses.status', 'statuses.color')
            ->orderByDesc('cnt')
            ->get();

        $orders = $this->getOrders();

        return view('livewire.customer.tabs.order', compact('totalCount', 'statusCounts', 'orders'));
    }

    private function getOrders(): LengthAwarePaginator
    {
        return Candidate::query()
            ->where('candidates.cus_id', $this->customerId)
            ->leftJoin('statuses', 'statuses.id', '=', 'candidates.status')
            ->leftJoin('service_types', 'service_types.id', '=', 'candidates.interview_id')
            ->select([
                'candidates.id',
                'candidates.order_id',
                'candidates.name',
                'candidates.surname',
                'candidates.invoice_sent',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.created',
                'candidates.expired',
                'statuses.status as status_name',
                'statuses.color as status_color',
                'service_types.name as service_name',
            ])
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('candidates.order_id', 'like', "%{$this->search}%")
                        ->orWhere('candidates.name', 'like', "%{$this->search}%")
                        ->orWhere('candidates.surname', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('candidates.id')
            ->paginate($this->perPage);
    }
}
