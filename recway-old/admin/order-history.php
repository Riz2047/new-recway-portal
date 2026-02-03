<?php

include_once ('includes/header.php');
include_once ('../includes/config.php');

if(!isset($_GET['id'])) {
    redirect('orders-history.php');
}

$query = "SELECT * FROM history WHERE order_id = {$_GET['id']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$history = $stmt->fetchAll();

?>


                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading d-flex justify-content-between w-100">
                            <h1 class=" my-4">History</h1>
                        </div>
                        <div class="timeline-container">
                            <div class="timeline-wrapper">
                                <ul class="sessions">
                                    <?php if($history): ?>
                                    <?php foreach ($history as $h): ?>

                                            <li>
                                                <div class="time"><?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?></div>
                                                <p><?php echo $h->desc ?></p>
                                            </li>

                                    <?php endforeach; ?>
                                    <?php else: ?>

                                        <li>
                                            <div class="time"><?php echo "No record found" ?></div>
                                        </li>

                                    <?php endif; ?>
                                </ul>
                            </div>
                        </div>
                    </div>

                </div>

<?php

include_once ('includes/footer.php');

?>
