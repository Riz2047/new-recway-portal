<?php

$activeLink = 'dashboard';

include_once "includes/header.php";

$candidates = findAllByQuery("SELECT * FROM candidates WHERE cus_id = {$_SESSION['reviewer']->cus_id} AND expired = 0 ORDER BY created DESC");

?>

      <section>
        <div class="container mt-3">
          <div class="row">
            <div class="col-lg-12">
              <div class="d-flex align-items-center justify-content-between w-100">
                <div>
                  <h2 class="f-18 w-800 text-black">
                    Candidates
                  </h2>
                </div>
              </div>
              <div class="table-div p-2">
                <table id="myTable" class="display Table">
                    <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Candidate</th>
                        <th>Report</th>
                        <th>Current Status</th>
                        <th class="dt-center">Change Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if(!empty($candidates)): ?>
                        <?php foreach ($candidates as $candidate):
                            if(getStatusServiceCat($candidate->status)->service_cat_id != BACKGROUND_ID) {
                                continue;
                            }
                            ?>
                        <?php $statusColors = ["#000000", "#3CB371", "#FFA500", "#FF0000"]; ?>
                        <?php $reportStatus = ["N/A", "Approved", "Deviation", "Denied"]; ?>
                            <tr>
                                <td><?php echo $candidate->order_id ?></td>
                                <td><?php echo $candidate->name." ".$candidate->surname ?></td>
                                <td><?php echo !empty($candidate->report) ? '<a class="text-dark" href="../uploads/'. $candidate->report .'">View</a>' : "N/A" ?></td>
                                <td style="color: <?php echo $statusColors[$candidate->report_status] ?>" class="current-status"><?php echo $reportStatus[$candidate->report_status] ?></td>
                                <td class="text-center dt-center">
                                    <div class="dropdown">
                                        <button class="table-menu-btn mx-auto" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                            <li class="mb-1 change-status" data-report-status="1" data-order-id="<?php echo $candidate->id ?>" style="cursor: pointer"><a style="color: #3CB371" class="no-decoration f-14 w-600 text-black ">Approved</a></li>
                                            <li class="mb-1 change-status" data-report-status="2" data-order-id="<?php echo $candidate->id ?>" style="cursor: pointer"><a style="color: #FFA500" class="no-decoration f-14 w-600 text-black ">Deviation</a></li>
                                            <li class="mb-1 change-status" data-report-status="3" data-order-id="<?php echo $candidate->id ?>" style="cursor: pointer"><a style="color: #FF0000" class="no-decoration f-14 w-600 text-black ">Denied</a></li>
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

<script>
    $(".change-status").click(function () {
        const reportStatus = $(this).data("report-status");
        const orderID = $(this).data("order-id");

        const statusText = $(this).find("a").text();
        const that = $(this)

        var statusColorsHex = {"Approved": "#3CB371", "Deviation": "#FFA500", "Denied": "#FF0000"};

        $.ajax({
            url: "../includes/ajax.php",
            method: "post",
            data: {reportStatus, orderID},
            success: function (response) {
                that.closest("tr").find(".current-status").text(statusText);
                that.closest("tr").find(".current-status").css("color", statusColorsHex[statusText]);
            }
        })
    })
</script>
