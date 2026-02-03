<?php

$activeLink = "departments";

include_once('customer/includes/header.php');

if (isset($_POST['add'])) {
    $name = $_POST['name'];
    $services2 = $_POST['services'] ?? array();
    if (!empty($name)) {
        insert("departments", ["dep_name" => $name, "dep_cus_id" => $_SESSION['customer']->id]);
        $msg = "<p class='text-success'>Department added successfully!</p>";
        $dep = findByQuery('SELECT * FROM departments WHERE dep_name = "' . $name . '" AND dep_cus_id = ' . $_SESSION['customer']->id);
    }
    if (!empty($dep)) {
        foreach ($services2 as $service) {
            $query = 'INSERT INTO department_services (dep_service_id, dep_id) VALUES (?,?)';
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$service, $dep->dep_id]);
        }
    }
}
$query = 'SELECT * FROM interviews LEFT JOIN customer_services ON interviews.id = customer_services.service_id WHERE customer_services.cus_id = ' . $_SESSION['customer']->id;
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Add Department</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form">
                    <?php echo isset($msg) ? $msg : '' ?>
                    <div class="form-tag mb-2">Department</div>
                    <div class="d-flex align-items-center form-row mb-3">
                        <label for="" class="label-lg">Department Name</label>
                        <input type="text" name="name" placeholder="Department Name" required class="w-100 from-input">
                    </div>
                    <div class="row">

                        <?php if (!empty($servicesCats)) : ?>
                            <?php foreach ($servicesCats as $servicesCat) : ?>
                                <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                <div class="col-lg-4">
                                    <label class="form-label">Status Required - <?php echo $servicesCat->name ?></label>
                                    <?php if (!empty($statuses)) : ?>
                                        <?php foreach ($statuses as $status) : ?>
                                            <div>
                                                <input class="form-check-input" type="checkbox" id="<?php echo str_replace(' ', '-', $status->variable) ?>" name="statuses[]" value="<?php echo $status->sID ?>">
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
                                    <div>
                                        <input class="form-check-input" id="<?php echo $service->title ?>" type="checkbox" name="services[]" value="<?php echo $service->id ?>">
                                        <label class="form-label form-check-label" for="<?php echo $service->title ?>"><?php echo $service->title ?></label>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
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

include_once('customer/includes/footer.php');

?>