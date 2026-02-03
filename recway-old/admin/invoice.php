<?php

include_once('includes/header.php');

if (!isset($_GET['id'])) {
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

    if (!empty($res)) {
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
        $body = replace($messages->staff_msg, $_POST['cus_name'], $can_name . " " . $candidate->surname, $_POST['cus_company'], $_POST['interview'], $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
        //        $body .= "<br><b>Comment:</b> {$comment}<br><br>";

        saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');
        echo sendMail($body, $staff->email, $staff->name, "Candidate Assigned");

        $message = "<p class='alert alert-success'>Staff Assigned!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not assign!</p>";
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

    if (!empty($res)) {
        $commentMessage = "<p class='alert alert-success'>Comment added successfully!</p>";
    } else {
        $commentMessage = "<p class='alert alert-danger'>Could not add comment!</p>";
    }
}

$query = 'SELECT * FROM comments WHERE order_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->id]);
$comments = $stmt->fetchAll();

$query = 'SELECT * FROM places WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->place]);
$place = $stmt->fetch();

$query = 'SELECT * FROM uploaded_pdf_candidate WHERE can_id = ? AND is_trash = 0';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$uploaded_pdf = $stmt->fetchAll();

?>

<?php echo !empty($emailMsg) ? '<div id="success-alert" style="position: fixed; bottom: 0; right: 20px; z-index: 1" class="alert alert-info" role="alert">
    ' . $emailMsg . '
</div>' : '' ?>

<div class="row">
    <div class="col-lg-12 ">
        <div class="main-heading  w-100">

            <div class="">
                <!--                                <h1 class="  mb-0 pb-0 mt-4">Candidate `s Information</h1>-->

                <div class="col-12 mt-3 d-flex flex-wrap align-items-center justify-content-center buttons">
                    <?php
                    $pageTitle = "Candidate `s Information";
                    $pageLink = "";
                    include_once "buttons-row.php";
                    ?>
                </div>

                <div class="d-flex justify-content-between">
                    <div class="d-flex align-items-center mt-2">
                        <p class="f-12 mb-0">Order: <?php echo $candidate->order_id ?></p>
                        <?php $status = getStatusById($candidate->status) ?>
                        <p class="f-12 mb-0 ms-3">Status:&nbsp;
                        <div class="text-white f-12 text-center" style="background-color: <?php echo $status->color ?>; padding: 5px 8px; border-radius: 20px"><?php echo $status->status ?></div>
                        </p>
                        <a style="color: var(--black);" data-toggle="tooltip" data-placement="top" title="Change Status" href="update-status.php?id=<?php echo $candidate->id ?>" class="mx-1"><i class="bi bi-pencil-square"></i></a>
                        <a style="font-size: 18px;color: var(--black);" data-toggle="tooltip" data-placement="top" title="Update Candidate" href="update-candidate.php?id=<?php echo $candidate->id ?>" class=""><i class="bi bi-person-gear"></i></a>
                    </div>
                    <div style="font-size: 14px">
                        <?php if (isset($_GET['status'])) : ?>
                            <?php echo !empty($candidatePrev) ? '<a class="w-500 me-2" href="invoice.php?id=' . $candidatePrev->id . '&status=' . $_GET['status'] . '"><i class="bi bi-arrow-left-short"></i> Previous</a>' : '' ?>
                            <?php echo !empty($candidateNext) ? '<a class="w-500" href="invoice.php?id=' . $candidateNext->id . '&status=' . $_GET['status'] . '">Next <i class="bi bi-arrow-right-short"></i></a>' : '' ?>
                        <?php else : ?>
                            <?php echo !empty($candidatePrev) ? '<a class="w-500 me-2" href="invoice.php?id=' . $candidatePrev->id . '"><i class="bi bi-arrow-left-short"></i> Previous</a>' : '' ?>
                            <?php echo !empty($candidateNext) ? '<a class="w-500" href="invoice.php?id=' . $candidateNext->id . '">Next <i class="bi bi-arrow-right-short"></i></a>' : '' ?>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="box shadow px-4 mt-2">
                    <p class="text-center text-primary fw-bold"><?php echo "Candidate " . $currentIndex + 1 . " of " . count($candidates) ?></p>

                    <div class="row p-2 w-600 mt-3 bg-light">
                        PROFILE
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Service Type</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <?php
                            $query = 'SELECT * FROM interviews WHERE id = ?';
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$candidate->interview_id]);
                            $interview = $stmt->fetch();
                            ?>
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $interview->title ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Interview Date</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo !empty($candidate->booked) ? $candidate->booked : 'Null' ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Full Name</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->name . " " . $candidate->surname ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">VASC ID</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo !empty($candidate->vasc_id) ? $candidate->vasc_id : "Null" ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Social Security Number</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->security ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Email</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->email ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Phone Number</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->phone ?></p>
                        </div>
                    </div>
                    <?php if ($interview->id == 2 || $interview->id == 4 || $interview->id == 26) : ?>
                        <div class="row border-bottom ">
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3">Place</p>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3"><?php echo !empty($place) ? $place->name : "Null" ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Company</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->company ?></p>
                        </div>
                    </div>
                    <?php if (!empty($candidate->country)) : ?>
                        <div class="row border-bottom ">
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3">Country</p>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->country ?></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Documents</p>
                        </div>
                        <!--                                        <div class="col-lg-6 col-md-6 col-12">-->
                        <!--                                            <p class="mb-0 f-18 px-2 py-3"><a --><?php //echo empty($candidate->cv) ? 'style="pointer-events:none; text-decoration:line-through" class="text-danger"' : 'style="cursor:pointer"' 
                                                                                                                ?><!-- --><?php //echo !empty($candidate->cv) ? "data-value='{$candidate->cv}'" : '' 
                                                                                                                            ?><!-- class="text-success" id="downloadZip">Download</a></p>-->
                        <!--                                        </div>-->
                        <div class="col-lg-6 col-md-6 col-12">
                            <?php if (!empty($candidate->cv)) :
                                $documents = explode(',', $candidate->cv);
                            ?>

                                <?php foreach ($documents as $document) : ?>
                                    <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../uploads/<?php echo $document ?>" style="cursor: pointer" class="text-success"><?php echo $document ?></a></p>
                                <?php endforeach; ?>

                            <?php else : ?>
                                <p class="mb-0 w-100 f-18 px-2 py-3">No Document</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (!empty($candidate->report)) : ?>
                        <div class="row border-bottom ">
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3">Background Check Report</p>
                            </div>
                            <div class="col-lg-6 col-md-6 col-12">
                                <p class="mb-0 f-18 px-2 py-3"><a target="_blank" href="../report-uploads/<?php echo $candidate->report ?>">Download</a></p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="row border-bottom ">
                        <?php
                        if ($candidate->staff_id != 0) {
                            $query = 'SELECT * FROM staff WHERE id = ?';
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$candidate->staff_id]);
                            $staff = $stmt->fetch();
                        }
                        ?>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Staff</p>
                        </div>
                        <div class="col-lg-6 d-flex col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo isset($staff) ? $staff->name : "Null" ?></p>
                            <?php echo !empty($staff) ? "<p class='mb-0 f-18 px-2 py-3'><a href='invoice.php?id={$_GET['id']}&a=unassign'>Unassign</a></p>" : '' ?>
                        </div>
                    </div>


                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Billing Details
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Invoice Recipient</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><a href="update-customer.php?id=<?php echo $customer->id ?>"><?php echo $candidate->referensperson ?></a></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Invoice Reference</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->reference ?></p>
                        </div>
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3">Invoice Comment</p>
                        </div>
                        <div class="col-lg-6 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->comment ?></p>
                        </div>
                    </div>

                    <?php if (!empty($uploaded_pdf)) { ?>
                        <div class="row p-2 w-600 mt-3 bg-light mt-5">
                            Deviation PDF
                        </div>
                        <?php foreach ($uploaded_pdf as $upload_pdf) { ?>
                            <div class="row border-bottom ">
                                <div class="col-lg-4 col-md-4 col-12">
                                    <p class="mb-0 f-18 px-2 py-3">
                                        <?php if ($upload_pdf->file_for == 1) { ?>
                                            Economic
                                        <?php } ?>
                                        <?php if ($upload_pdf->file_for == 2) { ?>
                                            Criminal Record
                                        <?php } ?></p>
                                </div>
                                <div class="col-lg-4 col-md-6 col-12">
                                    <p class="mb-0 f-18 px-2 py-3"><?= $upload_pdf->file_name ?></p>
                                </div>
                                <div class="col-lg-4 col-md-6 col-12">
                                    <a href="../uploads/<?= $upload_pdf->file_name ?>" target="_blank" class="btn btn-primary mt-2">Preview</a>
                                </div>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <div id="assign-staff" class="row p-2 w-600 mt-3 bg-light mt-5">
                        Additional Note
                    </div>
                    <div class="row border-bottom ">
                        <div class="col-lg-12 col-md-6 col-12">
                            <p class="mb-0 f-18 px-2 py-3"><?php echo $candidate->note ?></p>
                        </div>
                    </div>

                    <?php

                    $query = 'SELECT * FROM staff';
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $staff = $stmt->fetchAll();
                    ?>

                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Assign Staff
                    </div>
                    <div class="row border-bottom">
                        <?php echo isset($message) ? '<span class="mt-2"></span>' : '' ?>
                        <?php echo isset($message) ? $message : '' ?>
                        <form action="invoice.php?id=<?php echo $_GET['id'] ?>#assign-staff" method="post">
                            <?php if ($staff) : ?>
                                <div class="row p-0 m-0 mt-3 mb-4">
                                    <div class="col-lg-12 ps-0">
                                        <!--                                                        <p class="f-14 mb-0 pb-0 w-500">Staff</p>-->
                                        <select class="form-select" name="staff" id="">
                                            <?php foreach ($staff as $st) : ?>
                                                <option <?php echo $st->id == $candidate->staff_id ? 'selected' : '' ?> value="<?php echo $st->id ?>"><?php echo $st->name ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-lg-12 mt-2 ps-0">
                                        <p class="m-0 p-0">Comment</p>
                                        <textarea class="sign-textarea w-100" name="comment" rows="3"></textarea>
                                    </div>

                                    <input type="hidden" name="can_name" value="<?php echo $candidate->name ?>">
                                    <input type="hidden" name="cus_name" value="<?php echo $customer->name ?>">
                                    <input type="hidden" name="cus_company" value="<?php echo $customer->company ?>">
                                    <input type="hidden" name="interview" value="<?php echo $interview->title ?>">

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            <?php else : ?>
                                <p class="alert alert-danger">No staff added yet!</p>
                            <?php endif; ?>
                        </form>
                    </div>

                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Order History
                    </div>
                    <div class="row border-bottom">
                        <div class="col-lg-12 ">
                            <div class="timeline-container px-2 py-3">
                                <div class="timeline-wrapper">
                                    <ul class="sessions">
                                        <?php if ($history) : ?>
                                            <?php foreach ($history as $h) : ?>

                                                <li>
                                                    <div class="time"><?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?></div>
                                                    <p class="m-0 p-0"><?php echo $h->desc ?></p>
                                                    <i><small class="m-0 p-0"><?php echo !empty($h->comment) ? 'Comment: ' . $h->comment : '' ?></small></i>
                                                </li>

                                            <?php endforeach; ?>
                                        <?php else : ?>

                                            <li>
                                                <div class="time"><?php echo "No record found" ?></div>
                                            </li>

                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Add Comment
                    </div>
                    <div class="row border-bottom">
                        <?php echo isset($commentMessage) ? '<span class="mt-2"></span>' : '' ?>
                        <?php echo isset($commentMessage) ? $commentMessage : '' ?>
                        <form action="" method="post">
                            <div class="row p-0 m-0">
                                <div class="col-lg-12 ps-0">
                                    <p class="f-14 mb-0 pb-0 w-500">Comment</p>
                                    <textarea name="comment" id="" rows="3" class="w-100 sign-textarea"></textarea>
                                </div>

                                <div class="col-lg-12 ps-0">
                                    <button type="submit" name="submit" class="btn-fill w-100 mt-4"><a>Submit</a></button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Comments
                    </div>
                    <div class="row border-bottom">
                        <div class="col-lg-12 mb-3">
                            <?php if (!empty($comments)) : ?>
                                <?php foreach ($comments as $comment) :
                                    $query = 'SELECT * FROM ' . $comment->author_type . ' WHERE id = ?';
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute([$comment->author_id]);
                                    $author = $stmt->fetch();
                                ?>
                                    <div class="mt-2 bg-light p-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="p-0 m-0 w-600">~<?php echo $author->name ?></small>
                                            <p class="m-0 p-0">
                                                <a href="edit-comment.php?oid=<?php echo $_GET['id'] ?>&cid=<?php echo $comment->id ?>"><i class="bi bi-pen"></i></a>
                                                <a href="<?php echo $_SERVER['PHP_SELF'] . "?" . $_SERVER['QUERY_STRING'] ?>&cid=<?php echo $comment->id ?>"><i class="bi bi-trash"></i></a>
                                            </p>
                                        </div>
                                        <p class="p-0 m-0"><?php echo $comment->comment ?></p>
                                        <p class="m-0 p-0 w-600" style="text-align: right; font-size: 12px"><?php echo date("M d, Y h:i A", strtotime($comment->created)) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <div class="mt-2">
                                    <p>No comments yet</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="row p-2 w-600 mt-3 bg-light mt-5">
                        Emails
                    </div>
                    <div class="data-table staff-table mt-3">
                        <form action="" method="post" id="d-form">
                            <table id="dataTable" class="table" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Email Type</th>
                                        <th>Email</th>
                                        <th>Date</th>
                                        <th>Text</th>
                                        <th class="d-none"></th>
                                        <th class="d-none"></th>
                                        <th class="d-none"></th>
                                        <th class="d-none"></th>
                                        <th class="dt-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($emails)) : ?>
                                        <?php $count = 0; ?>
                                        <?php foreach ($emails as $email) : ?>
                                            <?php if ($email->user_type == "Candidate") : ?>
                                                <?php
                                                $query = 'SELECT * FROM candidates WHERE order_id = ?';
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([$email->order_id]);
                                                $candidate = $stmt->fetch();
                                                ?>

                                                <tr>
                                                    <td><?php echo $email->order_id ?></td>
                                                    <td><?php echo $email->msg_type ?></td>
                                                    <td><?php echo $email->email ?></td>
                                                    <td><?php echo $email->created ?></td>
                                                    <td><textarea name="text[]" class="sign-textarea" rows="3"><?php echo $email->text ?></textarea></td>
                                                    <td class="d-none"><input type="text" name="user_type[]" value='<?php echo $email->user_type ?>'></td>
                                                    <td class="d-none"><input type="text" name="order_id[]" value='<?php echo $email->order_id ?>'></td>
                                                    <td class="d-none"><input type="text" name="msg_type[]" value='<?php echo $email->msg_type ?>'></td>
                                                    <td class="d-none"><input type="text" name="name[]" value='<?php echo $email->user_name ?>'></td>
                                                    <td class="d-none"><input type="text" name="email[]" value="<?php echo $email->email ?>"></td>
                                                    <td class="d-none"><input type="text" name="subject[]" value="<?php echo $email->subject ?>"></td>
                                                    <td class="d-none"><input type="text" name="count" value="<?php echo $count ?>"></td>
                                                    <td class="text-center dt-center">
                                                        <button name="resend" value="<?php echo $count ?>" class="btn text-dark-blue">Resend</button>
                                                        <?php $count++; ?>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>

                            </table>
                        </form>
                    </div>
                </div>
            </div>


        </div>

    </div>

    <?php

    include_once('includes/footer.php');

    ?>

    <script>
        $(document).ready(function() {
            $('#downloadZip').click(function() {
                $.ajax({
                    url: '../includes/ajax.php',
                    type: 'post',
                    data: {
                        'zip': true,
                        'files': $(this).attr('data-value')
                    },
                    success: function(response) {
                        console.log(response)
                        // window.location = response;
                        window.open(response, '_blank');
                    }
                });
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            if (localStorage) {
                var posReader = localStorage["posStorage"];
                if (posReader) {
                    $('.layout').scrollTop(posReader);
                    localStorage.removeItem("posStorage");
                }
            }

            $('.layout').scroll(function(e) {
                localStorage["posStorage"] = $(this).scrollTop();
            })

            $("#success-alert").fadeTo(2000, 500).slideUp(500, function() {
                $("#success-alert").slideUp(500);
            });
        })
    </script>