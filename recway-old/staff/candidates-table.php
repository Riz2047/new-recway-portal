<?php
// Updated: 2025-09-28 19:12:00 - Fixed column alignment

$query = "SELECT * FROM tables_settings WHERE name = 'Candidate'";

$stmt = $conn->prepare($query);

$stmt->execute();

$table = $stmt->fetchAll();

$table_columns_data = null;

if (!empty($table[0]->meta_data)) {

    $table_columns_data = json_decode($table[0]->meta_data, true);

}



$query = "SELECT * FROM customers";

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();



$query = "SELECT * FROM places";

$stmt = $conn->prepare($query);

$stmt->execute();

$places = $stmt->fetchAll();



$query = "SELECT * FROM statuses";

$stmt = $conn->prepare($query);

$stmt->execute();

$status = $stmt->fetchAll();


$arr = [
    'status_no_4' => 0,
    'status_no_7' => 0,
    'status_no_9' => 0,
    'status_no_21' => 0,
    'status_no_22' => 0,
    'status_no_37' => 0,
    'status_no_40' => 0,
    'status_no_42' => 0,
];
?>

<style>

    .delay_btn {

        height: 10px !important;

        width: 10% !important;

        padding: 8% !important;

        float: right !important;

        margin-top: 2% !important;

        margin-right: 30% !important;

        font-size: 15px !important;

    }



    #table {

        overflow-x: auto;

        white-space: nowrap;

        width: 100%;

    }



    #dataTabel {

        border-collapse: collapse;

    }



    #scrollRightButton {

        position: fixed;

        right: 26px;

        top: 50%;

        transform: translateY(-50%);

        width: 30px;

        border: 2px solid #a99b83;

        font-weight: 800;

        /* display: none; */

        border-radius: 50%;

        color: #a99b83;

        background-color: white;

        z-index: 999;

    }



    #scrollLeftButton {

        position: fixed;

        top: 50%;

        transform: translateY(-50%);

        width: 30px;

        left: 286px;

        border: 2px solid #a99b83;

        font-weight: 800;

        /* display: none; */

        border-radius: 50%;

        color: #a99b83;

        background-color: white;

        z-index: 999;

    }



    .scroll_btn:hover {

        box-shadow: -3px 3px 20px 0px rgb(0 0 0 / 47%);

    }



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



    .dataTables_scrollBody {

        overflow-y: hidden !important;

    }

.dataTables_scrollBody {
    overflow-x: auto !important;
    overflow-y: visible !important;
}
.table-section .dropdown-menu,
.dataTables_wrapper .dropdown-menu {
    z-index: 2000;
}

.table-section .dropdown.dropstart .dropdown-menu {
    right: auto;
    left: 0;
}

/* Open dropdowns above within the scroll body */
.dataTables_scrollBody .dropdown .dropdown-menu {
    top: auto !important;
    bottom: 100% !important;
    margin-bottom: .5rem;
}

</style>

<div class="table-div">



    <form action="" method="post" id="d-form">

        <div class="card card-cascade narrower mb-4">



            <!--Card image-->

            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">





                <a href="#" class="white-text mx-3"><?php echo isset($candidatesCustomer) ? $candidatesCustomer . "'s " : "" ?>Candidates</a>



                <div style="display:flex !important">

                    <?php if (isset($allowed_staff_permission['create_candidate']) && !empty($allowed_staff_permission['create_candidate'])) { ?>

                        <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Candidate">

                            <span onclick="location.href='add-candidate.php'"><i class="bi bi-person-plus"></i></span>

                        </button>

                    <?php } ?>

                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" onclick="show_add_card(this)">

                        <i class="bi bi-filter"></i>

                    </button>

                    <?php if (isset($allowed_staff_permission['update_candidate']) && !empty($allowed_staff_permission['update_candidate'])) { ?>

                        <!-- <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text3" data-toggle="tooltip" data-placement="top" title="Change Customer">

                            <span class=""><i class="bi bi-person"></i></span>

                        </button> -->

                    <?php } ?>

                    <!-- <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text2" data-toggle="tooltip" data-placement="top" title="Change Staff">

                        <span class=""><i class="bi bi-pen"></i></span>

                    </button> -->

                    <?php if (isset($allowed_staff_permission['change_status']) && !empty($allowed_staff_permission['change_status'])) { ?>

                        <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text4" data-toggle="tooltip" data-placement="top" title="Change Status">

                            <span class=""><i class="bi bi-clipboard-data"></i></span>

                        </button>

                    <?php } ?>

                    <?php if (isset($login_user->category) && !empty($login_user->category) && $login_user->category == 2) { ?>

                        <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right" onclick="show_card(this)" style="margin-top: 6px !important;">

                            <i class="bx bxs-chevron-down arrow"></i>

                        </button>

                    <?php } ?>

                </div>

            </div>

            <div class="col-md-12">

                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">



                    <div class="card-body" style="display: none !important;">

                        <div class="row">

                            <input type="hidden" id="table_id" value="<?= $table[0]->id ?>">

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="place" name="column[candidate][place]" data-id="place_show" value="1" <?php if (isset($table_columns_data['place']) && !empty($table_columns_data['place'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="place">Place</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="name" name="column[candidate][name]" data-id="name_show" value="1" <?php if (isset($table_columns_data['name']) && !empty($table_columns_data['name'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="name">Name</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="customer" name="column[candidate][customer]" data-id="customer_show" value="1" <?php if (isset($table_columns_data['customer']) && !empty($table_columns_data['customer'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="customer">Customer</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[candidate][company]" data-id="company_show" value="1" <?php if (isset($table_columns_data['company']) && !empty($table_columns_data['company'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="company">Company</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="staff" name="column[candidate][staff]" data-id="staff_show" value="1" <?php if (isset($table_columns_data['staff']) && !empty($table_columns_data['staff'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="staff">Staff</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="status" name="column[candidate][status]" data-id="status_show" value="1" <?php if (isset($table_columns_data['status']) && !empty($table_columns_data['status'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="status">Status</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" data-id="invoice_sent_show" value="1" <?php if (isset($table_columns_data['invoice_sent']) && !empty($table_columns_data['invoice_sent'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="economy" name="column[candidate][economy]" data-id="economy_show" value="1" <?php if (isset($table_columns_data['economy']) && !empty($table_columns_data['economy'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="economy">Economy</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" data-id="criminal_record_show" value="1" <?php if (isset($table_columns_data['criminal_record']) && !empty($table_columns_data['criminal_record'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="social_record" name="column[candidate][social_record]" data-id="social_record_show" value="1" <?php if (isset($table_columns_data['social_record']) && !empty($table_columns_data['social_record'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="social_record">Social Media</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="reported_sm" name="column[candidate][reported_sm]" data-id="reported_sm_show" value="1" <?php if (isset($table_columns_data['reported_sm']) && !empty($table_columns_data['reported_sm'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="reported_sm">Reported</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="order_created" name="column[candidate][order_created]" data-id="order_created_show" value="1" <?php if (isset($table_columns_data['order_created']) && !empty($table_columns_data['order_created'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="order_created">Order Created</label>

                            </div>

                            <div class="col-md-3">

                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="interview_date" name="column[candidate][interview_date]" data-id="interview_date_show" value="1" <?php if (isset($table_columns_data['interview_date']) && !empty($table_columns_data['interview_date'])) { ?> checked <?php } ?>>

                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>

                            </div>

                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="archive_date" name="column[candidate][archive_date]" data-id="archive_date_show" value="1" <?php if (isset($table_columns_data['archive_date']) && !empty($table_columns_data['archive_date'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="archive_date">Archive Time</label>
                            </div>
                        </div>

                    </div>

                    <div class="row p-3 pt-0" id="show_add_card" style="display: none !important;">

                        <div class="col-md-3 p-3">

                            <label>Place</label>

                            <select class="form-control filter-select" id="fil_place">

                                <option value="">Filter by Place</option>

                                <?php if (!empty($places)) { ?>

                                    <?php foreach ($places as $place) { ?>

                                        <option value="<?= $place->id ?>"><?= $place->name ?></option>

                                    <?php } ?>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Candidate Name</label>

                            <input type="text" class="form-control" placeholder="Filter By Candidate Name" id="fil_can">

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Company Name</label>

                            <select class="form-select  w-100 filter-select" id="fil_com">

                                <option selected value="0">All Companies</option>

                                <?php if (!empty($customers)) : $companies = [] ?>

                                    <?php foreach ($customers as $customer) : ?>

                                        <?php if (!in_array(strtolower(trim($customer->company)), $companies)) : ?>

                                            <option value="<?php echo $customer->company ?>"><?php echo $customer->company ?></option>

                                        <?php array_push($companies, strtolower(trim($customer->company)));

                                        endif; ?>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </select>

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Customer Name</label>

                            <select class="form-control filter-select" id="fil_cus">

                                <option value="">Filter by Customer</option>

                                <?php if (!empty($customers)) { ?>

                                    <?php foreach ($customers as $customer) { ?>

                                        <option value="<?= $customer->id ?>"><?= $customer->name ?></option>

                                    <?php } ?>

                                <?php } ?>

                            </select>

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Order Created From</label>

                            <input type="date" id="order_created_from" class="form-control">

                            <input type="hidden" id="fil_status" value="<?php if (isset($_GET['status']) && !empty($_GET['status'])) { ?><?= $_GET['status'] ?><?php } ?>">

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Order Created To</label>

                            <input type="date" id="order_created_to" class="form-control">

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Interview Date From</label>

                            <input type="date" id="interview_date_from" class="form-control">

                        </div>

                        <div class="col-md-3 p-3">

                            <label>Interview Date From</label>

                            <input type="date" id="interview_date_to" class="form-control">

                        </div>

                        <div class="col-md-12">

                            <button type="button" class="btn btn-primary btn-sm float-right" onclick="filter_data()">Apply</button>

                            <button type="button" class="btn btn-danger btn-sm float-right" onclick="window.location.reload()">Reset</button>

                        </div>

                    </div>

                </div>

            </div>

            <div id="table">

                <button type="button" id="scrollRightButton" class="scroll_btn" onclick="scroll_right()">&gt;</button>

                <button type="button" id="scrollLeftButton" class="scroll_btn" onclick="scroll_left()">&lt;</button>

                <table id="dataTable" data-table="candidate" class="display Table" style="width: 100% !important">

                    <thead>

                        <tr>

                            <th></th>

                            <th class="table-head">

                                <input class="form-check-input d-check" id="delete-all" name="all" type="checkbox">

                                <label class="form-check-label" for="delete-all" class="mr-2 label-table"></label>

                            </th>

                            <th class="table-head">Action</th>

                            <th class="table-head">#</th>

                            <th class="table-head">Order ID</th>



                            <th class="table-head  place_show <?php if (!isset($table_columns_data['place']) || empty($table_columns_data['place'])) { ?> custom_hide<?php } ?>" <?php if(isset($_GET['service']) && $_GET['service'] == 3){ ?>style="display:none"<?php } ?>>Place</th>

                            <th class="table-head name_show <?php if (!isset($table_columns_data['name']) || empty($table_columns_data['name'])) { ?> custom_hide<?php } ?>">Name</th>

                            <th class="table-head  customer_show <?php if (!isset($table_columns_data['customer']) || empty($table_columns_data['customer'])) { ?> custom_hide<?php } ?>">Customer</th>

                            <th class="table-head  company_show <?php if (!isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>">Company</th>

                            <th class="table-head  staff_show <?php if (!isset($table_columns_data['staff']) || empty($table_columns_data['staff'])) { ?> custom_hide<?php } ?>">Staff</th>

                            <th class="table-head  reported_sm_show <?php if (!isset($table_columns_data['reported_sm']) || empty($table_columns_data['reported_sm'])) { ?> custom_hide<?php } ?>">Reported</th>

                            <th class="table-head  status_show <?php if (!isset($table_columns_data['status']) || empty($table_columns_data['status'])) { ?> custom_hide <?php } ?>">Status</th>

                            <!-- <th class="table-head d-none">Identity</th> -->

                            <th class="table-head  invoice_sent_show <?php if (!isset($table_columns_data['invoice_sent']) || empty($table_columns_data['invoice_sent'])) { ?> custom_hide<?php } ?>">Invoice Sent</th>

                            <th class="table-head  interview_date_show <?php if (!isset($table_columns_data['interview_date']) || empty($table_columns_data['interview_date']) || (isset($_GET['service']) && $_GET['service'] == 3)) { ?> custom_hide<?php } ?>" <?php if(isset($_GET['service']) && $_GET['service'] == 3){ ?>style="display:none"<?php } ?>>Interview Date</th>

                            <th class="table-head  economy_show <?php if (!isset($table_columns_data['economy']) || empty($table_columns_data['economy'])) { ?> custom_hide <?php } ?>">Economy</th>

                            <th class="table-head  criminal_record_show <?php if (!isset($table_columns_data['criminal_record']) || empty($table_columns_data['criminal_record'])) { ?> custom_hide<?php } ?>">Criminal Record</th>

                            <th class="table-head  social_record_show  <?php if (!isset($table_columns_data['social_record']) || empty($table_columns_data['social_record'])) { ?> custom_hide<?php } ?>">Social Media</th>

                            <th class="table-head" <?php if(!isset($_GET['service']) || $_GET['service'] != 3){ ?>style="display:none"<?php } ?>>Background Check Date</th>

                            <th class="table-head  order_created_show <?php if (!isset($table_columns_data['order_created']) || empty($table_columns_data['order_created'])) { ?> custom_hide<?php } ?>">Order Created</th>

                            <th class="table-head  archive_date_show <?php if (!isset($table_columns_data['archive_date']) || empty($table_columns_data['archive_date'])) { ?> custom_hide<?php } ?>">Archive Time</th>

                            <th class="table-head" <?php if(isset($_GET['service']) && $_GET['service'] != 3 && $_GET['service'] != 'all' && $_GET['service'] != ''){ ?>style="display:none"<?php } ?>>Delivery Date</th>

                            <th class="table-head">Service Type</th>



                        </tr>

                    </thead>

                    <tbody>



                        <?php if (!empty($candidates)) : ?>

                            <?php $i = 1; ?>

                            <?php foreach ($candidates as $key => $candidate) : ?>

                                <?php

                                if (isset($_GET['status']) && !empty($_GET['status'])) {

                                    if ($candidate->status == $_GET['status']) {

                                    } else {

                                        continue;

                                    }

                                }

                                $query = 'SELECT * FROM customers WHERE id = ?';

                                $stmt = $conn->prepare($query);

                                $stmt->execute([$candidate->cus_id]);

                                $customer = $stmt->fetch();



                                $query = 'SELECT * FROM interviews WHERE id = ?';

                                $stmt = $conn->prepare($query);

                                $stmt->execute([$candidate->interview_id]);

                                $interview = $stmt->fetch();



                                $query = 'SELECT * FROM places WHERE id = ?';

                                $stmt = $conn->prepare($query);

                                $stmt->execute([$candidate->place]);

                                $place = $stmt->fetch();

                                $daysToArchive = !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null";
                                // if($candidate->status == 4 || $candidate->status == 7 || $candidate->status == 9 || $candidate->status == 21 || $candidate->status == 22 || $candidate->status == 37 || $candidate->status == 40 || $candidate->status == 42){
                                //     $query = 'SELECT * FROM history WHERE `order_id` = ? ORDER BY `id` DESC LIMIT 1';
                                //     $stmt = $conn->prepare($query);
                                //     $stmt->execute([$candidate->id]);
                                //     $history = $stmt->fetch();
                                //     $daysToArchive = "N/A";
                                //     if ($history) {
                                //         // Extract the date from the `date_time` column
                                //         $recordDate = new DateTime($history->date_time);
                                //         $currentDate = new DateTime(); // Current date and time
                                        
                                //         // Calculate the difference in days
                                //         $interval = $recordDate->diff($currentDate);
                                //         $daysElapsed = $interval->days; // Number of days passed
                                        
                                //         // Subtract elapsed days from 28
                                //         $daysRemaining = 28 - $daysElapsed;
                                        
                                //         if ($daysRemaining > 0) {
                                //             $daysToArchive = "After ".$daysRemaining." days";
                                //         } else {
                                //             continue;
                                //         }
                                //     }
                                // }else{
                                //     $daysToArchive = "N/A";
                                // }


                                ?>

                                <?php

                                if ($candidate->staff_id != 0) {

                                    $query = 'SELECT * FROM staff WHERE id = ?';

                                    $stmt = $conn->prepare($query);

                                    $stmt->execute([$candidate->staff_id]);

                                    $staff = $stmt->fetch();

                                } else {

                                    $staff = "";

                                }

                                ?>

<?php 
                                if($candidate->status == 4 || $candidate->status == 7 || $candidate->status == 9 || $candidate->status == 21 || $candidate->status == 22 || $candidate->status == 37 || $candidate->status == 40 || $candidate->status == 42){
                                    if (isset($arr['status_no_'.$candidate->status])) {
                                        $arr['status_no_'.$candidate->status]++;
                                    } else {
                                        $arr['status_no_'.$candidate->status] = 1;
                                    }
                                }
                               ?>

                                <tr>

                                    <td></td>

                                    <td class="f-14">

                                        <input class="form-check-input d-check delete-candidate" id="checkbox-<?php echo $candidate->id ?>" name="delete[]" value="<?php echo $candidate->id ?>" type="checkbox">

                                        <label class="form-check-label" for="checkbox-<?php echo $candidate->id ?>" class="mr-2 label-table"></label>

                                    </td>



                                    <td>

                                        <div class="dropdown">

                                            <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">

                                                <i class="bi bi-gear"></i>

                                            </button>

                                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                                <?php if (isset($allowed_staff_permission['update_candidate']) && !empty($allowed_staff_permission['update_candidate'])) { ?>

                                                    <li class="mb-1"><a href="update-candidate.php?id=<?php echo $candidate->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                                            Edit</a>

                                                    </li>

                                                <?php } ?>

                                                <li class="mb-1"><a href="invoice.php?id=<?php echo $candidate->id ?>" class="no-decoration f-14 w-600 text-black"><i class="bi bi-eye  f-14 text-black me-2"></i>

                                                        View</a></li>



                                                <?php if (isset($allowed_staff_permission['change_staff']) && !empty($allowed_staff_permission['change_staff'])) { ?>

                                                    <li class="mb-1"><a href="change-staff.php?id=<?php echo $candidate->id ?>" class="no-decoration f-14 w-600 text-black"><i class="bi bi-people  f-14 text-black me-2"></i>

                                                            Change Staff</a></li>

                                                <?php } ?>



                                                <?php if (isset($allowed_staff_permission['change_status']) && !empty($allowed_staff_permission['change_status'])) { ?>

                                                    <li class="mb-1"><a href="update-status.php?id=<?php echo $candidate->id ?>" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen  f-14 text-black me-2"></i>

                                                            Change Status</a></li>



                                                <?php } ?>

                                                <li class="mb-1"><a href="comment.php?id=<?php echo $candidate->id ?>" class="no-decoration f-14 w-600 text-black"><i class="bi bi-book  f-14 text-black me-2"></i>

                                                        Comment</a></li>



                                            </ul>

                                        </div>



                                    </td>

                                    <td class="f-14"><?php echo $i++ ?></td>

                                    <td class="f-14"><?php echo $candidate->order_id ?></td>



                                    <td class="f-14 place_show <?php if (!isset($table_columns_data['place']) || empty($table_columns_data['place'])) { ?> custom_hide<?php } ?>" <?php if(isset($_GET['service']) && $_GET['service'] == 3){ ?>style="display:none"<?php } ?>><?php echo !empty($place) ? $place->name : "Video" ?></td>

                                    <td data-tool-id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseleave="tooltipLeave(this);" onmouseenter="tooltipHover(this)" class="f-14 name_show <?php if (!isset($table_columns_data['name']) || empty($table_columns_data['name'])) { ?> custom_hide<?php } ?>"><a href="invoice.php?sno=<?= $key + 1 ?>&id=<?php echo $candidate->id ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>" class="no-decoration text-black open-candidate " data-sno="<?= $key + 1 ?>" data-id="<?php echo $candidate->id ?>" data-status="<?php echo $_GET['status'] ?? '' ?>"><?php echo $candidate->name . " " . $candidate->surname ?>

                                        </a>

                                    </td>

                                    <td class="f-14 customer_show <?php if (!isset($table_columns_data['customer']) || empty($table_columns_data['customer'])) { ?> custom_hide<?php } ?>"><a class="no-decoration text-black" href="<?php if (isset($allowed_staff_permission['update_customer']) && !empty($allowed_staff_permission['update_customer'])) { ?>update-customer.php?id=<?php echo $customer->id ?><?php } else { ?>#<?php } ?>"><?php echo !empty($customer->name) ? $customer->name : null ?></a></td>

                                    <td class="f-14 company_show <?php if (!isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>"><a class="no-decoration text-black" href="<?php if (isset($allowed_staff_permission['update_customer']) && !empty($allowed_staff_permission['update_customer'])) { ?>update-customer.php?id=<?php echo $customer->id ?><?php } else { ?>#<?php } ?>"><?php echo !empty($customer->company) ? $customer->company : 'Null' ?></a></td>

                                    <td class="f-14 staff_show <?php if (!isset($table_columns_data['staff']) || empty($table_columns_data['staff'])) { ?> custom_hide<?php } ?>"><?php echo !empty($staff) ? $staff->name : "Not Assigned" ?></td>

                                    <td class="f-14 reported_sm_show <?php if (!isset($table_columns_data['reported_sm']) || empty($table_columns_data['reported_sm'])) { ?> custom_hide<?php } ?>">

                                        <input class="form-check-input reported_sm" data-rid="<?php echo $candidate->id ?>" id="reported-<?php echo $candidate->id ?>" <?php echo $candidate->reported_to_sm == 1 ? 'checked' : '' ?> name="reported_to_sm" value="<?php echo $candidate->id ?>" type="checkbox" onclick="check_reported_by(this)">

                                        <label class="form-check-label" for="reported-<?php echo $candidate->id ?>" class="mr-2 label-table"></label>

                                    </td>

                                    <?php $status = getStatusById($candidate->status) ?>

                                    <td class=" d-flex justify-content-center status_show <?php if (!isset($table_columns_data['status']) || empty($table_columns_data['status'])) { ?> custom_hide<?php } ?>">

                                        <div class="status-approved" style="background-color: <?php echo $status->color ?>"><?php echo $status->status ?></div>

                                    </td>

                                    <!-- <td class="f-14">
                                        <?php
                                        // Build Identity HTML like old table
                                        $svcCat = isset($candidate->service_cat_id) ? (int)$candidate->service_cat_id : 0;
                                        $isVerified = isset($candidate->is_verified) ? (int)$candidate->is_verified : -1;
                                        if ($svcCat != 1 && $svcCat != 9 && $svcCat != 10) {
                                            echo '<span class="badge bg-info">N/A</span>';
                                        } elseif ($isVerified === 1) {
                                            echo '<span class="badge bg-success">Verified</span>';
                                        } elseif ($isVerified === 0) {
                                            echo '<span class="badge bg-warning">Pending</span> '
                                                . '<button type="button" class="btn btn-sm btn-outline-primary ms-2 resent-verification-btn" data-candidate-id="' . $candidate->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Resend verification link">'
                                                . '<i class="fas fa-redo-alt"></i></button>';
                                        } else { // Rejected
                                            echo '<span class="badge bg-danger">Rejected</span> '
                                                . '<button type="button" class="btn btn-sm btn-outline-primary ms-2 resent-verification-btn" data-candidate-id="' . $candidate->id . '" data-bs-toggle="tooltip" data-bs-placement="top" title="Resend verification link">'
                                                . '<i class="fas fa-redo-alt"></i></button>';
                                        }
                                        ?>
                                    </td> -->

                                    <td class="f-14 invoice_sent_show <?php if (!isset($table_columns_data['invoice_sent']) || empty($table_columns_data['invoice_sent'])) { ?> custom_hide<?php } ?>">

                                        <input class="form-check-input invoice_sent" data-id="<?php echo $candidate->id ?>" id="incoice-<?php echo $candidate->id ?>" <?php echo $candidate->invoice_sent == 1 ? 'checked' : '' ?> name="invoice_sent" value="<?php echo $candidate->id ?>" type="checkbox">

                                        <label class="form-check-label" for="incoice-<?php echo $candidate->id ?>" class="mr-2 label-table"></label>

                                    </td>

                                    <td class="f-14 interview_date_show <?php if (!isset($table_columns_data['interview_date']) || empty($table_columns_data['interview_date'])) { ?> custom_hide<?php } ?>"><?php echo !empty($candidate->booked) ? $candidate->booked : "Null" ?></td>

                                    <td class="economy_show <?php if (!isset($table_columns_data['economy']) || empty($table_columns_data['economy'])) { ?> custom_hide<?php } ?>">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" <?php echo $candidate->economy == 0 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>">

                                                <span class="custom-economy-radio uncheck_economy" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" <?php echo $candidate->economy == 1 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>">

                                                <span class="custom-economy2-radio check_economy" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                        </div>

                                    </td>

                                    <td class="criminal_record_show <?php if (!isset($table_columns_data['criminal_record']) || empty($table_columns_data['criminal_record'])) { ?> custom_hide<?php } ?>">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" <?php echo $candidate->criminal_record == 0 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-criminal">

                                                <span class="custom-economy-radio uncheck_criminal" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" <?php echo $candidate->criminal_record == 1 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-criminal">

                                                <span class="custom-economy2-radio check_criminal" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                        </div>

                                    </td>

                                    <td class="social_record_show  <?php if (!isset($table_columns_data['social_record']) || empty($table_columns_data['social_record'])) { ?> custom_hide<?php } ?>">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" <?php echo $candidate->social == 0 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-social">

                                                <span class="custom-economy-radio uncheck_social" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" <?php echo $candidate->social == 1 ? 'checked' : '' ?> type="radio" name="<?php echo $candidate->order_id ?>-social">

                                                <span class="custom-economy2-radio check_social" data-id="<?php echo $candidate->id ?>"></span>

                                            </label>

                                        </div>

                                    </td>





                                    <td class="f-14 background_check_date"><?php echo !empty($candidate->background_check_date) ? $candidate->background_check_date : 'Null' ?></td>

                                    <td class="f-14 order_created_show <?php if (!isset($table_columns_data['order_created']) || empty($table_columns_data['order_created'])) { ?> custom_hide<?php } ?>"><?php echo $candidate->created ?></td>

                                    <td class="f-14 archive_date_show <?php if (!isset($table_columns_data['archive_date']) || empty($table_columns_data['archive_date'])) { ?> custom_hide<?php } ?>"><?php echo !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null" ?></td>

                                    <td class="f-14"><?php echo $interview->title ?></td> <!-- Service Type -->



                                </tr>



                            <?php endforeach; ?>

                        <?php endif; ?>



                    </tbody>

                </table>

            </div>

        </div>

    </form>

    <?php if (!empty($candidates)) : ?>

        <?php foreach ($candidates as $key => $candidate) : ?>

            <?php

            $query = "SELECT * FROM history WHERE order_id = {$candidate->id}";

            $stmt = $conn->prepare($query);

            $stmt->execute();

            $history = $stmt->fetchAll();

            ?>

            <span class="his_tooltiptext text-left pl-4 pr-3 pt-2 pb-2" id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseenter="tooltipOver(this)" onmouseleave="tooltipCardLeave(this)">

                <h5><b><u>Order History</u></b></h5>

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

<script>

    const interviewID = <?php echo INTERVIEW_ID; ?>;



    //    document.addEventListener('DOMContentLoaded', function() {

    //        var contentModal = document.getElementById('content-modal');

    //        var modalDialog = contentModal.querySelector('.modal-dialog');

    //        modalDialog.classList.add('modal-xl');

    //    });

</script>

<!-- <?php // if (!empty($candidates)) : 

        ?> -->

<!-- <script> -->

<!-- // let _candidates = <?php // echo json_encode($candidates); 

                            ?>; -->

<!-- // </script> -->

<!-- <?php // else : 

        ?> -->

<!-- <script> -->

<!-- // let _candidates = null; -->

<!-- // </script> -->

<!-- <?php // endif; 

        ?> -->

<script>

    function scroll_right() {

        var scrollBody = $('.dataTables_scrollBody');

        var scrollLeftPosition = scrollBody.scrollLeft();

        scrollBody.animate({

            scrollLeft: scrollLeftPosition + 300

        });

    }



    function scroll_left() {

        var scrollBody = $('.dataTables_scrollBody');

        var scrollLeftPosition = scrollBody.scrollLeft();

        scrollBody.animate({

            scrollLeft: scrollLeftPosition - 300

        });

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


        $(obj).mouseenter(function() {

            clearTimeout(tooltipTimer);

        });

    }

    function tooltipCardLeave(card) {
        $(card).stop(true, true).animate({ opacity: 0 }, {
            duration: 200,
            step: function (now) { $(this).css('transform', 'translateX(' + (now * 100) + '%)'); },
            complete: function () { $(this).css('visibility', 'hidden'); }
        });
    }

    function hideAllTooltips() {
        $('.his_tooltiptext').each(function(){
            var card = $(this);
            var id = card.attr('id');
            var trigger = $('[data-tool-id="' + id + '"]');
            if (!card.is(':hover') && !trigger.is(':hover')) {
                hideTooltipElement(card);
            }
        });
    }

    function hideTooltipElement(card) {
        card.stop(true, true).animate({ opacity: 0 }, {
            duration: 200,
            step: function (now) { $(this).css('transform', 'translateX(' + (now * 100) + '%)'); },
            complete: function () { $(this).css('visibility', 'hidden'); }
        });
    }

    function filter_data() {

        var place = $('#fil_place').val()

        var status = $('#fil_status').val()

        var company = $('#fil_com').val()

        var customer = $('#fil_cus').val()

        var candidate = $('#fil_can').val()

        var order_created_from = $('#order_created_from').val()

        var order_created_to = $('#order_created_to').val()

        var interview_date_from = $('#interview_date_from').val()

        var interview_date_to = $('#interview_date_to').val()

        var where_condition = "<?php echo htmlspecialchars($candidates_addition_query, ENT_QUOTES); ?>";

        $.ajax({

            type: "POST",

            url: "../includes/pages.php",

            data: {

                'filter_candidates': 1,

                'place': place,

                'status': status,

                'company': company,

                'customer': customer,

                'candidate': candidate,

                'order_created_from': order_created_from,

                'order_created_to': order_created_to,

                'interview_date_from': interview_date_from,

                'interview_date_to': interview_date_to,

                'where_condition': where_condition,

            },

            success: function(response) {

                if (response != '') {

                    $('#table').html('');

                    var html = '';

                    response = JSON.parse(response);

                    html = `<table id="dataTable" data-table="candidate" class="display Table" style="width: 100% !important">

                <thead>

                    <tr>

                        <th></th>

                        <th class="table-head">

                            <input class="form-check-input d-check" id="delete-all" name="all" type="checkbox">

                            <label class="form-check-label" for="delete-all" class="mr-2 label-table"></label>

                        </th>

                        <th class="table-head">Action</th>

                        <th class="table-head">#</th>

                        <th class="table-head">Order ID</th>



                        <th class="table-head  place_show <?php if (!isset($table_columns_data['place']) || empty($table_columns_data['place'])) { ?> custom_hide<?php } ?>" <?php if(isset($_GET['service']) && $_GET['service'] == 3){ ?>style="display:none"<?php } ?>>Place</th>

                        <th class="table-head">VASC ID</th>

                        <th class="table-head name_show <?php if (!isset($table_columns_data['name']) || empty($table_columns_data['name'])) { ?> custom_hide<?php } ?>">Name</th>

                        <th class="table-head">SSN</th>

                        <th class="table-head  customer_show <?php if (!isset($table_columns_data['customer']) || empty($table_columns_data['customer'])) { ?> custom_hide<?php } ?>">Customer</th>

                        <th class="table-head  company_show <?php if (!isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>">Company</th>

                        <th class="table-head  staff_show <?php if (!isset($table_columns_data['staff']) || empty($table_columns_data['staff'])) { ?> custom_hide<?php } ?>">Staff</th>

                        <th class="table-head  reported_sm_show <?php if (!isset($table_columns_data['reported_sm']) || empty($table_columns_data['reported_sm'])) { ?> custom_hide<?php } ?>">Reported</th>

                        <th class="table-head  status_show <?php if (!isset($table_columns_data['status']) || empty($table_columns_data['status'])) { ?> custom_hide <?php } ?>">Status</th>

                        

                        <th class="table-head  invoice_sent_show <?php if (!isset($table_columns_data['invoice_sent']) || empty($table_columns_data['invoice_sent'])) { ?> custom_hide<?php } ?>">Invoice Sent</th>

                        <th class="table-head  interview_date_show <?php if (!isset($table_columns_data['interview_date']) || empty($table_columns_data['interview_date'])) { ?> custom_hide<?php } ?>">Interview Date</th>





                        <th class="table-head  economy_show <?php if (!isset($table_columns_data['economy']) || empty($table_columns_data['economy'])) { ?> custom_hide <?php } ?>">Economy</th>

                        <th class="table-head  criminal_record_show <?php if (!isset($table_columns_data['criminal_record']) || empty($table_columns_data['criminal_record'])) { ?> custom_hide<?php } ?>">Criminal Record</th>

                        <th class="table-head  social_record_show <?php if (!isset($table_columns_data['social_record']) || empty($table_columns_data['social_record'])) { ?> custom_hide<?php } ?>">Social Media</th>



                        <th class="table-head">Invoice Date</th>

                        // <th class="table-head">Background Check Date</th>



                        <th class="table-head  order_created_show <?php if (!isset($table_columns_data['order_created']) || empty($table_columns_data['order_created'])) { ?> custom_hide<?php } ?>">Order Created</th>



                        <!--                    --><?php //if((isset($_GET['service']) && $_GET['service'] == INTERVIEW_ID) || !isset($_GET['service'])): 

                                                    ?>



                        <!--                    --><?php //endif; 

                                                    ?>

                        <th class="table-head">Delivery Date</th>

                        <th class="table-head">Service Type</th>

                        



                    </tr>

                </thead>

                <tbody>`

                    var i = 0

                    $(response).each(function(k, v) {

                        i++

                        html += `<tr>

                                    <td></td>

                                    <td class="f-14">

                                        <input class="form-check-input d-check delete-candidate" id="checkbox-` + v.id + `" name="delete[]" value="` + v.id + `" type="checkbox">

                                        <label class="form-check-label" for="checkbox-` + v.id + `" class="mr-2 label-table"></label>

                                    </td>



                                    <td>

                                        <div class="dropdown">

                                            <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">

                                                <i class="bi bi-gear"></i>

                                            </button>

                                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                                <?php if (isset($allowed_staff_permission['update_candidate']) && !empty($allowed_staff_permission['update_candidate'])) { ?>

                                                    <li class="mb-1"><a href="update-candidate.php?id=` + v.id + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                                            Edit</a>

                                                    </li>

                                                <?php } ?>

                                                <li class="mb-1"><a href="invoice.php?id=` + v.id + `" class="no-decoration f-14 w-600 text-black"><i class="bi bi-eye  f-14 text-black me-2"></i>

                                                        View</a></li>



                                                <?php if (isset($allowed_staff_permission['change_staff']) && !empty($allowed_staff_permission['change_staff'])) { ?>

                                                    <li class="mb-1"><a href="change-staff.php?id=` + v.id + `" class="no-decoration f-14 w-600 text-black"><i class="bi bi-people  f-14 text-black me-2"></i>

                                                            Change Staff</a></li>

                                                <?php } ?>



                                                <?php if (isset($allowed_staff_permission['change_status']) && !empty($allowed_staff_permission['change_status'])) { ?>

                                                    <li class="mb-1"><a href="update-status.php?id=` + v.id + `" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen  f-14 text-black me-2"></i>

                                                            Change Status</a></li>



                                                <?php } ?>

                                                <li class="mb-1"><a href="comment.php?id=` + v.id + `" class="no-decoration f-14 w-600 text-black"><i class="bi bi-book  f-14 text-black me-2"></i>

                                                        Comment</a></li>



                                            </ul>

                                        </div>



                                    </td>

                                    <td class="f-14">` + i + `</td>

                                    <td class="f-14">` + v.order_id + `</td>



                                    <td class="f-14 place_show " <?php if(isset($_GET['service']) && $_GET['service'] == 3){ ?>style="display:none"<?php } ?>>` + (v.place_name != '' ? v.place_name : 'Video') + `</td>

                                    <td class="f-14">` + (v.vasc_id != '' ? v.vasc_id : 'Null') + `</td>

                                    <td data-tool-id="his_tooltip_` + v.order_id + `" onmouseleave="tooltipLeave(this);" onmouseenter="tooltipHover(this)" class="f-14 name_show "><a href="invoice.php?sno=` + i + `&id=` + v.id + `" class="no-decoration text-black open-candidate " data-sno="` + i + `" data-id="` + v.id + `">` + v.name + " " + v.surname + `</a></td>

                                    <td class="f-14">` + v.security + `</td>

                                    <td class="f-14 customer_show "><a class="no-decoration text-black" href="update-customer.php?id=` + v.cus_id + `">` + v.customer_name + `</a></td>

                                    <td class="f-14 company_show "><a class="no-decoration text-black" href="update-customer.php?id=` + v.cus_id + `">` + v.customer_company + `</a></td>

                                    <td class="f-14 staff_show ">` + (v.staff_name != '' ? v.staff_name : 'Not Assigned') + `</td>

                                    <td class="f-14 reported_sm_show">

                                        <input class="form-check-input reported_sm" data-rid="` + v.id + `" id="reported-` + v.id + `" ` + (v.reported_to_sm === 1 ? 'checked' : '') + ` name="reported_to_sm" value="` + v.id + `" type="checkbox" onclick="check_reported_by(this)">

                                        <label class="form-check-label" for="reported-` + v.id + `" class="mr-2 label-table"></label>

                                    </td>

                                    <td class=" d-flex justify-content-center status_show ">

                                        <div class="status-approved" style="background-color: ` + v.status_color + `">` + v.status_name + `</div>

                                    </td>

                                    <td class="f-14 invoice_sent_show ">

                                        <input class="form-check-input invoice_sent" data-id="` + v.id + `" id="incoice-` + v.id + `" ` + (v.invoice_sent === 1 ? 'checked' : '') + ` name="invoice_sent" value="` + v.id + `" type="checkbox" onclick="fun_invoice_date(this)">

                                        <label class="form-check-label" for="incoice-` + v.id + `" class="mr-2 label-table"></label>

                                        </td>

                                        <td class="f-14 interview_date_show">` + v.booked + `</td>

                                    <td class="economy_show ">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" ` + (v.economy === 0 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `">

                                                <span class="custom-economy-radio uncheck_economy" data-id="` + v.id + `"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" ` + (v.economy === 1 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `">

                                                <span class="custom-economy2-radio check_economy" data-id="` + v.id + `"></span>

                                            </label>

                                        </div>

                                    </td>

                                    <td class="criminal_record_show ">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" ` + (v.criminal_record === 0 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `-criminal">

                                                <span class="custom-economy-radio uncheck_criminal" data-id="` + v.id + `"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" ` + (v.criminal_record === 1 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `-criminal">

                                                <span class="custom-economy2-radio check_criminal" data-id="` + v.id + `"></span>

                                            </label>

                                        </div>

                                    </td>

                                    <td class="social_record_show ">

                                        <div class="d-flex justify-content-center ">

                                            <label class="me-2">

                                                <input class="economy-radio" ` + (v.social === 0 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `-social">

                                                <span class="custom-economy-radio uncheck_social" data-id="` + v.id + `"></span>

                                            </label>

                                            <label>

                                                <input class="economy2-radio" ` + (v.social === 1 ? 'checked' : '') + ` type="radio" name="` + v.order_id + `-social">

                                                <span class="custom-economy2-radio check_social" data-id="` + v.id + `"></span>

                                            </label>

                                        </div>

                                    </td>

                                    <td class="f-14 invoice_date">` + v.invoice_date + `</td>

                                    <td class="f-14 background_check_date">` + v.background_check_date + `</td>

                                    <td class="f-14 order_created_show">` + v.created + `</td>

                                    <td class="f-14">` + v.delivery_date + `</td>

                                    <td class="f-14">` + v.interview_title + `</td>

                                </tr>`

                    })

                    html += `</tbody>

            </table>`

                    $('#table').html(html)

                    var table = $('#dataTable').DataTable({

                        language: {

                            search: "",

                            searchPlaceholder: "Search..."

                        },

                        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>t<'row'<'col-sm-6'i><'col-sm-6'p>><'row'<'col-sm-12'B>>",

                        buttons: [{

                            extend: 'excelHtml5',

                            exportOptions: {

                                columns: [3, 4, 5, 6, 7, 8, 9, 10, 15, 16, 17, 18, 19, 20], // exclude columns 1, 2, 9, 10, and 11

                            },

                        }],

                        scrollX: true,

                        "order": [],

                        "pageLength": 100,

                        columnDefs: [{

                                type: 'checkbox', // use the custom 'checkbox' sorting function for this column

                                targets: [12, 13, 14] // the index of the checkbox column (zero-based)

                            },

                            {

                                className: 'dt-control',

                                orderable: false,

                                targets: 0 // Adjust the target column as needed

                            }



                        ]

                    });

                    for (var key in hiddenColumns) {

                        if (key != 19) {

                            table.column(key).visible(false);

                        }

                    }



                    var url = new URL(window.location.href);

                    var serviceID = url.searchParams.get('service');

                    // Convert to number for comparison
                    var serviceIDNum = serviceID ? parseInt(serviceID, 10) : null;

                    // Function to hide/show columns for service=3
                    function hideColumnsForService3() {
                        if (serviceIDNum === 3) {
                            // Hide Place column (index 5) for service=3
                            table.column(5).visible(false);
                            // Hide Interview Date column (index 13) for service=3
                            table.column(13).visible(false);
                            // Show Background Check Date column (index 17) for service=3
                            table.column(17).visible(true);
                            table.column(18).visible(true);
                            table.column(15).visible(false);
                            table.column(19).visible(true);
                            // Show Delivery Date column (index 20) for service=3
                            table.column(20).visible(true);
                            table.column(21).visible(true);
                        } else if (serviceIDNum !== null && serviceIDNum !== '' && serviceIDNum !== 3) {
                            table.column(17).visible(false);
                            table.column(20).visible(false);
                        } else {
                            table.column(17).visible(false);
                            table.column(20).visible(true);
                        }
                    }

                    // Hide columns immediately and also on init event
                    hideColumnsForService3();
                    
                    // Also ensure columns are hidden after table is fully initialized
                    table.on('init.dt', function() {
                        hideColumnsForService3();
                    });
                    
                    // Ensure columns stay hidden after each draw
                    table.on('draw.dt', function() {
                        hideColumnsForService3();
                    });



                    // Add event listener for opening and closing details

                    table.on('click', 'td.dt-control', function(e) {

                        let tr = e.target.closest('tr');

                        let row = table.row(tr);



                        if (row.child.isShown()) {

                            // This row is already open - close it

                            row.child.hide();

                        } else {

                            // Open this row

                            row.child(format(row.data(), row.index())).show();

                        }

                    });

                }

                $('.paginate_button').each(function(i, v) {

                    $(this).attr('onclick', 'reinitiateDataTable()')

                })

                $('select[name="dataTable_length"]').attr('onchange', 'reinitiateDataTable()')

                $('#dataTable_filter').find('input[type="search"]').attr('oninput', 'reinitiateDataTable()')

                $('.sorting').each(function() {

                    $(this).attr('onclick', 'reinitiateDataTable()');

                })

                $('.dropdownBtn').click(function(event) {

                    event.stopPropagation();

                    var drop = $(this);

                    drop.addClass('right-one');

                    $('.dropdownBtn').each(function() {

                        if ($(this).hasClass('right-one')) {

                            if ($(this).closest('td').find('.dropdown-menu').hasClass('show')) {

                                $(this).closest('td').find('.dropdown-menu').removeClass('show')

                                $(this).removeClass('right-one')

                            } else {

                                $(this).closest('td').find('.dropdown-menu').addClass('show')

                            }

                        } else {

                            $(this).closest('td').find('.dropdown-menu').removeClass('show')

                        }

                    })

                    dropDownFixPosition($(this), $(this).closest('td').find('.dropdown-menu'));

                })



                function dropdown_open(event) {

                    var drop = $(event);

                    drop.addClass('right-one');

                    $('.dropdownBtn').each(function() {

                        if ($(this).hasClass('right-one')) {

                            if ($(this).closest('td').find('.dropdown-menu').hasClass('show')) {

                                $(this).closest('td').find('.dropdown-menu').removeClass('show')

                                $(this).removeClass('right-one')

                            } else {

                                $(this).closest('td').find('.dropdown-menu').addClass('show')

                            }

                        } else {

                            $(this).closest('td').find('.dropdown-menu').removeClass('show')

                        }

                    })

                    dropDownFixPosition($(event), $(event).closest('td').find('.dropdown-menu'));

                }



                function dropDownFixPosition(button, dropdown) {

                    var button_drop = button[0].getBoundingClientRect();

                    var top = parseInt(button_drop.top) + 23;

                    var left = parseInt(button_drop.left) - 15

                    if (dropdown.closest('tr').is(':last-child')) {

                        if (dropdown.height() > 200) {

                            top = 295;

                        }

                    }

                    dropdown.css('top', top + "px");

                    dropdown.css('left', left + "px");

                    dropdown.css('position', 'fixed');

                }



                // Update the dropdown position when scrolling the table container

                $('#dataTable').scroll(function() {

                    $('.dropdown-menu').each(function() {

                        if ($(this).hasClass('show') == true) {

                            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));

                        }

                    });

                });



                // Update the dropdown position when scrolling the window

                $(window).scroll(function() {

                    $('.dropdown-menu').each(function() {

                        if ($(this).hasClass('show') == true) {

                            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));

                        }

                    });

                });

                $('.modal, .modal-body').scroll(function() {

                    $('.dropdown-menu').each(function() {

                        if ($(this).hasClass('show') == true) {

                            dropDownFixPosition($(this).closest('.dropdown').find('.dropdownBtn'), $(this));

                        }

                    });

                });

                reinitiateDataTable()

            }

        });

    }

    function check_reported_by(obj) {

        var can_id = $(obj).data('rid');

        var reported = null

        var rep_date = 'Null'

        if ($(obj).is(':checked')) {

            reported = 1

            var d = new Date();



            var month = d.getMonth() + 1;

            var day = d.getDate();



            var rep_date = d.getFullYear() + '/' +

                (month < 10 ? '0' : '') + month + '/' +

                (day < 10 ? '0' : '') + day;

        } else {

            reported = 2

        }

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                'reported_to_sm': 1,

                'can_id': can_id,

                'reported': reported

            },

            success: function(response) {}

        });



    }

</script>
<script>
var j_arr = <?= json_encode(isset($arr) ? $arr : []) ?>; // Convert the PHP array to a JavaScript array
if (Object.keys(j_arr).length > 0) { // Check if the array is not empty
    for (var key in j_arr) {
        if (j_arr.hasOwnProperty(key)) {
            var element = document.getElementById(key); // Get the element by the key (id)
            if (element) {
                element.textContent = j_arr[key]; // Set the element's text to the value from j_arr
            }
        }
    }
}
</script>