<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CandidateMessage;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Services\EmailTemplateRenderer;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/**
 * Manages per-customer × per-service email templates (the `messages` table).
 *
 * Mirrors the old system's admin2/messages.php page.
 * Each row in `messages` stores all status-specific email bodies for
 * one customer + one service type combination.
 */
class MessageTemplateController extends Controller
{
    /**
     * Index: customer + service type selector, template editor.
     */
    public function index(Request $request): Renderable
    {
        $this->authorize('viewAny', Customer::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('Message Templates'));

        $customers = Schema::hasTable('customers')
            ? Customer::with('user')->get()->sortBy(fn ($c) => $c->user?->name ?? '')->values()
            : collect();

        $serviceTypes = Schema::hasTable('service_types')
            ? ServiceType::orderBy('name')->get(['id', 'name'])
            : collect();

        $catalogue = EmailTemplateRenderer::catalogue();

        // Determine which message columns exist in the messages table.
        $messageCols = $this->getMessageColumns();

        return $this->renderViewWithBreadcrumbs('backend.pages.message-templates.index', compact(
            'prefix',
            'customers',
            'serviceTypes',
            'catalogue',
            'messageCols'
        ));
    }

    /**
     * AJAX: Load the messages row for a given customer + service type.
     * Returns all column values as JSON.
     */
    public function load(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
        ]);

        if (! Schema::hasTable('messages')) {
            return response()->json(['messages' => [], 'exists' => false]);
        }

        $row = CandidateMessage::where('cus_id', $validated['cus_id'])
            ->where('interview_id', $validated['interview_id'])
            ->first();

        return response()->json([
            'messages' => $row ? $row->toArray() : [],
            'exists' => $row !== null,
        ]);
    }

    /**
     * AJAX: Save (upsert) a single column value for a customer + service.
     */
    public function save(Request $request): JsonResponse
    {
        $this->authorize('update', Customer::class);

        $allowedCols = $this->getMessageColumns();

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'column' => ['required', 'string', 'in:' . implode(',', $allowedCols)],
            'value' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('messages')) {
            return response()->json(['success' => false, 'message' => 'messages table not available'], 422);
        }

        CandidateMessage::updateOrCreate(
            [
                'cus_id' => $validated['cus_id'],
                'interview_id' => $validated['interview_id'],
            ],
            [
                $validated['column'] => $validated['value'] ?: null,
            ]
        );

        return response()->json(['success' => true, 'message' => __('Template saved.')]);
    }

    /**
     * AJAX: Bulk-save all columns for a customer + service at once.
     */
    public function saveAll(Request $request): JsonResponse
    {
        $this->authorize('update', Customer::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'columns' => ['required', 'array'],
        ]);

        if (! Schema::hasTable('messages')) {
            return response()->json(['success' => false, 'message' => 'messages table not available'], 422);
        }

        $allowedCols = $this->getMessageColumns();
        $update = [];

        foreach ($validated['columns'] as $col => $val) {
            if (in_array($col, $allowedCols, true)) {
                $update[$col] = $val ?: null;
            }
        }

        CandidateMessage::updateOrCreate(
            [
                'cus_id' => $validated['cus_id'],
                'interview_id' => $validated['interview_id'],
            ],
            $update
        );

        return response()->json(['success' => true, 'message' => __('All templates saved.')]);
    }

    /**
     * AJAX: Copy all message templates from one customer+service to another.
     */
    public function copy(Request $request): JsonResponse
    {
        $this->authorize('update', Customer::class);

        $validated = $request->validate([
            'from_cus_id' => ['required', 'integer', 'exists:customers,id'],
            'from_interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'to_cus_id' => ['required', 'integer', 'exists:customers,id'],
            'to_interview_id' => ['required', 'integer', 'exists:service_types,id'],
        ]);

        if (! Schema::hasTable('messages')) {
            return response()->json(['success' => false], 422);
        }

        $source = CandidateMessage::where('cus_id', $validated['from_cus_id'])
            ->where('interview_id', $validated['from_interview_id'])
            ->first();

        if (! $source) {
            return response()->json(['success' => false, 'message' => __('Source template not found.')], 404);
        }

        $allowedCols = $this->getMessageColumns();
        $data = [];
        foreach ($allowedCols as $col) {
            $data[$col] = $source->getAttribute($col);
        }

        CandidateMessage::updateOrCreate(
            [
                'cus_id' => $validated['to_cus_id'],
                'interview_id' => $validated['to_interview_id'],
            ],
            $data
        );

        return response()->json(['success' => true, 'message' => __('Templates copied successfully.')]);
    }

    /**
     * AJAX: Preview a template body with sample data.
     */
    public function preview(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Customer::class);

        $body = $request->input('body', '');
        $rendered = app(EmailTemplateRenderer::class)->previewWithSampleData($body);
        $unknown = EmailTemplateRenderer::unknownPlaceholders($body);

        return response()->json(['html' => $rendered, 'unknown' => $unknown]);
    }

    // -------------------------------------------------------------------------

    /**
     * Returns the list of all valid message column names that exist in the
     * `messages` table (so we never write to unknown columns).
     *
     * @return string[]
     */
    private function getMessageColumns(): array
    {
        if (! Schema::hasTable('messages')) {
            return [];
        }

        $excluded = ['id', 'cus_id', 'interview_id', 'created_at', 'updated_at'];

        return array_values(
            array_diff(
                Schema::getColumnListing('messages'),
                $excluded
            )
        );
    }
}
