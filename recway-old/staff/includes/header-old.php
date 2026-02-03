<?php







//ini_set('display_errors', 1);



//ini_set('display_startup_errors', 1);



//error_reporting(E_ALL);







include_once('../includes/functions.php');







setCurrentURL();







if (!isset($_SESSION['staff'])) {



    redirect('signin.php');



}

// if (isset($_SESSION['staff']) && $_SESSION['staff']->id == 28) {

//     $query = 'SELECT * FROM staff WHERE id = ?';

//     $stmt = $conn->prepare($query);

//     $stmt->execute([38]);

//     $login_user = $stmt->fetch(PDO::FETCH_OBJ);

//     unset($_SESSION['staff']);

//     $_SESSION['staff'] = $login_user;



// }

$query = 'SELECT * FROM staff WHERE id = ?';



$stmt = $conn->prepare($query);



$stmt->execute([$_SESSION['staff']->id]);



$login_user = $stmt->fetch();







$staff_permissions = [];



$allowed_staff_permission = [];



$query = 'SELECT * FROM user_category WHERE id = ?';



$stmt = $conn->prepare($query);



$stmt->execute([$login_user->category]);



$category_permissions = $stmt->fetchAll();



if (!empty($category_permissions[0]->permissions_id)) {



    $staff_permissions = explode(',', $category_permissions[0]->permissions_id);



}



if (!empty($staff_permissions)) {



    foreach ($staff_permissions as $staff_key => $staff_permission) {



        $query = 'SELECT title FROM user_permissions WHERE id = ?';



        $stmt = $conn->prepare($query);



        $stmt->execute([$staff_permission]);



        $result = $stmt->fetch();



        $allowed_staff_permission[$result->title] = 1;



    }



}



include_once("../includes/expired.php");







// $candidates_addition_query = '';
$candidates_addition_query = ' AND staff_id = ' . (int)$_SESSION['staff']->id;;



if (isset($allowed_staff_permission['view_own_candidate']) && !empty($allowed_staff_permission['view_own_candidate'])) {



    // $candidates_addition_query = ' AND staff_id = ' . $_SESSION['staff']->id;
    $candidates_addition_query = ' AND staff_id = ' . (int)$_SESSION['staff']->id;


}



if (isset($allowed_staff_permission['view_all_candidate']) && !empty($allowed_staff_permission['view_all_candidate'])) {



    $candidates_addition_query = '';



}







$staff_addition_query = '';



if (isset($_SESSION['staff']->id) && !empty($_SESSION['staff']->id)) {



    $query = 'SELECT * FROM staff WHERE id = ?';



    $stmt = $conn->prepare($query);



    $stmt->execute([$_SESSION['staff']->id]);



    $staff_mem = $stmt->fetch();



    if (!empty($staff_mem)) {



        if (isset($staff_mem->staff_members) && !empty($staff_mem->staff_members)) {
            if (!empty($candidates_addition_query)) {
                $staff_addition_query .= ',' . (int)$_SESSION['staff']->id;
                $candidates_addition_query = ' AND staff_id IN (' . $staff_mem->staff_members . $staff_addition_query . ') AND expired = 0';
            }
        }



    }



}







function getStatusCard($status)



{



    global $conn;







    $query = 'SELECT * FROM candidates WHERE status = ? AND expired = 0';



    $stmt = $conn->prepare($query);



    $stmt->execute([$status]);



    return $stmt->fetchAll();



}



function getneworderscount($id, $candidates_addition_query = null)



{



    global $conn;







    $query = 'SELECT COUNT(candidates.id) as new_orders 



              FROM statuses 



              LEFT JOIN service_categories ON service_categories.id = statuses.status_type 



              LEFT JOIN candidates ON candidates.status = statuses.id 



              WHERE statuses.status LIKE "New Order" 



              AND statuses.status_type = ? 



              AND expired = 0' . $candidates_addition_query;







    $stmt = $conn->prepare($query);



    $stmt->execute([$id]);



    return $stmt->fetchColumn();



}

function getBookedcount($id, $candidates_addition_query = null)

{

    global $conn;



    $query = 'SELECT COUNT(candidates.id) as new_orders 

              FROM statuses 

              LEFT JOIN service_categories ON service_categories.id = statuses.status_type 

              LEFT JOIN candidates ON candidates.status = statuses.id 

              WHERE statuses.status LIKE "Booked" 

              AND statuses.status_type = ? 

              AND expired = 0' . $candidates_addition_query;



    $stmt = $conn->prepare($query);

    $stmt->execute([$id]);

    return $stmt->fetchColumn();

}



function getStatusCardofStaff($status, $candidates_addition_query = null)



{



    global $conn;







    $query = 'SELECT * FROM candidates WHERE status = ? AND expired = 0' . $candidates_addition_query;



    $stmt = $conn->prepare($query);



    $stmt->execute([$status]);



    return $stmt->fetchAll();



}







$query = 'SELECT * FROM service_categories';



$stmt = $conn->prepare($query);



$stmt->execute();



$services = $stmt->fetchAll();



$current_page = basename($_SERVER['PHP_SELF']);



if ($current_page == 'candidates.php' || $current_page == 'index.php') {

    if (isset($_GET['status']) && !empty($_GET['status'])) {

        $_SESSION['status'] = $_GET['status'];

    }else{

        if(isset($_SESSION['status'])){

            unset($_SESSION['status']);

        }

    }

    if (isset($_GET['service']) && !empty($_GET['service'])) {

        $_SESSION['service'] = $_GET['service'];

    }else{

    if(isset($_SESSION['service'])){

        unset($_SESSION['service']);

    }

    }



}

$sessionURL = null;

if (isset($_SESSION['status']) && !empty($_SESSION['status'])) {

    $sessionURL .= "?status=".$_SESSION['status'];

}



if (isset($_SESSION['service']) && !empty($_SESSION['service'])) {

    if(!empty($sessionURL)){

        $sessionURL .= "&service=".$_SESSION['service'];

    }else{

        $sessionURL .= "?service=".$_SESSION['service'];

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



    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">



    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />



    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap">



    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">



    <link href="../iconpicker/dist/css/bootstrapicons-iconpicker.css" rel="stylesheet" />



    <link href="assets/select2/dist/css/select2.min.css" rel="stylesheet" />







    <link rel="stylesheet" href="assets/calendar/css/style.css">



    <link rel="stylesheet" href="assets/css/style.css">



    <link rel="stylesheet" href="assets/css/responsive.css">



    <link rel="stylesheet" href="assets/summernote/summernote-bs4.min.css">







    <link rel="icon" type="image/x-icon" href="../assets/images/favicon.png">



    <title>Recway - Portal</title>



</head>



<style>



    div#dataTable_wrapper {



        padding: 30px !important;



    }







    span a.paginate_button {



        border-radius: 50% !important;



        padding: 1em 1em !important;



    }







    .custom_hide {



        display: none !important;



    }







    table.dataTable.stripe>tbody>tr.odd>*,



    table.dataTable.display>tbody>tr.odd>* {



        box-shadow: unset;



    }







    .select2-container {



        width: 100% !important;



    }



    .has-dot {

        position: relative;

        display: inline-block;

    }



    .has-dot::after {

        content: "";

        position: absolute;

        top: 7px;

        right: 6px;

        width: 8px;

        height: 8px;

        background: #ff0000;

        border-radius: 50%;

    }





    /* .dropdown-menu {



position: fixed !important;



transition: none !important;



inset: none !important;



} */



</style>







<body>



    <div class="p-0 m-0 w-100">



        <div class="sidebar-section">



            <div class="sidebar">



                <div class="logo-section">



                    <img src="assets/images/logo.png" alt="" class="img-fluid">



                </div>



                <ul class="nav-links">



                    <li class="<?php echo isset($activeLink) && $activeLink == 'dashboard' ? 'active' : '' ?>">



                        <a href="index.php<?=$sessionURL?>">



                            <i class='bx bx-grid-alt'></i>



                            <span class="link_name">Staff Dashboard</span>



                        </a>



                    </li>



                    <?php if (isset($allowed_staff_permission['view_customer']) && !empty($allowed_staff_permission['view_customer'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'customers' ? 'active' : '' ?>">



                            <a href="customers.php">



                                <i class="bx bx-user"></i>



                                <span class="link_name">Customers</span>



                            </a>



                        </li>



                    <?php } ?>



                    <?php if (isset($allowed_staff_permission['view_own_candidate']) || isset($allowed_staff_permission['view_all_candidate'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'candidates' ? 'active' : '' ?>">



                            <a href="candidates.php<?=$sessionURL?>">



                                <i class="bx bx-collection"></i>



                                <span class="link_name">Candidates</span>



                            </a>



                        </li>



                    <?php } ?>



                    <?php if (isset($allowed_staff_permission['view_status']) && !empty($allowed_staff_permission['view_status'])) { ?>



                        <li class="dropdown-li <?php echo isset($activeLink) && $activeLink == 'statuses' ? 'active' : '' ?>">



                            <div class="iocn-link">



                                <a href="#">



                                    <i class="bi bi-clipboard-data"></i>



                                    <span class="link_name">Statuses</span>



                                </a>



                                <i class='bx bxs-chevron-down arrow'></i>



                            </div>



                            <ul class="sub-menu">



                                <?php if (!empty($services)) : ?>



                                    <?php foreach ($services as $key => $service) : ?>



                                        <li class="<?php echo $key == 0 ? 'mt-3' : '' ?>">



                                            <a href="statuses.php?id=<?php echo $service->id ?>">



                                                <?php echo $service->name  ?>



                                            </a>



                                        </li>



                                    <?php endforeach; ?>



                                <?php endif; ?>



                            </ul>



                        </li>



                    <?php } ?>



                    <?php if (isset($allowed_staff_permission['view_service']) && !empty($allowed_staff_permission['view_service'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'services' ? 'active' : '' ?>">



                            <a href="services.php">



                                <i class="bi bi-card-checklist"></i>



                                <span class="link_name">Services</span>



                            </a>



                        </li>



                    <?php } ?>

                    <?php if (isset($allowed_staff_permission['view_statistics']) && !empty($allowed_staff_permission['view_statistics'])) { ?>

                    <li class="<?php echo isset($activeLink) && $activeLink == 'analytics' ? 'active' : '' ?>">

                        <a href="analytics.php">

                            <i class="bi bi-graph-up-arrow"></i>

                            <span class="link_name">Statistics</span>

                        </a>

                    </li>

                    <?php } ?>

                    <?php if (isset($allowed_staff_permission['view_place']) && !empty($allowed_staff_permission['view_place'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'places' ? 'active' : '' ?>">



                            <a href="places.php">



                                <i class="bi bi-geo-alt"></i>



                                <span class="link_name">Places</span>

 

                            </a>



                        </li>



                    <?php } ?>



                    <?php if (isset($allowed_staff_permission['view_message']) && !empty($allowed_staff_permission['view_message'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'messages' ? 'active' : '' ?>">



                            <a href="messages.php">



                                <i class="bi bi-chat-dots"></i>



                                <span class="link_name">Messages</span>



                            </a>



                        </li>



                    <?php } ?>



                    <li class="<?php echo isset($activeLink) && $activeLink == 'profile' ? 'active' : '' ?>">



                        <a href="profile.php?id=<?php echo $_SESSION['staff']->id ?>">



                            <i class="bi bi-person-gear"></i>



                            <span class="link_name">Account Settings</span>



                        </a>



                    </li>



                    <?php if (isset($allowed_staff_permission['view_site_setting']) && !empty($allowed_staff_permission['view_site_setting'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'settings' ? 'active' : '' ?>">



                            <a href="settings.php">



                                <i class="bi bi-chat-dots"></i>



                                <span class="link_name">Site Settings</span>



                            </a>



                        </li>



                    <?php } ?>



                    <?php if (isset($allowed_staff_permission['view_documentation']) && !empty($allowed_staff_permission['view_documentation'])) { ?>



                        <li class="<?php echo isset($activeLink) && $activeLink == 'documentation' ? 'active' : '' ?>">



                            <a href="documentation.php">



                                <i class="bi bi-chat-dots"></i>



                                <span class="link_name">Documentation</span>



                            </a>



                        </li>



                    <?php } ?>



                </ul>



            </div>



        </div>



        <div class="main-section ms-auto">



            <header class="header">



                <div class="d-flex justify-content-between flex-wrap">



                    <div class="menu-btn order-1">



                        <i class="bi bi-list"></i>



                    </div>



                    <div class="order-md-2 order-3 d-flex align-items-center desktop-menu">



                        <div class="text-white f-20 me-3 menu-lg" style="cursor: pointer;">



                            <i class="bi bi-list"></i>



                        </div>



                        <!--                        <div class="search-section order-md-2 order-3 ">-->



                        <!--                            <i class="bi bi-search search-input"></i>-->



                        <!--                            <input type="search" placeholder="Search here...">-->



                        <!--                        </div>-->



                    </div>



                    <div class="d-flex justify-content-end align-content-center order-md-3 order-2">



                        <!--                        <div class="languages">-->



                        <!--                            <img src="assets/images/Flag_of_Sweden.svg.png" alt="" class="img-fluid non-active">-->



                        <!--                            <img src="assets/images/US.webp" alt="" class="img-fluid active">-->



                        <!--                        </div>-->



                        <?php



if (isset($_GET['service']) && $_GET['service'] != 'all') {

    $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$_GET['service']]);

    $services = $stmt->fetchAll();

    $services = array_column($services, 'id');

    $services = implode(",", $services);

}



$query = 'SELECT * FROM candidates WHERE expired = 0' . $candidates_addition_query;



if (isset($_GET['service']) && $_GET['service'] != 'all') {

    $query .= " AND interview_id IN (" . $services . ")";

}



$query .= "  ORDER BY CASE

WHEN booked IS NULL OR booked = '' THEN 1  -- Places empty interview dates at the end

ELSE 0

END, booked ASC";

$stmt = $conn->prepare($query);

$stmt->execute();

$candidates = $stmt->fetchAll();





if (!empty($candidates)) {

    foreach ($candidates as $row) {

        $staffId = $_SESSION['staff']->id;



        $query = "

SELECT comments.*, candidates.order_id as can_id

FROM comments

INNER JOIN candidates ON candidates.id = comments.order_id

WHERE comments.author_id != :staff_id

AND comments.order_id = :order_id

GROUP BY comments.order_id

";



        $stmt2 = $conn->prepare($query);

        $stmt2->execute([

            ':staff_id' => $staffId,

            ':order_id' => $row->id

        ]);



        $fetched = $stmt2->fetchAll();



        if (!empty($fetched)) {

            $comment = $fetched[0];



            $readBy = array_filter(array_map('trim', explode(',', $comment->read_by_staff ?? '')));



            if (!in_array($staffId, $readBy)) {

                $comments[] = $comment;

            }

        }

    }

}





?>

<div class="profile-img p-2 mr-3 <?php if (!empty($comments)) { ?> has-dot <?php } ?>">

    <span class="fas fa-bell f-20 text-white"></span>



    <div class="tool-pit" style="width: 250px;padding: 20px 0px 20px 20px;">

        <div class="tool-pit-content">

            <div class="d-flex justify-content-end">

                <div class="arrow-up me-3" style="top: 5px;right: -4px;"></div>

            </div>

            <ul class="menus" id="comment-menus" style="padding: 0px 10px 4px 10px !important;font-size: small;height: 266px;overflow-y: scroll;">

            <?php if (!empty($comments)) { ?>

                <?php foreach ($comments as $key => $row) { ?>

                                                <li style="border-left: 4px solid #33b5e5;margin-bottom: 2px;" id="<?=$row->order_id?>-comment">                           

                                                     <a style="font-size: small;width:100%;display: inline-block;" href="invoice.php?sno=<?= $key + 1 ?>&id=<?php echo $row->order_id ?><?php echo isset($_GET['status']) ? '&status=' . $_GET['status'] : '' ?>" class="no-decoration text-black open-candidate " data-sno="<?= $key + 1 ?>" data-id="<?php echo $row->order_id ?>" data-status="<?php echo $_GET['status'] ?? '' ?>">

                            <b><?= $row->can_id ?></b> Comment has been

                            added by <?= $row->author_type ?></a></li>

                                            <?php } ?>

                <?php } else { ?>

                    <p class="no-comments text-muted m-2">No comments found</p>

                <?php } ?>

            </ul>



        </div>

    </div>

</div>



                        <div class="me-2" style="margin-top: 7px">



                            <div id="google_translate_element2"></div>







                            <a href="#" id="lang-en" onclick="doGTranslate('sv|en');return false;" title="English" class="gflag nturl me-2" style="background-position:-0px -0px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="English" /></a>



                            <a href="#" id="lang-sv" onclick="doGTranslate('en|sv');return false;" title="Swedish" class="gflag nturl" style="background-position:-700px -200px;"><img src="//gtranslate.net/flags/blank.png" height="16" width="16" alt="Swedish" /></a>



                        </div>







                        <div class="profile-img">



                            <img src="assets/images/219983.png" alt="">







                            <div class="tool-pit">



                                <div class="tool-pit-content">



                                    <div class="d-flex justify-content-end">



                                        <div class="arrow-up me-3"></div>



                                    </div>



                                    <div class="tool-pit-content--header">



                                        <p class="title mb-0 pb-0">Staff</p>



                                        <h1 class="name mt-2 mb-0 pb-0"><?php echo $_SESSION['staff']->name ?></h1>



                                    </div>







                                    <ul class=" menus">



                                        <li><a href="profile.php?id=<?php echo $_SESSION['staff']->id ?>">Account Settings</a></li>



                                        <li><a href="logout.php">Logout</a></li>







                                        <hr>



                                    </ul>







                                </div>



                            </div>



                        </div>



                    </div>



                </div>



            </header>