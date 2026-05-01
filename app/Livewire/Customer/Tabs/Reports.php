<?php

namespace App\Livewire\Customer\Tabs;

use App\Livewire\Concerns\BuildsReportTemplates;
use App\Services\Reports\ReportTemplateService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Reports extends Component
{
    use BuildsReportTemplates;

    private ReportTemplateService $reportTemplateService;

    public int $customerId;

    /** @var Collection<int, object> */
    public Collection $services;

    /** @var Collection<int, object> */
    public Collection $statuses;

    public ?int $selectedService = null;

    /** @var array{sv: array{version:int,sections:array<int,array<string,mixed>>}, en: array{version:int,sections:array<int,array<string,mixed>>}} */
    public array $templates = [
        'sv' => ['version' => 1, 'sections' => []],
        'en' => ['version' => 1, 'sections' => []],
    ];

    /** @var array{sv: array{version:int,sections:array<int,array<string,mixed>>}, en: array{version:int,sections:array<int,array<string,mixed>>}} */
    public array $globalTemplates = [
        'sv' => ['version' => 1, 'sections' => []],
        'en' => ['version' => 1, 'sections' => []],
    ];

    /** @var array{sv: bool, en: bool} */
    public array $isOverridden = [
        'sv' => false,
        'en' => false,
    ];

    public function boot(ReportTemplateService $reportTemplateService): void
    {
        $this->reportTemplateService = $reportTemplateService;
    }

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
        $this->services = $this->reportTemplateService->getCustomerBackgroundServices($customerId);
        $this->statuses = collect([]);
        $this->selectedService = $this->services->first()?->id;
        $this->loadEditorData();
    }

    public function updatedSelectedService(): void
    {
        $this->loadEditorData();
    }

    public function saveTemplates(): void
    {
        if (! $this->selectedService) {
            return;
        }

        foreach (['sv', 'en'] as $lang) {
            $this->reportTemplateService->saveTemplate($this->customerId, $this->selectedService, $lang, $this->templates[$lang] ?? []);
        }

        $this->loadEditorData();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Customer report templates saved successfully.'),
        ]);
    }

    public function resetLanguageToGlobal(string $lang): void
    {
        if (! in_array($lang, ['sv', 'en'], true) || ! $this->selectedService) {
            return;
        }

        $this->reportTemplateService->deleteTemplate($this->customerId, $this->selectedService, $lang);

        $this->loadEditorData();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Template was reset to global default.'),
        ]);
    }

    private function loadEditorData(): void
    {
        $this->statuses = collect([]);
        $this->templates = [
            'sv' => $this->reportTemplateService->defaultPayload('sv'),
            'en' => $this->reportTemplateService->defaultPayload('en'),
        ];
        $this->globalTemplates = [
            'sv' => $this->reportTemplateService->defaultPayload('sv'),
            'en' => $this->reportTemplateService->defaultPayload('en'),
        ];
        $this->isOverridden = ['sv' => false, 'en' => false];

        if (! $this->selectedService) {
            return;
        }

        $this->statuses = $this->reportTemplateService->getStatusesForService($this->selectedService);

        foreach (['sv', 'en'] as $lang) {
            $this->globalTemplates[$lang] = $this->reportTemplateService->loadTemplate(0, $this->selectedService, $lang);
            $this->isOverridden[$lang] = $this->reportTemplateService->templateExists($this->customerId, $this->selectedService, $lang);
            $this->templates[$lang] = $this->isOverridden[$lang]
                ? $this->reportTemplateService->loadTemplate($this->customerId, $this->selectedService, $lang)
                : $this->globalTemplates[$lang];
        }
    }

    public function render()
    {
        return view('livewire.customer.tabs.reports', [
            'services' => $this->services,
            'statuses' => $this->statuses,
        ]);
    }
}
