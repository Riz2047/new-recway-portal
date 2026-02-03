<?php

$activeLink = "start-order";

include_once "includes/header.php";

$query = "SELECT * FROM service_categories";
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
$services2 = array();

$query = "SELECT * FROM customer_services WHERE customer_services.cus_id={$_SESSION['customer']->id}";
$stmt = $conn->prepare($query);
$stmt->execute();
$customer_services = $stmt->fetchAll();

foreach ($customer_services as $service) {
    $query = "SELECT * FROM interviews WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$service->service_id]);
    $interview = $stmt->fetch();

    $allowed_service = findObjectById($interview->service_cat_id, $services);
    if(!empty($allowed_service)) {
        $a = $services[array_search($allowed_service, $services)];
        if(!in_array($a, $services2)) {
            array_push($services2, $a);
        }
    }
}

$candidates = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['customer']->id} AND expired = 0 ORDER BY created DESC LIMIT 4");
?>
    
      <section>
        <div class="container mt-3">
          <div class="row h-100">
            <div class="col-lg-9 ">
                 <div class="container-fluid p-0 ">
                    <div class="row p-0">
                        <?php if(!empty($services2)): ?>
                        <?php foreach ($services2 as $service): ?>
                            <div class="col-lg-6 col-md-6">
                                <a href="interview.php?id=<?php echo $service->id ?>" class="no-decoration">
                                    <div class="service-card">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <img src="assets/images/<?php echo $service->id == 1 ? 'interview.png' : 'history.png' ?>" alt="" class="img-fluid" height="200" width="200" >
                                        </div>
                                        <h1 class="f-18 w-700  text-center"><?php echo $service->name ?> </h1>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center w-700">No services to show</p>
                        <?php endif; ?>
                        <div class="col-lg-12">
                            <div class="grey-box mt-2 ">
                                <div class="d-flex justify-content-between align-items-center">
                                  <div>
                                    <p class="f-16 w-600 text-black mb-0 pb-0">Contact Us:</p>
                                  </div>
                                  <div class="icon">
                                    <i class="bi bi-envelope-at text-black"></i>
                                  </div>
                                </div>
                                <div class="d-flex justify-content-between align-items-center ">
                                  <h1 class="f-24 w-600 text-black mb-0 pb-0">  <a href="mailto:info@recway.nu"
                                    class="no-decoration text-black f-16 ">info@recway.nu</a></h1>
                                </div>
                
                              </div>
                        </div>
                    </div>

                 </div>

            </div>
            <div class="col-lg-3">
               
                <div class="grey-box h-100">
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