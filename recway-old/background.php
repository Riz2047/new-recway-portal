<?php

$activeLink = "all-orders";

include_once "customer/includes/header.php";

if(isset($_GET['status'])) {
    $query = "SELECT * FROM candidates WHERE cus_id = ? AND status = {$_GET['status']} AND expired = 0";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['customer']->id]);
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates WHERE cus_id = ? AND expired = 0';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['customer']->id]);
    $candidates = $stmt->fetchAll();
}

?>

        <?php include_once "customer/partials/order-buttons.php" ?>
      <section>
        <div class="container mt-3">
          <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">List of Background Check Orders</p>
            <div class="col-lg-12">
              <div class="table-div p-2">
                <table id="myTable2" class="display Table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Candidate</th>
                        <th>Status</th>
                        <?php if(!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID): ?>
                            <?php if(in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))): ?>
                                <th>Interview Date</th>
                            <?php endif; ?>
                        <?php endif; ?>
                        <th>Service Type</th>
                        <?php if(in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['customer']->id))): ?>
                            <th>Delivery Date</th>
                        <?php endif; ?>
                        <th>Staff</th>
                        <th class="dt-center">Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($candidates)): ?>
                        <?php foreach ($candidates as $candidate): ?>
                            <?php if(getStatusServiceCat($candidate->status)->service_cat_id == BACKGROUND_ID): ?>
                                <?php
                                $query = 'SELECT * FROM interviews WHERE id = ?';
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$candidate->interview_id]);
                                $interview = $stmt->fetch();

                                $query = 'SELECT * FROM staff WHERE id = ?';
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$candidate->staff_id]);
                                $staff = $stmt->fetch();
                                ?>
                                <tr>
                                    <td><?php echo $candidate->order_id ?></td>
                                    <td><a style="text-decoration: none; color: var(--black)" href="invoice.php?id=<?php echo $candidate->id ?>"><?php echo $candidate->name." ".$candidate->surname ?></a></td>
                                    <?php $status = getStatusById($candidate->status) ?>
                                    <td class="text-nowrap"><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                                    <?php if(!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID): ?>
                                        <?php if(in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['customer']->id))): ?>
                                            <td><?php echo !empty($candidate->booked) ? $candidate->booked : "Null" ?></td>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <td><?php echo $interview->title ?></td>
                                    <?php if(in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['customer']->id))): ?>
                                        <td><?php echo !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null" ?></td>
                                    <?php endif; ?>
                                    <td><?php echo !empty($staff) ? $staff->name : "Not Assigned" ?></td>
                                    <td class="text-center dt-center">
                                        <div class="dropdown">
                                            <button class="table-menu-btn mx-auto" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="bi bi-three-dots"></i>
                                            </button>
                                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="invoice.php?id=<?php echo $candidate->id ?>"><i
                                                                class="bi bi-eye text-black f-14 me-2"></i>View</a></li>
                                                <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="update-candidate.php?id=<?php echo $candidate->id ?>"><i
                                                                class="bi bi-pen text-black f-14 me-2"></i>
                                                        Edit</a></li>
                                                <li class="" <?php echo $candidate->status == 7 ? 'style="pointer-events: none;"' : '' ?>><a class="no-decoration f-14 w-600 text-black "
                                                                                                                                             href="cancel.php?<?php echo isset($_GET['status']) ? 'status=' . $_GET['status'] . '&id=' . $candidate->id : 'id=' . $candidate->id ?>"
                                                                                                                                             class="mx-1" <?php echo $candidate->status == 7 ? 'style="color:#bebebe;"' : '' ?>><i
                                                                class="bi bi-x-circle text-black f-14 me-2"></i>Cancel</a>
                                                </li>
                                            </ul>
                                        </div>

                                    </td>
                                </tr>
                            <?php endif; ?>
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

include_once "customer/includes/footer.php";

?>