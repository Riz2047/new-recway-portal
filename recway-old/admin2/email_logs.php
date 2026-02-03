<?php



$activeLink = "email_logs";



include_once('includes/header.php');

// Email logs data will be loaded via AJAX (DataTables server-side)
$email_logs = [];
?>

<?php flash("candidateRecovered"); ?>

<div class="mx-lg-4 main-content">

    <div class="container">

        <?php include_once "buttons-row.php" ?>



        <!-- table row -->

        <div class="row">

            <div class="col-lg-12">

                <div class="table-div">

                    <form action="" method="post" id="d-form">

                        <div class="card card-cascade narrower mb-4">

                            <!--Card image-->

                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">

                                <a href="#" class="white-text mx-3">Email Logs</a>

                            </div>

                            <table id="dataTable" data-table="email_logs" class="display Table" style="width: 100%">

                                <thead>

                                    <tr>

                                        <th class="table-head">Order ID</th>

                                        <th class="table-head">Email Type</th>

                                        <th class="table-head">Email To</th>

                                        <th class="table-head">Status</th>

                                        <th class="table-head">Created At</th>

                                        <th class="table-head"></th>

                                    </tr>

                                </thead>

                                <tbody>
                                    <!-- Rows will be loaded via AJAX (server-side DataTables) -->
                                </tbody>

                            </table>

                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script>

    var statuses = <?php echo json_encode($statuses) ?>

</script>

<?php



include_once('includes/footer.php');



?>

<script>

    function delete_email(obj) {

        var id = $(obj).closest('td').find('.email_id').val()

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                'delete_email': 1,

                'email_id': id,

            },

            success: function(response) {

                $(obj).closest('tr').remove()

                alert('Email Deleted Successfully')

            }

        });

    }

</script>