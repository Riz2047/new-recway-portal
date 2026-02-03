<?php
require_once "../../vendor/autoload.php";
include_once('../../includes/functions.php');

// Fetch all customers
// $customers = findAllByQuery("SELECT id FROM customers");

// if (!empty($customers)) {
//     // Prepare the insert statement once for better performance
//     $insertQuery = "INSERT INTO allowed_emails (cus_id, status_id, allowed) VALUES (:cus_id, :status_id, 1)";
//     $stmt = $conn->prepare($insertQuery);
    
//     foreach ($customers as $customer) {
//         // Insert first row with status_id 65
//         $stmt->bindValue(':cus_id', $customer->id);
//         $stmt->bindValue(':status_id', 65);
//         $stmt->execute();
        
//         // Insert second row with status_id 66
//         $stmt->bindValue(':cus_id', $customer->id);
//         $stmt->bindValue(':status_id', 66);
//         $stmt->execute();
//     }
    
//     echo "Successfully inserted " . (count($customers) * 2) . " records into allowed_emails table.";
// } else {
//     echo "No customers found.";
// }


// $interviews = findAllByQuery("SELECT * FROM interviews WHERE service_cat_id = 9");
// if(!empty($interviews)){
//     foreach($interviews as $int){
//         // First insert query with status_id 65
//         $query1 = "INSERT INTO status_services (service_id, status_id, msg_col) VALUES (:service_id, 65, 'noans_msg')";
//         $stmt1 = $conn->prepare($query1);
//         $stmt1->bindParam(':service_id', $int->id);
//         $stmt1->execute();
        
//         // Second insert query with status_id 66
//         $query2 = "INSERT INTO status_services (service_id, status_id, msg_col) VALUES (:service_id, 66, 'REbook_interviews')";
//         $stmt2 = $conn->prepare($query2);
//         $stmt2->bindParam(':service_id', $int->id);
//         $stmt2->execute();
//     }
// }

// Get customer data for services 42,47,50,53,65
// $serviceIds = [42, 47, 50, 53, 65];
// $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));

// // Single query with JOIN to get all needed data
// $query = "SELECT c.id, c.name, c.statuses 
//           FROM customers c
//           JOIN customer_services cs ON c.id = cs.cus_id
//           WHERE cs.service_id IN ($placeholders)";

// $stmt = $conn->prepare($query);
// foreach ($serviceIds as $k => $id) {
//     $stmt->bindValue(($k + 1), $id, PDO::PARAM_INT);
// }
// $stmt->execute();
// $customers = $stmt->fetchAll(PDO::FETCH_OBJ);

// // Process each customer
// foreach ($customers as $customer) {
//     // 1. Display customer name
//     echo "Customer Name: " . htmlspecialchars($customer->name) . "<br>";
    
//     // 2. Update statuses
//     $currentStatuses = explode(',', $customer->statuses);
    
//     // Add new statuses if they don't exist
//     if (!in_array('65', $currentStatuses)) {
//         $currentStatuses[] = '65';
//     }
//     if (!in_array('66', $currentStatuses)) {
//         $currentStatuses[] = '66';
//     }
    
//     $updatedStatuses = implode(',', $currentStatuses);
    
//     // Update the customer record
//     $updateQuery = "UPDATE customers SET statuses = :statuses WHERE id = :cus_id";
//     $updateStmt = $conn->prepare($updateQuery);
//     $updateStmt->bindParam(':statuses', $updatedStatuses);
//     $updateStmt->bindParam(':cus_id', $customer->id);
//     $updateStmt->execute();
// }

// echo "Status updates completed for all customers.";


// $interviewIds = [42, 47, 50, 53, 65];
// $idsString = implode(',', $interviewIds);

// $noansMsgValue = '<p>Hej, <strong>{customer}, </strong></p>
// <p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p>
// <p><br />Vi har försök  nå <strong>{candidate} </strong>vid flera tillfällen utan resultat. Vi vill gärna att ni kontaktar  <strong>{candidate} </strong>för att följa upp bokningen. </p>
// <p> </p>
// <p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p>
// <p>https://www.recway.se</p>
// <p> </p>
// <p><em>This e-mail is confidential and may contain legally privileged information. If you have received this email in error, please immediately notify us and delete the message from your system. As a recipient of this mail, you are responsible for deleting both mail and attachments as soon as the purpose of access to this information expires, but no longer than six months.</em></p>'; // Replace with your actual value
// $rebookValue = '<p>Hej, <strong>{customer}, </strong></p>
// <p>Uppdatering av status för beställningen: <strong>{orderid}</strong></p>
// <p><strong><br /></strong><strong>{candidate} </strong>blivit ombokat/bokat om sin {interview}. <br /><br />Datum: <strong>{date}<br /></strong></p>
// <p> </p>
// <p><strong>Med vänliga hälsningar</strong><br />Recway – Vägen till en säkrare rekrytering</p>
// <p><a href="https://www.recway.se" target="_blank">https://www.recway.se</a></p>
// <p> </p>
// <p><em>This e-mail is confidential and may contain legally privileged information. If you have received this email in error, please immediately notify us and delete the message from your system. As a recipient of this mail, you are responsible for deleting both mail and attachments as soon as the purpose of access to this information expires, but no longer than six months.</em></p>
// <p><br /><br /></p>';   // Replace with your actual value

// $query = "UPDATE messages 
//           SET noans_msg = '$noansMsgValue', 
//               REbook_interviews = '$rebookValue'
//           WHERE interview_id IN ($idsString)";

// $conn->exec($query); 
// LIYA4A
// MBWUH1
// $candidate = findByQuery("SELECT * FROM candidates WHERE order_id = 'MBWUH1'");
// $customer = findByQuery("SELECT * FROM customers WHERE id = $candidate->cus_id");
// $interview = findByQuery("SELECT * FROM interviews WHERE id = $candidate->interview_id");
// $service_category = findByQuery("SELECT * FROM service_categories WHERE id = $interview->service_cat_id");
// echo "<pre>";
// if (!empty($candidate)) {
//     // foreach ($candidates as $candidate) {
//         $messages = getMessages($candidate->cus_id, $candidate->interview_id);
//         if (!empty($messages)) {
//             $cus_msg = $interview->service_cat_id == 1 || $interview->service_cat_id == 9 ? $messages->cus_msg : $messages->cus_msg_background;
//             if(empty($cus_msg)){
//                 $cus_msg = $messages->cus_msg;
//             }
//             // $cusBody = replace($cus_msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');

//             // saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $service_category->name);
//             $statusID = 1;
//             if ($interview->service_cat_id == 1) {
//                 $statusID = 1;
//             } elseif ($interview->service_cat_id == 3) {
//                 $statusID = 13;
//             } elseif ($interview->service_cat_id == 9) { 
//                 $statusID = 33;
//             }
//             $msg = getStatusMessage($statusID, $candidate->interview_id, $candidate->cus_id);
//             if ($msg) {
//                 $msg = $msg->col;
//             }

//             $canBody = replace($msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');

//             saveEmail("Candidate", $candidate->name, $candidate->order_id, 'Candidate Message', $canBody, $candidate->email, $service_category->name);



//             // // admin email msg
//             // if (empty($messages->admin_msg)) {
//             //     $messages->admin_msg = 'Order has been created successfully For ' . $customer->name . '(customer) and OrderID is' . $candidate->order_id;
//             // }
//             // $adminBody = replace($messages->admin_msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');

//             // $query = 'SELECT * FROM admin LIMIT 1';
//             // $stmt = $conn->prepare($query);
//             // $stmt->execute(); 
//             // $admin = $stmt->fetch();


//             // saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');
//         }

//     // }
// }

// echo "<pre>";
// $candidates = findAllByQuery("SELECT id,order_id, meta_info FROM candidates WHERE expired = 0");

// if (!empty($candidates)) {
//     foreach ($candidates as $row) {
//         if (!empty($row->meta_info)) {
//             $meta_info = json_decode($row->meta_info, true);
//             if (!empty($meta_info)) {
//                 // List of mst_types to check in every condition
//                 $required_types = ['Admin Message', 'Customer Message', 'Candidate Message'];

//                 foreach (['send_email', 'send_email_cus', 'send_email_can'] as $meta_key) {
//                     if (isset($meta_info[$meta_key]) && $meta_info[$meta_key] === 'yes') {
//                         foreach ($required_types as $mst_type) {
//                             // Check if the email of the given mst_type exists for this order_id
//                             $email_check = findByQuery("SELECT id FROM emails WHERE order_id = '{$row->order_id}' AND msg_type = '$mst_type'");

//                             if (empty($email_check)) {
//                                 // If the email is missing, print the order_id and the missing email type
//                                 echo "Missing Email: Order ID: {$row->order_id}, Type: {$mst_type}\n";
//                             }
//                         }
//                     }
//                 }
//             }
//         }

//         // Fetch all logs from history table for this candidate
//         $history_logs = findAllByQuery("SELECT statuses.status 
//                                         FROM history 
//                                         JOIN statuses ON history.desc = statuses.status_detail 
//                                         WHERE history.order_id = {$row->id}");

//         if (!empty($history_logs)) {
//             // Fetch all existing email types for this order_id again
//             $existing_emails = findAllByQuery("SELECT msg_type FROM emails WHERE order_id = '{$row->order_id}'");
//             $existing_types = array_column($existing_emails, 'msg_type');

//             foreach ($history_logs as $history) {
//                 $msg_type = $history->status . " Message"; // Use status variable as msg_type

//                 if (!in_array($msg_type, $existing_types)) {
//                     echo "Missing Email: Order ID: {$row->order_id}, Type: {$msg_type}\n";
//                 }
//             }
//         }
//     }
// }




// use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\IOFactory;
// $candidates = findAllByQuery("SELECT candidates.id, candidates.order_id, candidates.created, candidates.booked FROM candidates JOIN customers ON customers.id = candidates.cus_id WHERE customers.company = 'Scania' AND candidates.booked BETWEEN '2025-01-01' AND '2025-04-30'");

// $columns_arr = [
//     'A' => ['undefined' => 'OrderID'],
//     'B' => ['undefined' => 'Created At'],
//     'C' => ['undefined' => 'Interview Date'],
//     'D' => ['undefined' => 'Interview Booked On'],
//     'E' => ['undefined' => 'Interview Booked In Days'],
//     'F' => ['undefined' => 'Under Investiagtion On'],
//     'G' => ['undefined' => 'Under Investiagtion In Days'],
//     'H' => ['undefined' => 'Approved On'],
//     'I' => ['undefined' => 'Approved in days'],
//     'J' => ['undefined' => 'Denied On'],
//     'K' => ['undefined' => 'Denied in days'],
// ];

// $spreadsheet = new Spreadsheet();
// $sheet = $spreadsheet->getActiveSheet();

// foreach ($columns_arr as $k => $column) {
//     foreach ($column as $ke => $val) {
//         $val = str_replace('_', ' ', $val);
//         $val = ucwords($val);
//         $sheet->setCellValue($k . '1', $val);
//     }
// }

// $i = 2;
// foreach ($candidates as $can) {
//     $history_rows = findAllByQuery("SELECT * FROM history WHERE order_id = " . (int) $can->id . " ORDER BY date_time ASC");

//     $created = new DateTime($can->created);
//     $booked = new DateTime($can->booked);

//     $investigationDate = null;
//     $approvedDate = null;
//     $deniedDate = null;
//     $bookedInDays = null;
//     $bookedOn = null;

//     foreach ($history_rows as $rec_stat) {
//         $desc = trim($rec_stat->desc);
//         if ($desc === 'Interview has been booked') {
//             $bookedInDays = $created->diff(new DateTime($rec_stat->date_time))->days;
//             $bookedOn = new DateTime($rec_stat->date_time);
//         }
//         if (!$investigationDate && in_array($desc, ['Candidate is under investigation', 'Candidate is under investigation with SPO'])) {
//             $investigationDate = new DateTime($rec_stat->date_time);
//         }
//         if (!$approvedDate && $desc === 'Candidate has been approved') {
//             $approvedDate = new DateTime($rec_stat->date_time);
//         }
//         if (!$deniedDate && $desc === 'Candidate has been denied after meeting with SPO') {
//             $deniedDate = new DateTime($rec_stat->date_time);
//         }
//     }

//     $sheet->setCellValue('A' . $i, $can->order_id);
//     $sheet->setCellValue('B' . $i, $can->created);
//     $sheet->setCellValue('C' . $i, $can->booked);
//     $sheet->setCellValue('D' . $i, $bookedOn);
//     $sheet->setCellValue('E' . $i, $bookedInDays);
//     $sheet->setCellValue('F' . $i, $investigationDate ? $investigationDate->format('Y-m-d H:i:s') : '');
//     $sheet->setCellValue('G' . $i, $investigationDate ? $booked->diff($investigationDate)->days : '');
//     $sheet->setCellValue('H' . $i, $approvedDate ? $approvedDate->format('Y-m-d H:i:s') : '');
//     $sheet->setCellValue(
//         'I' . $i,
//         $approvedDate
//         ? ($investigationDate
//             ? $investigationDate->diff($approvedDate)->days
//             : $booked->diff($approvedDate)->days)
//         : ''
//     );
//     $sheet->setCellValue('J' . $i, $deniedDate ? $deniedDate->format('Y-m-d H:i:s') : '');

//     $sheet->setCellValue(
//         'K' . $i,
//         $deniedDate
//         ? ($investigationDate
//             ? $investigationDate->diff($deniedDate)->days
//             : $booked->diff($deniedDate)->days)
//         : ''
//     );

//     $i++;
// }

// $end = 'ZZ';

// try {
//     $filename = 'All Customers Report.xlsx';
//     $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
//     ob_clean();
//     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//     header('Content-Disposition: attachment;filename="' . $filename . '"');
//     header('Cache-Control: max-age=0');
//     $writer->save('php://output');
//     exit;
// } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
//     echo json_encode(['error' => $e->getMessage()]);
// }




































// use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;
// use PhpOffice\PhpSpreadsheet\Spreadsheet;
// use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
// use PhpOffice\PhpSpreadsheet\IOFactory;

// $candidates = findAllByQuery("SELECT candidates.*, staff.name as staff_name,customers.cost_place as cost_place,customers.company as customer_company,customers.name as customer_name,statuses.status as status_name,interviews.title as interview_title,places.name as place_name FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id");
// $columns_arr = [
//     'A' => ['undefined' => 'order_id'],
//     'B' => ['undefined' => 'vasc_id'],
//     'C' => ['undefined' => 'security_number'],
//     'D' => ['undefined' => 'candidate'],
//     'E' => ['undefined' => 'company'],
//     'F' => ['undefined' => 'customer'],
//     'G' => ['undefined' => 'invoice_recepient'],
//     'H' => ['undefined' => 'invoice_reference'],
//     'I' => ['undefined' => 'invoice__comment'],
//     'J' => ['pref' => 'hiring_manager_name (_invoice_recipient_)'],
//     'K' => ['ref' => 'cost_center_for_hiring_manager (_invoice_reference_)'],
//     'L' => ['comment' => 'hr_unit_for_hiring_manager (_invoice_comment_)'],
//     'M' => ['pref' => 'hiring_manager / reference (invoice_recipient)'],
//     'N' => ['undefined' => 'cost_place'],
//     'O' => ['undefined' => 'status'],
//     'P' => ['undefined' => 'recent_status'],
//     'Q' => ['undefined' => 'interview_date'],
//     'R' => ['undefined' => 'delivery_date'],
//     'S' => ['undefined' => 'invoice_sent'],
//     'T' => ['undefined' => 'staff'],
//     'U' => ['undefined' => 'place'],
//     'V' => ['undefined' => 'service_type'],
//     'W' => ['undefined' => 'created_on'],
// ];


// $spreadsheet = new Spreadsheet();
// $sheet = $spreadsheet->getActiveSheet();
// $x = 1;
// $directly_approved = 1;
// $meta = '';
// foreach ($columns_arr as $k => $column) {
//     foreach ($column as $ke => $val) {
//         $val = str_replace('_', ' ', $val);
//         $val = ucwords($val);
//         $sheet->setCellValue($k . '1', $val);
//     }
// }
// $i = 2;
// foreach ($candidates as $l => $candidate) {
//     foreach ($columns_arr as $k => $column) {
//         foreach ($column as $key => $val) {
//             // Handling static data
//             switch ($val) {
//                 case 'order_id':
//                     $sheet->setCellValue($k . $i, $candidate->order_id);
//                     break;
//                 case 'vasc_id':
//                     $sheet->setCellValue($k . $i, !empty($candidate->vasc_id) ? $candidate->vasc_id : '');
//                     break;
//                 case 'security_number':
//                     $sheet->setCellValue($k . $i, !empty($candidate->security) ? $candidate->security : '');
//                     break;
//                 case 'candidate':
//                     $sheet->setCellValue($k . $i, $candidate->name . " " . $candidate->surname);
//                     break;
//                 case 'company':
//                     $sheet->setCellValue($k . $i, !empty($candidate->customer_company) ? $candidate->customer_company : "Null");
//                     break;
//                 case 'customer':
//                     $sheet->setCellValue($k . $i, !empty($candidate->customer_name) ? $candidate->customer_name : "Null");
//                     break;
//                 case 'invoice_recepient':
//                     $sheet->setCellValue($k . $i, !empty($candidate->referensperson) ? $candidate->referensperson : "Null");
//                     break;
//                 case 'invoice_reference':
//                     $sheet->setCellValue($k . $i, !empty($candidate->reference) ? $candidate->reference : "Null");
//                     break;
//                 case 'invoice__comment':
//                     $sheet->setCellValue($k . $i, !empty($candidate->comment) ? $candidate->comment : "Null");
//                     break;
//                 default:
//                     break;
//             }
//             switch ($key) {
//                 case 'pref':
//                     $sheet->setCellValue($k . $i, !empty($candidate->referensperson) ? $candidate->referensperson : "Null");
//                     break;
//                 case 'ref':
//                     $sheet->setCellValue($k . $i, !empty($candidate->reference) ? $candidate->reference : "Null");
//                     break;
//                 case 'comment':
//                     $sheet->setCellValue($k . $i, !empty($candidate->comment) ? $candidate->comment : "Null");
//                     break;
//                     // Add more cases for other indexes if needed
//             }
//             if ($val == 'invoice_sent') {
//                 // $invoice_sent = "✔️";
//                 if (empty($candidate->invoice_sent)) {
//                     // $invoice_sent = "❌";
//                     $sheet->setCellValue($k . $i, "❌");
//                 } else if (!empty($candidate->invoice_sent)) {
//                     // $invoice_sent = "✔️";
//                     $sheet->setCellValue($k . $i, "✔️");
//                 }
//             }
//             switch ($val) {
//                 case 'cost_place':
//                     $sheet->setCellValue($k . $i, !empty($candidate->cost_place) ? $candidate->cost_place : "Null");
//                     break;
//                 case 'status':
//                     $sheet->setCellValue($k . $i, !empty($candidate->status_name) ? $candidate->status_name : "Null");
//                     break;
//                 case 'recent_status':
//                     $cel_val = array();
//                     $query = "SELECT history.*,candidates.order_id as can_id FROM history LEFT JOIN candidates ON history.order_id = candidates.id WHERE history.order_id = :orderId";
//                     $stmt = $conn->prepare($query);
//                     $stmt->bindParam(':orderId', $candidate->id, PDO::PARAM_INT);
//                     $stmt->execute();
//                     $st_det = $stmt->fetchAll();
//                     $comma = 1;
//                     if (!empty($st_det)) {
//                         foreach ($st_det as $a => $rec_stat) {
//                                 $rec_stat->desc = trim($rec_stat->desc);
//                             if ($rec_stat->desc == 'Interview has been Rescheduling' || $rec_stat->desc == 'Interview Interrupted' || $rec_stat->desc == 'Candidate is under investigation' || $rec_stat->desc == 'Candidate is under investigation with SPO' || $rec_stat->desc == 'Candidate has been denied after meeting with SPO' || $rec_stat->desc == 'Candidate did not show up') {
//                                 $comma++;
//                                 if ($comma == 2) {
//                                     $cel_val[] =  !empty($rec_stat->desc) ? $rec_stat->desc : "";
//                                 } else {
//                                     $cel_val[] =  !empty($rec_stat->desc) ? $rec_stat->desc : "";
//                                 }
//                             }
//                         }
//                         $cel_val = implode(' , ', $cel_val);
//                     }
//                     $sheet->setCellValue($k . $i, $cel_val);
//                     break;
//                 case 'interview_date':
//                     $sheet->setCellValue($k . $i, !empty($candidate->booked) ? $candidate->booked : "Null");
//                     break;
//                 case 'delivery_date':
//                     $sheet->setCellValue($k . $i, !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null");
//                     break;
//                 case 'place':
//                     $sheet->setCellValue($k . $i, !empty($candidate->place_name) ? $candidate->place_name : "Null");
//                     break;
//                 case 'staff':
//                     $sheet->setCellValue($k . $i, !empty($candidate->staff_name) ? $candidate->staff_name : "Not Assigned");
//                     break;
//                 case 'service_type':
//                     $sheet->setCellValue($k . $i, $candidate->interview_title);
//                     break;
//                 case 'created_on':
//                     $sheet->setCellValue($k . $i, $candidate->created);
//                     break;
//                 case 'service__cost':
//                     $sheet->setCellValue($k . $i, !empty($candidate->service_cost) ? $candidate->service_cost : 0);
//                     break;
//                 case 'travel__cost':
//                     $sheet->setCellValue($k . $i, !empty($candidate->travel_cost) ? $candidate->travel_cost : 0);
//                     break;
//                 case 'total__cost':
//                     $candidate_total_cost = $candidate->travel_cost + $candidate->service_cost;
//                     $total_cost += $candidate_total_cost;
//                     $sheet->setCellValue($k . $i, $candidate_total_cost);
//                     break;
//             }
//         }
//     }
//     $i++;
// }
// $end = 'ZZ';
// $startLetter = $k;

// if (!empty($candidates)) {
//     $i = 2;
//     $headersSet = array();
//     foreach ($candidates as $candidate) {
//         if (!empty($candidate->meta_data)) {
//             $meta_datas = json_decode($candidate->meta_data);
//             foreach ($meta_datas as $key => $meta_data) {
//                 $key = trim($key);
//                 if ($key == 'Report-ID for the background check from SRS.' || $key == 'Report-ID for the background check from SRS') {
//                     $sheet->setCellValue('X1', "Report-ID for the background check from SRS");
//                     $sheet->setCellValue('X' . $i, $meta_data);
//                 } else if ($key == 'Is currently applying for the position of') {
//                     $sheet->setCellValue('Y1', 'Is currently applying for the position of');
//                     $sheet->setCellValue('Y' . $i, $meta_data);
//                 } else if ($key == 'Employee or consultant?') {
//                     $sheet->setCellValue('Z1', 'Employee or consultant?');
//                     $sheet->setCellValue('Z' . $i, $meta_data);
//                 } else if ($key == 'Country of origin') {
//                     $sheet->setCellValue('AA1', 'Country of origin');
//                     $sheet->setCellValue('AA' . $i, $meta_data);
//                 } else if ($key == 'Have you informed the candidate or consultant about the purpose of security  interview?') {
//                     $sheet->setCellValue('AB1', 'Have you informed the candidate or consultant about the purpose of security  interview?');
//                     $sheet->setCellValue('AB' . $i, $meta_data);
//                 }
//             }
//         }
//         $i++;
//     }
// }
// try {
//     $filename = 'customers_data.xlsx';
//     $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
//     ob_clean();
//     header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
//     header('Content-Disposition: attachment;filename="' . $filename . '"');
//     header('Cache-Control: max-age=0');
//     $writer->save('php://output');
//     exit;
// } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {
//     echo json_encode(['error' => $e->getMessage()]);
// }









// $candidates = findAllByQuery("SELECT candidates.*, staff.name as staff_name, customers.cost_place as cost_place, customers.company as customer_company, customers.name as customer_name, statuses.status as status_name, interviews.title as interview_title, places.name as place_name FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id WHERE customers.company = 'Scania' AND candidates.booked BETWEEN '2025-01-01' AND '2025-04-30'");

// $spreadsheet = new Spreadsheet();
// $sheet = $spreadsheet->getActiveSheet();
// $x = 1;
// $directly_approved = 1;
// $meta = '';

// // Export candidate data to the sheet
// foreach ($columns_arr as $k => $column) {
//     foreach ($column as $ke => $val) {
//         $val = str_replace('_', ' ', $val);
//         $val = ucwords($val);
//         $sheet->setCellValue($k . '1', $val);
//     }
// }

// $i = 2;
// $data = ''; // Variable to store all the data to be written to the .txt file
// foreach ($candidates as $l => $candidate) {
//     $data .= "OrderId: " . $candidate->order_id . "\n";
//     $data .= "Candidate: " . $candidate->name . " " . $candidate->surname . "\n"; // Candidate name
//     foreach ($columns_arr as $k => $column) {
//         foreach ($column as $key => $val) {
//             switch ($val) {
//                 case 'recent_status':
//                     // Fetch the history for this specific candidate based on their order_id
//                     $cel_val = array();
//                     $query = "SELECT * FROM history WHERE order_id = :orderId"; // Filter by order_id for each candidate
//                     $stmt = $conn->prepare($query);
//                     $stmt->bindParam(':orderId', $candidate->id, PDO::PARAM_INT);
//                     $stmt->execute();
//                     $st_det = $stmt->fetchAll();
//                     $comma = 1;
//                     if (!empty($st_det)) {
//                         foreach ($st_det as $a => $rec_stat) {
//                             $rec_stat->desc = trim($rec_stat->desc);
//                             $cel_val[] = !empty($rec_stat->desc) ? $rec_stat->desc : "";

//                             // Add history entry with proper formatting
//                             $data .= "[" . date("M d, Y h:i A", strtotime($rec_stat->date_time)) . "]  =>  " . $rec_stat->desc . ";\n";

//                             // Add comment (if exists) to the history
//                             if (!empty($rec_stat->comment)) {
//                                 // Replace <br> with newline for better formatting in text file
//                                 $rec_stat->comment = str_replace('<br>', "\n", $rec_stat->comment);
//                                 $data .=  trim($rec_stat->comment) . "\n"; // Use a dash or another format for comment
//                             }
//                         }
//                     }
//                     $data .= "\n";
//                     $data .= "\n";
//                     $data .= "\n";
//                     $data .= "\n";
//                     break;
//             }
//         }
//     }
//     $i++;
// }

// Write the gathered data to the .txt file
// try {
//     $filename = 'scania_candidates_data.txt';

//     ob_clean();
//     header('Content-Type: text/plain');
//     header('Content-Disposition: attachment; filename="' . $filename . '"');
//     header('Cache-Control: max-age=0');

//     echo $data; // Output the collected data
//     exit;
// } catch (Exception $e) {
//     echo json_encode(['error' => $e->getMessage()]);
// }
