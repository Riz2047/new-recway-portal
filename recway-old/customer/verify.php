<?php

include_once ('../includes/functions.php');

$otp = findByQuery("SELECT * FROM otp_verification WHERE email = '{$_SESSION['email']}' AND date_time > DATE_SUB(NOW(), INTERVAL 24 HOUR)");

if(empty($otp)) {
    redirect("signin.php");
}

if(isset($_POST['otp'])) {
    if($otp->otp !== $_POST['otp']) {
        $msg = "<p class='text-danger w-500 f-16'>Your OTP is incorrect</p>";
    } else {
        $user = findByQuery("SELECT * FROM customers WHERE email = '{$_SESSION['email']}' LIMIT 1");
        unset($_SESSION['email']);
        $_SESSION['customer'] = $user;

        delete("otp_verification", "id", $otp->id);
        redirect("index.php");
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!--Sofia Fonts  -->
  <link
    href="https://fonts.googleapis.com/css2?family=Sofia+Sans+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,800;1,700&display=swap"
    rel="stylesheet">
  <!-- Nunito -->
  <link
    href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Sofia+Sans+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,800;1,700&display=swap"
    rel="stylesheet">
  <!-- bootstarp -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">
  <link rel="stylesheet" href="assets/css/typography.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/responsive.css">
  <title>Dashboard | Customer</title>
</head>

<body class="bg-white border-0">
  <div class="container">
    <div class="row">
        <div class="col-lg-12 ">
            <div class="logo-login mt-2">
                <img src="assets/images/logo.png" alt="" class="">
            </div>
        </div>
        <div class="col-lg-6 d-md-block d-none">
            <div class="d-flex justify-content-center align-items-center">
                <img src="assets/images/Hello-bro.png" alt="" class="img-fluid">
            </div>
        </div>
        <div class="col-lg-6">
           <div class="d-flex justify-content-center align-items-center h-100">
            <div class="grey-box">
                <h1 class="f-30 w-500 text-black">Verify</h1>
                <?php echo $msg ?? "" ?>
                <p class="f-14 w-500">
                    We have sent a verification code to your email address
                </p>
                <form action="" class="form" method="post">
                    <div class="d-flex align-items-center form-row mb-3">
                      <label for="otp">Code</label>
                      <input id="otp" type="text" name="otp" placeholder="Enter Code" class="w-100 from-input">
                      <div class="form-icon me-2">
                        <i class="bi bi-key"></i>
                      </div>
                    </div>
                      <div class="mt-3 mb-1 log-btn-div">
                        <button type="submit" name="login" class="log-btn ">Verify</button>
                      </div>
                  </form>
            </div>
           </div>
        </div>
    </div>
  </div>



  <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
    integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
    crossorigin="anonymous"></script>
  <script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>
  <script src="assets/js/app.js"></script>
</body>

</html>