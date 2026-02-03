<?php

include_once ('includes/header.php');

$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

$query = "SELECT * FROM settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll();

foreach ($settings as $setting) {
    $var = $setting->name;
    $$var = $setting->value;
}

if(isset($_POST['add_customer'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $cost_place = $_POST['cost_place'];
    $statuses = isset($_POST['statuses']) && !empty($_POST['statuses']) ? $_POST['statuses'] : "";
    $statusStr = "";
    $services2 = $_POST['services'] ?? array();

    $query = "SELECT * FROM customers WHERE email = '{$email}'";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $customerExists = $stmt->fetch();

    if(empty($customerExists)) {
        $crypt_pass = password_hash($password, PASSWORD_BCRYPT);

        if(!empty($statuses)) {
            foreach ($statuses as $key => $status) {
                if($key != count($statuses)-1) {
                    $statusStr = $statusStr . $status . ",";
                }else {
                    $statusStr = $statusStr . $status;
                }
            }
        }

        $query = 'INSERT INTO customers (name,email,password,phone,company,cost_place, statuses) VALUES (?,?,?,?,?,?,?)';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $email, $crypt_pass, $phone, $company, $cost_place, $statusStr]);
        if(!empty($res)) {

            $cus_id = $conn->lastInsertId();
            insertMessages($cus_id);

            $query = 'INSERT INTO allowed_emails (cus_id, status_id, allowed) SELECT ? AS cus_id, id AS status_id, 1 AS allowed FROM statuses';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$cus_id]);

            foreach ($services2 as $service) {
                $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$cus_id, $service]);
            }

            $body = replace($cus_reg_msg, $name, '', $company, '','',$email, $password, '', '');

            $subject = "Registration";

            saveEmail("Customer", $name, "N/A", 'Customer Registration Message', $body, $email, $subject);
            sendMail($body, $email, $name, $subject);

            $message = "<p class='alert alert-success'>Customer added successfully!</p>";
            redirect('customers.php');
        } else {
            $message = "<p class='alert alert-danger'>Could not add customer!</p>";
        }
    } else {
        $message = "<p class='alert alert-danger'>Customer with this email already exists!</p>";
    }
}

$statuses = getStatuses();

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Add Customer";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Password</p>
                                        <input type="text" value="<?php echo rand_string(7) ?>" required name="password" class="sign-input w-100 mb-3" placeholder="Password ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Phone</p>
                                        <input type="text" required name="phone" class="sign-input w-100 mb-3" placeholder="Phone Number ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Company</p>
                                        <input type="text" required name="company" class="sign-input w-100 mb-3" placeholder="Company ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Cost Place</p>
                                        <input type="text" required name="cost_place" class="sign-input w-100 mb-3" placeholder="Cost Place ">
                                    </div>

                                    <?php if(!empty($servicesCats)): ?>
                                    <?php foreach ($servicesCats as $servicesCat): ?>
                                        <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                        <div class="col-lg-4 ps-0">
                                            <p class="f-14 mb-0 pb-0 w-500">Status Required - <?php echo $servicesCat->name ?></p>
                                            <?php if(!empty($statuses)): ?>
                                                <?php foreach ($statuses as $status): ?>
                                                    <div>
                                                        <input type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                        <label for="<?php echo str_replace(' ', '-', $status->variable) ?>"><?php echo $status->status ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>

<!--                                    <div class="col-lg-6 ps-0">-->
<!--                                        <p class="f-14 mb-0 pb-0 w-500">Status Required</p>-->
<!--                                        --><?php //if(!empty($statuses)): ?>
<!--                                        --><?php //foreach ($statuses as $status): ?>
<!--                                            <div>-->
<!--                                                <input type="checkbox" id="--><?php //echo str_replace(' ', '-', $status->variable) ?><!--" name="statuses[]" value="--><?php //echo $status->id ?><!--">-->
<!--                                                <label for="--><?php //echo str_replace(' ', '-', $status->variable) ?><!--">--><?php //echo $status->status ?><!--</label>-->
<!--                                            </div>-->
<!--                                        --><?php //endforeach; ?>
<!--                                        --><?php //endif; ?>
<!--                                    </div>-->

                                    <div class="col-lg-4 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Allowed Services</p>
                                        <?php if(!empty($services)): ?>
                                            <?php foreach ($services as $service): ?>
                                                <div>
                                                    <input id="<?php echo $service->title ?>" <?php echo $service->service_cat_id == 1  ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                                    <label for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="add_customer" class="btn-fill w-100 mt-4"><a>Save</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>