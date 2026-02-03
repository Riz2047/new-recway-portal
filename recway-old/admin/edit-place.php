<?php

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
        $message = "<p class='alert alert-success'>Place updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not update place!</p>";
    }
}

$query = 'SELECT * FROM places WHERE id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$place = $stmt->fetch();

?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Service";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Name</p>
                                        <input type="text" required name="name" value="<?php echo $place->name ?>" class="sign-input w-100 mb-3" placeholder="Your Name ">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Edit</a></button>
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
