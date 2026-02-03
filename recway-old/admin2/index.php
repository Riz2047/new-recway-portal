<?php



$activeLink = "dashboard";



include_once('includes/header.php');



if (isset($_POST['delete'])) {

    foreach ($_POST['delete'] as $delete) {

        $query = 'SELECT * FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);

        $candidate = $stmt->fetch();



        $query = 'DELETE FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);



        $query = 'DELETE FROM emails WHERE order_id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$candidate->order_id]);

    }

}



if (isset($_GET['service']) && $_GET['service'] != 'all') {

    $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$_GET['service']]);

    $services = $stmt->fetchAll();

    $services = array_column($services, 'id');

    $services = implode(",", $services);
}

// Candidates data will be loaded via AJAX in the DataTable
$candidates = []; // Empty array for compatibility

$query = 'SELECT * FROM customers';

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();



$query = 'SELECT * FROM staff';

$stmt = $conn->prepare($query);

$stmt->execute();

$staff = $stmt->fetchAll();



$statuses = getStatuses();



?>

<div class="mx-lg-4 main-content">

    <div class="container">

        <?php include_once "buttons-row.php" ?>



        <div class="row justify-content-center">



            <div class=" col-lg-4 col-md-6 mt-3">

                <a href="candidates.php">

                    <div class="total-card shadow-sm">

                        <div class="d-flex justify-content-between align-items-center" style="height:40px !important">

                            <div class="d-flex flex-column align-items-start">

                                <h1 class="text-lg">Total Orders</h1>
                                <h1 class="text-numer mb-0 pb-0" style="font-size:20px !important" id="total-orders">Loading...</h1>
                            </div>

                            <div class="icon-card">

                                <i class="bi bi-clipboard-data "></i>

                            </div>

                        </div>

                    </div>

                </a>

            </div>



            <div class=" col-lg-4 col-md-6 mt-3">

                <a href="customers.php">

                    <div class="total-card shadow-sm">

                        <div class="d-flex justify-content-between align-items-center" style="height:40px !important">

                            <div class="d-flex flex-column align-items-start">

                                <h1 class="text-lg">Total Customers</h1>

                                <h1 class="text-numer mb-0 pb-0" style="font-size:20px !important"><?php echo !empty($customers) ? count($customers) : 0 ?></h1>

                            </div>

                            <div class="icon-card">

                                <i class="bi bi-person  "></i>

                            </div>

                        </div>

                    </div>

                </a>

            </div>



            <div class=" col-lg-4 col-md-6 mt-3">

                <a href="staff.php">

                    <div class="total-card shadow-sm">

                        <div class="d-flex justify-content-between align-items-center" style="height:40px !important">

                            <div class="d-flex flex-column align-items-start">

                                <h1 class="text-lg">Total Staff</h1>

                                <h1 class="text-numer mb-0 pb-0" style="font-size:20px !important"><?php echo !empty($staff) ? count($staff) : 0 ?></h1>

                            </div>

                            <div class="icon-card">

                                <i class="bi bi-people"></i>

                            </div>

                        </div>

                    </div>

                </a>

            </div>

        </div>



        <?php
        // Candidates data is now loaded via AJAX in the DataTable
        // No need for static queries here
    ?>
        <!-- table row -->

        <div class="row mt-5">

            <div class="col-lg-12">

            </div>

            <div class="col-lg-12">

                <?php include_once "candidates-table.php" ?>

            </div>

        </div>

    </div>

</div>



<?php



include_once('includes/footer.php');



?>