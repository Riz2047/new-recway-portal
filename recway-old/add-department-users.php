<?php

$activeLink = "department_users";

include_once('customer/includes/header.php');

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $dep = $_POST['department'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $per = $_POST['permissions'] ?? array();
    if (!empty($name) && !empty($dep) && !empty($email) && !empty($password)) {

        $user = findByQuery("SELECT * FROM department_users WHERE dep_user_email = '{$email}'");
        $department = findByQuery("SELECT * FROM departments WHERE dep_id = '{$dep}'");
        if (!empty($department->dep_name)) {
            $department = $department->dep_name;
        } else {
            $department = '';
        }
        if (!empty($user)) {
            $msg = "<p class='text-danger'>This email already exists!</p>";
        } else {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            insert("department_users", ["dep_user_name" => $name, "dep_user_email" => $email, "dep_user_password" => $hashedPassword, 'dep_id' => $dep]);
            $user = findByQuery("SELECT * FROM department_users WHERE dep_user_email = '{$email}'");

            if (!empty($user)) {
                if (!empty($per)) {
                    foreach ($per as $pers) {
                        $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$pers, $user->dep_user_id, 1]);
                    }
                }
            }

            $body = "<p>You are added as a User of {$department} Department by {$_SESSION['customer']->name}. Please use following credentials to login.</p>";
            $body .= "<br>";
            $body .= "<strong>Email: {$email}</strong>";
            $body .= "<br>";
            $body .= "<strong>Password: {$password}</strong>";
            $body .= "<br><br>";
            $body .= "Click on the following link to access the portal";
            $body .= "<br><br>";
            $body .= "<a href='https://orderspi.se/department_user'>https://orderspi.se/department_user</a>";
            sendMail($body, $email, "User", "User added");

            $msg = "<p class='text-success'>User added successfully!</p>";
        }
    } else {
        $msg = "<p class='text-danger'>User is not created due to insufficient information!</p>";
    }
}
$departments = findallByQuery("SELECT * FROM departments WHERE dep_cus_id = {$_SESSION['customer']->id} AND dep_trash = 0");
$permissions = findallByQuery("SELECT * FROM user_permissions");

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Add Department User</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form">
                    <?php echo isset($msg) ? $msg : '' ?>
                    <div class="form-tag mb-2">Department User</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Name</label>
                        <input type="text" name="name" required placeholder="Enter name" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-person"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Email</label>
                        <input type="email" name="email" required placeholder="Enter email" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-envelope-at"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Password</label>
                        <input type="text" name="password" required value="<?php echo rand_string(7) ?>" placeholder="Enter password" class="w-100 from-input">
                        <div class="form-icon me-2">
                            <i class="bi bi-key"></i>
                        </div>
                    </div>
                    <div class="d-flex align-items-center form-row mb-3" style="border-right:0px !important">
                        <label for="" class="label-lg" style="border-right:0px !important">Department</label>
                        <select name="department" class="form-select">
                            <?php if (!empty($departments)) { ?>
                                <?php foreach ($departments as $k => $depart) { ?>
                                    <option value="<?= $departments[$k]->dep_id ?>"><?= $departments[$k]->dep_name ?></option>
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
                                    <input class="form-check-input" id="<?php echo $permission->title ?>" type="checkbox" name="permissions[]" value="<?php echo $permission->id ?>">
                                    <label class="form-label form-check-label" for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="add" class="form-btn border-0">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>


<?php

include_once('customer/includes/footer.php');

?>