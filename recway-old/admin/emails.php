<?php

include_once ('includes/header.php');

if(isset($_POST['resend'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $text = $_POST['text'];
    $subject = $_POST['subject'];

    sendMail($text, $email, $name, $subject);
}

$query = 'SELECT * FROM emails ORDER BY id DESC';
$stmt = $conn->prepare($query);
$stmt->execute();
$emails = $stmt->fetchAll();

?>


                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading mb-2 d-flex align-items-center justify-content-between w-100">
                            <div class="d-flex align-items-center">
                                <h1 class="">Emails</h1>
                            </div>
                        </div>
                        <div class="box shadow">
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