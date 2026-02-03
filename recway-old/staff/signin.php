<?php

include_once('../includes/functions.php');

if (isset($_SESSION['staff'])) {
    redirect('index.php');
}

loginAdmin2('staff');

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

    <title>Sign in</title>
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
                                <!--                                <div class="continue-msg">Sign in to continue</div>-->
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 39px;"><i class="bi bi-envelope"></i></span>
                                    </div>
                                    <input type="email" name="email" class="mb-3" placeholder="Email Address " style="width: 90%!important;" />
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <!--                                    <a href="" class="f-14">Forgot code</a>-->
                                </div>
                                <div class="password">
                                    <div class="input-group mb-3">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 40px;"><i class="bi bi-key"></i></span>
                                        </div>
                                        <input type="password" name="password" autocomplete="current-password" id="id_password" placeholder="Enter your password" style="width: 90%!important;" />
                                    </div>
                                    <i class="far fa-eye" id="togglePassword" style="margin-left: -30px; cursor: pointer;"></i>
                                </div>
                                <div class="col-md-12">
                                    <input class="form-check-input" id="remember_me" value="" type="checkbox">
                                    <label class="form-check-label" for="remember_me" class="mr-2">Remember Me</label>
                                </div>
                                <div class="col-md-12" style="text-align:right !important">
                                    <button type="submit" name="login" class="mt-3 w-600 f-15 float-right">Sign in</button>
                                </div>
                                <div class="col-md-12" style="text-align:Center !important">
                                    <hr>
                                    <a href="f_p_verify.php">Forget Your Password?</a>
                                </div>
                            </div>
                        </form>
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