<?php

include_once ('includes/header.php');

if(isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'SELECT * FROM admins WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $admin = $stmt->fetch();

        $query = 'DELETE FROM admin WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);

        $query = 'DELETE FROM emails WHERE email = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$admin->email]);
    }
}

$query = 'SELECT * FROM admin WHERE id != ? AND id != ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['admin']->id, 1]);
$admins = $stmt->fetchAll();

if(isset($_GET['delete'])) {
    $query = 'DELETE FROM admin WHERE id = ?';
    $stmt = $conn->prepare($query);
    if($stmt->execute([$_GET['delete']])) {
        redirect('admins.php');
    }
}


?>


                <div class="row">
                    <div class="col-lg-12 ">
                        <?php
                        $pageTitle = "Admins";
                        $pageLink = "add-admin.php";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow table-responsive">
                            <div class="data-table">
                                <form action="" method="post" id="d-form">
                                    <table id="dataTable" class="table" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th class="dt-center">Action</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(!empty($admins)): ?>
                                            <?php foreach ($admins as $admin): ?>
                                                <tr>
                                                    <td class="text-center dt-center">
                                                        <div class="dropdown profile-dropdown ">
                                                            <button class=" " type="button" id="dropdownMenuButton1"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu shadow-sm"
                                                                aria-labelledby="dropdownMenuButton1">
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="update-admin.php?id=<?php echo $admin->id ?>"><i
                                                                                class="bi bi-pencil-square me-2 f-16 w-600"></i>Edit</a></li>
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="admins.php?delete=<?php echo $admin->id ?>"><i
                                                                                class="bi bi-person-x w-600 me-2 f-16"></i>
                                                                        Delete</a></li>
                                                            </ul>
                                                        </div>

                                                    </td>
                                                    <td><a style="text-decoration: none; color: var(--black)" href="update-admin.php?id=<?php echo $admin->id ?>"><?php echo $admin->name ?></a></td>
                                                    <td><?php echo $admin->email ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                        </tbody>

                                    </table>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>

<?php

include_once ('includes/footer.php');

?>