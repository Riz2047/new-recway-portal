<?php

$activeLink = "candidates";

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
        flash("commentUpdated", "Comment updated successfully!");
        redirect("invoice.php?id=" . $_GET['oid']);
    } else {
        flash("commentUpdated", "Could not update comment!", "errorMsg");
    }
}

$query = 'SELECT * FROM comments WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['cid']]);
$comment = $stmt->fetch();

?>

            <?php flash("commentUpdated"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Edit Comment</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label" for="comment">Comment</label>
                                                    <textarea required name="comment" class="form-control" id="comment"><?php echo $comment->comment ?></textarea>
                                                </div>
                                            </div>
                                           
                                           <div class="d-flex justify-content-end">
                                            <button type="submit" name="submit" class="btn-primary bg-primary">Update</button>
                                           </div>
                                        </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php

include_once ('includes/footer.php');

?>