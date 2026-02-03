<?php



$activeLink = "candidates";



include_once('includes/header.php');



if (!isset($_POST['delete']) && !isset($_POST['update'])) {

    redirect('index.php');

}



if (isset($_POST['update'])) {

    $staff_id = $_POST['staff'];

    $candidates = $_POST['candidates'];

    $comment = $_POST['comment'];

    $comment .= '<br>-' . $_SESSION['staff']->name;

    $query = 'SELECT * FROM staff WHERE id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$staff_id]);

    $staff = $stmt->fetch();



    foreach ($candidates as $key => $candidate) {

        $can_name = $_POST['can_name'][$key];

        $can_surname = $_POST['can_surname'][$key];

        $query = 'SELECT candidates.*,customers.name as cus_name,customers.company as cus_company,interviews.title as inter_title,places.name as place_name  FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id WHERE candidates.id = ?';

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$candidate]);

        $can_info = $stmt->fetch();

        if(empty($can_info->staff_id)){
            $last_candidate = 1; 
        }else{
            $last_candidate = $can_info->staff_id; 
        }

        $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$staff_id, $candidate]);



        $query = 'SELECT candidates.*,customers.name as cus_name,customers.company as cus_company,interviews.title as inter_title,places.name as place_name  FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id WHERE candidates.id = ?';

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$candidate]);

        $can_info = $stmt->fetch();



        $query = 'SELECT staff_msg as msg FROM messages WHERE cus_id = ? AND interview_id = ?';

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$can_info->interview_id, $can_info->cus_id]);

        $staff_message = $stmt->fetch();

        if (empty($staff_message)) {

            $query = 'SELECT staff_msg as msg FROM messages WHERE cus_id = ? AND interview_id = ?';

            $stmt = $conn->prepare($query);

            $res = $stmt->execute([0, 0]);

            $staff_message = $stmt->fetch();

        }



        $body = replace($staff_message->msg, $can_info->cus_name, $can_name . " " . $can_surname, $can_info->cus_company, $can_info->inter_title, $staff->name, '', '', '', '', $can_info->order_id, '', '', $comment, $can_info->vasc_id, $can_info->inter_title, !empty($can_info->place) ? $can_info->place_name : '');



        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {

            saveEmail("Staff", $staff->name, $can_info->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');

            sendMail($body, $staff->email, $staff->name, "Candidate Assigned");

        } else {

            saveEmail("Staff", $staff->name, $can_info->order_id, "Staff Message", $body, $staff->email, "Candidate Assigned");

        }




        if (isSwedenWorkingHours() == 1) {
            $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$candidate, "Staff ({$staff->name}) Assigned to {$can_name} {$can_surname}", $comment]); 
        }else{
            $nextWorkingHour = getNextWorkingHour()->format('Y-m-d H:i:s');
            $query = "INSERT INTO history (order_id, `desc`,date_time, comment, staff_id) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$candidate, "Staff ({$staff->name}) Assigned to {$can_name} {$can_surname}",$nextWorkingHour, $comment,$last_candidate]);
            
        }

    }



    flash("allStaffChanged", "Staff has been changed!");

    redirect('candidates.php');

}



$query = 'SELECT * FROM staff';

$stmt = $conn->prepare($query);

$stmt->execute();

$staff = $stmt->fetchAll();



?>



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

                                <?php if (!empty($staff)) : ?>

                                    <?php foreach ($staff as $st) : ?>

                                        <option value="<?php echo $st->id ?>"><?php echo $st->name ?></option>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>



                            <div class="col-lg-12 mb-3 p-0">

                                <label class="form-label">Comment</label>

                                <textarea class="sign-textarea w-100" name="comment" rows="3"></textarea>

                            </div>



                            <?php if (isset($_POST['delete'])) : ?>

                                <?php foreach ($_POST['delete'] as $can) : ?>

                                    <?php

                                    $query = 'SELECT * FROM candidates WHERE id = ?';

                                    $stmt = $conn->prepare($query);

                                    $stmt->execute([$can]);

                                    $candidate = $stmt->fetch();

                                    ?>

                                    <input type="hidden" name="can_name[]" value="<?php echo $candidate->name ?>">

                                    <input type="hidden" name="can_surname[]" value="<?php echo $candidate->surname ?>">

                                    <input type="hidden" name="candidates[]" value="<?php echo $can ?>">

                                <?php endforeach; ?>

                            <?php endif; ?>

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