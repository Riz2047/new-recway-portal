<?php

include_once "includes/header.php";

?>
    
      <section>
        <div class="container mt-3">
          <div class="row">
            <p class="f-14 text-grey w-400 mb-0 pb-0">Edit Candidate Information</p>
            <div class="col-lg-12">
                <form action="" class="form">
                  <div class="form-tag mb-2">Personal Info</div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for=""> Name<span class="star text-danger">*</span></label>
                    <input type="text" placeholder="Enter Candidate Name" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-person"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="">Surname<span class="star text-danger">*</span></label>
                    <input type="text" placeholder="Enter Candidate Surname" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-person"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="">Email<span class="star text-danger">*</span></label>
                    <input type="text" placeholder="Enter Candidate Email" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-envelope"></i>
                    </div>
                  </div>
                  <div class="d-flex align-items-center form-row mb-3">
                    <label for="">Phone<span class="star text-danger">*</span></label>
                    <input type="text" placeholder="Enter Candidate Phone Number" class="w-100 from-input">
                    <div class="form-icon me-2">
                        <i class="bi bi-telephone"></i>
                    </div>
                  </div>
                    <div class="form-row mb-3 border-0">
                        <label for="" class="border-0">Documents<span class="star text-danger">*</span></label>
                            <div class="drop-zone">
                                <span class="drop-zone__prompt">
                                  <div class="d-flex flex-column justify-content-center align-items-center">
                                    <i class="bi bi-cloud-arrow-up-fill f-40"></i>
                                    Here you can upload several documents (Interview Templates, Documents or CV)
                                  </div>
                                </span>
                                <input type="file" name="myFile" class="drop-zone__input">
                              </div>
                      </div>
                    <div class="d-flex justify-content-end">
                      <a href="" class="form-btn">Send Order</a>
                    </div>
                  </form>
            </div>
            </div>
          </div>
      </section>

<?php

include_once "includes/footer.php";

?>