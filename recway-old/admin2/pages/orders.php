<?php



include_once('../../includes/functions.php');



if (isset($_GET['company'])) {

  $company = trim($_GET['company']);

  $interview_date_from = isset($_GET['startDate']) ? $_GET['startDate'] : '';

  $interview_date_to = isset($_GET['endDate']) ? $_GET['endDate'] : '';



  // Define the base query

$query1 = "SELECT customers.id, customers.name as cus_name, customers.company as cus_company, 
            candidates.id as can_id, candidates.*, 
            places.id as place_id, places.name as place_name,
            staff.id as staff_id, staff.name as staff_name, 
            statuses.id as status_id, statuses.status as status_name, statuses.color, 
            interviews.id as int_id, interviews.title as int_title, interviews.service_cat_id as service_category 
          FROM customers
          LEFT JOIN candidates ON customers.id = candidates.cus_id
          LEFT JOIN places ON candidates.place = places.id
          LEFT JOIN staff ON candidates.staff_id = staff.id
          LEFT JOIN statuses ON candidates.status = statuses.id
          LEFT JOIN interviews ON interviews.id = candidates.interview_id
          WHERE expired = 0 AND invoice_sent = 0";

if (!empty($company)) {
    $query1 .= " AND customers.company = :company";
}
if (!empty($interview_date_from)) {
    $query1 .= " AND DATE(candidates.booked) >= :interview_date_from";
}
if (!empty($interview_date_to)) {
    $query1 .= " AND DATE(candidates.booked) <= :interview_date_to";
}

$stmt1 = $conn->prepare($query1);

if (!empty($company)) {
    $stmt1->bindParam(':company', $company);
}
if (!empty($interview_date_from)) {
    $stmt1->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));
}
if (!empty($interview_date_to)) {
    $stmt1->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));
}

$stmt1->execute();
$candidates1 = $stmt1->fetchAll(PDO::FETCH_OBJ);

$query2 = "SELECT customers.id, customers.name as cus_name, customers.company as cus_company, 
            candidates.id as can_id, candidates.*, 
            places.id as place_id, places.name as place_name,
            staff.id as staff_id, staff.name as staff_name, 
            statuses.id as status_id, statuses.status as status_name, statuses.color, 
            interviews.id as int_id, interviews.title as int_title, interviews.service_cat_id as service_category 
          FROM customers
          LEFT JOIN candidates ON customers.id = candidates.cus_id
          LEFT JOIN places ON candidates.place = places.id
          LEFT JOIN staff ON candidates.staff_id = staff.id
          LEFT JOIN statuses ON candidates.status = statuses.id
          LEFT JOIN interviews ON interviews.id = candidates.interview_id
          WHERE expired = 0 AND invoice_sent = 0";

if (!empty($company)) {
    $query2 .= " AND customers.company = :company";
}
if (!empty($interview_date_from)) {
    $query2 .= " AND DATE(candidates.delivery_date) >= :interview_date_from";
}
if (!empty($interview_date_to)) {
    $query2 .= " AND DATE(candidates.delivery_date) <= :interview_date_to";
}

$stmt2 = $conn->prepare($query2);

if (!empty($company)) {
    $stmt2->bindParam(':company', $company);
}
if (!empty($interview_date_from)) {
    $stmt2->bindValue(':interview_date_from', date('Y-m-d', strtotime($interview_date_from)));
}
if (!empty($interview_date_to)) {
    $stmt2->bindValue(':interview_date_to', date('Y-m-d', strtotime($interview_date_to)));
}

$stmt2->execute();
$candidates2 = $stmt2->fetchAll(PDO::FETCH_OBJ);

$merged = array_merge($candidates1, $candidates2);
$candidates = [];
$orderIds = [];

foreach ($merged as $row) {
    if (!in_array($row->order_id, $orderIds)) {
        $orderIds[] = $row->order_id;
        $candidates[] = $row;
    }
}

}

?>



<style>

  #pdf-table th {

    padding: 0.6rem !important

  }



  #pdf-table td {

    padding: 0.6rem !important

  }



  .his_tooltiptext {

    visibility: hidden;

    background-color: #f9f9f9;

    width: 300px;

    color: #545454;

    text-align: center;

    border-radius: 6px;

    padding: 5px 0;

    white-space: normal;

    max-height: 80%;

    min-height: auto;

    overflow-y: auto;



    /* Position the tooltip text */

    position: fixed;

    z-index: 1;

    top: 10%;

    left: 80%;

    margin-left: 8px;



    /* Fade in tooltip */

    border: 2px solid #b4b4b4;

    box-shadow: -3px 3px 20px 0px rgb(0 0 0 / 24%);

    opacity: 0;

    transition: opacity 0.3s, transform 0.3s;

    transform: translateX(100%);

  }



  td {

    padding: 5px !important

  }

</style>



<div class="container-fluid">

  <div class="row">



    <div class="col-md-9">

    </div>

    <div class="col-md-3">

      <input type="search" id="filterInput4" class="form-control m-2">

    </div>

    <table class="table">

      <thead>

        <tr>

          <th>#</th>

          <th>Order Id</th>

          <th>Vasc Id</th>

          <th>SSN</th>

          <th>Name</th>

          <th>Customer</th>

          <th>Company</th>

          <th>Status</th>

          <th>Invoice Sent</th>

          <th>Interveiw Date / Delivery Date</th>

          <th>Service Type</th>

        </tr>

      </thead>

      <tbody id="filtertab4">

        <?php foreach ($candidates as $i => $candidate) : ?>

          <tr>

            <td><?= $i + 1 ?></td>

            <td class="a"><?= $candidate->order_id ?></td>

            <td><?= ($candidate->vasc_id ? $candidate->vasc_id : 'Null') ?></td>

            <td><?= ($candidate->security ? $candidate->security : 'Null') ?></td>

            <td data-tool-id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseleave="tooltipLeave(this);" onmouseenter="tooltipHover(this)"><?= ($candidate->name ? $candidate->name : '') ?> <?= ($candidate->surname ? $candidate->surname : '') ?></td>

            <td><?= $candidate->cus_name ?></td>

            <td><?= ($candidate->cus_company ? $candidate->cus_company : 'Null') ?></td>

            <td><span class="text-light p-1" style="background:<?= $candidate->color ?>"><?= $candidate->status_name ?></span></td>

            <td class="f-14 invoice_sent_show">

              <input class="form-check-input invoice_sent" onclick="invoice_sent(this)" data-id="<?php echo $candidate->order_id ?>" id="orincoice-<?php echo $candidate->order_id ?>" <?php echo $candidate->invoice_sent == 1 ? 'checked' : '' ?> name="invoice_sent" value="<?php echo $candidate->order_id ?>" type="checkbox">

              <label class="form-check-label" for="orincoice-<?php echo $candidate->order_id ?>" class="mr-2 label-table"></label>

            </td>

            <td>
              <?php if($candidate->service_category == 3){ ?>
                <?= ($candidate->delivery_date ? $candidate->delivery_date : 'Null') ?>
                <?php } else{ ?>
                  <?= ($candidate->booked ? $candidate->booked : 'Null') ?>
              <?php } ?>
            </td>

            <td><?= ($candidate->int_title ? $candidate->int_title : 'Null') ?></td>

          </tr>

        <?php endforeach; ?>

      </tbody>

    </table>

  </div>

</div>



<?php if (!empty($candidates)) : ?>

  <?php foreach ($candidates as $key => $candidate) : ?>

    <?php

    $query = "SELECT * FROM history WHERE order_id = {$candidate->can_id}";

    $stmt = $conn->prepare($query);

    $stmt->execute();

    $history = $stmt->fetchAll();

    ?>

    <span class="his_tooltiptext text-left pl-4 pr-3 pt-2 pb-2" id="his_tooltip_<?php echo $candidate->order_id ?>" onmouseleave="tooltiphide(this)">

      <h5><b><u>Order History</u></b></h5>

      <?php if (!empty($history)) : ?>

        <?php foreach ($history as $h) : ?>

          <div class="mt-3 mb-3">

            <div class="time"><?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?></div>

            <p class="m-0"><?php echo $h->desc ?>

            </p>

            <i><small class="m-0 p-0"><?php echo !empty($h->comment) ? 'Comment: ' . $h->comment : '' ?></small></i>

          </div>

        <?php endforeach; ?>

      <?php endif; ?>

    </span>

  <?php endforeach; ?>

<?php endif; ?>

<script>

    $("#filterInput4").on("keyup", function() {

      var value = $(this).val().toLowerCase();

      $("#filtertab4 tr").filter(function() {

        $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1)

      });

    });

</script>