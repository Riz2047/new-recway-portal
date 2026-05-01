<?php

require_once "includes/functions.php";
// OTP cleanup
expiredOTP();
// Reminder emails for orders under investigation with uploaded interview reports
$swedenTimezone = new DateTimeZone('Europe/Stockholm');
$swedenTime = new DateTime('now', $swedenTimezone);
$currentTime = $swedenTime->format('H:i:s');
$dayOfWeek = $swedenTime->format('N');
if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '17:00:00' && $currentTime < '18:00:00') {
    sendInvestigationReminderEmails();
    // Reminder emails for assigned staff when order is in specific statuses
    sendStaffReminderEmails();
    // Task Invoice Email to Manager with Statistic Role when order transitions from Booked to Approved/Under Investigation
    sendTaskInvoiceEmails();
}
