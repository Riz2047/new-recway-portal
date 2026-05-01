<?php

include_once('../../includes/functions.php');

// Set headers for JSON response
header('Content-Type: application/json');
// Check if user is logged in (admin or staff)
if (! isset($_SESSION['admin']) && ! isset($_SESSION['staff'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit;
}

// Handle export invoice request
if (isset($_GET['action']) && $_GET['action'] === 'export_invoice') {
    $invoice_id = $_GET['invoice_id'] ?? '';

    if (empty($invoice_id)) {
        echo "Error: No invoice ID provided";
        exit;
    }

    try {
        // Get invoice details
        $invoice_query = "SELECT ci.*, c.name as customer_name, c.company, c.last_invoice_sent, c.invoice_period FROM customer_invoices ci 
                         LEFT JOIN customers c ON ci.customer_id = c.id 
                         WHERE ci.id = ?";
        $invoice_stmt = $conn->prepare($invoice_query);
        $invoice_stmt->execute([$invoice_id]);
        $invoice = $invoice_stmt->fetch();

        if (! $invoice) {
            echo "Error: Invoice not found";
            exit;
        }

        // Build candidate dataset like includes/ajax.php new_export, constrained by candidate_ids
        $candidates = [];
        $baseSelect = "SELECT cand.id, cand.order_id, cand.vasc_id, cand.security, cand.name, cand.surname, cand.created, cand.booked, cand.interview_id,cand.place,
                               cand.invoice_sent, cand.invoice_date, cand.service_cost, cand.travel_cost,
                               (COALESCE(cand.service_cost,0) + COALESCE(cand.travel_cost,0)) AS total_cost,
                               cand.referensperson AS cand_referensperson,cand.reference AS cand_reference,
                               cust.name AS customer_name, cust.company AS company_name, cust.cost_place,
                               s.status AS status_name, stf.name AS staff_name, pl.name AS place_name,
                               sbd.referenceperson AS inv_recipient, sbd.reference AS inv_reference, sbd.comment AS inv_comment,
                               cand.delivery_date, cand.booked AS cand_interview_date, interview.title AS service_type, interview.service_cat_id
                        FROM candidates cand
                        LEFT JOIN customers cust ON cand.cus_id = cust.id
                        LEFT JOIN statuses s ON cand.status = s.id
                        LEFT JOIN staff stf ON cand.staff_id = stf.id
                        LEFT JOIN places pl ON cand.place = pl.id
                        LEFT JOIN standard_billing_details sbd ON sbd.cus_id = cand.cus_id
                        LEFT JOIN interviews interview ON interview.id = cand.interview_id
                        "
        ;

        if (! empty($invoice->candidate_ids)) {
            $ids = json_decode($invoice->candidate_ids, true);
            if (is_array($ids) && count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $candidates_query = $baseSelect . " WHERE cand.id IN ($placeholders) ORDER BY cand.created DESC";
                $candidates_stmt = $conn->prepare($candidates_query);
                $candidates_stmt->execute($ids);
                $candidates = $candidates_stmt->fetchAll();
            }
        }

        // if (empty($candidates)) {
        //     // Fallback to customer's last invoice sent date to now, by booked range
        //     $customer_settings = findByQuery("SELECT last_invoice_sent FROM customers WHERE id = " . intval($invoice->customer_id));
        //     $start_date = !empty($customer_settings) && !empty($customer_settings->last_invoice_sent) ? $customer_settings->last_invoice_sent : $invoice->created_date;
        //     $end_date = date('Y-m-d');
        //     $candidates_query = $baseSelect . " WHERE cand.cus_id = ? AND DATE(cand.booked) >= ? AND DATE(cand.booked) <= ? AND cand.status IN (4, 7, 21, 22, 52, 54, 55) ORDER BY cand.created DESC";
        //     $candidates_stmt = $conn->prepare($candidates_query);
        //     $candidates_stmt->execute([$invoice->customer_id, $start_date, $end_date]);
        //     $candidates = $candidates_stmt->fetchAll();
        // }

        // Match admin candidates XLSX export using PhpSpreadsheet
        if (function_exists('ob_get_length') && ob_get_length()) {
            ob_clean();
        }
        if (! class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $autoload = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoload)) {
                require_once $autoload;
            }
        }
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Invoices');
            // Title H1
            $sheet->setCellValue('H1', 'Recway Invoice --Company:' . $invoice->company . ' & --Customer:' . $invoice->customer_name);
            // Headers in row 2
            $headers = [
                'Order Id','Vasc Id','Security Number','Candidate','Company','Customer','Billing Details',
                'Status','Status History','Interview Date','Delivery Date','Invoice Sent','Staff','Place','Service Type','Created On',
            ];

            $col = 1;
            foreach ($headers as $h) {
                $sheet->setCellValueByColumnAndRow($col++, 2, $h);
            }
            $sheet->getStyle('A2:AR2')->getFont()->setBold(true);
            // Enable text wrapping for the Billing Details column (column G)
            $sheet->getStyle('G')->getAlignment()->setWrapText(true);
            $sheet->getStyle('I')->getAlignment()->setWrapText(true);

            // Data rows
            $rowNum = 3;

            // Fetch dynamic billing labels from order_forms (like in your HTML)

            foreach ($candidates as $cand) {
                $invoice_recipent_label = 'Invoice Recipient';
                $invoice_reference_label = 'Invoice Reference';
                // $invoice_comment_label = 'Invoice Comment';

                // Fetch order form for this candidate's customer + service
                $order_forms = findAllByQuery('SELECT * FROM order_forms WHERE cus_id = '
                    . intval($invoice->customer_id) . ' AND service_id = ' . intval($cand->interview_id));

                if (! empty($order_forms) && ! empty($order_forms[0]->form)) {
                    $order_form = json_decode($order_forms[0]->form)->form_builder ?? null;
                    if (! empty($order_form->billing_info)) {
                        foreach ($order_form->billing_info as $key => $value) {
                            $k_a = explode(',', $key);
                            if (! empty($k_a[2])) {
                                switch ($k_a[2]) {
                                    case 'pref':
                                        $invoice_recipent_label = $k_a[1];
                                        break;
                                    case 'ref':
                                        $invoice_reference_label = $k_a[1];
                                        break;

                                }
                            }
                        }
                    }
                }

                // Build Billing Details text
                $billing_details = [];
                if (! empty($cand->cand_referensperson)) {
                    $billing_details[] = $invoice_recipent_label . ': ' . $cand->cand_referensperson;
                }
                if (! empty($cand->cand_reference)) {
                    $billing_details[] = $invoice_reference_label . ': ' . $cand->cand_reference;
                }

                $billing_details_str = implode("\n", $billing_details);
                $fullName = trim(((($cand->name ?? '')) . ' ' . ($cand->surname ?? '')));

                $last_invoice_query = "
                    SELECT MAX(created_date) AS last_invoice_date, candidate_ids
                    FROM customer_invoices
                    WHERE 
                    JSON_CONTAINS(candidate_ids,?, '$')
                    AND id < ?
                ";
                $last_invoice_stmt = $conn->prepare($last_invoice_query);
                $last_invoice_stmt->execute([$cand->id, $invoice->id]);
                $last_invoice = $last_invoice_stmt->fetch(PDO::FETCH_ASSOC);
                $last_invoice_date = ! empty($last_invoice['last_invoice_date'])
                    ? $last_invoice['last_invoice_date']
                    : '1970-01-01 00:00:00';
                // echo "<pre>";
                // print_r($last_invoice);
                // echo "</pre>";
                // die;

                // --- Fetch Status History with Status Matching and Timing Logic ---
                $history_query = "
                    SELECT 
                        h.`desc` AS status_desc,
                        h.`date_time`,
                        st.`status` AS matched_status,
                        st.`status_type`
                    FROM `history` h
                    INNER JOIN `statuses` st 
                        ON h.`desc` = st.`status_detail`
                    WHERE 
                        h.`order_id` = ?
                        AND st.`status_type` = ?
                        AND st.`status_detail` IN (
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
                    AND h.`date_time` > ?
                    AND h.`date_time` <= ?
                    ORDER BY h.`date_time` ASC
                ";

                $history_stmt = $conn->prepare($history_query);
                $history_stmt->execute([$cand->id, $cand->service_cat_id, $last_invoice_date, $invoice->created_date]);
                $history_rows = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

                $status_history = '';

                if (! empty($history_rows)) {
                    $lines = [];

                    foreach ($history_rows as $h) {
                        $status_text = $h['status_desc'];
                        $time_text = date('Y-m-d H:i', strtotime($h['date_time']));
                        $line = "$time_text - $status_text";

                        // Candidate cancellation logic
                        if ($status_text === 'Candidate canceled the booked appointment') {
                            $booked_time = findByQuery("
                                SELECT MAX(`date_time`) AS booked_time 
                                FROM `history`
                                WHERE `order_id` = {$cand->id}
                                AND `desc` = 'Interview has been booked'
                                AND `date_time` < '{$h['date_time']}'
                                AND `date_time` > '{$last_invoice_date}'
                                AND `date_time` <= '{$invoice->created_date}'

                            ");
                            if (! empty($booked_time->booked_time)) {
                                $hours_diff = (strtotime($h['date_time']) - strtotime($booked_time->booked_time)) / 3600;
                                if ($hours_diff >= 24) {
                                    $line .= " (Late cancellation — Include in invoice)";
                                } else {
                                    $line .= " (Early cancellation — Exclude from invoice)";
                                }
                            }
                        }

                        // Customer cancellation logic
                        if ($status_text === 'Interview has been canceled by customer') {
                            $booked_time = findByQuery("
                                SELECT MAX(`date_time`) AS booked_time 
                                FROM `history`
                                WHERE `order_id` = {$cand->id}
                                AND `desc` = 'Interview has been booked'
                                AND `date_time` < '{$h['date_time']}'
                                AND `date_time` > '{$last_invoice_date}'
                                AND `date_time` <= '{$invoice->created_date}'

                            ");
                            if (! empty($booked_time->booked_time)) {
                                $hours_diff = (strtotime($h['date_time']) - strtotime($booked_time->booked_time)) / 3600;
                                if ($hours_diff >= 24) {
                                    $line .= " (Late customer cancellation — include in invoice)";
                                } else {
                                    $line .= " (Early customer cancellation — Exclude from invoiced)";
                                }
                            } else {
                                $line .= " (No booking found — not invoiced)";
                            }
                        }

                        $lines[] = $line;
                    }

                    $status_history = implode("\n", $lines);
                }

                $values = [
                    $cand->order_id ?? '',
                    $cand->vasc_id ?? '',
                    $cand->security ?? '',
                    $fullName,
                    $cand->company_name ?? '',
                    $cand->customer_name ?? '',
                    // $cand->cand_referensperson ?? '',
                    // $cand->cand_reference, // Do (5 Siffror)
                    $billing_details_str ?? '',
                    $cand->status_name ?? '',
                    $status_history,
                    $cand->cand_interview_date ?? '',
                    $cand->delivery_date ?? '',
                    ($cand->invoice_sent ? 'Yes' : 'No'),
                    $cand->staff_name ?? '',
                    $cand->service_cat_id !== 3 ? $cand->place_name ?? 'Video' : 'N/A',
                    $cand->service_type ?? '',
                    ($cand->created ?? ''),

                ];
                $col = 1;
                foreach ($values as $v) {
                    $sheet->setCellValueByColumnAndRow($col++, $rowNum, $v);
                }
                $rowNum++;
            }
            for ($i = 1; $i <= count($headers); $i++) {
                $sheet->getColumnDimensionByColumn($i)->setAutoSize(true);
            }
            $customer_name = $invoice->customer_name;
            $company_name = $invoice->company;

            $inv_created_date = date('Y-m-d', strtotime($invoice->created_date));
            $cus_invoice_period = $invoice->period == 'day' ? 'daily' : ($invoice->period == 'week' ? 'weekly' : ($invoice->period == 'month' ? 'monthly' : 'unknown'));
            $fileName = "{$cus_invoice_period}_invoice_{$company_name}_{$customer_name}_{$inv_created_date}.xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $fileName . '"');
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;
        }
        // Fallback to CSV if PhpSpreadsheet missing
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="recway-Portal.csv"');
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Recway - Portal']);
        fputcsv($output, ['#', 'Order ID', 'Place', 'VASC ID', 'Name', 'SSN', 'Customer', 'Company', 'Interview Date', 'Economy', 'Criminal Record', 'Social', 'Invoice Date', 'Background Check Date']);

        $rowNum = 1;
        foreach ($candidates as $cand) {
            $fullName = trim(($cand->name ?? '') . ' ' . ($cand->surname ?? ''));
            fputcsv($output, [
                $rowNum++,
                $cand->order_id,
                $cand->place,
                $cand->vasc_id,
                $fullName,
                $cand->security, // SSN substitute
                $cand->customer_name,
                $cand->company_name,
                '', // Interview Date (not available in current schema)
                $cand->economy,
                $cand->criminal_record,
                $cand->social,
                $cand->invoice_date,
                $cand->background_check_date,
            ]);
        }

        // No summary/footer rows to match candidates export style

        fclose($output);
        exit;

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
        exit;
    }
}

// Handle export request
if (isset($_GET['action']) && $_GET['action'] === 'export') {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="customer_invoices.csv"');

    // Get filter parameters
    $period_filter = $_GET['period_filter'] ?? '';
    $status_filter = $_GET['status_filter'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';

    // Build query with filters for customer_invoices table
    $where_conditions = [];
    $params = [];

    if (! empty($period_filter)) {
        $where_conditions[] = "c.invoice_period = ?";
        $params[] = $period_filter;
    }

    if (! empty($status_filter)) {
        $where_conditions[] = "ci.status = ?";
        $params[] = $status_filter;
    }

    if (! empty($date_from)) {
        $where_conditions[] = "DATE(ci.created_date) >= ?";
        $params[] = $date_from;
    }

    if (! empty($date_to)) {
        $where_conditions[] = "DATE(ci.created_date) <= ?";
        $params[] = $date_to;
    }

    $where_clause = ! empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $query = "SELECT 
                c.name as customer_name,
                c.invoice_period AS period,
                ci.status,
                ci.created_date
              FROM customer_invoices ci
              LEFT JOIN customers c ON ci.customer_id = c.id
              $where_clause
              ORDER BY ci.created_date DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    $results = $stmt->fetchAll();

    // Output CSV
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Customer Name', 'Period', 'Status', 'Created Date']);

    foreach ($results as $row) {
        fputcsv($output, [
            $row->customer_name,
            $row->period,
            $row->status,
            $row->created_date,
        ]);
    }

    fclose($output);
    exit;
}

// Handle status update
if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $invoice_id = $_POST['invoice_id'] ?? '';
    $status = $_POST['status'] ?? '';
    $notes = $_POST['notes'] ?? '';

    if (empty($invoice_id) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        $query = "UPDATE customer_invoices SET status = ?, notes = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$status, $notes, $invoice_id]);

        if ($result) {
            // If invoice is marked as sent, flag linked candidates as invoiced today
            if ($status === 'sent') {
                $inv = findByQuery("SELECT candidate_ids FROM customer_invoices WHERE id = " . intval($invoice_id));
                if (! empty($inv) && ! empty($inv->candidate_ids)) {
                    $ids = json_decode($inv->candidate_ids, true);
                    if (is_array($ids) && count($ids) > 0) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $upd = $conn->prepare("UPDATE candidates SET invoice_sent = 1, invoice_date = CURDATE() WHERE id IN ($placeholders)");
                        $upd->execute($ids);
                    }
                }
            }
            if ($status === 'to_be_invoiced') {
                $inv = findByQuery("SELECT candidate_ids FROM customer_invoices WHERE id = " . intval($invoice_id));
                if (! empty($inv) && ! empty($inv->candidate_ids)) {
                    $ids = json_decode($inv->candidate_ids, true);
                    if (is_array($ids) && count($ids) > 0) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $upd = $conn->prepare("UPDATE candidates SET invoice_sent = 0, invoice_date = NULL WHERE id IN ($placeholders)");
                        $upd->execute($ids);
                    }
                }
            }
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Handle DataTables server-side processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'get_invoices') {

    // Get DataTables parameters
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $search_value = $_POST['search']['value'] ?? '';
    $order = $_POST['order'][0] ?? null;

    // Get filter parameters
    $period_filter = $_POST['period_filter'] ?? '';
    $status_filter = $_POST['status_filter'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $fil_customer = $_POST['fil_customer'] ?? '';
    $fil_company = $_POST['fil_company'] ?? '';
    $fil_order_id = $_POST['fil_order_id'] ?? '';
    $fil_interview_date_from = $_POST['fil_interview_date_from'] ?? '';
    $fil_interview_date_to = $_POST['fil_interview_date_to'] ?? '';
    $fil_delivery_date_from = $_POST['fil_delivery_date_from'] ?? '';
    $fil_delivery_date_to = $_POST['fil_delivery_date_to'] ?? '';
    $fil_service_cat = $_POST['fil_service_cat'] ?? '';

    // Build WHERE conditions for customer_invoices table
    $where_conditions = [];
    // $where_conditions[] = "ci.id NOT BETWEEN 78 AND 577";
    // Exclude invoice ID 637 due to data issues
    $where_conditions[] = "ci.id <> 637";

    $params = [];

    if (! empty($period_filter)) {
        $where_conditions[] = "ci.period = ?";
        $params[] = $period_filter;
    }

    if (! empty($status_filter)) {
        // Filter based on orders' invoice_sent status, not customer_invoices.status
        if ($status_filter === 'sent') {
            // All candidates must have invoice_sent = 1 (no candidates with invoice_sent = 0)
            $where_conditions[] = "NOT EXISTS (
                SELECT 1
                FROM candidates cand
                WHERE JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
                AND cand.invoice_sent = 0
            ) AND EXISTS (
                SELECT 1
                FROM candidates cand
                WHERE JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
            )";
        } elseif ($status_filter === 'to_be_invoiced') {
            // At least one candidate has invoice_sent = 0
            $where_conditions[] = "EXISTS (
                SELECT 1
                FROM candidates cand
                WHERE JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
                AND cand.invoice_sent = 0
            )";
        }
    }

    if (! empty($date_from)) {
        $where_conditions[] = "DATE(ci.created_date) >= ?";
        $params[] = $date_from;
    }

    if (! empty($date_to)) {
        $where_conditions[] = "DATE(ci.created_date) <= ?";
        $params[] = $date_to;
    }

    if (! empty($fil_customer)) {
        $where_conditions[] = "c.id = ?";
        $params[] = $fil_customer;
    }
    if (! empty($fil_company)) {
        $where_conditions[] = "c.company = ?";
        $params[] = $fil_company;
    }
    if (! empty($fil_order_id)) {
        $where_conditions[] = "EXISTS (
            SELECT 1
            FROM candidates cand
            WHERE cand.order_id = ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
        $params[] = $fil_order_id;
    }
    if (! empty($fil_service_cat)) {
        if ($fil_service_cat !== 'all') {
            $where_conditions[] = "EXISTS (
            SELECT 1 
            FROM candidates cand
            INNER JOIN interviews i ON cand.interview_id = i.id
            WHERE i.service_cat_id = ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
            $params[] = $fil_service_cat;
        }
    }
    if (! empty($fil_interview_date_from)) {
        $where_conditions[] = "EXISTS (
            SELECT 1
            FROM candidates cand
            WHERE DATE(cand.booked) >= ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
        $params[] = $fil_interview_date_from;
    }
    if (! empty($fil_interview_date_to)) {
        $where_conditions[] = "EXISTS (
            SELECT 1
            FROM candidates cand
            WHERE DATE(cand.booked) <= ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
        $params[] = $fil_interview_date_to;
    }
    if (! empty($fil_delivery_date_from)) {
        $where_conditions[] = "EXISTS (
            SELECT 1
            FROM candidates cand
            WHERE DATE(cand.delivery_date) >= ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
        $params[] = $fil_delivery_date_from;
    }
    if (! empty($fil_delivery_date_to)) {
        $where_conditions[] = "EXISTS (
            SELECT 1
            FROM candidates cand
            WHERE DATE(cand.delivery_date) <= ?
            AND JSON_CONTAINS(ci.candidate_ids, cand.id, '$')
        )";
        $params[] = $fil_delivery_date_to;
    }

    // Add search condition
    if (! empty($search_value)) {
        $where_conditions[] = "(c.name LIKE ? OR c.company LIKE ? )";
        $search_param = "%$search_value%";
        $params[] = $search_param;
        $params[] = $search_param;
    }

    $where_clause = ! empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

    $columns_map = [
        0 => 'c.name',
        1 => 'c.company',
        2 => 'c.invoice_period',
        3 => 'ci.status',
        4 => 'ci.created_date',
    ];
    $order_clause = 'ORDER BY ci.created_date DESC';
    if ($order) {
        $order_col_index = intval($order['column'] ?? 4);
        $order_dir = strtolower($order['dir'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
        if (array_key_exists($order_col_index, $columns_map)) {
            $order_clause = 'ORDER BY ' . $columns_map[$order_col_index] . ' ' . $order_dir;
        }
    }
    // Count total records (optimized query)
    $count_query = "SELECT COUNT(*) as total FROM customer_invoices ci LEFT JOIN customers c ON ci.customer_id = c.id $where_clause";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();

    // Get filtered records with candidate count
    $data_query = "SELECT 
                    ci.id,
                    ci.candidate_ids,
                    c.name as customer_name,
                    c.company,
                    c.invoice_period AS period,
                    ci.status,
                    ci.created_date,
                    ci.notes,
                    (SELECT COUNT(*) FROM candidates WHERE cus_id = ci.customer_id AND DATE(created) >= DATE(ci.created_date)) as candidate_count
                  FROM customer_invoices ci
                  LEFT JOIN customers c ON ci.customer_id = c.id
                  $where_clause
                  $order_clause
                  LIMIT $start, $length";

    $data_stmt = $conn->prepare($data_query);
    $data_stmt->execute($params);
    $records = $data_stmt->fetchAll();

    // Format data for DataTables
    $data = [];
    foreach ($records as $record) {
        // Check status based on orders' invoice_sent status, not customer_invoices.status
        $calculated_status = 'to_be_invoiced';
        if (! empty($record->candidate_ids)) {
            $candidate_ids = json_decode($record->candidate_ids, true);
            if (is_array($candidate_ids) && count($candidate_ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($candidate_ids), '?'));
                $status_check_query = "SELECT COUNT(*) as total, SUM(CASE WHEN invoice_sent = 1 THEN 1 ELSE 0 END) as sent_count 
                                      FROM candidates WHERE id IN ($placeholders)";
                $status_check_stmt = $conn->prepare($status_check_query);
                $status_check_stmt->execute($candidate_ids);
                $status_check_result = $status_check_stmt->fetch(PDO::FETCH_OBJ);

                // If all candidates have invoice_sent = 1, status is 'sent'
                if ($status_check_result && $status_check_result->total > 0 && $status_check_result->sent_count == $status_check_result->total) {
                    $calculated_status = 'sent';
                }
            }
        }

        $status_badge = '';
        switch ($calculated_status) {
            case 'to_be_invoiced':
                $status_badge = '<span class="badge bg-warning">To be invoiced</span>';
                break;
            case 'sent':
                $status_badge = '<span class="badge bg-info">Sent</span>';
                break;
                // case 'paid':
                //     $status_badge = '<span class="badge bg-success">Paid</span>';
                //     break;
            default:
                $status_badge = '<span class="badge bg-secondary">' . ucfirst($calculated_status) . '</span>';
        }

        // Improve actions UI: align horizontally in a flex container
        $actions = '<div class="d-flex align-items-center gap-2 action-btns">'
                 . '<button type="button" class="btn btn-outline-primary btn-sm" onclick="updateInvoiceStatus(' . $record->id . ', \'' . $record->status . '\')">Update Status</button>'
                 . '<button type="button" class="btn btn-custom btn-sm" onclick="exportInvoice(' . $record->id . ')">Export Invoice</button>'
                 . '</div>';

        $data[] = [
            'customer_name' => '<p style="cursor: pointer;" onclick="showInvoiceOrders(' . $record->id . ')">' . $record->customer_name . '</p>',
            'company' => '<p style="cursor: pointer;" onclick="showInvoiceOrders(' . $record->id . ')">' . $record->company . '</p>',
            'period' => ucfirst($record->period),
            'status' => $status_badge,
            'created_date' => date('Y-m-d H:i', strtotime($record->created_date)),
            'actions' => $actions,
        ];
    }

    // Return JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data,
    ]);
    exit;
}

// if (isset($_POST['action']) && $_POST['action'] === 'get_invoice_orders') {
//     $candidate_ids = $_POST['candidate_ids'] ?? '';
//     $implode_candidate_ids = implode(',', $candidate_ids);

//     if (empty($candidate_ids)) {
//         echo json_encode(['success' => false, 'message' => 'Candidate IDs are required']);
//         exit;
//     }

//     try {

//         $query = "SELECT
//             c.order_id,
//             c.booked AS interview_date,
//             c.delivery_date,
//             p.name AS place,
//             i.title AS `service`,
//             i.service_cat_id AS service_type,
//             cust.name AS customer,
//             cust.company AS company
//         FROM
//             candidates c
//         LEFT JOIN
//             places p
//             ON c.place = p.id
//         LEFT JOIN
//             interviews i
//             ON c.interview_id = i.id
//         LEFT JOIN
//             customers cust
//             ON c.cus_id = cust.id
//         WHERE
//             c.id IN ($implode_candidate_ids)";
//         $stmt = $conn->prepare($query);
//         $stmt->execute();
//         $orders = $stmt->fetchAll();

//         if ($orders) {
//             echo json_encode(['success' => true, 'orders' => $orders]);
//         } else {
//             echo json_encode(['success' => false, 'message' => 'No orders found for this invoice']);
//         }
//     } catch (Exception $e) {
//         echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
//     }
//     exit;
// }

if (isset($_POST['action']) && $_POST['action'] === 'get_invoice_orders') {
    $invoice_id = $_POST['invoice_id'] ?? '';

    header('Content-Type: application/json');

    if (empty($invoice_id)) {
        echo json_encode(['success' => false, 'message' => 'Error: No invoice ID provided']);
        exit;
    }

    try {
        // Get invoice details
        $invoice_query = "SELECT ci.*, c.name as customer_name, c.company, c.last_invoice_sent, c.invoice_period 
                          FROM customer_invoices ci 
                          LEFT JOIN customers c ON ci.customer_id = c.id 
                          WHERE ci.id = ?";
        $invoice_stmt = $conn->prepare($invoice_query);
        $invoice_stmt->execute([$invoice_id]);
        $invoice = $invoice_stmt->fetch(PDO::FETCH_OBJ);

        if (! $invoice) {
            echo json_encode(['success' => false, 'message' => 'Error: Invoice not found']);
            exit;
        }

        // Build candidate dataset
        $candidates = [];
        $baseSelect = "SELECT cand.id, cand.order_id, cand.vasc_id, cand.security, cand.name, cand.surname, 
                              cand.created, cand.booked, cand.interview_id, cand.place,
                              cand.invoice_sent, cand.invoice_date, cand.service_cost, cand.travel_cost,
                              (COALESCE(cand.service_cost,0) + COALESCE(cand.travel_cost,0)) AS total_cost,
                              cand.referensperson AS cand_referensperson, cand.reference AS cand_reference,
                              cand.comment AS cand_comment,
                              cust.name AS customer_name, cust.company AS company_name, cust.cost_place, cust.email AS customer_email,
                              s.status AS status_name, stf.name AS staff_name, pl.name AS place_name,
                              sbd.referenceperson AS inv_recipient, sbd.reference AS inv_reference, sbd.comment AS inv_comment,
                              cand.delivery_date, cand.booked AS cand_interview_date, interview.title AS service_type, 
                              interview.service_cat_id
                       FROM candidates cand
                       LEFT JOIN customers cust ON cand.cus_id = cust.id
                       LEFT JOIN statuses s ON cand.status = s.id
                       LEFT JOIN staff stf ON cand.staff_id = stf.id
                       LEFT JOIN places pl ON cand.place = pl.id
                       LEFT JOIN standard_billing_details sbd ON sbd.cus_id = cand.cus_id
                       LEFT JOIN interviews interview ON interview.id = cand.interview_id";

        if (! empty($invoice->candidate_ids)) {
            $ids = json_decode($invoice->candidate_ids, true);
            if (is_array($ids) && count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $candidates_query = $baseSelect . " WHERE cand.id IN ($placeholders) ORDER BY cand.created DESC";
                $candidates_stmt = $conn->prepare($candidates_query);
                $candidates_stmt->execute($ids);
                $candidates = $candidates_stmt->fetchAll(PDO::FETCH_OBJ);
            }
        }

        $orders = [];

        foreach ($candidates as $cand) {
            $invoice_recipent_label = 'Invoice Recipient';
            $invoice_reference_label = 'Invoice Reference';
            $invoice_comment_label = 'Comment';
            // Fetch order form
            $order_forms = findAllByQuery('SELECT * FROM order_forms WHERE cus_id = '
                . intval($invoice->customer_id) . ' AND service_id = ' . intval($cand->interview_id));

            if (! empty($order_forms) && ! empty($order_forms[0]->form)) {
                $order_form = json_decode($order_forms[0]->form)->form_builder ?? null;
                if (! empty($order_form->billing_info)) {
                    foreach ($order_form->billing_info as $key => $value) {
                        $k_a = explode(',', $key);
                        if (! empty($k_a[2])) {
                            switch ($k_a[2]) {
                                case 'pref':
                                    $invoice_recipent_label = $k_a[1];
                                    break;
                                case 'ref':
                                    $invoice_reference_label = $k_a[1];
                                    break;
                                case 'comment':
                                    $invoice_comment_label = $k_a[1];
                                    break;
                            }
                        }
                    }
                }
            }

            // Build billing details
            $billing_details = [];
            if (! empty($cand->cand_referensperson)) {
                $billing_details[] = '<strong>'. $invoice_recipent_label .'</strong>'. ': ' . $cand->cand_referensperson;
            }
            if (! empty($cand->cand_reference)) {
                $billing_details[] = '<strong>'. $invoice_reference_label . '</strong>'.': ' . $cand->cand_reference;
            }
            if (! empty($cand->cand_comment)) {
                $billing_details[] = '<strong>'. $invoice_comment_label .'</strong>'.': ' . $cand->cand_comment;
            }
            $billing_details_str = implode("</br>", $billing_details);
            $fullName = trim(($cand->name ?? '') . ' ' . ($cand->surname ?? ''));
            // Last invoice check
            $last_invoice_query = "
                SELECT MAX(created_date) AS last_invoice_date, candidate_ids
                FROM customer_invoices
                WHERE JSON_CONTAINS(candidate_ids, ?, '$')
                AND id < ?
            ";
            $last_invoice_stmt = $conn->prepare($last_invoice_query);
            $last_invoice_stmt->execute([$cand->id, $invoice->id]);
            $last_invoice = $last_invoice_stmt->fetch(PDO::FETCH_ASSOC);
            $last_invoice_date = ! empty($last_invoice['last_invoice_date'])
                ? $last_invoice['last_invoice_date']
                : '1970-01-01 00:00:00';

            // Status history logic
            $history_query = "
                SELECT 
                    h.`desc` AS status_desc,
                    h.`date_time`,
                    st.`status` AS matched_status,
                    st.`status_type`
                FROM `history` h
                INNER JOIN `statuses` st 
                    ON h.`desc` = st.`status_detail`
                WHERE 
                    h.`order_id` = ?
                    AND st.`status_type` = ?
                    AND st.`status_detail` IN (
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
                    AND h.`date_time` > ?
                    AND h.`date_time` <= ?
                ORDER BY h.`date_time` ASC
            ";
            $history_stmt = $conn->prepare($history_query);
            $history_stmt->execute([$cand->id, $cand->service_cat_id, $last_invoice_date, $invoice->created_date]);
            $history_rows = $history_stmt->fetchAll(PDO::FETCH_ASSOC);

            $status_history = '';
            if (! empty($history_rows)) {
                $lines = [];
                foreach ($history_rows as $h) {
                    $status_text = $h['status_desc'];
                    $time_text = date('Y-m-d H:i', strtotime($h['date_time']));
                    $line = "$time_text - $status_text";

                    // Candidate cancellation logic
                    if ($status_text === 'Candidate canceled the booked appointment') {
                        $booked_time = findByQuery("
                            SELECT MAX(`date_time`) AS booked_time 
                            FROM `history`
                            WHERE `order_id` = {$cand->id}
                            AND `desc` = 'Interview has been booked'
                            AND `date_time` < '{$h['date_time']}'
                            AND `date_time` > '{$last_invoice_date}'
                            AND `date_time` <= '{$invoice->created_date}'
                        ");
                        if (! empty($booked_time->booked_time)) {
                            $hours_diff = (strtotime($h['date_time']) - strtotime($booked_time->booked_time)) / 3600;
                            if ($hours_diff >= 24) {
                                $line .= " (Late cancellation — Include in invoice)";
                            } else {
                                $line .= " (Early cancellation — Exclude from invoice)";
                            }
                        }
                    }

                    // Customer cancellation logic
                    if ($status_text === 'Interview has been canceled by customer') {
                        $booked_time = findByQuery("
                            SELECT MAX(`date_time`) AS booked_time 
                            FROM `history`
                            WHERE `order_id` = {$cand->id}
                            AND `desc` = 'Interview has been booked'
                            AND `date_time` < '{$h['date_time']}'
                            AND `date_time` > '{$last_invoice_date}'
                            AND `date_time` <= '{$invoice->created_date}'
                        ");
                        if (! empty($booked_time->booked_time)) {
                            $hours_diff = (strtotime($h['date_time']) - strtotime($booked_time->booked_time)) / 3600;
                            if ($hours_diff >= 24) {
                                $line .= " (Late customer cancellation — include in invoice)";
                            } else {
                                $line .= " (Early customer cancellation — Exclude from invoiced)";
                            }
                        } else {
                            $line .= " (No booking found — not invoiced)";
                        }
                    }

                    $lines[] = $line;
                }
                $status_history = implode("</br>", $lines);
            }

            // Push into orders array formatted for frontend
            $orders[] = [
                'candidate_id' => $cand->id ?? '',
                'order_id' => $cand->order_id ?? '',
                'vasc_id' => $cand->vasc_id ?? 'N/A',
                // 'security'        => $cand->security ?? '',
                // 'full_name'       => $fullName,
                'company' => $cand->company_name,
                'customer' => $cand->customer_name,
                'customer_email' => $cand->customer_email,
                'billing_details' => $billing_details_str ?? '',
                'status' => $cand->status_name ?? '',
                'status_history' => $status_history ?? 'N/A',
                'interview_date' => $cand->cand_interview_date,
                'delivery_date' => $cand->delivery_date,
                'invoice_sent' => ($cand->invoice_sent ? 'Yes' : 'No'),
                'invoice_sent_bool' => ($cand->invoice_sent ? true : false),
                'staff' => $cand->staff_name,
                'place' => $cand->place_name,
                'service' => $cand->service_type ?? '',
                'service_type' => $cand->service_cat_id ?? '',
            ];
        }

        echo json_encode(['success' => true, 'orders' => $orders, 'invoice_id' => $invoice_id]);
        exit;

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// Handle update invoice sent for a candidate
if (isset($_POST['action']) && $_POST['action'] === 'update_order_invoice_sent') {
    $candidate_id = $_POST['candidate_id'] ?? '';
    $invoice_sent = $_POST['invoice_sent'] ?? '0';
    $invoice_id = $_POST['invoice_id'] ?? '';

    header('Content-Type: application/json');

    if (empty($candidate_id) || empty($invoice_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    try {
        // Update candidate invoice_sent and invoice_date
        $invoice_sent_bool = ($invoice_sent === '1' || $invoice_sent === true || $invoice_sent === 'true');

        if ($invoice_sent_bool) {
            $query = "UPDATE candidates SET invoice_sent = 1, invoice_date = CURDATE() WHERE id = ?";
        } else {
            $query = "UPDATE candidates SET invoice_sent = 0, invoice_date = NULL WHERE id = ?";
        }

        $stmt = $conn->prepare($query);
        $result = $stmt->execute([$candidate_id]);

        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Invoice sent status updated successfully',
                'invoice_sent' => $invoice_sent_bool,
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update invoice sent status']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// If no valid action, return error
echo json_encode(['error' => 'Invalid request']);
