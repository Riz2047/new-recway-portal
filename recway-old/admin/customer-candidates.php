<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect('customers.php');
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();

if(isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'DELETE FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
    }
}

if(isset($_GET['status'])) {
    $query = 'SELECT * FROM candidates WHERE status = ? AND cus_id = ? AND expired = 0 ORDER BY booked ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['status'], $_GET['id']]);
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates WHERE cus_id = ?  AND expired = 0 ORDER BY booked ASC';
    $stmt = $conn->prepare($query);
    $stmt->execute([$_GET['id']]);
    $candidates = $stmt->fetchAll();
}

if(isset($_POST['resend'])) {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $text = $_POST['text'];
    $subject = $_POST['subject'];

    sendMail($text, $email, $name, $subject);
}

$query = "SELECT * FROM emails WHERE email = ? ORDER BY id DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$customer->email]);
$emails = $stmt->fetchAll();


?>

                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="d-flex justify-content-between buttons-row">
                            <div class="main-heading  w-100">
                                <h1 class="f-14 my-4"><?php echo $customer->name ?>'s Orders</h1>
                            </div>
                            <div class="d-flex align-items-center buttons">
                                <p class="f-16 mt-0 mb-0 ms-2 d-text text-white"
                                   style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                    <i class="bi bi-trash"></i></p>
                                <p class="f-16 mt-0 mb-0 ms-2 d-text2 text-white"
                                   style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                    <i class="bi bi-pen"></i></p>
                                <p class="f-16 mt-0 mb-0 ms-2 d-text3 text-white"
                                   style="cursor: pointer; display: none; background-color: var(--black);padding: 5px 8px;border-radius: 4px">
                                    <i class="bi bi-person"></i></p>
                                <a href="candidates.php?status=0" class="d-flex f-14 w-500 order"><i
                                            class="bi bi-file-earmark-text me-2"></i>Pending(<?php echo count(getStatusCard(0)) ?>)</a>
                                <a href="candidates.php" class="d-flex f-14 w-500 order"><i
                                            class="bi bi-file-earmark-text me-2"></i>All Orders</a>
                            </div>
                        </div>
                        <div class="box shadow ">
                            <?php include_once "candidates-table.php" ?>
                        </div>
                    </div>

                </div>

<?php

include_once ('includes/footer.php');

?>
