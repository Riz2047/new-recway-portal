<?php

$activeLink = "admins";

include_once ('includes/header.php');

if(isset($_POST['update_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];

    $query = 'UPDATE admin SET name = ?, email = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $_GET['id']]);
    if(!empty($res)) {
        flash("adminUpdated", "Admin updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("adminUpdated", "Could not update admin!");
    }
}

$query = 'SELECT * FROM admin WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$admin = $stmt->fetch();

if(isset($_POST['resend'])) {
    $count = $_POST['count'];
    $user_type = $_POST['user_type'][$_POST['resend']];
    $order_id = $_POST['order_id'][$_POST['resend']];
    $msg_type = $_POST['msg_type'][$_POST['resend']];
    $email = $_POST['email'][$_POST['resend']];
    $name = $_POST['name'][$_POST['resend']];
    $text = $_POST['text'][$_POST['resend']];
    $subject = $_POST['subject'][$_POST['resend']];

    saveEmail($user_type, $name, $order_id, $msg_type, $text, $email , $subject);
    $emailMsg = sendMail($text, $email, $name, $subject);
}

$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$admin->email]);
$emails = $stmt->fetchAll();

?>

            <?php flash("adminUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Update Admin</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" required name="name" value="<?php echo $admin->name ?>" class="form-control" id="name">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="email">Email</label>
                                                    <input type="email" required name="email" value="<?php echo $admin->email ?>" class="form-control" id="email">
                                                    <input type="hidden" required name="old_email" value="<?php echo $admin->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                                </div>
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label">Last Login</label>
                                                    <p><b><?php echo $admin->last_login ?></b></p>
                                                </div>

                                            </div> 
                                           
                                           <div class="d-flex justify-content-end">
                                            <button type="submit" name="update_admin" class="btn-primary bg-primary">Update</button>
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

include_once ('includes/footer.php');

?>