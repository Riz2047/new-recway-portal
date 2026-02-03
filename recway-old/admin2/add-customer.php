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



if (isset($_POST['add_customer'])) {

    $email_text = null;

    $name = $_POST['name'];

    $email = $_POST['email'];

    $password = $_POST['password'];

    $phone = $_POST['phone'];
    $company_manager = !empty($_POST['company_manager']) ? $_POST['company_manager'] : null;
    $pref = !empty($_POST['pref']) ? $_POST['pref'] : null;
    $ref = !empty($_POST['ref']) ? $_POST['ref'] : null;
    $comment = !empty($_POST['comment']) ? $_POST['comment'] : null;

    $parent_customer = !empty($_POST['parent_customer']) ? $_POST['parent_customer'] : null;

    $cus_department = !empty($_POST['cus_department']) ? $_POST['cus_department'] : null;

    $interview_template = !empty($_POST['interview_template']) ? $_POST['interview_template'] : null;

    $send_security_report = !empty($_POST['send_security_report']) ? $_POST['send_security_report'] : 0;

    $changed_registration_email = isset($_POST['changed_registration_email']) ? $_POST['changed_registration_email'] : null;

    $reg_email = null;
    // Handle combine services - convert array to comma-separated string
    $combine_bk_and_security = isset($_POST['combine_bk_and_security']) && is_array($_POST['combine_bk_and_security']) ? implode(',', $_POST['combine_bk_and_security']) : '0'; 
    $timra_report = isset($_POST['timra_report']) ? $_POST['timra_report'] : 0;
    // Handle combine statuses - convert array to comma-separated string
    $combine_status = isset($_POST['combine_status']) && is_array($_POST['combine_status']) ? implode(',', $_POST['combine_status']) : '';

    if (!empty($changed_registration_email)) {
        $email_text = $changed_registration_email;
        $reg_email = $changed_registration_email;
    } else {
        if (!empty($parent_customer)) {
            $parent_cus_msg = findByQuery("SELECT * FROM customers WHERE id = '$parent_customer'");
            if (!empty($parent_cus_msg->reg_email)) {
                $email_text = $parent_cus_msg->reg_email;
                $reg_email = $parent_cus_msg->reg_email;
            } else {
                if (!empty($changed_registration_email)) {
                    $email_text = $changed_registration_email;
                    $reg_email = $changed_registration_email;
                } else {
                    $email_text = $cus_reg_msg;
                }
            }
        } else {
            if (!empty($changed_registration_email)) {
                $email_text = $changed_registration_email;
                $reg_email = $changed_registration_email;
            } else {
                $email_text = $cus_reg_msg;
            }
        }
    }
    // if (!empty($parent_customer)) {

    //     $parent_cus_msg = findByQuery("SELECT * FROM customers WHERE id = '$parent_customer'");

    //     if (!empty($parent_cus_msg->reg_email)) {

    //         $email_text = $parent_cus_msg->reg_email;

    //         $reg_email = $parent_cus_msg->reg_email;

    //     } else {

    //         if (!empty($changed_registration_email)) {

    //             $email_text = $changed_registration_email;

    //             $reg_email = $changed_registration_email;

    //         } else {

    //             $email_text = $cus_reg_msg;

    //         }

    //     }

    // } else {

    //     if (!empty($changed_registration_email)) {

    //         $email_text = $changed_registration_email;

    //         $reg_email = $changed_registration_email;

    //     } else {

    //         $email_text = $cus_reg_msg;

    //     }

    // }

    $company = $_POST['company'];

    $org_no = $_POST['org_no'];

		$client_wish = $_POST['client_wish'];

    $send_email = isset($_POST['send_email']) ? $_POST['send_email'] : 0;

    $statuses = isset($_POST['statuses']) && !empty($_POST['statuses']) ? $_POST['statuses'] : "";

    $statusStr = "";

    $services2 = !empty($_POST['services']) ? $_POST['services'] : '';

    $per = !empty($_POST['permissions']) ? $_POST['permissions'] : '';



    $query = "SELECT * FROM customers WHERE email = '{$email}'";

    $stmt = $conn->prepare($query);

    $stmt->execute();

    $customerExists = $stmt->fetch();



    if (empty($customerExists)) {

        $crypt_pass = password_hash($password, PASSWORD_BCRYPT);



        if (!empty($statuses)) {

            foreach ($statuses as $key => $status) {

                if ($key != count($statuses) - 1) {

                    $statusStr = $statusStr . $status . ",";

                } else {

                    $statusStr = $statusStr . $status;

                }

            }

        }

    // Invoice settings from form (last_invoice_sent may be null if not provided)
    $invoice_period = $_POST['invoice_period'] ?? 'month';
    $last_invoice_sent = !empty($_POST['last_invoice_sent']) ? $_POST['last_invoice_sent'] : null;

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

    $query = 'INSERT INTO customers (name,email,password,phone,company,org_no, statuses,reg_email,parent_id,dep_id,interview_template,send_security_report, sent_email,combine_bk_and_security, timra_report, combine_status, invoice_period, last_invoice_sent, client_wish) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $crypt_pass, $phone, $company, $org_no, $statusStr, $reg_email, $parent_customer, $cus_department, $interview_template, $send_security_report, $send_email, $combine_bk_and_security, $timra_report, $combine_status, $invoice_period, $last_invoice_sent, $client_wish]);
        $cus_id = $conn->lastInsertId();
        
        if (!empty($company_manager)) {
            $query = 'INSERT INTO company_manager (company,cus_id) VALUES (?,?)';
            $stmt = $conn->prepare($query);
            $re = $stmt->execute([$company, $cus_id]);
        }
        if (!empty($pref) || !empty($ref) || !empty($comment)) {
            $query = 'INSERT INTO standard_billing_details (cus_id,referenceperson,reference,comment) VALUES (?,?,?,?)';
            $stmt = $conn->prepare($query);
            $re = $stmt->execute([$cus_id, $pref, $ref, $comment]);
        }
        $user = findByQuery("SELECT * FROM customers WHERE email = '{$email}'");

        if (!empty($user)) {

            if (!empty($per)) {

                foreach ($per as $pers) {

                    $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';

                    $stmt = $conn->prepare($query);

                    $re = $stmt->execute([$pers, $user->id, 2]);

                }

            }

        }



        if (!empty($res)) {

            if (!empty($parent_customer)) {

                $insert_form = [];

                $parent_msg = findAllByQuery("SELECT * FROM messages WHERE cus_id = '$parent_customer'");

                $parent_forms = findAllByQuery("SELECT * FROM order_forms WHERE cus_id = '$parent_customer'");

                if (!empty($parent_forms)) {

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

                if (!empty($parent_msg)) {

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
                    'user' => 'Admin'
                ];
                $meta_info = json_encode($meta_info);
                if(!empty($parent_reports)){
                    foreach($parent_reports as $report){
                        $query = 'INSERT INTO customer_reports_html (cus_id, report_data, interview_id, lang, meta_info) VALUES (?,?,?,?,?)';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$cus_id, $report->report_data,$report->interview_id,$report->lang,$meta_info]);
                    }
                }
                
            } else {

                insertMessages($cus_id, $services2);

            }



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

<style>
/* Form Design Improvements - Only for form, not tabs */
.update-form {
    background: #ffffff;
    padding: 30px;
    border-radius: 12px;
}

/* Form Sections */
.form-section {
    background: #f9fafb;
    padding: 24px;
    border-radius: 12px;
    border-left: 4px solid #4c6ef8;
    margin-bottom: 24px;
    transition: all 0.3s ease;
}

.form-section:hover {
    background: #ffffff;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
}

.section-title {
    font-size: 18px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 2px solid #e5e7eb;
    display: flex;
    align-items: center;
}

.section-title i {
    color: #4c6ef8;
    font-size: 20px;
    margin-right: 8px;
}

/* Enhanced Form Controls */
.form-label {
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
    font-size: 14px;
    display: block;
}

.form-control {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #ffffff;
}

.form-control:focus {
    border-color: #4c6ef8;
    box-shadow: 0 0 0 4px rgba(76, 110, 248, 0.1);
    outline: none;
}

.form-control:hover {
    border-color: #d1d5db;
}

/* Enhanced Checkbox Styling */
.form-check-input {
    width: 20px;
    height: 20px;
    margin-right: 10px;
    cursor: pointer;
    border: 2px solid #d1d5db;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.form-check-input:checked {
    background-color: #4c6ef8;
    border-color: #4c6ef8;
}

.form-check-input:focus {
    box-shadow: 0 0 0 4px rgba(76, 110, 248, 0.1);
}

.form-check-label {
    cursor: pointer;
    color: #4b5563;
    font-weight: 500;
    transition: color 0.3s ease;
}

.form-check-label:hover {
    color: #4c6ef8;
}

/* Bootstrap Accordion Custom Styling */
.accordion {
    --bs-accordion-border-color: #e5e7eb;
    --bs-accordion-border-radius: 8px;
    --bs-accordion-btn-padding-x: 18px;
    --bs-accordion-btn-padding-y: 14px;
    --bs-accordion-body-padding-x: 18px;
    --bs-accordion-body-padding-y: 16px;
}

.accordion-item {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    margin-bottom: 16px;
    overflow: hidden;
}

.accordion-button {
    background-color: #ffffff;
    color: #1f2937;
    font-weight: 600;
    border: none;
    box-shadow: none;
}

.accordion-button:not(.collapsed) {
    background-color: #4c6ef8;
    color: #ffffff;
    box-shadow: none;
}

.accordion-button:focus {
    border-color: #4c6ef8;
    box-shadow: 0 0 0 0.25rem rgba(76, 110, 248, 0.25);
}

.accordion-button:hover {
    background-color: #f3f4f6;
}

.accordion-button:not(.collapsed):hover {
    background-color: #667eea;
}

.accordion-body {
    background-color: #ffffff;
}

.accordion-body .form-check {
    padding: 10px 0;
    border-bottom: 1px solid #f3f4f6;
}

.accordion-body .form-check:last-child {
    border-bottom: none;
}

/* Enhanced Select Styling */
.filter-select {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
    background: #ffffff;
}

.filter-select:focus {
    border-color: #4c6ef8;
    box-shadow: 0 0 0 4px rgba(76, 110, 248, 0.1);
    outline: none;
}

/* Enhanced Textarea Styling */
.sign-textarea {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    transition: all 0.3s ease;
    resize: vertical;
    font-family: inherit;
}

.sign-textarea:focus {
    border-color: #4c6ef8;
    box-shadow: 0 0 0 4px rgba(76, 110, 248, 0.1);
    outline: none;
}

/* Enhanced Button Styling */
.btn-primary, .bg-primary {
    background: linear-gradient(135deg, #4c6ef8 0%, #667eea 100%);
    border: none;
    padding: 12px 32px;
    border-radius: 8px;
    font-weight: 600;
    font-size: 14px;
    color: #ffffff;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 12px rgba(76, 110, 248, 0.3);
}

.btn-primary:hover, .bg-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(76, 110, 248, 0.4);
}

/* Better spacing */
.mb-3 {
    margin-bottom: 1.5rem !important;
}

.row {
    margin-left: -10px;
    margin-right: -10px;
}

.row > * {
    padding-left: 10px;
    padding-right: 10px;
}

/* Enhanced checkbox container */
.form-check {
    padding: 8px 0;
    transition: all 0.3s ease;
}

.form-check:hover {
    background: #f9fafb;
    padding-left: 8px;
    border-radius: 4px;
}

/* Responsive */
@media (max-width: 768px) {
    .form-section {
        padding: 16px;
    }
    
    .update-form {
        padding: 20px;
    }
}
</style>

<div class="mx-lg-4 main-content">

    <div class="container">



        <div class="row ">



            <div class="col-lg-12">

                <div class="table-section">

                    <div class="d-flex justify-content-between align-items-center mb-3">

                        <h1 class="main-heading">Add Customer</h1>

                    </div>



                    <form class="update-form" method="post" enctype="multipart/form-data">

                        <!-- Basic Information Section -->
                        <div class="form-section mb-4">
                            <h3 class="section-title">
                                <i class="bi bi-person-circle"></i>Basic Information
                            </h3>
                            <div class="row mb-3">

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="name">Name</label>

                                <input type="text" class="form-control" name="name" required id="name">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="email">Email</label>

                                <input type="email" class="form-control" name="email" required id="email">

                            </div>

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="password">Password</label>

                                <input type="text" value="<?php echo rand_string(7) ?>" class="form-control"
                                    name="password" required id="password">

                            </div>

                            <div class="col-md-6 mb-3">

                                <label class="form-label" for="phone">Phone</label>

                                <input type="text" class="form-control" name="phone" required id="phone">

                            </div>

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="company">Company</label>

                                <input type="text" class="form-control" name="company" required id="company">

                            </div>

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="cost_place">Organization Number</label>

                                <input type="text" class="form-control" name="org_no" required id="cost_place">

                            </div>

														<div class="col-md-6 mb-3">
															<label>The client wishes the interview material to be sent to an external party</label>
															<input type="text" class="form-control" name="client_wish" required id="client_wish">						
														</div>

                        </div>
                        </div>

                        <!-- Settings Section -->
                        <div class="form-section mb-4">
                            <h3 class="section-title">
                                <i class="bi bi-gear"></i>Settings
                            </h3>
                            <div class="row mb-3">

                            <div class="col-md-6 mb-3">

                                <label>Parent Customer</label>

                                <select name="parent_customer" id="parent_customer" onchange="get_dep(this)"
                                    class="form-control filter-select">

                                    <option value="">-Select Customer-</option>

                                    <?php if (!empty($parent_customer)) { ?>

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



                        </div>
                        </div>

                        <!-- Email & Report Options Section -->
                        <div class="form-section mb-4">
                            <div class="col-lg-12 mb-3">
                                <div class="accordion" id="emailReportAccordion">
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingEmailReport">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseEmailReport" aria-expanded="false" aria-controls="collapseEmailReport">
                                                <i class="bi bi-envelope me-2"></i>Email & Report Options
                                            </button>
                                        </h2>
                                        <div id="collapseEmailReport" class="accordion-collapse collapse" aria-labelledby="headingEmailReport" data-bs-parent="#emailReportAccordion">
                                            <div class="accordion-body">
                                                <div class="row mb-3">

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
                                                        Timrå Interview Template</label>
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
                                                        <?php if (!empty($combine_services)) { ?>
                                                            <?php foreach ($combine_services as $service) { ?>
                                                                <option value="<?= $service->id ?>" <?php if (!empty($exp_services)) { ?> <?php foreach ($exp_services as $exp_service) { ?> <?php if (trim($exp_service) == $service->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $service->title ?></option>
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
                                                        <?php if (!empty($combine_statuses)) { ?>
                                                            <?php foreach ($combine_statuses as $status) { ?>
                                                                <option value="<?= $status->id ?>" <?php if (!empty($exp_statuses)) { ?> <?php foreach ($exp_statuses as $exp_status) { ?> <?php if (trim($exp_status) == $status->id) { ?> selected <?php } ?> <?php } ?> <?php } ?>><?= $status->status ?></option>
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
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Permissions & Services Section -->
                        <div class="form-section mb-4">
                            <h3 class="section-title">
                                <i class="bi bi-shield-check"></i>Permissions & Services
                            </h3>
                            <div class="row mb-3">

                            <div class="col-lg-12 mb-3">
                                <div class="accordion" id="permissionsAccordion">
                                    <!-- Permissions Accordion -->
                                    <div class="accordion-item">
                                        <h2 class="accordion-header" id="headingPermissions">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePermissions" aria-expanded="false" aria-controls="collapsePermissions">
                                                Permissions
                                            </button>
                                        </h2>
                                        <div id="collapsePermissions" class="accordion-collapse collapse" aria-labelledby="headingPermissions" data-bs-parent="#permissionsAccordion">
                                            <div class="accordion-body">

                                <?php if (!empty($permissions)): ?>

                                    <?php foreach ($permissions as $permission): ?>

                                        <div class="form-check">

                                            <input class="form-check-input" id="<?php echo $permission->title ?>"
                                                type="checkbox" <?php if ($permission->user_type == 1) { ?> checked <?php } ?>
                                                name="permissions[]" value="<?php echo $permission->id ?>">

                                            <label class="form-label form-check-label"
                                                for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                                            </div>
                                        </div>
                                    </div>

                                    <!-- Status Required Accordions -->
                                    <?php if (!empty($servicesCats)) : ?>
                                        <?php $accordion_index = 0; ?>
                                        <?php foreach ($servicesCats as $servicesCat) : ?>
                                            <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                            <?php $accordion_id = 'status_' . $servicesCat->id; ?>
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading<?php echo $accordion_id ?>">
                                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $accordion_id ?>" aria-expanded="false" aria-controls="collapse<?php echo $accordion_id ?>">
                                                        Status Required - <?php echo $servicesCat->name ?>
                                                    </button>
                                                </h2>
                                                <div id="collapse<?php echo $accordion_id ?>" class="accordion-collapse collapse" aria-labelledby="heading<?php echo $accordion_id ?>" data-bs-parent="#permissionsAccordion">
                                                    <div class="accordion-body">

                                        <?php if (!empty($statuses)): ?>

                                            <?php foreach ($statuses as $status): ?>

                                                <div class="form-check">

                                                    <input class="form-check-input" type="checkbox"
                                                        id="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>" name="statuses[]"
                                                        checked value="<?php echo $status->sID ?>">

                                                    <label class="form-label form-check-label"
                                                        for="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>"><?php echo $status->status ?></label>

                                                </div>

                                            <?php endforeach; ?>

                                        <?php endif; ?>

                                                    </div>
                                                </div>
                                            </div>
                                            <?php $accordion_index++; ?>
                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                    <!-- Allowed Services Accordion -->
                                    <?php if (!empty($services)) : ?>
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="headingServices">
                                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseServices" aria-expanded="false" aria-controls="collapseServices">
                                                    Allowed Services
                                                </button>
                                            </h2>
                                            <div id="collapseServices" class="accordion-collapse collapse" aria-labelledby="headingServices" data-bs-parent="#permissionsAccordion">
                                                <div class="accordion-body">

                                        <?php foreach ($services as $service): ?>

                                            <div class="form-check">

                                                <input class="form-check-input service_checkbox" id="<?php echo $service->title ?>"
                                                    <?php echo $service->service_cat_id == 1 ? 'checked' : '' ?> type="checkbox"
                                                    name="services[]" value="<?php echo $service->id ?>">

                                                <label class="form-label form-check-label"
                                                    for="<?php echo $service->title ?>"><?php echo $service->title ?></label>

                                            </div>

                                        <?php endforeach; ?>

                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

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
        }else{
            // Clear selections and hide the multi-select fields
            $('#combine_services').val(null).trigger('change');
            $('#combine_statuses').val(null).trigger('change');
            $("#combine_services").parent().hide();
            $("#combine_statuses").parent().hide();
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
</script>