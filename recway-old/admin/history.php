<?php

include_once ('includes/header.php');

if(isset($_GET['oid'])) {
    $query = "UPDATE candidates SET invoice_sent = 0, invoice_date = NULL, expired = 0 WHERE order_id = '{$_GET['oid']}'";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute();

    if($res) {
        $query = "DELETE FROM order_history WHERE order_id = '{$_GET['oid']}'";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute();

        if($res) {
            $message = "<p class='alert alert-success'>Order recovered!</p>";
        }
    }
}


if(isset($_GET['id'])) {
    $query = 'SELECT *, o.id AS oid FROM order_history o';
    if(isset($_GET['status'])) {
        $query .= " INNER JOIN candidates c ON c.order_id = o.order_id WHERE c.cus_id = {$_GET['id']} AND c.status = {$_GET['status']}";
    } else {
        $query .= " INNER JOIN candidates c ON c.order_id = o.order_id WHERE c.cus_id = {$_GET['id']}";
    }
} else {
    $query = 'SELECT *, o.id AS oid FROM order_history o';
}
$query .= " ORDER BY o.created DESC";
$stmt = $conn->prepare($query);
$stmt->execute();
$order_histories = $stmt->fetchAll();

$query = "SELECT * FROM customers";
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

if(isset($_GET['id'])) {
    $query = "SELECT * FROM customers c INNER JOIN candidates c2 on c.id = c2.cus_id WHERE c.id = {$_GET['id']}";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customerMain = $stmt->fetch();

}

?>


                <div class="row">
                    <div class="col-lg-12 history-data">
                        <?php
                        $pageTitle = "History";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>



                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>

                            <div class="row">
                                <div class="col-lg-12">
                                    <select class="form-select mb-2" name="customer" id="customer">
                                        <option disabled selected>Choose Customer</option>
                                        <?php if(!empty($customers)): ?>
                                        <?php foreach ($customers as $customer): ?>
                                            <option <?php echo isset($_GET['id']) && $customer->id == $_GET['id'] ? 'selected' : '' ?> value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <?php if(isset($_GET['id'])): ?>
                            <div class="row">
                                <p  class="ms-1">Cost Place: <?php echo !empty($customerMain->cost_place) ? $customerMain->cost_place : 'Null' ?></p>
                            </div>
                            <?php endif; ?>
                            <div class="data-table  ">
                                <div class="customer-table history-table">
                                    <form action="" method="post" id="d-form">
                                        <table id="dataTable" class="table display nowrap" style="width:100%">
                                            <thead>
                                            <tr>
                                                <th>Order ID</th>
                                                <th>Service Type</th>
                                                <th>Company</th>
                                                <th>Invoice Date</th>
                                                <?php if(isset($_GET['id'])): ?>
                                                    <th>Reference</th>
                                                <?php endif; ?>
                                                <th>Created</th>
                                                <th>Status</th>
                                                <th>Status Date</th>
                                                <th class="dt-center">Action</th>
                                            </tr>
                                            </thead>
                                            <tbody>

                                            <?php if(!empty($order_histories)): ?>
                                            <?php foreach ($order_histories as $order_history): ?>
                                            <?php
                                                $query = 'SELECT * FROM interviews WHERE id = ?';
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([$order_history->interview_id]);
                                                $interview = $stmt->fetch();
                                            ?>
                                                <tr>
                                                    <td><?php echo $order_history->order_id ?></td>
                                                    <td><?php echo $interview->title ?></td>
                                                    <td><?php echo $order_history->company ?></td>
                                                    <td><?php echo !empty($order_history->invoice_date) ? date('M d, Y', strtotime($order_history->invoice_date)) : 'Null' ?></td>
                                                    <?php if(isset($_GET['id'])): ?>
                                                        <td><?php echo $order_history->reference ?></td>
                                                    <?php endif; ?>
                                                    <td><?php echo date('M d, Y', strtotime($order_history->created)) ?></td>
                                                    <?php $status = getStatusById($order_history->status) ?>
                                                    <td><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                                                    <td><?php echo date('M d, Y', strtotime($order_history->status_date)) ?></td>
                                                    <td class="text-center dt-center">
                                                        <div class="dropdown profile-dropdown " >
                                                            <button class=" " type="button" id="dropdownMenuButton1"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu shadow-sm"
                                                                aria-labelledby="dropdownMenuButton1">
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="history-detail.php?id=<?php echo $order_history->oid ?>"><i
                                                                                class="bi bi-eye me-2 f-16 w-600"></i>View</a></li>
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="?oid=<?php echo $order_history->order_id ?>"><i
                                                                                class="bi bi-arrow-repeat me-2 f-16 w-600"></i>Recover</a></li>
                                                            </ul>
                                                        </div>

                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
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

include_once ('includes/footer.php');

?>

<script>
    $('#customer').on('change', function () {
        location.href = location.pathname + "?id=" + $(this).val();
    })
</script>
