<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Sweden Working Hours
    |--------------------------------------------------------------------------
    | Reminders and invoice notifications respect Sweden's working hours.
    | Mon–Fri, start and end in HH:MM:SS format.
    */

    'sweden_timezone' => 'Europe/Stockholm',
    'work_start' => '08:00:00',
    'work_end' => '18:00:00',

    /*
    |--------------------------------------------------------------------------
    | Investigation Reminder
    |--------------------------------------------------------------------------
    | Sent to company managers when a candidate has been "under investigation"
    | with an uploaded interview report for N or more working days.
    */

    'investigation_reminder' => [
        'working_days_threshold' => 5,
        // EmailTemplate.variable to use for the email body.
        'template_variable' => 'investigation_reminder',
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff Reminder
    |--------------------------------------------------------------------------
    | Sent to the assigned staff member when a candidate stays in an
    | "active / needs action" status for N or more working days.
    |
    | status_variables: list of Status.variable values that trigger reminders.
    | Use an empty array to use the service's built-in default list.
    */

    'staff_reminder' => [
        'working_days_threshold' => 5,
        'template_variable' => 'staff_reminder',
        'status_variables' => [
            'booked',
            'rebooking',
            'pending',
            'under_investigation',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Delayed Email Processing
    |--------------------------------------------------------------------------
    | Max emails to process per run of emails:process-delayed.
    */

    'delayed_email_batch_size' => 100,

    /*
    |--------------------------------------------------------------------------
    | Invoice Generation
    |--------------------------------------------------------------------------
    | Already implemented in InvoiceService / GenerateInvoicesCommand.
    | Runs weekdays at 09:00.
    */

    'invoice_generate_time' => '09:00',

    /*
    |--------------------------------------------------------------------------
    | Reminder Schedule Time
    |--------------------------------------------------------------------------
    | Time (HH:MM) to run daily reminders (matches old system's 17:00–18:00 window).
    */

    'reminders_time' => '17:00',

];
