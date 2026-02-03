<?php

include_once ('includes/header.php');

if(isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'DELETE FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
    }
}

if(isset($_GET['service']) && $_GET['service'] != 'all') {
    $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['service']]);
    $services = $stmt->fetchAll();
    $services = array_column($services, 'id');
    $services = implode(",", $services);
}

$query = 'SELECT * FROM candidates WHERE expired = 0';

if(isset($_GET['service']) && $_GET['service'] != 'all') {
    $query .= " AND interview_id IN (" . $services . ")";
}

$query .= " ORDER BY booked ASC";

$stmt = $conn->prepare($query);
$stmt->execute();
$candidates = $stmt->fetchAll();

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll();

//function getStatusCard($status) {
//    global $conn;
//
//    $query = 'SELECT * FROM candidates WHERE status = ?';
//    $stmt = $conn->prepare($query);
//    $stmt->execute([$status]);
//    return $stmt->fetchAll();
//}

$statuses = getStatuses();


?>

<div class="row">
    <div class="col-lg-12">
        <div class="row buttons-row align-items-center">
            <?php
            $pageTitle = "Admin Dashboard";
            $pageLink = "add-admin.php";
            include_once "buttons-row.php";
            ?>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 col-md-6 mt-3">
        <a href="candidates.php" style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500">Total Orders</h1>
                        <h1 class="f-22 w-800"><?php echo !empty($candidates) ? count($candidates) : 0 ?></h1>
                    </div>
                    <div class="">
                        <i class="bi bi-clipboard-data f-40 "></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-4 col-md-6 mt-3">
        <a href="customers.php" style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500">Total Customers</h1>
                        <h1 class="f-22 w-800"><?php echo !empty($customers) ? count($customers) : 0 ?></h1>
                    </div>
                    <div class="">
                        <i class="bi bi-person f-40"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-4 col-md-6 mt-3">
        <a href="staff.php" style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500">Total Staff</h1>
                        <h1 class="f-22 w-800"><?php echo !empty($staff) ? count($staff) : 0 ?></h1>
                    </div>
                    <div class="">
                        <i class="bi bi-people f-40"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>

<!--    --><?php
//    foreach ($statuses as $key => $status):
//    $statusData = getStatusCard($key);
//    ?>
<!---->
<!--        <div class="col-lg-4 col-md-6 mt-3">-->
<!--            <a href="candidates.php?status=--><?php //echo $key ?><!--" style="text-decoration: none">-->
<!--                <div class="total-card shadow-sm">-->
<!--                    <div class="d-flex justify-content-between align-items-center">-->
<!--                        <div class="d-flex flex-column align-items-start">-->
<!--                            <h1 class="f-16 w-500">--><?php //echo $status ?><!--</h1>-->
<!--                            <h1 class="f-22 w-800">--><?php //echo !empty($statusData) ? count($statusData) : 0 ?><!--</h1>-->
<!--                        </div>-->
<!--                        <div class="">-->
<!--                            <i class="bi --><?php //echo $statusIcons[$key] ?><!-- f-40"></i>-->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
<!--            </a>-->
<!--        </div>-->
<!---->
<!--    --><?php //endforeach; ?>
</div>

<?php

if(isset($_GET['service']) && $_GET['service'] != 'all') {
    $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['service']]);
    $services = $stmt->fetchAll();
    $services = array_column($services, 'id');
    $services = implode(",", $services);
}

$query = 'SELECT * FROM candidates WHERE expired = 0';

if(isset($_GET['service']) && $_GET['service'] != 'all') {
    $query .= " AND interview_id IN (" . $services . ")";
}

$query .= "  ORDER BY booked ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$candidates = $stmt->fetchAll();

?>

                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading d-flex mb-2 align-items-center w-100">
                            <h1 class="mt-4">Candidates</h1>
                            <p class="f-16 mt-4 mb-0 ms-2 d-text text-white"
                               style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                <i class="bi bi-trash"></i></p>
                            <p class="f-16 mt-4 mb-0 ms-2 d-text2 text-white"
                               style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                <i class="bi bi-pen"></i></p>
                            <p class="f-16 mt-4 mb-0 ms-2 d-text3 text-white"
                               style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                <i class="bi bi-person"></i></p>
                        </div>
                        <div class="box shadow ">
                            <?php include_once "candidates-table.php" ?>
                        </div>
                    </div>

                </div>


<?php

include_once ('includes/footer.php');

?>
