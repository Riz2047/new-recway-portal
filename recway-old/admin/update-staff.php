<?php

include_once ('includes/header.php');

if(isset($_POST['update_staff'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];

    $query = 'UPDATE staff SET name = ?, email = ?, phone = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $phone, $_GET['id']]);
    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Staff updated successfully!</p>";
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        $message = "<p class='alert alert-danger'>Could not update staff!</p>";
    }
}

$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$staff = $stmt->fetch();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Staff";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="update-staff.php?id=<?php echo $_GET['id'] ?>" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" value="<?php echo $staff->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" value="<?php echo $staff->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                        <input type="hidden" required name="old_email" value="<?php echo $staff->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Phone</p>
                                        <input type="text" required name="phone" value="<?php echo $staff->phone ?>" class="sign-input w-100 mb-3" placeholder="Phone Number ">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_staff" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>