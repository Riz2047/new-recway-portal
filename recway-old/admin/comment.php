<?php

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
        $message = "<p class='alert alert-success'>Comment added successfully!</p>";
    } else {
        $message = "<p class='alert alert-danger'>Could not add comment!</p>";
    }
}

?>


                <div class="row">

                    <div class="col-lg-12">
                        <div class="main-heading  w-100">
                            <h1 class=" mt-3 mb-4">Add Comment</h1>
                        </div>
                        <div class="box shadow">
                            <?php echo isset($message) ? $message : '' ?>
                            <form action="" method="post">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Comment</p>
                                        <textarea name="comment" id="" rows="3" class="w-100 sign-textarea"></textarea>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <button type="submit" name="submit" class="btn-fill w-100 mt-4"><a>Submit</a></button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>


<?php

include_once ('includes/footer.php');

?>