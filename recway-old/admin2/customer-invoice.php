<?php

$activeLink = "customer-invoice";

include_once('includes/header.php');
$query = "SELECT `id`,`name`,`company` FROM customers";
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$serive_cat_query = "SELECT `id`,`name` FROM service_categories";
$serive_cat_stmt = $conn->prepare($serive_cat_query);
$serive_cat_stmt->execute();
$service_categories = $serive_cat_stmt->fetchAll();

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
                            <!-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Export Data">
                                <span onclick="exportData()"><i class="bi bi-download"></i></span>
                            </button> -->
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
                                <!-- <option value="paid">Paid</option> -->
                            </select>
                        </div>
                        <!-- <div class="col-md-3 p-3">
                            <label>Customer Name</label>
                            <input type="text" class="form-control" placeholder="Filter By Customer Name" id="fil_customer">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Company Name</label>
                            <input type="text" class="form-control" placeholder="Filter By Company Name" id="fil_company">
                        </div> -->
                         <div class="col-md-3 p-3">
                            <label>Company Name</label>
                            <select class="form-select  w-100 filter-select" id="fil_company">
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
                            <select class="form-control filter-select" id="fil_customer">
                                <option value="">Filter by Customer</option>
                                <?php if (! empty($customers)) { ?>
                                    <?php foreach ($customers as $customer) { ?>
                                        <option value="<?= $customer->id ?>"><?= $customer->name ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Order Id</label>
                            <input type="text" class="form-control" placeholder="Filter Invoice that Contains this Order" id="fil_order_id">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Service Type</label>
                            <select class="form-control filter-select" id="fil_service_cat">
                                <option value="all">All</option>
                                <?php if (! empty($service_categories)) { ?>
                                    <?php foreach ($service_categories as $service_cats) { ?>
                                        <option value="<?= $service_cats->id ?>"><?= $service_cats->name ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Invoice Created Date From</label>
                            <input type="date" id="date_from" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Invoice Created Date To</label>
                            <input type="date" id="date_to" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Interview Date From</label>
                            <input type="date" id="fil_interview_date_from" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Interview Date To</label>
                            <input type="date" id="fil_interview_date_to" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Delivery Date From</label>
                            <input type="date" id="fil_delivery_date_from" class="form-control">
                        </div>
                        <div class="col-md-3 p-3">
                            <label>Delivery Date To</label>
                            <input type="date" id="fil_delivery_date_to" class="form-control">
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
                                        <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[invoice][company]" data-id="company_show" value="1" checked>
                                        <label class="form-label form-check-label" for="company">Company Name</label>
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
                                    <th class="table-head company_show">Company Name</th>
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
                            <!-- <option value="paid">Paid</option> -->
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
<!-- Status Update Modal -->
<!-- Order List Modal -->
<!-- Order List Modal -->
<div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-fullscreen">
    <div class="modal-content shadow border-0 rounded-0">
      <div class="modal-header bg-primary text-white py-2 sticky-top">
        <h5 class="modal-title" id="orderModalLabel"></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body" id="orderModalBody" style="overflow-y: auto;">
        <div class="text-center text-muted py-3">
          <i class="fas fa-spinner fa-spin me-2"></i> Loading orders...
        </div>
      </div>

      <div class="modal-footer bg-light py-2 sticky-bottom">
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">
          <i class="fas fa-times me-1"></i> Close
        </button>
      </div>
    </div>
  </div>
</div>



<!-- <div class="modal fade" id="orderModal" tabindex="-1" aria-labelledby="orderModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderModalLabel">Invoice Orders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderModalBody">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div> -->


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

/* Style invoice sent checkbox to make it more visible */
.invoice-sent-checkbox {
    width: 22px !important;
    height: 22px !important;
    cursor: pointer !important;
    border: 2px solid #007bff !important;
    border-radius: 4px;
    background-color: #fff !important;
    margin: 0 auto;
    display: block;
    appearance: checkbox;
    -webkit-appearance: checkbox;
    -moz-appearance: checkbox;
    position: relative !important;
    z-index: 1050 !important;
    flex-shrink: 0;
    opacity: 1 !important;
    pointer-events: auto !important;
    -webkit-pointer-events: auto !important;
}

.invoice-sent-checkbox:checked {
    background-color: #007bff !important;
    border-color: #007bff !important;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3e%3cpath fill='none' stroke='%23fff' stroke-linecap='round' stroke-linejoin='round' stroke-width='3' d='M6 10l3 3l6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: center;
    background-size: contain;
    opacity: 1 !important;
}

.invoice-sent-checkbox:disabled {
    opacity: 0.6 !important;
    cursor: not-allowed !important;
    border-color: #ccc !important;
    pointer-events: auto !important;
}

.invoice-sent-checkbox:hover:not(:disabled) {
    border-color: #0056b3 !important;
    box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
}

/* Ensure checkbox cell has proper styling and is clickable */
table.table td .invoice-sent-checkbox {
    margin: 0 auto;
    position: relative !important;
    z-index: 1050 !important;
}

table.table td.text-center {
    vertical-align: middle;
    padding: 10px !important;
    position: relative;
    z-index: 1;
}

/* Ensure table rows and cells don't block pointer events */
#orderModalBody table.table tbody tr td {
    pointer-events: auto !important;
    position: relative;
}

/* Ensure checkbox column is clickable */
#orderModalBody table.table tbody tr td.text-center,
#orderModalBody table.table tbody tr td.invoice-sent-cell {
    z-index: 1050 !important;
    pointer-events: auto !important;
    position: relative !important;
}

#orderModalBody table.table tbody tr td.invoice-sent-cell div {
    pointer-events: auto !important;
    z-index: 1051 !important;
}

/* Prevent any overlays from blocking the checkbox */
#orderModalBody .table-responsive {
    position: relative;
    z-index: 1;
}

#orderModalBody .table-responsive table {
    position: relative;
    z-index: 1;
}

/* Ensure sticky header doesn't block table body checkboxes */
#orderModalBody .table-responsive table thead.sticky-top {
    z-index: 1020;
}

#orderModalBody .table-responsive table tbody {
    position: relative;
    z-index: 1;
}

#orderModalBody .table-responsive table tbody tr {
    position: relative;
    z-index: 1;
}

#orderModalBody .table-responsive table tbody tr td.invoice-sent-cell {
    z-index: 1050 !important;
}
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
                d.fil_company = $('#fil_company').val();
                d.fil_service_cat = $('#fil_service_cat').val();
                d.fil_order_id = $('#fil_order_id').val();
                d.fil_interview_date_from = $('#fil_interview_date_from').val();
                d.fil_interview_date_to = $('#fil_interview_date_to').val();
                d.fil_delivery_date_from = $('#fil_delivery_date_from').val();
                d.fil_delivery_date_to = $('#fil_delivery_date_to').val();
                d.action = 'get_invoices';
            }
        },
        "columns": [
            { "data": "customer_name" ,"orderable": true},
            { "data": "company" ,"orderable": true},
            { "data": "period" ,"orderable": true},
            { "data": "status", "orderable": true},
            { "data": "created_date" ,"orderable": true},
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
    $('#fil_interview_date_from').val('');
    $('#fil_interview_date_to').val('');
    $('#fil_delivery_date_from').val('');
    $('#fil_delivery_date_to').val('');
    // ensure select fields reset to their default options and notify any select plugins
    setTimeout(function() {
        $('#fil_customer').val('').trigger('change');
        $('#fil_company').val('0').trigger('change');
        $('#fil_service_cat').val('all').trigger('change');
    }, 10);
    $('#fil_customer').val('');
    $('#fil_service_cat').val('all');
    $('#fil_company').val('');
    $('#fil_order_id').val('');
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
function showInvoiceOrders(invoiceId) {
    $.ajax({
        type: 'POST',
        url: './includes/invoice_ajax.php',
        dataType: 'json',
        data: {
            action: 'get_invoice_orders',
            invoice_id: invoiceId
        },
        beforeSend: function() {
            // show loader while fetching
            $('#orderModalBody').html(`
                <div class="text-center text-muted py-3">
                    <i class="fas fa-spinner fa-spin me-2"></i> Fetching orders...
                </div>
            `);
            $('#orderModal').modal('show');
        },
        success: function(response) {
            if (response.success) {
                const orders = response.orders;
                console.log(orders);
                const invoiceId = response.invoice_id;
                if (orders.length > 0) {
                    const customerName = orders[0].customer ?? 'N/A';
                    const companyName = orders[0].company ?? 'N/A';
                    const customerEmail = orders[0].customer_email ?? 'N/A';
                    $('#orderModalLabel').html(`
                    
                        <span class="text-white">
                        <i class="fas fa-building me-2"></i>
                        Company: ${companyName}</span>
                        <br/>
                        <span class="text-white">
                        <i class="fas fa-user me-2"></i>
                        Customer: ${customerName}  </span> 
                        <span class="text-white">
                        <i class="fas fa-envelope me-2"></i>
                        Email: ${customerEmail}</span>
                    `);
                    // smaller, cleaner table for narrow modal
                    let orderList = `
                    <div class="table-responsive" style="max-height: 80vh; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 6px;">
                    <table class="table table-bordered table-hover align-middle mb-0" style="min-width: 1000px;">
                        <thead class="table-primary sticky-top" style="top: 0; z-index: 1020;">
                        <tr>
                            <th style="width: 40px; text-align:center;">#</th>
                            <th>Order ID</th>
                            <th>VASC ID</th>
                            <th>Staff</th>
                            <th>Interview Date / Delivery Date</th>
                            <th>Place</th>
                            <th>Service Type</th>
                            <th>Status</th>
                            <th style="min-width:200px;">Status History</th>
                            <th style="min-width:200px;">Billing Details</th>
                            <th style="width: 120px; text-align:center;">Invoice Sent</th>
                        </tr>
                        </thead>
                        <tbody>
                    `;

                    orders.forEach((order, index) => {
                    const checked = order.invoice_sent_bool ? 'checked' : '';
                    orderList += `
                        <tr>
                        <td class="text-center">${index + 1}</td>
                        <td><strong>${order.order_id}</strong></td>
                        <td><strong>${order.vasc_id ?? 'N/A'}</strong></td>
                        <td>${order.staff ?? 'N/A'}</td>
                        <td>${
                            order.service_type == 3 
                            ? (order.delivery_date ?? 'N/A') 
                            : (order.interview_date ?? 'N/A')
                        }</td>
                        <td>${
                            order.service_type == 3 
                            ? 'N/A' 
                            : (order.place ?? 'Video')
                        }</td>
                        <td>${order.service ?? 'N/A'}</td>
                        <td>${order.status ?? 'N/A'}</td>
                        <td style="white-space: pre-line;">${order.status_history ?? 'N/A'}</td>
                        <td style="white-space: pre-line;">${order.billing_details ?? 'N/A'}</td>
                        <td class="text-center invoice-sent-cell" style="position: relative; z-index: 1050;">
                            <div style="display: inline-block; position: relative; z-index: 1051; pointer-events: auto;">
                                <input type="checkbox" 
                                       class="form-check-input invoice-sent-checkbox" 
                                       data-candidate-id="${order.candidate_id}"
                                       ${checked}
                                       onchange="updateOrderInvoiceSent(${order.candidate_id}, this.checked, ${invoiceId})"
                                       style="position: relative; z-index: 1052; pointer-events: auto; cursor: pointer;">
                            </div>
                        </td>
                        </tr>
                    `;
                    });

                    orderList += `
                        </tbody>
                    </table>
                    </div>
                    `;
                    $('#orderModalBody').html(orderList);
                } else {
                    $('#orderModalBody').html(`
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-info-circle me-2"></i> No orders found for this invoice.
                        </div>
                    `);
                }
            } else {
                $('#orderModalBody').html(`
                    <div class="alert alert-danger small mb-0 py-2 px-3">
                        <i class="fas fa-exclamation-triangle me-2"></i> ${response.message}
                    </div>
                `);
            }
        },
        error: function() {
            $('#orderModalBody').html(`
                <div class="alert alert-danger small mb-0 py-2 px-3">
                    <i class="fas fa-exclamation-circle me-2"></i> Error fetching orders.
                </div>
            `);
        }
    });
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

function updateOrderInvoiceSent(candidateId, isChecked, invoiceId) {
    // Disable checkbox while processing
    const checkbox = $(`.invoice-sent-checkbox[data-candidate-id="${candidateId}"]`);
    checkbox.prop('disabled', true);
    
    $.ajax({
        type: 'POST',
        url: './includes/invoice_ajax.php',
        dataType: 'json',
        data: {
            action: 'update_order_invoice_sent',
            candidate_id: candidateId,
            invoice_sent: isChecked ? '1' : '0',
            invoice_id: invoiceId
        },
        success: function(response) {
            checkbox.prop('disabled', false);
            if (response.success) {
                // Reload the main invoice table to reflect status changes
                if (window.invoiceTable) {
                    window.invoiceTable.ajax.reload();
                }
            } else {
                // Revert checkbox state on error
                checkbox.prop('checked', !isChecked);
                alert('Error updating invoice sent status: ' + response.message);
            }
        },
        error: function() {
            checkbox.prop('disabled', false);
            // Revert checkbox state on error
            checkbox.prop('checked', !isChecked);
            alert('Error updating invoice sent status');
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
        fil_customer: $('#fil_customer').val(),
        fil_company: $('#fil_company').val(),
        fil_company: $('#fil_service_cat').val(),
        fil_order_id: $('#fil_order_id').val()
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
