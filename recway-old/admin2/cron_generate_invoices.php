<?php

include_once('../includes/functions.php');

// This script should be run as a cron job
// Example: 0 9 * * * php /path/to/admin2/cron_generate_invoices.php

echo "Starting invoice generation process...\n";

try {
    // Get all customers with invoice settings on customers table
    $query = "SELECT c.id, c.name, c.email, c.invoice_period, c.last_invoice_sent
              FROM customers c
              WHERE c.invoice_period IS NOT NULL";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customers = $stmt->fetchAll();

    $invoices_created = 0;
    $today = date('Y-m-d');

    foreach ($customers as $customer) {
        $should_generate = false;
        $start_date = null;

        // Determine run schedule ONLY by current calendar boundary (ignore last_invoice_sent)
        switch ($customer->invoice_period) {
            case 'day':
                // Run daily for previous day window
                $should_generate = true;
                $start_date = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'week':
                // Run only on Mondays; window starts last Monday
                $isMonday = ((int)date('N') === 1);
                $should_generate = $isMonday;
                $start_date = date('Y-m-d', strtotime('last monday'));
                break;
            case 'month':
                // Run only on the 1st; window starts first day of last month
                $isFirstOfMonth = (date('d') === '01');
                $should_generate = $isFirstOfMonth;
                $start_date = date('Y-m-01', strtotime('first day of last month'));
                break;
            default:
                $should_generate = false;
                $start_date = $today;
        }

        if ($should_generate) {
            // $candidates_query = "SELECT id, COALESCE(service_cost, 0) + COALESCE(travel_cost, 0) AS total_cost
            //                      FROM candidates
            //                      WHERE cus_id = ? AND status IN (4,5,7,11,21,22,36,37,41,42,52,53,55,58) AND invoice_sent = 0 AND invoice_genrated = 0";
            $candidates_query = "SELECT 
                    c.id,
                    COALESCE(c.service_cost, 0) + COALESCE(c.travel_cost, 0) AS total_cost
                FROM 
                    candidates c
                WHERE 
                    c.cus_id = ?
                    AND (
                    COALESCE(
                        (
                            SELECT MAX(h.date_time)
                            FROM history h
                            WHERE 
                                h.order_id = c.id
                                AND h.`desc` IN (
                                    'Candidate has been approved',
                                    'Interview Interrupted',
                                    'Candidate has been denied after meeting with SPO',
                                    'Interview has been canceled by customer',
                                    'Candidate did not show up',
                                    'A deviation is found in the background check service',
                                    'Kandiodaten dök inte upp',
                                    'Kandidaten har genomfört intervjun',
                                    'Candidate canceled the booked appointment',
                                    'Denied for followup'
                                )
                        ),
                        '1970-01-01 00:00:00'
                    ) > COALESCE(
                        (
                            SELECT MAX(ci.created_date)
                            FROM customer_invoices ci
                            WHERE JSON_CONTAINS(ci.candidate_ids,c.id,'$')
                        ),
                        '1970-01-01 00:00:00'
                    )
                )


                    AND (
                        -- Normal statuses
                        c.status IN (4,5,7,11,21,22,36,37,41,42,52,53,55,58)

                        -- Candidate canceled (only if booked before and after 24h)
                        OR (
                            c.status IN (38,62)
                            AND EXISTS (
                                SELECT 1 
                                FROM history h_cancel
                                WHERE 
                                    h_cancel.order_id = c.id
                                    AND h_cancel.`desc` = 'Candidate canceled the booked appointment'
                                    AND (
                                        SELECT TIMESTAMPDIFF(
                                            HOUR,
                                            (
                                                SELECT MAX(h_book.date_time)
                                                FROM history h_book
                                                WHERE 
                                                    h_book.order_id = c.id
                                                    AND h_book.`desc` = 'Interview has been booked'
                                                    AND h_book.date_time < h_cancel.date_time
                                            ),
                                            h_cancel.date_time
                                        )
                                    ) >= 24
                            )
                            )

                        --  Customer canceled (only if booked before and canceled after 24h)
                        OR (
                            c.status IN (9,56)
                            AND EXISTS (
                                SELECT 1 
                                FROM history h_cancel
                                WHERE 
                                    h_cancel.order_id = c.id
                                    AND h_cancel.`desc` = 'Interview has been canceled by customer'
                                    AND EXISTS (
                                        SELECT 1
                                        FROM history h_book
                                        WHERE 
                                            h_book.order_id = c.id
                                            AND h_book.`desc` = 'Interview has been booked'
                                            AND h_book.date_time < h_cancel.date_time
                                    )
                                    AND (
                                        SELECT TIMESTAMPDIFF(
                                            HOUR,
                                            (
                                                SELECT MAX(h_book2.date_time)
                                                FROM history h_book2
                                                WHERE 
                                                    h_book2.order_id = c.id
                                                    AND h_book2.`desc` = 'Interview has been booked'
                                                    AND h_book2.date_time < h_cancel.date_time
                                            ),
                                            h_cancel.date_time
                                        )
                                    ) >= 24
                            )
                        )

                    )";
            // AND c.invoice_sent = 0
            // AND c.invoice_genrated = 0";

            $candidates_stmt = $conn->prepare($candidates_query);
            $candidates_stmt->execute([$customer->id]);
            $candidates = $candidates_stmt->fetchAll();

            $candidate_ids = [];
            $total_amount = 0;
            foreach ($candidates as $cand) {
                $candidate_ids[] = (int)$cand->id;
                $total_amount += (float)$cand->total_cost;
            }
            $candidate_count = count($candidate_ids);

            if ($candidate_count > 0) {
                $invoice_query = "INSERT INTO customer_invoices (customer_id, period, invoice_amount, status, due_date, notes, created_date, candidate_ids) 
                                 VALUES (?, ?, ?, 'to_be_invoiced', ?, ?, NOW(), ?)";
                $invoice_stmt = $conn->prepare($invoice_query);
                $due_date = date('Y-m-d', strtotime('+30 days'));
                $notes = "Auto-generated invoice for {$customer->name} including outstanding candidates. " .
                        "Total candidates: {$candidate_count}. " .
                        "Invoice period: {$customer->invoice_period}";

                $invoice_stmt->execute([
                    $customer->id,
                    $customer->invoice_period,
                    $total_amount ?: 0,
                    $due_date,
                    $notes,
                    json_encode($candidate_ids),
                ]);

                $update_query = "UPDATE customers SET last_invoice_sent = ? WHERE id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->execute([$today, $customer->id]);

                // Mark candidates as invoice generated to avoid re-pick
                if (! empty($candidate_ids)) {
                    $placeholders = implode(',', array_fill(0, count($candidate_ids), '?'));
                    $mark_stmt = $conn->prepare("UPDATE candidates SET invoice_genrated = 1 WHERE id IN ($placeholders)");
                    $mark_stmt->execute($candidate_ids);
                }

                $invoices_created++;
                echo "Created invoice for customer: {$customer->name} (ID: {$customer->id}) - Amount: {$total_amount} - Candidates: {$candidate_count}\n";
            } else {
                echo "No candidates found for customer: {$customer->name} (ID: {$customer->id}) in period {$start_date} to {$today}\n";
            }
        } else {
            echo "Skipping customer: {$customer->name} (ID: {$customer->id}) - Period not reached\n";
        }
    }

    echo "Invoice generation completed. Created {$invoices_created} invoices.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
