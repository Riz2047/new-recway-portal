<?php

$activeLink = "admins";

include_once('includes/header.php');

//if(isset($_POST['delete'])) {
//    foreach ($_POST['delete'] as $delete) {
//        $query = 'SELECT * FROM admins WHERE id = ?';
//        $stmt = $conn->prepare($query);
//        $stmt->execute([$delete]);
//        $admin = $stmt->fetch();
//
//        $query = 'DELETE FROM admin WHERE id = ?';
//        $stmt = $conn->prepare($query);
//        $stmt->execute([$delete]);
//
//        $query = 'DELETE FROM emails WHERE email = ?';
//        $stmt = $conn->prepare($query);
//        $stmt->execute([$admin->email]);
//    }
//    flash("adminDeleted", "Admin has been deleted!");
//}

$query = 'SELECT * FROM admin WHERE id != ? AND id != ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['admin']->id, 1]);
$admins = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $query = 'DELETE FROM admin WHERE id = ?';
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['delete']])) {
        flash("adminDeleted", "Admin has been deleted!");
        redirect('admins.php');
    }
}
$query = "SELECT * FROM tables_settings WHERE name = 'Admin'";
$stmt = $conn->prepare($query);
$stmt->execute();
$table = $stmt->fetchAll();
$table_columns_data = null;
if (!empty($table[0]->meta_data)) {
    $table_columns_data = json_decode($table[0]->meta_data, true);
}
?>
<?php if (!isset($_GET['delete'])) : ?>
    <?php flash("adminDeleted"); ?>
<?php endif; ?>
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


                                <a href="#" class="white-text mx-3">Admins</a>

                                <div style="display:flex !important">
                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Admin">
                                        <span onclick="location.href='add-admin.php'"><i class="bi bi-person-plus"></i></span>
                                    </button>
                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text" data-toggle="tooltip" data-placement="top" title="Remove Admin">
                                        <span class=""><i class="bi bi-trash"></i></span>
                                    </button>
                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right m-0" onclick="show_card(this)" style="height: 31px;margin-top: 6px !important;">
                                        <i class="bx bxs-chevron-down arrow"></i>
                                    </button>
                                </div>

                            </div>
                            <div class="col-md-12">
                                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">

                                    <div class="card-body" style="display: none !important;">
                                        <div class="row">
                                            <input type="hidden" id="table_id" value="<?= $table[0]->id ?>">
                                            <div class="col-md-3">
                                                <input class="form-check-input" onclick="columns_check(this)" type="checkbox" id="email" name="column[admin][email]" data-id="email_show" value="1" <?php if (isset($table_columns_data['email']) && !empty($table_columns_data['email'])) { ?> checked <?php } ?>>
                                                <label class="form-label form-check-label" for="email">Email</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <table id="dataTable" class="display Table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head">Action</th>
                                        <th class="table-head">Name</th>
                                        <th class="table-head email_show <?php if (!isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>">Email</th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (!empty($admins)) : ?>
                                        <?php foreach ($admins as $key => $admin) : ?>

                                            <tr>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <li class="mb-1"><a href="update-admin.php?id=<?php echo $admin->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                    Edit</a>
                                                            </li>

                                                            <li class="mb-1"><a href="admins.php?delete=<?php echo $admin->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                                    Delete</a>
                                                            </li>

                                                        </ul>
                                                    </div>

                                                </td>
                                                <td class="f-14"><a class="no-decoration text-black" href="update-admin.php?id=<?php echo $admin->id ?>"><?php echo $admin->name ?></a></td>
                                                <td class="f-14 email_show <?php if (!isset($table_columns_data['email']) || empty($table_columns_data['email'])) { ?> custom_hide<?php } ?>"><?php echo $admin->email ?></td>

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