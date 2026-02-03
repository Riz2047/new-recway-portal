<?php

$activeLink = "analytics";

include_once('includes/header.php');

$endDate = Date('Y-m-d');
$startDate = Date('Y-m-d', strtotime($endDate . '-30 days'));

$query = 'SELECT c.*, COUNT(ca.id) AS order_count 
          FROM customers c
          LEFT JOIN candidates ca ON c.id = ca.cus_id
          WHERE ca.booked != "" AND ca.expired != 1
          GROUP BY c.id';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$query = 'SELECT c.company, COUNT(ca.id) AS order_count 
          FROM customers c
          LEFT JOIN candidates ca ON c.id = ca.cus_id
          WHERE ca.booked != "" AND ca.expired != 1
          GROUP BY c.company';
$stmt = $conn->prepare($query);
$stmt->execute();
$companies = $stmt->fetchAll();

$query = 'SELECT * FROM analytics';
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics = $stmt->fetchAll();

$query = 'SELECT status FROM statuses GROUP BY `status` ORDER BY id';
$stmt = $conn->prepare($query);
$stmt->execute();
$last_status = $stmt->fetchAll();

$query = 'SELECT customers.name, customers.company, COUNT(candidates.id) AS order_count 
          FROM customers 
          LEFT JOIN candidates ON customers.id = candidates.cus_id 
                                 AND YEAR(candidates.created) = YEAR(NOW())
          GROUP BY customers.id
          ORDER BY order_count DESC';
$stmt = $conn->prepare($query);
$stmt->execute();
$booked_order_cus = $stmt->fetchAll();

$query = 'SELECT customers.company, COUNT(candidates.id) AS order_count 
          FROM customers 
          LEFT JOIN candidates ON customers.id = candidates.cus_id 
                                 AND YEAR(candidates.created) = YEAR(NOW())
          GROUP BY customers.company
          ORDER BY order_count DESC';
$stmt = $conn->prepare($query);
$stmt->execute();
$booked_order_comp = $stmt->fetchAll();

$query = "SELECT candidates.*,statuses.status as status_name,statuses.color as status_color ,customers.name as cus_name, customers.company as company_name,interviews.service_cat_id as service_category
    FROM candidates 
    LEFT JOIN customers ON candidates.cus_id = customers.id
    LEFT JOIN statuses ON candidates.status = statuses.id 
    LEFT JOIN interviews ON candidates.interview_id = interviews.id
    WHERE booked != '' AND invoice_sent = 0 AND expired = 0  ORDER BY CASE
    WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end
    ELSE 0
END, booked ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$uninvoiced_orders = $stmt->fetchAll();

function findObjectByValue($value, $array)
{

    foreach ($array as $element) {
        if ($value == $element->display) {
            return $element;
        }
    }

    return false;
}

$query = "SELECT order_forms.*,customers.company FROM order_forms LEFT JOIN customers ON order_forms.cus_id = customers.id";
$stmt = $conn->prepare($query);
$stmt->execute();
$forms = $stmt->fetchAll();

$uniqueValues = array();

?>

<style>
    #tooltip {
        width: 22% !important;
        position: fixed;
        top: 90px;
        left: 563px;
        background-color: white;
        border: 2px solid #aeaeae;
        border-radius: 15px;
        display: none;
        max-height: 75%;
        overflow: auto;
    }
    td {
        white-space: normal !important
    }
</style>

<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row">
            <div class="col-lg-12 d-flex justify-content-md-between flex-md-row flex-column justify-content-center align-items-center">
                <div class="d-flex  align-items-center">
                    <!-- <span class="me-3 ">
                        <button class="btn-primary w-600 mb-3 rangeTabLink" data-bs-toggle="modal" data-bs-target="#lastDays">
                            <i class="bi bi-calendar2-check me-2"></i>Last 30 days
                        </button>
                    </span> -->
                    <span>
                        <button class="btn-primary w-600 mb-3" data-bs-toggle="modal" data-bs-target="#filter">
                            <i class="bi bi-filter me-2"></i>Filters
                        </button>
                    </span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="me-3">
                        <button class="btn-primary w-600  mb-3" data-bs-toggle="modal" data-bs-target="#customize">
                            <i class="bi bi-calendar2-check me-2"></i>Customize
                        </button>
                    </span>
                    <span class="me-3">
                        <button class="btn-primary w-600  mb-3" data-bs-toggle="modal" data-bs-target="#customize_columns">
                            <i class="bi bi-calendar2-check me-2"></i>Customize Export Columns
                        </button>
                    </span>
                    <span>
                        <button class="btn-primary w-600 mb-3 order new_export">
                            <i class="bi bi-gear me-2"></i>Export
                        </button>
                    </span>
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class=" col-lg-3 col-md-6 mt-3">
                <a href="" style="text-decoration: none;">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Created</h1>
                                <h1 class="text-numer created-count count mb-0 pb-0" data-chart="createdChart"></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-bookmark-star"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>


            <div class=" col-lg-3 col-md-6 mt-3">
                <a href="" style="text-decoration: none;">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Approved</h1>
                                <h1 class="text-numer approved-count count mb-0 pb-0" data-chart="approvedChart"></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-bookmark-check"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class=" col-lg-3 col-md-6 mt-3">
                <a href="" style="text-decoration: none;">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Booked</h1>
                                <h1 class="text-numer booked-count count mb-0 pb-0" data-chart="bookedChart"></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class=" col-lg-3 col-md-6 mt-3">
                <a href="" style="text-decoration: none;">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Canceled</h1>
                                <h1 class="text-numer canceled-count count mb-0 pb-0" data-chart="canceledChart"></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-bookmark-x"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class=" col-lg-4 col-md-4 mt-3">
                <a href="#" style="text-decoration: none;" data-bs-toggle="modal" data-bs-target="#cus_with_orders">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Customers with Orders</h1>
                                <h1 class="text-numer c_o_count mb-0 pb-0"><?= count($booked_order_cus) ?></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-bookmark-star"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class=" col-lg-4 col-md-4 mt-3">
                <a href="#" style="text-decoration: none;" data-bs-toggle="modal" data-bs-target="#comp_with_orders">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Company Orders</h1>
                                <h1 class="text-numer comp_o_count mb-0 pb-0"><?= count($booked_order_comp) ?></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <div class=" col-lg-4 col-md-4 mt-3">
                <a href="#" style="text-decoration: none;" data-bs-toggle="modal" data-bs-target="#uninvoiced_orders">
                    <div class="total-card shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex flex-column align-items-start">
                                <h1 class="text-lg">Uninvoiced Orders</h1>
                                <h1 class="text-numer u_n_count mb-0 pb-0"><?= count($uninvoiced_orders) ?></h1>
                            </div>
                            <div class="icon-card">
                                <i class="bi bi-bookmark-x"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <div class="modal fade" id="uninvoiced_orders" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <p class="f-16 w-700 mb-0 pb-0">Uninvoiced Orders</p>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-9">
                            </div>
                            <div class="col-md-3">
                                <input type="search" id="filterInput" class="form-control m-2">
                            </div>
                            <div id="tooltip">
                                <button type="button" class="btn-close float-right m-2" aria-label="Close" onclick="closeDiv()"></button>
                                <ul id="history">

                                </ul>
                            </div>
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>OrderId</th>
                                        <th>Customer Name</th>
                                        <th>Company Name</th>
                                        <th>Service</th>
                                        <th>Interview Date / Delivery Date</th>
                                        <th></th>
                                        <th>Status</th>
                                        <th>Invoice Sent</th>
                                    </tr>
                                </thead>
                                <tbody id="un_invo">
                                    <?php if (!empty($uninvoiced_orders)) {
                                        $i = 0; ?>
                                        <?php foreach ($uninvoiced_orders as $uninvoiced_order) { ?>
                                            <tr>
                                                <td><?= ++$i ?></td>
                                                <td><?= $uninvoiced_order->order_id ?></td>
                                                <td><?= $uninvoiced_order->cus_name ?></td>
                                                <td><?= $uninvoiced_order->company_name ?></td>
                                                <td>
                                                    <?php if ($uninvoiced_order->service_category == 1) { ?>
                                                        Interviews
                                                    <?php } else if ($uninvoiced_order->service_category == 3) { ?>
                                                        Background Check
                                                    <?php } else if ($uninvoiced_order->service_category == 9) { ?>
                                                        Folloe-up-Interview
                                                    <?php } ?>
                                                </td>
                                                <td><?= $uninvoiced_order->booked ?></td>
                                                <?php
                                                $query = "SELECT * FROM history WHERE order_id = {$uninvoiced_order->id}";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute();
                                                $history = $stmt->fetchAll();
                                                $class = '';
                                                if (!empty($history)) {
                                                    foreach ($history as $his) {
                                                        if ($his->desc == 'Interview Interrupted' || $his->desc == 'Candidate is under investigation with SPO' || $his->desc == 'Candidate has been denied after meeting with SPO' || $his->desc == 'Interview has been Rescheduling' || $his->desc == 'Candidate did not show up') {
                                                            $class = 'btn-success';
                                                        }
                                                    }
                                                }
                                                ?>
                                                <td>
                                                    <button class="<?= $class ?>" type="button" aria-expanded="false" data-id="<?= $uninvoiced_order->id ?>" onclick="get_history(this)" style="float: right;margin: 5px;">
                                                        <i class="bi bi-clock-history"></i>
                                                    </button>
                                                </td>
                                                <td><span class="badge badge-pill" style="background-color:<?= $uninvoiced_order->status_color ?>"><?= $uninvoiced_order->status_name ?></span></td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input" data-chkcomp="<?= trim($uninvoiced_order->company_name) ?>" value="<?= $uninvoiced_order->order_id ?>" id="inv_<?= $uninvoiced_order->order_id ?>" onclick="invoice_sent(this)">
                                                    <label for="inv_<?= $uninvoiced_order->order_id ?>" class="form-check-label ">
                                                    </label>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="cus_with_orders" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <p class="f-16 w-700 mb-0 pb-0">Customers with orders</p>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-9">
                            </div>
                            <div class="col-md-3">
                                <input type="search" id="filterInput2" class="form-control m-2">
                            </div>
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Name</th>
                                        <th>Company Name</th>
                                        <th>No of Orders</th>
                                    </tr>
                                </thead>
                                <tbody id="filtertab2">
                                    <?php if (!empty($booked_order_cus)) {
                                        $i = 0; ?>
                                        <?php foreach ($booked_order_cus as $booked_order_cu) { ?>
                                            <tr>
                                                <td><?= ++$i ?></td>
                                                <td><?= $booked_order_cu->name ?></td>
                                                <td><?= $booked_order_cu->company ?></td>
                                                <td><?= $booked_order_cu->order_count ?></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="comp_with_orders" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="exampleModalLabel">
                            <p class="f-16 w-700 mb-0 pb-0">Company orders</p>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-9">
                            </div>
                            <div class="col-md-3">
                                <input type="search" id="filterInput3" class="form-control m-2">
                            </div>
                            <table class="table-bordered">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Company Name</th>
                                        <th>No of Orders</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody id="filtertab3">
                                    <?php if (!empty($booked_order_comp)) {
                                        $i = 0; ?>
                                        <?php foreach ($booked_order_comp as $booked_order_cu) { ?>
                                            <tr>
                                                <td><?= ++$i ?></td>
                                                <td><?= $booked_order_cu->company ?></td>
                                                <td><?= $booked_order_cu->order_count ?></td>
                                                <td>
                                                    <input type="checkbox" class="form-check-input" value="<?= $booked_order_cu->company ?>" id="<?= $booked_order_cu->company ?>" onclick="check_checkboxes(this)">
                                                    <label for="<?= $booked_order_cu->company ?>" class="form-check-label ">
                                                    </label>
                                                </td>
                                                <td>
                                                    <button type="button" data-name="<?= $booked_order_cu->company ?>" onclick="trigger_export(this);$(this).addClass('btn-success')"><span class="fa fa-print"></span></button>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>




        <!-- table row -->
        <div class="row mt-3 justify-content-center h-100 ">
            <div class="col-lg-12 mt-3">
                <h1 class="f-16 w-700 text-primary white-box">
                    Orders Data
                </h1>
            </div>
            <?php $status = findObjectByValue('created-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 created-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Created</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="createdChart" style="width:100%;"></canvas>
                </div>
            </div>
            <?php $status = findObjectByValue('approved-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 approved-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Approved</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="approvedChart" style="width:100%;"></canvas>
                </div>
            </div>
            <?php $status = findObjectByValue('booked-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 booked-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Booked</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="bookedChart" style="width:100%;"></canvas>
                </div>
            </div>
            <?php $status = findObjectByValue('canceled-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 canceled-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Canceled</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="canceledChart" style="width:100%;"></canvas>
                </div>
            </div>
            <div class="col-lg-12 mt-3">
                <h1 class="f-16 w-700 text-primary white-box">
                    Customers Data
                </h1>
            </div>
            <?php $status = findObjectByValue('most-orders-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 most-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 most-orders height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Customer with most orders</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="customerMostChart" style="width:100%;"></canvas>
                </div>
            </div>
            <?php $status = findObjectByValue('no-orders-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 no-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 no-orders height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Customers with no orders</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <div class="d-flex px-2">
                        <p class="w-50 w-600">Name</p>
                        <p class="w-50 w-600">Email</p>
                    </div>
                    <div class="overflow-div px-2 noorders-users">
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-3 justify-content-center h-100">
            <div class="col-lg-12 mt-3">
                <h1 class="f-16 w-700 text-primary white-box">
                    Companies Data
                </h1>
            </div>
            <?php $status = findObjectByValue('total-orders-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 total-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 no-orders height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Companies total orders</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <div class="d-flex px-2">
                        <p class="w-50 w-600">Name</p>
                        <p class="w-50 w-600 text-center">Orders</p>
                    </div>
                    <div class="overflow-div px-2 companies-table">

                    </div>
                </div>
            </div>
            <?php $status = findObjectByValue('company-orders-main', $analytics)->status ?>
            <div class="col-lg-6 mb-3 company-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
                <div class="white-box mt-3 fix-card h-100 height-orders">
                    <div class="d-flex justify-content-between chats-cards mt-2">
                        <p class="f-14 w-600 ">Companies orders</p>
                        <p class="f-12 w-700 created-title-date"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
                    </div>
                    <canvas id="singleCompanyChart" style="width:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>


<div class="backdrop"></div>

<!--Last days Modal -->
<!-- Button trigger modal -->
<div class="modal fade" id="lastDays" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body">
                <div class="px-lg-3 px-1">
                    <div class="row update-form h-100 justify-content-center">
                        <div class="col-lg-4 mb-md-0 mb-3">
                            <div class="d-flex flex-column justify-content-between  h-100">
                                <div>
                                    <p class="f-14 mb-0 pb-0 w-500 mb-3">Data Range</p>
                                    <div class="d-flex align-items-center">
                                        <input type="text" class="sign-input w-100 mb-3" placeholder="From">
                                        <p class="f-14 w-500 mx-1">To</p>
                                        <input type="text" class="sign-input w-100 mb-3" placeholder="To">
                                    </div>
                                </div>
                                <div class="d-flex align-items-center buttons">
                                    <a id="cancel-date" data-bs-dismiss="modal" class="d-flex f-14 w-500 btn-primary-sm w-50 me-2">Cancel</a>
                                    <a id="apply-date" data-bs-dismiss="modal" class="d-flex f-14 w-500 btn-primary-sm bg-primary w-50 ms-2 apply">Apply</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-md-0 mb-3">
                            <div class="ftco-section shadow-sm p-1">
                                <div class="col-md-12">
                                    <div class="calendar calendar-first" id="calendar_first">
                                        <div class="calendar_header">
                                            <button class="switch-month switch-left"> <i class="fa fa-chevron-left"></i></button>
                                            <h2></h2>
                                            <button class="switch-month switch-right"> <i class="fa fa-chevron-right"></i></button>
                                        </div>
                                        <div class="calendar_weekdays"></div>
                                        <div class="calendar_content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 mb-md-0 mb-3">
                            <div class="ftco-section shadow-sm p-1">
                                <div class="col-md-12">
                                    <div class="calendar calendar-first" id="calendar_second">
                                        <div class="calendar_header">
                                            <button class="switch-month switch-left"> <i class="fa fa-chevron-left"></i></button>
                                            <h2></h2>
                                            <button class="switch-month switch-right"> <i class="fa fa-chevron-right"></i></button>
                                        </div>
                                        <div class="calendar_weekdays"></div>
                                        <div class="calendar_content"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================Filter---------- -->
<div class="modal fade" id="filter" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <p class="f-16 w-700 mb-0 pb-0">Filter</p>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="px-lg-3 px-1">
                    <div class="row h-100 justify-content-center">
                        <div class="col-lg-12 d-flex flex-column justify-content-between align-items-between">
                            <form action="" class="update-form">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-3 mb-3">
                                        <label class="f-14 mb-0 pb-0 w-600">Customers</label>
                                        <select class="form-select  w-100 select2 customer-select" aria-label="Default select example">
                                            <option selected value="0">All Customers</option>
                                            <?php if (!empty($customers)) : ?>
                                                <?php foreach ($customers as $customer) : ?>
                                                    <option value="<?php echo $customer->id ?>"><?php echo $customer->name ?>&nbsp;&nbsp;&nbsp;&nbsp; (<?= $customer->order_count ?>)</option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 mb-3">
                                        <label class="f-14 mb-0 pb-0 w-600">Companies</label>
                                        <select class="form-select  w-100 select2 company-select" aria-label="Default select example" onchange="checkBillingDetail()">
                                            <option selected value="0">All Companies</option>
                                            <?php if (!empty($companies)) { ?>
                                                <?php foreach ($companies as $company) { ?>
                                                    <option value="<?= $company->company ?>" data-companyname="<?= $company->company ?>"><?= $company->company ?>&nbsp;&nbsp;&nbsp;&nbsp; (<?= $company->order_count ?>)</option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 mb-3">
                                        <label class="f-14 mb-0 pb-0 w-600">Service Category</label>
                                        <select class="form-select  w-100 select2 service-select" onchange="change_title(this)">
                                            <option selected value="0">All Services</option>
                                            <option value="1">Interviews</option>
                                            <option value="3">Background Check</option>
                                            <option value="9">Follow-up-interview</option>
                                        </select>
                                    </div>

                                    <div class="col-lg-3 mb-3">
                                        <label class="f-14 mb-0 pb-0 w-600">Last Status</label>
                                        <select class="form-select  w-100 select2 lastStatus-select" aria-label="Default select example">
                                            <option selected value="0">Select Status</option>
                                            <?php if (!empty($last_status)) : ?>
                                                <?php foreach ($last_status as $last_statu) : ?>
                                                    <option value="<?php echo $last_statu->status ?>"><?php echo $last_statu->status ?></option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="col-md-3">
                                        <label>Order Created From</label>
                                        <input type="date" id="order_created_from" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Order Created To</label>
                                        <input type="date" id="order_created_to" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="interview_label_from">Interview Date From</label>
                                        <input type="date" id="startDate" class="form-control" value="<?php echo $startDate ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="interview_label_to">Interview Date To</label>
                                        <input type="date" id="endDate" class="form-control" value="<?php echo $endDate ?>">
                                    </div>
                                    <div class="col-lg-12">
                                        <div class="d-flex justify-content-between mt-3 mb-3">

                                            <button type="button" id="reset-filters" class="f-14  w-500 no-decoration text-primary btn-primary-sm bg-primary">Reset Filters</button>

                                            <div class="d-flex align-items-center ">
                                                <div class="me-3">
                                                    <button type="button" data-bs-dismiss="modal" id="cancel-filter" class="f-14 w-500 btn-primary-sm order">Cancel</button>
                                                </div>
                                                <div>
                                                    <button type="button" data-bs-dismiss="modal" class=" f-14 w-500 order-fill apply btn-primary-sm bg-primary" onclick="apply_filter_2();">Apply</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ================Customize---------- -->
<div class="modal fade" id="customize" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <p class="f-16 w-700 mb-0 pb-0">Customize data.</p>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="px-md-3 px-1">
                    <div class="row h-100 justify-content-center">
                        <div class="col-lg-12 d-flex flex-column justify-content-between align-items-between">
                            <div class="">
                                <div class="row bg-light p-3">
                                    <p class="f-14 mb-0 pb-0 w-700 mb-3 text-dark-blue">Orders Data</p>
                                    <div class="col-12 mb-3">
                                        <?php $status = findObjectByValue('created-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="created-main" type="checkbox" class="form-check-input" id="created-check" name="created">
                                            <label style="line-height: 14px" for="created-check" class="form-check-label ">
                                                Created<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of
                                                    created orders by the
                                                    customers</small>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3 mt-3">
                                        <?php $status = findObjectByValue('approved-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start ">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="approved-main" type="checkbox" class="form-check-input" id="approved-check" name="approved">
                                            <label style="line-height: 14px" for="approved-check" class="form-check-label">Approved<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of
                                                    approved orders by the
                                                    customers</small></label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3 mt-3">
                                        <?php $status = findObjectByValue('booked-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start ">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="booked-main" type="checkbox" class="form-check-input" id="booked-check" name="booked">
                                            <label style="line-height: 14px" for="booked-check" class="form-check-label">Booked<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of
                                                    booked orders by the
                                                    customers</small></label>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3 mt-3">
                                        <?php $status = findObjectByValue('canceled-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start ">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="canceled-main" type="checkbox" class="form-check-input" id="canceled-check" name="canceled">
                                            <label style="line-height: 14px" for="canceled-check" class="form-check-label">Canceled<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of
                                                    canceled orders by the
                                                    customers</small></label>
                                        </div>
                                    </div>
                                </div>


                                <div class="row  bg-light p-3 mt-3">
                                    <p class="f-14 mb-0 pb-0 w-700  mb-3 text-dark-blue">Customers Data</p>
                                    <div class="col-12 mb-3">
                                        <?php $status = findObjectByValue('most-orders-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start ">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="most-orders-main" type="checkbox" class="form-check-input" id="most-orders" name="most-orders">
                                            <label style="line-height: 14px" for="most-orders" class="form-check-label">Most Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Customers with
                                                    most orders</small></label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-3">
                                        <?php $status = findObjectByValue('no-orders-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start ">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="no-orders-main" type="checkbox" class="form-check-input" id="no-orders" name="no-orders">
                                            <label style="line-height: 14px" for="no-orders" class="form-check-label">No Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Customers with
                                                    no
                                                    orders</small></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="row bg-light mt-3 p-3">

                                    <p class="f-14 mb-0 pb-0 w-700  mb-3 text-dark-blue">Companies Data</p>
                                    <div class="col-12">
                                        <?php $status = findObjectByValue('total-orders-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="total-orders-main" type="checkbox" class="form-check-input" id="companies-total-orders" name="companies-total-orders">
                                            <label style="line-height: 14px" for="companies-total-orders" class="form-check-label">Total Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of total
                                                    orders created in companies</small></label>
                                        </div>
                                    </div>
                                    <div class="col-12 mt-4">
                                        <?php $status = findObjectByValue('company-orders-main', $analytics)->status ?>
                                        <div class="d-flex align-items-start">
                                            <input <?php echo $status == 1 ? 'checked' : '' ?> value="company-orders-main" type="checkbox" class="form-check-input" id="companies-orders" name="companies-orders">
                                            <label style="line-height: 14px" for="companies-orders" class="form-check-label">Company Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of
                                                    orders created in companies</small></label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="customize_columns" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <p class="f-16 w-700 mb-0 pb-0">Customize Columns</p>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="px-md-3 px-1">
                    <div class="row h-100 justify-content-center">
                        <div class="col-lg-12 d-flex flex-column justify-content-between align-items-between">
                            <div class="">
                                <div class="row bg-light p-3">
                                    <p class="f-14 mb-0 pb-0 w-700 mb-3 text-dark-blue">Customize Columns For Export</p>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="order_id" name="customize_columns[]" id="order_id">
                                            <label style="line-height: 14px" for="order_id" class="form-check-label ">
                                                Order ID
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="vasc_id" name="customize_columns[]" id="vasc_id">
                                            <label style="line-height: 14px" for="vasc_id" class="form-check-label ">
                                                VASC ID
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="security_number" name="customize_columns[]" id="security_number">
                                            <label style="line-height: 14px" for="security_number" class="form-check-label ">
                                                Security Number
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="candidate" name="customize_columns[]" id="can_name">
                                            <label style="line-height: 14px" for="can_name" class="form-check-label ">
                                                Candidate Name
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="company" name="customize_columns[]" id="cus_company">
                                            <label style="line-height: 14px" for="cus_company" class="form-check-label ">
                                                Company
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="customer" name="customize_columns[]" id="cus_name">
                                            <label style="line-height: 14px" for="cus_name" class="form-check-label ">
                                                Customer Name
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="invoice_recepient" name="customize_columns[]" id="invoice_recepient">
                                            <label style="line-height: 14px" for="invoice_recepient" class="form-check-label ">
                                                Invoice Recipient
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="invoice_reference" name="customize_columns[]" id="invoice_reference">
                                            <label style="line-height: 14px" for="invoice_reference" class="form-check-label ">
                                                Invoice Reference
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="invoice__comment" name="customize_columns[]" id="invoice__comment">
                                            <label style="line-height: 14px" for="invoice__comment" class="form-check-label ">
                                                Invoice Comment
                                            </label>
                                        </div>
                                    </div>
                                    <?php
                                    // Initialize an array to store processed strings
                                    $processedStrings = array();

                                    foreach ($forms as $form) {
                                        $formData = json_decode($form->form, true);

                                        // Check if billing_info exists
                                        if (isset($formData['form_builder']['billing_info'])) {
                                            // Access the billing_info array
                                            $billingInfo = $formData['form_builder']['billing_info'];

                                            // Remove the last element from the array
                                            array_pop($billingInfo);

                                            foreach ($billingInfo as $key => $values) {
                                                // Remove <br> tags from the key
                                                $key = str_replace('<br>', ' ', $key);

                                                // Split the string by commas
                                                $parts = explode(',', $key);

                                                // Check if there are at least two parts
                                                if (count($parts) >= 2) {
                                                    // Extract the second part after the first comma
                                                    $result = trim($parts[1]);
                                                    $index = trim($parts[2]);
                                                    if (isset($parts[6]) && !empty($parts[6]) && $parts[6] == 'new_field') {
                                                    } else {
                                                        if ($index  != 'note') {
                                                            // Check if the extracted string has not been processed already
                                                            if (!in_array($result, $processedStrings)) {
                                    ?>
                                                                <div class="col-md-12">
                                                                    <div class="d-flex align-items-start mt-2 mb-2">
                                                                        <input type="checkbox" class="form-check-input billing_detail" checked value="<?= strtolower(str_replace(' ', '_', $result)) ?>" name="customize_columns[]" data-id="<?= $index ?>" id="<?= strtolower(str_replace(' ', '_', $result)) ?>" data-company="<?= $form->company ?>">
                                                                        <label style="line-height: 14px" for="<?= strtolower(str_replace(' ', '_', $result)) ?>" class="form-check-label ">
                                                                            <?= $result; ?>
                                                                        </label>
                                                                    </div>
                                                                </div>
                                    <?php
                                                                // Add the extracted string to the processed array
                                                                $processedStrings[] = $result;
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="cost_place" name="customize_columns[]" id="cost_place">
                                            <label style="line-height: 14px" for="cost_place" class="form-check-label ">
                                                Cost Place
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="status" name="customize_columns[]" id="status">
                                            <label style="line-height: 14px" for="status" class="form-check-label ">
                                                Status
                                            </label>
                                        </div>
                                    </div>
                                    <div style="display:none">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="recent_status" name="customize_columns[]" id="recent_status">
                                            <label style="line-height: 14px" for="recent_status" class="form-check-label ">
                                                Recent Status
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="interview_date" name="customize_columns[]" id="interview_date">
                                            <label style="line-height: 14px" for="interview_date" class="form-check-label ">
                                                Interview Date
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="delivery_date" name="customize_columns[]" id="delivery_date">
                                            <label style="line-height: 14px" for="delivery_date" class="form-check-label ">
                                                Delivery Date
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="invoice_sent" name="customize_columns[]" id="invoice_sent">
                                            <label style="line-height: 14px" for="invoice_sent" class="form-check-label ">
                                                Invoice Sent
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="staff" name="customize_columns[]" id="Staff">
                                            <label style="line-height: 14px" for="Staff" class="form-check-label ">
                                                Staff
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="place" id="place" name="customize_columns[]">
                                            <label style="line-height: 14px" for="place" class="form-check-label ">
                                                Place
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="service_type" name="customize_columns[]" id="service_type">
                                            <label style="line-height: 14px" for="service_type" class="form-check-label ">
                                                Service Type
                                            </label>
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="d-flex align-items-start">
                                            <input type="checkbox" class="form-check-input" checked value="created_on" name="customize_columns[]" id="created_on">
                                            <label style="line-height: 14px" for="created_on" class="form-check-label ">
                                                Created On
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<?php

include_once('includes/footer.php');

?>

<script src="assets/js/analytics.js"></script>

<script>
    $('input[type=checkbox]').each(function() {
        $(this).on('change', function() {
            var that = $(this)
            $.ajax({
                type: "POST",
                url: '../includes/ajax.php',
                data: {
                    "analytics": true,
                    "display": $(this).val(),
                    "displayStatus": $(this).is(":checked")
                },
                success: function(response) {
                    var res = JSON.parse(response);

                    if (res.status == 1) {
                        $('.' + res.display).css('display', 'block');
                    } else {
                        $('.' + res.display).css('display', 'none');
                    }
                }
            });
        })
    })
    $('#filter').on('shown.bs.modal', function() {
        $('.select2').select2({
            dropdownParent: $('#filter .modal-content')
        });
    });
    $(document).ready(function() {
        $("#filterInput").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#un_invo tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        $("#filterInput2").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#filtertab2 tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        $("#filterInput3").on("keyup", function() {
            var value = $(this).val().toLowerCase();
            $("#filtertab3 tr").filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)
            });
        });
        comp_cus()
    });

    function invoice_sent(obj) {
        var order_id = $(obj).val();
        var inv_sent = 0;
        if ($(obj).is(':checked')) {
            inv_sent = 1
        }
        $.ajax({
            type: "POST",
            url: '../includes/pages.php',
            data: {
                "order_id": order_id,
                "inv_sent": inv_sent,
                "inv_sent_analytics": 1,
            },
            success: function(response) {}
        });
    }

    function apply_filter_2() {
        var startDate = $('#startDate').val();
        var endDate = $('#endDate').val();
        var customerSelected = $('.customer-select').val()
        var service = $('.service-select').val()
        var companySelected = $('.company-select').val()
        var create_from = $('#order_created_from').val();
        var create_to = $('#order_created_to').val();
        $.ajax({
            type: "POST",
            url: '../includes/pages.php',
            data: {
                "interview_date_from": startDate,
                "interview_date_to": endDate,
                "customer": customerSelected,
                "company": companySelected,
                "created_from": create_from,
                "created_to": create_to,
                "service_category": service,
                "apply_filter": 1,
            },
            success: function(response) {
                var html = ''
                response = JSON.parse(response);
                if (response.uninvoiced_orders && response.uninvoiced_orders != '') {
                    html = ''
                    var i = 1;
                    response.uninvoiced_orders.forEach(function(order, k) {
                        var btn_class = '';
                        order.order_history.forEach(function(row, k) {
                            if (row.desc == 'Interview Interrupted' || row.desc == 'Candidate is under investigation with SPO' || row.desc == 'Candidate has been denied after meeting with SPO' || row.desc == 'Interview has been Rescheduling' || row.desc == 'Candidate did not show up') {
                                btn_class = 'btn-success'
                            }
                        })
                        html += `<tr>
                                                <td>` + i++ + `</td>
                                                <td>` + order.order_id + `</td>
                                                <td>` + order.customer_name + `</td>
                                                <td>` + order.customer_company + `</td>`
                        if (order.service_category == 1) {
                            html += `<td>Interviews</td>`
                        } else if (order.service_category == 3) {
                            html += `<td>Background Check</td>`
                        } else if (order.service_category == 9) {
                            html += `<td>Follow-up-Interview</td>`
                        }
                        if (order.booked != null) {
                            html += `<td>` + order.booked + `</td>`
                        } else {
                            html += `<td>` + order.delivery_date + `</td>`
                        }
                        html += `<td>
                                                    <button class="` + btn_class + `" type="button" aria-expanded="false" data-id="` + order.id + `" onclick="get_history(this)" style="float: right;margin: 5px;">
                                                        <i class="bi bi-clock-history"></i>
                                                        </button>
                                                        </td>
                                                <td><span class="badge badge-pill" style="background-color:` + order.status_color + `">` + order.status_name + `</span></td>
                                                <td class="text-center">
                                                    <input type="checkbox" class="form-check-input " data-chkcomp="` + $.trim(order.customer_company) + `" value="` + order.order_id + `" id="inv_` + order.order_id + `" onclick="invoice_sent(this)">
                                                    <label for="inv_` + order.order_id + `" class="form-check-label ">
                                                    </label>
                                                </td>
                                            </tr>`;
                        $('#un_invo').html(html)
                        $('.u_n_count').text(k + 1)
                    });
                } else {
                    $('#un_invo').html('')
                    $('.u_n_count').text(0)
                }
                if (response.customers_with_orders && response.customers_with_orders != '') {
                    html = ''
                    var j = 1;
                    response.customers_with_orders.forEach(function(order, k) {
                        html += `<tr>
                                                <td>` + j++ + `</td>
                                                <td>` + order.name + `</td>
                                                <td>` + order.company + `</td>
                                                <td>` + order.order_count + `</td>
                                            </tr>`
                        $('#filtertab2').html(html)
                        $('.c_o_count').text(k + 1)
                    });
                } else {
                    $('#filtertab2').html('')
                    $('.c_o_count').text(0)
                }
                if (response.comp_with_orders && response.comp_with_orders != '') {
                    html = ''
                    var j = 1;
                    response.comp_with_orders.forEach(function(order, k) {
                        html += `<tr>
                                                <td>` + j++ + `</td>
                                                <td>` + order.company + `</td>
                                                <td><a href="orders.php?company=` + $.trim(order.company) + `&startDate=` + startDate + `&endDate=` + endDate + `" data-company="` + $.trim(order.company) + `" class="text-dark open-order">` + order.order_count + `</a></td>
                                                <td>
                                                    <input type="checkbox" class="form-check-input" value="` + order.company + `" id="` + order.company + `" onclick="check_checkboxes(this)">
                                                    <label for="` + order.company + `" class="form-check-label ">
                                                    </label>
                                                </td>
                                                <td>
                                                    <button type="button" data-name="` + order.company + `"`
                        if (response.exported_company) {
                            response.exported_company.forEach(function(ex, x) {
                                if ($.trim(ex.exported_company) === $.trim(order.company)) {
                                    html += ` class="btn-success" `;
                                }
                            });
                        }
                        html += ` onclick="trigger_export(this);$(this).addClass('btn-success')"><span class="fa fa-print"></span></button>
                                                </td>
                                            </tr>`
                        $('#filtertab3').html(html)
                        $('.comp_o_count').text(k + 1)
                    });
                } else {
                    $('#filtertab3').html('')
                    $('.comp_o_count').text(0)
                }
            }
        });
    }

    function get_history(obj) {
        var id = $(obj).data('id')
        $.ajax({
            type: "POST",
            url: '../includes/pages.php',
            data: {
                "id": id,
                "order_history": 1,
            },
            success: function(response) {
                if (response != '') {
                    var html = ''
                    response = JSON.parse(response);
                    response.result.forEach(function(history, k) {
                        html += `<li class="m-2">
                                        <p class="m-0">` + history.desc + `</p>
                                        <i><small> ` + history.date_time + ` </small></i>
                                    </li>`
                    });
                    $('#history').html(html);
                    $('#tooltip').show();
                }
            }
        });
    }

    function closeDiv() {
        $('#tooltip').hide()
    }
    $(document).click(function(event) {
        var myDiv = $('#tooltip');
        var targetElement = event.target;

        // Check if the clicked element is not inside the myDiv
        if (!myDiv.is(targetElement) && myDiv.has(targetElement).length === 0) {
            myDiv.hide();
        }
    });

    function checkBillingDetail() {
        var comp = $('.company-select').find('option:selected').data('companyname');
        comp = $.trim(comp).toLowerCase()
        $('.billing_detail').each(function() {
            var chk_comp = $(this).data('company');
            chk_comp = $.trim(chk_comp).toLowerCase()
            if (comp == chk_comp) {
                $(this).attr('checked', true);
            } else {
                $(this).attr('checked', false);
            }
        })
    }
    $('#startDate').on('change', function() {
        comp_cus()
    })
    $('#endDate').on('change', function() {
        comp_cus()
    })

    function comp_cus() {
        var startdate = $('#startDate').val();
        var service = $('.service-select').val()
        var enddate = $('#endDate').val();
        $.ajax({
            type: "POST",
            url: '../includes/pages.php',
            data: {
                "interview_date_from": startdate,
                "interview_date_to": enddate,
                "service_category": service,
                "apply_filter": 1,
            },
            success: function(response) {
                if (response != '') {
                    var html = ''
                    response = JSON.parse(response);
                    if (response.comp_with_orders) {
                        html = '';
                        html = `<option selected value="0">All Companies</option>`;
                        response.comp_with_orders.forEach(function(order, k) {
                            html += `<option value="` + order.company + `" data-companyname="` + order.company + `">` + order.company + `       (` + order.order_count + `)</option>`
                        });
                        $('.company-select').html(html);
                        $('.company-select').select2({
                            dropdownParent: $('#filter .modal-content')
                        });
                    }
                    if (response.customers_with_orders) {
                        html = '';
                        html = `<option selected value="0">All Customers</option>`;
                        response.customers_with_orders.forEach(function(order, k) {
                            html += `<option value="` + order.id + `">` + order.name + `       (` + order.order_count + `)</option>`
                        });
                        $('.customer-select').html(html);
                        $('.customer-select').select2({
                            dropdownParent: $('#filter .modal-content')
                        });
                    }
                }
            }
        });
    }

    function trigger_export(obj) {
        var old = $.trim($('.company-select').val());
        var current = $.trim($(obj).data('name'));
        $('.company-select option').each(function() {
            var optionValue = $.trim($(this).val());
            if (optionValue === current) {
                $(this).prop('selected', true);
            }
        });
        checkBillingDetail()
        $('.order.new_export').click();
        $('.company-select option').each(function() {
            var optionValue = $.trim($(this).val());
            if (optionValue === old) {
                $(this).prop('selected', true);
            }
        });
        $('.billing_detail').attr('checked', true);

        var startdate = $('#startDate').val();
        var enddate = $('#endDate').val();
        $.ajax({
            type: "POST",
            url: '../includes/pages.php',
            data: {
                "company": current,
                "company_exported": 1,
                "interview_date_from": startdate,
                "interview_date_to": enddate,
            },
            success: function(response) {
                if (response != '') {}
            }
        });
    }

    function check_checkboxes(obj) {
        var company = $.trim($(obj).val());
        if ($(obj).is(':checked')) {
            $('input[type=checkbox][data-chkcomp="' + company + '"]').each(function() {
                if (!$(this).is(':checked')) {
                    $(this).click();
                }
            })
        } else {
            $('input[type=checkbox][data-chkcomp="' + company + '"]').each(function() {
                if ($(this).is(':checked')) {
                    $(this).click();
                }
            })
        }

    }

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

    function change_title(obj) {
        if ($(obj).val() == 3) {
            $('.interview_label_from').html('Delivery Date From');
            $('.interview_label_to').html('Delivery Date To');
        } else {
            $('.interview_label_from').html('Interview Date From');
            $('.interview_label_to').html('Interview Date To');
        }
    }
</script>