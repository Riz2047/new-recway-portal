<?php

include_once "../includes/functions.php";

function getStatusCard($status) {
    global $conn;

    $query = 'SELECT * FROM candidates WHERE status = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

?>

<div class="d-flex align-items-center buttons">
    <a href="candidates.php?status=0" class="d-flex f-14 w-500 order"><i
            class="bi bi-file-earmark-text me-2"></i>Pending(<?php echo count(getStatusCard(0)) ?>)</a>
    <a href="candidates.php" class="d-flex f-14 w-500 order"><i
            class="bi bi-file-earmark-text me-2"></i>All Orders</a>
    <div class="dropdown dropdown2 ">
        <button class=" " type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                aria-expanded="false">
            <i class="bi bi-plus f-22"></i>
        </button>
        <ul class="dropdown-menu shadow-sm" aria-labelledby="dropdownMenuButton1">
            <li class="mb-2"><a class="dropdown-item f-14" href="add-staff.php"><i
                        class="bi bi-person-plus me-2 f-16 w-600"></i>Staff</a></li>
            <li class="mb-2"><a class="dropdown-item f-14" href="add-customer.php"><i
                        class="bi bi-person-plus w-600 me-2 f-16"></i>Customer</a></li>
        </ul>
    </div>
</div>
