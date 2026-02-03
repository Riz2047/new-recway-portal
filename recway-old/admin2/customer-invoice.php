<?php

$activeLink = "customer-invoice";

include_once('includes/header.php');


?>

<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>

        <!-- Invoice Table -->
        <div class="table-div">
            <form action="" method="post" id="d-form">
                <div class="card card-cascade narrower mb-4">
                    <!--Card image-->
                    <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">
                        <a href="#" class="white-text mx-3">Customer Invoices</a>
                        <div style="display:flex !important">
                            <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Export Data">
                                <span onclick="exportData()"><i class="bi bi-download"></i></span>
                            </button>
                            <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" onclick="show_filter_card(this)">
                                <i class="bi bi-filter"></i>
                            </button>
                            <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right" onclick="show_card(this)" style="margin-top: 6px !important;">
                                <i class="bx bxs-chevron-down arrow"></i>
                            </button>
                        </div>
                    </div>
                    <div class="row p-3 pt-0" id="show_filter_card" style="display: none !important;">
                        <div class="col-md-3 p-3">
                            <label>Period Filter</label>
                            <select class="form-control filter-select" id="period_filter">
                                <option value="">Filter by Period</option>
                                <option value="day">Day</option>
                                <option value="week">Week</option>
                                <option value="month">Month</option>
                            </select>
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Status Filter</label>
                            <select class="form-control filter-select" id="status_filter">
                                <option value="">Filter by Status</option>
                                <option value="to_be_invoiced">To be invoiced</option>
                                <option value="sent">Sent</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Customer Name</label>
                            <input type="text" class="form-control" placeholder="Filter By Customer Name" id="fil_customer">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Date From</label>
                            <input type="date" id="date_from" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Date To</label>
                            <input type="date" id="date_to" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <button type="button" class="btn btn-primary btn-sm float-right" onclick="applyFilters()">Apply</button>
                            <button type="button" class="btn btn-danger btn-sm float-right" onclick="clearFilters()">Reset</button>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">
                            <div class="card-body" style="display: none !important;">
                                <div class="row">
                                    <div class="col-md-3">
                                        <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="customer_name" name="column[invoice][customer_name]" data-id="customer_name_show" value="1" checked>
                                        <label class="form-label form-check-label" for="customer_name">Customer Name</label>
                                    </div>
                                    <div class="col-md-3">
                                        <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="period" name="column[invoice][period]" data-id="period_show" value="1" checked>
                                        <label class="form-label form-check-label" for="period">Period</label>
                                    </div>
                                    <div class="col-md-3">
                                        <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="status" name="column[invoice][status]" data-id="status_show" value="1" checked>
                                        <label class="form-label form-check-label" for="status">Status</label>
                                    </div>
                                    
                                    <div class="col-md-3">
                                        <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="created_date" name="column[invoice][created_date]" data-id="created_date_show" value="1" checked>
                                        <label class="form-label form-check-label" for="created_date">Created Date</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="table">
                        <table id="invoiceTable" data-table="invoice" class="display Table" style="width: 100% !important">
                            <thead>
                                <tr>
                                    <th class="table-head customer_name_show">Customer Name</th>
                                    <th class="table-head period_show">Period</th>
                                    <th class="table-head status_show">Status</th>
                                    <th class="table-head created_date_show">Created Date</th>
                                    <th class="table-head">Actions</th>
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
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Update Invoice Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="statusForm">
                    <input type="hidden" id="invoice_id" name="invoice_id">
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Status</label>
                        <select class="form-select" id="new_status" name="status" required>
                            <option value="to_be_invoiced">To be invoiced</option>
                            <option value="sent">Sent</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes (Optional)</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateStatus()">Update Status</button>
            </div>
        </div>
    </div>
</div>

<?php include_once('includes/footer.php'); ?>

<style>
/* Ensure DataTable controls are contained within the card */
#invoiceTable_wrapper {
    padding: 0 !important;
    margin: 0 !important;
}

#invoiceTable_wrapper .dataTables_length,
#invoiceTable_wrapper .dataTables_filter,
#invoiceTable_wrapper .dataTables_info,
#invoiceTable_wrapper .dataTables_paginate {
    padding: 10px 20px !important;
    margin: 0 !important;
}

#invoiceTable_wrapper .dataTables_length {
    padding-top: 20px !important;
}

#invoiceTable_wrapper .dataTables_filter {
    padding-top: 20px !important;
}

#invoiceTable_wrapper .dataTables_info {
    padding-bottom: 20px !important;
}

#invoiceTable_wrapper .dataTables_paginate {
    padding-bottom: 20px !important;
}

/* Style the search input to match the design */
#invoiceTable_wrapper .dataTables_filter input {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px 10px;
    margin-left: 5px;
}

/* Style the length select */
#invoiceTable_wrapper .dataTables_length select {
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 5px;
    margin: 0 5px;
}

/* Style pagination buttons */
#invoiceTable_wrapper .paginate_button {
    border: 1px solid #ddd !important;
    margin: 0 2px !important;
    border-radius: 4px !important;
}

#invoiceTable_wrapper .paginate_button.current {
    background: #007bff !important;
    color: white !important;
    border-color: #007bff !important;
}
.btn-custom{    background: #007bff; color: white;}
/* Improve actions alignment and spacing */
.action-btns { display: inline-flex; align-items: center; gap: .5rem; }
.action-btns .btn { min-width: 115px; }
</style>

<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#invoiceTable').DataTable({
        "processing": true,
        "serverSide": true,
        "ajax": {
            "url": "./includes/invoice_ajax.php",
            "type": "POST",
            "data": function(d) {
                d.period_filter = $('#period_filter').val();
                d.status_filter = $('#status_filter').val();
                d.date_from = $('#date_from').val();
                d.date_to = $('#date_to').val();
                d.fil_customer = $('#fil_customer').val();
            }
        },
        "columns": [
            { "data": "customer_name" },
            { "data": "period" },
            { "data": "status" },
            { "data": "created_date" },
            { 
                "data": "actions",
                "orderable": false,
                "searchable": false
            }
        ],
        "order": [[4, "desc"]],
        "pageLength": 10,
        "responsive": true,
        "deferRender": true,
        "dom": '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
               '<"row"<"col-sm-12"tr>>' +
               '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
        "language": {
            "processing": "Loading invoices...",
            "emptyTable": "No invoices found",
            "zeroRecords": "No matching invoices found"
        }
    });

    // Store table reference globally
    window.invoiceTable = table;
    
    // Ensure DataTable controls are properly contained
    setTimeout(function() {
        var wrapper = $('#invoiceTable_wrapper');
        if (wrapper.length) {
            // Move the wrapper inside the card if it's not already
            if (!wrapper.closest('.card').length) {
                wrapper.appendTo('#table');
            }
        }
    }, 100);
});

function applyFilters() {
    if (window.invoiceTable) {
        window.invoiceTable.ajax.reload();
    }
}

function clearFilters() {
    $('#period_filter').val('');
    $('#status_filter').val('');
    $('#date_from').val('');
    $('#date_to').val('');
    $('#fil_customer').val('');
    if (window.invoiceTable) {
        window.invoiceTable.ajax.reload();
    }
}

function updateInvoiceStatus(invoiceId, currentStatus) {
    $('#invoice_id').val(invoiceId);
    $('#new_status').val(currentStatus);
    $('#notes').val('');
    $('#statusModal').modal('show');
}

function updateStatus() {
    var formData = {
        invoice_id: $('#invoice_id').val(),
        status: $('#new_status').val(),
        notes: $('#notes').val(),
        action: 'update_status'
    };

    $.ajax({
        type: 'POST',
        url: './includes/invoice_ajax.php',
        dataType: 'json',
        data: formData,
        success: function(response) {
            if (response.success) {
                alert('Status updated successfully');
                $('#statusModal').modal('hide');
                if (window.invoiceTable) {
                    window.invoiceTable.ajax.reload();
                }
            } else {
                alert('Error updating status: ' + response.message);
            }
        },
        error: function() {
            alert('Error updating status');
        }
    });
}

function exportData() {
    var params = {
        action: 'export',
        period_filter: $('#period_filter').val(),
        status_filter: $('#status_filter').val(),
        date_from: $('#date_from').val(),
        date_to: $('#date_to').val(),
        fil_customer: $('#fil_customer').val()
    };
    
    var queryString = Object.keys(params).map(key => key + '=' + encodeURIComponent(params[key])).join('&');
    window.open('./includes/invoice_ajax.php?' + queryString, '_blank');
}

function exportInvoice(invoiceId) {
    window.open('./includes/invoice_ajax.php?action=export_invoice&invoice_id=' + invoiceId, '_blank');
}

function show_card(element) {
    // Toggle card visibility functionality
    var card = $(element).closest('.card').find('.card-body');
    card.toggle();
}

function show_filter_card(element) {
    // Toggle filter card visibility
    $('#show_filter_card').toggle();
}

function columns_check(obj) {
    var check = 0;
    if ($(obj).is(':checked')) {
        check = 1;
    }
    
    var columnId = $(obj).attr('data-id');
    var columnName = $(obj).attr('name');
    
    // Show/hide column
    if (check) {
        $('.' + columnId).removeClass('custom_hide');
    } else {
        $('.' + columnId).addClass('custom_hide');
    }
    
    // You can add AJAX call here to save column preferences
    // Similar to how it's done in candidates table
}
</script>
