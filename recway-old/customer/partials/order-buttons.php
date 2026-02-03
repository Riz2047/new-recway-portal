<?php
$query = "SELECT service_categories.* FROM service_categories LEFT JOIN interviews ON service_categories.id = interviews.service_cat_id LEFT JOIN candidates ON candidates.interview_id = interviews.id WHERE candidates.cus_id = {$_SESSION['customer']->id} AND expired = 0 GROUP BY service_categories.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$serviceCats = $stmt->fetchAll();
?>
<div class="row px-4 pt-3 pb-2 d-flex">
    <div class="col-lg-3">
        <a href="orders.php" class="no-decoration text-black">
            <div class="text-center py-2 orders-btn">All Orders</div>
        </a>
    </div>
    <?php if (!empty($serviceCats)) { ?>
        <?php foreach ($serviceCats as $serviceCat) { ?>
            <div class="col-lg-3">
                <a href="orders.php?service=<?= $serviceCat->id ?>" class="no-decoration text-black">
                    <div class="text-center py-2 orders-btn"><?= $serviceCat->name ?></div>
                </a>
            </div>
        <?php } ?>
    <?php } ?>
</div>