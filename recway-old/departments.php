<?php

$activeLink = 'departments';

include_once "customer/includes/header.php";

$department = findAllByQuery("SELECT * FROM departments WHERE dep_cus_id = {$_SESSION['customer']->id} AND dep_trash = 0 ORDER BY dep_name DESC");

if (isset($_GET['trash'])) {
    $query = 'UPDATE departments SET dep_trash = 1 WHERE dep_id = ?';
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['trash']])) {
        flash("departmentDeleted", "Department has been deleted!");
        redirect('departments.php');
    }
}
?>
<section>
    <div class="container mt-3">
        <?php if (!isset($_GET['trash'])) : ?>
            <?php flash("departmentDeleted"); ?>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-12">
                <?php if (isset($user_allowed_permissions['Create-department']) && !empty($user_allowed_permissions['Create-department'])) {  ?>
                    <div class="d-flex mb-2 justify-content-end">
                        <a href="add-department.php" class="form-btn" style="float:right !important">Add Department</a>
                    </div>
                <?php } ?>
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div>
                        <h2 class="f-18 w-800 text-black">
                            Departments
                        </h2>
                    </div>
                </div>
                <div class="table-div p-2">
                    <table id="myTable" class="display Table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($department)) { ?>
                                <?php foreach ($department as $dep) { ?>
                                    <tr>
                                        <td><?= $dep->dep_name ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <?php if (isset($user_allowed_permissions['Update-department']) && !empty($user_allowed_permissions['Update-department'])) {  ?>
                                                    <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                        <li class="mb-1"><a href="department_edit.php?id=<?= $dep->dep_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                Edit</a>
                                                        </li>

                                                        <li class="mb-1"><a href="departments.php?trash=<?= $dep->dep_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                                Delete</a>
                                                        </li>

                                                    </ul>
                                                <?php } ?>
                                            </div>

                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
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