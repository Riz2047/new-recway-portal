<?php

$activeLink = "customers";

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

        flash("customerUpdated", "Customer updated successfully!");
        $query = 'UPDATE emails SET email = ? WHERE email = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$email, $old_email]);
    } else {
        flash("customerUpdated", "Could not update customer!");
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
            <div class="mx-lg-4 main-content">
                <div class="container">
                    <div class="row justify-content-center ">
                        <div class="col-lg-12 mb-3 d-flex justify-content-between">
                            <div class="profile-img">
    
                                <div class="tool-pit tool-pit2">
                                    <div class="tool-pit-content">
                                        <div class="d-flex justify-content-end">
                                            <div class="arrow-up me-3"></div>
                                        </div>
                                        <div class="tool-pit-content--header">
                                            <!-- <a href="" class="no-decoration text-white">Change Status</a> -->
                                        </div>
    
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-12 px-lg-0 mb-lg-0 mb-3 ">
                            <div class="white-box-p-0 h-100">
                                <div class="tab">
                                    <button class="tablinks f-14 w-700 " id="defaultOpen"
                                        onclick="openCity(event, 'edit')">Edit Customer</button>
                                    <button class="tablinks f-14 w-700 " onclick="openCity(event, 'orders')">Orders</button>
                                    <button class="tablinks f-14 w-700 " onclick="openCity(event, 'emails')">Emails</button>

                                </div>

                                <div id="edit" class="tabcontent ">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="table-section">
                                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                                        <h1 class="main-heading">Update Customer</h1>
                                                    </div>

                                                    <form class="update-form" method="post" enctype="multipart/form-data">
                                                        <div class="row mb-3">
                                                            <div class="col-lg-6 mb-3">
                                                                <label class="form-label" for="name">Name</label>
                                                                <input type="text" class="form-control" name="name" value="<?php echo $customer->name ?>" required id="name">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="email">Email</label>
                                                                <input type="email" class="form-control" name="email" value="<?php echo $customer->email ?>" required id="email">
                                                                <input type="hidden" required name="old_email" value="<?php echo $customer->email ?>" class="sign-input w-100 mb-3" placeholder="Email Address ">
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label class="form-label" for="phone">Phone</label>
                                                                <input type="text" class="form-control" name="phone" value="<?php echo $customer->phone ?>" required id="phone">
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
                                                                <label class="form-label" for="company">Company</label>
                                                                <input type="text" class="form-control" name="company" value="<?php echo $customer->company ?>" required id="company">
                                                            </div>
                                                            <div class="col-lg-6 mb-3">
                                                                <label class="form-label" for="cost_place">Cost Place</label>
                                                                <input type="text" class="form-control" name="cost_place" value="<?php echo $customer->cost_place ?>" required id="cost_place">
                                                            </div>

                                                            <div class="col-lg-6 mb-3">
                                                                <label class="form-label">Send Interview Report</label>
                                                                <select name="send_report" class="form-control mb-3">
                                                                    <option <?php echo empty($customer->send_security_report) ? 'selected' : '' ?> value="0">No</option>
                                                                    <option <?php echo !empty($customer->send_security_report) ? 'selected' : '' ?> value="1">Yes</option>
                                                                </select>
                                                            </div>

                                                            <?php if(!empty($servicesCats)): ?>
                                                                <?php foreach ($servicesCats as $servicesCat): ?>
                                                                    <?php $statuses3 = getStatusesByService($servicesCat->id) ?>
                                                                    <div class="col-lg-4" id="required-status">
                                                                        <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                                                        <?php if(!empty($statuses3)): ?>
                                                                            <?php foreach ($statuses3 as $status): ?>
                                                                                <div>
                                                                                    <input <?php echo in_array($status->sID, $cusStatuses) ? 'checked' : '' ?> class="form-check-input" type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>">
                                                                                    <label class="form-label form-check-label" for="<?php echo str_replace(' ', '-', $status->variable) ?>"><?php echo $status->status ?></label>
                                                                                </div>
                                                                            <?php endforeach; ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            <?php endif; ?>
                                                            <div class="col-lg-4">
                                                                <label class="form-label">Allowed Services</label>
                                                                <?php if(!empty($services)): ?>
                                                                    <?php foreach ($services as $service): ?>
                                                                        <div>
                                                                            <input class="form-check-input" id="<?php echo $service->title ?>" <?php echo in_array($service->id, $allowed_services) ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                                                            <label class="form-label form-check-label" for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>

                                                        <div class="d-flex justify-content-end">
                                                            <button id="update_customer_btn" type="submit" name="update_customer" class="btn-primary bg-primary">Update</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div id="orders" class="tabcontent ">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="table-section">
                                                    <div class="row">
                                                        <div class="main-heading  w-100">
                                                            <h1 class="main-heading">Total Orders</h1>
                                                            <!--                                        <input class="sign-input w-100 mb-1" type="text" placeholder="Filter by Date" name="stats_date" id="stats_date">-->
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
                                                                <?php if(!empty($statuses)): ?>
                                                                    <?php foreach ($statuses as $status): ?>
                                                                        <tr id="<?php echo str_replace(' ', '-', $status->status) ?>">
                                                                            <th><a class="no-decoration text-black" href="history.php?id=<?php echo $_GET['id'] ?>&status=<?php echo $status->id ?>"><?php echo $status->status ?></a></th>
                                                                            <td><?php echo $data[$status->variable] ?></td>
                                                                        </tr>
                                                                    <?php endforeach; ?>
                                                                <?php endif; ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div id="emails" class="tabcontent ">
                                    <div class="container">
                                        <div class="row">
                                            <div class="col-lg-12">
                                                <div class="table-section">
                                                    <h1 class="main-heading">Emails</h1>

                                                    <form action="" method="post" id="d-form">
                                                        <table class="display Table w-100">
                                                            <thead>
                                                            <tr>
                                                                <th class="table-head">Order ID</th>
                                                                <th class="table-head">Email Type</th>
                                                                <th class="table-head">Email</th>
                                                                <th class="table-head">Date</th>
                                                                <th class="table-head">Text</th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="d-none"></th>
                                                                <th class="table-head">Action</th>

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
                                                                            <td class="f-14"><?php echo $email->order_id ?></td>
                                                                            <td class="f-14"><?php echo $email->msg_type ?></td>
                                                                            <td class="f-14"><?php echo $email->email ?></td>
                                                                            <td class="f-14"><?php echo $email->created ?></td>
                                                                            <td class="f-14"><textarea name="text[]" class="sign-textarea" rows="3"><?php echo $email->text ?></textarea></td>
                                                                            <td class="d-none"><input type="text" name="user_type[]" value='<?php echo $email->user_type ?>'></td>
                                                                            <td class="d-none"><input type="text" name="order_id[]" value='<?php echo $email->order_id ?>'></td>
                                                                            <td class="d-none"><input type="text" name="msg_type[]" value='<?php echo $email->msg_type ?>'></td>
                                                                            <td class="d-none"><input type="text" name="name[]" value='<?php echo $email->user_name ?>'></td>
                                                                            <td class="d-none"><input type="text" name="email[]" value="<?php echo $email->email ?>"></td>
                                                                            <td class="d-none"><input type="text" name="subject[]" value="<?php echo $email->subject ?>"></td>
                                                                            <td class="d-none"><input type="text" name="count" value="<?php echo $count ?>"></td>
                                                                            <td class="text-center dt-center f-14">
                                                                                <button name="resend" value="<?php echo $count ?>" class="btn-primary-sm bg-primary">Resend</button>
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

<?php

include_once "includes/footer.php";

?>

<script>
    function openCity(evt, cityName) {
        var i, tabcontent, tablinks;
        tabcontent = document.getElementsByClassName("tabcontent");
        for (i = 0; i < tabcontent.length; i++) {
            tabcontent[i].style.display = "none";
        }
        tablinks = document.getElementsByClassName("tablinks");
        for (i = 0; i < tablinks.length; i++) {
            tablinks[i].className = tablinks[i].className.replace(" active", "");
        }
        document.getElementById(cityName).style.display = "block";
        evt.currentTarget.className += " active";
    }
    document.getElementById("defaultOpen").click();
</script>

<script>
    var customer = <?php echo json_encode($customer); ?>;
</script>