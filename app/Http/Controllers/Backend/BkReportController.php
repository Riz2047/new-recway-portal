<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateReportHtml;
use App\Models\Customer;
use App\Services\Reports\ReportHtmlRenderer;
use App\Services\Reports\ReportTemplateService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BkReportController extends Controller
{
    public function __construct(
        private readonly ReportTemplateService $templateService,
        private readonly ReportHtmlRenderer $renderer,
    ) {
    }

    // -------------------------------------------------------------------------
    // Editor page
    // -------------------------------------------------------------------------

    public function edit(Request $request, Candidate $candidate): \Illuminate\View\View
    {
        $this->authorize('viewAny', Customer::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $breadcrumbs = [
            ['label' => __('Candidates'), 'url' => route($prefix . '.candidates.index')],
            ['label' => $candidate->order_id ?? '#' . $candidate->id, 'url' => route($prefix . '.candidates.edit', $candidate)],
            ['label' => __('BK Report')],
        ];

        return view('backend.pages.candidates.bk-report', compact('candidate', 'breadcrumbs', 'prefix'));
    }

    // -------------------------------------------------------------------------
    // Preview — returns clean HTML in a new tab (printable as PDF)
    // -------------------------------------------------------------------------

    public function preview(Request $request, Candidate $candidate, string $lang): Response
    {
        $this->authorize('viewAny', Customer::class);

        if (! in_array($lang, ['sv', 'en'], true)) {
            abort(404);
        }

        $template = $this->resolveTemplate($candidate, $lang);
        $subs = $this->buildSubstitutions($candidate);

        $html = $this->renderer->render($template, $subs, standalone: true);

        return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
    }

    // -------------------------------------------------------------------------
    // Generate PDF via Browsershot
    // -------------------------------------------------------------------------

    public function pdf(Request $request, Candidate $candidate, string $lang): StreamedResponse
    {
        $this->authorize('viewAny', Customer::class);

        if (! in_array($lang, ['sv', 'en'], true)) {
            abort(404);
        }

        $template = $this->resolveTemplate($candidate, $lang);
        $subs = $this->buildSubstitutions($candidate);
        $html = $this->renderer->render($template, $subs, standalone: true);

        $filename = ($candidate->order_id ?? 'report') . '-' . $lang . '.pdf';

        $pdf = Browsershot::html($html)
            ->format('A4')
            ->margins(20, 15, 20, 15)
            ->showBackground()
            ->pdf();

        return response()->streamDownload(
            function () use ($pdf): void { echo $pdf; },
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    // -------------------------------------------------------------------------
    // Upload PDF blob sent from client-side jsPDF
    // -------------------------------------------------------------------------

    public function upload(Request $request, Candidate $candidate): \Illuminate\Http\JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $orderRef = $candidate->order_id ?? 'report-' . $candidate->id;
        $filename = $orderRef . '.pdf';

        $path = $request->file('file')->storeAs(
            'bk-reports/' . $candidate->id,
            $filename,
            'public'
        );

        $candidate->update(['report' => $path]);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'path' => $path,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** @return array{version:int,sections:array<int,array<string,mixed>>} */
    private function resolveTemplate(Candidate $candidate, string $lang): array
    {
        // 1. Candidate-specific saved report
        $saved = CandidateReportHtml::query()
            ->where('candidate_id', $candidate->id)
            ->where('lang', $lang)
            ->first();

        if ($saved && ! empty($saved->report_data)) {
            return $this->templateService->normalizePayload($saved->report_data, $lang);
        }

        // 2. Customer-specific template
        $customerId = $candidate->cus_id;
        $serviceId = $candidate->interview_id;

        if ($customerId && $serviceId) {
            if ($this->templateService->templateExists($customerId, $serviceId, $lang)) {
                return $this->templateService->loadTemplate($customerId, $serviceId, $lang);
            }
        }

        // 3. Global template (cus_id = 0)
        if ($serviceId) {
            $global = $this->templateService->loadTemplate(0, $serviceId, $lang);
            $nonEmpty = count($global['sections'] ?? []) > 0;
            if ($nonEmpty) {
                return $global;
            }
        }

        // 4. Default payload
        return $this->templateService->defaultPayload($lang);
    }

    /** @return array<string,string> */
    private function buildSubstitutions(Candidate $candidate): array
    {
        $name = trim(($candidate->name ?? '') . ' ' . ($candidate->surname ?? ''));
        $company = $candidate->customer?->company
            ?? $candidate->customer?->user?->name
            ?? '';
        $service = $candidate->serviceType?->name ?? '';

        return [
            '{can_name}' => $name ?: '{can_name}',
            '{cus_company}' => $company ?: '{cus_company}',
            '{serviceTitle}' => $service ?: '{serviceTitle}',
        ];
    }
}
