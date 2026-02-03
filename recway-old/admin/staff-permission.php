<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("staff.php");
}

$query = "SELECT * FROM permissions";
$stmt = $conn->prepare($query);
$stmt->execute();
$permissions = $stmt->fetchAll();

$query = "SELECT * FROM staff_permissions WHERE staff_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$staff_permissions = $stmt->fetchAll();

$db_permissions = [];
if(!empty($staff_permissions)) {
    $db_permissions = array_column($staff_permissions, 'permission_id');
}

if(isset($_POST['update'])) {
    $allowed_permissions = $_POST['permissions'] ?? "";
    $all_permissions = array_column($permissions, 'id');

    if(!empty($allowed_permissions)) {
        foreach ($all_permissions as $permission) {
            if(in_array($permission, $allowed_permissions)) {
                if(!in_array($permission, $db_permissions)) {
                    $query = "INSERT INTO staff_permissions (staff_id, permission_id) VALUES (?,?)";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([$_GET['id'], $permission]);
                }
            } else {
                $query = "DELETE FROM staff_permissions WHERE staff_id = ? AND permission_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$_GET['id'], $permission]);
            }
        }
    } else {
        $query = "DELETE FROM staff_permissions WHERE staff_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_GET['id']]);
    }

    $message = "<p class='alert alert-success'>Permissions updated successfully!</p>";
}

$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$staff = $stmt->fetch();

$query = "SELECT * FROM staff_permissions WHERE staff_id = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$staff_permissions = $stmt->fetchAll();

$allowed_permissions = [];
if(!empty($staff_permissions)) {
    $allowed_permissions = array_column($staff_permissions, 'permission_id');
}

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Staff Permissions";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <p class="f-14 mb-0 pb-0 w-500">Permissions</p>
                                    <?php if(!empty($permissions)): ?>
                                    <?php foreach ($permissions as $permission): ?>
                                        <div>
                                            <input type="checkbox" <?php echo in_array($permission->id, $allowed_permissions) ? 'checked' : '' ?> id="<?php echo $permission->permission ?>" name="permissions[]" value="<?php echo $permission->id ?>">
                                            <label for="<?php echo $permission->permission ?>"><?php echo $permission->permission ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>