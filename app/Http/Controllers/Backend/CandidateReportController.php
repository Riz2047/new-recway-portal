<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Customer;
use App\Services\Candidate\InterviewReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serves security/interview report files securely through an authenticated route.
 * Files are stored in storage/app/security-reports/ (private disk) and are
 * NOT directly accessible via a public URL.
 */
class CandidateReportController extends Controller
{
    public function __construct(private readonly InterviewReportService $reportService)
    {
    }

    // -------------------------------------------------------------------------
    // Download / stream a report file
    // -------------------------------------------------------------------------

    public function download(Request $request, Candidate $candidate, string $type): StreamedResponse
    {
        $this->authorize('viewAny', Customer::class);

        if (! in_array($type, InterviewReportService::ALLOWED_TYPES, true)) {
            abort(404, 'Unknown report type.');
        }

        $reports = $this->reportService->getReports($candidate);
        $filename = $reports[$type] ?? null;

        if (! $filename) {
            abort(404, 'Report not uploaded yet.');
        }

        $disk = InterviewReportService::DISK;

        if (! Storage::disk($disk)->exists($filename)) {
            abort(404, 'Report file not found on disk.');
        }

        // Determine a user-friendly display name.
        $labels = [
            InterviewReportService::TYPE_SPI => 'SPI-Report',
            InterviewReportService::TYPE_ELLEVIO => 'Ellevio-Report',
            InterviewReportService::TYPE_TIMRA => 'Timra-Report',
        ];

        $baseName = basename($filename);
        $displayName = ($labels[$type] ?? $type) . '-' . ($candidate->order_id ?? $candidate->id) . '-' . $baseName;

        // Detect MIME type.
        $absolutePath = Storage::disk($disk)->path($filename);
        $mimeType = mime_content_type($absolutePath) ?: 'application/octet-stream';

        // Stream inline (preview in browser) for PDF, force download for others.
        $disposition = str_contains($mimeType, 'pdf') ? 'inline' : 'attachment';

        return Storage::disk($disk)->download($filename, $displayName, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => "{$disposition}; filename=\"{$displayName}\"",
        ]);
    }

    // -------------------------------------------------------------------------
    // Delete a report (called from the full edit page)
    // -------------------------------------------------------------------------

    public function destroy(Request $request, Candidate $candidate, string $type): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', Customer::class);

        if (! in_array($type, InterviewReportService::ALLOWED_TYPES, true)) {
            abort(404);
        }

        $this->reportService->delete($candidate, $type);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        return back()->with('success', __('Report deleted successfully.'));
    }
}
