<?php

$activeLink = 'dashboard';
include_once "includes/header.php";
$query_st = '';
if (isset($_SESSION['department_user']->child_department) && !empty($_SESSION['department_user']->child_department)) {
  $child_departments = explode(',', $_SESSION['department_user']->child_department);
  foreach ($child_departments as $child_department) {
    $query_st .= "OR dep_id = {$child_department} ";
  }
}

$candidates = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['department_user']->dep_cus_id} AND dep_id = {$_SESSION['department_user']->dep_id} {$query_st} AND expired = 0 ORDER BY created DESC");
$candidates2 = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['department_user']->dep_cus_id} AND dep_id = {$_SESSION['department_user']->dep_id} {$query_st} AND expired = 0 ORDER BY created DESC LIMIT 10");
$expired = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['department_user']->dep_cus_id} AND dep_id = {$_SESSION['department_user']->dep_id} AND expired = 1");

?>

<section>
  <div class="container mt-lg-2 mt-0">
    <div class="row">
      <div class="col-lg-6 ">
        <a href="orders.php" class="no-decoration">
          <div class="total-card mb-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="f-16 w-500 mb-0 pb-0">Total Candidates</p>
              </div>
              <div class="icon">
                <i class="bi bi-kanban text-dark"></i>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4">
              <h1 class="f-24 w-600 mb-0 pb-0"><?php echo count($candidates) ?></h1>
            </div>
          </div>
        </a>
      </div>
      <div class="col-lg-6 ">
        <a href="history.php" class="no-decoration">
          <div class="total-card mb-2">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <p class="f-16 w-500 mb-0 pb-0">Total History</p>
              </div>
              <div class="icon">
                <i class="bi bi-hourglass-split text-dark"></i>
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-4">
              <h1 class="f-24 w-600 mb-0 pb-0"><?php echo count($expired) ?></h1>
            </div>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>
<?php include_once "partials/order-buttons.php" ?>
<section>
  <div class="container mt-3">
    <div class="row">
      <div class="col-lg-12">
        <div class="d-flex align-items-center justify-content-between w-100">
          <div>
            <h2 class="f-18 w-800 text-black">
              Recent Orders
            </h2>
          </div>

          <div>
            <a href="" class="no-decoration text-grey f-14 w-500">View All</a>
          </div>
        </div>
        <div class="table-div p-2">
          <table id="myTable" class="display Table">
            <thead>
              <tr>
                <th>Order ID</th>
                <th>Candidate</th>
                <th>Status</th>
                <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                  <th>Background Check</th>
                <?php endif; ?>
                <?php if (!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID) : ?>
                  <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                    <th>Interview Date</th>
                  <?php endif; ?>
                <?php endif; ?>
                <th>Service Type</th>
                <?php if (in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                  <th>Delivery Date</th>
                <?php endif; ?>
                <th>Staff</th>
                <th class="dt-center">Action</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($candidates2)) : ?>
                <?php foreach ($candidates2 as $candidate) : ?>
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
                    <td><a style="text-decoration: none; color: var(--black)" href="invoice.php?id=<?php echo $candidate->id ?>"><?php echo $candidate->name . " " . $candidate->surname ?></a></td>
                    <?php $status = getStatusById($candidate->status) ?>
                    <td class="text-nowrap"><span style="background-color: <?php echo $status->color ?>; padding: 5px; border-radius: 20px; color: white;font-size: 12px"><?php echo $status->status ?></span></td>
                    <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                      <td class="background_check_date"><?php echo !empty($candidate->background_check_date) ? $candidate->background_check_date : 'Null' ?></td>
                    <?php endif; ?>
                    <?php if (!isset($_GET['status']) || getStatusServiceCat($_GET['status'])->service_cat_id == INTERVIEW_ID) : ?>
                      <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                        <td><?php echo !empty($candidate->booked) ? $candidate->booked : "Null" ?></td>
                      <?php endif; ?>
                    <?php endif; ?>
                    <td><?php echo $interview->title ?></td>
                    <?php if (in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
                      <td><?php echo !empty($candidate->delivery_date) ? $candidate->delivery_date : "Null" ?></td>
                    <?php endif; ?>
                    <td><?php echo !empty($staff) ? $staff->name : "Not Assigned" ?></td>
                    <td class="text-center dt-center">
                      <div class="dropdown">
                        <button class="table-menu-btn mx-auto" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="bi bi-three-dots"></i>
                        </button>
                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                          <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="invoice.php?id=<?php echo $candidate->id ?>"><i class="bi bi-eye text-black f-14 me-2"></i>View</a></li>
                          <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="update-candidate.php?id=<?php echo $candidate->id ?>"><i class="bi bi-pen text-black f-14 me-2"></i>
                              Edit</a></li>
                          <li class="" <?php echo $candidate->status == 7 ? 'style="pointer-events: none;"' : '' ?>><a class="no-decoration f-14 w-600 text-black " href="cancel.php?<?php echo isset($_GET['status']) ? 'status=' . $_GET['status'] . '&id=' . $candidate->id : 'id=' . $candidate->id ?>" class="mx-1" <?php echo $candidate->status == 7 ? 'style="color:#bebebe;"' : '' ?>><i class="bi bi-x-circle text-black f-14 me-2"></i>Cancel</a>
                          </li>
                          <!-- <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="add-researcher.php?id=<?php echo $candidate->id ?>"><i class="bi bi-person-check text-black f-16 me-1"></i>
                              Add Researcher</a></li> -->
                        </ul>
                      </div>

                    </td>
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