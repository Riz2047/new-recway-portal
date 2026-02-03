<?php

$activeLink = "staff";

include_once('includes/header.php');

$query = 'SELECT * FROM user_permissions WHERE user_type = 3';
$stmt = $conn->prepare($query);
$stmt->execute();
$permissions = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $query = 'DELETE FROM user_permissions WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
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


                                <a href="#" class="white-text mx-3">Staff Permissions</a>

                                <div>
                                    <button type="button" onclick="show_add_card(this)" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                        <span><i class="bi bi-plus"></i></span>
                                    </button>
                                </div>

                            </div>
                            <div class="col-md-12" id="show_add_card" style="display: none !important;">
                                <div class="card" style="width: 98% !important;margin-left: 11px !important">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Add Permission</h5>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-12 mb-3">
                                                <label class="form-label" for="name">Title</label>
                                                <input type="text" class="form-control" id="name" placeholder="XXXX_XXXX Use '_' in title">
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" onclick="add_permission()" class="btn-primary bg-primary">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12" id="update_service_card" name="update_section" style="display: none !important;">
                                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">
                                    <div class="card-header">
                                        <div class="card-title">
                                            <h5>Update Permission</h5>
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
                                                <button type="button" onclick="update_permission()" class="btn-primary bg-primary">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <table id="dataTable" class="display Table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head">Action</th>
                                        <th class="table-head">Title</th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (!empty($permissions)) : ?>
                                        <?php foreach ($permissions as $key => $permission) : ?>

                                            <tr>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <input type="hidden" class="u_id" value="<?php echo $permission->id ?>">
                                                            <input type="hidden" class="u_name" value="<?php echo $permission->title ?>">
                                                            <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                    Edit</a>
                                                            <li class="mb-1"><a href="?delete=<?php echo $permission->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                                    Delete</a>
                                                            </li>

                                                        </ul>
                                                    </div>

                                                </td>
                                                <td class="f-14 name_text"><?php echo $permission->title ?></td>

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
<?php

include_once('includes/footer.php');

?>