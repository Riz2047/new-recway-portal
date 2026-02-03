<?php

$activeLink = "department_users";

include_once "customer/includes/header.php";

if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $department = $_POST['department'];
    $permissions = $_POST['permissions'] ?? array();
    if (update('department_users', ['dep_user_name' => $name, 'dep_user_email' => $email, 'dep_id' => $department], 'dep_user_id', $_GET['id'])) {
        delete('user_allowed_permissions', 'user_id', $_GET['id']);
        if (!empty($permissions)) {
            foreach ($permissions as $pers) {
                $query = 'INSERT INTO user_allowed_permissions (per_id, user_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$pers, $_GET['id']]);
            }
        }
        $message = "<p class='text-success'>User updated successfully!</p>";
    } else {
        $message = "<p class='text-success'>User is not updated!</p>";
    }
}
if (isset($_GET['id'])) {
    $user = findByQuery("SELECT * FROM department_users WHERE dep_user_id = '{$_GET['id']}'");
} else {
    redirect('department_users.php');
}
$departments = findallByQuery("SELECT * FROM departments WHERE dep_cus_id = {$_SESSION['customer']->id} AND dep_trash = 0");
$permissions = findallByQuery("SELECT * FROM user_permissions WHERE user_type = 1");
$allow_permissions = findallByQuery("SELECT * FROM user_allowed_permissions WHERE user_id = {$_GET['id']}");
?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Update User</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form" enctype="multipart/form-data">
                    <?php echo isset($message) ? $message : '' ?>
                    <div class="form-tag mb-2">Profile Info</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Name</label>
                        <input type="text" name="name" required placeholder="Enter name" class="w-100 from-input" <?php if (!empty($user->dep_user_name)) { ?>value="<?= $user->dep_user_name ?>" <?php } ?>>
                        <div class="form-icon me-2">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Email</label>
                        <input type="email" name="email" required placeholder="Enter email" class="w-100 from-input" <?php if (!empty($user->dep_user_email)) { ?>value="<?= $user->dep_user_email ?>" <?php } ?>>
                        <div class="form-icon me-2">
                            <i class="bi bi-envelope-at"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3" style="border-right:0px !important">
                        <label for="" class="label-lg" style="border-right:0px !important">Department</label>
                        <select name="department" class="form-select">
                            <?php if (!empty($departments)) { ?>
                                <?php foreach ($departments as $k => $depart) { ?>
                                    <option value="<?= $departments[$k]->dep_id ?>" <?php if ($user->dep_id == $departments[$k]->dep_id) { ?>selected <?php } ?>><?= $departments[$k]->dep_name ?></option>
                                <?php } ?>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-lg-12">
                        <label class="form-label">
                            <h4>User Permissions</h4>
                        </label>
                        <?php if (!empty($permissions)) : ?>
                            <?php foreach ($permissions as $permission) : ?>
                                <div>
                                    <input class="form-check-input" id="<?php echo $permission->title ?>" <?php if (!empty($allow_permissions)) { ?> <?php foreach ($allow_permissions as $allow) { ?> <?php if ($allow->per_id == $permission->id) { ?> checked <?php } ?> <?php } ?> <?php } ?> type="checkbox" name="permissions[]" value="<?php echo $permission->id ?>">
                                    <label class="form-label form-check-label" for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="update" class="form-btn border-0">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>