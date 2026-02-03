<?php

$activeLink = "customers";

include_once('includes/header.php');

if (!isset($_POST['delete']) && !isset($_POST['update'])) {
    redirect('index.php');
}


if (isset($_POST['update'])) {
    $groupid = null;
    $insert_array = [];
    $insert_form = [];
    $cus_ids = !empty($_POST['cus_id']) ? $_POST['cus_id'] : null;
    $parent_customer = !empty($_POST['parent_customer']) ? $_POST['parent_customer'] : null;
    $cus_department = !empty($_POST['cus_department']) ? $_POST['cus_department'] : null;
    $parent_msg = findAllByQuery("SELECT * FROM messages WHERE cus_id = '$parent_customer'");
    $parent_forms = findAllByQuery("SELECT * FROM order_forms WHERE cus_id = '$parent_customer'");
    if (!empty($cus_ids)) {
        foreach ($cus_ids as $cus_id) {
            update('customers', ['parent_id' => $parent_customer, 'dep_id' => $cus_department], 'id', $cus_id);
            $cur_msg = findByQuery("SELECT * FROM messages WHERE cus_id = '$cus_id'");
            if (!empty($cur_msg)) {
                delete('messages', 'cus_id', $cus_id);
            }
            $cur_forms = findByQuery("SELECT * FROM order_forms WHERE cus_id = '$cus_id'");
            if (!empty($cur_forms)) {
                delete('order_forms', 'cus_id', $cus_id);
            }
            if (!empty($parent_forms)) {
                foreach ($parent_forms as $parent_fo) {
                    foreach ($parent_fo as $f_m => $parent_f) {
                        if ($f_m != 'id') {
                            if ($f_m == 'cus_id') {
                                $insert_form[$f_m] = $cus_id;
                            } else {
                                $insert_form[$f_m] = $parent_f;
                            }
                        }
                    }
                    insert('order_forms', $insert_form);
                }
            }

            if (!empty($parent_msg)) {
                foreach ($parent_msg as $parent_ms) {
                    foreach ($parent_ms as $k_m => $parent_m) {
                        if ($k_m != 'id') {
                            if ($k_m == 'cus_id') {
                                $insert_array[$k_m] = $cus_id;
                            } else {
                                $insert_array[$k_m] = $parent_m;
                            }
                        }
                    }
                    insert('messages', $insert_array);
                }
            }
        }
    }
    redirect('customers.php');
}
$parent_customer = findallByQuery("SELECT * FROM customers");

?>

<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Company</h1>
                    </div>
                    <form class="update-form" method="post">
                        <div class="col-md-12 mb-3">
                            <label>Parent Customer</label>
                            <select name="parent_customer" id="parent_customer" onchange="get_dep(this)" class="filter-select form-control">
                                <option value="">-Select Customer-</option>
                                <?php if (!empty($parent_customer)) { ?>
                                    <?php foreach ($parent_customer as $key => $val) { ?>
                                        <option value="<?= $val->id ?>"><?= $val->name ?></option>
                                    <?php } ?>
                                <?php } ?>
                            </select>
                            <?php if (isset($_POST['delete'])) : ?>
                                <?php foreach ($_POST['delete'] as $can) : ?>
                                    <input type="hidden" name="cus_id[]" value="<?= $can ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label>Departments</label>
                            <select name="cus_department" id="cus_department" class="filter-select form-control">
                                <option value="">-Select Department-</option>
                            </select>
                        </div>
                        <div class="d-flex justify-content-end">
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

<?php

include_once('includes/footer.php');

?>
<script>
    function get_dep(obj) {
        var cus_id = $(obj).val();
        if (cus_id != '') {
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    'id': cus_id,
                    'get_par_department': 1
                },
                success: function(response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.customers != '') {
                            var cus = response.customers;
                            $('.main-heading').html(cus[0].company)
                        } else {
                            $('.main-heading').html('Company')
                        }
                        var opt_html = '<option value="">-Select Department-</option>';
                        if (response.departments != '') {
                            var dep = response.departments;
                            $(dep).each(function(i, v) {
                                opt_html += '<option value="' + v.dep_id + '">' + v.dep_name + '</option>';
                            })
                            $('#cus_department').html(opt_html)
                        } else {
                            $('#cus_department').html(opt_html)
                        }
                        if (response.services != '') {
                            var ser = response.services;
                        }
                    }
                }
            });
        }
    }
</script>