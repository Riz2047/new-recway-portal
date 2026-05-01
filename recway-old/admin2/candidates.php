<?php
$activeLink = "candidates";
include_once('includes/header.php');
$statuses = getStatuses();
if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'SELECT * FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $candidate = $stmt->fetch();
        $query = 'DELETE FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $query = 'DELETE FROM emails WHERE order_id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$candidate->order_id]);
    }
}
if (isset($_GET['service']) && $_GET['service'] != 'all') {
    $query = 'SELECT * FROM interviews WHERE service_cat_id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['service']]);
    $services = $stmt->fetchAll();
    $services = array_column($services, 'id');
    $services = implode(",", $services);
}
if (isset($_GET['status'])) {
    $query = "SELECT * FROM candidates WHERE status = {$_GET['status']} AND expired = 0";
    if (isset($_GET['service']) && $_GET['service'] != 'all') {
        $query .= " AND interview_id IN (" . $services . ")";
    }
    $query .= "  ORDER BY CASE
    WHEN booked IS NULL OR booked = NULL THEN 1  -- Places empty interview dates at the end
    ELSE 0
END, booked ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates WHERE expired = 0';
    if (isset($_GET['service']) && $_GET['service'] != 'all') {
        $query .= " AND interview_id IN (" . $services . ")";
    }
    $query .= "  ORDER BY CASE
    WHEN booked IS NULL OR booked = NULL THEN 1  -- Places empty interview dates at the end
    ELSE 0
END, booked ASC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
}
?>
<?php flash("allCustomersChanged") ?>
<?php flash("allStaffChanged") ?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>
        <!-- table row -->
        <div class="row">
            <div class="col-lg-12">
                <?php include_once "candidates-table.php" ?>
            </div>
        </div>
    </div>
</div>
<script>
    const interviewID = <?php echo INTERVIEW_ID; ?>;
</script>
<?php
include_once('includes/footer.php');
?>