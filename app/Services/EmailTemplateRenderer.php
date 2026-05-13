<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Candidate;
use App\Models\Customer;
use App\Models\Status;

/**
 * Single source of truth for all email template variable replacement.
 *
 * Usage:
 *   $renderer = app(EmailTemplateRenderer::class);
 *   $body = $renderer->render($templateHtml, $renderer->contextFromCandidate($candidate, $status, $date, $comment));
 *
 * All variables use {curly_braces} syntax, identical to the old system.
 */
class EmailTemplateRenderer
{
    // -------------------------------------------------------------------------
    // Variable catalogue — single source of truth
    // -------------------------------------------------------------------------

    /**
     * Returns all available variables with their descriptions.
     * Used by the editor UI to show a picker.
     *
     * @return array<string, array{placeholder:string, description:string, group:string}>
     */
    public static function catalogue(): array
    {
        return [
            // Candidate
            'candidate' => ['placeholder' => '{candidate}',              'description' => 'Full name of the candidate',                     'group' => 'Candidate'],
            'candidate_email' => ['placeholder' => '{candidate_email}',        'description' => 'Candidate\'s email address',                     'group' => 'Candidate'],
            'vasc_id' => ['placeholder' => '{vasc_id}',                'description' => 'Candidate VASC ID',                              'group' => 'Candidate'],
            'ssn' => ['placeholder' => '{ssn}',                    'description' => 'Candidate SSN / Date of birth',                  'group' => 'Candidate'],
            'orderid' => ['placeholder' => '{orderid}',                'description' => '6-character Order ID (e.g. A1B2C3)',            'group' => 'Candidate'],
            'order_date' => ['placeholder' => '{order_date}',             'description' => 'Date when the order was created',               'group' => 'Candidate'],
            'interview_date' => ['placeholder' => '{interview_date}',         'description' => 'Booked interview date and time',                'group' => 'Candidate'],
            'delivery_date' => ['placeholder' => '{delivery_date}',          'description' => 'Expected delivery / decision date',             'group' => 'Candidate'],
            'background_check_date' => ['placeholder' => '{background_check_date}',  'description' => 'Background check completion date',              'group' => 'Candidate'],
            'place' => ['placeholder' => '{place}',                  'description' => 'Interview location / place',                    'group' => 'Candidate'],
            'status' => ['placeholder' => '{status}',                 'description' => 'Current candidate status label',               'group' => 'Candidate'],

            // Customer / Company
            'customer' => ['placeholder' => '{customer}',               'description' => 'Customer\'s full name',                         'group' => 'Customer'],
            'company' => ['placeholder' => '{company}',                'description' => 'Customer\'s company name',                      'group' => 'Customer'],
            'customer_company' => ['placeholder' => '{customer_company}',       'description' => 'Alias for {company}',                          'group' => 'Customer'],
            'email' => ['placeholder' => '{email}',                  'description' => 'Customer\'s email address',                     'group' => 'Customer'],
            'password' => ['placeholder' => '{password}',               'description' => 'Account password (new account emails only)',    'group' => 'Customer'],
            'invoice_period' => ['placeholder' => '{invoice_period}',         'description' => 'Customer\'s invoice period (daily/weekly/monthly)', 'group' => 'Customer'],

            // Service / Interview
            'interview' => ['placeholder' => '{interview}',              'description' => 'Service type name (alias for {service})',       'group' => 'Service'],
            'service' => ['placeholder' => '{service}',                'description' => 'Service type name',                             'group' => 'Service'],
            'service_category' => ['placeholder' => '{service_category}',       'description' => 'Service category name (e.g. Background Check)', 'group' => 'Service'],

            // Staff
            'staff' => ['placeholder' => '{staff}',                  'description' => 'Assigned staff member\'s name',                 'group' => 'Staff'],
            'staff_email' => ['placeholder' => '{staff_email}',            'description' => 'Assigned staff member\'s email',               'group' => 'Staff'],

            // System
            'date' => ['placeholder' => '{date}',                   'description' => 'Date of the current action / event',           'group' => 'System'],
            'today' => ['placeholder' => '{today}',                  'description' => 'Today\'s date',                                'group' => 'System'],
            'comment' => ['placeholder' => '{comment}',                'description' => 'Comment or note attached to the action',        'group' => 'System'],
            'app_name' => ['placeholder' => '{app_name}',               'description' => 'Application / site name',                      'group' => 'System'],
        ];
    }

    // -------------------------------------------------------------------------
    // Context builders — typed helpers that assemble the values array
    // -------------------------------------------------------------------------

    /**
     * Build a context array from a Candidate model and optional extras.
     * This is the main context builder used by status changes, reminders, etc.
     *
     * @param array<string, string> $extras  Additional key→value overrides
     * @return array<string, string>
     */
    public function contextFromCandidate(
        Candidate $candidate,
        ?Status   $status = null,
        string    $date = '',
        string    $comment = '',
        string    $password = '',
        array     $extras = []
    ): array {
        $candidate->loadMissing(['customer.user', 'serviceType', 'staff', 'placeRelation']);

        $customer = $candidate->customer;
        $customerUser = $customer?->user;
        $staff = $candidate->staff;
        $serviceType = $candidate->serviceType;
        $place = $candidate->placeRelation;

        $invoicePeriod = match (strtolower((string) $customer?->invoice_period)) {
            'day' => 'daily',
            'week' => 'weekly',
            'month' => 'monthly',
            default => (string) ($customer?->invoice_period ?? ''),
        };

        $ctx = [
            // Candidate
            'candidate' => trim(($candidate->name ?? '') . ' ' . ($candidate->surname ?? '')),
            'candidate_email' => $candidate->email ?? '',
            'vasc_id' => $candidate->vasc_id ?? '',
            'ssn' => $candidate->security ?? '',
            'orderid' => $candidate->order_id ?? '',
            'order_date' => $candidate->created_at?->format('Y-m-d') ?? '',
            'interview_date' => $candidate->booked?->format('d M Y H:i') ?? '',
            'delivery_date' => $candidate->delivery_date?->format('d M Y') ?? '',
            'background_check_date' => $candidate->background_check_date?->format('d M Y') ?? '',
            'place' => $place?->name ?? '',
            'status' => $status?->status ?? $candidate->statusRelation?->status ?? '',

            // Customer
            'customer' => $customerUser?->name ?? '',
            'company' => $customer?->company ?? '',
            'customer_company' => $customer?->company ?? '',
            'email' => $customerUser?->email ?? '',
            'password' => $password,
            'invoice_period' => $invoicePeriod,

            // Service
            'interview' => $serviceType?->name ?? '',
            'service' => $serviceType?->name ?? '',
            'service_category' => $serviceType?->serviceCategory?->name ?? '',

            // Staff
            'staff' => $staff?->name ?? '',
            'staff_email' => $staff?->email ?? '',

            // System
            'date' => $date ?: now()->format('Y-m-d'),
            'today' => now()->format('d M Y'),
            'comment' => $comment,
            'app_name' => config('app.name', 'Recway'),
        ];

        return array_merge($ctx, $extras);
    }

    /**
     * Build a minimal context from raw values (for services that don't have a Candidate object).
     *
     * @param array<string, string> $values
     * @return array<string, string>
     */
    public function contextFromArray(array $values): array
    {
        $defaults = [
            'candidate' => '', 'candidate_email' => '', 'vasc_id' => '', 'ssn' => '',
            'orderid' => '', 'order_date' => '', 'interview_date' => '',
            'delivery_date' => '', 'background_check_date' => '', 'place' => '', 'status' => '',
            'customer' => '', 'company' => '', 'customer_company' => '',
            'email' => '', 'password' => '', 'invoice_period' => '',
            'interview' => '', 'service' => '', 'service_category' => '',
            'staff' => '', 'staff_email' => '',
            'date' => now()->format('Y-m-d'), 'today' => now()->format('d M Y'),
            'comment' => '', 'app_name' => config('app.name', 'Recway'),
        ];

        return array_merge($defaults, $values);
    }

    // -------------------------------------------------------------------------
    // Renderer
    // -------------------------------------------------------------------------

    /**
     * Replace all {placeholders} in $template with values from $context.
     *
     * Behaviour:
     *   - Empty string value → placeholder is REMOVED (not left in output)
     *   - Missing key in context → placeholder is left unchanged (safe)
     *   - HTML-safe: values are NOT escaped (templates contain trusted HTML)
     *
     * @param array<string, string> $context
     */
    public function render(string $template, array $context): string
    {
        foreach ($context as $key => $value) {
            $placeholder = '{' . $key . '}';

            if ($value !== '' && $value !== null) {
                $template = str_replace($placeholder, (string) $value, $template);
            } else {
                // Remove the placeholder when value is empty.
                $template = str_replace($placeholder, '', $template);
            }
        }

        return $template;
    }

    /**
     * Render with a Candidate + optional extras in one call.
     */
    public function renderForCandidate(
        string    $template,
        Candidate $candidate,
        ?Status   $status = null,
        string    $date = '',
        string    $comment = '',
        string    $password = '',
        array     $extras = []
    ): string {
        $ctx = $this->contextFromCandidate($candidate, $status, $date, $comment, $password, $extras);
        return $this->render($template, $ctx);
    }

    // -------------------------------------------------------------------------
    // Preview helpers (for the admin UI)
    // -------------------------------------------------------------------------

    /**
     * Build a sample context with dummy values for live preview.
     *
     * @return array<string, string>
     */
    public static function sampleContext(): array
    {
        return [
            'candidate' => 'Anna Svensson',
            'candidate_email' => 'anna.svensson@example.com',
            'vasc_id' => 'VSC-12345',
            'ssn' => '850612-4321',
            'orderid' => 'AB1C2D',
            'order_date' => now()->subDays(5)->format('Y-m-d'),
            'interview_date' => now()->addDays(3)->format('d M Y H:i'),
            'delivery_date' => now()->addDays(8)->format('d M Y'),
            'background_check_date' => now()->addDays(10)->format('d M Y'),
            'place' => 'Stockholm Office',
            'status' => 'Booked',
            'customer' => 'Erik Johansson',
            'company' => 'Acme AB',
            'customer_company' => 'Acme AB',
            'email' => 'erik@acme.se',
            'password' => '••••••••',
            'invoice_period' => 'monthly',
            'interview' => 'Security Check',
            'service' => 'Security Check',
            'service_category' => 'Background Check',
            'staff' => 'Maria Larsson',
            'staff_email' => 'maria@recway.se',
            'date' => now()->format('Y-m-d'),
            'today' => now()->format('d M Y'),
            'comment' => 'Candidate confirmed attendance.',
            'app_name' => config('app.name', 'Recway'),
        ];
    }

    /**
     * Render a template using sample data (for preview endpoints).
     */
    public function previewWithSampleData(string $template): string
    {
        return $this->render($template, self::sampleContext());
    }

    /**
     * List all {placeholders} found in a template string.
     *
     * @return string[]
     */
    public static function extractPlaceholders(string $template): array
    {
        preg_match_all('/\{([a-z_]+)\}/', $template, $matches);
        return array_unique($matches[1] ?? []);
    }

    /**
     * Validate a template — returns unknown placeholders (not in catalogue).
     *
     * @return string[]
     */
    public static function unknownPlaceholders(string $template): array
    {
        $known = array_keys(self::catalogue());
        $used = self::extractPlaceholders($template);
        return array_values(array_diff($used, $known));
    }
}
