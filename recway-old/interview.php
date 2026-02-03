<?php

$activeLink = "start-order";

include_once "customer/includes/header.php";

if (!isset($_GET['id'])) {
    redirect("services.php");
}

// Create a DateTime object for Sweden's timezone
$swedenTimezone = new DateTimeZone('Europe/Stockholm');
$swedenTime = new DateTime('now', $swedenTimezone);
$currentTime = $swedenTime->format('H:i:s');
$dayOfWeek = date('N');


$query = 'SELECT * FROM interviews WHERE service_cat_id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$interviews = $stmt->fetchAll();

$query = "SELECT * FROM service_categories WHERE id={$_GET['id']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$service = $stmt->fetch();

$query = "SELECT * FROM customer_services WHERE cus_id={$_SESSION['customer']->id}";
$stmt = $conn->prepare($query);
$stmt->execute();
$customer_services = $stmt->fetchAll();

if (!empty($interviews) && !empty($customer_services)) {
    $allowed_services = array();
    array_walk($interviews, function ($interview, $key) {
        global $customer_services, $allowed_services;
        if (in_array($interview->id, array_column($customer_services, "service_id"))) {
            array_push($allowed_services, $interview);
        }
    });
}

$candidates = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['customer']->id} AND expired = 0 ORDER BY created DESC LIMIT 4");
$question_permission = findAllByQuery("SELECT * FROM user_permissions LEFT JOIN user_allowed_permissions ON user_permissions.id = user_allowed_permissions.per_id WHERE user_allowed_permissions.user_id = {$_SESSION['customer']->id} AND user_permissions.title = '3-Background-Check-Questions'");

?>

<section class="h-100">
    <div class="container mt-3 h-100 ">
        <div class="row h-100">
            <div class="col-lg-9 ">
                <div class="container-fluid p-0 ">
                    <div class="row p-0">
                        <?php if (!empty($allowed_services)) : ?>
                            <?php foreach ($allowed_services as $interview) : ?>
                                <div class="col-lg-6 col-md-6">
                                    <a <?php if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') { ?> href="order.php" <?php } else { ?>href="#" <?php } ?>class="no-decoration">
                                        <div class="service-card pb-0">

                                            <div class="d-flex justify-content-between">
                                                <h1 class="f-18 w-700 text-black "><?php echo $interview->title ?> </h1>
                                                </br>
                                                <div class="tooltip-css">
                                                    <i class="bi bi-info-circle-fill text-dark f-18 "> </i>
                                                    <span class="tooltiptext-css">
                                                        <p class="f-12 text-white w-400 mb-1 pb-0"><?php echo $interview->desc ?></p>
                                                    </span>
                                                </div>
                                            </div>
                                            <?php if (!empty($interview->delivery_days)) { ?>
                                                <p class="f-16 text-black ">Delivery Days : <?php echo $interview->delivery_days ?> </p>
                                            <?php } ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div>
                                                    <img src="customer/assets/images/<?php echo $_GET['id'] == 1 ? 'interview.png' : 'history.png' ?>" alt="" class="img-fluid" height="80" width="80">
                                                </div>
                                                <div>
                                                    <a href="order.php?i=<?php echo $interview->id ?><?php if ($_GET['id'] == 3 && !empty($question_permission[0]->per_id)) { ?>&&question_check=1<?php } ?>" class="form-btn">Place Order</a>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="modal fade" id="time_modal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content ">
                                    <div class="modal-header">
                                        <h5 class="modal-title f-16 w-600 text-black" id="exampleModalLabel">Service Not Available</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>These services are only available from Monday to Friday. <br> <b> Thank you!!</b></p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="form-btn" data-bs-dismiss="modal">Okay</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-3">
                <div class="grey-box ">
                    <div class="d-flex align-items-center justify-content-between w-100 mb-1">
                        <div>
                            <h2 class="f-14 w-800 text-black">
                                Recent Orders
                            </h2>
                        </div>
                        <div>
                            <p><a href="orders.php" class="no-decoration text-grey f-12 w-500">View </a></p>
                        </div>
                    </div>
                    <?php if (!empty($candidates)) : ?>
                        <?php foreach ($candidates as $candidate) : ?>
                            <a href="invoice.php?id=<?php echo $candidate->id ?>" class="no-decoration">
                                <div class="side-card mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h1 class="f-14 w-600 text-black mb-0 pb-0"><?php echo $candidate->name . " " . $candidate->surname ?></h1>
                                            <p class="mb-0 pb-0 f-12 text-grey w-600"><?php echo $candidate->order_id ?></p>
                                        </div>
                                        <div>
                                            <h2 class="f-14 w-800 text-black m-0 p-0">
                                                <?php echo $candidate->vasc_id ?>
                                            </h2>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p>No recent orders</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>