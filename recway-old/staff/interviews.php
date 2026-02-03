<?php

$activeLink = "services";

include_once('includes/header.php');

if (!isset($_GET['id'])) {
    redirect("services.php");
}

if (isset($_GET['delete'])) {
    $query = "DELETE FROM interviews WHERE id={$_GET['delete']} AND service_cat_id={$_GET['id']}";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("interviews.php?id=" . $_GET['id']);
}

if (isset($_POST['update'])) {
    if (isset($_POST['types']) && !empty($_POST['types'])) {
        $types = $_POST['types'];
        $descs = $_POST['descs'];
        $ids = $_POST['ids'];

        $query = "SELECT status_id FROM `status_services` WHERE service_id IN (SELECT id FROM interviews WHERE service_cat_id = {$_GET['id']}) GROUP BY status_id;";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $added_statuses = $stmt->fetchAll();

        if (!empty($added_statuses)) {
            $added_statuses = array_column($added_statuses, "status_id");
            $added_statuses = implode(",", $added_statuses);
        }

        foreach ($types as $key => $type) {
            $query = "SELECT * FROM interviews WHERE id={$ids[$key]} AND service_cat_id={$_GET['id']}";
            $stmt = $conn->prepare($query);
            $stmt->execute();
            $service_type = $stmt->fetch();

            if (!empty($service_type)) {
                $query = 'UPDATE interviews SET title = ?, `desc` = ? WHERE id = ? AND service_cat_id = ?';
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$type, $descs[$key], $ids[$key], $_GET['id']]);
            } else {
                if (!empty($type)) {
                    $query = 'INSERT INTO interviews (title, `desc`, service_cat_id) VALUES (?,?,?)';
                    $stmt = $conn->prepare($query);
                    $res = $stmt->execute([$type, $descs[$key], $_GET['id']]);
                    $lastInsertId = $conn->lastInsertId();

                    if (!empty($added_statuses)) {
                        $query = "INSERT INTO status_services (status_id, service_id, msg_col)
                        SELECT status_id, {$lastInsertId} AS service_id, msg_col FROM status_services WHERE status_id IN (" . $added_statuses . ") GROUP BY status_id";
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute();
                        $customers = findAllByQuery('SELECT * from customers');
                        $messages = findByQuery('SELECT * from messages WHERE cus_id = 0 AND interview_id = 0');
                        $admin_msg = $messages->admin_msg;
                        $cus_msg = $messages->cus_msg;
                        foreach ($customers as $customer) {
                            insert('messages', ['cus_id' => $customer->id, 'interview_id' => $lastInsertId]);
                        }
                        $query = 'UPDATE messages SET admin_msg = ?, `cus_msg` = ? WHERE interview_id = ?';
                        $stmt = $conn->prepare($query);
                        $res = $stmt->execute([$admin_msg, $cus_msg, $lastInsertId]);
                    }
                }
            }
        }

        if (!empty($res)) {

            flash("typesUpdated", "Types updated successfully!");
        } else {
            flash("typesUpdated", "Could not update types!", "errorMsg");
        }
    }
}

$query = 'SELECT * FROM interviews WHERE service_cat_id=' . $_GET['id'];
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

?>

<?php flash("typesUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Service Types</h1>
                    </div>

                    <form class="update-form" method="post">
                        <div class="types">
                            <?php if (!empty($services)) : ?>
                                <?php foreach ($services as $key => $service) : ?>
                                    <div class="col-lg-12 ps-0 mt-3 inner-type">
                                        <div class="d-flex justify-content-between">
                                            <p class="f-14 mb-0 pb-0 w-500">Type <?php echo $key + 1 ?></p>
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

                        <div style="cursor: pointer; width: fit-content; font-weight: bold" class="add-row mt-2">
                            + Add Row
                        </div>

                        <div class="d-flex justify-content-end mt-3">
                            <button type="submit" name="update" class="btn-primary bg-primary">Save</button>
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
    $('.add-row').click(function() {
        var types = $('.types');
        var totalTypes = types.find('.inner-type');
        var lastID = parseInt($('.inner-type:last-child').find('input').val())
        var type = '<div class="col-lg-12 ps-0 mt-3 inner-type"><p class="f-14 mb-0 pb-0 w-500">Type ' + (totalTypes.length + 1) + '</p><input class="sign-input w-100 mt-2" name="types[]"><textarea rows="3" class="sign-textarea w-100 mt-2" name="descs[]"></textarea><input type="hidden" name="ids[]" value="' + (isNaN(lastID) ? '0' : (lastID + 1)) + '"></div>';
        types.append(type)
    })
</script>