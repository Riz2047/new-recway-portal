<?php

include_once "customer/includes/header.php";
$documentation = findallByQuery("SELECT * FROM documentation ")[0]->meta_data;
?>

<section>
    <div class="container mt-3">
        <div class="row">
            <div class="col-lg-12 p-4">
                <?php echo $documentation ?>
            </div>
        </div>
    </div>
</section>

<?php

include_once "customer/includes/footer.php";

?>