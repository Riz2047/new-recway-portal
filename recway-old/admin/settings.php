<?php

include_once ('includes/header.php');

if(isset($_POST['update_settings'])) {
    unset($_POST['update_settings']);
    foreach ($_POST as $key => $value) {
        $query = "UPDATE settings SET `value` = ? WHERE `name` = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$value, $key]);
        $message = "<p class='alert alert-success'>Settings updated successfully!</p>";
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


                <div class="row">

                    <div class="col-lg-12">
                        <div class="main-heading  w-100">
                            <h1 class=" mt-3 mb-4">Update Settings</h1>
                        </div>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Primary Color</p>
                                        <input type="color" required name="primaryColor" value="<?php echo $primaryColor ?>" class=" w-100 mb-3">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Secondary Color</p>
                                        <input type="color" required name="secondaryColor" value="<?php echo $secondaryColor ?>" class=" w-100 mb-3">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Home Page Heading</p>
                                        <input type="text" required name="home_heading" class="sign-input w-100 mb-3" value="<?php echo $home_heading ?>">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Home Page Description</p>
                                        <textarea name="home_message" required rows="3" class="sign-textarea w-100 mb-3"><?php echo $home_message ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Email Sender Name</p>
                                        <input type="text" required name="emailFrom" class="sign-input w-100 mb-3" value="<?php echo $emailFrom ?>">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Admin Registration Message</p>
                                        <textarea name="admin_reg_msg" required rows="3" class="sign-textarea w-100 mb-3"><?php echo $admin_reg_msg ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Customer Registration Message</p>
                                        <textarea name="cus_reg_msg" required rows="3" class="sign-textarea w-100 mb-3"><?php echo $cus_reg_msg ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Staff Registration Message</p>
                                        <textarea name="staff_reg_msg" required rows="3" class="sign-textarea w-100 mb-3"><?php echo $staff_reg_msg ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_settings" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>