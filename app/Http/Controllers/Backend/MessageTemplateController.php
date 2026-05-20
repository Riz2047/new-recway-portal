<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\CandidateMessage;
use App\Models\Customer;
use App\Models\ServiceType;
use App\Services\CustomerPropagationService;
use App\Services\EmailTemplateRenderer;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Manages per-customer × per-service email templates (the `messages` table).
 *
 * Templates are stored as a JSON object in messages.templates.
 * Key convention: the key is the msg_col value from status_services
 * (e.g. 'approved_msg', 'pending_msg') or a fixed special key
 * ('cus_msg', 'admin_msg', 'staff_msg').
 */
class MessageTemplateController extends Controller
{
    public function __construct(private readonly CustomerPropagationService $propagation)
    {
    }

    /** Fixed templates referenced directly by name in PHP code — always shown. */
    private const SPECIAL_KEYS = [
        'cus_msg' => ['label' => 'Customer — New Order',         'group' => 'Customer'],
        'admin_msg' => ['label' => 'Admin — New Order',             'group' => 'Admin'],
        'staff_msg' => ['label' => 'Staff — Assigned Notification', 'group' => 'Staff'],
    ];

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

        [$messageCols, $colLabels] = $this->buildTemplateMetadata();

        return $this->renderViewWithBreadcrumbs('backend.pages.message-templates.index', compact(
            'prefix',
            'customers',
            'serviceTypes',
            'catalogue',
            'messageCols',
            'colLabels',
        ));
    }

    /**
     * AJAX: Load the templates JSON for a given customer + service type.
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
            'messages' => $row?->templates ?? [],
            'exists' => $row !== null,
        ]);
    }

    /**
     * AJAX: Save (upsert) a single template key for a customer + service.
     */
    public function save(Request $request): JsonResponse
    {
        $this->authorize('update', Customer::class);

        $allowedKeys = $this->getAllowedKeys();

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'column' => ['required', 'string', 'in:' . implode(',', $allowedKeys)],
            'value' => ['nullable', 'string'],
        ]);

        if (! Schema::hasTable('messages')) {
            return response()->json(['success' => false, 'message' => 'messages table not available'], 422);
        }

        $row = CandidateMessage::firstOrNew([
            'cus_id' => $validated['cus_id'],
            'interview_id' => $validated['interview_id'],
        ]);

        $row->setTemplate($validated['column'], $validated['value'] ?: null);

        // Push the full updated templates to child customers for the same service.
        $this->propagation->propagateMessages(
            $validated['cus_id'],
            $validated['interview_id'],
            $row->fresh()->templates ?? []
        );

        return response()->json(['success' => true, 'message' => __('Template saved.')]);
    }

    /**
     * AJAX: Bulk-save all template keys for a customer + service at once.
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

        $allowedKeys = array_flip($this->getAllowedKeys());
        $templates = [];

        foreach ($validated['columns'] as $key => $val) {
            if (isset($allowedKeys[$key])) {
                $templates[$key] = ($val !== null && $val !== '') ? $val : null;
            }
        }

        CandidateMessage::updateOrCreate(
            [
                'cus_id' => $validated['cus_id'],
                'interview_id' => $validated['interview_id'],
            ],
            ['templates' => $templates]
        );

        // Push the saved templates to child customers for the same service.
        $this->propagation->propagateMessages(
            $validated['cus_id'],
            $validated['interview_id'],
            $templates
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

        $copiedTemplates = $source->templates ?? [];

        CandidateMessage::updateOrCreate(
            [
                'cus_id' => $validated['to_cus_id'],
                'interview_id' => $validated['to_interview_id'],
            ],
            ['templates' => $copiedTemplates]
        );

        // Propagate the copied templates to children of the target customer.
        $this->propagation->propagateMessages(
            $validated['to_cus_id'],
            $validated['to_interview_id'],
            $copiedTemplates
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
     * Build the full list of template keys and their display metadata.
     *
     * Keys are msg_col values (e.g. 'approved_msg') or special keys ('cus_msg', …).
     * Sourced from status_services.msg_col across all statuses.
     *
     * Returns [$messageCols, $colLabels]:
     *   $messageCols — flat ordered array of all keys
     *   $colLabels   — key => ['label' => '…', 'group' => '…', 'code' => '…']
     */
    private function buildTemplateMetadata(): array
    {
        $messageCols = [];
        $colLabels = [];

        // Special fixed keys always shown first.
        foreach (self::SPECIAL_KEYS as $key => $meta) {
            $messageCols[] = $key;
            $colLabels[$key] = array_merge($meta, ['code' => $key]);
        }

        // Dynamic keys: all distinct msg_col values from status_services,
        // joined with statuses to get the human-readable label and group.
        if (Schema::hasTable('status_services') && Schema::hasTable('statuses')) {
            DB::table('status_services')
                ->join('statuses', 'statuses.id', '=', 'status_services.status_id')
                ->leftJoin('service_categories', 'service_categories.id', '=', 'statuses.status_type')
                ->whereNotNull('status_services.msg_col')
                ->where('status_services.msg_col', '!=', '')
                ->select(
                    'status_services.msg_col',
                    'statuses.status as status_name',
                    'statuses.variable',
                    'service_categories.name as category_name'
                )
                ->orderBy('service_categories.name')
                ->orderBy('statuses.status')
                ->get()
                ->unique('msg_col')   // each msg_col shown once even if shared by services
                ->each(function ($row) use (&$messageCols, &$colLabels): void {
                    $key = $row->msg_col;
                    if (isset($colLabels[$key])) {
                        return; // already added (special key or duplicate msg_col)
                    }
                    $messageCols[] = $key;
                    $colLabels[$key] = [
                        'label' => $row->status_name,
                        'group' => $row->category_name ?? 'Status',
                        'code' => $row->variable,
                    ];
                });
        }

        return [$messageCols, $colLabels];
    }

    /** @return string[] */
    private function getAllowedKeys(): array
    {
        [$keys] = $this->buildTemplateMetadata();

        return $keys;
    }
}
