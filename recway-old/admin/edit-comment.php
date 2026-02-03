<?php

include_once ('includes/header.php');

if(!isset($_GET['oid']) || !isset($_GET['cid'])){
    redirect('index.php');
}

if(isset($_POST['submit'])) {
    $comment = $_POST['comment'];

    $query = 'UPDATE comments SET comment = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$comment, $_GET['cid']]);

    if(!empty($res)) {
        $message = "<p class='alert alert-success'>Comment updated successfully!</p>";
        redirect("invoice.php?id=" . $_GET['oid']);
    } else {
        $message = "<p class='alert alert-danger'>Could not update comment!</p>";
    }
}

$query = 'SELECT * FROM comments WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['cid']]);
$comment = $stmt->fetch();

?>


                <div class="row">

                    <div class="col-lg-12">
                        <div class="main-heading  w-100">
                            <h1 class=" mt-3 mb-4">Edit Comment</h1>
                        </div>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Comment</p>
                                        <textarea name="comment" id="" rows="3" class="w-100 sign-textarea"><?php echo $comment->comment ?></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="submit" class="btn-fill w-100 mt-4"><a>Update</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>