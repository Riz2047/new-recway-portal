<?php

include_once ('../includes/functions.php');
include_once ('../includes/config.php');

if(isset($_SESSION['staff'])) {
    redirect('index.php');
}

if(isset($_POST['submit'])) {
    $email = $_POST['email'];

    $query = 'SELECT * FROM staff WHERE email = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$email]);
    $customer = $stmt->fetch();

    if($customer) {
        $body = "<b>Click on the link below to change password.</b><br>";
        $body .= "<a href='". SITE_URL ."staff/password.php?id=". $customer->id ."'>Change password</a>";
        sendMail($body, $email, $customer->name, "Change password");
        $message = '<p class="alert alert-success">Please check your mailbox!</p>';
    } else {
        $message = '<p class="alert alert-danger">No account found with this email!</p>';
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../assets/bootstrap-5/css/bootstrap.css">
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/responsive.css">
  <link rel="stylesheet" href="../assets/css/typograpgy.css">
  <title>Change password</title>
</head>

<body>

<body>
<div class="container m-0 p-0 w-100 mw-100">
  <div class="row m-0 p-0 w-100 mw-100">
    <div class="col-lg-6 m-0 p-0 mx-auto">
      <div class="container">
        <div class="row sign-row d-flex justify-content-center align-items-center">
          <form method="post" class="col-lg-9 sign-form mx-auto">
            <h1 class="f-32 w-600 pb-0 mb-4">Change Password</h1>
            <?php echo isset($message) ? $message : '' ?>
            <p class="f-16 mb-0 pb-0 w-600">Enter your email</p>
            <input type="email" name="email" required class="sign-input w-100 mb-3" placeholder="Email Address ">

            <button type="submit" name="submit" class="btn-fill mt-4 mx-0"><a>Confirm</a></button>
            <br><small class="mb-0 pb-0 w-600"><a href="signin.php">Login?</a></small>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>


<script src="../assets/bootstrap-5/js/bootstrap.bundle.min.js"></script>
</body>

<script src="../assets/bootstrap-5/js/bootstrap.bundle.min.js"></script>
</body>

</html>