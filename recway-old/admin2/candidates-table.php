<?php
$query = "SELECT * FROM tables_settings WHERE name = 'Candidate'";
$stmt = $conn->prepare($query);
$stmt->execute();
$table = $stmt->fetchAll();
$table_columns_data = null;
if (! empty($table[0]->meta_data)) {
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
</style>
<div class="table-div">
    <form action="" method="post" id="d-form">
        <div class="card card-cascade narrower mb-4">
            <!--Card image-->
            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">
                <a href="#" class="white-text mx-3"><?php echo isset($candidatesCustomer) ? $candidatesCustomer . "'s " : "" ?>Candidates</a>
                <div style="display:flex !important">
                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Candidate">
                        <span onclick="location.href='add-candidate.php'"><i class="bi bi-person-plus"></i></span>
                    </button>
                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" onclick="show_add_card(this)">
                        <i class="bi bi-filter"></i>
                    </button>
                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text2" data-toggle="tooltip" data-placement="top" title="Change Staff">
                        <span class=""><i class="bi bi-pen"></i></span>
                    </button>
                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text4" data-toggle="tooltip" data-placement="top" title="Change Status">
                        <span class=""><i class="bi bi-clipboard-data"></i></span>
                    </button>
                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text" data-toggle="tooltip" data-placement="top" title="Remove Order">
                        <span class=""><i class="bi bi-trash"></i></span>
                    </button>
                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right" onclick="show_card(this)" style="margin-top: 6px !important;">
                        <i class="bx bxs-chevron-down arrow"></i>
                    </button>
                </div>
            </div>
            <div class="col-md-12">
                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">
                    <div class="card-body" style="display: none !important;">
                        <div class="row">
                            <input type="hidden" id="table_id" value="<?= $table[0]->id ?>">
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="place" name="column[candidate][place]" data-id="place_show" value="1" <?php if (isset($table_columns_data['place']) && ! empty($table_columns_data['place'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="place">Place</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="name" name="column[candidate][name]" data-id="name_show" value="1" <?php if (isset($table_columns_data['name']) && ! empty($table_columns_data['name'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="name">Name</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="customer" name="column[candidate][customer]" data-id="customer_show" value="1" <?php if (isset($table_columns_data['customer']) && ! empty($table_columns_data['customer'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="customer">Customer</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[candidate][company]" data-id="company_show" value="1" <?php if (isset($table_columns_data['company']) && ! empty($table_columns_data['company'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="company">Company</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="staff" name="column[candidate][staff]" data-id="staff_show" value="1" <?php if (isset($table_columns_data['staff']) && ! empty($table_columns_data['staff'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="staff">Staff</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="status" name="column[candidate][status]" data-id="status_show" value="1" <?php if (isset($table_columns_data['status']) && ! empty($table_columns_data['status'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="status">Status</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" data-id="invoice_sent_show" value="1" <?php if (isset($table_columns_data['invoice_sent']) && ! empty($table_columns_data['invoice_sent'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="economy" name="column[candidate][economy]" data-id="economy_show" value="1" <?php if (isset($table_columns_data['economy']) && ! empty($table_columns_data['economy'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="economy">Economy</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" data-id="criminal_record_show" value="1" <?php if (isset($table_columns_data['criminal_record']) && ! empty($table_columns_data['criminal_record'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="social_record" name="column[candidate][social_record]" data-id="social_record_show" value="1" <?php if (isset($table_columns_data['social_record']) && ! empty($table_columns_data['social_record'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="social_record">Social Media</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="reported_sm" name="column[candidate][reported_sm]" data-id="reported_sm_show" value="1" <?php if (isset($table_columns_data['reported_sm']) && ! empty($table_columns_data['reported_sm'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="reported_sm">Reported</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="order_created" name="column[candidate][order_created]" data-id="order_created_show" value="1" <?php if (isset($table_columns_data['order_created']) && ! empty($table_columns_data['order_created'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="interview_date" name="column[candidate][interview_date]" data-id="interview_date_show" value="1" <?php if (isset($table_columns_data['interview_date']) && ! empty($table_columns_data['interview_date'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                            </div>
                            <div class="col-md-3">
                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="archive_date" name="column[candidate][archive_date]" data-id="archive_date_show" value="1" <?php if (isset($table_columns_data['archive_date']) && ! empty($table_columns_data['archive_date'])) { ?> checked <?php } ?>>
                                <label class="form-label form-check-label" for="archive_date">Archive Time</label>
                            </div>
                        </div>
                    </div>
                    <div class="row p-3 pt-0" id="show_add_card" style="display: none !important;">
                        <div class="col-md-3 p-3">
                            <label>Place</label>
                            <select class="form-control filter-select" id="fil_place">
                                <option value="">Filter by Place</option>
                                <?php if (! empty($places)) { ?>
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
                                <?php if (! empty($customers)) : $companies = [] ?>
                                    <?php foreach ($customers as $customer) : ?>
                                        <?php if (! in_array(strtolower(trim($customer->company)), $companies)) : ?>
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
                                <?php if (! empty($customers)) { ?>
                                    <?php foreach ($customers as $customer) { ?>
                                        <option value="<?= $customer->id ?>"><?= $customer->name ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Order Created From</label>
                            <input type="date" id="order_created_from" class="form-control">
                            <input type="hidden" id="fil_status" value="<?php if (isset($_GET['status']) && ! empty($_GET['status'])) { ?><?= $_GET['status'] ?><?php } ?>">
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
                            <label>Interview Date To</label>
                            <input type="date" id="interview_date_to" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Delivery Date From</label>
                            <input type="date" id="delivery_date_from" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Delivery Date To</label>
                            <input type="date" id="delivery_date_to" class="form-control">
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
                            <th class="table-head  place_show <?php if (! isset($table_columns_data['place']) || empty($table_columns_data['place'])) { ?> custom_hide<?php } ?>" <?php if (isset($_GET['service']) && $_GET['service'] == 3) { ?>style="display:none"<?php } ?>>Place</th>
                            <th class="table-head">VASC ID</th>
                            <th class="table-head name_show <?php if (! isset($table_columns_data['name']) || empty($table_columns_data['name'])) { ?> custom_hide<?php } ?>">Name</th>
                            <th class="table-head">SSN</th>
                            <th class="table-head  customer_show <?php if (! isset($table_columns_data['customer']) || empty($table_columns_data['customer'])) { ?> custom_hide<?php } ?>">Customer</th>
                            <th class="table-head  company_show <?php if (! isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>">Company</th>
                            <th class="table-head  staff_show <?php if (! isset($table_columns_data['staff']) || empty($table_columns_data['staff'])) { ?> custom_hide<?php } ?>">Staff</th>
                            <th class="table-head  reported_sm_show <?php if (! isset($table_columns_data['reported_sm']) || empty($table_columns_data['reported_sm'])) { ?> custom_hide<?php } ?>">Reported</th>
                            <th class="table-head  status_show <?php if (! isset($table_columns_data['status']) || empty($table_columns_data['status'])) { ?> custom_hide <?php } ?>">Status</th>
                            <th class="table-head  invoice_sent_show <?php if (! isset($table_columns_data['invoice_sent']) || empty($table_columns_data['invoice_sent'])) { ?> custom_hide<?php } ?>">Invoice Sent</th>
                            <th class="table-head  interview_date_show <?php if (! isset($table_columns_data['interview_date']) || empty($table_columns_data['interview_date']) || (isset($_GET['service']) && $_GET['service'] == 3)) { ?> custom_hide<?php } ?>" <?php if (isset($_GET['service']) && $_GET['service'] == 3) { ?>style="display:none"<?php } ?>>Interview Date</th>
                            <th class="table-head  economy_show <?php if (! isset($table_columns_data['economy']) || empty($table_columns_data['economy'])) { ?> custom_hide <?php } ?>">Economy</th>
                            <th class="table-head  criminal_record_show <?php if (! isset($table_columns_data['criminal_record']) || empty($table_columns_data['criminal_record'])) { ?> custom_hide<?php } ?>">Criminal Record</th>
                            <th class="table-head  social_record_show  <?php if (! isset($table_columns_data['social_record']) || empty($table_columns_data['social_record'])) { ?> custom_hide<?php } ?>">Social Media</th>
                            <th class="table-head">Invoice Date</th>
                            <th class="table-head">Background Check Date</th>
                            <th class="table-head  order_created_show <?php if (! isset($table_columns_data['order_created']) || empty($table_columns_data['order_created'])) { ?> custom_hide<?php } ?>">Order Created</th>
                            <th class="table-head  archive_date_show <?php if (! isset($table_columns_data['archive_date']) || empty($table_columns_data['archive_date'])) { ?> custom_hide<?php } ?>">Archive Time</th>
                            <!--                    --><?php //if((isset($_GET['service']) && $_GET['service'] == INTERVIEW_ID) || !isset($_GET['service'])):
                                                        ?>
                            <!--                    --><?php //endif;
                                                        ?>
                            <!-- <th class="table-head">Delivery Date</th> -->
                             <th class="table-head" <?php if (isset($_GET['service']) && $_GET['service'] != 3 && $_GET['service'] != 'all' && $_GET['service'] != '') { ?>style="display:none"<?php } ?>>Delivery Date</th>
                            <th class="table-head">Service Type</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Data will be loaded via AJAX -->
                    </tbody>
                </table>
            </div>
        </div>
    </form>
</div>
<script>
    const interviewID = <?php echo INTERVIEW_ID; ?>;
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
            // Old static tooltip behavior disabled; dynamic tooltip handles hide/show.
            tooltip.css('visibility', 'hidden');
        }, 500);
        // Reset timeout if mouse re-enters the trigger element
        $(obj).mouseenter(function() {
            clearTimeout(tooltipTimer);
        });
    }
    function filter_data() {
        // Reload DataTable with new filters
        if (typeof table !== 'undefined' && table) {
            table.ajax.reload();
        }
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
