<?php

declare(strict_types=1);

namespace App\Livewire\Backend\Reports;

use App\Livewire\Concerns\BuildsReportTemplates;
use App\Services\Reports\ReportTemplateService;
use Illuminate\Support\Collection;
use Livewire\Component;

class Templates extends Component
{
    use BuildsReportTemplates;

    private ReportTemplateService $reportTemplateService;

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

    public function boot(ReportTemplateService $reportTemplateService): void
    {
        $this->reportTemplateService = $reportTemplateService;
    }

    public function mount(): void
    {
        $this->services = $this->reportTemplateService->getBackgroundServices();
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
            $this->reportTemplateService->saveTemplate(0, $this->selectedService, $lang, $this->templates[$lang] ?? []);
        }

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Success'),
            'message' => __('Global report templates saved successfully.'),
        ]);
    }

    private function loadEditorData(): void
    {
        if (! $this->selectedService) {
            $this->statuses = collect([]);
            $this->templates = [
                'sv' => $this->reportTemplateService->defaultPayload('sv'),
                'en' => $this->reportTemplateService->defaultPayload('en'),
            ];
            return;
        }

        $this->statuses = $this->reportTemplateService->getStatusesForService($this->selectedService);
        $this->templates['sv'] = $this->reportTemplateService->loadTemplate(0, $this->selectedService, 'sv');
        $this->templates['en'] = $this->reportTemplateService->loadTemplate(0, $this->selectedService, 'en');
    }

    public function render()
    {
        return view('livewire.backend.reports.templates', [
            'services' => $this->services,
            'statuses' => $this->statuses,
        ]);
    }
}
