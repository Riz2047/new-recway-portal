<?php

$statuses2 = getStatuses();

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$serviceCats = $stmt->fetchAll();

?>

<div class="row buttons-row align-items-center">
    <div class="main-heading col-9">
        <h1 class="f-14 my-4"><?php echo $pageTitle ?></h1>
    </div>
    <div class="<?php echo empty($pageLink) ? 'd-none' : '' ?> d-flex col-3 justify-content-end align-items-center buttons">
        <div class="dropdown dropdown2 ">
            <button class=" " onclick="window.location.href='<?php echo $pageLink ?>'" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-<?php echo isset($pageIcon) && !empty($pageIcon) ? $pageIcon : 'person-plus' ?> f-22"></i>
            </button>
        </div>
    </div>
    <div class="col-12 d-flex flex-wrap align-items-center justify-content-center buttons">
        <?php if (!empty($serviceCats)) : ?>
            <?php foreach ($serviceCats as $key => $serviceCat) : ?>
                <a href="?service=<?php echo $serviceCat->id ?><?php echo isset($_GET['id']) ? '&id=' . $_GET['id'] : '' ?>" data-catid="<?php echo $serviceCat->id ?>" class="d-flex f-14 w-500 mb-3 services-btns <?php echo isset($_GET['service']) && $_GET['service'] == $serviceCat->id ? 'active-service' : '' ?> <?php echo $key == 0 && !isset($_GET['service']) ? 'active-service' : '' ?>"><?php echo $serviceCat->name ?></a>
            <?php endforeach; ?>
        <?php endif; ?>
        <a href="candidates.php" class="d-flex f-14 w-500 services-btns mb-3 ">All Orders</a>
    </div>
    <hr class="w-50 mx-auto">
    <?php if (!empty($serviceCats)) : ?>
        <?php foreach ($serviceCats as $key => $serviceCat) : ?>
            <?php $statuses2 = getStatusesByService($serviceCat->id) ?>
            <?php
            if (isset($_GET['service']) && $_GET['service'] == $serviceCat->id) {
                $class = '';
            } else if (!isset($_GET['service']) && $key == 0) {
                $class = '';
            } else {
                $class = 'd-none';
            }
            ?>
            <div class="<?php echo $class ?> col-12 d-flex flex-wrap align-items-center justify-content-center buttons service-btns-inner <?php echo 'service-' . $serviceCat->id ?>">
                <?php if (!empty($statuses2)) : ?>
                    <?php foreach ($statuses2 as $status) : ?>
                        <a href="candidates.php?status=<?php echo $status->sID ?><?php echo isset($_GET['service']) ? '&service=' . $_GET['service'] : '' ?>" class="d-flex f-14 w-500 order mb-3 "><i class="bi bi-file-earmark-text me-2"></i><?php echo $status->status ?>(<?php echo count(getStatusCard($status->sID)) ?>)</a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>