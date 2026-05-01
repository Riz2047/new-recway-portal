<?php

require_once "../vendor/autoload.php";

use PhpOffice\PhpSpreadsheet\Spreadsheet;

use PhpOffice\PhpSpreadsheet\IOFactory;

include_once('../includes/connection.php');

function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y')
{

    $dates = [];

    $current = strtotime($first);

    $last = strtotime($last);

    while ($current <= $last) {

        $dates[] = date($output_format, $current);

        $current = strtotime($step, $current);

    }

    return $dates;

}

if (isset($_POST['reported'])) {

    global $pdo;

    $conn = $pdo->open();

    if ($_POST['checked']) {

        $reported = 1;

    } else {

        $reported = 0;

    }

    $query = "UPDATE candidates SET reported = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$reported, $_POST['id']]);

    echo $res;

}

if (isset($_POST['invoice_sent'])) {

    global $pdo;

    $conn = $pdo->open();

    $date = date('Y-m-d');

    if ($_POST['checked'] == 'true') {

        $invoice_sent = 1;

        $query = "UPDATE candidates SET invoice_sent = ?, invoice_date = '{$date}' WHERE id = ?";

    } else {

        $invoice_sent = 0;

        $query = "UPDATE candidates SET invoice_sent = ?, invoice_date = NULL WHERE id = ?";

        $date = 'Null';

    }

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$invoice_sent, $_POST['id']]);

    echo $date;

}

function getData($desc, $start, $end, $id)
{

    global $conn;

    $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';

    $query .= " INNER JOIN candidates c ON history.order_id = c.id";

    $query .= " INNER JOIN customers ON c.cus_id = customers.id";

    $query .= ' WHERE `desc` LIKE "%'. $desc .'%" AND c.cus_id = ? AND Date(date_time) >= ? AND Date(date_time) <= ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id, $start, $end]);

    $candidates = $stmt->fetchAll();

    $candidates2 = [];

    if (! empty($candidates)) {

        foreach ($candidates as $key => $candidate) {

            $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' AND `desc` NOT LIKE '%Interview has been booked%' LIMIT 1";

            $stmt = $conn->prepare($query);

            $stmt->execute([$start, $end]);

            $can = $stmt->fetch();

            if (! empty($can) && ! value_exists($can->id, $candidates)) {

                unset($candidates[$key]);

                $candidates2 = array_values($candidates);

            }

        }

        if (! empty($candidates2)) {

            $candidates = $candidates2;

        }

    }

    return $candidates;

}

if (isset($_POST['filter'])) {

    global $pdo;

    $conn = $pdo->open();

    $id = $_POST['id'];

    $start = $_POST['start'];

    $end = $_POST['end'];

    $query = 'SELECT * FROM candidates WHERE cus_id = ? AND created >= ? AND created <= ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id, $start, $end]);

    $candidates = $stmt->fetchAll();

    $data = [];

    $data['total'] = count($candidates);

    $statuses2 = ["pending", "booked", "approved", "interrupted", "investigation", "denied", "show", "canceled", "answer"];

    $data['booked'] = 0;

    $data['approved'] = 0;

    $data['interrupted'] = 0;

    $data['investigation'] = 0;

    $data['denied'] = 0;

    $data['show'] = 0;

    $data['canceled'] = 0;

    $data['answer'] = 0;

    if (! empty($candidates)) {

        foreach ($candidates as $candidate) {

            $data[$statuses2[$candidate->status]] += 1;

        }

    }

    $query = 'SELECT * FROM candidates WHERE cus_id = ? AND created >= ? AND created <= ? AND status = 0';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id, $start, $end]);

    $pending = $stmt->fetchAll();

    $data['pending'] = count($pending);

    //    $booked = getData('Interview has been booked', $start, $end, $id);

    //    $data['booked'] = count($booked);

    //    $approved = getData('approved', $start, $end, $id);

    //    $data['approved'] = count($approved);

    //    $interrupted = getData('interrupted', $start, $end, $id);

    //    $data['interrupted'] = count($interrupted);

    //    $investigation = getData('investigation', $start, $end, $id);

    //    $data['investigation'] = count($investigation);

    //    $denied = getData('denied', $start, $end, $id);

    //    $data['denied'] = count($denied);

    //    $show = getData('show up', $start, $end, $id);

    //    $data['show'] = count($show);

    //    $canceled = getData('canceled', $start, $end, $id);

    //    $data['canceled'] = count($canceled);

    //    $answer = getData("Candidate doesn't answer", $start, $end, $id);

    //    $data['answer'] = count($answer);

    echo json_encode($data);

}

if (isset($_POST['history'])) {

    global $pdo;

    $conn = $pdo->open();

    $start = $_POST['start'];

    $end = $_POST['end'];

    $query = 'SELECT * FROM order_history';

    $query .= ' INNER JOIN interviews ON order_history.interview_id = interviews.id';

    $query .= ' WHERE DATE(created) >= ? AND DATE(created) <= ? ORDER BY created DESC';

    $stmt = $conn->prepare($query);

    $stmt->execute([$start, $end]);

    $history = $stmt->fetchAll();

    echo json_encode($history);

}

if (isset($_POST['zip'])) {

    $files = $_POST['files'];

    $files = substr_replace($files, "", -1);

    $files = explode(',', $files);

    if (count($files) === 1) {

        echo '../uploads/' . $files[0];

    } else {

        $zip = new ZipArchive();

        $zipName = '../uploads/' . time() . '-file.zip';

        if ($zip->open($zipName, ZipArchive::CREATE) === true) {

            // Add files to the zip file

            foreach ($files as $file) {

                $zip->addFile('../uploads/'.$file);

            }

            // All files are added, so close the zip file.

            $zip->close();

            echo $zipName;

        }

    }

}

function value_exists($id, $candidates)
{

    foreach ($candidates as $candidate) {

        if ($id == $candidate->hid) {

            return true;

        }

    }

    return false;

}

if (isset($_POST['status'])) {

    global $pdo;

    $conn = $pdo->open();

    $start = $_POST['startDate'];

    $end = $_POST['endDate'];

    $col = '';

    $bulb = false;

    $period = date_range($start, $end, '+1 day', 'd M');

    $customerSelected = $_POST['customerSelected'];

    $companySelected = $_POST['companySelected'];

    if ($_POST['status'] == 'created') {

        $query = 'SELECT *,candidates.id AS cid FROM candidates';

        if ($companySelected != 0) {

            $query .= " INNER JOIN customers ON customers.id = candidates.cus_id";

        }

        $query .= ' WHERE created >= ? AND created <= ?';

        if ($customerSelected != 0) {

            $query .= " AND candidates.cus_id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        // if($customerSelected != 0) {

        //     $query = 'SELECT * FROM candidates WHERE created >= ? AND created <= ? AND cus_id = ' . $customerSelected;

        // } else {

        //     $query = 'SELECT * FROM candidates WHERE created >= ? AND created <= ?';

        // }

        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end]);

        $candidates = $stmt->fetchAll();

        $col = 'created';

    }

    if ($_POST['status'] == 'approved') {

        $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';

        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }

        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }

        $query .= ' WHERE `desc` = ? AND Date(date_time) >= ? AND Date(date_time) <= ?';

        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        $stmt = $conn->prepare($query);

        $stmt->execute(['Candidate has been approved', $start, $end]);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';

        $candidates2 = [];

        if (! empty($candidates)) {

            foreach ($candidates as $key => $candidate) {

                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

                $stmt = $conn->prepare($query);

                $stmt->execute([$start, $end]);

                $can = $stmt->fetch();

                if (! empty($can) && ! value_exists($can->id, $candidates)) {

                    unset($candidates[$key]);

                    $candidates2 = array_values($candidates);

                }

            }

            if (! empty($candidates2)) {

                $candidates = $candidates2;

            }

        }

    }

    if ($_POST['status'] == 'booked') {

        $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';

        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }

        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }

        $query .= ' WHERE `desc` = ? AND Date(date_time) >= ? AND Date(date_time) <= ?';

        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        $stmt = $conn->prepare($query);

        $stmt->execute(['Interview has been booked', $start, $end]);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';

        $candidates2 = [];

        if (! empty($candidates)) {

            foreach ($candidates as $key => $candidate) {

                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

                $stmt = $conn->prepare($query);

                $stmt->execute([$start, $end]);

                $can = $stmt->fetch();

                if (! empty($can) && ! value_exists($can->id, $candidates)) {

                    unset($candidates[$key]);

                    $candidates2 = array_values($candidates);

                }

            }

            if (! empty($candidates2)) {

                $candidates = $candidates2;

            }

        }

    }

    if ($_POST['status'] == 'canceled') {

        $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';

        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }

        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }

        $query .= " WHERE `desc` LIKE '%canceled%' AND Date(date_time) >= ? AND Date(date_time) <= ?";

        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end]);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';

        $candidates2 = [];

        if (! empty($candidates)) {

            foreach ($candidates as $key => $candidate) {

                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

                $stmt = $conn->prepare($query);

                $stmt->execute([$start, $end]);

                $can = $stmt->fetch();

                if (! empty($can) && ! value_exists($can->id, $candidates)) {

                    unset($candidates[$key]);

                    $candidates2 = array_values($candidates);

                }

            }

            if (! empty($candidates2)) {

                $candidates = $candidates2;

            }

        }

    }

    if ($_POST['status'] == 'customerMost') {

        $query = "SELECT cus_id,COUNT(cus_id) AS totalOrders FROM candidates";

        if ($customerSelected != 0 || $companySelected) {

            $query .= " INNER JOIN customers ON customers.id = candidates.cus_id";

        }

        $query .= " WHERE created >= ? AND created <= ?";

        if ($customerSelected != 0) {

            $query .= " AND customers.id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        $query .= " GROUP BY cus_id ORDER BY totalOrders DESC LIMIT 1";

        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end]);

        $candidate = $stmt->fetch();

        $bulb = true;

        if (! empty($candidate)) {

            $query = 'SELECT * FROM customers WHERE id = ? LIMIT 1';

            $stmt = $conn->prepare($query);

            $stmt->execute([$candidate->cus_id]);

            $customerData = $stmt->fetch();

        }

    }

    if ($_POST['status'] == 'customerLeast') {

        $query = "SELECT id,name,email FROM customers";

        if ($customerSelected != 0) {

            $query .= " WHERE customers.id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " WHERE customers.company = '{$companySelected}'";

        }

        $stmt = $conn->prepare($query);

        $stmt->execute();

        $customers = $stmt->fetchAll();

        $bulb = 'noOrders';

        $customersArr = [];

        if (! empty($customers)) {

            foreach ($customers as $customer) {

                $query = 'SELECT * FROM candidates WHERE cus_id = ? AND created >= ? AND created <= ? LIMIT 1';

                $stmt = $conn->prepare($query);

                $stmt->execute([$customer->id, $start, $end]);

                $candidate = $stmt->fetch();

                if (empty($candidate)) {

                    array_push($customersArr, $customer);

                }

            }

        }

    }

    if ($_POST['status'] == 'singleCompany') {

        if ($companySelected != 0) {

            $query = "SELECT * FROM candidates AS ca INNER JOIN customers c on ca.cus_id = c.id WHERE created >= ? AND created <= ? AND c.company = '{$companySelected}'";

        } elseif ($customerSelected != 0) {

            $query = "SELECT * FROM candidates AS ca INNER JOIN customers c on ca.cus_id = c.id WHERE created >= ? AND created <= ? AND c.id = {$customerSelected}";

        } else {

            $query = 'SELECT * FROM candidates WHERE created >= ? AND created <= ?';

        }

        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end]);

        $candidates = $stmt->fetchAll();

        $col = 'created';

    }

    if ($_POST['status'] == 'company') {

        $query = 'SELECT *, o.total
        FROM customers 
        INNER JOIN (Select cus_id, count(*) as total from candidates Group by cus_id order by total desc)AS o
        on customers.id = o.cus_id';

        if ($customerSelected != 0) {

            $query .= " WHERE customers.id = {$customerSelected}";

        }

        if ($companySelected != 0) {

            $query .= " WHERE customers.company = '{$companySelected}'";

        }

        $stmt = $conn->prepare($query);

        $stmt->execute();

        $customers = $stmt->fetchAll();

        $bulb = 'companies';

        $companies = [];

        $companyOrders = [];

        $companyLinks = [];

        if (! empty($customers)) {

            foreach ($customers as $customer) {

                array_push($companies, $customer->company);

                array_push($companyOrders, $customer->total);

                array_push($companyLinks, $customer->id);

            }

        }

    }

    if (! $bulb) {

        if (! empty($candidates)) {

            $created = [];

            foreach ($candidates as $object) {

                if (isset($object->$col)) {

                    $c = $object->$col;

                    $c = Date('d M', strtotime($c));

                    if (! isset($created[$c])) {

                        $created[$c] = 0;

                    }

                    $created[$c]++;

                }

            }

            $data = [];

            foreach ($period as $p) {

                if (isset($created[$p])) {

                    $data += [$p => $created[$p]];

                } else {

                    $data += [$p => 0];

                }

            }

            echo json_encode(['created' => $data, 'period' => $period]);

        } else {

            $data = [];

            foreach ($period as $p) {

                if (isset($created[$p])) {

                    $data += [$p => $created[$p]];

                } else {

                    $data += [$p => 0];

                }

            }

            echo json_encode(['created' => $data, 'period' => $period]);

        }

    } elseif ($bulb === true) {

        if (! empty($customerData)) {

            echo json_encode(['name' => $customerData->name, 'orders' => $candidate->totalOrders]);

        } else {

            echo json_encode(['name' => "", 'orders' => 0]);

        }

    } elseif ($bulb === 'noOrders') {

        if (! empty($customersArr)) {

            echo json_encode(['customers' => $customersArr]);

        } else {

            echo json_encode(['customers' => 'no-data']);

        }

    } elseif ($bulb === 'companies') {

        if (! empty($companies)) {

            echo json_encode(['companies' => $companies, 'companyOrders' => $companyOrders, 'companyLinks' => $companyLinks]);

        } else {

            echo json_encode(['companies' => 'no-data', 'companyOrders' => 0, 'companyLinks']);

        }

    }

}

if (isset($_POST['analytics'])) {

    global $pdo;

    $conn = $pdo->open();

    $display = $_POST['display'];

    if ($_POST['displayStatus'] == 'true') {

        $status = 1;

    } else {

        $status = 0;

    }

    $query = "UPDATE analytics SET status = ? WHERE display = ?";

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$status, $display]);

    if ($res) {

        echo json_encode(['display' => $display, 'status' => $status]);

    }

}

if (isset($_POST['export'])) {

    global $pdo;

    $conn = $pdo->open();

    $start = $_POST['startDate'];

    $end = $_POST['endDate'];

    $customerSelected = $_POST['customerSelected'];

    $companySelected = $_POST['companySelected'];

    $query = 'SELECT *,candidates.id AS cid, candidates.name AS cname FROM candidates';

    $query .= " INNER JOIN customers ON customers.id = candidates.cus_id";

    $query .= " LEFT JOIN staff ON candidates.staff_id = staff.id";

    //    if($companySelected != 0) {

    //        $query .= " INNER JOIN customers ON customers.id = candidates.cus_id";

    //    }

    //    $query .= ' WHERE created >= ? AND created <= ?';

    $query .= ' WHERE (booked >= ? AND booked <= ?) OR (created >= ? AND created <= ?)';

    if ($customerSelected != 0) {

        $query .= " AND candidates.cus_id = {$customerSelected}";

    }

    if ($companySelected != 0) {

        $query .= " AND customers.company = '{$companySelected}'";

    }

    $stmt = $conn->prepare($query);

    $stmt->execute([$start, $end, $start, $end]);

    $candidates = $stmt->fetchAll();

    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'Order ID');

    $sheet->setCellValue('B1', 'Candidate Name');

    $sheet->setCellValue('C1', 'Company');

    $sheet->setCellValue('D1', 'Invoice Reference');

    $sheet->setCellValue('E1', 'Cost Place');

    $sheet->setCellValue('F1', 'Staff');

    $sheet->setCellValue('G1', 'Status');

    $sheet->setCellValue('H1', 'Interview Date');

    if (! empty($candidates)) {

        $statuses = ["Pending", "Booked", "Approved", "Interview Interrupted", "Under investigation with SPO", "Denied", "Did not show up", "Canceled", "Candidate doesn't answer"];

        $i = 2;

        foreach ($candidates as $candidate) {

            $sheet->setCellValue('A' . $i, $candidate->order_id);

            $sheet->setCellValue('B' . $i, $candidate->cname . " " . $candidate->surname);

            $sheet->setCellValue('C' . $i, $candidate->company);

            $sheet->setCellValue('D' . $i, $candidate->reference);

            $sheet->setCellValue('E' . $i, $candidate->cost_place);

            $sheet->setCellValue('F' . $i, $candidate->name);

            $sheet->setCellValue('G' . $i, $statuses[$candidate->status]);

            $sheet->setCellValue('H' . $i, $candidate->booked);

            $i++;

        }

    }

    try {

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();

        $writer->save('php://output');

        $xlsData = ob_get_contents();

        ob_end_clean();

        $response = [

            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,".base64_encode($xlsData),

        ];

        die(json_encode($response));

    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {

        echo json_encode(['error' => $e]);

    }

    //    echo json_encode($candidates);

}
