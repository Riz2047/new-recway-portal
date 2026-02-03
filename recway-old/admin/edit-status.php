<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("statuses.php");
}

if(isset($_POST['update'])) {
    $status = $_POST['status'];
    $status_detail = $_POST['status_detail'];
    $icon = $_POST['icon'];
    $variable = $_POST['variable'];
    $color = $_POST['color'];

    $query = 'UPDATE statuses SET variable = ?, status = ?, status_detail = ?, status_icon = ?, color = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$variable, $status, $status_detail, $icon, $color, $_GET['id']]);

    if($res) {
        $message = "<p class='alert alert-success'>Status updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not update status!</p>";
    }
}

$query = 'SELECT * FROM statuses s INNER JOIN status_services ss ON s.id = ss.status_id WHERE s.id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$statuses = $stmt->fetchAll();

?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Edit Status";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <?php $status = $statuses[0]; ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Status</p>
                                        <input type="text" required name="status" value="<?php echo $status->status ?>" class="sign-input w-100 mb-3" placeholder="Enter status ">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Status Detail</p>
                                        <textarea type="text" required name="status_detail" class="sign-textarea w-100 mb-3" placeholder="Enter status detail e.g. (Interview has been booked)"><?php echo $status->status_detail ?></textarea>
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Variable</p>
                                        <input type="text" required name="variable" value="<?php echo $status->variable ?>" class="sign-input w-100 mb-3 cols" placeholder="Enter status variable">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Color</p>
                                        <input type="color" required name="color" value="<?php echo $status->color ?>" class="mb-3" placeholder="Enter status color">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Icon</p>
                                        <input name="icon" value="<?php echo $status->status_icon ?>" type="text" autocomplete="off" class="form-control iconpicker mb-3" placeholder="Icon Picker" aria-label="Icone Picker" aria-describedby="basic-addon1" />
                                    </div>

<!--                                    --><?php //if(!empty($services)): ?>
<!--                                        <div class="col-lg-12 d-flex flex-column ps-0">-->
<!--                                            <p class="f-14 mb-0 pb-0 w-500">Services</p>-->
<!--                                    --><?php //foreach ($services as $service): ?>
<!--                                        <label for="--><?php //echo $service->title ?><!--">-->
<!--                                            <input checked type="checkbox" id="--><?php //echo $service->title ?><!--" value="--><?php //echo $service->id ?><!--" name="services[]" class="mb-3">-->
<!--                                            --><?php //echo $service->title ?>
<!--                                            <a href="#" data-id="--><?php //echo $service->id ?><!--" class="ms-2 service-message"><i class="bi bi-chat"></i></a>-->
<!--                                        </label>-->
<!--                                    --><?php //endforeach; ?>
<!--                                        </div>-->
<!--                                    --><?php //endif; ?>
<!--                                    <div class="col-lg-12 ps-0">-->
<!--                                        <p class="f-14 mb-0 pb-0 w-500">Message <small>(for all services)</small></p>-->
<!--                                        <textarea required name="message" class="sign-textarea w-100 mb-3" placeholder="Enter message "></textarea>-->
<!--                                    </div>-->
<!--                                    <div class="col-lg-12 ps-0">-->
<!--                                        <p class="f-14 mb-0 pb-0 w-500">Message Column</p>-->
<!--                                        <input type="text" required name="msg_col" class="sign-input w-100 mb-3 cols" placeholder="Enter message column ">-->
<!--                                    </div>-->
                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update" class="btn-fill w-100 mt-4"><a>Update</a></button>
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

    $('.service-message').click(function (e) {
        e.preventDefault()

        var id = $(this).attr("data-id");
        $(this).closest('label').after('<div class="col-lg-12 ps-0"> <p class="f-14 mb-0 pb-0 w-500">Message</p> <input type="hidden" name="status_message_ids[]" value="'+id+'"> <textarea required name="status_messages[]" class="sign-textarea w-100 mb-3" placeholder="Enter message "></textarea> </div><div class="col-lg-12 ps-0"><p class="f-14 mb-0 pb-0 w-500">Message Column</p><input type="text" required name="status_msg_cols[]" class="sign-input w-100 mb-3 cols" placeholder="Enter message column "></div>')
        $(this).remove()
    })
    
    $(document).on('input', '.cols', function () {
        var inputValue = $(this).val();
        var regex = /^[a-zA-Z][a-zA-Z0-9_]*$/;
        if (!regex.test(inputValue)) {
            $(this).val(inputValue.substring(0, inputValue.length - 1));
        }
    })

</script>
