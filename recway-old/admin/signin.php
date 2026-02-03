<?php

include_once('../includes/functions.php');

if (isset($_SESSION['admin'])) {
    redirect('index.php');
}

login('admin');

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/bootstrap-5/css/bootstrap.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.13.0/css/all.min.css">

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/typograpgy.css">
    <title>Sign-In</title>
</head>
<style>
    .btn-fill {
        background: blue !important;
    }
</style>

<body>
    <div class="container m-0 p-0 w-100 mw-100">
        <div class="row m-0 p-0 w-100 mw-100">
            <div class="col-lg-6 m-0 p-0 mx-auto">
                <div class="container">
                    <div class="row sign-row d-flex justify-content-center align-items-center">
                        <form method="post" class="col-lg-9 sign-form mx-auto">
                            <!--                                <div class="continue-msg">LogIn to continue</div>-->
                            <?php echo isset($message) ? $message : '' ?>
                            <h4 class="">Sign-In</h4>
                            <p class="f-14 w-400 mb-3">Access your portal using yor email address and password.</p>
                            <label>Email</label>
                            <input required type="email" name="email" class=" w-100 mb-3" placeholder="Email Address ">
                            <div class="d-flex align-items-center justify-content-between">
                                <label>Password</label>
                                <!--                                    <a href="" class="f-14">Forgot password?</a>-->
                            </div>
                            <div class="password">
                                <input type="password" name="password" autocomplete="current-password" required="" id="id_password" class="w-100">
                                <i class="far fa-eye" id="togglePassword" style="margin-left: -30px; cursor: pointer;"></i>
                            </div>
                            <a><button type="submit" name="login" class="mt-4 w-600 f-15 w-100 btn-fill" style="margin-left: 0px;">Sign in</button></a>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>


    <script src="../assets/bootstrap-5/js/bootstrap.bundle.min.js"></script>

    <script>
        // Password
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#id_password');

        togglePassword.addEventListener('click', function(e) {
            // toggle the type attribute
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);
            // toggle the eye slash icon
            this.classList.toggle('fa-eye-slash');
        });
    </script>
</body>

</html>