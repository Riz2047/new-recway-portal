<?php
$activeLink = "statuses";
include_once('includes/header.php');
if (! isset($_GET['id'])) {
    redirect("index.php");
}
if (isset($_GET['delete'])) {
    $col_nam = findByQuery('SELECT msg_col FROM status_services WHERE status_id=' . $_GET['delete']);
    $query = 'DELETE FROM statuses WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
    delete('status_services', 'status_id', $_GET['delete']);
    // $query = "ALTER TABLE `messages` DROP " . $col_nam->msg_col;
    // $stmt = $conn->prepare($query);
    // $stmt->execute();
    redirect("statuses.php?id=" . $_GET['id']);
}
// Statuses will be loaded via AJAX
$statuses = [];
$query = "SELECT * FROM service_categories WHERE id != ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$services = $stmt->fetchAll();
// Copy status functionality moved to AJAX handler in pages.php
?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>
        <!-- table row -->
        <div class="row">
            <div class="col-lg-12">
                <div class="table-div">
                    <form action="" method="post" id="d-form">
                        <div class="card card-cascade narrower mb-4">
                            <!--Card image-->
                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">
                                <a href="#" class="white-text mx-3">Statuses</a>
                                <div>
                                    <button type="button" onclick="show_add_card(this)"
                                        class="btn btn-outline-white btn-rounded btn-sm px-2">
                                        <i class="fa-solid fas fa-copy"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Status">
                                        <span onclick="location.href='add-status.php?serv_cat=<?= $_GET['id'] ?>'"><i class="bi bi-clipboard-plus"></i></span>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-12" id="show_add_card" style="display: none !important;">
                                <div class="card" style="width: 98% !important;margin-left: 11px !important">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Copy Statuses</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <form id="copy-status-form" method="post">
                                            <div class="row">
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label">From Service</label>
                                                    <select name="service_cat" id="service_cat" class="form-control"
                                                        onchange="fetch_statuses(this)">
                                                        <option value="">Select Service</option>
                                                        <?php if (! empty($services)) { ?>
                                                            <?php foreach ($services as $service) { ?>
                                                                <option value="<?= $service->id ?>"><?= $service->name ?>
                                                                </option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label">Status</label>
                                                    <select name="status" id="status_select" class="form-control">
                                                        <option value="">Select Status</option>
                                                    </select>
                                                </div>
                                                <div class="d-flex justify-content-end">
                                                    <button type="button" id="copy-status-btn"
                                                        class="btn-primary bg-primary">Copy</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <table id="dataTable" class="display Table" data-table="statuses" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head">Action</th>
                                        <th class="table-head">Sr#</th>
                                        <th class="table-head">Status</th>
                                        <th class="table-head">Status (Swedish)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var service_cat_id = <?php echo $_GET['id'] ?>;
</script>
<?php
include_once('includes/footer.php');
?>
<script>
    $('#customer').on('change', function() {
        location.href = location.pathname + "?id=" + $(this).val();
    })

    function fetch_statuses(obj) {
        var id = $(obj).val();
        var target_service_id = typeof service_cat_id !== 'undefined' ? service_cat_id : '';
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'fetch_statuses': 1,
                'id': id,

                'target_service_id': target_service_id
            },
            dataType: 'json',
            success: function(response) {
                console.log(response);
                var $statusSelect = $('#status_select');
                $statusSelect.find('option:not(:first)').remove();
                if (response.success && response.service_categories && response.service_categories.length > 0) {
                    $.each(response.service_categories, function(index, category) {
                        $statusSelect.append(
                            $('<option></option>')
                            .val(category.id)
                            .text(category.status)
                            .data('status-detail', category.status_detail)
                            .data('status-icon', category.status_icon)
                        );
                    });
                } else if (response.message) {
                    // Show message if no statuses available
                    $statusSelect.append(
                        $('<option></option>')
                        .val('')
                        .text(response.message)
                        .prop('disabled', true)
                    );
                }
            },
            error: function(xhr, status, error) {
                console.error("Error fetching statuses:", error);
                alert("Failed to load statuses. Please try again.");
            }
        });
    }
    // Handle copy status button click via AJAX (using click instead of submit to avoid nested form issues)
    $(document).ready(function() {
        // Use click event on button instead of form submit to avoid nested form issues
        $(document).on('click', '#copy-status-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();

            var status = $('#status_select').val();
            var service_cat = $('#service_cat').val();
            var target_service_cat_id = typeof service_cat_id !== 'undefined' ? service_cat_id : '';

            console.log('Copy status button clicked:', {
                status: status,
                service_cat: service_cat,
                target_service_cat_id: target_service_cat_id
            });

            if (!status || !service_cat || !target_service_cat_id) {
                alert('Please select both service and status');
                return false;
            }

            var $btn = $('#copy-status-btn');
            var originalText = $btn.text();
            $btn.prop('disabled', true).text('Copying...');

            $.ajax({
                type: "POST",
                url: "../includes/pages.php",
                data: {
                    'action': 'copy_status',
                    'status': status,
                    'service_cat': service_cat,
                    'target_service_cat_id': target_service_cat_id
                },
                dataType: 'json',
                success: function(response) {
                    console.log('Copy status response:', response);
                    if (response && response.success) {
                        alert('Status copied successfully!');
                        // Reload the DataTable
                        if (typeof table !== 'undefined' && table && typeof table.ajax !== 'undefined') {
                            table.ajax.reload(null, false);
                        } else {
                            // Fallback: reload page
                            location.reload();
                        }
                        // Reset form safely
                        var $form = $('#copy-status-form');
                        if ($form.length > 0 && $form[0]) {
                            $form[0].reset();
                        }
                        $('#status_select').find('option:not(:first)').remove();
                    } else {
                        alert('Error: ' + (response && response.message ? response.message : 'Failed to copy status'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error("Error copying status:", {
                        xhr: xhr,
                        status: status,
                        error: error,
                        responseText: xhr.responseText
                    });
                    var errorMsg = "Failed to copy status. Please try again.";
                    if (xhr.responseText) {
                        try {
                            var errorResponse = JSON.parse(xhr.responseText);
                            if (errorResponse.message) {
                                errorMsg = errorResponse.message;
                            }
                        } catch (e) {
                            // Not JSON, use default message
                        }
                    }
                    alert(errorMsg);
                },
                complete: function() {
                    $btn.prop('disabled', false).text(originalText);
                }
            });
            return false;
        });

        // Also prevent form submission if it happens
        $('#copy-status-form').on('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            return false;
        });

        // Handle delete status link clicks
        $(document).on('click', '.delete-status-link', function(e) {
            e.preventDefault();
            var statusId = $(this).data('status-id');
            var serviceCatId = $(this).data('service-cat-id');

            if (confirm('Are you sure you want to delete this status?')) {
                // Redirect to delete URL
                window.location.href = 'statuses.php?id=' + serviceCatId + '&delete=' + statusId;
            }
        });
    });
</script>