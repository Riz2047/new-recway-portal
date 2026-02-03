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

    $vasc_id = isset($_POST['vasc_id']) ? $_POST['vasc_id'] : null;

    $place = isset($_POST['place']) ? $_POST['place'] : null;

    $form_builder = isset($_POST['form_builder']) ? $_POST['form_builder'] : null;

    $background_check_date = !empty($_POST['background_check_date']) ? $_POST['background_check_date'] : null;

    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;
    $combine_interview_id = isset($_POST['security_interview_service_type']) ? $_POST['security_interview_service_type'] : '0';

    if (!empty($_FILES['files']['name'][0])) {
        $stmt = $conn->prepare("SELECT cv FROM candidates WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $existingFiles = !empty($row['cv']) ? explode(',', trim($row['cv'], ',')) : [];
        $remainingSlots = 5 - count($existingFiles);
        $newFiles = [];

        $totalFiles = count($_FILES['files']['name']);



        $files = null;

        for ($i = 0; $i < $totalFiles; $i++) {
 if ($remainingSlots <= 0)
                break;

            
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
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ?, cv = ?,meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $files, $form_builder, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, combine_interview_id = ?, meta_data = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $combine_interview_id, $form_builder, $_GET['id']]);
    }



    if (!empty($res)) {

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



$query = 'SELECT * FROM places';

$stmt = $conn->prepare($query);

$stmt->execute();

$places = $stmt->fetchAll();

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->cus_id]);
$customer = $stmt->fetch();
if (!empty($candidate->cus_id)) {

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

                            <div class="col-lg-6 mb-3">

                                <label class="form-label" for="security">Social Security Number</label>

                                <input type="text" class="form-control" value="<?php echo $candidate->security ?>" name="security" required id="security">

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

                            <div class="col-md-12 mb-3 d-none" id="place">

                                <label class="form-label" for="">Place</label>

                                <select id="" name="place" class="form-control filter-select">

                                    <?php if (!empty($places)) : ?>

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
                                    <?php if (!empty($interviews)) : ?>

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
                                <?php if($interview->id == 1 || $interview->id == 2): ?>
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

    function check_p_c(obj = null) {

        if (obj == null) {

            var obj_val = $('#interview').val();

        } else {

            var obj_val = $(obj).val();

        }

        var place = $('#hidden_interview').find('option[value=' + obj_val + ']').data('place');

        var country = $('#hidden_interview').find('option[value=' + obj_val + ']').data('country');

        if (place == 1) {

            $('#place').removeClass('d-none')

            $('select[name="place"]').prop("disabled", false)

        } else {

            $('#place').addClass('d-none')

            $('select[name="place"]').prop("disabled", true)

        }

        if (country == 1) {

            $('#country').removeClass('d-none')

            $('select[name="country"]').prop("disabled", false)

        } else {

            $('#country').addClass('d-none')

            $('select[name="country"]').prop("disabled", true)

        }

    }

    $(document).ready(function() {

        check_p_c();
        check_combine_bk_and_security();
    })
    // change_services();
    check_p_c(null)
    check_combine_bk_and_security();

    $('.filter-select').select2({

        dropdownParent: $('#content-modal .modal-content')
    });
    
    
    
    function check_combine_bk_and_security(){
        var selectedCustomer = $('#customer');
        var interview = $('#interview').val();
        var selectedInterview = $('#hidden_interview option[value="' + interview + '"]');
        
        var combine_bk_and_security = selectedCustomer ? selectedCustomer.data('combine-bk-and-security').toString() : 0; 
        var combine_bk_and_security_array = combine_bk_and_security && combine_bk_and_security.length > 0 ? combine_bk_and_security.split(',') : 0;
        var service_cat_id = selectedInterview.length > 0 ? selectedInterview.data('interview-service-cat-id') : 0;
        
        if(combine_bk_and_security_array && combine_bk_and_security_array.includes(selectedInterview.val().toString()) && service_cat_id == 3){
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