<?php

include_once ('includes/header.php');

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

if(isset($_POST['update_msgs'])) {
    if(isset($_GET['id'])) {
        $cus_id = $_GET['id'];
    } else {
        $cus_id = $customers[0]->id;
    }

    if(isset($_GET['sid'])) {
        $service_id = $_GET['sid'];
    } else {
        $service_id = $services[0]->id;
    }

    $query = 'UPDATE messages SET ';
    $params = array();
    foreach($_POST as $key => $value) {
        if($key == 'update_msgs' || $key == 'cus_id' || $key == 'sid') {
            continue;
        }
        $query .= $key . ' = ?, ';
        $params[] = $value;
    }
    $query = rtrim($query, ', ') . ' WHERE cus_id = ? AND interview_id = ?';
    $params[] = $cus_id;
    $params[] = $service_id;

    $stmt = $conn->prepare($query);
    $res = $stmt->execute($params);
    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Messages updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not update messages!</p>";
    }
}

if(isset($_POST['single-update'])) {
    $textMsg = $_POST[$_POST['single-update']];
    $col = $_POST['single-update'];

    if(isset($_GET['id'])) {
        $cus_id = $_GET['id'];
    } else {
        $cus_id = $customers[0]->id;
    }

    if(isset($_GET['sid'])) {
        $service_id = $_GET['sid'];
    } else {
        $service_id = $services[0]->id;
    }

    $query = "UPDATE messages SET {$col} = ? WHERE cus_id = ? AND interview_id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$textMsg, $cus_id, $service_id]);
    if(!empty($res)) {

        $message = "<p class='alert alert-success'>Success! Message updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Error! Could not update message!</p>";
    }
}

if(isset($_GET['sid'])) {
    $sid = $_GET['sid'];
} else {
    $sid = $services[0]->id;
}

$msgCols = getMsgColsByService($sid);
$msgCols = array_column($msgCols, "msg_col");
$msgCols = implode(",", $msgCols);

if(isset($_GET['id']) && isset($_GET['sid'])) {
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['id'], $_GET['sid']]);
    $messages = $stmt->fetch();
} elseif (isset($_GET['id']) && !isset($_GET['sid'])) {
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['id'], $services[0]->id]);
    $messages = $stmt->fetch();
} elseif (!isset($_GET['id']) && isset($_GET['sid'])) {
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? AND interview_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$customers[0]->id, $_GET['sid']]);
    $messages = $stmt->fetch();
} else {
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([$customers[0]->id]);
    $messages = $stmt->fetch();
}

?>

<?php echo !empty($message) ? '<div id="success-alert" style="position: fixed; bottom: 0; right: 20px; z-index: 1" class="alert alert-success" role="alert">
    ' . $message . '
</div>' : '' ?>

                <div class="row variables">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Update Messages";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <?php if(!empty($customers)): ?>
                            <select class="form-select mb-2" name="customers" id="customers-messages">
                            <?php foreach ($customers as $customer): ?>
                                <option <?php echo isset($_GET['id']) && $customer->id == $_GET['id'] ? 'selected' : '' ?> value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                            <?php endforeach; ?>
                            </select>
                            <?php endif; ?>

                            <?php if(!empty($services)): ?>
                                <select class="form-select mb-2" name="services" id="service-messages">
                                    <?php foreach ($services as $service): ?>
                                        <option <?php echo isset($_GET['sid']) && $service->id == $_GET['sid'] ? 'selected' : '' ?> value="<?php echo $service->id ?>"><?php echo $service->name ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php endif; ?>
<!--                            <small>Write {customer}, {candidate}, {company}, {interview}, {staff}, {status}, {date}, {orderid}, {interview_date}, {staff_email} as variables.</small>-->
                            <small>Variables moved to the textarea. Click on <i class="bi bi-input-cursor-text"></i> icon to insert variable at the cursor position.
                                <a style="color: var(--dark-blue); cursor: pointer" data-bs-toggle="modal" data-bs-target="#exampleModal"> Watch Demo</a></small>
                            <form action="" method="post">
                                <div class="row p-0 m-0">

                                    <?php $messages = (array) $messages ?>
                                    <?php if(!empty($messages)):
                                        unset($messages['id']);
                                        unset($messages['cus_id']);
                                        unset($messages['interview_id']);
                                        ?>
                                    <?php foreach ($messages as $col => $message): ?>
                                        <div class="col-lg-6 ps-0 mt-3">
                                            <p class="f-14 mb-0 pb-0 w-500"><?php echo $col ?></p>
                                            <div class="position-relative">
                                                <textarea rows="5" class="sign-textarea w-100" name="<?php echo $col ?>"><?php echo $message ?></textarea>
                                                <?php include "comments-dropdown.php" ?>
                                                <button id="msg-save" name="single-update" value="<?php echo $col ?>" style="top: 5px; right: 10px" class="btn position-absolute p-0"><i class="bi bi-cloud-arrow-up"></i></button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php endif; ?>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="update_msgs" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Variable Demo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body d-flex justify-content-center align-items-center">
                <img style="width: 100%" src="../assets/images/demo.gif" alt="">
            </div>
        </div>
    </div>
</div>

<?php

include_once ('includes/footer.php');

?>

<!--<script>-->
<!--    $('#msg-edit').click(function (e) {-->
<!--        e.preventDefault();-->
<!--        $(this).siblings('textarea').prop('disabled', false);-->
<!--    })-->
<!--</script>-->

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

    $('.dropdown li').on('click', function () {
        var textArea = $(this).closest('.dropdown').siblings('textarea')
        var cursorPos = textArea.prop('selectionStart');
        var v = textArea.val();
        var textBefore = v.substring(0,  cursorPos);
        var textAfter  = v.substring(cursorPos, v.length);
        textArea.val(textBefore + $(this).text() + textAfter)
    })

    $('#customers-messages').on('change', function () {
        // location.href = "messages.php?id=" + $(this).val();
        var id = $(this).val();
        var sid = $("#service-messages").val();
        var query = $.param({ id: id, sid: sid});
        // Update the query parameter in the URL
        window.history.pushState({}, "", "?" + query);
        location.reload()
    })

    $('#service-messages').on('change', function () {
        // location.href = "messages.php?id=" + $(this).val();
        var sid = $(this).val();
        var id = $("#customers-messages").val();
        var query = $.param({ id: id, sid: sid});
        // Update the query parameter in the URL
        window.history.pushState({}, "", "?" + query);
        location.reload()
    })

</script>
