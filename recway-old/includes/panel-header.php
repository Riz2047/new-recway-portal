<?php

include_once ('includes/functions.php');

if(!isset($_SESSION['customer'])) {
    redirect('signin.php');
}

include_once ("includes/expired.php");

$query = 'SELECT * FROM candidates WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['customer']->id]);
$candidates = $stmt->fetchAll();

if(!empty($candidates)) {
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
    if(!empty($allowed_service)) {
        $a = $services[array_search($allowed_service, $services)];
        if(!in_array($a, $services2)) {
            array_push($services2, $a);
        }
    }
}

function findObjectById($id, $services){

    foreach ( $services as $element ) {
        if ( $id == $element->id ) {
            return $element;
        }
    }

    return false;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/bootstrap-5/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css"
          integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g=="
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.9.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/css-pro-layout@1.1.0/dist/css/css-pro-layout.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/typograpgy.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <link rel="stylesheet" href="assets/css/layout.css">
    <link rel="stylesheet" href="assets/css/timeline.css">

    <link rel="icon" type="image/x-icon" href="assets/images/favicon.png">

    <title>Customer Dashboard</title>

</head>

<body>
<div class="layout has-sidebar fixed-sidebar fixed-header">
    <aside id="sidebar" class="sidebar break-point-lg has-bg-image">
        <div class="image-wrapper">
            <img src="https://user-images.githubusercontent.com/25878302/144499035-2911184c-76d3-4611-86e7-bc4e8ff84ff5.jpg"
                 alt="sidebar background" />
        </div>
        <div class="sidebar-layout">
            <div class="sidebar-header">
                <a id="btn-collapse" href="#">
                    <i class="bi bi-list f-22 text-white ps-2"></i>
                </a>
                <a id="btn-toggle2" href="#" class="sidebar-toggler break-point-lg">
                    <i class="bi bi-list"></i>
                </a>
            </div>
            <div class="sidebar-content">
                <nav class="menu open-current-submenu">
                    <ul>
                        <li class="menu-item">
                            <a href="index.php">
                                    <span class="menu-icon">
                                        <svg xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1" width="20px" height="20px" viewBox="0 0 256 256" xml:space="preserve">
                                        <defs>
                                        </defs>
                                        <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)" >
                                            <path d="M 35.813 54.013 H 4.514 C 2.025 54.013 0 51.987 0 49.498 V 4.514 C 0 2.025 2.025 0 4.514 0 h 31.299 c 2.489 0 4.514 2.025 4.514 4.514 v 44.984 C 40.328 51.987 38.303 54.013 35.813 54.013 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,80,192); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                            <path d="M 35.813 90 H 4.514 C 2.025 90 0 87.975 0 85.485 V 69.741 c 0 -2.489 2.025 -4.515 4.514 -4.515 h 31.299 c 2.489 0 4.514 2.025 4.514 4.515 v 15.744 C 40.328 87.975 38.303 90 35.813 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,109,255); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                            <path d="M 85.485 90 H 54.187 c -2.489 0 -4.515 -2.025 -4.515 -4.515 V 40.501 c 0 -2.489 2.025 -4.514 4.515 -4.514 h 31.299 c 2.489 0 4.515 2.025 4.515 4.514 v 44.984 C 90 87.975 87.975 90 85.485 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,80,192); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                            <path d="M 85.485 24.773 H 54.187 c -2.489 0 -4.515 -2.025 -4.515 -4.515 V 4.514 C 49.672 2.025 51.697 0 54.187 0 h 31.299 C 87.975 0 90 2.025 90 4.514 v 15.745 C 90 22.748 87.975 24.773 85.485 24.773 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,109,255); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                        </g>
                                        </svg>
                                    </span>
                                <span class="menu-title text-dark-blue mt-1">Customer Dashboard</span>
                            </a>
                        </li>

                    </ul>
                    <div class="menu-item py-3">
                        <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">ORDERS</span>
                    </div>

                    <ul>
                        <li class="menu-item">
                            <a href="orders.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </span>
                                <span class="menu-title">All Orders <?php echo !empty($candidates) ? '<small class="order-counter">' . count($candidates) . '</small>' : '' ?></span>
                            </a>
                        </li>

                        <?php if(!empty($services2)): ?>
                            <?php foreach ($services2 as $service): ?>
                                <li class="menu-item sub-menu" style="cursor:pointer;">
                                    <a>
                                <span class="menu-icon">
                                    <i class="<?php echo $service->icon ?>"></i>
                                </span>
                                        <span class="menu-title"><?php echo $service->name ?></span>
                                    </a>
                                    <?php $statuses = getStatusesByService($service->id) ?>
                                    <div class="sub-menu-list ps-0 ms-0">
                                        <ul class="ps-0 ms-0">
                                            <?php if(!empty($statuses)): ?>
                                                <?php foreach ($statuses as $status): ?>
                                                    <li class="menu-item">
                                                        <a href="orders.php?status=<?php echo $status->sID ?>">
                                <span class="menu-icon">
                                    <i class="bi <?php echo $status->status_icon ?>"></i>
                                </span>
                                                            <span class="menu-title"><?php echo $status->status ?> <?php echo isset($countStatus) && isset($countStatus[$status->sID]) ? '<small class="order-counter">' . $countStatus[$status->sID] . '</small>' : '' ?></span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            <?php endif; ?>

                                        </ul>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>

                    </ul>

<!--                    <ul>-->
<!--                        <li class="menu-item">-->
<!--                            <a href="orders.php">-->
<!--                                    <span class="menu-icon">-->
<!--                                        <i class="bi bi-person-badge"></i>-->
<!--                                    </span>-->
<!--                                <span class="menu-title">All Orders --><?php //echo !empty($candidates) ? '<small class="order-counter">' . count($candidates) . '</small>' : '' ?><!--</span>-->
<!--                            </a>-->
<!--                        </li>-->
<!--                        --><?php //if(!empty($statuses)): ?>
<!--                            --><?php //foreach ($statuses as $status): ?>
<!--                                <li class="menu-item">-->
<!--                                    <a href="orders.php?status=--><?php //echo $status->id ?><!--">-->
<!--                                <span class="menu-icon">-->
<!--                                    <i class="bi --><?php //echo $status->status_icon ?><!--"></i>-->
<!--                                </span>-->
<!--                                        <span class="menu-title">--><?php //echo $status->status ?><!-- --><?php //echo isset($countStatus) && isset($countStatus[$status->id]) ? '<small class="order-counter">' . $countStatus[$status->id] . '</small>' : '' ?><!--</span>-->
<!--                                    </a>-->
<!--                                </li>-->
<!--                            --><?php //endforeach; ?>
<!--                        --><?php //endif; ?>
<!--                    </ul>-->
                    <div class="menu-item py-3">
                        <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">ORDERS MANAGEMENT</span>
                    </div>
                    <ul>
                        <li class="menu-item">
                            <a href="history.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-clock-history"></i>
                                    </span>
                                <span class="menu-title">History</span>
                            </a>
                        </li>


                    </ul>
                    <div class="menu-item py-3">
                        <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">SETTINGS</span>
                    </div>
                    <ul>
                        <li class="menu-item">
                            <a href="profile.php?id=<?php echo $_SESSION['customer']->id ?>">
                                    <span class="menu-icon">
                                        <i class="bi bi-gear"></i>
                                    </span>
                                <span class="menu-title">Account Settings</span>
                            </a>
                        </li>

                    </ul>
                </nav>
            </div>
        </div>
    </aside>

    <div id="overlay" class="overlay"></div>
    <div class="layout">
        <header class="header">
            <!-- <a id="btn-collapse" href="#">
                <i class="ri-menu-line ri-xl"></i>
            </a> -->

            <a id="btn-toggle" href="#" class="sidebar-toggler break-point-lg">
                <i class="bi bi-list"></i>
            </a>
            <div class="d-flex ms-auto">
                <div class="me-2" style="margin-top: 7px"><div id="google_translate_element2" ></div>

                    <a href="#" id="lang-en" onclick="doGTranslate('sv|en');return false;" title="English" class="gflag nturl me-2"
                       style="background-position:-0px -0px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16"
                                                                   alt="English"/></a>
                    <a href="#" id="lang-sv" onclick="doGTranslate('en|sv');return false;" title="Swedish" class="gflag nturl"
                       style="background-position:-700px -200px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16"
                                                                       alt="Swedish"/></a></div>

                <div class="user-icon">
                    <i class="fa-regular fa-user"></i>
                </div>
                <div class="dropdown profile-dropdown">
                    <button class=" dropdown-toggle" type="button" id="dropdownMenuButton1"
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo $_SESSION['customer']->name ?>
                    </button>
                    <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                        <li><a class="dropdown-item" href="profile.php?id=<?php echo $_SESSION['customer']->id ?>"><i class="bi bi-gear me-2"></i>Account Settings</a>
                        </li>
                        <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
        <main class="content">