<?php

namespace App\Livewire\Customer\Tabs;

use Livewire\Component;
use App\Models\StandardBillingDetail;

class Billing extends Component
{
    public $customerId;
    public $billing;

    public function mount($customerId)
    {
        $this->customerId = $customerId;

        $this->loadData();
    }

    public function loadData()
    {
        $this->billing = StandardBillingDetail::where('cus_id', $this->customerId)->first();
    }

    public function render()
    {
        return view('livewire.customer.tabs.billing', [
        'billingDetails' => $this->billing,
        ]);
    }
}
