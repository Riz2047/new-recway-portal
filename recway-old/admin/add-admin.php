<?php

include_once ('includes/header.php');

$query = "SELECT * FROM settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll();

foreach ($settings as $setting) {
    $var = $setting->name;
    $$var = $setting->value;
}

if(isset($_POST['add_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];

    $crypt_pass = password_hash($password, PASSWORD_BCRYPT);

    $query = 'INSERT INTO admin (name,email,password) VALUES (?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $crypt_pass]);
    if(!empty($res)) {

        $body = replace($admin_reg_msg,'', '', '', '', $name, $email, $password, '', '');

        $subject = "Registration";

        saveEmail("Admin", $name, "N/A", 'Admin Registration Message', $body, $email, $subject);
        sendMail($body, $email, $name, $subject);

        $message = "<p class='alert alert-success'>Admin added successfully!</p>";
        // redirect('admins.php');
    } else {
        $message = "<p class='alert alert-danger'>Could not add admin!</p>";
    }
}

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Add Admin";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Password</p>
                                        <input type="text" value="<?php echo rand_string(7) ?>" required name="password" class="sign-input w-100 mb-3" placeholder="Password ">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="add_admin" class="btn-fill w-100 mt-4"><a>Save</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>