<?php



$activeLink = "dashboard";



include_once('includes/header.php');



if (isset($_POST['delete'])) {

    foreach ($_POST['delete'] as $delete) {

        $query = 'DELETE FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);

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

// Data will be loaded via DataTables (server-side) like admin2
$candidates = [];

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



        <?php



        if (isset($_GET['service']) && $_GET['service'] != 'all') {

            $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';

            $stmt = $conn->prepare($query);

            $stmt->execute([$_GET['service']]);

            $services = $stmt->fetchAll();

            $services = array_column($services, 'id');

            $services = implode(",", $services);

        }


        // if (isset($_GET['status'])) {
        //     $query = "SELECT * FROM candidates WHERE status = {$_GET['status']} AND expired = 0 ". $candidates_addition_query;
        
        //     if (isset($_GET['service']) && $_GET['service'] != 'all') {
        //         $query .= " AND interview_id IN (" . $services . ")";
        //     }
        
        //     $query .= "  ORDER BY CASE
        //     WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end
        //     ELSE 0
        // END, booked ASC";
        //     $stmt = $conn->prepare($query);
        //     $stmt->execute();
        //     $candidates = $stmt->fetchAll();
        // } else {
        //     $query = 'SELECT * FROM candidates WHERE expired = 0 '. $candidates_addition_query;
        //     if (isset($_GET['service']) && $_GET['service'] != 'all') {
        //         $query .= " AND interview_id IN (" . $services . ")";
        //     }
        
        //     $query .= "  ORDER BY CASE
        //     WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end
        //     ELSE 0
        // END, booked ASC";
        //     $stmt = $conn->prepare($query);
        //     $stmt->execute();
        //     $candidates = $stmt->fetchAll();
        // }
        



        ?>



        <!-- table row -->

        <div class="row" id="candidates-wrapper">

            <div class="col-lg-12">

            </div>

            <div class="col-lg-12">

                <?php include_once "candidates-table.php" ?>

            </div>

            <div class="col-lg-12"></div>

        </div>

    </div>

</div>



<?php



include_once('includes/footer.php');



?>