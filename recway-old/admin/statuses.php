<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("index.php");
}

if(isset($_GET['delete'])) {
    $query = 'DELETE FROM statuses WHERE id=' . $_GET['delete'];
    $stmt = $conn->prepare($query);
    $stmt->execute();
    redirect("statuses.php");
}

$statuses = getStatusesByService($_GET['id']);

?>


    <div class="row">
        <div class="col-lg-12 history-data">
            <div class="d-flex justify-content-between buttons-row">
                <?php
                $pageTitle = "Statuses";
                $pageLink = "add-status.php";
                $pageIcon = "clipboard-plus";
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
                                    <th>Status</th>
                                    <th class="dt-center">Action</th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php if(!empty($statuses)): ?>
                                    <?php foreach ($statuses as $key => $status): ?>
                                        <tr>
                                            <td><?php echo $key+1 ?></td>
                                            <td><?php echo $status->status ?></td>
                                            <td class="text-center dt-center">
                                                <div class="dropdown profile-dropdown " >
                                                    <button class=" " type="button" id="dropdownMenuButton1"
                                                            data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-sm"
                                                        aria-labelledby="dropdownMenuButton1">
                                                        <li class="mb-2"><a class="dropdown-item f-14" href="edit-status.php?id=<?php echo $status->sID ?>"><i
                                                                        class="bi bi-pen me-2 f-16 w-600"></i>Edit</a></li>
                                                        <li class="mb-2 delete-status"><a class="dropdown-item f-14" href="?id=<?php echo $_GET['id'] ?>&delete=<?php echo $status->id ?>"><i
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

<script>
    $('.delete-status').on('click', function (e) {
        e.preventDefault()
        if(confirm("Are you sure you want to delete?")) {
            location.href = $(this).find('a').attr("href")
        } else {
            return false;
        }
    })
</script>
