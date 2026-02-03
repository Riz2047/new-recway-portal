<?php

$activeLink = "services";

include_once ('includes/header.php');

if(isset($_POST['add'])) {
    $name = $_POST['name'];

    $query = 'INSERT INTO service_categories(name) VALUES(?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name]);
    if(!empty($res)) {
        flash("serviceAdded", "Service added successfully!");
    } else {
        flash("serviceAdded", "Could not add service!", "errorMsg");
    }
}

?>

            <?php flash("serviceAdded"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Add Service</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" required name="name" class="form-control" id="name">
                                                </div>
                                            </div>
                                           
                                           <div class="d-flex justify-content-end">
                                            <button type="submit" name="add" class="btn-primary bg-primary">Save</button>
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