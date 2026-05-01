<?php

$activeLink = "customers";

include_once('includes/header.php');

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

$query = 'SELECT customers.*,c.name as parent_customer FROM customers LEFT JOIN customers as c ON customers.parent_id = c.id';

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();

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

if (! empty($table[0]->meta_data)) {

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

                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Customer">

                                        <span onclick="location.href='add-customer.php'"><i class="bi bi-person-plus"></i></span>

                                    </button>

                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text" data-toggle="tooltip" data-placement="top" title="Remove Customer">

                                        <span class=""><i class="bi bi-trash"></i></span>

                                    </button>

                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-parent" data-toggle="tooltip" data-placement="top" title="Parent Customer">

                                        <span class=""><i class="bi bi-card-checklist"></i></span>

                                    </button>

                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-group" data-toggle="tooltip" data-placement="top" title="Groups">

                                        <span class=""><i class="bi bi-pen"></i></span>

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

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="email" name="column[customers][email]" data-id="email_show" value="1" <?php if (isset($table_columns_data['email']) && ! empty($table_columns_data['email'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="email">Email</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="phone" name="column[customers][phone]" data-id="phone_show" value="1" <?php if (isset($table_columns_data['phone']) && ! empty($table_columns_data['phone'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="phone">Phone</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[customers][company]" data-id="company_show" value="1" <?php if (isset($table_columns_data['company']) && ! empty($table_columns_data['company'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="company">Company</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="cost_place" name="column[customers][cost_place]" data-id="cost_place_show" value="1" <?php if (isset($table_columns_data['cost_place']) && ! empty($table_columns_data['cost_place'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="cost_place">Organization Number</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="parent_customer" name="column[customers][parent_customer]" data-id="parent_customer_show" value="1" <?php if (isset($table_columns_data['parent_customer']) && ! empty($table_columns_data['parent_customer'])) { ?> checked <?php } ?>>

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

                                        <th class="table-head">Status</th>

                                        <th class="table-head">Name</th>

                                        <th class="table-head email_show <?php if (! isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>">Email</th>

                                        <th class="table-head phone_show <?php if (! isset($table_columns_data['phone']) || empty($table_columns_data['phone'])) { ?> custom_hide<?php } ?>">Phone</th>

                                        <th class="table-head">
                                            <abbr title="Interview Template">In.Temp</abbr>
                                        </th>
                                        <th class="table-head">
                                            <abbr title="Interview Report Upload">In.Rep</abbr>
                                        </th>

                                        <th class="table-head company_show <?php if (! isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>">Company</th>

                                        <th class="table-head cost_place_show <?php if (! isset($table_columns_data['cost_place']) || empty($table_columns_data['cost_place'])) { ?> custom_hide<?php } ?>">Organization Number</th>

                                        <th class="table-head parent_customer_show <?php if (! isset($table_columns_data['parent_customer']) || empty($table_columns_data['parent_customer'])) { ?> custom_hide<?php } ?>">Parent Customer</th>

                                        <th class="table-head"><abbr title="Interview Remainder Email">IRE</abbr></th>
                                        <th class="table-head"><abbr
                                                title="Background Check Remainder Email">BCRE</abbr></th>



                                    </tr>

                                </thead>

                                <tbody>



                                    <?php if (! empty($customers)) : ?>

                                        <?php foreach ($customers as $key => $customer) : ?>



                                            <tr>

                                                <td class="f-14">

                                                    <input class="form-check-input d-check delete-candidate" id="checkbox-<?php echo $customer->id ?>" name="delete[]" value="<?php echo $customer->id ?>" type="checkbox">

                                                    <label class="form-check-label" for="checkbox-<?php echo $customer->id ?>" class="mr-2 label-table"></label>

                                                </td>

                                                <td>

                                                    <div class="dropdown">

                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">

                                                            <i class="bi bi-gear"></i>

                                                        </button>

                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                                            <li class="mb-1"><a href="update-customer.php?id=<?php echo $customer->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                                                    Edit</a>

                                                            </li>



                                                            <li class="mb-1"><a href="customers.php?delete=<?php echo $customer->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                                                    Delete</a>

                                                            </li>

                                                            <li class="mb-1">

                                                                <a href="#" class="no-decoration f-14 w-600 delay_set_id text-black" data-bs-toggle="modal" data-bs-target="#delay_date"> <i class="bi bi-info  text-black f-14 me-2"></i>

                                                                    Duration

                                                                </a>

                                                            </li>



                                                        </ul>

                                                    </div>

                                                </td>

                                                <td class="f-14"><a class="no-decoration text-black open-customer" data-id="<?php echo $customer->id ?>" data-days="<?php echo $customer->report_delete_duration ?>" href="update-customer.php?id=<?php echo $customer->id ?>"><?php echo $customer->name ?></a></td>

                                                <td class="f-14 email_show <?php if (! isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>"><?php echo $customer->email ?></td>

                                                <td class="f-14 phone_show <?php if (! isset($table_columns_data['phone']) || empty($table_columns_data['phone'])) { ?> custom_hide<?php } ?>"><?php echo $customer->phone ?></td>

                                                <td class="f-14">

                                                    <input class="form-check-input" id="interview_template-<?php echo $customer->id ?>" <?php echo $customer->interview_template == 1 ? 'checked' : '' ?> value="<?php echo $customer->id ?>" type="checkbox" onclick="check_interview_template(this)">

                                                    <label class="form-check-label" for="interview_template-<?php echo $customer->id ?>" class="mr-2 label-table"></label>

                                                </td>
                                                                                                <td class="f-14">
                                                    <input class="form-check-input" data-cuscheckbox="<?php echo $customer->id ?>"
                                                        id="interview_upload_allowed-<?php echo $customer->id ?>" <?php echo $customer->interview_upload_allowed == 1 ? 'checked' : '' ?>
                                                        value="<?php echo $customer->id ?>" type="checkbox"
                                                        onclick="check_interview_upload_allowed(this)"
                                                        data-parent="<?php echo $customer->parent_id ?>">
                                                    <label class="form-check-label"
                                                        for="interview_upload_allowed-<?php echo $customer->id ?>"
                                                        class="mr-2 label-table"></label>
                                                </td>

                                                <td class="f-14 company_show <?php if (! isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide<?php } ?>"><?php echo $customer->company ?></td>

                                                <td class="f-14 cost_place_show <?php if (! isset($table_columns_data['cost_place']) || empty($table_columns_data['cost_place'])) { ?> custom_hide<?php } ?>"><?php echo $customer->org_no ?></td>

                                                <td class="f-14 parent_customer_show <?php if (! isset($table_columns_data['parent_customer']) || empty($table_columns_data['parent_customer'])) { ?> custom_hide<?php } ?>"><?php echo $customer->parent_customer ?></td>

                                                <td class="f-14">

                                                    <input class="form-check-input email_remainder" id="email_remainder_template-<?php echo $customer->id ?>" <?php echo $customer->remainder_email == 1 ? 'checked' : '' ?> value="<?php echo $customer->id ?>" type="checkbox" onclick="check_remainder_email_template(this); openOrCloseEmailReminderModal(this)" data-id="<?= $customer->id ?>">

                                                    <label class="form-check-label" for="email_remainder_template-<?php echo $customer->id ?>" class="mr-2 label-table"></label>

                                                </td>
                                                                                                <td class="f-14">
                                                    <input class="form-check-input email_remainder"
                                                        id="bk_email_remainder_template-<?php echo $customer->id ?>" <?php echo $customer->bk_remainder_email == 1 ? 'checked' : '' ?>
                                                        value="<?php echo $customer->id ?>" type="checkbox"
                                                        onclick="check_bk_remainder_email_template(this); openOrCloseEmailBKReminderModal(this)"
                                                        data-id="<?= $customer->id ?>">
                                                    <label class="form-check-label"
                                                        for="bk_email_remainder_template-<?php echo $customer->id ?>"
                                                        class="mr-2 label-table"></label>
                                                </td>

                                            </tr>



                                        <?php endforeach; ?>

                                    <?php endif; ?>



                                </tbody>

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



<div class="modal fade" id="email_reaminder_template" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">

                    <p class="f-16 w-700 mb-0 pb-0">Interview Email Remainder Template</p>

                </h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <form id="email_remainder_form" action="" method="post">

                    <input type="hidden" name="cus_id" id="email_template_id">

                    <label for="">Email Body</label>

                    <textarea name="email_body" id="" style="width: 100%;" rows="6"></textarea>

                    <div class="d-flex justify-content-end">

                        <button type="submit" name="update_candidate" class="btn-primary bg-primary" data-bs-dismiss="modal">Save</button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>
<div class="modal fade" id="bk_email_reaminder_template" tabindex="-1" aria-labelledby="exampleModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">
                    <p class="f-16 w-700 mb-0 pb-0">Background Check Email Remainder Template</p>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bk_email_remainder_form" action="" method="post">
                    <input type="hidden" name="cus_id" id="bk_email_template_id">
                    <label for="">Email Body</label>
                    <textarea name="bk_email_body" id="" style="width: 100%;" rows="6"></textarea>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="update_candidate" class="btn-primary bg-primary"
                            data-bs-dismiss="modal">Save</button>
                    </div>
                </form>
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
    function check_interview_upload_allowed(obj) {
        var check = 0
        if ($(obj).is(':checked')) {
            check = 1
            $('input[data-parent="' + $(obj).val() + '"]').attr('checked', true);
        }else{
            $('input[data-parent="' + $(obj).val() + '"]').attr('checked', false);
        }
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: {
                'interview_upload_allowed': 1,
                'check': check,
                'id': $(obj).val(),
            },
            success: function (response) {
                
            }
        });
    }


    function check_remainder_email_template(obj) {

        var check = 0

        if ($(obj).is(':checked')) {

            check = 1

        }

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                'interviewed_template': 1,

                'check': check,

                'id': $(obj).val(),

            },

            success: function(response) {



            }

        });

    }
    function check_bk_remainder_email_template(obj) {
        var check = 0
        if ($(obj).is(':checked')) {
            check = 1
        }
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: {
                'bk_interviewed_template': 1,
                'check': check,
                'id': $(obj).val(),
            },
            success: function (response) {

            }
        });
    }


    $(document).ready(function() {



        $('#email_remainder_form').submit(function(e) {

            e.preventDefault();



            var formData = new FormData(this);

            formData.append('remainder_email_template', 1);



            $.ajax({

                type: 'POST',

                url: './includes/table_ajax.php',

                data: formData,

                processData: false, // Prevent jQuery from automatically processing the FormData object

                contentType: false, // Prevent jQuery from setting contentType

                success: function(response) {

                    alert('Email Template Saved Successfully')

                },

                error: function(xhr, status, error) {

                    // Handle error here

                    console.error(xhr.responseText);

                }

            });

        });
                $('#bk_email_remainder_form').submit(function (e) {
            e.preventDefault();

            var formData = new FormData(this);
            formData.append('bk_remainder_email_template', 1);

            $.ajax({
                type: 'POST',
                url: './includes/table_ajax.php',
                data: formData,
                processData: false, // Prevent jQuery from automatically processing the FormData object
                contentType: false, // Prevent jQuery from setting contentType
                success: function (response) {
                    alert('Email Template Saved Successfully')
                },
                error: function (xhr, status, error) {
                    // Handle error here
                    console.error(xhr.responseText);
                }
            });
        });

    });



    function openOrCloseEmailReminderModal(checkboxElement) {

        var isChecked = $(checkboxElement).is(":checked");

        if (isChecked) {

            $('#email_template_id').val($(checkboxElement).data('id'))

            $.ajax({

                type: 'POST',

                url: './includes/table_ajax.php',

                data: {

                    'fetch_template': 1,

                    'id': $(checkboxElement).data('id')

                },

                success: function(response) {

                    $('textarea[name="email_body"]').val('')

                    if (response != '') {

                        response = JSON.parse(response)

                        $('textarea[name="email_body"]').val(response[0].remainder_email_template)

                    }

                    $('#email_reaminder_template').modal('show');

                },

                error: function(xhr, status, error) {

                    console.error(xhr.responseText);

                }

            });

        } else {

            $('#email_reaminder_template').modal('hide');

        }

    }
        function openOrCloseEmailBKReminderModal(checkboxElement) {
        var isChecked = $(checkboxElement).is(":checked");
        if (isChecked) {
            $('#bk_email_template_id').val($(checkboxElement).data('id'))
            $.ajax({
                type: 'POST',
                url: './includes/table_ajax.php',
                data: {
                    'fetch_bk_template': 1,
                    'id': $(checkboxElement).data('id')
                },
                success: function (response) {
                    $('textarea[name="bk_email_body"]').val('')
                    if (response != '') {
                        response = JSON.parse(response)
                        $('textarea[name="bk_email_body"]').val(response[0].bk_remainder_email_template)
                    }
                    $('#bk_email_reaminder_template').modal('show');
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        } else {
            $('#bk_email_reaminder_template').modal('hide');
        }
    }

</script>