<?php

$activeLink = "reviewers";

include_once ('customer/includes/header.php');
//include_once ('includes/config.php');

if(isset($_POST['add'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $reviewer = findByQuery("SELECT * FROM reviewers WHERE cus_id = {$_SESSION['customer']->id} AND email = '{$email}'");

    if(!empty($reviewer)) {
        $msg = "<p class='text-danger'>This email already exists!</p>";
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        insert("reviewers", ["cus_id" => $_SESSION['customer']->id, "email" => $email, "password" => $hashedPassword]);

        $body = "<p>You are added as a reviewer by {$_SESSION['customer']->name}. Please use following credentials to login.</p>";
        $body .= "<br>";
        $body .= "<strong>Email: {$email}</strong>";
        $body .= "<br>";
        $body .= "<strong>Password: {$password}</strong>";
        $body .= "<br><br>";
        $body .= "Click on the following link to access the portal";
        $body .= "<br><br>";
        $body .= "<a href='https://orderspi.se/reviewer'>https://orderspi.se/reviewer</a>";
        sendMail($body, $email, "Reviewer", "Reviewer added");

        $msg = "<p class='text-success'>Reviewer added successfully!</p>";
    }
}

?>

    <section>
        <div class="container mt-3">
            <div class="row">
                <p class="f-14 text-grey w-400 mb-0 pb-0">Add Reviewer</p>
                <div class="col-lg-12">
                    <form action="" method="post" class="form">
                        <?php echo isset($msg) ? $msg : '' ?>
                        <div class="form-tag mb-2">Reviewer</div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="" class="label-lg">Email</label>
                            <input type="email" name="email" placeholder="Enter email" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-envelope-at"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="" class="label-lg">Password</label>
                            <input type="text" name="password" value="<?php echo rand_string(7) ?>" placeholder="Enter password" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-key"></i>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="add" class="form-btn border-0">Add</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>


<?php

include_once ('customer/includes/footer.php');

?>