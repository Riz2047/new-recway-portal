<?php

include_once ('../includes/functions.php');
include_once ('../includes/config.php');

if(isset($_SESSION['staff'])) {
    redirect('index.php');
}

if(!isset($_GET['id'])) {
    redirect('signin.php');
}

if(isset($_POST['submit'])) {
    $password = $_POST['password'];
    $crypt_pass = password_hash($password, PASSWORD_BCRYPT);

    $query = 'UPDATE staff SET password = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$crypt_pass, $_GET['id']]);

    if($res) {
        redirect('signin.php');
    } else {
        $message = '<p class="alert alert-danger">Cannot change password! Try again later.</p>';
    }
}

?>


<?php if(!strpos($_SERVER['REQUEST_URI'], 'customer')) : ?>

    <style>
        :root {
            --dark-blue: <?php echo cssVars()['primaryColor'] ?>;
            --light-blue: <?php echo cssVars()['secondaryColor'] ?>;
        }
    </style>

<?php endif; ?>

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

    <body>
        <div class="container m-0 p-0 w-100 mw-100">
            <div class="row m-0 p-0 w-100 mw-100">
                <div class="col-lg-6 m-0 p-0 mx-auto">
                    <div class="container">
                        <div class="row sign-row d-flex justify-content-center align-items-center">
                            <form method="post" class="col-lg-9 sign-form mx-auto">
<!--                                <div class="continue-msg">LogIn to continue</div>-->
                                <?php echo isset($message) ? $message : '' ?>
                                <h4 class="">Change Password</h4>
                                <p class="f-14 w-400 mb-3">Enter new password.</p>
                                <label >Password</label>
                                <input required type="password" name="password" class=" w-100 mb-3" placeholder="Password ">
                                 <a><button type="submit" name="submit" class="mt-4 w-600 f-15 w-100">Change</button></a>
                                <a href="signin.php" class="f-14">Sign in?</a>
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

            togglePassword.addEventListener('click', function (e) {
                // toggle the type attribute
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                // toggle the eye slash icon
                this.classList.toggle('fa-eye-slash');
            });
        </script>
    </body>

</html>