<?php

include_once('includes/header.php');

if (! isset($_GET['id'])) {
    redirect('orders.php');
}

$query = "SELECT * FROM staff_permissions WHERE staff_id = ? AND permission_id = ? LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['staff']->id, 1]);
$view_customer_permission = $stmt->fetch();

if (empty($view_customer_permission)) {
    redirect("index.php");
}

$query = "SELECT * FROM customers WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();

?>

                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading  w-100">
                            
                            <div class=" ">
                                <h1 class="  mb-0 pb-0 mt-4">Customer `s Information</h1>
                                <div class="box shadow px-4 mt-2">
                                    <div class="row p-2 w-600 mt-3 bg-light">
                                        PROFILE
                                    </div>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Name</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->name ?></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Email</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->email ?></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Phone</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->phone ?></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Company</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->company ?></p>
                                        </div>
                                    </div>
                                    <div class="row border-bottom ">
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3">Cost Place</p>
                                        </div>
                                        <div class="col-lg-6 col-md-6 col-12">
                                            <p class="mb-0 f-18 px-2 py-3"><?php echo $customer->cost_place ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                        </div>
                       
                    </div>

<?php

include_once('includes/footer.php');

?>