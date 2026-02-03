<?php

include_once ('includes/header.php');

if(!isset($_GET['id']) || $_GET['id'] != $_SESSION['admin']->id){
    redirect('index.php');
}

if(isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];

    $query = 'UPDATE admin SET name = ?, email = ?';

    if(!empty($_POST['password'])) {
        $query .= ", password = ? WHERE id = ?";

        $crypt_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $crypt_pass, $_GET['id']]);
    } else {
        $query .= " WHERE id = ?";
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $_GET['id']]);
    }

    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Profile updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not update profile!</p>";
    }
}

$query = 'SELECT * FROM admin WHERE id = ? LIMIT 1';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$admin = $stmt->fetch();

if(isset($_POST['resend'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $text = $_POST['text'];
    $subject = $_POST['subject'];

    sendMail($text, $email, $name, $subject);
}

$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$admin->email]);
$emails = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <div class="main-heading  w-100">
                            <h1 class=" mt-3 mb-4">Update Profile</h1>
                        </div>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="profile.php?id=<?php echo $_GET['id'] ?>" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Name</p>
                                        <input type="text" required name="name" value="<?php echo $admin->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Email</p>
                                        <input type="email" required name="email" value="<?php echo $admin->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-16 mb-0 pb-0 w-600">Password</p>
                                        <small>Leave empty if not want to change password</small>
                                        <input type="password" name="password" class="sign-input w-100 mb-3" placeholder="Password ">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_profile" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>

                            <div class="main-heading  w-100">
                                <h1 class=" mt-5 mb-4">Emails</h1>
                            </div>
                            <div class="data-table staff-table">
                                <form action="" method="post" id="d-form">
                                    <table id="dataTable" class="table" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>User Type</th>
                                            <th>User Name</th>
                                            <th>Email Type</th>
                                            <th>Email</th>
                                            <th>Text</th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                            <th class="dt-center">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(!empty($emails)): ?>
                                            <?php foreach ($emails as $email): ?>
                                                <?php
                                                $query = 'SELECT * FROM candidates WHERE order_id = ?';
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([$email->order_id]);
                                                $candidate = $stmt->fetch();
                                                ?>

                                                <tr>
                                                    <td><?php echo $email->order_id ?></td>
                                                    <td><?php echo $email->user_type ?></td>
                                                    <td><?php echo $email->user_name ?></td>
                                                    <td><?php echo $email->msg_type ?></td>
                                                    <td><?php echo $email->email ?></td>
                                                    <td><textarea class="sign-textarea" rows="3"><?php echo $email->text ?></textarea></td>
                                                    <td class="d-none"><input type="text" name="name" value='<?php echo $email->name ?>'></td>
                                                    <td class="d-none"><input type="text" name="text" value='<?php echo $email->text ?>'></td>
                                                    <td class="d-none"><input type="text" name="email" value="<?php echo $email->email ?>"></td>
                                                    <td class="d-none"><input type="text" name="subject" value="<?php echo $email->subject ?>"></td>
                                                    <td class="text-center dt-center">
                                                        <button name="resend" class="btn text-dark-blue">Resend</button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>

                                    </table>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>