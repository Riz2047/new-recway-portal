<?php
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);
include_once('../includes/functions.php');
setCurrentURL();
if (! isset($_SESSION['admin'])) {
    redirect('signin.php');
}
include_once("../includes/expired.php");
function getStatusCard($status)
{
    global $conn;
    $query = 'SELECT * FROM candidates WHERE status = ? AND expired = 0';
    $stmt = $conn->prepare($query);
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}
$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/bootstrap-5/css/bootstrap.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.12.1/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css" integrity="sha512-KfkfwYDsLkIlwQp6LFnl8zNdLGxu9YAA1QvwINks4PhcElQSvqcyVLLD9aMhXd13uQjoXtEKNosOWaZqXgel0g==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.1/font/bootstrap-icons.css">
    <!-- Bootstrap Icons -->
    <!--    <link rel="stylesheet" href="/path/to/cdn/bootstrap-icons.min.css" />-->
    <link href="../iconpicker/dist/css/bootstrapicons-iconpicker.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://unpkg.com/css-pro-layout@1.1.0/dist/css/css-pro-layout.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.datatables.net/datetime/1.1.2/css/dataTables.dateTime.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css" integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.3.4/css/buttons.dataTables.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/typograpgy.css">
    <link rel="stylesheet" href="../assets/css/responsive.css">
    <link rel="stylesheet" href="../assets/css/layout.css">
    <link rel="stylesheet" href="../assets/css/timeline.css">
    <link rel="stylesheet" href="calendar/css/style.css">
    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">
    <title>Admin Dashboard</title>
</head>
<body>
    <div class="layout has-sidebar fixed-sidebar fixed-header">
        <aside id="sidebar" class="sidebar break-point-lg has-bg-image">
            <div class="image-wrapper">
                <img src="https://user-images.githubusercontent.com/25878302/144499035-2911184c-76d3-4611-86e7-bc4e8ff84ff5.jpg" alt="sidebar background" />
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
                                            <g style="stroke: none; stroke-width: 0; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: none; fill-rule: nonzero; opacity: 1;" transform="translate(1.4065934065934016 1.4065934065934016) scale(2.81 2.81)">
                                                <path d="M 35.813 54.013 H 4.514 C 2.025 54.013 0 51.987 0 49.498 V 4.514 C 0 2.025 2.025 0 4.514 0 h 31.299 c 2.489 0 4.514 2.025 4.514 4.514 v 44.984 C 40.328 51.987 38.303 54.013 35.813 54.013 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,80,192); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                                <path d="M 35.813 90 H 4.514 C 2.025 90 0 87.975 0 85.485 V 69.741 c 0 -2.489 2.025 -4.515 4.514 -4.515 h 31.299 c 2.489 0 4.514 2.025 4.514 4.515 v 15.744 C 40.328 87.975 38.303 90 35.813 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,109,255); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                                <path d="M 85.485 90 H 54.187 c -2.489 0 -4.515 -2.025 -4.515 -4.515 V 40.501 c 0 -2.489 2.025 -4.514 4.515 -4.514 h 31.299 c 2.489 0 4.515 2.025 4.515 4.514 v 44.984 C 90 87.975 87.975 90 85.485 90 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,80,192); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                                <path d="M 85.485 24.773 H 54.187 c -2.489 0 -4.515 -2.025 -4.515 -4.515 V 4.514 C 49.672 2.025 51.697 0 54.187 0 h 31.299 C 87.975 0 90 2.025 90 4.514 v 15.745 C 90 22.748 87.975 24.773 85.485 24.773 z" style="stroke: none; stroke-width: 1; stroke-dasharray: none; stroke-linecap: butt; stroke-linejoin: miter; stroke-miterlimit: 10; fill: rgb(0,109,255); fill-rule: nonzero; opacity: 1;" transform=" matrix(1 0 0 1 0 0) " stroke-linecap="round" />
                                            </g>
                                        </svg>
                                    </span>
                                    <span class="menu-title text-dark-blue mt-1">Admin Dashboard</span>
                                </a>
                            </li>
                        </ul>
                        <div class="menu-item py-3">
                            <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">PEOPLE</span>
                        </div>
                        <ul>
                            <li class="menu-item">
                                <a href="admins.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-person-badge"></i>
                                    </span>
                                    <span class="menu-title">Admins</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="staff.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-people"></i>
                                    </span>
                                    <span class="menu-title">Staff</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="customers.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-person"></i>
                                    </span>
                                    <span class="menu-title">Customers</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="candidates.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-person-workspace"></i>
                                    </span>
                                    <span class="menu-title">Candidates</span>
                                </a>
                            </li>
                        </ul>
                        <div class="menu-item py-3">
                            <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">ORDERS</span>
                        </div>
                        <ul>
                            <li class="menu-item sub-menu">
                                <a>
                                    <span class="menu-icon">
                                        <i class="bi bi-clipboard-data"></i>
                                    </span>
                                    <span class="menu-title">Statuses</span>
                                </a>
                                <div class="sub-menu-list ps-0 ms-0">
                                    <ul class="ps-0 ms-0">
                                        <?php if (! empty($services)) : ?>
                                            <?php foreach ($services as $service) : ?>
                                                <li class="menu-item">
                                                    <a href="statuses.php?id=<?php echo $service->id ?>">
                                                        <span class="menu-icon">
                                                            <i class="bi bi-dot"></i>
                                                        </span>
                                                        <span class="menu-title"><?php echo $service->name  ?></span>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </li>
                            <li class="menu-item">
                                <a href="services.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-card-checklist"></i>
                                    </span>
                                    <span class="menu-title">Services</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="analytics.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-graph-up-arrow"></i>
                                    </span>
                                    <span class="menu-title">Statistics</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="history.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-clock-history"></i>
                                    </span>
                                    <span class="menu-title">History</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="logs.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-file-text"></i>
                                    </span>
                                    <span class="menu-title">Logs</span>
                                </a>
                            </li>
                        </ul>
                        <div class="menu-item py-3">
                            <span class="ps-4 f-14 w-500 text-light-white ls-3 menu-heading ">SETTINGS</span>
                        </div>
                        <ul>
                            <!--                        <li class="menu-item">-->
                            <!--                            <a href="emails.php">-->
                            <!--                                    <span class="menu-icon">-->
                            <!--                                        <i class="bi bi-envelope"></i>-->
                            <!--                                    </span>-->
                            <!--                                <span class="menu-title">Emails</span>-->
                            <!--                            </a>-->
                            <!--                        </li>-->
                            <li class="menu-item">
                                <a href="places.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-geo-alt"></i>
                                    </span>
                                    <span class="menu-title">Places</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="messages.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-chat-left-dots"></i>
                                    </span>
                                    <span class="menu-title">Messages</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="profile.php?id=<?php echo $_SESSION['admin']->id ?>">
                                    <span class="menu-icon">
                                        <i class="bi bi-gear"></i>
                                    </span>
                                    <span class="menu-title">Account Settings</span>
                                </a>
                            </li>
                            <li class="menu-item">
                                <a href="settings.php">
                                    <span class="menu-icon">
                                        <i class="bi bi-gear-wide"></i>
                                    </span>
                                    <span class="menu-title">Site Settings</span>
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
                    <div class="me-2" style="margin-top: 7px">
                        <div id="google_translate_element2"></div>
                        <a href="#" id="lang-en" onclick="doGTranslate('sv|en');return false;" title="English" class="gflag nturl me-2" style="background-position:-0px -0px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="English" /></a>
                        <a href="#" id="lang-sv" onclick="doGTranslate('en|sv');return false;" title="Swedish" class="gflag nturl" style="background-position:-700px -200px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="Swedish" /></a>
                    </div>
                    <div class="user-icon">
                        <i class="fa-regular fa-user"></i>
                    </div>
                    <div class="dropdown profile-dropdown">
                        <button class=" dropdown-toggle" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                            <?php echo $_SESSION['admin']->name ?>
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                            <li><a class="dropdown-item" href="profile.php?id=<?php echo $_SESSION['admin']->id ?>"><i class="bi bi-gear me-2"></i>Account Settings</a>
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-left me-2"></i>Logout</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>
            <main class="content">
                <?php
                //function rand_string( $length ) {
                //
                //    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+";
                //    return substr(str_shuffle($chars),0,$length);
                //
                //}
?>