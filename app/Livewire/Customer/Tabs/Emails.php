<?php

namespace App\Livewire\Customer\Tabs;

use App\Mail\CandidateStatusMail;
use App\Models\Candidate;
use App\Models\CandidateEmail;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;
use Livewire\WithPagination;

class Emails extends Component
{
    use WithPagination;

    public int $customerId;
    public string $search = '';
    public string $filterType = 'all';
    public int $perPage = 15;

    public ?int $resendEmailId = null;
    public string $resendSubject = '';
    public string $resendBody = '';
    public bool $sending = false;

    public function mount(int $customerId): void
    {
        $this->customerId = $customerId;
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function prepareResend(int $emailId): void
    {
        $email = CandidateEmail::find($emailId);

        if (! $email) {
            return;
        }

        $this->resendEmailId = $emailId;
        $this->resendSubject = (string) ($email->subject ?? '');
        $this->resendBody = (string) ($email->text ?? '');
    }

    public function cancelResend(): void
    {
        $this->resendEmailId = null;
        $this->resendSubject = '';
        $this->resendBody = '';
        $this->resetValidation();
    }

    public function resend(): void
    {
        $this->validate([
            'resendSubject' => ['required', 'string', 'max:500'],
            'resendBody' => ['required', 'string'],
        ]);

        $original = CandidateEmail::find($this->resendEmailId);

        if (! $original || ! $original->email) {
            $this->addError('resendBody', __('Email record not found or has no recipient.'));

            return;
        }

        Mail::to($original->email)->send(
            new CandidateStatusMail($this->resendSubject, $this->resendBody)
        );

        CandidateEmail::create([
            'user_type' => $original->user_type,
            'user_name' => $original->user_name,
            'order_id' => $original->order_id,
            'msg_type' => trim(($original->msg_type ?? '') . ' (Resent)'),
            'text' => $this->resendBody,
            'email' => $original->email,
            'subject' => $this->resendSubject,
        ]);

        $this->cancelResend();

        $this->dispatch('notify', [
            'variant' => 'success',
            'title' => __('Sent'),
            'message' => __('Email resent successfully.'),
        ]);
    }

    public function render(): \Illuminate\View\View
    {
        $orderIds = Candidate::query()
            ->where('cus_id', $this->customerId)
            ->whereNotNull('order_id')
            ->pluck('order_id');

        $baseQuery = CandidateEmail::query()->whereIn('order_id', $orderIds);

        $typeCounts = (clone $baseQuery)
            ->selectRaw('user_type, COUNT(*) as cnt')
            ->groupBy('user_type')
            ->pluck('cnt', 'user_type')
            ->toArray();

        $totalCount = array_sum($typeCounts);

        $emails = (clone $baseQuery)
            ->when($this->filterType !== 'all', fn ($q) => $q->where('user_type', $this->filterType))
            ->when($this->search !== '', function ($q): void {
                $q->where(function ($inner): void {
                    $inner->where('subject', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%")
                        ->orWhere('msg_type', 'like', "%{$this->search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate($this->perPage);

        return view('livewire.customer.tabs.emails', compact('emails', 'typeCounts', 'totalCount'));
    }
}
