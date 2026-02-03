<?php



$activeLink = "statuses";



include_once('includes/header.php');



if (!isset($_GET['id'])) {

    redirect("index.php");

}



if (isset($_GET['delete'])) {

    $col_nam = findByQuery('SELECT msg_col FROM status_services WHERE status_id=' . $_GET['delete']);

    $query = 'DELETE FROM statuses WHERE id=' . $_GET['delete'];

    $stmt = $conn->prepare($query);

    $stmt->execute();

    delete('status_services', 'status_id', $_GET['delete']);

    // $query = "ALTER TABLE `messages` DROP " . $col_nam->msg_col;

    // $stmt = $conn->prepare($query);

    // $stmt->execute();

    redirect("statuses.php");

}



$query = "SELECT *, statuses.id AS sID FROM statuses INNER JOIN status_services ss on statuses.id = ss.status_id INNER JOIN service_categories sc ON statuses.status_type = sc.id WHERE sc.id = ? GROUP BY ss.status_id;";

$stmt = $conn->prepare($query);

$stmt->execute([$_GET['id']]);

$statuses = $stmt->fetchAll();

// $statuses = getStatusesByService($_GET['id']);





$query = "SELECT * FROM service_categories WHERE id != ?";

$stmt = $conn->prepare($query);

$stmt->execute([$_GET['id']]);

$services = $stmt->fetchAll();



if (isset($_POST['copy_status'])) {

    $status = $_POST['status'];

    $service_cat = $_POST['service_cat'];

    $id = $_GET['id'];



    if (!empty($id) && !empty($service_cat) && !empty($status)) {

        // 1. Copy the status

        $statusToCopy = findByQuery("SELECT * FROM statuses WHERE id =" . $status);

        $statusData = array(

            'variable' => $statusToCopy->variable,

            'status' => $statusToCopy->status,

            'status_detail' => $statusToCopy->status_detail,

            'status_icon' => $statusToCopy->status_icon,

            'color' => $statusToCopy->color,

            'status_type' => $id, // Using target service ID
                        'status_sv' => $statusToCopy->status_sv, 

        );

        $lid = insert('statuses', $statusData);



        // 2. Create status_services entries

        $interviews = findAllByQuery("SELECT * FROM interviews WHERE service_cat_id =" . $id);

        $mag_col = findByQuery("SELECT * FROM status_services WHERE status_id = " . $status);



        if (!empty($lid)) {

            if (!empty($interviews)) {

                foreach ($interviews as $int) {

                    $dss = array(

                        'status_id' => $lid, // Using new status ID

                        'service_id' => $int->id,

                        'msg_col' => $mag_col->msg_col,

                    );

                    insert('status_services', $dss);

                }

            }

        }



        // 3. Update customer statuses

        $serviceIds = array_column($interviews, 'id');

        if (!empty($serviceIds)) {

            $placeholders = implode(',', array_fill(0, count($serviceIds), '?'));

            $query = "SELECT c.id, c.name, c.statuses 

                     FROM customers c

                     JOIN customer_services cs ON c.id = cs.cus_id

                     WHERE cs.service_id IN ($placeholders)";



            $stmt = $conn->prepare($query);

            foreach ($serviceIds as $k => $sid) {

                $stmt->bindValue(($k + 1), $sid, PDO::PARAM_INT);

            }

            $stmt->execute();

            $customers = $stmt->fetchAll(PDO::FETCH_OBJ);



            $insertQuery = "INSERT INTO allowed_emails (cus_id, status_id, allowed) VALUES (:cus_id, :status_id, 1)";

            $stmt = $conn->prepare($insertQuery);

            foreach ($customers as $customer) {



                $stmt->bindValue(':cus_id', $customer->id);

                $stmt->bindValue(':status_id', $lid);

                $stmt->execute();

                

                $currentStatuses = explode(',', $customer->statuses);

                if (!in_array($lid, $currentStatuses)) {

                    $currentStatuses[] = $lid;

                    $updatedStatuses = implode(',', $currentStatuses);



                    $updateQuery = "UPDATE customers SET statuses = ? WHERE id = ?";

                    $updateStmt = $conn->prepare($updateQuery);

                    $updateStmt->execute([$updatedStatuses, $customer->id]);

                }



            }

        }



        // 4. Update messages

        if (!empty($interviews)) {

            $interviewIds = array_column($interviews, 'id');

            $placeholders = implode(',', array_fill(0, count($interviewIds), '?'));



            // Get the message template

            $msgColumn = $mag_col->msg_col;

            $noansMsgValue = findByQuery("SELECT `{$msgColumn}` FROM messages WHERE cus_id = 197 AND `{$msgColumn}` != '' LIMIT 1");



            if (empty($noansMsgValue->$msgColumn)) {

                $noansMsgValue = findByQuery("SELECT `{$msgColumn}` FROM messages WHERE `{$msgColumn}` != '' ORDER BY id DESC LIMIT 1");

            }



            if (!empty($noansMsgValue->$msgColumn)) {

                $query = "UPDATE messages 

                 SET `{$msgColumn}` = ?

                 WHERE interview_id IN ($placeholders)";



                $stmt = $conn->prepare($query);

                $stmt->bindValue(1, $noansMsgValue->$msgColumn, PDO::PARAM_STR);



                foreach ($interviewIds as $k => $interviewId) {

                    $stmt->bindValue(($k + 2), $interviewId, PDO::PARAM_INT);

                }



                $stmt->execute();

            } else {

                // Handle case where no message template was found

                error_log("No message template found for column: {$msgColumn}");

            }

        }

    }

}



?>



<div class="mx-lg-4 main-content">

    <div class="container">

        <?php include_once "buttons-row.php" ?>



        <!-- table row -->

        <div class="row">

            <div class="col-lg-12">

                <div class="table-div">

                    <form action="" method="post" id="d-form">

                        <div class="card card-cascade narrower mb-4">



                            <!--Card image-->

                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">





                                <a href="#" class="white-text mx-3">Statuses</a>



                                <div>

                                                                        <button type="button" onclick="show_add_card(this)"

                                        class="btn btn-outline-white btn-rounded btn-sm px-2">

                                        <i class="fa-solid fas fa-copy"></i>

                                    </button>

                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Status">

                                        <span onclick="location.href='add-status.php?serv_cat=<?= $_GET['id'] ?>'"><i class="bi bi-clipboard-plus"></i></span>

                                    </button>

                                </div>



                            </div>

                                                        <div class="col-md-12" id="show_add_card" style="display: none !important;">

                                <div class="card" style="width: 98% !important;margin-left: 11px !important">

                                    <div class="card-header">

                                        <div class="card-title">

                                            <h5>Copy Statuses</h5>

                                        </div>

                                    </div>

                                    <div class="card-body">

                                        <form action="" method="post">

                                            <div class="row">

                                                <div class="col-lg-6 mb-3">

                                                    <label class="form-label">From Service</label>

                                                    <select name="service_cat" class="form-control"

                                                        onchange="fetch_statuses(this)">

                                                        <option value="">Select Service</option>

                                                        <?php if (!empty($services)) { ?>

                                                            <?php foreach ($services as $service) { ?>

                                                                <option value="<?= $service->id ?>"><?= $service->name ?>

                                                                </option>

                                                            <?php } ?>

                                                        <?php } ?>

                                                    </select>

                                                </div>

                                                <div class="col-lg-6 mb-3">

                                                    <label class="form-label">Status</label>

                                                    <select name="status" class="form-control">

                                                        <option value="">Select Status</option>

                                                    </select>

                                                </div>

                                                <div class="d-flex justify-content-end">

                                                    <button type="submit" name="copy_status"

                                                        class="btn-primary bg-primary">Copy</button>

                                                </div>

                                            </div>

                                        </form>

                                    </div>

                                </div>

                            </div>

                            <table id="dataTable" class="display Table" style="width: 100%">

                                <thead>

                                    <tr>

                                        <th class="dt-center table-head">Action</th>

                                        <th class="table-head">Sr#</th>

                                        <th class="table-head">Status</th>
<th class="table-head">Status (Swedish)</th>


                                    </tr>

                                </thead>

                                <tbody>



                                    <?php if (!empty($statuses)) : ?>

                                        <?php foreach ($statuses as $key => $status) : ?>



                                            <tr>

                                                <td>

                                                    <div class="dropdown">

                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">

                                                            <i class="bi bi-gear"></i>

                                                        </button>

                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                                            <li class="mb-1"><a href="edit-status.php?id=<?php echo $status->sID ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                                                    Edit</a>

                                                            <li class="mb-1 delete-status"><a href="?id=<?php echo $_GET['id'] ?>&delete=<?php echo $status->sID ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                                                    Delete</a>

                                                            </li>



                                                        </ul>

                                                    </div>



                                                </td>

                                                <td class="f-14"><?php echo $key + 1 ?></td>

                                                <td class="f-14"><?php echo $status->status ?></td>
<td class="f-14"><?php echo $status->status_sv ?? '-' ?></td>


                                            </tr>



                                        <?php endforeach; ?>

                                    <?php endif; ?>



                                </tbody>

                            </table>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

    var statuses = <?php echo json_encode($statuses) ?>

</script>

<?php



include_once('includes/footer.php');



?>

<script>

    $('#customer').on('change', function() {

        location.href = location.pathname + "?id=" + $(this).val();

    })

        function fetch_statuses(obj) {

        var id = $(obj).val();

        $.ajax({

            type: "POST",

            url: "../includes/pages.php",

            data: {

                'fetch_statuses': 1,

                'id': id,

            },

            dataType: 'json',

            success: function (response) {

                console.log(response);



                var $statusSelect = $('select[name="status"]');



                $statusSelect.find('option:not(:first)').remove();



                if (response.success && response.service_categories && response.service_categories.length > 0) {

                    $.each(response.service_categories, function (index, category) {

                        $statusSelect.append(

                            $('<option></option>')

                                .val(category.id)

                                .text(category.status)

                                .data('status-detail', category.status_detail)

                                .data('status-icon', category.status_icon)

                        );

                    });



                }

            },

            error: function (xhr, status, error) {

                console.error("Error fetching statuses:", error);

                alert("Failed to load statuses. Please try again.");

            }

        });

    }

</script>