<?php

$activeLink = "start-order";

include_once "customer/includes/header.php";

if (!isset($_GET['i'])) {
    redirect('index.php');
}
function getDateAfterDays($days)
{
    // Start from the current date
    $date = new DateTime();

    // Loop through the number of days
    while ($days > 0) {
        // Add 1 day to the current date
        $date->modify('+1 day');

        // Check if the current day is a weekday (Monday to Friday)
        if ($date->format('N') < 6) {
            $days--; // Decrease the number of days to go
        }
    }

    // Return the date after the number of days
    return $date->format('Y-m-d');
}
// Create a DateTime object for Sweden's timezone
$swedenTimezone = new DateTimeZone('Europe/Stockholm');
$swedenTime = new DateTime('now', $swedenTimezone);
$currentTime = $swedenTime->format('H:i:s');
$dayOfWeek = date('N');

$query = "SELECT * FROM customer_services WHERE cus_id={$_SESSION['customer']->id} AND service_id = {$_GET['i']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$customer_services = $stmt->fetchAll();
if (empty($customer_services)) {
    redirect('index.php');
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['customer']->id]);
$customer = $stmt->fetch();

$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['i']]);
$interview = $stmt->fetch();

if (empty($interview)) {
    redirect('index.php');
}

if (isset($_POST['order'])) {
    $d_date = null;
    $vasc_id = isset($_POST['vasc_id']) ? $_POST['vasc_id'] : null;
    $security = $_POST['security'];
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $referensperson = $_POST['pref'];
    $reference = $_POST['ref'];
    $cus_id = $_SESSION['customer']->id;
    $interview_id = $interview->id;
    $comment = isset($_POST['comment']) ? $_POST['comment'] : null;
    $note = isset($_POST['note']) ? $_POST['note'] : null;
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $country = isset($_POST['country']) ? $_POST['country'] : null;
    $sendMail = $_POST['sendMail'];
    $qs = !empty($_POST['qs']) ? $_POST['qs'] : null;
    $form_builder = isset($_POST['form_builder']) ? $_POST['form_builder'] : null;
    $meta_info = array(
        'send_email' => $sendMail,
        'created_by' => $_SESSION['customer']->id,
        'created_on' => date('Y-m-d H:i:s'),
        'user' => 'Customer'
    );
    // if (!empty($interview->delivery_days)) {
    //     $d_date = getDateAfterDays($interview->delivery_days);
    // }
    if (!empty($meta_info)) {
        $meta_info = json_encode($meta_info);
    }

    if (!empty($interview->place)) {
    } else {
        $place = null;
    }
    if (!empty($form_builder)) {
        $form_builder = json_encode($form_builder);
    }
    if (!empty($qs)) {
        $qs = json_encode($qs);
    }

    $query = "SELECT * FROM candidates";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();

    $order_ids = [];
    if (!empty($candidates)) {
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
    if ($interview->service_cat_id == 1) {
        $statusID = 1;
    } else if ($interview->service_cat_id == 3) {
        $statusID = 13;
    } else if ($interview->service_cat_id == 9) {
        $statusID = 33;
    }

    $query = "SELECT * FROM customer_services WHERE cus_id = ? AND service_id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$cus_id, $interview_id]);
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

    $query = "INSERT INTO candidates (order_id, vasc_id ,security, name, surname, email, phone, place, country, cv, referensperson, reference, comment, note, cus_id, interview_id, status,cus_qs_ans,meta_data,interview_template,meta_info,service_cost,delivery_date) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($query);

    $files = null;
    if (!empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);

        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = time() . '-' . $_FILES['files']['name'][$i];
            $fileName = str_replace(",", "", $fileName);
            $files .= $fileName . ',';
            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], 'uploads/' . $fileName);
        }
    }
    $template_file = null;
    if (!empty($_FILES['template']['name'])) {
        $fileName = time() . '-' . $_FILES['template']['name'];
        $fileName = str_replace(",", "", $fileName);
        $template_file  = $fileName;
        move_uploaded_file($_FILES['template']['tmp_name'], 'uploads/' . $fileName);
    }
    $res = $stmt->execute([$uid, $vasc_id, $security, $name, $surname, $email, $phone, $place, $country, isset($files) && !empty($files) ? $files : null, $referensperson, $reference, $comment, $note, $cus_id, $interview_id, $statusID, $qs, $form_builder, isset($template_file) && !empty($template_file) ? $template_file : null, $meta_info, $service_cost,$d_date]);

    if ($res) {
        $lastInsertId = $conn->lastInsertId();

        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$lastInsertId]);
        $candidate = $stmt->fetch();

        $query = 'SELECT * FROM interviews WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->interview_id]);
        $interview = $stmt->fetch();

        $query = 'SELECT * FROM places WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->place]);
        $place = $stmt->fetch();

        $query = 'SELECT * FROM service_categories WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$interview->service_cat_id]);
        $serviceCat = $stmt->fetch();

        $query = "INSERT INTO history (order_id, `desc`) VALUES (?,?)";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$lastInsertId, 'Order Created']);

        if ($sendMail == 'yes') {
            $messages = getMessages($cus_id, $interview->id);
            if (empty($messages)) {
                $messages = getMessages(0, 0);
            }

            $cus_msg = $interview->service_cat_id == 1 || $interview->service_cat_id == 9 ? $messages->cus_msg : $messages->cus_msg_background;

            $cusBody = replace($cus_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name);
                $mailMsg = sendMail($cusBody, $customer->email, $customer->name, $serviceCat->name);
            } else {
                saveEmail("Customer", $customer->name, $candidate->order_id, 'Customer Message', $cusBody, $customer->email, $serviceCat->name, '1');
            }
            if ($interview->service_cat_id == 1) {
                $statusID = 1;
            } elseif ($interview->service_cat_id == 3) {
                $statusID = 13;
            } elseif ($interview->service_cat_id == 9) {
                $statusID = 33;
            }
            $msg = getStatusMessage($statusID, $interview_id, $cus_id);
            if ($msg) {
                $msg = $msg->col;
            }

            $canBody = replace($msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');

            //            if($interview->id == 1 || $interview->id == 3) {
            //                $canBody = replace($messages->can_msg, $customer->name, $name. " " .$surname, $customer->company, $interview->title,'','','', '', '', $candidate->order_id,'','','', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            //            }else{
            //                $canBody = replace($messages->can_msg_2, $customer->name, $name. " " .$surname, $customer->company, $interview->title,'','','', '', '', $candidate->order_id,'','','', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');
            //            }

            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name);
                $mailMsg = sendMail($canBody, $_POST['email'], $_POST['name'], $serviceCat->name);
            } else {
                saveEmail("Candidate", $name, $candidate->order_id, 'Candidate Message', $canBody, $email, $serviceCat->name, '1');
            }
            if ($customer->sent_email == 1) {
                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                    saveEmail("Customer", $name, $candidate->order_id, 'CC email of candidate registration', $canBody, $customer->email, $serviceCat->name);
                    $mailMsg = sendMail($canBody, $customer->email, $_POST['name'], $serviceCat->name);
                } else {
                    saveEmail("Customer", $name, $candidate->order_id, 'CC email of candidate registration', $canBody, $customer->email, $serviceCat->name, '1');
                }
            }
            if (empty($messages->admin_msg)) {
                $messages->admin_msg = 'Order has been created successfully For ' . $customer->name . '(customer) and OrderID is' . $candidate->order_id;
            }
            $adminBody = replace($messages->admin_msg, $customer->name, $name . " " . $surname, $customer->company, $interview->title, '', '', '', '', '', $candidate->order_id, '', '', '', $candidate->vasc_id, $interview->title, !empty($place) ? $place->name : '');

            $query = 'SELECT * FROM admin LIMIT 1';
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $admin = $stmt->fetch();
            if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created');
                $mailMsg = sendMail($adminBody, $admin->email, $admin->name, "Order Created");
                $mailMsg = "<p class='text-success text-center w-700 f-20'>Order created successfully!</p>";
            } else {
                saveEmail("Admin", $admin->name, $candidate->order_id, 'Admin Message', $adminBody, $admin->email, 'Order Created', '1');
                $mailMsg = "<p class='text-success text-center w-700 f-20'>Order created successfully!</p>";
            }
        } else {
            $mailMsg = "<p class='text-success text-center w-700 f-20'>Candidate created successfully!</p>";
        }
    } else {
        $mailMsg = "<p class='text-danger text-center w-700 f-20'>Data save error!</p>";
    }
    redirect('orders.php');
}

$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();

$customer_question = findallByQuery("SELECT * FROM customer_question WHERE cus_id = {$_SESSION['customer']->id}");
if (empty($customer_question)) {
    $customer_question = findallByQuery("SELECT * FROM customer_question WHERE cus_id = 0");
}
if (!empty($customer_question)) {
    $customer_meta_data = json_decode($customer_question[0]->meta_data, true);
}


$query = "SELECT form FROM order_forms WHERE cus_id = {$_SESSION['customer']->id} AND service_id =" . $_GET["i"];
$stmt = $conn->prepare($query);
$stmt->execute();
$default_form = $stmt->fetch();
$personal = null;
$billing = null;
if (!empty($default_form)) {
    if (!empty($default_form->form)) {
        $default_form = json_decode($default_form->form);
        if (isset($default_form->form_builder) && !empty($default_form->form_builder)) {
            $form_builder = $default_form->form_builder;
            if (isset($form_builder->personal_info) && !empty($form_builder->personal_info)) {
                $personal = $form_builder->personal_info;
            }
            if (isset($form_builder->billing_info) && !empty($form_builder->billing_info)) {
                $billing = $form_builder->billing_info;
            }
        }
    }
}
$attachments = 0;
if (!empty($personal)) {
    foreach ($personal as $p_k => $p_v) {
        $real_dta = explode(',', $p_k);
        $name = isset($real_dta[2]) ? $real_dta[2] : '';
        if ($name == 'document_file') {
            $attachments = 1;
        }
    }
}
if (isset($cus_login->interview_template) && !empty($cus_login->interview_template)) {
    $attachments = 1;
}
?>
<style>
    #progressbar {
        margin-bottom: 30px;
        overflow: hidden;
        color: lightgrey;
    }

    #progressbar .active {
        color: #000000;
    }

    #progressbar li {
        list-style-type: none;
        font-size: 12px;
        width: 25%;
        float: left;
        position: relative;
        text-align: center
    }

    /*Icons in the ProgressBar*/
    #progressbar #attachment:before {
        font-family: FontAwesome;
        content: "\f0c6"
    }

    #progressbar #personal:before {
        font-family: FontAwesome;
        content: "\f007";
    }

    #progressbar #payment:before {
        font-family: FontAwesome;
        content: "\f09d";
    }

    #progressbar #confirm:before {
        font-family: FontAwesome;
        content: "\f00c";
    }

    /*ProgressBar before any progress*/
    #progressbar li:before {
        width: 50px;
        height: 50px;
        line-height: 45px;
        display: block;
        font-size: 18px;
        color: #ffffff;
        background: lightgray;
        border-radius: 50%;
        margin: 0 auto 10px auto;
        padding: 2px;
        z-index: 7;
        position: relative;
        float: right;
    }

    /*ProgressBar connectors*/
    #progressbar li:after {
        content: '';
        width: 100%;
        height: 2px;
        background: lightgray;
        position: absolute;
        left: 0;
        top: 25px;
        z-index: 1;
    }

    /*Color number of the step and the connector before it*/
    #progressbar li.active:before,
    #progressbar li.active:after {
        background: #ac0206;
        transition-duration: 1s;
    }
</style>
<section>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.0.3/css/font-awesome.css">
    <div class="container mt-3">
        <div class="row">
            <p class="f-22 text-grey w-700 mb-0 pb-0"><?php echo $interview->title ?></p>
            <div class="col-lg-12">
                <form method="post" action="order.php?i=<?php echo $_GET['i'] ?>" id="order_form" enctype="multipart/form-data" class="form">
                    <div class="row" <?php if (isset($_GET['question_check']) && !empty($_GET['question_check'])) { ?>id="qs_check" style="display:none !important" <?php } ?>>
                        <?php echo isset($mailMsg) ? $mailMsg : '' ?>
                        <?php if (!empty($default_form)) { ?>
                            <?php if (!empty($personal)) { ?>
                                <!-- progressbar -->
                                <?php if (empty($attachments)) { ?>
                                    <style>
                                        #progressbar li {
                                            width: 30% !important;
                                        }
                                    </style>
                                <?php } ?>
                                <ul id="progressbar">
                                    <li class="active" id="personal"><strong>Personal Information</strong></li>
                                    <?php if (!empty($attachments)) { ?>
                                        <li id="attachment"><strong>Attachments</strong></li>
                                    <?php } ?>
                                    <li id="payment"><strong>Billing Details</strong></li>
                                    <li id="confirm"><strong>Terms & Condidtion</strong></li>
                                </ul>
                                <fieldset>
                                    <div class="form-card">
                                        <h3 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Personal Information</h3><br>
                                        <?php foreach ($personal as $p_k => $p_v) { ?>
                                            <?php
                                            $real_dta = explode(',', $p_k);
                                            $type = isset($real_dta[0]) ? $real_dta[0] : '';
                                            $label = isset($real_dta[1]) ? $real_dta[1] : '';
                                            $name = isset($real_dta[2]) ? $real_dta[2] : '';
                                            $placehol = isset($real_dta[3]) ? $real_dta[3] : '';
                                            $req = isset($real_dta[4]) ? $real_dta[4] : '';
                                            $is_tra = isset($real_dta[5]) ? $real_dta[5] : '';
                                            $new_field = isset($real_dta[6]) ? $real_dta[6] : '';
                                            ?>
                                            <?php if ($name != 'document_file') { ?>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label class="label-lg"><?= $label ?><?php if (!empty($req)) { ?><span class="star text-danger">*</span><?php } ?></label>
                                                    <input type="<?= $type ?>" <?php if ($new_field != '') { ?> name="form_builder[<?= $label ?>]" <?php } else { ?>name="<?= $name ?>" <?php } ?> placeholder="<?php if (empty($p_v)) { ?><?= $placehol ?><?php } else { ?><?= $p_v ?><?php } ?>" <?= $req ?> class="w-100 from-input">
                                                </div>
                                            <?php } ?>
                                        <?php } ?>


                                        <?php if (!empty($interview->place)) : ?>
                                            <div class="d-flex align-items-center form-row mb-3">
                                                <label for="place">Place<span class="star text-danger">*</span></label>
                                                <select id="place" name="place" class="form-select from-input" aria-label="Default select example">
                                                    <?php if (!empty($places)) : ?>
                                                        <?php foreach ($places as $place) : ?>
                                                            <option value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($interview->country)) : ?>
                                            <div class="d-flex align-items-center form-row mb-3">
                                                <label for="place">Country<span class="star text-danger">*</span></label>
                                                <select id="place" name="country" class="form-select from-input" aria-label="Default select example">
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
                                                <!-- <div class="form-icon me-2">
                                <i class="bi bi-globe2"></i>
                              </div> -->
                                            </div>
                                        <?php endif; ?>
                                    <?php } ?>
                                    </div>
                                    <input type="button" name="next" class="btn btn-dark next action-button" style="float: right;" value="Next Step" />
                                </fieldset>
                                <?php if (!empty($attachments)) { ?>
                                    <fieldset style="display:none">
                                        <div class="form-card">
                                            <h2 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Attachments</h2>
                                            <?php if (!empty($personal)) { ?>
                                                <?php foreach ($personal as $p_k => $p_v) { ?>
                                                    <?php
                                                    $real_dta = explode(',', $p_k);
                                                    $type = isset($real_dta[0]) ? $real_dta[0] : '';
                                                    $label = isset($real_dta[1]) ? $real_dta[1] : '';
                                                    $name = isset($real_dta[2]) ? $real_dta[2] : '';
                                                    $placehol = isset($real_dta[3]) ? $real_dta[3] : '';
                                                    $req = isset($real_dta[4]) ? $real_dta[4] : '';
                                                    $is_tra = isset($real_dta[5]) ? $real_dta[5] : '';
                                                    $new_field = isset($real_dta[6]) ? $real_dta[6] : '';
                                                    ?>
                                                    <?php if ($name == 'document_file') { ?>
                                                        <div class="form-row mb-3 border-0">
                                                            <label for="cv" class="border-0"><?= $label ?></label>
                                                            <br>
                                                            <small class="text-success" id="doc-msg"></small>
                                                            <div class="drop-zone">
                                                                <span class="drop-zone__prompt">
                                                                    <div class="d-flex flex-column justify-content-center align-items-center">
                                                                        <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                                                        Here you can upload several documents (Documents or CV)
                                                                    </div>
                                                                </span>
                                                                <input type="file" name="files[]" id="cv" class="drop-zone__input" accept="application/pdf" multiple>
                                                            </div>
                                                        </div>
                                                    <?php } ?>
                                                <?php } ?>
                                            <?php } ?>
                                            <?php if (isset($cus_login->interview_template) && !empty($cus_login->interview_template)) { ?>
                                                <div class="form-row mb-3 border-0">
                                                    <label for="interview_template" class="border-0">Interview Template</label>
                                                    <br>
                                                    <small class="text-success" id="doc-msg"></small>
                                                    <div class="drop-zone">
                                                        <span class="drop-zone__prompt">
                                                            <div class="d-flex flex-column justify-content-center align-items-center">
                                                                <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                                                Here you can upload Interview Templates
                                                            </div>
                                                        </span>
                                                        <input type="file" name="template" id="interview_template" class="drop-zone__input" accept="application/pdf">
                                                    </div>
                                                </div>
                                            <?php } ?>
                                        </div>
                                        <input type="button" name="next" class="next btn btn-dark action-button" value="Next Step" style="float: right;" />
                                        <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                    </fieldset>
                                <?php } ?>

                                <?php if (!empty($billing)) { ?>
                                    <fieldset style="display:none">
                                        <div class="form-card">
                                            <h3 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Billing Info</h3><br>
                                            <?php foreach ($billing as $b_k => $b_v) { ?>
                                                <?php
                                                $real_dta = explode(',', $b_k);
                                                $type = isset($real_dta[0]) ? $real_dta[0] : '';
                                                $label = isset($real_dta[1]) ? $real_dta[1] : '';
                                                $name = isset($real_dta[2]) ? $real_dta[2] : '';
                                                $placehol = isset($real_dta[3]) ? $real_dta[3] : '';
                                                $req = isset($real_dta[4]) ? $real_dta[4] : '';
                                                $is_tra = isset($real_dta[5]) ? $real_dta[5] : '';
                                                $new_field = isset($real_dta[6]) ? $real_dta[6] : '';
                                                ?>
                                                <?php if ($name != 'note') { ?>
                                                    <div class="d-flex align-items-center form-row mb-3">
                                                        <label class="label-lg"><?= $label ?><?php if (!empty($req)) { ?><span class="star text-danger">*</span><?php } ?></label>
                                                        <input type="<?= $type ?>" <?php if ($new_field != '') { ?> name="form_builder[<?= $label ?>]" <?php } else { ?>name="<?= $name ?>" <?php } ?> placeholder="<?php if (empty($b_v)) { ?><?= $placehol ?><?php } else { ?><?= $b_v ?><?php } ?>" <?= $req ?> class="w-100 from-input">
                                                    </div>
                                                <?php } else { ?>
                                                    <div class="form-row mb-1 border-0">
                                                        <label for="" class="border-0">Note</label>
                                                        <textarea name="note" id="" class="w-100 form-textarea" rows="4" placeholder="<?php if (empty($b_v)) { ?>Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual.<?php } else { ?><?= $b_v ?><?php } ?>"></textarea>
                                                    </div>
                                                <?php } ?>
                                            <?php } ?>
                                        </div>
                                        <input type="button" name="next" class="btn btn-dark next action-button" style="float: right;" value="Next Step" />
                                        <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                    </fieldset>
                                <?php } ?>
                                <fieldset style="display:none">
                                    <div class="form-card">
                                        <h3 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Terms & Conditions</h3><br>
                                        <div class="d-flex align-items-center ">
                                            <p class="f-14 w-700 mb-0 pb-0 me-3">Send Mail<span class="star text-danger">*</span></label></p>
                                            <div class="form-check me-2">
                                                <input class="form-check-input" type="radio" name="sendMail" value="yes" checked id="flexRadioDefault1">
                                                <label class="form-check-label" for="flexRadioDefault1">
                                                    Yes
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="sendMail" value="no" id="flexRadioDefault2">
                                                <label class="form-check-label" for="flexRadioDefault2">
                                                    No
                                                </label>
                                            </div>
                                        </div>

                                        <div class="form-check mt-3">
                                            <input class="form-check-input" required type="checkbox" value="" id="flexCheckDefault">
                                            <label class="form-check-label" for="flexCheckDefault">
                                                I agree to the <a class="text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Integrity Policy</a>
                                            </label>
                                        </div>
                                    </div>
                                    <button type="submit" name="order" id="submit_btn" class="btn btn-dark border-0" style="float: right;">Send Order</button>
                                    <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                </fieldset>
                            <?php } else { ?>
                                <div class="row">
                                    <div class="col-md-12 mx-0">

                                        <!-- progressbar -->
                                        <ul id="progressbar">
                                            <li class="active" id="personal"><strong>Personal Information</strong></li>
                                            <li id="attachment"><strong>Attachments</strong></li>
                                            <li id="payment"><strong>Billing Details</strong></li>
                                            <li id="confirm"><strong>Terms & Condidtion</strong></li>
                                        </ul>
                                        <!-- fieldsets -->
                                        <fieldset>
                                            <div class="form-card">
                                                <h2 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Personal Information</h2>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="name"> Name<span class="star text-danger">*</span></label>
                                                    <input type="text" required id="name" name="name" placeholder="Enter Candidate Name" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="surname">Surname<span class="star text-danger">*</span></label>
                                                    <input type="text" required id="surname" name="surname" placeholder="Enter Candidate Surname" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="email">Email<span class="star text-danger">*</span></label>
                                                    <input id="email" required type="email" name="email" placeholder="Enter Candidate Email" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-envelope"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="phone">Phone<span class="star text-danger">*</span></label>
                                                    <input id="phone" required type="text" name="phone" placeholder="Enter Candidate Phone Number" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-telephone"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="ssn">Social Security Number<span class="star text-danger">*</span></label>
                                                    <input id="ssn" type="text" name="security" placeholder="Enter Candidate Social Security Number" class="w-100 from-input" required>
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-shield-fill-exclamation"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="vasc">VASC ID</label>
                                                    <input id="vasc" type="text" name="vasc_id" placeholder="Enter Candidate VASC ID" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-app-indicator"></i>
                                                    </div>
                                                </div>
                                                <?php if (!empty($interview->place)) : ?>
                                                    <div class="d-flex align-items-center form-row mb-3">
                                                        <label for="place">Place<span class="star text-danger">*</span></label>
                                                        <select id="place" name="place" class="form-select from-input" aria-label="Default select example">
                                                            <?php if (!empty($places)) : ?>
                                                                <?php foreach ($places as $place) : ?>
                                                                    <option value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                        </select>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($interview->country)) : ?>
                                                    <div class="d-flex align-items-center form-row mb-3">
                                                        <label for="place">Country<span class="star text-danger">*</span></label>
                                                        <select id="place" name="country" class="form-select from-input" aria-label="Default select example">
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
                                                <?php endif; ?>
                                            </div>
                                            <input type="button" name="next" class="btn btn-dark next action-button" style="float: right;" value="Next Step" />
                                        </fieldset>
                                        <fieldset style="display:none">
                                            <div class="form-card">
                                                <h2 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Attachments</h2>
                                                <div class="form-row mb-3 border-0">
                                                    <label for="cv" class="border-0">Documents</label>
                                                    <br>
                                                    <small class="text-success" id="doc-msg"></small>
                                                    <div class="drop-zone">
                                                        <span class="drop-zone__prompt">
                                                            <div class="d-flex flex-column justify-content-center align-items-center">
                                                                <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                                                Here you can upload document (Document or CV)
                                                            </div>
                                                        </span>
                                                        <input type="file" name="files[]" id="cv" class="drop-zone__input" accept="application/pdf" multiple>
                                                    </div>
                                                </div>
                                                <?php if (isset($cus_login->interview_template) && !empty($cus_login->interview_template)) { ?>
                                                    <div class="form-row mb-3 border-0">
                                                        <label for="interview_template" class="border-0">Interview Template</label>
                                                        <br>
                                                        <small class="text-success" id="doc-msg"></small>
                                                        <div class="drop-zone">
                                                            <span class="drop-zone__prompt">
                                                                <div class="d-flex flex-column justify-content-center align-items-center">
                                                                    <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                                                    Here you can upload Interview Templates
                                                                </div>
                                                            </span>
                                                            <input type="file" name="template" id="interview_template" class="drop-zone__input" accept="application/pdf">
                                                        </div>
                                                    </div>

                                                <?php } ?>
                                            </div>
                                            <input type="button" name="next" class="next btn btn-dark action-button" value="Next Step" style="float: right;" />
                                            <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                        </fieldset>
                                        <fieldset style="display:none">
                                            <div class="form-card">
                                                <h2 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Billing Details</h2>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="pref" class="label-lg">Reference<br>(Invoice Recipient)<span class="star text-danger">*</span></label>
                                                    <input id="pref" type="text" required name="pref" placeholder="Enter Candidate Reference" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-app-indicator"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="ref" class="label-lg">Reference<span class="star text-danger">*</span></label>
                                                    <input id="ref" required type="text" name="ref" placeholder="Enter Candidate Reference" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-app-indicator"></i>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center form-row mb-3">
                                                    <label for="" class="label-lg">Invoice Comment <br> (Visible on the invoice)</label>
                                                    <input type="text" name="comment" placeholder="Enter Invoice Comment" class="w-100 from-input">
                                                    <div class="form-icon me-2">
                                                        <i class="bi bi-card-text"></i>
                                                    </div>
                                                </div>
                                                <div class="form-row mb-1 border-0">
                                                    <label for="" class="border-0">Note</label>
                                                    <textarea name="note" id="" class="w-100 form-textarea" rows="4" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."></textarea>
                                                </div>
                                            </div>
                                            <input type="button" name="next" class="btn btn-dark next action-button" value="Next" style="float: right;" />
                                            <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                        </fieldset>
                                        <fieldset style="display:none">
                                            <div class="form-card">
                                                <h3 class="fs-title" style="background-color: lightgray;margin-bottom:25px !important;">Terms & Conditions</h3><br>
                                                <div class="d-flex align-items-center ">
                                                    <p class="f-14 w-700 mb-0 pb-0 me-3">Send Mail<span class="star text-danger">*</span></label></p>
                                                    <div class="form-check me-2">
                                                        <input class="form-check-input" type="radio" name="sendMail" value="yes" checked id="flexRadioDefault1">
                                                        <label class="form-check-label" for="flexRadioDefault1">
                                                            Yes
                                                        </label>
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio" name="sendMail" value="no" id="flexRadioDefault2">
                                                        <label class="form-check-label" for="flexRadioDefault2">
                                                            No
                                                        </label>
                                                    </div>
                                                </div>

                                                <div class="form-check mt-3">
                                                    <input class="form-check-input" required type="checkbox" value="" id="flexCheckDefault">
                                                    <label class="form-check-label" for="flexCheckDefault">
                                                        I agree to the <a class="text-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">Integrity Policy</a>
                                                    </label>
                                                </div>
                                            </div>
                                            <button type="submit" name="order" id="submit_btn" class="btn btn-dark border-0" style="float: right;">Send Order</button>
                                            <input type="button" name="previous" class="previous btn btn-dark action-button-previous m-2 mt-0 ml-0 mb-0" value="Previous" style="float: right;" />
                                        </fieldset>
                                    </div>
                                </div>
                            <?php } ?>
                            <!-- <div class="d-flex align-items-center form-row mb-3">
                      <label for="">Candidate's Country</label>
                      <select class="form-select from-input" aria-label="Default select example">
                        <option selected>Enter Candidate Country</option>
                        <option value="1">Pakistan</option>
                        <option value="2">Swedin</option>
                        <option value="3">Us</option>
                      </select>
                    </div> -->
                    </div>
                    <?php if (isset($_GET['question_check']) && !empty($_GET['question_check'])) { ?>
                        <div class="row" id="qs_box">
                            <div class="col-md-12">
                                <h5>Please answer the following:</h5>
                            </div>
                            <?php if (!empty($customer_meta_data)) { ?>
                                <?php foreach ($customer_meta_data as $i => $meta_data) { ?>
                                    <?php if (isset($meta_data['qs']) && !empty($meta_data['qs'])) { ?>
                                        <?php if (isset($meta_data['type']) && !empty($meta_data['type']) && $meta_data['type'] == 'radio') { ?>
                                            <div class="col-md-12 mb-4">
                                                <h5> <?php if (isset($meta_data['qs']) && !empty($meta_data['qs'])) { ?><?= $meta_data['qs'] ?> <?php } ?></h5>
                                                <?php if (isset($meta_data['option']) && !empty($meta_data['option'])) { ?>
                                                    <?php foreach ($meta_data['option'] as $val) { ?>
                                                        <input type="radio" <?php if (isset($meta_data['qs']) && !empty($meta_data['qs'])) { ?>name="qs[<?= $meta_data['qs'] ?>]" <?php } ?> class="form-check-input" value="<?= $val ?>">
                                                        <label><?= $val ?></label><br>
                                                    <?php } ?>
                                                <?php } ?>
                                            </div>
                                        <?php } ?>
                                        <?php if (isset($meta_data['type']) && !empty($meta_data['type']) && $meta_data['type'] == 'free_text') { ?>
                                            <div class="col-md-12 mb-4">
                                                <h5> <?php if (isset($meta_data['qs']) && !empty($meta_data['qs'])) { ?><?= $meta_data['qs'] ?> <?php } ?></h5>
                                                <textarea <?php if (isset($meta_data['qs']) && !empty($meta_data['qs'])) { ?>name="qs[<?= $meta_data['qs'] ?>]" <?php } ?> class="form-control"></textarea>
                                            </div>
                                        <?php } ?>
                                    <?php } ?>
                                <?php } ?>
                            <?php } ?>
                            <div class="col-md-12 mt-4 text-right">
                                <button class="btn btn-dark" style="float:right !important" type="button" onclick="show_other_data()">
                                    Prceed To Order
                                </button>
                            </div>
                        </div>
                    <?php } ?>
                </form>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content ">
            <div class="modal-header">
                <h5 class="modal-title f-16 w-600 text-black" id="exampleModalLabel">Integrity Policy</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <h1 class="f-16 w-600 text-black">Allmänt</h1>
                <p class="f-14 w-500 text-grey">Recway AB (Recway), org.nr 559102-3444, värnar om din personliga
                    integritet. Syftet med denna policy är att på ett tydligt och transparent sätt
                    redogöra för hur Recway AB hanterar dina personuppgifter i enlighet med
                    EU:s dataskyddsförordning 2016/679 (General Data Protection Regulation,
                    GDPR). Nedan hittar du bland annat information om vilka personuppgifter
                    Recway AB behandlar, för vilka ändamål, den rättsliga grunden för
                    behandlingen, hur länge dina uppgifter sparas samt vilka rättigheter du har.<br>
                    För samtliga av våra kunder, granskade, och egna medarbetare gäller att det
                    är du som enskild som, i enlighet med GDPR, har makten över dina
                    uppgifter.</p>

                <h1 class="f-16 w-600 text-black">Vem är personuppgiftsansvarig?</h1>
                <p class="f-14 w-500 text-grey">Våra kunder är personuppgiftsansvariga för behandlingen av dina
                    personuppgifter, och vi är personuppgiftsbiträde åt vår kund, när det gäller
                    tjänsterna:</p>
                <ul>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">Säkerhetsprövningsintervju </p>
                    </li>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">Bakgrundskontroll</p>
                    </li>
                    <li>
                        <p class="f-14 w-500 text-grey mb-0">Utbildning</p>
                    </li>
                </ul>
                <p class="f-14 w-500 text-grey">
                    Det är då vår kund som ansvarar för att informera dig som anställd,
                    jobbsökande eller referent om personuppgiftsbehandlingen.
                </p>
                <p class="f-14 w-500 text-grey">
                    Har du några frågor rörande behandlingen av dina personuppgifter från vår
                    sida, vänligen kontakta oss på <a href="mailto:dataprotection@recway.nu">dataprotection@recway.nu</a>.
                    För fler kontaktuppgifter, se rubriken ”Så kontaktar du oss”.
                </p>
                <p class="f-14 w-500 text-grey">
                    För frågor om behandlingen av personuppgifter från vår kund hör du enklast
                    av dig till deras personuppgiftsansvarige eller deras eventuella
                    dataskyddsombud.
                </p>
                <h1 class="f-16 w-600 text-black">Hur samlar vi in information?</h1>
                <p class="f-14 w-500 text-grey"><b>Från kund:</b> Vi samlar in information om dig från våra kunder, t.ex.
                    personuppgifter som du valt att inkludera i ditt CV och/eller andra
                    dokument.</p>
                <p class="f-14 w-500 text-grey"><b>Från tredje part:</b> Vi samlar in information om dig från allmänt tillgängliga
                    källor hos svenska eller utländska myndigheter samt från företag och/eller
                    utbildningsinstitut angivna i ditt CV.</p>
                <p class="f-14 w-500 text-grey">Recway får information från kunden om den som ska intervjuas (namn,
                    personnummer, kontaktinformation, tjänst som ska tillsättas, CV med
                    tillhörande information om eventuella utbildningar och tidigare arbetsgivare).
                    Vid en säkerhetsprövningsintervju samlar vi in information om den
                    granskade från offentliga källor och öppet publicerade webbplatser. Detta
                    material bearbetas därefter och slutligen sammanställs relevant information i
                    en rapport som den granskade får ta del av.</p>

                <h1 class="f-16 w-600 text-black">Varför samlar vi in personuppgifter?</h1>
                <p class="f-14 w-500 text-grey"><i> Avseende kandidater som genomgår en säkerhetsprövningsintervju behandlas
                        personuppgifter i huvudsak för de ändamål som anges nedan: </i></p>

                <p class="f-14 w-500 text-grey"><b> För administration och leverans av säkerhetsprövningsintervju:
                        Recway AB </b>behandlar dina personuppgifter för att kunna producera och
                    leverera våra tjänster, d.v.s. säkerhetsprövningsintervjuer.</p>

                <p class="f-14 w-500 text-grey"><b>Utskick och kommunikation:</b> Recway AB behandlar dina personuppgifter
                    för att vi ska kunna kommunicera relevant information till dig under tiden en
                    säkerhetsprövningsintervju pågår.</p>

                <p class="f-14 w-500 text-grey"><i> Avseende kunder behandlas personuppgifter i huvudsak för de ändamål som anges nedan: </i></p>

                <p class="f-14 w-500 text-grey"><b>För marknadsföring:</b> Recway AB använder dina personuppgifter för att
                    tillhandahålla information/marknadsföring via e-post, sms eller andra
                    kontaktvägar när du har en aktiv kundrelation med oss samt för att vi ska
                    kunna utföra riktade erbjudanden och tjänster.</p>

                <p class="f-14 w-500 text-grey"><b>För att leverera vår tjänst:</b> Recway AB behandlar våra kunders
                    personuppgifter främst i syfte att kunna leverera vår tjänst och uppfylla vårt
                    avtal med dig, d.v.s. tillhandahålla säkerhetsprövningsintervjuer.</p>

                <p class="f-14 w-500 text-grey"><b>För affärsutveckling:</b> Recway AB använder information om våra kunder i
                    syfte att ta fram statistiska data om tjänstens nyttjande. Denna statistik
                    identifierar dock aldrig enskilda personer, utan sker på aggregerad nivå.
                    Denna analys utförs i syfte att kunna utveckla, leverera och förbättra våra
                    produkter och tjänster.</p>

                <p class="f-14 w-500 text-grey"><i> Gällande våra utbildningar behandlas personuppgifter i huvudsak för de ändamål som
                        anges nedan:</i></p>
                <p class="f-14 w-500 text-grey"><b>Utbildning:</b> Personuppgifter om de som anmäler sig till våra kurser <br> Recway behandlar personuppgifter i samband med anmälan till våra kurser, för att administrera kursanmälan, genomföra kursen och för uppföljning. De kategorier av personuppgifter som behandlas är namn och kontaktuppgifter samt annan information som anges i anmälan. Den rättsliga grunden är att fullgöra det avtal som ingåtts genom anmälan, och när det gäller uppföljning, vårt berättigade intresse av att följa upp vår verksamhet.</p>

                <h1 class="f-16 w-600 text-black">Lagring</h1>
                <p class="f-14 w-500 text-grey">Recway AB säkerställer att vår personuppgiftsbehandling sker enligt gällande lagstiftning, vilket innebär att dina personuppgifter inte bevaras längre än vad som är nödvändigt med hänsyn till ändamålen med behandlingen. Avseende våra kandidater raderas därför alltid alla personuppgifter 14 dagar efter slutförd leverans. Vid avbruten process raderas alla personuppgifter skyndsamt. Avseende våra kunder lagras dina personuppgifter så länge du är kund hos oss. Uppgifterna gallras ut när de inte längre är aktuella eller nödvändiga för de ändamål som de samlats in för, t.ex. direktmarknadsföring eller analyser. Viss information kan dock behållas längre om det krävs med hänsyn till andra lagkrav, t.ex. bokföringslagen (1999:1078). Vid all hantering av personuppgifter iakttas dock alltid högsta säkerhet och sekretess. För de som anmäler sig till våra kurser och genomgår utbildning hos oss gäller att uppgifterna lagras från anmälan till och med 1 år efter avslutad kurs.</p>

                <h1 class="f-16 w-600 text-black">Dina rättigheter</h1>
                <p class="f-14 w-500 text-grey">Som registrerad har du flera olika rättigheter vad gäller dina personuppgifter och du har möjlighet att påverka din information och vad som sparas.</p>
                <p class="f-14 w-500 text-grey"><b>Komplettering och rättelse av uppgifter:</b> Recway AB kommer på din begäran eller efter eget initiativ att rätta eller komplettera uppgifter som upptäcks vara felaktiga, ofullständiga eller missvisande. Du har rätt att utan onödigt dröjsmål få dina personuppgifter raderade.</p>
                <p class="f-14 w-500 text-grey"><b>Kopia av uppgifter:</b> Kunder som har beställd en säkerhetsprövningsintervju får under 14 dagar tillgång till resultatet av säkerhetsprövningsintervjun. Eftersom alla personuppgifter raderas efter denna period, kan registerutdrag inte hämtas efter detta. I övrigt gäller att du har rätt att begära kopia av personuppgifterna under den tid de lagras hos oss (se ovan under ”Lagring”, såtillvida att de inte gallrats innan det av någon annan anledning). Om du begär ut dina uppgifter har du även rätt till att få dessa i enlighet med regleringen gällande dataportabilitet.</p>
                <p class="f-14 w-500 text-grey"><b>Invändningar:</b> Du kan när som helst avstå från att ta emot marknadsföringskommunikation från oss genom att i eventuella utskick välja att avsluta prenumeration. Om du behöver ytterligare hjälp avseende vår kommunikation, vänligen kontakta oss. Se rubriken ”Så kontaktar du oss” för kontaktuppgifter. Du har alltid rätt att göra invändningar till vår behandling av personuppgifter om du anser att vi inte har berättigade skäl till behandlingen.</p>
                <h1 class="f-14 w-500 text-grey"><b> Rätt att avbryta processen samt att återkalla samtycke:</b></h1>
                <p class="f-14 w-500 text-grey">Du har rätt att avbryta processen. På din begäran kommer Recway AB omedelbart sluta behandla dina personuppgifter och skyndsamt radera dina uppgifter. I det fall du väljer att avbryta processen eller återkalla ditt samtycke kan det dock medföra att nödvändiga moment för vidare hantering, exempelvis säkerhetsprövning för att ingå avtal med vår kund, inte kan genomföras. Om uppdragsgivaren väljer att avbryta processen kommer Recway AB skyndsamt radera dina uppgifter. Avbruten process påverkar dock inte lagligheten av behandlingen av dina personuppgifter innan processen avbröts eller samtycket återkallades.</p>

                <h1 class="f-14 w-500 text-grey"><b> Rätt att begära begränsning av behandlingen av personuppgifter:</b></h1>
                <p class="f-14 w-500 text-grey">Du kan med undantag för lagring begära att personuppgifterna bara får behandlas med ditt samtycke för att fastställa, göra gällande eller försvara rättsliga anspråk eller för att skydda någon annan fysisk eller juridisk persons rättigheter eller för skäl som rör ett viktigt allmänintresse för EU eller för ett EU-land. Även i detta fall vill vi dock uppmärksamma dig på att en sådan begäran kan medföra att nödvändiga moment för vidare hantering, exempelvis säkerhetsprövning för att ingå avtal med vår kund, inte kan genomföras.</p>

                <h1 class="f-16 w-600 text-black">Våra leverantörer och var de finns</h1>
                <p class="f-14 w-500 text-grey">Vi delar även personuppgifter med leverantörer av den IT-infrastruktur som vi behöver för att kunna tillhandahålla våra tjänster. Våra leverantörer lagrar data inom Sverige. Vi har vidtagit flera säkerhetsåtgärder såsom avancerad kryptering och pseudonymisering av data för att skydda dina personuppgifter.</p>

                <h1 class="f-16 w-600 text-black">Klagomål</h1>
                <p class="f-14 w-500 text-grey">Om du anser att dina rättigheter inte respekteras av Recway AB får du gärna kontakta oss. Du har också alltid rätt att inge klagomål till tillsynsmyndigheten Integritetsskyddsmyndigheten om du anser att Recway AB behandlar dina personuppgifter i strid med tillämplig lagstiftning. Sådana klagomål kan inges via e-post, <a href="mailto:imy@imy.se">imy@imy.se</a>, eller med brev till Integritetsskyddsmyndigheten, Box 8114, 104 20 Stockholm. Läs mer på www.imy.se</p>

                <h1 class="f-16 w-600 text-black">Cookies och länkar till andra hemsidor</h1>
                <p class="f-14 w-500 text-grey">Personuppgifter kan insamlas när du använder vår webbplats, och då lagras informationen om din användning och vilka sidor som besöks. Det kan vara teknisk information om din enhet och internetuppkoppling såsom operativsystem, webbläsarversion, IP-adress, cookies och unika identifierare. Vid besök på våra webbplatser där våra tjänster tillhandahålls kan olika tekniker användas för att känna igen dig i syfte att lära oss mer om våra användare. Detta kan ske direkt eller genom användning av teknik från tredje part. För att kunna använda vår webbplats fullt ut måste du acceptera cookies, och det gör du genom din webbläsares inställningar eller nere i sidfoten på din dator eller mobiltelefon. Vill du inte acceptera cookies kan du stänga av cookies via din webbläsares säkerhetsinställningar. Detta innebär dock att webbplatsen inte kommer att fungera som avsett. I händelse av att vår webbplats innehåller länkar till tredje parts webbplatser, hemsidor eller material publicerat hos tredje part, är dessa länkar endast för informationssyfte. Eftersom Recway AB saknar kontroll över innehållet på dessa webbplatser eller dess material ansvarar vi inte för dess innehåll. Recway AB ansvarar inte heller för skador eller förluster som skulle kunna uppstå vid användning av dessa länkar.</p>

                <h1 class="f-16 w-600 text-black">Så kontaktar du oss</h1>
                <p class="f-14 w-500 text-grey">För ytterligare information är du välkommen att kontakta oss på:</p>
                <p class="f-14 w-500 text-grey">Recway AB<br>
                    Olof Palmes gata 29<br>
                    111 20 Stockholm</p>
                <p class="f-14 w-500 text-grey">E-postadress: <a href="mailto:dataprotection@recway.nu">dataprotection@recway.nu</a><br>
                    Mobiltelefon: 070 65 65 770<br>
                    Telefonnummer: 08-611 10 20</p>

                <h1 class="f-16 w-600 text-black">Förändringar av denna personuppgiftspolicy</h1>
                <p class="f-14 w-500 text-grey">Denna personuppgiftspolicy är reviderad per den 28 juni 2022 (version 2022:2).</p>


            </div>
            <div class="modal-footer">
                <button type="button" class="form-btn" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php

include_once "customer/includes/footer.php";

?>
<script>
    $(document).ready(function() {
        $("#order_form").submit(function() {
            $("#submit_btn").hide();
        });
        var current_fs, next_fs, previous_fs; //fieldsets
        var opacity;

        $(".next").click(function() {
            var allValid = true;
            var required_inputs = $(this).closest('fieldset').find('input[required]')
            $(required_inputs).each(function() {
                if ($(this).attr('type') === 'email') {
                    var emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
                    if (!emailPattern.test($(this).val())) {
                        $(this).parent().css('border-bottom', '2px solid red');
                        this.setCustomValidity("Please enter a valid email address");
                        $(this).css('zoom', '1.2')
                        this.reportValidity();
                        $(this).on('input', function() {
                            if ($(this).val() !== '') {
                                $(this).parent().css('border-bottom', '1px solid black')
                                $(this).css('zoom', '0')
                                this.setCustomValidity("");
                            }
                        });
                        allValid = false;
                    } else {
                        $(this).parent().css('border-bottom', '1px solid black');
                        $(this).css('zoom', '0')
                        this.setCustomValidity("");
                    }
                } else {
                    if ($(this).val() == '') {
                        $(this).parent().css('border-bottom', '2px solid red')
                        $(this).css('zoom', '1.2')
                        this.setCustomValidity("Fill this Field");
                        this.reportValidity();
                        allValid = false;
                        $(this).on('input', function() {
                            if ($(this).val() !== '') {
                                $(this).parent().css('border-bottom', '1px solid black')
                                $(this).css('zoom', '0')
                                this.setCustomValidity("");
                            }
                        });
                        return false;
                    } else {
                        $(this).parent().css('border-bottom', '1px solid black')
                    }
                }
            })
            if (allValid) {
                current_fs = $(this).closest('fieldset');
                next_fs = $(this).closest('fieldset').next();

                //Add Class Active
                $("#progressbar li").eq($("fieldset").index(next_fs)).addClass("active");

                //show the next fieldset
                next_fs.show();
                //hide the current fieldset with style
                current_fs.animate({
                    opacity: 0
                }, {
                    step: function(now) {
                        // for making fielset appear animation
                        opacity = 1 - now;

                        current_fs.css({
                            'display': 'none',
                            'position': 'relative'
                        });
                        next_fs.css({
                            'opacity': opacity
                        });
                    },
                    duration: 900
                });
            }
        });

        $(".previous").click(function() {

            current_fs = $(this).closest('fieldset');
            previous_fs = $(this).closest('fieldset').prev();
            console.log(previous_fs)
            //Remove class active
            $("#progressbar li").eq($("fieldset").index(current_fs)).removeClass("active");

            //show the previous fieldset
            previous_fs.show();

            //hide the current fieldset with style
            current_fs.animate({
                opacity: 0
            }, {
                step: function(now) {
                    // for making fielset appear animation
                    opacity = 1 - now;

                    current_fs.css({
                        'display': 'none',
                        'position': 'relative'
                    });
                    previous_fs.css({
                        'opacity': opacity
                    });
                },
                duration: 900
            });
        });
    });
</script>