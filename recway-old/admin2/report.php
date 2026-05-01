<?php
echo "<pre>";
print_r($_GET);
echo "</pre>";
exit;
$activeLink = "candidates";

include_once('includes/header.php');

if (! isset($_GET['id'])) {
    redirect('index.php');
}

$candidate = findByQuery("SELECT candidates.*, staff.name AS staffName, interviews.title AS serviceTitle 
FROM candidates 
LEFT JOIN staff ON candidates.staff_id = staff.id 
INNER JOIN interviews ON candidates.interview_id = interviews.id 
WHERE candidates.id = {$_GET['id']}");

$report_html = findByQuery("SELECT * FROM reports_html WHERE candidate_id = {$_GET['id']} AND lang = 'en'");
$customer = findByQuery("SELECT company FROM customers WHERE id = {$candidate->cus_id}");

?>

<div class="mx-lg-4 main-content mt-2">
    <div class="container">

        <div class="row ">

            <div class="col-lg-12">
                <div class="table-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <h1 class="main-heading">Generate Report</h1>
                    </div>

                    <form action="process.php" method="post" class="update-form">
                        <div class="row">
                            <div id="report-html">
                                <?php if (! empty($report_html)): ?>
                                    <?php echo $report_html->report_data ?>
                                <?php else: ?>
                                    <div class="elements">
                                        <div class="col-lg-12 grab-elements">
                                            <p class="f-16 mb-0 pb-0 w-600">Result</p>
                                            <select name="result" id="" class="form-select mb-3">
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div class="introduction text grab-elements">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Introduction Heading</p>
                                                <input type="text" name="intro_heading" value="Introduction" required class="sign-input form-control w-100 mb-3 form-control" placeholder="Introduction heading ">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Introduction</p>
                                                <textarea name="intro" id="" required placeholder="Introduction" rows="3" class="w-100 sign-textarea mb-3">We at Recway AB are pleased to have been assigned by <?php echo $customer->company ?> to conduct a thorough <?php echo $candidate->serviceTitle ?>. This is an important process to ensure that the potential candidate is suitable and reliable for the position in question. By examining the individual's criminal history, education, employment history, and financial status, we can identify any warning signs and reduce the risk of misconduct. We are a company that places a great emphasis on integrity and security, and we will carry out this critical process with the utmost care and professionalism.</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>

                                        <div class="background text grab-elements">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Background Heading</p>
                                                <input type="text" value="Background" name="background_heading" required class="sign-input form-control w-100 mb-3" placeholder="Background heading ">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Background Text</p>
                                                <textarea name="background" id="" required placeholder="Background" rows="3" class="w-100 sign-textarea mb-3">Recway conducted a background check on <?php echo $candidate->name . " " . $candidate->surname ?>. This report contains a description of the assignment, a summary of our analysis and a summary of the information collected.</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>

                                        <div class="information text grab-elements">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Information & Facts Heading</p>
                                                <input type="text" value="Information and Facts" name="information_heading" required class="sign-input form-control w-100 mb-3" placeholder="Background heading ">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Information & Facts Text</p>
                                                <textarea name="information" id="" required placeholder="Information and Facts" rows="3" class="w-100 sign-textarea mb-3">Recway specializes in gathering information from various sources, including the internet, databases, registries, and public records held by authorities. Their methodology involves always collecting information from or validating with the original source, to ensure the accuracy of the information presented in their reports. Recway makes a concerted effort to verify any information that leads to notes by cross-checking it with other sources. It's important to note that information in various systems and databases, even those held by authorities, may have been recorded multiple times for various reasons, and Recway cannot be held responsible for any factual errors in the sources.Therefore, it's crucial to cross-check any divergent information with the candidate to ensure the most accurate information is presented in the report.</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>

                                        <div class="comment text mb-3 grab-elements" style="">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2">
                                                    <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber ui-sortable-handle"></i> Text Heading
                                                </p>
                                                <input type="text" name="comment_headings[]" required="" class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading " value="Summary">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                                                <textarea name="comment_description[]" id="" required="" placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions" spellcheck="true">After an extensive and professional background check, it can be determined that Niklas has a reliable and credible background without any deviations or negative indications. The personal data has been verified and confirmed as correct and convincing.

Financially, no suspicious or illegal financial transactions have been discovered in connection with Niklas' name, and his financial background is assessed as stable and without indications of possible dishonesty.

Within the legal history, no criminal or civil complaint, criminal investigation or ongoing dispute has been identified that can be linked to Niklas.

The driver's license check has shown that Niklas' driver's license status is intact, and no reports of revocation, suspension or invalidity of his driver's license have been found.

During the review of any vehicles registered in Niklas' name, no negative information or problems have been found in connection with the registration or ownership.

In terms of real estate holdings, no suspicious or irregular real estate deals have been discovered in connection with Niklas.

In terms of corporate involvement, all participations in companies by Niklas have been investigated, and there is no indication that he is involved in any frivolous or questionable corporate dealings.

The review of Nikla's involvement as a beneficial owner in various companies has not shown any indications that he is connected to any shady corporate structures.

Regarding internet exposure, all relevant parts of Niklas' exposure have been investigated, and no negative associations or rumors have been identified.

The social media activity has been carefully reviewed, and no deviations or irregularities have been noted on Nikla's social media platforms.

When studying Niklas' exposure in the news media, no negative events or publications that could cast doubt on his trustworthiness have been identified.

In summary, the results of the background check provide clear and convincing evidence of Niklas' reliability and suitability for various purposes that may require such a check. He can be considered a trustworthy individual with a clean and credible background.
</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>

                                        <div class="pageBreak mb-3 grab-elements mt-2" style="">
                                            <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i>
                                            <hr>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-page-break">Delete Page Break</button>
                                            </div>
                                        </div>

                                        <div class="profile grab-elements my-2">
                                            <p><i style="cursor: grab" class="bi bi-grip-vertical"></i> Profile</p>
                                            <select id="" class="form-select mb-3 summary-select profile-summary">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-5" style="overflow-x: auto" class="tables grab-elements">
                                            <p class="mt-2" data-table-id="table-5">
                                                <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                                <strong contenteditable="true">Economy</strong>
                                            </p>
                                            <table id="table-5" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Payment Remarks" class="form-control" name="table-5_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-5_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-5_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-5"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Debts with the enforcement agency" class="form-control" name="table-5_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-5_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-5_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-5"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-5">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-5">Delete Table</button>

                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="tableIncome" style="overflow-x: auto" class="tableIncome grab-elements">
                                            <p data-table-id="tableIncome" class="mt-2" >
                                                <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                                <strong contenteditable="true">Income details</strong>
                                            </p>
                                            <table id="tableIncome" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Column 1</th>
                                                    <th>Column 2</th>
                                                    <th>Column 3</th>
                                                    <th>Column 4</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Type of task" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" value="2022 (income year 2021)" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" value="2021 (income year 2020)" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" value="2020 (income year 2019)" class="form-control">
                                                    </td>
                                                    <td>
                                                        <!--                                                    <input type="text" value="Approval" class="form-control">-->
                                                        <select class="form-select" name="statusMain">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Taxed income" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="tableIncome_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Capital deficit" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="tableIncome_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="tableIncome">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="tableIncome">Delete Table</button>

                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-0" style="overflow-x: auto" class="tables grab-elements">
                                            <p class="mt-2" data-table-id="table-0">
                                                <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                                <strong contenteditable="true">Law</strong>
                                            </p>
                                            <table id="table-0" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Judgment district court" class="form-control" name="table-0_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-0_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-0_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Judgment Court of Appeal" class="form-control" name="table-0_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-0_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-0_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="The administrative courts" class="form-control" name="table-0_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-0_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-0_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-0">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-0">Delete Table</button>

                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-1" style="overflow-x: auto" class="tables grab-elements">
                                            <p class="mt-2" data-table-id="table-1">
                                                <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                                <strong contenteditable="true">Corporate commitment</strong>
                                            </p>
                                            <table id="table-1" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Board member and Chairman" class="form-control" name="table-1_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-1_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-1_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-1"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Historical corporate involvement" class="form-control" name="table-1_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-1_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-1_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-1"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-1">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-1">Delete Table</button>

                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-6" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-6">
                                                <i style="cursor: grabbing;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">Historical corporate involvement</strong>
                                            </p>
                                            <table id="table-6" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-6_col1[]" value="Picnic Sweden Trading Company">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-6_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-6_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-6"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-6_col1[]" value="Bergving, Niklas (Individual company)">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-6_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-6_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-6"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-6">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-6">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-10" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-10">
                                                <i style="cursor: grabbing;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">Driving license</strong>
                                            </p>
                                            <table id="table-10" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col1[]" value="Driving license qualification">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-10_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-10"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col1[]" value="Revocation of driver's license">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-10_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-10"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col1[]" value="Amount of vehicles">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-10_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-10_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-10"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-10">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-10">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-2" style="overflow-x: auto" class="tables grab-elements">
                                            <p class="mt-2" data-table-id="table-2">
                                                <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                                <strong contenteditable="true">Civil registration address</strong>
                                            </p>
                                            <table id="table-2" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" value="Civil registration address" class="form-control" name="table-2_col1[]">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-2_col2[]">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-2_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-2"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-2">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-2">Delete Table</button>

                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>

                                        <div data-table-id="table-7" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-7">
                                                <i style="cursor: grabbing;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">Vehicle control</strong>
                                            </p>
                                            <table id="table-7" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-7_col1[]" value="Passenger car RHY54K">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-7_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-7_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-7"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-7">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-7">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>
                                        <div data-table-id="table-8" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-8">
                                                <i style="cursor: grab;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">Property holding</strong>
                                            </p>
                                            <table id="table-8" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-8_col1[]" value="Ulricehamn Braxen 9">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-8_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-8_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-8"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-8">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-8">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>
                                        <div data-table-id="table-9" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-9">
                                                <i style="cursor: grab;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">PEP/Sanction</strong>
                                            </p>
                                            <table id="table-9" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-9_col1[]" value="PEP/Sanction">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-9_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-9_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-9"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-9">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-9">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>
                                        <div data-table-id="table-11" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-11">
                                                <i style="cursor: grab;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">CV check employer</strong>
                                            </p>
                                            <table id="table-11" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Employer 1">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Position">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Period of employment">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Reference person">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="-">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="-">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-" selected="selected">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Employer 2">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Position">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Period of employment">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Reference person">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="-">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="-">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-" selected="selected">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Employer 3">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Position">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Period of employment">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col1[]" value="Reference person">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-11_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-11_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-11"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-11">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-11">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>
                                        <div data-table-id="table-12" class="tables grab-elements" style="overflow-x: auto;">
                                            <p class="mt-2" data-table-id="table-12">
                                                <i style="cursor: grabbing;" class="bi bi-grip-vertical ui-sortable-handle"></i>
                                                <strong contenteditable="true">CV check higher post-secondary education</strong>
                                            </p>
                                            <table id="table-12" class="w-100" style="min-width: 722px">
                                                <thead>
                                                <tr>
                                                    <th>Head</th>
                                                    <th>Value</th>
                                                    <th>Status</th>
                                                </tr>
                                                <tr>
                                                    <th></th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col1[]" value="Institute 1">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-12_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-12"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col1[]" value="Training">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-12_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-12"></i>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col1[]" value="Degree ">
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" name="table-12_col2[]" value="">
                                                    </td>
                                                    <td>
                                                        <select class="form-select" name="table-12_col3[]">
                                                            <option value="Approved">Approved</option>
                                                            <option value="Deviation">Deviation</option>
                                                            <option value="Denied">Denied</option>
                                                            <option value="-">-</option>
                                                        </select>
                                                    </td>
                                                    <td>
                                                        <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-12"></i>
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                            <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px">
                                                <button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-12">Add Row</button>
                                                <button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-12">Delete Table</button>
                                            </div>
                                            <select id="" class="form-select mb-3 summary-select">
                                                <option value="None">Select Status</option>
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </div>
                                        <div class="comment text mb-3 grab-elements" style="">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2">
                                                    <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber ui-sortable-handle"></i> Text Heading
                                                </p>
                                                <input type="text" name="comment_headings[]" required="" class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading " value="SOCIAL MEDIA">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                                                <textarea name="comment_description[]" id="" required="" placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions" spellcheck="false">Facebook:
Niklas has an active account on Facebook, but we have observed that his activity on this platform is limited. During the current investigation period, we found no recent posts or interactions on his Facebook account. It seems that Niklas uses Facebook mainly to keep in touch with friends and acquaintances, without any pronounced activity to create his own content.

Instagram:
On Instagram, Niklas also has an account, but even here his activity is quite limited. No recently published images, posts or interactions have been noted during the reviewed time period. Niklas appears to be using Instagram to consume content and follow the activities of other users.

LinkedIn:
Linkedin is the social media platform where Niklas is most active. He has a well-maintained Linkedin account with regular posts and engaging updates. We noted several professional posts that received comments and likes from his network, demonstrating his active participation in industry discussions and professional networking.

TikTok:
We have also observed that Niklas has an account on Tiktok, where users share short videos. During the current investigation period, we noted no published videos on his account.

Deviations:
We have not found any discrepancies or irregularities on any of Nikla's social media accounts, including Facebook, Instagram, Linkedin and Tiktok. All accounts appear to be genuine and follow the general terms of use of the respective platform.

Conclusion:
Niklas is most active on Linkedin, where he regularly participates in professional discussions and networking. Facebook and Instagram are mainly used to keep in touch with friends and acquaintances, while activity on Tiktok is limited. No suspicious activity or deviations have been detected on any of Niklas' social media accounts. After an extensive and professional background check, it can be determined that Niklas has a reliable and credible background without any deviations or negative indications. The personal data has been verified and confirmed as correct and convincing.
</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>

                                        <div class="comment text mb-3 grab-elements" style="">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2">
                                                    <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber ui-sortable-handle"></i> Text Heading
                                                </p>
                                                <input type="text" name="comment_headings[]" required="" class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading " value="SOURCES">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                                                <textarea name="comment_description[]" id="" required="" placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions" spellcheck="true">Recway performs background checks where the number of sources checked varies depending on the scope of the check. Recway retrieves public data from several authorities and institutions in Sweden, including the Swedish Tax Agency, the Swedish Crown Enforcement Agency, the Central Student Support Committee, current universities, the Swedish Transport Agency, the Supreme Court, the Labor Court and all of Sweden's courts of appeal, district and administrative courts. To ensure a comprehensive check, they also include information from open sources on the internet as well as a selection of the most popular social media platforms in use today. By combining these sources, Recway provides a thorough and reliable background check.</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>
                                        <div class="comment text mb-3 grab-elements" style="">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2">
                                                    <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber ui-sortable-handle"></i> Text Heading
                                                </p>
                                                <input type="text" name="comment_headings[]" required="" class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading " value="RESPONSIBILITY">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                                                <textarea name="comment_description[]" id="" required="" placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions" spellcheck="true">The report may only be used by the Customer and may not be distributed to others. Recway is responsible
not against anyone other than the Customer for the content of the report or for other use of
the report than in connection with a background check. Recway is not responsible for any errors in them
sources we obtain information from.
källor vi hämtar uppgifter från. </textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>
                                        <div class="comment text mb-3 grab-elements">
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600 mt-2">
                                                    <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber ui-sortable-handle"></i> Text Heading
                                                </p>
                                                <input type="text" name="comment_headings[]" required="" class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading " value="METHOD">
                                            </div>
                                            <div class="col-lg-12">
                                                <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                                                <textarea name="comment_description[]" id="" required="" placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions" spellcheck="true">Recway carries out background checks through a structural acquisition of public data from authority registers to verify and supplement information that the candidate has provided about himself, for example in CVs. To verify CV credentials, Recway checks with Swedish universities, colleges and former employers.

After the information has been collected, Recway analyzes the material to identify any correlations and discrepancies. Recway uses a color coding where red means notable deviations, yellow means notable deviations, and green means no deviations have been found. It is important to point out that the deviation assessment is not made according to any predetermined standard or template, but each case is assessed individually.

A red or yellow marking means that one or more factors are of an unusual or noteworthy nature. It acts as a signal that something deviates from the normal and may need to be investigated more closely. Examples of notable deviations can be several recurring debts with the Swedish Enforcement Agency or several bankruptcies.

However, it is important to note that Recway cannot identify or discover all possible information about the candidate. The background checks provide a thorough assessment based on available information, but there is no guarantee that all relevant information can be obtained.</textarea>
                                                <select id="" class="form-select mb-3">
                                                    <option value="left">Left</option>
                                                    <option value="justify">Justify</option>
                                                </select>
                                                <select id="" class="form-select mb-3 summary-select">
                                                    <option value="None">Select Status</option>
                                                    <option value="Approved">Approved</option>
                                                    <option value="Deviation">Deviation</option>
                                                    <option value="Denied">Denied</option>
                                                </select>
                                            </div>
                                            <div class="col-lg-12 ps-0">
                                                <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-lg-12">
                                        <p class="f-16 mb-0 pb-0 w-600 mt-2">Table Caption</p>
                                        <input type="text" name="table-caption" id="table-caption" required class="sign-input form-control w-100 mb-3" placeholder="Table Caption ">
                                    </div>

                                    <!--                        <div class="row mx-auto">-->
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6">
                                            <button type="button" id="add-table" class="btn-primary-sm bg-primary mt-1 text-white w-100 mb-3 mx-0">Add Table</button>
                                        </div>

                                        <div class="col-lg-3 col-md-6 ">
                                            <button type="button" id="add-page-break" class="btn-primary-sm bg-primary mt-1 text-white w-100 mb-3 mx-0">Add Page Break</button>
                                        </div>

                                        <div class="col-lg-3 col-md-6   ">
                                            <button type="button" id="add-comment" class="btn-primary-sm bg-primary mt-1 text-white w-100 mb-3 mx-0">Add Text</button>
                                        </div>

                                        <div class="col-lg-3 col-md-6  ">
                                            <input type="file" accept="image/jpeg, image/png" id="image-input" style="display:none">
                                            <button type="button" id="add-image" class="btn-primary-sm bg-primary mt-1 text-white w-100 mb-3 mx-0">Add Image</button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="col-lg-4 ">
                                <button type="button" id="preview" data-bs-toggle="modal" data-bs-target="#backgroundReportModal" class="btn-primary-sm bg-primary w-100 mt-4 mx-0 report-btn"><a>Preview Report</a></button>
                            </div>

                            <div class="col-lg-4 ">
                                <button type="button" id="generate" class="btn-primary-sm bg-primary w-100 mt-4 mx-0 report-btn"><a>Generate Report</a></button>
                            </div>

                            <div class="col-lg-4 ">
                                <button type="button" id="submit" class="btn-primary-sm bg-primary w-100 mt-4 mx-0 report-btn"><a>Submit Report</a></button>
                            </div>

                            <div class="col-lg-12 ">
                                <button type="button" data-id="<?php echo $_GET['id'] ?>" id="save" class="btn-primary-sm bg-primary w-100 mt-4 mx-0"><a>Save Report</a></button>
                            </div>

                            <div class="col-lg-12 mt-4">
                                <p id="report-msg"></p>
                            </div>
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

<script>
    var tables = [];
    $(document).ready(function() {

        // Initialize a variable to store the maximum x
        var tableCount = 0;

        // Iterate through the div elements with class .tables
        $('.tables').each(function() {
            // Get the data-table-id attribute value
            var tableId = $(this).data('table-id');

            // Extract the numeric part from the attribute (assuming format is table-x)
            var match = tableId.match(/table-(\d+)/);

            // If there is a match and the extracted number is greater than the current max, update max
            if (match && parseInt(match[1]) > tableCount) {
                tableCount = parseInt(match[1]);
            }
        });

        tableCount += 1

        $('.elements').sortable({
            items: '.grab-elements',
            handle: '.bi-grip-vertical',
            axis: 'y',
        });

        $("#tableIncome").each(function () {
            var tableId = "tableIncome"
            var tableCaption = $(this).find("strong").text()
            // Add drag and drop event handlers
            $(`[data-table-id="${tableId}"]`).on('mousedown', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grabbing');
            });

            $(`[data-table-id="${tableId}"]`).on('mouseup', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grab');
            });

            // Add row event handler
            $('body').on('click', `.add-row[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                var rowHtml;
                if(tableId != 'tableIncome') {
                    rowHtml = '<tr><td><input type="text" class="form-control" name="' + tableId + '_col1[]"></td><td><input type="text" class="form-control" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Approved">Approved</option><option value="Deviation">Deviation</option><option value="Denied">Denied</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
                } else {
                    rowHtml = `<tr>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <select class="form-select" name="tableIncome_col3[]">
                                                <option value="Approved">Approved</option>
                                                <option value="Deviation">Deviation</option>
                                                <option value="Denied">Denied</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>`;
                }
                $(`#${tableId} tbody`).append(rowHtml);
            })

            $('body').on('click', `.delete-table[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                $(`#${tableId}`).remove()
                $(`.add-row[data-table-id="${tableId}"]`).remove()
                $(`p[data-table-id="${tableId}"`).remove()
                $(this).remove()


                // Find the index of the table with matching tableId
                const tableIndex = tables.findIndex(table => table.id === tableId);

                // Remove the table from the tables array
                if (tableIndex !== -1) {
                    tables = tables.filter((table, index) => index !== tableIndex);
                }
            })

            // Add delete row event handler
            $(`#${tableId}`).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
            });

            // Add table data to tables array
            tables.push({
                caption: tableCaption,
                id: tableId,
                data: []
            });
        })

        $(".tables").each(function () {
            var tableId = $(this).data("table-id")
            var tableCaption = $(this).find("strong").text()
            // Add drag and drop event handlers
            $(`[data-table-id="${tableId}"]`).on('mousedown', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grabbing');
            });

            $(`[data-table-id="${tableId}"]`).on('mouseup', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grab');
            });

            // Add row event handler
            $('body').on('click', `.add-row[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                var rowHtml;
                if(tableId != 'tableIncome') {
                    rowHtml = '<tr><td><input type="text" class="form-control" name="' + tableId + '_col1[]"></td><td><input type="text" class="form-control" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Approved">Approved</option><option value="Deviation">Deviation</option><option value="Denied">Denied</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
                } else {
                    rowHtml = `<tr>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control">
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>`;
                }
                $(`#${tableId} tbody`).append(rowHtml);
            })

            $('body').on('click', `.delete-table[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                $(`#${tableId}`).remove()
                $(`.add-row[data-table-id="${tableId}"]`).remove()
                $(`p[data-table-id="${tableId}"`).remove()
                $(this).closest(`div[data-table-id="${tableId}"`).remove()
                $(this).remove()


                // Find the index of the table with matching tableId
                const tableIndex = tables.findIndex(table => table.id === tableId);

                // Remove the table from the tables array
                if (tableIndex !== -1) {
                    tables = tables.filter((table, index) => index !== tableIndex);
                }
            })

            // Add delete row event handler
            $(`#${tableId}`).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
            });

            // Add table data to tables array
            tables.push({
                caption: tableCaption,
                id: tableId,
                data: []
            });
        })

        $('#add-table').click(function() {
            const tableCaption = $('#table-caption').val();
            const tableId = `table-${tableCount}`;
            tableCount++;

            // Create table HTML
            let tableHtml = `<div data-table-id="${tableId}" class="tables grab-elements" style="overflow-x: auto"><p class="mt-2" data-table-id="${tableId}"><i style="cursor: grab" class="bi bi-grip-vertical"></i> <strong contenteditable="true">${tableCaption}</strong></p>`;
            tableHtml += `<table id="${tableId}" class="w-100" style="min-width: 722px">`;
            tableHtml += '<thead><tr><th>Head</th><th>Value</th><th>Status</th></tr><th></th></thead>';
            tableHtml += '<tbody></tbody></table>';
            tableHtml += `<div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" ><button type="button" class="add-row btn-primary-sm bg-primary  text-white me-1" style="width:auto; min-width:130px "  data-table-id="${tableId}">Add Row</button>`;
            tableHtml += `<button type="button" class="delete-table btn-primary-sm bg-primary  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="${tableId}">Delete Table</button></div>
            <select id="" class="form-select mb-3 summary-select">
                <option value="None">Select Status</option>
                <option value="Approved">Approved</option>
                <option value="Deviation">Deviation</option>
                <option value="Denied">Denied</option>
            </select></div>`;

            // Append table to container
            $('.elements').append(tableHtml);
            $('#table-caption').val("")

            // Add drag and drop event handlers
            $(`[data-table-id="${tableId}"]`).on('mousedown', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grabbing');
            });

            $(`[data-table-id="${tableId}"]`).on('mouseup', '.bi-grip-vertical', function() {
                $(this).css('cursor', 'grab');
            });

            // Add row event handler
            $('body').on('click', `.add-row[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                const rowHtml = '<tr><td><input type="text" class="form-control" name="' + tableId + '_col1[]"></td><td><input type="text" class="form-control" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Approved">Approved</option><option value="Deviation">Deviation</option><option value="Denied">Denied</option><option value="-">-</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
                $(`#${tableId} tbody`).append(rowHtml);
            })

            $('body').on('click', `.delete-table[data-table-id="${tableId}"]`, function() {
                const tableId = $(this).data('table-id');
                $(`#${tableId}`).remove()
                $(`.add-row[data-table-id="${tableId}"]`).remove()
                $(`p[data-table-id="${tableId}"`).remove()
                $(this).closest(`div[data-table-id="${tableId}"`).remove()
                $(this).remove()


                // Find the index of the table with matching tableId
                const tableIndex = tables.findIndex(table => table.id === tableId);

                // Remove the table from the tables array
                if (tableIndex !== -1) {
                    tables = tables.filter((table, index) => index !== tableIndex);
                }
            })

            // Add delete row event handler
            $(`#${tableId}`).on('click', '.delete-row', function() {
                $(this).closest('tr').remove();
            });

            // Add table data to tables array
            tables.push({
                caption: tableCaption,
                id: tableId,
                data: []
            });
        });

        $('#add-comment').click(function () {
            var comment = `<div class="comment text mb-3 grab-elements"><div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i> Text Heading</p>
                            <input type="text" name="comment_headings[]" required class="sign-input form-control w-100 mb-3 comment_headings" placeholder="Text heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                            <textarea name="comment_description[]" id="" required placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions"></textarea>
                            <select id="" class="form-select mb-3">
                                <option value="left">Left</option>
                                <option value="justify">Justify</option>
                            </select>
                            <select id="" class="form-select mb-3 summary-select">
                                <option value="None">Select Status</option>
                                <option value="Approved">Approved</option>
                                <option value="Deviation">Deviation</option>
                                <option value="Denied">Denied</option>
                            </select>
                        </div>
                        <div class="col-lg-12 ps-0">
                            <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                        </div>
                        </div>`;

            $(".elements").append(comment)
        })

        $('#add-page-break').click(function () {
            var pageBreak = `<div class="pageBreak mb-3 grab-elements mt-2"><i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i><hr>
            <div class="col-lg-12 ps-0">
                            <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-page-break">Delete Page Break</button>
                        </div></div>`;

            $(".elements").append(pageBreak)
        })

        $('#add-image').click(function () {
            $('#image-input').click();
        })

        var formats = {"image/png": "PNG", "image/jpeg": "JPG", "image/jpg": "JPG"}
        $('#image-input').change(function() {
            const file = $('#image-input')[0].files[0];

            if (file) {
                if(!(file.type in formats)) {
                    alert("Image should be JPG or PNG!")
                } else {
                    // create a FileReader object to read the image file
                    const reader = new FileReader();
                    reader.addEventListener('load', () => {
                        // create a new Image object
                        const img = new Image();

                        // set the src attribute of the image
                        img.src = reader.result;

                        // wait for the image to load
                        img.addEventListener('load', () => {
                            // get the width, height, and format of the image
                            const width = img.width;
                            const height = img.height;
                            const format = file.type;

                            // create the image HTML
                            const image = `<div class="image mb-3 grab-elements"><div class="col-lg-12">
          <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical images-grabber"></i> Image</p>
          <img src="${reader.result}" data-width="${width}" data-height="${height}" data-format="${formats[format]}" alt="" width="200" class="ms-1">
        </div>
        <div class="col-lg-12 ps-0">
          <button type="button" class="btn-primary-sm bg-primary w-auto px-4 m-0 mt-1 text-white delete-image">Delete Image</button>
        </div>
        </div>`;

                            $(".elements").append(image)
                        });
                    });

                    reader.readAsDataURL(file);
                }
            }
        });


        // Add delete comment event handler
        $(`body`).on('click', '.delete-comment', function() {
            $(this).closest('.text').remove();
        });

        // Add delete image event handler
        $(`body`).on('click', '.delete-image', function() {
            $(this).closest('.image').remove();
        });

        // Add delete page break event handler
        $(`body`).on('click', '.delete-page-break', function() {
            $(this).closest('.pageBreak').remove();
        });
    });
</script>

<script>
    var candidate = <?php echo json_encode($candidate); ?>;

    window.jsPDF = window.jspdf.jsPDF;

    $(".report-btn").click(function () {
        // Create new jsPdf instance
        const doc = new jsPDF()
        var pageNumber = 1;
        var leftMargin = 15;
        var rightMargin = 15;
        var footerHeight = 7;
        var tableExists = false;
        var y = 35;
        var primaryColor = "#AC0206";
        var primaryColorRGB = [172, 2, 6];
        var secondaryColor = "#807D7D";
        var secondaryColorRGB = [127, 126, 126];
        var statusColors = {"Approved": [60, 179, 113], "Deviation": [255, 165, 0], "Denied": [255, 0, 0]};
        var statusColorsHex = {"Approved": "#3CB371", "Deviation": "#FFA500", "Denied": "#FF0000"};
        var statusImages = {"Approved": "approved.png", "Deviation": "deviation.png", "Denied": "denied.png"};

        // Define header function
        const addHeader = function() {
            console.log(doc.internal.pageSize.width)
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.setFillColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.triangle(0, 0, 0, 20, 200, 0, "F")

            doc.setDrawColor(secondaryColorRGB[0], secondaryColorRGB[1], secondaryColorRGB[2])
            doc.setFillColor(secondaryColorRGB[0], secondaryColorRGB[1], secondaryColorRGB[2])
            doc.triangle(doc.internal.pageSize.width, 0, doc.internal.pageSize.width, 30, doc.internal.pageSize.width - 60, 0, "F")

            // Set font size and style for header
            doc.setFontSize(8)
            doc.setFont("Helvetica", "Bold")

            doc.setTextColor("#ffffff")
            doc.text('Recway AB', 5, 5)
            doc.setTextColor("#D3D3D3")
            doc.text('info@recway.se', 5, 8)

            // Add date on right side
            doc.setTextColor("#ffffff")
            const date = new Date();
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            doc.text(formattedDate, doc.internal.pageSize.width - 20, 5)
        }

        // Define footer function
        const addFooter = function(pageNum = 0) {
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.setFillColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.rect(0, doc.internal.pageSize.height - 15, doc.internal.pageSize.width, 15, "F")

            // Set font size and style for header
            doc.setFontSize(10)
            doc.setFont("Helvetica", "Bold")

            doc.setTextColor("#ffffff")
            // doc.text(pageNum !== 0 ? pageNum.toString() : (doc.getCurrentPageInfo().pageNumber + 3).toString(), doc.internal.pageSize.width - 10, doc.internal.pageSize.height - footerHeight)
            // doc.text(pageNumber.toString(), doc.internal.pageSize.width - 10, doc.internal.pageSize.height - footerHeight)
        }

        function getTextWidth(text, fontSize) {
            // Text width in mm
            return (doc.getStringUnitWidth(text) * fontSize) / (72/25.6)
        }

        function pxToMm(px) {
            return px * 25.4 / 72;
        }


        function textFont(text) {
            switch (text) {
                case "title":
                    doc.setFontSize(18)
                    doc.setFont("Helvetica", 'Bold')
                    break;
                case "mainHeading":
                    doc.setFontSize(16)
                    doc.setFont("Helvetica", "Bold")
                    break;
                case "subHeading":
                    doc.setFontSize(12)
                    doc.setFont("Helvetica", "Bold")
                    break;
                case "normalText":
                    doc.setFontSize(12)
                    doc.setFont("Helvetica", "")
                    break;
                default:
                    doc.setFontSize(12)
                    doc.setFont("Helvetica", "")
                    break;
            }
        }

        function generateTable(table) {
            y = tableExists ? doc.lastAutoTable.finalY + 15 : y

            var tableHeight = 0;
            var tableHasSplit = false;
            // textFont("mainHeading")
            // doc.setTextColor("#000000")
            // doc.text(table.caption, leftMargin, y)

            var data = [];
            table.data.forEach(function (row) {
                if(row[0] !== "" || row[1] !== "") {
                    data.push({key: row[0], value: row[1], result: row[2]})
                }
            })

            y = y + 5
            doc.autoTable({
                startY: y,
                margin: {top: 30, bottom: 20},
                head: [{key: 'Key', value: 'Value', result: "Result"}],
                body: data,
                showHead: false,
                theme: 'plain',
                tableLineWidth: 0.1,
                // pageBreak: 'avoid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold', cellWidth: 81.5 },
                    value: {cellWidth: 80},
                    result: {textColor: "#ffffff", cellWidth: 20}
                },
                didParseCell: function(data) {
                    // data.cell.styles.lineWidth = 0.1
                    // data.cell.styles.hLineWidth = 0
                    // Check if cell is in last column
                    if (data.column.index === data.table.columns.length - 1) {
                        // Set background color
                        if(data.cell.raw !== "-") {
                            data.cell.styles.fillColor = statusColors[data.cell.raw];
                            data.cell.text = ""
                        } else {
                            data.cell.styles.textColor = [0, 0, 0];
                            if (data.row.index % 2 === 0) {
                                // Set background color to grey for even rows
                                data.cell.styles.fillColor = [240, 240, 240];
                            }
                        }
                    } else if (data.row.index % 2 === 0) {
                        // Set background color to grey for even rows
                        data.cell.styles.fillColor = [240, 240, 240];
                    }
                },
                didDrawPage: function (data) {
                    console.log(doc.getCurrentPageInfo())

                    tableExists = true

                    addHeader()
                    addFooter()
                    // check if the table has split
                    if (!tableHasSplit) {
                        // set the table caption
                        if(data.cursor.y < data.settings.startY) {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            var tableHead = data.settings.margin.top - 5
                            doc.text(table.caption.toUpperCase(), leftMargin, tableHead);
                            y = tableHead + 2
                            doc.setLineWidth(0.6)
                            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
                            doc.line(leftMargin, y, leftMargin + 10, y)
                            toc_headings.push(table.caption)
                            toc_numbers[table.caption] = doc.getCurrentPageInfo().pageNumber + 3
                        } else {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            var tableHead = data.settings.startY - 5
                            doc.text(table.caption.toUpperCase(), leftMargin, tableHead);
                            y = tableHead + 2
                            doc.setLineWidth(0.6)
                            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
                            doc.line(leftMargin, y, leftMargin + 10, y)
                            toc_headings.push(table.caption)
                            toc_numbers[table.caption] = doc.getCurrentPageInfo().pageNumber + 3
                        }
                        tableHasSplit = true;
                        y = data.cursor.y + 10
                    }

                }
            })

        }

        function generateTable2(table) {
            y = tableExists ? doc.lastAutoTable.finalY + 15 : y

            var tableHeight = 0;
            var tableHasSplit = false;
            // textFont("mainHeading")
            // doc.setTextColor("#000000")
            // doc.text(table.caption, leftMargin, y)

            var data = [];
            table.data.forEach(function (row) {
                data.push({col1: row[0], col2: row[1], col3: row[2], col4: row[3], col5: row[4]})
            })

            y = y + 5
            doc.autoTable({
                startY: y,
                margin: {top: 30, bottom: 20},
                head: [{col1: 'Col1', col2: 'Col2', col3: "Col3", col4: "Col4", col5: "Approval"}],
                body: data,
                showHead: false,
                theme: 'plain',
                tableLineWidth: 0.1,
                // pageBreak: 'avoid',
                // columnStyles: {
                //     col1: { textColor: 0, fontStyle: 'bold' }
                // },
                didParseCell: function(data) {
                    // Check if cell is in last column
                    if (data.row.index === 0) {
                        // Set background color
                        console.log(data.cell.styles)
                        data.cell.styles.fillColor = primaryColorRGB;
                        data.cell.styles.textColor = 255
                        data.cell.styles.fontStyle = 'bold'
                    } else if (data.column.index === data.table.columns.length - 1) {
                        // Set background color
                        data.cell.styles.fillColor = statusColors[data.cell.raw];
                        data.cell.styles.textColor = 255
                        data.cell.text = ""
                    } else if (data.row.index % 2 === 0) {
                        // Set background color to grey for even rows
                        data.cell.styles.fillColor = [240, 240, 240];
                    }
                },
                didDrawPage: function (data) {
                    console.log(data)
                    console.log(doc.getCurrentPageInfo())

                    tableExists = true

                    addHeader()
                    addFooter()
                    // check if the table has split
                    if (!tableHasSplit) {
                        // set the table caption
                        if(data.cursor.y < data.settings.startY) {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            var tableHead = data.settings.margin.top - 5
                            doc.text(table.caption.toUpperCase(), leftMargin, tableHead);
                            y = tableHead + 2
                            doc.setLineWidth(0.6)
                            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
                            doc.line(leftMargin, y, leftMargin + 10, y)
                            toc_headings.push(table.caption)
                            toc_numbers[table.caption] = doc.getCurrentPageInfo().pageNumber + 3
                        } else {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            var tableHead = data.settings.startY - 5
                            doc.text(table.caption.toUpperCase(), leftMargin, tableHead);
                            y = tableHead + 2
                            doc.setLineWidth(0.6)
                            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
                            doc.line(leftMargin, y, leftMargin + 10, y)
                            toc_headings.push(table.caption)
                            toc_numbers[table.caption] = doc.getCurrentPageInfo().pageNumber + 3
                        }
                        tableHasSplit = true;
                        y = data.cursor.y + 10
                    }

                },
            })

        }

        // Add first page with header
        addHeader()
        addFooter()

        var toc_headings = [];
        var toc_numbers = [];
        var summary_items = [];

        function addTOC() {
            var y = 30
            doc.setTextColor("#000000")
            textFont("mainHeading")
            doc.text("Table of Contents", (doc.internal.pageSize.width / 2) - 25, 30)
            for(let i = 0; i < toc_headings.length; i++) {
                textFont("subHeading")
                doc.setFontSize(10)
                y = i === 0 ? y + 5 : y
                doc.textWithLink(toc_headings[i].toUpperCase(), leftMargin + 10, y + 5, {pageNumber: toc_numbers[[toc_headings[i]]]});
                doc.setLineWidth(0.2)
                doc.setLineDash([1,1])
                doc.setDrawColor(0,0,0)
                doc.line(getTextWidth(toc_headings[i].toUpperCase(), doc.getFontSize()) + leftMargin + 10, y + 5, doc.internal.pageSize.width - rightMargin - 10, y + 5)
                doc.textWithLink(toc_numbers[[toc_headings[i]]].toString(), doc.internal.pageSize.width - rightMargin - 10, y + 5, {pageNumber: toc_numbers[[toc_headings[i]]]});
                y += 5
            }
        }

        function addInformation() {
            var orderID = candidate.order_id;
            var serviceTitle = candidate.serviceTitle;
            var result = $("select[name='result']").val()

            doc.addImage("../assets/images/logo.png", 'PNG', leftMargin, y, 50, 17)
            textFont("subHeading")
            doc.setTextColor(primaryColor)

            y = y + 3
            // doc.text("Order Information", doc.internal.pageSize.width - getTextWidth("Order Information", doc.getFontSize()) - rightMargin, y)

            // y = y + 5
            doc.setFontSize(10)
            doc.setFont("Helvetica", "")
            doc.setTextColor(secondaryColor)

            doc.text("Order# " + orderID, doc.internal.pageSize.width - getTextWidth("Order# " + orderID, doc.getFontSize()) - rightMargin, y)

            // doc.setLineWidth(0.5)
            // doc.setDrawColor(statusColors[result][0], statusColors[result][1], statusColors[result][2])
            // doc.rect(doc.internal.pageSize.width - getTextWidth("Result: " + result, doc.getFontSize()) - rightMargin - 12, y + 3, getTextWidth("Result: " + result, doc.getFontSize()) + 12, 8)

            // y = y + 8
            // textFont("subHeading")
            // doc.setTextColor(statusColorsHex[result])
            // doc.text("Result: " + result, doc.internal.pageSize.width - getTextWidth("Result: " + result, doc.getFontSize()) - rightMargin - 2, y)

            doc.addImage("../assets/images/" + statusImages[result], 'PNG', doc.internal.pageSize.width - 50, y, 45, 45)

            y = y + 20 + 10
            textFont("title")
            doc.setTextColor("#000000")
            doc.text(serviceTitle, leftMargin, y)
            y = y + 5
            doc.setLineWidth(1)
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            // doc.line(leftMargin, y, leftMargin + 50, y)
            doc.addImage("../assets/images/line.png", 'PNG', leftMargin, y, 45, 2)

            y += 10
        }

        addInformation()

        function addProfile() {
            // doc.addPage()
            // pageNumber++
            // addHeader()
            // addFooter()

            y = 35
            textFont("mainHeading")
            doc.setTextColor("#000000")
            doc.text("Personal Information".toUpperCase(), leftMargin, y)
            y = y + 2
            doc.setLineWidth(0.6)
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.line(leftMargin, y, leftMargin + 10, y)
            toc_headings.push("Personal Information")
            toc_numbers["Personal Information"] = doc.getCurrentPageInfo().pageNumber + 3
            summary_items["Personal Information"] = $(".profile-summary").val()

            var data = [
                {key: "Name", value: candidate.name + " " + candidate.surname},
                {key: "Email", value: candidate.email},
                {key: "Phone", value: candidate.phone},
                {key: "Invoice Recipient", value: candidate.referensperson},
                {key: "Invoice Reference", value: candidate.reference},
                {key: "Service Type", value: candidate.serviceTitle},
                {key: "SSN", value: candidate.security},
                {key: "Staff", value: candidate.staffName !== null ? candidate.staffName : "Not assigned"}
            ];

            if (candidate.vasc_id !== null && candidate.vasc_id !== '') {
                data.push({ key: "VASC ID", value: candidate.vasc_id });
            }

            y = y + 5
            doc.autoTable({
                startY: y,
                head: [{key: 'Key', value: 'Value'}],
                body: data,
                showHead: false,
                theme: 'grid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold' },
                },
                didParseCell: function(data) {
                    if (data.row.index % 2 === 0) { // Check if odd row
                        data.cell.styles.fillColor = [240, 240, 240] // Set background color to grey
                    }
                }
            })
        }

        $(".elements").find(".grab-elements").each(function () {
            // console.log($(".elements .grab-elements"))
            if($(this).hasClass("pageBreak")) {
                doc.addPage()
                addHeader()
                pageNumber++
                addFooter()
                y = 35
                tableExists = false
            } else if($(this).hasClass("profile")) {
                addProfile();
                doc.addPage()
                pageNumber++
                addHeader()
                addFooter()
            } else if($(this).hasClass("tableIncome")) {
                const tableData = [];
                const table = {}

                const tableCaption = $(this).find('strong').text()
                summary_items[tableCaption] = $(this).find(".summary-select").val()

                // Get table rows data
                $(this).find('tbody tr').each(function() {
                    const rowData = [];

                    // Get row cells data
                    $(this).find('td input').each(function() {
                        rowData.push($(this).val());
                    });

                    $(this).find('td select').each(function() {
                        rowData.push($(this).val());
                    });

                    tableData.push(rowData);
                });

                table.caption = tableCaption
                table.data = tableData

                generateTable2(table)
            } else if($(this).hasClass("text")) {
                var heading = $(this).find('input').val()
                var description = $(this).find('textarea').val()
                var align = $(this).find('select:not(.summary-select)').val()
                tableExists = false

                textFont("normalText")

                var lines = doc.splitTextToSize(description, doc.internal.pageSize.width);
                var lineHeight = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
                var height = lines.length * lineHeight + 10 + 5;

                if(lines.length > 4) {
                    height -= 5
                }

                if(height > doc.internal.pageSize.height - footerHeight - (y + 5)) {
                    doc.addPage()
                    pageNumber++
                    addHeader()
                    addFooter()
                    y = 35
                }

                // Add Heading
                textFont("mainHeading")
                doc.setTextColor("#000000")
                doc.text(heading.toUpperCase(), leftMargin, y)
                y = y + 2
                doc.setLineWidth(0.6)
                doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
                doc.line(leftMargin, y, leftMargin + 10, y)
                toc_headings.push(heading)
                toc_numbers[heading] = doc.getCurrentPageInfo().pageNumber + 3
                summary_items[heading] = $(this).find(".summary-select").val()

                // Add Description
                y += 7
                textFont("normalText")
                doc.text(description, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align})
                y += height - 5
            } else if($(this).hasClass("tables")) {
                const tableData = [];
                const table = {}

                const tableCaption = $(this).find('strong').text()
                summary_items[tableCaption] = $(this).find(".summary-select").val()

                // Get table rows data
                $(this).find('tbody tr').each(function() {
                    const rowData = [];

                    // Get row cells data
                    $(this).find('td input').each(function() {
                        rowData.push($(this).val());
                    });

                    $(this).find('td select').each(function() {
                        rowData.push($(this).val());
                    });

                    tableData.push(rowData);
                });

                table.caption = tableCaption
                table.data = tableData

                generateTable(table)
            } else if($(this).hasClass("image")) {
                var image = $(this).find("img")
                var imageSrc = image.attr("src")
                var imageWidth = pxToMm(image.data("width"))
                var imageHeight = pxToMm(image.data("height"))
                var imageFormat = image.data("format")

                console.log(imageSrc)

                var maxWidth = 130; // set the maximum width of the image in mm
                var maxHeight = 130; // set the maximum height of the image in mm

                // calculate the aspect ratio of the image
                var aspectRatio = imageWidth / imageHeight;

                // calculate the new width and height of the image while maintaining the aspect ratio
                if (imageWidth > maxWidth) {
                    imageWidth = maxWidth;
                    imageHeight = imageWidth / aspectRatio;
                }
                if (imageHeight > maxHeight) {
                    imageHeight = maxHeight;
                    imageWidth = imageHeight * aspectRatio;
                }

                tableExists = false

                if(doc.internal.pageSize.height - y - 10 < imageHeight) {
                    doc.addPage()
                    addHeader()
                    addFooter()
                    y = 35
                    doc.addImage(imageSrc, imageFormat, ((doc.internal.pageSize.width - imageWidth) / 2), y, imageWidth, imageHeight)
                    y += imageHeight + 15
                } else {
                    y += 5
                    doc.addImage(imageSrc, imageFormat, ((doc.internal.pageSize.width - imageWidth) / 2), y, imageWidth, imageHeight)
                    y += imageHeight + 15
                }
            }
        })

        function addSummary() {
            doc.insertPage(1)

            var imgWidth = doc.internal.pageSize.getWidth(); // Get the width of the page
            var imgHeight = doc.internal.pageSize.getHeight(); // Get the height of the page

            // Add the image to the document
            doc.addImage("../assets/images/reportbg3.png", 'JPEG', 0, 0, imgWidth, imgHeight);

            addHeader()
            addFooter()
            y = 30
            doc.setTextColor("#ffffff")
            textFont("mainHeading")
            doc.text("Summary".toUpperCase(), leftMargin, y)
            textFont("subHeading")
            doc.text("Overview by subarea", leftMargin, y+7)
            toc_headings.unshift("Summary")
            toc_numbers["Summary"] = doc.getCurrentPageInfo().pageNumber + 2
            y += 17
            for(var i = 0; i < toc_headings.length; i++) {
                if(toc_headings[i] !== "Summary" && summary_items[toc_headings[i]] !== "None") {
                    console.log(i)
                    console.log(toc_headings[i])
                    console.log(summary_items[toc_headings[i]])
                    var value = summary_items[toc_headings[i]]
                    doc.setFontSize(10)
                    doc.setLineWidth(5)
                    doc.setDrawColor(statusColors[value][0], statusColors[value][1], statusColors[value][2])
                    doc.line(leftMargin, y, leftMargin + 50, y)
                    doc.text(toc_headings[i].toUpperCase(), leftMargin + 55, y  + 1)
                    y += 10
                }
            }

            textFont("mainHeading")
            y += 5
            doc.text("DEFINITIONS", leftMargin, y)
            y += 7
            textFont("subHeading")
            doc.text("DEFINITION", leftMargin, y)
            doc.text("COLOR CODE", doc.internal.pageSize.getWidth() - rightMargin - 22, y)
            y += 5
            doc.setLineWidth(0.6)
            doc.setDrawColor(255, 255, 255)
            doc.line(leftMargin, y, doc.internal.pageSize.getWidth() - rightMargin, y)

            y += 10
            doc.text("APPROVED", leftMargin, y + 2)
            doc.setLineWidth(10)
            doc.setDrawColor(61, 179, 112)
            doc.line(doc.internal.pageSize.getWidth() - rightMargin - 10, y, (doc.internal.pageSize.getWidth() - rightMargin), y)

            y += 15
            doc.text("DEVIATION", leftMargin, y + 2)
            doc.setLineWidth(10)
            doc.setDrawColor(255, 166, 0)
            doc.line(doc.internal.pageSize.getWidth() - rightMargin - 10, y, (doc.internal.pageSize.getWidth() - rightMargin), y)

            y += 15
            doc.text("DENIED", leftMargin, y + 2)
            doc.setLineWidth(10)
            doc.setDrawColor(172, 2, 6)
            doc.line(doc.internal.pageSize.getWidth() - rightMargin - 10, y, (doc.internal.pageSize.getWidth() - rightMargin), y)
        }

        addSummary()

        doc.insertPage(1)
        addHeader()
        addFooter(1)
        addTOC()

        doc.insertPage(1)
        // Add image on first page
        var imgWidth = doc.internal.pageSize.getWidth(); // Get the width of the page
        var imgHeight = doc.internal.pageSize.getHeight(); // Get the height of the page

        // Add the image to the document
        doc.addImage("../assets/images/reportbg2.jpg", 'JPEG', 0, 0, imgWidth, imgHeight);

        doc.setTextColor("#ffffff")
        doc.setFontSize(20);
        doc.text("BACKGROUND CHECK", leftMargin, 80)
        doc.setFontSize(32)
        doc.text(candidate.serviceTitle, leftMargin, 95)
        doc.setDrawColor(255, 255, 255)
        doc.setLineWidth(1)
        doc.line(leftMargin, 100, leftMargin + 20, 100)

        // Function to add the total number of pages for each page group
        var totalPages = [];
        function addTotalPages() {
            var pageCount = doc.internal.getNumberOfPages();
            for (var i = 0; i < pageCount; i++) {
                totalPages.push(doc.internal.getNumberOfPages());
                doc.setPage(i + 1); // Switch to the page to write the total number of pages
                doc.setFontSize(10)
                doc.setFont("Helvetica", "Bold")

                doc.setTextColor("#ffffff")
                doc.text((i + 1) + " of " + totalPages[i], doc.internal.pageSize.width - 18, doc.internal.pageSize.height - 6);
            }
        }

        addTotalPages()

        var blobPDF = new Blob([doc.output('blob')], {type: "application/pdf"})
        var blobURL = URL.createObjectURL(blobPDF)
        if($(this).attr("id") === "preview") {
            $('#frame').attr('src', blobURL)
            // var string = doc.output('datauristring');
            // var embed = "<embed width='100%' height='100%' src='" + string + "'/>"
            // var x = window.open();
            // x.document.open();
            // x.document.write(embed);
            // x.document.close();
        } else if($(this).attr("id") === "generate") {
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
                url: '../report-upload.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    console.log(response)
                    $("#report-msg").removeClass()
                    $("#report-msg").empty()

                    if(response.includes("Error")) {
                        $("#report-msg").addClass("text-error text-center")
                    } else {
                        $("#report-msg").addClass("text-success text-center")
                    }
                    $("#report-msg").text("File uploaded successfully!")
                },
                error: function(xhr, status, error) {
                    console.log('Error uploading file: ' + error);
                }
            });

        }
    })

    $(document).on("keyup change", "input", function () {
        $(this).attr("value", $(this).val());
    });

    $(document).on("keyup change", "textarea", function () {
        $(this).text($(this).val());
    });

    $(document).on("change", "select", function () {
        $(this).find("option:selected").attr("selected", true);
    });

    $("#save").click(function () {
        // console.log($(".tableIncome").html())
        // return 0;

        var html = $("#report-html").html();

        $("#report-msg").removeClass()
        $("#report-msg").addClass("text-center text-danger")
        $("#report-msg").text("Please wait while saving the report...")

        $.ajax({
            type: "POST",
            url: "../includes/ajax.php",
            data: {id: $(this).data('id'), formHtml: html, lang: 'en' },
            success: function(response) {
                $("#report-msg").removeClass()
                $("#report-msg").addClass("text-center text-success")
                $("#report-msg").text("Report saved successfully!")
            },
            error: function(xhr, status, error) {
                console.error("Error storing form HTML: " + error);
            }
        });
    })
</script>
