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
    <div class="dropdown dropdown2 p-0 m-0 ">
        <button onclick="location.href='add-admin.php'" class="p-0 m-0 " type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown"
                aria-expanded="false">
            <i class="bi bi-plus f-22"></i>
        </button>
    </div>
</div>
