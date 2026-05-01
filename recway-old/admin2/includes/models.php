<!-- Download PDF Modal -->
<div class="modal fade" id="pdfModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Download PDFs</h5>

                <button type="button" class="btn-close" data-bs-target="#content-modal" data-bs-toggle="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <?php if (! empty($candidate->cv)) :

                    $documents = explode(',', $candidate->cv);

                    ?>



                    <?php foreach ($documents as $document) : ?>

                        <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../uploads/<?php echo $document ?>" style="cursor: pointer" class="text-success"><?php echo $document ?></a></p>

                    <?php endforeach; ?>



                <?php else : ?>

                    <p class="mb-0 w-100 f-18 px-2 py-3">No Document</p>

                <?php endif; ?>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-target="#content-modal" data-bs-toggle="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<!-- Download Report Modal -->

<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Download Report</h5>

                <button type="button" class="btn-close" data-bs-target="#content-modal" data-bs-toggle="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <?php if (! empty($candidate->report)) :

                    ?>

                    <p style="overflow: hidden;white-space: nowrap; text-overflow: ellipsis" class="mb-0 w-100 f-18 p-0 pt-1"><a target="_blank" href="../report-uploads/<?php echo $candidate->report ?>" style="cursor: pointer" class="text-success"><?php echo $candidate->report ?></a></p>



                <?php else : ?>

                    <?php print_r($candidate->report); ?>

                    <p class="mb-0 w-100 f-18 px-2 py-3 no-report">No Report Submitted</p>

                <?php endif; ?>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-target="#content-modal" data-bs-toggle="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<!-- Security Report Modal -->

<div class="modal fade" id="securityReportModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Report Preview</h5>

                <button type="button" class="btn-close" data-bs-target="#content-modal" data-bs-toggle="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <iframe src="" id="frame" width="100%" height="100%"></iframe>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-target="#content-modal" data-bs-toggle="modal">Close</button>

            </div>

        </div>

    </div>

</div>


<!-- Place Selection Modal - Higher z-index to appear on top -->
<div class="modal fade" id="placeSelectModal" tabindex="-1" aria-labelledby="placeSelectModalLabel" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog" style="z-index: 1061;">
        <div class="modal-content" style="z-index: 1062;">
            <div class="modal-header">
                <h5 class="modal-title" id="placeSelectModalLabel">Select Interview Place</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="selected_place" class="form-label">Place</label>
                    <select name="selected_place" id="selected_place" class="form-control" required>
                        <option value="">Please select a place</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitPlaceSelection">Submit</button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Ensure place selection modal appears on top of other modals */
    #placeSelectModal.modal {
        z-index: 1060 !important;
    }
    #placeSelectModal.modal.show {
        z-index: 1060 !important;
    }
    /* Ensure backdrop for placeSelectModal is on top */
    body.modal-open .modal-backdrop:last-of-type {
        z-index: 1000 !important;
    }
    /* When placeSelectModal is open, ensure it's on top */
    body:has(#placeSelectModal.show) #placeSelectModal {
        z-index: 1060 !important;
    }
    /* Alternative approach for better browser support */
    .modal.show#placeSelectModal {
        z-index: 1060 !important;
    }
</style>




<!-- Background Report Content Modal -->

<div class="modal fade" id="backgroundReportContentModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Generate Report</h5>

                <button type="button" class="btn-close closing_btns" data-bs-target="#content-modal" data-bs-toggle="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">



            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary closing_btns" data-bs-target="#content-modal" data-bs-toggle="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<!-- Background Report Modal -->

<div class="modal fade" id="backgroundReportModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Report Preview</h5>

                <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#backgroundReportContentModal" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <iframe src="" id="backgroundReportframe" width="100%" height="100%"></iframe>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#backgroundReportContentModal" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<!-- Background Report - SV Modal -->

<div class="modal fade" id="backgroundReportModal2" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="exampleModalLabel">Report Preview</h5>

                <button type="button" class="btn-close" data-bs-toggle="modal" data-bs-target="#backgroundReportContentModal" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

                <iframe src="" id="backgroundReportframe2" width="100%" height="100%"></iframe>

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-toggle="modal" data-bs-target="#backgroundReportContentModal" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>



<!--Customer Content Modal-->

<div class="modal" id="customer-modal" tabindex="-1">

    <div class="modal-dialog modal-fullscreen">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

            </div>

        </div>

    </div>

</div>



<!--Main Content Modal-->

<div class="modal" id="content-modal" tabindex="-1">

    <div class="modal-dialog modal-fullscreen modal-xl">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

            </div>

        </div>

    </div>

</div>

<!-- Updates Centered Modal -->
<div class="modal fade" id="updates-modal" tabindex="-1" aria-labelledby="updatesModalLabel" aria-hidden="true">

    <div class="modal-dialog">

        <div class="modal-content">

            <div class="modal-header">

                <h5 class="modal-title" id="updatesModalLabel"></h5>

                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>

            </div>

            <div class="modal-body">

            </div>

            <div class="modal-footer">

                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

            </div>

        </div>

    </div>

</div>