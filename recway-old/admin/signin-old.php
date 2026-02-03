<?php

include_once ('../includes/functions.php');

if(isset($_SESSION['admin'])) {
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
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/typograpgy.css">
    <title>Sign-In</title>
</head>

<body>

    <body>
        <div class="container m-0 p-0 w-100 mw-100">
            <div class="row m-0 p-0 w-100 mw-100">
                <div class="col-lg-6 m-0 p-0 mx-auto">
                    <div class="container">
                        <div class="row sign-row d-flex justify-content-center align-items-center">
                            <form method="post" class="col-lg-9 sign-form mx-auto">
                                <h1 class="f-32 w-600 pb-0 mb-4">Sign-In</h1>
                                <?php echo isset($message) ? $message : '' ?>
                                <p class="f-16 mb-0 pb-0 w-600">Email</p>
                                <input type="email" name="email" class="sign-input w-100 mb-3" required placeholder="Email Address ">
                                <p class="f-16 mb-0 pb-0 w-600">Password</p>
                                <input type="password" name="password" class="sign-input w-100" required placeholder="Password ">

                                <button type="submit" name="login" class="btn-fill mt-4"><a>Sign In</a></button>
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