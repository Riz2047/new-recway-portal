<?php

$activeLink = "faqs";

include_once ('includes/header.php');

if(isset($_POST['add'])) {
    $question = $_POST['question'];
    $answer = $_POST['answer'];

    $query = 'INSERT INTO faqs(question,answer) VALUES(?,?)';
    $stmt = $conn->prepare($query);
    $res = $stmt->execute([$question, $answer]);
    if(!empty($res)) {
        flash("faqAdded", "FAQ added successfully!");
    } else {
        flash("faqAdded", "Could not add FAQ!", "errorMsg");
    }
}

?>

<?php flash("faqAdded"); ?>
    <div class="mx-lg-4 main-content">
        <div class="container">

            <div class="row ">

                <div class="col-lg-12">
                    <div class="table-section">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h1 class="main-heading">Add FAQ</h1>
                        </div>

                        <form class="update-form" method="post">
                            <div class="row mb-3">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label" for="question">Question</label>
                                    <textarea required name="question" class="form-control" id="question"></textarea>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-lg-12 mb-3">
                                    <label class="form-label" for="answer">Answer</label>
                                    <textarea required name="answer" class="form-control" id="answer"></textarea>
                                </div>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" name="add" class="btn-primary bg-primary">Save</button>
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