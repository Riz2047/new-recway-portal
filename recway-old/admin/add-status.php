<?php

include_once ('includes/header.php');

$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

if(isset($_POST['add'])) {
    $status = $_POST['status'];
    $status_detail = $_POST['status_detail'];
    $icon = $_POST['icon'];
    $variable = $_POST['variable'];
    $color = $_POST['color'];
//    $email_to = isset($_POST['email_to']) && !empty($_POST['email_to']) ? implode(",", $_POST['email_to']) : 0;
    $services_selected = $_POST['services'] ?? null;
    $status_message_ids = $_POST['status_message_ids'] ?? [];
    $status_messages = $_POST['status_messages'] ?? null;
    $status_message_cols = $_POST['status_msg_cols'] ?? null;
    $message = $_POST['message'];
    $msg_col = $_POST['msg_col'];


    $query = 'INSERT INTO statuses(variable, status, color, status_detail, status_icon) VALUES(?,?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$variable, $status, $color, $status_detail, $icon]);
    if(!empty($res)) {
        $status_id = $conn->lastInsertId();

        $query = 'INSERT INTO allowed_emails (cus_id, status_id, allowed) SELECT id AS cus_id, ? AS status_id, 1 AS allowed FROM customers';
        $stmt = $conn->prepare($query);
        $res = $stmt->execute([$status_id]);

        if(!empty($status_messages)) {
            foreach ($status_messages as $key => $message) {
                $query = "ALTER TABLE messages ADD COLUMN {$status_message_cols[$key]} TEXT";
                $stmt = $conn->prepare($query);
                $stmt->execute();

                $query = "UPDATE messages SET {$status_message_cols[$key]} = ?";
                $stmt = $conn->prepare($query);
                $stmt->execute([$message]);

                $query = 'INSERT INTO status_services(service_id, status_id, msg_col) VALUES(?,?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$status_message_ids[$key], $status_id, $status_message_cols[$key]]);
            }
        }

        $services_included = array_filter($services, function ($obj) use ($status_message_ids, $services_selected) {
            return !in_array($obj->id, $status_message_ids) && in_array($obj->id, $services_selected);
        });

        $query = "ALTER TABLE messages ADD COLUMN {$msg_col} TEXT";
        $stmt = $conn->prepare($query);
        $stmt->execute();

        $query = "UPDATE messages SET {$msg_col} = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$message]);

        if(!empty($services_included)) {
            foreach ($services_included as $service) {
                $query = 'INSERT INTO status_services(service_id, status_id, msg_col) VALUES(?,?,?)';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$service->id, $status_id, $msg_col]);
            }
        }

        $message = "<p class='alert alert-success'>Status added successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not add status!</p>";
    }
}

?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Add Status";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Status</p>
                                        <input type="text" required name="status" class="sign-input w-100 mb-3" placeholder="Enter status ">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Status Detail</p>
                                        <textarea type="text" required name="status_detail" class="sign-textarea w-100 mb-3" placeholder="Enter status detail e.g. (Interview has been booked)"></textarea>
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Variable</p>
                                        <input type="text" required name="variable" class="sign-input w-100 mb-3 cols" placeholder="Enter status variable">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Color</p>
                                        <input type="color" required name="color" class="mb-3" placeholder="Enter status color">
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Icon</p>
                                        <input name="icon" type="text" autocomplete="off" class="form-control iconpicker mb-3" placeholder="Icon Picker" aria-label="Icone Picker" aria-describedby="basic-addon1" />
                                    </div>
<!--                                    <div class="col-lg-12 ps-0">-->
<!--                                        <p class="f-14 mb-0 pb-0 w-500">Email To</p>-->
<!--                                        <label for="admin">-->
<!--                                            <input type="checkbox" id="admin" value="1" name="email_to[]" class="mb-3">-->
<!--                                            Admin-->
<!--                                        </label>-->
<!--                                        <label for="customer">-->
<!--                                            <input type="checkbox" id="customer" value="2" name="email_to[]" class="mb-3">-->
<!--                                            Customer-->
<!--                                        </label>-->
<!--                                        <label for="candidate">-->
<!--                                            <input type="checkbox" id="candidate" value="3" name="email_to[]" class="mb-3">-->
<!--                                            Candidate-->
<!--                                        </label>-->
<!--                                    </div>-->
                                    <?php if(!empty($services)): ?>
                                        <div class="col-lg-12 d-flex flex-column ps-0">
                                            <p class="f-14 mb-0 pb-0 w-500">Services</p>
                                    <?php foreach ($services as $service): ?>
                                        <label for="<?php echo $service->title ?>">
                                            <input checked type="checkbox" id="<?php echo $service->title ?>" value="<?php echo $service->id ?>" name="services[]" class="mb-3">
                                            <?php echo $service->title ?>
                                            <a href="#" data-id="<?php echo $service->id ?>" class="ms-2 service-message"><i class="bi bi-chat"></i></a>
                                        </label>
                                    <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Message <small>(for all services)</small></p>
                                        <textarea required name="message" class="sign-textarea w-100 mb-3" placeholder="Enter message "></textarea>
                                    </div>
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Message Column</p>
                                        <input type="text" required name="msg_col" class="sign-input w-100 mb-3 cols msgCols" placeholder="Enter message column ">
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
