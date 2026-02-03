<?php

include_once ('includes/header.php');

if(isset($_GET['d'])) {
    $query = "DELETE FROM order_history WHERE id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['d']]);

    if($res) {
        redirect('history.php');
    }
}

if(!isset($_GET['id'])){
    redirect('history.php');
}

$query = "SELECT * FROM order_history WHERE id = {$_GET['id']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$history = $stmt->fetch();


$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$history->interview_id]);
$interview = $stmt->fetch();

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$history->cus_id]);
$customer = $stmt->fetch();

$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$history->staff_id]);
$staff = $stmt->fetch();

?>

                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading box shadow  w-100">
                            
                            <div class="container-fluid ">
                                <div class="row p-2 w-600 mt-3 bg-light">
                                    <div class="d-flex justify-content-between">
                                        <span>Order History</span>
                                        <a style="text-decoration: none; color: black" data-toggle="tooltip" data-placement="top" title="Delete" href="history-detail.php?d=<?php echo $history->id ?>" class="mx-1"><i class="fa-solid fa-trash"></i></a>
                                    </div>
                                </div>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3">Company</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo $history->company ?></p>
                                    </div>
                                </div>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3">Service Type</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo $interview->title ?></p>
                                    </div>
                                </div>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3">Customer</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->name ?></p>
                                    </div>
                                </div>
                                <?php if(!empty($staff)): ?>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Staff</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $staff->name ?></p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3">Order ID</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo $history->order_id ?></p>
                                    </div>
                                </div>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3">Created</p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo date('M d, Y', strtotime($history->created)) ?></p>
                                    </div>
                                </div>
                                <div class="row border-bottom ">
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <?php $status = getStatusById($history->status) ?>
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo $status->status ?></p>
                                    </div>
                                    <div class="col-lg-6 col-md-6 col-12">
                                        <p class="mb-0 f-18 px-2 py-3"><?php echo date('M d, Y', strtotime($history->status_date)) ?></p>
                                    </div>
                                </div>
                            </div>
                            

                        </div>
                       
                    </div>

<?php

include_once ('includes/footer.php');

?>