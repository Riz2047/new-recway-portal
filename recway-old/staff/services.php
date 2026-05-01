<?php

$activeLink = "services";

include_once('includes/header.php');

if (isset($_GET['delete'])) {
    $query = 'DELETE FROM service_categories WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("services.php");
}
if (! isset($allowed_staff_permission['view_service'])) {
    redirect("index.php");
}
$query = 'SELECT * FROM service_categories';
$query .= "  ORDER BY name ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

?>
<style>
    .table-container {
        overflow-y: auto;
    }
</style>
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
                                <a href="#" class="white-text mx-3">Services</a>
                                <div>
                                    <?php if (isset($allowed_staff_permission['create_service']) && ! empty($allowed_staff_permission['create_service'])) { ?>
                                        <button type="button" onclick="show_add_card(this)" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                            <span><i class="bi bi-file-plus"></i></span>
                                        </button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-md-12" id="show_add_card" style="display: none !important;">
                                <div class="card" style="width: 98% !important;margin-left: 11px !important">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Add Service</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="name">Name</label>
                                                <input type="text" class="form-control" id="name">
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" onclick="add_service()" class="btn-primary bg-primary">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12" id="update_service_card" name="update_section" style="display: none !important;">
                                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Update Service</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="name">Name</label>
                                                <input type="text" class="form-control" id="main_u_name">
                                                <input type="hidden" id="main_u_id" value="<?php echo $service->id ?>">
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" onclick="update_s(this)" class="btn-warning bg-warning mr-2" style="border-radius: 9px !important;">Close</button>
                                                <button type="button" onclick="update_service()" class="btn-primary bg-primary">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <table id="dataTable" class="display Table mt-3" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head">Action</th>
                                        <th class="table-head">Sr#</th>
                                        <th class="table-head">Service</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (! empty($services)) : ?>
                                        <?php foreach ($services as $key => $service) : ?>

                                            <tr>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <input type="hidden" class="u_id" value="<?php echo $service->id ?>">
                                                            <input type="hidden" class="u_name" value="<?php echo $service->name ?>">
                                                            <?php if (isset($allowed_staff_permission['update_service']) && ! empty($allowed_staff_permission['update_service'])) { ?>
                                                                <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                        Edit</a>
                                                                </li>
                                                            <?php } ?>
                                                        </ul>
                                                    </div>
                                                </td>
                                                <td class="f-14"><?php echo $key + 1 ?></td>
                                                <td class="f-14"><a class="no-decoration text-black name_text" href="interviews.php?id=<?php echo $service->id ?>"><?php echo $service->name ?></a></td>
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