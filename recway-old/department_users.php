<?php

$activeLink = 'department_users';

include_once "customer/includes/header.php";

$department_users = findAllByQuery("SELECT * FROM departments LEFT JOIN department_users ON departments.dep_id = department_users.dep_id WHERE department_users.dep_user_trash = 0 AND departments.dep_cus_id = {$_SESSION['customer']->id} ORDER BY department_users.dep_id DESC");

if (isset($_GET['trash'])) {
    $query = 'UPDATE department_users SET dep_user_trash = 1 WHERE dep_user_id = ?';
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['trash']])) {
        flash("userDeleted", "User has been deleted!");
        redirect('department_users.php');
    }
}
?>
<section>
    <div class="container mt-3">
        <?php if (!isset($_GET['trash'])) : ?>
            <?php flash("userDeleted"); ?>
        <?php endif; ?>
        <div class="row">
            <div class="col-lg-12">
                <?php if (isset($user_allowed_permissions['Create-department-user']) && !empty($user_allowed_permissions['Create-department-user'])) {  ?>
                    <div class="d-flex mb-2 justify-content-end">
                        <a href="add-department-users.php" class="form-btn" style="float:right !important">Add Department Users</a>
                    </div>
                <?php } ?>
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div>
                        <h2 class="f-18 w-800 text-black">
                            Departments Users
                        </h2>
                    </div>
                </div>
                <div class="table-div p-2">
                    <table id="myTable" class="display Table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Department</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($department_users)) { ?>
                                <?php foreach ($department_users as $dep_user) { ?>
                                    <tr>

                                        <td><?= $dep_user->dep_user_name ?></td>
                                        <td><?= $dep_user->dep_user_email ?></td>
                                        <td><?= $dep_user->dep_name ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <?php if (isset($user_allowed_permissions['Update-department-user']) && !empty($user_allowed_permissions['Update-department-user'])) {  ?>
                                                    <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots"></i>
                                                    </button>
                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                        <li class="mb-1"><a href="department_user_edit.php?id=<?= $dep_user->dep_user_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                Edit</a>
                                                        </li>

                                                        <li class="mb-1"><a href="department_users.php?trash=<?= $dep_user->dep_user_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
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