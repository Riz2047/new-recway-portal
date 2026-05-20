<?php

declare(strict_types=1);

namespace App\Services\Candidate;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use App\Models\CandidateMessage;
use App\Models\Status;
use App\Models\StatusServiceLink;
use App\Models\User;
use App\Services\EmailTemplateRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Sends the four creation-time emails when a candidate is added:
 *   1. Customer  — cus_msg column  (if admin chose "Send Mail: Customer = yes")
 *   2. Candidate — status-specific column via status_services  (if admin chose "Send Mail: Candidate = yes")
 *      + CC to customer when customer.sent_email = true
 *   3. Staff     — staff_msg column  (whenever a staff member is assigned)
 *   4. Admin     — admin_msg column  (always)
 *
 * Mirrors the old add-candidate.php email block.
 */
class CandidateCreationEmailService
{
    public function __construct(
        private readonly EmailTemplateRenderer $renderer,
    ) {
    }

    public function send(Candidate $candidate, bool $sendToCustomer, bool $sendToCandidate): void
    {
        if (! Schema::hasTable('messages')) {
            return;
        }

        $candidate->loadMissing(['customer.user', 'serviceType', 'staff', 'placeRelation']);

        $messages = CandidateMessage::where('cus_id', $candidate->cus_id)
            ->where('interview_id', $candidate->interview_id)
            ->first();

        if (! $messages) {
            Log::warning('CandidateCreationEmailService: no messages row found', [
                'cus_id' => $candidate->cus_id,
                'interview_id' => $candidate->interview_id,
                'order_id' => $candidate->order_id,
            ]);
            return;
        }

        $status = Status::find($candidate->status);
        $customer = $candidate->customer;
        $date = now()->toDateString();

        $customerEmail = $customer?->user?->email ?? '';
        $customerName = $customer?->user?->name ?? '';

        // 1. Customer email (cus_msg)
        if ($sendToCustomer && $customerEmail) {
            $body = $messages->getBodyForKey('cus_msg');
            if ($body) {
                $body = $this->renderer->renderForCandidate($body, $candidate, $status, $date);
                $this->sendAndSave(
                    body:      $body,
                    to:        $customerEmail,
                    name:      $customerName,
                    subject:   'Customer Message',
                    userType:  'Customer',
                    userName:  $customerName,
                    orderId:   $candidate->order_id,
                    msgType:   'Customer Message',
                );
            }
        }

        // 2. Candidate email (status-specific column)
        if ($sendToCandidate && $candidate->email) {
            $body = $this->resolveStatusBody($candidate, $messages);
            if ($body) {
                $body = $this->renderer->renderForCandidate($body, $candidate, $status, $date);
                $candidateName = trim($candidate->name . ' ' . $candidate->surname);

                $this->sendAndSave(
                    body:      $body,
                    to:        $candidate->email,
                    name:      $candidateName,
                    subject:   $status?->status ?? 'New Order',
                    userType:  'Candidate',
                    userName:  $candidateName,
                    orderId:   $candidate->order_id,
                    msgType:   'Candidate Message',
                );

                // CC to customer when customer.sent_email = true
                if ($customer?->sent_email && $customerEmail) {
                    $this->sendAndSave(
                        body:      $body,
                        to:        $customerEmail,
                        name:      $customerName,
                        subject:   $status?->status ?? 'New Order',
                        userType:  'Customer',
                        userName:  $customerName,
                        orderId:   $candidate->order_id,
                        msgType:   'CC email of candidate registration',
                    );
                }
            }
        }

        // 3. Staff email (staff_msg) — whenever staff is assigned
        $staff = $candidate->staff;
        if ($staff && $staff->email) {
            $body = $messages->getBodyForKey('staff_msg');
            if ($body) {
                $body = $this->renderer->renderForCandidate($body, $candidate, $status, $date);
                $this->sendAndSave(
                    body:      $body,
                    to:        $staff->email,
                    name:      $staff->name,
                    subject:   'Candidate Assigned',
                    userType:  'Staff',
                    userName:  $staff->name,
                    orderId:   $candidate->order_id,
                    msgType:   'Staff Message',
                );
            }
        }

        // 4. Admin email (admin_msg) — always
        $adminBody = $messages->getBodyForKey('admin_msg')
            ?? "Order has been created successfully for {$customerName} (customer) and OrderID is {$candidate->order_id}";
        $adminBody = $this->renderer->renderForCandidate($adminBody, $candidate, $status, $date);

        $admin = User::role('Admin')->first();
        if ($admin?->email) {
            $this->sendAndSave(
                body:      $adminBody,
                to:        $admin->email,
                name:      $admin->name,
                subject:   'Order Created',
                userType:  'Admin',
                userName:  $admin->name,
                orderId:   $candidate->order_id,
                msgType:   'Admin Message',
            );
        }
    }

    /**
     * Find the candidate's initial-status email body via the status_services msg_col mapping.
     */
    private function resolveStatusBody(Candidate $candidate, CandidateMessage $messages): ?string
    {
        if (! Schema::hasTable('status_services')) {
            return null;
        }

        $link = StatusServiceLink::where('status_id', $candidate->status)
            ->where('service_id', $candidate->interview_id)
            ->first();

        if (! $link || empty($link->msg_col)) {
            return null;
        }

        return $messages->getBodyForKey($link->msg_col);
    }

    private function sendAndSave(
        string $body,
        string $to,
        string $name,
        string $subject,
        string $userType,
        string $userName,
        string $orderId,
        string $msgType,
    ): void {
        if (Schema::hasTable('emails')) {
            CandidateEmail::create([
                'user_type' => $userType,
                'user_name' => $userName,
                'order_id' => $orderId,
                'msg_type' => $msgType,
                'text' => $body,
                'email' => $to,
                'subject' => $subject,
            ]);
        }

        try {
            Mail::to($to, $name)->queue(new CandidateStatusMail($subject, $body));
        } catch (\Throwable $e) {
            Log::error('CandidateCreationEmailService: failed to send', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
