<?php



$activeLink = "admins";



include_once('includes/header.php');



$query = "SELECT * FROM settings";

$stmt = $conn->prepare($query);

$stmt->execute();

$settings = $stmt->fetchAll();



foreach ($settings as $setting) {

    $var = $setting->name;

    $var = $setting->value;

}



if (isset($_POST['add_admin'])) {

    $name = $_POST['name'];

    $email = $_POST['email'];

    $password = $_POST['password'];



    $crypt_pass = password_hash($password, PASSWORD_BCRYPT);



    $query = 'INSERT INTO admin (name,email,password) VALUES (?,?,?)';

    $stmt = $conn->prepare($query);

    $res = $stmt->execute([$name, $email, $crypt_pass]);

    if (!empty($res)) {

        // Create a DateTime object for Sweden's timezone

        $swedenTimezone = new DateTimeZone('Europe/Stockholm');

        $swedenTime = new DateTime('now', $swedenTimezone);

        $currentTime = $swedenTime->format('H:i:s');

        $dayOfWeek = date('N');



        //matching time between 8am to 5pm

        
                    $body = replace($admin_reg_msg, '', '', '', '', $name, $email, $password, '', '');
        
        
        
                    $subject = "Registration";

        if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {



            saveEmail("Admin", $name, "N/A", 'Admin Registration Message', $body, $email, $subject);

            sendMail($body, $email, $name, $subject);



            flash("adminAdded", "Admin added successfully!");

            // redirect('admins.php');

        } else {

            saveEmail("Admin", $name, "N/A", 'Admin Registration Message', $body, $email, $subject, '1');
flash("adminAdded", "Admin added successfully!");
        }

    } else {

        flash("adminAdded", "Could not add admin!", "errorMsg");

    }

}



?>



<?php flash("adminAdded"); ?>

<div class="mx-lg-4 main-content">

    <div class="container">



        <div class="row ">



            <div class="col-lg-12">

                <div class="table-section">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h1 class="main-heading">Add Admin</h1>

                    </div>



                    <form class="update-form" method="post">

                        <div class="row mb-3">

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="name">Name</label>

                                <input type="text" required name="name" class="form-control" id="name">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="email">Email</label>

                                <input type="email" required name="email" class="form-control" id="email">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="password">Password</label>

                                <input type="text" value="<?php echo rand_string(7) ?>" required name="password" class="form-control" id="password">

                            </div>





                        </div>



                        <div class="d-flex justify-content-end">

                            <button type="submit" name="add_admin" class="btn-primary bg-primary">Save</button>

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