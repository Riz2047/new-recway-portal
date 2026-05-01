<?php
$activeLink = "candidates";
include_once('includes/header.php');
if (! isset($_GET['id'])) {
    redirect('orders.php');
}
if (isset($_GET['a'])) {
    $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([0, $_GET['id']]);
}
if (isset($_GET['cid'])) {
    $query = 'DELETE FROM comments WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['cid']]);
}
if (isset($_POST['update'])) {
    $staff_id = $_POST['staff'];
    $can_name = $_POST['can_name'];
    $comment = $_POST['comment'];
    $query = 'SELECT * FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();
    $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$staff_id, $_GET['id']]);
    // Create a DateTime object for Sweden's timezone
    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
    $swedenTime = new DateTime('now', $swedenTimezone);
    $currentTime = $swedenTime->format('H:i:s');
    $dayOfWeek = date('N');
    if (! empty($res)) {
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['id']]);
        $candidate = $stmt->fetch();
        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->interview_id]);
        $interview = $stmt->fetch();
        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();
        $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$_GET['id'], "Staff ({$staff->name}) Assigned to {$candidate->name} {$candidate->surname}", $comment]);
        $messages = getMessages($candidate->cus_id, $interview->id);
        $body = replace($messages->staff_msg, $_POST['cus_name'], $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');
        //        $body .= "<br><b>Comment:</b> {$comment}<br><br>";
        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');
            sendMail($body, $staff->email, $staff->name, "Candidate Assigned");
        } else {
            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned', '1');
        }
        flash("staffUpdated", "Staff updated successfully!");
    } else {
        flash("staffUpdated", "Could not update staff!", "errorMsg");
    }
}
if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $date = $_POST['date'];
    $cus_name = $_POST['cus_name'];
    $can_name = $_POST['can_name'];
    $cus_email = $_POST['cus_email'];
    $comment = $_POST['comment'];
    $orderID = $_POST['order_id'];
    $report = isset($_FILES['report']) && ! empty($_FILES['report']['name']) ? $_FILES['report']['name'] : "";
    $interviewID = $_POST['interviewID'];
    $reportName = time() . "-" . substr(uniqid(), -6) . ".pdf";
    if (! empty($report)) {
        move_uploaded_file($_FILES['report']['tmp_name'], '../uploads/' . $reportName);
    }
    $status = getStatusById($status);
    $date_time = date('Y-m-d H:i:s', strtotime($date . date('H:i:s')));
    if ($status->variable == "booked") {
        $query = 'UPDATE candidates SET status = ?, booked = ?';
        if (! empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, $date, $_GET['id']]);
    } elseif ($status->variable == "rebooking") {
        $query = 'UPDATE candidates SET status = ?, booked = ?';
        if (! empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, null, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET status = ?';
        if (! empty($report)) {
            $query .= ", report = '{$reportName}'";
        }
        // Set Delivery Date
        if ($status->variable == "approval_received") {
            // Set the delivery date to 3 or 5 days from the current date, depending on the interview ID
            $delivery_date = $interviewID == 10 ? date('Y-m-d', strtotime($date . ' +3 days')) : date('Y-m-d', strtotime($date . ' +5 days'));
            // Check if the delivery date falls on a weekend (6 for Saturday or 7 for Sunday)
            if (date('N', strtotime($delivery_date)) >= 6) {
                // If the delivery date falls on a weekend, add the required number of days to set it to the next Monday
                $days_to_add = 8 - date('N', strtotime($delivery_date));
                $delivery_date = date('Y-m-d', strtotime($delivery_date . ' +' . $days_to_add . ' days'));
            }
            $query .= ", delivery_date = '{$delivery_date}'";
        }
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status->id, $_GET['id']]);
    }
    $res = "true";
    if (! empty($res)) {
        // $comment .= !empty($comment) ? '<br>-' . $_SESSION['admin']->name : '';
        $comment .= '<br>-' . $_SESSION['admin']->name;
        $query = "INSERT INTO history (order_id, `desc`, date_time, comment) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($query);
        if ($status->variable == "booked") {
            $res = $stmt->execute([$_GET['id'], $status->status_detail, date('Y-m-d H:i:s'), $comment]);
        } else {
            $res = $stmt->execute([$_GET['id'], $status->status_detail, $date_time, $comment]);
        }
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['id']]);
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
        $msg = getStatusMessage($status->id, $service->id, $candidate->cus_id);
        if (! empty($msg)) {
            $msg = $msg->col;
            $body = replace($msg, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], ! empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID, $date, ! empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, ! empty($place) ? $place->name : '');
            saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status);
            if (isEmailAllowed($candidate->cus_id, $status->id)) {
                $directory = "../security-report-uploads/";
                $filename = $candidate->interview_report;
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    if (($status->variable == "approved" || $status->variable == "denied") && ! empty($filename) && file_exists($directory . $filename) && $customer->send_security_report == 1) {
                        sendMail($body, $cus_email, $cus_name, $status->status, $directory . $filename);
                    } else {
                        sendMail($body, $cus_email, $cus_name, $status->status);
                    }
                } else {
                    saveEmail("Customer", $cus_name, $orderID, $status->status . ' Message', $body, $cus_email, $status->status, '1');
                }
            }
            if ($status->variable == "canceled") {
                $body = $msg;
                $body = replace($body, $cus_name, $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], ! empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, ! empty($place) ? $place->name : '');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled');
                    sendMail($body, $candidate->email, $candidate->name, 'Order Canceled');
                } else {
                    saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled', '1');
                }
            }
        }
        $combine_status_array = explode(',', $customer->combine_status);
        if ($status->id && $combine_status_array && in_array($status->id, $combine_status_array)) {
            $combine_bk_and_security_array = explode(',', $customer->combine_bk_and_security);
            if ($combine_bk_and_security_array && in_array($candidate->interview_id, $combine_bk_and_security_array)) {
                // Update candidate to status 1 and set interview_id to combine_interview_id
                $query = 'UPDATE candidates SET status = 1, interview_id = ? WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->combine_interview_id, $_GET['id']]);
                // Recursively call the same function with updated status and interview_id
                $_POST['status'] = 1; // Set status to 1
                $_POST['interviewID'] = $candidate->combine_interview_id; // Set interview_id to combine_interview_id
                $_POST['comment'] = "Background check is transferred to Security check interview.";
                // Call the same function recursively
                include __FILE__;
                return; // Exit to prevent duplicate processing
            }
        }
        flash("statusUpdated", "Status updated successfully!");
    } else {
        flash("statusUpdated", "Could not update status!", "errorMsg");
    }
}
if (isset($_GET['status'])) {
    $query = 'SELECT * FROM candidates WHERE status = ? AND expired = 0 ORDER BY booked ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['status']]);
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates WHERE expired = 0 ORDER BY booked ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
}
$query = 'SELECT * FROM candidates WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidate = $stmt->fetch();
$currentIndex = array_search($candidate->order_id, array_column($candidates, "order_id"));
$candidateNext = $candidates[$currentIndex + 1] ?? "";
$candidatePrev = $candidates[$currentIndex - 1] ?? "";
$query = "SELECT * FROM history WHERE order_id = {$_GET['id']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$history = $stmt->fetchAll();
$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->cus_id]);
$customer = $stmt->fetch();
$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->interview_id]);
$interview = $stmt->fetch();
$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->interview_id]);
$can_interview = $stmt->fetch();
if (isset($_POST['resend'])) {
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
    flash("msgResent", "Email has been resent successfully!");
}
$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->email]);
$emails = $stmt->fetchAll();
if (isset($_POST['submit'])) {
    $comment = $_POST['comment'];
    $query = 'INSERT INTO comments (order_id, author_id, author_type, comment) VALUES (?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['id'], $_SESSION['admin']->id, 'admin', $comment]);
    if (! empty($res)) {
        flash("commentAdded", "Comment added successfully!");
    } else {
        flash("commentAdded", "Could not add comment!", "errorMsg");
    }
}
if (isset($_POST['update_candidate'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $security = $_POST['security'];
    $hasPersonalId = isset($_POST['hasPersonalId']) ? $_POST['hasPersonalId'] : 0;
    $note = $_POST['note'];
    $service = $_POST['service'];
    $vasc_id = $_POST['vasc_id'] ?? null;
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $background_check_date = ! empty($_POST['background_check_date']) ? $_POST['background_check_date'] : null;
    $delivery_date = ! empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    if (! empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);
        $filesArray = [];
        $files = null;
        for ($i = 0; $i < $totalFiles; $i++) {
            // $fileName = time() . '-' . $_FILES['files']['name'][$i];
            // $files .= $fileName . ',';
            // $originalName = $_FILES['files']['name'][$i];
            // $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            // $fileName = time() . '-' . uniqid() . '.' . $fileExtension;
            $originalName = $_FILES['files']['name'][$i];
            $fileName = time() . '-' . str_replace(",", "", $originalName);
            $filesArray[] = $fileName;
            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName);
        }
        $files = implode(',', $filesArray);
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, hasPersonalId = ?, cv = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $hasPersonalId, $files, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, hasPersonalId = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $hasPersonalId, $_GET['id']]);
    }
    if (! empty($res)) {
        flash("candidateUpdated", "Candidate updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("candidateUpdated", "Could not update candidate!");
    }
}
$query = 'SELECT * FROM comments WHERE order_id = ? ORDER BY id DESC';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->id]);
$comments = $stmt->fetchAll();
$totalComments = count($comments);
$staffId = $_SESSION['staff']->id;
$unreadCount = 0;
if (! empty($comments)) {
    foreach ($comments as $comment) {
        if ($comment->author_id != $staffId) {
            $readBy = explode(',', $comment->read_by_staff ?? '');
            $readBy = array_map('trim', $readBy);
            if (! in_array($staffId, $readBy)) {
                $unreadCount++;
            }
        }
    }
}
$query = 'SELECT * FROM places WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->place]);
$place = $stmt->fetch();
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$interviews = $stmt->fetchAll();
if (function_exists('getStaffAllowedPermissions')) {
    getStaffAllowedPermissions();
}
$userCategory = $_SESSION['user_category'] ?? null;
$hasBackgroundPermission = function_exists('staffHasPermission') && staffHasPermission('view_background_orders');
$backgroundServiceCategoryId = defined('BACKGROUND_ID') ? BACKGROUND_ID : 3;
if ($userCategory == 5 && $hasBackgroundPermission) {
    $interviews = array_filter($interviews, function ($interview) use ($backgroundServiceCategoryId) {
        return (int)$interview->service_cat_id === (int)$backgroundServiceCategoryId;
    });
    $interviews = array_values($interviews);
}
$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();
$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->staff_id]);
$staff = $stmt->fetch();
$query = 'SELECT service_categories.* FROM service_categories LEFT JOIN interviews ON service_categories.id = interviews.service_cat_id LEFT JOIN candidates ON interviews.id = candidates.interview_id WHERE candidates.id = ' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();
$query = 'SELECT * FROM uploaded_pdf_candidate WHERE can_id = ? AND is_trash = 0';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$uploaded_pdf = $stmt->fetchAll();
$order_forms = findAllByQuery('SELECT * FROM order_forms WHERE cus_id = ' . $candidate->cus_id . ' AND service_id = ' . $candidate->interview_id);
if (! empty($order_forms)) {
    if (! empty($order_forms[0]->form)) {
        $order_form = json_decode($order_forms[0]->form)->form_builder;
        if (! empty($order_form->billing_info)) {
            foreach ($order_form->billing_info as $key => $value) {
                $k_a = explode(',', $key);
                if ($k_a[2] == 'pref') {
                    $invoice_recipent_label = $k_a[1];
                } elseif ($k_a[2] == 'ref') {
                    $invoice_reference_label = $k_a[1];
                } elseif ($k_a[2] == 'comment') {
                    $invoice_comment_label = $k_a[1];
                }
            }
        }
    }
}
$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['staff']->id]);
$log_staff = $stmt->fetch();
$bir_interview_place = null;
if (! empty($place)) {
    $bir_interview_place = $place->name;
}
if (! empty($candidate->BIR_interview_place)) {
    $bir_interview_place = $candidate->BIR_interview_place;
}
?>
<?php flash("staffUpdated"); ?>
<?php flash("commentAdded"); ?>
<?php flash("msgResent"); ?>
<?php flash("candidateUpdated"); ?>
<?php flash("statusUpdated"); ?>
<?php flash("staffAssigned"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row justify-content-center ">
            <!-- <div class="col-lg-3 mb-3">
                            <div class="white-box ">
                                <div class="candidate-profile mx-auto">
                                    <h1 class="f-26 w-700 text-white m-0 p-0 font-secondary">KR</h1>
                                </div>
                                <div class="candidate-info ">
                                    <h1 class="f-16 w-700 text-black m-0 p-0 mt-2 text-center">Kewin Roe</h1>
                                    <p class=" f-14 text-grey w-500 mb-0 text-center">Order# 3265</p>
                                    <div class="status-active px-3 py-1 f-18 my-2 mx-auto">Active</div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 ">Security Number</p>
                                        <p class="f-14 w-700 text-black text-lg-start text-center">20220823789</p>
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 text-lg-start text-center">VASC ID</p>
                                        <p class="f-14 w-700 text-black text-lg-start text-center">1234</p>
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 text-lg-start text-center">Email</p>
                                        <p class="f-14 w-700 text-black text-lg-start text-center">kewinroe@gmail.com</p>
                                        <div class="d-flex justify-content-center">
                                            <button class="blank-btn "><i class="bi bi-cloud-download-fill me-2"></i> Download PDF</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div> -->
            <div class="col-lg-12 mb-3 d-flex justify-content-between">
                <div style="font-size: 14px">
                    <?php if (isset($_GET['status'])): ?>
                        <?php echo ! empty($candidatePrev) ? '<a class="w-700 me-2 no-decoration text-dark" href="invoice.php?id=' . $candidatePrev->id . '&status=' . $_GET['status'] . '"><i class="bi bi-arrow-left-short"></i> Previous</a>' : '' ?>
                        <?php echo ! empty($candidateNext) ? '<a class="w-700 no-decoration text-dark" href="invoice.php?id=' . $candidateNext->id . '&status=' . $_GET['status'] . '">Next <i class="bi bi-arrow-right-short"></i></a>' : '' ?>
                    <?php else: ?>
                        <?php echo ! empty($candidatePrev) ? '<a class="w-700 me-2 no-decoration text-dark" href="invoice.php?id=' . $candidatePrev->id . '"><i class="bi bi-arrow-left-short"></i> Previous</a>' : '' ?>
                        <?php echo ! empty($candidateNext) ? '<a class="w-700 no-decoration text-dark" href="invoice.php?id=' . $candidateNext->id . '">Next <i class="bi bi-arrow-right-short"></i></a>' : '' ?>
                    <?php endif; ?>
                </div>
                <div class="profile-img">
                    <button class="f-16 w-600 text-dark mb-0 pb-0 btn-primary-sm">Action</button>
                    <input type="hidden" name="updated_candidate" id="updated_candidate" value="<?php echo $candidate->combine_interview_id ?>">
                    <input type="hidden" name="updated_customer_combine" id="updated_customer_combine" value="<?php echo $customer->combine_interview_id ?>">
                    <input type="hidden" name="updated_status" id="updated_status" value="<?php echo $candidate->status ?>">
                    <input type="hidden" name="updated_customer" id="updated_customer" value="<?php echo $customer->combine_bk_and_security ?>">
                    <input type="hidden" name="updated_combine_statuses" id="updated_combine_statuses" value="<?php echo $customer->combine_status ?>">
                    <input type="hidden" name="current_interview_id" id="current_interview_id" value="<?php echo $candidate->interview_id ?>">
                    <div class="tool-pit tool-pit2">
                        <div class="tool-pit-content">
                            <div class="d-flex justify-content-end">
                                <div class="arrow-up me-3"></div>
                            </div>
                            <div class="tool-pit-content--header">
                                <!-- <a href="" class="no-decoration text-white">Change Status</a> -->
                            </div>
                            <ul class=" menus">
                                <li><a class="open-report" data-bs-toggle="modal" data-id="<?php echo $candidate->id ?>"
                                        data-lang="en" href="report.php?id=<?php echo $candidate->id ?>">Generate Report
                                        - En</a></li>
                                <li><a class="open-report" data-bs-toggle="modal" data-id="<?php echo $candidate->id ?>"
                                        data-lang="sv" href="report-sv.php?id=<?php echo $candidate->id ?>">Generate
                                        Report - Sv</a></li>
                                <!--                                            <hr>-->
                                <!--                                            <li><a href=""> <i class="bx bx-user me-3"></i>Change Status</a></li>-->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8 px-lg-0 mb-lg-0 mb-3 ">
                <div class="white-box-p-0 h-100">
                    <div class="tab">
                        <button class="tablinks f-14 w-700 " id="defaultOpen"
                            onclick="openCity(event, 'profile')">Profile</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'billing')">Billing
                            Details</button>
                        <button class="tablinks f-14 w-700 "
                            onclick="openCity(event, 'attachment')">Attachments&nbsp;&nbsp;<span
                                id="cv_pdf"><?php echo ! empty($candidate->cv) ? "✔️" : "❌" ?></span>
                            <span
                                id="int_pdf"><?php echo ! empty($candidate->interview_template) ? "✔️" : "❌" ?></span></button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'notes')">Additinal notes by
                            customer</button>
                        <button
                            class="tablinks f-14 w-700 <?php if ($unreadCount > 0): ?> text-success <?php endif; ?> "
                            onclick="openCity(event, 'comments')">Internal Comments &nbsp;
                            <?php if ($totalComments > 0): ?><span id="internal_comment_count"
                                    style="<?php if ($unreadCount > 0): ?>background-color: #2f9265;<?php else: ?>background-color: grey;<?php endif; ?>color: white;border-radius: 100%;padding: 2px 5px 2px 5px;"><?= $totalComments ?></span><?php endif; ?></button>
                    </div>
                    <div id="profile" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-6 order-lg-1 order-2">
                                    <div class="mt-3 ">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 ">
                                            Security Number</p>
                                        <p class="f-14 w-700 text-black up_ssn">
                                            <?php echo $candidate->security ?>
                                        </p>
                                    </div>
                                    <?php if (! empty($candidate->vasc_id)) { ?>
                                        <div class="mt-3">
                                            <p class="f-12 w-600 text-grey mb-0 pb-0 ">
                                                VASC ID</p>
                                            <p class="f-14 w-700 text-black up_vasc_id"><?= $candidate->vasc_id ?></p>
                                        </div>
                                    <?php } ?>
                                    <div class="mt-3">
                                        <?php
                                        $query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->interview_id]);
$interview = $stmt->fetch();
?>
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Service Type</p>
                                        <p class="f-14 w-700 text-black up_service_type"><?php echo $interview->title ?>
                                        </p>
                                    </div>
                                    <?php if (! empty($candidate->booked)) { ?>
                                        <div class="mt-3">
                                            <p class="f-12 w-600 text-grey mb-0 pb-0">Interview Date</p>
                                            <p class="f-14 w-700 text-black up_interview_date"><?= $candidate->booked ?></p>
                                        </div>
                                    <?php } ?>
                                    <?php if (! empty($candidate->delivery_date)) { ?>
                                        <div class="mt-3">
                                            <p class="f-12 w-600 text-grey mb-0 pb-0">Delivery Date</p>
                                            <p class="f-14 w-700 text-black up_interview_date">
                                                <?= $candidate->delivery_date ?>
                                            </p>
                                        </div>
                                    <?php } ?>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Email</p>
                                        <p class="f-14 w-700 text-black up_email"><?php echo $candidate->email ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Phone Number</p>
                                        <p class="f-14 w-700 text-black up_phone"><?php echo $candidate->phone ?></p>
                                    </div>
                                    <?php if (! empty($interview->place)): ?>
                                        <div class="mt-3">
                                            <p class="f-12 w-600 text-grey mb-0 pb-0">Place</p>
                                            <p class="f-14 w-700 text-black up_place">
                                                <?php echo ! empty($place) ? $place->name : "Null" ?>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Company</p>
                                        <p class="f-14 w-700 text-black up_company"><?php echo $customer->company ?></p>
                                    </div>
                                    <?php if (isset($candidate->meta_data) && ! empty($candidate->meta_data)): ?>
                                        <?php $can_meta_data = json_decode($candidate->meta_data); ?>
                                        <?php foreach ($can_meta_data as $m_key => $m_value): ?>
                                            <div class="mt-3">
                                                <p class="f-12 w-600 text-grey mb-0 pb-0"><?= $m_key ?></p>
                                                <p class="f-14 w-700 text-black"><?= $m_value ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    <div class="mt-3">
                                        <p class="f-12 w-500 text-grey mb-0 pb-0">Additional notes from customer</p>
                                        <p class="f-14 w-500 text-black up_note">
                                            <?php echo ! empty($candidate->note) ? $candidate->note : "Null" ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-lg-6 order-lg-2 order-1">
                                    <div class="candidate-profile mx-auto">
                                        <h1 class="f-26 w-700 text-white m-0 p-0 font-secondary">
                                            <?php echo substr(str_replace(" ", "", $candidate->name), 0, 1) . substr(str_replace(" ", "", $candidate->surname), 0, 1) ?>
                                        </h1>
                                    </div>
                                    <div class="candidate-info ">
                                        <h1 class="f-16 w-700 text-black m-0 p-0 mt-2 text-center up_name">
                                            <?php echo $candidate->name . " " . $candidate->surname ?>
                                        </h1>
                                        <p class=" f-14 text-grey w-500 mb-0 text-center">Order#
                                            <?php echo $candidate->order_id ?>
                                        </p>
                                        <?php $status = getStatusById($candidate->status) ?>
                                        <div class="status-active px-3 py-1 f-18 my-2 mx-auto up_status"
                                            style="background-color: <?php echo $status->color ?>">
                                            <?php echo $status->status ?>
                                        </div>
                                    </div>
                                    <?php if (! empty($log_staff->can_upload_report)) { ?>
                                        <?php if ($can_interview->service_cat_id == 1 || $can_interview->service_cat_id == 9 || $can_interview->service_cat_id == 10) { ?>
                                            <?php
    $spiUploaded = false;
                                            $ellevioUploaded = false;
                                            $timraUploaded = false;
                                            if (! empty($candidate->interview_report)) {
                                                $decoded = json_decode($candidate->interview_report, true);
                                                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                                    $spiUploaded = isset($decoded['spi']);
                                                    $ellevioUploaded = isset($decoded['ellevio']);
                                                    $timraUploaded = isset($decoded['timra']);
                                                } else {
                                                    $spiUploaded = true;
                                                }
                                            }
                                            ?>
                                            <div class="mt-3">
                                                <div class="d-flex justify-content-center">
                                                    <?php if (! empty($customer->interview_upload_allowed)) { ?>
                                                        <button type="button"
                                                            class="btn text-white p-2 <?php echo $spiUploaded ? 'btn-success' : 'btn-primary bg-primary'; ?> btn-sm"
                                                            onclick="triggerFileInput()">
                                                            <i class="bi bi-cloud-upload-fill me-2"></i> Upload Interview Report
                                                        </button>
                                                    <?php } else { ?>
                                                        <button type="button" onclick="show_activation_text()"
                                                            class="btn text-white p-2 btn-danger btn-sm">
                                                            Upload Interview Report
                                                        </button>
                                                    <?php } ?>
                                                    <input type="file" id="fileInput" style="display: none;"
                                                        onchange="uploadFile()">
                                                </div>
                                                <?php if (! empty($customer->ellevio_report)) { ?>
                                                    <div class="d-flex justify-content-center">
                                                        <button type="button"
                                                            class="btn text-white p-2 <?php echo $ellevioUploaded ? 'btn-success' : 'btn-primary bg-primary'; ?> btn-sm"
                                                            onclick="triggerFile2Input()">
                                                            <i class="bi bi-cloud-upload-fill me-2"></i> Upload Ellevio Report
                                                        </button>
                                                        <input type="file" id="fileInput2" style="display: none;"
                                                            onchange="uploadEllevioFile()">
                                                    </div>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                    <?php if ($can_interview->service_cat_id == 1 || $can_interview->service_cat_id == 9 || $can_interview->service_cat_id == 10) { ?>
                                        <div class="d-flex justify-content-center mt-2">
                                            <?php if ($candidate->status == 3 || $candidate->status == 35 || $candidate->status == 51) { ?>
                                                <?php if (empty($candidate->interview_template)) { ?>
                                                    <button type="button" class="btn btn-sm btn-info text-white p-2"
                                                        id="generate_temp_btn" onclick="pdf_gene(<?= $_GET['id'] ?>)"><i
                                                            class="bi bi-cloud-download-fill me-2"></i>Generate SPI
                                                        Template</button>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (empty($candidate->interview_template)) { ?>
                                                <?php if (! empty($customer->ellevio_report)) { ?>
                                                    <button type="button" class="btn btn-sm btn-info text-white p-2"
                                                        onclick="pdf_gene_ellevio(<?= $_GET['id'] ?>)"><i
                                                            class="bi bi-cloud-download-fill me-2"></i>Generate Ellevio
                                                        Template</button>
                                                <?php } ?>
                                                <?php if (! empty($customer->timra_report)) { ?>
                                                    <button type="button" class="btn btn-sm btn-info text-white p-2"
                                                        onclick="pdf_gene_timra(<?= $_GET['id'] ?>)"><i
                                                            class="bi bi-cloud-download-fill me-2"></i>Generate Timrå
                                                        Referens</button>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="billing" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">
                                            <?php echo ! empty($invoice_recipent_label) ? $invoice_recipent_label : 'Invoice Recipient' ?>
                                        </p>
                                        <p class="f-14 w-700 text-black"><?php echo $candidate->referensperson ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">
                                            <?php echo ! empty($invoice_reference_label) ? $invoice_reference_label : 'Invoice Reference' ?>
                                        </p>
                                        <p class="f-14 w-700 text-black"><?php echo $candidate->reference ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">
                                            <?php echo ! empty($invoice_comment_label) ? $invoice_comment_label : 'Invoice Comment' ?>
                                        </p>
                                        <p class="f-14 w-700 text-black">
                                            <?php echo ! empty($candidate->comment) ? $candidate->comment : "Null" ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="attachment" class="tabcontent ">
                        <div class="container">
                            <form action="#" id="uploadcv" method="post" enctype="multipart/form-data">
                                <input type="hidden" id="customer" value="<?php echo $customer->id ?>"
                                    data-combine-bk-and-security="<?php echo $customer->combine_bk_and_security ?>">
                                <div class="row">
                                    <p><b>Uploaded Document/CV by customer</b></p>
                                    <div class="col-md-6">
                                        <input type="file" name="file_1[]" id="file_1" multiple>
                                        <input type="hidden" name="can_id" value="<?= $_GET['id'] ?>">
                                    </div>
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-primary float-right btn-sm">Upload</button>
                                    </div>
                                    <div class="col-md-12 mt-2" id="cv_div">
                                        <?php if (! empty($candidate->cv)):
                                            $documents = explode(',', $candidate->cv);
                                            $uploadedCount = count($documents);
                                            ?>
                                            <table class="table table-bordered table-sm" id="cv_table">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>File Name</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($documents as $index => $document): ?>
                                                        <tr>
                                                            <td><?= $index + 1 ?></td>
                                                            <td
                                                                style="max-width:250px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                                                                <?= htmlspecialchars($document) ?>
                                                            </td>
                                                            <td class="text-start">
                                                                <a target="_blank"
                                                                    href="../uploads/<?= htmlspecialchars($document) ?>"
                                                                    class="btn btn-sm d-inline-flex align-items-center justify-content-center me-2"
                                                                    style="background:rgba(33, 116, 241, 1); color:#fff; border-radius:6px; width:38px; height:38px;"
                                                                    onmouseover="this.style.background='rgba(13, 109, 253, 0.7)';"
                                                                    onmouseout="this.style.background='rgba(33, 116, 241, 1)';">
                                                                    <i class="fa fa-eye"></i>
                                                                </a>
                                                                <button type="button"
                                                                    onclick="deleteExisting('<?= htmlspecialchars($document) ?>', this)"
                                                                    class="btn btn-sm d-inline-flex align-items-center justify-content-center"
                                                                    style="background:#ec3043ff; color:#fff; border-radius:6px; width:38px; height:38px;"
                                                                    onmouseover="this.style.background='rgba(220, 53, 70, 0.7)';"
                                                                    onmouseout="this.style.background='#ec3043ff';">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php else:
                                            $uploadedCount = 0;
                                            ?>
                                            <p class="mb-0 f-14 pl-3" id="no_cv_text">No Document/CV uploaded by customer</p>
                                            <table class="table table-bordered table-sm d-none" id="cv_table">
                                                <tbody></tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                            <hr class="m-2">
                            <form action="#" id="uploadinterview_template" method="post" enctype="multipart/form-data">
                                <div class="row">
                                    <p><b>Interview template by customer</b></p>
                                    <div class="col-md-6" id="int_div">
                                        <?php if (! empty($candidate->interview_template)):
                                            ?>
                                            <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis"
                                                class="mb-0 w-100 f-14 pt-1"><a target="_blank"
                                                    href="../uploads/<?php echo $candidate->interview_template ?>"
                                                    style="cursor: pointer"
                                                    class="text-success"><?php echo $candidate->interview_template ?></a>
                                            </p>
                                        <?php else: ?>
                                            <p class="mb-0 w-100 f-14 pl-3">No interview template uploaded by customer</p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-6">
                                        <input type="file" name="file_1">
                                        <input type="hidden" name="can_id" value="<?= $_GET['id'] ?>">
                                    </div>
                                    <div class="col-md-12 text-right">
                                        <button type="submit" class="btn btn-primary float-right btn-sm">Upload
                                            Interview Template</button>
                                    </div>
                                </div>
                            </form>
                            <hr class="m-2">
                        </div>
                    </div>
                    <div id="notes" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12 ">
                                    <div class="mt-3">
                                        <p class="f-12 w-500 text-grey mb-0 pb-0">Note</p>
                                        <p class="f-14 w-500 text-black up_note">
                                            <?php echo ! empty($candidate->note) ? $candidate->note : "Null" ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="comments" class="tabcontent " style="height: 500px">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12" id="comments-inner"
                                    style="overflow-y: scroll;max-height: 310px;">
                                    <?php if (! empty($comments)): ?>
                                        <?php foreach ($comments as $comment):
                                            $query = 'SELECT * FROM ' . $comment->author_type . ' WHERE id = ?';
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([$comment->author_id]);
                                            $author = $stmt->fetch();
                                            ?>
                                            <div class="mt-2 bg-light p-2 comment">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="p-0 m-0 w-16 w-700">~<?php echo $author->name ?></small>
                                                    <p class="m-0 p-0">
                                                        <!-- <a
                                                            href="edit-comment.php?oid=<?php echo $_GET['id'] ?>&cid=<?php echo $comment->id ?>"><i
                                                                class="bi bi-pen text-success"></i></a> -->
                                                        <a class="delete_comment_btn" data-id="<?php echo $comment->id ?>"
                                                            href="<?php echo $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'] ?>&cid=<?php echo $comment->id ?>"><i
                                                                class="bi bi-trash text-danger ms-1"></i></a>
                                                    </p>
                                                </div>
                                                <p class="p-0 m-0 f-14 w-500 mt-3"><?php echo $comment->comment ?></p>
                                                <p class="m-0 p-0 w-700 f-12 mt-4" style="text-align: right; font-size: 12px">
                                                    <?php echo date("M d, Y h:i A", strtotime($comment->created)) ?>
                                                </p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="mt-2">
                                            <p>No comments yet</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-12">
                                    <form class="update-form" method="post">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="comment">Comment</label>
                                                <textarea required name="comment" class="form-control"
                                                    id="comment"></textarea>
                                            </div>
                                        </div>
                                        <div id="add_comment_msg" class="text-center"></div>
                                        <div class="d-flex justify-content-end">
                                            <button id="add_comment_btn" type="submit" name="submit"
                                                class="btn-primary bg-primary f-10">Add Comment</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 ">
                <div class="white-box">
                    <div class="container px-0">
                        <div class="row">
                            <div class="col-lg-12">
                                <h1 class="f-16 w-700 text-black mb-2 mt-1 pb-0 ">Order History</h1>
                                <div class="wrapper order-history">
                                    <ul class="sessions mt-2">
                                        <?php if (! empty($history)): ?>
                                            <?php foreach ($history as $h): ?>
                                                <li>
                                                    <div class="time" <?php if (strtotime($h->date_time) > time()) { ?>
                                                        style="color:brown" <?php } ?>>
                                                        <?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?>
                                                    </div>
                                                    <p class="f-14 w-500"><?php echo $h->desc ?>
                                                    </p>
                                                    <i><small
                                                            class="m-0 p-0"><?php echo ! empty($h->comment) ? 'Comment: ' . $h->comment : '' ?></small></i>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center  mt-2">
            <div class="col-lg-12 px-lg-0 mb-lg-0 mb-3 ">
                <div class="white-box">
                    <div class="tab2">
                        <?php
                        $color = '';
if ($candidate->economy == 0 && $candidate->social == 0 && $candidate->criminal_record == 0) {
    $color = "text-danger";
} elseif ($candidate->economy == 0 || $candidate->social == 0 || $candidate->criminal_record == 0) {
    $color = "text-warning";
} elseif ($candidate->economy == 1 && $candidate->social == 1 && $candidate->criminal_record == 1) {
    $color = "text-success";
}
?>
                        <button class="tablinks2 f-14 w-700 text-left"
                            style="font: caption;font-weight: bolder;width: 100%;" id="update_pdf" onclick="open_tab()"
                            style="width: 100% !important;">
                            <span class="bk_text <?= $color ?>">BK for criminal, economy & social media</span> <i
                                class="float-right bx bxs-chevron-down arrow"
                                style="font-size:20px !important"></i></button>
                    </div>
                    <div id="update-pdf" class="tabcontent2" style="display:none">
                        <div class="container">
                            <div class="container mb-2 mt-3 p-0">
                                <form action="#" id="uploadpdf" method="post" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="">Type</label>
                                            <select name="for_type" id="sel_for_type" class="form-control">
                                                <option value="1">Economic</option>
                                                <option value="2">Criminal Record</option>
                                                <option value="3">Social Media</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="">Upload Document</label><br>
                                            <input type="file" name="file_1">
                                            <input type="hidden" name="can_id" value="<?= $_GET['id'] ?>">
                                        </div>
                                        <div class="col-md-12 text-right">
                                            <button type="submit"
                                                class="btn btn-primary float-right btn-sm">Upload</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <table class="table table-bordered" id="pdf-table">
                                        <thead>
                                            <tr>
                                                <th>Uploaded For</th>
                                                <th>File</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbody-uploaded-pdf">
                                            <?php if (! empty($uploaded_pdf)) { ?>
                                                <?php foreach ($uploaded_pdf as $upload_pdf) { ?>
                                                    <tr>
                                                        <td>
                                                            <?php if ($upload_pdf->file_for == 1) { ?>
                                                                Economic
                                                            <?php } ?>
                                                            <?php if ($upload_pdf->file_for == 2) { ?>
                                                                Criminal Record
                                                            <?php } ?>
                                                            <?php if ($upload_pdf->file_for == 3) { ?>
                                                                Social Media
                                                            <?php } ?>
                                                        </td>
                                                        <td>
                                                            <?= $upload_pdf->file_name ?>
                                                        </td>
                                                        <td>
                                                            <a href="../uploads/<?= $upload_pdf->file_name ?>" target="_blank"
                                                                class="btn bg-primary">Preview</a>
                                                            <input type="hidden" value="<?= $upload_pdf->file_name ?>"
                                                                class="file_name">
                                                            <button type="button" class="btn text-white bg-danger"
                                                                onclick="delete_file(this)">Delete</button>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row justify-content-center  mt-4">
            <div class="col-lg-12 px-lg-0 mb-lg-0 mb-3 ">
                <div class="white-box">
                    <div class="tab2">
                        <button class="tablinks2 f-14 w-700 " id="defaultOpen2"
                            onclick="editCity(event, 'update-records')">Update Records</button>
                        <?php if (isset($allowed_staff_permission['change_staff']) && ! empty($allowed_staff_permission['change_staff'])) { ?>
                            <button class="tablinks2 f-14 w-700 " onclick="editCity(event, 'staff2')">Assign Staff</button>
                        <?php } ?>
                        <button class="tablinks2 f-14 w-700 " onclick="editCity(event, 'email')">Email</button>
                        <?php if (isset($allowed_staff_permission['update_candidate']) && ! empty($allowed_staff_permission['update_candidate'])) { ?>
                            <button class="tablinks2 f-14 w-700 " onclick="editCity(event, 'edit-candidate')">Edit
                                Candidate</button>
                        <?php } ?>
                        <button class="tablinks2 f-14 w-700 " onclick="editCity(event, 'update-status')">Update
                            Status</button>
                    </div>
                    <div id="staff2" class="tabcontent2 ">
                        <?php
$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll();
?>
                        <div class="container">
                            <div class="row">
                                <form class="update-form" method="post">
                                    <div class="col-md-12 mb-3" id="">
                                        <label class="form-label" for="">Staff</label>
                                        <select id="" name="staff" class="form-control mb-3 filter-select">
                                            <?php if (! empty($staff)): ?>
                                                <?php foreach ($staff as $st): ?>
                                                    <option <?php echo ! empty($candidate->staff_id) && $candidate->staff_id == $st->id ? 'selected' : '' ?>
                                                        value="<?php echo $st->id ?>"><?php echo $st->name ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <div class="col-lg-12 p-0 mb-3">
                                            <label class="form-label">Comment</label>
                                            <textarea class="sign-textarea w-100" name="comment" rows="3"></textarea>
                                        </div>
                                        <input type="hidden" name="can_name" value="<?php echo $candidate->name ?>">
                                        <input type="hidden" name="can_surname"
                                            value="<?php echo $candidate->surname ?>">
                                        <input type="hidden" name="cus_name" value="<?php echo $customer->name ?>">
                                        <input type="hidden" name="cus_company"
                                            value="<?php echo $customer->company ?>">
                                        <input type="hidden" name="interview" value="<?php echo $interview->title ?>">
                                    </div>
                                    <div id="update_staff_msg" class="text-center"></div>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="update" id="update_staff_btn"
                                            class="btn-primary bg-primary">Update</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div id="email" class="tabcontent2 ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12 p-0">
                                    <div class="table-section w-100 p-0">
                                        <div class="table-div w-100">
                                            <form action="" method="post" id="d-form">
                                                <table class="display Table w-100">
                                                    <thead>
                                                        <tr>
                                                            <th class="table-head">Order ID</th>
                                                            <th class="table-head">Email Type</th>
                                                            <th class="table-head">Email</th>
                                                            <th class="table-head">Date</th>
                                                            <th class="table-head">Text</th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="d-none"></th>
                                                            <th class="table-head">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (! empty($emails)): ?>
                                                            <?php $count = 0; ?>
                                                            <?php foreach ($emails as $email): ?>
                                                                <?php if ($email->user_type == "Candidate"): ?>
                                                                    <?php
                                            $query = 'SELECT * FROM candidates WHERE order_id = ?';
                                                                    $stmt = $conn->prepare($query);
                                                                    $stmt->execute([$email->order_id]);
                                                                    $scandidate = $stmt->fetch();
                                                                    ?>
                                                                    <tr>
                                                                        <td class="f-14"><?php echo $email->order_id ?></td>
                                                                        <td class="f-14"><?php echo $email->msg_type ?></td>
                                                                        <td class="f-14"><?php echo $email->email ?></td>
                                                                        <td class="f-14"><?php echo $email->created ?></td>
                                                                        <td class="f-14"><textarea name="text[]"
                                                                                class="sign-textarea"
                                                                                rows="3"><?php echo $email->text ?></textarea></td>
                                                                        <td class="d-none"><input type="text" name="user_type[]"
                                                                                value='<?php echo $email->user_type ?>'></td>
                                                                        <td class="d-none"><input type="text" name="order_id[]"
                                                                                value='<?php echo $email->order_id ?>'></td>
                                                                        <td class="d-none"><input type="text" name="msg_type[]"
                                                                                value='<?php echo $email->msg_type ?>'></td>
                                                                        <td class="d-none"><input type="text" name="name[]"
                                                                                value='<?php echo $email->user_name ?>'></td>
                                                                        <td class="d-none"><input type="text" name="email[]"
                                                                                value="<?php echo $email->email ?>"></td>
                                                                        <td class="d-none"><input type="text" name="subject[]"
                                                                                value="<?php echo $email->subject ?>"></td>
                                                                        <td class="d-none"><input type="text" name="count"
                                                                                value="<?php echo $count ?>"></td>
                                                                        <td class="text-center dt-center f-14">
                                                                            <button name="resend" value="<?php echo $count ?>"
                                                                                class="btn-primary-sm bg-primary resend_btn">Resend</button>
                                                                            <?php $count++; ?>
                                                                        </td>
                                                                    </tr>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                                <div id="resend_msg" class="text-center"></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="edit-candidate" class="tabcontent2 ">
                        <div class="container">
                            <form class="update-form" method="post" enctype="multipart/form-data">
                                <input type="hidden" id="customer" value="<?php echo $customer->id ?>"
                                    data-combine-bk-and-security="<?php echo $customer->combine_bk_and_security ?>">
                                <div class="row mb-3">
                                    <div class="form-check col-md-12 col-sm-12 mb-2" id="hasPersonalIdWrapper">
                                        <input class="form-check-input" type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" <?php echo $candidate->hasPersonalId ? 'checked' : '' ?> onchange="toggleInputType();">
                                        <label class="form-check-label" for="hasPersonalId">
                                            Has Personal Identification Number
                                        </label>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" id="ssnLabel" for="ssn">Social Security Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="security" required id="ssn" placeholder="YYMMDD-XXXX" value="<?php echo htmlspecialchars($candidate->security, ENT_QUOTES, 'UTF-8'); ?>">
                                        <small id="pnrHelp" class="form-text"></small>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="vasc_id">VASC ID</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo $candidate->vasc_id ?>" name="vasc_id" id="vasc_id">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" class="form-control" value="<?php echo $candidate->name ?>"
                                            name="name" required id="name">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="surname">Surname</label>
                                        <input type="text" class="form-control"
                                            value="<?php echo $candidate->surname ?>" name="surname" required
                                            id="surname">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-control" value="<?php echo $candidate->email ?>"
                                            name="email" required id="email">
                                        <input type="hidden" required name="old_email"
                                            value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3"
                                            placeholder="Email Address ">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="phone">Phone</label>
                                        <input type="text" class="form-control" value="<?php echo $candidate->phone ?>"
                                            name="phone" required id="phone">
                                    </div>
                                    <div class="col-md-12 mb-3 <?php echo $candidate->interview_id == 2 || $candidate->interview_id == 4 || $candidate->interview_id == 26 ? '' : 'd-none' ?>"
                                        id="place">
                                        <label class="form-label" for="">Place</label>
                                        <select id="" name="place" class="form-control filter-select">
                                            <?php if (! empty($places)): ?>
                                                <?php foreach ($places as $place): ?>
                                                    <option <?php echo $place->id == $candidate->place ? 'selected' : '' ?>
                                                        value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                        <select id="hidden_interview" style="display:none">
                                            <?php foreach ($interviews as $inter): ?>
                                                <option value="<?php echo $inter->id ?>"
                                                    data-country="<?= $interview->country ?>"
                                                    data-place="<?= $interview->place ?>"
                                                    data-interview-service-cat-id="<?php echo $interview->service_cat_id ?>"
                                                    <?php echo $inter->id == $candidate->interview_id ? 'selected' : '' ?>
                                                    data-country="<?= $inter->country ?>" data-place="<?= $inter->place ?>">
                                                    <?php echo $inter->title ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label" for="">Service Type</label>
                                        <select id="interview" name="service" class="form-control filter-select"
                                            onchange="check_combine_bk_and_security()">
                                            <?php if (! empty($interviews)): ?>
                                                <?php foreach ($interviews as $key => $interview_row): ?>
                                                    <option
                                                        data-interview-service-cat-id="<?php echo $interview_row->service_cat_id ?>"
                                                        <?php echo $interview_row->id == $candidate->interview_id ? 'selected' : '' ?> value="<?php echo $interview_row->id ?>">
                                                        <?php echo $interview_row->title ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-12 col-sm-12 mb-2 d-none" id="security_interview_service_type_div">
                                        <label class="form-label" for="security_interview_service_type">Security Interview Service Type</label>
                                        <!-- <select class="form-control" onchange="fetch_form_security_interview_service_type(this);" id="security_interview_service_type" name="security_interview_service_type" required="true"> -->
                                        <select class="form-control" onchange="check_combine_bk_and_security()" id="security_interview_service_type" name="combine_interview_id">
                                            <option value="0">Select Security Interview Service Type</option>
                                            <?php foreach ($interviews as $interview): ?>
                                                <?php if ($interview->service_cat_id == 1): ?>
                                                    <option value="<?php echo $interview->id ?>" <?php echo (isset($candidate->combine_interview_id) && $candidate->combine_interview_id == $interview->id) || (isset($customer->combine_interview_id) && $customer->combine_interview_id == $interview->id) ? 'selected' : '' ?>>
                                                        <?php echo $interview->title ?>
                                                    </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="background_check_date">Background Check
                                            Date</label>
                                        <input type="date" class="form-control"
                                            value="<?php echo $candidate->background_check_date ?>"
                                            name="background_check_date" id="background_check_date">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="delivery_date">Delivery Date</label>
                                        <input type="date" class="form-control"
                                            value="<?php echo $candidate->delivery_date ?>" name="delivery_date"
                                            id="delivery_date">
                                    </div>
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group file-area w-100">
                                            <div class="d-flex justify-content-between">
                                                <label for="images" class="form-label">Documents</label>
                                            </div>
                                            <input class="sign-input w-100 " type="file" name="files[]" id="cv"
                                                accept="application/pdf" multiple />
                                            <div class="file-dummy sign-input  ">
                                                <div class="success "></div>
                                                <div class="file-icon"><i style="font-size: 28px; color: #5c636a"
                                                        class="fa-solid fa-cloud-arrow-up "></i></div>
                                                <div class="default ">Here you can upload several documents
                                                    <small>(Interview Templates, Documents or CV)</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Note
                                        </label>
                                        <br>
                                        <textarea name="note" id="" style="width: 100%;" rows="6"
                                            placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."><?php echo $candidate->note ?></textarea>
                                    </div>
                                </div>
                                <div id="update_candidate_msg" class="text-center"></div>
                                <?php if (isset($allowed_staff_permission['update_candidate']) && ! empty($allowed_staff_permission['update_candidate'])) { ?>
                                    <div class="d-flex justify-content-end">
                                        <button id="update_candidate_btn" type="submit" name="update_candidate"
                                            class="btn-primary bg-primary">Update</button>
                                    </div>
                                <?php } ?>
                            </form>
                        </div>
                    </div>
                    <?php
                    $srs = null;
if (! empty($candidate->meta_data)) {
    $data = json_decode($candidate->meta_data, true);
    if (! empty($data) && is_array($data)) {
        foreach ($data as $key => $value) {
            if ($key == "This interview is suggested in the SRS portal?") {
                if (! empty($value)) {
                    if (strtolower($value) == "yes") {
                        $srs = 1;
                    }
                }
            }
        }
    }
}
?>
                    <div id="update-status" class="tabcontent2 ">
                        <div class="container">
                            <div class="row">
                                <form id="status-form" class="update-form" method="post">
                                    <div class="row">
                                        <div class="col-lg-6 mb-3" id="">
                                            <label class="form-label" for="">Status</label>
                                            <?php
                        $query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->interview_id]);
$interview = $stmt->fetch();
$cusStatuses = explode(',', $customer->statuses);
?>
                                            <select class="form-control filter-select" name="status" id="change-status"
                                                style="" onchange="show_bir(this)">
                                                <?php if (! empty($servicesCats)): ?>
                                                    <?php foreach ($servicesCats as $servicesCat): ?>
                                                        <optgroup label="<?php echo $servicesCat->name ?>">
                                                            <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                                            <?php foreach ($statuses as $key => $status): ?>
                                                                <?php if (in_array($status->sID, $cusStatuses)): ?>
                                                                    <option data-status-variable="<?php echo $status->variable ?>" <?php echo $status->sID == $candidate->status ? 'selected' : '' ?> <?php if (! empty($srs) && ($status->sID == 4 || $status->sID == 7)) { ?>
                                                                        disabled <?php } ?> value="<?php echo $status->sID ?>">
                                                                        <?php echo $status->status ?>
                                                                    </option>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </optgroup>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-6 mb-3">
                                            <label class="form-label">Date</label>
                                            <input type="date" id="date" required name="date" class="form-control">
                                        </div>
                                        <?php if ($customer->send_security_report == 1) { ?>
                                            <div class="col-lg-12 mb-3" id="bir_interview_place_row" style="display:none">
                                                <label class="form-label">Where did the interview take place?</label>
                                                <input type="text" name="bir_interview_place" id="bir_interview_place"
                                                    placeholder="Where did the interview take place?" class="form-control"
                                                    disabled="disabled" value="<?= $bir_interview_place ?>">
                                            </div>
                                        <?php } ?>
                                        <!-- <div class="col-lg-12 mb-3 service_cost" hidden>
                                            <label class="form-label">Travelling Cost</label>
                                            <input type="number" id="travelling_cost" value="<?php echo $candidate->travel_cost ?>" class="form-control" name="travelling_cost">
                                        </div> -->
                                        <div class="col-lg-12 mb-3">
                                            <label class="form-label" for="comment">Comment</label>
                                            <textarea name="comment" class="form-control" id="comment"></textarea>
                                        </div>
                                        <div class="col-lg-12 mb-3">
                                            <label class="form-label">Upload Report</label>
                                            <input name="report" type="file" class="form-control">
                                        </div>
                                        <input type="hidden" name="cus_name" value="<?php echo $customer->name ?>">
                                        <input type="hidden" name="cus_email" value="<?php echo $customer->email ?>">
                                        <input type="hidden" name="can_name" value="<?php echo $candidate->name ?>">
                                        <input type="hidden" name="booked" value="<?php echo $candidate->booked ?>">
                                        <input type="hidden" name="cus_company"
                                            value="<?php echo $customer->company ?>">
                                        <input type="hidden" name="order_id" value="<?php echo $candidate->order_id ?>">
                                        <input type="hidden" name="interview" value="<?php echo $interview->title ?>">
                                        <input type="hidden" name="interviewID" value="<?php echo $interview->id ?>">
                                    </div>
                                </form>
                                <div id="update_status_msg" class="text-center"></div>
                                <?php if (isset($allowed_staff_permission['update_candidate']) && ! empty($allowed_staff_permission['update_candidate'])) { ?>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="update_status"
                                            class="btn-primary bg-primary  can-report-btn can-report-btn-update">Update</button>
                                    </div>
                                <?php } ?>
                            </div>
                        </div>
                    </div>
                    <div id="update-records" class="tabcontent2 ">
                        <div class="container">
                            <div class="row">
                                <div class="record d-flex col-8 justify-content-between">
                                    <input type="checkbox" name="invoice_sent" class="form-check-input invoice_sent"
                                        id="inv-<?php echo $candidate->id ?>" <?php echo $candidate->invoice_sent == 1 ? 'checked' : '' ?> data-id="<?php echo $candidate->id ?>">
                                    <label class="form-check-label f-14 w-600 text-grey mb-0 pb-0 "
                                        for="inv-<?php echo $candidate->id ?>">
                                        Invoice Sent
                                    </label>
                                    <p class=" f-14 w-600 text-grey mb-0 pb-0 ">Invoice Date: <span
                                            class="invoice_date f-14 w-700 text-black mb-0 pb-0 "><?php echo ! empty($candidate->invoice_date) ? $candidate->invoice_date : 'Null' ?></span>
                                    </p>
                                </div>
                                <div class="col-4"></div>
                                <div class="col-md-8">
                                    <input class="form-check-input" id="reported_to_sm<?php echo $candidate->id ?>"
                                        type="checkbox" <?php echo $candidate->reported_to_sm == 1 ? 'checked' : '' ?>
                                        data-rid="<?php echo $candidate->id ?>" onclick="check_reported_by(this)">
                                    <label class="form-check-label f-14 w-600 text-grey mb-0 pb-0 "
                                        for="reported_to_sm<?php echo $candidate->id ?>">
                                        Reported
                                    </label>
                                    <p class=" f-14 w-600 text-grey mb-0 pb-0 float-right">Reported Date: <span
                                            class=" f-14 w-700 text-black mb-0 pb-0 "
                                            id="rep_date_time"><?php echo ! empty($candidate->reported_to_sm_on) ? $candidate->reported_to_sm_on : 'Null' ?></span>
                                    </p>
                                </div>
                                <div class="col-8 record-main d-flex justify-content-between">
                                    <div>
                                        <?php
                                        $bgRadioDisabled = (isset($userCategory) && (int)$userCategory === 1 ? ' disabled' : '');
$bgSpanDisabledAttr = (isset($userCategory) && (int)$userCategory === 1 ? ' data-disabled=1' : '');
?>
                                        <div class="record d-flex mt-2">
                                            <label class="me-2">
                                                <input class="economy-radio" <?php echo $candidate->economy == 0 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>" onclick="colorOfBk()">
                                                <span class="custom-economy-radio uncheck_economy"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <label class="me-2">
                                                <input class="economy2-radio" <?php echo $candidate->economy == 1 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>" onclick="colorOfBk()">
                                                <span class="custom-economy2-radio check_economy"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <p class="f-14 w-600 text-grey mb-0 pb-0 ">Economy</p>
                                        </div>
                                        <div class="record d-flex mt-2">
                                            <label class="me-2">
                                                <input class="economy-radio" <?php echo $candidate->criminal_record == 0 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>-criminal"
                                                    onclick="colorOfBk()">
                                                <span class="custom-economy-radio uncheck_criminal"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <label class="me-2">
                                                <input class="economy2-radio" <?php echo $candidate->criminal_record == 1 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>-criminal"
                                                    onclick="colorOfBk()">
                                                <span class="custom-economy2-radio check_criminal"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <p class="f-14 w-600 text-grey mb-0 pb-0 ">Criminal Record</p>
                                        </div>
                                        <div class="record d-flex mt-2">
                                            <label class="me-2">
                                                <input class="economy-radio" <?php echo $candidate->social == 0 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>-social"
                                                    onclick="colorOfBk()">
                                                <span class="custom-economy-radio uncheck_social"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <label class="me-2">
                                                <input class="economy2-radio" <?php echo $candidate->social == 1 ? 'checked' : '' ?><?php echo $bgRadioDisabled; ?> type="radio"
                                                    name="<?php echo $candidate->order_id ?>-social"
                                                    onclick="colorOfBk()">
                                                <span class="custom-economy2-radio check_social"
                                                    data-id="<?php echo $candidate->id ?>" <?php echo $bgSpanDisabledAttr; ?>></span>
                                            </label>
                                            <p class="f-14 w-600 text-grey mb-0 pb-0 ">Social Media</p>
                                        </div>
                                    </div>
                                    <p class=" f-14 w-600 text-grey mb-0 pb-0 mt-2">Background Check Date: <span
                                            class="background_check_date f-14 w-700 text-black mb-0 pb-0 "><?php echo ! empty($candidate->background_check_date) ? $candidate->background_check_date : 'Null' ?></span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="pdfModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pdfModalLabel">Download PDFs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php
                if (! empty($candidate->cv)):
                    $documents = explode(',', $candidate->cv);
                    ?>
                    <?php foreach ($documents as $document): ?>
                        <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis"
                            class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../uploads/<?php echo $document ?>"
                                style="cursor: pointer" class="text-success"><?php echo $document ?></a></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="mb-0 w-100 f-18 px-2 py-3">No Document</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
include_once "includes/footer.php";
?>
<script>
    function open_tab() {
        if ($('#update-pdf').is(':hidden')) {
            $('#update-pdf').slideDown(500)
        } else {
            $('#update-pdf').slideUp(500)
        }
    }
    function show_bir(obj) {
        if ($(obj).val() == 6 || $(obj).val() == 39) {
            $('#bir_interview_place_row').show();
            $('input[name="bir_interview_place"]').attr('disabled', false)
        } else {
            $('#bir_interview_place_row').hide();
            $('input[name="bir_interview_place"]').attr('disabled', true)
        }
    }
    function change_button(obj) {
        if ($(obj).val()) {
            $('#generate_temp_btn').attr('disabled', false);
        } else {
            $('#generate_temp_btn').attr('disabled', true);
        }
    }
    $(document).ready(function() {
        show_bir($('select[name="status"]'))
    })
    function openCity(evt, cityName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(cityName).style.display = "block";
        evt.currentTarget.className += " active";
        if (cityName == 'comments') {
            $('#internal_comment_count').css('background-color', 'grey')
            let $li = $('#<?= $_GET['id'] ?>-comment');
            let $ul = $li.closest('ul');
            $li.remove();
            if ($('#comment-menus').find('li').length < 1) {
                $('.profile-img').removeClass('has-dot');
                $ul.append('<p class="no-comments text-muted m-2">No comments found</p>');
            }
            evt.currentTarget.classList.remove('text-success');
            $.ajax({
                type: "POST",
                url: "../includes/pages.php",
                data: {
                    'read_by_staff': 1,
                    'id': "<?= $_GET['id'] ?>"
                },
                success: function(response) {
                    if (response) {}
                },
                error: function() {}
            });
        }
    }
    document.getElementById("defaultOpen").click();
    function check_combine_bk_and_security() {
        var selectedCustomer = $('#updated_customer');
        var selectedCandidate = $('#updated_candidate');
        var interview = $('#interview').val();
        // Get the selected option from the main interview dropdown
        var selectedInterviewOption = $('#interview option:selected');
        var combine_bk_and_security_array = selectedCustomer.length > 0 ? selectedCustomer.val().split(',') : 0;
        var service_cat_id = selectedInterviewOption.length > 0 ? selectedInterviewOption.data('interview-service-cat-id') : 0;
        var combine_interview_id = selectedCandidate.length > 0 ? selectedCandidate.val() : 0;
        // console.log('Customer combine_bk_and_security:', combine_bk_and_security_array);
        // console.log('Interview service_cat_id:', service_cat_id);
        // console.log('Selected customer:', selectedCustomer.val());
        // console.log('Selected interview:', selectedInterviewOption.text());
        // console.log('Selected interview option:', selectedInterviewOption.val());
        // console.log(combine_bk_and_security_array.includes(selectedInterviewOption.val()) ,service_cat_id);
        if (combine_bk_and_security_array && combine_bk_and_security_array.includes(selectedInterviewOption.val()) && service_cat_id == 3) {
            console.log('Showing security interview service type div');
            $('#security_interview_service_type_div').removeClass('d-none');
            // Initialize place field state when security interview service type div is shown
            var securityServiceType = $('#security_interview_service_type').val();
            if (securityServiceType == 2) {
                $('div[id="place"]').removeClass('d-none');
                $('select[name="place"]').prop("disabled", false);
            } else {
                $('div[id="place"]').addClass('d-none');
                $('select[name="place"]').prop("disabled", true);
            }
        } else {
            console.log('Hiding security interview service type div');
            $('#security_interview_service_type_div').addClass('d-none');
            $('#security_interview_service_type').val('0');
        }
    }
    function bindSsnBehavior() {
        // Default: treat as Personal ID input
        const hasPersonalId = document.getElementById('hasPersonalId');
        const ssn = document.getElementById('ssn');
        const pnrHelp = document.getElementById('pnrHelp');
        const ssnLabel = document.getElementById('ssnLabel');
        if (!hasPersonalId || !ssn) return;
        // set default state (unchecked => PNR)
        if (!hasPersonalId.hasAttribute('data-initialized')) {
            hasPersonalId.checked = <?php echo $candidate->hasPersonalId ? 'true' : 'false'; ?>;
            hasPersonalId.setAttribute('data-initialized', '1');
        }
        document.getElementById('ssn').value = "<?php echo htmlspecialchars($candidate->security, ENT_QUOTES, 'UTF-8'); ?>";
        toggleInputType();
        ssn.addEventListener('input', validateSecurityField);
        ssn.addEventListener('blur', validateSecurityField);
    }
    function toggleInputType() {
        const hasPersonalId = document.getElementById('hasPersonalId');
        const securityField = document.getElementById('ssn');
        const ssnLabel = document.getElementById('ssnLabel');
        const pnrHelp = document.getElementById('pnrHelp');
        if (!securityField) return;
        // If the security field is being used as a date of birth field, format it correctly
        if (!hasPersonalId || !hasPersonalId.checked) {
            if (securityField.type !== 'date') {
                securityField.type = 'date';
                securityField.removeAttribute('inputmode');
                securityField.removeAttribute('placeholder');
                securityField.value = ''; // Clear value only when switching to date
            }
            if (ssnLabel) ssnLabel.innerHTML = 'Date of Birth <span class="text-danger">*</span>';
            if (pnrHelp) pnrHelp.textContent = 'Date of birth is required';
        } else {
            if (securityField.type !== 'text') {
                securityField.type = 'text';
                securityField.setAttribute('inputmode', 'numeric');
                securityField.placeholder = 'YYMMDD-XXXX';
                securityField.value = ''; // Clear value only when switching to text
            }
            if (ssnLabel) ssnLabel.innerHTML = 'Personal identification number <span class="text-danger">*</span>';
            if (pnrHelp) pnrHelp.textContent = 'Personal identification number is required';
        }
        // Clear validation states
        securityField.classList.remove('is-valid', 'is-invalid');
        if (pnrHelp) pnrHelp.classList.remove('text-success', 'text-danger');
    }
    function validateSecurityField() {
        const hasPersonalId = document.getElementById('hasPersonalId');
        const securityField = document.getElementById('ssn');
        const pnrHelp = document.getElementById('pnrHelp');
        if (!securityField) return;
        securityField.classList.remove('is-valid', 'is-invalid');
        if (pnrHelp) pnrHelp.classList.remove('text-success', 'text-danger');
        if (!hasPersonalId || !hasPersonalId.checked) {
            if (securityField.value.trim() === '') {
                securityField.classList.add('is-invalid');
                if (pnrHelp) {
                    pnrHelp.textContent = 'Date of birth is required';
                    pnrHelp.classList.add('text-danger');
                }
            } else {
                securityField.classList.add('is-valid');
                if (pnrHelp) {
                    pnrHelp.textContent = 'Date of birth is valid';
                    pnrHelp.classList.add('text-success');
                }
            }
        } else {
            const validation = validatePNR(securityField.value);
            if (securityField.value.trim() === '') {
                securityField.classList.add('is-invalid');
                if (pnrHelp) {
                    pnrHelp.textContent = 'Personal identification number is required';
                    pnrHelp.classList.add('text-danger');
                }
            } else if (validation.isValid) {
                securityField.classList.add('is-valid');
                if (pnrHelp) {
                    pnrHelp.textContent = validation.message;
                    pnrHelp.classList.add('text-success');
                }
            } else {
                securityField.classList.add('is-invalid');
                if (pnrHelp) {
                    pnrHelp.textContent = validation.message;
                    pnrHelp.classList.add('text-danger');
                }
            }
        }
    }
    function validatePNR(pnr) {
        // Check if the PNR is empty (optional field)
        if (!pnr.trim()) {
            return {
                isValid: false,
                message: 'Personal identification number is required'
            };
        }
        // Allow format: YYMMDD-XXXX or YYMMDDXXXX (with or without dash)
        const pnrPattern = /^(\d{6})-?(\d{4})$/;
        const match = pnr.match(pnrPattern);
        if (!match) {
            return {
                isValid: false,
                message: 'Required format is YYMMDD-XXXX or YYMMDDXXXX'
            };
        }
        // Combine the matched groups to get the full 10-digit number
        const cleanPNR = match[1] + match[2];
        // Extract date components
        const year = parseInt(cleanPNR.substring(0, 2));
        const month = parseInt(cleanPNR.substring(2, 4));
        const day = parseInt(cleanPNR.substring(4, 6));
        // Validate year (should be between 00-99, but we'll be more lenient)
        if (year < 0 || year > 99) {
            return {
                isValid: false,
                message: 'Invalid year in Personal identification number'
            };
        }
        // Validate month (should be between 01-12)
        if (month < 1 || month > 12) {
            return {
                isValid: false,
                message: 'Invalid month in Personal identification number(01-12)'
            };
        }
        if (day < 1 || day > 31) {
            return {
                isValid: false,
                message: `Invalid day in Personal identification number(01-31)`
            };
        }
        return {
            isValid: true,
            message: 'Personal identification number is valid'
        };
    }
    function luhn10(num) {
        let sum = 0;
        for (let i = 0; i < num.length; i++) {
            let digit = parseInt(num[i], 10);
            if (i % 2 === 0) { // double even index (0-based) per Swedish PNR on 10-digit string
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
        }
        return sum % 10 === 0;
    }
    $(document).ready(function() {
        if (document.getElementById('ssn')) {
            bindSsnBehavior();
        }
    })
    if (document.getElementById('ssn')) {
        bindSsnBehavior();
    }
</script>
<script>
    var candidate = <?php echo json_encode($candidate); ?>;
    var customer = <?php echo json_encode($customer); ?>;
    var staff = <?php echo json_encode($staff); ?>;
    function check_field() {
        var city = $('#city_report').val()
        if (city != '') {
            $('#preview').attr('disabled', false)
            $('#generate').attr('disabled', false)
            $('#city_text_msg').hide()
        } else {
            $('#preview').attr('disabled', true)
            $('#generate').attr('disabled', true)
            $('#city_text_msg').show()
        }
    }
    function editCity(evt, cityName) {
        if (cityName == 'update-status') {
            console.log(cityName)
            var statusVariable = $('#change-status').find("option:selected").data("status-variable")
            $("#report-section").remove()
            if ((statusVariable === "approved" || statusVariable === "denied" || statusVariable == "Approved_followup" || statusVariable == "Denied_followup") && customer.send_security_report == 1) {
                $("#status-form").after($("#report-template").html())
                if (statusVariable === "approved" || statusVariable == "Approved_followup") {
                    $(".reason-col").remove()
                }
                check_field()
            }
        }
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent2");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks2");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(cityName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    document.getElementById("defaultOpen2").click();
    check_combine_bk_and_security();
    // Full page loader functions
    function showPageLoader(msg) {
        var msg = msg ? msg : 'Transferring Candidate to Security Interview.';
        // Create loader HTML if it doesn't exist
        if ($('#page-loader').length === 0) {
            $('body').append(`
                <div id="page-loader" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.8);
                    z-index: 9999;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    flex-direction: column;
                ">
                    <div class="spinner-border text-light" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="text-light mt-3" style="font-size: 1.2rem; font-weight: 500;">
                        ${msg}    
                    </div>
                </div>
            `);
        }
        $('#page-loader').show();
    }
    function hidePageLoader() {
        $('#page-loader').hide();
    }
    function showpopup(message) {
        flash("errorMsg", message)
    }
</script>
<script id="report-template" type="text/template">
    <div id="report-section">
        <div class="row">
            <div class="col-lg-12 mb-3 reason-col">
                <label class="form-label">Reason</label>
                <textarea id="reason" name="reason_denied" placeholder="Reason for denied" rows="3" class="w-100 sign-textarea form-control"></textarea>
            </div>
            <div class="col-lg-12 mb-3">
                <label class="form-label">Where did the interview take place?</label>
                <input type="text" name="city" id="city_report" placeholder="Where did the interview take place?" maxlength="15" oninput="check_field()" class="w-100 sign-input form-control" value="<?= $candidate->BIR_interview_place ?>">
            </div>
        </div>
        <div class="row">
                    <div class="col-md-12" id="city_text_msg" style="display:none">
                <p class="text-danger">Please Fill the Interview Place Filed First !!</p>
            </div>
            <div class="col-lg-6 mb-3">
                <button type="button" id="preview" onclick="check_field()" data-bs-toggle="modal" data-bs-target="#securityReportModal" class="btn-fill w-100 mt-4 mx-0 can-report-btn btn-primary bg-primary"><a>Preview Report</a></button>
            </div>
            <div class="col-lg-6 mb-3">
                <button type="button" id="generate" onclick="check_field()" class="btn-fill w-100 mt-4 mx-0 can-report-btn btn-primary bg-primary"><a>Generate Report</a></button>
            </div>
            <!--        <div class="col-lg-4 ">-->
            <!--            <button type="button" id="submit" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Submit Report</a></button>-->
            <!--        </div>-->
        </div>
        <div class="col-lg-12 mt-4">
            <p id="report-msg"></p>
        </div>
    </div>
</script>
<script>
    $('#interview').on('change', function() {
        if ($(this).val() == 2 || $(this).val() == 4 || $(this).val() == 26) {
            $('#place').removeClass('d-none')
            $("select[name='place']").prop("disabled", false)
        } else {
            $('#place').addClass('d-none')
            $("select[name='place']").prop("disabled", true)
        }
        if ($(this).val() == 10 || $(this).val() == 12 || $(this).val() == 13) {
            $('#country').removeClass('d-none')
            $("select[name='country']").prop("disabled", false)
        } else {
            $('#country').addClass('d-none')
            $("select[name='country']").prop("disabled", true)
        }
    })
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $('#content-modal').remove();
    var candidate = <?php echo json_encode($candidate); ?>;
    var customer = <?php echo json_encode($customer); ?>;
    var staff = <?php echo json_encode($staff); ?>;
    $("#change-status").on("change", function() {
        var statusVariable = $(this).find("option:selected").data("status-variable")
        $("#report-section").remove()
        if ((statusVariable === "approved" || statusVariable === "denied" || statusVariable == "Approved_followup" || statusVariable == "Denied_followup") && customer.send_security_report == 1) {
            $("#status-form").after($("#report-template").html())
            if (statusVariable === "approved" || statusVariable == "Approved_followup") {
                $(".reason-col").remove()
            }
            check_field()
        }
    })
    // $("#send-report-status").on("change", function () {
    //     var statusVariable = $("#change-status").find("option:selected").data("status-variable")
    //     $("#report-section").remove()
    //     if((statusVariable === "approved" || statusVariable === "denied") && $(this).prop('checked')) {
    //         $("#status-form").after($("#report-template").html())
    //         if(statusVariable === "approved") {
    //             $(".reason-col").remove()
    //         }
    //     }
    // })
    window.jsPDF = window.jspdf.jsPDF;
    // $(window).on('load', function() {
    //     $("#preview").click()
    // })
    $("body").on("click", ".can-report-btn", async function(e) {
        e.preventDefault();
        if ($(this).attr("id") !== "preview" && $(this).attr("id") !== "generate") {
            const result = await Swal.fire({
                title: "Are you sure?",
                text: "Have you selected the correct status? If unsure, please check the intranet before proceeding.",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, I am sure",
                cancelButtonText: "No, I need to check it",
            });
            if (!result.isConfirmed) {
                return;
            }
        }
        if ($(this).attr("id") !== "preview" && $(this).attr("id") !== "generate") {
            $(this).attr("disabled", true)
            $("#update_status_msg").html($("#spinner").html())
        }
        var that = $(this)
        // Create new jsPdf instance
        const doc = new jsPDF()
        var x = 10;
        var y = 5;
        var leftMargin = 10;
        var rightMargin = 10;
        var statusVariable = $("#change-status").find("option:selected").data("status-variable")
        if ($(this).hasClass("can-report-btn-update")) {
            if ((statusVariable !== "approved" && statusVariable !== "denied") || customer.send_security_report == 0) {
                // $("#status-form").submit()
                updateStatus(that)
                return;
            }
        }
        // Define header function
        const addHeader = function() {
            y = 5
            doc.addImage("../assets/images/vattenfall.png", 'PNG', (doc.internal.pageSize.width / 2) - 25, y, 50, 8)
        }
        // Define footer function
        const addFooter = function() {
            doc.setTextColor("#9298A0")
            doc.setFontSize(8)
            const date = new Date();
            const options = {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            };
            const formattedDate = date.toLocaleDateString('en-US', options);
            doc.text(formattedDate, leftMargin, doc.internal.pageSize.height - 5)
            doc.text("Confidentiality class: C3 - Restricted", doc.internal.pageSize.width - 56, doc.internal.pageSize.height - 10)
            doc.text("(after completion of the form)", doc.internal.pageSize.width - 56, doc.internal.pageSize.height - 5)
        }
        const addTable = function(caption, table) {
            doc.setFontSize(12);
            doc.setFont("Helvetica", "Bold");
            doc.text(caption, leftMargin, y)
            y += 3
            var data = [];
            table.forEach(function(row) {
                data.push({
                    key: row[0],
                    value: row[1]
                })
            })
            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    value: 'Value'
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 90,
                        fillColor: '#DBE5F1'
                    },
                },
                didParseCell: function(data) {}
            })
        }
        // Function to draw a checkmark symbol
        function drawCheckmark(doc, x, y) {
            var tickSize = 2; // Size of the tick lines
            doc.setLineWidth(0.5); // Set line width for tick lines
            doc.setDrawColor(0, 0, 0)
            doc.line(x, y, x + tickSize, y + tickSize); // Draw the first tick line
            doc.line(x - 0.2 + tickSize, y - 0.2 + tickSize, x + tickSize * 2, y - tickSize); // Draw the second tick line
        }
        const addTable2 = function(table) {
            y += 3;
            var data = [];
            table.forEach(function(row, index) {
                const rowData = {
                    key: row[0],
                    col1: row[1],
                    col2: row[2],
                    col3: row[3]
                };
                if (index > 2) {
                    rowData.key = {
                        content: rowData.key,
                        colSpan: 2
                    };
                    delete rowData.col1;
                }
                data.push(rowData);
            });
            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    col1: 'Col1',
                    col2: 'Col2',
                    col3: 'Col3'
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 90,
                        fillColor: '#DBE5F1'
                    },
                    col2: {
                        textColor: '#000000'
                    }
                },
                didParseCell: function(data) {
                    if (data.row.index > 2 && data.column.index === 1) {
                        data.cell.colSpan = 2;
                    }
                },
                didDrawCell: function(data) {
                    if (data.cell.section === "body" && data.column.index === 2 && data.row.index > 2) {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;
                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5
                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }
                }
            });
        }
        const addTable3 = function(table, status) {
            y += 3
            var data = [];
            table.forEach(function(row) {
                data.push({
                    key: row[0],
                    value: row[1]
                })
            })
            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    col1: 'Col1',
                    col2: "Col2"
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 120,
                        fillColor: '#DBE5F1'
                    },
                },
                didDrawCell: function(data) {
                    if (data.cell.section === "body" && data.column.index === 2 && status === "denied") {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;
                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5
                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }
                    if (data.cell.section === "body" && data.column.index === 1 && status === "approved") {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;
                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5
                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }
                }
            })
        }
        function getTextWidth(text, fontSize) {
            // Text width in mm
            return (doc.getStringUnitWidth(text) * fontSize) / (72 / 25.6)
        }
        function pxToMm(px) {
            return px * 25.4 / 72;
        }
        // Add first page with header
        addHeader()
        addFooter()
        // Report Data
        y += 20;
        doc.setFontSize(14)
        doc.setTextColor("#000000")
        doc.setFont("Helvetica", 'Bold')
        doc.text("Result of the basic investigation", leftMargin, y)
        y += 10;
        doc.setFontSize(12)
        doc.setFont("Helvetica", '')
        var para = `Denna blankett ska användas vid återrapportering efter genomförd grundutredning.
        Med grundutredning enligt 3 kap. 3 § säkerhetsskyddslagen (2018:585) avses en utredning om personliga förhållanden av betydelse för säkerhetsprövningen. Utredningen ska omfatta betyg, intyg, referenser och uppgifter som den som prövningen gäller har lämnat samt andra uppgifter i den utsträckning det är relevant för prövningen. De detaljerade kraven återfinns i Vattenfalls kravspecifikation för Säkerhetsprövning.`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })
        y += 33;
        para = `This form must be used when reporting back after a basic investigation has been completed.
        With basic investigation according to ch. 3 Section 3 of the Swedish Protective Security Act (2018:585) refers to an investigation into personal circumstances of importance for the security vetting. The investigation shall include grades, certificates, references and information provided by the person to whom the examination applies, as well as other information to the extent that it is relevant to the examination. The detailed requirements can be found in Vattenfall's requirements specification for Security Vetting.`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })
        // Generate Table
        y += 35
        const table = [];
        var caption = "Beställare av säkerhetsprövningen (på Vattenfall)";
        table.push(["Namn & användarnamn / Name & User-ID", customer.name])
        table.push(["E-post / E-mail", customer.email])
        table.push(["Företag / Company", customer.company])
        addTable(caption, table)
        y += 28
        table.length = 0
        caption = "Bakgrundskontroll genomförd av / Basic investigation conducted by"
        table.push(["Namn / Name", "Staff at Recway AB"])
        table.push(["Telefonnummer / Telephone number", "08-551 063 97"])
        table.push(["E-post / E-mail", "info@recway.se"])
        table.push(["Företag / Company", "Recway AB"])
        addTable(caption, table)
        y += 37
        table.length = 0
        caption = "Intervjuarens uppgifter / Information about the interviewer"
        table.push(["Namn / Name", staff.name])
        table.push(["Telefonnummer / Telephone number", staff.phone])
        table.push(["E-post / E-mail", staff.email])
        table.push(["Företag / Company", "Recway AB"])
        addTable(caption, table)
        y += 37
        table.length = 0
        console.log(candidate)
        caption = "Kandidatens uppgifter / Information about the vetted candidate"
        table.push(["Namn / Name", candidate.name + " " + candidate.surname])
        table.push(["Personnummer (ååmmdd-xxxx) Birth date (yymmdd-xxxx)", candidate.security])
        table.push(["VASC-ID", candidate.vasc_id])
        addTable(caption, table)
        y += 35
        doc.setDrawColor(0, 0, 0)
        // doc.setFillColor(0,0,0)
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 25)
        para = `Svaren i personbedömningen vidimeras genom undertecknande på sida två.
Formuläret skickas via mail till: securityvetting@vattenfall.com
The answers in the vetting is authenticated by signing the form on page two.
The form sends by e-mail to: securityvetting@vattenfall.com`;
        doc.setFontSize(12)
        doc.setFont("Helvetica", "")
        doc.text(para, leftMargin + 5, y + 7, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })
        doc.addPage()
        addHeader()
        addFooter()
        y += 20;
        doc.setFontSize(14)
        doc.setTextColor("#000000")
        doc.setFont("Helvetica", 'Bold')
        doc.text("Result of the basic investigation", leftMargin, y)
        y += 7;
        doc.setFontSize(12)
        doc.setFont("Helvetica", '')
        var para = `Markera vilka bakgrundskontroller som genomförts. Detaljer om respektive kontroll finns i Vattenfalls kravspecifikation för säkerhetsprövning. Resultatet ska överlämnas till Vattenfall separat.
Select which of the background screening activities that have been performed. Details about the respective controls can be found in the Specification of requirements for Security Vetting. The results of the screening shall be handed over to Vattenfall separately.
`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })
        y += 26
        doc.setFontSize(8)
        doc.text("Not Applicable*", doc.internal.pageSize.width / 2, y)
        doc.setFontSize(8)
        doc.text("Ja/Yes", (doc.internal.pageSize.width / 2) + 31, y)
        doc.setFontSize(8)
        doc.text("Nej/No", (doc.internal.pageSize.width / 2) + 61, y)
        table.length = 0
        table.push([`Kontroll av CV (Curriculum Vitae)*
Verification of Resumé/CV`, "", "", ""])
        table.push([`Kontroll av referenser*
Verification of references/employer check`, "", "", ""])
        table.push([`Kontroll av betyg, intyg och diplom*
Verification of education, grades and diplomas`, "", "", ""])
        table.push([`Kreditupplysning (säkerhetsklass 2)
Credit check (security class 2-positions)`, "", "", ""])
        table.push([`Kontroll mot Kronofogden
Verification against the Enforcement authority / The Bailiff check`, "", "", ""])
        table.push([`Kontroll av folkbokföring
Verification of civil registration`, "", "", ""])
        table.push([`Kontroll av exponering på sociala medier
Verification of exposure on social medias`, "", "", ""])
        table.push([`Kontroll av öppna källor
Verification of open sources`, "", "", ""])
        table.push([`Kontroll av bolagsaktiviteter samt föreningsaktiviteter
Verification of corporate and associated activities`, "", "", ""])
        table.push([`Kontroll av rättsliga processer och historiska/pågående domar
Verification of legal processes and historical/ongoing judgements`, "", "", ""])
        addTable2(table)
        y = doc.lastAutoTable.finalY + 5;
        doc.setFontSize(10)
        doc.setFont("Helvetica", "Bold")
        doc.text("Resultat av säkerhetsprövningsintervjun ", leftMargin + 5, y)
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text("(markera med ett X)", leftMargin + 75, y)
        y += 5
        doc.setFontSize(10)
        doc.setFont("Helvetica", "Bold")
        doc.text("Result of the security vetting ", leftMargin + 5, y)
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text("(mark with an X) ", leftMargin + 55, y)
        y += 2
        doc.setFontSize(8)
        doc.text("Ja/Yes", (doc.internal.pageSize.width / 2) + 30, y)
        doc.setFontSize(8)
        doc.text("Nej/No", (doc.internal.pageSize.width / 2) + 60, y)
        table.length = 0
        table.push([`Det finns en god personlig kännedom om den prövade
There is a god knowledge about the vetted person`, "", ""])
        table.push([`Individen kan antas vara lojal mot de intressen som ska skyddas av säkerhetsskyddslagen
The individual can be assumed to be loyal to the interests to be protected by the Swedish Protective Security Act`, "", ""])
        table.push([`Individen kan i övrigt anses pålitlig från säkerhetssynpunkt.
The individual can otherwise be considered reliable from a security point of view.`, "", ""])
        addTable3(table, statusVariable)
        y = doc.lastAutoTable.finalY + 2;
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 15)
        para = $("#reason").val()
        doc.text("Om ”nej” ovan, ange anledning / If ”no” above, state reason: ", leftMargin + 2, y + 4)
        doc.line(leftMargin + 2, y + 5, leftMargin + 76, y + 5)
        doc.text(para ? para : "", leftMargin + 2, y + 8, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2)
        })
        y += 19
        doc.text(`Datum för bakgrundskontroll /
Date for the background check`, leftMargin, y)
        var bcd = candidate.background_check_date
        var date = new Date(bcd);
        var options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        var formattedDate = date.toLocaleDateString('en-US', options);
        doc.setFont("Helvetica", "Bold")
        doc.text(bcd ? formattedDate : "N/A", leftMargin, y + 6)
        y += 10
        doc.setFont("Helvetica", "")
        var interview_date = candidate.booked
        date = new Date(interview_date)
        options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        formattedDate = date.toLocaleDateString('en-US', options);
        doc.text(`Datum för intervjun / Date for the interview`, leftMargin, y)
        doc.setFont("Helvetica", "Bold")
        doc.text(interview_date ? formattedDate : "N/A", leftMargin, y + 3)
        y -= 10
        doc.text(`Vidimering av genomförd grundutredning`, doc.internal.pageSize.width - 65, y)
        doc.setFont("Helvetica", "")
        doc.text(`Ort / City : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        var city = $("#city_report").val()
        doc.text(city ? city : "", doc.internal.pageSize.width - 51, y + 3)
        y += 3
        doc.setFont("Helvetica", "")
        var dateVal = $("#date").val()
        date = new Date(dateVal)
        options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        formattedDate = date.toLocaleDateString('en-US', options);
        doc.text(`Datum / Date : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text(formattedDate, doc.internal.pageSize.width - 45, y + 3)
        y += 3
        doc.setFont("Helvetica", "")
        doc.text(`Signatur/ansvarig för genomförd
grundutredning : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text(staff.name ? staff.name : "", doc.internal.pageSize.width - 43, y + 6.5)
        y += 12
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text(`* Dessa kontroller utförs av Vattenfall i fall av nyrekryteringar. Vid konsult/entreprenörsuppdrag utförs de av leverantören själv.
   These controls are carried out by Vattenfall, in cases of recruitments. For consultants, they are carried out by the supplier itself.`, leftMargin, y)
        var blobPDF = new Blob([doc.output('blob')], {
            type: "application/pdf"
        })
        var blobURL = URL.createObjectURL(blobPDF)
        if ($(this).attr("id") === "preview") {
            $('#frame').attr('src', blobURL)
        } else if ($(this).attr("id") === "generate") {
            doc.save(candidate.order_id + ".pdf")
        } else {
            $("#report-msg").removeClass()
            $("#report-msg").empty()
            $("#report-msg").addClass("text-danger text-center")
            $("#report-msg").html(`<div class="lds-ring"><div></div><div></div><div></div><div></div></div>` + "Please wait while the report is being submitted...")
            // Convert the PDF blob to FormData object
            var formData = new FormData();
            formData.append('file', blobPDF, 'filename.pdf');
            formData.append('id', candidate.id);
            formData.append('filename', candidate.order_id);
            if ($('#report-section').length > 0) {
                if ($('#city_report').val() == '') {
                    alert('Please Fill City Field');
                    that.prop("disabled", false);
                    $("#update_status_msg").html("")
                    $("#report-msg").removeClass()
                    $("#report-msg").empty()
                    return
                }
            }
            // Send the form data to the PHP script using AJAX
            $.ajax({
                url: '../security-report-upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response)
                    $("#report-msg").removeClass()
                    $("#report-msg").empty()
                    if (response.includes("Error")) {
                        $("#report-msg").addClass("text-error text-center")
                    } else {
                        $("#report-msg").addClass("text-success text-center")
                    }
                    $("#report-msg").text("File uploaded successfully!")
                    // $("#status-form").submit()
                    updateStatus(that)
                    that.prop("disabled", false);
                    $("#update_status_msg").html("")
                },
                error: function(xhr, status, error) {
                    console.log('Error uploading file: ' + error);
                }
            });
        }
    })
</script>
<script type="text/template" id="timeline">
    <li>
        <div class="time">{date}</div>
        <p class="f-14 w-500">{description}
        </p>
        {comment_li}
    </li>
</script>
<script type="text/template" id="commentTemplate">
    <div class="mt-2 bg-light p-2 comment">
        <div class="d-flex justify-content-between align-items-center">
            <small class="p-0 m-0 w-16 w-700">~{author}</small>
            <p class="m-0 p-0">
                <a class="delete_comment_btn" data-id="{comment_id}" href="<?php echo $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'] ?>&cid={comment_id}"><i class="bi bi-trash text-danger ms-1"></i></a>
            </p>
        </div>
        <p class="p-0 m-0 f-14 w-500 mt-3">{comment}</p>
        <p class="m-0 p-0 w-700 f-12 mt-4"
           style="text-align: right; font-size: 12px">{date}
        </p>
    </div>
</script>
<script type="text/template" id="docs">
    <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../uploads/{document}" style="cursor: pointer" class="text-success">{document}</a></p>
</script>
<!--AJAX-->
<script>
    var id = <?php echo $_GET['id']; ?>;
    // Fetch Order History
    function fetchHistory() {
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                type: 'fetch_history',
                id
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $(".sessions").empty()
                    for (const h of response.history) {
                        var timeline = $("#timeline").html()
                        timeline = timeline.replace("{date}", formatDate(h.date_time))
                            .replace("{description}", h.desc)
                        if (h.comment !== null && h.comment !== "") {
                            timeline = timeline.replace("{comment_li}", '<i><small class="m-0 p-0">{comment}</small></i>')
                            timeline = timeline.replace("{comment}", "Comment: " + h.comment)
                        } else {
                            timeline = timeline.replace("{comment_li}", "")
                        }
                        $(".sessions").append(timeline)
                    }
                } else {
                    alert("Error fetching data")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    }
    // Fetch Comments
    function fetchComments() {
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                type: 'fetch_comments',
                id
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#comments-inner").empty()
                    for (const comment of response.comments) {
                        var commentTemplate = $("#commentTemplate").html()
                        commentTemplate = commentTemplate
                            .replace("{author}", comment.author)
                            .replaceAll("{comment_id}", comment.id)
                            .replace("{comment}", comment.comment)
                            .replace("{date}", formatDate(comment.created))
                        $("#comments-inner").append(commentTemplate)
                    }
                } else {
                    alert("Error fetching data")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    }
    // Fetch Candidate
    function fetchCandidate() {
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                type: 'fetch_candidate',
                id
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    $("#updated_candidate").val(response.candidate.combine_interview_id);
                    $(".up_ssn").text(response.candidate.security)
                    $(".up_vasc_id").text(response.candidate.vasc_id)
                    $(".up_name").text(response.candidate.name + " " + response.candidate.surname)
                    $(".up_email").text(response.candidate.email)
                    $(".up_phone").text(response.candidate.phone)
                    $(".up_place").text(response.candidate.place)
                    $(".up_service_type").text(response.candidate.title)
                    $(".up_note").text(response.candidate.note)
                    $(".background_check_date").text(response.candidate.background_check_date)
                    if (response.candidate.cv) {
                        const docs = response.candidate.cv.split(",")
                        $("#pdfModal").find(".modal-body").empty()
                        docs.forEach((doc) => {
                            var docTemplate = $("#docs").html()
                            docTemplate = docTemplate.replaceAll("{document}", doc)
                            $("#pdfModal").find(".modal-body").append(docTemplate)
                        })
                    }
                } else {
                    alert("Error fetching data")
                }
            },
            error: function(e) {
                console.log(e.responseText)
                alert("AJAX request failed!");
            }
        });
    }
    // Update Staff
    $("#update_staff_btn").on("click", function(e) {
        e.preventDefault()
        $(this).prop("disabled", true);
        $("#update_staff_msg").html($("#spinner").html())
        var formData = new FormData($(this).closest("form")[0]);
        formData.append('type', 'update_staff');
        formData.append('id', id);
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                console.log(response)
                if (response.success) {
                    flash("successMsg", "Staff updated successfully!")
                    that.prop("disabled", false);
                    $("#update_staff_msg").html("")
                    fetchHistory()
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    })
    // Add Comment
    $("#add_comment_btn").on("click", function(e) {
        e.preventDefault()
        $(this).prop("disabled", true);
        $("#add_comment_msg").html($("#spinner").html())
        var formData = new FormData($(this).closest("form")[0]);
        formData.append('type', 'add_comment');
        formData.append('id', id);
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    flash("successMsg", "Comment added successfully!")
                    that.prop("disabled", false);
                    $("#add_comment_msg").html("")
                    $('#comments').find('textarea[name="comment"]').val('');
                    fetchComments()
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    })
    // Update Candidate
    $("#update_candidate_btn").on("click", function(e) {
        e.preventDefault()
        if ($('#ssn').val() == '' || $('#ssn').hasClass('is-invalid')) {
            $('#ssn').focus();
            validateSecurityField();
            return;
        }
        $(this).prop("disabled", true);
        $("#update_candidate_msg").html($("#spinner").html())
        var formData = new FormData($(this).closest("form")[0]);
        formData.append('type', 'update_candidate');
        formData.append('id', id);
        var current_interview_id = $('#current_interview_id').val();
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    console.log(response.candidate.interview_id.toString(), current_interview_id, response.candidate);
                    if (response.candidate.interview_id.toString() != current_interview_id) {
                        showPageLoader('Please wait while changing Candidate Service Type');
                        // Small delay to show the loader, then reload
                        window.location.reload();
                    }
                    flash("successMsg", "Candidate updated successfully!")
                    that.prop("disabled", false);
                    $("#update_candidate_msg").html("")
                    fetchCandidate();
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                console.log(e.responseText)
                alert("AJAX request failed!");
            }
        });
    })
    // Update Status
    function updateStatus(that) {
        if ($('#date').val() == '') {
            alert("Please Select Date First");
            that.prop("disabled", false);
            $("#update_status_msg").html("")
            $("#report-msg").removeClass()
            $("#report-msg").empty()
            return;
        }
        if ($('#bir_interview_place').is(':visible') && $('#bir_interview_place').val() == '') {
            alert('Please fill interview place field');
            that.prop("disabled", false);
            $("#update_status_msg").html("")
            $("#report-msg").removeClass()
            $("#report-msg").empty()
            return;
        }
        // if(!$('#travelling_cost').is(':hidden') && $('#travelling_cost').val() == '') {
        //     alert("Please Add Travelling Cost First");
        //     that.prop("disabled", false);
        //     $("#update_status_msg").html("");
        //     $("#report-msg").removeClass();
        //     $("#report-msg").empty();
        //     return;
        // }
        var formData = new FormData($("#status-form")[0]);
        formData.append('type', 'update_status');
        formData.append('id', id);
        var candidate_present_status = $("#updated_status").val();
        var combine_bk_and_security = $("#updated_customer").val().split(',');
        var combine_interview_id = $("#updated_candidate").val();
        var combine_interview_id_string = $("#updated_candidate").val().toString();
        var combine_interview_id_cus = $("#updated_customer_combine").val();
        var status = formData.get('status');
        var combine_status_array = $("#updated_combine_statuses").val().split(',');
        var interview_id = $("#current_interview_id").val();
        console.log(candidate_present_status, 'selectedStatus');
        console.log(status, 'helllo status');
        // Show loader when starting the request
        // showPageLoader();
        // console.log(combine_interview_id,'helllo combine_interview_id');
        // console.log(combine_interview_id_string,'helllo combine_interview_id_string');
        // console.log(combine_bk_and_security,'helllo combine_bk_and_security');
        // console.log(combine_bk_and_security.includes(combine_interview_id),'combine_bk_and_security.includes(combine_interview_id)');
        // console.log(combine_bk_and_security.includes(combine_interview_id_string),'combine_bk_and_security.includes(combine_interview_id_string)');
        // console.log(combine_bk_and_security.includes(interview_id),'combine_bk_and_security.includes(interview_id)');
        if (combine_status_array.includes(status) && (combine_bk_and_security.includes(interview_id.toString()) || combine_bk_and_security.includes(interview_id))) {
            if ((combine_interview_id == '0' || combine_interview_id == 0) && (combine_interview_id_cus == '0' || combine_interview_id_cus == 0)) {
                showpopup('Please select a security interview first');
                that.prop("disabled", false);
                $("#update_status_msg").html("")
                return;
            }
        }
        if ($('#report-section').length > 0) {
            if ($('#city_report').val() == '') {
                alert('Please Fill City Field');
                return;
            }
        }
        // Send the data to the server
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    var combine_bk_and_security_array = response.customer.combine_bk_and_security.split(',');
                    var combine_status_array = response.customer.combine_status.split(',');
                    console.log(combine_bk_and_security_array, 'combine_bk_and_security_array');
                    console.log(combine_status_array, 'combine_status_array');
                    // Check if this is status 22 and needs special handling
                    console.log(combine_status_array.includes(response.status.id), 'combine_status_array.includes(response.status.id)');
                    if (response.status && response.status.id && combine_status_array.includes(response.status.id.toString())) {
                        // console.log('Status 22 detected - updating to combine interview flow');
                        // console.log('Customer combine_bk_and_security:', response.customer.combine_bk_and_security);
                        // console.log('Candidate combine_interview_id:', response.candidate.combine_interview_id);
                        // // Check if customer has combine_bk_and_security enabled and candidate has combine_interview_id
                        console.log('Checking conditions:', combine_bk_and_security_array.includes(response.candidate.interview_id.toString()));
                        console.log('Checking conditions:', combine_bk_and_security_array, response.candidate.interview_id);
                        // console.log('- Customer exists:', !!response.customer);
                        // console.log('- Customer combine_bk_and_security == 1:', response.customer && response.customer.combine_bk_and_security == 1);
                        // console.log('- Candidate exists:', !!response.candidate);
                        // console.log('- Candidate combine_interview_id > 0:', response.candidate && response.candidate.combine_interview_id > 0);
                        if (response.combine_interview_place == 1) {
                            if (response.customer &&
                                response.candidate && combine_bk_and_security_array.includes(response.candidate.interview_id.toString())) {
                                console.log('Status 22 detected - updating to combine interview flow');
                                var combine_id = response.candidate.combine_interview_id == '0' || response.candidate.combine_interview_id == 0 ? response.candidate.combine_interview_id : response.customer.combine_interview_id
                                // Store formData and other variables for later use
                                var storedFormData = new FormData();
                                // Copy all entries from original formData
                                for (var pair of formData.entries()) {
                                    storedFormData.append(pair[0], pair[1]);
                                }
                                var storedThat = that;
                                // Populate place dropdown from existing select on page
                                var placeOptions = '<option value="">Please select a place</option>';
                                $('select[name="place"] option').each(function() {
                                    if ($(this).val()) {
                                        placeOptions += '<option value="' + $(this).val() + '">' + $(this).text() + '</option>';
                                    }
                                });
                                // If no places found in select, try to fetch via AJAX
                                if (placeOptions === '<option value="">Please select a place</option>') {
                                    $.ajax({
                                        type: "POST",
                                        url: "../includes/pages.php",
                                        data: {
                                            get_places_list: 1
                                        },
                                        dataType: "json",
                                        success: function(placesResponse) {
                                            if (placesResponse && placesResponse.success && placesResponse.places) {
                                                placesResponse.places.forEach(function(place) {
                                                    placeOptions += '<option value="' + place.id + '">' + place.name + '</option>';
                                                });
                                                $('#placeSelectModal').find('select[name="selected_place"]').html(placeOptions);
                                                // Ensure modal appears on top by setting z-index
                                                $('#placeSelectModal').css('z-index', '1060');
                                                $('#placeSelectModal').modal('show');
                                                // After modal is shown, ensure it's on top
                                                setTimeout(function() {
                                                    $('#placeSelectModal').css('z-index', '1060');
                                                    $('.modal-backdrop:last').css('z-index', '1000');
                                                }, 100);
                                            } else if (placesResponse && Array.isArray(placesResponse)) {
                                                placesResponse.forEach(function(place) {
                                                    placeOptions += '<option value="' + place.id + '">' + place.name + '</option>';
                                                });
                                                $('#placeSelectModal').find('select[name="selected_place"]').html(placeOptions);
                                                // Ensure modal appears on top by setting z-index
                                                $('#placeSelectModal').css('z-index', '1060');
                                                $('#placeSelectModal').modal('show');
                                                // After modal is shown, ensure it's on top
                                                setTimeout(function() {
                                                    $('#placeSelectModal').css('z-index', '1060');
                                                    $('.modal-backdrop:last').css('z-index', '1059');
                                                }, 100);
                                            } else {
                                                alert('Unable to load places. Please try again.');
                                                storedThat.prop("disabled", false);
                                                $("#update_status_msg").html("")
                                            }
                                        },
                                        error: function() {
                                            alert('Unable to load places. Please try again.');
                                            storedThat.prop("disabled", false);
                                            $("#update_status_msg").html("")
                                        }
                                    });
                                } else {
                                    // Show modal with places
                                    $('#placeSelectModal').find('select[name="selected_place"]').html(placeOptions);
                                    // Ensure modal appears on top by setting z-index
                                    $('#placeSelectModal').css('z-index', '1060');
                                    $('#placeSelectModal').modal('show');
                                    // After modal is shown, ensure it's on top
                                    setTimeout(function() {
                                        $('#placeSelectModal').css('z-index', '1060');
                                        $('.modal-backdrop:last').css('z-index', '1059');
                                    }, 100);
                                }
                                // Ensure modal appears on top when shown
                                $('#placeSelectModal').off('shown.bs.modal').on('shown.bs.modal', function() {
                                    $(this).css('z-index', '1060');
                                    $('.modal-backdrop:last').css('z-index', '1059');
                                });
                                // Handle modal close/cancel - re-enable button
                                $('#placeSelectModal').off('hidden.bs.modal').on('hidden.bs.modal', function() {
                                    // Only re-enable if modal was closed without submitting
                                    if (!$('#placeSelectModal').data('submitted')) {
                                        storedThat.prop("disabled", false);
                                        $("#update_status_msg").html("")
                                    }
                                    $('#placeSelectModal').removeData('submitted');
                                });
                                // Handle place selection submission
                                $('#submitPlaceSelection').off('click').on('click', function() {
                                    var selectedPlace = $('#placeSelectModal').find('select[name="selected_place"]').val();
                                    if (!selectedPlace || selectedPlace === '') {
                                        alert('Please select a place');
                                        return;
                                    }
                                    // Mark as submitted before closing
                                    $('#placeSelectModal').data('submitted', true);
                                    // Close modal
                                    $('#placeSelectModal').modal('hide');
                                    // Add place to formData and proceed with AJAX request
                                    storedFormData.set('interviewID', combine_id);
                                    storedFormData.set('status', 1);
                                    storedFormData.set('comment', "Background check is transferred to Security check interview.");
                                    storedFormData.set('place', selectedPlace);
                                    // Recursively call the same function
                                    $.ajax({
                                        type: "POST",
                                        url: "../includes/pages.php",
                                        data: storedFormData,
                                        contentType: false,
                                        processData: false,
                                        dataType: "json",
                                        success: function(recursiveResponse) {
                                            console.log('Recursive update response:', recursiveResponse);
                                            if (recursiveResponse.success) {
                                                flash("successMsg", "Status updated for combined interview  successfully!")
                                                storedThat.prop("disabled", false);
                                                $("#update_status_msg").html("")
                                                $(".up_status").text(recursiveResponse.status.status)
                                                $(".up_status").css('background-color', recursiveResponse.status.color)
                                                // fetchHistory()
                                                // Show full page loader before reload
                                                showPageLoader('Transferring Candidate to Security Interview.');
                                                // Small delay to show the loader, then reload
                                                // window.location.reload();
                                                window.location.reload();
                                            } else {
                                                flash("errorMsg", "Error updating to combine interview flow!")
                                                storedThat.prop("disabled", false);
                                                $("#update_status_msg").html("")
                                            }
                                        },
                                        error: function(e) {
                                            console.error("Recursive AJAX request failed:", e);
                                            flash("errorMsg", "Error updating to combine interview flow!")
                                            storedThat.prop("disabled", false);
                                            $("#update_status_msg").html("")
                                        }
                                    });
                                });
                                return; // Exit to prevent duplicate processing
                            }
                        } else {
                            if (response.customer &&
                                response.candidate && combine_bk_and_security_array.includes(response.candidate.interview_id.toString())) {
                                console.log('Status 22 detected - updating to combine interview flow');
                                var combine_id = response.candidate.combine_interview_id == '0' || response.candidate.combine_interview_id == 0 ? response.candidate.combine_interview_id : response.customer.combine_interview_id
                                // console.log(response.candidate.combine_interview_id =='0' , response.candidate.combine_interview_id == 0 , response.candidate.combine_interview_id , response.customer.combine_interview_id)
                                // // Create new form data for the recursive update
                                // console.log(combine_id,'fffffffffff')
                                formData.set('interviewID', combine_id); // Set interview_id to combine_interview_id
                                formData.set('status', 1); // Set status to 1
                                formData.set('comment', "Background check is transferred to Security check interview.");
                                // Recursively call the same function
                                $.ajax({
                                    type: "POST",
                                    url: "../includes/pages.php",
                                    data: formData,
                                    contentType: false,
                                    processData: false,
                                    dataType: "json",
                                    success: function(recursiveResponse) {
                                        console.log('Recursive update response:', recursiveResponse);
                                        if (recursiveResponse.success) {
                                            flash("successMsg", "Status updated for combined interview  successfully!")
                                            that.prop("disabled", false);
                                            $("#update_status_msg").html("")
                                            $(".up_status").text(recursiveResponse.status.status)
                                            $(".up_status").css('background-color', recursiveResponse.status.color)
                                            // fetchHistory()
                                            // Show full page loader before reload
                                            showPageLoader('Transferring Candidate to Security Interview.');
                                            // Small delay to show the loader, then reload
                                            window.location.reload();
                                        } else {
                                            flash("errorMsg", "Error updating to combine interview flow!")
                                        }
                                    },
                                    error: function(e) {
                                        console.error("Recursive AJAX request failed:", e);
                                        flash("errorMsg", "Error updating to combine interview flow!")
                                    }
                                });
                                return; // Exit to prevent duplicate processing
                            }
                        }
                    }
                    // Normal success flow
                    flash("successMsg", "Status updated successfully!")
                    that.prop("disabled", false);
                    $("#update_status_msg").html("")
                    $(".up_status").text(response.status.status)
                    $(".up_status").css('background-color', response.status.color)
                    $(($('#dataTable').find('tbody').find('a'))).each(function() {
                        if ($(this).attr('data-id') == id) {
                            $(this).closest('tr').find('.status_show').find('div').text(response.status.status)
                            $(this).closest('tr').find('.status_show').find('div').css('background-color', response.status.color)
                        }
                    })
                    fetchHistory()
                } else {
                    // Hide loader on error
                    hidePageLoader();
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                // Hide loader on AJAX error
                hidePageLoader();
                alert("AJAX request failed!");
            }
        });
    }
    // Resend Email
    $(".resend_btn").on("click", function(e) {
        e.preventDefault()
        $(this).prop("disabled", true);
        $("#resend_msg").html($("#spinner").html())
        var formData = new FormData($(this).closest("form")[0]);
        formData.append('type', 'resend_mail');
        formData.append('id', id);
        formData.append('resend', $(this).val());
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    flash("successMsg", "Email resent successfully!")
                    that.prop("disabled", false);
                    $("#resend_msg").html("")
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                console.log(e.responseText)
                alert("AJAX request failed!");
            }
        });
    })
    // Delete Comment
    $("body").off("click", ".delete_comment_btn").on("click", ".delete_comment_btn", function(e) {
        e.preventDefault()
        if (confirm("Are you sure you want to delete this internal comment?")) {
            var formData = new FormData();
            formData.append('type', 'delete_comment');
            formData.append('id', $(this).data("id"));
            // Send the data to the server
            var that = $(this)
            $.ajax({
                type: "POST",
                url: "../includes/pages.php",
                data: formData,
                contentType: false,
                processData: false,
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        that.closest(".comment").remove()
                    } else {
                        flash("errorMsg", "Error deleting comment!")
                    }
                },
                error: function(e) {
                    console.log(e.responseText)
                    alert("AJAX request failed!");
                }
            });
        }
    })
    $(document).ready(function() {
        $("#uploadpdf").on('submit', function(e) {
            e.preventDefault();
            var type = $('#sel_for_type').find('option:selected').text()
            const fileInput = $('#uploadpdf').parent().find('input[type="file"]').val();
            if (fileInput) {
                // Create a FormData object to send the file
                var formData = new FormData(this);
                formData.append('upload_pdf', 1);
                $.ajax({
                    type: 'POST',
                    url: './includes/table_ajax.php', // Server-side script to handle the file upload
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        response = JSON.parse(response)
                        if (response != '') {
                            html = `<tr>
                                    <td>
                                        ` + type + `
                                    </td>
                                    <td>
                                        ` + response.file + `
                                    </td>
                                    <td>
                                        <a href="../uploads/` + response.file + `" target="_blank" class="btn bg-primary">Preview</a>
                                        <input type="hidden" value="` + response.file + `" class="file_name">
                                        <button type="button" class="btn text-white bg-danger" onclick="delete_file(this)">Delete</button>
                                    </td>
                                </tr>`
                            $('#tbody-uploaded-pdf').append(html)
                            $('#uploadpdf').parent().find('input[type="file"]').val('');
                            alert('Uploaded Successfully');
                        }
                    }
                });
            } else {
                alert('Please select a file to upload');
            }
        });
        $("#uploadcv").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('upload_cv', 1);
            $.ajax({
                type: 'POST',
                url: './includes/table_ajax.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    response = JSON.parse(response);
                    if (response && response.files && response.files.length > 0) {
                        var cvTable = $("#cv_table");
                        var cvTableBody = cvTable.find("tbody");
                        var noCvText = $("#no_cv_text");
                        if (noCvText.length) noCvText.addClass("d-none");
                        cvTable.removeClass("d-none");
                        var currentCount = cvTableBody.find("tr").length;
                        var currentHeaderCount = cvTable.find("thead").length;
                        if (currentHeaderCount === 0) {
                            cvTable.append('<thead><tr><th>#</th><th>File Name</th><th>Actions</th></tr></thead>');
                        }
                        response.files.forEach(function(file, index) {
                            var rowNumber = currentCount + index + 1;
                            var newRow = `
                <tr>
                    <td>${rowNumber}</td>
                    <td style="max-width:250px;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;">
                        ${file}
                    </td>
                    <td class="text-start">
                    <a target="_blank"
                    href="../uploads/${file}"
                    class="btn btn-sm d-inline-flex align-items-center justify-content-center me-2"
                    style="background:rgba(33, 116, 241, 1); color:#fff; border-radius:6px; width:38px; height:38px;"
                    onmouseover="this.style.background='rgba(13, 109, 253, 0.7)';"
                    onmouseout="this.style.background='rgba(33, 116, 241, 1)';">
                        <i class="fa fa-eye"></i>
                    </a>
                    <button type="button"
                    onclick="deleteExisting('${file}', this)"
                    class="btn btn-sm d-inline-flex align-items-center justify-content-center"
                    style="background:#ec3043ff; color:#fff; border-radius:6px; width:38px; height:38px;"
                    onmouseover="this.style.background='rgba(220, 53, 70, 0.7)';"
                    onmouseout="this.style.background='#ec3043ff';">
                        <i class="fa fa-trash"></i>
                    </button>
                    </td>
                </tr>
            `;
                            cvTableBody.append(newRow);
                        });
                        $('#cv_div').closest('.row').find('input[type="file"]').val('');
                        Swal.fire("Uploaded Successfully", response.files.length + " file(s) have been added.", "success");
                    }
                }
            });
        });
        $("#uploadinterview_template").on('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(this);
            formData.append('upload_int', 1);
            $.ajax({
                type: 'POST',
                url: './includes/table_ajax.php',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    response = JSON.parse(response)
                    if (response != '') {
                        html = `<p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-14 pt-1"><a target="_blank" href="../uploads/` + response.file + `" style="cursor: pointer" class="text-success">` + response.file + `</a></p>`
                        $('#int_div').html(html)
                        if ($('#int_pdf').length) {
                            $('#int_pdf').text("✔️")
                        } else {
                            $('#update_pdf').append(`Interview template <span id="int_pdf">✔️</span>`)
                        }
                        $('#int_div').closest('form').find('input[type="file"]').val('');
                        alert('Uploaded Successfully');
                    }
                }
            });
        });
    });
    function check_reported_by(obj) {
        var can_id = $(obj).data('rid');
        var reported = null
        var rep_date = 'Null'
        if ($(obj).is(':checked')) {
            reported = 1
            var d = new Date();
            var month = d.getMonth() + 1;
            var day = d.getDate();
            var rep_date = d.getFullYear() + '/' +
                (month < 10 ? '0' : '') + month + '/' +
                (day < 10 ? '0' : '') + day;
        } else {
            reported = 2
        }
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: {
                'reported_to_sm': 1,
                'can_id': can_id,
                'reported': reported
            },
            success: function(response) {
                fetchHistory()
                $('#rep_date_time').html(rep_date)
            }
        });
    }
    $('.filter-select').select2({
        dropdownParent: $('#content-modal .modal-content')
    });
    function colorOfBk() {
        var color = ''
        var success = 0
        var danger = 0
        $('#update-records').find('.economy-radio').each(function(i, k) {
            if ($(this).is(':checked')) {
                danger += 1
            }
        })
        $('#update-records').find('.economy2-radio').each(function(i, k) {
            if ($(this).is(':checked')) {
                success += 1
            }
        })
        if (success == 3) {
            color = 'text-success'
        } else if (danger > 0 && danger < 3) {
            color = 'text-warning'
        } else if (danger == 3) {
            color = 'text-danger'
        }
        $('.bk_text').removeClass('text-warning')
        $('.bk_text').removeClass('text-success')
        $('.bk_text').removeClass('text-danger')
        $('.bk_text').addClass(color)
    }
    function delete_file(obj) {
        var btn = $(obj);
        var id = btn.closest('td').find('.file_name').val();
        if (id != '') {
            if (confirm('Are you sure you want to delete this file')) {
                $.ajax({
                    type: "POST",
                    url: "./includes/table_ajax.php",
                    data: {
                        'delete_file': 1,
                        'id': id
                    },
                    success: function(response) {
                        if (response) {
                            flash("successMsg", "Deleted successfully!")
                            $(btn).closest('tr').remove();
                        }
                    },
                    error: function(e) {
                        flash("errorMsg", "Error saving data!")
                    }
                });
            }
        }
    }
    function triggerFileInput() {
        document.getElementById('fileInput').click();
    }
    function triggerFile2Input() {
        document.getElementById('fileInput2').click();
    }
    function triggerFileTimraInput() {
        document.getElementById('fileInputTimra').click();
    }
    function uploadFile() {
        const file = $('#fileInput')[0].files[0];
        if (!file) {
            flash("errorMsg", "Please select a file to upload.");
            return;
        }
        const formData = new FormData();
        formData.append('interview_report', file);
        formData.append('type', 'spi');
        formData.append('can_id', "<?= $_GET['id'] ?>");
        formData.append('interview_report_upload', 1);
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: formData,
            contentType: false,
            processData: false,
            success: function(response) {
                flash("successMsg", "File uploaded successfully!");
                fetchHistory()
            },
            error: function() {
                flash("errorMsg", "Error uploading file.");
            }
        });
    }
    function uploadEllevioFile() {
        const file = $('#fileInput2')[0].files[0];
        if (!file) {
            flash("errorMsg", "Please select a file to upload.");
            return;
        }
        const formData = new FormData();
        formData.append('type', 'ellevio');
        formData.append('interview_report', file);
        formData.append('can_id', "<?= $_GET['id'] ?>");
        formData.append('interview_report_upload', 1);
        if (confirm("Are you sure you want to upload the file as Ellevio interview report?")) {
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    flash("successMsg", "File uploaded successfully!");
                    fetchHistory()
                },
                error: function() {
                    flash("errorMsg", "Error uploading file.");
                }
            });
        }
    }
    function uploadTimraFile() {
        const file = $('#fileInputTimra')[0].files[0];
        if (!file) {
            flash("errorMsg", "Please select a file to upload.");
            return;
        }
        const formData = new FormData();
        formData.append('type', 'timra');
        formData.append('interview_report', file);
        formData.append('can_id', "<?= $_GET['id'] ?>");
        formData.append('interview_report_upload', 1);
        if (confirm("Are you sure you want to upload the file as Timrå interview report?")) {
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    flash("successMsg", "File uploaded successfully!");
                    fetchHistory()
                },
                error: function() {
                    flash("errorMsg", "Error uploading file.");
                }
            });
        }
    }
    function pdf_gene(obj) {
        // Show confirmation popup before generating template
        Swal.fire({
            title: 'Bekräftelse',
            html: 'Har du läst och tagit del av instruktionen för denna kund innan du genererar rapporten?<br><br>Instruktionen finns tillgänglig i Pulshub.<br><br>Rapporten kan endast genereras efter att du har bekräftat att instruktionen är genomläst.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ja',
            cancelButtonText: 'Nej',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // User clicked "Ja" - create history and then generate template
                $.ajax({
                    type: "POST",
                    url: "../includes/pages.php",
                    data: {
                        'action': 'create_spi_history',
                        'candidate_id': obj
                    },
                    dataType: "json",
                    success: function(historyResponse) {
                        if (historyResponse.success) {
                            // History created successfully, now generate the template
                            generateTemplate(obj);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Fel',
                                text: 'Kunde inte skapa historik. Försök igen.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Fel',
                            text: 'Ett fel uppstod vid skapande av historik.'
                        });
                    }
                });
            }
            // If user clicked "Nej" or closed the popup, do nothing (just close popup)
        });
    }
    function pdf_gene_ellevio(obj) {
        // Show confirmation popup before generating template
        Swal.fire({
            title: 'Bekräftelse',
            html: 'Har du läst och tagit del av instruktionen för denna kund innan du genererar rapporten?<br><br>Instruktionen finns tillgänglig i Pulshub.<br><br>Rapporten kan endast genereras efter att du har bekräftat att instruktionen är genomläst.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ja',
            cancelButtonText: 'Nej',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // User clicked "Ja" - create history and then generate template
                $.ajax({
                    type: "POST",
                    url: "../includes/pages.php",
                    data: {
                        'action': 'create_ellevio_history',
                        'candidate_id': obj
                    },
                    dataType: "json",
                    success: function(historyResponse) {
                        if (historyResponse.success) {
                            // History created successfully, now generate the template
                            fillStaffNameInPDF(obj);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Fel',
                                text: 'Kunde inte skapa historik. Försök igen.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Fel',
                            text: 'Ett fel uppstod vid skapande av historik.'
                        });
                    }
                });
            }
            // If user clicked "Nej" or closed the popup, do nothing (just close popup)
        });
    }
    function generateTemplate(obj) {
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_inte_data': obj,
            },
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    var order_id = response.order_id;
                    var name = response.name.replace(/\s+/g, '').substring(0, 1) + response.surname.replace(/\s+/g, '').substring(0, 1);
                    var vasc_id = response.vasc_id;
                    var refperson = $('#hiring_manager_name').val();
                    var cus_name = response.cus_name.replace(/\s+/g, ' ').trim();
                    var cus_company = response.cus_company.trim();
                    var place = response.place_name;
                    var staff = response.staff;
                    var interview_date = response.booked;
                    var cri_check = response.criminal_record;
                    var ssn = response.security;
                    var soc_check = response.social;
                    var eco_check = response.economy;
                    var bk_date = response.background_check_date;
                    var now = new Date();
                    var hours = now.getHours();
                    var minutes = now.getMinutes();
                    var seconds = now.getSeconds();
                    // if (place && interview_date) {
                    //     place_inter_date = place + '          ' + interview_date
                    // } else if (place) {
                    //     place_inter_date = place
                    // } else if (interview_date) {
                    place_inter_date = interview_date
                    // }
                    hours = (hours < 10 ? '0' : '') + hours;
                    minutes = (minutes < 10 ? '0' : '') + minutes;
                    seconds = (seconds < 10 ? '0' : '') + seconds;
                    var currentTime = hours + ':' + minutes + ':' + seconds;
                    var day = now.getDate();
                    var month = now.getMonth() + 1;
                    var year = now.getFullYear();
                    day = (day < 10 ? '0' : '') + day;
                    month = (month < 10 ? '0' : '') + month;
                    var currentDate = year + '-' + month + '-' + day;
                    var srs = "N/A"; // Default value
                    var report_id = "N/A";
                    var apply_position = "N/A";
                    var e_or_c = "N/A";
                    if (response.meta_data) {
                        var data;
                        try {
                            data = JSON.parse(response.meta_data); // Decode JSON
                        } catch (e) {
                            data = null; // If parsing fails, set data to null
                        }
                        if (data) {
                            Object.keys(data).forEach(function(key) {
                                if (key.trim() == "Is currently applying for the position of and If this is a consultant transition please specify") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        apply_position = data[key];
                                    }
                                }
                                if (key.trim() == "Employee or consultant?") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        e_or_c = data[key];
                                    }
                                }
                                if (key === "Report-ID for the background check from SRS") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        report_id = data[key]; // Assign valid value
                                    }
                                }
                                if (key === "This interview is suggested in the SRS portal?") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        srs = data[key]; // Assign valid value
                                    }
                                }
                            });
                        }
                    }
                    const checkboxString = "☐";
                    const checkedCheckboxString = "☒";
                    const def_check = "Ja ☐	Nej ☐";
                    if (eco_check == 1) {
                        eco_check = "Ja ☒	Nej ☐";
                    } else if (eco_check == 0) {
                        eco_check = "Ja ☐	Nej ☒";
                    } else {
                        eco_check = "Ja ☐	Nej ☐";
                    }
                    if (soc_check == 1) {
                        soc_check = "Ja ☒	Nej ☐";
                    } else if (soc_check == 0) {
                        soc_check = "Ja ☐	Nej ☒";
                    } else {
                        soc_check = "Ja ☐	Nej ☐";
                    }
                    if (cri_check == 1) {
                        cri_check = "Ja ☒	Nej ☐";
                    } else if (cri_check == 0) {
                        cri_check = "Ja ☐	Nej ☒";
                    } else {
                        cri_check = "Ja ☐	Nej ☐";
                    }
                    if (srs == undefined) {
                        srs = "N/A"
                    }
                    var temp = null;
                    var name_ini = name;
                    if (response.status == 35) {
                        temp = "./../assets/docx/Follow_up_template.docx";
                        name = response.name + " " + response.surname;
                    } else if (response.status == 51) {
                        temp = "./../assets/docx/Exit_Interview.docx";
                        name = response.name + " " + response.surname;
                    } else {
                        if (cus_company == 'Scania') {
                            temp = "./../assets/docx/Scania_interview_template.docx";
                        } else {
                            temp = "./../assets/docx/default_interview_template.docx";
                        }
                    }
                    function loadFile(url, callback) {
                        PizZipUtils.getBinaryContent(url, callback);
                    }
                    loadFile(
                        temp,
                        function(error, content) {
                            if (error) {
                                throw error;
                            }
                            const zip = new PizZip(content);
                            const doc = new window.docxtemplater(zip, {
                                paragraphLoop: true,
                                linebreaks: true,
                            });
                            doc.render({
                                place_inter_date: place_inter_date,
                                social_security_number: ssn ? ssn : '',
                                staff: staff ? staff : 'N/A',
                                time: currentTime ? currentTime : '',
                                vasc_id: vasc_id ? vasc_id : 'N/A',
                                name_ini: name ? name : 'N/A',
                                ord_id: order_id ? order_id : 'N/A',
                                inv_ref: refperson ? refperson : 'N/A',
                                company: cus_company ? cus_company : '',
                                bk_date: bk_date ? bk_date : '',
                                customer_name: cus_name ? cus_name : 'N/A',
                                eco_check: eco_check,
                                soc_check: soc_check,
                                cri_check: cri_check,
                                apply_position: apply_position,
                                e_or_c: e_or_c,
                                srs: srs,
                                rapport_id: report_id,
                                current_date: currentDate,
                            });
                            const blob = doc.getZip().generate({
                                type: "blob",
                                mimeType: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                compression: "DEFLATE",
                            });
                            var vasc = '';
                            if (vasc_id) {
                                vasc += vasc_id + "_"
                            }
                            saveAs(blob, order_id + "_" + vasc + name_ini + "_" + interview_date + ".docx");
                        }
                    );
                    //call history fetch
                    fetchHistory();
                }
            }
        });
    }
    function pdf_gene_timra(obj) {
        // Show confirmation popup before generating template
        Swal.fire({
            title: 'Bekräftelse',
            html: 'Har du läst och tagit del av instruktionen för denna kund innan du genererar rapporten?<br><br>Instruktionen finns tillgänglig i Pulshub.<br><br>Rapporten kan endast genereras efter att du har bekräftat att instruktionen är genomläst.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ja',
            cancelButtonText: 'Nej',
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // User clicked "Ja" - create history and then generate template
                $.ajax({
                    type: "POST",
                    url: "../includes/pages.php",
                    data: {
                        'action': 'create_timra_history',
                        'candidate_id': obj
                    },
                    dataType: "json",
                    success: function(historyResponse) {
                        if (historyResponse.success) {
                            // History created successfully, now generate the template
                            generateTimraTemplate(obj);
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Fel',
                                text: 'Kunde inte skapa historik. Försök igen.'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Fel',
                            text: 'Ett fel uppstod vid skapande av historik.'
                        });
                    }
                });
            }
            // If user clicked "Nej" or closed the popup, do nothing (just close popup)
        });
    }
    function generateTimraTemplate(obj) {
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_inte_data': obj,
            },
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    var order_id = response.order_id;
                    var name = response.name.replace(/\s+/g, '').substring(0, 1) + response.surname.replace(/\s+/g, '').substring(0, 1);
                    var vasc_id = response.vasc_id;
                    var refperson = response.referensperson;
                    var cus_name = response.cus_name.replace(/\s+/g, ' ').trim();
                    var cus_company = response.cus_company.trim();
                    var place = response.place_name;
                    var staff = response.staff;
                    var interview_date = response.booked;
                    var cri_check = response.criminal_record;
                    var ssn = response.security;
                    var soc_check = response.social;
                    var eco_check = response.economy;
                    var bk_date = response.background_check_date;
                    var now = new Date();
                    var hours = now.getHours();
                    var minutes = now.getMinutes();
                    var seconds = now.getSeconds();
                    place_inter_date = interview_date
                    // }
                    hours = (hours < 10 ? '0' : '') + hours;
                    minutes = (minutes < 10 ? '0' : '') + minutes;
                    seconds = (seconds < 10 ? '0' : '') + seconds;
                    var currentTime = hours + ':' + minutes + ':' + seconds;
                    var day = now.getDate();
                    var month = now.getMonth() + 1;
                    var year = now.getFullYear();
                    day = (day < 10 ? '0' : '') + day;
                    month = (month < 10 ? '0' : '') + month;
                    var currentDate = year + '-' + month + '-' + day;
                    var srs = "N/A"; // Default value
                    var report_id = "N/A";
                    var apply_position = "N/A";
                    var e_or_c = "N/A";
                    const fullText = String(place);
                    if (response.meta_data) {
                        var data;
                        try {
                            data = JSON.parse(response.meta_data); // Decode JSON
                        } catch (e) {
                            data = null; // If parsing fails, set data to null
                        }
                    }
                    var temp = null
                    var name_ini = name
                    temp = "./../assets/docx/Timrå-Referenstagning-grundutredning.docx";
                    function loadFile(url, callback) {
                        PizZipUtils.getBinaryContent(url, callback);
                    }
                    loadFile(
                        temp,
                        function(error, content) {
                            if (error) {
                                throw error;
                            }
                            const zip = new PizZip(content);
                            const doc = new window.docxtemplater(zip, {
                                paragraphLoop: true,
                                linebreaks: true,
                            });
                            doc.render({
                                inter_date: currentDate,
                                place: place ? place : 'N/A',
                                social_security_number: ssn ? ssn : '',
                                staff: staff ? staff : 'N/A',
                                time: currentTime ? currentTime : '',
                                vasc_id: vasc_id ? vasc_id : 'N/A',
                                name_ini: name ? name : 'N/A',
                                ord_id: order_id ? order_id : 'N/A',
                                inv_ref: refperson ? refperson : 'N/A',
                                company: cus_company ? cus_company : '',
                                bk_date: bk_date ? bk_date : '',
                                customer_name: cus_name ? cus_name : 'N/A',
                                eco_check: eco_check,
                                soc_check: soc_check,
                                cri_check: cri_check,
                                apply_position: apply_position,
                                e_or_c: e_or_c,
                                srs: srs,
                                rapport_id: report_id,
                                current_date: currentDate,
                            });
                            const blob = doc.getZip().generate({
                                type: "blob",
                                mimeType: "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
                                compression: "DEFLATE",
                            });
                            var vasc = '';
                            if (vasc_id) {
                                vasc += vasc_id + "_"
                            }
                            const link = document.createElement('a');
                            link.href = URL.createObjectURL(blob);
                            link.download = `Timra_${order_id}_${vasc_id ?? ''}_${name_ini.toUpperCase()}_${currentDate}.docx`;
                            link.click();
                        }
                    );
                }
                //call history fetch
                fetchHistory();
            }
        });
    }
    function check_combine_bk_and_security() {
        var selectedCustomer = $('#updated_customer');
        var selectedCandidate = $('#updated_candidate');
        var interview = $('#interview').val();
        // Get the selected option from the main interview dropdown
        var selectedInterviewOption = $('#interview option:selected');
        var combine_bk_and_security_array = selectedCustomer.length > 0 ? selectedCustomer.val().split(',') : 0;
        var service_cat_id = selectedInterviewOption.length > 0 ? selectedInterviewOption.data('interview-service-cat-id') : 0;
        var combine_interview_id = selectedCandidate.length > 0 ? selectedCandidate.val() : 0;
        // console.log('Customer combine_bk_and_security:', combine_bk_and_security_array);
        // console.log('Interview service_cat_id:', service_cat_id);
        // console.log('Selected customer:', selectedCustomer.val());
        // console.log('Selected interview:', selectedInterviewOption.text());
        // console.log('Selected interview option:', selectedInterviewOption);
        // console.log('Selected interview option:', selectedInterviewOption.val());
        // console.log(combine_bk_and_security_array.includes(selectedInterviewOption.val()) ,service_cat_id);
        if (combine_bk_and_security_array && combine_bk_and_security_array.includes(selectedInterviewOption.val()) && service_cat_id == 3) {
            console.log('Showing security interview service type div');
            $('#security_interview_service_type_div').removeClass('d-none');
            // Initialize place field state when security interview service type div is shown
            var securityServiceType = $('#security_interview_service_type').val();
            if (securityServiceType == 2) {
                $('div[id="place"]').removeClass('d-none');
                $('select[name="place"]').prop("disabled", false);
            } else {
                $('div[id="place"]').addClass('d-none');
                $('select[name="place"]').prop("disabled", true);
            }
        } else {
            console.log('Hiding security interview service type div');
            $('#security_interview_service_type_div').addClass('d-none');
            $('#security_interview_service_type').val('0');
        }
    }
</script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
<script src="https://unpkg.com/@pdf-lib/fontkit@0.0.4/dist/fontkit.umd.min.js"></script>
<script>
    async function fillStaffNameInPDF(obj) {
        const existingPdfBytes = await fetch("./../assets/docx/Eliviio.pdf").then(res => res.arrayBuffer());
        const pdfDoc = await PDFLib.PDFDocument.load(existingPdfBytes);
        const form = pdfDoc.getForm();
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_inte_data': obj,
            },
            dataType: "json",
            success: async function(response) {
                if (response != '') {
                    var order_id = response.order_id;
                    var name = response.name.replace(/\s+/g, '').substring(0, 1) + response.surname.replace(/\s+/g, '').substring(0, 1);
                    var vasc_id = response.vasc_id;
                    var phone_number = response.staff_number;
                    var refperson = $('#hiring_manager_name').val();
                    var cus_name = response.cus_name.replace(/\s+/g, ' ').trim();
                    var cus_company = response.cus_company.trim();
                    var place = response.place_name;
                    var staff = response.staff;
                    var interview_date = response.booked;
                    var cri_check = response.criminal_record;
                    var ssn = response.security;
                    var soc_check = response.social;
                    var eco_check = response.economy;
                    var bk_date = response.background_check_date;
                    var now = new Date();
                    var hours = now.getHours();
                    var minutes = now.getMinutes();
                    var seconds = now.getSeconds();
                    // if (place && interview_date) {
                    //     place_inter_date = place + '          ' + interview_date
                    // } else if (place) {
                    //     place_inter_date = place
                    // } else if (interview_date) {
                    place_inter_date = interview_date
                    // }
                    hours = (hours < 10 ? '0' : '') + hours;
                    minutes = (minutes < 10 ? '0' : '') + minutes;
                    seconds = (seconds < 10 ? '0' : '') + seconds;
                    var currentTime = hours + ':' + minutes + ':' + seconds;
                    var day = now.getDate();
                    var month = now.getMonth() + 1;
                    var year = now.getFullYear();
                    day = (day < 10 ? '0' : '') + day;
                    month = (month < 10 ? '0' : '') + month;
                    var currentDate = year + '-' + month + '-' + day;
                    var srs = "N/A"; // Default value
                    var report_id = "N/A"; // Default value
                    var apply_position = "N/A";
                    var department = "N/A";
                    if (response.meta_data) {
                        var data;
                        try {
                            data = JSON.parse(response.meta_data); // Decode JSON
                        } catch (e) {
                            data = null; // If parsing fails, set data to null
                        }
                        if (data) {
                            Object.keys(data).forEach(function(key) {
                                if (key === "Avdelning/Enhet") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        apply_position = data[key];
                                    }
                                }
                                if (key === "Befattning/arbete i företaget") {
                                    if (data[key] && data[key] !== "-" && data[key] !== "NA") {
                                        department = data[key];
                                    }
                                }
                            });
                        }
                    }
                    var temp = null
                    var name_ini = name
                    can_name = response.name + " " + response.surname;
                    name = (response.name).toUpperCase() + " " + response.surname;
                    form.getTextField("DatumRow1").setText(interview_date);
                    form.getTextField("DatumRow1_2").setText(interview_date);
                    form.getTextField("Intervjun genomförd avRow1").setText(staff);
                    form.getTextField("Intervjun genomförd avRow1_2").setText(staff);
                    form.getTextField("TelefonnummerRow1").setText(phone_number);
                    form.getTextField("TelefonnummerRow1_2").setText(phone_number);
                    form.getTextField("Företag motsvarandeRow1").setText(cus_company);
                    form.getTextField("Företag motsvarandeRow1_2").setText(cus_company);
                    form.getTextField("AvdelningEnhetRow1").setText(apply_position);
                    form.getTextField("AvdelningEnhetRow1_2").setText(apply_position);
                    form.getTextField("Befattningarbete i företagetRow1").setText(department);
                    form.getTextField("Befattningarbete i företagetRow1_2").setText(department);
                    form.getTextField("Efternamn och alla förnamn tilltalsnamn med VERSALERRow1").setText(name);
                    form.getTextField("Efternamn och alla förnamn tilltalsnamn med VERSALERRow1_2").setText(name);
                    form.getTextField("PersonnummerRow1").setText(ssn);
                    form.getTextField("PersonnummerRow1_2").setText(ssn);
                    const firstPage = pdfDoc.getPages()[0];
                    const thirdPage = pdfDoc.getPages()[2]; // zero-indexed, page 3 is index 2
                    const font = await pdfDoc.embedFont(PDFLib.StandardFonts.Helvetica);
                    if (place) {
                        place = place
                    }
                    const textField = form.createTextField('place_name_field');
                    const textField2 = form.createTextField('place_name_field2');
                    // textField.setText(''); // Optional default text
                    // Set position and size
                    textField.addToPage(firstPage, {
                        x: 97,
                        y: 130,
                        width: 75,
                        height: 15,
                    });
                    textField2.addToPage(thirdPage, {
                        x: 97,
                        y: 152,
                        width: 90,
                        height: 15,
                    });
                    // Draw 
                    if (!place) {
                        place = "";
                    }
                    if (!interview_date) {
                        interview_date = "";
                    }
                    const fullText = String(place);
                    form.getTextField("place_name_field").setText(fullText);
                    form.getTextField("place_name_field2").setText(fullText);
                    firstPage.drawText(interview_date, {
                        x: 180,
                        y: 132,
                        size: 11,
                        font
                    });
                    // firstPage.drawText(can_name, { x: 350, y: 132, size: 11, font });
                    pdfDoc.registerFontkit(fontkit);
                    const fontBytes2 = await fetch('./../assets/fonts/quick_signature/QuickSignaturePersonalUse.otf').then(res => res.arrayBuffer());
                    const signatureFont = await pdfDoc.embedFont(fontBytes2);
                    firstPage.drawText(can_name, {
                        x: 280,
                        y: 132,
                        size: 55,
                        font: signatureFont,
                    });
                    // Register fontkit
                    pdfDoc.registerFontkit(fontkit);
                    const secondPage = pdfDoc.getPages()[1];
                    // Fetch a font that supports Unicode characters
                    const fontUrl = 'https://pdf-lib.js.org/assets/ubuntu/Ubuntu-R.ttf';
                    const fontBytes = await fetch(fontUrl).then(res => res.arrayBuffer());
                    const customFont = await pdfDoc.embedFont(fontBytes);
                    const check = await fetch('./../assets/docx/checkmark.png').then(res => res.arrayBuffer());
                    const checkImage = await pdfDoc.embedPng(check);
                    if (cri_check == 1) {
                        secondPage.drawImage(checkImage, {
                            x: 493,
                            y: 495,
                            width: 25,
                            height: 15,
                        });
                    } else if (cri_check == 0) {
                        secondPage.drawImage(checkImage, {
                            x: 455,
                            y: 495,
                            width: 30,
                            height: 15,
                        });
                    }
                    thirdPage.drawText(interview_date, {
                        x: 195,
                        y: 155,
                        size: 11,
                        font
                    });
                    thirdPage.drawText(staff, {
                        x: 400,
                        y: 155,
                        size: 11,
                        font
                    });
                    thirdPage.drawText(staff, {
                        x: 100,
                        y: 115,
                        size: 45,
                        font: signatureFont
                    });
                    thirdPage.drawText('Recway AB', {
                        x: 400,
                        y: 120,
                        size: 11,
                        font
                    });
                    const pdfBytes = await pdfDoc.save();
                    const blob = new Blob([pdfBytes], {
                        type: 'application/pdf'
                    });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `Ellevio_${order_id}_${vasc_id ?? ''}_${name_ini.toUpperCase()}_${interview_date}.pdf`;
                    link.click();
                }
                //call history fetch
                fetchHistory();
            }
        });
    }
    async function show_activation_text() {
        const result = await Swal.fire({
            title: "Interview Report Upload",
            text: "Please report admin to activate this function for this candidate customer",
            icon: "warning",
            showCancelButton: true,
            cancelButtonText: "Close",
            showConfirmButton: false,
        });
        if (!result.isConfirmed) {
            return;
        }
    }
</script>
<script>
    async function deleteExisting(fileName, btn) {
        const result = await Swal.fire({
            title: "Delete File",
            text: "Are you sure you want to delete " + fileName + "?",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it",
            cancelButtonText: "Cancel"
        });
        if (result.isConfirmed) {
            $.ajax({
                type: 'POST',
                url: './includes/table_ajax.php',
                data: {
                    delete_cv: fileName,
                    can_id: $("input[name='can_id']").val()
                },
                success: function(response) {
                    response = JSON.parse(response);
                    if (response && response.deleted) {
                        var row = btn.closest("tr");
                        row.remove();
                        // alreadyUploaded -= 1;
                        renumberRows();
                        Swal.fire("Deleted!", fileName + " has been removed.", "success");
                    }
                }
            });
        }
    }
    var fileInput = document.getElementById("file_1");
    var cvTable = document.getElementById("cv_table");
    var cvTableBody = cvTable ? cvTable.querySelector("tbody") : null;
    var noCvText = document.getElementById("no_cv_text");
    var selectedFiles = [];
    fileInput.addEventListener("change", function() {
        if (noCvText) noCvText.classList.add("d-none");
        cvTable.classList.remove("d-none");
    });
    function renumberRows() {
        var rows = cvTableBody.querySelectorAll("tr");
        var i = 0;
        rows.forEach(function(row) {
            i = i + 1;
            row.cells[0].innerText = i;
        });
        if (i == 0) {
            if (noCvText) noCvText.classList.remove("d-none");
            cvTable.querySelector("thead").remove();
        }
    }
    $("#file_1").on("change", function() {
        var cvTableBody = $("#cv_table").find("tbody");
        var existingCount = cvTableBody.find("tr").length;
        var newFiles = this.files;
        if (existingCount + newFiles.length > 5) {
            Swal.fire("Limit Exceeded", "You can only have a maximum of 5 files total. You already have " + existingCount + ".", "warning");
            this.value = ""; // reset file selection
            return false;
        }
    });
</script>