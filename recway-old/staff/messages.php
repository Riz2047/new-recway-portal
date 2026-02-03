<?php

$activeLink = "messages";

include_once('includes/header.php');

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$query = 'SELECT * FROM interviews';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

if (isset($_POST['update_msgs'])) {
    if (isset($_GET['id'])) {
        $cus_id = $_GET['id'];
    } else {
        $cus_id = $customers[0]->id;
    }

    if (isset($_GET['sid'])) {
        $service_id = $_GET['sid'];
    } else {
        $service_id = $services[0]->id;
    }
    $cus_messages = findAllByQuery("SELECT * FROM messages WHERE cus_id = $cus_id AND interview_id = $service_id");
    $insert_msg_array = null;
    if (empty($cus_messages)) {
        $default_cus_messages = findByQuery("SELECT * FROM messages WHERE cus_id = 0 AND interview_id = 0");
        foreach ($default_cus_messages as $key => $default_cus_message) {
            if ($key != 'id') {
                if ($key == 'cus_id') {
                    $insert_msg_array[$key] = $cus_id;
                } else if ($key == 'interview_id') {
                    $insert_msg_array[$key] = $service_id;
                } else {
                    $insert_msg_array[$key] = $default_cus_message;
                }
            }
        }
        insert('messages', $insert_msg_array);
    }

    $query = 'UPDATE messages SET ';
    $params = array();
    foreach ($_POST as $key => $value) {
        if ($key == 'update_msgs' || $key == 'cus_id' || $key == 'sid' || $key == 'customers' || $key == 'services') {
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

    $insert = [];
    $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = $cus_id");
    $parent_messages = findAllByQuery("SELECT messages.* FROM messages LEFT JOIN customers ON messages.cus_id = customers.id WHERE cus_id = $cus_id");
    if (!empty($child_customers)) {
        foreach ($child_customers as $child_customer) {
            if (!empty($parent_messages)) {
                delete('messages', 'cus_id', $child_customer->id);
                foreach ($parent_messages as $parent_message) {
                    $parent_message->cus_id = $child_customer->id;
                    foreach ($parent_message as $k => $parent_messag) {
                        if ($k != 'id') {
                            $insert[$k] = $parent_messag;
                        }
                    }
                    insert('messages', $insert);
                }
            }
        }
    }
    if (!empty($res)) {
        $message = "<p class='alert alert-success'>Messages updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not update messages!</p>";
    }
}

if (isset($_POST['single-update'])) {
    $textMsg = $_POST[$_POST['single-update']];
    $col = $_POST['single-update'];

    if (isset($_GET['id'])) {
        $cus_id = $_GET['id'];
    } else {
        $cus_id = $customers[0]->id;
    }

    if (isset($_GET['sid'])) {
        $service_id = $_GET['sid'];
    } else {
        $service_id = $services[0]->id;
    }
    $cus_messages = findAllByQuery("SELECT * FROM messages WHERE cus_id = $cus_id AND interview_id = $service_id");
    $insert_msg_array = null;
    if (empty($cus_messages)) {
        $default_cus_messages = findByQuery("SELECT * FROM messages WHERE cus_id = 0 AND interview_id = 0");
        foreach ($default_cus_messages as $key => $default_cus_message) {
            if ($key != 'id') {
                if ($key == 'cus_id') {
                    $insert_msg_array[$key] = $cus_id;
                } else if ($key == 'interview_id') {
                    $insert_msg_array[$key] = $service_id;
                } else {
                    $insert_msg_array[$key] = $default_cus_message;
                }
            }
        }
        insert('messages', $insert_msg_array);
    }

    $query = "UPDATE messages SET {$col} = ? WHERE cus_id = ? AND interview_id = ?";
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$textMsg, $cus_id, $service_id]);

    $insert = [];
    $child_customers = findAllByQuery("SELECT * FROM customers WHERE parent_id = $cus_id");
    if (!empty($child_customers)) {
        foreach ($child_customers as $child_customer) {
            $query = "UPDATE messages SET {$col} = ? WHERE cus_id = ? AND interview_id = ?";
            $stmt = $conn->prepare($query);
            $res = $stmt->execute([$textMsg, $child_customer->id, $service_id]);
        }
    }


    if (!empty($res)) {

        $message = "<p class='alert alert-success'>Success! Message updated successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Error! Could not update message!</p>";
    }
}

if (isset($_GET['sid'])) {
    $sid = $_GET['sid'];
} else {
    $sid = $services[0]->id;
}

$msgCols = getMsgColsByService($sid);
if ($msgCols != false) {
    $msgCols = array_column($msgCols, "msg_col");
    $msgCols = implode(",", $msgCols);
    if (isset($_GET['id']) && isset($_GET['sid'])) {
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
} else {
    $messages = null;
}
if (empty($messages)) {
    $query = 'SELECT cus_msg,' . $msgCols . ' FROM messages WHERE cus_id = ? LIMIT 1';
    $stmt = $conn->prepare($query);
    $stmt->execute([0]);
    $messages = $stmt->fetch();
}

?>
<?php flash("staffAssigned"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Update Messages</h1>
                    </div>

                    <form class="update-form" method="post">
                        <div class="row" id="">

                            <?php if (!empty($customers)) : ?>
                                <div class="col-lg-6 mb-3">
                                    <select class="form-control" name="customers" id="customers-messages">
                                        <?php foreach ($customers as $customer) : ?>
                                            <option <?php echo isset($_GET['id']) && $customer->id == $_GET['id'] ? 'selected' : '' ?> value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($services)) : ?>
                                <div class="col-lg-6 mb-3">
                                    <select class="form-control" name="services" id="service-messages">
                                        <?php foreach ($services as $service) : ?>
                                            <option <?php echo isset($_GET['sid']) && $service->id == $_GET['sid'] ? 'selected' : '' ?> value="<?php echo $service->id ?>"><?php echo $service->title ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php endif; ?>

                            <form action="" method="post">
                                <!--                                                    <div class="row">-->

                                <?php $messages = (array) $messages ?>
                                <?php if (!empty($messages)) :
                                    unset($messages['id']);
                                    unset($messages['cus_id']);
                                    unset($messages['interview_id']);
                                ?>
                                    <?php foreach ($messages as $col => $message) : ?>
                                        <div class="col-lg-6 mb-3">
                                            <label class="form-label"><?php echo $col ?></label>
                                            <div class="position-relative">
                                                <textarea rows="5" class="sign-textarea w-100" name="<?php echo $col ?>"><?php echo $message ?></textarea>
                                                <?php include "comments-dropdown.php" ?>
                                                <?php if (isset($allowed_staff_permission['update_message']) && !empty($allowed_staff_permission['update_message'])) { ?>
                                                    <button id="msg-save" name="single-update" value="<?php echo $col ?>" style="top: 5px; right: 10px" class="btn position-absolute p-0 m-0 mt-1 me-2"><i class="bi bi-cloud-arrow-up"></i></button>
                                                <?php } ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <?php if (isset($allowed_staff_permission['update_message']) && !empty($allowed_staff_permission['update_message'])) { ?>
                                    <div class="d-flex justify-content-end">
                                        <button type="submit" name="update_msgs" class="btn-primary bg-primary">Update</button>
                                    </div>
                                <?php } ?>
                                <!--                                                    </div>-->
                            </form>
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

<script>
    $(document).ready(function() {
        if (localStorage) {
            var posReader = localStorage["posStorage"];
            if (posReader) {
                $('.layout').scrollTop(posReader);
                localStorage.removeItem("posStorage");
            }
        }

        $('.layout').scroll(function(e) {
            localStorage["posStorage"] = $(this).scrollTop();
        })

        $("#success-alert").fadeTo(2000, 500).slideUp(500, function() {
            $("#success-alert").slideUp(500);
        });
    })

    $('.dropdown li').on('click', function() {
        var textArea = $(this).closest('.dropdown').siblings('textarea')
        var cursorPos = textArea.prop('selectionStart');
        var v = textArea.val();
        var textBefore = v.substring(0, cursorPos);
        var textAfter = v.substring(cursorPos, v.length);
        textArea.val(textBefore + $(this).text() + textAfter)
    })

    $('#customers-messages').on('change', function() {
        // location.href = "messages.php?id=" + $(this).val();
        var id = $(this).val();
        var sid = $("#service-messages").val();
        var query = $.param({
            id: id,
            sid: sid
        });
        // Update the query parameter in the URL
        window.history.pushState({}, "", "?" + query);
        location.reload()
    })

    $('#service-messages').on('change', function() {
        // location.href = "messages.php?id=" + $(this).val();
        var sid = $(this).val();
        var id = $("#customers-messages").val();
        var query = $.param({
            id: id,
            sid: sid
        });
        // Update the query parameter in the URL
        window.history.pushState({}, "", "?" + query);
        location.reload()
    })
</script>