<?php

include_once ('includes/header.php');

if(isset($_POST['add'])) {
    $name = $_POST['name'];

    $query = 'INSERT INTO service_categories(name) VALUES(?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$name]);
    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Service added successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not add service!</p>";
    }
}

?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Add Service";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" class="sign-input w-100 mb-3" placeholder="Service Name ">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="add" class="btn-fill w-100 mt-4"><a>Add</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>

<script>
    $(document).ready(function () {
        if (localStorage) {
            var posReader = localStorage["posStorage"];
            if (posReader) {
                $('.layout').scrollTop(posReader);
                localStorage.removeItem("posStorage");
            }
        }

        $('.layout').scroll( function (e) {
            localStorage["posStorage"] = $(this).scrollTop();
        })

        $("#success-alert").fadeTo(2000, 500).slideUp(500, function(){
            $("#success-alert").slideUp(500);
        });
    })

</script>
