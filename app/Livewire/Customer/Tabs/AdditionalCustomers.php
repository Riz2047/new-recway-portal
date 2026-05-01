<?php

namespace App\Livewire\Customer\Tabs;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AdditionalCustomers extends Component
{
    public int $customerId;
    public bool $showForm = false;
    public bool $isEditing = false;
    public ?int $editingId = null;
    public string $name = '';
    public string $email = '';

    /** @var array<int, array<string, mixed>> */
    public array $additionalCustomers = [];

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->loadAdditionalCustomers();
    }

    public function showAddForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function editAdditionalCustomer(int $id): void
    {
        $customer = DB::table('additional_customers')
            ->where('id', $id)
            ->where('cus_id', $this->customerId)
            ->first();

        if (! $customer) {
            return;
        }

        $this->editingId = (int) $customer->id;
        $this->name = (string) $customer->name;
        $this->email = (string) ($customer->email ?? '');
        $this->showForm = true;
        $this->isEditing = true;
    }

    public function saveAdditionalCustomer(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('additional_customers', 'email')
                    ->where(fn ($query) => $query->where('cus_id', $this->customerId))
                    ->ignore($this->editingId),
            ],
        ], [
            'name.required' => __('Please enter additional customer name.'),
            'email.email' => __('Please enter a valid email address.'),
            'email.unique' => __('Email already exists for this customer.'),
        ]);

        if ($this->isEditing && $this->editingId) {
            DB::table('additional_customers')
                ->where('id', $this->editingId)
                ->where('cus_id', $this->customerId)
                ->update([
                    'name' => $validated['name'],
                    'email' => $validated['email'] ?: null,
                    'updated_at' => now(),
                ]);

            $message = __('Additional customer updated successfully.');
        } else {
            DB::table('additional_customers')->insert([
                'cus_id' => $this->customerId,
                'name' => $validated['name'],
                'email' => $validated['email'] ?: null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $message = __('Additional customer added successfully.');
        }

        $this->resetForm();
        $this->loadAdditionalCustomers();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => $message,
        ]);
    }

    public function deleteAdditionalCustomer(int $id): void
    {
        DB::table('additional_customers')
            ->where('id', $id)
            ->where('cus_id', $this->customerId)
            ->delete();

        $this->loadAdditionalCustomers();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Additional customer deleted successfully.'),
        ]);
    }

    public function cancelForm(): void
    {
        $this->resetForm();
    }

    private function loadAdditionalCustomers(): void
    {
        $this->additionalCustomers = DB::table('additional_customers')
            ->where('cus_id', $this->customerId)
            ->orderBy('id')
            ->get(['id', 'name', 'email'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'email' => (string) ($row->email ?? ''),
            ])
            ->all();
    }

    private function resetForm(): void
    {
        $this->showForm = false;
        $this->isEditing = false;
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->resetValidation();
    }

    public function render()
    {
        return view('livewire.customer.tabs.additional-customers');
    }
}
