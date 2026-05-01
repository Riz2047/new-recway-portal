<?php

$activeLink = "customer-language";

include_once('includes/header.php');

// Language data will be loaded via AJAX (DataTables server-side)
$languages = [];

?>
<style>
    .table-container {
        overflow-y: auto;
    }
</style>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <div class="table-div">
                    <form action="" method="post" id="d-form">
                        <div class="card card-cascade narrower mb-4">
                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">
                                <a href="#" class="white-text mx-3">Customer Language</a>
                                <div>
                                     <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" onclick="showAddLanguageModal()" data-toggle="tooltip" data-placement="top" aria-label="Add Language String" data-bs-original-title="Add Language String">
                                        <span><i class="bi bi-plus-square"></i></span>
                                    </button>
                                </div>
                            </div>
                            <!-- Update Language Section -->
                            <div class="col-md-12 mt-3" id="update_language_card" style="display: none;">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Update Language</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="language_en">English</label>
                                                <input type="text" class="form-control" id="language_en">
                                            </div>
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="language_swg">Swedish</label>
                                                <input type="text" class="form-control" id="language_swg">
                                            </div>
                                            <input type="hidden" id="language_id">
                                            <div class="d-flex justify-content-end">
                                                <button type="button" onclick="close_update_section()"
                                                        class="btn btn-warning me-2">Close
                                                </button>
                                                <button type="button" onclick="update_language()"
                                                        class="btn btn-primary">Update
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <!-- Add Language Modal -->
                            <div class="col-md-12 mt-3" id="add_language_card" style="display: none;">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Add New Language String</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-6 mb-3">
                                                <label class="form-label" for="add_language_key">Language Key</label>
                                                <input type="text" class="form-control" id="add_language_key" placeholder="e.g., new_feature">
                                            </div>
                                            <div class="col-lg-6 mb-3">
                                                <label class="form-label" for="add_language_en">English Translation</label>
                                                <input type="text" class="form-control" id="add_language_en" placeholder="e.g., New Feature">
                                            </div>
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="add_language_swg">Swedish Translation</label>
                                                <input type="text" class="form-control" id="add_language_swg" placeholder="e.g., Ny funktion">
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" onclick="closeAddLanguageModal()"
                                                        class="btn btn-danger btn-sm float-right">Cancel
                                                </button>
                                                <button type="button" onclick="addLanguageString()"
                                                        class="btn btn-primary btn-sm float-right">Add Language
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <table id="dataTable" data-table="customer-language" class="display Table mt-3" style="width: 100%">
                                <thead>
                                <tr>
                                    <th class="table-head">#</th>
                                    <th class="table-head">English</th>
                                    <th class="table-head">Swedish</th>
                                    <th class="dt-center table-head">Action</th>

                                </tr>
                                </thead>
                                <tbody>
                                    <!-- Rows will be loaded via AJAX (server-side DataTables) -->
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
   var statuses = <?php echo json_encode($statuses ?? []) ?>
</script>
<?php

include_once('includes/footer.php');

?>

<script>
    $(document).on('click', '.dropdown-menu a', function (e) {
        e.preventDefault();
        e.stopPropagation(); // Prevents the dropdown from closing when clicking an item
        alert(1);
    });
 // Function to show add language modal
    function showAddLanguageModal() {
        $('#add_language_card').show();
        $('#add_language_key').val('');
        $('#add_language_en').val('');
        $('#add_language_swg').val('');
    }

    // Function to close add language modal
    function closeAddLanguageModal() {
        $('#add_language_card').hide();
    }

    // Function to add new language string
    function addLanguageString() {
        let key = $('#add_language_key').val().trim();
        let en = $('#add_language_en').val().trim();
        let swg = $('#add_language_swg').val().trim();

        if (!key || !en || !swg) {
            alert('Please fill in all fields');
            return;
        }

        $.ajax({
            url: 'add-language-customer.php',
            type: 'POST',
            data: {key: key, en: en, swg: swg},
            success: function (response) {
                try {
                    let result = JSON.parse(response);
                    if (result.status === 'success') {
                        alert('Language string added successfully!');
                        $('#add_language_card').hide();
                        // Reload the DataTable
                        if (typeof table !== 'undefined') {
                            table.ajax.reload();
                        }
                    } else {
                        alert('Error: ' + result.message);
                    }
                } catch (e) {
                    alert('Language string added successfully!');
                    $('#add_language_card').hide();
                    if (typeof table !== 'undefined') {
                        table.ajax.reload();
                    }
                }
            },
            error: function () {
                alert('Error adding language string!');
            }
        });
    }
    // Function to open the update section and populate fields
    function update_s(element) {
        let row = $(element).closest('tr'); // Get the closest <tr>
        let id = row.find('input[type="hidden"]').val() || "Not Found"; // Get hidden input value
        let en = row.find('td:eq(1)').text().trim(); // 3rd column (0-based index)
        let swg = row.find('td:eq(2)').text().trim(); // 4th column

        console.log("Extracted Values -> ID:", id, "English:", en, "Swedish:", swg); // Debugging

        // Update form fields
        $('#language_id').val(id);
        $('#language_en').val(en);
        $('#language_swg').val(swg);

        $('#update_language_card').show();
    }


    // Function to close the update section
    function close_update_section() {
        $('#update_language_card').hide();
    }
    // Prevent dropdown from closing when clicking inside


    // Function to update language details
    function update_language() {
        let id = $('#language_id').val();
        let en = $('#language_en').val();
        let swg = $('#language_swg').val();

        if (!en || !swg) {
            alert('Please fill in both English and Swedish translations');
            return;
        }
        $.ajax({
            url: 'update-language-customer.php', // Your backend update script
            type: 'POST',
            data: {id: id, en: en, swg: swg},
            success: function (response) {
                let row = $('input[value="' + id + '"]').closest('tr');
                row.find('td:eq(1)').text(en);  // English column update
                row.find('td:eq(2)').text(swg); // Swedish column update

                // ✅ Update section hide karein
                $('#update_language_card').hide();
                alert('Language updated successfully!');
                // location.reload();
            },
            error: function () {
                alert('Error updating language!');
            }
        });
    }
</script>
<script>
    $('#customer').on('change', function () {
        location.href = location.pathname + "?id=" + $(this).val();
    })
</script>