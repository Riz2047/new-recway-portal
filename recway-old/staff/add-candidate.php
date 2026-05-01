<?php
$activeLink = "candidates";
include_once('includes/header.php');
if (isset($_POST['order'])) {
    $vasc_id = isset($_POST['vasc_id']) ? $_POST['vasc_id'] : null;
    $security = $_POST['security'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $referensperson = $_POST['pref'];
    $reference = $_POST['ref'];
    $cus_id = $_POST['customer'];
    $interview_id = $_POST['interview'];
    $comment = isset($_POST['comment']) ? $_POST['comment'] : null;
    $note = isset($_POST['note']) ? $_POST['note'] : null;
    $sendMail = $_POST['sendMail'];
    $sendMailCan = $_POST['sendMailCan'];
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $staff_id = isset($_POST['staff']) ? $_POST['staff'] : 0;
    $country = isset($_POST['country']) ? $_POST['country'] : null;
    $form_builder = isset($_POST['form_builder']) ? $_POST['form_builder'] : null;
    $security_interview_service_type = isset($_POST['security_interview_service_type']) ? $_POST['security_interview_service_type'] : $customer->combine_interview_id;
    $hasPersonalId = isset($_POST['hasPersonalId']) ? $_POST['hasPersonalId'] : 0;
    $meta_info = [
        'send_email_cus' => $sendMail,
        'send_email_can' => $sendMailCan,
        'created_by' => $_SESSION['staff']->id,
        'created_on' => date('Y-m-d H:i:s'),
        'user' => 'Staff',
    ];
    if (! empty($meta_info)) {
        $meta_info = json_encode($meta_info);
    }
    if (! empty($form_builder)) {
        $form_builder = json_encode($form_builder);
    }
    $query = "SELECT * FROM candidates";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
    $query = 'SELECT * FROM customers WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$cus_id]);
    $customer = $stmt->fetch();
    $selectedServiceCategoryId = null;
    $query = 'SELECT service_cat_id FROM interviews WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $selectedInterview = $stmt->fetch();
    if (! empty($selectedInterview)) {
        $selectedServiceCategoryId = $selectedInterview->service_cat_id;
    }

    // Check for duplicate candidate within the same company
    $company = trim($customer->company);

    // Find all customers in the same company
    $query = "SELECT id FROM customers WHERE TRIM(company) = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$company]);
    $companyCustomerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (! empty($companyCustomerIds)) {
        $placeholders = implode(',', array_fill(0, count($companyCustomerIds), '?'));
        $isPNR = preg_match('/^(\d{6}|\d{8})-?\d{4}$/', $security);

        $query = "SELECT c.id FROM candidates c
                        LEFT JOIN interviews i ON c.interview_id = i.id
						WHERE cus_id IN ($placeholders) 
						AND (email = ? OR phone = ?";

        $params = array_merge($companyCustomerIds, [$email, $phone]);

        if ($isPNR) {
            // Normalize input: remove dash for comparison
            $normalizedSecurity = str_replace('-', '', $security);
            $query .= " OR REPLACE(security, '-', '') = ?";
            $params[] = $normalizedSecurity;
        }

        $query .= ")";
        if (! empty($selectedServiceCategoryId)) {
            $query .= " AND i.service_cat_id = ?";
            $params[] = $selectedServiceCategoryId;
        }
        $query .= " AND c.expired = 0 LIMIT 1";
        $stmt = $conn->prepare($query);
        $stmt->execute($params);
        $duplicate = $stmt->fetch();

        if ($duplicate) {
            flash("candidateAdded", "Candidate already registered: This candidate is already registered in your company.", "errorMsg");
            echo "<script>window.location.href='" . $_SERVER['PHP_SELF'] . "';</script>";
            exit;
        }
    }

    $order_ids = [];
    if (! empty($candidates)) {
        foreach ($candidates as $candidate) {
            array_push($order_ids, $candidate->order_id);
        }
    }
    $permitted_chars = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $uid = substr(str_shuffle($permitted_chars), 0, 6);
    while (in_array($uid, $order_ids)) {
        $uid = substr(str_shuffle($permitted_chars), 0, 6);
    }
    $query = 'SELECT * FROM interviews WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$interview_id]);
    $interview = $stmt->fetch();
    if (! empty($interview->place) || $security_interview_service_type == 2) {
    } else {
        $place = null;
    }
    if ($interview->service_cat_id == 1) {
        $statusID = 1;
    } elseif ($interview->service_cat_id == 3) {
        $statusID = 13;
    } elseif ($interview->service_cat_id == 9) {
        $statusID = 33;
    } elseif ($interview->service_cat_id == 10) {
        $statusID = 49;
    }
    $query = "SELECT * FROM customer_services WHERE cus_id = ? AND service_id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_POST['customer'], $interview_id]);
    $data = $stmt->fetch();
    $service_cost = '';
    if ($data->service_cost != 0) {
        $service_cost = $data->service_cost;
    } else {
        $query = "SELECT * FROM interviews WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$interview_id]);
        $data = $stmt->fetch();
        $service_cost = $data->cost;
    }
    $query = "INSERT INTO candidates (order_id, vasc_id, security, name, surname, email, phone, place, country, cv, referensperson, reference, comment, note, cus_id, interview_id, status,staff_id, meta_data,interview_template,meta_info, service_cost,combine_interview_id, hasPersonalId) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);
    $files = null;
    if (! empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);
        $filesArray = []; // to store filenames temporarily
        for ($i = 0; $i < $totalFiles; $i++) {
            // $fileName = time() . '-' . $_FILES['files']['name'][$i];
            // $fileName = str_replace(",", "", $fileName);
            // $files .= $fileName . ',';
            // // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            // move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName);
            // $originalName = $_FILES['files']['name'][$i];
            // $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            // $fileName = time() . '-' . uniqid() . '.' . $fileExtension;
            $originalName = $_FILES['files']['name'][$i];
            $fileName = time() . '-' . str_replace(",", "", $originalName);
            $filesArray[] = $fileName;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName);
        }
        $files = implode(',', $filesArray);
    }
    $template_file = null;
    if (! empty($_FILES['template']['name'])) {
        $fileName = time() . '-' . $_FILES['template']['name'];
        $fileName = str_replace(",", "", $fileName);
        $template_file = $fileName;
        move_uploaded_file($_FILES['template']['tmp_name'], '../uploads/' . $fileName);
    }
    $res = $stmt->execute([$uid, $vasc_id, $security, $name, $surname, $email, $phone, $place, $country, isset($files) ? $files : null, $referensperson, $reference, $comment, $note, $cus_id, $interview_id, $statusID, $staff_id, $form_builder, $template_file, $meta_info, $service_cost, $security_interview_service_type, $hasPersonalId]);
    if ($res) {
        $lastInsertId = $conn->lastInsertId();
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$lastInsertId]);
        $candidate = $stmt->fetch();
        $query = 'SELECT * FROM customers WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$cus_id]);
        $customer = $stmt->fetch();
        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();
        $query = 'SELECT * FROM staff WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$interview_id]);
        $interview = $stmt->fetch();
        $query = 'SELECT * FROM service_categories WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$interview->service_cat_id]);
        $serviceCat = $stmt->fetch();
        $query = "INSERT INTO history (order_id, `desc`) VALUES (?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$lastInsertId, 'Order Created']);
        $messages = [];
        // Create a DateTime object for Sweden's timezone
        $swedenTimezone = new DateTimeZone('Europe/Stockholm');
        $swedenTime = new DateTime('now', $swedenTimezone);
        $currentTime = $swedenTime->format('H:i:s');
        $dayOfWeek = date('N');
        $messages = getMessages($cus_id, $interview->id);
        if (! empty($messages)) {
            // email msg for customer
            if ($sendMail == 'yes') {
                $cus_msg = $interview->service_cat_id == 1 || $interview->service_cat_id == 9 ? $messages->cus_msg : $messages->cus_msg;
                $cusBody = replace($cus_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name);
                    $mailMsg = sendMail($cusBody, $customer->email, $customer->name, $interview->title);
                } else {
                    saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name, '1');
                }
            }
            if ($sendMailCan == 'yes') {
                if ($interview->service_cat_id == 1) {
                    $statusID = 1;
                } elseif ($interview->service_cat_id == 3) {
                    $statusID = 13;
                } elseif ($interview->service_cat_id == 9) {
                    $statusID = 33;
                } elseif ($interview->service_cat_id == 10) {
                    $statusID = 49;
                }
                $msg = getStatusMessage($statusID, $interview_id, $cus_id);
                if ($msg) {
                    $msg = $msg->col;
                }
                // staff if assigned email msg
                if (! empty($staff_id)) {
                    $staff_msg = getMessages($candidate->cus_id, $interview->id);
                    if (empty($staff_msg)) {
                        $staff_msg = getMessages();
                    }
                    $body = replace($staff_msg->staff_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, $staff->name, '', '', '', '', $candidate->order_id, '', '', $comment, $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                        saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned');
                        sendMail($body, $staff->email, $staff->name, "Candidate Assigned");
                    } else {
                        saveEmail("Staff", $staff->name, $candidate->order_id, 'Staff Message', $body, $staff->email, 'Candidate Assigned', '1');
                    }
                }
                // email msg for candidate
                $canBody = replace($msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name);
                    $mailMsg = sendMail($canBody, $_POST['email'], $_POST['name'], $serviceCat->name);
                } else {
                    saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name, '1');
                }
            }
            if (empty($messages->admin_msg)) {
                $messages->admin_msg = 'Order has been created successfully For ' . $customer->name . '(customer) and OrderID is' . $candidate->order_id;
            }
            $adminBody = replace($messages->admin_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, ! empty($place) ? $place->name : '');
            $query = 'SELECT * FROM admin LIMIT 1';
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $admin = $stmt->fetch();
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');
                $mailMsg = sendMail($adminBody, $admin->email, $admin->name, "Order Created");
            } else {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created', '1');
            }
        } else {
            delete('candidates', 'id', $lastInsertId);
            delete('history', 'order_id', $lastInsertId);
            $fail = 1;
        }
        if (isset($fail) && $fail == 1) {
            flash("candidateAdded", "Data save error due to lack of email messages!", "errorMsg");
        } else {
            flash("candidateAdded", "Candidate created successfully!");
        }
    } else {
        flash("candidateAdded", "Data save error!");
    }
}
$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$interviews = $stmt->fetchAll();
$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();
$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll();
?>
<?php flash("candidateAdded"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Add Candidate</h1>
                    </div>
                    <form class="update-form" method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-md-12">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="customer">Customer</label>
                                        <select id="customer" name="customer" class="form-control filter-select"
                                            onchange="get_form_of();change_services()">
                                            <?php foreach ($customers as $customer): ?>
                                                <option
                                                    data-template="<?php echo isset($customer->interview_template) && $customer->interview_template == 1 ? '1' : '' ?>"
                                                    data-combine-bk-and-security="<?php echo $customer->combine_bk_and_security ?>"
                                                    data-customer-id="<?php echo $customer->id ?>"
                                                    <?php echo isset($cus_id) && $cus_id == $customer->id ? 'selected' : '' ?>
                                                    value="<?php echo $customer->id ?>"><?php echo $customer->name ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php
                                    // Background-only mode: filter to only Background Check (service_cat_id = BACKGROUND_ID / 3)
                                    if (function_exists('getStaffAllowedPermissions')) {
                                        getStaffAllowedPermissions(); // ensures $_SESSION['user_category'] is set
                                    }
$userCategory = $_SESSION['user_category'] ?? null;
$hasBackgroundPermission = function_exists('staffHasPermission') && staffHasPermission('view_background_orders');
$backgroundServiceCategoryId = defined('BACKGROUND_ID') ? BACKGROUND_ID : 3;
if ($userCategory == 5 && $hasBackgroundPermission) {
    // Filter to only show Background Check services
    $interviews = array_filter($interviews, function ($interview) use ($backgroundServiceCategoryId) {
        return (int)$interview->service_cat_id === (int)$backgroundServiceCategoryId;
    });
    // Re-index array after filtering
    $interviews = array_values($interviews);
}
?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="interview">Service Type</label>
                                        <select id="interview" name="interview" class="form-control filter-select" onchange="get_form_of();check_p_c(this);check_combine_bk_and_security()">
                                            <?php foreach ($interviews as $interview) : ?>
                                                <option value="<?php echo $interview->id ?>" <?php echo isset($interview_id) && $interview_id == $interview->id ? 'selected' : '' ?>><?php echo $interview->title ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <select id="hidden_interview" style="display:none">
                                        <?php foreach ($interviews as $interview) : ?>
                                            <option value="<?php echo $interview->id ?>" data-country="<?= $interview->country ?>"  data-interview-service-cat-id="<?php echo $interview->service_cat_id ?>"data-place="<?= $interview->place ?>"><?php echo $interview->title ?></option>
                                        <?php endforeach; ?>
                                    </select>
                            <div class="col-md-12 col-sm-12 mb-2 d-none" id="security_interview_service_type_div">
                              <label class="form-label" for="security_interview_service_type">Security Interview Service Type</label>
                              <!-- <select class="form-control" onchange="fetch_form_security_interview_service_type(this);" id="security_interview_service_type" name="security_interview_service_type" required="true"> -->
                              <select class="form-control" onchange="check_combine_bk_and_security()" id="security_interview_service_type" name="security_interview_service_type">
                              <option value="0">Select Security Interview Service Type</option>
                              <?php foreach ($interviews as $interview): ?>
                                <?php if ($interview->service_cat_id == 1): ?>
                                                <option value="<?php echo $interview->id ?>" <?php echo isset($interview_id) && $interview_id == $interview->id ? 'selected' : '' ?>>
                                                    <?php echo $interview->title ?>
                                                </option>
                                                <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                            </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label" for="interview">Staff</label>
                                        <select id="staff" name="staff" class="form-control filter-select">
                                            <option value="">Select Staff</option>
                                            <?php if (! empty($staff)) { ?>
                                                <?php foreach ($staff as $staf) { ?>
                                                    <option value="<?= $staf->id ?>"><?= $staf->name ?></option>
                                                <?php } ?>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <span>Send Mail - Customer</span>
                                        <div class="">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sendMail" value="yes" checked id="gridCheck">
                                                <label class="form-label form-check-label" for="gridCheck">
                                                    Yes
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sendMail" value="no" id="gridCheck2">
                                                <label class="form-label form-check-label" for="gridCheck2">
                                                    No
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <span>Send Mail - Candidate</span>
                                        <div class="">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sendMailCan" value="yes" checked id="gridCheck3">
                                                <label class="form-label form-check-label" for="gridCheck3">
                                                    Yes
                                                </label>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sendMailCan" value="no" id="gridCheck4">
                                                <label class="form-label form-check-label" for="gridCheck4">
                                                    No
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="personal_info_row">
                                    <div class="form-check col-md-12 col-sm-12 mb-2" id="hasPersonalIdWrapper">
                                        <input class="form-check-input" type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" onchange="toggleInputType()">
                                        <label class="form-check-label" for="hasPersonalId">
                                            Has Personal Identification Number
                                        </label>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" id="ssnLabel" for="ssn">Social Security Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="security" required id="ssn" placeholder="YYMMDD-XXXX">
                                        <small id="pnrHelp" class="form-text"></small>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="vasc_id">VASC ID</label>
                                        <input type="text" class="form-control" name="vasc_id" id="vasc_id">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" class="form-control" name="name" required id="name">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="surname">Surname</label>
                                        <input type="text" class="form-control" name="surname" required id="surname">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-control" name="email" required id="email">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="phone">Phone</label>
                                        <input type="text" class="form-control" name="phone" required id="phone">
                                    </div>
                                </div>
                                <div class="col-md-12 mb-3 d-none pl-0 pr-0" id="place">
                                    <label class="form-label" for="place">Place</label>
                                    <select id="place" name="place" class="form-control filter-select">
                                        <?php if (! empty($places)) : ?>
                                            <?php foreach ($places as $place) : ?>
                                                <option value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3 d-none pl-0 pr-0" id="country">
                                    <label class="form-label" for="">Country</label>
                                    <select id="" disabled name="country" class="form-control filter-select">
                                        <option value="Afghanistan">Afghanistan</option>
                                        <option value="Aland Islands">Aland Islands</option>
                                        <option value="Albania">Albania</option>
                                        <option value="Algeria">Algeria</option>
                                        <option value="American Samoa">American Samoa</option>
                                        <option value="Andorra">Andorra</option>
                                        <option value="Angola">Angola</option>
                                        <option value="Anguilla">Anguilla</option>
                                        <option value="Antarctica">Antarctica</option>
                                        <option value="Antigua and Barbuda">Antigua and Barbuda</option>
                                        <option value="Argentina">Argentina</option>
                                        <option value="Armenia">Armenia</option>
                                        <option value="Aruba">Aruba</option>
                                        <option value="Australia">Australia</option>
                                        <option value="Austria">Austria</option>
                                        <option value="Azerbaijan">Azerbaijan</option>
                                        <option value="Bahamas">Bahamas</option>
                                        <option value="Bahrain">Bahrain</option>
                                        <option value="Bangladesh">Bangladesh</option>
                                        <option value="Barbados">Barbados</option>
                                        <option value="Belarus">Belarus</option>
                                        <option value="Belgium">Belgium</option>
                                        <option value="Belize">Belize</option>
                                        <option value="Benin">Benin</option>
                                        <option value="Bermuda">Bermuda</option>
                                        <option value="Bhutan">Bhutan</option>
                                        <option value="Bolivia">Bolivia</option>
                                        <option value="Bonaire, Sint Eustatius and Saba">Bonaire, Sint Eustatius and Saba</option>
                                        <option value="Bosnia and Herzegovina">Bosnia and Herzegovina</option>
                                        <option value="Botswana">Botswana</option>
                                        <option value="Bouvet Island">Bouvet Island</option>
                                        <option value="Brazil">Brazil</option>
                                        <option value="British Indian Ocean Territory">British Indian Ocean Territory</option>
                                        <option value="Brunei Darussalam">Brunei Darussalam</option>
                                        <option value="Bulgaria">Bulgaria</option>
                                        <option value="Burkina Faso">Burkina Faso</option>
                                        <option value="Burundi">Burundi</option>
                                        <option value="Cambodia">Cambodia</option>
                                        <option value="Cameroon">Cameroon</option>
                                        <option value="Canada">Canada</option>
                                        <option value="Cape Verde">Cape Verde</option>
                                        <option value="Cayman Islands">Cayman Islands</option>
                                        <option value="Central African Republic">Central African Republic</option>
                                        <option value="Chad">Chad</option>
                                        <option value="Chile">Chile</option>
                                        <option value="China">China</option>
                                        <option value="Christmas Island">Christmas Island</option>
                                        <option value="Cocos (Keeling) Islands">Cocos (Keeling) Islands</option>
                                        <option value="Colombia">Colombia</option>
                                        <option value="Comoros">Comoros</option>
                                        <option value="Congo">Congo</option>
                                        <option value="Congo, Democratic Republic of the Congo">Congo, Democratic Republic of the Congo</option>
                                        <option value="Cook Islands">Cook Islands</option>
                                        <option value="Costa Rica">Costa Rica</option>
                                        <option value="Cote D'Ivoire">Cote D'Ivoire</option>
                                        <option value="Croatia">Croatia</option>
                                        <option value="Cuba">Cuba</option>
                                        <option value="Curacao">Curacao</option>
                                        <option value="Cyprus">Cyprus</option>
                                        <option value="Czech Republic">Czech Republic</option>
                                        <option value="Denmark">Denmark</option>
                                        <option value="Djibouti">Djibouti</option>
                                        <option value="Dominica">Dominica</option>
                                        <option value="Dominican Republic">Dominican Republic</option>
                                        <option value="Ecuador">Ecuador</option>
                                        <option value="Egypt">Egypt</option>
                                        <option value="El Salvador">El Salvador</option>
                                        <option value="Equatorial Guinea">Equatorial Guinea</option>
                                        <option value="Eritrea">Eritrea</option>
                                        <option value="Estonia">Estonia</option>
                                        <option value="Ethiopia">Ethiopia</option>
                                        <option value="Falkland Islands (Malvinas)">Falkland Islands (Malvinas)</option>
                                        <option value="Faroe Islands">Faroe Islands</option>
                                        <option value="Fiji">Fiji</option>
                                        <option value="Finland">Finland</option>
                                        <option value="France">France</option>
                                        <option value="French Guiana">French Guiana</option>
                                        <option value="French Polynesia">French Polynesia</option>
                                        <option value="French Southern Territories">French Southern Territories</option>
                                        <option value="Gabon">Gabon</option>
                                        <option value="Gambia">Gambia</option>
                                        <option value="Georgia">Georgia</option>
                                        <option value="Germany">Germany</option>
                                        <option value="Ghana">Ghana</option>
                                        <option value="Gibraltar">Gibraltar</option>
                                        <option value="Greece">Greece</option>
                                        <option value="Greenland">Greenland</option>
                                        <option value="Grenada">Grenada</option>
                                        <option value="Guadeloupe">Guadeloupe</option>
                                        <option value="Guam">Guam</option>
                                        <option value="Guatemala">Guatemala</option>
                                        <option value="Guernsey">Guernsey</option>
                                        <option value="Guinea">Guinea</option>
                                        <option value="Guinea-Bissau">Guinea-Bissau</option>
                                        <option value="Guyana">Guyana</option>
                                        <option value="Haiti">Haiti</option>
                                        <option value="Heard Island and Mcdonald Islands">Heard Island and Mcdonald Islands</option>
                                        <option value="Holy See (Vatican City State)">Holy See (Vatican City State)</option>
                                        <option value="Honduras">Honduras</option>
                                        <option value="Hong Kong">Hong Kong</option>
                                        <option value="Hungary">Hungary</option>
                                        <option value="Iceland">Iceland</option>
                                        <option value="India">India</option>
                                        <option value="Indonesia">Indonesia</option>
                                        <option value="Iran, Islamic Republic of">Iran, Islamic Republic of</option>
                                        <option value="Iraq">Iraq</option>
                                        <option value="Ireland">Ireland</option>
                                        <option value="Isle of Man">Isle of Man</option>
                                        <option value="Israel">Israel</option>
                                        <option value="Italy">Italy</option>
                                        <option value="Jamaica">Jamaica</option>
                                        <option value="Japan">Japan</option>
                                        <option value="Jersey">Jersey</option>
                                        <option value="Jordan">Jordan</option>
                                        <option value="Kazakhstan">Kazakhstan</option>
                                        <option value="Kenya">Kenya</option>
                                        <option value="Kiribati">Kiribati</option>
                                        <option value="Korea, Democratic People's Republic of">Korea, Democratic People's Republic of</option>
                                        <option value="Korea, Republic of">Korea, Republic of</option>
                                        <option value="Kosovo">Kosovo</option>
                                        <option value="Kuwait">Kuwait</option>
                                        <option value="Kyrgyzstan">Kyrgyzstan</option>
                                        <option value="Lao People's Democratic Republic">Lao People's Democratic Republic</option>
                                        <option value="Latvia">Latvia</option>
                                        <option value="Lebanon">Lebanon</option>
                                        <option value="Lesotho">Lesotho</option>
                                        <option value="Liberia">Liberia</option>
                                        <option value="Libyan Arab Jamahiriya">Libyan Arab Jamahiriya</option>
                                        <option value="Liechtenstein">Liechtenstein</option>
                                        <option value="Lithuania">Lithuania</option>
                                        <option value="Luxembourg">Luxembourg</option>
                                        <option value="Macao">Macao</option>
                                        <option value="Macedonia, the Former Yugoslav Republic of">Macedonia, the Former Yugoslav Republic of</option>
                                        <option value="Madagascar">Madagascar</option>
                                        <option value="Malawi">Malawi</option>
                                        <option value="Malaysia">Malaysia</option>
                                        <option value="Maldives">Maldives</option>
                                        <option value="Mali">Mali</option>
                                        <option value="Malta">Malta</option>
                                        <option value="Marshall Islands">Marshall Islands</option>
                                        <option value="Martinique">Martinique</option>
                                        <option value="Mauritania">Mauritania</option>
                                        <option value="Mauritius">Mauritius</option>
                                        <option value="Mayotte">Mayotte</option>
                                        <option value="Mexico">Mexico</option>
                                        <option value="Micronesia, Federated States of">Micronesia, Federated States of</option>
                                        <option value="Moldova, Republic of">Moldova, Republic of</option>
                                        <option value="Monaco">Monaco</option>
                                        <option value="Mongolia">Mongolia</option>
                                        <option value="Montenegro">Montenegro</option>
                                        <option value="Montserrat">Montserrat</option>
                                        <option value="Morocco">Morocco</option>
                                        <option value="Mozambique">Mozambique</option>
                                        <option value="Myanmar">Myanmar</option>
                                        <option value="Namibia">Namibia</option>
                                        <option value="Nauru">Nauru</option>
                                        <option value="Nepal">Nepal</option>
                                        <option value="Netherlands">Netherlands</option>
                                        <option value="Netherlands Antilles">Netherlands Antilles</option>
                                        <option value="New Caledonia">New Caledonia</option>
                                        <option value="New Zealand">New Zealand</option>
                                        <option value="Nicaragua">Nicaragua</option>
                                        <option value="Niger">Niger</option>
                                        <option value="Nigeria">Nigeria</option>
                                        <option value="Niue">Niue</option>
                                        <option value="Norfolk Island">Norfolk Island</option>
                                        <option value="Northern Mariana Islands">Northern Mariana Islands</option>
                                        <option value="Norway">Norway</option>
                                        <option value="Oman">Oman</option>
                                        <option value="Pakistan">Pakistan</option>
                                        <option value="Palau">Palau</option>
                                        <option value="Palestinian Territory, Occupied">Palestinian Territory, Occupied</option>
                                        <option value="Panama">Panama</option>
                                        <option value="Papua New Guinea">Papua New Guinea</option>
                                        <option value="Paraguay">Paraguay</option>
                                        <option value="Peru">Peru</option>
                                        <option value="Philippines">Philippines</option>
                                        <option value="Pitcairn">Pitcairn</option>
                                        <option value="Poland">Poland</option>
                                        <option value="Portugal">Portugal</option>
                                        <option value="Puerto Rico">Puerto Rico</option>
                                        <option value="Qatar">Qatar</option>
                                        <option value="Reunion">Reunion</option>
                                        <option value="Romania">Romania</option>
                                        <option value="Russian Federation">Russian Federation</option>
                                        <option value="Rwanda">Rwanda</option>
                                        <option value="Saint Barthelemy">Saint Barthelemy</option>
                                        <option value="Saint Helena">Saint Helena</option>
                                        <option value="Saint Kitts and Nevis">Saint Kitts and Nevis</option>
                                        <option value="Saint Lucia">Saint Lucia</option>
                                        <option value="Saint Martin">Saint Martin</option>
                                        <option value="Saint Pierre and Miquelon">Saint Pierre and Miquelon</option>
                                        <option value="Saint Vincent and the Grenadines">Saint Vincent and the Grenadines</option>
                                        <option value="Samoa">Samoa</option>
                                        <option value="San Marino">San Marino</option>
                                        <option value="Sao Tome and Principe">Sao Tome and Principe</option>
                                        <option value="Saudi Arabia">Saudi Arabia</option>
                                        <option value="Senegal">Senegal</option>
                                        <option value="Serbia">Serbia</option>
                                        <option value="Serbia and Montenegro">Serbia and Montenegro</option>
                                        <option value="Seychelles">Seychelles</option>
                                        <option value="Sierra Leone">Sierra Leone</option>
                                        <option value="Singapore">Singapore</option>
                                        <option value="Sint Maarten">Sint Maarten</option>
                                        <option value="Slovakia">Slovakia</option>
                                        <option value="Slovenia">Slovenia</option>
                                        <option value="Solomon Islands">Solomon Islands</option>
                                        <option value="Somalia">Somalia</option>
                                        <option value="South Africa">South Africa</option>
                                        <option value="South Georgia and the South Sandwich Islands">South Georgia and the South Sandwich Islands</option>
                                        <option value="South Sudan">South Sudan</option>
                                        <option value="Spain">Spain</option>
                                        <option value="Sri Lanka">Sri Lanka</option>
                                        <option value="Sudan">Sudan</option>
                                        <option value="Suriname">Suriname</option>
                                        <option value="Svalbard and Jan Mayen">Svalbard and Jan Mayen</option>
                                        <option value="Swaziland">Swaziland</option>
                                        <option selected value="Sweden">Sweden</option>
                                        <option value="Switzerland">Switzerland</option>
                                        <option value="Syrian Arab Republic">Syrian Arab Republic</option>
                                        <option value="Taiwan, Province of China">Taiwan, Province of China</option>
                                        <option value="Tajikistan">Tajikistan</option>
                                        <option value="Tanzania, United Republic of">Tanzania, United Republic of</option>
                                        <option value="Thailand">Thailand</option>
                                        <option value="Timor-Leste">Timor-Leste</option>
                                        <option value="Togo">Togo</option>
                                        <option value="Tokelau">Tokelau</option>
                                        <option value="Tonga">Tonga</option>
                                        <option value="Trinidad and Tobago">Trinidad and Tobago</option>
                                        <option value="Tunisia">Tunisia</option>
                                        <option value="Turkey">Turkey</option>
                                        <option value="Turkmenistan">Turkmenistan</option>
                                        <option value="Turks and Caicos Islands">Turks and Caicos Islands</option>
                                        <option value="Tuvalu">Tuvalu</option>
                                        <option value="Uganda">Uganda</option>
                                        <option value="Ukraine">Ukraine</option>
                                        <option value="United Arab Emirates">United Arab Emirates</option>
                                        <option value="United Kingdom">United Kingdom</option>
                                        <option value="United States">United States</option>
                                        <option value="United States Minor Outlying Islands">United States Minor Outlying Islands</option>
                                        <option value="Uruguay">Uruguay</option>
                                        <option value="Uzbekistan">Uzbekistan</option>
                                        <option value="Vanuatu">Vanuatu</option>
                                        <option value="Venezuela">Venezuela</option>
                                        <option value="Viet Nam">Viet Nam</option>
                                        <option value="Virgin Islands, British">Virgin Islands, British</option>
                                        <option value="Virgin Islands, U.s.">Virgin Islands, U.s.</option>
                                        <option value="Wallis and Futuna">Wallis and Futuna</option>
                                        <option value="Western Sahara">Western Sahara</option>
                                        <option value="Yemen">Yemen</option>
                                        <option value="Zambia">Zambia</option>
                                        <option value="Zimbabwe">Zimbabwe</option>
                                    </select>
                                </div>
                                <div class="row" id="document_row">
                                    <div class="col-lg-12 mb-3">
                                        <div class="form-group file-area w-100">
                                            <div class="d-flex justify-content-between">
                                                <label for="images" class="form-label">Documents</label>
                                            </div>
                                            <input class="sign-input w-100 " type="file" name="files[]" id="cv" accept="application/pdf" multiple />
                                            <div class="file-dummy sign-input  ">
                                                <div class="success "></div>
                                                <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                <div class="default ">Here you can upload several documents <small>(Document or CV)</small></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="row" id="billing_info_row">
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="pref">Reference (Invoice Recepient)</label>
                                        <input type="text" class="form-control" name="pref" required id="pref">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="ref">Reference</label>
                                        <input type="text" class="form-control" name="ref" required id="ref">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Invoice Comment (Visible on the invoice)
                                        </label>
                                        <br>
                                        <textarea name="comment" id="" style="width: 100%;" rows="6" placeholder="Invoice Comment (Visible on the invoice)"></textarea>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Note
                                        </label>
                                        <br>
                                        <textarea name="note" id="" style="width: 100%;" rows="6" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="order" class="btn-primary bg-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>
<?php
include_once('includes/footer.php');
?>
<script>
        <?php
    // Ensure background-only variables are available in JavaScript scope
    if (function_exists('getStaffAllowedPermissions')) {
        getStaffAllowedPermissions();
    }
$jsUserCategory = $_SESSION['user_category'] ?? null;
$jsHasBackgroundPermission = function_exists('staffHasPermission') && staffHasPermission('view_background_orders');
$jsShowOnlyBackground = ($jsUserCategory == 5 && $jsHasBackgroundPermission);
?>
    var showOnlyBackground = <?php echo $jsShowOnlyBackground ? 'true' : 'false'; ?>;
    var backgroundServiceCategoryId = <?php echo defined('BACKGROUND_ID') ? BACKGROUND_ID : 3; ?>;
    function bindSsnBehavior() {
        // Default: treat as Personal ID input
        const hasPersonalId = document.getElementById('hasPersonalId');
        const ssn = document.getElementById('ssn');
        const pnrHelp = document.getElementById('pnrHelp');
        const ssnLabel = document.getElementById('ssnLabel');
        if (!hasPersonalId || !ssn) return;
        // set default state (unchecked => PNR)
        if (!hasPersonalId.hasAttribute('data-initialized')) {
            hasPersonalId.checked = false;
            hasPersonalId.setAttribute('data-initialized', '1');
        }
        toggleInputType();
        ssn.addEventListener('input', validateSecurityField);
        ssn.addEventListener('blur', validateSecurityField);
    }
    function toggleInputType() {
        const hasPersonalId = document.getElementById('hasPersonalId');
        const securityField = document.getElementById('ssn');
        const ssnLabel = document.getElementById('ssnLabel');
        const pnrHelp = document.getElementById('pnrHelp');
        if (!securityField) return;
        if (!hasPersonalId || !hasPersonalId.checked) {
            securityField.type = 'date';
            securityField.removeAttribute('inputmode');
            securityField.removeAttribute('placeholder');
            securityField.value = '';
            if (ssnLabel) ssnLabel.innerHTML = 'Date of Birth <span class="text-danger">*</span>';
            if (pnrHelp) pnrHelp.textContent = 'Date of birth is required';
        } else {
            securityField.type = 'text';
            securityField.setAttribute('inputmode', 'numeric');
            securityField.placeholder = 'YYMMDD-XXXX';
            securityField.value = '';
            if (ssnLabel) ssnLabel.innerHTML = 'Personal identification number <span class="text-danger">*</span>';
            if (pnrHelp) pnrHelp.textContent = 'Personal identification number is required';
        }
        // Clear validation states
        securityField.classList.remove('is-valid', 'is-invalid');
        if (pnrHelp) pnrHelp.classList.remove('text-success', 'text-danger');
    }
    function validateSecurityField() {
        const hasPersonalId = document.getElementById('hasPersonalId');
        const securityField = document.getElementById('ssn');
        const pnrHelp = document.getElementById('pnrHelp');
        if (!securityField) return;
        securityField.classList.remove('is-valid', 'is-invalid');
        if (pnrHelp) pnrHelp.classList.remove('text-success', 'text-danger');
        if (!hasPersonalId || !hasPersonalId.checked) {
            if (securityField.value.trim() === '') {
                securityField.classList.add('is-invalid');
                if (pnrHelp) { pnrHelp.textContent = 'Date of birth is required'; pnrHelp.classList.add('text-danger'); }
            } else {
                securityField.classList.add('is-valid');
                if (pnrHelp) { pnrHelp.textContent = 'Date of birth is valid'; pnrHelp.classList.add('text-success'); }
            }
        } else {
            const validation = validatePNR(securityField.value);
            if (securityField.value.trim() === '') {
                securityField.classList.add('is-invalid');
                if (pnrHelp) { pnrHelp.textContent = 'Personal identification number is required'; pnrHelp.classList.add('text-danger'); }
            } else if (validation.isValid) {
                securityField.classList.add('is-valid');
                if (pnrHelp) { pnrHelp.textContent = validation.message; pnrHelp.classList.add('text-success'); }
            } else {
                securityField.classList.add('is-invalid');
                if (pnrHelp) { pnrHelp.textContent = validation.message; pnrHelp.classList.add('text-danger'); }
            }
        }
    }
    // function validatePNR(value) {
    //     // Normalize
    //     const raw = (value || '').trim();
    //     // Accept forms: YYMMDD-XXXX, YYYYMMDDXXXX, YYMMDDXXXX, YYMMDD+XXXX
    //     const cleaned = raw.replace(/[^0-9+]/g, '');
    //     let y, m, d, seq;
    //     if (/^\d{12}$/.test(cleaned)) { // YYYYMMDDXXXX
    //         y = parseInt(cleaned.slice(0, 4), 10);
    //         m = parseInt(cleaned.slice(4, 6), 10);
    //         d = parseInt(cleaned.slice(6, 8), 10);
    //         seq = cleaned.slice(8);
    //     } else if (/^\d{10}$/.test(cleaned)) { // YYMMDDXXXX
    //         const yy = parseInt(cleaned.slice(0, 2), 10);
    //         // Infer century: naive 1900/2000 windowing based on current year
    //         const currentYear = new Date().getFullYear() % 100;
    //         const century = yy > currentYear ? 1900 : 2000;
    //         y = century + yy;
    //         m = parseInt(cleaned.slice(2, 4), 10);
    //         d = parseInt(cleaned.slice(4, 6), 10);
    //         seq = cleaned.slice(6);
    //     } else if (/^\d{6}[+\-]?\d{4}$/.test(raw)) { // YYMMDD-XXXX or YYMMDD+XXXX with delimiter kept
    //         const yy = parseInt(raw.slice(0, 2), 10);
    //         const delimiter = raw[6];
    //         const currentYear = new Date().getFullYear() % 100;
    //         let base = yy > currentYear ? 1900 : 2000;
    //         if (delimiter === '+') base -= 100; // 100 years older
    //         y = base + yy;
    //         m = parseInt(raw.slice(2, 4), 10);
    //         d = parseInt(raw.slice(4, 6), 10);
    //         seq = raw.slice(7).replace(/\D/g, '');
    //     } else {
    //         return { isValid: false, message: 'Invalid format. Use YYMMDD-XXXX' };
    //     }
    //     // Basic date check
    //     const dt = new Date(y, m - 1, d);
    //     if (dt.getFullYear() !== y || dt.getMonth() !== m - 1 || dt.getDate() !== d) {
    //         return { isValid: false, message: 'Invalid date in personal number' };
    //     }
    //     // Luhn check on YYMMDDXXXX (10 digits)
    //     const ten = ('' + ('' + (y % 100)).padStart(2, '0') + ('' + m).padStart(2, '0') + ('' + d).padStart(2, '0') + seq).replace(/\D/g, '');
    //     if (!/^\d{10}$/.test(ten)) {
    //         return { isValid: false, message: 'Invalid personal number length' };
    //     }
    //     if (!luhn10(ten)) {
    //         return { isValid: false, message: 'Invalid personal number checksum' };
    //     }
    //     return { isValid: true, message: 'Personal identification number is valid' };
    // }
function validatePNR(pnr) {
        // Check if the PNR is empty (optional field)
        if (!pnr.trim()) {
          return { isValid: false, message: 'Personal identification number is required' };
        }
        // Allow format: YYMMDD-XXXX or YYMMDDXXXX (with or without dash)
        const pnrPattern = /^(\d{6})-?(\d{4})$/;
        const match = pnr.match(pnrPattern);
        if (!match) {
          return { isValid: false, message: 'Required format is YYMMDD-XXXX or YYMMDDXXXX' };
        }
        // Combine the matched groups to get the full 10-digit number
        const cleanPNR = match[1] + match[2];
        // Extract date components
        const year = parseInt(cleanPNR.substring(0, 2));
        const month = parseInt(cleanPNR.substring(2, 4));
        const day = parseInt(cleanPNR.substring(4, 6));
        // Validate year (should be between 00-99, but we'll be more lenient)
        if (year < 0 || year > 99) {
          return { isValid: false, message: 'Invalid year in Personal identification number' };
        }
        // Validate month (should be between 01-12)
        if (month < 1 || month > 12) {
          return { isValid: false, message: 'Invalid month in Personal identification number(01-12)' };
        }
        if (day < 1 || day > 31) {
          return { isValid: false, message: `Invalid day in Personal identification number(01-31)` };
        }
        return { isValid: true, message: 'Personal identification number is valid' };
      }
    function luhn10(num) {
        let sum = 0;
        for (let i = 0; i < num.length; i++) {
            let digit = parseInt(num[i], 10);
            if (i % 2 === 0) { // double even index (0-based) per Swedish PNR on 10-digit string
                digit *= 2;
                if (digit > 9) digit -= 9;
            }
            sum += digit;
        }
        return sum % 10 === 0;
    }
    function check_p_c(obj = null) {
        if (obj == null) {
            var obj_val = $('#interview').val();
        } else {
            var obj_val = $(obj).val();
        }
        var place = $('#hidden_interview').find('option[value=' + obj_val + ']').data('place');
        var country = $('#hidden_interview').find('option[value=' + obj_val + ']').data('country');
        // Only handle place field if security_interview_service_type_div is hidden
        // (i.e., when we're not in the combined BK and security flow)
        if ($('#security_interview_service_type_div').hasClass('d-none')) {
        if (place == 1) {
            $('#place').removeClass('d-none')
            $("select[name='place']").prop("disabled", false)
        } else {
            $('#place').addClass('d-none')
            $("select[name='place']").prop("disabled", true)
        }
        }
        if (country == 1) {
            $('#country').removeClass('d-none')
            $("select[name='country']").prop("disabled", false)
        } else {
            $('#country').addClass('d-none')
            $("select[name='country']").prop("disabled", true)
        }
    }
    function change_services() {
        var cus_id = $('#customer').val()
        var html = '';
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_cus_service': 1,
                'cus_id': cus_id,
            },
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    // Background-only filter: only show service_cat_id = 3 (Background Check)
                    // showOnlyBackground and backgroundServiceCategoryId are defined globally at top of script
                    $(response).each(function(i, e) {
                        // If background-only mode, skip services that aren't Background Check
                        if (showOnlyBackground && e.service_cat_id != backgroundServiceCategoryId) {
                            return; // skip this iteration
                        }
                        html += `<option value="` + e.id + `">` + e.title + `</option>`
                    })
                }
                $('#interview').html(html)
                get_form_of();
                check_p_c(null)
            },
            error: function(e) {
                alert("AJAX request failed!!");
            }
        });
    }
    function get_form_of() {
        var ser_id = $('#interview').val();
        var id = $('#customer').val();
        var template = $('#customer option:selected').data('template');
        var personal_info = '';
        var billing_info = '';
        $.ajax({
            type: "POST",
            url: "../includes/pages.php",
            data: {
                'get_service_form': 1,
                'ser_id': ser_id,
                'cus_id': id,
            },
            dataType: "json",
            success: function(response) {
                if (response != '') {
                    var html = '';
                    response = JSON.parse(response.form);
                    if ("form_builder" in response) {
                        response = response.form_builder;
                        var per_info_html = '';
                        var bil_info_html = '';
                        var doc_file_html = '';
                        if ("personal_info" in response) {
                            personal_info = response.personal_info
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
                                    if (name === 'security') {
                                        // Inject SSN toggle and helper like create_order.blade.php
                                        per_info_html += `<div class="form-check col-md-12 col-sm-12 mb-2" id="hasPersonalIdWrapper">
                                                <input class="form-check-input" type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" onchange="toggleInputType()">
                                                <label class="form-check-label" for="hasPersonalId">Has Personal Identification Number</label>
                                            </div>`;
                                        per_info_html += `<div class="col-lg-6 mb-3">
                                                <label class="form-label" id="ssnLabel">${label} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="security" id="ssn" ${req} placeholder="${placehol || 'YYMMDD-XXXX'}"/>
                                                <small id="pnrHelp" class="form-text"></small>
                                            </div>`;
                                    } else if (name != 'document_file') {
                                        per_info_html += `<div class="col-lg-6 mb-3">
                                                    <label class="form-label">` + label + `</label>
                                                    <input type="` + type + `" class="form-control" `
                                        if (is_new != '') {
                                            per_info_html += `name="form_builder[` + name + `]" `
                                        } else {
                                            per_info_html += `name="` + name + `" `
                                        }
                                        per_info_html += ` ` + req + ` placeholder="` + placehol + `"/> </div>`;
                                    } else {
                                        doc_file_html += `<div class="col-lg-12 mb-3">
                                        <div class="form-group file-area w-100">
                                            <div class="d-flex justify-content-between">
                                                <label for="images" class="form-label">` + label + `</label>
                                            </div>
                                            <input class="sign-input w-100 " type="file" name="files[]" id="cv" accept="application/pdf" multiple />
                                            <div class="file-dummy sign-input  ">
                                                <div class="success "></div>
                                                <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                <div class="default ">Here you can upload several documents <small>(Interview Templates, Documents or CV)</small></div>
                                            </div>
                                        </div>
                                    </div>`
                                    }
                                }
                            });
                        }
                        if ("billing_info" in response) {
                            billing_info = response.billing_info
                            $.each(billing_info, function(p, v) {
                                real_data = p.split(',')
                                if (real_data != '') {
                                    var type = real_data[0] ? real_data[0] : 'text';
                                    var label = real_data[1] ? real_data[1] : '';
                                    var name = real_data[2] ? real_data[2] : '';
                                    var placehol = real_data[3] ? real_data[3] : '';
                                    var req = real_data[4] ? real_data[4] : '';
                                    var is_tra = real_data[5] ? real_data[5] : '';
                                    var is_new = real_data[6] ? real_data[6] : '';
                                    if (name != 'note') {
                                        bil_info_html += `<div class="col-lg-6 mb-3">
                                        <label class="form-label">` + label + `</label>
                                        <input type="` + type + `" class="form-control"`
                                        if (is_new != '') {
                                            bil_info_html += ` name = "form_builder[` + name + `]"`
                                        } else {
                                            bil_info_html += ` name = "` + name + `"`
                                        }
                                        bil_info_html += ` ` + req + ` placeholder="` + placehol + `"/> </div>`
                                    } else {
                                        bil_info_html += `<div class="col-md-12 mb-3">
                                        <label class="form-label">` + label + `
                                        </label>
                                        <br>
                                        <textarea name="note" id="" style="width: 100%;" rows="6" placeholder="` + placehol + `"></textarea>
                                    </div>`
                                    }
                                }
                            })
                        }
                        if (template == 1) {
                            bil_info_html += `<div class="row" id="template_row">
                                        <div class="col-lg-12 mb-3">
                                            <div class="form-group file-area w-100">
                                                <div class="d-flex justify-content-between">
                                                    <label class="form-label">Interview Template</label>
                                                </div>
                                                <input class="sign-input w-100 " type="file" name="template" accept="application/pdf" />
                                                <div class="file-dummy sign-input  ">
                                                    <div class="success "></div>
                                                    <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                    <div class="default ">Here you can upload template<small>(Interview Template)</small></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>`
                        }
                        $('#personal_info_row').html(per_info_html)
                        // Initialize SSN field behavior if present
                        if (document.getElementById('ssn')) {
                            bindSsnBehavior();
                        }
                        $('#billing_info_row').html(bil_info_html)
                        $('#document_row').html(doc_file_html)
                    }
                } else {
                    var personal_info_row = `<div class="form-check col-md-12 col-sm-12 mb-2" id="hasPersonalIdWrapper">
                                        <input class="form-check-input" type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" onchange="toggleInputType()">
                                        <label class="form-check-label" for="hasPersonalId">Has Personal Identification Number</label>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" id="ssnLabel" for="ssn">Social Security Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="security" required id="ssn" placeholder="YYMMDD-XXXX">
                                        <small id="pnrHelp" class="form-text"></small>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="vasc_id">VASC ID</label>
                                        <input type="text" class="form-control" name="vasc_id" id="vasc_id">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="name">Name</label>
                                        <input type="text" class="form-control" name="name" required id="name">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="surname">Surname</label>
                                        <input type="text" class="form-control" name="surname" required id="surname">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="email">Email</label>
                                        <input type="email" class="form-control" name="email" required id="email">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label" for="phone">Phone</label>
                                        <input type="text" class="form-control" name="phone" required id="phone">
                                    </div>`;
                    var document_row = `<div class="col-lg-12 mb-3">
                                        <div class="form-group file-area w-100">
                                            <div class="d-flex justify-content-between">
                                                <label for="images" class="form-label">Documents</label>
                                            </div>
                                            <input class="sign-input w-100 " type="file" name="files[]" id="cv" accept="application/pdf" multiple />
                                            <div class="file-dummy sign-input  ">
                                                <div class="success "></div>
                                                <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                <div class="default ">Here you can upload several documents <small>(Interview Templates, Documents or CV)</small></div>
                                            </div>
                                        </div>
                                    </div>`
                    var billing_info_row = `<div class="col-lg-6 mb-3">
                                        <label class="form-label" for="pref">Reference (Invoice Recepient)</label>
                                        <input type="text" class="form-control" name="pref" required id="pref">
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" for="ref">Reference</label>
                                        <input type="text" class="form-control" name="ref" required id="ref">
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Invoice Comment (Visible on the invoice)
                                        </label>
                                        <br>
                                        <textarea name="comment" id="" style="width: 100%;" rows="6" placeholder="Invoice Comment (Visible on the invoice)"></textarea>
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label class="form-label">Note
                                        </label>
                                        <br>
                                        <textarea name="note" id="" style="width: 100%;" rows="6" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."></textarea>
                                    </div>`
                    if (template == 1) {
                        billing_info_row += `<div class="row" id="template_row">
                                        <div class="col-lg-12 mb-3">
                                            <div class="form-group file-area w-100">
                                                <div class="d-flex justify-content-between">
                                                    <label class="form-label">Interview Template</label>
                                                </div>
                                                <input class="sign-input w-100 " type="file" name="template" accept="application/pdf" />
                                                <div class="file-dummy sign-input  ">
                                                    <div class="success "></div>
                                                    <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                    <div class="default ">Here you can upload template<small>(Interview Template)</small></div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>`
                    }
                    $('#personal_info_row').html(personal_info_row)
                    bindSsnBehavior();
                    $('#document_row').html(document_row)
                    $('#billing_info_row').html(billing_info_row)
                }
                check_p_c(null)
            },
            error: function(e) {
                alert("AJAX request failed!");
            }
        });
    }
    $(document).ready(function() {
        get_form_of();
        change_services();
        check_p_c(null)
        // Bind SSN behavior for initial static markup
        if (document.getElementById('ssn')) {
            bindSsnBehavior();
        }

        // $('#addCandidateForm').on('submit', function(e) {
        //     e.preventDefault();
            
        //     var formData = new FormData(this);
        //     formData.append('type', 'create_candidate');
        //     formData.append('user_type', 'Staff');
            
        //     var btn = $(this).find('button[type="submit"]');
        //     btn.prop('disabled', true).text('Saving...');
            
        //     $.ajax({
        //         type: "POST",
        //         url: "../includes/pages.php",
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         dataType: "json",
        //         success: function(response) {
        //             if (response.success) {
        //                 toastr.success(response.message);
        //                 setTimeout(function() {
        //                     window.location.href = 'candidates.php';
        //                 }, 1500);
        //             } else {
        //                 toastr.error(response.message);
        //                 btn.prop('disabled', false).text('Save');
        //             }
        //         },
        //         error: function() {
        //             toastr.error('An error occurred. Please try again.');
        //             btn.prop('disabled', false).text('Save');
        //         }
        //     });
        // });
    })
    get_form_of();
    change_services();
    check_p_c(null)
function check_combine_bk_and_security(){
        var selectedCustomer = $('#customer option:selected');
        var interview = $('#interview').val();
        var selectedInterview = $('#hidden_interview option[value="' + interview + '"]');
        console.log(selectedCustomer.data('combine-bk-and-security'),'selectedCustomer');
        var combine_bk_and_security = selectedCustomer.length > 0 ? selectedCustomer.data('combine-bk-and-security') : 0;
        var combine_bk_and_security_array = combine_bk_and_security.length > 0 ? combine_bk_and_security.split(',') : 0;
        var service_cat_id = selectedInterview.length > 0 ? selectedInterview.data('interview-service-cat-id') : 0;
        if(combine_bk_and_security_array && combine_bk_and_security_array.includes(selectedInterview.val()) && service_cat_id == 3){
            console.log('Showing security interview service type div');
            $('#security_interview_service_type_div').removeClass('d-none');
            // Initialize place field state when security interview service type div is shown
            var securityServiceType = $('#security_interview_service_type').val();
            console.log(securityServiceType,'hehehehehhe');
            if (securityServiceType == 2) {
                $('div[id="place"]').removeClass('d-none');
                $('select[name="place"]').prop("disabled", false);
            } else {
                $('div[id="place"]').addClass('d-none');
                $('select[name="place"]').prop("disabled", true);
            }
        }else{
            $('#security_interview_service_type_div').addClass('d-none');
            $('#security_interview_service_type').val('0');
        }
       }
</script>