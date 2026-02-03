<?php



include_once "includes/functions.php";



if (!isset($_SESSION['customer'])) {

    redirect('signin.php');

}



$customer = findById("customers", $_SESSION['customer']->id);



include_once("includes/expired.php");



$permissions = findallByQuery("SELECT * FROM user_permissions JOIN user_allowed_permissions ON user_permissions.id = user_allowed_permissions.per_id WHERE user_allowed_permissions.user_id = '{$_SESSION['customer']->id}' AND user_allowed_permissions.user_type = 2");

$user_allowed_permissions = null;

if (!empty($permissions)) {

    foreach ($permissions as $permission) {

        $user_allowed_permissions[$permission->title] = 1;

    }

}

$query = 'SELECT * FROM candidates WHERE cus_id = ? AND expired = 0';

$stmt = $conn->prepare($query);

$stmt->execute([$_SESSION['customer']->id]);

$candidates = $stmt->fetchAll();



if (!empty($candidates)) {

    $countStatus = array_count_values(array_column($candidates, 'status'));

}



$query = 'SELECT * FROM service_categories';

$stmt = $conn->prepare($query);

$stmt->execute();

$services = $stmt->fetchAll();



$statuses = getStatuses();



$query = "SELECT * FROM service_categories";

$stmt = $conn->prepare($query);

$stmt->execute();

$services = $stmt->fetchAll();

$services2 = array();



$query = "SELECT * FROM customer_services WHERE customer_services.cus_id={$_SESSION['customer']->id}";

$stmt = $conn->prepare($query);

$stmt->execute();

$customer_services = $stmt->fetchAll();



foreach ($customer_services as $service) {

    $query = "SELECT * FROM interviews WHERE id = ?";

    $stmt = $conn->prepare($query);

    $stmt->execute([$service->service_id]);

    $interview = $stmt->fetch();



    $allowed_service = findObjectById($interview->service_cat_id, $services);

    if (!empty($allowed_service)) {

        $a = $services[array_search($allowed_service, $services)];

        if (!in_array($a, $services2)) {

            array_push($services2, $a);

        }

    }

}



function findObjectById($id, $services)

{



    foreach ($services as $element) {

        if ($id == $element->id) {

            return $element;

        }

    }



    return false;

}

function getStatusesByServiceOfCustomer($service_id)

{

    global $conn;

    $statuses = [];

    $customer = findById("customers", $_SESSION['customer']->id);

    if (!empty($customer->statuses)) {

        $stats = explode(',', $customer->statuses);

        foreach ($stats as $stat) {

            $query = "SELECT statuses.* FROM statuses LEFT JOIN status_services ON statuses.id = status_services.status_id LEFT JOIN interviews ON status_services.service_id = interviews.id WHERE statuses.id = ? AND interviews.service_cat_id = ? GROUP BY status_services.status_id";

            $stmt = $conn->prepare($query);

            $stmt->execute([$stat, $service_id]);

            $statuses[] = $stmt->fetchAll();

        }

    }

    return $statuses;



}



$customer = findById("customers", $_SESSION['customer']->id);

$cus_login = findByQuery("Select interview_template from customers WHERE id = " . $_SESSION['customer']->id);

?>



<!DOCTYPE html>

<html lang="en">



<head>

    <meta charset="UTF-8">

    <meta http-equiv="X-UA-Compatible" content="IE=edge">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!--Sofia Fonts  -->

    <link href="https://fonts.googleapis.com/css2?family=Sofia+Sans+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,800;1,700&display=swap" rel="stylesheet">

    <!-- Nunito -->

    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800&family=Sofia+Sans+Condensed:ital,wght@0,300;0,400;0,500;0,600;0,800;1,700&display=swap" rel="stylesheet">

    <!-- bootstarp -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">

    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.3/css/jquery.dataTables.min.css">

    <link rel="stylesheet" href="customer/assets/css/typography.css">

    <link rel="stylesheet" href="customer/assets/css/style.css">

    <link rel="stylesheet" href="customer/assets/css/responsive.css">



    <link rel="icon" type="image/x-icon" href="customer/assets/images/favicon.png">

    <title>Dashboard | Customer</title>

</head>



<body>

    <div class="d-flex " id="top">

        <aside class="sidebar">

            <div class="logo">

                <a href="">

                    <img src="customer/assets/images/logo.png" alt="" class="img-fluid">

                </a>

            </div>

            <div class="profile-information mt-1">

                <div class="candidate-profile">

                    <?php $cusName = explode(" ", $customer->name); ?>

                    <?php

                    if (count($cusName) > 1) {

                        $initials = strtoupper(substr($cusName[0], 0, 1) . substr($cusName[1], 0, 1));

                    } else {

                        $initials = strtoupper(substr($cusName[0], 0, 1));

                    }

                    ?>

                    <h1 class="f-26 w-700 text-white m-0 p-0 font-secondary"><?php echo $initials; ?></h1>

                </div>

                <h1 class="f-18 w-700 mb-0 pb-0 mt-2"><?php echo $customer->name ?></h1>

                <p class="f-16 text-grey w-500"><?php echo $customer->email ?></p>

            </div>

            <nav class="nav-menu  d-flex flex-column ">

                <div class="d-flex align-items-center">

                    <a class="nav-link <?php echo $activeLink == 'dashboard' ? 'active-nav' : '' ?>" href="index.php">

                        <i class="bi bi-speedometer2"></i>

                        Dashboard

                    </a>

                </div>

                <?php if (isset($user_allowed_permissions['Create-order']) && !empty($user_allowed_permissions['Create-order'])) {

                ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'start-order' ? 'active-nav' : '' ?>" href="services.php">

                            <i class="bi bi-cast"></i>

                            Create Order

                        </a>

                    </div>

                <?php     }

                ?>

                <?php if (isset($user_allowed_permissions['View-order']) && !empty($user_allowed_permissions['View-order'])) {

                ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'all-orders' ? 'active-nav' : '' ?>" href="orders.php">

                            <i class="bi bi-kanban"></i>

                            All Orders<div class="notification-number ms-2 mt-1"><?php echo !empty($candidates) ? count($candidates) : '' ?></div>

                        </a>

                    </div>

                <?php    }

                ?>

                <?php if (isset($user_allowed_permissions['View-order']) && !empty($user_allowed_permissions['View-order'])) {

                ?>

                    <?php if (!empty($services2)) : ?>

                        <?php foreach ($services2 as $service) : ?>

                            <div>

                                <button class="dropdown-btn d-flex nav-link ">

                                    <i class="<?php echo $service->icon ?>"></i>

                                    <?php echo $service->name ?>

                                    <i class="bi bi-chevron-down ms-2 f-12 mt-1"></i>

                                </button>

                                <div class="dropdown-container">

                                    <?php $statuses = getStatusesByServiceOfCustomer($service->id) ?>

                                    <?php if (!empty($statuses)) : ?>

                                        <?php foreach ($statuses as $status) : ?>

                                            <?php if (!empty($status[0])) : ?>

                                            <a href="orders.php?status=<?php echo $status[0]->id ?>" class="d-flex f-14 w-600 text-grey mb-3 no-decoration dropdown-container-link"><?php echo $status[0]->status ?> <?php echo isset($countStatus) && isset($countStatus[$status[0]->id]) ? '<div class="notification-number ms-2 mt-1">' . $countStatus[$status[0]->id] . '</div>' : '' ?> </a>

                                            <?php endif; ?>

                                        <?php endforeach; ?>

                                    <?php endif; ?>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php endif; ?>

                <?php   }

                ?>

                <?php if (isset($user_allowed_permissions['View-department']) && !empty($user_allowed_permissions['View-department'])) {  ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'departments' ? 'active-nav' : '' ?>" href="departments.php">

                            <i class="bi bi-collection"></i>

                            Departments

                        </a>

                    </div>

                <?php  } ?>

                <?php if (isset($user_allowed_permissions['View-department-user']) && !empty($user_allowed_permissions['View-department-user'])) {  ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'department_users' ? 'active-nav' : '' ?>" href="department_users.php">

                            <i class="bi bi-people"></i>

                            Department Users

                        </a>

                    </div>

                <?php  } ?>

                <?php if (isset($user_allowed_permissions['View-reviewer']) && !empty($user_allowed_permissions['View-reviewer'])) {

                ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'reviewers' ? 'active-nav' : '' ?>" href="reviewers.php">

                            <i class="bi bi-person-check"></i>

                            Reviewers

                        </a>

                    </div>

                <?php    }

                ?>

                <?php if (isset($user_allowed_permissions['View-history']) && !empty($user_allowed_permissions['View-history'])) {

                ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'history' ? 'active-nav' : '' ?>" href="history.php">

                            <i class="bi bi-hourglass-split"></i>

                            History

                        </a>

                    </div>

                <?php    }

                ?>

                <div class="d-flex align-items-center">

                    <a class="nav-link <?php echo $activeLink == 'profile' ? 'active-nav' : '' ?>" href="profile.php">

                        <i class="bi bi-person-gear"></i>

                        Profile

                    </a>

                </div>

                <?php if (isset($user_allowed_permissions['View-emails']) && !empty($user_allowed_permissions['View-emails'])) {

                ?>

                    <div class="d-flex align-items-center">

                        <a class="nav-link <?php echo $activeLink == 'emails' ? 'active-nav' : '' ?>" href="emails.php">

                            <i class="bi bi-envelope"></i>

                            Emails

                        </a>

                    </div>

                <?php    }

                ?>

                <div class="d-flex align-items-center mt-auto ">

                    <a class="nav-link text-dark" href="logout.php">

                        <i class="bi bi-box-arrow-left"></i>

                        Log Out

                    </a>

                </div>

            </nav>

        </aside>



        <main class="main ms-auto">

            <header class="header w-100 mt-lg-2">

                <div class="container">

                    <div class="row">

                        <div class="col-lg-12">

                            <div class="d-flex justify-content-between align-items-lg-end align-items-center   w-100">

                            <div>
                                    <h1 class="f-30 w-700 font-secondary m-0 p-0" style="
    width: 15rem;
    float: left;
">Customer Dashboard</h1>
    <p class="text-success">We are excited to announce the upcoming launch of a new version of our portal, aimed at enhancing functionality and user experience
    </p>
                                </div>

                                <div class="d-flex align-items-center">



                                    <div class="contact-div">

                                        <div>

                                            <i class="bi bi-envelope-at f-28 text-black p-0"></i>

                                        </div>

                                        <div class="ms-2 ">

                                            <h2 class="f-12 w-700 text-grey p-0 m-0 ">Contact Us:</h2>

                                            <div style="margin-top: -5px !important;">

                                                <a href="mailto:info@recway.nu" class="no-decoration p-0 text-grey w-700 f-14">info@recway.nu</a>

                                            </div>

                                            <div style="margin-top: -8px !important;">

                                                <span class="no-decoration p-0 text-grey w-700 f-12">+468 502 492 55</s>

                                            </div>

                                        </div>

                                    </div>



                                    <div class="mx-2 d-flex flex-column" style="margin-top: 7px">

                                        <div id="google_translate_element2"></div>



                                        <a href="#" id="lang-en" onclick="doGTranslate('sv|en');return false;" title="English" class="gflag nturl me-2" style="background-position:-0px -0px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="English" /></a>

                                        <a href="#" id="lang-sv" onclick="doGTranslate('en|sv');return false;" title="Swedish" class="gflag nturl" style="background-position:-700px -200px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="Swedish" /></a>

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