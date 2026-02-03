<?php

$activeLink = "all-orders";

include_once('customer/includes/header.php');
//include_once ('includes/config.php');
// Create a DateTime object for Sweden's timezone
$swedenTimezone = new DateTimeZone('Europe/Stockholm');
$swedenTime = new DateTime('now', $swedenTimezone);
$currentTime = $swedenTime->format('H:i:s');
$dayOfWeek = date('N');

if (!isset($_GET['id'])) {
    redirect('orders.php');
}

if (isset($_POST['cancel'])) {
    $comment = $_POST['comment'];

    $query = 'SELECT * FROM candidates WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['id']]);
    $candidate = $stmt->fetch();

    $query = 'SELECT * FROM customers WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$candidate->cus_id]);
    $customer = $stmt->fetch();

    $query = 'SELECT * FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$candidate->staff_id]);
    $staff = $stmt->fetch();

    $query = 'SELECT * FROM interviews WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$candidate->interview_id]);
    $interview = $stmt->fetch();

    $status = getStatusById(9);

    $query = 'UPDATE candidates SET status = 9 WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['id']]);

    if (!empty($res)) {
        $comment .= '<br>-' . $_SESSION['customer']->name;
        $query = "INSERT INTO history (order_id, `desc`, date_time, comment) VALUES (?,?,?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$_GET['id'], $status->status_detail, date('Y-m-d H:i:s'), $comment]);
    }

    $messages = getMessages($candidate->cus_id, $interview->id);
    if (!empty($staff)) {
        $body = $messages->staff_cancel;
        $body = replace($body, $customer->name, $candidate->name . " " . $candidate->surname, $customer->company, $interview->title, $staff->name, '', '', '', '', $candidate->order_id);

        saveEmail("Staff", $staff->name, $candidate->order_id, 'Order Cancel Staff', $body, $staff->email, 'Order Canceled');
        sendMail($body, $staff->email, $staff->name, 'Order Canceled');
    }

    $body = $messages->can_cancel;
    $body = replace($body, $customer->name, $candidate->name, $customer->company, $interview->title, !empty($staff->name) ? $staff->name : '', '', '', '', '', $candidate->order_id);
    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
        saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled');
        sendMail($body, $candidate->email, $candidate->name, 'Order Canceled');
    } else {
        saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled', '1');
    }

    redirect('orders.php');
}

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Cancel Order</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form">
                    <?php echo isset($message) ? $message : '' ?>
                    <div class="form-tag mb-2">Comment</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Add a reason to cancel this orde</label>
                        <input type="text" name="comment" placeholder="Enter Comment" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-card-text"></i>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <?php if ($dayOfWeek >= 1 && $dayOfWeek <= 5) { ?>
                            <button type="submit" name="cancel" class="form-btn border-0">Cancel</button>
                        <?php } else { ?>
                            <button type="button" name="cancel" class="form-btn border-0" data-bs-toggle="modal" data-bs-target="#time_modal">Cancel</button>
                        <?php } ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<div class="modal fade" id="time_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title f-16 w-600 text-black" id="exampleModalLabel">Service Not Available</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>These services are only available from Monday to Fridat between 8am to 5pm. <br> <b> Thank you!!</b></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="form-btn" data-bs-dismiss="modal">Okay</button>
            </div>
        </div>
    </div>
</div>


<?php

include_once('customer/includes/footer.php');

?>