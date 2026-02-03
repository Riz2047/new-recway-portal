<?php

include_once ('includes/header.php');

if(isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'SELECT * FROM staff WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $staff = $stmt->fetch();

        $query = 'DELETE FROM staff WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);

        $query = 'DELETE FROM emails WHERE email = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$staff->email]);
    }
}

$query = 'SELECT * FROM staff';
$stmt = $conn->prepare($query);
$stmt->execute();
$staff = $stmt->fetchAll();

if(isset($_GET['delete'])) {
    $query = 'DELETE FROM staff WHERE id = ?';
    $stmt = $conn->prepare($query);
    if($stmt->execute([$_GET['delete']])) {
        redirect('staff.php');
    }
}

?>


                <div class="row">
                    <div class="col-lg-12 ">
                        <?php
                        $pageTitle = "Staff";
                        $pageLink = "add-staff.php";
                        include_once "buttons-row.php";
                        ?>
                        <div class="box shadow">
                            <div class="data-table staff-table">
                                <form action="" method="post" id="d-form">
                                    <table id="dataTable" class="table" style="width:100%">
                                        <thead>
                                        <tr>
                                            <th><input id="delete-all" class="d-check" type="checkbox" name="all"></th>
                                            <th class="dt-center">Action</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php if(!empty($staff)): ?>
                                            <?php foreach ($staff as $st): ?>
                                                <?php
                                                $is_staff = "";
                                                if($st->id != 0){
                                                    $query = 'SELECT * FROM candidates WHERE staff_id = ?';
                                                    $stmt = $conn->prepare($query);
                                                    $stmt->execute([$st->id]);
                                                    $is_staff = $stmt->fetch();
                                                }
                                                ?>
                                                <tr>
                                                    <td><input <?php echo !empty($is_staff) ? 'disabled' : '' ?> type="checkbox" class="<?php echo !empty($is_staff) ? '' : 'delete-candidate' ?> d-check" value="<?php echo $st->id ?>" name="delete[]"></td>
                                                    <td class="text-center dt-center">
                                                        <div class="dropdown profile-dropdown ">
                                                            <button class=" " type="button" id="dropdownMenuButton1"
                                                                    data-bs-toggle="dropdown" aria-expanded="false">
                                                                <i class="fa-solid fa-ellipsis-vertical"></i>
                                                            </button>
                                                            <ul class="dropdown-menu shadow-sm"
                                                                aria-labelledby="dropdownMenuButton1">
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="update-staff.php?id=<?php echo $st->id ?>"><i
                                                                                class="bi bi-pencil-square me-2 f-16 w-600"></i>Edit</a></li>
                                                                <li <?php echo !empty($is_staff) ? 'style="pointer-events: none;"' : '' ?> class="mb-2"><a <?php echo !empty($is_staff) ? 'style="color:#bebebe;"' : '' ?> class="dropdown-item f-14" href="staff.php?delete=<?php echo $st->id ?>"><i
                                                                                class="bi bi-person-x w-600 me-2 f-16"></i>
                                                                        Delete</a></li>
                                                                <li class="mb-2"><a class="dropdown-item f-14" href="staff-permission.php?id=<?php echo $st->id ?>"><i
                                                                                class="bi bi-shield-lock me-2 f-16 w-600"></i>Permissions</a></li>
                                                            </ul>
                                                        </div>

                                                    </td>
                                                    <td><a style="text-decoration: none; color: var(--black)" href="staff-candidates.php?id=<?php echo $st->id ?>"><?php echo $st->name ?></a></td>
                                                    <td><?php echo $st->email ?></td>
                                                    <td><?php echo $st->phone ?></td>
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