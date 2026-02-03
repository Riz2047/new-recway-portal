<?php

include_once ('includes/header.php');

if(isset($_POST['update_admin'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];

    $query = 'UPDATE admin SET name = ?, email = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $_GET['id']]);
    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Admin updated successfully!</p>";
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        $message = "<p class='alert alert-danger'>Could not update admin!</p>";
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
<?php echo !empty($emailMsg) ? '<div id="success-alert" style="position: fixed; bottom: 0; right: 20px; z-index: 1" class="alert alert-info" role="alert">
    ' . $emailMsg . '
</div>' : '' ?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Admin";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="update-admin.php?id=<?php echo $_GET['id'] ?>" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" value="<?php echo $admin->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" value="<?php echo $admin->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                        <input type="hidden" required name="old_email" value="<?php echo $admin->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_admin" class="btn-fill w-100 mt-4"><a>Update</a></button>
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
                                            <?php $count = 0; ?>
                                            <?php foreach ($emails as $email): ?>
                                                <?php if($email->user_type == "Admin"): ?>
                                                    <?php
                                                    $query = 'SELECT * FROM candidates WHERE order_id = ?';
                                                    $stmt = $conn->prepare($query);
                                                    $stmt->execute([$email->order_id]);
                                                    $candidate = $stmt->fetch();
                                                    ?>

                                                    <tr>
                                                        <td><?php echo $email->order_id ?></td>
                                                        <td><?php echo $email->msg_type ?></td>
                                                        <td><?php echo $email->email ?></td>
                                                        <td><textarea name="text[]" class="sign-textarea" rows="3"><?php echo $email->text ?></textarea></td>
                                                        <td class="d-none"><input type="text" name="user_type[]" value='<?php echo $email->user_type ?>'></td>
                                                        <td class="d-none"><input type="text" name="order_id[]" value='<?php echo $email->order_id ?>'></td>
                                                        <td class="d-none"><input type="text" name="msg_type[]" value='<?php echo $email->msg_type ?>'></td>
                                                        <td class="d-none"><input type="text" name="name[]" value='<?php echo $email->user_name ?>'></td>
                                                        <td class="d-none"><input type="text" name="email[]" value="<?php echo $email->email ?>"></td>
                                                        <td class="d-none"><input type="text" name="subject[]" value="<?php echo $email->subject ?>"></td>
                                                        <td class="d-none"><input type="text" name="count" value="<?php echo $count ?>"></td>
                                                        <td class="text-center dt-center">
                                                            <button name="resend" value="<?php echo $count ?>" class="btn text-dark-blue">Resend</button>
                                                            <?php $count++; ?>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
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

<script>
    $(document).ready(function () {
        if (localStorage) {
            var posReader = localStorage["posStorage"];
            if (posReader) {
                $('.layout').scrollTop(posReader);
                localStorage.removeItem("posStorage");
            }
        }

        $('.layout').scroll( function (e) {
            localStorage["posStorage"] = $(this).scrollTop();
        })

        $("#success-alert").fadeTo(2000, 500).slideUp(500, function(){
            $("#success-alert").slideUp(500);
        });
    })

</script>
