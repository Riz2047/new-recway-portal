<?php

$activeLink = "statuses";

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("statuses.php");
}

if(isset($_POST['update'])) {
    $status = $_POST['status'];
    $status_detail = $_POST['status_detail'];
        $status_detail = $_POST['status_detail'];
    $icon = $_POST['icon'];
    $variable = $_POST['variable'];
    $color = $_POST['color'];

    $query = 'UPDATE statuses SET variable = ?, status = ?, status_detail = ?, status_sv = ?, status_icon = ?, color = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$variable, $status, $status_detail, $status_sv, $icon, $color, $_GET['id']]);

    if($res) {
        flash("statusUpdated", "Status updated successfully!");
    } else {
        flash("statusUpdated", "Could not update status!", "errorMsg");
    }
}

$query = 'SELECT * FROM statuses s INNER JOIN status_services ss ON s.id = ss.status_id WHERE s.id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$statuses = $stmt->fetchAll();

?>

            <?php flash("statusUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <?php $status = $statuses[0] ?>
                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Update Status</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-6 mb-3">
                                                    <label class="form-label" for="status">Status</label>
                                                    <input type="text" required name="status" value="<?php echo $status->status ?>" class="form-control" id="status">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="status_detail">Status Detail</label>
                                                    <input type="text" required name="status_detail" value="<?php echo $status->status_detail ?>" class="form-control" id="status_detail">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="status_sv">Translated Status (swedish)</label>
                                                    <input type="text" required name="status_sv" value="<?php echo $status->status_sv ?? '' ?>" class="form-control cols" id="status_sv">
                                                </div>
                                                <div class="col-md-6 mb-3">
                                                    <label class="form-label" for="variable">Variable</label>
                                                    <input type="text" required name="variable" value="<?php echo $status->variable ?>" class="form-control cols" id="variable">
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label" for="color">Color</label>
                                                    <br>
                                                    <input type="color" required name="color" value="<?php echo $status->color ?>" class="" id="color">
                                                </div>
                                                <div class="col-md-12 mb-3">
                                                    <label class="form-label" for="status_detail">Icon</label>
                                                    <input type="text" required autocomplete="off" name="icon" value="<?php echo $status->status_icon ?>" class="form-control iconpicker" id="icon" aria-label="Icon Picker" aria-describedby="basic-addon1">
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
        $(this).closest('label').after('<div class="col-lg-12 ps-0"> <p class="f-14 mb-0 pb-0 w-500">Message</p> <input type="hidden" name="status_message_ids[]" value="'+id+'"> <textarea required name="status_messages[]" class="sign-textarea w-100 mb-3" placeholder="Enter message "></textarea> </div><div class="col-lg-12 ps-0"><p class="f-14 mb-0 pb-0 w-500">Message Column</p><input type="text" required name="status_msg_cols[]" class="sign-input w-100 mb-3 cols msgCols" placeholder="Enter message column "></div>')
        $(this).remove()
    })

    $(document).on('input', '.cols', function () {
        var inputValue = $(this).val();
        var regex = /^[a-zA-Z][a-zA-Z0-9_]*$/;
        if (!regex.test(inputValue)) {
            $(this).val(inputValue.substring(0, inputValue.length - 1));
        }
    })


    $(document).on('keyup', 'input[name="variable"]', function () {

        var that = $(this)
        that.parents().eq(0).find("small").remove()
        $.ajax({
            url: "../includes/ajax.php",
            method: "post",
            data: {statusVariable: true, variable: $(this).val()},
            success: function (response) {
                if(response == "1") {
                    that.parents().eq(0).find("small").remove()
                    that.after("<small class='text-danger var-error'>Variable already exists</small>");
                    $("button[name='add']").prop('disabled', true)
                } else {
                    that.parents().eq(0).find("small").remove()
                    if($("form").find(".var-error").length === 0) {
                        $("button[name='add']").prop('disabled', false)
                    }
                }
            }
        })
    })

    $(document).on('keyup', '.msgCols', function () {

        var that = $(this)
        that.parents().eq(0).find("small").remove()
        $.ajax({
            url: "../includes/ajax.php",
            method: "post",
            data: {msgColVariable: true, variable: $(this).val()},
            success: function (response) {
                if(response == "1") {
                    that.parents().eq(0).find("small").remove()
                    that.after("<small class='text-danger var-error'>Message column already exists</small>");
                    $("button[name='add']").prop('disabled', true)
                } else {
                    that.parents().eq(0).find("small").remove()
                    if($("form").find(".var-error").length === 0) {
                        $("button[name='add']").prop('disabled', false)
                    }
                }
            }
        })
    })
</script>

