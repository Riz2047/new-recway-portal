<?php
$activeLink = "candidates";
include_once('includes/header.php');
if (isset($_POST['update_candidate'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $security = $_POST['security'];
    $note = $_POST['note'];
    $service = $_POST['service'];
    $vasc_id = $_POST['vasc_id'] ?? null;
    $place = isset($_POST['place']) ? $_POST['place'] : null;
    $form_builder = isset($_POST['form_builder']) ? $_POST['form_builder'] : null;
    $background_check_date = ! empty($_POST['background_check_date']) ? $_POST['background_check_date'] : null;
    $delivery_date = ! empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $combine_interview_id = isset($_POST['security_interview_service_type']) ? $_POST['security_interview_service_type'] : '0';
    $hasPersonalId = isset($_POST['hasPersonalId']) ? $_POST['hasPersonalId'] : 0;
    if (! empty($_FILES['files']['name'][0])) {
        $stmt = $conn->prepare("SELECT cv FROM candidates WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingFiles = ! empty($row['cv']) ? explode(',', trim($row['cv'], ',')) : [];
        $remainingSlots = 5 - count($existingFiles);
        $newFiles = [];
        $totalFiles = count($_FILES['files']['name']);
        $files = null;
        for ($i = 0; $i < $totalFiles; $i++) {
            if ($remainingSlots <= 0) {
                break;
            }
            // $originalName = $_FILES['files']['name'][$i];
            // $fileExtension = pathinfo($originalName, PATHINFO_EXTENSION);
            // $fileName = time() . '-' . uniqid() . '.' . $fileExtension;
            $originalName = $_FILES['files']['name'][$i];
            $fileName = time() . '-' . str_replace(",", "", $originalName);
            if (move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/' . $fileName)) {
                $newFiles[] = $fileName;
                $remainingSlots--;
            }
        }
        $finalFiles = array_merge($existingFiles, $newFiles);
        $files = implode(',', $finalFiles);
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ?, hasPersonalId = ?, cv = ?,meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $hasPersonalId, $files, $form_builder, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ?, hasPersonalId = ?, meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $hasPersonalId, $form_builder, $_GET['id']]);
    }
    if (! empty($res)) {
        flash("candidateUpdated", "Candidate updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("candidateUpdated", "Could not update candidate!");
    }
}
$query = 'SELECT * FROM candidates WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidate = $stmt->fetch();
$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$interviews = $stmt->fetchAll();
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
$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();
$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->cus_id]);
$customer = $stmt->fetch();
if (! empty($candidate->cus_id)) {
    $query = "SELECT form FROM order_forms WHERE cus_id = {$candidate->cus_id} AND service_id =" . $candidate->interview_id;
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $default_form = $stmt->fetch();
}
?>
<?php flash("candidateUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Update Candidate</h1>
                    </div>
                    <form class="update-form" method="post" enctype="multipart/form-data">
                        <input type="hidden" id="customer" value="<?php echo $customer->id ?>" 
                               data-combine-bk-and-security="<?php echo $customer->combine_bk_and_security ?>">
                        <div class="row mb-3">
                            <div class="form-check col-md-12 col-sm-12 mb-2" id="hasPersonalIdWrapper">
                                        <input class="form-check-input" type="checkbox" id="hasPersonalId" name="hasPersonalId" value="1" <?php echo $candidate->hasPersonalId ? 'checked' : '' ?> onchange="toggleInputType();">
                                        <label class="form-check-label" for="hasPersonalId">
                                            Has Personal Identification Number
                                        </label>
                                    </div>
                                    <div class="col-lg-6 mb-3">
                                        <label class="form-label" id="ssnLabel" for="ssn">Social Security Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="security" required id="ssn" placeholder="YYMMDD-XXXX" value="<?php echo htmlspecialchars($candidate->security, ENT_QUOTES, 'UTF-8'); ?>">
                                        <small id="pnrHelp" class="form-text"></small>
                                    </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="vasc_id">VASC ID</label>
                                <input type="text" class="form-control" value="<?php echo $candidate->vasc_id ?>" name="vasc_id" id="vasc_id">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" class="form-control" value="<?php echo $candidate->name ?>" name="name" required id="name">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="surname">Surname</label>
                                <input type="text" class="form-control" value="<?php echo $candidate->surname ?>" name="surname" required id="surname">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" class="form-control" value="<?php echo $candidate->email ?>" name="email" required id="email">
                                <input type="hidden" required name="old_email" value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" class="form-control" value="<?php echo $candidate->phone ?>" name="phone" required id="phone">
                            </div>
                            <div class="col-md-12 mb-3 <?php echo $candidate->interview_id == 2 || $candidate->interview_id == 4 || $candidate->interview_id == 26 ? '' : 'd-none' ?>" id="place">
                                <label class="form-label" for="">Place</label>
                                <select id="" name="place" class="form-control filter-select">
                                    <?php if (! empty($places)) : ?>
                                        <?php foreach ($places as $place) : ?>
                                            <option <?php echo $place->id == $candidate->place ? 'selected' : '' ?> value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <select id="hidden_interview" style="display:none">
                                    <?php foreach ($interviews as $interview) : ?>
                                        <option value="<?php echo $interview->id ?>" 
                                            data-country="<?= $interview->country ?>" 
                                            data-place="<?= $interview->place ?>"
                                            data-interview-service-cat-id="<?php echo $interview->service_cat_id ?>"><?php echo $interview->title ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label" for="">Service Type</label>
                                <select id="interview" name="service" class="form-control filter-select" onchange="check_p_c(this);check_combine_bk_and_security();">
                                    <?php if (! empty($interviews)) : ?>
                                        <?php foreach ($interviews as $key => $interview) : ?>
                                            <option <?php echo $interview->id == $candidate->interview_id ? 'selected' : '' ?> value="<?php echo $interview->id ?>"><?php echo $interview->title ?></option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-md-12 col-sm-12 mb-2 d-none" id="security_interview_service_type_div">
                              <label class="form-label" for="security_interview_service_type">Security Interview Service Type</label>
                              <!-- <select class="form-control" onchange="fetch_form_security_interview_service_type(this);" id="security_interview_service_type" name="security_interview_service_type" required="true"> -->
                              <select class="form-control" onchange="check_combine_bk_and_security()" id="security_interview_service_type" name="security_interview_service_type">
                              <option value="0">Select Security Interview Service Type</option>
                              <?php foreach ($interviews as $interview): ?>
                                <?php if ($interview->id == 1 || $interview->id == 2): ?>
                                                <option value="<?php echo $interview->id ?>" <?php echo isset($candidate->combine_interview_id) && $candidate->combine_interview_id == $interview->id ? 'selected' : '' ?>>
                                                    <?php echo $interview->title ?>
                                                </option>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                            </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="background_check_date">Background Check Date</label>
                                <input type="date" class="form-control" value="<?php echo $candidate->background_check_date ?>" name="background_check_date" id="background_check_date">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="delivery_date">Delivery Date</label>
                                <input type="date" class="form-control" value="<?php echo $candidate->delivery_date ?>" name="delivery_date" id="delivery_date">
                            </div>
                            <div class="col-lg-12 mb-3">
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
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Note
                                </label>
                                <br>
                                <textarea name="note" id="" style="width: 100%;" rows="6" placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual."><?php echo $candidate->note ?></textarea>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update_candidate" class="btn-primary bg-primary">Update</button>
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
    function bindSsnBehavior() {
        // Default: treat as Personal ID input
        const hasPersonalId = document.getElementById('hasPersonalId');
        const ssn = document.getElementById('ssn');
        const pnrHelp = document.getElementById('pnrHelp');
        const ssnLabel = document.getElementById('ssnLabel');
        if (!hasPersonalId || !ssn) return;
        // set default state (unchecked => PNR)
        if (!hasPersonalId.hasAttribute('data-initialized')) {
            hasPersonalId.checked = <?php echo $candidate->hasPersonalId ? 'true' : 'false'; ?>;
            hasPersonalId.setAttribute('data-initialized', '1');
        }
        document.getElementById('ssn').value = "<?php echo htmlspecialchars($candidate->security, ENT_QUOTES, 'UTF-8'); ?>";
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
        // If the security field is being used as a date of birth field, format it correctly
        if (!hasPersonalId || !hasPersonalId.checked) {
            if (securityField.type !== 'date') {
                securityField.type = 'date';
                securityField.removeAttribute('inputmode');
                securityField.removeAttribute('placeholder');
                securityField.value = ''; // Clear value only when switching to date
            }
            if (ssnLabel) ssnLabel.innerHTML = 'Date of Birth <span class="text-danger">*</span>';
            if (pnrHelp) pnrHelp.textContent = 'Date of birth is required';
        } else {
            if (securityField.type !== 'text') {
                securityField.type = 'text';
                securityField.setAttribute('inputmode', 'numeric');
                securityField.placeholder = 'YYMMDD-XXXX';
                securityField.value = ''; // Clear value only when switching to text
            }
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
        console.log('');
        if (place == 1) {
            $('#place').removeClass('d-none')
            $("select[name='place']").prop("disabled", false)
        } else {
            $('#place').addClass('d-none')
            $("select[name='place']").prop("disabled", true)
        }
        if (country == 1) {
            $('#country').removeClass('d-none')
            $("select[name='country']").prop("disabled", false)
        } else {
            $('#country').addClass('d-none')
            $("select[name='country']").prop("disabled", true)
        }
    }
    $(document).ready(function() {
        check_p_c();
        check_combine_bk_and_security();
        if (document.getElementById('ssn')) {
            bindSsnBehavior();
        }
    })
    // change_services();
    check_p_c(null)
    check_combine_bk_and_security();
    if (document.getElementById('ssn')) {
            bindSsnBehavior();
        }
    $('.filter-select').select2({
        dropdownParent: $('#content-modal .modal-content')
    });
    function check_combine_bk_and_security(){
        var selectedCustomer = $('#customer');
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