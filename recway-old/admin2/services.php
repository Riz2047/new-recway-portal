<?php



$activeLink = "services";



include_once('includes/header.php');



if (isset($_GET['delete'])) {

    $query = 'DELETE FROM service_categories WHERE id=' . $_GET['delete'];

    $stmt = $conn->prepare($query);

    $stmt->execute();

    redirect("services.php");

}



// Services data will be loaded via AJAX (DataTables server-side)
$services = [];



?>

<style>

    .table-container {

        overflow-y: auto;

    }

</style>

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

                                <a href="#" class="white-text mx-3">Services</a>

                                <div>

                                    <button type="button" onclick="show_add_card(this)" class="btn btn-outline-white btn-rounded btn-sm px-2">

                                        <span><i class="bi bi-file-plus"></i></span>

                                    </button>

                                </div>

                            </div>

                            <div class="col-md-12" id="show_add_card" style="display: none !important;">

                                <div class="card" style="width: 98% !important;margin-left: 11px !important">

                                    <div class="card-header">

                                        <div class="card-title">

                                            <h5>Add Service</h5>

                                        </div>

                                    </div>

                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-lg-6 mb-3">

                                                <label class="form-label" for="name">Name</label>

                                                <input type="text" class="form-control" id="name">

                                            </div>
                                                                                        <div class="col-lg-6 mb-3">
                                                <label class="form-label" for="name_sv">Translated Name (Swedish)</label>
                                                <input type="text" class="form-control" id="name_sv">
                                            </div>
                                            <div class="d-flex justify-content-end">

                                                <button type="button" onclick="add_service()" class="btn-primary bg-primary">Add</button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <div class="col-md-12" id="update_service_card" name="update_section" style="display: none !important;">

                                <div class="card  mb-4" style="width: 98% !important;margin-left: 11px !important">

                                    <div class="card-header">

                                        <div class="card-title">

                                            <h5>Update Service</h5>

                                        </div>

                                    </div>

                                    <div class="card-body">

                                        <div class="row">

                                            <div class="col-lg-6 mb-3">

                                                <label class="form-label" for="name">Name</label>

                                                <input type="text" class="form-control" id="main_u_name">

                                                <input type="hidden" id="main_u_id" value="<?php echo $service->id ?>">

                                            </div>
                                                                                        <div class="col-lg-6 mb-3">
                                                <label class="form-label" for="name_sv">Translated Name (Swedish)</label>
                                                <input type="text" class="form-control" id="main_u_name_sv">
                                            </div>
                                            <div class="d-flex justify-content-end">

                                                <button type="button" onclick="update_s(this)" class="btn-warning bg-warning mr-2" style="border-radius: 9px !important;">Close</button>

                                                <button type="button" onclick="update_service()" class="btn-primary bg-primary">Update</button>

                                            </div>

                                        </div>

                                    </div>

                                </div>

                            </div>

                            <table id="dataTable" data-table="services" class="display Table mt-3" style="width: 100%">

                                <thead>

                                    <tr>

                                        <th class="dt-center table-head">Action</th>

                                        <th class="table-head">Sr#</th>

                                        <th class="table-head">Service</th>
                                        <th class="table-head">Service (Swedish)</th>
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