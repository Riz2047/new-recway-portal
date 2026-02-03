<?php

$activeLink = "all-orders";

include_once "includes/header.php";

if(isset($_POST['update_candidate'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];

    if(!empty($_FILES['files']['name'])) {
        $totalFiles = count($_FILES['files']['name']);

        $files = null;
        for($i=0; $i<$totalFiles; $i++) {
            $fileName = time() . '-' . $_FILES['files']['name'][$i];
            $files .= $fileName . ',';
            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/'.$fileName);
        }
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, cv = IFNULL(CONCAT(cv, ?), ?) WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $files, $files, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $_GET['id']]);
    }

    if(!empty($res)) {
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

?>
    
      <section>
        <div class="container mt-3">
          <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Update Candidate</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form" enctype="multipart/form-data">
                    <?php echo isset($message) ? $message : '' ?>
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
                    <div class="form-row mb-3 border-0">
                        <label for="cv" class="border-0">Documents</label>
                        <div class="drop-zone">
                                <span class="drop-zone__prompt">
                                  <div class="d-flex flex-column justify-content-center align-items-center">
                                    <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                    Here you can upload several documents (Interview Templates, Documents or CV)
                                  </div>
                                </span>
                            <input type="file" name="files[]" id="cv" class="drop-zone__input" accept="application/pdf" multiple>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                      <button type="submit" name="update_candidate" class="form-btn border-0">Update</button>
                    </div>
                  </form>
            </div>
            </div>
          </div>
      </section>

<?php

include_once "includes/footer.php";

?>