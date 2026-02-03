<?php

$activeLink = "profile";

include_once "includes/header.php";

$_GET['id'] = $_SESSION['customer']->id;

if(isset($_POST['update'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $cost_place = $_POST['cost_place'];

    $query = 'UPDATE customers SET name = ?, phone = ?, cost_place = ?';

    if(!empty($_POST['password'])) {
        $crypt_pass = password_hash($_POST['password'], PASSWORD_BCRYPT);

        $query .= ', password = ? WHERE id = ?';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $phone, $cost_place, $crypt_pass, $_GET['id']]);
    } else {
        $query .= " WHERE id = ?";

        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$name, $phone, $cost_place, $_GET['id']]);
    }

    if(!empty($res)) {
        $message = "<p class='text-success'>Profile updated successfully!</p>";
    } else {
        $message = "<p class='text-danger'>Could not update profile!</p>";
    }
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();

?>
    
      <section>
        <div class="container mt-3">
          <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Profile</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form">
                    <?php echo isset($message) ? $message : '' ?>
                  <div class="form-tag mb-2">Profile Info</div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="name"> Name<span class="star text-danger">*</span></label>
                    <input id="name" type="text" required name="name" value="<?php echo $customer->name ?>" placeholder="Enter Name" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-person"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="email">Email<span class="star text-danger">*</span></label>
                    <input id="email" type="email" required disabled name="email" value="<?php echo $customer->email ?>" placeholder="Enter Email" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-envelope"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="phone">Phone<span class="star text-danger">*</span></label>
                    <input id="phone" type="text" required name="phone" value="<?php echo $customer->phone ?>" placeholder="Enter Phone Number" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-telephone"></i>
                    </div>
                  </div>
                  
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="company">Company<span class="star text-danger">*</span></label>
                    <input id="company" type="text" required disabled name="company"  value="<?php echo $customer->company ?>" placeholder="Enter Company Name" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-building"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="cost_place">Cost Place<span class="star text-danger">*</span></label>
                    <input id="cost_place" type="text" required name="cost_place"  value="<?php echo $customer->cost_place ?>"  placeholder="Enter Cost Place" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-buildings"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="password">Password<span class="star text-danger"></span></label>
                    <input id="password" type="password" name="password" placeholder="Leave empty to not change password" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-key"></i>
                    </div>
                  </div>
                    <div class="d-flex justify-content-end">
                      <button type="submit" name="update" class="form-btn border-0">Update</button>
                    </div>
                  </form>
            </div>
            </div>
          </div>
      </section>

<?php

include_once "includes/footer.php";

?>