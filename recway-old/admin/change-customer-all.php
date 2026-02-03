<?php

include_once ('includes/header.php');

if(!isset($_POST['delete']) && !isset($_POST['update'])) {
    redirect('index.php');
}

if(isset($_POST['update'])) {
    $customer_id = $_POST['customer'];
    $candidates = $_POST['candidates'];

    foreach ($candidates as $key => $candidate) {

        $query = 'UPDATE candidates SET cus_id = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$customer_id, $candidate]);

//        $query = "INSERT INTO history (order_id, `desc`, comment) VALUES (?,?,?)";
//        $stmt = $conn->prepare($query);
//        $res = $stmt->execute([$candidate, "Staff ({$staff->name}) Assigned to {$can_name} {$can_surname}", $comment]);
    }

    redirect('candidates.php');

}

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between buttons-row">
                            <div class="main-heading  w-100">
                                <h1 class="f-14 my-4">Change Customer</h1>
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
                                <?php if($customers): ?>
                                    <div class="row p-0 m-0">
                                        <div class="col-lg-12 ps-0">
                                            <p class="f-14 mb-0 pb-0 w-500">Customer</p>
                                            <select class="form-select" name="customer" id="">
                                                <?php foreach ($customers as $customer): ?>
                                                    <option value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <?php if(isset($_POST['delete'])): ?>
                                            <?php foreach ($_POST['delete'] as $can): ?>
                                                <input type="hidden" name="candidates[]" value="<?php echo $can ?>">
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        <div class="col-lg-12 ps-0">
                                            <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <p class="alert alert-danger">No customer added yet!</p>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>