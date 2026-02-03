<?php

include_once('../includes/functions.php');

if (!isset($_SESSION['password_forgot']) && empty($_SESSION['password_forgot'])) {
    redirect('index.php');
}

if (isset($_POST['reset_password'])) {
    if (!empty($_POST['new_password'])  && !empty($_POST['confirm_password']) && $_POST['new_password'] == $_POST['confirm_password']) {
        $user = findByQuery("SELECT * FROM staff WHERE email = '{$_SESSION['forgot_p_email']}' LIMIT 1");
        update("staff", ["password" => password_hash($_POST['confirm_password'], PASSWORD_BCRYPT)], "email", $_SESSION['forgot_p_email']);
        $_SESSION['staff'] = $user;
        unset($_SESSION['forgot_p_email']);
        unset($_SESSION['password_forgot']);
        redirect("index.php");
    } else {
        $msg = "<p class='text-danger w-500 f-16'>Password doesn't match !</p>";
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

    <title>Reset Password</title>
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
                                <div class="password">
                                    <?php echo $msg ?? "" ?>
                                    <div class="input-group mb-3">
                                        <label>New password</label>
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 40px;"><i class="bi bi-key"></i></span>
                                            </div>
                                            <input type="password" name="new_password" placeholder="Enter new password" style="width: 90%!important;" />
                                            <i class="far fa-eye-slash" onclick="show_password(this)" style="margin-left: -30px; cursor: pointer; padding-top:10px"></i>
                                        </div>
                                    </div>
                                    <div class="input-group mb-3">
                                        <label>Confirm password</label>
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 40px;"><i class="bi bi-key"></i></span>
                                            </div>
                                            <input type="password" name="confirm_password" placeholder="Re-enter new password" style="width: 90%!important;" />
                                            <i class="far fa-eye-slash" onclick="show_password(this)" style="margin-left: -30px; cursor: pointer; padding-top:10px"></i>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-12" style="text-align:right !important">
                                    <button type="submit" name="reset_password" class="mt-3 w-600 f-15 float-right">Reset Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <script src="./assets/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.6.0/umd/popper.min.js" integrity="sha512-BmM0/BQlqh02wuK5Gz9yrbe7VyIVwOzD1o40yi1IsTjriX/NGF37NyXHfmFzIlMmoSIBXgqDiG1VNU6kB5dBbA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</body>
<script>
    function show_password(obj) {
        var password = $(obj).closest('.input-group').find('input');
        var type = password.attr('type');
        console.log(password.html())
        if (type == 'password') {
            password.attr('type', 'text');
            $(obj).removeClass('fa-eye-slash')
            $(obj).addClass('fa-eye')
        } else {
            password.attr('type', 'password');
            $(obj).removeClass('fa-eye')
            $(obj).addClass('fa-eye-slash')
        }
    }
</script>

</html>