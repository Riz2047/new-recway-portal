<?php
include_once('../includes/functions.php');
// Auth gate: return JSON for AJAX endpoints instead of HTML redirects
if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {
    if (isset($_POST['action']) && $_POST['action'] === 'get_candidates_data') {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    redirect('signin.php');
}
//Fetch History
if (isset($_POST['type']) && $_POST['type'] == "fetch_history") {
    // Clear any previous output
    ob_clean();
    header('Content-Type: application/json');
    
    try {
        $query = "SELECT * FROM history WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_POST['id']]);
        $history = $stmt->fetchAll();
        
        if (!empty($history)) {
            echo json_encode(['success' => true, "history" => $history]);
        } else {
            echo json_encode(['success' => true, "history" => []]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Database error']);
    }
    exit;
}
//Fetch Comments
if (isset($_POST['type']) && $_POST['type'] == "fetch_comments") {
    $query = 'SELECT * FROM comments WHERE order_id = ? ORDER BY id DESC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['id']]);
    $comments = $stmt->fetchAll();
    if (!empty($comments)) {
        foreach ($comments as &$comment) {
            $query = 'SELECT * FROM ' . $comment->author_type . ' WHERE id = ?';
            $stmt = $conn->prepare($query);
            $stmt->execute([$comment->author_id]);
            $author = $stmt->fetch();
            $comment->author = $author->name;
        }
        echo json_encode(['success' => true, "comments" => $comments]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Fetch Candidate
if (isset($_POST['type']) && $_POST['type'] == "fetch_candidate") {
    $query = 'SELECT c.name, c.surname, c.security, c.vasc_id, c.email, c.phone, c.report,c.note, c.background_check_date, c.cv, c.combine_interview_id, p.name AS place, i.title FROM candidates c';
    $query .= ' INNER JOIN interviews i ON c.interview_id = i.id';
    $query .= ' LEFT JOIN places p ON c.place = p.id';
    $query .= " WHERE c.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['id']]);
    $candidate = $stmt->fetch();
    if (!empty($candidate)) {
        echo json_encode(['success' => true, "candidate" => $candidate]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Update Staff
if (isset($_POST['type']) && $_POST['type'] == "update_staff") {
    $staff_id = $_POST['staff'];
    $can_name = $_POST['can_name'];
    $comment = $_POST['comment'];
    $query = 'SELECT * FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();
    $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$staff_id, $_POST['id']]);
    // Create a DateTime object for Sweden's timezone
    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
    $swedenTime = new DateTime('now', $swedenTimezone);
    $currentTime = $swedenTime->format('H:i:s');
    $dayOfWeek = date('N');
    //matching time between 8am to 5pm
    if (!empty($res)) {
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$_POST['id']]);
        $candidate = $stmt->fetch();
        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->interview_id]);
        $interview = $stmt->fetch();
        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();
        if (isSwedenWorkingHours() == 1) {
            $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_POST['id'], "Staff ({$staff->name}) Assigned to {$candidate->name} {$candidate->surname}", $comment]);
        } else {
            $nextWorkingHour = getNextWorkingHour()->format('Y-m-d H:i:s');
            $query = "INSERT INTO history (order_id, `desc`,date_time, comment, staff_id) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            if (empty($candidate->staff_id)) {
                $last_candidate = 1;
            } else {
                $last_candidate = $candidate->staff_id;
            }
            $res = $stmt->execute([$_POST['id'], "Staff ({$staff->name}) Assigned to {$candidate->name} {$candidate->surname}", $nextWorkingHour, $comment, $last_candidate]);
        }
        $messages = getMessages($candidate->cus_id, $interview->id);
        if ($messages === false) {
            $messages = 'Massage';
        } else {
            $messages = $messages->staff_msg;
        }
        $body = replace($messages, $_POST['cus_name'], $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
        //        $body .= "<br><b>Comment:</b> {$comment}<br><br>";
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');
            sendMail($body, $staff->email, $staff->name, "Candidate Assigned");
        } else {
            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned', '1');
        }
        echo json_encode(['success' => true, 'staff' => $staff]);
    } else {
        echo json_encode(['error' => true]);
    }
}
// Add Comment
if (isset($_POST['type']) && $_POST['type'] == "add_comment") {
    $comment = $_POST['comment'];
    $commented_by = '';
    $commented_type = 'admin';
    if (isset($_SESSION['admin']->id) && !empty($_SESSION['admin']->id)) {
        $commented_by = $_SESSION['admin']->id;
    } else {
        $commented_by = $_SESSION['staff']->id;
        $commented_type = 'staff';
    }
    $query = 'INSERT INTO comments (order_id, author_id, author_type, comment) VALUES (?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_POST['id'], $commented_by, $commented_type, $comment]);
    if (!empty($res)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
if (isset($_POST['fetch_statuses']) && !empty($_POST['fetch_statuses'])) {
    $service_category = findAllByQuery("SELECT * FROM statuses WHERE status_type = " . $_POST['id']);
    if (!empty($service_category)) {
        echo json_encode(['success' => true, 'service_categories' => $service_category]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Update Candidate
if (isset($_POST['type']) && $_POST['type'] == "update_candidate") {
    // Create a DateTime object for Sweden's timezone
    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
    $swedenTime = new DateTime('now', $swedenTimezone);
    $currentTime = $swedenTime->format('H:i:s');
    $dayOfWeek = date('N');
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $security = $_POST['security'];
    $note = $_POST['note'];
    $service = $_POST['service'];
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $combine_interview_id = isset($_POST['combine_interview_id']) ? $_POST['combine_interview_id'] : 0;
    $query = 'SELECT * FROM candidates WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['id']]);
    $candidate = $stmt->fetch();
    $status = $candidate->status;
    $oldservice = $candidate->interview_id;
    $oldemail2 = $candidate->email;
    if ($email != $oldemail2) {
        $customer = findByQuery("SELECT * FROM customers WHERE id = $candidate->cus_id");
        $interview = findByQuery("SELECT * FROM interviews WHERE id = $candidate->interview_id");
        $service_category = findByQuery("SELECT * FROM service_categories WHERE id = $interview->service_cat_id");
        if (!empty($candidate)) {
            // foreach ($candidates as $candidate) {
            $messages = getMessages($candidate->cus_id, $candidate->interview_id);
            if (!empty($messages)) {
                $statusID = 1;
                if ($interview->service_cat_id == 1) {
                    $statusID = 1;
                } elseif ($interview->service_cat_id == 3) {
                    $statusID = 13;
                } elseif ($interview->service_cat_id == 9) {
                    $statusID = 33;
                } elseif ($interview->service_cat_id == 10) {
                    $statusID = 49;
                }
                $msg = getStatusMessage($statusID, $candidate->interview_id, $candidate->cus_id);
                if ($msg) {
                    $msg = $msg->col;
                }
                $canBody = replace($msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Candidate", $candidate->name, $candidate->order_id, 'Candidate Message', $canBody, $email, $service_category->name);
                    sendMail($canBody, $email, $candidate->name, $service_category->name);
                } else {
                    saveEmail("Candidate", $candidate->name, $candidate->order_id, 'Candidate Message', $canBody, $email, $service_category->name, '1');
                }
            }
        }
    }
    if ($service != $oldservice) {
        $customer = findByQuery("SELECT * FROM customers WHERE id = $candidate->cus_id");
        $interview = findByQuery("SELECT * FROM interviews WHERE id = $candidate->interview_id");
        $service_category = findByQuery("SELECT * FROM service_categories WHERE id = $interview->service_cat_id");
        if (!empty($candidate)) {
            // foreach ($candidates as $candidate) {
            $messages = getMessages($candidate->cus_id, $candidate->interview_id);
            if (!empty($messages)) {
                $cus_msg = $interview->service_cat_id == 1 || $interview->service_cat_id == 9 ? $messages->cus_msg : $messages->cus_msg_background;
                if (empty($cus_msg)) {
                    $cus_msg = $messages->cus_msg;
                }
                // $cusBody = replace($cus_msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');
                // saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $service_category->name);
                $statusID = 1;
                if ($interview->service_cat_id == 1) {
                    $status = 1;
                    $statusID = 1;
                } elseif ($interview->service_cat_id == 3) {
                    $status = 13;
                    $statusID = 13;
                } elseif ($interview->service_cat_id == 9) {
                    $status = 33;
                    $statusID = 33;
                } elseif ($interview->service_cat_id == 10) {
                    $status = 49;
                    $statusID = 49;
                }
                $msg = getStatusMessage($statusID, $candidate->interview_id, $candidate->cus_id);
                if ($msg) {
                    $msg = $msg->col;
                }
                $canBody = replace($msg, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, '');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Candidate", $candidate->name, $candidate->order_id, 'Candidate Message', $canBody, $candidate->email, $service_category->name);
                    sendMail($canBody, $candidate->email, $candidate->name, $service_category->name);
                } else {
                    saveEmail("Candidate", $candidate->name, $candidate->order_id, 'Candidate Message', $canBody, $candidate->email, $service_category->name, '1');
                }
            }
        }
    }
    $query = 'SELECT * FROM interviews WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$service]);
    $interviews = $stmt->fetch();
    if (!empty($interviews->place) || $combine_interview_id == 2) {
    } else {
        $place = null;
    }
    $vasc_id = isset($_POST['vasc_id']) ? $_POST['vasc_id'] : null;
    $background_check_date = !empty($_POST['background_check_date']) ? $_POST['background_check_date'] : null;
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
   if (!empty($_FILES['files']['name'][0])) {
        $stmt = $conn->prepare("SELECT cv FROM candidates WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingFiles = !empty($row['cv']) ? explode(',', trim($row['cv'], ',')) : [];
        $remainingSlots = 5 - count($existingFiles);
        $newFiles = [];
        $totalFiles = count($_FILES['files']['name']);
        $files = null;
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($remainingSlots <= 0)
                break;
            $originalName = $_FILES['files']['name'][$i];
            $fileName = time() . '-' . uniqid() . '-' . str_replace(",", "", $originalName);
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName)) {
                $newFiles[] = $fileName;
                $remainingSlots--;
            }
        }
        $finalFiles = array_merge($existingFiles, $newFiles);
        $files = implode(',', $finalFiles);
        $query = 'UPDATE candidates SET name = ?, surname = ?, status = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ?, cv = IFNULL(CONCAT(cv, ?), ?)  WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $status, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $files, $files, $_POST['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, status = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $status, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $_POST['id']]);
    }
    if (!empty($res)) {
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$_POST['id']]);
        $candidate = $stmt->fetch();
        echo json_encode(['success' => true, 'candidate' => $candidate]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Update Status
if (isset($_POST['type']) && $_POST['type'] == "update_status") {
    $status = $_POST['status'];
    $date = $_POST['date'];
    $cus_name = $_POST['cus_name'];
    $can_name = $_POST['can_name'];
    $cus_email = $_POST['cus_email'];
    $bir_interview_place = isset($_POST['bir_interview_place']) ? $_POST['bir_interview_place'] : false;
    if ($bir_interview_place) {
        $query = "UPDATE candidates SET BIR_interview_place = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$bir_interview_place, $_POST['id']]);
    }
    $comment = !empty($_POST['comment']) ? $_POST['comment'] : null;
    $orderID = $_POST['order_id'];
    $report = isset($_FILES['report']) && !empty($_FILES['report']['name']) ? $_FILES['report']['name'] : "";
    $interviewID = $_POST['interviewID'];
    $reportName = time() . "-" . substr(uniqid(), -6) . ".pdf";
    $travelling_cost = !empty($_POST['travelling_cost']) ? $_POST['travelling_cost'] : null;
    $last_interview_date = 0;
    $last_staff_id = 0;
    $d_date = 0;
    $last_status = 0;
    $query = 'SELECT * FROM candidates WHERE order_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$orderID]);
    $rec_order = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($rec_order) {
        if (isSwedenWorkingHours() == 1) {
        } else {
            $last_status = $rec_order['status'];
            $d_date = $rec_order['delivery_date'];
            $last_interview_date = $rec_order['booked'];
        }
    }
    if (!empty($report)) {
        move_uploaded_file($_FILES['report']['tmp_name'], '../uploads/' . $reportName);
    }
    $status = getStatusById($status);
    $date_time = date('Y-m-d H:i:s', strtotime($date . date('H:i:s')));
    // if($status->variable == 'approved' && !empty($travelling_cost)) {
    //     $query = "UPDATE candidates SET travel_cost = ? WHERE id = ?";
    //     $stmt = $conn->prepare($query);
    //     $res = $stmt->execute([$travelling_cost, $_POST['id']]);
    // }
    if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
        $query = 'UPDATE candidates SET status = ?, booked = ?';
        if (!empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, $date, $_POST['id']]);
        if (isset($_SESSION['staff']->id) && !empty($_SESSION['staff']->id)) {
            $query = 'INSERT INTO staff_logs (staff_id, log_msg) VALUES (?,?)';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_SESSION['staff']->id, " changed the status of <a href='invoice.php?id={$_POST['id']}'>{$orderID}</a> to {$status->status}"]);
        }
    } elseif ($status->variable == "rebooking") {
        $query = 'UPDATE candidates SET status = ?, booked = ?';
        if (!empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, null, $_POST['id']]);
        if (isset($_SESSION['staff']->id) && !empty($_SESSION['staff']->id)) {
            $query = 'INSERT INTO staff_logs (staff_id, log_msg) VALUES (?,?)';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_SESSION['staff']->id, " changed the status of <a href='invoice.php?id={$_POST['id']}'>{$orderID}</a> to {$status->status}"]);
        }
    } else {
        $query = 'UPDATE candidates SET status = ?';
        if (!empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        if ($status->variable == "approval_received") {
            $query1 = 'SELECT * FROM interviews WHERE id = ?';
            $stmt = $conn->prepare($query1);
            $stmt->execute([$interviewID]);
            $interviews = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!empty($interviews['delivery_days'])) {
                $d_date = getDateAfterDays($interviews['delivery_days']);
                $query .= ", delivery_date = '{$d_date}'";
            }
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, $_POST['id']]);
        // Logging the status change
        if (isset($_SESSION['staff']->id) && !empty($_SESSION['staff']->id)) {
            $logQuery = 'INSERT INTO staff_logs (staff_id, log_msg) VALUES (?,?)';
            $logStmt = $conn->prepare($logQuery);
            $res = $logStmt->execute([$_SESSION['staff']->id, " changed the status of <a href='invoice.php?id={$_POST['id']}'>{$orderID}</a> to {$status->status}"]);
        }
    }
    $res = "true";
    if (!empty($res)) {
        $commented_by = '';
        if (isset($_SESSION['admin']->name) && !empty($_SESSION['admin']->name)) {
            $commented_by = $_SESSION['admin']->name;
        } else {
            $commented_by = $_SESSION['staff']->name;
        }
        // $comment .= !empty($comment) ? '<br>-' . $_SESSION['admin']->name : '';
        $comment .= '<br>-' . $commented_by;
        if (isSwedenWorkingHours() == 1) {
            $query = "INSERT INTO history (order_id, `desc`, date_time, comment) VALUES (?,?,?,?)";
            $stmt = $conn->prepare($query);
            if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
                $res = $stmt->execute([$_POST['id'], $status->status_detail, date('Y-m-d H:i:s'), $comment]);
            } else {
                $res = $stmt->execute([$_POST['id'], $status->status_detail, $date_time, $comment]);
            }
        } else {
            $nextWorkingHour = getNextWorkingHour()->format('Y-m-d H:i:s');
            $query = "INSERT INTO history (order_id, `desc`, date_time, comment, last_status,staff_id,last_interview_date,last_delivery_date) VALUES (?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
                if (!empty($rec_order['booked'])) {
                    $last_interview_date = $rec_order['booked'];
                } else {
                    $last_interview_date = 1;
                }
                $res = $stmt->execute([$_POST['id'], $status->status_detail, $nextWorkingHour, $comment, $last_status, $last_staff_id, $last_interview_date, $d_date]);
            } else {
                if ($status->variable == "approval_received") {
                    if (!empty($rec_order['delivery_date'])) {
                        $d_date = $rec_order['delivery_date'];
                    } else {
                        $d_date = 1;
                    }
                }
                $res = $stmt->execute([$_POST['id'], $status->status_detail, $nextWorkingHour, $comment, $last_status, $last_staff_id, $last_interview_date, $d_date]);
            }
        }
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$_POST['id']]);
        $candidate = $stmt->fetch();
        $query = 'SELECT * FROM staff WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->staff_id]);
        $staff = $stmt->fetch();
        $query = 'SELECT * FROM customers WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->cus_id]);
        $customer = $stmt->fetch();
        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->interview_id]);
        $service = $stmt->fetch();
        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();
        $query = 'SELECT * FROM additional_customers WHERE cus_id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->cus_id]);
        $add_cus = $stmt->fetchAll();
        if ($status->id == 1 && ($customer->combine_bk_and_security != "0" || $customer->combine_bk_and_security != 0) && ($candidate->combine_interview_id != 0 || $candidate->combine_interview_id != '0')) {
                $d_date = null;
                $vasc_id = isset($candidate->vasc_id) ? $candidate->vasc_id : null;
                $security = isset($candidate->security) ? $candidate->security : null;
                $name = isset($candidate->name) ? $candidate->name : null;
                $surname = isset($candidate->surname) ? $candidate->surname : null;
                $email = isset($candidate->email) ? $candidate->email : null;
                $phone = isset($candidate->phone) ? $candidate->phone : null;
                $referensperson = isset($candidate->pref) ? $candidate->pref : null;
                $reference = isset($candidate->ref) ? $candidate->ref : null;
                $cus_id = isset($candidate->cus_id) ? $candidate->cus_id : null;
                $interview_id = isset($candidate->combine_interview_id) ? $candidate->combine_interview_id : null;
                $comment = isset($candidate->comment) ? $candidate->comment : null;
                $note = isset($candidate->note) ? $candidate->note : null;
                $sendMail = isset($customer->send_email) ? $customer->send_email : null;
                // $sendMailCan = isset($customer->send_email_question) ? $customer->send_email_question : null;
                // $sendMail = isset($customer->send_email) ? $customer->send_email : null;
                $sendMailCan = 'yes';
                $sendMail = 'yes';
                $place = isset($candidate->place) ? $candidate->place : null;
                $staff_id = isset($candidate->staff_id) ? $candidate->staff_id : 0;
                $country = isset($candidate->country) ? $candidate->country : null;
                $mailMsg1 = null;
                $mailMsg2 = null;
                $mailMsg3 = null;
                $mailMsg4 = null;
                $meta_info = array(
                    'send_email_cus' => $sendMail,
                    'send_email_can' => $sendMailCan,
                    'created_by' => $_SESSION['admin']->id,
                    'created_on' => date('Y-m-d H:i:s'),
                    'user' => 'Admin'
                );
                $query = 'SELECT * FROM interviews WHERE id = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$interview_id]);
                    $interview = $stmt->fetch();
                if (!empty($interview->place) || $candidate->combine_interview_id == 2) {
                    $query = 'SELECT * FROM places WHERE id = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$candidate->place]);
                    $place = $stmt->fetch();
                } else {
                    $place = null;
                }
                // if (!empty($interview->delivery_days)) {
                //     $d_date = getDateAfterDays($interview->delivery_days);
                // }
                if ($interview->service_cat_id == 1) {
                    $statusID = 1;
                } else if ($interview->service_cat_id == 3) {
                    $statusID = 13;
                } else if ($interview->service_cat_id == 9) {
                    $statusID = 33;
                } else if ($interview->service_cat_id == 10) {
                    $statusID = 49;
                }
                if ($candidate->id) {
                    $lastInsertId = $candidate->id;
                    $query = 'SELECT * FROM service_categories WHERE id = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$interview->service_cat_id]);
                    $serviceCat = $stmt->fetch();
                    // $query = "INSERT INTO history (order_id, `desc`) VALUES (?,?)";
                    // $stmt = $conn->prepare($query);
                    // $res = $stmt->execute([$lastInsertId, 'Order Created']);
                    $messages = [];
                    // Create a DateTime object for Sweden's timezone
                    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                    $swedenTime = new DateTime('now', $swedenTimezone);
                    $currentTime = $swedenTime->format('H:i:s');
                    $dayOfWeek = date('N');
                    $messages = getMessages($cus_id, $interview->id);
                    if (!empty($messages)) {
            $query = 'UPDATE candidates SET status = 1, interview_id = ? WHERE id = ?';
            $stmt = $conn->prepare($query);
            $stmt->execute([$candidate->combine_interview_id, $candidate->id]);
                        if ($sendMail == 'yes') {
                            // echo json_encode(['success' => true, 'sendMail' => $sendMail, 'customer' => $customer, 'interview' => $interview, 'serviceCat' => $serviceCat, 'messages' => $messages]);
                            $cus_msg = $interview->service_cat_id == 1 || $interview->service_cat_id == 9 ? $messages->cus_msg : $messages->cus_msg_background;
                            // customer email msg
                            $cusBody = replace($cus_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
                            // if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name);
                                $mailMsg1 = sendMail($cusBody, $customer->email, $customer->name, $serviceCat->name);
                            // } 
                            // else {
                                // saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name, '1');
                            // }
                        }
                        if ($sendMailCan == 'yes') {
                            if ($interview->service_cat_id == 1) {
                                $statusID = 1;
                            } elseif ($interview->service_cat_id == 3) {
                                $statusID = 13;
                            } elseif ($interview->service_cat_id == 9) {
                                $statusID = 33;
                            } else if ($interview->service_cat_id == 10) {
                                $statusID = 49;
                            }
                            $msg = getStatusMessage($statusID, $interview_id, $cus_id);
                            if ($msg) {
                                $msg = $msg->col;
                            }
                            // staff if assigned email msg
                            if (!empty($staff_id)) {
                                $staff_msg = getMessages($candidate->cus_id, $interview->id);
                                if (empty($staff_msg)) {
                                    $staff_msg = getMessages();
                                }
                                $body = replace($staff_msg->staff_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
                                // if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                    saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');
                                    $mailMsg2 = sendMail($body, $staff->email, $staff->name, "Candidate Assigned");
                                // } else {
                                    // saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned', '1');
                                // }
                            }
                            // candidate email msg
                            $canBody = replace($msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
                            // if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name);
                                $mailMsg3 = sendMail($canBody, $candidate->email, $candidate->name, $serviceCat->name);
                            // } else {
                                // saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name, '1');
                            // }
                            if ($customer->sent_email == 1) {
                            // if (1 == 1) {
                                // if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                    saveEmail("Customer", $name, $candidate->order_id, 'CC email of candidate registration', $canBody, $customer->email, $serviceCat->name);
                                    $mailMsg4 = sendMail($canBody, $customer->email, $customer->name, $serviceCat->name);
                                // } else {
                                    // saveEmail("Customer", $name, $candidate->order_id, 'CC email of candidate registration', $canBody, $customer->email, $serviceCat->name, '1');
                                // }
                            }
                        }
                        // admin email msg
                        if (empty($messages->admin_msg)) {
                            $messages->admin_msg = 'Order has been created successfully For ' . $customer->name . '(customer) and OrderID is' . $candidate->order_id;
                        }
                        $adminBody = replace($messages->admin_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
                        $query = 'SELECT * FROM admin LIMIT 1';
                        $stmt = $conn->prepare($query);
                        $stmt->execute();
                        $admin = $stmt->fetch();
                        // if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                            saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');
                            $mailMsg5 = sendMail($adminBody, $admin->email, $admin->name, "Order Created");
                            // } else {
                            // saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created', '1');
                        // }
                        $msg = getStatusMessage($status->id, $interview->id, $candidate->cus_id);
                        if (!empty($msg)) {
                            $msg = $msg->col;
                            // Create a DateTime object for Sweden's timezone
                            $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                            $swedenTime = new DateTime('now', $swedenTimezone);
                            $currentTime = $swedenTime->format('H:i:s');
                            $dayOfWeek = date('N');
                            //matching time between 8am to 5pm
                            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '06:00:00' && $currentTime < '18:00:00') {
                                $body = replace($msg, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $interview->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID, $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status);
                                if (!empty($add_cus)) { // additional customers email send
                                    foreach ($add_cus as $ad_cu) {
                                        saveEmail("Additional Customer", $ad_cu->name, $orderID, $status->status . ' Message', $body, $ad_cu->email, $status->status);
                                    }
                                }
                                if (isEmailAllowed($candidate->cus_id, $status->id)) {
                                    $directory = "../security-report-uploads/";
                                    $filename = $candidate->basic_investigation_result;
                                    if (($status->variable == "approved" || $status->variable == "denied") && !empty($filename) && file_exists($directory . $filename) && $customer->send_security_report == 1) {
                                        // $query = "UPDATE candidates SET travel_cost = ? WHERE id = ?";
                                        // $stmt = $conn->prepare($query);
                                        // $res = $stmt->execute([$travelling_cost, $_POST['id']]);
                                        sendMail($body, $cus_email, $cus_name, $status->status, $directory . $filename);
                                        if (!empty($add_cus)) { // additional customers email send
                                            foreach ($add_cus as $ad_cu) {
                                                sendMail($body, $ad_cu->email, $ad_cu->name, $status->status, $directory . $filename);
                                            }
                                        }
                                    } else {
                                        sendMail($body, $cus_email, $cus_name, $status->status);
                                        if (!empty($add_cus)) { // additional customers email send
                                            foreach ($add_cus as $ad_cu) {
                                                sendMail($body, $ad_cu->email, $ad_cu->name, $status->status);
                                            }
                                        }
                                    }
                                }
                                if ($status->variable == "canceled") {
                                    $body = $msg;
                                    $body = replace($body, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $interview->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled');
                                    sendMail($body, $candidate->email, $candidate->name, 'Order Canceled');
                                }
                            } else {
                                $body = replace($msg, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $interview->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID, $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status, "1");
                                if (!empty($add_cus)) { // additional customers email send
                                    foreach ($add_cus as $ad_cu) {
                                        saveEmail("Additional Customer", $ad_cu->name, $orderID, $status->status . ' Message', $body, $ad_cu->email, $status->status, "1");
                                    }
                                }
                                if ($status->variable == "canceled") {
                                    $body = $msg;
                                    $body = replace($body, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $interview->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled', "1");
                                }
                            }
                        }
                    } else {
                        // flash("email messages not found!", "errorMsg");
                        echo json_encode(['success' => true, 'msg' => 'email messages not found!']);
                    }
                }
        }
        else{
        $msg = getStatusMessage($status->id, $service->id, $candidate->cus_id);
        if (!empty($msg)) {
            $msg = $msg->col;
            // Create a DateTime object for Sweden's timezone
            $swedenTimezone = new DateTimeZone('Europe/Stockholm');
            $swedenTime = new DateTime('now', $swedenTimezone);
            $currentTime = $swedenTime->format('H:i:s');
            $dayOfWeek = date('N');
            //matching time between 8am to 5pm
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                $body = replace($msg, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID, $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status);
                if (!empty($add_cus)) { // additional customers email send
                    foreach ($add_cus as $ad_cu) {
                        saveEmail("Additional Customer", $ad_cu->name, $orderID, $status->status . ' Message', $body, $ad_cu->email, $status->status);
                    }
                }
                if (isEmailAllowed($candidate->cus_id, $status->id)) {
                    $directory = "../security-report-uploads/";
                    $filename = $candidate->basic_investigation_result;
                    if (($status->variable == "approved" || $status->variable == "denied") && !empty($filename) && file_exists($directory . $filename) && $customer->send_security_report == 1) {
                        // $query = "UPDATE candidates SET travel_cost = ? WHERE id = ?";
                        // $stmt = $conn->prepare($query);
                        // $res = $stmt->execute([$travelling_cost, $_POST['id']]);
                        sendMail($body, $cus_email, $cus_name, $status->status, $directory . $filename);
                        if (!empty($add_cus)) { // additional customers email send
                            foreach ($add_cus as $ad_cu) {
                                sendMail($body, $ad_cu->email, $ad_cu->name, $status->status, $directory . $filename);
                            }
                        }
                    } else {
                        sendMail($body, $cus_email, $cus_name, $status->status);
                        if (!empty($add_cus)) { // additional customers email send
                            foreach ($add_cus as $ad_cu) {
                                sendMail($body, $ad_cu->email, $ad_cu->name, $status->status);
                            }
                        }
                    }
                }
                if ($status->variable == "canceled") {
                    $body = $msg;
                    $body = replace($body, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled');
                    sendMail($body, $candidate->email, $candidate->name, 'Order Canceled');
                }
            } else {
                $body = replace($msg, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID, $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status, "1");
                if (!empty($add_cus)) { // additional customers email send
                    foreach ($add_cus as $ad_cu) {
                        saveEmail("Additional Customer", $ad_cu->name, $orderID, $status->status . ' Message', $body, $ad_cu->email, $status->status, "1");
                    }
                }
                if ($status->variable == "canceled") {
                    $body = $msg;
                    $body = replace($body, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled', "1");
                }
            }
        }
        }
        echo json_encode(['success' => true, 'status' => $status, 'candidate' => $candidate, 'customer' => $customer]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Resend Mail
if (isset($_POST['type']) && $_POST['type'] == "resend_mail") {
    $count = $_POST['count'];
    $user_type = $_POST['user_type'][$_POST['resend']];
    $order_id = $_POST['order_id'][$_POST['resend']];
    $msg_type = $_POST['msg_type'][$_POST['resend']];
    $email = $_POST['email'][$_POST['resend']];
    $name = $_POST['name'][$_POST['resend']];
    $text = $_POST['text'][$_POST['resend']];
    $subject = $_POST['subject'][$_POST['resend']];
    saveEmail($user_type, $name, $order_id, $msg_type, $text, $email, $subject);
    $emailMsg = sendMail($text, $email, $name, $subject);
    echo json_encode(['success' => true]);
}
//Delete Comment
if (isset($_POST['type']) && $_POST['type'] == "delete_comment") {
    $query = 'DELETE FROM comments WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_POST['id']]);
    echo json_encode(['success' => true]);
}
//Update Customer
if (isset($_POST['type']) && $_POST['type'] == "update_customer") {
$updated_by = 'unknown';
if (isset($_SESSION['admin'])) {
    $updated_by = $_SESSION['admin']->name;
} elseif (isset($_SESSION['staff'])) {
    $updated_by = $_SESSION['staff']->name;
}
$logData = [
    'updated_by' => $updated_by,
    'post_data' => $_POST
];
cuslogMessage(json_encode($logData), 'UPDATE_CUSTOMER');
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $org_no = $_POST['org_no'];
    $parent_customer = !empty($_POST['parent_customer']) ? $_POST['parent_customer'] : null;
    $cus_department = !empty($_POST['cus_department']) ? $_POST['cus_department'] : null;
    $statuses = $_POST['statuses'] ?? array();
    $statusStr = "";
    $services2 = $_POST['services'] ?? array();
    $permissions = $_POST['permissions'] ?? array();
    $send_report = $_POST['send_report'];
    $changed_registration_email = $_POST['changed_registration_email'];
    $remainder_email_template = !empty($_POST['remainder_email_template']) ? $_POST['remainder_email_template'] : '';
    $interview_upload_allowed = !empty($_POST['interview_upload_allowed']) ? $_POST['interview_upload_allowed'] : '';
    $groupid = [];
    $insert_array = null;
    $insert_form = [];
    $email_send = !empty($_POST['send_email']) ? $_POST['send_email'] : 0;
    $ellevio_report = !empty($_POST['ellevio_report']) ? $_POST['ellevio_report'] : 0;
    $send_email_question = !empty($_POST['send_email_question']) ? $_POST['send_email_question'] : 0;
    $select_groups = !empty($_POST['select_group']) ? $_POST['select_group'] : null;
    $combine_bk_and_security = !empty($_POST['combine_bk_and_security']) ? $_POST['combine_bk_and_security'] : 0;
    $combine_status = !empty($_POST['combine_status']) ? $_POST['combine_status'] : 0;
    if (!empty($select_groups)) {
        foreach ($select_groups as $select_group) {
            if (is_numeric($select_group)) {
                $groupid[] = $select_group;
            } else {
                $groupid[] = insert('groups', ['name' => $select_group]);
            }
        }
    }
    if (!empty($groupid)) {
        $groupid = implode(',', $groupid);
    } else {
        $groupid = null;
    }
    $query = 'SELECT * FROM interviews';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $services = $stmt->fetchAll();
    $query = 'SELECT * FROM customer_services WHERE cus_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['id']]);
    $customer_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $allowed_services = array_column($customer_services, 'service_id');
    if (!empty($statuses)) {
        foreach ($statuses as $key => $status) {
            if ($key != count($statuses) - 1) {
                $statusStr = $statusStr . $status . ",";
            } else {
                $statusStr = $statusStr . $status;
            }
        }
    }
    $query = 'UPDATE customers SET name = ?, email = ?, phone = ?, company = ?, org_no = ?, statuses = ?,interview_upload_allowed = ?, send_security_report = ?, groups = ?, reg_email = ?,parent_id = ?,dep_id = ?, remainder_email_template = ?, sent_email = ?, ellevio_report = ?, send_email_question = ?, combine_bk_and_security = ?, combine_status = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $phone, $company, $org_no, $statusStr,$interview_upload_allowed, $send_report, $groupid, $changed_registration_email, $parent_customer, $cus_department, $remainder_email_template, $email_send, $ellevio_report, $send_email_question, $combine_bk_and_security, $combine_status, $_POST['id']]);
    $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = " . $_POST['id']);
    if (!empty($child_customers)) {
        foreach ($child_customers as $row) {
            $update_query = "UPDATE customers SET statuses = :statuses,interview_upload_allowed = :interview_upload_allowed  WHERE id = :id";
            $stmt = $conn->prepare($update_query);
            $stmt->execute([':statuses' => $statusStr,':interview_upload_allowed'=>$interview_upload_allowed, ':id' => $row->id]);
            $combine_update_query= "UPDATE customers SET combine_bk_and_security = :combine_bk_and_security, combine_status = :combine_status WHERE id = :id";
            $stmt = $conn->prepare($combine_update_query);
            $stmt->execute([':combine_bk_and_security' => $combine_bk_and_security, ':combine_status' => $combine_status, ':id' => $row->id]);
        }
    }
    if (!empty($services2)) {
        foreach ($services2 as $service2) {
            $messages_of_ser = findAllByQuery("SELECT * FROM messages WHERE cus_id = {$_POST['id']} AND interview_id = $service2");
            if (empty($messages_of_ser)) {
                $insert_msg_array = null;
                $default_cus_messages = findByQuery("SELECT * FROM messages WHERE cus_id = 0 AND interview_id = 0");
                foreach ($default_cus_messages as $key => $default_cus_message) {
                    if ($key != 'id') {
                        if ($key == 'cus_id') {
                            $insert_msg_array[$key] = $_POST['id'];
                        } else if ($key == 'interview_id') {
                            $insert_msg_array[$key] = $service2;
                        } else {
                            $insert_msg_array[$key] = $default_cus_message;
                        }
                    }
                }
                insert('messages', $insert_msg_array);
            }
        }
    }
    if (!empty($permissions)) {
        $query = 'DELETE FROM user_allowed_permissions WHERE user_id = ? AND user_type = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$_POST['id'], 2]);
    }
    foreach ($permissions as $pers) {
        $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$pers, $_POST['id'], 2]);
    }
    if (!empty($res)) {
        $excludeServices = array_diff(array_column($services, "id"), $services2);
        $includeServices = array_diff($services2, $allowed_services);
        if (!empty($excludeServices)) {
            foreach ($excludeServices as $excludeService) {
                $query = 'DELETE from customer_services WHERE cus_id = ? AND service_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_POST['id'], $excludeService]);
                $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = " . $_POST['id']);
                if (!empty($child_customers)) {
                    foreach ($child_customers as $row) {
                        $query = 'DELETE from customer_services WHERE cus_id = ? AND service_id = ?';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$row->id, $excludeService]);
                    }
                }
            }
        }
        if (!empty($includeServices)) {
            foreach ($includeServices as $includeService) {
                $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_POST['id'], $includeService]);
                $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = " . $_POST['id']);
                if (!empty($child_customers)) {
                    foreach ($child_customers as $row) {
                        $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$row->id, $includeService]);
                    }
                }
            }
        }
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
        $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = " . $_POST['id']);
        if (!empty($child_customers)) {
            foreach ($child_customers as $row) {
                $update_query = "UPDATE customers SET sent_email = :email_send WHERE id = :id";
                $stmt = $conn->prepare($update_query);
                $stmt->execute([':email_send' => $email_send, ':id' => $row->id]);
            }
        }
        if (!empty($parent_customer)) {
            $parent_msg = findAllByQuery("SELECT * FROM messages WHERE cus_id = '$parent_customer'");
            $cur_msg = findByQuery("SELECT * FROM messages WHERE cus_id = " . $_POST['id']);
            $parent_forms = findAllByQuery("SELECT * FROM order_forms WHERE cus_id = '$parent_customer'");
            $cur_forms = findByQuery("SELECT * FROM order_forms WHERE cus_id = " . $_POST['id']);
            $cus_reports = findAllByQuery("SELECT * FROM customer_reports_html WHERE cus_id = " . $_POST['id']);
            if (!empty($cus_reports)) {
                delete('customer_reports_html', 'cus_id', $_POST['id']);
            }
            if (!empty($cur_msg)) {
                delete('messages', 'cus_id', $_POST['id']);
            }
            if (!empty($cur_forms)) {
                delete('order_forms', 'cus_id', $_POST['id']);
            }
            if (!empty($parent_forms)) {
                foreach ($parent_forms as $parent_fo) {
                    foreach ($parent_fo as $f_m => $parent_f) {
                        if ($f_m != 'id') {
                            if ($f_m == 'cus_id') {
                                $insert_form[$f_m] = $_POST['id'];
                            } else {
                                $insert_form[$f_m] = $parent_f;
                            }
                        }
                    }
                    insert('order_forms', $insert_form);
                }
            }
            if (!empty($parent_msg)) {
                foreach ($parent_msg as $parent_ms) {
                    foreach ($parent_ms as $k_m => $parent_m) {
                        if ($k_m != 'id') {
                            if ($k_m == 'cus_id') {
                                $insert_array[$k_m] = $_POST['id'];
                            } else {
                                $insert_array[$k_m] = $parent_m;
                            }
                        }
                    }
                    insert('messages', $insert_array);
                }
            }
            $parent_reports = findAllByQuery("SELECT * FROM customer_reports_html WHERE cus_id = '$parent_customer'");
            $meta_info = [
                'created_by' => $_SESSION['admin']->id,
                'created_on' => date('Y-m-d H:i:s'),
                'user' => 'Admin'
            ];
            $meta_info = json_encode($meta_info);
            if (!empty($parent_reports)) {
                foreach ($parent_reports as $report) {
                    $query = 'INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)';
                    $stmt = $conn->prepare($query);
                    $res = $stmt->execute([$_POST['id'], $report->report_data, $report->interview_id, $report->lang, $meta_info]);
                }
            }
        }
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Update remainder email
if (isset($_POST['type']) && $_POST['type'] == "update_remainder_emails") {
    $remainder_email_template = !empty($_POST['remainder_email_template']) ? $_POST['remainder_email_template'] : '';
    $remainder_email = !empty($_POST['remainder_email']) ? $_POST['remainder_email'] : '';
    $bk_remainder_email_template = !empty($_POST['bk_remainder_email_template']) ? $_POST['bk_remainder_email_template'] : '';
    $bk_remainder_email = !empty($_POST['bk_remainder_email']) ? $_POST['bk_remainder_email'] : '';
    $query = 'UPDATE customers SET remainder_email_template = ?,remainder_email = ?,bk_remainder_email_template = ?,bk_remainder_email = ?  WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$remainder_email_template, $remainder_email, $bk_remainder_email_template, $bk_remainder_email, $_POST['id']]);
    if (!empty($res)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Resend Mail
if (isset($_POST['type']) && $_POST['type'] == "resend_mail_cus") {
    $count = $_POST['count'];
    $user_type = $_POST['user_type'][$_POST['resend']];
    $order_id = $_POST['order_id'][$_POST['resend']];
    $msg_type = $_POST['msg_type'][$_POST['resend']];
    $email = $_POST['email'][$_POST['resend']];
    $name = $_POST['name'][$_POST['resend']];
    $text = $_POST['text'][$_POST['resend']];
    $subject = $_POST['subject'][$_POST['resend']];
    saveEmail($user_type, $name, $order_id, $msg_type, $text, $email, $subject);
    $emailMsg = sendMail($text, $email, $name, $subject);
    echo json_encode(['success' => true]);
}
//Fetch Messages
if (isset($_POST['type']) && $_POST['type'] == "fetch_messages_cus") {
    $service_id = $_POST['sid'];
    $cus_id = $_POST['id'];
    if (isset($_POST['copyid']) && !empty($_POST['copyid'])) {
        $service_id = $_POST['copyid'];
    }
    if (isset($_POST['cusid']) && !empty($_POST['cusid'])) {
        $cus_id = $_POST['cusid'];
    }
    $msgCols = getMsgColsByService($service_id);
    $msgCols = array_column($msgCols, "msg_col");
    $msgCols = implode(",", $msgCols);
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$cus_id, $service_id]);
    $messages = $stmt->fetch();
    if (empty($messages)) {
        $insert_msg_array = null;
        $messages = findByQuery("SELECT * FROM messages WHERE cus_id = 0 AND interview_id = 0");
        foreach ($messages as $key => $default_cus_message) {
            if ($key != 'id') {
                if ($key == 'cus_id') {
                    $insert_msg_array[$key] = $cus_id;
                } else if ($key == 'interview_id') {
                    $insert_msg_array[$key] = $service_id;
                } else {
                    $insert_msg_array[$key] = $default_cus_message;
                }
            }
        }
        insert('messages', $insert_msg_array);
        $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
        $stmt = $conn->prepare($query);
        $stmt->execute([$cus_id, $service_id]);
        $messages = $stmt->fetch();
    }
    if (!empty($messages)) {
        echo json_encode(['success' => true, "messages" => $messages]);
    } else {
        echo json_encode(['error' => true]);
    }
}
//Update Messages
if (isset($_POST['type']) && $_POST['type'] == "update_messages") {
$updated_by = 'unknown';
if (isset($_SESSION['admin'])) {
    $updated_by = $_SESSION['admin']->name;
} elseif (isset($_SESSION['staff'])) {
    $updated_by = $_SESSION['staff']->name;
}
$logData = [
    'updated_by' => $updated_by,
    'post_data' => $_POST
];
cuslogMessage(json_encode($logData), 'UPDATE_MESSAGES');
    $cus_id = $_POST['id'];
    $service_id = $_POST['sid'];
    $query = 'UPDATE messages SET ';
    $params = array();
    foreach ($_POST as $key => $value) {
        if ($key == 'update_msgs' || $key == 'cus_id' || $key == 'sid' || $key == 'customers' || $key == 'services' || $key == 'id' || $key == 'type') {
            continue;
        }
        $query .= $key . ' = ?, ';
        $params[] = $value;
    }
    $query = rtrim($query, ', ') . ' WHERE cus_id = ? AND interview_id = ?';
    $params[] = $cus_id;
    $params[] = $service_id;
    $stmt = $conn->prepare($query);
    $res = $stmt->execute($params);
    $insert = [];
    $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = $cus_id");
    $parent_messages = findAllByQuery("SELECT messages.* FROM messages LEFT JOIN customers ON messages.cus_id = customers.id WHERE cus_id = $cus_id");
    if (!empty($child_customers)) {
        foreach ($child_customers as $child_customer) {
            if (!empty($parent_messages)) {
                delete('messages', 'cus_id', $child_customer->id);
                foreach ($parent_messages as $parent_message) {
                    $parent_message->cus_id = $child_customer->id;
                    foreach ($parent_message as $k => $parent_messag) {
                        if ($k != 'id') {
                            $insert[$k] = $parent_messag;
                        }
                    }
                    insert('messages', $insert);
                }
            }
        }
    }
    if (!empty($res)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
if (isset($_POST['get_service_form']) && !empty($_POST['get_service_form'])) {
    if (isset($_POST['ser_id']) && !empty($_POST['ser_id'])) {
        if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
            if (isset($_POST['copy_customer']) && !empty($_POST['copy_customer'])) {
                $_POST['cus_id'] = $_POST['copy_customer'];
            }
            $form = findByQuery('SELECT * FROM order_forms WHERE cus_id = ' . $_POST['cus_id'] . ' AND service_id = ' . $_POST['ser_id']);
            echo json_encode($form);
        }
    }
}
if (isset($_POST['save_form_builder']) && !empty($_POST['save_form_builder'])) {
    if (isset($_POST['ser_id']) && !empty($_POST['ser_id'])) {
        if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
            $form_build = [];
            $form = findByQuery('SELECT * FROM order_forms WHERE cus_id = ' . $_POST['cus_id'] . ' AND service_id = ' . $_POST['ser_id']);
            if (isset($_POST['new_form_builder']) && !empty($_POST['new_form_builder'])) {
                $form_build['new_form_builder'] = $_POST['new_form_builder'];
            }
            if (isset($_POST['form_builder']) && !empty($_POST['form_builder'])) {
                $form_build['form_builder'] = $_POST['form_builder'];
            }
            $insertarr = array(
                'cus_id' => $_POST['cus_id'],
                'service_id' => $_POST['ser_id'],
                'form' => json_encode($form_build)
            );
            if (!empty($form)) {
                $query = 'UPDATE order_forms SET form = ? WHERE cus_id = ? AND service_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$insertarr['form'], $_POST['cus_id'], $_POST['ser_id']]);
            } else {
                insert('order_forms', $insertarr);
            }
            $child_customers = findAllByQuery('SELECT * FROM customers WHERE parent_id = ' . $_POST['cus_id']);
            if (!empty($child_customers)) {
                foreach ($child_customers as $child_customer) {
                    $chi_form = findByQuery('SELECT * FROM order_forms WHERE cus_id = ' . $child_customer->id . ' AND service_id = ' . $_POST['ser_id']);
                    if (!empty($chi_form)) {
                        $query = 'UPDATE order_forms SET form = ? WHERE cus_id = ? AND service_id = ?';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$insertarr['form'], $child_customer->id, $_POST['ser_id']]);
                    } else {
                        $insertarr['cus_id'] = $child_customer->id;
                        insert('order_forms', $insertarr);
                    }
                }
            }
            echo json_encode(['success' => "Successfully Saved"]);
        }
    }
}
if (isset($_POST['get_cus_service']) && !empty($_POST['get_cus_service'])) {
    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
        $result = findAllByQuery('SELECT * from interviews LEFT JOIN customer_services ON interviews.id = customer_services.service_id WHERE cus_id = ' . $_POST['cus_id'] . ' GROUP BY id');
        echo json_encode($result);
    }
}
if (isset($_POST['filter_candidates']) && !empty($_POST['filter_candidates'])) {
    $result = filter_candidate($_POST['place'], $_POST['candidate'], $_POST['customer'], $_POST['order_created_from'], $_POST['order_created_to'], $_POST['interview_date_from'], $_POST['interview_date_to'], $_POST['status'], $_POST['company'], isset($_POST['where_condition']) && !empty($_POST['where_condition']) ? $_POST['where_condition'] : '');
    echo json_encode($result);
}
if (isset($_POST['get_inte_data']) && !empty($_POST['get_inte_data'])) {
    $query = 'SELECT candidates.*,places.name as place_name,customers.name as cus_name,customers.company as cus_company,staff.name as staff,staff.phone as staff_number FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN staff ON candidates.staff_id=staff.id LEFT JOIN places ON candidates.place = places.id WHERE candidates.id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['get_inte_data']]);
    $pdf_data = $stmt->fetch();
    echo json_encode($pdf_data);
}
if (isset($_POST['inv_sent_analytics'])) {
    if (isset($_POST['order_id']) && !empty($_POST['order_id'])) {
        if (isset($_POST['inv_sent']) && !empty($_POST['inv_sent'])) {
            $query = 'UPDATE candidates SET invoice_sent = ?, invoice_date = ? WHERE order_id = ?';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([1, date('Y-m-d'), $_POST['order_id']]);
        }
        if (isset($_POST['inv_sent']) && empty($_POST['inv_sent'])) {
            $query = 'UPDATE candidates SET invoice_sent = ?, invoice_date = ? WHERE order_id = ?';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([0, null, $_POST['order_id']]);
        }
    }
}
if (isset($_POST['apply_filter'])) {
    $interview_date_from = isset($_POST['interview_date_from']) ? $_POST['interview_date_from'] : '';
    $interview_date_to = isset($_POST['interview_date_to']) ? $_POST['interview_date_to'] : '';
    $customer = isset($_POST['customer']) ? $_POST['customer'] : '';
    $company = isset($_POST['company']) ? $_POST['company'] : '';
    $service_category = isset($_POST['service_category']) ? $_POST['service_category'] : '';
    $order_created_from = isset($_POST['created_from']) ? $_POST['created_from'] : '';
    $order_created_to = isset($_POST['created_to']) ? $_POST['created_to'] : '';
    $booked_order_cus = [];
    // condition for all services
    if ($service_category == 1 || $service_category == 9 || $service_category == 0) {
        // Query
        $query = 'SELECT customers.id,customers.name, customers.company, COUNT(candidates.id) AS order_count 
              FROM customers 
              LEFT JOIN candidates ON customers.id = candidates.cus_id
              LEFT JOIN interviews ON candidates.interview_id = interviews.id';
        // Add conditions
        $query .= ' WHERE expired = 0 AND invoice_sent = 0'; // Initial condition
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
        // Grouping and ordering
        $query .= ' GROUP BY customers.id
                ORDER BY order_count DESC';
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        if (!empty($customer)) {
            $stmt->bindParam(':customer', $customer);
        }
        if (!empty($company)) {
            $stmt->bindParam(':company', $company);
        }
        if (!empty($service_category)) {
            $stmt->bindParam(':service_category', $service_category);
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
            $booked_order_cus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $booked_order_cus = $stmt->errorInfo();
        }
    }
    $bk_booked_order_cus = [];
    // condition for only background check
    if ($service_category == 3 || $service_category == 0) {
        // background checks for
        $query = 'SELECT customers.id,customers.name, customers.company, COUNT(candidates.id) AS order_count 
        FROM customers 
        LEFT JOIN candidates ON customers.id = candidates.cus_id
        LEFT JOIN interviews ON candidates.interview_id = interviews.id';
        // Add conditions
        $query .= ' WHERE expired = 0 AND status IN (18,21,22) AND invoice_sent = 0'; // Initial condition
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
        if (!empty($service_category)) {
            $query .= " AND interviews.service_cat_id = :service_category";
        }
        // Grouping and ordering
        $query .= ' GROUP BY customers.id ORDER BY order_count DESC';
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        if (!empty($customer)) {
            $stmt->bindParam(':customer', $customer);
        }
        if (!empty($service_category)) {
            $stmt->bindParam(':service_category', $service_category);
        }
        if (!empty($company)) {
            $stmt->bindParam(':company', $company);
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
            $bk_booked_order_cus = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $bk_booked_order_cus = $stmt->errorInfo();
        }
    }
    $bk_booked_order_cus = array_merge($bk_booked_order_cus, $booked_order_cus);
    $booked_order_cus = [];
    foreach ($bk_booked_order_cus as $bk_row) {
        $id = $bk_row['id'];
        if (isset($booked_order_cus[$id])) {
            $booked_order_cus[$id]['order_count'] += $bk_row['order_count'];
        } else {
            $booked_order_cus[$id] = $bk_row;
        }
    }
    $booked_order_cus = array_values($booked_order_cus);
    $booked_order_comp = [];
    if ($service_category == 1 || $service_category == 9 || $service_category == 0) {
        // Query
        $query = 'SELECT customers.company, COUNT(candidates.id) AS order_count 
        FROM customers 
        LEFT JOIN candidates ON customers.id = candidates.cus_id
        LEFT JOIN interviews ON candidates.interview_id = interviews.id';
        // Add conditions
        $query .= ' WHERE expired = 0 AND invoice_sent = 0'; // Initial condition
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
        // Grouping and ordering
        $query .= ' GROUP BY customers.company
          ORDER BY order_count DESC';
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        if (!empty($customer)) {
            $stmt->bindParam(':customer', $customer);
        }
        if (!empty($service_category)) {
            $stmt->bindParam(':service_category', $service_category);
        }
        if (!empty($company)) {
            $stmt->bindParam(':company', $company);
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
            $booked_order_comp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $booked_order_comp = $stmt->errorInfo();
        }
    }
    $bk_booked_order_comp = [];
    if ($service_category == 3 || $service_category == 0) {
        // background checks for company
        $query = 'SELECT customers.company, COUNT(candidates.id) AS order_count 
        FROM customers 
        LEFT JOIN candidates ON customers.id = candidates.cus_id
        LEFT JOIN interviews ON candidates.interview_id = interviews.id';
        // Add conditions
        $query .= ' WHERE expired = 0 AND status IN (18,21,22) AND invoice_sent = 0'; // Initial condition
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
        if (!empty($service_category)) {
            $query .= " AND interviews.service_cat_id = :service_category";
        }
        // Grouping and ordering
        $query .= ' GROUP BY customers.company
      ORDER BY order_count DESC';
        // Prepare and execute statement
        $stmt = $conn->prepare($query);
        if (!empty($customer)) {
            $stmt->bindParam(':customer', $customer);
        }
        if (!empty($service_category)) {
            $stmt->bindParam(':service_category', $service_category);
        }
        if (!empty($company)) {
            $stmt->bindParam(':company', $company);
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
            $bk_booked_order_comp = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $bk_booked_order_comp = $stmt->errorInfo();
        }
    }
    $bk_booked_order_comp = array_merge($bk_booked_order_comp, $booked_order_comp);
    $booked_order_comp = [];
    foreach ($bk_booked_order_comp as $bk_row) {
        $normalizedCompany = strtolower(trim($bk_row['company']));
        if (isset($booked_order_comp[$normalizedCompany])) {
            $booked_order_comp[$normalizedCompany]['order_count'] += $bk_row['order_count'];
        } else {
            $booked_order_comp[$normalizedCompany] = $bk_row;
        }
    }
    $booked_order_comp = array_values($booked_order_comp);
    // Query
    $query = 'SELECT * FROM exported_company WHERE 0 = 0';
    if (!empty($interview_date_from)) {
        $query .= " AND interview_date_from = :interview_date_from";
    }
    if (!empty($interview_date_to)) {
        $query .= " AND interview_date_to = :interview_date_to";
    }
    $stmt = $conn->prepare($query);
    if (!empty($interview_date_from)) {
        $stmt->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));
    }
    if (!empty($interview_date_to)) {
        $stmt->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));
    }
    $res = $stmt->execute();
    $exported_company = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $result = [];
    if ($service_category == 1 || $service_category == 9 || $service_category == 0) {
        $result = filter_candidate('', '', $customer, $order_created_from, $order_created_to, $interview_date_from, $interview_date_to, '', $company, isset($_POST['where_condition']) && !empty($_POST['where_condition']) ? $_POST['where_condition'] : '', $service_category);
    }
    $data = [];
    if ($service_category == 3 || $service_category == 0) {
        $query = "SELECT candidates.*, statuses.status as status_name, statuses.color as status_color, staff.name as staff_name, customers.id as customer_id, customers.name as customer_name, customers.company as customer_company, places.name as place_name, interviews.title as interview_title,interviews.service_cat_id as service_category
          FROM candidates 
          LEFT JOIN statuses ON candidates.status = statuses.id 
          LEFT JOIN staff ON candidates.staff_id = staff.id 
          LEFT JOIN customers ON candidates.cus_id = customers.id 
          LEFT JOIN places ON candidates.place = places.id 
          LEFT JOIN interviews ON candidates.interview_id = interviews.id 
          WHERE candidates.expired = 0 AND candidates.invoice_sent = 0 AND candidates.status = 18";
        $bindings = [];
        if (!empty($customer)) {
            $query .= " AND candidates.cus_id = :customer";
            $bindings[':customer'] = $customer;
        }
        if (!empty($order_created_from)) {
            $query .= " AND candidates.created >= :order_created_from";
            $bindings[':order_created_from'] = date('Y-m-d', strtotime($order_created_from));
        }
        if (!empty($order_created_to)) {
            $query .= " AND candidates.created <= :order_created_to";
            $bindings[':order_created_to'] = date('Y-m-d', strtotime($order_created_to));
        }
        if (!empty($interview_date_from)) {
            $query .= " AND candidates.delivery_date >= :interview_date_from";
            $bindings[':interview_date_from'] = date('Y-m-d', strtotime($interview_date_from));
        }
        if (!empty($interview_date_to)) {
            $query .= " AND candidates.delivery_date <= :interview_date_to";
            $bindings[':interview_date_to'] = date('Y-m-d', strtotime($interview_date_to));
        }
        $query .= " ORDER BY CASE
    WHEN delivery_date IS NULL OR delivery_date = '' THEN 1  -- Places empty interview dates at the end
    ELSE 0 END, delivery_date ASC";
        $stmt = $conn->prepare($query);
        foreach ($bindings as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $res = $stmt->execute();
        if ($res) {
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $errorInfo = $stmt->errorInfo();
        }
    }
    $result = array_merge($result, $data);
    if (!empty($result)) {
        foreach ($result as $k => $row) {
            $result[$k]['order_history'] = findAllByQuery('SELECT * FROM history WHERE order_id = ' . $row['id']);
        }
    }
    echo json_encode(['uninvoiced_orders' => $result, 'customers_with_orders' => $booked_order_cus, 'comp_with_orders' => $booked_order_comp, 'exported_company' => $exported_company]);
}
if (isset($_POST['order_history']) && !empty($_POST['order_history'])) {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $result = findAllByQuery("SELECT * FROM history WHERE order_id = {$_POST['id']}");
        echo json_encode(['result' => $result]);
    }
}
if (isset($_POST['company_exported']) && !empty($_POST['company_exported'])) {
    $interview_date_from = isset($_POST['interview_date_from']) ? $_POST['interview_date_from'] : '';
    $interview_date_to = isset($_POST['interview_date_to']) ? $_POST['interview_date_to'] : '';
    $company = isset($_POST['company']) ? $_POST['company'] : '';
    $exported_by = '';
    if (isset($_SESSION['admin']->id) && !empty($_SESSION['admin']->id)) {
        $exported_by = $_SESSION['admin']->id;
    } else {
        $exported_by = $_SESSION['staff']->id;
    }
    insert('exported_company', ['exported_company' => $company, 'interview_date_from' => $interview_date_from, 'interview_date_to' => $interview_date_to, 'exported_on' => date('Y-m-d'), 'exported_by' => $exported_by]);
}
if (isset($_POST['getSpecificFromCustomer']) && !empty($_POST['getSpecificFromCustomer'])) {
    $cus_id = $_POST['id'];
    // Fetch customer services based on cus_id
    $customer_services = findAllByQuery('SELECT * FROM `customer_services` WHERE cus_id = ' . $cus_id);
    // Extract service_id values
    $service_ids = array_column($customer_services, 'service_id');
    // Prepare placeholders for the IN clause
    $placeholders = implode(',', array_fill(0, count($service_ids), '?'));
    // Fetch data from interviews table using the service_ids
    $query = 'SELECT id,title FROM `interviews` WHERE id IN (' . $placeholders . ')';
    $stmt = $conn->prepare($query);
    $stmt->execute($service_ids);
    $interviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
    // Return the interviews data as JSON
    echo json_encode(['result' => $interviews]);
    exit;
}
if (isset($_POST['getStatusServices']) && !empty($_POST['getStatusServices'])) {
    $service_id = $_POST['service_id'];
    // Fetch customer services based on cus_id
    $customer_services = findAllByQuery('SELECT `msg_col` FROM `status_services` WHERE service_id = ' . $service_id);
    // Return the interviews data as JSON
    echo json_encode(['result' => $customer_services]);
    exit;
}
if (isset($_POST['type']) && $_POST['type'] == "send_email") {
    $query = 'SELECT * FROM candidates WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_POST['id']]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch as associative arrays
    $query = 'INSERT INTO emails (user_type, user_name, order_id, msg_type, text, subject, email, created) VALUES (?,?,?,?,?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([
        'Candidate',
        $comments[0]['name'],
        $comments[0]['order_id'],
        'Candidate Message',
        '',
        '',
        $comments[0]['email'],
        date('Y-m-d H:i:s')
    ]);
    if ($res) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
if (isset($_POST['type']) && $_POST['type'] == "update_service_cost") {
    $service_id = $_POST['service_id'];
    $service_cost = $_POST['service_cost'];
    $customer_id = $_POST['customer_id'];
    $query = 'UPDATE customer_services SET service_cost = ? WHERE cus_id = ? AND service_id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$service_cost, $customer_id, $service_id]);
    if ($res) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => true]);
    }
}
if (isset($_POST['type']) && $_POST['type'] == "get_service_cost") {
    $service_id = $_POST['service_id'];
    $customer_id = $_POST['customer_id'];
    $query = 'SELECT service_cost FROM `customer_services` WHERE cus_id = ? AND service_id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$customer_id, $service_id]);
    $service_cost = $stmt->fetchAll();
    if (!empty($service_cost)) {
        echo json_encode(['success' => true, "service_cost" => $service_cost]);
    } else {
        echo json_encode(['error' => true]);
    }
}
if (isset($_POST['read_by_admin']) && !empty($_POST['read_by_admin'])) {
    $order_id = $_POST['id'];
    $adminId = $_SESSION['admin']->id;
    $stmt = $conn->prepare("SELECT id, read_by_admin FROM comments WHERE order_id = ? AND author_id != ?");
    $stmt->execute([$order_id, $adminId]);
    $comments = $stmt->fetchAll();
    foreach ($comments as $comment) {
        $readBy = array_filter(array_map('trim', explode(',', $comment->read_by_admin ?? '')));
        if (!in_array($adminId, $readBy)) {
            $readBy[] = $adminId;
            $updatedReadBy = implode(',', $readBy);
            $updateStmt = $conn->prepare("UPDATE comments SET read_by_admin = ? WHERE id = ?");
            $updateStmt->execute([$updatedReadBy, $comment->id]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}
if (isset($_POST['read_by_staff']) && !empty($_POST['read_by_staff'])) {
    $order_id = $_POST['id'];
    $staffId = $_SESSION['staff']->id;
    $stmt = $conn->prepare("SELECT id, read_by_staff FROM comments WHERE order_id = ? AND author_id != ?");
    $stmt->execute([$order_id, $staffId]);
    $comments = $stmt->fetchAll();
    foreach ($comments as $comment) {
        $readBy = array_filter(array_map('trim', explode(',', $comment->read_by_staff ?? '')));
        if (!in_array($staffId, $readBy)) {
            $readBy[] = $staffId;
            $updatedReadBy = implode(',', $readBy);
            $updateStmt = $conn->prepare("UPDATE comments SET read_by_staff = ? WHERE id = ?");
            $updateStmt->execute([$updatedReadBy, $comment->id]);
        }
    }
    echo json_encode(['success' => true]);
    exit;
}
if (isset($_POST['type']) && $_POST['type'] == 'fetch_service_cus') {
    $cus_id = $_POST['id'];
    if (!empty($cus_id)) {
        $stmt = $conn->prepare("SELECT * FROM interviews 
        LEFT JOIN customer_services ON interviews.id = customer_services.service_id 
        WHERE cus_id = ? 
        GROUP BY interviews.id");
        $stmt->execute([$cus_id]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'services' => $services]);
        exit;
    }
}
// Get services by category
if (isset($_POST['type']) && $_POST['type'] == 'get_services_by_category') {
    $category_id = $_POST['category_id'];
    if (!empty($category_id)) {
        $stmt = $conn->prepare("SELECT id, title FROM interviews WHERE service_cat_id = ? ORDER BY title ASC");
        $stmt->execute([$category_id]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'services' => $services]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Category ID is required']);
        exit;
    }
}
// Get statuses by type
if (isset($_POST['type']) && $_POST['type'] == 'get_statuses_by_type') {
    $status_type = $_POST['status_type'];
    if (!empty($status_type)) {
        $stmt = $conn->prepare("SELECT id, status FROM statuses WHERE status_type = ? ORDER BY status ASC");
        $stmt->execute([$status_type]);
        $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'statuses' => $statuses]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Status type is required']);
        exit;
    }
}
// DataTable server-side processing for candidates
if (isset($_POST['action']) && $_POST['action'] == 'get_candidates_data') {
    // Always return JSON for this endpoint
    header('Content-Type: application/json');
    if (function_exists('ob_get_length') && ob_get_length()) {
        ob_clean();
    }
    // Check database connection
    if (!isset($conn) || $conn === null) {
        echo json_encode(['error' => 'Database connection not available']);
        exit;
    }
    // Get DataTable parameters
    $draw = intval($_POST['draw']);
    $start = intval($_POST['start']);
    $length = intval($_POST['length']);
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumn = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'asc';
    // Get filter parameters
    $service = $_POST['service'] ?? '';
    $status = $_POST['status'] ?? '';
    // Build base query
    $baseQuery = "SELECT c.*, 
                         cu.name as customer_name, 
                         cu.company as customer_company,
                         s.name as staff_name,
                         p.name as place_name,
                         i.title as interview_title,
                         st.status as status_name,
                         st.color as status_color
                  FROM candidates c
                  LEFT JOIN customers cu ON c.cus_id = cu.id
                  LEFT JOIN staff s ON c.staff_id = s.id
                  LEFT JOIN places p ON c.place = p.id
                  LEFT JOIN interviews i ON c.interview_id = i.id
                  LEFT JOIN statuses st ON c.status = st.id
                  WHERE c.expired = 0";
    $countQuery = "SELECT COUNT(*) as total FROM candidates c
                   LEFT JOIN customers cu ON c.cus_id = cu.id
                   WHERE c.expired = 0";
    $params = [];
    $paramTypes = [];
    // Add service filter
    if (!empty($service) && $service != 'all') {
        $baseQuery .= " AND c.interview_id IN (SELECT id FROM interviews WHERE service_cat_id = ?)";
        $countQuery .= " AND c.interview_id IN (SELECT id FROM interviews WHERE service_cat_id = ?)";
        $params[] = $service;
        $paramTypes[] = PDO::PARAM_INT;
    }
    // Add status filter
    if (!empty($status)) {
        $baseQuery .= " AND c.status = ?";
        $countQuery .= " AND c.status = ?";
        $params[] = $status;
        $paramTypes[] = PDO::PARAM_INT;
    }
    // Add search filter
    if (!empty($searchValue)) {
        $searchCondition = " AND (c.name LIKE ? OR c.surname LIKE ? OR CONCAT(c.name, ' ', c.surname) LIKE ? OR c.order_id LIKE ? OR cu.name LIKE ? OR cu.company LIKE ?)";
        $baseQuery .= $searchCondition;
        $countQuery .= $searchCondition;
        $searchParam = "%{$searchValue}%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchParam;
            $paramTypes[] = PDO::PARAM_STR;
        }
    }
    // Get total count
    try {
        $stmt = $conn->prepare($countQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    } catch (Exception $e) {
        error_log("Count Query Error: " . $e->getMessage());
        echo json_encode(['error' => 'Count query failed: ' . $e->getMessage()]);
        exit;
    }
    // Add ordering (must match thead columns count/order)
    $columns = ['c.id', 'c.id', 'c.id', 'c.id', 'c.order_id', 'p.name', 'c.vasc_id', 'c.name', 'c.security', 'cu.name', 'cu.company', 's.name', 'c.reported_to_sm', 'st.status', 'c.invoice_sent', 'c.booked', 'c.economy', 'c.criminal_record', 'c.social', 'c.invoice_date', 'c.background_check_date', 'c.created', 'c.archive_date', 'c.delivery_date', 'i.title'];
    $orderColumnName = $columns[$orderColumn] ?? 'c.id';
    $baseQuery .= " ORDER BY {$orderColumnName} {$orderDir}";
    // Add pagination
    $baseQuery .= " LIMIT {$start}, {$length}";
    // Execute main query
    try {
        $stmt = $conn->prepare($baseQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("SQL Error: " . $e->getMessage());
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit;
    }
    // Format data for DataTable
    $data = [];
    foreach ($candidates as $index => $candidate) {
        $row = [
            '', // Empty for expand button
            '<input class="form-check-input d-check delete-candidate" id="checkbox-' . $candidate['id'] . '" name="delete[]" value="' . $candidate['id'] . '" type="checkbox">
            <label class="form-check-label" for="checkbox-' . $candidate['id'] . '"></label>',
            '<div class="dropdown">
                <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton' . $candidate['id'] . '" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-gear"></i>
                </button>
                <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $candidate['id'] . '" style="top: 151px; left: 479px; position: fixed;">
                    <li class="mb-1"><a href="update-candidate.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen text-black f-14 me-2"></i>Edit</a></li>
                    <li class="mb-1"><a href="invoice.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-eye f-14 text-black me-2"></i>View</a></li>
                    <li class="mb-1"><a href="change-staff.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-people f-14 text-black me-2"></i>Change Staff</a></li>
                    <li class="mb-1"><a href="update-status.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen f-14 text-black me-2"></i>Change Status</a></li>
                    <li class="mb-1"><a href="comment.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-book f-14 text-black me-2"></i>Comment</a></li>
                    <li class="mb-1"><a href="report.php?id=' . $candidate['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-list f-14 text-black me-2"></i>Generate Report</a></li>
                </ul>
            </div>',
            $start + $index + 1,
            $candidate['order_id'],
            !empty($candidate['place_name']) ? $candidate['place_name'] : 'Video',
            !empty($candidate['vasc_id']) ? $candidate['vasc_id'] : 'Null',
            '<a href="invoice.php?sno=' . ($start + $index + 1) . '&id=' . $candidate['id'] . '" class="no-decoration text-black open-candidate" data-sno="' . ($start + $index + 1) . '" data-id="' . $candidate['id'] . '" data-status="">' . $candidate['name'] . ' ' . $candidate['surname'] . '</a>',
            $candidate['security'],
            '<a class="no-decoration text-black" href="update-customer.php?id=' . $candidate['cus_id'] . '">' . ($candidate['customer_name'] ?? '') . '</a>',
            '<a class="no-decoration text-black" href="update-customer.php?id=' . $candidate['cus_id'] . '">' . ($candidate['customer_company'] ?? 'Null') . '</a>',
            $candidate['staff_name'] ?? 'Not Assigned',
            '<input class="form-check-input reported_sm" data-rid="' . $candidate['id'] . '" id="reported-' . $candidate['id'] . '" ' . ($candidate['reported_to_sm'] == 1 ? 'checked' : '') . ' name="reported_to_sm" value="' . $candidate['id'] . '" type="checkbox" onclick="check_reported_by(this)">
            <label class="form-check-label" for="reported-' . $candidate['id'] . '"></label>',
            '<div class="status-approved" style="background-color: ' . $candidate['status_color'] . '">' . $candidate['status_name'] . '</div>',
            '<input class="form-check-input invoice_sent" data-id="' . $candidate['id'] . '" id="invoice-' . $candidate['id'] . '" ' . ($candidate['invoice_sent'] == 1 ? 'checked' : '') . ' name="invoice_sent" value="' . $candidate['id'] . '" type="checkbox">
            <label class="form-check-label" for="invoice-' . $candidate['id'] . '"></label>',
            $candidate['booked'] ?? 'Null',
            '<div class="d-flex justify-content-center">
                <label class="me-2">
                    <input class="economy-radio" ' . ($candidate['economy'] == 0 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '">
                    <span class="custom-economy-radio uncheck_economy" data-id="' . $candidate['id'] . '"></span>
                </label>
                <label>
                    <input class="economy2-radio" ' . ($candidate['economy'] == 1 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '">
                    <span class="custom-economy2-radio check_economy" data-id="' . $candidate['id'] . '"></span>
                </label>
            </div>',
            '<div class="d-flex justify-content-center">
                <label class="me-2">
                    <input class="economy-radio" ' . ($candidate['criminal_record'] == 0 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '-criminal">
                    <span class="custom-economy-radio uncheck_criminal" data-id="' . $candidate['id'] . '"></span>
                </label>
                <label>
                    <input class="economy2-radio" ' . ($candidate['criminal_record'] == 1 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '-criminal">
                    <span class="custom-economy2-radio check_criminal" data-id="' . $candidate['id'] . '"></span>
                </label>
            </div>',
            '<div class="d-flex justify-content-center">
                <label class="me-2">
                    <input class="economy-radio" ' . ($candidate['social'] == 0 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '-social">
                    <span class="custom-economy-radio uncheck_social" data-id="' . $candidate['id'] . '"></span>
                </label>
                <label>
                    <input class="economy2-radio" ' . ($candidate['social'] == 1 ? 'checked' : '') . ' type="radio" name="' . $candidate['order_id'] . '-social">
                    <span class="custom-economy2-radio check_social" data-id="' . $candidate['id'] . '"></span>
                </label>
            </div>',
            $candidate['invoice_date'] ?? 'Null',
            $candidate['background_check_date'] ?? 'Null',
            $candidate['created'],
            ($candidate['archive_date'] ?? 'Null'),
            $candidate['delivery_date'] ?? 'Null',
            $candidate['interview_title']
        ];
        $data[] = $row;
    }
    // Return DataTable response
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    exit;
}

// Staff table - server-side DataTables endpoint
if (isset($_POST['action']) && $_POST['action'] == 'get_staff_data') {
    header('Content-Type: application/json');
    if (function_exists('ob_get_length') && ob_get_length()) {
        ob_clean();
    }

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumn = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'asc';

    // Base and count queries
    $baseQuery = "SELECT s.*, 
                         COALESCE(COUNT(c.staff_id), 0) AS total_orders,
                         uc.title AS staff_category_title
                  FROM staff s
                  LEFT JOIN candidates c ON s.id = c.staff_id
                  LEFT JOIN user_category uc ON s.category = uc.id
                  WHERE 1=1";
    $countQuery = "SELECT COUNT(*) AS total
                   FROM staff s
                   LEFT JOIN user_category uc ON s.category = uc.id
                   WHERE 1=1";

    $params = [];
    $paramTypes = [];

    if (!empty($searchValue)) {
        $searchCondition = " AND (s.name LIKE ? OR s.email LIKE ? OR s.phone LIKE ? OR uc.title LIKE ?)";
        $baseQuery .= $searchCondition;
        $countQuery .= $searchCondition;
        $searchParam = "%{$searchValue}%";
        for ($i = 0; $i < 4; $i++) {
            $params[] = $searchParam;
            $paramTypes[] = PDO::PARAM_STR;
        }
    }

    // Grouping for total_orders aggregation
    $baseQuery .= " GROUP BY s.id";

    // Ordering map: must align with staff.php thead
    $columns = [
        's.id',                 // 0 checkbox (not sortable effectively)
        's.id',                 // 1 action
        's.name',               // 2 name
        's.email',              // 3 email
        's.phone',              // 4 phone
        'total_orders',         // 5 no. of orders
        's.can_upload_report',  // 6 report upload
        'uc.title'              // 7 category
    ];
    $orderColumnName = $columns[$orderColumn] ?? 's.id';
    // Enforce safe direction
    $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';
    $baseQuery .= " ORDER BY {$orderColumnName} {$orderDir}";

    // Pagination
    $baseQuery .= " LIMIT {$start}, {$length}";

    // Execute count
    try {
        $stmt = $conn->prepare($countQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Count query failed: ' . $e->getMessage()]);
        exit;
    }

    // Execute main
    try {
        $stmt = $conn->prepare($baseQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit;
    }

    // Build DataTables data array
    $data = [];
    foreach ($rows as $st) {
        $isStaffAssigned = false; // not needed to compute for each row (expensive)
        $checkboxHtml = '<input class="form-check-input d-check delete-candidate" id="checkbox-' . $st['id'] . '" name="delete[]" value="' . $st['id'] . '" type="checkbox">'
                      . '<label class="form-check-label" for="checkbox-' . $st['id'] . '"></label>';

        $actionHtml = '<div class="dropdown">'
                    . '  <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton' . $st['id'] . '" data-bs-toggle="dropdown" aria-expanded="false">'
                    . '    <i class="bi bi-gear"></i>'
                    . '  </button>'
                    . '  <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $st['id'] . '">'
                    . '    <li class="mb-1"><a href="update-staff.php?id=' . $st['id'] . '" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>Edit</a></li>'
                    . '    <li class="mb-1"><a href="staff.php?delete=' . $st['id'] . '" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>Delete</a></li>'
                    . '  </ul>'
                    . '</div>';

        $nameHtml = '<a class="no-decoration text-black" href="staff-candidates.php?id=' . $st['id'] . '">' . htmlspecialchars($st['name']) . '</a>';
        $email = htmlspecialchars($st['email'] ?? '');
        $phone = htmlspecialchars($st['phone'] ?? '');
        $totalOrders = (int)($st['total_orders'] ?? 0);
        $reportCheckbox = '<input class="form-check-input" id="report_checkbox-' . $st['id'] . '" value="' . $st['id'] . '" ' . (!empty($st['can_upload_report']) ? 'checked' : '') . ' type="checkbox" onclick="change_report_column(this)">'
                        . '<label class="form-check-label" for="report_checkbox-' . $st['id'] . '"></label>';
        $category = htmlspecialchars($st['staff_category_title'] ?? '');

        $data[] = [
            $checkboxHtml,
            $actionHtml,
            $nameHtml,
            $email,
            $phone,
            $totalOrders,
            $reportCheckbox,
            $category
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    exit;
}

// Customers table - server-side DataTables endpoint
if (isset($_POST['action']) && $_POST['action'] == 'get_customers_data') {
    header('Content-Type: application/json');
    if (function_exists('ob_get_length') && ob_get_length()) {
        ob_clean();
    }

    $draw = intval($_POST['draw'] ?? 0);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 10);
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumn = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'asc';

    $baseQuery = "SELECT cu.*, parent.name AS parent_customer
                  FROM customers cu
                  LEFT JOIN customers parent ON cu.parent_id = parent.id
                  WHERE 1=1";
    $countQuery = "SELECT COUNT(*) AS total
                   FROM customers cu
                   LEFT JOIN customers parent ON cu.parent_id = parent.id
                   WHERE 1=1";

    $params = [];
    $paramTypes = [];

    if (!empty($searchValue)) {
        $searchCondition = " AND (cu.name LIKE ? OR cu.email LIKE ? OR cu.phone LIKE ? OR cu.company LIKE ? OR cu.org_no LIKE ? OR parent.name LIKE ?)";
        $baseQuery .= $searchCondition;
        $countQuery .= $searchCondition;
        $searchParam = "%{$searchValue}%";
        for ($i = 0; $i < 6; $i++) {
            $params[] = $searchParam;
            $paramTypes[] = PDO::PARAM_STR;
        }
    }

    $columns = [
        'cu.id',         // 0 checkbox
        'cu.id',         // 1 action
        'cu.name',       // 2 name
        'cu.email',      // 3 email
        'cu.phone',      // 4 phone
        'cu.interview_template',         // 5 In.Temp
        'cu.interview_upload_allowed',   // 6 In.Rep
        'cu.company',    // 7 company
        'cu.org_no',     // 8 organization number
        'parent.name',   // 9 parent customer
        'cu.remainder_email',           // 10 IRE
        'cu.bk_remainder_email'         // 11 BCRE
    ];
    $orderColumnName = $columns[$orderColumn] ?? 'cu.id';
    $orderDir = strtolower($orderDir) === 'desc' ? 'desc' : 'asc';
    $baseQuery .= " ORDER BY {$orderColumnName} {$orderDir}";

    $baseQuery .= " LIMIT {$start}, {$length}";

    try {
        $stmt = $conn->prepare($countQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    } catch (Exception $e) {
        echo json_encode(['error' => 'Count query failed: ' . $e->getMessage()]);
        exit;
    }

    try {
        $stmt = $conn->prepare($baseQuery);
        for ($i = 0; $i < count($params); $i++) {
            $stmt->bindValue($i + 1, $params[$i], $paramTypes[$i]);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database query failed: ' . $e->getMessage()]);
        exit;
    }

    $data = [];
    foreach ($rows as $customer) {
        $checkboxHtml = '<input class="form-check-input d-check delete-candidate" id="checkbox-' . $customer['id'] . '" name="delete[]" value="' . $customer['id'] . '" type="checkbox">'
                      . '<label class="form-check-label" for="checkbox-' . $customer['id'] . '"></label>';

        $actionHtml = '<div class="dropdown">'
                    . '  <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton' . $customer['id'] . '" data-bs-toggle="dropdown" aria-expanded="false">'
                    . '    <i class="bi bi-gear"></i>'
                    . '  </button>'
                    . '  <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $customer['id'] . '">'
                    . '    <li class="mb-1"><a href="update-customer.php?id=' . $customer['id'] . '" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>Edit</a></li>'
                    . '    <li class="mb-1"><a href="customers.php?delete=' . $customer['id'] . '" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>Delete</a></li>'
                    . '    <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 delay_set_id text-black" data-bs-toggle="modal" data-bs-target="#delay_date"><i class="bi bi-info text-black f-14 me-2"></i>Duration</a></li>'
                    . '  </ul>'
                    . '</div>';

        $nameHtml = '<a class="no-decoration text-black open-customer" data-id="' . $customer['id'] . '" data-days="' . ($customer['report_delete_duration'] ?? '') . '" href="update-customer.php?id=' . $customer['id'] . '">' . htmlspecialchars($customer['name']) . '</a>';
        $email = htmlspecialchars($customer['email'] ?? '');
        $phone = htmlspecialchars($customer['phone'] ?? '');
        $inTemp = '<input class="form-check-input" id="interview_template-' . $customer['id'] . '" ' . ((int)$customer['interview_template'] === 1 ? 'checked' : '') . ' value="' . $customer['id'] . '" type="checkbox" onclick="check_interview_template(this)">'
                . '<label class="form-check-label" for="interview_template-' . $customer['id'] . '"></label>';
        $inRep = '<input class="form-check-input" data-cuscheckbox="' . $customer['id'] . '" id="interview_upload_allowed-' . $customer['id'] . '" ' . ((int)$customer['interview_upload_allowed'] === 1 ? 'checked' : '') . ' value="' . $customer['id'] . '" type="checkbox" onclick="check_interview_upload_allowed(this)" data-parent="' . ($customer['parent_id'] ?? '') . '">'
               . '<label class="form-check-label" for="interview_upload_allowed-' . $customer['id'] . '"></label>';
        $company = htmlspecialchars($customer['company'] ?? '');
        $orgNo = htmlspecialchars($customer['org_no'] ?? '');
        $parentCustomer = htmlspecialchars($customer['parent_customer'] ?? '');
        $ire = '<input class="form-check-input email_remainder" id="email_remainder_template-' . $customer['id'] . '" ' . ((int)$customer['remainder_email'] === 1 ? 'checked' : '') . ' value="' . $customer['id'] . '" type="checkbox" onclick="check_remainder_email_template(this); openOrCloseEmailReminderModal(this)" data-id="' . $customer['id'] . '">'
             . '<label class="form-check-label" for="email_remainder_template-' . $customer['id'] . '"></label>';
        $bcre = '<input class="form-check-input email_remainder" id="bk_email_remainder_template-' . $customer['id'] . '" ' . ((int)$customer['bk_remainder_email'] === 1 ? 'checked' : '') . ' value="' . $customer['id'] . '" type="checkbox" onclick="check_bk_remainder_email_template(this); openOrCloseEmailBKReminderModal(this)" data-id="' . $customer['id'] . '">'
              . '<label class="form-check-label" for="bk_email_remainder_template-' . $customer['id'] . '"></label>';

        $data[] = [
            $checkboxHtml,
            $actionHtml,
            $nameHtml,
            $email,
            $phone,
            $inTemp,
            $inRep,
            $company,
            $orgNo,
            $parentCustomer,
            $ire,
            $bcre
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
    exit;
}
// History DataTable AJAX endpoint

if (isset($_POST['action']) && $_POST['action'] == 'get_history_data') {

    // Authentication check

    if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {

        http_response_code(401);

        echo json_encode(['error' => 'Unauthorized']);

        exit;

    }



    header('Content-Type: application/json');

    ob_clean();



    try {

        // Get DataTable parameters

        $draw = intval($_POST['draw']);

        $start = intval($_POST['start']);

        $length = intval($_POST['length']);

        $searchValue = $_POST['search']['value'] ?? '';

        $orderColumn = intval($_POST['order'][0]['column'] ?? 0);

        $orderDir = $_POST['order'][0]['dir'] ?? 'desc';

        

        // Get custom filters

        $customerId = $_POST['customer_id'] ?? '';

        $status = $_POST['status'] ?? '';



        // Build base query

        $baseQuery = 'SELECT oh.*, oh.id AS oid, i.title as interview_title, s.status, s.color as status_color, c.company, c.reference

                      FROM order_history oh

                      LEFT JOIN interviews i ON oh.interview_id = i.id

                      LEFT JOIN candidates ca ON oh.order_id = ca.order_id

                      LEFT JOIN customers c ON ca.cus_id = c.id

                      LEFT JOIN statuses s ON oh.status = s.id';



        $countQuery = 'SELECT COUNT(*) as total FROM order_history oh

                       LEFT JOIN candidates ca ON oh.order_id = ca.order_id

                       LEFT JOIN customers c ON ca.cus_id = c.id';



        $whereConditions = [];

        $params = [];



        // Apply customer filter

        if (!empty($customerId)) {

            $whereConditions[] = 'ca.cus_id = ?';

            $params[] = $customerId;

        }



        // Apply status filter

        if (!empty($status)) {

            $whereConditions[] = 'oh.status = ?';

            $params[] = $status;

        }



        // Apply search filter

        if (!empty($searchValue)) {

            $whereConditions[] = '(oh.order_id LIKE ? OR i.title LIKE ? OR c.company LIKE ? OR c.reference LIKE ?)';

            $searchParam = '%' . $searchValue . '%';

            $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);

        }



        // Add WHERE clause if conditions exist

        if (!empty($whereConditions)) {

            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);

            $baseQuery .= $whereClause;

            $countQuery .= $whereClause;

        }



        // Get total count

        $stmt = $conn->prepare($countQuery);

        $stmt->execute($params);

        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



        // Define columns for ordering

        $columns = [

            0 => 'oh.order_id',

            1 => 'i.title',

            2 => 'c.company',

            3 => 'oh.invoice_date',

            4 => 'oh.created',

            5 => 's.status',

            6 => 'oh.status_date'

        ];



        // Add ORDER BY clause

        if (isset($columns[$orderColumn])) {

            $baseQuery .= ' ORDER BY ' . $columns[$orderColumn] . ' ' . strtoupper($orderDir);

        } else {

            $baseQuery .= ' ORDER BY oh.created DESC';

        }



        // Add LIMIT for pagination

        $baseQuery .= ' LIMIT ' . $start . ', ' . $length;



        // Execute main query

        $stmt = $conn->prepare($baseQuery);

        $stmt->execute($params);

        $histories = $stmt->fetchAll(PDO::FETCH_ASSOC);



        // Format data for DataTable

        $data = [];

        foreach ($histories as $history) {

            $row = [

                // Action column

                '<div class="dropdown">

                    <button class="table-menu-btn mx-auto dropdownBtn" type="button" data-bs-toggle="dropdown" id="dropdownMenuButton' . $history['id'] . '" aria-expanded="false">

                        <i class="bi bi-gear"></i>

                    </button>

                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $history['id'] . '">

                        <li class="mb-1"><a href="?oid=' . $history['order_id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-arrow-repeat text-black f-14 me-2"></i>Recover</a></li>

                    </ul>

                </div>',

                // Order ID

                $history['order_id'],

                // Service Type

                $history['interview_title'] ?? 'N/A',

                // Company

                $history['company'] ?? 'N/A',

                // Invoice Date

                !empty($history['invoice_date']) ? date('Y-m-d', strtotime($history['invoice_date'])) : 'Null',

                // Reference (if customer filter is applied)

                !empty($customerId) ? ($history['reference'] ?? 'N/A') : '',

                // Created

                date('Y-m-d', strtotime($history['created'])),

                // Status

                '<div class="f-14 d-flex justify-content-center status_show">

                    <div class="status-approved" style="background-color: ' . ($history['status_color'] ?? '#ccc') . '">' . ($history['status'] ?? 'N/A') . '</div>

                </div>',

                // Status Date

                date('Y-m-d', strtotime($history['status_date']))

            ];



            // Remove reference column if not filtering by customer

            if (empty($customerId)) {

                array_splice($row, 5, 1); // Remove reference column

            }



            $data[] = $row;

        }



        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => $totalRecords,

            'recordsFiltered' => $totalRecords,

            'data' => $data

        ]);



    } catch (Exception $e) {

        error_log("History query error: " . $e->getMessage());

        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => 0,

            'recordsFiltered' => 0,

            'data' => [],

            'error' => 'Database error occurred'

        ]);

    }

    exit;

}



// Customer Languages DataTable AJAX endpoint

if (isset($_POST['action']) && $_POST['action'] == 'get_customer_languages_data') {

    // Authentication check

    if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {

        http_response_code(401);

        echo json_encode(['error' => 'Unauthorized']);

        exit;

    }



    header('Content-Type: application/json');

    ob_clean();



    try {

        // Get DataTable parameters

        $draw = intval($_POST['draw']);

        $start = intval($_POST['start']);

        $length = intval($_POST['length']);

        $searchValue = $_POST['search']['value'] ?? '';

        $orderColumn = intval($_POST['order'][0]['column'] ?? 0);

        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';



        // Build base query

        $baseQuery = 'SELECT * FROM customer_languages';

        $countQuery = 'SELECT COUNT(*) as total FROM customer_languages';



        $whereConditions = [];

        $params = [];



        // Apply search filter

        if (!empty($searchValue)) {

            $whereConditions[] = 'value LIKE ?';

            $params[] = '%' . $searchValue . '%';

        }



        // Add WHERE clause if conditions exist

        if (!empty($whereConditions)) {

            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);

            $baseQuery .= $whereClause;

            $countQuery .= $whereClause;

        }



        // Get total count

        $stmt = $conn->prepare($countQuery);

        $stmt->execute($params);

        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



        // Define columns for ordering

        $columns = [

            0 => 'id',

            1 => 'value',

            2 => 'value'

        ];



        // Add ORDER BY clause

        if (isset($columns[$orderColumn])) {

            $baseQuery .= ' ORDER BY ' . $columns[$orderColumn] . ' ' . strtoupper($orderDir);

        } else {

            $baseQuery .= ' ORDER BY id ASC';

        }



        // Add LIMIT for pagination

        $baseQuery .= ' LIMIT ' . $start . ', ' . $length;



        // Execute main query

        $stmt = $conn->prepare($baseQuery);

        $stmt->execute($params);

        $languages = $stmt->fetchAll(PDO::FETCH_ASSOC);



        // Format data for DataTable

        $data = [];

        foreach ($languages as $index => $language) {

            $value = !empty($language['value']) ? json_decode($language['value'], true) : null;

            $enValue = '';

            $swgValue = '';

            

            if (is_array($value)) {

                $enValue = $value['en'] ?? '';

                $swgValue = $value['swg'] ?? '';

            }



            $data[] = [

                $start + $index + 1, // Row number

                $enValue,

                $swgValue,

                '<input type="hidden" value="' . $language['id'] . '">

                <div class="dropdown">

                    <a href="#" onclick="update_s(this)" class="btn bg-primary no-decoration f-12 w-600 m-0">

                        <i class="fas fa-edit f-14"></i>

                    </a>

                </div>'

            ];

        }



        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => $totalRecords,

            'recordsFiltered' => $totalRecords,

            'data' => $data

        ]);



    } catch (Exception $e) {

        error_log("Customer Languages query error: " . $e->getMessage());

        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => 0,

            'recordsFiltered' => 0,

            'data' => [],

            'error' => 'Database error occurred'

        ]);

    }

    exit;

}



// FAQs DataTable AJAX endpoint
