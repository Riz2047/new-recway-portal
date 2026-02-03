<?php



$activeLink = "staff";



include_once('includes/header.php');



if (isset($_POST['delete'])) {

    foreach ($_POST['delete'] as $delete) {

        $query = 'SELECT * FROM staff WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);

        $staff = $stmt->fetch();



        $query = 'DELETE FROM staff WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);



        $query = 'DELETE FROM emails WHERE email = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$staff->email]);

    }

}



$query = 'SELECT staff.*, COUNT(c.staff_id) AS total_orders,user_category.title as staff_category_title FROM staff';

$query .= ' LEFT JOIN user_category ON staff.category = user_category.id';

$query .= ' LEFT JOIN candidates as c ON staff.id = c.staff_id GROUP BY staff.id';

$query .= "  ORDER BY total_orders DESC";

$stmt = $conn->prepare($query);

$stmt->execute();

$staff = $stmt->fetchAll();



if (isset($_GET['delete'])) {

    $query = 'DELETE FROM staff WHERE id = ?';

    $stmt = $conn->prepare($query);

    if ($stmt->execute([$_GET['delete']])) {

        redirect('staff.php');

    }

}



$query = "SELECT * FROM tables_settings WHERE name = 'Staff'";

$stmt = $conn->prepare($query);

$stmt->execute();

$table = $stmt->fetchAll();

$table_columns_data = null;

if (!empty($table[0]->meta_data)) {

    $table_columns_data = json_decode($table[0]->meta_data, true);

}



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





                                <a href="#" class="white-text mx-3">Staff</a>



                                <div style="display:flex !important">

                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Staff">

                                        <span onclick="location.href='add-staff.php'"><i class="bi bi-person-plus"></i></span>

                                    </button>

                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text" data-toggle="tooltip" data-placement="top" title="Remove Staff">

                                        <span class=""><i class="bi bi-trash"></i></span>

                                    </button>

                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right m-0" onclick="show_card(this)" style="height: 31px;margin-top: 6px !important;">

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

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="email" name="column[staff][email]" data-id="email_show" value="1" <?php if (isset($table_columns_data['email']) && !empty($table_columns_data['email'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="email">Email</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="phone" name="column[staff][phone]" data-id="phone_show" value="1" <?php if (isset($table_columns_data['phone']) && !empty($table_columns_data['phone'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="phone">Phone</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="no_of_orders" name="column[staff][no_of_orders]" data-id="no_of_orders_show" value="1" <?php if (isset($table_columns_data['no_of_orders']) && !empty($table_columns_data['no_of_orders'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="no_of_orders">No of Orders</label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <table id="dataTable" data-table="staff" class="display Table" style="width: 100%">

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

                                        <th class="table-head no_of_orders_show <?php if (!isset($table_columns_data['no_of_orders']) || empty($table_columns_data['no_of_orders'])) { ?> custom_hide<?php } ?>">No. of Orders</th>
                                        <th class="table-head">Report Upload</th>
                                        <th class="table-head">Category</th>



                                    </tr>

                                </thead>

                                <tbody>



                                    <?php if (!empty($staff)) : ?>

                                        <?php foreach ($staff as $st) : ?>

                                            <?php

                                            $is_staff = "";

                                            if ($st->id != 0) {

                                                $query = 'SELECT * FROM candidates WHERE staff_id = ?';

                                                $stmt = $conn->prepare($query);

                                                $stmt->execute([$st->id]);

                                                $is_staff = $stmt->fetch();

                                            }

                                            ?>

                                            <tr>

                                                <td>

                                                    <input <?php echo !empty($is_staff) ? 'disabled' : '' ?> class="form-check-input d-check <?php echo !empty($is_staff) ? '' : 'delete-candidate' ?> " <?php echo !empty($is_staff) ? 'style="display:none"' : '' ?> id="checkbox-<?php echo $st->id ?>" name="delete[]" value="<?php echo $st->id ?>" type="checkbox">

                                                    <label class="form-check-label" for="checkbox-<?php echo $st->id ?>" class="mr-2 label-table"></label>

                                                </td>

                                                <td>

                                                    <div class="dropdown">

                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">

                                                            <i class="bi bi-gear"></i>

                                                        </button>

                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                                            <li class="mb-1"><a href="update-staff.php?id=<?php echo $st->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                                                    Edit</a>

                                                            </li>



                                                            <li <?php echo !empty($is_staff) ? 'style="pointer-events: none;"' : '' ?> class="mb-1"><a <?php echo !empty($is_staff) ? 'style="color:#bebebe;"' : '' ?> href="staff.php?delete=<?php echo $st->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                                                    Delete</a>

                                                            </li>

                                                        </ul>

                                                    </div>

                                                </td>

                                                <td class="f-14"><a class="no-decoration text-black" href="staff-candidates.php?id=<?php echo $st->id ?>"><?php echo $st->name ?></a></td>

                                                <td class="f-14 email_show <?php if (!isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>"><?php echo $st->email ?></td>

                                                <td class="f-14 phone_show <?php if (!isset($table_columns_data['phone']) || empty($table_columns_data['phone'])) { ?> custom_hide<?php } ?>"><?php echo $st->phone ?></td>

                                                <td class="f-14 no_of_orders_show <?php if (!isset($table_columns_data['no_of_orders']) || empty($table_columns_data['no_of_orders'])) { ?> custom_hide<?php } ?>"><?php echo $st->total_orders ?></td>
                                                <td class="f-14">
                                                <input class="form-check-input " id="report_checkbox-<?php echo $st->id ?>" value="<?php echo $st->id ?>" <?php if(!empty($st->can_upload_report)){ ?>checked<?php } ?> type="checkbox" onclick="change_report_column(this)">
                                                <label class="form-check-label" for="report_checkbox-<?php echo $st->id ?>" class="mr-2 label-table"></label>
                                                </td>
                                                <td class="f-14"><?php echo $st->staff_category_title ?></td>

                                            </tr>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>



<?php



include_once('includes/footer.php');



?>
<script>
    function change_report_column(obj){
        var id = $(obj).val();
        var check_val = 0;
        if($(obj).is(':checked')){
            check_val = 1
        }else{
            check_val = 0
        }

        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: {
                'update_report_upload': 1,
                'staff_id': id,
                'check_val': check_val
            },
            success: function (response) {
            }
        });
    }
</script>