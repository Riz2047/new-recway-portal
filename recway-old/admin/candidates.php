<?php

include_once('includes/header.php');

$statuses = getStatuses();

if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $candidate = $stmt->fetch();

        $query = 'DELETE FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);

        $query = 'DELETE FROM emails WHERE email = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->email]);
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

if (isset($_GET['status'])) {
    $query = "SELECT * FROM candidates WHERE status = {$_GET['status']} AND expired = 0";

    if (isset($_GET['service']) && $_GET['service'] != 'all') {
        $query .= " AND interview_id IN (" . $services . ")";
    }

    $query .= " ORDER BY booked ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates WHERE expired = 0';

    if (isset($_GET['service']) && $_GET['service'] != 'all') {
        $query .= " AND interview_id IN (" . $services . ")";
    }

    $query .= "  ORDER BY booked ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
}

?>

<div class="row">
    <!--                    <input type="text" id="min" name="min">-->
    <!--                    <input type="text" id="max" name="max">-->
    <div class="col-lg-12 ">
        <div class="row buttons-row align-items-center">
            <style>
                .buttons2 {
                    flex-direction: row !important;
                }
            </style>
            <div class="main-heading col-9 ">
                <h1 class="f-14 my-4">Candidates</h1>
            </div>
            <div class="d-flex col-3 justify-content-end align-items-center buttons buttons2">
                <p class="f-16 mt-0 mb-0 ms-2 d-text text-white" style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                    <i class="bi bi-trash"></i>
                </p>
                <p class="f-16 mt-0 mb-0 ms-2 d-text2 text-white" style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                    <i class="bi bi-pen"></i>
                </p>
                <p class="f-16 mt-0 mb-0 ms-2 d-text3 text-white" style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                    <i class="bi bi-person"></i>
                </p>
                <div class="dropdown dropdown2 ">
                    <button class=" " onclick="window.location.href='add-candidate.php'" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-person-plus f-22"></i>
                    </button>
                </div>
            </div>
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-center buttons">
                <?php
                $pageTitle = "";
                $pageLink = "";
                include_once "buttons-row.php";
                ?>
            </div>
        </div>
        <div class="box shadow">
            <?php include_once "candidates-table.php" ?>
        </div>
    </div>

</div>

<?php

include_once('includes/footer.php');

?>