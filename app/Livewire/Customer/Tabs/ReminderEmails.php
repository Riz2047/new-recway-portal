<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\Customer;
use Livewire\Component;

class ReminderEmails extends Component
{
    public int $customerId;

    public bool $remainderEmail = false;
    public string $remainderEmailTemplate = '';

    public bool $bkRemainderEmail = false;
    public string $bkRemainderEmailTemplate = '';

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->loadData();
    }

    public function save(): void
    {
        $this->validate([
            'remainderEmailTemplate' => ['nullable', 'string'],
            'bkRemainderEmailTemplate' => ['nullable', 'string'],
        ]);

        Customer::query()
            ->whereKey($this->customerId)
            ->update([
                'remainder_email' => $this->remainderEmail,
                'remainder_email_template' => $this->remainderEmailTemplate,
                'bk_remainder_email' => $this->bkRemainderEmail,
                'bk_remainder_email_template' => $this->bkRemainderEmailTemplate,
            ]);

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Saved'),
            'message' => __('Reminder email settings updated successfully.'),
        ]);
    }

    private function loadData(): void
    {
        $customer = Customer::query()
            ->whereKey($this->customerId)
            ->select(['remainder_email', 'remainder_email_template', 'bk_remainder_email', 'bk_remainder_email_template'])
            ->first();

        if (! $customer) {
            return;
        }

        $this->remainderEmail = (bool) $customer->remainder_email;
        $this->remainderEmailTemplate = (string) ($customer->remainder_email_template ?? '');
        $this->bkRemainderEmail = (bool) $customer->bk_remainder_email;
        $this->bkRemainderEmailTemplate = (string) ($customer->bk_remainder_email_template ?? '');
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.customer.tabs.reminder-emails');
    }
}
