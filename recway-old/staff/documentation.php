<?php

$activeLink = "documentation";

include_once('includes/header.php');
if (!isset($allowed_staff_permission['update_candidate'])) {
    redirect('index.php');
}
if (isset($_POST['update_settings'])) {
    $cus_doc = $_POST['cus_documentation'];
    $query = "DELETE FROM documentation";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $settings = $stmt->fetchAll();
    insert('documentation', ['meta_data' => $cus_doc]);
}
$old_cus_doc = findAllByQuery('SELECT * FROM documentation')[0]->meta_data;
?>

<?php flash("settingsUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Documentation</h1>
                    </div>

                    <form class="update-form" method="post">
                        <div class="row">
                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Customer Panel</label>
                                <textarea name="cus_documentation" class="summernote"><?php if (!empty($old_cus_doc)) {
                                                                                            echo $old_cus_doc;
                                                                                        } ?></textarea>
                            </div>
                            <?php if (isset($allowed_staff_permission['update_documentation']) && !empty($allowed_staff_permission['update_documentation'])) { ?>
                                <div class="d-flex justify-content-end">
                                    <button type="submit" name="update_settings" class="btn-primary bg-primary">Update</button>
                                </div>
                            <?php } ?>
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