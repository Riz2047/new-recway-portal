<div class="row px-4 pt-3 pb-2 d-flex">
    <div class="col-lg-4">
        <a href="orders.php" class="no-decoration text-black">
            <div class="text-center py-2 orders-btn">All Orders</div>
        </a>
    </div>
    <?php if (in_array(INTERVIEW_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
        <div class="col-lg-4">
            <a href="interviews.php" class="no-decoration text-black">
                <div class="text-center py-2 orders-btn">Interviews</div>
            </a>
        </div>
    <?php endif; ?>
    <?php if (in_array(BACKGROUND_ID, getCustomerServiceCat($_SESSION['department_user']->dep_cus_id))) : ?>
        <div class="col-lg-4">
            <a href="background.php" class="no-decoration text-black">
                <div class="text-center py-2 orders-btn">Background Check</div>
            </a>
        </div>
    <?php endif; ?>
</div>