<?php



$activeLink = "customers";



include_once('includes/header.php');

if (!isset($allowed_staff_permission['view_customer']) && empty($allowed_staff_permission['view_customer'])) {

    redirect('index.php');

}





if (isset($_POST['delete'])) {

    foreach ($_POST['delete'] as $delete) {

        $query = 'SELECT * FROM customers WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);

        $customer = $stmt->fetch();



        $query = 'DELETE FROM customers WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);



        $query = 'DELETE FROM emails WHERE email = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$customer->email]);
    }
}

// Server-side DataTables will load customers via AJAX
$customers = [];

if (isset($_GET['delete'])) {

    $query = 'DELETE FROM customers WHERE id = ?';

    $stmt = $conn->prepare($query);

    if ($stmt->execute([$_GET['delete']])) {

        redirect('customers.php');

    }

}



$query = "SELECT * FROM tables_settings WHERE name = 'Customers'";

$stmt = $conn->prepare($query);

$stmt->execute();

$table = $stmt->fetchAll();

$table_columns_data = null;

if (!empty($table[0]->meta_data)) {

    $table_columns_data = json_decode($table[0]->meta_data, true);

}



?>



<style>

    /* .dropdown .dropdownBtn:focus+.dropdown-menu {

        display: block;

    } */

</style>

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





                                <a href="#" class="white-text mx-3">Customers</a>



                                <div style="display:flex !important">

                                    <?php if (isset($allowed_staff_permission['create_customer']) && !empty($allowed_staff_permission['create_customer'])) { ?>

                                        <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Customer">

                                            <span onclick="location.href='add-customer.php'"><i class="bi bi-person-plus"></i></span>

                                        </button>

                                    <?php } ?>

                                    <?php if (isset($allowed_staff_permission['update_customer']) && !empty($allowed_staff_permission['update_customer'])) { ?>

                                        <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-parent" data-toggle="tooltip" data-placement="top" title="Parent Customer">

                                            <span class=""><i class="bi bi-card-checklist"></i></span>

                                        </button>

                                    <?php } ?>

                                    <?php if (isset($allowed_staff_permission['update_customer']) && !empty($allowed_staff_permission['update_customer'])) { ?>

                                        <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-group" data-toggle="tooltip" data-placement="top" title="Groups">

                                            <span class=""><i class="bi bi-pen"></i></span>

                                        </button>

                                    <?php } ?>



                                    <!-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right m-0" onclick="show_card(this)" style="height: 31px;margin-top: 6px !important;">

                                        <i class="bx bxs-chevron-down arrow"></i>

                                    </button> -->

                                </div>

                            </div>

                            <div class="col-md-12">

                                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">



                                    <div class="card-body" style="display: none !important;">

                                        <div class="row">

                                            <input type="hidden" id="table_id" value="<?= $table[0]->id ?>">

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="email" name="column[customers][email]" data-id="email_show" value="1" <?php if (isset($table_columns_data['email']) && !empty($table_columns_data['email'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="email">Email</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="phone" name="column[customers][phone]" data-id="phone_show" value="1" <?php if (isset($table_columns_data['phone']) && !empty($table_columns_data['phone'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="phone">Phone</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[customers][company]" data-id="company_show" value="1" <?php if (isset($table_columns_data['company']) && !empty($table_columns_data['company'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="company">Company</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="cost_place" name="column[customers][cost_place]" data-id="cost_place_show" value="1" <?php if (isset($table_columns_data['cost_place']) && !empty($table_columns_data['cost_place'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="cost_place">Cost Place</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="parent_customer" name="column[customers][parent_customer]" data-id="parent_customer_show" value="1" <?php if (isset($table_columns_data['parent_customer']) && !empty($table_columns_data['parent_customer'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="parent_customer">Parent Customer</label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <table id="dataTable" data-table="customer" class="display Table" style="width: 100% !important">

                                <thead>

                                    <tr>

                                        <th class="table-head">

                                            <input class="form-check-input d-check" id="delete-all" name="all" type="checkbox">

                                            <label class="form-check-label" for="delete-all" class="mr-2 label-table"></label>

                                        </th>

                                        <th class="dt-center table-head">Action</th>

                                        <th class="table-head">Name</th>

                                        <th class="table-head email_show <?php if (!isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>">Email</th>

                                        <th class="table-head phone_show <?php if (!isset($table_columns_data['phone']) || empty($table_columns_data['phone'])) { ?> custom_hide<?php } ?>">Phone</th>

                                        <?php if (isset($login_user->category) && !empty($login_user->category) && $login_user->category == 2) { ?>

                                            <th class="table-head">Interview Template</th>

                                        <?php } ?>

                                        <th class="table-head company_show <?php if (!isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>">Company</th>

                                        <th class="table-head cost_place_show <?php if (!isset($table_columns_data['cost_place']) || empty($table_columns_data['cost_place'])) { ?> custom_hide<?php } ?>">Cost Place</th>

                                        <th class="table-head parent_customer_show <?php if (!isset($table_columns_data['parent_customer']) || empty($table_columns_data['parent_customer'])) { ?> custom_hide<?php } ?>">Parent Customer</th>



                                    </tr>

                                </thead>
                                <tbody></tbody>
                            </table>

                        </div>

                    </form>

                    <div class="modal fade" id="delay_date" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

                        <div class="modal-dialog">

                            <div class="modal-content">

                                <div class="modal-header">

                                    <h5 class="modal-title" id="exampleModalLabel">Duration</h5>

                                    <button type="button" class="btn-close" data-bs-target="#delay_date" data-bs-toggle="modal" aria-label="Close"></button>

                                </div>

                                <div class="modal-body">

                                    <div class="row">

                                        <div class="col-md-12">

                                            <label for="">

                                                Days

                                            </label>

                                            <input type="number" id="delay_duration" class="form-control" min="1" onchange="check_date(this)">

                                            <input type="hidden" id="delay_cus_id" value="">

                                        </div>

                                    </div>

                                </div>

                                <div class="modal-footer">

                                    <button type="button" class="btn btn-secondary" data-bs-target="#delay_date" data-bs-toggle="modal">Close</button>

                                    <button type="button" class="btn btn-success" data-bs-target="#delay_date" data-bs-toggle="modal" onclick="setDurationDays(this);">Save</button>

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

<script>

    function check_interview_template(obj) {

        var check = 0

        if ($(obj).is(':checked')) {

            check = 1

        }

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                'interview_template': 1,

                'check': check,

                'id': $(obj).val(),

            },

            success: function(response) {



            }

        });

    }

</script>