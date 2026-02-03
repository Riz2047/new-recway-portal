<?php

include_once ('includes/header.php');

if(isset($_GET['delete'])) {
    $query = 'DELETE FROM service_categories WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("services.php");
}

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$services = $stmt->fetchAll();

?>


    <div class="row">
        <div class="col-lg-12 history-data">
            <div class="d-flex justify-content-between buttons-row">
                <?php
                $pageTitle = "Services";
                $pageLink = "add-service.php";
                $pageIcon = "file-plus";
                include_once "buttons-row.php";
                ?>
            </div>



            <div class="box shadow">
                <?php echo isset($message) ? $message : '' ?>

                <div class="data-table  ">
                    <div class="customer-table history-table">
                            <table id="dataTable" class="table display nowrap" style="width:100%">
                                <thead>
                                <tr>
                                    <th>Sr.#</th>
                                    <th>Service</th>
                                    <th class="dt-center">Action</th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php if(!empty($services)): ?>
                                    <?php foreach ($services as $key => $service): ?>
                                        <tr>
                                            <td><?php echo $key+1 ?></td>
                                            <td><a href="interviews.php?id=<?php echo $service->id ?>"><?php echo $service->name ?></a></td>
                                            <td class="text-center dt-center">
                                                <div class="dropdown profile-dropdown " >
                                                    <button class=" " type="button" id="dropdownMenuButton1"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-sm"
                                                        aria-labelledby="dropdownMenuButton1">
                                                        <li class="mb-2"><a class="dropdown-item f-14" href="edit-service.php?id=<?php echo $service->id ?>"><i
                                                                        class="bi bi-pen me-2 f-16 w-600"></i>Edit</a></li>
                                                        <li class="mb-2"><a class="dropdown-item f-14" href="?delete=<?php echo $service->id ?>"><i
                                                                        class="bi bi-trash me-2 f-16 w-600"></i>Delete</a></li>
                                                    </ul>
                                                </div>

                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php

include_once ('includes/footer.php');

?>