<?php
$query = "SELECT service_categories.* FROM service_categories LEFT JOIN interviews ON service_categories.id = interviews.service_cat_id LEFT JOIN candidates ON candidates.interview_id = interviews.id WHERE 0=0 " . $candidates_addition_query . " GROUP BY service_categories.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$serviceCats = $stmt->fetchAll();
$catIds = [];
?>
<style>
    .a_customize {
        font-size: 10px !important;
        font-weight: 800 !important;
        padding: 7px 6px;
    }
    .tab_card_customize {
        font-size: 15px !important;
        font-weight: 800 !important;
        padding: 20px 0px !important;
    }
    .arrow-down-green {
        width: 0;
        height: 0;
        border-left: 20px solid transparent;
        border-right: 20px solid transparent;
        border-top: 20px solid #9e9e9e;
        position: absolute;
        bottom: -15px;
        left: calc(50% - 10px);
    }
    .customize-warning {
        border-radius: 4px;
        font-size: 14px;
        padding: 7px 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white !important;
        outline: none;
        position: relative;
        border-radius: 0.5rem;
        user-select: none;
        color: #a5a5a5 !important;
        text-decoration: none;
    }
    .customize-warning .btn-number {
        background-color: #9e9e9e !important;
    }
    .customize-warning:hover {
        background-color: #9e9e9e !important;
        color: white !important;
    }
    .customize-warning:active {
        background-color: #9e9e9e !important;
        color: white !important;
    }
    .customize-warning:hover .btn-number {
        background-color: white !important;
        color: #9e9e9e !important;
    }
    .warning-card {
        border-bottom: 6px solid #9e9e9e !important;
    }
    .warning-card:hover {
        background-color: #9e9e9e !important;
        color: white;
    }
    .warning-card.active-service {
        background-color: #9e9e9e !important;
        color: white;
    }
    .cyan-card.active-service {
        background-color: #33b5e5 !important;
        color: white;
    }
    .purple-card.active-service {
        background-color: #4c6ef8 !important;
        color: white;
    }
    .arrow-down-warning {
        width: 0;
        height: 0;
        border-left: 20px solid transparent;
        border-right: 20px solid transparent;
        border-top: 20px solid #9e9e9e;
        position: absolute;
        bottom: -15px;
        left: calc(50% - 10px);
    }
    .cus-badge-warning {
        color: white;
        background-color: #9e9e9e;
    }
    .customize-info {
        border-radius: 4px;
        font-size: 14px;
        padding: 7px 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white !important;
        outline: none;
        position: relative;
        border-radius: 0.5rem;
        user-select: none;
        color: #a5a5a5 !important;
        text-decoration: none;
    }
    .customize-info .btn-number {
        background-color: #33b5e5 !important;
    }
    .customize-info:hover {
        background-color: #33b5e5 !important;
        color: white !important;
    }
    .customize-info:hover .btn-number {
        background-color: white !important;
        color: #33b5e5 !important;
    }
    .arrow-down-cyan {
        width: 0;
        height: 0;
        border-left: 20px solid transparent;
        border-right: 20px solid transparent;
        border-top: 20px solid #33b5e5;
        position: absolute;
        bottom: -15px;
        left: calc(50% - 10px);
    }
    .cyan-card {
        border-bottom: 6px solid #33b5e5;
    }
    .cyan-card:hover {
        background-color: #33b5e5;
        color: white;
    }
    .cyan-card:active {
        background-color: #33b5e5;
        color: white;
    }
    .customize-success {
        border-radius: 4px;
        font-size: 14px;
        padding: 7px 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        background-color: white !important;
        outline: none;
        position: relative;
        border-radius: 0.5rem;
        user-select: none;
        color: #a5a5a5 !important;
        text-decoration: none;
    }
    .customize-success .btn-number {
        background-color: #198754 !important;
    }
    .customize-success:hover {
        background-color: #198754 !important;
        color: white !important;
    }
    .customize-success:hover .btn-number {
        background-color: white !important;
        color: #198754 !important;
    }
    .arrow-down-success {
        width: 0;
        height: 0;
        border-left: 20px solid transparent;
        border-right: 20px solid transparent;
        border-top: 20px solid #198754;
        position: absolute;
        bottom: -15px;
        left: calc(50% - 10px);
    }
    .success-card {
        border-bottom: 6px solid #198754;
    }
    .success-card:hover {
        background-color: #198754;
        color: white;
    }
    .success-card:active {
        background-color: #198754;
        color: white;
    }
    .badge-success {
        color: white;
        background-color: #198754;
    }
</style>
<?php
$backgroundServiceId = 3;
if (function_exists('getStaffAllowedPermissions')) {
    getStaffAllowedPermissions();
}
$userCategory = $_SESSION['user_category'] ?? null;
$hasBackgroundPermission = function_exists('staffHasPermission') && staffHasPermission('view_background_orders');
$showOnlyBackground = ($userCategory == 5 && $hasBackgroundPermission);
?>
<div class="buttons-row">
    <div class="row justify-content-center">
        <div class=" col-lg-3 col-md-3 order-md-1 order-1  mb-3 ">
            <a href="candidates.php" class="tab-card yellow-card tab_card_customize rounded-card" <?php if (! isset($_GET['service'])) : ?> style="background-color:#ffbf43;color:white" <?php endif; ?>>
                All Orders
            </a>
            <?php if (! isset($_GET['service'])) : ?>
                <div class="arrow-down-yellow"></div>
            <?php endif; ?>
        </div>
        <?php if (! empty($serviceCats)) : ?>
            <?php foreach ($serviceCats as $key => $serviceCat) :
                // For background-only staff: show all services, but for non-background services only show "Booked" count
                if ($showOnlyBackground && $serviceCat->id != $backgroundServiceId) {
                    // For other services, only count "Booked" status
                    $new_orders = getBookedcount($serviceCat->id, $candidates_addition_query);
                    // Only show the service button if there are booked orders
                    if ($new_orders == 0) {
                        continue;
                    }
                } else {
                    // For Background Check or non-restricted users, use normal count
                    $new_orders = getBookedcount($serviceCat->id, $candidates_addition_query);
                }
                $catIds[] = $serviceCat->id;
                ?>
                <div class=" col-lg-3 col-md-3 order-md-1 order-3 mb-3  position-relative">
                    <a href="?service=<?php echo $serviceCat->id ?><?php echo isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" 
                    data-catid="<?php echo $serviceCat->id ?>" 
                    class="tab-card 
                        <?php if ($serviceCat->id == 1): ?>
                            warning-card
                        <?php elseif ($serviceCat->id == 3): ?>
                            purple-card
                        <?php elseif ($serviceCat->id == 9): ?>
                            cyan-card
                        <?php elseif ($serviceCat->id == 10): ?>
                            success-card
                        <?php endif; ?>
                            services-btns 
                        <?php echo isset($_GET['service']) && $_GET['service'] == $serviceCat->id ? 'active-service' : '' ?>
                            tab_card_customize rounded-card" >
                        <?php echo $serviceCat->name ?>
                        <?php $new_orders = getBookedcount($serviceCat->id, $candidates_addition_query); ?>
                        <span class="ml-3 badge badge-pill 
                            <?php if ($serviceCat->id == 1): ?>
                                cus-badge-warning
                            <?php elseif ($serviceCat->id == 3): ?>
                                badge-primary
                            <?php elseif ($serviceCat->id == 9): ?>
                                badge-info
                            <?php elseif ($serviceCat->id == 10): ?>
                                badge-success
                            <?php endif; ?>" style="">
                            <?= $new_orders ?>
                        </span>
                    </a>
                    <?php if (isset($_GET['service']) && $_GET['service'] == $serviceCat->id && $_GET['service'] == 1): ?>
                        <div class="arrow-down-warning"></div>
                        <?php elseif (isset($_GET['service']) && $_GET['service'] == $serviceCat->id && $_GET['service'] == 3): ?>
                            <div class="arrow-down-purple"></div>
                    <?php elseif (isset($_GET['service']) && $_GET['service'] == $serviceCat->id && $_GET['service'] == 9): ?>
                        <div class="arrow-down-cyan"></div>
                        <?php elseif (isset($_GET['service']) && $_GET['service'] == $serviceCat->id && $_GET['service'] == 10): ?>
                            <div class="arrow-down-success"></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="row justify-content-center">
        <div class="col-lg-12">
            <div class="btns-row my-4">
                <?php
                    $serviceCatId = $_GET['service'] ?? null;
$statuses2 = getStatusesByService($serviceCatId, $catIds)
?>
                <?php if (! empty($statuses2)) : ?>
                    <?php foreach ($statuses2 as $status) :
                        // For background-only staff: only show "Booked" status for non-background services
                        if ($showOnlyBackground && (int)$status->service_cat_id !== (int)$backgroundServiceId) {
                            // Only show "Booked" status for other services
                            $bookedStatusQuery = "SELECT id FROM statuses WHERE status LIKE 'Booked' AND status_type = ? LIMIT 1";
                            $bookedStmt = $conn->prepare($bookedStatusQuery);
                            $bookedStmt->execute([$status->service_cat_id]);
                            $bookedStatusId = $bookedStmt->fetchColumn();
                            if ((int)$status->sID !== (int)$bookedStatusId) {
                                continue; // Skip non-Booked statuses for non-background services
                            }
                        }
                        ?>
                        <span>
                            <a href="candidates.php?status=<?php echo $status->sID ?><?php echo isset($_GET['service']) ? '&service=' . $_GET['service'] : '' ?>"
                                class="
                                <?php if ($status->service_cat_id == 1): ?>
                                    customize-warning
                                <?php elseif ($status->service_cat_id == 3): ?>
                                    btn-primary
                                <?php elseif ($status->service_cat_id == 9): ?>
                                    btn-info customize-info 
                                <?php elseif ($status->service_cat_id == 10): ?>
                                    btn-success customize-success
                                <?php endif; ?>
                                    a_customize">
                                <?php echo $status->status ?>
                                <div class="btn-number" id="status_no_<?php echo $status->sID ?>"><?php echo count(getStatusCardofStaff($status->sID, $candidates_addition_query)) ?></div>
                            </a>
                        </span>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>