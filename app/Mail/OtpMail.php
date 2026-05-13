<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OtpMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly string $otp,
        private readonly string $recipientName,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Your verification code: :otp', ['otp' => $this->otp]),
        );
    }

    public function build(): self
    {
        return $this->html($this->buildHtml());
    }

    private function buildHtml(): string
    {
        $otp = $this->otp;
        $name = $this->recipientName;

        return <<<HTML
<div style="font-family: sans-serif; max-width: 480px; margin: 0 auto;">
    <div style="background-color: #4f46e5; color: #fff; text-align: center; padding: 16px 24px; border-radius: 8px 8px 0 0;">
        <h2 style="margin: 0; font-size: 20px;">Your Verification Code</h2>
    </div>
    <div style="background-color: #f9fafb; padding: 24px; border: 1px solid #e5e7eb; border-top: none; border-radius: 0 0 8px 8px;">
        <p style="color: #374151; margin-top: 0;">Hello <strong>{$name}</strong>,</p>
        <p style="color: #374151;">Use the following code to complete your login:</p>
        <div style="text-align: center; margin: 24px 0;">
            <span style="
                background-color: #e0e7ff;
                color: #3730a3;
                font-size: 36px;
                font-weight: 700;
                letter-spacing: 10px;
                padding: 12px 24px;
                border-radius: 8px;
                font-family: monospace;
                display: inline-block;
            ">{$otp}</span>
        </div>
        <p style="color: #6b7280; font-size: 14px;">This code is valid for <strong>24 hours</strong>.</p>
        <p style="color: #6b7280; font-size: 13px;">If you did not request this code, please ignore this email.</p>
        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 20px 0;">
        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">Recway AB</p>
    </div>
</div>
HTML;
    }
}
