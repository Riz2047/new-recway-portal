<?php

$activeLink = "emails";

include_once "includes/header.php";

if (!isset($user_allowed_permissions['View-emails']) && empty($user_allowed_permissions['View-emails'])) {
    redirect('index.php');
}

$services = findAll("service_categories");

?>

<section>
    <div class="container mt-3">
        <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Emails Setting</p>
            <div class="col-lg-12">
                <form action="" method="post" class="form">
                    <?php echo isset($message) ? $message : '' ?>
                    <div class="form-tag mb-2">Select emails which you want to receive</div>
                    <?php if (!empty($services)) : ?>
                        <?php foreach ($services as $service) : ?>
                            <p class="fw-bold mt-2 mb-0"><?php echo $service->name ?></p>
                            <?php $statuses = getStatusesByService($service->id); ?>
                            <div class="row">
                                <?php if (!empty($statuses)) : ?>
                                    <?php foreach ($statuses as $status) : ?>
                                        <div class="col-lg-3">
                                            <label><input <?php echo isEmailAllowed($_SESSION['department_user']->dep_cus_id, $status->sID) ? 'checked' : '' ?> type="checkbox" class="emails-status" value="<?php echo $status->sID ?>" data-cus-id="<?php echo $_SESSION['department_user']->dep_cus_id ?>"> <?php echo $status->status ?></label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <!--                    <div class="d-flex justify-content-end">-->
                    <!--                      <button type="submit" name="update" class="form-btn border-0">Update</button>-->
                    <!--                    </div>-->
                </form>
            </div>
        </div>
    </div>
</section>

<?php

include_once "includes/footer.php";

?>

<script>
    $(".emails-status").click(function() {
        $.ajax({
            url: "includes/ajax.php",
            method: "post",
            data: {
                email_status: "true",
                status_id: $(this).val(),
                cus_id: $(this).data("cus-id"),
                allowed: $(this).is(":checked")
            },
            success: function(response) {
                // console.log(response)
            }
        })
    })
</script>