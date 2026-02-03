<?php



$activeLink = "history";



include_once('includes/header.php');



if (isset($_GET['oid'])) {

    $query = "UPDATE candidates SET invoice_sent = 0, invoice_date = NULL, expired = 0 WHERE order_id = '{$_GET['oid']}'";

    $stmt = $conn->prepare($query);

    $res = $stmt->execute();



    if ($res) {

        $query = "DELETE FROM order_history WHERE order_id = '{$_GET['oid']}'";

        $stmt = $conn->prepare($query);

        $res = $stmt->execute();



        if ($res) {

            flash("candidateRecovered", "Candidate has been recovered!");

        }

    }

}





// History data will be loaded via AJAX (DataTables server-side)
$order_histories = [];



$query = "SELECT * FROM customers";

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();



if (isset($_GET['id'])) {

    $query = "SELECT * FROM customers c INNER JOIN candidates c2 on c.id = c2.cus_id WHERE c.id = {$_GET['id']}";

    $stmt = $conn->prepare($query);

    $stmt->execute();

    $customerMain = $stmt->fetch();

}

$query = "SELECT * FROM tables_settings WHERE name = 'History'";

$stmt = $conn->prepare($query);

$stmt->execute();

$table = $stmt->fetchAll();

$table_columns_data = null;

if (!empty($table[0]->meta_data)) {

    $table_columns_data = json_decode($table[0]->meta_data, true);

}

?>



<?php flash("candidateRecovered"); ?>

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

                                <a href="#" class="white-text mx-3">History</a>

                                <div style="display:flex !important">

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

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="company" name="column[history][company]" data-id="company_show" value="1" <?php if (isset($table_columns_data['company']) && !empty($table_columns_data['company'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="company">Company</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="invoice_date" name="column[history][invoice_date]" data-id="invoice_date_show" value="1" <?php if (isset($table_columns_data['invoice_date']) && !empty($table_columns_data['invoice_date'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="invoice_date">Invoice Date</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="created" name="column[history][created]" data-id="created_show" value="1" <?php if (isset($table_columns_data['created']) && !empty($table_columns_data['created'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="created">Created</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="status" name="column[history][status]" data-id="status_show" value="1" <?php if (isset($table_columns_data['status']) && !empty($table_columns_data['status'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="status">Status</label>

                                            </div>

                                            <div class="col-md-3">

                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="status_date" name="column[history][status_date]" data-id="status_date_show" value="1" <?php if (isset($table_columns_data['status_date']) && !empty($table_columns_data['status_date'])) { ?> checked <?php } ?>>

                                                <label class="form-label form-check-label" for="status_date">Status Date</label>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="row">

                                <div class="col-lg-12 pl-5 pr-5">

                                    <select class="form-select mb-1 filter-select" name="customer" id="customer">

                                        <option disabled selected>Choose Customer</option>

                                        <?php if (!empty($customers)) : ?>

                                            <?php foreach ($customers as $customer) : ?>

                                                <option <?php echo isset($_GET['id']) && $customer->id == $_GET['id'] ? 'selected' : '' ?> value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                    </select>

                                </div>

                            </div>

                            <?php if (isset($_GET['id'])) : ?>

                                <div class="row ml-4 mt-3">

                                    <p class="ms-1">Cost Place: <?php echo !empty($customerMain->cost_place) ? $customerMain->cost_place : 'Null' ?></p>

                                </div>

                            <?php endif; ?>

                            <table id="dataTable" data-table="history" class="display Table" style="width: 100%">

                                <thead>

                                    <tr>

                                        <th class="dt-center table-head">Action</th>

                                        <th class="table-head">Order ID</th>

                                        <th class="table-head">Service Type</th>

                                        <th class="table-head company_show <?php if (!isset($table_columns_data['company']) || empty($table_columns_data['company'])) { ?> custom_hide <?php } ?>">Company</th>

                                        <th class="table-head invoice_date_show  <?php if (!isset($table_columns_data['invoice_date']) || empty($table_columns_data['invoice_date'])) { ?> custom_hide <?php } ?>">Invoice Date</th>

                                        <?php if (isset($_GET['id'])) : ?>

                                            <th class="table-head">Reference</th>

                                        <?php endif; ?>

                                        <th class="table-head created_show <?php if (!isset($table_columns_data['created']) || empty($table_columns_data['created'])) { ?> custom_hide <?php } ?>">Created</th>

                                        <th class="table-head status_show <?php if (!isset($table_columns_data['status']) || empty($table_columns_data['status'])) { ?> custom_hide <?php } ?>">Status</th>

                                        <th class="table-head status_date_show <?php if (!isset($table_columns_data['status_date']) || empty($table_columns_data['status_date'])) { ?> custom_hide <?php } ?>">Status Date</th>



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

    var statuses = <?php echo json_encode($statuses) ?>

</script>

<?php



include_once('includes/footer.php');



?>

<script>

    $('#customer').on('change', function() {

        location.href = location.pathname + "?id=" + $(this).val();

    })

</script>