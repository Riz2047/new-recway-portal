<?php



$activeLink = "faqs";



include_once('includes/header.php');



// if(isset($_GET['delete'])) {

//     $query = 'DELETE FROM faqs WHERE id=' . $_GET['delete'];

//     $stmt = $conn->prepare($query);

//     $stmt->execute();

//     redirect("faqs.php");
// }

// FAQ data will be loaded via AJAX (DataTables server-side)
$faqs = [];

?>

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

                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 mb-4 d-flex justify-content-between align-items-center">





                                <a href="#" class="white-text mx-3">FAQs</a>



                                <div>

                                    <!-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2">

                                        <span onclick="location.href='add-faq.php'"><i class="bi bi-plus"></i></span>

                                    </button> -->

                                </div>



                            </div>
                            <table id="dataTable" data-table="faqs" class="display Table" style="width: 100%">
                                <thead>

                                    <tr>

                                        <th class="table-head">Sr#</th>

                                        <th class="table-head">Question</th>

                                        <th class="table-head">Answer</th>

                                        <th class="dt-center table-head">Action</th>



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

    $('#customer').on('change', function() {

        location.href = location.pathname + "?id=" + $(this).val();

    })

</script>