<?php

$activeLink = "staff";

include_once('includes/header.php');

if (!isset($_GET['id'])) {
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
if (!empty($staff_permissions)) {
    $db_permissions = array_column($staff_permissions, 'permission_id');
}

if (isset($_POST['update'])) {
    $allowed_permissions = $_POST['permissions'] ?? "";
    $all_permissions = array_column($permissions, 'id');

    if (!empty($allowed_permissions)) {
        foreach ($all_permissions as $permission) {
            if (in_array($permission, $allowed_permissions)) {
                if (!in_array($permission, $db_permissions)) {
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

    flash("permissionsUpdated", "Permissions updated successfully!");
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
if (!empty($staff_permissions)) {
    $allowed_permissions = array_column($staff_permissions, 'permission_id');
}

?>

<?php flash("permissionsUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Staff Permissions</h1>
                    </div>

                    <form action="" method="post" class="update-form">
                        <div class="row">
                            <label class="form-label">Permissions</label>
                            <?php if (!empty($permissions)) : ?>
                                <?php foreach ($permissions as $permission) : ?>
                                    <div>
                                        <input type="checkbox" class="form-check-input" <?php echo in_array($permission->id, $allowed_permissions) ? 'checked' : '' ?> id="<?php echo $permission->permission ?>" name="permissions[]" value="<?php echo $permission->id ?>">
                                        <label class="form-check-label" for="<?php echo $permission->permission ?>"><?php echo $permission->permission ?></label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>

                            <div class="col-lg-12 mt-2">
                                <button type="submit" name="update" class="btn-primary bg-primary">Update</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<?php

include_once('includes/footer.php');

?>