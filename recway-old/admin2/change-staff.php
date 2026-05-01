<?php

$activeLink = "candidates";

include_once('includes/header.php');

if (! isset($_GET['id'])) {

    redirect('index.php');

}

if (isset($_POST['update'])) {

    $staff_id = $_POST['staff'];

    $can_name = $_POST['can_name'];

    $can_surname = $_POST['can_surname'];

    $comment = $_POST['comment'];

    $query = 'SELECT candidates.*,customers.name as cus_name,customers.company as cus_company,interviews.title as inter_title,places.name as place_name  FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id WHERE candidates.id = ?';

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$_GET['id']]);

    $can_info = $stmt->fetch();

    if (empty($can_info->staff_id)) {
        $last_candidate = 1;
    } else {
        $last_candidate = $can_info->staff_id;
    }

    $query = 'SELECT * FROM staff WHERE id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$staff_id]);

    $staff = $stmt->fetch();

    $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$staff_id, $_GET['id']]);

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

        if (isSwedenWorkingHours() == 1) {
            $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_POST['id'], "Staff ({$staff->name}) Assigned to {$candidate->name} {$candidate->surname}", $comment]);
        } else {
            $nextWorkingHour = getNextWorkingHour()->format('Y-m-d H:i:s');
            $query = "INSERT INTO history (order_id, `desc`,date_time, comment, staff_id) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_POST['id'], "Staff ({$staff->name}) Assigned to {$candidate->name} {$candidate->surname}",$nextWorkingHour, $comment,$last_candidate]);

        }

        $messages = getMessages($candidate->cus_id, $interview->id);

        $body = replace($messages->staff_msg, $_POST['cus_name'], $can_name . " " . $can_surname, $_POST['company'], $_POST['interview'], $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');

        //        $body .= "<br><b>Comment:</b> {$comment}<br><br>";

        // Create a DateTime object for Sweden's timezone

        $swedenTimezone = new DateTimeZone('Europe/Stockholm');

        $swedenTime = new DateTime('now', $swedenTimezone);

        $currentTime = $swedenTime->format('H:i:s');

        $dayOfWeek = date('N');

        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {

            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');

            sendMail($body, $staff->email, $staff->name, "Candidate Assigned");

        } else {

            saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned', '1');

        }

        flash("staffAssigned", "Staff assigned successfully!");

        redirect("invoice.php?id={$_GET['id']}");

    } else {

        flash("staffAssigned", "Could not assign staff!", "errorMsg");

    }

}

$query = 'SELECT * FROM candidates WHERE id = ?';

$stmt = $conn->prepare($query);

$stmt->execute([$_GET['id']]);

$candidate = $stmt->fetch();

$query = 'SELECT * FROM staff';

$stmt = $conn->prepare($query);

$stmt->execute();

$staff = $stmt->fetchAll();

$query = 'SELECT * FROM customers WHERE id = ?';

$stmt = $conn->prepare($query);

$stmt->execute([$candidate->cus_id]);

$customer = $stmt->fetch();

$query = 'SELECT * FROM interviews WHERE id = ?';

$stmt = $conn->prepare($query);

$stmt->execute([$candidate->interview_id]);

$interview = $stmt->fetch();

?>

<?php flash("staffAssigned"); ?>

<div class="mx-lg-4 main-content">

    <div class="container">



        <div class="row ">



            <div class="col-lg-12">

                <div class="table-section">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h1 class="main-heading">Change Staff</h1>

                    </div>



                    <form class="update-form" method="post">

                        <div class="col-md-12 mb-3" id="">

                            <label class="form-label" for="">Staff</label>

                            <select id="" name="staff" class="form-control">

                                <?php if (! empty($staff)) : ?>

                                    <?php foreach ($staff as $st) : ?>

                                        <option <?php echo ! empty($candidate->staff_id) && $candidate->staff_id == $st->id ? 'selected' : '' ?> value="<?php echo $st->id ?>"><?php echo $st->name ?></option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>



                            <div class="col-lg-12 mb-3">

                                <label class="form-label">Comment</label>

                                <textarea class="sign-textarea w-100" name="comment" rows="3"></textarea>

                            </div>



                            <input type="hidden" name="can_name" value="<?php echo $candidate->name ?>">

                            <input type="hidden" name="can_surname" value="<?php echo $candidate->surname ?>">

                            <input type="hidden" name="cus_name" value="<?php echo $customer->name ?>">

                            <input type="hidden" name="company" value="<?php echo $customer->company ?>">

                            <input type="hidden" name="interview" value="<?php echo $interview->title ?>">

                        </div>



                        <div class="d-flex justify-content-end">

                            <button type="submit" name="update" class="btn-primary bg-primary">Update</button>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

</div>

</div>



<?php

include_once('includes/footer.php');

?>