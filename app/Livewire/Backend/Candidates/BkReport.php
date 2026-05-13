<?php

declare(strict_types=1);

namespace App\Livewire\Backend\Candidates;

use App\Livewire\Concerns\BuildsReportTemplates;
use App\Models\Candidate;
use App\Models\CandidateReportHtml;
use App\Services\Reports\ReportTemplateService;
use Livewire\Component;

class BkReport extends Component
{
    use BuildsReportTemplates;

    private ReportTemplateService $reportTemplateService;

    public int $candidateId;
    public string $activeLang = 'sv';

    /** candidate display data (non-reactive) */
    public string $candidateName = '';
    public string $orderRef = '';
    public string $customerName = '';
    public string $serviceName = '';

    /** @var array{sv: array{version:int,sections:array<int,array<string,mixed>>}, en: array{version:int,sections:array<int,array<string,mixed>>}} */
    public array $templates = [
        'sv' => ['version' => 1, 'sections' => []],
        'en' => ['version' => 1, 'sections' => []],
    ];

    /** @var array{sv:bool,en:bool} */
    public array $isOverridden = ['sv' => false, 'en' => false];

    public function boot(ReportTemplateService $reportTemplateService): void
    {
        $this->reportTemplateService = $reportTemplateService;
    }

    public function mount(int $candidateId): void
    {
        $this->candidateId = $candidateId;

        $candidate = Candidate::with(['customer.user', 'serviceType'])->findOrFail($candidateId);

        $this->candidateName = trim(($candidate->name ?? '') . ' ' . ($candidate->surname ?? ''));
        $this->orderRef = $candidate->order_id ?? '#' . $candidate->id;
        $this->customerName = $candidate->customer?->company
            ?? $candidate->customer?->user?->name
            ?? '';
        $this->serviceName = $candidate->serviceType?->name ?? '';

        $this->loadEditorData($candidate);
    }

    public function save(): void
    {
        foreach (['sv', 'en'] as $lang) {
            CandidateReportHtml::query()->updateOrCreate(
                ['candidate_id' => $this->candidateId, 'lang' => $lang],
                ['report_data' => json_encode($this->templates[$lang], JSON_UNESCAPED_UNICODE)]
            );
        }

        $this->isOverridden = ['sv' => true, 'en' => true];

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Saved'),
            'message' => __('Report saved for this candidate.'),
        ]);
    }

    public function resetToTemplate(): void
    {
        CandidateReportHtml::query()
            ->where('candidate_id', $this->candidateId)
            ->delete();

        $candidate = Candidate::with(['customer', 'serviceType'])->findOrFail($this->candidateId);
        $this->loadEditorData($candidate);

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Reset'),
            'message' => __('Report reset to customer/global template.'),
        ]);
    }

    private function loadEditorData(Candidate $candidate): void
    {
        foreach (['sv', 'en'] as $lang) {
            $saved = CandidateReportHtml::query()
                ->where('candidate_id', $this->candidateId)
                ->where('lang', $lang)
                ->first();

            if ($saved && ! empty($saved->report_data)) {
                $this->templates[$lang] = $this->reportTemplateService->normalizePayload($saved->report_data, $lang);
                $this->isOverridden[$lang] = true;
                continue;
            }

            $this->isOverridden[$lang] = false;

            $serviceId = $candidate->interview_id;
            $customerId = $candidate->cus_id;

            if ($customerId && $serviceId && $this->reportTemplateService->templateExists($customerId, $serviceId, $lang)) {
                $this->templates[$lang] = $this->reportTemplateService->loadTemplate($customerId, $serviceId, $lang);
            } elseif ($serviceId) {
                $this->templates[$lang] = $this->reportTemplateService->loadTemplate(0, $serviceId, $lang);
            } else {
                $this->templates[$lang] = $this->reportTemplateService->defaultPayload($lang);
            }
        }
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.backend.candidates.bk-report');
    }
}
