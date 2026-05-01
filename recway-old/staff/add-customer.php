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
    $parent_customer = ! empty($_POST['parent_customer']) ? $_POST['parent_customer'] : null;
    $cus_department = ! empty($_POST['cus_department']) ? $_POST['cus_department'] : null;
    $interview_template = ! empty($_POST['interview_template']) ? $_POST['interview_template'] : null;
    $send_security_report = ! empty($_POST['send_security_report']) ? $_POST['send_security_report'] : 0;
    $changed_registration_email = isset($_POST['changed_registration_email']) ? $_POST['changed_registration_email'] : null;
    $reg_email = null;
    // Handle combine services - convert array to comma-separated string
    $combine_bk_and_security = isset($_POST['combine_bk_and_security']) && is_array($_POST['combine_bk_and_security']) ? implode(',', $_POST['combine_bk_and_security']) : '0';
    // Handle combine statuses - convert array to comma-separated string
    $combine_status = isset($_POST['combine_status']) && is_array($_POST['combine_status']) ? implode(',', $_POST['combine_status']) : '';
    $combine_interview_id = isset($_POST['combine_interview_id']) ? $_POST['combine_interview_id'] : 0;
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
        if (! empty($changed_registration_email)) {
            $email_text = $changed_registration_email;
            $reg_email = $changed_registration_email;
        } else {
            $email_text = $cus_reg_msg;
        }
    }
    $company = $_POST['company'];
    $cost_place = $_POST['cost_place'];
    $statuses = isset($_POST['statuses']) && ! empty($_POST['statuses']) ? $_POST['statuses'] : "";
    $statusStr = "";
    $services2 = $_POST['services'] ?? [];
    $per = $_POST['permissions'] ?? [];
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
        $invoice_period = isset($_POST['invoice_period']) && $_POST['invoice_period'] !== '' ? $_POST['invoice_period'] : 'day';
        $last_invoice_sent = ! empty($_POST['last_invoice_sent']) ? $_POST['last_invoice_sent'] : null;
        if (empty($last_invoice_sent)) {
            $todayTs = strtotime(date('Y-m-d'));
            switch ($invoice_period) {
                case 'day':
                    $last_invoice_sent = date('Y-m-d', strtotime('-1 day', $todayTs));
                    break;
                case 'week':
                    $last_invoice_sent = date('Y-m-d', strtotime('last monday', $todayTs));
                    break;
                case 'month':
                    $last_invoice_sent = date('Y-m-01', strtotime('first day of last month', $todayTs));
                    break;
            }
        }
        $query = 'INSERT INTO customers (name,email,password,phone,company,cost_place, statuses,reg_email,parent_id,dep_id,interview_template,send_security_report,combine_bk_and_security,combine_status,combine_interview_id, invoice_period, last_invoice_sent) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $crypt_pass, $phone, $company, $cost_place, $statusStr, $reg_email, $parent_customer, $cus_department, $interview_template, $send_security_report, $combine_bk_and_security, $combine_status,$combine_interview_id, $invoice_period, $last_invoice_sent]);
        $cus_id = $conn->lastInsertId();
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
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="company">Company</label>
                                <input type="text" class="form-control" name="company" required id="company">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="cost_place">Cost Place</label>
                                <input type="text" class="form-control" name="cost_place" required id="cost_place">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label>Profile</label>
                                <select name="parent_customer" id="parent_customer" onchange="get_dep(this)" class="form-control filter-select">
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
                                    <textarea rows="5" class="sign-textarea w-100" disabled name="changed_registration_email"><?php echo $cus_reg_msg ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input class="form-check-input" id="interview_template" type="checkbox" name="interview_template" value="1">
                                <label class="form-label form-check-label" for="interview_template">Interview Template</label>
                            </div>
                            <div class="col-md-6 mb-3">
                                <input class="form-check-input" id="send_security_report" type="checkbox" name="send_security_report" value="1">
                                <label class="form-label form-check-label" for="send_security_report">Send result of the basic investigation</label>
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
                                                </div>
                            <div class="row mb-4">
                            <div class="col-lg-3">
                                <label class="form-label">
                                    User Permissions
                                </label>
                                <?php if (! empty($permissions)) : ?>
                                    <?php foreach ($permissions as $permission) : ?>
                                        <div>
                                            <input class="form-check-input" id="<?php echo $permission->title ?>" type="checkbox" <?php if ($permission->user_type == 1) { ?> checked <?php } ?> name="permissions[]" value="<?php echo $permission->id ?>">
                                            <label class="form-label form-check-label" for="<?php echo $permission->title ?>"><?php echo $permission->title ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <?php if (! empty($servicesCats)) : ?>
                                <?php foreach ($servicesCats as $servicesCat) : ?>
                                    <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                    <div class="col-lg-3">
                                        <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                        <?php if (! empty($statuses)) : ?>
                                            <?php foreach ($statuses as $status) : ?>
                                                <div>
                                                    <input class="form-check-input" type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>" name="statuses[]" checked value="<?php echo $status->sID ?>">
                                                    <label class="form-label form-check-label" for="<?php echo str_replace(' ', '-', $status->variable) ?><?php echo $status->sID ?>"><?php echo $status->status ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="col-lg-3">
                                <label class="form-label">Allowed Services</label>
                                <?php if (! empty($services)) : ?>
                                    <?php foreach ($services as $service) : ?>
                                        <div>
                                            <input class="form-check-input service_checkbox" id="<?php echo $service->title ?>" <?php echo $service->service_cat_id == 1 ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                            <label class="form-label form-check-label" for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
        show_services_status()
        $('input[type="radio"][name="active_mail"]').on('click', function() {
            if ($(this).attr('id') == 'same_email') {
                $('#row_of_email').css('display', 'none');
                $('#row_of_email').find('textarea').prop('disabled', true)
            }
            if ($(this).attr('id') == 'change_email') {
                $('#row_of_email').css('display', 'block');
                $('#row_of_email').find('textarea').prop('disabled', false)
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
                success: function(response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.customers != '') {
                            var cus = response.customers;
                            if (cus[0].invoice_period) {
                                $("select[name='invoice_period']").val(cus[0].invoice_period).trigger('change');
                            }
                            if (cus[0].statuses != '') {
                                var stat = cus[0].statuses;
                                var stat = stat.split(",");
                                $('input[name="statuses[]"]').attr('checked', false);
                                stat.forEach(function(e) {
                                    $('input[name="statuses[]"]').each(function() {
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
                        if (response.permissions != '') {
                            var per = response.permissions;
                            $('input[name="permissions[]"]').attr('checked', false)
                            $('input[name="permissions[]"]').each(function(a, c) {
                                var chk_box = $(this);
                                $(per).each(function(k, b) {
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
                        }
                    }
                }
            });
        }
    }
</script>
<script>
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