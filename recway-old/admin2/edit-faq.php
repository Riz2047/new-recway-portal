<?php

$activeLink = "faqs";

include_once ('includes/header.php');

if(!isset($_GET['id'])) {
    redirect("faqs.php");
}

if(isset($_POST['update'])) {
    $question = $_POST['question'];
    $answer = $_POST['answer'];

    $query = 'UPDATE faqs SET question = ?, answer = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$question, $answer, $_GET['id']]);
    if(!empty($res)) {
        flash("faqUpdated", "FAQ updated successfully!");
    } else {
        flash("faqUpdated", "Could not update FAQ!", "errorMsg");
    }
}

$query = 'SELECT * FROM faqs WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$faq = $stmt->fetch();

?>

<?php flash("faqUpdated"); ?>
    <div class="mx-lg-4 main-content">
        <div class="container">

            <div class="row ">

                <div class="col-lg-12">
                    <div class="table-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="main-heading">Update FAQ</h1>
                        </div>

                        <form class="update-form" method="post">
                            <div class="row mb-3">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label" for="question">Question</label>
                                    <textarea required name="question" class="form-control" id="question"><?php echo $faq->question ?></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label" for="answer">Answer</label>
                                    <textarea required name="answer" class="form-control" id="answer"><?php echo $faq->answer ?></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="update" class="btn-primary bg-primary">Update</button>
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