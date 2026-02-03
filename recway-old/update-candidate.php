<?php

$activeLink = "all-orders";

include_once "customer/includes/header.php";

if (isset($_POST['update_candidate'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $security = $_POST['security'];
    $vasc_id = isset($_POST['vasc_id']) ? $_POST['vasc_id'] : '';
    $pref = $_POST['pref'];
    $ref = $_POST['ref'];
    $comment = isset($_POST['comment']) ? $_POST['comment'] : '';
    $note = isset($_POST['note']) ? $_POST['note'] : '';
    $form_builder = isset($_POST['form_builder']) ? $_POST['form_builder'] : '';
    if (!empty($form_builder)) {
        $form_builder = json_encode($form_builder);
    }
    $template_file = null;
    if (!empty($_FILES['template']['name'])) {
        $fileName = time() . '-' . $_FILES['template']['name'];
        $fileName = str_replace(",", "", $fileName);
        $template_file  = $fileName;
        move_uploaded_file($_FILES['template']['tmp_name'], 'uploads/' . $fileName);
        $query = 'UPDATE candidates SET interview_template = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$template_file, $_GET['id']]);
    }
    if (!empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);

        $files = null;
        for ($i = 0; $i < $totalFiles; $i++) {
            $fileName = time() . '-' . $_FILES['files']['name'][$i];
            $files .= $fileName . ',';
            move_uploaded_file($_FILES['files']['tmp_name'][$i], 'uploads/' . $fileName);
        }
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, security = ?, vasc_id = ?, referensperson = ?, reference = ?, comment = ?, note = ?, cv = IFNULL(CONCAT(cv, ?), ?),meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $security, $vasc_id, $pref, $ref, $comment, $note, $files, $files, $form_builder, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, security = ?, vasc_id = ?, referensperson = ?, reference = ?, comment = ?, note = ?,meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $security, $vasc_id, $pref, $ref, $comment, $note, $form_builder, $_GET['id']]);
    }

    if (!empty($res)) {
        $message = "<p class='text-success text-center w-700 f-20'>Candidate updated successfully!</p>";
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        $message = "<p class='text-danger text-center w-700 f-20'>Could not update candidate!</p>";
    }
}

$query = 'SELECT * FROM candidates WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidate = $stmt->fetch();


$query = "SELECT form FROM order_forms WHERE cus_id = {$_SESSION['customer']->id} AND service_id =" . $candidate->interview_id;
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
?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Update Candidate</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form" enctype="multipart/form-data">
                    <?php echo isset($message) ? $message : '' ?>

                    <?php if (!empty($default_form)) { ?>
                        <?php if (!empty($personal)) { ?>
                            <input type="hidden" required name="old_email" value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                            <div class="form-tag mb-2">Personal Info</div>
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
                                        <input type="<?= $type ?>" <?php if ($new_field != '') { ?> name="form_builder[<?= $label ?>]" <?php $meta_data =  json_decode($candidate->meta_data) ?> value="<?php echo isset($meta_data->$label) ? $meta_data->$label : '' ?>" <?php } else { ?>name="<?= $name ?>" value="<?= $candidate->$name ?>" <?php } ?> placeholder="<?= $placehol ?>" <?= $req ?> class="w-100 from-input">
                                    </div>
                                <?php } else { ?>
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
                        <?php if (!empty($billing)) { ?>
                            <div class="form-tag mb-2">Billing Info</div>
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
                                        <input type="<?= $type ?>" <?php if ($new_field != '') { ?> name="form_builder[<?= $label ?>]" <?php $meta_data =  json_decode($candidate->meta_data) ?> value="<?php echo isset($meta_data->$label) ? $meta_data->$label : '' ?>" <?php } else { ?>name="<?= $name ?>" <?php } ?> placeholder="<?= $placehol ?>" <?= $req ?> class="w-100 from-input" <?php if ($name == 'ref') { ?>value="<?= $candidate->reference ?>" <?php } ?> <?php if ($name == 'pref') { ?>value="<?= $candidate->referensperson ?>" <?php } ?> <?php if ($name == 'comment') { ?>value="<?= $candidate->comment ?>" <?php } ?>>
                                    </div>
                                <?php } else { ?>
                                    <div class="form-row mb-1 border-0">
                                        <label for="" class="border-0">Note</label>
                                        <textarea name="note" id="" class="w-100 form-textarea" rows="4" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."></textarea>
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
                    <?php } else { ?>

                        <div class="form-tag mb-2">Profile Info</div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="name"> Name<span class="star text-danger">*</span></label>
                            <input id="name" type="text" required name="name" value="<?php echo $candidate->name ?>" placeholder="Enter Name" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="surname"> Surname<span class="star text-danger">*</span></label>
                            <input id="surname" type="text" required name="surname" value="<?php echo $candidate->surname ?>" placeholder="Enter Name" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-person"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="email">Email<span class="star text-danger">*</span></label>
                            <input id="email" type="email" required name="email" value="<?php echo $candidate->email ?>" placeholder="Enter Email" class="w-100 from-input">
                            <input type="hidden" required name="old_email" value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                            <div class="form-icon me-2">
                                <i class="bi bi-envelope"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="phone">Phone<span class="star text-danger">*</span></label>
                            <input id="phone" type="text" required name="phone" value="<?php echo $candidate->phone ?>" placeholder="Enter Phone Number" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-telephone"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="ssn">Social Security Number<span class="star text-danger">*</span></label>
                            <input id="ssn" type="text" value="<?php echo $candidate->security ?>" name="security" placeholder="Enter Candidate Social Security Number" class="w-100 from-input" required>
                            <div class="form-icon me-2">
                                <i class="bi bi-shield-fill-exclamation"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="vasc">VASC ID</label>
                            <input id="vasc" type="text" value="<?php echo $candidate->vasc_id ?>" name="vasc_id" placeholder="Enter Candidate VASC ID" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-app-indicator"></i>
                            </div>
                        </div>
                        <div class="form-row mb-3 border-0">
                            <label for="cv" class="border-0">Documents</label>
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
                        <div class="form-tag mb-2">Billing</div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="pref" class="label-lg">Reference<br>(Invoice Recipient)<span class="star text-danger">*</span></label>
                            <input id="pref" type="text" required name="pref" value="<?php echo $candidate->referensperson ?>" placeholder="Enter Candidate Reference" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-app-indicator"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="ref" class="label-lg">Reference<span class="star text-danger">*</span></label>
                            <input id="ref" required type="text" name="ref" value="<?php echo $candidate->reference ?>" placeholder="Enter Candidate Reference" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-app-indicator"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="" class="label-lg">Invoice Comment <br> (Visible on the invoice)</label>
                            <input type="text" name="comment" value="<?php echo $candidate->comment ?>" placeholder="Enter Invoice Comment" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-card-text"></i>
                            </div>
                        </div>
                        <div class="form-row mb-1 border-0">
                            <label for="" class="border-0">Note</label>
                            <textarea name="note" id="" class="w-100 form-textarea" rows="4" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."><?php echo $candidate->note ?></textarea>
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
                    <?php } ?>
                    <div class="d-flex justify-content-end">
                        <button type="submit" name="update_candidate" class="form-btn border-0">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>