<?php

$activeLink = "staff";

include_once('includes/header.php');

if (isset($_POST['delete'])) {
    $query = 'SELECT * FROM user_category WHERE id = ?';
    $stmt = $conn->prepare($query);
    $stmt->execute([$delete]);
    $admin = $stmt->fetch();
    flash("adminDeleted", "Staff Category has been deleted!");
}

$query = "SELECT * FROM user_category";
$stmt = $conn->prepare($query);
$stmt->execute();
$categorys = $stmt->fetchAll();
?>
<?php if (!isset($_GET['delete'])) : ?>
    <?php flash("adminDeleted"); ?>
<?php endif; ?>
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


                                <a href="#" class="white-text mx-3">Staff Category</a>

                                <div style="display:flex !important">
                                    <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2" data-toggle="tooltip" data-placement="top" title="Add Staff Category">
                                        <span onclick="location.href='add_staff_category.php'"><i class="bi bi-person-plus"></i></span>
                                    </button>
                                    <button type="button" style="display: none" class="btn btn-outline-white btn-rounded btn-sm px-2 d-text" data-toggle="tooltip" data-placement="top" title="Remove Category">
                                        <span class=""><i class="bi bi-trash"></i></span>
                                    </button>
                                    <!-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2 float-right m-0" onclick="show_card(this)" style="height: 31px;margin-top: 6px !important;">
                                        <i class="bx bxs-chevron-down arrow"></i>
                                    </button> -->
                                </div>

                            </div>
                            <table id="dataTable" class="display Table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="dt-center table-head" style="width: 20%">Action</th>
                                        <th class="table-head">Title</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (!empty($categorys)) : ?>
                                        <?php foreach ($categorys as $key => $category) : ?>
                                            <tr>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-gear"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <li class="mb-1"><a href="update_staff_category.php?id=<?php echo $category->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                    Edit</a>
                                                            </li>
                                                            <li class="mb-1"><a href="staff_category.php?delete=<?php echo $category->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                                    Delete</a>
                                                            </li>
                                                        </ul>
                                                    </div>

                                                </td>
                                                <td class="f-14"><a class="no-decoration text-black" href="update_staff_category.php?id=<?php echo $category->id ?>"><?php echo $category->title ?></a></td>

                                            </tr>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

include_once('includes/footer.php');

?>