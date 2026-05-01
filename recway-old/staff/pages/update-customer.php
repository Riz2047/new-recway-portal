<?php
$activeLink = "customers";
include_once('../../includes/functions.php');
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
$query = 'SELECT * FROM additional_customers WHERE cus_id = ' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$add_cus = $stmt->fetchAll();
$query = 'SELECT * FROM customer_services WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($customer_services, 'service_id');
if (isset($_POST['update_customer'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $cost_place = $_POST['cost_place'];
    $statuses = $_POST['statuses'];
    $invoice_period = isset($_POST['invoice_period']) && $_POST['invoice_period'] !== '' ? $_POST['invoice_period'] : $customer->invoice_period;
    $remainder_email_template = $_POST['remainder_email_template'];
    $statusStr = "";
    $services2 = $_POST['services'] ?? [];
    $send_report = $_POST['send_report'];
    $permissions = $_POST['permissions'] ?? [];
    $combine_bk_and_security = ! empty($_POST['combine_bk_and_security']) ? $_POST['combine_bk_and_security'] : 0;
    // Handle combine services - now received as comma-separated string
    $combine_bk_and_security = isset($_POST['combine_bk_and_security']) ? $_POST['combine_bk_and_security'] : '';
    // Handle combine statuses - now received as comma-separated string
    $combine_status = isset($_POST['combine_status']) ? $_POST['combine_status'] : '';
    $combine_interview_id = isset($_POST['combine_interview_id']) ? $_POST['combine_interview_id'] : 0;

    if (! empty($statuses)) {
        foreach ($statuses as $key => $status) {
            if ($key != count($statuses) - 1) {
                $statusStr = $statusStr . $status . ",";
            } else {
                $statusStr = $statusStr . $status;
            }
        }
    }
    $query = 'UPDATE customers SET name = ?, email = ?, phone = ?, company = ?, cost_place = ?, statuses = ?, send_security_report = ?, remainder_email_template,combine_bk_and_security = ?, combine_status = ?,combine_interview_id = ?, invoice_period = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $phone, $company, $cost_place, $statusStr, $send_report, $remainder_email_template, $combine_bk_and_security, $combine_status, $combine_interview_id, $invoice_period, $_GET['id']]);
    $query = 'DELETE FROM user_allowed_permissions WHERE user_id = ? AND user_type = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['id'], 2]);
    if (! empty($permissions)) {
        foreach ($permissions as $pers) {
            $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$pers, $_GET['id'], 2]);
        }
    }
    if (! empty($res)) {
        $excludeServices = array_diff(array_column($services, "id"), $services2);
        $includeServices = array_diff($services2, $allowed_services);
        if (! empty($excludeServices)) {
            foreach ($excludeServices as $excludeService) {
                $query = 'DELETE from customer_services WHERE cus_id = ? AND service_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_GET['id'], $excludeService]);
            }
        }
        if (! empty($includeServices)) {
            foreach ($includeServices as $includeService) {
                $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_GET['id'], $includeService]);
            }
        }
        flash("customerUpdated", "Customer updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("customerUpdated", "Could not update customer!");
    }
}
$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();
if (! empty($customer)) {
    $cusStatuses = explode(',', $customer->statuses);
}
$statuses = getStatuses();
$query = 'SELECT * FROM customer_services WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($customer_services, 'service_id');
if (isset($_POST['resend'])) {
    $count = $_POST['count'];
    $user_type = $_POST['user_type'][$_POST['resend']];
    $order_id = $_POST['order_id'][$_POST['resend']];
    $msg_type = $_POST['msg_type'][$_POST['resend']];
    $email = $_POST['email'][$_POST['resend']];
    $name = $_POST['name'][$_POST['resend']];
    $text = $_POST['text'][$_POST['resend']];
    $subject = $_POST['subject'][$_POST['resend']];
    saveEmail($user_type, $name, $order_id, $msg_type, $text, $email, $subject);
    $emailMsg = sendMail($text, $email, $name, $subject);
}
$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$customer->email]);
$emails = $stmt->fetchAll();
$query = 'SELECT * FROM candidates WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidates = $stmt->fetchAll();
$query = 'SELECT * FROM candidates WHERE cus_id = ? AND invoice_sent = 1';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$invoicedCandidates = $stmt->fetchAll();
$keys = array_column($statuses, "id");
$values = array_column($statuses, "variable");
$statuses2 = array_combine($keys, $values);
$data = [];
foreach ($statuses2 as $key => $status) {
    $data[$status] = 0;
}
if (! empty($candidates)) {
    foreach ($candidates as $candidate) {
        $data[$statuses2[$candidate->status]] += 1;
    }
}
$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();
$msgCols = getMsgColsByService($servicesCats[0]->id);
$msgCols = array_column($msgCols, "msg_col");
$msgCols = implode(",", $msgCols);
$query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id'], $servicesCats[0]->id]);
$messages = $stmt->fetch();
$query = "SELECT
    c.id,
    c.cus_id,
    COUNT(c.id) AS total_orders,
    COUNT(CASE WHEN s.variable = 'approved' THEN 1 END) AS total_approved,
    COUNT(CASE WHEN s.variable = 'canceled' THEN 1 END) AS total_canceled,
    COUNT(CASE WHEN c.invoice_sent = 1 THEN 1 END) AS total_invoiced
FROM
    candidates c
LEFT JOIN
    statuses s ON c.status = s.id
WHERE
    c.cus_id = ?
GROUP BY
    c.cus_id;
";
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$orders = $stmt->fetch();
if (isset($_GET['id']) && ! empty($_GET['id'])) {
    $query = null;
    $department = findAllByQuery("SELECT * FROM departments WHERE dep_cus_id = {$_GET['id']} AND dep_trash = 0 ORDER BY dep_name DESC");
    $department_users = findAllByQuery("SELECT * FROM customers LEFT JOIN departments ON customers.dep_id = departments.dep_id WHERE customers.parent_id = {$_GET['id']}");
    $dep_services = findAllByQuery('SELECT * FROM interviews LEFT JOIN customer_services ON interviews.id = customer_services.service_id WHERE customer_services.cus_id = ' . $_GET['id']);
}
$permissions = findallByQuery("SELECT * FROM user_permissions WHERE user_type != 3");
$user_permissions = findallByQuery("SELECT * FROM user_permissions JOIN user_allowed_permissions ON user_permissions.id = user_allowed_permissions.per_id WHERE user_allowed_permissions.user_id = {$_GET['id']} AND user_allowed_permissions.user_type = 2 AND user_permissions.user_type = 1");
$allow_permissions = findallByQuery("SELECT * FROM user_allowed_permissions WHERE user_id = {$_GET['id']} AND user_type = 2");
$user_allow_permissions = findallByQuery("SELECT * FROM user_allowed_permissions WHERE user_id = {$_GET['id']} AND user_type = 2");
$customer_question = findallByQuery("SELECT * FROM customer_question WHERE cus_id = {$_GET['id']}");
$groups = findallByQuery("SELECT * FROM `groups`");
if (empty($customer_question)) {
    $customer_question = findallByQuery("SELECT * FROM customer_question WHERE cus_id = 0");
}
if (! empty($customer_question)) {
    $customer_meta_data = json_decode($customer_question[0]->meta_data, true);
}
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$all_services = $stmt->fetchAll();
$cus_services = findAllByQuery('SELECT * from interviews LEFT JOIN customer_services ON interviews.id = customer_services.service_id WHERE cus_id = ' . $_GET['id'] . ' GROUP BY id');
$query = 'SELECT form FROM order_forms WHERE cus_id =' . $_GET["id"] . ' AND service_id = 1';
$stmt = $conn->prepare($query);
$stmt->execute();
$default_form = $stmt->fetch();
$parent_customer = findallByQuery("SELECT * FROM customers");
$query = "SELECT DISTINCT company 
          FROM customers
          WHERE company IS NOT NULL
          ORDER BY company";
$stmt = $conn->prepare($query);
$stmt->execute();
$manager_companies = $stmt->fetchAll(PDO::FETCH_ASSOC);
$cus_id = $_GET['id'];
$query = "SELECT *
          FROM company_manager WHERE cus_id = '$cus_id'";
$stmt = $conn->prepare($query);
$stmt->execute();
$selected_manger = $stmt->fetch(PDO::FETCH_ASSOC);
// Fetch active customers from company_manager with the same company as current customer
$same_company_customers = [];
if (! empty($customer) && ! empty($customer->company)) {
    $company_name = $customer->company;
    // Get all cus_id from company_manager with the same company
    $query = "SELECT cus_id FROM company_manager WHERE company = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$company_name]);
    $company_manager_cus_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // Fetch customer details for those IDs
    if (! empty($company_manager_cus_ids)) {
        $placeholders = implode(',', array_fill(0, count($company_manager_cus_ids), '?'));
        $query = "SELECT * FROM customers WHERE id IN ($placeholders) ORDER BY name ASC";
        $stmt = $conn->prepare($query);
        $stmt->execute($company_manager_cus_ids);
        $same_company_customers = $stmt->fetchAll();
    }
}
// custom email templates start ***********
$query = "SELECT * FROM custom_email_template";
$stmt = $conn->prepare($query);
$stmt->execute();
$custom_email_template = $stmt->fetchAll(PDO::FETCH_ASSOC);
// standard billing details start ***********
$standard_pref = "";
$standard_ref = "";
$standard_comment = "";
$query = "SELECT * FROM standard_billing_details WHERE cus_id = '$cus_id'";
$stmt = $conn->prepare($query);
$stmt->execute();
$standard_billing = $stmt->fetch(PDO::FETCH_ASSOC);
if (! empty($standard_billing)) {
    if (! empty($standard_billing['referenceperson'])) {
        $standard_pref = $standard_billing['referenceperson'];
    }
    if (! empty($standard_billing['reference'])) {
        $standard_ref = $standard_billing['reference'];
    }
    if (! empty($standard_billing['comment'])) {
        $standard_comment = $standard_billing['comment'];
    }
}
?>
<style>
    .select2-container--open {
        z-index: 10001 !important;
    }
</style>
<div class="mx-lg-4 main-content mt-2">
    <div class="container">
        <div class="row justify-content-center ">
            <div class="col-lg-12 mb-3 d-flex justify-content-between">
                <div class="profile-img">
                    <div class="tool-pit tool-pit2">
                        <div class="tool-pit-content">
                            <div class="d-flex justify-content-end">
                                <div class="arrow-up me-3"></div>
                            </div>
                            <div class="tool-pit-content--header">
                                <!-- <a href="" class="no-decoration text-white">Change Status</a> -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-12 px-lg-0 mb-lg-0 mb-3 ">
                <div class="white-box-p-0 h-100">
                    <div class="tab">
                        <button class="tablinks f-14 w-700 " id="defaultOpen" onclick="openCity(event, 'profile')">Profile</button>
                        <button class="tablinks f-14 w-700 " id="defaultOpen" onclick="openCity(event, 'edit')">Edit Customer</button>
                        <button class="tablinks f-14 w-700 " id="departmentsOpen" onclick="openCity(event, 'departments')">Departments</button>
                        <button class="tablinks f-14 w-700 " id="departmentUsersOpen" onclick="openCity(event, 'department_users')">Department Users</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'orders')">Orders</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'emails')">Emails</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'messages')">Messages</button>
                        <button class="tablinks f-14 w-700 totalInvoicedBtn" onclick="openCity(event, 'invoiced')">Invoiced</button>
                        <button class="tablinks f-14 w-700" onclick="openCity(event, 'background-qs')">Background Questions</button>
                        <button class="tablinks f-12 w-700" onclick="openCity(event, 'form_builder')">Form Builder</button>
                        <button class="tablinks f-12 w-700" id="addCuOpen" onclick="openCity(event, 'additional_customers')">Additional Customers</button>
                    </div>
                    <div id="profile" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-6 order-lg-1 order-2">
                                    <div class="mt-3 ">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 ">
                                            Email</p>
                                        <p class="f-14 w-700 text-black up_ssn">
                                            <?php echo $customer->email ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0 ">
                                            Phone</p>
                                        <p class="f-14 w-700 text-black up_vasc_id"><?php echo $customer->phone ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Company</p>
                                        <p class="f-14 w-700 text-black up_interview_date"><?php echo $customer->company ?></p>
                                    </div>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Cost Place</p>
                                        <p class="f-14 w-700 text-black up_interview_date"><?php echo $customer->cost_place ?></p>
                                    </div>
                                </div>
                                <div class="col-lg-6 order-lg-2 order-1">
                                    <div class="candidate-profile mx-auto">
                                        <?php
                                        // Split the full name into an array of words
                                        $names = explode(" ", $customer->name);
// Get the first letter of the first name using mb_substr
$first_name_initial = mb_substr($names[0], 0, 1, 'UTF-8');
// Check if there is a last name and get its first letter using mb_substr
if (count($names) > 1) {
    $last_name_initial = mb_substr(end($names), 0, 1, 'UTF-8');
} else {
    $last_name_initial = ""; // If there's no last name
}
?>
                                        <h1 class="f-26 w-700 text-white m-0 p-0 font-secondary"><?php echo $first_name_initial . $last_name_initial ?></h1>
                                    </div>
                                    <div class="candidate-info ">
                                        <h1 class="f-16 w-700 text-black m-0 p-0 mt-2 text-center up_name"><?php echo $customer->name  ?>
                                        </h1>
                                        <div class="status-active px-3 py-1 f-18 my-2 mx-auto" style="background-color: #4C6EF8">Total Orders <?php echo ! empty($orders) ? $orders->total_orders : 0 ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mt-2 p-2">
                                <div class=" col-lg-4 col-md-6 mt-3">
                                    <a style="text-decoration: none;" class="totalInvoiced">
                                        <div class="total-card shadow2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column align-items-start">
                                                    <h1 class="text-lg">Total Invoiced</h1>
                                                    <h1 class="text-numer mb-0 pb-0"><?php echo ! empty($orders) ? $orders->total_invoiced : 0 ?></h1>
                                                </div>
                                                <div class="icon-card">
                                                    <i class="bi bi-clipboard-data"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class=" col-lg-4 col-md-6 mt-3">
                                    <a style="text-decoration: none;">
                                        <div class="total-card shadow2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column align-items-start">
                                                    <h1 class="text-lg">Total Approved</h1>
                                                    <h1 class="text-numer mb-0 pb-0"><?php echo ! empty($orders) ? $orders->total_approved : 0 ?></h1>
                                                </div>
                                                <div class="icon-card">
                                                    <i class="bi bi-clipboard-check"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class=" col-lg-4 col-md-6 mt-3">
                                    <a style="text-decoration: none;">
                                        <div class="total-card shadow2">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column align-items-start">
                                                    <h1 class="text-lg">Total Canceled</h1>
                                                    <h1 class="text-numer mb-0 pb-0"><?php echo ! empty($orders) ? $orders->total_canceled : 0 ?></h1>
                                                </div>
                                                <div class="icon-card">
                                                    <i class="bi bi-clipboard-x"></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Company Managers</h1>
                                        </div>
                                        <?php if (! empty($customer) && ! empty($customer->company)) { ?>
                                            <p class="text-muted mb-3">Active customers with company: <strong><?php echo htmlspecialchars($customer->company); ?></strong></p>
                                            <table class="table table-bordered">
                                                <thead>
                                                    <tr>
                                                        <th><b>Name</b></th>
                                                        <th><b>Email</b></th>
                                                        <th><b>Phone</b></th>
                                                        <th><b>Actions</b></th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (! empty($same_company_customers)) { ?>
                                                        <?php foreach ($same_company_customers as $sameCompanyCustomer) { ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($sameCompanyCustomer->name); ?></td>
                                                                <td><?php echo htmlspecialchars($sameCompanyCustomer->email); ?></td>
                                                                <td><?php echo htmlspecialchars($sameCompanyCustomer->phone ?? 'N/A'); ?></td>
                                                                <td style="width:6% !important">
                                                                    <a href="update-customer.php?id=<?php echo $sameCompanyCustomer->id; ?>"
                                                                        class="btn btn-sm btn-outline-primary">
                                                                        <i class="bi bi-eye"></i> View
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <tr class="no_record_found">
                                                            <td colspan="4" class="text-center">
                                                                <h5>No other customers found with the same company</h5>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                </tbody>
                                            </table>
                                        <?php } else { ?>
                                            <div class="alert alert-info">
                                                <p class="mb-0">This customer does not have a company assigned.</p>
                                            </div>
                                        <?php } ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div id="edit" class="tabcontent ">
                        <?php
                            $can_edit_contact = (isset($allowed_staff_permission['update_customer_detail'])
                            && ! empty($allowed_staff_permission['update_customer_detail']));
?>
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Update Customer</h1>
                                        </div>
                                        <form class="update-form" method="post" enctype="multipart/form-data">
                                            <div class="row mb-3">
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" class="form-control" name="name" value="<?php echo $customer->name ?>" required id="name">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="email">Email</label>
                                                    <input type="email" class="form-control" name="email" value="<?php echo $customer->email ?>" required id="email" <?php echo ! $can_edit_contact ? 'disabled' : ''; ?>>
                                                    <input type="hidden" required name="old_email" value="<?php echo $customer->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="phone">Phone</label>
                                                    <input type="text" class="form-control" name="phone" value="<?php echo $customer->phone ?>" required id="phone" <?php echo ! $can_edit_contact ? 'disabled' : ''; ?>>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label" for="company">Company</label>
                                                    <input type="text" class="form-control" name="company" value="<?php echo $customer->company ?>" required id="company">
                                                </div>
                                                
                                                <div class="col-lg-6">
                                                    <label class="form-label" for="cost_place">Cost Place</label>
                                                    <input type="text" class="form-control" name="cost_place" value="<?php echo $customer->cost_place ?>" required id="cost_place">
                                                </div>
                                                <div class="col-lg-6">
                                                    <label class="form-label">Send result of the basic investigation</label>
                                                    <select name="send_report" class="form-control mb-3">
                                                        <option <?php echo empty($customer->send_security_report) ? 'selected' : '' ?> value="0">No</option>
                                                        <option <?php echo ! empty($customer->send_security_report) ? 'selected' : '' ?> value="1">Yes</option>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label">Parent Customer</label>
                                                    <select name="parent_customer" id="parent_customer" onchange="get_dep(this)" class="form-control filter-select">
                                                        <option value="">-Select Customer-</option>
                                                        <?php if (! empty($parent_customer)) { ?>
                                                            <?php foreach ($parent_customer as $par_customer) { ?>
                                                                <option value="<?= $par_customer->id ?>" <?php if ($customer->parent_id == $par_customer->id) { ?> selected <?php } ?>><?= $par_customer->name ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label" for="invoice_period">Invoice Period</label>
                                                    <select name="invoice_period" id="invoice_period" class="form-control filter-select">
                                                        <option value="">-Select Invoice Period-</option>
                                                        <option value="month" <?php echo ($customer->invoice_period === 'month') ? 'selected' : ''; ?>>Monthly</option>
                                                        <option value="week" <?php echo ($customer->invoice_period === 'week') ? 'selected' : ''; ?>>Weekly</option>
                                                        <option value="day" <?php echo ($customer->invoice_period === 'day') ? 'selected' : ''; ?>>Daily</option>
                                                    </select>
                                                </div>
                                                <?php
                        if (! empty($customer->parent_id)) {
                            $parent_departent = findAllByQuery("SELECT * FROM customers LEFT JOIN departments ON customers.id = departments.dep_cus_id WHERE customers.id = $customer->parent_id");
                        }
?>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label">Department</label>
                                                    <select name="cus_department " id="cus_department" class="form-control filter-select">
                                                        <option value="">-Select Department-</option>
                                                        <?php if (! empty($parent_departent)) { ?>
                                                            <?php foreach ($parent_departent as $par_departent) { ?>
                                                                <option value="<?= $par_departent->dep_id ?>" <?php if ($customer->dep_id == $par_departent->dep_id) { ?> selected <?php } ?>><?= $par_departent->dep_name ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Groups</label>
                                                    <select name="select_group[]" class="filter-select select2tag" multiple>
                                                        <?php if (! empty($groups)) {
                                                            $exp_arr = null;
                                                            if (! empty($customer->groups)) {
                                                                $exp_arr = explode(',', $customer->groups);
                                                            }
                                                            ?>
                                                            <?php foreach ($groups as $group) { ?>
                                                                <option value="<?= $group->id ?>" <?php if (! empty($exp_arr)) { ?> <?php foreach ($exp_arr as $exp_arrs) { ?> <?php if ($exp_arrs == $group->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $group->name ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <input class="form-check-input" id="combine_bk_and_security" type="checkbox" onchange="show_services_status()"
                                                        name="combine_bk_and_security" value="1" <?= $customer->combine_bk_and_security != "0" ? 'checked' : '' ?>>
                                                    <label class="form-label form-check-label" for="combine_bk_and_security">
                                                        Combine Background Check and Security Interview</label>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Allowed Services to transfer</label>
                                                    <select name="combine_bk_and_security[]" id="combine_services" class="filter-select select2tag" multiple>
                                                        <?php
                                                            // Get services with service category 3
                                                            $combine_services = findallByQuery("SELECT * FROM interviews WHERE service_cat_id = 3 ORDER BY title ASC");
$exp_services = null;
if (! empty($customer->combine_bk_and_security) && $customer->combine_bk_and_security != '0') {
    $exp_services = explode(',', $customer->combine_bk_and_security);
}
?>
                                                        <?php if (! empty($combine_services)) { ?>
                                                            <?php foreach ($combine_services as $service) { ?>
                                                                <option value="<?= $service->id ?>" <?php if (! empty($exp_services)) { ?> <?php foreach ($exp_services as $exp_service) { ?> <?php if (trim($exp_service) == $service->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $service->title ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Allowed Statuses to transfer</label>
                                                    <select name="combine_status[]" id="combine_statuses" class="filter-select select2tag" multiple>
                                                        <?php
// Get statuses with status type 3
$combine_statuses = findallByQuery("SELECT * FROM statuses WHERE status_type = 3 ORDER BY status ASC");
$exp_statuses = null;
if (! empty($customer->combine_status) && $customer->combine_status != '0') {
    $exp_statuses = explode(',', $customer->combine_status);
}
?>
                                                        <?php if (! empty($combine_statuses)) { ?>
                                                            <?php foreach ($combine_statuses as $status) { ?>
                                                                <option value="<?= $status->id ?>" <?php if (! empty($exp_statuses)) { ?> <?php foreach ($exp_statuses as $exp_status) { ?> <?php if (trim($exp_status) == $status->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $status->status ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label">Combine Interview service</label>
                                                    <select name="combine_interview_id" id="combine_interview_id" class="filter-select">
                                                        <option value="0">Select Combine Interview</option>
                                                        <?php
// Get statuses with status type 3
$combine_services = findallByQuery("SELECT * FROM interviews WHERE service_cat_id != 3 ORDER BY title ASC");
// $exp_services = null;

?>
                                                        <?php if (! empty($combine_services)) { ?>
                                                            <?php foreach ($combine_services as $service) { ?>
                                                                <option value="<?= $service->id ?>" <?php if ($customer->combine_interview_id == $service->id) { ?> selected <?php } ?>><?= $service->title ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Registration Email Massage</label>
                                                    <textarea rows="5" class="sign-textarea w-100" name="changed_registration_email"><?php if (! empty($customer->reg_email)) {
                                                        echo $customer->reg_email;
                                                    } else {
                                                        echo $cus_reg_msg;
                                                    } ?></textarea>
                                                </div>
                                                <?php if (isset($login_user->category) && ! empty($login_user->category) && $login_user->category == 2) { ?>
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label">Remainder Email Template</label>
                                                    <textarea rows="5" class="sign-textarea w-100" name="remainder_email_template"><?php if (! empty($customer->remainder_email_template)) {
                                                        echo $customer->remainder_email_template;
                                                    } else {
                                                        echo '';
                                                    } ?></textarea>
                                                </div>
                                                <?php } ?>
                                                <div class="col-lg-3">
                                                    <label class="form-label">
                                                        Permissions
                                                    </label>
                                                    <?php if (! empty($permissions)) : ?>
                                                        <?php foreach ($permissions as $permission) : ?>
                                                            <div>
                                                                <input class="form-check-input" id="<?php echo $permission->title ?>" <?php if (! empty($allow_permissions)) { ?> <?php foreach ($allow_permissions as $allow) { ?> <?php if ($allow->per_id == $permission->id) { ?> checked <?php } ?> <?php } ?> <?php } ?> type="checkbox" name="permissions[]" value="<?php echo $permission->id ?>">
                                                                <label class="form-label form-check-label" for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (! empty($servicesCats)) : ?>
                                                    <?php foreach ($servicesCats as $servicesCat) : ?>
                                                        <?php $statuses3 = getStatusesByService($servicesCat->id) ?>
                                                        <div class="col-lg-3" id="required-status">
                                                            <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                                            <?php if (! empty($statuses3)) : ?>
                                                                <?php foreach ($statuses3 as $status) : ?>
                                                                    <div>
                                                                        <input <?php echo in_array($status->sID, $cusStatuses) ? 'checked' : '' ?> class="form-check-input" type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                                        <label class="form-label form-check-label" for="<?php echo str_replace(' ', '-', $status->variable) ?>"><?php echo $status->status ?></label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="col-lg-3">
                                                    <?php if (! empty($services)) : ?>
                                                        <label class="form-label">Allowed Services</label>
                                                        <?php foreach ($services as $service) : ?>
                                                            <div>
                                                                <input class="form-check-input service_checkbox" id="<?php echo $service->id . $service->title ?>" <?php echo in_array($service->id, $allowed_services) ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                                                <label class="form-label form-check-label" for="<?php echo $service->id . $service->title ?>"><?php echo $service->title ?></label>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div id="update_customer_msg" class="text-center"></div>
                                            <div class="d-flex justify-content-end">
                                                <button id="update_customer_btn" type="submit" name="update_customer" class="btn-primary bg-primary">Update</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="departments" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Departments</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm" onclick="openCity(event, 'add_department')">Add</button>
                                        </div>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><b>Name</b></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (! empty($department)) { ?>
                                                    <?php foreach ($department as $dep) { ?>
                                                        <tr>
                                                            <td><?= $dep->dep_name ?></td>
                                                            <td style="width:6% !important">
                                                                <div class="dropdown">
                                                                    <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                                        <i class="bi bi-gear"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list">
                                                                        <li class="mb-1"><a href="#" onclick="openCity(event, 'update_department'),get_data(this)" data-id="<?= $dep->dep_id ?>" data-type="1" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                                Edit</a>
                                                                        </li>
                                                                        <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" data-id="<?= $dep->dep_id ?>" data-type="2"><i class="bi bi-trash  f-14 text-black me-2"></i>
                                                                                Trash</a>
                                                                        </li> -->
                                                                        <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black"><i class="bi bi-people f-14 text-black me-2"></i>
                                                                                Users</a>
                                                                        </li> -->
                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr class="no_record_found">
                                                        <td colspan="2" class="text-center">
                                                            <h5>No Records Found</h5>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="add_department" class="tabcontent">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Add Department</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity($('#departmentsOpen'), 'departments' )">Back</button>
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="" class="label-lg">Department Name</label>
                                            <input type="text" name="name" placeholder="Department Name" required class="w-100 from-input">
                                            <input type="hidden" name="dep_cus_id" value="<?= $_GET['id'] ?>">
                                        </div>
                                        <div class="col-md-12 p-0 mb-3" style="border-right:0px !important">
                                            <label for="depar_select2" class="label-lg" style="border-right:0px !important">Department (Show orders of these departments to this also)</label>
                                            <div class="col-md-12 p-0">
                                                <select name="department" class="form-select select2" multiple id="depar_select2" style="height: 33px;">
                                                    <?php if (! empty($department)) { ?>
                                                        <?php foreach ($department as $k => $depart) { ?>
                                                            <option value="<?= $department[$k]->dep_id ?>"><?= $department[$k]->dep_name ?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- <div class="row">
                                            <?php if (! empty($servicesCats)) : ?>
                                                <?php foreach ($servicesCats as $servicesCat) : ?>
                                                    <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                                    <div class="col-lg-4">
                                                        <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                                        <?php if (! empty($statuses)) : ?>
                                                            <?php foreach ($statuses as $status) : ?>
                                                                <div>
                                                                    <input class="form-check-input" type="checkbox" id="status<?php echo $status->sID ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                                    <label class="form-label form-check-label" for="status<?php echo $status->sID ?>"><?php echo $status->status ?></label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <div class="col-lg-4">
                                                <?php if (! empty($dep_services)) : ?>
                                                    <label class="form-label">Allowed Services</label>
                                                    <?php foreach ($dep_services as $dep_service) :  ?>
                                                        <div>
                                                            <input class="form-check-input" id="services<?php echo $dep_service->id ?>" type="checkbox" name="services[]" value="<?php echo $dep_service->id ?>">
                                                            <label class="form-label form-check-label" for="services<?php echo $dep_service->id ?>"><?php echo $dep_service->title ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div> -->
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="add_department_btn" onclick="departments_data(this)">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="update_department" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Update Department</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity($('#departmentsOpen'), 'departments' )">Back</button>
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="name"> Department Name </label>
                                            <input id="name" type="text" required name="name" placeholder="Department Name" class="w-100 from-input">
                                            <input type="hidden" name="up_dep_id">
                                        </div>
                                        <div class="col-md-12 p-0 mb-3" style="border-right:0px !important">
                                            <label class="label-lg" style="border-right:0px !important">Department (Show orders of these departments to this also)</label>
                                            <div class="col-md-12 p-0">
                                                <select name="child_department" class="form-select select2" multiple style="height: 33px;">
                                                    <?php if (! empty($department)) { ?>
                                                        <?php foreach ($department as $k => $depart) { ?>
                                                            <option value="<?= $department[$k]->dep_id ?>"><?= $department[$k]->dep_name ?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                        </div>
                                        <!-- <div class="row">
                                            <?php if (! empty($servicesCats)) : ?>
                                                <?php foreach ($servicesCats as $servicesCat) : ?>
                                                    <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                                    <div class="col-lg-4">
                                                        <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                                        <?php if (! empty($statuses)) : ?>
                                                            <?php foreach ($statuses as $status) : ?>
                                                                <div>
                                                                    <input class="form-check-input" type="checkbox" id="ud_status<?php echo $status->sID ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                                    <label class="form-label form-check-label" for="ud_status<?php echo $status->sID ?>"><?php echo $status->status ?></label>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <div class="col-lg-4">
                                                <label class="form-label">Allowed Services</label>
                                                <?php if (! empty($dep_services)) : ?>
                                                    <?php foreach ($dep_services as $dep_service) :  ?>
                                                        <div>
                                                            <input class="form-check-input" id="ud_services<?php echo $dep_service->id ?>" type="checkbox" name="services[]" value="<?php echo $dep_service->id ?>">
                                                            <label class="form-label form-check-label" for="ud_services<?php echo $dep_service->id ?>"><?php echo $dep_service->title ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div> -->
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="update_department_btn" onclick="departments_data(this)">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="department_users" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Department Users</h1>
                                            <!-- <button class="btn btn-outline-blue btn-rounded btn-sm" onclick="openCity(event, 'add_department_users')">Add</button> -->
                                        </div>
                                        <div class="col-md-4 p-0 mb-4">
                                            <label for="">Department</label>
                                            <select id="" class="form-control filter-select" onchange="show_dep_users(this)">
                                                <option value="">--Filter By Department--</option>
                                                <?php if (! empty($department)) { ?>
                                                    <?php foreach ($department as $dep) { ?>
                                                        <option value="<?= $dep->dep_id ?>"><?= $dep->dep_name ?></option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><b>Name</b></th>
                                                    <th><b>Email</b></th>
                                                    <th><b>Department</b></th>
                                                    <!-- <th></th> -->
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (! empty($department_users)) { ?>
                                                    <?php foreach ($department_users as $dep_user) { ?>
                                                        <tr class="<?= $dep_user->dep_name ?>">
                                                            <td><?= $dep_user->name ?></td>
                                                            <td><?= $dep_user->email ?></td>
                                                            <td><?= $dep_user->dep_name ?></td>
                                                            <!-- <td style="width:6% !important"> -->
                                                            <!-- <div class="dropdown"> -->
                                                            <!-- <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                                        <i class="bi bi-gear"></i>
                                                                    </button> -->
                                                            <!-- <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list"> -->
                                                            <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black " onclick="openCity(event, 'update_department_users'),get_user_data(this)" data-id="<?= $dep_user->dep_user_id ?>" data-type="1"><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                                Edit</a>
                                                                        </li> -->
                                                            <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black"><i class="bi bi-trash f-14 text-black me-2"></i>
                                                                                Trash</a>
                                                                        </li> -->
                                                            <!-- </ul> -->
                                                            <!-- </div> -->
                                                            <!-- </td> -->
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center no_record_found">
                                                            <h5>No Records Found</h5>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div id="add_department_users" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Add Department Users</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity($('#departmentUsersOpen'), 'department_users' )">Back</button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="" class="label-lg">Name</label>
                                                <input type="text" name="name" required placeholder="Enter name" class="w-100 from-input">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="" class="label-lg">Email</label>
                                                <input type="email" name="email" required placeholder="Enter email" class="w-100 from-input">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="" class="label-lg">Password</label>
                                                <input type="text" name="password" required value="<?php echo rand_string(7) ?>" placeholder="Enter password" class="w-100 from-input">
                                            </div>
                                            <div class="col-md-6" style="border-right:0px !important">
                                                <label for="" class="label-lg" style="border-right:0px !important">Department</label>
                                                <select name="department" class="form-select" style="height: 33px;">
                                                    <?php if (! empty($department)) { ?>
                                                        <?php foreach ($department as $k => $depart) { ?>
                                                            <option value="<?= $department[$k]->dep_id ?>"><?= $department[$k]->dep_name ?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 mt-3">
                                                <?php if (! empty($user_permissions)) : ?>
                                                    <label class="form-label">
                                                        <h4>User Permissions</h4>
                                                    </label>
                                                    <?php foreach ($user_permissions as $user_per) : ?>
                                                        <div>
                                                            <input class="form-check-input" id="add-dep-user-<?php echo $user_per->title ?>" type="checkbox" name="permissions[]" checked value="<?php echo $user_per->per_id ?>">
                                                            <label class="form-label form-check-label" for="add-dep-user-<?php echo $user_per->title ?>"><?php echo $user_per->title ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="add_dep_user_btn" onclick="departments_data(this)">Add</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="update_department_users" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Update Department Users</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity(event, 'department_users')">Back</button>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label for="" class="label-lg">Name</label>
                                                <input type="text" name="name" required placeholder="Enter name" class="w-100 from-input">
                                                <input type="hidden" name="up_dep_user_id">
                                            </div>
                                            <div class="col-md-6">
                                                <label for="" class="label-lg">Email</label>
                                                <input type="email" name="email" required placeholder="Enter email" class="w-100 from-input">
                                            </div>
                                            <div class="col-md-12" style="border-right:0px !important">
                                                <label for="" class="label-lg" style="border-right:0px !important">Department</label>
                                                <select name="department" class="form-select" style="height: 33px;">
                                                    <?php if (! empty($department)) { ?>
                                                        <?php foreach ($department as $k => $depart) { ?>
                                                            <option value="<?= $department[$k]->dep_id ?>"><?= $department[$k]->dep_name ?></option>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 mt-3">
                                                <label class="form-label">
                                                    <h4>User Permissions</h4>
                                                </label>
                                                <?php if (! empty($user_permissions)) : ?>
                                                    <?php foreach ($user_permissions as $user_pers) : ?>
                                                        <div>
                                                            <input class="form-check-input" id="update_<?php echo $user_pers->title ?>" type="checkbox" name="permissions[]" value="<?php echo $user_pers->per_id ?>">
                                                            <label class="form-label form-check-label" for="update_<?php echo $user_pers->title ?>"><?php echo $user_pers->title ?></label>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="d-flex justify-content-end">
                                                <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="update_dep_user_btn" onclick="departments_data(this)">Update</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div> -->
                    <div id="orders" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="row">
                                            <div class="main-heading  w-100">
                                                <h1 class="main-heading">Total Orders</h1>
                                                <!--                                        <input class="sign-input w-100 mb-1" type="text" placeholder="Filter by Date" name="stats_date" id="stats_date">-->
                                                <input type="hidden" id="cus_id" value="<?php echo $_GET['id'] ?>">
                                            </div>
                                            <div class="col-lg-12 mt-1">
                                                <a href="customer-candidates.php?id=<?php echo $customer->id ?>" style="text-decoration: none">
                                                    <div class="total-card shadow-sm">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <div class="d-flex flex-column align-items-start">
                                                                <h1 class="f-16 w-500">Total Orders</h1>
                                                                <h1 id="total_orders_count" class="f-22 w-800"><?php echo ! empty($candidates) ? count($candidates) : 0 ?></h1>
                                                            </div>
                                                            <div class="">
                                                                <i class="bi bi-clipboard-data f-40 "></i>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        </div>
                                        <div class="row mt-3">
                                            <div class="col-lg-12">
                                                <table class="table table-bordered">
                                                    <tbody>
                                                        <?php if (! empty($statuses)) : ?>
                                                            <?php foreach ($statuses as $status) : ?>
                                                                <tr id="<?php echo str_replace(' ', '-', $status->status) ?>">
                                                                    <th><a class="no-decoration text-black" href="history.php?id=<?php echo $_GET['id'] ?>&status=<?php echo $status->id ?>"><?php echo $status->status ?></a></th>
                                                                    <td><?php echo $data[$status->variable] ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="emails" class="tabcontent ">
                            <div class="container">
                                <div class="row">
                                    <div class="col-lg-12">
                                        <div class="table-section">
                                            <h1 class="main-heading">Emails</h1>
                                            <form action="" method="post" id="d-form">
                                                <input type="hidden" id="customer_email" value="<?php echo $customer->email; ?>">
                                                <table id="emails_table" data-table="customer_emails" class="display Table w-100" style="width: 100% !important;">
                                                    <thead>
                                                        <tr>
                                                            <th class="table-head" style="min-width: 80px;">Order ID</th>
                                                            <th class="table-head" style="min-width: 120px;">Email Type</th>
                                                            <th class="table-head" style="min-width: 150px;">Email</th>
                                                            <th class="table-head" style="min-width: 120px;">Date</th>
                                                            <th class="table-head" style="min-width: 200px;">Text</th>
                                                            <th class="table-head" style="min-width: 80px;">Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- DataTable will populate this via AJAX -->
                                                    </tbody>
                                                </table>
                                                <div id="resend_msg" class="text-center"></div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <div id="messages" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <?php if (! empty($services)) : ?>
                                        <div class="col-lg-12 mb-3">
                                            <select class="form-control" name="services" id="service-messages">
                                                <?php foreach ($services as $service) : ?>
                                                    <option <?php echo isset($_GET['sid']) && $service->id == $_GET['sid'] ? 'selected' : '' ?> value="<?php echo $service->id ?>"><?php echo $service->title ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <form action="" method="post">
                                        <div class="row">
                                            <div class="row messages m-0 p-0">
                                                <?php $messages = (array) $messages ?>
                                                <?php if (! empty($messages)) :
                                                    unset($messages['id']);
                                                    unset($messages['cus_id']);
                                                    unset($messages['interview_id']);
                                                    ?>
                                                    <?php foreach ($messages as $col => $message) : ?>
                                                        <div class="col-lg-6 mb-3">
                                                            <label class="form-label"><?php echo $col ?></label>
                                                            <div class="position-relative">
                                                                <textarea rows="5" class="sign-textarea w-100" name="<?php echo $col ?>"><?php echo $message ?></textarea>
                                                                <?php include "../comments-dropdown.php" ?>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div id="update_message_msg" class="text-center"></div>
                                            <div class="d-flex justify-content-end">
                                                <button id="update_msg_btn" type="submit" name="update" class="btn-primary bg-primary">Update</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="invoiced" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <table class="table table-striped table-bordered">
                                        <thead>
                                            <tr>
                                                <th class="text-center">Order ID</th>
                                                <th class="text-center">Name</th>
                                                <th class="text-center">Invoice Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (! empty($invoicedCandidates)) : ?>
                                                <?php foreach ($invoicedCandidates as $candidate) : ?>
                                                    <tr>
                                                        <td><?php echo $candidate->order_id ?></td>
                                                        <td><?php echo $candidate->name . " " . $candidate->surname ?></td>
                                                        <td><?php echo $candidate->invoice_date ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else : ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="background-qs" class="tabcontent ">
                        <div class="container">
                            <form action="#" method="post">
                                <input type="hidden" name="cus_id" value="<?= $_GET['id'] ?>">
                                <div class="row questions-row">
                                    <div class="col-md-12">
                                        <button type="button" class="btn btn-primary float-right" onclick="add_question_type_base()">Add Question</button>
                                    </div>
                                    <div class="row" id="add-question" style="display:none">
                                        <div class="col-md-12">
                                            <h5>Question Type</h5>
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <input type="radio" class="question_type" name="qs_type" checked value="radio_opt">
                                                    <label>Radio Options</label>
                                                </div>
                                                <div class="col-md-6">
                                                    <input type="radio" class="question_type" name="qs_type" value="free_text">
                                                    <label>Free text</label>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-12 text-right">
                                            <button type="button" class="btn btn-secondary btn-sm" onclick="add_question_type_base()">Close</button>
                                            <button type="button" class="btn btn-success btn-sm" onclick="add_question();">Add</button>
                                        </div>
                                    </div>
                                    <?php
                                    $qsno = 0;
?>
                                    <?php if (! empty($customer_meta_data)) { ?>
                                        <?php foreach ($customer_meta_data as $i => $meta_data) { ?>
                                            <?php if (isset($meta_data['type']) && ! empty($meta_data['type']) && $meta_data['type'] == 'radio') { ?>
                                                <div class="col-lg-12 mb-4 question_count">
                                                    <label for="">Question <?= ++$qsno ?>:</label> <button type="button" class="btn btn-danger ml-5 mb-2" onclick="remove_question(this)"><span class="bi bi-trash"></span></button>
                                                    <input type="text" name="qs[<?= $i ?>][qs]" <?php if (isset($meta_data['qs']) && ! empty($meta_data['qs'])) { ?>value="<?= $meta_data['qs'] ?>" <?php } ?> class="form-control">
                                                    <input type="hidden" name="qs[<?= $i ?>][type]" value="radio">
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <h5>Answers</h5>
                                                        </div>
                                                        <div class="col-md-8">
                                                            <button type="button" class="btn btn-primary" onclick="add_option(this)" data-id="<?= $i ?>"><i class="bi bi-plus-lg"></i></button>
                                                        </div>
                                                        <?php if (isset($meta_data['option']) && ! empty($meta_data['option'])) { ?>
                                                            <?php foreach ($meta_data['option'] as $val) { ?>
                                                                <div class="col-md-12">
                                                                    <div class="row">
                                                                        <div class="col-md-4">
                                                                            <input type="text" name="qs[<?= $i ?>][option][]" class="form-control" value="<?= $val ?>">
                                                                        </div>
                                                                        <div class="col-md-8">
                                                                            <button type="button" class="btn btn-danger mt-0" onclick="remove_option(this)"><i class="bi bi-trash"></i></button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </div>
                                                </div>
                                            <?php } ?>
                                            <?php if (isset($meta_data['type']) && ! empty($meta_data['type']) && $meta_data['type'] == 'free_text') { ?>
                                                <div class="col-lg-12 question_count">
                                                    <label for="">Question <?= ++$qsno ?>:</label><button type="button" class="btn btn-danger ml-5 mb-2" onclick="remove_question(this)"><span class="bi bi-trash"></span></button>
                                                    <input type="text" name="qs[<?= $i ?>][qs]" value="What does security mean to you?" <?php if (isset($meta_data['qs']) && ! empty($meta_data['qs'])) { ?>value="<?= $meta_data['qs'] ?>" <?php } ?> class="form-control">
                                                    <input type="hidden" name="qs[<?= $i ?>][type]" value="free_text">
                                                </div>
                                            <?php } ?>
                                        <?php } ?>
                                    <?php } ?>
                                </div>
                                <div class="col-lg-12 text-right mt-3 pr-0">
                                    <button type="button" class="btn btn-primary float-right mb-4" onclick="add_question_data(this)">Save</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div id="form_builder" class="tabcontent ">
                        <div class="container">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h1 class="main-heading">Form Builder</h1>
                                <button class="btn btn-outline-blue btn-rounded btn-sm" onclick="show_field_row()">Add Field</button>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-5 pt-3" style="display:none;border:3px solid grey;border-radius:5px" id="field_row">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label>Label</label>
                                            <input type="text" id="label_field" class="form-control">
                                        </div>
                                        <div class="col-md-6">
                                            <label>Type</label>
                                            <select id="type_field" class="form-control">
                                                <option value="text">Text</option>
                                                <option value="email">Email</option>
                                                <option value="number">Number</option>
                                                <!-- <option value="radio">Radio Button</option> -->
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Placeholder</label>
                                            <input type="text" id="placeholder_field" class="form-control">
                                        </div>
                                        <div class="col-md-6 mt-3 pt-3">
                                            <input type="checkbox" id="req" class="form-check-input" value="1">
                                            <label class="form-label form-check-label" for="req">Required</label>
                                        </div>
                                        <div class="col-md-12">
                                            <button type="button" class="btn btn-outline-yellow btn-rounded btn-sm float-right mb-3" onclick="add_field(this)" data-id="billing_info">Add To Billing Info</button>
                                            <button type="button" class="btn btn-outline-blue btn-rounded btn-sm float-right mb-3" onclick="add_field(this)" data-id="personal_info">Add To Personal Info</button>
                                            <button type="button" class="btn btn-primary btn-rounded btn-sm float-right mb-3" onclick="add_field_def(this)" data-id="vasc_id">Vasc Id</button>
                                            <button type="button" class="btn btn-primary btn-rounded btn-sm float-right mb-3" onclick="add_field_def(this)" data-id="document_file">Document</button>
                                            <button type="button" class="btn btn-primary btn-rounded btn-sm float-right mb-3" onclick="add_field_def(this)" data-id="comment">Invoice Comment</button>
                                            <button type="button" class="btn btn-primary btn-rounded btn-sm float-right mb-3" onclick="add_field_def(this)" data-id="note">Note</button>
                                        </div>
                                    </div>
                                </div>
                                <form action="#" method="post">
                                    <input type="hidden" name="cus_id" value="<?= $_GET['id'] ?>">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label>Copy From Customer</label>
                                            <select id="c_customer" name="c_customer" class="form-control filter-select" onchange="get_form_of(this),initial_sortable()" data-type="copy_customer">
                                                <option value="">-Select Customer to copy from-</option>
                                                <?php if ($parent_customer) { ?>
                                                    <?php foreach ($parent_customer as $all_cus) { ?>
                                                        <option value="<?= $all_cus->id ?>"><?= $all_cus->name ?></option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Service Type</label>
                                            <select id="service_types" name="service_type" class="form-control filter-select" onchange="get_form_of(this),initial_sortable()" data-type="serv_type">
                                                <?php if ($cus_services) { ?>
                                                    <?php foreach ($cus_services as $cus_ser) { ?>
                                                        <option value="<?= $cus_ser->id ?>"><?= $cus_ser->title ?></option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label>Copy From</label>
                                            <select id="copy_from" class="form-control" onchange="get_form_of(this),initial_sortable()" data-type="copy_from">
                                                <option value=""></option>
                                                <?php if ($all_services) { ?>
                                                    <?php foreach ($all_services as $all_ser) { ?>
                                                        <option value="<?= $all_ser->id ?>"><?= $all_ser->title ?></option>
                                                    <?php } ?>
                                                <?php } ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row p-3 mt-4 ml-1 mr-1" id="add_columns_row" style="border:3px solid grey;border-radius:5px">
                                        <?php if ($default_form) { ?>
                                            <?php foreach ($default_form as $default_for) { ?>
                                                <?php
            $form_builder = json_decode($default_form->form);
                                                if (! empty($form_builder->form_builder)) {
                                                    $form_builder = $form_builder->form_builder;
                                                }
                                                $billing = null;
                                                $personal = null;
                                                if (isset($form_builder->personal_info) && ! empty($form_builder->personal_info)) {
                                                    $personal = $form_builder->personal_info;
                                                }
                                                if (isset($form_builder->billing_info) && ! empty($form_builder->billing_info)) {
                                                    $billing = $form_builder->billing_info;
                                                }
                                                ?>
                                                <div class="row" id="personal_info_row">
                                                    <h5 style="background-color: lightgrey;">Personal Info</h5>
                                                    <?php if (! empty($personal)) { ?>
                                                        <?php foreach ($personal as $input_k => $input_v) { ?>
                                                            <?php
                                                            $real_dta = explode(',', $input_k);
                                                            $type = isset($real_dta[0]) ? $real_dta[0] : 'text';
                                                            $label = isset($real_dta[1]) ? $real_dta[1] : '';
                                                            $name = isset($real_dta[2]) ? $real_dta[2] : '';
                                                            $placehol = isset($real_dta[3]) ? $real_dta[3] : '';
                                                            $req = isset($real_dta[4]) ? $real_dta[4] : '';
                                                            $is_tra = isset($real_dta[5]) ? $real_dta[5] : '';
                                                            $is_new = isset($real_dta[6]) ? $real_dta[6] : '';
                                                            ?>
                                                            <?php if ($type != 'radio') { ?>
                                                                <div class="col-md-12 mb-2 sortable-items">
                                                                    <label ondblclick="change_type(this)" onblur="change_label(this)"><?= $label ?><?php if (! empty($req)) { ?><span class="star text-danger">*</span><?php } ?></label>
                                                                    <?php if (empty($is_tra)) { ?><button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button><?php } ?>
                                                                    <?php if (empty($is_tra)) { ?><button type="button" class="btn btn-sm float-right <?php if (! empty($req)) { ?> btn-warning <?php } ?>" onclick="change_required(this)"><span class="bi bi-star"></span></button><?php } ?>
                                                                    <input type="<?= $type ?>" class="form-control" name="form_builder[personal_info][<?= $type ?>,<?= $label ?>,<?= $name ?>,<?= $placehol ?>,<?= $req ?>,<?= $is_tra ?>,<?= $is_new ?>]" value="<?= $placehol ?>" placeholder="<?= $placehol ?>" data-id="<?= $name ?>">
                                                                </div>
                                                            <?php } else { ?>
                                                                <div class="col-md-12 mb-2 sortable-items">
                                                                    <label ondblclick="change_type(this)" onblur="change_label(this)"></label>
                                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                                    <button type="button" class="btn btn-success btn-sm float-right" onclick="add_radio_option(this)" data-id="` + builder_type + `"><span class="bi bi-plus"></span></button>
                                                                    <input type="text" class="form-control" name="form_builder[` + builder_type + `][` + type + `,` + label + `,` + label + `,,,,new_field]">
                                                                    <div class="row radio_options">
                                                                        <div class="col-md-12">
                                                                            <div class="row mt-2">
                                                                                <div class="col-md-10">
                                                                                    <input type="text" class="form-control" name="form_builder[` + builder_type + `][` + label + `][]" value="Yes">
                                                                                </div>
                                                                                <div class="col-md-2">
                                                                                    <button type="button" class="btn btn-danger float-right" onclick="delete_opt(this)"><span class="bi bi-trash"></span></button>
                                                                                </div>
                                                                            </div>
                                                                            <div class="row mt-2">
                                                                                <div class="col-md-10">
                                                                                    <input type="text" class="form-control" name="form_builder[` + builder_type + `][` + label + `][]" value="No">
                                                                                </div>
                                                                                <div class="col-md-2">
                                                                                    <button type="button" class="btn btn-danger float-right" onclick="delete_opt(this)"><span class="bi bi-trash"></span></button>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </div>
                                                <div class="row mt-5" id="billing_info_row">
                                                    <h5 style="background-color: lightgrey;">Billing Info</h5>
                                                    <?php if (! empty($billing)) { ?>
                                                        <?php foreach ($billing as $input_b => $input_b) { ?>
                                                            <?php
                                                            $b_real_dta = explode(',', $input_b);
                                                            $b_type = isset($b_real_dta[0]) ? $b_real_dta[0] : 'text';
                                                            $b_label = isset($b_real_dta[1]) ? $b_real_dta[1] : '';
                                                            $b_name = isset($b_real_dta[2]) ? $b_real_dta[2] : '';
                                                            $b_placehol = isset($b_real_dta[3]) ? $b_real_dta[3] : '';
                                                            $b_req = isset($b_real_dta[4]) ? $b_real_dta[4] : '';
                                                            $b_is_tra = isset($b_real_dta[5]) ? $b_real_dta[5] : '';
                                                            $b_is_new = isset($b_real_dta[6]) ? $b_real_dta[6] : '';
                                                            ?>
                                                            <div class="col-md-12 mb-2 sortable-items">
                                                                <label ondblclick="change_type(this)" onblur="change_label(this)"><?= $b_label ?><?php if (! empty($b_req)) { ?><span class="star text-danger">*</span><?php } ?></label>
                                                                <?php if (empty($b_is_tra)) { ?><button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button><?php } ?>
                                                                <?php if (empty($b_is_tra)) { ?><button type="button" class="btn btn-sm float-right <?php if (! empty($b_req)) { ?> btn-warning <?php } ?>" onclick="change_required(this)"><span class="bi bi-star"></span></button><?php } ?>
                                                                <input type="<?= $b_type ?>" class="form-control" name="form_builder[billing_info][<?= $b_type ?>,<?= $b_label ?>,<?= $b_name ?>,<?= $b_placehol ?>,<?= $b_req ?>,<?= $b_is_tra ?>,<?= $b_is_new ?>]" value="<?= $b_placehol ?>" placeholder="<?= $b_placehol ?>" data-id="<?= $b_name ?>">
                                                            </div>
                                                        <?php } ?>
                                                    <?php } ?>
                                                </div>
                                            <?php } ?>
                                        <?php } else { ?>
                                            <div class="row" id="personal_info_row">
                                                <h5 style="background-color: lightgrey;">Personal Info</h5>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)"> Name<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Name,name,Enter Candidate Name,required,n_trash]" value="Enter Candidate Name" placeholder="Enter Candidate Name" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Surname<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Surname,surname,Enter Candidate Surname,required,n_trash]" value="Enter Candidate Surname" placeholder="Enter Candidate Surname" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Email<span class="star text-danger">*</span></label>
                                                    <input type="email" name="form_builder[personal_info][email,Email,email,Enter Candidate Email,required,n_trash]" value="Enter Candidate Email" placeholder="Enter Candidate Email" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Phone<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Phone,phone,Enter Candidate Phone Number,required,n_trash]" value="Enter Candidate Phone Number" placeholder="Enter Candidate Phone Number" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Social Security Number<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Social Security Number,security,Enter Candidate Social Security Number,required,n_trash]" value="Enter Candidate Social Security Number" placeholder="Enter Candidate Social Security Number" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">VASC ID</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[personal_info][text,VASC ID,vasc_id,Enter Candidate VASC ID,]" data-id="vasc_id" value="Enter Candidate VASC ID" placeholder="Enter Candidate VASC ID">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Documents</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[personal_info][,Documents,document_file,,,]" data-id="document_file">
                                                </div>
                                            </div>
                                            <div class="row mt-5" id="billing_info_row">
                                                <h5 style="background-color: lightgrey;">Billing Info</h5>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Reference<br>(Invoice Recipient)<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[billing_info][text,Reference<br>(Invoice Recipient),pref,Enter Candidate Reference,required,n_trash]" value="Enter Candidate Reference" placeholder="Enter Candidate Reference" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Reference<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[billing_info][text,Reference,ref,Enter Candidate Reference,required,n_trash]" placeholder="Enter Candidate Reference" value="Enter Candidate Reference" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Invoice Comment</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[billing_info][text,Invoice Comment,comment,Enter Invoice Comment,]" data-id="comment" value="Enter Invoice Comment" placeholder="Enter Invoice Comment">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Note</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" data-id="note" name="form_builder[billing_info][text,Note,note,Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.,]" value="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual." placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.">
                                                </div>
                                            </div>
                                        <?php } ?>
                                    </div>
                                    <div class="col-lg-12 text-right mt-3 pr-0">
                                        <button type="button" class="btn btn-primary float-right mb-4" onclick="save_form_builder(this)">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    <div id="additional_customers" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Additional Customers</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm" onclick="openCity(event, 'add_additional_customers')">Add</button>
                                        </div>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th><b>Name</b></th>
                                                    <th><b>Email</b></th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (! empty($add_cus)) { ?>
                                                    <?php foreach ($add_cus as $ad_cu) { ?>
                                                        <tr>
                                                            <td><?= $ad_cu->name ?></td>
                                                            <td><?= $ad_cu->email ?></td>
                                                            <td style="width:6% !important">
                                                                <div class="dropdown">
                                                                    <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                                        <i class="bi bi-gear"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list">
                                                                        <li class="mb-1"><a href="#" onclick="openCity(event, 'update_additional_customers'),get_ad_cu_data(this)" data-id="<?= $ad_cu->id ?>" data-name="<?= $ad_cu->name ?>" data-email="<?= $ad_cu->email ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                                Edit</a>
                                                                        </li>
                                                                        <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" onclick="delete_ad_cu(this)" data-id="<?= $ad_cu->id ?>"><i class="bi bi-trash  f-14 text-black me-2"></i>
                                                                                Delete</a>
                                                                        </li>
                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php } ?>
                                                <?php } else { ?>
                                                    <tr class="no_record_found">
                                                        <td colspan="2" class="text-center">
                                                            <h5>No Records Found</h5>
                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="add_additional_customers" class="tabcontent">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Add Additional Customers</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity($('#addCuOpen'), 'additional_customers' )">Back</button>
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="" class="label-lg">Name</label>
                                            <input type="text" name="add_cus_name" placeholder="Additional Customer name" required class="w-100 from-input">
                                            <input type="hidden" name="add_cus_id" value="<?= $_GET['id'] ?>">
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="" class="label-lg">Email</label>
                                            <input type="email" name="add_cus_email" placeholder="Additional Customer email" required class="w-100 from-input">
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="add_ad_cu_btn" onclick="additional_customer_data(this)">Add</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="update_additional_customers" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="table-section">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <h1 class="main-heading">Update Additional Customers</h1>
                                            <button class="btn btn-outline-blue btn-rounded btn-sm back_btn" onclick="openCity($('#adCuOpen'), 'additional_customers' )">Back</button>
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="" class="label-lg">Name</label>
                                            <input type="text" name="add_cus_name_u" placeholder="Additiona customer name" required class="w-100 from-input">
                                            <input type="hidden" name="add_cus_id_u">
                                        </div>
                                        <div class="d-flex align-items-center form-row mb-3">
                                            <label for="" class="label-lg">Email</label>
                                            <input type="email" name="add_cus_email_u" placeholder="Additiona customer email" required class="w-100 from-input">
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" class="btn btn-outline-blue btn-rounded btn-sm" id="update_ad_cu_btn" onclick="additional_customer_data(this)">Update</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script type="text/template" id="messageTemplate">
    <div class="col-lg-6 mb-3">
        <label class="form-label">{col}</label>
        <div class="position-relative">
            <textarea rows="5" class="sign-textarea w-100" name="{col}">{message}</textarea>
            <?php include "../comments-dropdown.php" ?>
        </div>
    </div>
</script>
<script>
    function show_services_status(){
        var combine_bk_and_security = $("#combine_bk_and_security").is(":checked");
        
        if(combine_bk_and_security){
            // Show the multi-select fields
            $("#combine_services").parent().show();
            $("#combine_statuses").parent().show();
            $("#combine_interview_id").parent().show();
        }else{
            // Clear selections and hide the multi-select fields
            $('#combine_services').val(null).trigger('change');
            $('#combine_statuses').val(null).trigger('change');
            $('#combine_interview_id').val(null).trigger('change');
            $("#combine_services").parent().hide();
            $("#combine_statuses").parent().hide();
            $("#combine_interview_id").parent().hide();
        }
    }
    $(document).ready(function() {
        initial_sortable()
        // Set initial state of services/statuses visibility
        show_services_status()
    });
// Function to load services with service category 3
// Function to update services display
// Function to update statuses display
function updateStatusesDisplay() {
    var selectedStatuses = $('#combine_statuses option:selected');
    var statusesText = [];
    selectedStatuses.each(function() {
        statusesText.push($(this).text());
    });
    $('#selected_statuses_display').text(statusesText.length > 0 ? statusesText.join(', ') : 'No statuses selected');
}
    function get_dep(obj) {
        var cus_id = $(obj).val();
        if (cus_id != '') {
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    'id': cus_id,
                    'get_par_department': 1
                },
                success: function(response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.customers != '') {
                            var cus = response.customers;
                            $('.main_heading').val(cus[0].company)
                            if (cus[0].invoice_period) {
                                $("select[name='invoice_period']").val(cus[0].invoice_period).trigger('change');
                            }  
                            var statuses = cus[0].statuses.split(',');
                            $('input[name="statuses[]"]').prop('checked', false);
                            statuses.forEach(function (status) {
                                $('input[name="statuses[]"][value="' + status + '"]').prop('checked', true);
                            });
                        } else {
                            $('.main_heading').val('Company')
                        }
                        var opt_html = '<option value="">-Select Department-</option>';
                        if (response.departments != '') {
                            var dep = response.departments;
                            $(dep).each(function(i, v) {
                                opt_html += '<option value="' + v.dep_id + '">' + v.dep_name + '</option>';
                            })
                            $('#cus_department').html(opt_html)
                        } else {
                            $('#cus_department').html(opt_html)
                        }
                        if (response.services != '') {
                            var ser = response.services;
                            $('.service_checkbox').attr('checked', false)
                            $('.service_checkbox').each(function(a, c) {
                                var chk_box = $(this);
                                $(ser).each(function(k, b) {
                                    if (chk_box.val() == b.service_id) {
                                        chk_box.attr('checked', true);
                                    }
                                })
                                chk_box = ''
                            })
                        }
                        if (response.customers != '') {
                            var cus = response.customers;
                             if (cus[0].combine_bk_and_security != "0") {
                                $('input[name="combine_bk_and_security"]').prop('checked', true);
                                var servicesArray = cus[0].combine_bk_and_security.split(',');
                                $('#combine_services').val(servicesArray).trigger('change');
                            } else {
                                $('input[name="combine_bk_and_security"]').prop('checked', false);
                                $('#combine_services').val(null).trigger('change');
                            }
                            if (cus[0].combine_status != "0") {
                                // $('input[name="combine_status"]').prop('checked', true);
                                var statusesArray = cus[0].combine_status.split(',');
                                $('#combine_statuses').val(statusesArray).trigger('change');
                            } else {
                                // $('input[name="combine_status"]').prop('checked', false);
                                $('#combine_statuses').val(null).trigger('change');
                            }
                            if (cus[0].combine_interview_id != "0") {
                                $('#combine_interview_id').val(cus[0].combine_interview_id).trigger('change');
                            } else {
                                $('#combine_interview_id').val(null).trigger('change');
                            }
                            show_services_status()
                            
                        }
                    }
                }
            });
        }
    }
    function show_field_row() {
        if ($('#field_row').is(':hidden')) {
            $('#field_row').slideDown(500)
        } else {
            $('#field_row').slideUp(500)
        }
    }
    function add_field_def(obj) {
        var field = $(obj).data('id');
        var html = '';
        var id = '';
        if (field == 'vasc_id') {
            html = `<div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">VASC ID</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" data-id="vasc_id" value="Enter Candidate VASC ID" name="form_builder[personal_info][text,VASC ID,vasc_id,Enter Candidate VASC ID,]" placeholder="Enter Candidate VASC ID">
                                                </div>`
            $('input').each(function() {
                if ($(this).data('id') == field) {
                    id = $(this).data('id');
                }
            })
            if (field == id) {
                alert('This field already exist!')
            } else {
                $('#personal_info_row').append(html)
            }
        }
        if (field == 'document_file') {
            html = `<div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Documents</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" data-id="document_file" class="form-control" name="form_builder[personal_info][,Documents,document_file,,,]">
                                                </div>`
            $('input').each(function() {
                if ($(this).data('id') == field) {
                    id = $(this).data('id');
                }
            })
            if (field == id) {
                alert('This field already exist!')
            } else {
                $('#personal_info_row').append(html)
            }
        }
        if (field == 'comment') {
            html = `<div class="col-md-12 mb-2 sortable-items">
            <label ondblclick="change_type(this)" onblur="change_label(this)">Invoice Comment</label>
            <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
            <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
            <input type="text" class="form-control" data-id="comment" name="form_builder[billing_info][text,Invoice Comment,comment,Enter Invoice Comment,]" placeholder="Enter Invoice Comment" value="Enter Invoice Comment">
            </div>`
            $('input').each(function() {
                if ($(this).data('id') == field) {
                    id = $(this).data('id');
                }
            })
            if (field == id) {
                alert('This field already exist!')
            } else {
                $('#billing_info_row').append(html)
            }
        }
        if (field == 'note') {
            html = `<div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Note</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" data-id="note" name="form_builder[billing_info][text,Note,note,Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.,]" value="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual." placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.">
                                                </div>`
            $('input').each(function() {
                if ($(this).data('id') == field) {
                    id = $(this).data('id');
                }
            })
            if (field == id) {
                alert('This field already exist!')
            } else {
                $('#billing_info_row').append(html)
            }
        }
        $('#field_row').slideUp(500)
        initial_sortable()
    }
    function add_field(obj) {
        var label = $('#label_field').val();
        var type = $('#type_field').val();
        var req = $('#req');
        var builder_type = $(obj).data('id')
        var req_field = '';
        var required = '';
        var placeholder = $('#placeholder_field').val()
        if (req.prop('checked') == true) {
            req_field = `<span class="star text-danger">*</span>`
            required = 'required';
        } else {
            req_field = '';
        }
        if (type == 'radio') {
            var field_html = `<div class="col-md-12 mb-2 sortable-items">
            <label ondblclick="change_type(this)" onblur="change_label(this)">` + label + `</label>
                            <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                            <button type="button" class="btn btn-success btn-sm float-right" onclick="add_radio_option(this)" data-name="` + label + `" data-id="` + builder_type + `"><span class="bi bi-plus"></span></button>
                            <input type="text" class="form-control" name="form_builder[` + builder_type + `][` + type + `,` + label + `,` + label + `,,,,new_field]">
                            <div class="row radio_options">
                            <div class="col-md-12">
                                <div class="row mt-2">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" name="form_builder[` + builder_type + `][radio_option,` + label + `][]" value="Yes">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger float-right" onclick="delete_opt(this)"><span class="bi bi-trash"></span></button>
                                    </div>
                                </div>
                                    <div class="row mt-2">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" name="form_builder[` + builder_type + `][radio_option,` + label + `][]" value="No">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger float-right" onclick="delete_opt(this)"><span class="bi bi-trash"></span></button>
                                    </div>
                                </div>
                                </div>
                            </div>
                            </div>`;
        } else {
            var field_html = `<div class="col-md-12 mb-2 sortable-items">
            <label ondblclick="change_type(this)" onblur="change_label(this)">` + label + `` + req_field + `</label>
                            <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                            <button type="button" class="btn btn-sm float-right`
            if (required != '') {
                field_html += ` btn-warning`
            }
            field_html += `" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                            <input type="` + type + `" class="form-control" value="` + placeholder + `" name="form_builder[` + builder_type + `][` + type + `,` + label + `,` + label + `,` + placeholder + `,` + required + `,,new_field]" placeholder="` + placeholder + `">
                            </div>`;
        }
        if (label != '') {
            if (builder_type == 'billing_info') {
                $('#billing_info_row').append(field_html)
            }
            if (builder_type == 'personal_info') {
                $('#personal_info_row').append(field_html)
            }
            $('#field_row').slideUp(500)
            req.prop('checked', false)
            $('#label_field').val('');
            $('#placeholder_field').val('')
        } else {
            alert("Please Enter Label First");
        }
        initial_sortable()
    }
    function remove_field(obj) {
        $(obj).closest('.col-md-12').remove();
    }
    function add_radio_option(obj) {
        var form_type = $(obj).data('id');
        var name = $(obj).data('name');
        var html = `<div class="row mt-2">
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" name="form_builder[` + form_type + `][radio_option,` + name + `]">
                                    </div>
                                    <div class="col-md-2">
                                        <button type="button" class="btn btn-danger float-right" onclick="delete_opt(this)"><span class="bi bi-trash"></span></button>
                                    </div>
                                </div>`
        $(obj).closest('.col-md-12').find('.radio_options').find('.col-md-12').append(html);
    }
    function delete_opt(obj) {
        $(obj).closest('.row').remove();
    }
    function change_label(obj) {
        var name = $(obj).closest('.col-md-12').find('input').attr('name')
        var label = $(obj).text()
        label = label.replace('*', '');
        name = name.split(',')
        name[1] = label;
        name = name.join(',')
        $(obj).closest('.col-md-12').find('input').attr('name', name)
    }
    function change_required(obj) {
        var name = $(obj).closest('.col-md-12').find('input').attr('name')
        name = name.split(',')
        if ($(obj).hasClass('btn-warning')) {
            $(obj).removeClass('btn-warning')
            name[4] = '';
        } else {
            $(obj).addClass('btn-warning')
            name[4] = 'required]';
        }
        name = name.join(',')
        $(obj).closest('.col-md-12').find('input').attr('name', name)
    }
    function get_form_of(obj) {
        var data_type_of = $(obj).data('type')
        var ser_id = $('#service_types').val();
        var copy_from = $('#copy_from').val();
        if (data_type_of == 'copy_customer') {
            if (copy_from != '') {
                ser_id = $('#copy_from').val();
            } else {
                ser_id = $('#service_types').val()
            }
        } else if (data_type_of == 'copy_from') {
            ser_id = $('#copy_from').val();
            if (ser_id == '') {
                ser_id = $('#service_types').val();
            }
        } else if (data_type_of == 'serv_type') {
            if ($('#copy_from').val() != '') {
                ser_id = $('#copy_from').val();
            } else {
                ser_id = $('#service_types').val();
            }
        }
        var id = <?php echo $_GET['id']; ?>;
        var copy_customer = $('#c_customer').val();
        var personal_info = '';
        var billing_info = '';
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_service_form': 1,
                'ser_id': ser_id,
                'cus_id': id,
                'copy_customer': copy_customer,
            },
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    var html = '';
                    response = JSON.parse(response.form);
                    if ("form_builder" in response) {
                        response = response.form_builder;
                        if ("personal_info" in response) {
                            personal_info = response.personal_info
                            html += `<div class="row" id="personal_info_row">
                            <h5 style="background-color: lightgrey;">Personal Info</h5>`
                            $.each(personal_info, function(p, v) {
                                real_data = p.split(',')
                                if (real_data != '') {
                                    var type = real_data[0] ? real_data[0] : 'text';
                                    var label = real_data[1] ? real_data[1] : '';
                                    var name = real_data[2] ? real_data[2] : '';
                                    var placehol = real_data[3] ? real_data[3] : '';
                                    var req = real_data[4] ? real_data[4] : '';
                                    var is_tra = real_data[5] ? real_data[5] : '';
                                    var is_new = real_data[6] ? real_data[6] : '';
                                    html += `<div class="col-md-12 mb-2 sortable-items">
                                                <label ondblclick="change_type(this)" onblur="change_label(this)"> ` + label
                                    if (req != '') {
                                        html += `<span class="star text-danger">*</span>`
                                    }
                                    html += `</label>`
                                    if (is_tra == '') {
                                        html += ` <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                        <button type="button" class="btn btn-sm float-right`
                                        if (req != '') {
                                            html += ` btn-warning`
                                        }
                                        html += `" onclick="change_required(this)"><span class="bi bi-star"></span></button>`
                                    }
                                    html += `<input type="` + type + `" value="` + v + `" name="form_builder[personal_info][` + type + `,` + label + `,` + name + `,` + placehol + `,` + req + `,` + is_tra + `,` + is_new + `]" placeholder="` + placehol + `" class="form-control">
                                                </div>`
                                }
                            })
                            html += `</div>`;
                        }
                        if ("billing_info" in response) {
                            billing_info = response.billing_info
                            html += `<div class="row mt-5" id="billing_info_row">
                            <h5 style="background-color: lightgrey;">Billing Info</h5>`
                            $.each(billing_info, function(k, b) {
                                real_data = k.split(',')
                                if (real_data != '') {
                                    var type = real_data[0] ? real_data[0] : 'text';
                                    var label = real_data[1] ? real_data[1] : '';
                                    var name = real_data[2] ? real_data[2] : '';
                                    var placehol = real_data[3] ? real_data[3] : '';
                                    var req = real_data[4] ? real_data[4] : '';
                                    var is_tra = real_data[5] ? real_data[5] : '';
                                    var is_new = real_data[6] ? real_data[6] : '';
                                    html += `<div class="col-md-12 mb-2 sortable-items">
                                                <label ondblclick="change_type(this)" onblur="change_label(this)"> ` + label
                                    if (req != '') {
                                        html += `<span class="star text-danger">*</span>`
                                    }
                                    html += `</label>`
                                    if (is_tra == '') {
                                        html += `<button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                        <button type="button" class="btn btn-sm float-right `
                                        if (req != '') {
                                            html += ` btn-warning`
                                        }
                                        html += `" onclick="change_required(this)"><span class="bi bi-star"></span></button>`
                                    }
                                    html += `<input type="` + type + `" value="` + b + `" name="form_builder[billing_info][` + type + `,` + label + `,` + name + `,` + placehol + `,` + req + `,` + is_tra + `,` + is_new + `]" placeholder="` + placehol + `" class="form-control">
                                                </div>`
                                }
                            })
                            html += `</div>`;
                        }
                    }
                    $('#add_columns_row').empty()
                    $('#add_columns_row').append(html)
                    initial_sortable()
                } else {
                    var personal_info = `<h5 style="background-color: lightgrey;">Personal Info</h5>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)"> Name<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Name,name,Enter Candidate Name,required,n_trash]" value="Enter Candidate Name" placeholder="Enter Candidate Name" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Surname<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Surname,surname,Enter Candidate Surname,required,n_trash]" value="Enter Candidate Surname" placeholder="Enter Candidate Surname" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Email<span class="star text-danger">*</span></label>
                                                    <input type="email" name="form_builder[personal_info][email,Email,email,Enter Candidate Email,required,n_trash]" value="Enter Candidate Email" placeholder="Enter Candidate Email" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Phone<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Phone,phone,Enter Candidate Phone Number,required,n_trash]" value="Enter Candidate Phone Number" placeholder="Enter Candidate Phone Number" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Social Security Number<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[personal_info][text,Social Security Number,security,Enter Candidate Social Security Number,required,n_trash]" value="Enter Candidate Social Security Number" placeholder="Enter Candidate Social Security Number" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">VASC ID</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[personal_info][text,VASC ID,vasc_id,Enter Candidate VASC ID,]" data-id="vasc_id" value="Enter Candidate VASC ID" placeholder="Enter Candidate VASC ID">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Documents</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[personal_info][,Documents,document_file,,,]" data-id="document_file">
                                                </div>`
                    var billing_info = `<h5 style="background-color: lightgrey;">Billing Info</h5>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Reference<br>(Invoice Recipient)<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[billing_info][text,Reference<br>(Invoice Recipient),pref,Enter Candidate Reference,required,n_trash]" value="Enter Candidate Reference" placeholder="Enter Candidate Reference" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Reference<span class="star text-danger">*</span></label>
                                                    <input type="text" name="form_builder[billing_info][text,Reference,ref,Enter Candidate Reference,required,n_trash]" placeholder="Enter Candidate Reference" value="Enter Candidate Reference" class="form-control">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Invoice Comment</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" name="form_builder[billing_info][text,Invoice Comment,comment,Enter Invoice Comment,]" data-id="comment" value="Enter Invoice Comment" placeholder="Enter Invoice Comment">
                                                </div>
                                                <div class="col-md-12 mb-2 sortable-items">
                                                    <label ondblclick="change_type(this)" onblur="change_label(this)">Note</label>
                                                    <button type="button" class="btn btn-danger btn-sm float-right" onclick="remove_field(this)"><span class="bi bi-trash"></span></button>
                                                    <button type="button" class="btn btn-sm float-right" onclick="change_required(this)"><span class="bi bi-star"></span></button>
                                                    <input type="text" class="form-control" data-id="note" name="form_builder[billing_info][text,Note,note,Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.,]" value="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual." placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.">
                                                </div>`
                    $('#personal_info_row').empty()
                    $('#personal_info_row').append(personal_info)
                    $('#billing_info_row').empty()
                    $('#billing_info_row').append(billing_info)
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
        initial_sortable()
    }
    function initial_sortable() {
        // Make the list sortable
        $("#personal_info_row").sortable({
            cursor: "move", // Set cursor to indicate draggable items
        });
        $("#billing_info_row").sortable({
            cursor: "move", // Set cursor to indicate draggable items
        });
    }
    function save_form_builder(obj) {
        var ser_id = $('#service_types').val();
        var id = <?php echo $_GET['id']; ?>;
        var formData = new FormData($(obj).closest("form")[0]);
        formData.append('save_form_builder', 1);
        formData.append('cus_id', id);
        formData.append('ser_id', ser_id);
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    if (response.success != '') {
                        alert(response.success);
                    }
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    }
    function openCity(evt, cityName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(cityName).style.display = "block";
        if (evt && evt.currentTarget) {
            evt.currentTarget.className += " active";
        } else {
            evt.addClass('active')
        }

                // Initialize emails DataTable when emails tab is opened
                if (cityName === 'emails') {
            setTimeout(function() {
                if (!$.fn.DataTable.isDataTable('#emails_table')) {
                    var emailsTable = $('#emails_table').DataTable({
                        language: { search: "", searchPlaceholder: "Search emails..." },
                        dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>t<'row'<'col-sm-6'i><'col-sm-6'p>>",
                        scrollX: true,
                        autoWidth: false,
                        order: [[3, 'desc']], // Order by date descending
                        pageLength: 10,
                        processing: true,
                        serverSide: true,
                        search: false, // Disable default search
                        columns: [
                            { title: "Order ID", data: 0, width: "12%" },
                            { title: "Email Type", data: 1, width: "18%" },
                            { title: "Email", data: 2, width: "18%" },
                            { title: "Date", data: 3, width: "15%" },
                            { 
                                title: "Text", 
                                data: 4, 
                                width: "30%",
                                render: function(data, type, row) {
                                    if (type === 'display' && data) {
                                        // Display content in textarea format
                                        return data;
                                    }
                                    return data;
                                }
                            },
                            { 
                                title: "Action", 
                                data: 5, 
                                orderable: false, 
                                width: "7%",
                                render: function(data, type, row) {
                                    if (type === 'display') {
                                        return '<button name="resend" value="' + (row[0] || '') + '" class="btn-primary-sm bg-primary resend_btn">Resend</button>';
                                    }
                                    return data;
                                }
                            }
                        ],
                        ajax: {
                            url: '../includes/pages.php',
                            type: 'POST',
                            data: function (d) {
                                d.action = 'get_customer_emails';
                                d.customer_email = $('#customer_email').val() || '';
                                return d;
                            },
                            complete: function() {
                                // Re-initialize any form elements if needed
                            },
                            drawCallback: function() {
                                // Fix header alignment and ensure full width after data loads
                                var table = $('#emails_table').DataTable();
                                $('#emails_table').css('width', '100%');
                                table.columns.adjust().draw();
                                
                                // Ensure table maintains proper styling
                                $('#emails_table').css({
                                    'width': '100%',
                                    'table-layout': 'auto'
                                });
                            }
                        }
                    });
                    // Add deferred search functionality
                    emailsTable.on('init.dt', function(){ 
                        // Get the wireDeferredSearch function from the global scope
                        if (typeof wireDeferredSearch === 'function') {
                            wireDeferredSearch(emailsTable);
                        }
                        // Ensure proper header alignment and full width on initialization
                        setTimeout(function() {
                            emailsTable.columns.adjust().draw();
                            // Force table to take full width
                            $('#emails_table').css('width', '100%');
                            emailsTable.columns.adjust();
                            
                            // Ensure proper table styling
                            $('#emails_table').css({
                                'width': '100%',
                                'table-layout': 'auto'
                            });
                        }, 100);
                    });
                    
                    // Handle window resize to maintain full width
                    $(window).on('resize', function() {
                        if ($.fn.DataTable.isDataTable('#emails_table')) {
                            $('#emails_table').css('width', '100%');
                            $('#emails_table').DataTable().columns.adjust();
                        }
                    });
                }
            }, 100);
        }
    }
    document.getElementById("defaultOpen").click();
    $('body').on('click', '.dropdown li', function() {
        var textArea = $(this).closest('.dropdown').siblings('textarea')
        var cursorPos = textArea.prop('selectionStart');
        var v = textArea.val();
        var textBefore = v.substring(0, cursorPos);
        var textAfter = v.substring(cursorPos, v.length);
        textArea.val(textBefore + $(this).text() + textAfter)
    })
</script>
<script>
    var customer = <?php echo json_encode($customer); ?>;
    var id = <?php echo $_GET['id']; ?>;
    // Fetch Messages
    $("#service-messages").on("change", function(e) {
        e.preventDefault()
        $("#update_customer_msg").html($("#spinner").html())
        var sid = $(this).val()
        var formData = new FormData();
        formData.append('type', 'fetch_messages_cus');
        formData.append('id', id);
        formData.append('sid', sid);
        // Send the data to the server
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            processData: false,
            contentType: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    if (response.messages) {
                        $(".messages").empty()
                        for (const col in response.messages) {
                            var messageTemplate = $("#messageTemplate").html()
                            messageTemplate = messageTemplate.replaceAll("{col}", col)
                                .replace("{message}", response.messages[col])
                            $(".messages").append(messageTemplate)
                        }
                    }
                } else {
                    flash("errorMsg", "Error fetching data!")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    })
    // Update Customer
    $("#update_customer_btn").on("click", function(e) {
        e.preventDefault()
        $(this).prop("disabled", true);
        $("#update_customer_msg").html($("#spinner").html())
        var formData = new FormData($(this).closest("form")[0]);
        
        // Remove the original multi-select arrays and add comma-separated strings
        formData.delete('combine_bk_and_security[]');
        formData.delete('combine_status[]');
        
        // Get selected services and convert to comma-separated string
        var selectedServices = $('#combine_services option:selected');
        var servicesString = '';
        if (selectedServices.length > 0) {
            servicesString = selectedServices.map(function() { return this.value; }).get().join(',');
        }
        formData.append('combine_bk_and_security', servicesString);
        
        // Get selected statuses and convert to comma-separated string
        var selectedStatuses = $('#combine_statuses option:selected');
        var statusesString = '';
        if (selectedStatuses.length > 0) {
            statusesString = selectedStatuses.map(function() { return this.value; }).get().join(',');
        }
        formData.append('combine_status', statusesString);
        formData.append('type', 'update_customer');
        formData.append('id', id);
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    flash("successMsg", "Customer updated successfully!")
                    that.prop("disabled", false);
                    $("#update_customer_msg").html("")
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    })
    // Resend Email
    $('body').on('click', '.resend_btn', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop("disabled", true);
        $("#resend_msg").html($("#spinner").html());
        // Get the email data from the DataTable row
        var table = $('#emails_table').DataTable();
        var rowData = table.row($btn.closest('tr')).data();
        console.log('Row data:', rowData);
        
        var formData = new FormData();
        formData.append('type', 'resend_mail_cus');
        formData.append('id', id);
        formData.append('resend', $btn.val());
        formData.append('count', '1'); // Add count parameter
        
        // Add email data as arrays (as expected by server)
        formData.append('user_type[]', 'Customer'); // Default user type
        formData.append('order_id[]', rowData[0]); // Order ID from first column
        formData.append('msg_type[]', rowData[1]); // Email type from second column
        formData.append('email[]', rowData[2]); // Email from third column
        formData.append('name[]', 'Customer'); // Default name
        formData.append('text[]', rowData[4]); // Text from fifth column
        formData.append('subject[]', 'Email Resend'); // Default subject
        // Send the data to the server
        var that = $btn;
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                console.log('Server response:', response);
                if (response.success) {
                    flash("successMsg", "Email resent successfully!")
                    that.prop("disabled", false);
                    $("#resend_msg").html("")
                } else {
                    console.log('Error from server:', response.error);
                    flash("errorMsg", "Error saving data: " + (response.error || 'Unknown error'))
                }
            },
            error: function(e) {
                console.log(e.responseText)
                alert("AJAX request failed!");
                that.prop("disabled", false);
                $("#resend_msg").html("")
            }
        });
    })
    // Update Messages
    $("#update_msg_btn").on("click", function(e) {
        e.preventDefault()
        $(this).prop("disabled", true);
        $("#update_message_msg").html($("#spinner").html())
        var sid = $("#service-messages").val()
        var formData = new FormData($(this).closest("form")[0]);
        formData.append('type', 'update_messages');
        formData.append('id', id);
        formData.append('sid', sid);
        // Send the data to the server
        var that = $(this)
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    flash("successMsg", "Messages updated successfully!")
                    that.prop("disabled", false);
                    $("#update_message_msg").html("")
                } else {
                    flash("errorMsg", "Error saving data!")
                }
            },
            error: function(e) {
                console.log(e.responseText)
                alert("AJAX request failed!");
            }
        });
    })
    $(".totalInvoiced").click(function() {
        $(".totalInvoicedBtn").click()
    })
    function add_question_data(obj) { // function to add or update customer questions
        var formData = new FormData($(obj).closest("form")[0]);
        formData.append('add_customer_question', 1);
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: formData,
            contentType: false,
            processData: false,
            dataType: "json",
            success: function(response) {
                if (response.success != '') {
                    alert(response.success)
                }
            },
        });
    }
    $(document).ready(function() {
        if ($(".filter-select").length) {
            $('.filter-select').select2();
        }
        $('.select2tag').select2({
            tags: true
        });
    })
    function change_type(obj) {
        $(obj).attr('contenteditable', 'true').focus();
    }
    function additional_customer_data(obj) {
        var id = $(obj).attr('id');
        var currentObject = $(obj);
        if (id != '') {
            if (id == 'add_ad_cu_btn') {
                container = $('#add_additional_customers');
                name = container.find('input[name="add_cus_name"]').val();
                cus_id = container.find('input[name="add_cus_id"]').val();
                email = container.find('input[name="add_cus_email"]').val();
                if (name == '') {
                    alert('Please enter additional customer name');
                    return;
                }
                if (email == '') {
                    alert('Please enter customer email');
                    return;
                }
                $.ajax({
                    type: "POST",
                    url: "./includes/table_ajax.php",
                    data: {
                        name: name,
                        email: email,
                        cus_id: cus_id,
                        add_additional_customer: 1,
                    },
                    success: function(response) {
                        response = JSON.parse(response);
                        if (response.error && response.error != '' && response.error != undefined) {
                            alert(response.error)
                        }
                        if (response.success && response.success != '' && response.success != undefined) {
                            alert(response.success)
                            html = `<tr>
                                    <td>` + name + `</td>
                                    <td>` + email + `</td>
                                    <td style="width:6% !important">
                                        <div class="dropdown">
                                            <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" >
                                            <li class="mb-1"><a href="#" onclick="openCity(event, 'update_additional_customers'),get_ad_cu_data(this)" data-id="` + response.last_id + `" data-name="` + response.name + `" data-email="` + response.email + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                        Edit</a>
                                                </li>
                                                <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" onclick="delete_ad_cu(this)" data-id="` + response.last_id + `" data-type="2"><i class="bi bi-trash  f-14 text-black me-2"></i>
                                                         Delete</a>
                                                </li> 
                                            </ul>
                                        </div>
                                    </td>
                                </tr>`;
                            $('#additional_customers').find('tbody').append(html)
                            $('#additional_customers').find('tbody').find('.no_record_found').remove();
                            $('#add_additional_customers').find('input[name="add_cus_name"]').val('');
                            $('#add_additional_customers').find('input[name="add_cus_email"]').val('');
                            container.find('.back_btn').click()
                        }
                    }
                });
            }
            if (id == 'update_ad_cu_btn') {
                container = $('#update_additional_customers');
                name = container.find('input[name="add_cus_name_u"]').val();
                email = container.find('input[name="add_cus_email_u"]').val();
                id = container.find('input[name="add_cus_id_u"]').val();
                $.ajax({
                    type: "POST",
                    url: "./includes/table_ajax.php",
                    data: {
                        name: name,
                        email: email,
                        id: id,
                        update_additional_customer: 1,
                    },
                    success: function(response) {
                        if (response != '') {
                            response = JSON.parse(response);
                            if (response.error && response.error != '' && response.error != undefined) {
                                alert(response.error)
                            }
                            if (response.success && response.success != '' && response.success != undefined) {
                                alert(response.success)
                                $($('#additional_customers').find('tbody').find('tr').find('a')).each(function() {
                                    if ($(this).data('id') == id) {
                                        $(this).closest('tr').find('td:first').text(name);
                                        $(this).closest('tr').find('td:eq(1)').text(email);
                                        const cell = $(this).closest('tr').find('td:eq(2)');
                                        const action = 
                                        `<div class="dropdown">
                                                    <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                        <i class="bi bi-gear"></i>
                                                    </button>
                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" >
                                                    <li class="mb-1"><a href="#" onclick="openCity(event, 'update_additional_customers'),get_ad_cu_data(this)" data-id="` + response.last_id + `" data-name="` + response.name + `" data-email="` + response.email + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                Edit</a>
                                                        </li>
                                                        <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" onclick="delete_ad_cu(this)" data-id="` + response.last_id + `" data-type="2"><i class="bi bi-trash  f-14 text-black me-2"></i>
                                                                Delete</a>
                                                        </li> 
                                                    </ul>
                                                </div>
                                        `;
                                        cell.html(action);
                                    }
                                })
                                
                                
                                
                                container.find('.back_btn').click();
                            }
                        }
                    }
                });
            }
        }
    }
    function get_ad_cu_data(obj) {
        var id = $(obj).data('id');
        var name = $(obj).data('name');
        var email = $(obj).data('email');
        container = $('#update_additional_customers');
        container.find('input[name="add_cus_name_u"]').val(name);
        container.find('input[name="add_cus_email_u"]').val(email);
        container.find('input[name="add_cus_id_u"]').val(id);
    }
    function delete_ad_cu(obj) {
        var id = $(obj).data('id');
        $.ajax({
            type: "POST",
            url: "./includes/table_ajax.php",
            data: {
                id: id,
                delete_ad_cu: 1,
            },
            success: function(response) {
                if (response != '') {
                    response = JSON.parse(response);
                    if (response.error && response.error != '' && response.error != undefined) {
                        alert(response.error)
                    }
                    if (response.success && response.success != '' && response.success != undefined) {
                        alert(response.success)
                        $(obj).closest('tr').remove()
                    }
                }
            }
        });
    }
</script>