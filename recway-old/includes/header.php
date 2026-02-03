<?php

include_once ('includes/functions.php');

if(!isset($_SESSION['customer'])) {
    redirect('signin.php');
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap-5/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/5.1.3/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/typograpgy.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
          integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!--Dropzone  -->
    <link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />

    <title>Home</title>
</head>

<?php if(basename($_SERVER['PHP_SELF']) === 'reports.php'): ?>
<body class="previous-body">
<?php else: ?>
<body>
<?php endif; ?>
<div class="container-fluid">
    <!-- Page Content  -->
    <div class="row">
        <nav class="navbar navbar-expand-lg navbar-light mb-1 ">

            <div class="container-fluid d-flex justify-content-between align-items-center">

                <button type="button" id="sidebarCollapse" class=" side-bar-btn btn text-sidebar bg-turbo-yellow">
                    <a href="index.php" class="f-32 w-500 text-white no-decoration">Recway</a>

                </button>

                <div class="d-flex align-items-center">
                    <div class="p-0 m-0 me-4"><a class="text-white" style="text-decoration:none;" href="index.php">Home</a></div>
                    <div class="header ">
                        <div class="dropdown header-dropdown">
                            <button class="account-btn dropdown-toggle f-18 w-400" type="button"
                                    id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                <!-- <img src="assets/images/image 224.png" alt="" class="img-fluid account-dp"> -->
                                <i class="fa-solid fa-user me-2"></i><?php echo $_SESSION['customer']->name ?>
                            </button>
                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                                <li><a class="dropdown-item" href="profile.php?id=<?php echo $_SESSION['customer']->id ?>">Profile</a></li>
                                <li><a class="dropdown-item" href="logout.php">Log Out</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

            </div>

        </nav>

    </div>