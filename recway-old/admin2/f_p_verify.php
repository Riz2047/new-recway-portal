<?php
include_once('../includes/functions.php');
if (isset($_POST['send_code'])) {
    if (isset($_POST['email']) && !empty($_POST['email'])) {
        $_SESSION['forgot_p_email'] = $_POST['email'];
        $user = findByQuery("SELECT * FROM admin WHERE email = '{$_POST['email']}' LIMIT 1");
        if (!empty($user)) {
            $otpDB = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_POST['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            if (!empty($otpDB)) {
                $otp = $otpDB->otp;
            } else {
                $otp = strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
                insert("otp_verification", ['email' => $_POST['email'], 'otp' => $otp]);
            }
            $body = '<div>
                              <div style="background-color: #AC0206; color: #ffffff; text-align:center; padding: 4px">
                                <h2>Your verification code</h2>
                              </div>
                              <div>
                                <p>Use the following verification code to reset your password</p>
                                <h1 style="background-color: #dddddd; padding: 5px 10px; border-radius: 10px; width: fit-content">' . $otp . '</h1>
                                <small>This code is valid for 24 hours</small>
                                <div style="font-weight: bold; background-color: #DDDDDD; text-align:center; padding: 4px">
                                    <p style="text-align: center">Regards</p>
                                    <p style="text-align: center">Recway AB</p>
                                </div>
                              </div>
                            </div>';
            sendMail($body, $_POST['email'], $user->name, "Code Verification");
            $msg = "<p class='text-success w-500 f-16'>We Have Sended Your an OTP Verification Code. Please Verify!</p>";
        } else {
            $msg = "<p class='text-danger w-500 f-16'>Re-check your email address for spelling mistakes</p>";
        }
    } else {
        $msg = "<p class='text-danger w-500 f-16'>Please re-enter your email address</p>";
    }
}
if (isset($_POST['verify_code'])) {
    if (!empty($_POST['email']) && $_POST['email'] == $_SESSION['forgot_p_email']) {
        $otp = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_POST['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
        if (isset($_POST['otp']) && !empty($_POST['otp'])) {
            if ($otp->otp !== $_POST['otp']) {
                $msg = "<p class='text-danger w-500 f-16'>Your OTP is incorrect</p>";
            } else {
                $user = findByQuery("SELECT * FROM admin WHERE email = '{$_POST['email']}' LIMIT 1");
                $_SESSION['password_forgot'] = 1;
                delete("otp_verification", "id", $otp->id);
                redirect("forgot_password.php");
            }
        } else {
            $msg = "<p class='text-danger w-500 f-16'>Something went wrong. Please enter OTP again !</p>";
        }
    } else {
        $msg = "<p class='text-danger w-500 f-16'>Email didn't match</p>";
    }
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
                            <div class="d-flex justify-content-center align-items-center">
                                <div class="grey-box">
                                    <?php echo $msg ?? "" ?>
                                    <p class="f-14 w-500">
                                        Please Enter Your Email And Send Code Then Verify!
                                    </p>
                                    <form action="" class="form" method="post">
                                        <div class="input-group mb-3">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 39px;"><i class="bi bi-envelope"></i></span>
                                            </div>
                                            <input type="email" name="email" class="mb-3" placeholder="Enter Email" style="width: 89%!important;" <?php if (isset($_SESSION['forgot_p_email']) && !empty($_SESSION['forgot_p_email'])) { ?> value="<?= $_SESSION['forgot_p_email'] ?>" <?php } ?> />
                                            <div class="input-group-prepend">
                                                <span class="input-group-text" id="basic-addon1" style="border-top-right-radius: 0px;border-bottom-right-radius: 0px;height: 39px;"><i class="bi bi-key"></i></span>
                                            </div>
                                            <input type="text" name="otp" class="mb-3" placeholder="Enter Code" style="width: 89%!important;" />
                                        </div>
                                        <div class="mt-3 mb-1 log-btn-div" style="text-align: end;">
                                            <button type="submit" name="send_code" class="log-btn ">Send Code</button>
                                            <button type="submit" name="verify_code" class="log-btn ">Verify Code</button>
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
</body>

</html>