<?php

session_start();



require_once "../vendor/autoload.php";



use PhpOffice\PhpSpreadsheet\Calculation\Statistical\Counts;

use PhpOffice\PhpSpreadsheet\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use PhpOffice\PhpSpreadsheet\IOFactory;



include_once('../includes/connection.php');



function date_range($first, $last, $step = '+1 day', $output_format = 'd/m/Y')

{



    $dates = array();

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





if (isset($_POST['background_check'])) {

    global $pdo;

    $conn = $pdo->open();



    $type = $_POST['type'];

    $id = $_POST['id'];

    $checked = $_POST['checked'];



    if ($type == 'economy') {

        $secondType = 'criminal_record';

    } else {

        $secondType = 'economy';

    }



    $query = "SELECT {$secondType} FROM candidates WHERE {$secondType} = -1 AND id = {$id}";

    $stmt = $conn->prepare($query);

    $stmt->execute();

    $check = $stmt->fetch();



    if (empty($check)) {

        $date = date('Y-m-d');

    } else {

        $date = null;

    }



    $query = "UPDATE candidates SET {$type} = ?, background_check_date = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$checked, $date, $id]);



    $query = 'SELECT * FROM candidates WHERE id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id]);

    $candidate = $stmt->fetch();



    if (!empty($date)) {

        echo json_encode(['date' => $date, 'candidate' => $candidate]);

    } else {

        echo json_encode(['date' => 'Null', 'candidate' => $candidate]);

    }

    //    echo !empty($date) ? $date : 'Null';

}



function getData($desc, $start, $end, $id)

{

    global $conn;



    $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';

    $query .= " INNER JOIN candidates c ON history.order_id = c.id";

    $query .= " INNER JOIN customers ON c.cus_id = customers.id";

    $query .= ' WHERE `desc` LIKE "%' . $desc . '%" AND c.cus_id = ? AND Date(date_time) >= ? AND Date(date_time) <= ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id, $start, $end]);

    $candidates = $stmt->fetchAll();



    $candidates2 = [];

    if (!empty($candidates)) {

        foreach ($candidates as $key => $candidate) {

            $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' AND `desc` NOT LIKE '%Interview has been booked%' LIMIT 1";

            $stmt = $conn->prepare($query);

            $stmt->execute([$start, $end]);

            $can = $stmt->fetch();

            if (!empty($can) && !value_exists($can->id, $candidates)) {

                unset($candidates[$key]);

                $candidates2 = array_values($candidates);

            }

        }

        if (!empty($candidates2)) {

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

    if (!empty($candidates)) {

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

        $zip = new ZipArchive;

        $zipName = '../uploads/' . time() . '-file.zip';

        if ($zip->open($zipName, ZipArchive::CREATE) === TRUE) {

            // Add files to the zip file

            foreach ($files as $file) {

                $zip->addFile('../uploads/' . $file);

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



        $query .= " INNER JOIN (SELECT order_id, MAX(date_time) AS latest_time FROM history WHERE DATE(date_time) BETWEEN ? AND ? GROUP BY order_id) t ON history.order_id = t.order_id AND history.date_time = t.latest_time";



        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }



        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }



        $query .= ' WHERE `desc` = ?';



        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }



        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }



        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end, 'Candidate has been approved']);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';



        //        $candidates2 = [];

        //        if(!empty($candidates)) {

        //            foreach ($candidates as $key => $candidate) {

        //                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

        //                $stmt = $conn->prepare($query);

        //                $stmt->execute([$start, $end]);

        //                $can = $stmt->fetch();

        //                if(!empty($can) && !value_exists($can->id, $candidates)) {

        //                    unset($candidates[$key]);

        //                    $candidates2 = array_values($candidates);

        //                }

        //            }

        //            if(!empty($candidates2)) {

        //                $candidates = $candidates2;

        //            }

        //        }

    }



    if ($_POST['status'] == 'booked') {

        $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';



        $query .= " INNER JOIN (SELECT order_id, MAX(date_time) AS latest_time FROM history WHERE DATE(date_time) BETWEEN ? AND ? GROUP BY order_id) t ON history.order_id = t.order_id AND history.date_time = t.latest_time";



        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }



        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }



        $query .= ' WHERE `desc` = ?';



        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }



        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }



        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end, 'Interview has been booked']);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';



        //        $candidates2 = [];

        //        if(!empty($candidates)) {

        //            foreach ($candidates as $key => $candidate) {

        //                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

        //                $stmt = $conn->prepare($query);

        //                $stmt->execute([$start, $end]);

        //                $can = $stmt->fetch();

        //                if(!empty($can) && !value_exists($can->id, $candidates)) {

        //                    unset($candidates[$key]);

        //                    $candidates2 = array_values($candidates);

        //                }

        //            }

        //            if(!empty($candidates2)) {

        //                $candidates = $candidates2;

        //            }

        //        }

    }



    if ($_POST['status'] == 'canceled') {

        $query = 'SELECT *, history.id AS hid, history.order_id AS oid FROM history';



        $query .= " INNER JOIN (SELECT order_id, MAX(date_time) AS latest_time FROM history WHERE DATE(date_time) BETWEEN ? AND ? GROUP BY order_id) t ON history.order_id = t.order_id AND history.date_time = t.latest_time";



        if ($companySelected != 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

            $query .= " INNER JOIN customers ON c.cus_id = customers.id";

        }



        if ($customerSelected != 0 && $companySelected == 0) {

            $query .= " INNER JOIN candidates c ON history.order_id = c.id";

        }



        $query .= ' WHERE `desc` LIKE ?';



        if ($customerSelected != 0) {

            $query .= " AND c.cus_id = {$customerSelected}";

        }



        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }



        $stmt = $conn->prepare($query);

        $stmt->execute([$start, $end, '%canceled%']);

        $candidates = $stmt->fetchAll();

        $col = 'date_time';



        //        $candidates2 = [];

        //        if(!empty($candidates)) {

        //            foreach ($candidates as $key => $candidate) {

        //                $query = "SELECT * FROM history WHERE id > {$candidate->hid} AND order_id = {$candidate->oid} AND Date(date_time) >= ? AND Date(date_time) <= ? AND `desc` NOT LIKE '%staff%' AND `desc` NOT LIKE '%created%' LIMIT 1";

        //                $stmt = $conn->prepare($query);

        //                $stmt->execute([$start, $end]);

        //                $can = $stmt->fetch();

        //                if(!empty($can) && !value_exists($can->id, $candidates)) {

        //                    unset($candidates[$key]);

        //                    $candidates2 = array_values($candidates);

        //                }

        //            }

        //            if(!empty($candidates2)) {

        //                $candidates = $candidates2;

        //            }

        //        }

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



        //        echo json_encode(['customersMost' => $candidate]);



        if (!empty($candidate)) {

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

        if (!empty($customers)) {

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

        } else if ($customerSelected != 0) {

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

        $query = 'SELECT customers.id,customers.name,customers.email,customers.company, COUNT(o.id) as  total

        FROM customers 

        INNER JOIN candidates AS o

        on customers.id = o.cus_id WHERE o.expired != "1"';



        if ($customerSelected != 0) {

            $query .= " AND customers.id = {$customerSelected}";

        }



        if ($companySelected != 0) {

            $query .= " AND customers.company = '{$companySelected}'";

        }

        $query .= ' GROUP BY customers.company';



        $stmt = $conn->prepare($query);

        $stmt->execute();

        $customers = $stmt->fetchAll();

        $bulb = 'companies';



        $companies = [];

        $companyOrders = [];

        $companyLinks = [];

        if (!empty($customers)) {

            foreach ($customers as $customer) {

                array_push($companies, $customer->company);

                array_push($companyOrders, $customer->total);

                array_push($companyLinks, $customer->id);

            }

        }

    }



    if (!$bulb) {

        if (!empty($candidates)) {

            $created = [];

            foreach ($candidates as $object) {

                if (isset($object->$col)) {

                    $c = $object->$col;

                    $c = Date('d M', strtotime($c));

                    if (!isset($created[$c])) {

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

    } else if ($bulb === true) {

        if (!empty($customerData)) {

            echo json_encode(['name' => $customerData->name, 'orders' => $candidate->totalOrders]);

        } else {

            echo json_encode(['name' => "", 'orders' => 0]);

        }

    } else if ($bulb === 'noOrders') {

        if (!empty($customersArr)) {

            echo json_encode(['customers' => $customersArr]);

        } else {

            echo json_encode(['customers' => 'no-data']);

        }

    } else if ($bulb === 'companies') {

        if (!empty($companies)) {

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



    $query = 'SELECT *, c.name AS cname, s.name AS sname, history.id AS hid, history.order_id AS oid FROM history';



    $query .= " INNER JOIN (SELECT order_id, MAX(date_time) AS latest_time FROM history WHERE `desc` NOT LIKE ? AND `desc` NOT LIKE ? AND DATE(date_time) BETWEEN ? AND ? GROUP BY order_id) t ON history.order_id = t.order_id AND history.date_time = t.latest_time";



    //    if($companySelected != 0) {

    $query .= " INNER JOIN candidates c ON history.order_id = c.id";

    $query .= " INNER JOIN customers ON c.cus_id = customers.id";

    $query .= " INNER JOIN staff s ON c.staff_id = s.id";

    //    }



    //    if($customerSelected != 0 && $companySelected == 0) {

    //        $query .= " INNER JOIN candidates c ON history.order_id = c.id";

    //    }



    //        $query .= ' WHERE `desc` = ? AND Date(date_time) >= ? AND Date(date_time) <= ?';



    if ($customerSelected != 0) {

        $query .= " AND c.cus_id = {$customerSelected}";

    }



    if ($companySelected != 0) {

        $query .= " AND customers.company = '{$companySelected}'";

    }



    $stmt = $conn->prepare($query);

    $stmt->execute(['%Staff%', '%Order Created%', $start, $end]);

    $candidates = $stmt->fetchAll();



    //    die(json_encode(['can' => $candidates]));



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

    $sheet->setCellValue('I1', 'Invoice Sent');

    $sheet->setCellValue('J1', 'Service Type');

    $sheet->setCellValue('K1', 'Place');

    $sheet->setCellValue('L1', 'Created On');



    if (!empty($candidates)) {



        $i = 2;

        foreach ($candidates as $candidate) {

            $query = "SELECT status FROM statuses WHERE status_detail = ? LIMIT 1";

            $stmt = $conn->prepare($query);

            $stmt->execute([$candidate->desc]);

            $status = $stmt->fetch();



            $query = "SELECT title FROM interviews WHERE id = ? LIMIT 1";

            $stmt = $conn->prepare($query);

            $stmt->execute([$candidate->interview_id]);

            $service_type = $stmt->fetch();



            $query = "SELECT name FROM places WHERE id = ? LIMIT 1";

            $stmt = $conn->prepare($query);

            $stmt->execute([$candidate->place]);

            $place = $stmt->fetch();



            if ($status->status != 'Canceled') {

                $sheet->setCellValue('A' . $i, $candidate->order_id);

                $sheet->setCellValue('B' . $i, $candidate->cname . " " . $candidate->surname);

                $sheet->setCellValue('C' . $i, $candidate->company);

                $sheet->setCellValue('D' . $i, $candidate->reference);

                $sheet->setCellValue('E' . $i, $candidate->cost_place);

                $sheet->setCellValue('F' . $i, $candidate->sname);

                $sheet->setCellValue('G' . $i, $status->status);

                $sheet->setCellValue('H' . $i, $candidate->booked);

                $sheet->setCellValue('I' . $i, $candidate->invoice_sent == 1 ? "✔️" : "❌");

                $sheet->setCellValue('J' . $i, $service_type->title);

                $sheet->setCellValue('K' . $i, !empty($place->name) ? $place->name : "");

                $sheet->setCellValue('L' . $i, $candidate->created);

                $i++;

            }

        }

    }



    try {

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();

        $writer->save('php://output');

        $xlsData = ob_get_contents();

        ob_end_clean();



        $response =  array(

            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . base64_encode($xlsData)

        );



        die(json_encode($response));

    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {

        echo json_encode(['error' => $e]);

    }



    //    echo json_encode($candidates);

}

if (isset($_POST['new_export'])) {



    global $pdo;

    $conn = $pdo->open();

    $start = $_POST['startDate'];

    $end = $_POST['endDate'];

    $create_from = $_POST['create_from'];

    $create_to = $_POST['create_to'];

    $lastStatus = isset($_POST['lastStatus']) ? $_POST['lastStatus'] : '';

    $columns_arr = $_POST['columns_arr'];

    $customerSelected = $_POST['customerSelected'];

    $companySelected = $_POST['companySelected'];

    $service_category = $_POST['service_category'];

    $candidates = [];

    if ($service_category == 0 || $service_category == 1 || $service_category == 9) {

        $candidates = filter_candidate_export(null, null, $customerSelected, $create_from, $create_to, $start, $end, $companySelected, $service_category);

    }

    $candidates_bk = [];

    if ($service_category == 0 || $service_category == 3) {

        $candidates_bk = filter_candidate_export_bk(null, null, $customerSelected, $create_from, $create_to, $start, $end, $companySelected);

    }

    $candidates = array_merge($candidates, $candidates_bk);

    $spreadsheet = new Spreadsheet();

    $sheet = $spreadsheet->getActiveSheet();

    foreach ($columns_arr as $k => $column) {

        foreach ($column as $ke => $val) {

            $val = str_replace('_', ' ', $val);

            $val = ucwords($val);

            $sheet->setCellValue($k . '1', $val);

        }

    }

    $total_cost = 0;

    if (!empty($candidates)) {

        $i = 2;

        foreach ($candidates as $l => $candidate) {

            if ($candidate['status_name'] != 'Canceled') {

                foreach ($columns_arr as $k => $column) {

                    foreach ($column as $key => $val) {

                        // Handling static data

                        switch ($val) {

                            case 'order_id':

                                $sheet->setCellValue($k . $i, $candidate['order_id']);

                                break;

                            case 'vasc_id':

                                $sheet->setCellValue($k . $i, !empty($candidate['vasc_id']) ? $candidate['vasc_id'] : '');

                                break;

                            case 'security_number':

                                $sheet->setCellValue($k . $i, !empty($candidate['security']) ? $candidate['security'] : '');

                                break;

                            case 'candidate':

                                $sheet->setCellValue($k . $i, $candidate['name'] . " " . $candidate['surname']);

                                break;

                            case 'company':

                                $sheet->setCellValue($k . $i, !empty($candidate['customer_company']) ? $candidate['customer_company'] : "Null");

                                break;

                            case 'customer':

                                $sheet->setCellValue($k . $i, !empty($candidate['customer_name']) ? $candidate['customer_name'] : "Null");

                                break;

                            case 'invoice_recepient':

                                $sheet->setCellValue($k . $i, !empty($candidate['referensperson']) ? $candidate['referensperson'] : "Null");

                                break;

                            case 'invoice_reference':

                                $sheet->setCellValue($k . $i, !empty($candidate['reference']) ? $candidate['reference'] : "Null");

                                break;

                            case 'invoice__comment':

                                $sheet->setCellValue($k . $i, !empty($candidate['comment']) ? $candidate['comment'] : "Null");

                                break;

                            default:

                                break;

                        }

                        switch ($key) {

                            case 'pref':

                                $sheet->setCellValue($k . $i, !empty($candidate['referensperson']) ? $candidate['referensperson'] : "Null");

                                break;

                            case 'ref':

                                $sheet->setCellValue($k . $i, !empty($candidate['reference']) ? $candidate['reference'] : "Null");

                                break;

                            case 'comment':

                                $sheet->setCellValue($k . $i, !empty($candidate['comment']) ? $candidate['comment'] : "Null");

                                break;

                                // Add more cases for other indexes if needed

                        }

                        if ($val == 'invoice_sent') {

                            // $invoice_sent = "✔️";

                            if (empty($candidate['invoice_sent'])) {

                                // $invoice_sent = "❌";

                                $sheet->setCellValue($k . $i, "❌");

                            } else if (!empty($candidate['invoice_sent'])) {

                                // $invoice_sent = "✔️";

                                $sheet->setCellValue($k . $i, "✔️");

                            }

                        }

                        switch ($val) {

                            case 'cost_place':

                                $sheet->setCellValue($k . $i, !empty($candidate['cost_place']) ? $candidate['cost_place'] : "Null");

                                break;

                            case 'status':

                                $sheet->setCellValue($k . $i, !empty($candidate['status_name']) ? $candidate['status_name'] : "Null");

                                break;

                            case 'recent_status':

                                $cel_val = array();

                                $query = "SELECT history.*,candidates.order_id as can_id FROM history LEFT JOIN candidates ON history.order_id = candidates.id WHERE history.order_id = :orderId";

                                $stmt = $conn->prepare($query);

                                $stmt->bindParam(':orderId', $candidate['id'], PDO::PARAM_INT);

                                $stmt->execute();

                                $st_det = $stmt->fetchAll();

                                $comma = 1;

                                if (!empty($st_det)) {

                                    foreach ($st_det as $a => $rec_stat) {

                                        if ($rec_stat->desc == 'Interview has been Rescheduling' || $rec_stat->desc == 'Interview Interrupted' || $rec_stat->desc == 'Candidate is under investigation with SPO' || $rec_stat->desc == 'Candidate has been denied after meeting with SPO' || $rec_stat->desc == 'Candidate did not show up') {

                                            $comma++;

                                            if ($comma == 2) {

                                                $cel_val[] =  !empty($rec_stat->desc) ? $rec_stat->desc : "";

                                            } else {

                                                $cel_val[] =  !empty($rec_stat->desc) ? $rec_stat->desc : "";

                                            }

                                        }

                                    }

                                    $cel_val = implode(' , ', $cel_val);

                                }

                                $sheet->setCellValue($k . $i, $cel_val);

                                break;

                            case 'interview_date':

                                $sheet->setCellValue($k . $i, !empty($candidate['booked']) ? $candidate['booked'] : "Null");

                                break;

                            case 'delivery_date':

                                $sheet->setCellValue($k . $i, !empty($candidate['delivery_date']) ? $candidate['delivery_date'] : "Null");

                                break;

                            case 'place':

                                $sheet->setCellValue($k . $i, !empty($candidate['place_name']) ? $candidate['place_name'] : "Null");

                                break;

                            case 'staff':

                                $sheet->setCellValue($k . $i, !empty($candidate['staff_name']) ? $candidate['staff_name'] : "Not Assigned");

                                break;

                            case 'service_type':

                                $sheet->setCellValue($k . $i, $candidate['interview_title']);

                                break;

                            case 'created_on':

                                $sheet->setCellValue($k . $i, $candidate['created']);

                                break;

                            case 'service__cost':

                                $sheet->setCellValue($k . $i, !empty($candidate['service_cost']) ? $candidate['service_cost'] : 0);

                                break;

                            case 'travel__cost':

                                $sheet->setCellValue($k . $i, !empty($candidate['travel_cost']) ? $candidate['travel_cost'] : 0);

                                break;

                            case 'total__cost':

                                $candidate_total_cost = $candidate['travel_cost'] + $candidate['service_cost'];

                                $total_cost += $candidate_total_cost;

                                $sheet->setCellValue($k . $i, $candidate_total_cost);

                                break;

                        }

                    }

                }

                $i++;

            }

        }

    }

    $sheet->setCellValue($k . $i, "Total: " . $total_cost);

    $end = 'ZZ';

    $startLetter = $k;





    if (!empty($candidates)) {

        $i = 2;

        $headersSet = array();

        foreach ($candidates as $candidate) {

            if ($candidate['status_name'] != 'Canceled') {

                if (!empty($candidate['meta_data'])) {

                    $candidate['meta_data'] = json_decode($candidate['meta_data']);

                    foreach ($candidate['meta_data'] as $key => $meta_data) {

                        if (!isset($headersSet[$key])) {

                            if ($startLetter == 'Z') {

                                $startLetter = 'AA';

                            } elseif (strlen($startLetter) == 1 && $startLetter != 'Z') {

                                ++$startLetter;

                            } else {

                                $startLetter++;

                            }

                            $sheet->setCellValue($startLetter . '1', $key);

                            $headersSet[$key] = true;

                        }

                        $sheet->setCellValue($startLetter . $i, !empty($meta_data) ? $meta_data : "Null");

                    }

                }

                $i++;

            }

        }

    }

    try {

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        ob_start();

        $writer->save('php://output');

        $xlsData = ob_get_contents();

        ob_end_clean();



        $response =  array(

            'file' => "data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64," . base64_encode($xlsData)

        );



        die(json_encode($response));

    } catch (\PhpOffice\PhpSpreadsheet\Writer\Exception $e) {

        echo json_encode(['error' => $e]);

    }

}



if (isset($_POST['statusVariable'])) {

    global $pdo;

    $conn = $pdo->open();



    $variable = $_POST['variable'];



    $query = 'SELECT * FROM statuses WHERE variable = ? LIMIT 1';

    $stmt = $conn->prepare($query);

    $stmt->execute([$variable]);

    $exists = $stmt->fetch();



    if (!empty($exists)) {

        echo true;

    } else {

        echo false;

    }

}



if (isset($_POST['msgColVariable'])) {

    global $pdo;

    $conn = $pdo->open();



    $variable = $_POST['variable'];



    $query = 'SELECT * FROM status_services WHERE msg_col = ? LIMIT 1';

    $stmt = $conn->prepare($query);

    $stmt->execute([$variable]);

    $exists = $stmt->fetch();



    if (!empty($exists)) {

        echo true;

    } else {

        echo false;

    }

}



if (isset($_POST['email_status'])) {

    $status_id = $_POST['status_id'];

    $cus_id = $_POST['cus_id'];

    $allowed = $_POST['allowed'] == "true" ? 1 : 0;



    global $pdo;

    $conn = $pdo->open();



    $query = "UPDATE allowed_emails SET allowed = ? WHERE cus_id = ? AND status_id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$allowed, $cus_id, $status_id]);

}



if (isset($_POST['formHtml'])) {

    global $pdo;

    $conn = $pdo->open();

    $lang = $_POST['lang'];



    $query = "SELECT id FROM reports_html WHERE candidate_id = ? AND lang = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$_POST['id'], $lang]);

    $report_html = $stmt->fetch();



    if (!empty($report_html)) {

        $query = "UPDATE reports_html SET report_data = ? WHERE candidate_id = ? AND lang = ?";

        $stmt = $conn->prepare($query);

        $stmt->execute([$_POST['formHtml'], $_POST['id'], $lang]);

    } else {

        $query = "INSERT INTO reports_html (candidate_id, report_data, lang) VALUES (?,?,?)";

        $stmt = $conn->prepare($query);

        $stmt->execute([$_POST['id'], $_POST['formHtml'], $lang]);

    }

}



if (isset($_POST['serviceformHtml'])) {

    global $pdo;

    $conn = $pdo->open();

    $lang = $_POST['lang'];



    $query = "SELECT id FROM company_reports_html WHERE interview_id = ? AND lang = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$_POST['id'], $lang]);

    $report_html = $stmt->fetch();



    if (!empty($report_html)) {

        $query = "UPDATE company_reports_html SET report_data = ? WHERE interview_id = ? AND lang = ?";

        $stmt = $conn->prepare($query);

        $stmt->execute([$_POST['formHtml'], $_POST['id'], $lang]);

    } else {

        $query = "INSERT INTO company_reports_html (interview_id, report_data, lang) VALUES (?,?,?)";

        $stmt = $conn->prepare($query);

        $stmt->execute([$_POST['id'], $_POST['formHtml'], $lang]);

    }

}



if (isset($_POST['comp_report'])) {

    global $pdo;

    $conn = $pdo->open();

    $lang = $_POST['lang'];

    // $company = $_POST['company'];

    $customer = isset($_POST['customer']) ? $_POST['customer'] : '';

    $interview_id = $_POST['interview_id'];



    $query = "SELECT id FROM company_reports_html WHERE customer = ? AND lang = ? AND interview_id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$customer, $lang, $interview_id]);

    $report_html = $stmt->fetch();



    if (!empty($report_html)) {

        $query = "UPDATE company_reports_html SET report_data = ?, meta_info = ? WHERE customer = ? AND lang = ? AND interview_id = ?";

        $stmt = $conn->prepare($query);

        $user_type = 'unknown';

        $user_id = null;



        if (isset($_SESSION['admin']) && isset($_SESSION['admin']->id)) {

            $user_type = 'admin';

            $user_id = $_SESSION['admin']->id;

        } elseif (isset($_SESSION['staff']) && isset($_SESSION['staff']->id)) {

            $user_type = 'staff';

            $user_id = $_SESSION['staff']->id;

        }



        $meta_info = array(

            'user_id' => $_SESSION['admin']->id,

            'user_type' => $user_type,

            'updated_at' => date('Y-m-d H:i:s')

        );

        $meta_info_json = json_encode($meta_info);

        $stmt->execute([$_POST['compformHtml'], $meta_info_json, $customer, $lang, $interview_id]);

    } else {

        $query = "INSERT INTO company_reports_html (customer, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)";

        $stmt = $conn->prepare($query);

        if (isset($_SESSION['admin']) && isset($_SESSION['admin']->id)) {

            $user_type = 'admin';

            $user_id = $_SESSION['admin']->id;

        } elseif (isset($_SESSION['staff']) && isset($_SESSION['staff']->id)) {

            $user_type = 'staff';

            $user_id = $_SESSION['staff']->id;

        }

        $meta_info = array(

            'user_id' => $_SESSION['admin']->id,

            'user_type' => $user_type,

            'created_at' => date('Y-m-d H:i:s')

        );



        $meta_info_json = json_encode($meta_info);

        $stmt->execute([$customer, $_POST['compformHtml'], $interview_id, $lang, $meta_info_json]);

    }

}



if (isset($_POST['reportStatus'])) {

    global $pdo;

    $conn = $pdo->open();



    $reportStatus = $_POST['reportStatus'];

    $orderID = $_POST['orderID'];



    $query = "UPDATE candidates SET report_status = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$reportStatus, $orderID]);

}





if (isset($_POST['interviewPlace'])) {



    global $pdo;

    $conn = $pdo->open();



    $id = $_POST['id'];

    $value = $_POST['value'];



    $query = "UPDATE interviews SET place = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$value, $id]);

}



if (isset($_POST['interviewCountry'])) {



    global $pdo;

    $conn = $pdo->open();



    $id = $_POST['id'];

    $value = $_POST['value'];



    $query = "UPDATE interviews SET country = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$value, $id]);

}





if (isset($_POST['checkedInterview'])) {

    global $pdo;

    $conn = $pdo->open();



    $id = $_POST['id'];



    $query = 'SELECT place, country,delivery_days FROM interviews WHERE id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$id]);

    $data = $stmt->fetch();



    echo json_encode(['data' => $data]);

}

if (isset($_POST['delivery_days'])) {

    global $pdo;

    $conn = $pdo->open();



    $id = $_POST['id'];

    $value = $_POST['delivery_days'];



    $query = "UPDATE interviews SET delivery_days = ? WHERE id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$value, $id]);

}





function filter_candidate_export($place = null, $candidate = null, $customer = null, $order_created_from = null, $order_created_to = null, $interview_date_from = null, $interview_date_to = null, $company = null, $service_category = null)

{

    global $conn;



    $query = "SELECT candidates.*,statuses.status as status_name,statuses.color as status_color, staff.name as staff_name,customers.id as customer_id,customers.name as customer_name,customers.cost_place as cost_place,customers.company as customer_company,places.name as place_name , interviews.title as interview_title FROM candidates LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN places ON candidates.place = places.id LEFT JOIN interviews ON candidates.interview_id = interviews.id WHERE expired = 0";  // Always true condition to simplify query building



    if (!empty($place)) {

        $query .= " AND candidates.place = :place";

    }



    if (!empty($candidate)) {

        $query .= " AND (candidates.name LIKE :candidate OR candidates.surname LIKE :candidate)";

    }



    if (!empty($customer)) {

        $query .= " AND candidates.cus_id = :customer";

    }



    if (!empty($order_created_from)) {

        $query .= " AND candidates.created >= :order_created_from";

    }



    if (!empty($order_created_to)) {

        $query .= " AND candidates.created <= :order_created_to";

    }



    if (!empty($interview_date_from)) {

        $query .= " AND candidates.booked >= :interview_date_from";

    }



    if (!empty($interview_date_to)) {

        $query .= " AND candidates.booked <= :interview_date_to";

    }

    if (!empty($company)) {

        $query .= " AND customers.company = :company";

    }

    if (!empty($service_category)) {

        $query .= " AND interviews.service_cat_id = :service_category";

    }



    $query .= " ORDER BY candidates.booked DESC";

    $stmt = $conn->prepare($query);



    if (!empty($place)) {

        $stmt->bindParam(':place', $place);

    }



    if (!empty($candidate)) {

        $stmt->bindParam(':candidate', $candidate);

    }



    if (!empty($customer)) {

        $stmt->bindParam(':customer', $customer);

    }

    if (!empty($service_category)) {

        $stmt->bindParam(':service_category', $service_category);

    }

    if (!empty($company)) {

        $company = trim($company);

        $companyParam = $company;

        $stmt->bindValue(':company', $companyParam);

    }

    if (!empty($order_created_from)) {

        $stmt->bindValue(':order_created_from', date('Y-m-d', strtotime($order_created_from)));

    }



    if (!empty($order_created_to)) {

        $stmt->bindValue(':order_created_to', date('Y-m-d', strtotime($order_created_to)));

    }



    if (!empty($interview_date_from)) {

        $stmt->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));

    }



    if (!empty($interview_date_to)) {

        $stmt->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));

    }

    $res = $stmt->execute();

    if ($res) {

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;

    } else {

        $errorInfo = $stmt->errorInfo();

        return false;

    }

}

function filter_candidate_export_bk($place = null, $candidate = null, $customer = null, $order_created_from = null, $order_created_to = null, $interview_date_from = null, $interview_date_to = null, $company = null)

{

    global $conn;



    $query = "SELECT candidates.*,statuses.status as status_name,statuses.color as status_color, staff.name as staff_name,customers.id as customer_id,customers.name as customer_name,customers.cost_place as cost_place,customers.company as customer_company,places.name as place_name , interviews.title as interview_title FROM candidates LEFT JOIN statuses ON candidates.status = statuses.id LEFT JOIN staff ON candidates.staff_id = staff.id LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN places ON candidates.place = places.id LEFT JOIN interviews ON candidates.interview_id = interviews.id WHERE expired = 0 AND candidates.invoice_sent = 0";  // Always true condition to simplify query building



    if (!empty($candidate)) {

        $query .= " AND (candidates.name LIKE :candidate OR candidates.surname LIKE :candidate)";

    }



    if (!empty($customer)) {

        $query .= " AND candidates.cus_id = :customer";

    }



    if (!empty($order_created_from)) {

        $query .= " AND candidates.created >= :order_created_from";

    }



    if (!empty($order_created_to)) {

        $query .= " AND candidates.created <= :order_created_to";

    }



    if (!empty($interview_date_from)) {

        $query .= " AND candidates.delivery_date >= :interview_date_from";

    }



    if (!empty($interview_date_to)) {

        $query .= " AND candidates.delivery_date <= :interview_date_to";

    }

    if (!empty($company)) {

        $query .= " AND customers.company = :company";

    }



    $query .= " ORDER BY candidates.delivery_date DESC";

    $stmt = $conn->prepare($query);



    if (!empty($candidate)) {

        $stmt->bindParam(':candidate', $candidate);

    }



    if (!empty($customer)) {

        $stmt->bindParam(':customer', $customer);

    }

    if (!empty($company)) {

        $company = trim($company);

        $companyParam = $company;

        $stmt->bindValue(':company', $companyParam);

    }

    if (!empty($order_created_from)) {

        $stmt->bindValue(':order_created_from', date('Y-m-d', strtotime($order_created_from)));

    }



    if (!empty($order_created_to)) {

        $stmt->bindValue(':order_created_to', date('Y-m-d', strtotime($order_created_to)));

    }



    if (!empty($interview_date_from)) {

        $stmt->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));

    }



    if (!empty($interview_date_to)) {

        $stmt->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));

    }

    $res = $stmt->execute();

    if ($res) {

        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $data;

    } else {

        $errorInfo = $stmt->errorInfo();

        return false;

    }

}


if (isset($_POST['cus_report'])) {
    if (isset($_POST['customer']) && !empty($_POST['customer'])) {
        global $pdo;
        $conn = $pdo->open();
        $lang = $_POST['lang'];
        $customer = isset($_POST['customer']) ? $_POST['customer'] : '';
        $interview_id = $_POST['interview_id'];

        $query = "SELECT id FROM customer_reports_html WHERE cus_id = ? AND lang = ? AND interview_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$customer, $lang, $interview_id]);
        $report_html = $stmt->fetch();
        if (!empty($report_html)) {
            $query = "UPDATE customer_reports_html SET report_data = ?, meta_info = ? WHERE cus_id = ? AND lang = ? AND interview_id = ?";
            $stmt = $conn->prepare($query);
            $user_type = 'unknown';
            $user_id = null;

            if (isset($_SESSION['admin']) && isset($_SESSION['admin']->id)) {
                $user_type = 'admin';
                $user_id = $_SESSION['admin']->id;
            } elseif (isset($_SESSION['staff']) && isset($_SESSION['staff']->id)) {
                $user_type = 'staff';
                $user_id = $_SESSION['staff']->id;
            }

            $meta_info = array(
                'user_id' => $_SESSION['admin']->id,
                'user_type' => $user_type,
                'updated_at' => date('Y-m-d H:i:s')
            );
            $meta_info_json = json_encode($meta_info);
            $stmt->execute([$_POST['compformHtml'], $meta_info_json, $customer, $lang, $interview_id]);
            
            $query = "SELECT id FROM customers WHERE parent_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$customer]);
            $child_report_html = $stmt->fetchAll();
            if(!empty($child_report_html)){
                foreach($child_report_html as $child_report){
                    $query = "SELECT id FROM customer_reports_html WHERE cus_id = ? AND lang = ? AND interview_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$child_report->id, $lang, $interview_id]);
                    $customer_check = $stmt->fetch();
                    if(!empty($customer_check)){
                        $query = "UPDATE customer_reports_html SET report_data = ?, meta_info = ? WHERE cus_id = ? AND lang = ? AND interview_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$_POST['compformHtml'], $meta_info_json, $child_report->id, $lang, $interview_id]);
                    }else{
                        $query = "INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)";
                        $stmt = $conn->prepare($query);
                        $meta_info = array(
                            'user_id' => $_SESSION['admin']->id,
                            'user_type' => $user_type,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $meta_info_json = json_encode($meta_info);
                        $stmt->execute([$child_report->id, $_POST['compformHtml'], $interview_id, $lang, $meta_info_json]);
                    }

                }
            }
        } else {
            $query = "INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            if (isset($_SESSION['admin']) && isset($_SESSION['admin']->id)) {
                $user_type = 'admin';
                $user_id = $_SESSION['admin']->id;
            } elseif (isset($_SESSION['staff']) && isset($_SESSION['staff']->id)) {
                $user_type = 'staff';
                $user_id = $_SESSION['staff']->id;
            }
            $meta_info = array(
                'user_id' => $_SESSION['admin']->id,
                'user_type' => $user_type,
                'created_at' => date('Y-m-d H:i:s')
            );

            $meta_info_json = json_encode($meta_info);
            $stmt->execute([$customer, $_POST['compformHtml'], $interview_id, $lang, $meta_info_json]);

            $query = "SELECT id FROM customers WHERE parent_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$customer]);
            $child_report_html = $stmt->fetchAll();
            
            if(!empty($child_report_html)){
                foreach($child_report_html as $child_report){
                    $query = "SELECT id FROM customer_reports_html WHERE cus_id = ? AND lang = ? AND interview_id = ?";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$child_report->id, $lang, $interview_id]);
                    $customer_check = $stmt->fetch();
                    if(!empty($customer_check)){
                        $query = "UPDATE customer_reports_html SET report_data = ?, meta_info = ? WHERE cus_id = ? AND lang = ? AND interview_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$_POST['compformHtml'], $meta_info_json, $child_report->id, $lang, $interview_id]);
                    }else{
                        $query = "INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)";
                        $stmt = $conn->prepare($query);
                        $meta_info = array(
                            'user_id' => $_SESSION['admin']->id,
                            'user_type' => $user_type,
                            'created_at' => date('Y-m-d H:i:s')
                        );
                        $meta_info_json = json_encode($meta_info);
                        $stmt->execute([$child_report->id, $_POST['compformHtml'], $interview_id, $lang, $meta_info_json]);
                    }

                }
            }
        }
    }
}