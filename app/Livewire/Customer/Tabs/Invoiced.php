<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\CustomerInvoice;
use Livewire\Component;
use Livewire\WithPagination;

class Invoiced extends Component
{
    use WithPagination;

    public int $customerId;
    public string $filterStatus = 'all';
    public string $filterPeriod = 'all';
    public int $perPage = 15;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingFilterPeriod(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $base = CustomerInvoice::query()->where('customer_id', $this->customerId);

        $stats = (clone $base)
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as sent,
                COALESCE(SUM(invoice_amount), 0) as total_amount
            ', [CustomerInvoice::STATUS_TO_BE_INVOICED, CustomerInvoice::STATUS_SENT])
            ->first();

        $invoices = (clone $base)
            ->when($this->filterStatus !== 'all', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPeriod !== 'all', fn ($q) => $q->where('period', $this->filterPeriod))
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.customer.tabs.invoiced', compact('stats', 'invoices'));
    }
}
