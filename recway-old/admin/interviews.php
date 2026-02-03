<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("services.php");
}

if(isset($_GET['delete'])) {
    $query = "DELETE FROM interviews WHERE id={$_GET['delete']} AND service_cat_id={$_GET['id']}";
    $stmt = $conn->prepare($query);
    $stmt->execute();

    redirect("interviews.php?id=" . $_GET['id']);
}

if(isset($_POST['update'])) {
    if(isset($_POST['types']) && !empty($_POST['types'])) {
        $types = $_POST['types'];
        $descs = $_POST['descs'];
        $ids = $_POST['ids'];

        foreach ($types as $key => $type) {
            $query = "SELECT * FROM interviews WHERE id={$ids[$key]} AND service_cat_id={$_GET['id']}";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $service_type = $stmt->fetch();

            if(!empty($service_type)) {
                $query = 'UPDATE interviews SET title = ?, `desc` = ? WHERE id = ? AND service_cat_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$type, $descs[$key], $ids[$key], $_GET['id']]);
            } else {
                if(!empty($type)) {
                    $query = 'INSERT INTO interviews (title, `desc`, service_cat_id) VALUES (?,?,?)';
                    $stmt = $conn->prepare($query);
                    $res = $stmt->execute([$type, $descs[$key], $_GET['id']]);
                }
            }
        }

        if(!empty($res)) {

            $message = "<p class='alert alert-success'>Types updated successfully!</p>";
        } else {
            $message = "<p class='alert alert-danger'>Could not update types!</p>";
        }
    }

//    for ($i=0; $i<4; $i++) {
//        $interview = "interview_" . ($i+1);
//        $option = "option_" . ($i+1);
//
//        $query = 'UPDATE interviews SET title = ? WHERE id = ?';
//        $stmt = $conn->prepare($query);
//        $res = $stmt->execute([$_POST[$interview], $_POST[$option]]);
//    }
}

$query = 'SELECT * FROM interviews WHERE service_cat_id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Service Types";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="interviews.php?id=<?php echo $_GET['id'] ?>" method="post">
                                <div class="row p-0 m-0">

                                    <div class="types">
                                        <?php if(!empty($services)): ?>
                                            <?php foreach ($services as $key => $service): ?>
                                                <div class="col-lg-12 ps-0 mt-3 inner-type">
                                                    <div class="d-flex justify-content-between">
                                                        <p class="f-14 mb-0 pb-0 w-500">Type <?php echo $key+1 ?></p>
                                                        <a href="?id=<?php echo $_GET['id'] ?>&delete=<?php echo $service->id  ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </a>
                                                    </div>
                                                    <input value="<?php echo $service->title ?>" class="sign-input w-100 mt-2" name="types[]">
                                                    <textarea rows="3" class="sign-textarea w-100 mt-2" name="descs[]"><?php echo $service->desc ?></textarea>
                                                    <input type="hidden" name="ids[]" value="<?php echo $service->id ?>">
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>

                                    <div style="cursor: pointer; width: fit-content" class="add-row">
                                        + Add Row
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button  type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
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
    $('.add-row').click(function () {
        var types = $('.types');
        var totalTypes = types.find('.inner-type');
        var lastID = parseInt($('.inner-type:last-child').find('input').val())
        var type = '<div class="col-lg-12 ps-0 mt-3 inner-type"><p class="f-14 mb-0 pb-0 w-500">Type '+ (totalTypes.length + 1) +'</p><input class="sign-input w-100 mt-2" name="types[]"><textarea rows="3" class="sign-textarea w-100 mt-2" name="descs[]"></textarea><input type="hidden" name="ids[]" value="'+ (isNaN(lastID) ? '0' : (lastID + 1)) +'"></div>';
        types.append(type)
    })
</script>
