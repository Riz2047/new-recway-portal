<?php
$activeLink = "customers";
include_once('includes/header.php');
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
$query = "SELECT * FROM settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll();
foreach ($settings as $setting) {
    $var = $setting->name;
    $$var = $setting->value;
}

function recway_is_strong_password_14($password)
{
    if (! is_string($password)) {
        return false;
    }
    if (strlen($password) !== 14) {
        return false;
    }
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{14}$/', $password) === 1;
}

function recway_generate_strong_password_14()
{
    $lower = 'abcdefghijklmnopqrstuvwxyz';
    $upper = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $digits = '0123456789';
    $special = '!@#$%^&*()-_=+[]{};:,.?';
    $all = $lower . $upper . $digits . $special;

    $chars = [
        $lower[random_int(0, strlen($lower) - 1)],
        $upper[random_int(0, strlen($upper) - 1)],
        $digits[random_int(0, strlen($digits) - 1)],
        $special[random_int(0, strlen($special) - 1)],
    ];
    while (count($chars) < 14) {
        $chars[] = $all[random_int(0, strlen($all) - 1)];
    }
    shuffle($chars);
    return implode('', $chars);
}

if (isset($_POST['add_customer'])) {
    $email_text = null;
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $company_manager = isset($_POST['company_manager']) ? 1 : 0;
    $pref = ! empty($_POST['pref']) ? $_POST['pref'] : null;
    $ref = ! empty($_POST['ref']) ? $_POST['ref'] : null;
    $comment = ! empty($_POST['comment']) ? $_POST['comment'] : null;
    $parent_customer = ! empty($_POST['parent_customer']) ? $_POST['parent_customer'] : null;
    $cus_department = ! empty($_POST['cus_department']) ? $_POST['cus_department'] : null;
    $interview_template = isset($_POST['interview_template']) ? 1 : 0;
    $interview_upload_allowed = isset($_POST['interview_upload_allowed']) ? 1 : 0;
    $send_security_report = ! empty($_POST['send_security_report']) ? $_POST['send_security_report'] : 0;
    $changed_registration_email = isset($_POST['changed_registration_email']) ? $_POST['changed_registration_email'] : null;
    $reg_email = null;
    // Handle combine services - convert array to comma-separated string
    $combine_bk_and_security = isset($_POST['combine_bk_and_security']) && is_array($_POST['combine_bk_and_security']) ? implode(',', $_POST['combine_bk_and_security']) : '0';
    $timra_report = isset($_POST['timra_report']) ? $_POST['timra_report'] : 0;
    // Handle combine statuses - convert array to comma-separated string
    $combine_status = isset($_POST['combine_status']) && is_array($_POST['combine_status']) ? implode(',', $_POST['combine_status']) : '';
    $combine_interview_id = isset($_POST['combine_interview_id']) ? $_POST['combine_interview_id'] : 0;
    if (! empty($changed_registration_email)) {
        $email_text = $changed_registration_email;
        $reg_email = $changed_registration_email;
    } else {
        if (! empty($parent_customer)) {
            $parent_cus_msg = findByQuery("SELECT * FROM customers WHERE id = '$parent_customer'");
            if (! empty($parent_cus_msg->reg_email)) {
                $email_text = $parent_cus_msg->reg_email;
                $reg_email = $parent_cus_msg->reg_email;
            } else {
                if (! empty($changed_registration_email)) {
                    $email_text = $changed_registration_email;
                    $reg_email = $changed_registration_email;
                } else {
                    $email_text = $cus_reg_msg;
                }
            }
        } else {
            $parent_cus_msg = findByQuery("SELECT * FROM customers WHERE id = '197'");
            if (! empty($parent_cus_msg->reg_email)) {
                $email_text = $parent_cus_msg->reg_email;
                $reg_email = $parent_cus_msg->reg_email;
            } else {
                if (! empty($changed_registration_email)) {
                    $email_text = $changed_registration_email;
                    $reg_email = $changed_registration_email;
                } else {
                    $email_text = $cus_reg_msg;
                }
            }
        }
    }
    $company = $_POST['company'];
    $org_no = $_POST['org_no'];
    $send_email = isset($_POST['send_email']) ? $_POST['send_email'] : 0;
    $statuses = isset($_POST['statuses']) && ! empty($_POST['statuses']) ? $_POST['statuses'] : "";
    $statusStr = "";
    $services2 = ! empty($_POST['services']) ? $_POST['services'] : '';
    $per = ! empty($_POST['permissions']) ? $_POST['permissions'] : '';
    $query = "SELECT * FROM customers WHERE email = '{$email}'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customerExists = $stmt->fetch();
    if (! recway_is_strong_password_14($password)) {
        flash("customerAdded", "Password must be exactly 14 characters and include at least 1 uppercase letter, 1 lowercase letter, 1 digit, and 1 special character.", "errorMsg");
    } elseif (empty($customerExists)) {
        $crypt_pass = password_hash($password, PASSWORD_BCRYPT);
        if (! empty($statuses)) {
            foreach ($statuses as $key => $status) {
                if ($key != count($statuses) - 1) {
                    $statusStr = $statusStr . $status . ",";
                } else {
                    $statusStr = $statusStr . $status;
                }
            }
        }
        // Invoice settings from form (last_invoice_sent may be null if not provided)
        $invoice_period = isset($_POST['invoice_period']) && $_POST['invoice_period'] !== '' ? $_POST['invoice_period'] : 'day';
        $last_invoice_sent = ! empty($_POST['last_invoice_sent']) ? $_POST['last_invoice_sent'] : null;
        // If last_invoice_sent not provided, default to previous period boundary
        if (empty($last_invoice_sent)) {
            $todayTs = strtotime(date('Y-m-d'));
            switch ($invoice_period) {
                case 'day':
                    // Yesterday
                    $last_invoice_sent = date('Y-m-d', strtotime('-1 day', $todayTs));
                    break;
                case 'week':
                    // Previous Monday
                    $last_invoice_sent = date('Y-m-d', strtotime('last monday', $todayTs));
                    break;
                case 'month':
                    // First day of previous month
                    $last_invoice_sent = date('Y-m-01', strtotime('first day of last month', $todayTs));
                    break;
            }
        }
        $query = 'INSERT INTO customers (name,email,password,phone,company,org_no, statuses,reg_email,parent_id,dep_id,interview_template,interview_upload_allowed, send_security_report, sent_email,combine_bk_and_security, timra_report, combine_status,combine_interview_id, invoice_period, last_invoice_sent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $crypt_pass, $phone, $company, $org_no, $statusStr, $reg_email, $parent_customer, $cus_department, $interview_template, $interview_upload_allowed, $send_security_report, $send_email, $combine_bk_and_security, $timra_report, $combine_status,$combine_interview_id, $invoice_period, $last_invoice_sent]);
        $cus_id = $conn->lastInsertId();
        $can_view_report = 1;
        if (! empty($company_manager)) {
            $query = 'INSERT INTO company_manager (company,cus_id, can_view_report) VALUES (?,?,?)';
            $stmt = $conn->prepare($query);
            $re = $stmt->execute([$company, $cus_id, $can_view_report]);
        }
        if (! empty($pref) || ! empty($ref) || ! empty($comment)) {
            $query = 'INSERT INTO standard_billing_details (cus_id,referenceperson,reference,comment) VALUES (?,?,?,?)';
            $stmt = $conn->prepare($query);
            $re = $stmt->execute([$cus_id, $pref, $ref, $comment]);
        }
        $user = findByQuery("SELECT * FROM customers WHERE email = '{$email}'");
        if (! empty($user)) {
            if (! empty($per)) {
                foreach ($per as $pers) {
                    $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';
                    $stmt = $conn->prepare($query);
                    $re = $stmt->execute([$pers, $user->id, 2]);
                }
            }
        }
        if (! empty($res)) {
            if (! empty($parent_customer)) {
                $insert_form = [];
                $parent_msg = findAllByQuery("SELECT * FROM messages WHERE cus_id = '$parent_customer'");
                $parent_forms = findAllByQuery("SELECT * FROM order_forms WHERE cus_id = '$parent_customer'");
                if (! empty($parent_forms)) {
                    foreach ($parent_forms as $parent_fo) {
                        foreach ($parent_fo as $f_m => $parent_f) {
                            if ($f_m != 'id') {
                                if ($f_m == 'cus_id') {
                                    $insert_form[$f_m] = $cus_id;
                                } else {
                                    $insert_form[$f_m] = $parent_f;
                                }
                            }
                        }
                        insert('order_forms', $insert_form);
                    }
                }
                if (! empty($parent_msg)) {
                    $insert_array = [];
                    foreach ($parent_msg as $parent_ms) {
                        foreach ($parent_ms as $k_m => $parent_m) {
                            if ($k_m != 'id') {
                                if ($k_m == 'cus_id') {
                                    $insert_array[$k_m] = $cus_id;
                                } else {
                                    $insert_array[$k_m] = $parent_m;
                                }
                            }
                        }
                        insert('messages', $insert_array);
                    }
                }
                $parent_reports = findAllByQuery("SELECT * FROM customer_reports_html WHERE cus_id = '$parent_customer'");
                $meta_info = [
                    'created_by' => $_SESSION['admin']->id,
                    'created_on' => date('Y-m-d H:i:s'),
                    'user' => 'Admin',
                ];
                $meta_info = json_encode($meta_info);
                if (! empty($parent_reports)) {
                    foreach ($parent_reports as $report) {
                        $query = 'INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$cus_id, $report->report_data,$report->interview_id,$report->lang,$meta_info]);
                    }
                }

            } else {
                $insert_form = [];
                $parent_msg = findAllByQuery("SELECT * FROM messages WHERE cus_id = '197'");
                $parent_forms = findAllByQuery("SELECT * FROM order_forms WHERE cus_id = '197'");
                $recway_customer = findByQuery("SELECT * FROM customers WHERE id = '197'");
                $elevio_and_send_email_quesiton_query = 'UPDATE customers SET send_email_question = ? , ellevio_report = ? WHERE email = ?';
                $stmt = $conn->prepare($elevio_and_send_email_quesiton_query);
                $res = $stmt->execute([$recway_customer->send_email_question, $recway_customer->ellevio_report, $email]);
                if (! empty($parent_forms)) {
                    foreach ($parent_forms as $parent_fo) {
                        foreach ($parent_fo as $f_m => $parent_f) {
                            if ($f_m != 'id') {
                                if ($f_m == 'cus_id') {
                                    $insert_form[$f_m] = $cus_id;

                                } else {
                                    $insert_form[$f_m] = $parent_f;
                                }
                            }
                        }
                        insert('order_forms', $insert_form);
                    }
                }
                if (! empty($parent_msg)) {
                    $insert_array = [];
                    foreach ($parent_msg as $parent_ms) {
                        foreach ($parent_ms as $k_m => $parent_m) {
                            if ($k_m != 'id') {
                                if ($k_m == 'cus_id') {
                                    $insert_array[$k_m] = $cus_id;
                                } else {
                                    $insert_array[$k_m] = $parent_m;
                                }
                            }
                        }
                        insert('messages', $insert_array);
                    }
                }
                $parent_reports = findAllByQuery("SELECT * FROM customer_reports_html WHERE cus_id = '197'");
                $meta_info = [
                    'created_by' => $_SESSION['admin']->id,
                    'created_on' => date('Y-m-d H:i:s'),
                    'user' => 'Admin',
                ];
                $meta_info = json_encode($meta_info);
                if (! empty($parent_reports)) {
                    foreach ($parent_reports as $report) {
                        $query = 'INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$cus_id, $report->report_data,$report->interview_id,$report->lang,$meta_info]);
                    }
                }

            }
            // else {
            //     insertMessages($cus_id, $services2);
            // }
            $query = 'INSERT INTO allowed_emails (cus_id, status_id, allowed) SELECT ? AS cus_id, id AS status_id, 1 AS allowed FROM statuses';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$cus_id]);
            foreach ($services2 as $service) {
                $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$cus_id, $service]);
            }
            $body = replace($email_text, $name, '', $company, '', '', $email, $password, '', '');
            $subject = "Registration";
            // Create a DateTime object for Sweden's timezone
            $swedenTimezone = new DateTimeZone('Europe/Stockholm');
            $swedenTime = new DateTime('now', $swedenTimezone);
            $currentTime = $swedenTime->format('H:i:s');
            $dayOfWeek = date('N');
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Customer", $name, "N/A", 'Customer Registration Message', $body, $email, $subject);
                sendMail($body, $email, $name, $subject);
            } else {
                saveEmail("Customer", $name, "N/A", 'Customer Registration Message', $body, $email, $subject, '1');
            }
            flash("customerAdded", "Customer added successfully!");
            // redirect('customers.php');
        } else {
            flash("customerAdded", "Could not add customer!", "errorMsg");
        }
    } else {
        flash("customerAdded", "Customer with this email already exists!", "errorMsg");
    }
}
$statuses = getStatuses();
$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();
$permissions = findallByQuery("SELECT * FROM user_permissions WHERE user_type != 3");
$parent_customer = findallByQuery("SELECT * FROM customers");
?>
<?php flash("customerAdded"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Add Customer</h1>
                    </div>
                    <form class="update-form" method="post" enctype="multipart/form-data">
                        <div class="row mb-3">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" class="form-control" name="name" required id="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" name="email" required id="email">
                            </div>
                            <div class="col-lg-6 mb-3" style="position: relative;">
                                <label class="form-label" for="password">Password</label>
                                <input
                                    type="password"
                                    value="<?php echo htmlspecialchars(recway_generate_strong_password_14(), ENT_QUOTES, 'UTF-8'); ?>"
                                    class="form-control"
                                    name="password"
                                    required
                                    id="password"
                                    minlength="14"
                                    maxlength="14"
                                    pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^A-Za-z0-9]).{14}$"
                                    title="Exactly 14 characters, with at least 1 uppercase, 1 lowercase, 1 digit, and 1 special character."
                                    autocomplete="new-password"
                                    style="padding-right: 44px;"
                                >
                                <button
                                    type="button"
                                    id="toggle_password"
                                    class="btn btn-link p-0"
                                    aria-label="Show password"
                                    style="position:absolute; right:12px; top:24px; height:38px; display:flex; align-items:center;"
                                >
                                    <span id="toggle_password_icon_show" aria-hidden="true">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                    <span id="toggle_password_icon_hide" aria-hidden="true" style="display:none;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M3 3l18 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M10.6 10.6A2 2 0 0 0 13.4 13.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M9.9 5.1A10.2 10.2 0 0 1 12 5c6.5 0 10 7 10 7a18 18 0 0 1-4.2 5.2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M6.3 6.3C3.6 8.3 2 12 2 12s3.5 7 10 7c1.1 0 2.1-.2 3-.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                </button>
                                <div id="password_rule_hint" class="form-text">
                                    Must be exactly 14 characters and include uppercase, lowercase, digit, and special character.
                                </div>
                                <div id="password_error" class="invalid-feedback"></div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" class="form-control" name="phone" required id="phone">
                            </div>
                            <div class="col-lg-6 mb-3" style="position: relative;">
                                <label class="form-label" for="company">Company</label>
                                <input type="text" class="form-control" name="company" required id="company" autocomplete="off">
                                <input type="hidden" name="existing_company_manager_cus_id" id="existing_company_manager_cus_id" value="">
                                <div id="company_suggestions" class="list-group" style="position: absolute; top: 100%; left: 0; right: 0; z-index: 1050; display: none;
                                    max-height: 200px; overflow-y: auto;">
                                </div>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="cost_place">Organization Number</label>
                                <input type="text" class="form-control" name="org_no" required id="cost_place">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Profile</label>
                                <select name="parent_customer" id="parent_customer" onchange="get_dep(this)"
                                    class="form-control filter-select">
                                    <option value="">-Select Customer-</option>
                                    <?php if (! empty($parent_customer)) { ?>
                                        <?php foreach ($parent_customer as $par_cust) { ?>
                                            <option value="<?= $par_cust->id ?>"><?= $par_cust->name ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </div>
                            <!-- Invoice Settings -->
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="invoice_period">Invoice Period</label>
                                <select name="invoice_period" id="invoice_period" class="form-control filter-select">
                                    <option value="">-Select Invoice Period-</option>
                                    <option value="month">Monthly</option>
                                    <option value="week">Weekly</option>
                                    <option value="day">Daily</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Department</label>
                                <select name="cus_department" id="cus_department" class="form-control filter-select">
                                    <option value="">-Select Department-</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input type="radio" name="active_mail" checked id="same_email">
                                <label>Same</label>
                                <input type="radio" name="active_mail" class="ml-5" id="change_email">
                                <label>Change Email</label>
                                <div class="position-relative" style="display:none" id="row_of_email">
                                    <textarea rows="5" class="sign-textarea w-100" disabled
                                        name="changed_registration_email"
                                        id="changed_registration_email"><?php echo $cus_reg_msg ?></textarea>
                                    <textarea rows="5" class="sign-textarea w-100" style="display: none;"
                                        id="swedish_language">
<p class="p1">Hej <strong>{customer}</strong>,</p>
<p class="p1">Varmt välkommen till Recway!</p>
<p class="p1">Du är nu upplagd som användare i vår kundportal. Här kan du enkelt beställa våra tjänster, följa status på dina ärenden och hantera alla delar av processen på ett säkert och effektivt sätt.</p>
<h3><strong>Dina inloggningsuppgifter</strong></h3>
<ul>
<li>
<p class="p1"><span class="s1"><strong>Portal:</strong></span> <a href="https://customer.recway.se/">https://customer.recway.se/</a></p>
</li>
<li>
<p class="p1"><span class="s1"><strong>E-post:</strong></span> {email}</p>
</li>
<li>
<p class="p1"><span class="s1"><strong>Lösenord:</strong></span> {password}</p>
</li>
</ul>
<p class="p2"> </p>
<p class="p1">Vid första inloggningen kommer du att få en <span class="s2"><strong>engångskod (OTP)</strong></span> skickad till både din e-postadress och ditt mobilnummer. Denna kod är giltig i 5–10 minuter och måste anges för att slutföra inloggningen. Detta är ett extra säkerhetslager för att skydda din åtkomst.</p>
<h3><strong>Så kommer du igång – Steg för steg</strong></h3>
<ol start="1">
<li>
<p class="p1"><strong>Logga in på portalen</strong></p>
<p class="p2">Gå till länken ovan och ange dina inloggningsuppgifter. Ange därefter den OTP-kod som skickas till din mejl och mobil.</p>
</li>
<li>
<p class="p1"><strong>Skapa en ny beställning</strong></p>
<p class="p2">Klicka på <em>“Create Order”</em> i vänstermenyn.</p>
</li>
<li>
<p class="p1"><strong>Välj tjänst</strong></p>
<p class="p2">Under <em>“Choose Service Category”</em>, välj vilken kategori som passar ert behov (ex. bakgrundskontroll, säkerhetsprövningsintervju, uppföljningssamtal).</p>
<p class="p2">Därefter väljer du en av de tjänster som är aktiverade för er organisation.</p>
</li>
<li>
<p class="p1"><strong>Fyll i kandidatens uppgifter</strong></p>
<p class="p2">Ange kandidatens namn, kontaktuppgifter och övrig relevant information i formuläret. Klicka på <em>“Next”</em>.</p>
</li>
<li>
<p class="p1"><strong>Bifoga CV (valfritt)</strong></p>
<p class="p2">Här kan du ladda upp kandidatens CV. Vill du inte bifoga något, klicka bara på <em>“Next”</em> för att gå vidare.</p>
</li>
<li>
<p class="p1"><strong>Faktureringsinformation</strong></p>
<p class="p2">Ange uppgifter såsom rekryterande chef, kostnadsställe eller annan fakturamottagare.</p>
</li>
</ol>
<h3><strong><br />Tips för en smidig process</strong></h3>
<p class="p1">När du lagt en beställning skickas automatiskt ett informationsmejl till kandidaten. Där förklaras vad som ska göras, varför beställningen är gjord och vad processen innebär.</p>
<p class="p1">Vi rekommenderar att du som beställare även informerar kandidaten i förväg, särskilt vid säkerhetsprövningsintervjuer, så att processen upplevs trygg och förutsägbar.</p>
<p class="p1"> </p>
<p class="p1">Tveka inte att höra av dig om du har några frågor eller om du önskar en personlig genomgång av portalen.</p>
<p class="p2"><br /><br />Vår kundsupport är tillgänglig under följande tider:</p>
<p class="p3"><strong>Måndag–Fredag:</strong><span class="s1"> 08:30–16:30</span></p>
<p class="p1"><span class="s2"><strong>Lunchstängt:</strong></span> 12:00–13:00</p>
<p class="p3"><strong>Helger & röda dagar:</strong><span class="s1"> Stängt</span></p>
<p class="p3"><span class="s2"><strong>E-post:</strong></span> <a href="mailto:info@recway.se">info@recway.se </a><span class="s2"><strong>Telefon:</strong></span> +46 8 551 063 97</p>
<p class="p1"><br /><strong>Bästa hälsningar,</strong></p>
<p class="p1">Recway – Vägen till en säkrare rekrytering</p>
<p class="p1"><a href="https://www.recway.se">https://www.recway.se</a></p>
<p class="p2"> </p>
<p class="p1"><em>Detta e-postmeddelande är konfidentiellt och kan innehålla juridiskt skyddad information. Om du av misstag mottagit detta meddelande, vänligen informera oss omedelbart och radera meddelandet. Som mottagare ansvarar du för att radera både e-post och eventuella bilagor när syftet med åtkomsten upphört, dock senast efter sex månader.</em></p>
</textarea>
                                </div>
                                <div class="row">
                                    <!-- <div class="col-md-12">
                                        <input type="radio" class="email_language " name="email_language"
                                            data-id="english" checked onclick="change_template(this)" disabled>
                                        <label>English Email</label>
                                        <input type="radio" class="email_language ml-3" name="email_language"
                                            data-id="swedish" class="ml-5" onclick="change_template(this)" disabled>
                                        <label>Swedish Email</label>
                                    </div> -->
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input class="form-check-input" id="send_email" type="checkbox" name="send_email"
                                    value="1">
                                <label class="form-label form-check-label" for="send_email">CC email of customer
                                    registration</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input class="form-check-input" id="company_manager" type="checkbox"
                                    name="company_manager" value="1">
                                <label class="form-label form-check-label" for="company_manager">Company
                                    Manager</label>
                            </div>
                            <div class="col-md-3 mb-3">
                                <input class="form-check-input" id="interview_template" type="checkbox"
                                    name="interview_template" value="1">
                                <label class="form-label form-check-label" for="interview_template">Interview
                                    Template</label>
                            </div>
                            <div class="col-md-3 mb-3">
                                <input class="form-check-input" id="send_security_report" type="checkbox"
                                    name="send_security_report" value="1">
                                <label class="form-label form-check-label" for="send_security_report">Send result of the basic investigation</label>
                            </div>
                                                                                                        <div class="col-md-4 mb-3">
                                                    <input class="form-check-input" id="interview_upload_allowed"
                                                        type="checkbox" name="interview_upload_allowed" value="1"
                                                        <?= $customer->interview_upload_allowed == 1 ? 'checked' : '' ?>>
                                                    <label class="form-label form-check-label"
                                                        for="interview_upload_allowed">
                                                        Interview upload report</label>
                                                </div>
                                                <div class="col-md-4 mb-3">
                                                    <input class="form-check-input" id="timra_report" type="checkbox"
                                                        name="timra_report" value="1">
                                                    <label class="form-label form-check-label" for="timra_report">
                                                        Referens Template</label>
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <input class="form-check-input" id="combine_bk_and_security" type="checkbox" onchange="show_services_status()"
                                                        name="combine_bk_and_security" value="1" >
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
$exp_services = null;

?>
                                                        <?php if (! empty($combine_services)) { ?>
                                                            <?php foreach ($combine_services as $service) { ?>
                                                                <option value="<?= $service->id ?>" <?php if (! empty($exp_services)) { ?> <?php foreach ($exp_services as $exp_service) { ?> <?php if (trim($exp_service) == $service->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $service->title ?></option>
                                                            <?php } ?>
                                                        <?php } ?>
                                                    </select>
                                                </div>
                            <div class="col-md-12 mb-3">
                                <div class="row">
                                    <div class="col-md-4">
                                        <button type="button" class="btn bg-primary ml-0"
                                            onclick="showdetails()">Standard
                                            Billing Details</button>
                                    </div>
                                    <div class="p-0" style="display:none" id="standard-billing-details">
                                        <div class="col-lg-12">
                                            <label for="">Reference<br>(Invoice Recipient)</label>
                                            <input type="text" name="pref" id="billing_pref" class="form-control">
                                        </div>
                                        <div class="col-lg-12">
                                            <label for="">Reference</label>
                                            <input type="text" name="ref" id="billing_ref" class="form-control">
                                        </div>
                                        <div class="col-lg-12">
                                            <label for="">Invoice Comment</label>
                                            <input type="text" name="comment" id="billing_comment" class="form-control">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-3">
                                <label class="form-label">
                                    User Permissions
                                </label>
                                <?php if (! empty($permissions)): ?>
                                    <?php foreach ($permissions as $permission): ?>
                                        <div>
                                            <input class="form-check-input" id="<?php echo $permission->title ?>"
                                                type="checkbox" <?php if ($permission->user_type == 1) { ?> checked <?php } ?>
                                                name="permissions[]" value="<?php echo $permission->id ?>">
                                            <label class="form-label form-check-label"
                                                for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (! empty($servicesCats)): ?>
                                <?php foreach ($servicesCats as $servicesCat): ?>
                                    <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                    <div class="col-lg-3">
                                        <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                        <?php if (! empty($statuses)): ?>
                                            <?php foreach ($statuses as $status): ?>
                                                <div>
                                                    <input class="form-check-input" type="checkbox"
                                                        id="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>" name="statuses[]"
                                                        checked value="<?php echo $status->sID ?>">
                                                    <label class="form-label form-check-label"
                                                        for="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>"><?php echo $status->status ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="col-lg-3">
                                <label class="form-label">Allowed Services</label>
                                <?php if (! empty($services)): ?>
                                    <?php foreach ($services as $service): ?>
                                        <div>
                                            <input class="form-check-input service_checkbox" id="<?php echo $service->title ?>"
                                                <?php echo $service->service_cat_id == 1 ? 'checked' : '' ?> type="checkbox"
                                                name="services[]" value="<?php echo $service->id ?>">
                                            <label class="form-label form-check-label"
                                                for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="add_customer" class="btn-primary bg-primary">Save</button>
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
    $(document).ready(function () {
        show_services_status()
        $('input[type="radio"][name="active_mail"]').on('click', function () {
            if ($(this).attr('id') == 'same_email') {
                $('#row_of_email').css('display', 'none');
                $('#row_of_email').find('textarea').prop('disabled', true)
                $('.email_language').prop('disabled', true);
            }
            if ($(this).attr('id') == 'change_email') {
                $('#row_of_email').css('display', 'block');
                $('#row_of_email').find('textarea').prop('disabled', false)
                $('.email_language').prop('disabled', false);
            }
        })
    })
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
                success: function (response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.customers != '') {
                            var cus = response.customers;
                            // If parent has invoice_period, mirror it into the child form dropdown
                            if (cus[0].invoice_period) {
                                $("select[name='invoice_period']").val(cus[0].invoice_period).trigger('change');
                            }
                           if (cus[0].interview_upload_allowed == 1) {
                                $('#interview_upload_allowed').attr('checked', true)
                            } else {
                                $('#interview_upload_allowed').attr('checked', false)
                            }
                            if (cus[0].statuses != '') {
                                var stat = cus[0].statuses;
                                var stat = stat.split(",");
                                $('input[name="statuses[]"]').attr('checked', false);
                                stat.forEach(function (e) {
                                    $('input[name="statuses[]"]').each(function () {
                                        if (e == $(this).val()) {
                                            $(this).attr('checked', true);
                                        }
                                    })
                                });
                            }
                        } else {
                            $('#company').val('')
                        }
                        var opt_html = '<option value="">-Select Department-</option>';
                        if (response.departments != '') {
                            var dep = response.departments;
                            $(dep).each(function (i, v) {
                                opt_html += '<option value="' + v.dep_id + '">' + v.dep_name + '</option>';
                            })
                            $('#cus_department').html(opt_html)
                        } else {
                            $('#cus_department').html(opt_html)
                        }
                        if (response.services != '') {
                            var ser = response.services;
                            $('.service_checkbox').attr('checked', false)
                            $('.service_checkbox').each(function (a, c) {
                                var chk_box = $(this);
                                $(ser).each(function (k, b) {
                                    if (chk_box.val() == b.service_id) {
                                        chk_box.attr('checked', true);
                                    }
                                })
                                chk_box = ''
                            })
                        }
                        if (response.permissions != '') {
                            var per = response.permissions;
                            $('input[name="permissions[]"]').attr('checked', false)
                            $('input[name="permissions[]"]').each(function (a, c) {
                                var chk_box = $(this);
                                $(per).each(function (k, b) {
                                    if (chk_box.val() == b.per_id) {
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
                                // var statusesArray = cus[0].combine_status.split(',');
                                // $('#combine_statuses').val(statusesArray).trigger('change');
                            } else {
                                // $('input[name="combine_status"]').prop('checked', false);
                                $('#combine_interview_id').val(null).trigger('change');
                            }
                            show_services_status()
                            if (cus[0].sent_email != '') {
                                $('input[name="send_email"]').attr('checked', true)
                            } else {
                                $('input[name="send_email"]').attr('checked', false)
                            }
                            if (cus[0].timra_report.toString() == '1') {
                                $('input[name="timra_report"]').prop('checked', true)
                                $('input[name="timra_report"]').val(1);
            
                            } else {
                                $('input[name="timra_report"]').prop('checked', false)
                                $('input[name="timra_report"]').val(0);
                            }
                        }
                    }
                }
            });
        }
    }
    function get_dep_stub(obj) {
        var cus_id = $(obj).val();
        if (cus_id != '') {
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    'id': cus_id,
                    'get_par_department_stub': 1
                },
                success: function (response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.customers != '') {
                            var cus = response.customers;
                        
                            // If parent has invoice_period, mirror it into the child form dropdown
                            if (cus[0].invoice_period) {
                                $("select[name='invoice_period']").val(cus[0].invoice_period).trigger('change');
                            }
                           if (cus[0].interview_upload_allowed == 1) {
                                $('#interview_upload_allowed').attr('checked', true)
                            } else {
                                $('#interview_upload_allowed').attr('checked', false)
                            }
                            if (cus[0].statuses != '') {
                                var stat = cus[0].statuses;
                                var stat = stat.split(",");
                                $('input[name="statuses[]"]').attr('checked', false);
                                stat.forEach(function (e) {
                                    $('input[name="statuses[]"]').each(function () {
                                        if (e == $(this).val()) {
                                            $(this).attr('checked', true);
                                        }
                                    })
                                });
                            }
                        } else {
                            $('#company').val('')
                        }
                        var opt_html = '<option value="">-Select Department-</option>';
                        if (response.departments != '') {
                            var dep = response.departments;
                            $(dep).each(function (i, v) {
                                opt_html += '<option value="' + v.dep_id + '">' + v.dep_name + '</option>';
                            })
                            $('#cus_department').html(opt_html)
                        } else {
                            $('#cus_department').html(opt_html)
                        }
                        if (response.services != '') {
                            var ser = response.services;
                            $('.service_checkbox').attr('checked', false)
                            $('.service_checkbox').each(function (a, c) {
                                var chk_box = $(this);
                                $(ser).each(function (k, b) {
                                    if (chk_box.val() == b.service_id) {
                                        chk_box.attr('checked', true);
                                    }
                                })
                                chk_box = ''
                            })
                        }
                        if (response.permissions != '') {
                            var per = response.permissions;
                            $('input[name="permissions[]"]').attr('checked', false)
                            $('input[name="permissions[]"]').each(function (a, c) {
                                var chk_box = $(this);
                                $(per).each(function (k, b) {
                                    if (chk_box.val() == b.per_id) {
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
                                // var statusesArray = cus[0].combine_status.split(',');
                                // $('#combine_statuses').val(statusesArray).trigger('change');
                            } else {
                                // $('input[name="combine_status"]').prop('checked', false);
                                $('#combine_interview_id').val(null).trigger('change');
                            }
                            show_services_status()
                            if (cus[0].sent_email != '') {
                                $('input[name="send_email"]').attr('checked', true)
                            } else {
                                $('input[name="send_email"]').attr('checked', false)
                            }
                            if (cus[0].timra_report.toString() == '1') {
                                $('input[name="timra_report"]').prop('checked', true)
                                $('input[name="timra_report"]').val(1);
            
                            } else {
                                $('input[name="timra_report"]').prop('checked', false)
                                $('input[name="timra_report"]').val(0);
                            }
                            if (cus[0].interview_template.toString() == '1') {
                                $('input[name="interview_template"]').prop('checked', true);
                            } else {
                                $('input[name="interview_template"]').prop('checked', false);
                            }
                           
                            if (cus[0].send_security_report.toString() == '1') {
                                $('input[name="send_security_report"]').prop('checked', true)
                                $('input[name="send_security_report"]').val(1);
            
                            } else {
                                $('input[name="send_security_report"]').prop('checked', false)
                                $('input[name="send_security_report"]').val(0);
                            }
                            // if(cus[0].company!= null && cus[0].company!= ''){
                            //     $('#company').val(cus[0].company);
                            // }
                            // if (cus[0].organization_number != null && cus[0].organization_number != '') {
                            //     $('#organization_number').val(cus[0].organization_number);
                            // }
                        }
                        if(response.company_manager != ''){
                            var cm = response.company_manager;
                             if (cm[0].toString() == '1') {
                                $('input[name="company_manager"]').prop('checked', true);
            
                            } else {
                                $('input[name="company_manager"]').prop('checked', false);
                            }
                        }
                        if(response.standard_billing_details != ''){
                            var billing_details = response.standard_billing_details;
                            $('#billing_pref').val(billing_details[0].referenceperson);
                            $('#billing_ref').val(billing_details[0].reference);
                            $('#billing_comment').val(billing_details[0].comment);
                        }
                    }
                }
            });
        }
    }
    function showdetails() {
        if ($('#standard-billing-details').is(':visible')) {
            $('#standard-billing-details').slideUp();
        } else {
            $('#standard-billing-details').slideDown();
        }
    }
    function change_template(obj) {
        var lan = $(obj).data('id');
        if ($('#change_email').is(':checked')) {
            if (lan == 'english') {
                $('#swedish_language').prop('disabled', true);
                $('#swedish_language').hide();
                $('#swedish_language').attr('name', '');
                $('#changed_registration_email').show();
                $('#changed_registration_email').attr('name', 'changed_registration_email');
                $('#changed_registration_email').prop('disabled', false);
            } else {
                $('#changed_registration_email').hide();
                $('#changed_registration_email').prop('disabled', true);
                $('#changed_registration_email').attr('name', '');
                $('#swedish_language').show();
                $('#swedish_language').prop('disabled', false);
                $('#swedish_language').attr('name', 'changed_registration_email');
            }
        }
    }
    document.addEventListener('DOMContentLoaded', function () {
        var el = document.createElement('input');
        el.type = 'hidden';
        el.id = 'parent_customer_stub';
        el.name = 'parent_customer';
        el.value = '197';
        document.body.appendChild(el);
        var obj = el;
        get_dep_stub(obj);
    });

    // Live company lookup: debounce input and query server for matching company_manager entries
    (function() {
        let companyTimer = null;
        const $company = $('#company');
        const $suggestions = $('#company_suggestions');

        function hideSuggestions() {
            $suggestions.hide().html('');
        }

        $company.on('input', function() {
            const term = $(this).val().trim();
            // clear any previously selected existing company id when user edits
            $('#existing_company_manager_cus_id').val('');
            $('input[name="company_manager"]').prop('checked', false);
            if (companyTimer) clearTimeout(companyTimer);
            if (!term) { hideSuggestions(); return; }
            companyTimer = setTimeout(function() {
                $.ajax({
                    type: 'POST',
                    url: './includes/table_ajax.php',
                    data: { search_company: term },
                    dataType: 'json',
                    success: function(res) {
                        if (!res || !res.length) { hideSuggestions(); return; }
                        let html = '';
                        res.forEach(function(r) {
                            // r.company expected
                            html += `<button type="button" class="list-group-item list-group-item-action company-suggestion-item" data-company="${escapeHtml(r.company)}">${escapeHtml(r.company)}</button>`;
                        });
                        $suggestions.html(html).show();
                    },
                    error: function() { hideSuggestions(); }
                });
            }, 300);
        });

        // click handler for suggestion
        $suggestions.on('click', '.company-suggestion-item', function() {
            const companyName = $(this).data('company') || $(this).text();
            // we only store company name (unique) — do not attach cus_id here
            $company.val(companyName);
            $('#existing_company_manager_cus_id').val('');
            // mark company_manager checkbox so admin knows this company exists
            $('input[name="company_manager"]').prop('checked', true);
            hideSuggestions();
        });

        // hide on outside click
        $(document).on('click', function(e) {
            if (!$(e.target).closest('#company').length && !$(e.target).closest('#company_suggestions').length) {
                hideSuggestions();
            }
        });

        function escapeHtml(text) {
            return text.replace(/[&"'<>]/g, function (m) { return ({'&':'&amp;','"':'&quot;',"'":'&#39;','<':'&lt;','>':'&gt;'}[m]); });
        }
    })();

    // Password policy validation (14 chars, upper/lower/digit/special)
    (function() {
        const $form = $('form.update-form').first();
        const $password = $('#password');
        const $err = $('#password_error');
        const $submit = $form.find('button[type="submit"][name="add_customer"]');
        const $toggle = $('#toggle_password');
        const $iconShow = $('#toggle_password_icon_show');
        const $iconHide = $('#toggle_password_icon_hide');

        function validatePassword(pw) {
            if (typeof pw !== 'string') pw = '';
            const rules = {
                length: pw.length === 14,
                lower: /[a-z]/.test(pw),
                upper: /[A-Z]/.test(pw),
                digit: /\d/.test(pw),
                special: /[^A-Za-z0-9]/.test(pw),
            };
            const ok = rules.length && rules.lower && rules.upper && rules.digit && rules.special;
            return { ok, rules };
        }

        function updateUi() {
            const pw = $password.val() || '';
            const { ok, rules } = validatePassword(pw);

            if (!pw) {
                $password.removeClass('is-invalid');
                $err.text('');
                $submit.prop('disabled', false);
                return;
            }

            if (ok) {
                $password.removeClass('is-invalid');
                $err.text('');
                $submit.prop('disabled', false);
                return;
            }

            const missing = [];
            if (!rules.length) missing.push('exactly 14 characters');
            if (!rules.upper) missing.push('1 uppercase letter');
            if (!rules.lower) missing.push('1 lowercase letter');
            if (!rules.digit) missing.push('1 digit');
            if (!rules.special) missing.push('1 special character');

            $password.addClass('is-invalid');
            $err.text('Password must have ' + missing.join(', ') + '.');
            $submit.prop('disabled', true);
        }

        $password.on('input blur', updateUi);
        $toggle.on('click', function() {
            const isHidden = ($password.attr('type') || 'password') === 'password';
            $password.attr('type', isHidden ? 'text' : 'password');
            $iconShow.toggle(!isHidden);
            $iconHide.toggle(isHidden);
            $toggle.attr('aria-label', isHidden ? 'Hide password' : 'Show password');
            $password.trigger('focus');
        });
        $form.on('submit', function(e) {
            const pw = $password.val() || '';
            const { ok } = validatePassword(pw);
            if (!ok) {
                e.preventDefault();
                updateUi();
                $password.focus();
            }
        });

        // initial state (handles auto-generated value)
        updateUi();
    })();
</script>