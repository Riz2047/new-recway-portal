<?php

$activeLink = "candidates";

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

    flash("allCustomersChanged", "Customers have been changed!");
    redirect('candidates.php');

}

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

?>

            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Change Customer</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="col-md-12 mb-3" id="">
                                                <label class="form-label" for="">Customer</label>
                                                <select id="" name="customer" class="form-control">
                                                    <?php if(!empty($customers)): ?>
                                                        <?php foreach ($customers as $customer): ?>
                                                            <option value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>

                                                <?php if(isset($_POST['delete'])): ?>
                                                    <?php foreach ($_POST['delete'] as $can): ?>
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

include_once ('includes/footer.php');

?>