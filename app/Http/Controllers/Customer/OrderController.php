<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\CompanyManager;
use App\Models\Customer;
use App\Models\CustomerQuestion;
use App\Models\Place;
use App\Models\ServiceCategory;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\User;
use App\Services\Candidate\StatusWorkflowService;
use App\Services\FormBuilderFieldService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    /** Status IDs that start the 14-day archive countdown */
    private const ARCHIVE_STATUS_IDS = [4, 7, 9, 21, 22, 37, 40, 42, 52, 55, 56];

    /** Order statuses that are awaiting a customer (company manager) decision. */
    private const AWAITING_DECISION_STATUSES = [6, 47, 39];

    /** "Change Status" dropdown options: Approved / Denied. */
    private const DECISION_STATUSES = [4, 7];

    /** "Change Status" dropdown options when the order is in follow-up review (status 39). */
    private const DECISION_STATUSES_FOLLOWUP = [37, 42];

    // ── Orders list ─────────────────────────────────────────────────────────

    public function index(): View
    {
        $user = Auth::user();
        $userId = $user->id;
        $customerIds = $this->resolveCustomerIds($userId);
        $isManager = $this->isCompanyManager($userId);

        $orders = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.expired', 0)
            ->leftJoin('service_types', 'candidates.interview_id', '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses', 'candidates.status', '=', 'statuses.id')
            ->leftJoin('users as staff', 'candidates.staff_id', '=', 'staff.id')
            ->leftJoin('customers', 'candidates.cus_id', '=', 'customers.id')
            ->select(
                'candidates.id',
                'candidates.order_id',
                'candidates.name',
                'candidates.surname',
                'candidates.status',
                'candidates.booked',
                'candidates.delivery_date',
                'candidates.cus_id',
                'candidates.created_at',
                'candidates.created',
                'service_types.name       as service_name',
                'service_categories.id   as service_category_id',
                'service_categories.name as service_category_name',
                'statuses.id    as status_id',
                'statuses.status as status_title',
                'statuses.color  as status_color',
                'staff.name      as staff_name',
                'customers.company as company_name',
            )
            ->orderByDesc('candidates.created_at')
            ->get();

        // Add archive countdown to each order
        foreach ($orders as $order) {
            $order->days_to_archive = $this->archiveCountdown($order);
        }

        // Remove orders whose countdown has already expired
        $orders = $orders->filter(fn ($o) => $o->days_to_archive !== 'expired');

        // Service categories for filter buttons
        $serviceCategories = ServiceCategory::orderBy('name')->get();

        // Statuses with counts (only those that appear in current orders)
        $statusIds = $orders->pluck('status')->unique()->filter()->values()->all();
        $statusesWithCounts = Status::whereIn('id', $statusIds)->orderBy('status')->get();
        foreach ($statusesWithCounts as $s) {
            $s->count = $orders->where('status', $s->id)->count();
        }

        // Per-category order counts
        $catCounts = $orders->groupBy('service_category_id')
            ->map(fn ($g) => $g->count());

        return view('customer.orders.index', compact(
            'orders',
            'serviceCategories',
            'isManager',
            'statusesWithCounts',
            'catCounts'
        ));
    }

    // ── View single order ────────────────────────────────────────────────────

    public function show(int $id, FormBuilderFieldService $formBuilder): View|RedirectResponse
    {
        $userId = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);

        $candidate = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.id', $id)
            ->leftJoin('service_types', 'candidates.interview_id', '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses', 'candidates.status', '=', 'statuses.id')
            ->leftJoin('users as staff_u', 'candidates.staff_id', '=', 'staff_u.id')
            ->leftJoin('places', 'candidates.place', '=', 'places.id')
            ->select(
                'candidates.*',
                'service_types.name       as service_name',
                'service_categories.name as service_category_name',
                'statuses.status  as status_title',
                'statuses.color   as status_color',
                'statuses.variable as status_variable',
                'staff_u.name     as staff_name',
                'places.name      as place_name',
            )
            ->first();

        if (! $candidate) {
            return redirect()->route('customer.orders.index')
                ->with('error', __('Order not found.'));
        }

        // History timeline (past records only)
        $history = CandidateHistory::where('order_id', $candidate->id)
            ->where('date_time', '<=', now())
            ->orderByDesc('date_time')
            ->get();

        // Decode meta_data (custom form answers)
        $metaData = [];
        if ($candidate->meta_data) {
            $decoded = json_decode($candidate->meta_data, true);
            $metaData = is_array($decoded) ? $decoded : [];
        }

        // CV files
        $cvFiles = $candidate->cv
            ? array_filter(explode(',', $candidate->cv))
            : [];

        // Archive countdown
        $daysToArchive = $this->archiveCountdown($candidate);

        // Check whether this customer is allowed to upload security reports.
        $customerId = $this->getCustomerId($userId);
        $sendSecurityReport = (bool) Customer::where('id', $customerId)->value('send_security_report');

        // "Change Status" is only offered to company managers, and only while
        // the order is awaiting a customer decision (statuses 6, 47, 39).
        $changeableStatuses = $this->isCompanyManager((int) $customerId)
            ? $this->changeableStatusesFor($candidate)
            : collect();

        // Show existing report filename if one was already uploaded.
        $existingReport = $candidate->basic_investigation_result ?: null;

        // Billing field labels — use the service's custom form labels when available.
        $formData = $formBuilder->load((int) $candidate->cus_id, $candidate->interview_id);
        $billingLabels = $formData['has_form_builder']
            ? $formBuilder->resolveBillingLabels($formData['fields'])
            : [];

        return view('customer.orders.show', compact(
            'candidate',
            'history',
            'metaData',
            'cvFiles',
            'daysToArchive',
            'changeableStatuses',
            'sendSecurityReport',
            'existingReport',
            'billingLabels',
        ));
    }

    // ── Edit order ───────────────────────────────────────────────────────────

    public function edit(int $id): View|RedirectResponse
    {
        $userId = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);

        $candidate = Candidate::query()
            ->whereIn('candidates.cus_id', $customerIds)
            ->where('candidates.id', $id)
            ->leftJoin('service_types', 'candidates.interview_id', '=', 'service_types.id')
            ->leftJoin('service_categories', 'service_types.service_category_id', '=', 'service_categories.id')
            ->leftJoin('statuses', 'candidates.status', '=', 'statuses.id')
            ->select(
                'candidates.*',
                'service_types.name           as service_name',
                'service_types.place          as service_place',
                'service_categories.id        as service_category_id',
                'service_categories.name      as service_category_name',
                'statuses.status              as status_title',
                'statuses.color               as status_color',
            )
            ->first();

        if (! $candidate) {
            return redirect()->route('customer.orders.index')
                ->with('error', __('Order not found.'));
        }

        // Don't allow edit on cancelled/closed statuses
        if (in_array($candidate->status, [9, 40, 56])) {
            return redirect()->route('customer.orders.show', $id)
                ->with('error', __('This order cannot be edited.'));
        }

        $places = Place::orderBy('name')->get();
        $cvFiles = $candidate->cv
            ? array_values(array_filter(explode(',', $candidate->cv)))
            : [];
        $metaData = $candidate->meta_data
            ? (json_decode($candidate->meta_data, true) ?? [])
            : [];

        return view('customer.orders.edit', compact(
            'candidate',
            'places',
            'cvFiles',
            'metaData'
        ));
    }

    // ── Update order ─────────────────────────────────────────────────────────

    public function update(Request $request, int $id): RedirectResponse
    {
        $userId = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);

        $candidate = Candidate::whereIn('cus_id', $customerIds)->findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'security' => 'required|string|max:255',
            'hasPersonalId' => 'required|in:0,1',
            'cv.*' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        // ── CV file management ───────────────────────────────────────────────
        $existingFiles = $candidate->cv
            ? array_values(array_filter(explode(',', $candidate->cv)))
            : [];

        // Remove files the user marked for deletion
        $removedFiles = array_filter(explode(',', $request->input('removed_files', '')));
        foreach ($removedFiles as $file) {
            $existingFiles = array_values(array_filter($existingFiles, fn ($f) => trim($f) !== trim($file)));
        }

        // Upload new CV files
        if ($request->hasFile('cv')) {
            $remaining = 10 - count($existingFiles);
            if ($remaining < 1) {
                return back()->withInput()->with('error', __('Maximum 10 CV files allowed.'));
            }
            $uploadDir = storage_path('app/public/uploads');
            if (! is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            foreach (array_slice($request->file('cv'), 0, $remaining) as $file) {
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $file->move($uploadDir, $filename);
                $existingFiles[] = $filename;
            }
        }

        // ── Save ────────────────────────────────────────────────────────────
        $candidate->fill([
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'security' => $request->security,
            'hasPersonalId' => $request->hasPersonalId,
            'place' => $request->input('place') ?: null,
            'country' => $request->input('country') ?: null,
            'referensperson' => $request->input('referensperson') ?: null,
            'reference' => $request->input('reference') ?: null,
            'comment' => $request->input('comment') ?: null,
            'note' => $request->input('note') ?: null,
            'cv' => $existingFiles ? implode(',', $existingFiles) : null,
            'meta_data' => $request->has('custom_answers')
                                    ? json_encode($request->input('custom_answers'))
                                    : $candidate->meta_data,
        ]);
        $candidate->save();

        CandidateHistory::create([
            'order_id' => $candidate->id,
            'desc' => 'Order updated by customer',
            'date_time' => now(),
        ]);

        return redirect()->route('customer.orders.show', $candidate->id)
            ->with('success', __('Order updated successfully.'));
    }

    // ── Cancel order ─────────────────────────────────────────────────────────

    public function cancel(Request $request, int $id): RedirectResponse
    {
        $customerIds = $this->resolveCustomerIds(Auth::id());

        $candidate = Candidate::whereIn('cus_id', $customerIds)->findOrFail($id);

        // Find the correct cancel status for this service's category
        $service = ServiceType::find($candidate->interview_id);
        $cancelStatus = null;
        if ($service) {
            $cancelStatus = Status::where('status_type', $service->service_category_id)
                ->where(function ($q) {
                    $q->where('variable', 'bkcanceledbycustomer')
                      ->orWhere('variable', 'canceledbycustomer');
                })
                ->first();
        }

        if ($cancelStatus) {
            $candidate->status = $cancelStatus->id;
            $candidate->save();
        }

        $user = Auth::user();
        $comment = $request->input('comment', '');
        if ($comment) {
            $comment .= "\n— " . $user->name;
        }

        CandidateHistory::create([
            'order_id' => $candidate->id,
            'desc' => 'Order cancelled by customer',
            'date_time' => now(),
            'comment' => $comment,
        ]);

        return redirect()->route('customer.orders.index')
            ->with('success', __('Order cancelled successfully.'));
    }

    // ── Change status ─────────────────────────────────────────────────────────

    public function changeStatus(Request $request, int $id): RedirectResponse
    {
        $userId = Auth::id();
        $customerIds = $this->resolveCustomerIds($userId);

        $candidate = Candidate::whereIn('cus_id', $customerIds)->findOrFail($id);

        $request->validate([
            'status' => 'required|exists:statuses,id',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Only company managers may change the status, and only to one of the
        // options offered for this order's current status.
        $customerId = $this->getCustomerId($userId);
        $allowed = $this->isCompanyManager((int) $customerId)
            ? $this->changeableStatusesFor($candidate)->pluck('id')
            : collect();

        if (! $allowed->contains((int) $request->status)) {
            return back()->with('error', __('Invalid status selection.'));
        }

        $user = Auth::user();
        $comment = $request->input('comment', '');

        try {
            app(StatusWorkflowService::class)->handle(
                candidate: $candidate,
                newStatusId: (int) $request->status,
                options: [
                    'date' => now()->toDateString(),
                    'comment' => $comment
                        ? $comment . '<br>-' . $user?->name
                        : '-' . $user?->name,
                ]
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('customer.orders.show', $candidate->id)
            ->with('success', __('Status updated successfully.'));
    }

    /**
     * Statuses a company-manager customer may change an order to from its
     * current status. Mirrors the old new_customer view-order "Change Status"
     * dropdown: only orders awaiting a decision (statuses 6, 47, 39) offer
     * Approved/Denied — or their follow-up equivalents while in status 39.
     */
    private function changeableStatusesFor(Candidate $candidate): Collection
    {
        if (! in_array($candidate->status, self::AWAITING_DECISION_STATUSES, true)) {
            return collect();
        }

        $ids = $candidate->status === 39
            ? self::DECISION_STATUSES_FOLLOWUP
            : self::DECISION_STATUSES;

        return Status::whereIn('id', $ids)->orderBy('id')->get();
    }

    // ── Create order ─────────────────────────────────────────────────────────

    public function create(): View|RedirectResponse
    {
        $customerId = $this->getCustomerId(Auth::id());
        if (! $customerId) {
            return redirect()->route('customer.orders.index')
                ->with('error', __('Customer profile not found.'));
        }

        // Service categories this customer has access to
        $serviceCategories = ServiceCategory::whereHas('serviceTypes', function ($q) use ($customerId) {
            $q->whereHas('customers', fn ($q2) => $q2->where('customers.id', $customerId));
        })->orderBy('name')->get();

        // Places for location-based services
        $places = Place::orderBy('name')->get();

        // Custom questions defined for this customer
        $customQuestions = CustomerQuestion::where('cus_id', $customerId)->first();
        $questions = [];
        if ($customQuestions && $customQuestions->meta_data) {
            $questions = is_array($customQuestions->meta_data)
                ? $customQuestions->meta_data
                : json_decode($customQuestions->meta_data, true) ?? [];
        }

        return view('customer.orders.create', compact(
            'serviceCategories',
            'places',
            'questions',
        ));
    }

    /** AJAX — return services for a given category that the customer can order. */
    public function getServices(Request $request): JsonResponse
    {
        $customerId = $this->getCustomerId(Auth::id());
        $categoryId = (int) $request->input('category_id');

        $services = ServiceType::where('service_category_id', $categoryId)
            ->whereHas('customers', fn ($q) => $q->where('customers.id', $customerId))
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'place', 'country', 'delivery_days', 'description']);

        return response()->json($services);
    }

    /** AJAX — return custom form definition for a service (mirrors old new_customer fetch_form). */
    public function fetchForm(Request $request, FormBuilderFieldService $formBuilder): JsonResponse
    {
        $customerId = $this->getCustomerId(Auth::id());
        if (! $customerId) {
            return response()->json([
                'form_fields' => [],
                'has_form_builder' => false,
            ]);
        }

        $request->validate([
            'service_id' => ['required', 'integer'],
        ]);

        $serviceTypeId = (int) $request->input('service_id');
        $data = $formBuilder->load($customerId, $serviceTypeId);

        return response()->json([
            'form_fields' => $data['fields'],
            'has_form_builder' => $data['has_form_builder'],
        ]);
    }

    /** Save a new order (AJAX — returns JSON). */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $customerId = $this->getCustomerId($userId);

        if (! $customerId) {
            return response()->json(['success' => false, 'error' => __('Customer profile not found.')], 422);
        }

        $request->validate([
            'service_type_id' => 'required|exists:service_types,id',
            'name' => 'required|string|max:255',
            'surname' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:50',
            'hasPersonalId' => 'required|in:0,1',
            'security' => 'required|string|max:255',
            'cv' => 'nullable|array|max:10',
            'cv.*' => 'file|mimes:pdf,doc,docx|max:10240',
            'agreed' => 'accepted',
            'form_builder' => ['nullable', 'array'],
            'form_builder.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $service = ServiceType::with('serviceCategory')->findOrFail($request->service_type_id);

        // ── Duplicate detection ──────────────────────────────────────────────
        $companyCustomerIds = $this->resolveCustomerIds($userId);

        $duplicate = Candidate::whereIn('cus_id', $companyCustomerIds)
            ->where('expired', 0)
            ->whereHas(
                'serviceType',
                fn ($q) =>
                $q->where('service_category_id', $service->service_category_id)
            )
            ->where(function ($q) use ($request) {
                $q->where('email', $request->email)
                  ->orWhere('phone', $request->phone);

                if ($request->hasPersonalId == '1') {
                    $normalized = str_replace('-', '', $request->security);
                    $q->orWhereRaw("REPLACE(security, '-', '') = ?", [$normalized]);
                }
            })
            ->exists();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'error' => __('A duplicate candidate already exists for this service.'),
            ], 422);
        }

        // ── CV file uploads ──────────────────────────────────────────────────
        $cvFilenames = [];
        if ($request->hasFile('cv')) {
            $uploadDir = public_path('../uploads');
            if (! is_dir($uploadDir)) {
                $uploadDir = storage_path('app/public/uploads');
                if (! is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
            }
            foreach ($request->file('cv') as $file) {
                $filename = uniqid() . '_' . $file->getClientOriginalName();
                $file->move($uploadDir, $filename);
                $cvFilenames[] = $filename;
            }
        }

        // ── Initial status ───────────────────────────────────────────────────
        $initialStatus = Status::where('status_type', $service->service_category_id)
            ->where(function ($q) {
                $q->where('variable', 'new_order')
                  ->orWhere('variable', 'new_order_background')
                  ->orWhere('variable', 'New_order_followuppinterview');
            })
            ->first();

        $rawFormBuilder = $request->input('form_builder', []);
        $metaData = ! empty($rawFormBuilder)
            ? json_encode($rawFormBuilder, JSON_UNESCAPED_UNICODE)
            : null;

        // ── Save order ───────────────────────────────────────────────────────
        $candidate = Candidate::create([
            'order_id' => $this->generateOrderId(),
            'name' => $request->name,
            'surname' => $request->surname,
            'email' => $request->email,
            'phone' => $request->phone,
            'security' => $request->security,
            'hasPersonalId' => $request->hasPersonalId,
            'place' => $request->place ?: null,
            'country' => $request->country ?: null,
            'cv' => $cvFilenames ? implode(',', $cvFilenames) : null,
            'referensperson' => $request->referensperson ?: null,
            'reference' => $request->reference ?: null,
            'comment' => $request->comment ?: null,
            'note' => $request->note ?: null,
            'cus_id' => $customerId,
            'interview_id' => $service->id,
            'status' => $initialStatus?->id ?? 0,
            'staff_id' => null,
            'expired' => 0,
            'meta_data' => $metaData,
            'meta_info' => json_encode([
                'send_email' => $request->input('sendMail', 'no'),
                'created_by' => $userId,
                'created_on' => now()->toDateTimeString(),
                'user' => 'Customer',
            ]),
        ]);

        // ── History record ───────────────────────────────────────────────────
        CandidateHistory::create([
            'order_id' => $candidate->id,
            'desc' => 'Order created by customer',
            'date_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'orderId' => $candidate->order_id,
            'keyId' => $candidate->id,
        ]);
    }

    // ── Security report upload ───────────────────────────────────────────────

    /**
     * Upload a PDF security report for an order.
     * Mirrors old system: new_customer/CandidateController::uploadPDF()
     * Stores to the shared security-report-uploads directory and updates
     * candidates.basic_investigation_result with the filename.
     */
    public function uploadSecurityReport(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $customerIds = $this->resolveCustomerIds(Auth::id());
        $candidate = Candidate::whereIn('cus_id', $customerIds)->findOrFail($id);

        // Permission gate — customer must have send_security_report enabled.
        $customerId = $this->getCustomerId(Auth::id());
        if (! Customer::where('id', $customerId)->value('send_security_report')) {
            return response()->json([
                'success' => false,
                'error' => __('You do not have permission to upload security reports.'),
            ], 403);
        }

        // Filename matches old system convention: {order_id}_{cus_id}.pdf
        $filename = $candidate->order_id . '_' . $candidate->cus_id . '.pdf';
        $uploadDir = base_path('../security-report-uploads');

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $request->file('file')->move($uploadDir, $filename);

        $candidate->basic_investigation_result = $filename;
        $candidate->save();

        Log::info('Customer uploaded security report.', [
            'candidate_id' => $candidate->id,
            'order_id' => $candidate->order_id,
            'customer_id' => $customerId,
        ]);

        return response()->json([
            'success' => true,
            'filename' => $filename,
            'message' => __('Security report uploaded successfully.'),
        ]);
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /**
     * candidates.cus_id references customers.id, not users.id.
     * This resolves the customer's own customers.id plus any visible peer IDs
     * (company manager scope, group scope).
     */
    private function resolveCustomerIds(int $userId): array
    {
        $customer = Customer::where('user_id', $userId)->first();
        if (! $customer) {
            return [];
        }

        $ids = [$customer->id];

        // Company manager: include all colleagues at the same company
        $manager = CompanyManager::where('cus_id', $customer->id)->first();
        if ($manager?->company) {
            $companyIds = Customer::whereRaw('TRIM(company) = ?', [trim($manager->company)])
                ->pluck('id')->toArray();
            $ids = array_merge($ids, $companyIds);
        }

        // Group-based visibility
        if ($customer->groups) {
            foreach (explode(',', $customer->groups) as $group) {
                $groupIds = Customer::where('groups', 'like', '%' . trim($group) . '%')
                    ->pluck('id')->toArray();
                $ids = array_merge($ids, $groupIds);
            }
        }

        return array_values(array_unique($ids));
    }

    private function isCompanyManager(int $userId): bool
    {
        return CompanyManager::where('cus_id', $userId)->whereNotNull('company')->exists();
    }

    /** Returns customers.id for the given users.id. */
    private function getCustomerId(int $userId): ?int
    {
        return Customer::where('user_id', $userId)->value('id');
    }

    /** Generate a unique 6-char uppercase order ID. */
    private function generateOrderId(): string
    {
        do {
            $id = strtoupper(Str::random(6));
        } while (Candidate::where('order_id', $id)->exists());

        return $id;
    }

    /** Returns remaining days (int), 'expired', or 'N/A'. */
    private function archiveCountdown(object $order): int|string
    {
        if (! in_array($order->status, self::ARCHIVE_STATUS_IDS)) {
            return 'N/A';
        }

        $last = CandidateHistory::where('order_id', $order->id)
            ->orderByDesc('id')->first();

        if (! $last) {
            return 'N/A';
        }

        $remaining = 14 - (int) Carbon::parse($last->date_time)->diffInDays(now());

        return $remaining > 0 ? $remaining : 'expired';
    }
}
