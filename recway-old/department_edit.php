<?php

$activeLink = "departments";

include_once "customer/includes/header.php";


$query = 'SELECT * FROM interviews';
 $stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();
$query = 'SELECT * FROM department_services WHERE dep_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$dep_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($dep_services, 'dep_service_id');
if (isset($_POST['update']) && isset($_GET['id'])) {
    $name = $_POST['name'];
    $statuses =  [];
    if (isset($_POST['statuses'])) {
        $statuses = $_POST['statuses'];
    }
    $statusStr = "";
    $services2 = $_POST['services'] ?? array();

    if (!empty($statuses)) {
        foreach ($statuses as $key => $status) {
            if ($key != count($statuses) - 1) {
                $statusStr = $statusStr . $status . ",";
            } else {
                $statusStr = $statusStr . $status;
            }
        }
    }
    if (update('departments', ['dep_name' => $name, 'dep_status' => $statusStr], 'dep_id', $_GET['id'])) {
        if (!empty($services2)) {
            $query = 'DELETE from department_services WHERE dep_id = ?';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$_GET['id']]);
        }

        if (!empty($services2)) {
            foreach ($services2 as $includeService) {
                $query = 'INSERT INTO department_services (dep_id, dep_service_id) VALUES (?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$_GET['id'], $includeService]);
            }
        }
        $message = "<p class='text-success text-center w-700 f-20'>Department updated successfully!</p>";
    } else {
        $message = "<p class='text-danger text-center w-700 f-20'>Could not update department!</p>";
    }
}
if (isset($_POST['trash'])) {
    if (update('deparment_users', ['dep_user_trash' => 1], 'dep_user_id', $_POST['trash'])) {
        $message = "<p class='text-danger text-center w-700 f-20'>User deleted successfully!</p>";
    }
}
$department =  findAllByQuery("SELECT * FROM departments WHERE  dep_id = {$_GET['id']} AND dep_trash = 0");
$department_users =  findAllByQuery("SELECT * FROM department_users WHERE  dep_id = {$_GET['id']} AND dep_user_trash = 0 ORDER BY dep_user_name DESC");
if (!empty($department)) {
    $cusStatuses = explode(',', $department[0]->dep_status);
}
$statuses = getStatuses();
$keys = array_column($statuses, "id");
$values = array_column($statuses, "variable");
$statuses2 = array_combine($keys, $values);
$data = array();
foreach ($statuses2 as $key => $status) {
    $data[$status] = 0;
}

if (!empty($candidates)) {
    foreach ($candidates as $candidate) {
        $data[$statuses2[$candidate->status]] += 1;
    }
}
$query = 'SELECT * FROM department_services WHERE dep_id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$dep_services = $stmt->fetchAll(PDO::FETCH_ASSOC);
$allowed_services = array_column($dep_services, 'dep_service_id');
$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();
?>

<section>
    <div class="container mt-3">
        <?php echo isset($message) ? $message : '' ?>
        <div class="row">
            <h2 class="f-18 w-800 text-black">
                Department Update
            </h2>
            <div class="col-lg-12">
                <form action="" method="post" class="form" enctype="multipart/form-data">
                    <div class="form-tag mb-2">Department Info</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="name"> Department Name </label>
                        <input id="name" type="text" required name="name" value="<?= $department[0]->dep_name ?>" placeholder="Department Name" class="w-100 from-input">
                    </div>
                    <div class="row">
                        <?php if (!empty($servicesCats)) : ?>
                            <?php foreach ($servicesCats as $servicesCat) : ?>
                                <?php $statuses3 = getStatusesByService($servicesCat->id) ?>
                                <div class="col-lg-4" id="required-status">
                                    <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                    <?php if (!empty($statuses3)) : ?>
                                        <?php foreach ($statuses3 as $status) : ?>
                                            <div>
                                                <input <?php echo in_array($status->sID, $cusStatuses) ? 'checked' : '' ?> class="form-check-input" type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>" />
                                                <label class="form-label form-check-label" for="<?php echo str_replace(' ', '-', $status->variable) ?>"><?php echo $status->status ?></label>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="col-lg-4">
                            <label class="form-label">Allowed Services</label>
                            <?php if (!empty($services)) : ?>
                                <?php foreach ($services as $service) : ?>
                                    <?php if (!empty($service->service_cat_id == 1)) : ?>
                                        <div>
                                            <input class="form-check-input" id="<?php echo $service->title ?>" <?php echo in_array($service->id, $allowed_services) ? 'checked' : '' ?> type="checkbox" name="services[]" value="<?php echo $service->id ?>" />
                                            <label class="form-label form-check-label" for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update" class="form-btn border-0">Update</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<section>
    <div class="container mt-3">
        <div class="row">
            <div class="col-lg-12">
                <div class="d-flex align-items-center justify-content-between w-100">
                    <div>
                        <h2 class="f-18 w-800 text-black">
                            Department Users
                        </h2>
                    </div>
                </div>
                <div class="table-div p-2">
                    <table id="myTable" class="display Table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($department_users)) { ?>
                                <?php foreach ($department_users as $dep_user) { ?>
                                    <tr>
                                        <td><?= $dep_user->dep_user_name ?></td>
                                        <td>
                                            <div class="dropdown">
                                                <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                    <li class="mb-1"><a href="department_user_edit.php?id=<?= $dep_user->dep_user_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                            Edit</a>
                                                    </li>

                                                    <li class="mb-1"><a href="department_edit.php?trash=<?= $dep_user->dep_user_id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                            Delete</a>
                                                    </li>

                                                </ul>
                                            </div>

                                        </td>
                                    </tr>
                                <?php } ?>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>