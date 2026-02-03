<?php

$activeLink = "all-orders";

include_once ('customer/includes/header.php');
//include_once ('includes/config.php');

if(!isset($_GET['id'])) {
    redirect('orders.php');
}

if(isset($_POST['add'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];
}

?>

    <section>
        <div class="container mt-3">
            <div class="row">
                <p class="f-14 text-grey w-400 mb-0 pb-0">Add Researcher</p>
                <div class="col-lg-12">
                    <form action="" method="post" class="form">
                        <?php echo isset($message) ? $message : '' ?>
                        <div class="form-tag mb-2">Researcher</div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="" class="label-lg">Researcher Email</label>
                            <input type="email" name="email" placeholder="Enter email" class="w-100 from-input">
                            <div class="form-icon me-2">
                                <i class="bi bi-envelope-at"></i>
                            </div>
                        </div>
                        <div class="d-flex align-items-center form-row mb-3">
                            <label for="" class="label-lg">Researcher Password</label>
                            <input type="email" name="password" value="<?php echo rand_string(7) ?>" placeholder="Enter password" class="w-100 from-input">
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