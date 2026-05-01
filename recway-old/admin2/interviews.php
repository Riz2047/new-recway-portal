<?php
$activeLink = "services";
include_once('includes/header.php');
if (! isset($_GET['id'])) {
    redirect("services.php");
}
if (isset($_GET['delete'])) {
    $query = "DELETE FROM interviews WHERE id={$_GET['delete']} AND service_cat_id={$_GET['id']}";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("interviews.php?id=" . $_GET['id']);
}
if (isset($_POST['update'])) {
    if (isset($_POST['types']) && ! empty($_POST['types'])) {
        $types = $_POST['types'];
        $descs = $_POST['descs'];
        $cost = $_POST['cost'];
        $ids = $_POST['ids'];
        $query = "SELECT status_id FROM `status_services` WHERE service_id IN (SELECT id FROM interviews WHERE service_cat_id = {$_GET['id']}) GROUP BY status_id;";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $added_statuses = $stmt->fetchAll();
        if (! empty($added_statuses)) {
            $added_statuses = array_column($added_statuses, "status_id");
            $added_statuses = implode(",", $added_statuses);
        }
        foreach ($types as $key => $type) {
            $query = "SELECT * FROM interviews WHERE id={$ids[$key]} AND service_cat_id={$_GET['id']}";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $service_type = $stmt->fetch();
            if (! empty($service_type)) {
                $query = 'UPDATE interviews SET title = ?, `desc` = ?, `cost` = ? WHERE id = ? AND service_cat_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$type, $descs[$key], $cost[$key], $ids[$key], $_GET['id']]);
                // Update customer associations
                $serviceId = $ids[$key];
                if (isset($_POST['customers'][$key]) && is_array($_POST['customers'][$key])) {
                    $selectedCustomers = array_filter($_POST['customers'][$key], function ($val) {
                        return ! empty($val);
                    });
                    // Find and add child customers for selected parent customers
                    $allCustomersToAdd = $selectedCustomers;
                    foreach ($selectedCustomers as $customerId) {
                        // Find all child customers for this parent
                        $query = 'SELECT id FROM customers WHERE parent_id = ?';
                        $stmt = $conn->prepare($query);
                        $stmt->execute([$customerId]);
                        $childCustomers = $stmt->fetchAll();
                        foreach ($childCustomers as $child) {
                            if (! in_array($child->id, $allCustomersToAdd)) {
                                $allCustomersToAdd[] = $child->id;
                            }
                        }
                    }
                    // Delete existing associations
                    $query = 'DELETE FROM customer_services WHERE service_id = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$serviceId]);
                    // Insert new associations (including child customers)
                    if (! empty($allCustomersToAdd)) {
                        $query = 'INSERT INTO customer_services (service_id, cus_id) VALUES (?, ?)';
                        $stmt = $conn->prepare($query);
                        foreach ($allCustomersToAdd as $customerId) {
                            $stmt->execute([$serviceId, $customerId]);
                        }
                    }
                } else {
                    // If no customers selected, remove all associations
                    $query = 'DELETE FROM customer_services WHERE service_id = ?';
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$serviceId]);
                }
            } else {
                if (! empty($type)) {
                    $query = 'INSERT INTO interviews (title, `desc`, service_cat_id) VALUES (?,?,?)';
                    $stmt = $conn->prepare($query);
                    $res = $stmt->execute([$type, $descs[$key], $_GET['id']]);
                    $lastInsertId = $conn->lastInsertId();
                    // Save customer associations for new service
                    if (isset($_POST['customers'][$key]) && is_array($_POST['customers'][$key])) {
                        $selectedCustomers = array_filter($_POST['customers'][$key], function ($val) {
                            return ! empty($val);
                        });
                        // Find and add child customers for selected parent customers
                        $allCustomersToAdd = $selectedCustomers;
                        foreach ($selectedCustomers as $customerId) {
                            // Find all child customers for this parent
                            $query = 'SELECT id FROM customers WHERE parent_id = ?';
                            $stmt = $conn->prepare($query);
                            $stmt->execute([$customerId]);
                            $childCustomers = $stmt->fetchAll();
                            foreach ($childCustomers as $child) {
                                if (! in_array($child->id, $allCustomersToAdd)) {
                                    $allCustomersToAdd[] = $child->id;
                                }
                            }
                        }
                        if (! empty($allCustomersToAdd)) {
                            $query = 'INSERT INTO customer_services (service_id, cus_id) VALUES (?, ?)';
                            $stmt = $conn->prepare($query);
                            foreach ($allCustomersToAdd as $customerId) {
                                $stmt->execute([$lastInsertId, $customerId]);
                            }
                        }
                    }
                    if (! empty($added_statuses)) {
                        $query = "INSERT INTO status_services (status_id, service_id, msg_col)
                        SELECT status_id, {$lastInsertId} AS service_id, msg_col FROM status_services WHERE status_id IN (" . $added_statuses . ") GROUP BY status_id";
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute();
                        $customers = findAllByQuery('SELECT * from customers');
                        $messages = findByQuery('SELECT * from messages WHERE cus_id = 0 AND interview_id = 0');
                        $admin_msg = $messages->admin_msg;
                        $cus_msg = $messages->cus_msg;
                        foreach ($customers as $customer) {
                            insert('messages', ['cus_id' => $customer->id, 'interview_id' => $lastInsertId]);
                        }
                        $query = 'UPDATE messages SET admin_msg = ?, `cus_msg` = ? WHERE interview_id = ?';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$admin_msg, $cus_msg, $lastInsertId]);
                    }
                }
            }
        }
        if (! empty($res)) {
            flash("typesUpdated", "Types updated successfully!");
        } else {
            flash("typesUpdated", "Could not update types!", "errorMsg");
        }
    }
}
$query = 'SELECT * FROM interviews WHERE service_cat_id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
// Fetch all customers for the multiselect (including parent_id to identify relationships)
$allCustomers = findAllByQuery('SELECT id, name, email, company, parent_id FROM customers ORDER BY name ASC');
// Build a map of parent_id => array of child customer IDs
$parentChildMap = [];
foreach ($allCustomers as $customer) {
    if (! empty($customer->parent_id)) {
        if (! isset($parentChildMap[$customer->parent_id])) {
            $parentChildMap[$customer->parent_id] = [];
        }
        $parentChildMap[$customer->parent_id][] = $customer->id;
    }
}
// Fetch linked customers for each service
$serviceCustomers = [];
if (! empty($services)) {
    foreach ($services as $service) {
        $query = 'SELECT cus_id FROM customer_services WHERE service_id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$service->id]);
        $linkedCustomers = $stmt->fetchAll();
        $serviceCustomers[$service->id] = array_column($linkedCustomers, 'cus_id');
    }
}
?>
<style>
    /* Custom style for larger checkboxes */
    .custom-checkbox .custom-control-input {
        width: 1.5em;
        height: 1.5em;
    }

    .custom-checkbox .custom-control-label {
        padding-left: 2em;
        font-size: 1.25em;
    }

    .services-list .select2 .select2-selection ul.select2-selection__rendered {
        height: 120px;
        overflow-y: auto;
    }
</style>
<?php flash("typesUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Service Types</h1>
                    </div>
                    <form class="update-form" method="post">
                        <div class="types">
                            <?php if (! empty($services)) : ?>
                                <?php foreach ($services as $key => $service) : ?>
                                    <div class="col-lg-12 ps-0 mt-3 inner-type services-list">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="f-14 mb-0 pb-0 w-500">Type <?php echo $key + 1 ?></p>
                                            <div class="d-flex align-items-center">
                                                <!-- Button next to the trash icon -->
                                                <?php if (isset($_GET['id']) && ! empty($_GET['id']) && $_GET['id'] == 3) { ?>
                                                    <div class="profile-img mr-3">
                                                        <button type="button" class="f-16 w-600 bg-primary text-dark mb-0 pb-0 btn-primary-sm">Action</button>
                                                        <div class="tool-pit tool-pit2">
                                                            <div class="tool-pit-content">
                                                                <div class="d-flex justify-content-end">
                                                                    <div class="arrow-up me-3"></div>
                                                                </div>
                                                                <div class="tool-pit-content--header p-2">
                                                                    <!-- <a href="" class="no-decoration text-white">Change Status</a> -->
                                                                </div>
                                                                <ul class=" menus" style="padding:0px !important; text-align:center">
                                                                    <li><a class="open-report" data-bs-toggle="modal" data-id="<?= $service->id ?>,service" data-lang="en" href="#">Report - En</a></li>
                                                                    <li><a class="open-report" data-bs-toggle="modal" data-id="<?= $service->id ?>,service" data-lang="sv" href="#">Report - Sv</a></li>
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php } ?>
                                                <a class="mr-3" data-bs-toggle="modal" data-bs-target="#exampleModal" data-id="<?php echo $service->id ?>">
                                                    <i class="bi bi-pencil-square text-success"></i>
                                                </a>
                                                <a href="?id=<?php echo $_GET['id'] ?>&delete=<?php echo $service->id ?>">
                                                    <i class="bi bi-trash text-danger"></i>
                                                </a>
                                            </div>
                                        </div>
                                        <input value="<?php echo $service->title ?>" class="sign-input w-100 mt-2" name="types[]">
                                        <input type="number" value="<?php echo $service->cost ?>" class="sign-input w-100 mt-2" name="cost[]" placeholder="Service Cost">
                                        <textarea rows="3" class="sign-textarea w-100 mt-2" name="descs[]"><?php echo $service->desc ?></textarea>
                                        <label class="f-14 mb-1 mt-2 w-500">Customers</label>
                                        <select class="customer-multiselect w-100 mt-2" name="customers[<?php echo $key ?>][]" multiple="multiple" data-service-index="<?php echo $key ?>" style="width: 100%;">
                                            <?php
                                            $linkedCustomerIds = isset($serviceCustomers[$service->id]) ? $serviceCustomers[$service->id] : [];
                                    foreach ($allCustomers as $customer) :
                                        $selected = in_array($customer->id, $linkedCustomerIds) ? 'selected' : '';
                                        ?>
                                                <option value="<?php echo $customer->id ?>" data-parent-id="<?php echo $customer->parent_id ?? '' ?>" <?php echo $selected ?>>
                                                    <?php echo htmlspecialchars($customer->name . ($customer->company ? ' (' . $customer->company . ')' : '') . ($customer->email ? ' - ' . $customer->email : '')) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <input type="hidden" name="ids[]" value="<?php echo $service->id ?>">
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div style="cursor: pointer; width: fit-content; font-weight: bold" class="add-row mt-2">
                            + Add Row
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" name="update" class="btn-primary bg-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<!-- Background Report Content Modal -->
<div class="modal fade" id="servicebackgroundReportContentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Generate Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Content will be loaded here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
        </div>
    </div>
</div>
<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="col-md-4">
                    <input class="form-check-input" type="checkbox" id="placeCheckbox">
                    <label class="form-label form-check-label" for="placeCheckbox">Place</label>
                </div>
                <div class="col-md-4">
                    <input class="form-check-input" type="checkbox" id="countryCheckbox">
                    <label class="form-label form-check-label" for="countryCheckbox">Country</label>
                </div>
                <?php if ($_GET['id'] == 3) { ?>
                    <div class="col-md-12 mt-4">
                        <label for="">Delivery Days</label>
                        <input type="number" name="delivery_days" id="deliver_days" class="form-control" oninput="set_deliver_days(this)">
                    </div>
                <?php } ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php
include_once('includes/footer.php');
?>
<script>
    // Global parent-child map for customers
    var globalParentChildMap = {};
    <?php if (! empty($parentChildMap)) : ?>
        <?php foreach ($parentChildMap as $parentId => $childIds) : ?>
            globalParentChildMap[<?php echo $parentId ?>] = <?php echo json_encode($childIds) ?>;
        <?php endforeach; ?>
    <?php endif; ?>
    // Function to setup customer select handler (auto-selects child customers)
    function setupCustomerSelectHandler(selectElement, parentChildMap) {
        var isUpdating = false;
        selectElement.on('change', function() {
            // Prevent infinite loop
            if (isUpdating) {
                return;
            }
            var selectedValues = $(this).val() || [];
            var select = $(this);
            var newSelections = selectedValues.slice(); // Copy array
            // Check each selected customer to see if it's a parent
            selectedValues.forEach(function(customerId) {
                if (parentChildMap[customerId]) {
                    // This customer has children, add them to selection
                    parentChildMap[customerId].forEach(function(childId) {
                        if (newSelections.indexOf(childId.toString()) === -1) {
                            newSelections.push(childId.toString());
                        }
                    });
                }
            });
            // Update selection if child customers were added
            if (newSelections.length > selectedValues.length) {
                isUpdating = true;
                select.val(newSelections).trigger('change');
                isUpdating = false;
            }
        });
    }
    $('.add-row').click(function() {
        var types = $('.types');
        var totalTypes = types.find('.inner-type');
        var lastID = parseInt($('.inner-type:last-child').find('input').val())
        var customerOptions = '';
        <?php if (! empty($allCustomers)) : ?>
            <?php foreach ($allCustomers as $customer) : ?>
                customerOptions += '<option value="<?php echo $customer->id ?>" data-parent-id="<?php echo $customer->parent_id ?? '' ?>"><?php echo htmlspecialchars(addslashes($customer->name . ($customer->company ? ' (' . $customer->company . ')' : '') . ($customer->email ? ' - ' . $customer->email : ''))) ?></option>';
            <?php endforeach; ?>
        <?php endif; ?>
        var type = '<div class="col-lg-12 ps-0 mt-3 inner-type"><p class="f-14 mb-0 pb-0 w-500">Type ' + (totalTypes.length + 1) + '</p><input class="sign-input w-100 mt-2" name="types[]"><input type="number" class="sign-input w-100 mt-2" name="cost[]" placeholder="Service Cost"><textarea rows="3" class="sign-textarea w-100 mt-2" name="descs[]"></textarea><label class="f-14 mb-1 mt-2 w-500">Customers</label><select class="customer-multiselect w-100 mt-2" name="customers[' + totalTypes.length + '][]" multiple="multiple" data-service-index="' + totalTypes.length + '" style="width: 100%;">' + customerOptions + '</select><input type="hidden" name="ids[]" value="' + (isNaN(lastID) ? '0' : (lastID + 1)) + '"></div>';
        types.append(type);
        // Initialize Select2 for the new multiselect
        var newSelect = $('.customer-multiselect').last();
        newSelect.select2({
            placeholder: 'Select customers...',
            allowClear: true,
            width: '100%'
        });
        // Add change handler for auto-selecting child customers
        setupCustomerSelectHandler(newSelect, globalParentChildMap);
    });
    $(document).ready(function() {
        $('#content-modal').remove()
        // Initialize Select2 for all customer multiselects
        $('.customer-multiselect').each(function() {
            $(this).select2({
                placeholder: 'Select customers...',
                allowClear: true,
                width: '100%'
            });
            // Setup change handler for auto-selecting child customers
            setupCustomerSelectHandler($(this), globalParentChildMap);
        });
    });
    $(document).ready(function() {
        // When the modal is about to be shown
        $('#exampleModal').on('show.bs.modal', function(event) {
            var button = $(event.relatedTarget); // Button that triggered the modal
            var id = button.data('id'); // Extract info from data-* attributes
            var modal = $(this);
            modal.data('id', id);
            $.ajax({
                url: '../includes/ajax.php',
                type: 'POST',
                data: {
                    id: id,
                    checkedInterview: true
                },
                success: function(response) {
                    try {
                        var parsedResponse = JSON.parse(response);
                        var data = parsedResponse.data;
                        $('#placeCheckbox').prop('checked', data.place == 1);
                        $('#countryCheckbox').prop('checked', data.country == 1);
                        $('#deliver_days').val(data.delivery_days);
                    } catch (e) {
                        console.error("Parsing error:", e);
                    }
                }
            });
        });
        $('#placeCheckbox').change(function() {
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var id = $('#exampleModal').data('id');
            $.ajax({
                url: '../includes/ajax.php',
                type: 'POST',
                data: {
                    id: id,
                    interviewPlace: true,
                    value: isChecked
                },
                success: function(response) {}
            });
        });
        $('#countryCheckbox').change(function() {
            var isChecked = $(this).is(':checked') ? 1 : 0;
            var id = $('#exampleModal').data('id');
            $.ajax({
                url: '../includes/ajax.php',
                type: 'POST',
                data: {
                    id: id,
                    interviewCountry: true,
                    value: isChecked
                },
                success: function(response) {}
            });
        });
    });

    function set_deliver_days(obj) {
        var value = $(obj).val();
        var id = $('#exampleModal').data('id');
        $.ajax({
            url: '../includes/ajax.php',
            type: 'POST',
            data: {
                id: id,
                delivery_days: value
            },
            success: function(response) {}
        });
    }
</script>