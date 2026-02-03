<?php

$activeLink = "places";

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("places.php");
}

if(isset($_POST['update'])) {
    $name = $_POST['name'];

    $query = 'UPDATE places SET name = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name, $_GET['id']]);
    if(!empty($res)) {
        flash("placeUpdated", "Place updated successfully!");
    } else {
        flash("placeUpdated", "Could not update place!", "errorMsg");
    }
}

$query = 'SELECT * FROM places WHERE id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$place = $stmt->fetch();

?>

            <?php flash("placeUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Update Place</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label" for="name">Name</label>
                                                    <input type="text" required value="<?php echo $place->name ?>" name="name" class="form-control" id="name">
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