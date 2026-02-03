<?php

$activeLink = "reviewers";

include_once "includes/header.php";
if (!isset($user_allowed_permissions['View-reviewer']) && empty($user_allowed_permissions['View-reviewer'])) {
    redirect('index.php');
}


if (isset($_GET['id'])) {
    delete("reviewers", "id", $_GET['id']);
 }

$reviewers = findAllByQuery("SELECT * FROM reviewers WHERE cus_id = {$_SESSION['department_user']->dep_cus_id}");

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <div class="d-flex mb-2 justify-content-end">
                <button type="button" onclick="window.location.href = 'add-reviewer.php'" class="form-btn border-0">Add Reviewer</button>
            </div>
            <p class="f-14 text-grey w-400 mb-0 pb-0">Reviewers</p>
            <div class="col-lg-12">
                <div class="table-div p-2">
                    <table id="myTable" class="display Table">
                        <thead>
                            <tr>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($reviewers)) : ?>
                                <?php foreach ($reviewers as $reviewer) : ?>
                                    <tr>
                                        <td><?php echo $reviewer->email ?></td>
                                        <td class="text-center dt-center">
                                            <div class="dropdown">
                                                <button class="table-menu-btn mx-auto" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                    <i class="bi bi-three-dots"></i>
                                                </button>
                                                <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                    <!--                                            <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="update-reviewer.php?id=--><?php //echo $reviewer->id 
                                                                                                                                                                                                        ?><!--"><i-->
                                                    <!--                                                            class="bi bi-pen text-black f-14 me-2"></i>-->
                                                    <!--                                                    Edit</a></li>-->
                                                    <li class="mb-1"><a class="no-decoration f-14 w-600 text-black " href="?id=<?php echo $reviewer->id ?>"><i class="bi bi-trash text-black f-14 me-2"></i>
                                                            Delete</a></li>
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
</section>

<?php

include_once "includes/footer.php";

?>