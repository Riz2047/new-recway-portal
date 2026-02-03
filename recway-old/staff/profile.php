<?php



$activeLink = "profile";



include_once('includes/header.php');



if (isset($_POST['update_profile'])) {

    $name = $_POST['name'];

    $email = $_POST['email'];



    $query = 'UPDATE staff SET name = ?, email = ?';



    if (!empty($_POST['password'])) {

        $query .= ", password = ? WHERE id = ?";



        $crypt_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);



        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$name, $email, $crypt_pass, $_SESSION['staff']->id]);

    } else {

        $query .= " WHERE id = ?";

        $stmt = $conn->prepare($query);

        $res = $stmt->execute([$name, $email, $_SESSION['staff']->id]);

    }



    if (!empty($res)) {

        flash("profileUpdated", "Profile updated successfully!");

    } else {

        flash("profileUpdate", "Could not update profile!", "errorMsg");

    }

}



$query = 'SELECT * FROM staff WHERE id = ? LIMIT 1';

$stmt = $conn->prepare($query);

$stmt->execute([$_SESSION['staff']->id]);

$admin = $stmt->fetch();



if (isset($_POST['resend'])) {

    $email = $_POST['email'];

    $name = $_POST['name'];

    $text = $_POST['text'];

    $subject = $_POST['subject'];



    sendMail($text, $email, $name, $subject);

}



$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";

$stmt = $conn->prepare($query);

$stmt->execute([$admin->email]);

$emails = $stmt->fetchAll();



?>



<?php flash("profileUpdated"); ?>

<div class="mx-lg-4 main-content">

    <div class="container">



        <div class="row ">



            <div class="col-lg-12">

                <div class="table-section">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h1 class="main-heading">Update Profile</h1>

                    </div>



                    <form class="update-form" method="post">

                        <div class="row mb-3">

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="name">Name</label>

                                <input type="text" required name="name" value="<?php echo $admin->name ?>" class="form-control" id="name">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="email">Email</label>

                                <input type="email" required name="email" value="<?php echo $admin->email ?>" class="form-control" id="email">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="password">Password</label>

                                <small>Leave empty if not want to change password</small>

                                <input type="text" name="password" class="form-control" id="password">

                            </div>
                            <div class="col-md-6 mb-3">
                                                    <label class="form-label">Last Login</label>
                                                    <p><b><?php echo $admin->last_login ?></b></p>
                            </div>




                        </div>



                        <div class="d-flex justify-content-end">

                            <button type="submit" name="update_profile" class="btn-primary bg-primary">Update</button>

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