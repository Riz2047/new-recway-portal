<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/**
 * Email sent on candidate status changes, report uploads, etc.
 * Body is pre-rendered HTML from the messages template table.
 * Optional $attachmentPath attaches a file (e.g. the security report PDF).
 */
class CandidateStatusMail extends Mailable
{
    public function __construct(
        private readonly string  $emailSubject,
        private readonly string  $htmlBody,
        private readonly ?string $attachmentPath = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->emailSubject);
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody);
    }

    /** @return array<Attachment> */
    public function attachments(): array
    {
        if (! $this->attachmentPath || ! file_exists($this->attachmentPath)) {
            return [];
        }

        return [Attachment::fromPath($this->attachmentPath)];
    }
}
