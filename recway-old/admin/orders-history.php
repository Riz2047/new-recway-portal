<?php

include_once ('includes/header.php');
include_once ('../includes/config.php');

if(isset($_GET['status'])) {
    $query = "SELECT * FROM candidates WHERE status = {$_GET['status']}";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
} else {
    $query = 'SELECT * FROM candidates';
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $candidates = $stmt->fetchAll();
}


?>


                <div class="row">
                    <div class="col-lg-12 ">
                        <div class="main-heading d-flex justify-content-between w-100">
                            <h1 class=" my-4">Candidates</h1>
                        </div>
                        <div class="data-table">
                            <table id="dataTable" class="table table-striped display nowrap" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Candidate</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>View</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if(!empty($candidates)): ?>
                                    <?php foreach ($candidates as $candidate): ?>
                                    <?php
                                        $query = 'SELECT * FROM interviews WHERE id = ?';
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute([$candidate->interview_id]);
                                        $interview = $stmt->fetch();
                                    ?>
                                        <tr>
                                            <td><?php echo $candidate->order_id ?></td>
                                            <td><?php echo $candidate->name . ' ' . $candidate->surname ?></td>
                                            <td><?php echo $interview->title ?></td>
                                            <td><?php echo $statuses[$candidate->status] ?></td>
                                            <td>
                                                <div class="d-flex justify-content-start action-icons">
                                                    <a href="order-history.php?id=<?php echo $candidate->id ?>" class="mx-1"><i class="fa-solid fa-eye"></i></a>
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

<?php

include_once ('includes/footer.php');

?>
