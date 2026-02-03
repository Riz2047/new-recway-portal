<?php

$activeLink = "staff";

include_once('includes/header.php');

if (isset($_POST['update_staff'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $category = $_POST['category'];

    $query = 'UPDATE staff SET name = ?, email = ?, phone = ?, category = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $phone, $category, $_GET['id']]);
    if (!empty($res)) {
        flash("staffUpdated", "Staff updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("staffUpdated", "Could not update staff!", "errorMsg");
    }
}

$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$staff = $stmt->fetch();
$query = 'SELECT * FROM user_category';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff_category = $stmt->fetchAll();

?>

<?php flash("staffUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Update Staff</h1>
                    </div>

                    <form class="update-form" method="post">
                        <div class="row mb-3">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" required name="name" value="<?php echo $staff->name ?>" class="form-control" id="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" required name="email" value="<?php echo $staff->email ?>" class="form-control" id="email">
                                <input type="hidden" required name="old_email" value="<?php echo $staff->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" required name="phone" value="<?php echo $staff->phone ?>" class="form-control" id="phone">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    <option value="">-Select Category-</option>
                                    <?php if (!empty($staff_category)) { ?>
                                        <?php foreach ($staff_category as $cat) { ?>
                                            <option value="<?= $cat->id ?>" <?php if ($cat->id == $staff->category) { ?>selected<?php } ?>><?= $cat->title ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update_staff" class="btn-primary bg-primary">Update</button>
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