<?php
$activeLink = "tables";
include_once('includes/header.php');

// flash("adminDeleted", "Admin has been deleted!");
?>
<!-- <?php // if (!isset($_GET['delete'])) : 
        ?> -->
<!-- <?php // flash("adminDeleted"); 
        ?> -->
<!-- <?php // endif; 
        ?> -->
<style>
    div.card-actions button.btn i.arrow:hover {
        transform: rotate(-180deg);
    }
</style>
<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>
        <div class="row">
            <form action="" method="post" id="d-form">
                <div class="card card-cascade narrower mb-4">
                    <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 mb-4 d-flex justify-content-between align-items-center">
                        <a href="#" class="white-text mx-3">Tables</a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Admin </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="ad_name" name="column[admin][name]" value="1">
                                                <label class="form-label form-check-label" for="ad_name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="ad_email" name="column[admin][email]" value="1">
                                                <label class="form-label form-check-label" for="ad_email">Place</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Staff </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="st_name" name="column[staff][name]" value="1">
                                                <label class="form-label form-check-label" for="st_name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="st_email" name="column[staff][email]" value="1">
                                                <label class="form-label form-check-label" for="st_email">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="st_phone" name="column[staff][phone]" value="1">
                                                <label class="form-label form-check-label" for="st_phone">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="st_no_of_orders" name="column[staff][no_of_orders]" value="1">
                                                <label class="form-label form-check-label" for="st_no_of_orders">Invoice Sent</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Customers </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="cust_name" name="column[customers][name]" value="1">
                                                <label class="form-label form-check-label" for="cust_name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="cust_company" name="column[customers][company]" value="1">
                                                <label class="form-label form-check-label" for="cust_company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="cust_status" name="column[customers][status]" value="1">
                                                <label class="form-label form-check-label" for="cust_status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="cust_invoice_sent" name="column[customers][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="cust_invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="cust_economy" name="column[customers][economy]" value="1">
                                                <label class="form-label form-check-label" for="cust_economy">Economy</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="card card-primary">
                                    <div class="card-header bg-primary">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="card-title mb-0">
                                                    <p class="mb-0"><b>Candidate </b>
                                                    </p>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card-actions">
                                                    <button type="button" class="btn bg-primary btn-sm float-right m-0" onclick="show_card(this)"><b><i class="bx bxs-chevron-down arrow"></i></b></button>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p><b>Columns</b></p>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_id" name="column[candidate][order_id]" value="1">
                                                <label class="form-label form-check-label" for="order_id">OrderId</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="place" name="column[candidate][place]" value="1">
                                                <label class="form-label form-check-label" for="place">Place</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="name" name="column[candidate][name]" value="1">
                                                <label class="form-label form-check-label" for="name">Name</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="company" name="column[candidate][company]" value="1">
                                                <label class="form-label form-check-label" for="company">Company</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="status" name="column[candidate][status]" value="1">
                                                <label class="form-label form-check-label" for="status">Status</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="invoice_sent" name="column[candidate][invoice_sent]" value="1">
                                                <label class="form-label form-check-label" for="invoice_sent">Invoice Sent</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="economy" name="column[candidate][economy]" value="1">
                                                <label class="form-label form-check-label" for="economy">Economy</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="criminal_record" name="column[candidate][criminal_record]" value="1">
                                                <label class="form-label form-check-label" for="criminal_record">Criminal Record</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="order_created" name="column[candidate][order_created]" value="1">
                                                <label class="form-label form-check-label" for="order_created">Order Created</label>
                                            </div>
                                            <div class="col-md-3">
                                                <input class="form-check-input" type="checkbox" id="interview_date" name="column[candidate][interview_date]" value="1">
                                                <label class="form-label form-check-label" for="interview_date">Interview Date</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
<?php

include_once('includes/footer.php');

?>