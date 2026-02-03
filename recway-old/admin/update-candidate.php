<?php

include_once ('includes/header.php');

if(isset($_POST['update_candidate'])) {
    $name = $_POST['name'];
    $surname = $_POST['surname'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $security = $_POST['security'];
    $note = $_POST['note'];
    $service = $_POST['service'];
    $vasc_id = $_POST['vasc_id'] ?? null;
    $place = $_POST['place'] ?? null;
    $background_check_date = !empty($_POST['background_check_date']) ? $_POST['background_check_date'] : null;
    $delivery_date = !empty($_POST['delivery_date']) ? $_POST['delivery_date'] : null;

    if(!empty($_FILES['files']['name'][0])) {
        $totalFiles = count($_FILES['files']['name']);

        $files = null;
        for($i=0; $i<$totalFiles; $i++) {
            $fileName = time() . '-' . $_FILES['files']['name'][$i];
            $files .= $fileName . ',';
            // $cv = !empty($_FILES['cv']['name']) ? $fileName : null;
            move_uploaded_file($_FILES['files']['tmp_name'][$i], '../uploads/'.$fileName);
        }
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ?, cv = IFNULL(CONCAT(cv, ?), ?) WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $files, $files, $_GET['id']]);
    } else {
        $query = 'UPDATE candidates SET name = ?, surname = ?, email = ?, phone = ?, place = ?, security = ?, vasc_id = ?, note = ?, interview_id = ?, background_check_date = ?, delivery_date = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $surname, $email, $phone, $place, $security, $vasc_id, $note, $service, $background_check_date, $delivery_date, $_GET['id']]);
    }

    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Candidate updated successfully!</p>";
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        $message = "<p class='alert alert-danger'>Could not update candidate!</p>";
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

//$query = 'SELECT * FROM comments WHERE order_id = ?';
//$stmt = $conn->prepare($query);
//$stmt->execute([$candidate->id]);
//$comments = $stmt->fetchAll();

$query = 'SELECT * FROM places';
$stmt = $conn->prepare($query);
$stmt->execute();
$places = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Candidate";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="update-candidate.php?id=<?php echo $_GET['id'] ?>" method="post" enctype="multipart/form-data">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" value="<?php echo $candidate->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Surname</p>
                                        <input type="text" required name="surname" value="<?php echo $candidate->surname ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                        <input type="hidden" required name="old_email" value="<?php echo $candidate->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Phone</p>
                                        <input type="text" required name="phone" value="<?php echo $candidate->phone ?>" class="sign-input w-100 mb-3" placeholder="Phone Number ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Social Security Number</p>
                                        <input type="text" required name="security" value="<?php echo $candidate->security ?>" class="sign-input w-100 mb-3" placeholder="Phone Number ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">VASC ID</p>
                                        <input type="text" name="vasc_id" value="<?php echo $candidate->vasc_id ?>" class="sign-input w-100 mb-3" placeholder="VASC ID ">
                                    </div>

                                    <?php if($candidate->interview_id == 2 || $candidate->interview_id == 4 || $candidate->interview_id == 26): ?>
                                        <div class="col-lg-6 ps-0">
                                            <p class="f-14 mb-0 pb-0 w-500">Place</p>
                                            <select class="form-select mb-3" name="place" id="">
                                                <?php foreach ($places as $key => $place): ?>
                                                    <option <?php echo $place->id == $candidate->place ? 'selected' : '' ?> value="<?php echo $place->id ?>"><?php echo $place->name ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Service Type</p>
                                        <select class="form-select mb-3" name="service" id="">
                                            <?php foreach ($interviews as $key => $interview): ?>
                                                <option <?php echo $interview->id == $candidate->interview_id ? 'selected' : '' ?> value="<?php echo $interview->id ?>"><?php echo $interview->title ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Background Check Date</p>
                                        <input type="date" name="background_check_date" value="<?php echo $candidate->background_check_date ?>" class="sign-input w-100 mb-3">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Delivery Date</p>
                                        <input type="date" name="delivery_date" value="<?php echo $candidate->delivery_date ?>" class="sign-input w-100 mb-3">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Additional Note</p>
                                        <textarea placeholder="Here you can create a note about the order that is visible to both you and us. Please note that this note will not be visible to the individual." class="w-100 sign-textarea" name="note" id="" rows="3"><?php echo $candidate->note ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <div class="form-group file-area w-100">
                                            <div class="d-flex justify-content-between">
                                                <label for="images" class="f-16 w-600 mt-2">Documents</label>
                                            </div>
                                            <input class="sign-input w-100 " type="file" name="files[]" id="cv"
                                                   accept="application/pdf" multiple/>
                                            <div class="file-dummy sign-input  ">
                                                <div class="success "></div>
                                                <div class="file-icon"><i style="font-size: 28px; color: #5c636a" class="fa-solid fa-cloud-arrow-up "></i></div>
                                                <div class="default ">Here you can upload several documents <small>(Interview Templates, Documents or CV)</small></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_candidate" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>


<!--                        <div class="box shadow mt-3">-->
<!--                            <div class="row">-->
<!--                                <div class="col-lg-12"><div class="bg-light p-2 w-600">Comments</div></div>-->
<!--                            </div>-->
<!--                            <div class="row">-->
<!--                                <div class="col-lg-12 mb-3">-->
<!--                                    --><?php //if(!empty($comments)): ?>
<!--                                        --><?php //foreach ($comments as $comment):
//                                            $query = 'SELECT * FROM '. $comment->author_type .' WHERE id = ?';
//                                            $stmt = $conn->prepare($query);
//                                            $stmt->execute([$comment->author_id]);
//                                            $author = $stmt->fetch();
//                                            ?>
<!--                                            <div class="mt-2 bg-light p-2">-->
<!--                                                <small class="p-0 m-0 w-600">~--><?php //echo $author->name ?><!--</small>-->
<!--                                                <p class="p-0 m-0">--><?php //echo $comment->comment ?><!--</p>-->
<!--                                                <p class="m-0 p-0 w-600" style="text-align: right; font-size: 12px">--><?php //echo date("M d, Y h:i A", strtotime($comment->created)) ?><!--</p>-->
<!--                                            </div>-->
<!--                                        --><?php //endforeach; ?>
<!--                                    --><?php //else: ?>
<!--                                        <div class="mt-2">-->
<!--                                            <p>No comments yet</p>-->
<!--                                        </div>-->
<!--                                    --><?php //endif; ?>
<!--                                </div>-->
<!--                            </div>-->
<!--                        </div>-->
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>