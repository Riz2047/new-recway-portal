<?php
$activeLink = "customers";
include_once('includes/header.php');
if (! isset($_POST['delete']) && ! isset($_POST['update'])) {
    redirect('index.php');
}
if (isset($_POST['update'])) {
    $groupid = null;
    $insert_array = null;
    $select_groups = ! empty($_POST['select_group']) ? $_POST['select_group'] : null;
    $cus_ids = ! empty($_POST['cus_id']) ? $_POST['cus_id'] : null;
    if (! empty($select_groups)) {
        foreach ($select_groups as $select_group) {
            if (is_numeric($select_group)) {
                $groupid[] = $select_group;
            } else {
                $groupid[] = insert('groups', ['name' => $select_group]);
            }
        }
    }
    foreach ($cus_ids as $cus_id) {
        $customer = findById('customers', $cus_id);
        if (! empty($customer)) {
            $cust_grp = null;
            $uniquegroupid = null;
            if (! empty($customer->groups)) {
                $cust_grp = explode(',', $customer->groups);
                if (! empty($cust_grp)) {
                    $array_merge = array_merge($groupid, $cust_grp);
                    $array_merge = array_unique($array_merge);
                    $uniquegroupid = implode(',', $array_merge);
                    update('customers', ['groups' => $uniquegroupid], 'id', $cus_id);
                }
            }
            if (empty($cust_grp)) {
                if (! empty($groupid)) {
                    if (is_array($groupid)) {
                        $groupid = implode(',', $groupid);
                    }
                    update('customers', ['groups' => $groupid], 'id', $cus_id);
                }
            }
        }
    }
    redirect('customers.php');
}
$query = 'SELECT * FROM `groups`';
$stmt = $conn->prepare($query);
$stmt->execute();
$groups = $stmt->fetchAll();
?>
<div class="mx-lg-4 main-content">
    <div class="container">
        <div class="row ">
            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Groups</h1>
                    </div>
                    <form class="update-form" method="post">
                        <div class="col-md-12 mb-3">
                            <label class="form-label" for="">Groups</label>
                            <select name="select_group[]" class="filter-select select2tag" multiple>
                                <?php if (! empty($groups)) : ?>
                                    <?php foreach ($groups as $gp) : ?>
                                        <option value="<?php echo $gp->id ?>"><?php echo $gp->name ?></option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                            <?php if (isset($_POST['delete'])) : ?>
                                <?php foreach ($_POST['delete'] as $can) : ?>
                                    <input type="hidden" name="cus_id[]" value="<?= $can ?>">
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="submit" name="update" class="btn-primary bg-primary">Save</button>
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
include_once('includes/footer.php');
?>