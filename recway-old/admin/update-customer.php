<?php

include_once ('includes/header.php');

$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

$query = 'SELECT * FROM customer_services WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($customer_services, 'service_id');

if(isset($_POST['update_customer'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $old_email = $_POST['old_email'];
    $phone = $_POST['phone'];
    $company = $_POST['company'];
    $cost_place = $_POST['cost_place'];
    $statuses = $_POST['statuses'];
    $statusStr = "";
    $services2 = $_POST['services'] ?? array();
    $send_report = $_POST['send_report'];

    if(!empty($statuses)) {
        foreach ($statuses as $key => $status) {
            if($key != count($statuses)-1) {
                $statusStr = $statusStr . $status . ",";
            }else {
                $statusStr = $statusStr . $status;
            }
        }
    }

    $query = 'UPDATE customers SET name = ?, email = ?, phone = ?, company = ?, cost_place = ?, statuses = ?, send_security_report = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $phone, $company, $cost_place, $statusStr, $send_report, $_GET['id']]);
    if(!empty($res)) {
        $excludeServices = array_diff(array_column($services, "id"), $services2);
        $includeServices = array_diff($services2, $allowed_services);

        if(!empty($excludeServices)) {
            foreach ($excludeServices as $excludeService) {
                $query = 'DELETE from customer_services WHERE cus_id = ? AND service_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_GET['id'], $excludeService]);
            }
        }

        if(!empty($includeServices)) {
            foreach ($includeServices as $includeService) {
                $query = 'INSERT INTO customer_services (cus_id, service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_GET['id'], $includeService]);
            }
        }

        $message = "<p class='alert alert-success'>Customer updated successfully!</p>";
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        $message = "<p class='alert alert-danger'>Could not update customer!</p>";
    }
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();

if(!empty($customer)) {
    $cusStatuses = explode(',', $customer->statuses);
}

$statuses = getStatuses();

$query = 'SELECT * FROM customer_services WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($customer_services, 'service_id');

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
$stmt->execute([$customer->email]);
$emails = $stmt->fetchAll();

$query = 'SELECT * FROM candidates WHERE cus_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidates = $stmt->fetchAll();

$keys = array_column($statuses, "id");
$values = array_column($statuses, "variable");
$statuses2 = array_combine($keys, $values);
$data = array();
foreach ($statuses2 as $key => $status) {
    $data[$status] = 0;
}

if(!empty($candidates)) {
    foreach ($candidates as $candidate) {
        $data[$statuses2[$candidate->status]] += 1;
    }
}

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();

?>

<?php echo !empty($emailMsg) ? '<div id="success-alert" style="position: fixed; bottom: 0; right: 20px; z-index: 1" class="alert alert-info" role="alert">
    ' . $emailMsg . '
</div>' : '' ?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Customer";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="update-customer.php?id=<?php echo $_GET['id'] ?>" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" value="<?php echo $customer->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Email</p>
                                        <input type="email" required name="email" value="<?php echo $customer->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                        <input type="hidden" required name="old_email" value="<?php echo $customer->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Phone</p>
                                        <input type="text" required name="phone" value="<?php echo $customer->phone ?>" class="sign-input w-100 mb-3" placeholder="Phone Number ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Company</p>
                                        <input type="text" required name="company" value="<?php echo $customer->company ?>" class="sign-input w-100 mb-3" placeholder="Company ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Cost Place</p>
                                        <input type="text" required name="cost_place" value="<?php echo $customer->cost_place ?>" class="sign-input w-100 mb-3" placeholder="Cost Place ">
                                    </div>

                                    <div class="col-lg-6 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Send Interview Report</p>
                                        <select name="send_report" class="form-select mb-3">
                                            <option <?php echo empty($customer->send_security_report) ? 'selected' : '' ?> value="0">No</option>
                                            <option <?php echo !empty($customer->send_security_report) ? 'selected' : '' ?> value="1">Yes</option>
                                        </select>
                                    </div>

                                    <?php if(!empty($servicesCats)): ?>
                                    <?php foreach ($servicesCats as $servicesCat): ?>
                                            <?php $statuses3 = getStatusesByService($servicesCat->id) ?>
                                        <div class="col-lg-4 ps-0" id="required-status">
                                            <p class="f-14 mb-0 pb-0 w-500">Status Required - <?php echo $servicesCat->name ?></p>
                                            <?php if(!empty($statuses3)): ?>
                                                <?php foreach ($statuses3 as $status): ?>
                                                    <div>
                                                        <input <?php echo in_array($status->sID, $cusStatuses) ? 'checked' : '' ?> type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                        <label for="<?php echo str_replace(' ', '-', $status->variable) ?>"><?php echo $status->status ?></label>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>

                                    <div class="col-lg-4 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Allowed Services</p>
                                        <?php if(!empty($services)): ?>
                                        <?php foreach ($services as $service): ?>
                                            <div>
                                                <input id="<?php echo $service->title ?>" <?php echo in_array($service->id, $allowed_services) ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                                <label for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_customer" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>

                            <div class="row">
                                <div class="main-heading  w-100">
                                    <h1 class=" mt-5 mb-2">Total Orders</h1>
                                    <input class="sign-input w-100 mb-1" type="text" placeholder="Filter by Date" name="stats_date" id="stats_date">
                                    <input type="hidden" id="cus_id" value="<?php echo $_GET['id'] ?>">
                                </div>
                                <div class="col-lg-12 mt-1">
                                    <a href="customer-candidates.php?id=<?php echo $customer->id ?>" style="text-decoration: none">
                                        <div class="total-card shadow-sm">
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="d-flex flex-column align-items-start">
                                                    <h1 class="f-16 w-500">Total Orders</h1>
                                                    <h1 id="total_orders_count" class="f-22 w-800"><?php echo !empty($candidates) ? count($candidates) : 0 ?></h1>
                                                </div>
                                                <div class="">
                                                    <i class="bi bi-clipboard-data f-40 "></i>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-lg-12">
                                    <table class="table table-bordered">
                                        <tbody>
<!--                                        <tr>-->
<!--                                            <th>Total Orders</th>-->
<!--                                            <td>--><?php //echo !empty($candidates) ? count($candidates) : 0 ?><!--</td>-->
<!--                                        </tr>-->
                                        <?php if(!empty($statuses)): ?>
                                        <?php foreach ($statuses as $status): ?>
                                            <tr id="<?php echo str_replace(' ', '-', $status->status) ?>">
                                                <th><a href="history.php?id=<?php echo $_GET['id'] ?>&status=<?php echo $status->id ?>"><?php echo $status->status ?></a></th>
                                                <td><?php echo $data[$status->variable] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="main-heading  w-100">
                                <h1 class=" mt-3 mb-4">Emails</h1>
                            </div>
                            <div class="data-table staff-table">
                                <form action="" method="post" id="d-form">
                                    <table id="dataTable" class="table" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th>Order ID</th>
                                            <th>Email Type</th>
                                            <th>Email</th>
                                            <th>Date</th>
                                            <th>Text</th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
                                            <th class="d-none"></th>
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
                                                <?php if($email->user_type == "Customer"): ?>
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
                                                        <td><?php echo $email->created ?></td>
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
