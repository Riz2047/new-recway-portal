<?php
include_once('../../includes/functions.php');

// Set headers for JSON response
header('Content-Type: application/json');
// Check if user is logged in (admin or staff)
if (!isset($_SESSION['admin']) && !isset($_SESSION['staff'])) {
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
        $invoice_query = "SELECT ci.*, c.name as customer_name FROM customer_invoices ci 
                         LEFT JOIN customers c ON ci.customer_id = c.id 
                         WHERE ci.id = ?";
        $invoice_stmt = $conn->prepare($invoice_query);
        $invoice_stmt->execute([$invoice_id]);
        $invoice = $invoice_stmt->fetch();
        
        if (!$invoice) {
            echo "Error: Invoice not found";
            exit;
        }
        
        // Build candidate dataset like includes/ajax.php new_export, constrained by candidate_ids
        $candidates = [];
        $baseSelect = "SELECT cand.id, cand.order_id, cand.vasc_id, cand.security, cand.name, cand.surname, cand.created, cand.booked,
                               cand.invoice_sent, cand.invoice_date, cand.service_cost, cand.travel_cost,
                               (COALESCE(cand.service_cost,0) + COALESCE(cand.travel_cost,0)) AS total_cost,
                               cust.name AS customer_name, cust.company AS company_name, cust.cost_place,
                               s.status AS status_name, stf.name AS staff_name, pl.name AS place_name,
                               sbd.referenceperson AS inv_recipient, sbd.reference AS inv_reference, sbd.comment AS inv_comment,
                               cand.delivery_date, '' AS service_type
                        FROM candidates cand
                        LEFT JOIN customers cust ON cand.cus_id = cust.id
                        LEFT JOIN statuses s ON cand.status = s.id
                        LEFT JOIN staff stf ON cand.staff_id = stf.id
                        LEFT JOIN places pl ON cand.place = pl.id
                        LEFT JOIN standard_billing_details sbd ON sbd.cus_id = cand.cus_id";

        if (!empty($invoice->candidate_ids)) {
            $ids = json_decode($invoice->candidate_ids, true);
            if (is_array($ids) && count($ids) > 0) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $candidates_query = $baseSelect . " WHERE cand.id IN ($placeholders) AND cand.status IN (4, 7, 21, 22, 52, 54, 55) ORDER BY cand.created DESC";
                $candidates_stmt = $conn->prepare($candidates_query);
                $candidates_stmt->execute($ids);
                $candidates = $candidates_stmt->fetchAll();
            }
        }

        if (empty($candidates)) {
            // Fallback to customer's last invoice sent date to now, by booked range
            $customer_settings = findByQuery("SELECT last_invoice_sent FROM customers WHERE id = " . intval($invoice->customer_id));
            $start_date = !empty($customer_settings) && !empty($customer_settings->last_invoice_sent) ? $customer_settings->last_invoice_sent : $invoice->created_date;
            $end_date = date('Y-m-d');
            $candidates_query = $baseSelect . " WHERE cand.cus_id = ? AND DATE(cand.booked) >= ? AND DATE(cand.booked) <= ? AND cand.status IN (4, 7, 21, 22, 52, 54, 55) ORDER BY cand.created DESC";
            $candidates_stmt = $conn->prepare($candidates_query);
            $candidates_stmt->execute([$invoice->customer_id, $start_date, $end_date]);
            $candidates = $candidates_stmt->fetchAll();
        }
        
        // Match admin candidates XLSX export using PhpSpreadsheet
        if (function_exists('ob_get_length') && ob_get_length()) { ob_clean(); }
        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $autoload = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoload)) { require_once $autoload; }
        }
        if (class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Invoices');
            // Title H1
            $sheet->setCellValue('H1', 'Recway - Portal');
            // Headers in row 2
            $headers = [
                'Order Id','Vasc Id','Security Number','Candidate','Company','Customer','Invoice Recepient','Invoice Reference','Invoice  Comment',
                'Hiring Manager Name ( Invoice Recipient )','Cost Center For Hiring Manager ( Invoice Reference )','Hr Unit For Hiring Manager ( Invoice Comment )',
                'Reference (invoice Recipient)','Reference','Invoice Comment','Hiring Manager Name','Cost Center For Hiring Manager','Hr Unit For Hiring Manager',
                'Hiring Manager / Reference(invoice Recipient)','Ansvarig Chef / Hiring Manager Name ( Invoice Recipient )','Do (5 Siffror)','Referens','Reference Hiring Manager',
                'Kostnadsställe','Hiring Manager','Cost Center','Hiring Manager /reference(invoice Recipient)','Costplace / Reference','Hiring Manager/reference(invoice Recipient)',
                'Anställningsnummer (4 Siffror)','Referensnummer','Cost Place','Status','Interview Date','Delivery Date','Invoice Sent','Staff','Place','Service Type','Created On','Service  Cost','Travel  Cost','Total  Cost'
            ];
            $col = 1; foreach ($headers as $h) { $sheet->setCellValueByColumnAndRow($col++, 2, $h); }
            $sheet->getStyle('A2:AR2')->getFont()->setBold(true);
            // Data rows
            $rowNum = 3;
            foreach ($candidates as $cand) {
                $fullName = trim(((($cand->name ?? '')) . ' ' . ($cand->surname ?? '')));
                $values = [
                    $cand->order_id ?? '',
                    $cand->vasc_id ?? '',
                    $cand->security ?? '',
                    $fullName,
                    $cand->company_name ?? '',
                    $cand->customer_name ?? '',
                    $cand->inv_recipient ?? '',
                    $cand->inv_reference ?? '',
                    $cand->inv_comment ?? '',
                    $cand->inv_recipient ?? '',
                    $cand->cost_place ?? '',
                    '', // HR Unit for Hiring Manager (Invoice Comment)
                    $cand->inv_recipient ?? '',
                    $cand->inv_reference ?? '',
                    $cand->inv_comment ?? '',
                    $cand->inv_recipient ?? '',
                    $cand->cost_place ?? '',
                    '', // HR Unit For Hiring Manager
                    $cand->inv_recipient ?? '',
                    $cand->inv_recipient ?? '',
                    '', // Do (5 Siffror)
                    $cand->inv_reference ?? '',
                    $cand->inv_reference ?? '',
                    $cand->cost_place ?? '',
                    $cand->inv_recipient ?? '',
                    $cand->cost_place ?? '',
                    $cand->inv_recipient ?? '',
                    $cand->cost_place ?? '',
                    $cand->inv_recipient ?? '',
                    '', // Anställningsnummer (4 Siffror)
                    '', // Referensnummer
                    $cand->cost_place ?? '',
                    $cand->status_name ?? '',
                    '', // Interview Date not mapped
                    $cand->delivery_date ?? '',
                    ($cand->invoice_sent ? 'Yes' : 'No'),
                    $cand->staff_name ?? '',
                    $cand->place_name ?? '',
                    $cand->service_type ?? '',
                    ($cand->created ?? ''),
                    (isset($cand->service_cost) ? $cand->service_cost : 0),
                    (isset($cand->travel_cost) ? $cand->travel_cost : 0),
                    (isset($cand->total_cost) ? $cand->total_cost : ((float)($cand->service_cost ?? 0) + (float)($cand->travel_cost ?? 0)))
                ];
                $col = 1; foreach ($values as $v) { $sheet->setCellValueByColumnAndRow($col++, $rowNum, $v); }
                $rowNum++;
            }
            for ($i = 1; $i <= count($headers); $i++) { $sheet->getColumnDimensionByColumn($i)->setAutoSize(true); }
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="recway-Portal.xlsx"');
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
                $cand->background_check_date
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
    
    if (!empty($period_filter)) {
        $where_conditions[] = "c.invoice_period = ?";
        $params[] = $period_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "ci.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(ci.created_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(ci.created_date) <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
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
            $row->created_date
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
                if (!empty($inv) && !empty($inv->candidate_ids)) {
                    $ids = json_decode($inv->candidate_ids, true);
                    if (is_array($ids) && count($ids) > 0) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $upd = $conn->prepare("UPDATE candidates SET invoice_sent = 1, invoice_date = CURDATE() WHERE id IN ($placeholders)");
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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get DataTables parameters
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $search_value = $_POST['search']['value'] ?? '';
    
    // Get filter parameters
    $period_filter = $_POST['period_filter'] ?? '';
    $status_filter = $_POST['status_filter'] ?? '';
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    $fil_customer = $_POST['fil_customer'] ?? '';
    
    // Build WHERE conditions for customer_invoices table
    $where_conditions = [];
    $params = [];
    
    if (!empty($period_filter)) {
        $where_conditions[] = "ci.period = ?";
        $params[] = $period_filter;
    }
    
    if (!empty($status_filter)) {
        $where_conditions[] = "ci.status = ?";
        $params[] = $status_filter;
    }
    
    if (!empty($date_from)) {
        $where_conditions[] = "DATE(ci.created_date) >= ?";
        $params[] = $date_from;
    }
    
    if (!empty($date_to)) {
        $where_conditions[] = "DATE(ci.created_date) <= ?";
        $params[] = $date_to;
    }
    
    if (!empty($fil_customer)) {
        $where_conditions[] = "c.name LIKE ?";
        $params[] = "%$fil_customer%";
    }
    
    // Add search condition
    if (!empty($search_value)) {
        $where_conditions[] = "(c.name LIKE ? OR ci.status LIKE ?)";
        $search_param = "%$search_value%";
        $params[] = $search_param;
        $params[] = $search_param;
    }
    
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    // Count total records (optimized query)
    $count_query = "SELECT COUNT(*) as total FROM customer_invoices ci LEFT JOIN customers c ON ci.customer_id = c.id $where_clause";
    $count_stmt = $conn->prepare($count_query);
    $count_stmt->execute($params);
    $total_records = $count_stmt->fetchColumn();
    
    // Get filtered records with candidate count
    $data_query = "SELECT 
                    ci.id,
                    c.name as customer_name,
                    c.invoice_period AS period,
                    ci.status,
                    ci.created_date,
                    ci.notes,
                    (SELECT COUNT(*) FROM candidates WHERE cus_id = ci.customer_id AND DATE(created) >= DATE(ci.created_date)) as candidate_count
                  FROM customer_invoices ci
                  LEFT JOIN customers c ON ci.customer_id = c.id
                  $where_clause
                  ORDER BY ci.created_date DESC
                  LIMIT $start, $length";
    
    $data_stmt = $conn->prepare($data_query);
    $data_stmt->execute($params);
    $records = $data_stmt->fetchAll();
    
    // Format data for DataTables
    $data = [];
    foreach ($records as $record) {
        $status_badge = '';
        switch ($record->status) {
            case 'to_be_invoiced':
                $status_badge = '<span class="badge bg-warning">To be invoiced</span>';
                break;
            case 'sent':
                $status_badge = '<span class="badge bg-info">Sent</span>';
                break;
            case 'paid':
                $status_badge = '<span class="badge bg-success">Paid</span>';
                break;
            default:
                $status_badge = '<span class="badge bg-secondary">' . ucfirst($record->status) . '</span>';
        }
        
        // Improve actions UI: align horizontally in a flex container
        $actions = '<div class="d-flex align-items-center gap-2 action-btns">'
                 . '<button type="button" class="btn btn-outline-primary btn-sm" onclick="updateInvoiceStatus(' . $record->id . ', \'' . $record->status . '\')">Update Status</button>'
                 . '<button type="button" class="btn btn-custom btn-sm" onclick="exportInvoice(' . $record->id . ')">Export Invoice</button>'
                 . '</div>';
        
        $data[] = [
            'customer_name' => $record->customer_name,
            'period' => ucfirst($record->period),
            'status' => $status_badge,
            'created_date' => date('Y-m-d H:i', strtotime($record->created_date)),
            'actions' => $actions
        ];
    }
    
    // Return JSON response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $total_records,
        'recordsFiltered' => $total_records,
        'data' => $data
    ]);
    exit;
}

// If no valid action, return error
echo json_encode(['error' => 'Invalid request']);
?>
