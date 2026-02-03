<?php

$activeLink = "profile";

include_once "includes/header.php";

$query = 'SELECT * FROM faqs';
 $stmt = $conn->prepare($query);
$stmt->execute();
$faqs = $stmt->fetchAll();

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">FAQs</p>
            <div class="col-lg-12">
                <div class="accordion mt-3" id="accordionExample">
                    <?php if (!empty($faqs)) : ?>
                        <?php foreach ($faqs as $key => $faq) : ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading<?php echo $faq->id ?>">
                                    <button class="accordion-button <?php echo $key == 0 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $faq->id ?>" aria-controls="collapse<?php echo $faq->id ?>">
                                        <?php echo $faq->question ?>
                                    </button>
                                </h2>
                                <div id="collapse<?php echo $faq->id ?>" class="accordion-collapse collapse <?php echo $key == 0 ? 'show' : '' ?>" aria-labelledby="heading<?php echo $faq->id ?>" data-bs-parent="#accordionExample">
                                    <div class="accordion-body">
                                        <?php echo $faq->answer ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <p class="text-center">No FAQ</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<?php

include_once "includes/footer.php";

?>