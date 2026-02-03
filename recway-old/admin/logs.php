<?php

include_once ('includes/header.php');

$query = 'SELECT * FROM staff_logs ORDER BY created_at DESC';
$stmt = $conn->prepare($query);
$stmt->execute();
$logs = $stmt->fetchAll();

?>

                <div class="row">

                    <div class="col-lg-12">
                        <?php
                        $pageTitle = "Logs";
                        $pageLink = "";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <div class="row border-bottom">
                                <div class="col-lg-12 ">
                                    <div class="timeline-container px-2 py-3">
                                        <div class="timeline-wrapper">
                                            <ul class="sessions">
                                                <?php if(!empty($logs)): ?>
                                                    <?php foreach ($logs as $log): ?>

                                                        <?php
                                                        $query = 'SELECT * FROM staff WHERE id = ?';
                                                        $stmt = $conn->prepare($query);
                                                        $stmt->execute([$log->staff_id]);
                                                        $staff = $stmt->fetch();
                                                        ?>
                                                        <li>
                                                            <div class="time"><?php echo date("M d, Y h:i A", strtotime($log->created_at)) ?></div>
                                                            <p class="m-0 p-0"><a href="update-staff.php?id=<?php echo $log->staff_id ?>"><?php echo $staff->name ?></a> <?php echo $log->log_msg ?></p>
<!--                                                            <i><small class="m-0 p-0">--><?php //echo !empty($h->comment) ? 'Comment: ' . $h->comment : '' ?><!--</small></i>-->
                                                        </li>

                                                    <?php endforeach; ?>
                                                <?php else: ?>

                                                    <li>
                                                        <div class="time"><?php echo "No logs found" ?></div>
                                                    </li>

                                                <?php endif; ?>
                                            </ul>
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

</script>
