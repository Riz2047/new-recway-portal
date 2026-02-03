<?php

include_once ('includes/header.php');

if(!isset($_POST['delete']) && !isset($_POST['update'])) {
    redirect('index.php');
}

if(isset($_POST['update'])) {
    $staff_id = $_POST['staff'];
    $candidates = $_POST['candidates'];
    $comment = $_POST['comment'];

    $query = 'SELECT * FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$staff_id]);
    $staff = $stmt->fetch();

    foreach ($candidates as $key => $candidate) {
        $can_name = $_POST['can_name'][$key];
        $can_surname = $_POST['can_surname'][$key];

        $query = 'UPDATE candidates SET staff_id = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$staff_id, $candidate]);

        $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$candidate, "Staff ({$staff->name}) Assigned to {$can_name} {$can_surname}", $comment]);
    }

    redirect('candidates.php');

}

$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between buttons-row">
                            <div class="main-heading  w-100">
                                <h1 class="f-14 my-4">Change Staff</h1>
                            </div>
                            <div class="d-flex align-items-center buttons">
                                <a href="candidates.php?status=0" class="d-flex f-14 w-500 order"><i
                                            class="bi bi-file-earmark-text me-2"></i>Pending(<?php echo count(getStatusCard(0)) ?>)</a>
                                <a href="candidates.php" class="d-flex f-14 w-500 order"><i
                                            class="bi bi-file-earmark-text me-2"></i>All Orders</a>
                            </div>
                        </div>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <?php if($staff): ?>
                                    <div class="row p-0 m-0">
                                        <div class="col-lg-12 ps-0">
                                            <p class="f-14 mb-0 pb-0 w-500">Staff</p>
                                            <select class="form-select" name="staff" id="">
                                                <?php foreach ($staff as $st): ?>
                                                    <option value="<?php echo $st->id ?>"><?php echo $st->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-lg-12 mt-2 ps-0">
                                            <p class="m-0 p-0">Comment</p>
                                            <textarea class="sign-textarea w-100" name="comment" rows="3"></textarea>
                                        </div>

                                        <?php if(isset($_POST['delete'])): ?>
                                            <?php foreach ($_POST['delete'] as $can): ?>
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
                                        <div class="col-lg-12 ps-0">
                                            <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="alert alert-danger">No staff added yet!</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>