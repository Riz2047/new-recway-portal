<?php



$activeLink = "candidates";

include_once('includes/header.php');



$statuses = getStatuses();

// Switch to server-side DataTables (AJAX). Do not pre-fetch candidates here.
if (isset($_POST['delete'])) {

    foreach ($_POST['delete'] as $delete) {

        $query = 'SELECT * FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);

        $candidate = $stmt->fetch();



        $query = 'DELETE FROM candidates WHERE id = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$delete]);



        $query = 'DELETE FROM emails WHERE email = ?';

        $stmt = $conn->prepare($query);

        $stmt->execute([$candidate->email]);
    }
}

// Let DataTables load rows via includes/pages.php (get_staff_candidate_data)
$candidates = [];

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