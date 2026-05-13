<?php

declare(strict_types=1);

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\CandidateHistory;
use App\Models\Customer;
use App\Services\Candidate\CandidateCreationEmailService;
use App\Services\Candidate\StatusWorkflowService;
use App\Models\Place;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\User;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    public function index(): Renderable
    {
        $this->authorize('viewAny', Candidate::class);

        $this->setBreadcrumbTitle(__('Candidates'));

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.index');
    }

    public function create(): Renderable
    {
        $this->authorize('create', Candidate::class);

        $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('New Candidate'))
            ->addBreadcrumbItem(__('Candidates'), route($prefix . '.candidates.index'));

        $customers = Schema::hasTable('customers')
            ? Customer::query()->with('user')->get()->sortBy(fn ($customer) => $customer->user?->name ?? '')
            : collect();

        $serviceTypes = Schema::hasTable('service_types')
            ? ServiceType::query()->orderBy('name')->get()
            : collect();

        $places = Schema::hasTable('places')
            ? Place::query()->orderBy('name')->get()
            : collect();

        $staff = Schema::hasTable('users')
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Manager', 'Manager with statistics', 'Moderator', 'User']))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.create', [
            'customers' => $customers,
            'serviceTypes' => $serviceTypes,
            'places' => $places,
            'staff' => $staff,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Candidate::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'security' => ['required', 'string', 'max:255'],
            'hasPersonalId' => ['nullable', 'boolean'],
            'vasc_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'place' => ['nullable', 'integer', 'exists:places,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'referensperson' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'combine_interview_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'send_mail_customer' => ['nullable', 'in:yes,no'],
            'send_mail_candidate' => ['nullable', 'in:yes,no'],
        ]);

        if (! Schema::hasTable('candidates')) {
            return back()
                ->withInput()
                ->with('error', __('Candidates table is not available in this environment.'));
        }

        $statusId = $this->resolveDefaultStatusId((int) $validated['interview_id']);

        $candidate = null;

        DB::transaction(function () use ($validated, $statusId, $request, &$candidate): void {
            $cvFiles = $this->uploadCvFiles($request);

            $candidate = Candidate::query()->create([
                'order_id' => $this->generateOrderId(),
                'vasc_id' => ($validated['vasc_id'] ?? null) ?: null,
                'security' => $validated['security'],
                'name' => $validated['name'],
                'surname' => $validated['surname'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'place' => $validated['place'] ?? null,
                'country' => $validated['country'] ?? null,
                'referensperson' => $validated['referensperson'] ?? null,
                'reference' => $validated['reference'] ?? null,
                'comment' => $validated['comment'] ?? null,
                'note' => $validated['note'] ?? null,
                'cus_id' => (int) $validated['cus_id'],
                'interview_id' => (int) $validated['interview_id'],
                'status' => $statusId,
                'staff_id' => ! empty($validated['staff_id']) ? (int) $validated['staff_id'] : null,
                'expired' => 0,
                'hasPersonalId' => ! empty($validated['hasPersonalId']) ? 1 : 0,
                'combine_interview_id' => ! empty($validated['combine_interview_id']) ? (int) $validated['combine_interview_id'] : null,
                'cv' => $cvFiles ?: null,
            ]);
        });

        // Send creation emails after the transaction commits.
        if ($candidate) {
            app(CandidateCreationEmailService::class)->send(
                candidate:         $candidate,
                sendToCustomer:    ($validated['send_mail_customer'] ?? 'yes') === 'yes',
                sendToCandidate:   ($validated['send_mail_candidate'] ?? 'yes') === 'yes',
            );
        }

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        return to_route($prefix . '.candidates.index')
            ->with('success', __('Candidate has been created successfully.'));
    }

    public function historyPreview(Candidate $candidate): JsonResponse
    {
        $this->authorize('viewAny', Candidate::class);

        $items = [];

        if (Schema::hasTable('history')) {
            $items = CandidateHistory::where('order_id', $candidate->id)
                ->orderByDesc('date_time')
                ->limit(6)
                ->get(['desc', 'date_time', 'comment'])
                ->map(fn ($h) => [
                    'desc' => $h->desc,
                    'date_time' => $h->date_time?->format('d M Y H:i'),
                    'comment' => $h->comment,
                ])
                ->all();
        }

        return response()->json(['items' => $items]);
    }

    public function history(Request $request, Candidate $candidate): Renderable
    {
        $this->authorize('viewAny', Candidate::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('History'))
            ->addBreadcrumbItem(__('Candidates'), route($prefix . '.candidates.index'))
            ->addBreadcrumbItem(
                $candidate->name . ' ' . $candidate->surname . ' #' . $candidate->order_id,
                route($prefix . '.candidates.edit', $candidate->id)
            );

        $search = $request->input('search', '');

        $historyQuery = Schema::hasTable('history')
            ? CandidateHistory::where('order_id', $candidate->id)
                ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                    $q2->where('desc', 'like', "%{$search}%")
                       ->orWhere('comment', 'like', "%{$search}%");
                }))
                ->orderByDesc('date_time')
            : null;

        $history = $historyQuery ? $historyQuery->paginate(25)->withQueryString() : collect();
        $total = Schema::hasTable('history')
            ? CandidateHistory::where('order_id', $candidate->id)->count()
            : 0;

        $candidate->load(['customer.user', 'serviceType', 'statusRelation']);

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.history', [
            'candidate' => $candidate,
            'history' => $history,
            'total' => $total,
            'search' => $search,
        ]);
    }

    public function storeHistory(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('update', Candidate::class);

        $validated = $request->validate([
            'desc' => ['required', 'string', 'max:500'],
            'comment' => ['nullable', 'string', 'max:2000'],
        ]);

        app(\App\Services\Candidate\CandidateHistoryService::class)
            ->logManual($candidate, $validated['desc'], $validated['comment'] ?? '');

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        return back()->with('success', __('History entry added.'));
    }

    public function deleteHistory(Request $request, Candidate $candidate, CandidateHistory $entry): RedirectResponse
    {
        $this->authorize('delete', Candidate::class);

        if ((int) $entry->order_id !== $candidate->id) {
            abort(403);
        }

        $entry->delete();

        return back()->with('success', __('History entry deleted.'));
    }

    // -------------------------------------------------------------------------
    // Email resend system
    // -------------------------------------------------------------------------

    public function emails(Request $request, Candidate $candidate): Renderable
    {
        $this->authorize('viewAny', Candidate::class);

        $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('Email Log'))
            ->addBreadcrumbItem(__('Candidates'), route($prefix . '.candidates.index'))
            ->addBreadcrumbItem(
                $candidate->name . ' ' . $candidate->surname . ' #' . $candidate->order_id,
                route($prefix . '.candidates.edit', $candidate->id)
            );

        $filter = $request->input('type', 'all');
        $search = $request->input('search', '');

        $emailsQuery = Schema::hasTable('emails')
            ? \App\Models\CandidateEmail::where('order_id', $candidate->order_id)
                ->when($filter !== 'all', fn ($q) => $q->where('user_type', $filter))
                ->when($search, fn ($q) => $q->where(function ($q2) use ($search) {
                    $q2->where('subject', 'like', "%{$search}%")
                       ->orWhere('email', 'like', "%{$search}%")
                       ->orWhere('msg_type', 'like', "%{$search}%")
                       ->orWhere('text', 'like', "%{$search}%");
                }))
                ->orderByDesc('id')
            : null;

        $emails = $emailsQuery ? $emailsQuery->paginate(20)->withQueryString() : collect();
        $counts = Schema::hasTable('emails')
            ? \App\Models\CandidateEmail::where('order_id', $candidate->order_id)
                ->selectRaw('user_type, count(*) as total')
                ->groupBy('user_type')
                ->pluck('total', 'user_type')
                ->toArray()
            : [];

        $candidate->load(['customer.user', 'serviceType', 'statusRelation']);

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.emails', [
            'candidate' => $candidate,
            'emails' => $emails,
            'counts' => $counts,
            'filter' => $filter,
            'search' => $search,
        ]);
    }

    public function resendEmail(Request $request, Candidate $candidate, \App\Models\CandidateEmail $email): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('update', Candidate::class);

        if ($email->order_id !== $candidate->order_id) {
            abort(403);
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:500'],
            'body' => ['required', 'string'],
        ]);

        try {
            \Illuminate\Support\Facades\Mail::to($email->email, $email->user_name ?? '')
                ->send(new \App\Mail\CandidateStatusMail($validated['subject'], $validated['body']));

            // Log the resend
            if (Schema::hasTable('emails')) {
                \App\Models\CandidateEmail::create([
                    'user_type' => $email->user_type,
                    'user_name' => $email->user_name,
                    'order_id' => $email->order_id,
                    'msg_type' => $email->msg_type . ' (Resent)',
                    'text' => $validated['body'],
                    'email' => $email->email,
                    'subject' => $validated['subject'],
                ]);
            }

            // Audit history
            if (Schema::hasTable('history')) {
                app(\App\Services\Candidate\CandidateHistoryService::class)->log(
                    $candidate->id,
                    "Email resent: {$email->msg_type} → {$email->email}",
                    '— ' . (\Illuminate\Support\Facades\Auth::user()?->name ?? 'admin')
                );
            }

            return back()->with('success', __('Email resent to :email.', ['email' => $email->email]));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Email resend controller failed', ['error' => $e->getMessage()]);
            return back()->with('error', __('Failed to resend: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function edit(Candidate $candidate): Renderable
    {
        $this->authorize('update', Candidate::class);

        $prefix = request()->routeIs('staff.*') ? 'staff' : 'admin';

        $this->setBreadcrumbTitle(__('Edit Candidate'))
            ->addBreadcrumbItem(__('Candidates'), route($prefix . '.candidates.index'));

        $serviceTypes = Schema::hasTable('service_types')
            ? ServiceType::query()->orderBy('name')->get()
            : collect();

        $places = Schema::hasTable('places')
            ? Place::query()->orderBy('name')->get()
            : collect();

        $staff = Schema::hasTable('users')
            ? User::query()
                ->whereHas('roles', fn ($query) => $query->whereIn('name', ['Manager', 'Manager with statistics', 'Moderator', 'User']))
                ->orderBy('name')
                ->get(['id', 'name', 'email'])
            : collect();

        $statuses = Schema::hasTable('statuses')
            ? Status::query()->orderBy('status')->get()
            : collect();

        $existingFiles = $this->parseExistingCvFiles($candidate->cv);

        $candidate->load(['customer.user', 'serviceType', 'statusRelation', 'staff', 'placeRelation']);

        return $this->renderViewWithBreadcrumbs('backend.pages.candidates.edit', [
            'candidate' => $candidate,
            'serviceTypes' => $serviceTypes,
            'places' => $places,
            'staff' => $staff,
            'statuses' => $statuses,
            'existingFiles' => $existingFiles,
        ]);
    }

    public function update(Request $request, Candidate $candidate): RedirectResponse
    {
        $this->authorize('update', Candidate::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'surname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:255'],
            'security' => ['required', 'string', 'max:255'],
            'vasc_id' => ['nullable', 'string', 'max:255'],
            'interview_id' => ['required', 'integer', 'exists:service_types,id'],
            'staff_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'integer', 'exists:statuses,id'],
            'place' => ['nullable', 'integer', 'exists:places,id'],
            'country' => ['nullable', 'string', 'max:255'],
            'booked' => ['nullable', 'date'],
            'background_check_date' => ['nullable', 'date'],
            'delivery_date' => ['nullable', 'date'],
            'economy' => ['nullable', 'in:-1,0,1'],
            'criminal_record' => ['nullable', 'in:-1,0,1'],
            'social' => ['nullable', 'in:-1,0,1'],
            'invoice_sent' => ['nullable', 'boolean'],
            'invoice_date' => ['nullable', 'date'],
            'invoice_genrated' => ['nullable', 'boolean'],
            'combine_interview_id' => ['nullable', 'integer', 'exists:service_types,id'],
            'service_cost' => ['nullable', 'numeric', 'min:0'],
            'travel_cost' => ['nullable', 'numeric', 'min:0'],
            'referensperson' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'files' => ['nullable', 'array'],
            'files.*' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            'remove_files' => ['nullable', 'array'],
            'remove_files.*' => ['nullable', 'string'],
        ]);

        try {
            $oldStatusId = $candidate->status;
            $newStatusId = ! empty($validated['status']) ? (int) $validated['status'] : null;
            $statusChanged = $newStatusId && $newStatusId !== $oldStatusId;

            DB::transaction(function () use ($validated, $request, $candidate, $statusChanged, $newStatusId): void {
                $oldEmail = $candidate->email;

                $existingCv = $candidate->cv ?? '';
                $existingFiles = array_filter(explode(',', $existingCv));

                if (! empty($validated['remove_files'])) {
                    foreach ($validated['remove_files'] as $fileToRemove) {
                        $existingFiles = array_filter($existingFiles, fn ($f) => trim($f) !== trim($fileToRemove));
                        Storage::disk('public')->delete('candidates/' . $fileToRemove);
                    }
                }

                $newFiles = $this->uploadCvFiles($request);
                if ($newFiles) {
                    $existingFiles[] = $newFiles;
                }

                $cvValue = implode(',', array_filter($existingFiles)) ?: null;

                $candidate->update([
                    'name' => $validated['name'],
                    'surname' => $validated['surname'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'security' => $validated['security'],
                    'vasc_id' => ($validated['vasc_id'] ?? null) ?: null,
                    'interview_id' => (int) $validated['interview_id'],
                    'staff_id' => ! empty($validated['staff_id']) ? (int) $validated['staff_id'] : null,
                    'combine_interview_id' => ! empty($validated['combine_interview_id']) ? (int) $validated['combine_interview_id'] : null,
                    'status' => $newStatusId ?? $candidate->status,
                    'place' => ! empty($validated['place']) ? (int) $validated['place'] : null,
                    'country' => ($validated['country'] ?? null) ?: null,
                    'booked' => ($validated['booked'] ?? null) ?: null,
                    'background_check_date' => ($validated['background_check_date'] ?? null) ?: null,
                    'delivery_date' => ($validated['delivery_date'] ?? null) ?: null,
                    'economy' => $validated['economy'] ?? null,
                    'criminal_record' => $validated['criminal_record'] ?? null,
                    'social' => $validated['social'] ?? null,
                    'invoice_sent' => ! empty($validated['invoice_sent']) ? 1 : 0,
                    'invoice_date' => ($validated['invoice_date'] ?? null) ?: null,
                    'invoice_genrated' => ! empty($validated['invoice_genrated']) ? 1 : 0,
                    'service_cost' => isset($validated['service_cost']) && $validated['service_cost'] !== '' ? (float) $validated['service_cost'] : null,
                    'travel_cost' => isset($validated['travel_cost']) && $validated['travel_cost'] !== '' ? (float) $validated['travel_cost'] : null,
                    'referensperson' => ($validated['referensperson'] ?? null) ?: null,
                    'reference' => ($validated['reference'] ?? null) ?: null,
                    'comment' => ($validated['comment'] ?? null) ?: null,
                    'note' => ($validated['note'] ?? null) ?: null,
                    'cv' => $cvValue,
                ]);

                // Sync email in the email-log table if the address changed.
                if ($oldEmail && $oldEmail !== $validated['email'] && Schema::hasTable('emails')) {
                    DB::table('emails')->where('email', $oldEmail)->update(['email' => $validated['email']]);
                }

                // Trigger full status workflow (email, history, combine) when status changed.
                if ($statusChanged && $newStatusId) {
                    $candidate->refresh();
                    app(StatusWorkflowService::class)->handle(
                        candidate: $candidate,
                        newStatusId: $newStatusId,
                        options: [
                            'date' => $validated['booked'] ?? now()->toDateString(),
                            'comment' => '',
                            // Skip double-logging: the update itself is the change, no separate history needed here.
                            'send_email' => true,
                        ]
                    );
                }
            });

            $prefix = $request->routeIs('staff.*') ? 'staff' : 'admin';

            return to_route($prefix . '.candidates.edit', $candidate->id)
                ->with('success', __('Candidate has been updated successfully.'));
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', __('Failed to update candidate: :message', ['message' => $e->getMessage()]));
        }
    }

    public function destroy(Candidate $candidate): RedirectResponse
    {
        $this->authorize('delete', Candidate::class);

        try {
            if (Schema::hasTable('emails')) {
                DB::table('emails')->where('email', $candidate->email)->delete();
            }

            $candidate->delete();

            return back()->with('success', __('Candidate has been deleted successfully.'));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to delete candidate: :message', ['message' => $e->getMessage()]));
        }
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        $this->authorize('delete', Candidate::class);

        $ids = array_filter((array) $request->input('ids', []), 'is_numeric');

        if (empty($ids)) {
            return back()->with('error', __('No candidates selected.'));
        }

        try {
            $candidates = Candidate::whereIn('id', $ids)->get();

            foreach ($candidates as $candidate) {
                if (Schema::hasTable('emails')) {
                    DB::table('emails')->where('email', $candidate->email)->delete();
                }
                $candidate->delete();
            }

            return back()->with('success', __(':count candidates deleted.', ['count' => $candidates->count()]));
        } catch (\Exception $e) {
            return back()->with('error', __('Failed to delete candidates: :message', ['message' => $e->getMessage()]));
        }
    }

    public function services(Request $request): JsonResponse
    {
        $this->authorize('create', Candidate::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
        ]);

        $services = $this->getCustomerServices((int) $validated['cus_id']);

        return response()->json([
            'services' => $services,
            'selected_service_id' => $services->first()['id'] ?? null,
        ]);
    }

    public function form(Request $request): JsonResponse
    {
        $this->authorize('create', Candidate::class);

        $validated = $request->validate([
            'cus_id' => ['required', 'integer', 'exists:customers,id'],
            'interview_id' => ['nullable', 'integer'],
        ]);

        $services = $this->getCustomerServices((int) $validated['cus_id']);
        $selectedServiceId = (int) ($validated['interview_id'] ?? 0);

        if (! $services->contains(fn (array $service): bool => (int) $service['id'] === $selectedServiceId)) {
            $selectedServiceId = (int) ($services->first()['id'] ?? 0);
        }

        $formBuilderData = $this->loadFormBuilderData((int) $validated['cus_id'], $selectedServiceId ?: null);

        return response()->json([
            'selected_service_id' => $selectedServiceId ?: null,
            'form_fields' => $formBuilderData['fields'],
            'has_form_builder' => $formBuilderData['has_form_builder'],
        ]);
    }

    private function generateOrderId(): string
    {
        $chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

        do {
            $candidate = '';
            for ($i = 0; $i < 6; $i++) {
                $candidate .= $chars[random_int(0, strlen($chars) - 1)];
            }
        } while (Candidate::query()->where('order_id', $candidate)->exists());

        return $candidate;
    }

    private function resolveDefaultStatusId(int $serviceTypeId): ?int
    {
        if (! Schema::hasTable('service_types')) {
            return null;
        }

        $serviceCategoryId = ServiceType::query()
            ->whereKey($serviceTypeId)
            ->value('service_category_id');

        if (! $serviceCategoryId) {
            return null;
        }

        if (! Schema::hasTable('statuses')) {
            return null;
        }

        return Status::query()
            ->where('status_type', $serviceCategoryId)
            ->orderBy('id')
            ->value('id');
    }

    /** @return Collection<int, array{id:int,name:string,place:int,country:int}> */
    private function getCustomerServices(int $customerId): Collection
    {
        if (! Schema::hasTable('service_types') || ! Schema::hasTable('service_type_user')) {
            return collect();
        }

        return DB::table('service_type_user')
            ->join('service_types', 'service_types.id', '=', 'service_type_user.service_type_id')
            ->where('service_type_user.cus_id', $customerId)
            ->orderBy('service_types.name')
            ->select(['service_types.id', 'service_types.name', 'service_types.place', 'service_types.country'])
            ->get()
            ->map(fn (object $service): array => [
                'id' => (int) $service->id,
                'name' => (string) $service->name,
                'place' => (int) ((string) ($service->place ?? '0') === '1'),
                'country' => (int) ((string) ($service->country ?? '0') === '1'),
            ])
            ->values();
    }

    /**
     * Upload CV files and return comma-separated filenames string.
     */
    private function uploadCvFiles(Request $request): string
    {
        if (! $request->hasFile('files')) {
            return '';
        }

        $uploaded = [];
        foreach ($request->file('files') as $file) {
            if (! $file->isValid()) {
                continue;
            }
            $filename = time() . '-' . $file->getClientOriginalName();
            Storage::disk('public')->putFileAs('candidates', $file, $filename);
            $uploaded[] = $filename;
        }

        return implode(',', $uploaded);
    }

    /**
     * Parse existing CV field (comma-separated filenames) into array.
     *
     * @return array<string>
     */
    private function parseExistingCvFiles(?string $cv): array
    {
        if (empty($cv)) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $cv))));
    }

    /**
     * @return array{
     *     fields: array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}>,
     *     has_form_builder: bool
     * }
     */
    private function loadFormBuilderData(int $customerId, ?int $serviceTypeId): array
    {
        if (! $serviceTypeId || ! Schema::hasTable('form_builders')) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $row = DB::table('form_builders')
            ->where('cus_id', $customerId)
            ->where('servicetype_id', $serviceTypeId)
            ->first();

        if (! $row || empty($row->form)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $decoded = json_decode((string) $row->form, true);
        if (! is_array($decoded)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $builder = $decoded['form_builder'] ?? $decoded;
        if (! is_array($builder)) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        $personalFields = $this->mapBuilderSection($builder['personal_info'] ?? [], 'personal');
        $billingFields = $this->mapBuilderSection($builder['billing_info'] ?? [], 'billing');
        $fields = array_values(array_merge($personalFields, $billingFields));

        if ($fields === []) {
            return [
                'fields' => $this->defaultCandidateFields(),
                'has_form_builder' => false,
            ];
        }

        return [
            'fields' => $fields,
            'has_form_builder' => true,
        ];
    }

    /**
     * @param array<mixed, mixed> $section
     *
     * @return array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}>
     */
    private function mapBuilderSection(array $section, string $sectionName): array
    {
        $normalized = [];

        foreach ($section as $metaKey => $value) {
            $parts = explode(',', (string) $metaKey);

            $type = trim($parts[0] ?? 'text');
            $label = trim($parts[1] ?? '');
            $name = trim($parts[2] ?? '');
            $placeholder = trim($parts[3] ?? '');
            $required = trim($parts[4] ?? '') === 'required';
            $optionString = trim($parts[7] ?? '');

            if ($name === '') {
                continue;
            }

            if ($placeholder === '') {
                $placeholder = is_string($value) ? trim($value) : '';
            }

            $options = [];
            if ($type === 'select' && $optionString !== '') {
                $options = collect(explode('|', $optionString))
                    ->map(fn (string $option): string => trim($option))
                    ->filter(fn (string $option): bool => $option !== '')
                    ->values()
                    ->all();
            }

            $normalized[] = [
                'section' => $sectionName,
                'type' => $type !== '' ? $type : 'text',
                'label' => $label !== '' ? $label : ucfirst(str_replace('_', ' ', $name)),
                'name' => $name,
                'placeholder' => $placeholder,
                'required' => $required,
                'options' => $options,
            ];
        }

        return $normalized;
    }

    /** @return array<int, array{section:string,type:string,label:string,name:string,placeholder:string,required:bool,options:array<int,string>}> */
    private function defaultCandidateFields(): array
    {
        return [
            ['section' => 'personal', 'type' => 'text', 'label' => 'Security / Date of Birth', 'name' => 'security', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'VASC ID', 'name' => 'vasc_id', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Name', 'name' => 'name', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Surname', 'name' => 'surname', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'email', 'label' => 'Email', 'name' => 'email', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'personal', 'type' => 'text', 'label' => 'Phone', 'name' => 'phone', 'placeholder' => '', 'required' => true, 'options' => []],
            ['section' => 'billing', 'type' => 'text', 'label' => 'Reference (Invoice Recipient)', 'name' => 'referensperson', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'text', 'label' => 'Reference', 'name' => 'reference', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'textarea', 'label' => 'Invoice Comment', 'name' => 'comment', 'placeholder' => '', 'required' => false, 'options' => []],
            ['section' => 'billing', 'type' => 'textarea', 'label' => 'Note', 'name' => 'note', 'placeholder' => '', 'required' => false, 'options' => []],
        ];
    }
}
