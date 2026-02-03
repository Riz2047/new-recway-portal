<?php

include_once('../includes/functions.php');

$otp = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_SESSION['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");



if (empty($otp)) {

    redirect("signin.php");

}



if (isset($_POST['otp'])) {

    if ($otp->otp !== $_POST['otp']) {

        $msg = "<p class='text-danger w-500 f-16'>Your OTP is incorrect</p>";

    } else {

        $user = findByQuery("SELECT * FROM staff WHERE email = '{$_SESSION['email']}' LIMIT 1");

        //Storing user in session
        if ($user) {
            update('staff', ['last_login' => date('Y-m-d H:i:s')], 'id', $user->id);
        }

        if ($_SESSION['userTable'] === 'admin') {

            $_SESSION['admin'] = $user;

        } elseif ($_SESSION['userTable'] === 'customers') {

            $_SESSION['customer'] = $user;

        } elseif ($_SESSION['userTable'] === 'staff') {

            $_SESSION['staff'] = $user;

        }



        delete("otp_verification", "id", $otp->id);

        unset($_SESSION['email']);

        unset($_SESSION['userTable']);

        redirect("index.php");

    }

}

if (isset($message) && !empty($message)) {

    if (strpos($message, "Logged")) {

        $message = "Logged in successfully!";

    } else {

        $message = "Email or password is wrong!";

    }

    flash("loginError", $message, "errorMsg");

}



?>



<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="assets/css/tablesBootstrap.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">



    <link rel='stylesheet' href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css'>

    <link href="https://fonts.googleapis.com/css2?family=Comfortaa:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">

    <link rel="stylesheet" href="assets/css/style.css">

    <link rel="stylesheet" href="assets/css/responsive.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">



    <title>Verification</title>

</head>



<body style="background-color: rgba(76, 110, 248, 0.303);background: no-repeat;background-image:url(assets/images/umbrella-4692572_1920.jpg);background-size: cover;">



    <div class="container">

        <?php flash("loginError") ?>

        <div class="row ">

            <div class="col-lg-6 mx-auto">

                <div class="container">

                    <div class="row sign-row d-flex justify-content-center align-items-center">

                        <form class="col-lg-9 sign-form mx-auto" method="post" style="background-color:white !important">

                            <div class="row">

                                <div class="col-md-12">

                                    <img src="assets/images/logo.png" alt="" class="img-fluid">

                                </div>

                            </div>

                            <div class="d-flex justify-content-center align-items-center h-100">

                                <div class="grey-box">

                                    <h1 class="f-30 w-500 text-black">Verify</h1>

                                    <?php echo $msg ?? "" ?>

                                    <p class="f-14 w-500">

                                        We have sent a verification code to your email address

                                    </p>

                                    <form action="" class="form" method="post">

                                        <div class="input-group mb-3">

                                            <div class="input-group-prepend">

                                                <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 39px;"><i class="bi bi-key"></i></span>

                                            </div>

                                            <input type="text" name="otp" class="mb-3" placeholder="Enter Code" style="width: 89%!important;" />

                                        </div>

                                        <div class="mt-3 mb-1 log-btn-div" style="text-align: end;">

                                            <button type="submit" name="login" class="log-btn ">Verify</button>

                                        </div>

                                    </form>

                                </div>

                            </div>

                        </form>

                    </div>

                </div>

            </div>



        </div>

    </div>

    </div>



    <script src="./assets/js/bootstrap.min.js"></script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.6.0/umd/popper.min.js" integrity="sha512-BmM0/BQlqh02wuK5Gz9yrbe7VyIVwOzD1o40yi1IsTjriX/NGF37NyXHfmFzIlMmoSIBXgqDiG1VNU6kB5dBbA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

    <script>

        // Login

        const togglePassword = document.querySelector('#togglePassword');

        const password = document.querySelector('#id_password');



        togglePassword.addEventListener('click', function(e) {

            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';

            password.setAttribute('type', type);

            this.classList.toggle('fa-eye-slash');

        });



        function check_it(obj) {

            if ($(obj).is(':checked')) {

                $(obj).attr('checked', true)

            }

        }

    </script>

</body>



</html>