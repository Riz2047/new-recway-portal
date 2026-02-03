<?php


include_once ('includes/header.php');

$endDate = Date('Y-m-d');
$startDate = Date('Y-m-d', strtotime($endDate . '-30 days'));

$query = 'SELECT * FROM customers';
$stmt = $conn->prepare($query);
$stmt->execute();
$customers = $stmt->fetchAll();

$query = 'SELECT * FROM analytics';
$stmt = $conn->prepare($query);
$stmt->execute();
$analytics = $stmt->fetchAll();

function findObjectByValue($value, $array){

    foreach ( $array as $element ) {
        if ( $value == $element->display ) {
            return $element;
        }
    }

    return false;
}

?>

<div class="row">
    <div class="col-lg-12">
        <div class="d-flex justify-content-between buttons-row">
            <div class="">
                <div class="tab d-flex align-items-center buttons">
                    <a style="cursor: pointer" class="d-flex f-14 w-500 order m-0 me-1 calender tablinks rangeTabLink"
                       onclick="toggleRangeTab.call(this,event)"><i
                                class="bi bi-calendar2-check me-2"></i>Last 30 days</a>
                    <a style="cursor: pointer" class="d-flex f-14 w-500 order m-0 me-1 filter filterTabLink tablinks"
                        onclick="toggleFilterTab.call(this, event)"><i class="bi bi-filter me-2"></i>Filters</a>
                </div>

                <div id="Range" class="tabcontent">
                    <div class="p-relative">
                        <!-- <a  class="d-flex f-14 w-500 order m-0 calender"><i class="bi bi-calendar2-check me-2"></i>Last 30 days</a> -->
                        <div class="btn-dropdown-calender">
                            <div class="row">
                                <div
                                        class="col-lg-4 order-lg-first order-last d-flex flex-column align-items-between justify-content-between">
                                    <div>
                                        <p class="f-14 mb-0 pb-0 w-500 mb-3">Data Range</p>
<!--                                        <select class="form-select sign-input w-100 mb-3"-->
<!--                                                aria-label="Default select example">-->
<!--                                            <option selected>Last 30 Days</option>-->
<!--                                            <option value="1">One</option>-->
<!--                                            <option value="2">Two</option>-->
<!--                                            <option value="3">Three</option>-->
<!--                                        </select>-->
                                        <div class="d-flex align-items-center">
                                            <input type="text" id="startDate" value="<?php echo $startDate ?>" class="sign-input w-100 mb-3"
                                                   placeholder="From">
                                            <p class="f-14 w-500 mx-1">To</p>
                                            <input type="text" id="endDate" value="<?php echo $endDate ?>" class="sign-input w-100 mb-3"
                                                   placeholder="To">
                                        </div>
                                    </div>


                                    <div class="d-flex align-items-center buttons">

                                        <a id="cancel-date" class="d-flex f-14 w-500 order w-50 m-0">Cancel</a>
                                        <a id="apply-date" class="d-flex f-14 w-500 order-fill w-50 apply">Apply</a>

                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="ftco-section shadow-sm p-1">
                                        <div class="col-md-12">
                                            <div class="calendar calendar-first" id="calendar_first">
                                                <div class="calendar_header">
                                                    <button class="switch-month switch-left"> <i
                                                                class="fa fa-chevron-left"></i></button>
                                                    <h2></h2>
                                                    <button class="switch-month switch-right"> <i
                                                                class="fa fa-chevron-right"></i></button>
                                                </div>
                                                <div class="calendar_weekdays"></div>
                                                <div class="calendar_content"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="ftco-section shadow-sm p-1">
                                        <div class="col-md-12">
                                            <div class="calendar calendar-first" id="calendar_second">
                                                <div class="calendar_header">
                                                    <button class="switch-month switch-left"> <i
                                                                class="fa fa-chevron-left"></i></button>
                                                    <h2></h2>
                                                    <button class="switch-month switch-right"> <i
                                                                class="fa fa-chevron-right"></i></button>
                                                </div>
                                                <div class="calendar_weekdays"></div>
                                                <div class="calendar_content"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                <div id="Filter" class="tabcontent">
                    <div class="p-relative">
                        <!-- <a href="" class="d-flex f-14 w-500 order filter"><i class="bi bi-filter me-2"></i>Filters</a> -->
                        <div class="btn-dropdown">
                            <p class="f-14 w-500">You can filter by customers or companies.</p>
                            <form action="">
                                <div class="row p-0 m-0">
                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Customers</p>
                                        <select class="form-select sign-input w-100 mb-3 customer-select"
                                                aria-label="Default select example">
                                            <option selected value="0">All Customers</option>
                                            <?php if(!empty($customers)): ?>
                                            <?php foreach($customers as $customer): ?>
                                                    <option value="<?php echo $customer->id ?>"><?php echo $customer->name ?></option>
                                            <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="col-lg-12 ps-0">
                                        <p class="f-14 mb-0 pb-0 w-500">Companies</p>
                                        <select class="form-select sign-input w-100 mb-3 company-select"
                                                aria-label="Default select example">
                                            <option selected value="0">All Companies</option>
                                            <?php if(!empty($customers)): $companies = [] ?>
                                                <?php foreach($customers as $customer): ?>
                                                    <?php if(!in_array(strtolower(trim($customer->company)), $companies)): ?>
                                                        <option value="<?php echo $customer->company ?>"><?php echo $customer->company ?></option>
                                                    <?php array_push($companies, strtolower(trim($customer->company))); endif; ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <?php //var_dump($companies) ?>
                                    <div class="col-lg-12 ps-0">
                                        <div class="d-flex justify-content-between buttons-row">

                                            <a id="reset-filters" class="f-14 my-4 w-500 clr-blue">Reset
                                                Filters</a>

                                            <div class="d-flex align-items-center buttons">

                                                <a id="cancel-filter" class="d-flex f-14 w-500 order">Cancel</a>
                                                <a
                                                   class="d-flex f-14 w-500 order-fill apply">Apply</a>

                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>

            <div>

            <div class="d-flex align-items-center buttons">

<!--                <a href="" class="d-flex f-14 w-500 order ms-0"><i-->
<!--                            class="bi bi-arrows-angle-expand  me-2 "></i>Customize</a>-->
                <a style="cursor: pointer" class="d-flex f-14 w-500 order m-0 me-1 calender tablinks customizeTabLink"
                   onclick="toggleCustomizeTab.call(this,event)"><i
                            class="bi bi-calendar2-check me-2"></i>Customize</a>
                <a style="cursor: pointer" class=" d-flex f-14 w-500 m-0 me-1 order export"><i class="bi bi-gear me-2"></i>Export</a>

            </div>

            <div id="Customize" class="tabcontent">
                <div class="p-relative">
                    <!-- <a  class="d-flex f-14 w-500 order m-0 calender"><i class="bi bi-calendar2-check me-2"></i>Last 30 days</a> -->
                    <div class="btn-dropdown-customize">
                        <div class="row">
                            <p class="f-14 mb-0 pb-0 w-500 mb-3 text-dark-blue">Orders Data</p>

                            <div class="col-12">
                                <?php $status = findObjectByValue('created-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="created-main" type="checkbox" id="created-check" name="created">
                                    <label style="line-height: 0.9" for="created-check" class="w-500 p-0 m-0 ps-2">Created<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of created orders by the customers</small></label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <?php $status = findObjectByValue('approved-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="approved-main" type="checkbox" id="approved-check" name="approved">
                                    <label style="line-height: 0.9" for="approved-check" class="w-500 p-0 m-0 ps-2">Approved<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of approved orders by the customers</small></label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <?php $status = findObjectByValue('booked-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="booked-main" type="checkbox" id="booked-check" name="booked">
                                    <label style="line-height: 0.9" for="booked-check" class="w-500 p-0 m-0 ps-2">Booked<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of booked orders by the customers</small></label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <?php $status = findObjectByValue('canceled-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="canceled-main" type="checkbox" id="canceled-check" name="canceled">
                                    <label style="line-height: 0.9" for="canceled-check" class="w-500 p-0 m-0 ps-2">Canceled<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of canceled orders by the customers</small></label>
                                </div>
                            </div>

                            <p class="f-14 mb-0 pb-0 w-500 mt-3 mb-3 text-dark-blue">Customers Data</p>
                            <div class="col-12">
                                <?php $status = findObjectByValue('most-orders-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="most-orders-main" type="checkbox" id="most-orders" name="most-orders">
                                    <label style="line-height: 0.9" for="most-orders" class="w-500 p-0 m-0 ps-2">Most Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Customer with most orders</small></label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <?php $status = findObjectByValue('no-orders-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="no-orders-main" type="checkbox" id="no-orders" name="no-orders">
                                    <label style="line-height: 0.9" for="no-orders" class="w-500 p-0 m-0 ps-2">No Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Customers with no orders</small></label>
                                </div>
                            </div>

                            <p class="f-14 mb-0 pb-0 w-500 mt-3 mb-3 text-dark-blue">Companies Data</p>
                            <div class="col-12">
                                <?php $status = findObjectByValue('total-orders-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="total-orders-main" type="checkbox" id="companies-total-orders" name="companies-total-orders">
                                    <label style="line-height: 0.9" for="companies-total-orders" class="w-500 p-0 m-0 ps-2">Total Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of total orders created in companies</small></label>
                                </div>
                            </div>
                            <div class="col-12 mt-3">
                                <?php $status = findObjectByValue('company-orders-main', $analytics)->status ?>
                                <div class="d-flex align-items-center ">
                                    <input <?php echo $status == 1 ? 'checked' : '' ?> value="company-orders-main" type="checkbox" id="companies-orders" name="companies-orders">
                                    <label style="line-height: 0.9" for="companies-orders" class="w-500 p-0 m-0 ps-2">Company Orders<br><small class="f-12 p-0 m-0" style="font-weight: normal">Number of orders created in companies</small></label>
                                </div>
                            </div>

                        </div>

                    </div>

                </div>
            </div>
        </div>

        </div>
    </div>
    <div class="d-flex justify-content-between buttons-row">
        <div class="main-heading  w-100">
            <h1 class="f-14 mt-3">Orders Data</h1>
        </div>
    </div>

    <div class="col-lg-3 col-md-6 mt-3">
        <a style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500 text-dark">Created</h1>
                        <h1 class="f-22 w-800"></h1>
                    </div>
                    <div class="created-count count text-dark f-32 w-500" data-chart="createdChart">

                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mt-3">
        <a style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500 text-dark">Approved</h1>
                        <h1 class="f-22 w-800"></h1>
                    </div>
                    <div class="approved-count count text-dark f-32 w-500" data-chart="approvedChart">

                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mt-3">
        <a style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500 text-dark">Booked</h1>
                        <h1 class="f-22 w-800"></h1>
                    </div>
                    <div class="booked-count count text-dark f-32 w-500" data-chart="bookedChart">

                    </div>
                </div>
            </div>
        </a>
    </div>

    <div class="col-lg-3 col-md-6 mt-3">
        <a style="text-decoration: none">
            <div class="total-card shadow-sm">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="d-flex flex-column align-items-start">
                        <h1 class="f-16 w-500 text-dark">Canceled</h1>
                        <h1 class="f-22 w-800"></h1>
                    </div>
                    <div class="canceled-count count text-dark f-32 w-500" data-chart="canceledChart">

                    </div>
                </div>
            </div>
        </a>
    </div>

    <?php $status = findObjectByValue('created-main', $analytics)->status ?>
    <div class="col-lg-6 created-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Created</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="createdChart" style="width:100%;"></canvas>

        </div>
    </div>

    <?php $status = findObjectByValue('approved-main', $analytics)->status ?>
    <div class="col-lg-6 approved-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Approved</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="approvedChart" style="width:100%;"></canvas>

        </div>
    </div>

    <?php $status = findObjectByValue('booked-main', $analytics)->status ?>
    <div class="col-lg-6 booked-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Booked</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="bookedChart" style="width:100%;"></canvas>

        </div>
    </div>

    <?php $status = findObjectByValue('canceled-main', $analytics)->status ?>
    <div class="col-lg-6 canceled-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Canceled</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="canceledChart" style="width:100%;"></canvas>

        </div>
    </div>

    <div class="d-flex justify-content-between buttons-row">
        <div class="main-heading  w-100">
            <h1 class="f-14 mt-3">Customers Data</h1>
        </div>
    </div>

    <?php $status = findObjectByValue('most-orders-main', $analytics)->status ?>
    <div class="col-lg-6 most-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 most-orders height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Customer with most orders</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="customerMostChart" style="width:100%;"></canvas>

        </div>
    </div>

    <?php $status = findObjectByValue('no-orders-main', $analytics)->status ?>
    <div class="col-lg-6 no-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 no-orders height-orders" style="overflow-y: auto; ">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Customers with no orders</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
<!--            <canvas id="customerLeastChart" style="width:100%;"></canvas>-->
            <div>
                <div class="d-flex justify-content-between">
                    <p class="text-left w-500 w-100">Name</p>
                    <p class="text-left w-500 w-100">Email</p>
                </div>
                <div class="noorders-users">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between buttons-row">
        <div class="main-heading  w-100">
            <h1 class="f-14 mt-3">Companies Data</h1>
        </div>
    </div>

    <?php $status = findObjectByValue('total-orders-main', $analytics)->status ?>
    <div class="col-lg-6 total-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
<!--        <div class="box shadow mt-3">-->
<!--            <div class="d-flex justify-content-between chats-cards">-->
<!--                <p class="f-14 w-400 ">Companies Total Orders</p>-->
<!--                <p class="created-title-date f-14 w-300">--><?php //echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?><!--</p>-->
<!--            </div>-->
<!--            <canvas id="companyChart" style="width:100%;"></canvas>-->
<!---->
<!--        </div>-->

        <div class="box shadow mt-3 no-orders height-orders" style="overflow-y: auto; ">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Companies total orders</p>
                <p class="created-title-datee f-14 w-300"><?php //echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <!--            <canvas id="customerLeastChart" style="width:100%;"></canvas>-->
            <div>
                <div class="d-flex justify-content-between">
                    <p class="text-center w-500 w-100">Name</p>
                    <p class="text-center w-500 w-100">Orders</p>
                </div>
                <div class="companies-table">
                </div>
            </div>
        </div>
    </div>

    <?php $status = findObjectByValue('company-orders-main', $analytics)->status ?>
    <div class="col-lg-6 company-orders-main" style="display: <?php echo $status == 1 ? 'block' : 'none' ?>">
        <div class="box shadow mt-3 height-orders">
            <div class="d-flex justify-content-between chats-cards">
                <p class="f-14 w-400 ">Company Orders</p>
                <p class="created-title-date f-14 w-300"><?php echo Date('d M', strtotime($startDate)) . ' - ' . Date('d M', strtotime($endDate)) ?></p>
            </div>
            <canvas id="singleCompanyChart" style="width:100%;"></canvas>

        </div>
    </div>

</div>

<?php

include_once ('includes/footer.php');

?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="../assets/analytics.js"></script>

<script>
    $('input[type=checkbox]').each(function () {
        $(this).on('change', function () {
            var that = $(this)
            $.ajax({
                type: "POST",
                url: '../includes/ajax.php',
                data: {"analytics":true, "display": $(this).val() , "displayStatus":$(this).is(":checked")},
                success: function(response)
                {
                    console.log(response)
                    var res = JSON.parse(response);

                    if(res.status == 1) {
                        $('.'+res.display).css('display', 'block');
                    } else {
                        $('.'+res.display).css('display', 'none');
                    }
                }
            });
        })
    })
</script>