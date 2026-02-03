<?php

include_once "../includes/functions.php";

if(!isset($_SESSION['reviewer'])) {
    redirect('signin.php');
}

$reviewer = findById("reviewers", $_SESSION['reviewer']->id);

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
    <link rel="stylesheet" href="../customer/assets/css/typography.css">
    <link rel="stylesheet" href="../customer/assets/css/style.css">
    <link rel="stylesheet" href="../customer/assets/css/responsive.css">

    <link rel="icon" type="image/x-icon" href="../customer/assets/images/favicon.png">
    <title>Dashboard | Reviewer</title>
</head>

<body>
<div class="d-flex " id="top">

    <main class="main ms-auto" style="width: 100%">
        <header class="header w-100 mt-lg-2">
            <div class="container">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="d-flex justify-content-between align-items-lg-end align-items-center   w-100">
                            <div>
                                <h1 class="f-30 w-700 font-secondary m-0 p-0">Reviewer Dashboard</h1>
                            </div>
                            <div class="d-flex align-items-center">

                                <div class="contact-div">
                                    <div>
                                        <i class="bi bi-person-check f-28 text-black p-0"></i>
                                    </div>
                                    <div class="ms-2 ">
                                        <h2 class="f-12 w-700 text-grey p-0 m-0 "><?php echo $reviewer->email ?></h2>
                                        <div style="margin-top: -5px !important;">
                                            <a href="logout.php"
                                               class="no-decoration p-0 text-grey w-700 f-14">Logout</a>
                                        </div>
                                    </div>
                                </div>

                                <div class="mx-2 d-flex flex-column" style="margin-top: 7px"><div id="google_translate_element2" ></div>

                                    <a href="#" id="lang-en" onclick="doGTranslate('sv|en');return false;" title="English" class="gflag nturl me-2"
                                       style="background-position:-0px -0px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16"
                                                                                   alt="English"/></a>
                                    <a href="#" id="lang-sv" onclick="doGTranslate('en|sv');return false;" title="Swedish" class="gflag nturl"
                                       style="background-position:-700px -200px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16"
                                                                                       alt="Swedish"/></a>
                                </div>

<!--                                <div class="select-language ms-2 ">-->
<!--                                    <img src="assets/images/US.webp" class="mb-1 active-lang" alt="">-->
<!--                                    <img src="assets/images/Flag_of_Sweden.svg.png" class="" alt="">-->
<!--                                </div>-->
                                <div class="side-menu ms-2">
                                    <i class="bi bi-list"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>