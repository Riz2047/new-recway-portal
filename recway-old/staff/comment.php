<?php

$activeLink = "candidates";

include_once ('includes/header.php');

if(!isset($_GET['id'])){
    redirect('index.php');
}

if(isset($_POST['submit'])) {
    $comment = $_POST['comment'];

    $query = 'INSERT INTO comments (order_id, author_id, author_type, comment) VALUES (?,?,?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$_GET['id'], $_SESSION['admin']->id, 'admin', $comment]);

    if(!empty($res)) {
        flash("commentAdded", "Comment added successfully!");
    } else {
        flash("commentAdded", "Could not add comment!", "errorMsg");
    }
}

?>

            <?php flash("commentAdded"); ?>
            <div class="mx-lg-4 main-content">
                <div class="container">

                    <div class="row ">

                        <div class="col-lg-12">
                            <div class="table-section">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h1 class="main-heading">Add Comment</h1>
                                    </div>

                                        <form class="update-form" method="post">
                                            <div class="row mb-3">
                                                <div class="col-lg-12 mb-3">
                                                    <label class="form-label" for="comment">Comment</label>
                                                    <textarea required name="comment" class="form-control" id="comment"></textarea>
                                                </div>
                                            </div>
                                           
                                           <div class="d-flex justify-content-end">
                                            <button type="submit" name="submit" class="btn-primary bg-primary">Save</button>
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