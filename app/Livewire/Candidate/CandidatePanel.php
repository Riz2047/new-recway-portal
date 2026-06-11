<?php

declare(strict_types=1);

namespace App\Livewire\Candidate;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateComment;
use App\Models\CandidateEmail;
use App\Models\CandidateHistory;
use App\Models\ServiceType;
use App\Models\Status;
use App\Models\UploadedPdfCandidate;
use App\Models\User;
use App\Services\Candidate\CandidateHistoryService;
use App\Services\Candidate\CombineInterviewService;
use App\Services\Candidate\InterviewReportService;
use App\Services\Candidate\StatusWorkflowService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class CandidatePanel extends Component
{
    use WithFileUploads;

    public int $candidateId = 0;
    public string $activeTab = 'profile';
    public string $activeActionTab = 'records';

    // Comment form
    public string $newComment = '';

    // Status form
    public ?int $newStatusId = null;
    public string $statusDate = '';
    public string $statusComment = '';
    public ?int $combineInterviewId = null;  // chosen when status triggers combine transfer

    // Staff form
    public ?int $newStaffId = null;
    public string $staffComment = '';

    // BK inline radios
    public string $economy = '-1';
    public string $criminalRecord = '-1';
    public string $social = '-1';

    // Invoice/records
    public bool $invoiceSent = false;
    public string $invoiceDate = '';
    public bool $reportedToSm = false;

    // File uploads (CV, BK docs, interview template)
    public $cvFiles = [];
    public $bkFile = null;
    public int $bkFileFor = 1;
    public $interviewTemplateFile = null;

    // Interview / security report uploads (SPI, Ellevio, Timrå)
    public $spiReportFile = null;
    public $ellevioReportFile = null;
    public $timraReportFile = null;

    // Inline candidate edit form fields
    public string $editName = '';
    public string $editSurname = '';
    public string $editEmail = '';
    public string $editPhone = '';
    public string $editSecurity = '';
    public string $editVascId = '';
    public ?int $editInterviewId = null;
    public ?int $editStaffId = null;
    public ?int $editPlace = null;
    public string $editCountry = '';
    public string $editBackgroundCheckDate = '';
    public string $editDeliveryDate = '';
    public string $editBookedDate = '';
    public string $editNote = '';
    public string $editServiceCost = '';
    public string $editTravelCost = '';
    public ?int   $editCombineInterviewId = null;  // per-candidate target for the combine feature

    #[On('openCandidatePanel')]
    public function openPanel(int $id): void
    {
        $this->candidateId = $id;
        $this->resetForms();
        $this->loadFromCandidate();
        $this->dispatch('candidatePanelOpened');
    }

    private function loadFromCandidate(): void
    {
        $candidate = $this->getCandidate();
        if (! $candidate) {
            return;
        }

        $this->economy = (string) ($candidate->economy ?? '-1');
        $this->criminalRecord = (string) ($candidate->criminal_record ?? '-1');
        $this->social = (string) ($candidate->social ?? '-1');
        $this->invoiceSent = (bool) $candidate->invoice_sent;
        $this->invoiceDate = $candidate->invoice_date?->format('Y-m-d') ?? '';
        $this->reportedToSm = (bool) $candidate->reported_to_sm;
        $this->newStatusId = $candidate->status;
        $this->newStaffId = $candidate->staff_id;
        $this->statusDate = now()->format('Y-m-d');

        // Inline edit fields
        $this->editName = $candidate->name ?? '';
        $this->editSurname = $candidate->surname ?? '';
        $this->editEmail = $candidate->email ?? '';
        $this->editPhone = $candidate->phone ?? '';
        $this->editSecurity = $candidate->security ?? '';
        $this->editVascId = $candidate->vasc_id ?? '';
        $this->editInterviewId = $candidate->interview_id;
        $this->editStaffId = $candidate->staff_id;
        $this->editPlace = $candidate->place ? (int) $candidate->place : null;
        $this->editCountry = $candidate->country ?? '';
        $this->editBackgroundCheckDate = $candidate->background_check_date?->format('Y-m-d') ?? '';
        $this->editDeliveryDate = $candidate->delivery_date?->format('Y-m-d') ?? '';
        $this->editBookedDate = $candidate->booked?->format('Y-m-d') ?? '';
        $this->editNote = $candidate->note ?? '';
        $this->editServiceCost = $candidate->service_cost !== null ? (string) $candidate->service_cost : '';
        $this->editTravelCost = $candidate->travel_cost !== null ? (string) $candidate->travel_cost : '';
        $this->editCombineInterviewId = $candidate->combine_interview_id ? (int) $candidate->combine_interview_id : null;
    }

    private function resetForms(): void
    {
        $this->newComment = '';
        $this->statusComment = '';
        $this->staffComment = '';
        $this->cvFiles = [];
        $this->bkFile = null;
        $this->interviewTemplateFile = null;
        $this->combineInterviewId = null;
        $this->activeTab = 'profile';
        $this->activeActionTab = 'records';
    }

    public function getCandidate(): ?Candidate
    {
        if (! $this->candidateId) {
            return null;
        }

        return Candidate::with([
            'customer.user',
            'serviceType',
            'statusRelation',
            'staff',
            'placeRelation',
        ])->find($this->candidateId);
    }

    // ---------------------------------------------------------------------------
    // Comments
    // ---------------------------------------------------------------------------

    public function addComment(): void
    {
        $this->validate(['newComment' => ['required', 'string', 'max:2000']]);

        if (! Schema::hasTable('comments')) {
            $this->notify('error', __('Comments table not available.'));
            return;
        }

        CandidateComment::create([
            'order_id' => $this->candidateId,
            'author_id' => Auth::id(),
            'author_type' => $this->isAdmin() ? 'admin' : 'staff',
            'comment' => $this->newComment,
        ]);

        $this->newComment = '';
        $this->notify('success', __('Comment added.'));
    }

    public function deleteComment(int $commentId): void
    {
        if (! Schema::hasTable('comments')) {
            return;
        }

        CandidateComment::where('id', $commentId)
            ->where('order_id', $this->candidateId)
            ->delete();

        $this->notify('success', __('Comment deleted.'));
    }

    // ---------------------------------------------------------------------------
    // Status update
    // ---------------------------------------------------------------------------

    public function updateStatus(): void
    {
        $this->validate([
            'newStatusId' => ['required', 'integer', 'exists:statuses,id'],
            'statusDate' => ['required', 'date'],
            'statusComment' => ['nullable', 'string', 'max:1000'],
            'combineInterviewId' => ['nullable', 'integer', 'exists:service_types,id'],
        ]);

        $candidate = $this->getCandidate();
        if (! $candidate) {
            return;
        }

        $status = Status::find($this->newStatusId);
        if (! $status) {
            return;
        }

        try {
            app(StatusWorkflowService::class)->handle(
                candidate: $candidate,
                newStatusId: (int) $this->newStatusId,
                options: [
                    'date' => $this->statusDate,
                    'comment' => $this->statusComment
                        ? $this->statusComment . '<br>-' . Auth::user()?->name
                        : '-' . Auth::user()?->name,
                    'combine_interview_id' => $this->combineInterviewId,
                ]
            );

            $this->notify('success', __('Status updated to :status.', ['status' => $status->status]));
            $this->loadFromCandidate();
            $this->statusComment = '';
            $this->combineInterviewId = null;
        } catch (\Throwable $e) {
            // RuntimeException from CombineInterviewService is surfaced as a friendly error.
            $this->notify('error', $e->getMessage());
        }
    }

    /**
     * Called from the view when a status is selected — checks whether the selected
     * status would trigger a combine transfer and whether a target is already set.
     * Returns combine state for the view to decide whether to show the picker.
     */
    public function checkCombineTrigger(int $statusId): array
    {
        $candidate = $this->getCandidate();
        if (! $candidate) {
            return ['triggers' => false];
        }

        $status = Status::find($statusId);
        if (! $status) {
            return ['triggers' => false];
        }

        $combineService = app(\App\Services\Candidate\CombineInterviewService::class);
        $triggers = $combineService->wouldTrigger($candidate, $status);

        if (! $triggers) {
            return ['triggers' => false];
        }

        $targetService = $combineService->resolveTargetServiceType($candidate);

        return [
            'triggers' => true,
            'target_set' => $targetService !== null,
            'target_name' => $targetService?->name,
            'target_id' => $targetService?->id,
        ];
    }

    // ---------------------------------------------------------------------------
    // Staff assignment
    // ---------------------------------------------------------------------------

    public function assignStaff(): void
    {
        $this->validate([
            'newStaffId' => ['nullable', 'integer', 'exists:users,id'],
            'staffComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $candidate = $this->getCandidate();
        if (! $candidate) {
            return;
        }

        $candidate->update(['staff_id' => $this->newStaffId ?: null]);

        $svc = app(CandidateHistoryService::class);
        if ($this->newStaffId) {
            $staffName = User::find($this->newStaffId)?->name ?? 'Unknown';
            $svc->logStaffAssigned($candidate, $staffName, $this->staffComment);
        } else {
            $svc->logStaffRemoved($candidate);
        }

        $this->staffComment = '';
        $this->notify('success', __('Staff assigned.'));
    }

    // ---------------------------------------------------------------------------
    // Background check radios (inline AJAX-like)
    // ---------------------------------------------------------------------------
    // Invoice / records
    // ---------------------------------------------------------------------------

    public function updateInvoiceSent(bool $value): void
    {
        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $update = ['invoice_sent' => $value ? 1 : 0];
        if ($value) {
            $update['invoice_date'] = now()->toDateString();
            $this->invoiceDate = now()->toDateString();
        }

        $candidate->update($update);
        $this->invoiceSent = $value;

        app(CandidateHistoryService::class)->logInvoiceSent($candidate, $value);
        $this->notify('success', __('Invoice status updated.'));
    }

    public function updateReported(bool $value): void
    {
        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $candidate->update([
            'reported_to_sm' => $value ? 1 : 0,
            'reported_to_sm_on' => $value ? now() : null,
        ]);
        $this->reportedToSm = $value;

        app(CandidateHistoryService::class)->logReported($candidate, $value);
        $this->notify('success', __('Reported status updated.'));
    }

    // ---------------------------------------------------------------------------
    // BK field toggle (overrides parent; adds history logging)
    // ---------------------------------------------------------------------------

    public function updateBkField(string $field, string $value): void
    {
        if (! in_array($field, ['economy', 'criminal_record', 'social'], true)) {
            return;
        }
        if (! in_array($value, ['-1', '0', '1'], true)) {
            return;
        }

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $candidate->update([$field => $value]);
        $this->{$field === 'criminal_record' ? 'criminalRecord' : $field} = $value;

        app(CandidateHistoryService::class)->logBkChanged($candidate, $field, $value);
        $this->notify('success', __('Background check updated.'));
    }

    // ---------------------------------------------------------------------------
    // File uploads
    // ---------------------------------------------------------------------------

    public function uploadCvFiles(): void
    {
        $this->validate(['cvFiles.*' => ['file', 'mimes:pdf', 'max:20480']]);

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $existing = array_filter(explode(',', $candidate->cv ?? ''));
        $svc = app(CandidateHistoryService::class);

        foreach ($this->cvFiles as $file) {
            $filename = time() . '-' . $file->getClientOriginalName();
            Storage::disk('public')->putFileAs('candidates', $file, $filename);
            $existing[] = $filename;
            $svc->logDocumentUploaded($candidate, $filename);
        }

        $candidate->update(['cv' => implode(',', array_filter($existing)) ?: null]);
        $this->cvFiles = [];
        $this->notify('success', __('Documents uploaded.'));
    }

    public function deleteCvFile(string $filename): void
    {
        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $existing = array_filter(
            array_filter(explode(',', $candidate->cv ?? '')),
            fn ($f) => trim($f) !== trim($filename)
        );

        Storage::disk('public')->delete('candidates/' . $filename);
        $candidate->update(['cv' => implode(',', $existing) ?: null]);

        app(CandidateHistoryService::class)->logDocumentDeleted($candidate, $filename);
        $this->notify('success', __('Document removed.'));
    }

    public function uploadBkFile(): void
    {
        $this->validate([
            'bkFile' => ['required', 'file', 'mimes:pdf', 'max:20480'],
            'bkFileFor' => ['required', 'in:1,2,3'],
        ]);

        if (! Schema::hasTable('uploaded_pdf_candidate')) {
            $this->notify('error', __('Table not available.'));
            return;
        }

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $filename = time() . '-' . $this->bkFile->getClientOriginalName();
        Storage::disk('public')->putFileAs('candidates/bk', $this->bkFile, $filename);

        UploadedPdfCandidate::create([
            'can_id' => $this->candidateId,
            'file_name' => $filename,
            'file_for' => $this->bkFileFor,
            'is_trash' => 0,
        ]);

        $typeLabels = [1 => 'Economy', 2 => 'Criminal Record', 3 => 'Social Media'];
        app(CandidateHistoryService::class)->logBkDocumentUploaded(
            $candidate,
            $filename,
            $typeLabels[$this->bkFileFor] ?? 'Unknown'
        );

        $this->bkFile = null;
        $this->notify('success', __('BK document uploaded.'));
    }

    public function deleteBkFile(int $pdfId): void
    {
        if (! Schema::hasTable('uploaded_pdf_candidate')) {
            return;
        }

        $pdf = UploadedPdfCandidate::where('id', $pdfId)
            ->where('can_id', $this->candidateId)
            ->first();

        if (! $pdf) {
            return;
        }

        $candidate = Candidate::find($this->candidateId);
        Storage::disk('public')->delete('candidates/bk/' . $pdf->file_name);

        if ($candidate) {
            app(CandidateHistoryService::class)->logBkDocumentDeleted($candidate, $pdf->file_name);
        }

        $pdf->delete();
        $this->notify('success', __('Document deleted.'));
    }

    public function uploadInterviewTemplate(): void
    {
        $this->validate([
            'interviewTemplateFile' => ['required', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $filename = time() . '-' . $this->interviewTemplateFile->getClientOriginalName();
        Storage::disk('public')->putFileAs('candidates/templates', $this->interviewTemplateFile, $filename);
        $candidate->update(['interview_template' => $filename]);

        app(CandidateHistoryService::class)->logInterviewTemplateUploaded($candidate, $filename);
        $this->interviewTemplateFile = null;
        $this->notify('success', __('Interview template uploaded.'));
    }

    // ---------------------------------------------------------------------------
    // Interview / security report uploads (SPI, Ellevio, Timrå)
    // ---------------------------------------------------------------------------

    public function uploadSpiReport(): void
    {
        $this->uploadInterviewReportByType('spiReportFile', InterviewReportService::TYPE_SPI);
    }

    public function uploadEllevioReport(): void
    {
        $this->uploadInterviewReportByType('ellevioReportFile', InterviewReportService::TYPE_ELLEVIO);
    }

    public function uploadTimraReport(): void
    {
        $this->uploadInterviewReportByType('timraReportFile', InterviewReportService::TYPE_TIMRA);
    }

    private function uploadInterviewReportByType(string $property, string $type): void
    {
        $this->validate([
            $property => ['required', 'file', 'mimes:pdf,doc,docx', 'max:20480'],
        ]);

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        try {
            app(InterviewReportService::class)->upload($candidate, $this->$property, $type);
            $this->$property = null;
            $this->notify('success', __('Report uploaded successfully.'));
        } catch (\Throwable $e) {
            $this->notify('error', __('Upload failed: :msg', ['msg' => $e->getMessage()]));
        }
    }

    public function deleteInterviewReport(string $type): void
    {
        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        app(InterviewReportService::class)->delete($candidate, $type);
        $this->notify('success', __('Report deleted.'));
    }

    // ---------------------------------------------------------------------------
    // Update candidate (inline edit)
    // ---------------------------------------------------------------------------

    public function updateCandidate(): void
    {
        $this->validate([
            'editName' => ['required', 'string', 'max:255'],
            'editSurname' => ['required', 'string', 'max:255'],
            'editEmail' => ['required', 'email',  'max:255'],
            'editPhone' => ['required', 'string', 'max:255'],
            'editSecurity' => ['required', 'string', 'max:255'],
            'editVascId' => ['nullable', 'string', 'max:255'],
            'editInterviewId' => ['nullable', 'integer'],
            'editStaffId' => ['nullable', 'integer'],
            'editPlace' => ['nullable', 'integer'],
            'editCountry' => ['nullable', 'string', 'max:255'],
            'editBackgroundCheckDate' => ['nullable', 'date'],
            'editDeliveryDate' => ['nullable', 'date'],
            'editBookedDate' => ['nullable', 'date'],
            'editNote' => ['nullable', 'string'],
            'editServiceCost' => ['nullable', 'numeric', 'min:0'],
            'editTravelCost' => ['nullable', 'numeric', 'min:0'],
            'editCombineInterviewId' => ['nullable', 'integer', 'exists:service_types,id'],
        ]);

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        $oldEmail = $candidate->email;

        $candidate->update([
            'name' => $this->editName,
            'surname' => $this->editSurname,
            'email' => $this->editEmail,
            'phone' => $this->editPhone,
            'security' => $this->editSecurity,
            'vasc_id' => $this->editVascId ?: null,
            'interview_id' => $this->editInterviewId ?: $candidate->interview_id,
            'staff_id' => $this->editStaffId ?: null,
            'place' => $this->editPlace ?: null,
            'country' => $this->editCountry ?: null,
            'background_check_date' => $this->editBackgroundCheckDate ?: null,
            'delivery_date' => $this->editDeliveryDate ?: null,
            'booked' => $this->editBookedDate ?: null,
            'note' => $this->editNote ?: null,
            'service_cost' => $this->editServiceCost !== '' ? (float) $this->editServiceCost : null,
            'travel_cost' => $this->editTravelCost !== '' ? (float) $this->editTravelCost : null,
            'combine_interview_id' => $this->editCombineInterviewId ?: null,
        ]);

        if ($oldEmail && $oldEmail !== $this->editEmail && Schema::hasTable('emails')) {
            \Illuminate\Support\Facades\DB::table('emails')
                ->where('email', $oldEmail)
                ->update(['email' => $this->editEmail]);
        }

        app(CandidateHistoryService::class)->logCandidateUpdated($candidate);
        $this->notify('success', __('Candidate updated successfully.'));
    }

    // ---------------------------------------------------------------------------
    // History management
    // ---------------------------------------------------------------------------

    /** Properties for the manual-entry form shown in the History tab. */
    public string $manualHistoryDesc = '';
    public string $manualHistoryComment = '';

    // ---------------------------------------------------------------------------
    // Email resend / preview state
    // ---------------------------------------------------------------------------
    public ?int  $previewEmailId = null;
    public ?int  $resendEmailId = null;
    public string $resendSubject = '';
    public string $resendBody = '';

    public function addManualHistory(): void
    {
        $this->validate([
            'manualHistoryDesc' => ['required', 'string', 'max:500'],
            'manualHistoryComment' => ['nullable', 'string', 'max:1000'],
        ]);

        $candidate = Candidate::find($this->candidateId);
        if (! $candidate) {
            return;
        }

        app(CandidateHistoryService::class)->logManual(
            $candidate,
            $this->manualHistoryDesc,
            $this->manualHistoryComment
        );

        $this->manualHistoryDesc = '';
        $this->manualHistoryComment = '';
        $this->notify('success', __('History entry added.'));
    }

    public function deleteHistoryEntry(int $historyId): void
    {
        CandidateHistory::where('id', $historyId)
            ->where('order_id', $this->candidateId)
            ->delete();

        $this->notify('success', __('History entry deleted.'));
    }

    private function notify(string $variant, string $message): void
    {
        $this->dispatch('notify', [
            'variant' => $variant,
            'title' => $variant === 'success' ? __('Success') : __('Error'),
            'message' => $message,
        ]);
    }

    // ---------------------------------------------------------------------------
    // Email resend / preview
    // ---------------------------------------------------------------------------

    /**
     * Toggle the HTML preview for an email row.
     * Closes the resend form if another email is open.
     */
    public function toggleEmailPreview(int $emailId): void
    {
        $this->previewEmailId = ($this->previewEmailId === $emailId) ? null : $emailId;
        $this->resendEmailId = null;
    }

    /**
     * Open/close the inline resend form for an email row.
     * Pre-populates subject + body from the saved record.
     */
    public function prepareResend(int $emailId): void
    {
        if ($this->resendEmailId === $emailId) {
            // Toggle off
            $this->resendEmailId = null;
            $this->resendSubject = '';
            $this->resendBody = '';
            return;
        }

        $email = CandidateEmail::find($emailId);
        if (! $email) {
            return;
        }

        $this->resendEmailId = $emailId;
        $this->resendSubject = $email->subject ?? '';
        $this->resendBody = $email->text ?? '';
        $this->previewEmailId = null; // close preview
    }

    /**
     * Send the (optionally edited) email and log the resend.
     */
    public function executeResend(): void
    {
        $this->validate([
            'resendSubject' => ['required', 'string', 'max:500'],
            'resendBody' => ['required', 'string'],
        ]);

        if (! $this->resendEmailId) {
            return;
        }

        $email = CandidateEmail::find($this->resendEmailId);
        if (! $email) {
            return;
        }

        try {
            Mail::to($email->email, $email->user_name ?? '')
                ->send(new CandidateStatusMail($this->resendSubject, $this->resendBody));

            // Save the resend as a new emails row (type flagged with " (Resent)").
            if (Schema::hasTable('emails')) {
                CandidateEmail::create([
                    'user_type' => $email->user_type,
                    'user_name' => $email->user_name,
                    'order_id' => $email->order_id,
                    'msg_type' => $email->msg_type . ' (Resent)',
                    'text' => $this->resendBody,
                    'email' => $email->email,
                    'subject' => $this->resendSubject,
                ]);
            }

            // Log to history
            $candidate = Candidate::find($this->candidateId);
            if ($candidate) {
                app(CandidateHistoryService::class)->log(
                    $candidate->id,
                    "Email resent: {$email->msg_type} → {$email->email}",
                    '— ' . (Auth::user()?->name ?? 'admin')
                );
            }

            $this->resendEmailId = null;
            $this->resendSubject = '';
            $this->resendBody = '';
            $this->notify('success', __('Email resent successfully.'));
        } catch (\Throwable $e) {
            Log::error('Email resend failed', ['id' => $this->resendEmailId, 'error' => $e->getMessage()]);
            $this->notify('error', __('Failed to send: :msg', ['msg' => $e->getMessage()]));
        }
    }

    /** Cancel the resend form. */
    public function cancelResend(): void
    {
        $this->resendEmailId = null;
        $this->resendSubject = '';
        $this->resendBody = '';
    }

    // ---------------------------------------------------------------------------

    private function isAdmin(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = Auth::user();
        return $user?->hasRole('Admin') ?? false;
    }

    // ---------------------------------------------------------------------------
    // Render
    // ---------------------------------------------------------------------------

    public function render(): Renderable
    {
        $candidate = $this->getCandidate();
        $history = collect();
        $comments = collect();
        $emails = collect();
        $bkFiles = collect();
        $statuses = collect();
        $allStaff = collect();
        $serviceTypes = collect();
        $places = collect();
        $existingCvFiles = [];
        $additionalCustomers = collect();
        $interviewReports = [];          // ['spi' => path, 'ellevio' => path, ...]
        $enabledReportTypes = [];          // ['spi' => true/false, ...]

        if ($candidate) {
            $existingCvFiles = array_values(array_filter(
                array_map('trim', explode(',', $candidate->cv ?? ''))
            ));

            if (Schema::hasTable('history')) {
                $history = CandidateHistory::where('order_id', $candidate->id)
                    ->orderByDesc('date_time')
                    ->get();
            }

            if (Schema::hasTable('comments')) {
                $comments = CandidateComment::where('order_id', $candidate->id)
                    ->with('author')
                    ->orderByDesc('id')
                    ->get();
            }

            if (Schema::hasTable('emails')) {
                $emails = CandidateEmail::where('order_id', $candidate->order_id)
                    ->orderByDesc('id')
                    ->get();
            }

            if (Schema::hasTable('uploaded_pdf_candidate')) {
                $bkFiles = UploadedPdfCandidate::where('can_id', $candidate->id)
                    ->where('is_trash', 0)
                    ->get();
            }

            if (Schema::hasTable('statuses')) {
                $serviceCategoryId = $candidate->serviceType?->service_category_id;
                $customerStatusIds = $candidate->customer
                    ? array_filter(explode(',', $candidate->customer->statuses ?? ''))
                    : [];

                $q = Status::orderBy('status');
                if ($serviceCategoryId) {
                    $q->where('status_type', $serviceCategoryId);
                }
                if (! empty($customerStatusIds)) {
                    $q->whereIn('id', $customerStatusIds);
                }
                $statuses = $q->get();
            }

            if (Schema::hasTable('users')) {
                $allStaff = User::whereHas('roles', fn ($q) => $q->whereIn('name', [
                    'Manager', 'Manager with statistics', 'Moderator', 'User',
                ]))->orderBy('name')->get(['id', 'name']);
            }

            if (Schema::hasTable('service_types')) {
                $serviceTypes = ServiceType::orderBy('name')->get(['id', 'name']);
            }

            if (Schema::hasTable('places')) {
                $places = \App\Models\Place::orderBy('name')->get(['id', 'name']);
            }

            // Additional customers (extra contacts at the customer company)
            if ($candidate->cus_id && Schema::hasTable('additional_customers')) {
                $additionalCustomers = \Illuminate\Support\Facades\DB::table('additional_customers')
                    ->where('cus_id', $candidate->cus_id)
                    ->orderBy('name')
                    ->get();
            }

            // Interview / security report uploads
            $reportSvc = app(InterviewReportService::class);
            $interviewReports = $reportSvc->getReports($candidate);
            $enabledReportTypes = $candidate->customer
                ? $reportSvc->enabledTypesForCustomer($candidate->customer)
                : array_fill_keys(InterviewReportService::ALLOWED_TYPES, false);
        }

        $isAdmin = $this->isAdmin();
        /** @var \App\Models\User|null $authUser */
        $authUser = Auth::user();
        $canChangeStaff = $isAdmin || ($authUser?->hasRole(['Manager', 'Manager with statistics']) ?? false);
        $canEditCandidate = $isAdmin;

        // Combine interview data (passed to view for conditional UI)
        $combineService = app(CombineInterviewService::class);
        $combineServiceTypes = collect();  // Security-type services for the picker
        $combineWouldTrigger = false;
        $combineTargetService = null;
        $combineTargetMissing = false;

        if ($candidate) {
            // Get security-category service types for the combine picker.
            // These are the service types the candidate could be transferred to.
            $combineBkIds = array_filter(
                array_map('trim', explode(',', $candidate->customer?->combine_bk_and_security ?? ''))
            );

            if (! empty($combineBkIds) && Schema::hasTable('service_types')) {
                // Show all service types NOT in the BK list (these are the "security" targets).
                $combineServiceTypes = ServiceType::whereNotIn('id', $combineBkIds)
                    ->orderBy('name')
                    ->get(['id', 'name']);
            }

            // Pre-compute combine state for the currently selected status.
            if ($this->newStatusId) {
                $selectedStatus = Status::find($this->newStatusId);
                if ($selectedStatus) {
                    $combineWouldTrigger = $combineService->wouldTrigger($candidate, $selectedStatus);
                    $combineTargetService = $combineService->resolveTargetServiceType($candidate);
                    $combineTargetMissing = $combineWouldTrigger && ! $combineTargetService;
                }
            }
        }

        return view('livewire.candidate.candidate-panel', [
            'candidate' => $candidate,
            'history' => $history,
            'comments' => $comments,
            'emails' => $emails,
            'bkFiles' => $bkFiles,
            'statuses' => $statuses,
            'allStaff' => $allStaff,
            'serviceTypes' => $serviceTypes,
            'places' => $places,
            'existingCvFiles' => $existingCvFiles,
            'additionalCustomers' => $additionalCustomers,
            'interviewReports' => $interviewReports,
            'enabledReportTypes' => $enabledReportTypes,
            'isAdmin' => $isAdmin,
            'canChangeStaff' => $canChangeStaff,
            'canEditCandidate' => $canEditCandidate,
            // Combine feature
            'combineServiceTypes' => $combineServiceTypes,
            'combineWouldTrigger' => $combineWouldTrigger,
            'combineTargetService' => $combineTargetService,
            'combineTargetMissing' => $combineTargetMissing,
            // Billing display — form-builder labels when a form exists, else empty (→ defaults shown)
            'billingDisplayFields' => $candidate ? $this->resolveBillingDisplayFields($candidate) : [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Billing display helper
    // -------------------------------------------------------------------------

    /**
     * Load the billing_info section from the form builder for this candidate's
     * customer+service and return an array of [label, value] pairs for display.
     * Returns [] when no form builder exists → view falls back to default labels.
     *
     * Value resolution:
     *   pref / referensperson / Invoice Recipient / Ansvarig chef → candidates.referensperson
     *   ref  / reference      / DO (5 siffror)                   → candidates.reference
     *   comment                                                    → candidates.comment
     *   note                                                       → candidates.note
     *   custom (e.g. Affärsområde)                                 → candidates.meta_data JSON by label
     *
     * @return array<int, array{0: string, 1: string|null}>
     */
    private function resolveBillingDisplayFields(Candidate $candidate): array
    {
        if (! Schema::hasTable('form_builders') || ! $candidate->cus_id || ! $candidate->interview_id) {
            return [];
        }

        $row = DB::table('form_builders')
            ->where('cus_id', $candidate->cus_id)
            ->where('servicetype_id', $candidate->interview_id)
            ->first();

        if (! $row || empty($row->form)) {
            return [];
        }

        $decoded = json_decode((string) $row->form, true);
        $builder = $decoded['form_builder'] ?? $decoded;

        if (! is_array($builder) || empty($builder['billing_info'])) {
            return [];
        }

        // Parse meta_data JSON for custom-field values.
        $metaData = [];
        if (! empty($candidate->meta_data)) {
            $md = json_decode((string) $candidate->meta_data, true);
            $metaData = is_array($md) ? $md : [];
        }

        $fields = [];

        foreach ($builder['billing_info'] as $metaKey => $ignored) {
            $parts      = explode(',', (string) $metaKey);
            $label      = trim($parts[1] ?? '');
            $name       = trim($parts[2] ?? '');

            if ($name === '' && $label === '') {
                continue;
            }

            // Clean display label — strip trailing required marker.
            $cleanLabel = rtrim(trim($label), ' *');
            $ll         = strtolower($label);

            // Map to the correct DB column or meta_data key.
            if ($name === 'pref' || $name === 'referensperson'
                || str_contains($ll, 'invoice recipient')
                || str_contains($ll, 'ansvarig chef')
                || str_contains($ll, 'hiring manager')) {
                $value = $candidate->referensperson;
            } elseif ($name === 'ref' || $name === 'reference'
                || (str_contains($ll, 'do') && str_contains($ll, 'siffror'))) {
                $value = $candidate->reference;
            } elseif ($name === 'comment') {
                $value = $candidate->comment;
            } elseif ($name === 'note') {
                $value = $candidate->note;
            } else {
                // Custom field — look up by clean label in meta_data JSON.
                $value = $metaData[$cleanLabel] ?? null;
            }

            $fields[] = [$cleanLabel, $value];
        }

        return $fields;
    }
}
