<?php

$activeLink = "all-orders";

include_once "includes/header.php";

if(!isset($_GET['id'])){
    redirect('orders.php');
}

$query = 'SELECT * FROM candidates WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$_GET['id']]);
$candidate = $stmt->fetch();

$query = "SELECT * FROM history WHERE order_id = {$_GET['id']}";
$stmt = $conn->prepare($query);
$stmt->execute();
$history = $stmt->fetchAll();

$query = 'SELECT * FROM places WHERE id = ?';
$stmt = $conn->prepare($query);
$stmt->execute([$candidate->place]);
$place = $stmt->fetch();

?>

      <section>
        <div class="container mt-3">
          <div class="row ">
            <div class="col-lg-3 mb-3">
                <div class="grey-box ">
                    <div class="candidate-profile mx-auto">
<!--                        <img src="assets/images/profile.jpg" alt="" class="img-fluid">-->
                        <h1 class="f-26 w-700 text-white m-0 p-0 font-secondary"><?php echo substr($candidate->name, 0,1) . substr($candidate->surname, 0,1) ?></h1>
                    </div>
                    <div class="candidate-info ">
                        <h1 class="f-16 w-700 text-black m-0 p-0 mt-2 text-center"><?php echo $candidate->name . " " . $candidate->surname ?></h1>
                        <p class=" f-14 text-grey w-500 mb-0 text-center">Order# <?php echo $candidate->order_id ?></p>
                        <?php $status = getStatusById($candidate->status) ?>
                        <div class="status-active px-3 py-1 f-18 my-2 mx-auto" style="background-color: <?php echo $status->color ?>; padding: 5px 8px; border-radius: 20px"><?php echo $status->status ?></div>
                        <div class="mt-3">
                            <p class="f-12 w-600 text-grey mb-0 pb-0 text-lg-start text-center">Security Number</p>
                            <p class="f-14 w-700 text-black text-lg-start text-center"><?php echo $candidate->security ?></p>

                            <p class="f-12 w-600 text-grey mb-0 pb-0 text-lg-start text-center">VASC ID</p>
                            <p class="f-14 w-700 text-black text-lg-start text-center"><?php echo !empty($candidate->vasc_id) ? $candidate->vasc_id : "Null" ?></p>

                            <p class="f-12 w-600 text-grey mb-0 pb-0 text-lg-start text-center">Email</p>
                            <p class="f-14 w-700 text-black text-lg-start text-center"><?php echo $candidate->email ?></p>

                            <?php if(!empty($candidate->cv) && !empty($candidate->report)): ?>
                                <div class="d-flex justify-content-center">
                                    <button class="blank-btn " data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="bi bi-cloud-download-fill me-2"></i> Download CV & Report</button>
                                </div>
                            <?php elseif(!empty($candidate->cv) && empty($candidate->report)): ?>
                                <div class="d-flex justify-content-center">
                                    <button class="blank-btn " data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="bi bi-cloud-download-fill me-2"></i> Download CV</button>
                                </div>
                            <?php elseif(empty($candidate->cv) && !empty($candidate->report)): ?>
                                <div class="d-flex justify-content-center">
                                    <button class="blank-btn " data-bs-toggle="modal" data-bs-target="#exampleModal"><i class="bi bi-cloud-download-fill me-2"></i> Download Report</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 px-lg-0 mb-3 ">
                <div class="grey-box ">
                    <div class="tab">
                        <button class="tablinks f-14 w-700 " id="defaultOpen" onclick="openCity(event, 'profile')">Profile</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'billing')">Billing Details</button>
                        <button class="tablinks f-14 w-700 " onclick="openCity(event, 'notes')">Additional Note</button>
                      </div>
                      
                      <div id="profile" class="tabcontent ">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12 p-0">
                                    <div class="mt-3">
                                        <?php
                                        $query = 'SELECT * FROM interviews WHERE id = ?';
                                        $stmt = $conn->prepare($query);
                                        $stmt->execute([$candidate->interview_id]);
                                        $interview = $stmt->fetch();
                                        ?>
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Service Type</p>
                                        <p class="f-14 w-700 text-black"><?php echo $interview->title ?>   </p>
                                    </div>

                                    <?php if(getStatusServiceCat($candidate->status)->service_cat_id == INTERVIEW_ID): ?>
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Interview Date</p>
                                        <p class="f-14 w-700 text-black"><?php echo !empty($candidate->booked) ? $candidate->booked : 'Null' ?></p>
                                    </div>
                                    <?php endif; ?>

                                  <div class="mt-3">
                                      <p class="f-12 w-600 text-grey mb-0 pb-0">Phone Number</p>
                                      <p class="f-14 w-700 text-black"><?php echo $candidate->phone ?></p>
                                  </div>

                                    <?php if($interview->id == 2 || $interview->id == 4 || $interview->id == 26): ?>
                                        <div class="mt-3">
                                            <p class="f-12 w-600 text-grey mb-0 pb-0">Place</p>
                                            <p class="f-14 w-700 text-black"><?php echo !empty($place) ? $place->name : "Null" ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                      </div>
                      
                      <div id="billing" class="tabcontent">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-6 p-0">
                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Invoice Recipient</p>
                                        <p class="f-14 w-700 text-black"><?php echo $candidate->referensperson ?></p>
                                    </div>

                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Invoice Reference</p>
                                        <p class="f-14 w-700 text-black"><?php echo $candidate->reference ?></p>
                                    </div>

                                    <div class="mt-3">
                                        <p class="f-12 w-600 text-grey mb-0 pb-0">Invoice Comment</p>
                                        <p class="f-14 w-700 text-black"><?php echo $candidate->comment ?></p>
                                    </div>
                                </div>
                           
                            </div>
                        </div>
                      </div>
                      
                      <div id="notes" class="tabcontent">
                        <div class="container">
                            <div class="row">
                                <div class="col-lg-12 p-0">
                                    <div class="mt-3">
                                        <p class="f-12 w-500 text-grey mb-0 pb-0">Only visible to you and us</p>
                                        <p class="f-14 w-500 text-black"><?php echo $candidate->note ?></p>

                                    </div>
                                </div>
                            </div>
                        </div>
                      </div>

                </div>
                  
            </div>
            <div class="col-lg-4">
              <div class="grey-box ">
                <div class="container p-0">
                  <div class="row">
                    <div class="col-lg-12">
                        <div class="wrapper">
                            <h1 class="f-16 w-700 text-black mb-0 pb-0 ">Order History</h1>
                            <ul class="sessions mt-2">
                                <?php if($history): ?>
                                    <?php foreach ($history as $h): ?>

                                        <li>
                                            <div class="time"><?php echo date("M d, Y h:i A", strtotime($h->date_time)) ?></div>
                                            <p class="p-0 m-0"><?php echo $h->desc ?></p>
                                            <i><small class="m-0 p-0"><?php echo !empty($h->comment) ? 'Comment: ' . $h->comment : '' ?></small></i>
                                        </li>

                                    <?php endforeach; ?>
                                <?php else: ?>

                                    <li>
                                        <div class="time"><?php echo "No record found" ?></div>
                                    </li>

                                <?php endif; ?>

                            </ul>
                          </div>
                    </div>
                  </div>
                  </div> 
            </div>
            </div>
           </div>
          </div>
      </section>

<!-- Modal -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Download PDFs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php if(!empty($candidate->cv)):
                    $documents = explode(',', $candidate->cv);
                    ?>
                    <p class="f-16 w-500 p-0 m-0">CV & other documents</p>

                    <?php foreach ($documents as $document): ?>
                        <?php if(!empty($document)): ?>
                            <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../uploads/<?php echo $document ?>" style="cursor: pointer" class="text-success open-doc"><i class="bi bi-filetype-pdf me-2"></i><small><?php echo $document ?></small></a></p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if(!empty($candidate->report)):
                    ?>
                    <p class="f-16 w-500 p-0 m-0 mt-2">Background Check Report</p>
                    <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../report-uploads/<?php echo $candidate->report ?>" style="cursor: pointer" class="text-success open-doc"><i class="bi bi-filetype-pdf me-2"></i><small><?php echo $candidate->report ?></small></a></p>
                <?php endif; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<?php

include_once "includes/footer.php";

?>

  <script>
    function openCity(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}
document.getElementById("defaultOpen").click();

    $(".open-doc").click(function (e) {
        e.preventDefault()

        var embed = "<embed width='100%' height='100%' src='" + $(this).attr("href") + "'/>"
        var x = window.open();
        x.document.open();
        x.document.write(embed);
        x.document.close();

        // window.open($(this).attr("href"), "_blank")
    })
  </script>