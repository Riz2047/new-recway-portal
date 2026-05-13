<?php

namespace App\Livewire\Customer\Tabs;

use App\Models\CompanyManager;
use App\Models\Customer;
use Livewire\Component;

class StatusManager extends Component
{
    public int $customerId;

    public ?CompanyManager $companyManager = null;

    public array $managerCompanies = [];

    public ?string $selectedCompany = null;

    public bool $canViewReport = false;

    public ?string $interviewReportTemplate = null;

    public ?string $underInvestigationTemplate = null;

    public ?string $approvedTemplate = null;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->loadData();
    }

    public function loadData(): void
    {
        $this->managerCompanies = Customer::query()
            ->whereNotNull('company')
            ->distinct()
            ->orderBy('company')
            ->pluck('company')
            ->toArray();

        $this->companyManager = CompanyManager::query()
            ->where('cus_id', $this->customerId)
            ->first();

        $this->selectedCompany = $this->companyManager?->company
            ?? Customer::query()->whereKey($this->customerId)->value('company');

        $this->canViewReport = (bool) ($this->companyManager?->can_view_report ?? false);
        $this->underInvestigationTemplate = $this->companyManager?->email_template;
        $this->approvedTemplate = $this->companyManager?->email_template_approved;
    }

    public function update(): void
    {
        $this->validate([
            'selectedCompany' => ['nullable', 'string', 'max:500'],
            'canViewReport' => ['boolean'],
            'underInvestigationTemplate' => ['nullable', 'string'],
            'approvedTemplate' => ['nullable', 'string'],
        ]);

        $this->companyManager = CompanyManager::query()->updateOrCreate(
            ['cus_id' => $this->customerId],
            [
                'company' => $this->selectedCompany,
                'can_view_report' => $this->canViewReport,
                'email_template' => $this->underInvestigationTemplate,
                'email_template_approved' => $this->approvedTemplate,
            ]
        );

        session()->flash('success', 'Updated successfully');
    }

    public function save(): void
    {
        $this->update();
    }

    public function render()
    {
        return view('livewire.customer.tabs.status-manager', [
            'managerCompanies' => $this->managerCompanies,
            'selectedCompany' => $this->selectedCompany,
            'companyManager' => $this->companyManager,
        ]);
    }
}
