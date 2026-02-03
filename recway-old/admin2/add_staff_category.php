<?php

$activeLink = "staff";

include_once('includes/header.php');

$query = "SELECT * FROM user_permissions WHERE user_type = 3";
$stmt = $conn->prepare($query);
$stmt->execute();
$permissions = $stmt->fetchAll();

if (isset($_POST['add_category'])) {
    $name = $_POST['name'];
    $per = !empty($_POST['permissions']) ? $_POST['permissions'] : null;
    if (!empty($per)) {
        $per = implode(',', $per);
    }
    $query = 'INSERT INTO user_category (title,permissions_id) VALUES (?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $per]);
    redirect('staff_category.php');
}
?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Add Staff Category</h1>
                    </div>

                    <form class="update-form" method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-lg-12 mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" class="form-control" name="name" required id="name">
                            </div>
                            <div class="col-lg-12">
                                <label class="form-label">
                                    Permissions
                                </label>
                                <div class="row">
                                    <?php if (!empty($permissions)) : ?>
                                        <?php foreach ($permissions as $permission) : ?>
                                            <div class="col-md-3">
                                                <input class="form-check-input" id="<?php echo $permission->title . $permission->id ?>" type="checkbox" name="permissions[]" value="<?php echo $permission->id ?>">
                                                <label class="form-label form-check-label" for="<?php echo $permission->title . $permission->id ?>"><?php echo $permission->title ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="add_category" class="btn-primary bg-primary">Save</button>
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