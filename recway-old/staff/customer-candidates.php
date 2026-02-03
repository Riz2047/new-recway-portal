<?php

$activeLink = "candidates";

include_once('includes/header.php');

if (!isset($_GET['id'])) {
    redirect('customers.php');
}

$query = 'SELECT * FROM customers WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$customer = $stmt->fetch();

if (isset($_POST['delete'])) {
    foreach ($_POST['delete'] as $delete) {
        $query = 'DELETE FROM candidates WHERE id = ?';
        $stmt = $conn->prepare($query);
        $stmt->execute([$delete]);
    }
}

if (isset($_GET['status'])) {
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

if (isset($_POST['resend'])) {
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

$candidatesCustomer = $customer->name;

?>

<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>

        <!-- table row -->
        <div class="row">
            <div class="col-lg-12">
                <?php include_once "candidates-table.php" ?>
            </div>
        </div>
    </div>
</div>

<?php

include_once('includes/footer.php');

?>