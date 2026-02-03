<?php

$activeLink = "start-order";

include_once "includes/header.php";

if(!isset($_GET['id'])) {
    redirect("services.php");
}

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

if(!empty($interviews) && !empty($customer_services)) {
    $allowed_services = array();
    array_walk($interviews, function ($interview,$key) {
        global $customer_services, $allowed_services;
        if(in_array($interview->id, array_column($customer_services, "service_id"))) {
            array_push($allowed_services, $interview);
        }
    });
}

$candidates = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['customer']->id} AND expired = 0 ORDER BY created DESC LIMIT 4");
?>
    
      <section class="h-100">
        <div class="container mt-3 h-100 ">
          <div class="row h-100">
            <div class="col-lg-9 ">
                 <div class="container-fluid p-0 ">
                    <div class="row p-0">
                        <?php if(!empty($allowed_services)): ?>
                        <?php foreach ($allowed_services as $interview): ?>
                            <div class="col-lg-6 col-md-6">
                                <a href="order.php" class="no-decoration">
                                    <div class="service-card pb-0">

                                        <div class="d-flex justify-content-between">
                                            <h1 class="f-18 w-700 text-black "><?php echo $interview->title ?> </h1>
                                            <div class="tooltip-css">
                                                <i class="bi bi-info-circle-fill text-dark f-18 " > </i>
                                                <span class="tooltiptext-css">
                                                    <p class="f-12 text-white w-400 mb-1 pb-0"><?php echo $interview->desc ?></p>
                                                </span>
                                             </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div >
                                                <img src="assets/images/<?php echo $_GET['id'] == 1 ? 'interview.png' : 'history.png' ?>" alt="" class="img-fluid" height="80" width="80" >
                                            </div>
                                            <div>
                                                <a href="order.php?i=<?php echo $interview->id ?>" class="form-btn">Place Order</a>
                                            </div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                     
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
                    <?php if(!empty($candidates)): ?>
                        <?php foreach ($candidates as $candidate): ?>
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
                    <?php else: ?>
                    <p>No recent orders</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>
          </div>
      </section>

<?php

include_once "includes/footer.php";

?>