<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmailTemplate;
use Illuminate\Database\Seeder;

/**
 * Seeds the three email templates required by the cron reminder system.
 * Uses updateOrCreate so it is safe to re-run.
 *
 * Variables supported in bodies:
 *   {customer}  {candidate}  {company}  {interview}  {staff}
 *   {status}    {date}       {orderid}  {vasc_id}    {service}  {place}
 *   {invoice_period}  (task invoice only)
 */
class EmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        // ------------------------------------------------------------------ //
        // 1. Investigation Reminder  (old: id=3)
        // ------------------------------------------------------------------ //
        EmailTemplate::updateOrCreate(
            ['variable' => 'investigation_reminder'],
            [
                'title' => 'Active Manager Reminder — Order Still Under Investigation',
                'body' => <<<'HTML'
Dear {customer},

<p>This is a reminder that order <strong>{orderid}</strong> for candidate <strong>{candidate}</strong>
is still in status <strong>"{status}"</strong> and the interview report is available for review.</p>

<p>Please review the report and update the candidate's status accordingly.</p>

<p>Best regards,<br>Recway AB</p>
HTML,
            ]
        );

        // ------------------------------------------------------------------ //
        // 2. Staff Reminder  (old: id=4)
        // ------------------------------------------------------------------ //
        EmailTemplate::updateOrCreate(
            ['variable' => 'staff_reminder'],
            [
                'title' => 'Staff Reminder — Candidate Still in Active Status',
                'body' => <<<'HTML'
Dear {staff},

<p>This is a reminder that order <strong>{orderid}</strong> for candidate <strong>{candidate}</strong>
is still in status <strong>"{status}"</strong>.</p>

<p>Please take the necessary action and update the candidate's status.</p>

<p>Best regards,<br>Recway AB</p>
HTML,
            ]
        );

        // ------------------------------------------------------------------ //
        // 3. Task Invoice Email  (old: id=5)
        // ------------------------------------------------------------------ //
        EmailTemplate::updateOrCreate(
            ['variable' => 'task_invoice_email'],
            [
                'title' => 'Task Invoice Notification for Manager',
                'body' => <<<'HTML'
Dear Manager,

<p>This is a task invoice notification for order <strong>{orderid}</strong>
for candidate <strong>{candidate}</strong> (customer: {customer} — {company}).</p>

<p>The interview service was: <strong>{service}</strong></p>

<p>This customer has a <strong>{invoice_period}</strong> invoice period.
Please send the invoice to the customer.</p>

<p>Best regards,<br>Recway AB</p>
HTML,
            ]
        );

        // ------------------------------------------------------------------ //
        // 4. Company Manager Notification (report uploaded)
        // ------------------------------------------------------------------ //
        EmailTemplate::updateOrCreate(
            ['variable' => 'report_uploaded_notification'],
            [
                'title' => 'Interview Report Uploaded — Manager Notification',
                'body' => <<<'HTML'
Dear {customer},

<p>The interview report for order <strong>{orderid}</strong>
(candidate: <strong>{candidate}</strong>) has been uploaded and is ready for review.</p>

<p>Status: <strong>{status}</strong></p>

<p>Best regards,<br>Recway AB</p>
HTML,
            ]
        );
    }
}
