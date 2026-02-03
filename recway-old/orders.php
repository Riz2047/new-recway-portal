<?php

$activeLink = "all-orders";

include_once "customer/includes/header.php";

$groups = findAllByQuery("SELECT groups FROM customers WHERE id = {$_SESSION['customer']->id}");
$where = "";
$gr_ids = array();
$group_ids = null;
if (isset($groups[0]->groups) && !empty($groups[0]->groups)) {
    $gr_arrs = explode(',', $groups[0]->groups);
    foreach ($gr_arrs as $group) {
        $query = "SELECT id FROM customers WHERE groups LIKE '%" . $group . "%'";
        $group_ids = findAllByQuery($query);

        if (!empty($group_ids)) {
            foreach ($group_ids as $g_id) {
                $gr_ids[] = $g_id->id;
            }
        }
    }

    if (!empty($gr_ids)) {
        $where = "cus_id IN (" . implode(", ", $gr_ids) . ")";
    } else {
        $where = "cus_id = {$_SESSION['customer']->id}";
    }
} else {
    $where = "cus_id = {$_SESSION['customer']->id}";
}

if (isset($_GET['status'])) {
    $candidates = findAllByQuery("SELECT * FROM candidates WHERE $where AND status = {$_GET['status']}  AND expired = 0 ORDER BY created DESC");
} else if (isset($_GET['service'])) {
    $candidates = findAllByQuery("SELECT candidates.* FROM candidates LEFT JOIN interviews ON interviews.id = candidates.interview_id WHERE $where AND interviews.service_cat_id = {$_GET['service']}  AND expired = 0 ORDER BY created DESC");
} else {
    $candidates = findAllByQuery("SELECT * FROM candidates WHERE $where AND expired = 0 ORDER BY created DESC");
}

?>
<style>
    /* Tooltip text */
    .his_tooltiptext {
        visibility: hidden;
        background-color: #f9f9f9;
        width: 300px;
        color: #545454;
        text-align: center;
        border-radius: 6px;
        padding: 5px 0;
        white-space: normal;
        max-height: 80%;
        min-height: auto;
        overflow-y: auto;

        /* Position the tooltip text */
        position: fixed;
        z-index: 1;
        top: 10%;
        left: 80%;
        margin-left: 8px;

        /* Fade in tooltip */
        border: 2px solid #b4b4b4;
        box-shadow: -3px 3px 20px 0px rgb(0 0 0 / 24%);
        opacity: 0;
        transition: opacity 0.3s, transform 0.3s;
        transform: translateX(100%);
    }

    .table-div {
        overflow-y: hidden !important;
    }
</style>
<?php include_once "customer/partials/order-buttons.php" ?>
<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">List of All Orders</p>
            <div class="col-lg-12">
                <div class="table-div p-2">
                    <table id="myTable2" class="display Table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Candidate</th>
                                <th>Status</th>
                                <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                    <th>Background Check</th>
                                <?php endif; ?>
                                <?php if (!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID) : ?>
                                    <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                        <th>Interview Date</th>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <th>Service Type</th>
                                <?php if (in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                    <th>Delivery Date</th>
                                <?php endif; ?>
                                <th>Staff</th>
                                <th class="dt-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($candidates)) : ?>
                                <?php foreach ($candidates as $candidate) : ?>
                                    <?php
                                    $query = 'SELECT * FROM interviews WHERE id = ?';
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute([$candidate->interview_id]);
                                    $interview = $stmt->fetch();

                                    $query = 'SELECT * FROM staff WHERE id = ?';
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute([$candidate->staff_id]);
                                    $staff = $stmt->fetch();
                                    ?>
                                    <tr>
                                        <td><?php echo $candidate->order_id ?></td>
                                        <td class="his_tooltip" data-tool-id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseleave="tooltipLeave(this);" onmouseenter="tooltipHover(this)"><a style="text-decoration: none; color: var(--black)" href="invoice.php?id=<?php echo $candidate->id ?>"><?php echo $candidate->name . " " . $candidate->surname ?></a></td>
                                        <?php $status = getStatusById($candidate->status) ?>
                                        <td class="text-nowrap"><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                                        <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                            <td class="background_check_date"><?php echo !empty($candidate->background_check_date) ? $candidate->background_check_date : 'Null' ?></td>
                                        <?php endif; ?>
                                        <?php if (!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID) : ?>
                                            <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                                <td><?php echo !empty($candidate->booked) ? $candidate->booked : "Null" ?></td>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <td><?php echo $interview->title ?></td>
                                        <?php if (in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['customer']->id))) : ?>
                                            <td><?php echo !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null" ?></td>
                                        <?php endif; ?>
                                        <td><?php echo !empty($staff) ? $staff->name : "Not Assigned" ?></td>
                                        <td class="text-center dt-center">
                                            <div class="dropdown">
                                                <button class="table-menu-btn mx-auto" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                    <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="invoice.php?id=<?php echo $candidate->id ?>"><i class="bi bi-eye text-black f-14 me-2"></i>View</a></li>
                                                    <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="update-candidate.php?id=<?php echo $candidate->id ?>"><i class="bi bi-pen text-black f-14 me-2"></i>
                                                            Edit</a></li>
                                                    <li class="" <?php echo $candidate->status == 7 ? 'style="pointer-events: none;"' : '' ?>><a class="no-decoration f-14 w-600 text-black " href="cancel.php?<?php echo isset($_GET['status']) ? 'status=' . $_GET['status'] . '&id=' . $candidate->id : 'id=' . $candidate->id ?>" class="mx-1" <?php echo $candidate->status == 7 ? 'style="color:#bebebe;"' : '' ?>><i class="bi bi-x-circle text-black f-14 me-2"></i>Cancel</a>
                                                    </li>
                                                </ul>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if (!empty($candidates)) : ?>
                        <?php foreach ($candidates as $key => $candidate) : ?>
                            <?php
                            $query = "SELECT * FROM history WHERE order_id = {$candidate->id}";
                            $stmt = $conn->prepare($query);
                            $stmt->execute();
                            $history = $stmt->fetchAll();
                            ?>
                            <span class="his_tooltiptext text-left pl-4 pr-3 pt-2 pb-2" id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseleave="tooltiphide(this)">
                                <h5><b><u>Order History</h5>
                                <?php if (!empty($history)) : ?>
                                    <?php foreach ($history as $h) : ?>
                                        <div class="mt-3 mb-3">
                                            <div class="time"><?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?></div>
                                            <p class="m-0"><?php echo $h->desc ?>
                                            </p>
                                            <i><small class="m-0 p-0"><?php echo !empty($h->comment) ? 'Comment: ' . $h->comment : '' ?></small></i>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </span>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>
<script>
    var tooltipTimer;

    function tooltipHover(obj) {
        $('.his_tooltiptext').css('transform', 'translateX(100%)')
        $('.his_tooltiptext').css('visibility', 'hidden').animate({
            opacity: 0
        });
        var id = $(obj).data('tool-id');
        clearTimeout(tooltipTimer);
        $('#' + id).css('visibility', 'visible').animate({
            opacity: 1
        }, {
            duration: 1000,
            step: function(now, fx) {
                $(this).css('transform', 'translateX(' + (1 - now) * 100 + '%)');
            }
        });
    }

    function tooltiphide(obj) {
        $(obj).css('transform', 'translateX(100%)')
        $(obj).css('visibility', 'hidden').animate({
            opacity: 0
        });
    }

    function tooltipOver(obj) {
        clearTimeout(tooltipTimer);
    }

    function tooltipLeave(obj) {
        var id = $(obj).data('tool-id');
        var tooltip = $('#' + id);
        tooltipTimer = setTimeout(function() {
            if (!tooltip.is(':hover') && !$(obj).is(':hover')) {
                tooltip.animate({
                    opacity: 0
                }, {
                    duration: 1000,
                    step: function(now, fx) {
                        $(this).css('transform', 'translateX(' + (now * 100) + '%)');
                    },
                    complete: function() {
                        $(this).css('visibility', 'hidden');
                    }
                });
            }
        }, 500); // Set a timeout before hiding the tooltip

        // Reset timeout if mouse re-enters the trigger element
        $(obj).mouseenter(function() {
            clearTimeout(tooltipTimer);
        });
    }
</script>