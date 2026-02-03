<?php

$activeLink = "history";

include_once "includes/header.php";

$query = 'SELECT * FROM order_history WHERE cus_id = ? ORDER BY created DESC';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['customer']->id]);
$order_histories = $stmt->fetchAll();

?>
    
      <section>
        <div class="container mt-3">
          <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">History</p>
            <div class="col-lg-12">
              <div class="table-div p-2">
                <table id="myTable" class="display Table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Company</th>
                        <th>Status</th>
                        <th>Service Type</th>
                        <th>Invoice Date</th>
                        <th>Order Created</th>
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
                                <td><?php echo $order_history->company ?></td>
                                <?php $status = getStatusById($order_history->status) ?>
                                <td class="text-nowrap"><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                                <td><?php echo $interview->title ?></td>
                                <td><?php echo !empty($order_history->invoice_date) ? date('M d, Y', strtotime($order_history->invoice_date)) : 'Null' ?></td>
                                <td><?php echo date('M d, Y', strtotime($order_history->created)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
              </div>
            </div>
            </div>
          </div>
      </section>

<?php

include_once "includes/footer.php";

?>