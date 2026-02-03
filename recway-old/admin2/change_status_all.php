<?php

$activeLink = "candidates";

include_once('includes/header.php');

$statuses = getStatuses();

if (isset($_POST['status'])) {
    $status = !empty($_POST['status']) ? $_POST['status'] : '';
    $date = !empty($_POST['date']) ? $_POST['date'] : '';
    $can_id = !empty($_POST['candidates']) ? $_POST['candidates'] : '';
    $cus_name = !empty($_POST['cus_name']) ? $_POST['cus_name'] : '';
    $can_name = !empty($_POST['can_name']) ? $_POST['can_name'] : '';
    $cus_email = !empty($_POST['cus_email']) ? $_POST['cus_email'] : '';
    $cus_company = !empty($_POST['cus_company']) ? $_POST['cus_company'] : '';
    $comment = !empty($_POST['comment']) ? $_POST['comment'] : '';
    $comment .= '<br>-' . $_SESSION['admin']->name;
    $orderID = !empty($_POST['order_id']) ? $_POST['order_id'] : '';
    $report = isset($_FILES['report']) && !empty($_FILES['report']['name']) ? $_FILES['report']['name'] : "";
    $interview = !empty($_POST['interview']) ? $_POST['interview'] : '';
    $interviewID = !empty($_POST['interviewID']) ? $_POST['interviewID'] : '';

    $reportName = time() . "-" . substr(uniqid(), -6) . ".pdf";

    if (!empty($report)) {
        move_uploaded_file($_FILES['report']['tmp_name'], '../uploads/' . $reportName);
    }

    $status = getStatusById($status);

    $date_time = date('Y-m-d H:i:s', strtotime($date . date('H:i:s')));
    if (!empty($can_id)) {
        foreach ($can_id as $k => $canid) {

            $last_interview_date = 0;
            $last_staff_id = 0;
            $d_date = 0;
            $last_status = 0;
            $query = 'SELECT * FROM candidates WHERE id = ?';
            $stmt = $conn->prepare($query);
            $stmt->execute([$canid]);
            $rec_order = $stmt->fetch(PDO::FETCH_ASSOC);
            if($rec_order){
                if (isSwedenWorkingHours() == 1) {}else{
                    $last_status = $rec_order['status'];
                    $d_date = $rec_order['delivery_date'];
                    $last_interview_date = $rec_order['booked'];
                }
            }

            if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
                $query = 'UPDATE candidates SET status = ?, booked = ?';
                if (!empty($report)) {
                    $query .= ", report = '{$reportName}'";
                }

                $query .= " WHERE id = ?";
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$status->id, $date, $canid]);
            } elseif ($status->variable == "rebooking") {
                $query = 'UPDATE candidates SET status = ?, booked = ?';
                if (!empty($report)) {
                    $query .= ", report = '{$reportName}'";
                }

                $query .= " WHERE id = ?";
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$status->id, null, $canid]);
            } else {
                $query = 'UPDATE candidates SET status = ?';
                if (!empty($report)) {
                    $query .= ", report = '{$reportName}'";
                }
                if ($status->variable == "approval_received") {
                    // Query the interview data without overwriting the update query
                    $interviewQuery = 'SELECT * FROM interviews WHERE id = ?';
                    $stmt = $conn->prepare($interviewQuery);
                    $stmt->execute([$interviewID]);
                    $interviews = $stmt->fetch(PDO::FETCH_ASSOC);
                
                    if (!empty($interviews['delivery_days'])) {
                        $d_date = getDateAfterDays($interviews['delivery_days']);
                        $query .= ", delivery_date = '{$d_date}'";
                    } else {
                        // Set default delivery date based on interviewID
                        $delivery_date = $interviewID == 10 ? date('Y-m-d', strtotime($date . ' +3 days')) : date('Y-m-d', strtotime($date . ' +5 days'));
                
                        // Adjust if the delivery date falls on a weekend
                        if (date('N', strtotime($delivery_date)) >= 6) {
                            $days_to_add = 8 - date('N', strtotime($delivery_date));
                            $delivery_date = date('Y-m-d', strtotime($delivery_date . ' +' . $days_to_add . ' days'));
                        }
                
                        $query .= ", delivery_date = '{$delivery_date}'";
                    }
                }

                $query .= " WHERE id = ?";
                $stmt = $conn->prepare($query);
                $res = $stmt->execute([$status->id, $canid]);

                // Logging the status change
                if (isset($_SESSION['staff']->id) && !empty($_SESSION['staff']->id)) {
                    $logQuery = 'INSERT INTO staff_logs (staff_id, log_msg) VALUES (?,?)';
                    $logStmt = $conn->prepare($logQuery);
                    $res = $logStmt->execute([$_SESSION['staff']->id, " changed the status of <a href='invoice.php?id={$canid}'>{$orderID}</a> to {$status->status}"]);
                }
            }


            $res = "true";
            if (!empty($res)) {
                if (isSwedenWorkingHours() == 1) {
                    $query = "INSERT INTO history (order_id, `desc`, date_time, comment) VALUES (?,?,?,?)";
                    $stmt = $conn->prepare($query);
                    if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
                        $res = $stmt->execute([$canid, $status->status_detail, date('Y-m-d H:i:s'), $comment]);
                    } else {
                        $res = $stmt->execute([$canid, $status->status_detail, $date_time, $comment]);
                    }   
                }else{
                    $nextWorkingHour = getNextWorkingHour()->format('Y-m-d H:i:s');
                    $query = "INSERT INTO history (order_id, `desc`, date_time, comment, last_status,staff_id,last_interview_date,last_delivery_date) VALUES (?,?,?,?,?,?,?,?)";
                    $stmt = $conn->prepare($query);
                    if ($status->variable == "booked" || $status->variable == "booked_msg_follow") {
                        if(!empty($rec_order['booked'])){
                            $last_interview_date = $rec_order['booked'];
                        }else{
                            $last_interview_date = 1;
                        }
                        $res = $stmt->execute([$canid, $status->status_detail, $nextWorkingHour, $comment, $last_status,$last_staff_id,$last_interview_date,$d_date]);
                    } else {
                        if ($status->variable == "approval_received") {
                            if(!empty($rec_order['delivery_date'])){
                                $d_date = $rec_order['delivery_date'];
                            }else{
                                $d_date = 1;
                            }
                        }
                        $res = $stmt->execute([$canid, $status->status_detail, $nextWorkingHour, $comment, $last_status,$last_staff_id,$last_interview_date,$d_date]);
                    } 
                }

                $query = 'SELECT * FROM candidates WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$canid]);
                $candidate = $stmt->fetch();

                $query = 'SELECT * FROM staff WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->staff_id]);
                $staff = $stmt->fetch();

                $query = 'SELECT * FROM customers WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->cus_id]);
                $customer = $stmt->fetch();

                $query = 'SELECT * FROM interviews WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->interview_id]);
                $service = $stmt->fetch();

                $query = 'SELECT * FROM places WHERE id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->place]);
                $place = $stmt->fetch();

                $query = 'SELECT * FROM additional_customers WHERE cus_id = ?';
                $stmt = $conn->prepare($query);
                $stmt->execute([$candidate->cus_id]);
                $add_cus = $stmt->fetchAll();

                $msg = getStatusMessage($status->id, $service->id, $candidate->cus_id);
                if (!empty($msg)) {
                    $msg = $msg->col;
                    // Create a DateTime object for Sweden's timezone
                    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                    $swedenTime = new DateTime('now', $swedenTimezone);
                    $currentTime = $swedenTime->format('H:i:s');
                    $dayOfWeek = date('N');

                    //matching time between 8am to 5pm
                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                        $body = replace($msg, $cus_name[$k], $can_name[$k] . " " . $candidate->surname, $cus_company[$k], $interview[$k], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID[$k], $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                        saveEmail("Customer", $cus_name[$k], $orderID[$k], $status->status . ' Message', $body, $cus_email[$k], $status->status);
                        if (!empty($add_cus)) { // additional customers email send
                            foreach ($add_cus as $ad_cu) {
                                saveEmail("Additional Customer", $ad_cu->name, $orderID[$k], $status->status . ' Message', $body, $ad_cu->email, $status->status);
                            }
                        }
                        if (isEmailAllowed($candidate->cus_id, $status->id)) {
                            $directory = "../security-report-uploads/";
                            $filename = $candidate->basic_investigation_result;
                            if (($status->variable == "approved" || $status->variable == "denied") && !empty($filename) && file_exists($directory . $filename) && $customer->send_security_report == 1) {
                                sendMail($body, $cus_email[$k], $cus_name[$k], $status->status, $directory . $filename);
                                if (!empty($add_cus)) { // additional customers email send
                                    foreach ($add_cus as $ad_cu) {
                                        sendMail($body, $ad_cu->email, $ad_cu->name, $status->status, $directory . $filename);
                                    }
                                }
                            } else {
                                sendMail($body, $cus_email[$k], $cus_name[$k], $status->status);
                                if (!empty($add_cus)) { // additional customers email send
                                    foreach ($add_cus as $ad_cu) {
                                        sendMail($body, $ad_cu->email, $ad_cu->name, $status->status);
                                    }
                                }
                            }
                        }

                        if ($status->variable == "canceledbycustomer") {
                            $body = $msg;
                            $body = replace($body, $cus_name[$k], $can_name[$k] . " " . $candidate->surname, $cus_company[$k], $interview[$k], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');

                            saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled');
                            sendMail($body, $candidate->email, $candidate->name, 'Order Canceled');
                        }
                    } else {
                        $body = replace($msg, $cus_name[$k], $can_name[$k] . " " . $candidate->surname, $cus_company[$k], $interview[$k], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $orderID[$k], $date, !empty($staff) ? $staff->email : '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');

                        saveEmail("Customer", $cus_name[$k], $orderID[$k], $status->status . ' Message', $body, $cus_email[$k], $status->status, "1");
                        if (!empty($add_cus)) { // additional customers email send
                            foreach ($add_cus as $ad_cu) {
                                saveEmail("Additional Customer", $ad_cu->name, $orderID[$k], $status->status . ' Message', $body, $ad_cu->email, $status->status, "1");
                            }
                        }
                        if ($status->variable == "canceledbycustomer") {
                            $body = $msg;
                            $body = replace($body, $cus_name[$k], $can_name[$k] . " " . $candidate->surname, $cus_company[$k], $interview[$k], !empty($staff) ? $staff->name : '', '', '', $status->status, $date, $candidate->order_id, '', '', $comment, $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                            saveEmail("Candidate", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Order Cancel Candidate', $body, $candidate->email, 'Order Canceled', "1");
                        }
                    }
                }
            }
        }
        flash("statusUpdated", "Status updated successfully!");
        redirect("candidates.php");
    } else {
        flash("statusUpdated", "Could not update status!", "errorMsg");
    }
}

$query = 'SELECT * FROM interviews WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->interview_id]);
$interview = $stmt->fetch();

$query = 'SELECT * FROM staff WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->staff_id]);
$staff = $stmt->fetch();

$query = 'SELECT * FROM service_categories';
$stmt = $conn->prepare($query);
$stmt->execute();
$servicesCats = $stmt->fetchAll();

?>
<?php flash("statusUpdated"); ?>
<div class="mx-lg-4 main-content">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h1 class="main-heading">Update Status</h1>
                    </div>

                    <form id="status-form" class="update-form" method="post">
                        <?php if (isset($_POST['delete'])) : ?>
                            <?php foreach ($_POST['delete'] as $can) : ?>
                                <input type="hidden" name="candidates[]" value="<?php echo $can ?>">
                                <?php
                                $query = 'SELECT * FROM candidates WHERE id = ?';
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$can]);
                                $candidate = $stmt->fetch();

                                $query = 'SELECT * FROM customers WHERE id = ?';
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$candidate->cus_id]);
                                $customer = $stmt->fetch();

                                $query = 'SELECT * FROM interviews WHERE id = ?';
                                $stmt = $conn->prepare($query);
                                $stmt->execute([$candidate->interview_id]);
                                $interview = $stmt->fetch();
                                ?>
                                <input type="hidden" name="cus_name[]" value="<?php echo $customer->name ?>">
                                <input type="hidden" name="cus_email[]" value="<?php echo $customer->email ?>">
                                <input type="hidden" name="can_name[]" value="<?php echo $candidate->name ?>">
                                <input type="hidden" name="booked[]" value="<?php echo $candidate->booked ?>">
                                <input type="hidden" name="cus_company[]" value="<?php echo $customer->company ?>">
                                <input type="hidden" name="order_id[]" value="<?php echo $candidate->order_id ?>">
                                <input type="hidden" name="interview[]" value="<?php echo $interview->title ?>">
                                <input type="hidden" name="interviewID[]" value="<?php echo $interview->id ?>">
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="row">
                            <div class="col-lg-6 mb-3" id="">
                                <label class="form-label" for="">Status</label>
                                <?php
                                $cusStatuses = explode(',', $customer->statuses);
                                ?>
                                <select class="form-control" name="status" id="change-status" style="">
                                    <?php if (!empty($servicesCats)) : ?>
                                        <?php foreach ($servicesCats as $servicesCat) : ?>
                                            <optgroup label="<?php echo $servicesCat->name ?>">
                                                <?php $statuses = getStatusesByService($servicesCat->id) ?>
                                                <?php foreach ($statuses as $key => $status) : ?>
                                                    <?php if (in_array($status->sID, $cusStatuses)) : ?>
                                                        <option data-status-variable="<?php echo $status->variable ?>" <?php echo $status->sID == $candidate->status ? 'selected' : '' ?> value="<?php echo $status->sID ?>"><?php echo $status->status ?></option>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>
                            <div class="col-lg-6 mb-3">
                                <label class="form-label">Date</label>
                                <input type="date" id="date" required name="date" class="form-control">
                            </div>

                            <!-- <div class="col-lg-12 mb-3 service_cost" hidden>
                                <label class="form-label">Travelling Cost</label>
                                <input type="number" id="travelling_cost" value="<?php echo $candidate->travel_cost ?>" class="form-control" name="travelling_cost" >
                            </div> -->

                            <div class="col-lg-12 mb-3">
                                <label class="form-label" for="comment">Comment</label>
                                <textarea name="comment" class="form-control" id="comment"></textarea>
                            </div>

                            <div class="col-lg-12 mb-3">
                                <label class="form-label">Upload Report</label>
                                <input name="report" type="file" class="form-control">
                            </div>
                        </div>
                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn-primary bg-primary" id="trigger_btn" onclick="check_date()">Update</button>
                            <button type="submit" name="update" id="form_submit_btn" class="btn-primary bg-primary report-btn report-btn-update" style="display:none">Update</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
</div>
</div>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Report Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <iframe src="" id="frame" width="100%" height="100%"></iframe>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php

include_once('includes/footer.php');

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script id="report-template" type="text/template">
    <div id="report-section">
        <div class="row">
            <div class="col-lg-12 mb-3 reason-col">
                <label class="form-label">Reason</label>
                <textarea id="reason" name="reason_denied" placeholder="Reason for denied" rows="3" class="w-100 sign-textarea form-control"></textarea>
            </div>

            <div class="col-lg-12 mb-3">
                <label class="form-label">Where did the interview take place?</label>
                <input type="text" name="city" id="city_report" placeholder="Where did the interview take place?" class="w-100 sign-input form-control" oninput="check_field()">
            </div>
        </div>

        <div class="row">

                        <div class="col-md-12" id="city_text_msg" style="display:none">
                <p class="text-danger">Please Fill the Interview Place Filed First !!</p>
            </div>
            <div class="col-lg-6 mb-3">
                <button type="button" id="preview" onclick="check_field()" data-bs-toggle="modal" data-bs-target="#exampleModal" class="btn-fill w-100 mt-4 mx-0 report-btn btn-primary bg-primary"><a>Preview Report</a></button>
            </div>

            <div class="col-lg-6 mb-3">
                <button type="button" id="generate" onclick="check_field()" class="btn-fill w-100 mt-4 mx-0 report-btn btn-primary bg-primary"><a>Generate Report</a></button>
            </div>

            <!--        <div class="col-lg-4 ">-->
            <!--            <button type="button" id="submit" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Submit Report</a></button>-->
            <!--        </div>-->
        </div>

        <div class="col-lg-12 mt-4">
            <p id="report-msg"></p>
        </div>
    </div>
</script>

<script>
    var candidate = <?php echo json_encode($candidate); ?>;
    var customer = <?php echo json_encode($customer); ?>;
    var staff = <?php echo json_encode($staff); ?>;

    function check_field() {
        var city = $('#city_report').val()
        if (city != '') {
            $('#preview').attr('disabled', false)
            $('#generate').attr('disabled', false)
            $('#trigger_btn').attr('disabled', false)
            $('#city_text_msg').hide()
        } else {
            $('#preview').attr('disabled', true)
            $('#generate').attr('disabled', true)
            $('#trigger_btn').attr('disabled', true)
            $('#city_text_msg').show()
        }
    }
    $(document).ready(function() {
        var statusVariable = $(this).find("option:selected").data("status-variable")
        $("#report-section").remove()
        if ((statusVariable === "approved" || statusVariable === "denied") && customer.send_security_report == 1) {
            $("#status-form").after($("#report-template").html())
            if (statusVariable === "approved") {
                $(".reason-col").remove()
            }
            check_field()
        }
    })

    function check_date() {
        if ($('#date').val() == '') {
            alert('Please Select Date First');
        } else {
            $('#form_submit_btn').click();
        }
        // if(!$('#travelling_cost').is(':hidden') && $('#travelling_cost').val() == '') {
        //     alert("Please Add Travelling Cost First");
        //     that.prop("disabled", false);
        //     $("update_status_msg").html("");
        //     $("report-msg").removeClass();
        //     $("report-msg").empty();
        //     return;
        // }

    }

    $("#change-status").on("change", function() {
        var statusVariable = $(this).find("option:selected").data("status-variable")
        $("#report-section").remove()
        if ((statusVariable === "approved" || statusVariable === "denied") && customer.send_security_report == 1) {
            $("#status-form").after($("#report-template").html())
            if (statusVariable === "approved") {
                $(".reason-col").remove()
            }
            check_field()
        } else {
            $('#trigger_btn').attr('disabled', false)
        }
    })

    // $("#send-report-status").on("change", function () {
    //     var statusVariable = $("#change-status").find("option:selected").data("status-variable")
    //     $("#report-section").remove()
    //     if((statusVariable === "approved" || statusVariable === "denied") && $(this).prop('checked')) {
    //         $("#status-form").after($("#report-template").html())
    //         if(statusVariable === "approved") {
    //             $(".reason-col").remove()
    //         }
    //     }
    // })

    window.jsPDF = window.jspdf.jsPDF;

    // $(window).on('load', function() {
    //     $("#preview").click()
    // })

    $("body").on("click", ".report-btn", function(e) {
        e.preventDefault();

        // Create new jsPdf instance
        const doc = new jsPDF()
        var x = 10;
        var y = 5;
        var leftMargin = 10;
        var rightMargin = 10;
        var statusVariable = $("#change-status").find("option:selected").data("status-variable")

        if ($(this).hasClass("report-btn-update")) {
            if ((statusVariable !== "approved" && statusVariable !== "denied") || customer.send_security_report == 0) {
                $("#status-form").submit()
                return;
            }
        }

        // Define header function
        const addHeader = function() {
            y = 5
            doc.addImage("../assets/images/vattenfall.png", 'PNG', (doc.internal.pageSize.width / 2) - 25, y, 50, 8)
        }

        // Define footer function
        const addFooter = function() {
            doc.setTextColor("#9298A0")
            doc.setFontSize(8)
            const date = new Date();
            const options = {
                day: 'numeric',
                month: 'short',
                year: 'numeric'
            };
            const formattedDate = date.toLocaleDateString('en-US', options);
            doc.text(formattedDate, leftMargin, doc.internal.pageSize.height - 5)

            doc.text("Confidentiality class: C3 - Restricted", doc.internal.pageSize.width - 56, doc.internal.pageSize.height - 10)
            doc.text("(after completion of the form)", doc.internal.pageSize.width - 56, doc.internal.pageSize.height - 5)
        }

        const addTable = function(caption, table) {
            doc.setFontSize(12);
            doc.setFont("Helvetica", "Bold");
            doc.text(caption, leftMargin, y)

            y += 3
            var data = [];
            table.forEach(function(row) {
                data.push({
                    key: row[0],
                    value: row[1]
                })
            })

            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    value: 'Value'
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 90,
                        fillColor: '#DBE5F1'
                    },
                },
                didParseCell: function(data) {

                }
            })
        }

        // Function to draw a checkmark symbol
        function drawCheckmark(doc, x, y) {
            var tickSize = 2; // Size of the tick lines
            doc.setLineWidth(0.5); // Set line width for tick lines
            doc.setDrawColor(0, 0, 0)
            doc.line(x, y, x + tickSize, y + tickSize); // Draw the first tick line
            doc.line(x - 0.2 + tickSize, y - 0.2 + tickSize, x + tickSize * 2, y - tickSize); // Draw the second tick line
        }

        const addTable2 = function(table) {
            y += 3;
            var data = [];
            table.forEach(function(row, index) {
                const rowData = {
                    key: row[0],
                    col1: row[1],
                    col2: row[2],
                    col3: row[3]
                };

                if (index > 2) {
                    rowData.key = {
                        content: rowData.key,
                        colSpan: 2
                    };
                    delete rowData.col1;
                }

                data.push(rowData);
            });

            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    col1: 'Col1',
                    col2: 'Col2',
                    col3: 'Col3'
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 90,
                        fillColor: '#DBE5F1'
                    },
                    col2: {
                        textColor: '#000000'
                    }
                },
                didParseCell: function(data) {
                    if (data.row.index > 2 && data.column.index === 1) {
                        data.cell.colSpan = 2;
                    }
                },
                didDrawCell: function(data) {
                    if (data.cell.section === "body" && data.column.index === 2 && data.row.index > 2) {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;

                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5

                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }
                }
            });
        }

        const addTable3 = function(table, status) {
            y += 3
            var data = [];
            table.forEach(function(row) {
                data.push({
                    key: row[0],
                    value: row[1]
                })
            })

            doc.autoTable({
                startY: y,
                margin: {
                    top: 25,
                    bottom: 25
                },
                head: [{
                    key: 'Key',
                    col1: 'Col1',
                    col2: "Col2"
                }],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: {
                        textColor: 0,
                        fontStyle: 'bold',
                        cellWidth: 120,
                        fillColor: '#DBE5F1'
                    },
                },
                didDrawCell: function(data) {
                    if (data.cell.section === "body" && data.column.index === 2 && status === "denied") {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;

                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5

                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }

                    if (data.cell.section === "body" && data.column.index === 1 && status === "approved") {
                        var cellWidth = data.cell.width;
                        var cellHeight = data.cell.height;
                        var cellX = data.cell.x;
                        var cellY = data.cell.y;

                        var tickX = cellX + (cellWidth / 2) - 2.5; // Calculate the position of the tick symbol
                        var tickY = cellY + (cellHeight / 2) - 2.5;
                        tickY += 2.5

                        drawCheckmark(doc, tickX, tickY); // Draw the checkmark symbol
                    }
                }
            })
        }

        function getTextWidth(text, fontSize) {
            // Text width in mm
            return (doc.getStringUnitWidth(text) * fontSize) / (72 / 25.6)
        }

        function pxToMm(px) {
            return px * 25.4 / 72;
        }

        // Add first page with header
        addHeader()
        addFooter()

        // Report Data
        y += 20;
        doc.setFontSize(14)
        doc.setTextColor("#000000")
        doc.setFont("Helvetica", 'Bold')
        doc.text("Result of the basic investigation", leftMargin, y)

        y += 10;
        doc.setFontSize(12)
        doc.setFont("Helvetica", '')
        var para = `Denna blankett ska användas vid återrapportering efter genomförd grundutredning.
        Med grundutredning enligt 3 kap. 3 § säkerhetsskyddslagen (2018:585) avses en utredning om personliga förhållanden av betydelse för säkerhetsprövningen. Utredningen ska omfatta betyg, intyg, referenser och uppgifter som den som prövningen gäller har lämnat samt andra uppgifter i den utsträckning det är relevant för prövningen. De detaljerade kraven återfinns i Vattenfalls kravspecifikation för Säkerhetsprövning.`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })

        y += 33;
        para = `This form must be used when reporting back after a basic investigation has been completed.
        With basic investigation according to ch. 3 Section 3 of the Swedish Protective Security Act (2018:585) refers to an investigation into personal circumstances of importance for the security vetting. The investigation shall include grades, certificates, references and information provided by the person to whom the examination applies, as well as other information to the extent that it is relevant to the examination. The detailed requirements can be found in Vattenfall's requirements specification for Security Vetting.`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })

        // Generate Table
        y += 35
        const table = [];
        var caption = "Beställare av säkerhetsprövningen (på Vattenfall)";
        table.push(["Namn & användarnamn / Name & User-ID", customer.name])
        table.push(["E-post / E-mail", customer.email])
        table.push(["Företag / Company", customer.company])
        addTable(caption, table)

        y += 28
        table.length = 0
        caption = "Bakgrundskontroll genomförd av / Basic investigation conducted by"
        table.push(["Namn / Name", "Staff at Recway AB"])
        table.push(["Telefonnummer / Telephone number", "08-551 063 97"])
        table.push(["E-post / E-mail", "info@recway.se"])
        table.push(["Företag / Company", "Recway AB"])
        addTable(caption, table)

        y += 37
        table.length = 0
        caption = "Intervjuarens uppgifter / Information about the interviewer"
        table.push(["Namn / Name", staff.name])
        table.push(["Telefonnummer / Telephone number", staff.phone])
        table.push(["E-post / E-mail", staff.email])
        table.push(["Företag / Company", "Recway AB"])
        addTable(caption, table)

        y += 37
        table.length = 0
        console.log(candidate)
        caption = "Kandidatens uppgifter / Information about the vetted candidate"
        table.push(["Namn / Name", candidate.name + " " + candidate.surname])
        table.push(["Personnummer (ååmmdd-xxxx) Birth date (yymmdd-xxxx)", candidate.security])
        table.push(["VASC-ID", candidate.vasc_id])
        addTable(caption, table)

        y += 35
        doc.setDrawColor(0, 0, 0)
        // doc.setFillColor(0,0,0)
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 25)
        para = `Svaren i personbedömningen vidimeras genom undertecknande på sida två.
Formuläret skickas via mail till: securityvetting@vattenfall.com
The answers in the vetting is authenticated by signing the form on page two.
The form sends by e-mail to: securityvetting@vattenfall.com`;
        doc.setFontSize(12)
        doc.setFont("Helvetica", "")
        doc.text(para, leftMargin + 5, y + 7, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })

        doc.addPage()
        addHeader()
        addFooter()

        y += 20;
        doc.setFontSize(14)
        doc.setTextColor("#000000")
        doc.setFont("Helvetica", 'Bold')
        doc.text("Result of the basic investigation", leftMargin, y)

        y += 7;
        doc.setFontSize(12)
        doc.setFont("Helvetica", '')
        var para = `Markera vilka bakgrundskontroller som genomförts. Detaljer om respektive kontroll finns i Vattenfalls kravspecifikation för säkerhetsprövning. Resultatet ska överlämnas till Vattenfall separat.
Select which of the background screening activities that have been performed. Details about the respective controls can be found in the Specification of requirements for Security Vetting. The results of the screening shall be handed over to Vattenfall separately.
`;
        doc.text(para, leftMargin, y, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2),
            align: 'left'
        })

        y += 26
        doc.setFontSize(8)
        doc.text("Not Applicable*", doc.internal.pageSize.width / 2, y)
        doc.setFontSize(8)
        doc.text("Ja/Yes", (doc.internal.pageSize.width / 2) + 31, y)
        doc.setFontSize(8)
        doc.text("Nej/No", (doc.internal.pageSize.width / 2) + 61, y)

        table.length = 0
        table.push([`Kontroll av CV (Curriculum Vitae)*
Verification of Resumé/CV`, "", "", ""])
        table.push([`Kontroll av referenser*
Verification of references/employer check`, "", "", ""])
        table.push([`Kontroll av betyg, intyg och diplom*
Verification of education, grades and diplomas`, "", "", ""])
        table.push([`Kreditupplysning (säkerhetsklass 2)
Credit check (security class 2-positions)`, "", "", ""])
        table.push([`Kontroll mot Kronofogden
Verification against the Enforcement authority / The Bailiff check`, "", "", ""])
        table.push([`Kontroll av folkbokföring
Verification of civil registration`, "", "", ""])
        table.push([`Kontroll av exponering på sociala medier
Verification of exposure on social medias`, "", "", ""])
        table.push([`Kontroll av öppna källor
Verification of open sources`, "", "", ""])
        table.push([`Kontroll av bolagsaktiviteter samt föreningsaktiviteter
Verification of corporate and associated activities`, "", "", ""])
        table.push([`Kontroll av rättsliga processer och historiska/pågående domar
Verification of legal processes and historical/ongoing judgements`, "", "", ""])
        addTable2(table)

        y = doc.lastAutoTable.finalY + 5;
        doc.setFontSize(10)
        doc.setFont("Helvetica", "Bold")
        doc.text("Resultat av säkerhetsprövningsintervjun ", leftMargin + 5, y)
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text("(markera med ett X)", leftMargin + 75, y)

        y += 5
        doc.setFontSize(10)
        doc.setFont("Helvetica", "Bold")
        doc.text("Result of the security vetting ", leftMargin + 5, y)
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text("(mark with an X) ", leftMargin + 55, y)

        y += 2
        doc.setFontSize(8)
        doc.text("Ja/Yes", (doc.internal.pageSize.width / 2) + 30, y)
        doc.setFontSize(8)
        doc.text("Nej/No", (doc.internal.pageSize.width / 2) + 60, y)

        table.length = 0
        table.push([`Det finns en god personlig kännedom om den prövade
There is a god knowledge about the vetted person`, "", ""])
        table.push([`Individen kan antas vara lojal mot de intressen som ska skyddas av säkerhetsskyddslagen
The individual can be assumed to be loyal to the interests to be protected by the Swedish Protective Security Act`, "", ""])
        table.push([`Individen kan i övrigt anses pålitlig från säkerhetssynpunkt.
The individual can otherwise be considered reliable from a security point of view.`, "", ""])
        addTable3(table, statusVariable)

        y = doc.lastAutoTable.finalY + 2;
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 15)
        para = $("#reason").val()
        doc.text("Om ”nej” ovan, ange anledning / If ”no” above, state reason: ", leftMargin + 2, y + 4)
        doc.line(leftMargin + 2, y + 5, leftMargin + 76, y + 5)
        doc.text(para ? para : "", leftMargin + 2, y + 8, {
            maxWidth: doc.internal.pageSize.width - (leftMargin * 2)
        })

        y += 19
        doc.text(`Datum för bakgrundskontroll /
Date for the background check`, leftMargin, y)
        var bcd = candidate.background_check_date
        var date = new Date(bcd);
        var options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        var formattedDate = date.toLocaleDateString('en-US', options);
        doc.setFont("Helvetica", "Bold")
        doc.text(bcd ? formattedDate : "N/A", leftMargin, y + 6)

        y += 10
        doc.setFont("Helvetica", "")
        var interview_date = candidate.booked
        date = new Date(interview_date)
        options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        formattedDate = date.toLocaleDateString('en-US', options);
        doc.text(`Datum för intervjun / Date for the interview`, leftMargin, y)
        doc.setFont("Helvetica", "Bold")
        doc.text(interview_date ? formattedDate : "N/A", leftMargin, y + 3)

        y -= 10
        doc.text(`Vidimering av genomförd grundutredning`, doc.internal.pageSize.width - 65, y)
        doc.setFont("Helvetica", "")
        doc.text(`Ort / City : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        var city = $("#city_report").val()
        doc.text(city ? city : "", doc.internal.pageSize.width - 51, y + 3)

        y += 3
        doc.setFont("Helvetica", "")
        var dateVal = $("#date").val()
        date = new Date(dateVal)
        options = {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        };
        formattedDate = date.toLocaleDateString('en-US', options);
        doc.text(`Datum / Date : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text(formattedDate, doc.internal.pageSize.width - 45, y + 3)

        y += 3
        doc.setFont("Helvetica", "")
        doc.text(`Signatur/ansvarig för genomförd
grundutredning : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text(staff.name ? staff.name : "", doc.internal.pageSize.width - 43, y + 6.5)

        y += 12
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text(`* Dessa kontroller utförs av Vattenfall i fall av nyrekryteringar. Vid konsult/entreprenörsuppdrag utförs de av leverantören själv.
   These controls are carried out by Vattenfall, in cases of recruitments. For consultants, they are carried out by the supplier itself.`, leftMargin, y)

        var blobPDF = new Blob([doc.output('blob')], {
            type: "application/pdf"
        })
        var blobURL = URL.createObjectURL(blobPDF)
        if ($(this).attr("id") === "preview") {
            $('#frame').attr('src', blobURL)
        } else if ($(this).attr("id") === "generate") {
            doc.save(candidate.order_id + ".pdf")
        } else {
            $("#report-msg").removeClass()
            $("#report-msg").empty()

            $("#report-msg").addClass("text-danger text-center")
            $("#report-msg").html(`<div class="lds-ring"><div></div><div></div><div></div><div></div></div>` + "Please wait while the report is being submitted...")

            // Convert the PDF blob to FormData object
            var formData = new FormData();
            formData.append('file', blobPDF, 'filename.pdf');
            formData.append('id', candidate.id);
            formData.append('filename', candidate.order_id);

            // Send the form data to the PHP script using AJAX
            $.ajax({
                url: '../security-report-upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response)
                    $("#report-msg").removeClass()
                    $("#report-msg").empty()

                    if (response.includes("Error")) {
                        $("#report-msg").addClass("text-error text-center")
                    } else {
                        $("#report-msg").addClass("text-success text-center")
                    }
                    $("#report-msg").text("File uploaded successfully!")
                    $("#status-form").submit()
                },
                error: function(xhr, status, error) {
                    console.log('Error uploading file: ' + error);
                }
            });

        }
    });

    // $(document).ready(function() {
    //     var selectElement = document.querySelector('#change-status'); // Replace 'select' with your actual select element selector
    //     var selectedValue = selectElement.value;
    //     var serviceCostDiv = document.querySelector('.service_cost');

    //     if (selectedValue == "4") {
    //         serviceCostDiv.removeAttribute('hidden');
    //     }
    //     toggleDivVisibility(selectElement);
    //     selectElement.addEventListener('change', function() {
    //         toggleDivVisibility(this);
    //     });
    // });

    // function toggleDivVisibility(selectElement) {
    //     var interviewPlace = <?php echo json_encode($interview->place); ?>;
    //     var selectedValue = selectElement.value;
    //     var serviceCostDiv = document.querySelector('.service_cost');

    //     if (selectedValue == "4" && interviewPlace == '1') {
    //         serviceCostDiv.removeAttribute('hidden');
    //     } else {
    //         serviceCostDiv.setAttribute('hidden', true);
    //     }
    // }
</script>