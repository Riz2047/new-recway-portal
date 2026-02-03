<?php

include_once('includes/header.php');

if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'SELECT * FROM customers WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
        $customer = $stmt->fetch();

        $query = 'DELETE FROM customers WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);

        $query = 'DELETE FROM emails WHERE email = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$customer->email]);
    }
}

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

if (isset($_GET['delete'])) {
    $query = 'DELETE FROM customers WHERE id = ?';
    $stmt = $conn->prepare($query);
    if ($stmt->execute([$_GET['delete']])) {
        redirect('customers.php');
    }
}

?>


<div class="row">
    <div class="col-lg-12 ">
        <?php
        $pageTitle = "Customers";
        $pageLink = "add-customer.php";
        include_once "buttons-row.php";
        ?>
        <div class="box shadow">
            <div class="data-table  ">
                <div class="customer-table">
                    <form action="" method="post" id="d-form">
                        <table id="dataTable" class="table display nowrap" data-table="customer" style="width:100%">
                            <thead>
                                <tr>
                                    <th><input id="delete-all" class="d-check" type="checkbox" name="all"></th>
                                    <th class="dt-center">Action</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Company</th>
                                    <th>Cost Place</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($customers)) : ?>
                                    <?php foreach ($customers as $customer) : ?>
                                        <tr>
                                            <td><input type="checkbox" class="delete-candidate d-check" value="<?php echo $customer->id ?>" name="delete[]"></td>
                                            <td class="text-center dt-center">
                                                <div class="dropdown profile-dropdown ">
                                                    <button class=" " type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="fa-solid fa-ellipsis-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu shadow-sm" aria-labelledby="dropdownMenuButton1">
                                                        <li class="mb-2"><a class="dropdown-item f-14" href="update-customer.php?id=<?php echo $customer->id ?>"><i class="bi bi-pencil-square me-2 f-16 w-600"></i>Edit</a></li>
                                                        <li class="mb-2"><a class="dropdown-item f-14" href="customers.php?delete=<?php echo $customer->id ?>"><i class="bi bi-person-x w-600 me-2 f-16"></i>
                                                                Delete</a></li>
                                                    </ul>
                                                </div>

                                            </td>
                                            <td><a style="text-decoration: none; color: var(--black)" href="update-customer.php?id=<?php echo $customer->id ?>"><?php echo $customer->name ?></a></td>
                                            <td><?php echo $customer->email ?></td>
                                            <td><?php echo $customer->phone ?></td>
                                            <td><?php echo $customer->company ?></td>
                                            <td><?php echo $customer->cost_place ?></td>
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

</div>

<?php

include_once('includes/footer.php');

?>