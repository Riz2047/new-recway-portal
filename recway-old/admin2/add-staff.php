<?php

use PhpOffice\PhpSpreadsheet\Calculation\Category;

$activeLink = "staff";

include_once('includes/header.php');

$query = "SELECT * FROM settings";
$stmt = $conn->prepare($query);
$stmt->execute();
$settings = $stmt->fetchAll();
$query = "SELECT * FROM user_category";
$stmt = $conn->prepare($query);
$stmt->execute();
$staff_category = $stmt->fetchAll();

$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$all_staff = $stmt->fetchAll();

foreach ($settings as $setting) {
    $var = $setting->name;
    $$var = $setting->value;
}

if (isset($_POST['add_staff'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $category = $_POST['category'];
    $phone = $_POST['phone'];
    $staff_members = !empty($_POST['staff_members']) ? $_POST['staff_members'] : '';
    if (!empty($staff_members)) {
        $staff_members = implode(',', $staff_members);
    }

    $crypt_pass = password_hash($password, PASSWORD_BCRYPT);

    $query = 'INSERT INTO staff (name,email,password,phone,category,staff_members) VALUES (?,?,?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $email, $crypt_pass, $phone, $category, $staff_members]);
    if (!empty($res)) {

        $body = replace($staff_reg_msg, '', '', '', '', $name, $email, $password, '', '');

        $subject = "Registration";

        saveEmail("Staff", $name, "N/A", 'Staff Registration Message', $body, $email, $subject);
        sendMail($body, $email, $name, $subject);

        flash("staffAdded", "Staff added successfully!");
        //        redirect('staff.php');
    } else {
        flash("staffAdded", "Could not add staff!", "errorMsg");
    }
}

?>

<?php flash("staffAdded"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Add Staff</h1>
                    </div>

                    <form class="update-form" method="post">
                        <div class="row mb-3">
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="name">Name</label>
                                <input type="text" required name="name" class="form-control" id="name">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="email">Email</label>
                                <input type="email" required name="email" class="form-control" id="email">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label" for="password">Password</label>
                                <input type="text" value="<?php echo rand_string(7) ?>" required name="password" class="form-control" id="password">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label" for="phone">Phone</label>
                                <input type="text" required name="phone" class="form-control" id="phone">
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control">
                                    <?php if (!empty($staff_category)) { ?>
                                        <?php foreach ($staff_category as $cat) { ?>
                                            <option value="<?= $cat->id ?>"><?= $cat->title ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Staff (Under this staff member):</label>
                                <select name="staff_members[]" class="form-control filter-select" multiple="multiple">
                                    <?php if (!empty($all_staff)) { ?>
                                        <?php foreach ($all_staff as $all_staf) { ?>
                                            <option value="<?= $all_staf->id ?>"><?= $all_staf->name ?></option>
                                        <?php } ?>
                                    <?php } ?>
                                </select>
                            </div>

                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" name="add_staff" class="btn-primary bg-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<?php

include_once('includes/footer.php');

?>