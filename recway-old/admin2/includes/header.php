<?php
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);
//error_reporting(E_ALL);
// Set headers to prevent caching
header("Cache-Control: no-cache, must-revalidate"); // HTTP 1.1
header("Pragma: no-cache"); // HTTP 1.0
header("Expires: 0"); // Proxies
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
function getneworderscount($id)
{
    global $conn;
    $query = 'SELECT COUNT(candidates.id) as new_orders 
              FROM statuses 
              LEFT JOIN service_categories ON service_categories.id = statuses.status_type 
              LEFT JOIN candidates ON candidates.status = statuses.id 
              WHERE statuses.status LIKE "New Order" 
              AND statuses.status_type = ? 
              AND expired = 0';
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}
function status_count($status)
{
    global $conn;
    $query = 'SELECT COUNT(id) as order_count FROM candidates WHERE status = ? AND expired = 0';
    $stmt = $conn->prepare($query);
    $stmt->execute([$status]);
    $count = $stmt->fetch();
    return $count->order_count;
}
$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
$current_page = basename($_SERVER['PHP_SELF']);
if ($current_page == 'candidates.php' || $current_page == 'index.php') {
    if (isset($_GET['status']) && ! empty($_GET['status'])) {
        $_SESSION['status'] = $_GET['status'];
    } else {
        if (isset($_SESSION['status'])) {
            unset($_SESSION['status']);
        }
    }
    if (isset($_GET['service']) && ! empty($_GET['service'])) {
        $_SESSION['service'] = $_GET['service'];
    } else {
        if (isset($_SESSION['service'])) {
            unset($_SESSION['service']);
        }
    }
}
$sessionURL = null;
if (isset($_SESSION['status']) && ! empty($_SESSION['status'])) {
    $sessionURL .= "?status=".$_SESSION['status'];
}
if (isset($_SESSION['service']) && ! empty($_SESSION['service'])) {
    if (! empty($sessionURL)) {
        $sessionURL .= "&service=".$_SESSION['service'];
    } else {
        $sessionURL .= "?service=".$_SESSION['service'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex">
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

	/* Background Check Alert Icon - Red Blinking Circle */
	.bg-check-alert-icon {
		display: inline-block;
		width: 12px;
		height: 12px;
		background-color: #ff4444;
		border-radius: 50%;
		margin-left: 5px;
		cursor: pointer;
		vertical-align: middle;
		position: relative;
	}
	
	/* Blinking Animation */
	@keyframes blink {
		0%, 100% { 
			opacity: 1; 
			transform: scale(1);
		}
		50% { 
			opacity: 0.3; 
			transform: scale(0.9);
		}
	}
	
	.blink-alert {
		animation: blink 1.5s infinite;
	}
	.tooltip-inner {
        max-width: 230px !important;
        text-align: left !important;
    }
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
							<span class="link_name">Dashboard</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'admins' ? 'active' : '' ?>">
						<a href="admins.php">
							<i class="bi bi-person"></i>
							<span class="link_name">Admins</span>
						</a>
					</li>
					<li class="dropdown-li <?php echo isset($activeLink) && $activeLink == 'staff' ? 'active' : '' ?><?php echo isset($activeLink) && $activeLink == 'staff_category' ? 'active' : '' ?><?php echo isset($activeLink) && $activeLink == 'staff-email' ? 'active' : '' ?>">
						<div class="iocn-link">
							<a href="#">
								<i class="bi bi-people"></i>
								<span class="link_name">Staff</span>
							</a>
							<i class='bx bxs-chevron-down arrow'></i>
						</div>
						<ul class="sub-menu">
							<li class="mt-3">
								<a href="staff.php">
									All Staff
								</a>
							</li>
							<li>
								<a href="staff_category.php">
									Staff Category
								</a>
							</li>
							<li>
								<a href="staff-email.php">
									Staff Email
								</a>
							</li>
							<!-- <li>
                                <a href="staff_category_permissions.php">
                                    Staff Permissions
                                </a>
                            </li> -->
						</ul>
					</li>
					<!-- <li class="<?php echo isset($activeLink) && $activeLink == 'staff' ? 'active' : '' ?>">
                        <a href="staff.php">
                            <i class="bi bi-people"></i>
                            <span class="link_name">Staff</span>
                        </a>
                    </li> -->
					<li class="<?php echo isset($activeLink) && $activeLink == 'customers' ? 'active' : '' ?>">
						<a href="customers.php">
							<i class="bx bx-user"></i>
							<span class="link_name">Customers</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'customer-invoice' ? 'active' : '' ?>">
						<a href="customer-invoice.php">
							<i class="bi bi-receipt"></i>
							<span class="link_name">Customer Invoice</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'candidates' ? 'active' : '' ?>">
						<a href="candidates.php<?=$sessionURL?>">
							<i class="bx bx-collection"></i>
							<span class="link_name">Candidates</span>
						</a>
					</li>
					<li class="dropdown-li <?php echo isset($activeLink) && $activeLink == 'statuses' ? 'active' : '' ?>">
						<div class="iocn-link">
							<a href="#">
								<i class="bi bi-clipboard-data"></i>
								<span class="link_name">Statuses</span>
							</a>
							<i class='bx bxs-chevron-down arrow'></i>
						</div>
						<ul class="sub-menu">
							<?php if (! empty($services)) : ?>
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
					<li class="dropdown-li 
					<?php echo isset($activeLink) && $activeLink == 'services' ? 'active' : '' ?>
					<?php echo isset($activeLink) && $activeLink == 'news-reports' ? 'active' : '' ?>">
						<div class="iocn-link">
							<a href="#">
								<i class="bi bi-card-checklist"></i>
								<span class="link_name">Services</span>
							</a>
							<i class='bx bxs-chevron-down arrow'></i>
						</div>
						<ul class="sub-menu">
								<li class="mt-3">
										<a href="services.php">
												Services
										</a>
								</li>
								<li>
										<a href="news-reports.php">
												News & Reports
										</a>
								</li>
						</ul>
					</li>


					<!-- <li class="<?php echo isset($activeLink) && $activeLink == 'services' ? 'active' : '' ?>">
						<a href="services.php">
							<i class="bi bi-card-checklist"></i>
							<span class="link_name">Services</span>
						</a>
					</li> -->
					<li class="<?php echo isset($activeLink) && $activeLink == 'analytics' ? 'active' : '' ?>">
						<a href="analytics.php">
							<i class="bi bi-graph-up-arrow"></i>
							<span class="link_name">Statistics</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'history' ? 'active' : '' ?>">
						<a href="history.php">
							<i class="bi bi-clock-history"></i>
							<span class="link_name">History</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'email_logs' ? 'active' : '' ?>">
						<a href="email_logs.php">
							<i class="bi bi-clock-history"></i>
							<span class="link_name">Email Logs</span>	
						</a>
					</li>
					<!--                    <li>-->
					<!--                        <a href="#">-->
					<!--                            <i class="bi bi-list-check"></i>-->
					<!--                            <span class="link_name">Logs</span>-->
					<!--                        </a>-->
					<!--                    </li>-->
					<li class="<?php echo isset($activeLink) && $activeLink == 'places' ? 'active' : '' ?>">
						<a href="places.php">
							<i class="bi bi-geo-alt"></i>
							<span class="link_name">Places</span>
						</a>
					</li>
					<li class="dropdown-li 
					<?php echo isset($activeLink) && $activeLink == 'messages' ? 'active' : '' ?>
					<?php echo isset($activeLink) && $activeLink == 'custom_message' ? 'active' : '' ?>">
						<div class="iocn-link">
							<a href="#">
								<i class="bi bi-chat-dots"></i>
								<span class="link_name">Messages</span>
							</a>
							<i class='bx bxs-chevron-down arrow'></i>
						</div>
						<ul class="sub-menu">
								<li class="mt-3">
										<a href="messages.php">
												Message
										</a>
								</li>
								<li>
										<a href="custom-message.php">
												Custom Message
										</a>
								</li>
						</ul>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'faqs' ? 'active' : '' ?>">
						<a href="faqs.php">
							<i class="bi bi-question-square"></i>
							<span class="link_name">FAQs</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'profile' ? 'active' : '' ?>">
						<a href="profile.php?id=<?php echo $_SESSION['admin']->id ?>">
							<i class="bi bi-person-gear"></i>
							<span class="link_name">Account Settings</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'updates' ? 'active' : '' ?>">
						<a href="updates.php">
							<i class="bi bi-arrow-up-circle"></i>
							<span class="link_name">Updates</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'settings' ? 'active' : '' ?>">
						<a href="settings.php">
							<i class="bi bi-chat-dots"></i>
							<span class="link_name">Site Settings</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'customer-language' ? 'active' : '' ?>">
						<a href="customer-language.php">
							<i class="bi bi-card-checklist"></i>
							<span class="link_name">Language</span>
						</a>
					</li>
					<li class="<?php echo isset($activeLink) && $activeLink == 'documentation' ? 'active' : '' ?>">
						<a href="documentation.php">
							<i class="bi bi-chat-dots"></i>
							<span class="link_name">Documentation</span>
						</a>
					</li>
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
$query = 'SELECT id FROM candidates WHERE expired = 0';
if (isset($_GET['service']) && $_GET['service'] != 'all') {
    $query .= " AND interview_id IN (" . $services . ")";
}
$query .= " ORDER BY CASE
    WHEN booked IS NULL OR booked = NULL THEN 1  -- Places empty interview dates at the end
    ELSE 0 END, booked ASC";
$stmt = $conn->prepare($query);
$stmt->execute();
$candidates = $stmt->fetchAll();
$comments = [];
if (! empty($candidates)) {
    foreach ($candidates as $row) {
        $adminId = $_SESSION['admin']->id;
        $query = "
                                    SELECT comments.*, candidates.order_id as can_id
                                    FROM comments
                                    INNER JOIN candidates ON candidates.id = comments.order_id
                                    WHERE comments.author_id != :admin_id
                                      AND comments.order_id = :order_id
                                    GROUP BY comments.order_id
                                ";
        $stmt2 = $conn->prepare($query);
        $stmt2->execute([
            ':admin_id' => $adminId,
            ':order_id' => $row->id,
        ]);
        $fetched = $stmt2->fetchAll();
        if (! empty($fetched)) {
            $comment = $fetched[0];
            $readBy = array_filter(array_map('trim', explode(',', $comment->read_by_admin ?? '')));
            if (! in_array($adminId, $readBy)) {
                $comments[] = $comment;
            }
        }
    }
}
?>
						<div class="profile-img p-2 mr-3 <?php if (! empty($comments)) { ?> has-dot <?php } ?>">
							<span class="fas fa-bell f-20 text-white"></span>
							<div class="tool-pit" style="width: 250px;padding: 20px 0px 20px 20px;">
								<div class="tool-pit-content">
									<div class="d-flex justify-content-end">
										<div class="arrow-up me-3" style="top: 5px;right: -4px;"></div>
									</div>
									<ul class="menus" id="comment-menus" style="padding: 0px 10px 4px 10px !important;font-size: small;height: 266px;overflow-y: scroll;">
										<?php if (! empty($comments)) { ?>
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
										<p class="title mb-0 pb-0">Admin</p>
										<h1 class="name mt-2 mb-0 pb-0"><?php echo $_SESSION['admin']->name ?></h1>
									</div>
									<ul class=" menus">
										<li><a href="profile.php?id=<?php echo $_SESSION['admin']->id ?>">Account Settings</a></li>
										<li><a href="logout.php">Logout</a></li>
										<hr>
										<li><a href="add-admin.php"> <i class="bx bx-user me-3"></i>Add New Admin</a></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</header>