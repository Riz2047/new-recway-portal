<?php

$activeLink = "faqs";

include_once('includes/header.php');

// if(isset($_GET['delete'])) {
//     $query = 'DELETE FROM faqs WHERE id=' . $_GET['delete'];
//     $stmt = $conn->prepare($query);
//     $stmt->execute();
//     redirect("faqs.php");
// }

// $query = 'SELECT * FROM faqs';
// $stmt = $conn->prepare($query);
// $stmt->execute();
// $faqs = $stmt->fetchAll();

?>

<div class="mx-lg-4 main-content">
    <div class="container">
        <?php include_once "buttons-row.php" ?>

        <!-- table row -->
        <div class="row">
            <div class="col-lg-12">
                <div class="table-div">

                    <form action="" method="post" id="d-form">
                        <div class="card card-cascade narrower mb-4">

                            <!--Card image-->
                            <div class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 mb-4 d-flex justify-content-between align-items-center">


                                <a href="#" class="white-text mx-3">FAQs</a>

                                <div>
                                    <!-- <button type="button" class="btn btn-outline-white btn-rounded btn-sm px-2">
                                        <span onclick="location.href='add-faq.php'"><i class="bi bi-plus"></i></span>
                                    </button> -->
                                </div>

                            </div>
                            <table id="dataTable" data-table="customer" class="display Table" style="width: 100%">
                                <thead>
                                    <tr>
                                        <th class="table-head">Sr#</th>
                                        <th class="table-head">Question</th>
                                        <th class="table-head">Answer</th>
                                        <th class="dt-center table-head">Action</th>

                                    </tr>
                                </thead>
                                <tbody>

                                    <?php if (! empty($faqs)) : ?>
                                        <?php foreach ($faqs as $key => $faq) : ?>

                                            <tr>
                                                <td class="f-14"><?php echo $key + 1 ?></td>
                                                <td class="f-14"><?php echo $faq->question ?></td>
                                                <td class="f-14"><?php echo $faq->answer ?></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" aria-expanded="false">
                                                            <i class="bi bi-three-dots"></i>
                                                        </button>
                                                        <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">
                                                            <li class="mb-1"><a href="edit-faq.php?id=<?php echo $faq->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                    Edit</a>
                                                            <li class="mb-1"><a href="?delete=<?php echo $faq->id ?>" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>
                                                                    Delete</a>
                                                            </li>

                                                        </ul>
                                                    </div>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>
                                    <?php endif; ?>

                                </tbody>
                            </table>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    var statuses = <?php echo json_encode($statuses) ?>
</script>
<?php

include_once('includes/footer.php');

?>
<script>
    $('#customer').on('change', function() {
        location.href = location.pathname + "?id=" + $(this).val();
    })
</script>