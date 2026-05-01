<?php

$activeLink = "statuses";
include_once('includes/header.php');

if (! isset($_GET['id'])) {
    redirect("index.php");
}
if (! isset($allowed_staff_permission['view_status'])) {
    redirect("index.php");
}

if (isset($_GET['delete'])) {
    $col_nam = findByQuery('SELECT msg_col FROM status_services WHERE status_id=' . $_GET['delete']);
    $query = 'DELETE FROM statuses WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
    delete('status_services', 'status_id', $_GET['delete']);
    $query = "ALTER TABLE `messages` DROP " . $col_nam->msg_col;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("statuses.php");
}
$query = "SELECT *, statuses.id AS sID FROM statuses INNER JOIN status_services ss on statuses.id = ss.status_id INNER JOIN service_categories sc ON statuses.status_type = sc.id WHERE sc.id = ? GROUP BY ss.status_id;";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$statuses = $stmt->fetchAll();
// $statuses = getStatusesByService($_GET['id']);

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
                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 mb-4 d-flex justify-content-between align-items-center">


                                <a href="#" class="white-text mx-3">Statuses</a>

                                <div>
                                    <?php if (isset($allowed_staff_permission['create_status']) && ! empty($allowed_staff_permission['create_status'])) { ?>
                                        <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Status">
                                            <span onclick="location.href='add-status.php?serv_cat=<?= $_GET['id'] ?>''"><i class="bi bi-clipboard-plus"></i></span>
                                        </button>
                                    <?php } ?>
                                </div>

                            </div>
                            <table id="dataTable" class="display Table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head">Action</th>
                                        <th class="table-head">Sr#</th>
                                        <th class="table-head">Status</th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (! empty($statuses)) : ?>
                                        <?php foreach ($statuses as $key => $status) : ?>

                                            <tr>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <?php if (isset($allowed_staff_permission['update_status']) && ! empty($allowed_staff_permission['update_status'])) { ?>
                                                                <li class="mb-1"><a href="edit-status.php?id=<?php echo $status->sID ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                        Edit</a>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>

                                                </td>
                                                <td class="f-14"><?php echo $key + 1 ?></td>
                                                <td class="f-14"><?php echo $status->status ?></td>

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
</script>