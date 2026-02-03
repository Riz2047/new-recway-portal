<?php

$activeLink = "services";

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("services.php");
}

if(isset($_POST['update'])) {
    $name = $_POST['name'];

    $query = 'UPDATE service_categories SET name = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $_GET['id']]);
    if(!empty($res)) {
        flash("serviceUpdated", "Service updated successfully!");
    } else {
        flash("serviceUpdated", "Could not update service!", "errorMsg");
    }
}

$query = 'SELECT * FROM service_categories WHERE id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$service = $stmt->fetch();

?>

            <?php flash("serviceUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Update Service</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" required name="name" value="<?php echo $service->name ?>" class="form-control" id="name">
                                                </div>
                                            </div>
                                           
                                           <div class="d-flex justify-content-end">
                                            <button type="submit" name="update" class="btn-primary bg-primary">Update</button>
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

include_once ('includes/footer.php');

?>