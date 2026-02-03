<?php

$activeLink = "settings";

include_once ('includes/header.php');

if(isset($_POST['update_settings'])) {
    unset($_POST['update_settings']);
    foreach ($_POST as $key => $value) {
        $query = "UPDATE settings SET `value` = ? WHERE `name` = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$value, $key]);
        flash("settingsUpdated", "Settings updated successfully!");
    }
}

$query = "SELECT * FROM settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll();

foreach ($settings as $setting) {
    $var = $setting->name;
    $$var = $setting->value;
}

?>

            <?php flash("settingsUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Update Settings</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Home Page Heading</label>
                                                    <input type="text" required name="home_heading" class="form-control" value="<?php echo $home_heading ?>">
                                                </div>

                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Home Page Description</label>
                                                    <textarea name="home_message" required rows="3" class="w-100"><?php echo $home_message ?></textarea>
                                                </div>

                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Email Sender Name</label>
                                                    <input type="text" required name="emailFrom" class="form-control" value="<?php echo $emailFrom ?>">
                                                </div>

                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Admin Registration Message</label>
                                                    <textarea name="admin_reg_msg" required rows="3" class="w-100"><?php echo $admin_reg_msg ?></textarea>
                                                </div>

                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Customer Registration Message</label>
                                                    <textarea name="cus_reg_msg" required rows="3" class="w-100"><?php echo $cus_reg_msg ?></textarea>
                                                </div>

                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Staff Registration Message</label>
                                                    <textarea name="staff_reg_msg" required rows="3" class="w-100"><?php echo $staff_reg_msg ?></textarea>
                                                </div>

                                                <div class="d-flex justify-content-end">
                                                    <button type="submit" name="update_settings" class="btn-primary bg-primary">Update</button>
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

include_once ('includes/footer.php');

?>