<?php

include_once ('includes/header.php');

if(!isset($_GET['id'])){
    redirect('index.php');
}

$candidate = findByQuery("SELECT candidates.*, staff.name AS staffName, interviews.title AS serviceTitle 
FROM candidates 
LEFT JOIN staff ON candidates.staff_id = staff.id 
INNER JOIN interviews ON candidates.interview_id = interviews.id 
WHERE candidates.id = {$_GET['id']}");

?>


    <div class="row">

        <div class="col-lg-12">
            <div class="main-heading  w-100">
                <h1 class=" mt-3 mb-4">Generate Report</h1>
            </div>
            <div class="box shadow">
                <?php echo isset($message) ? $message : '' ?>
                <form action="process.php" method="post">
                    <div class="row p-0 m-0">
                        <div class="elements">
                            <div class="mb-2">
                                <a href="report.php?id=<?php echo $_GET['id'] ?>">Translate to English</a>
                            </div>
                            <div class="col-lg-12 grab-elements">
                                <p class="f-16 mb-0 pb-0 w-600">Result</p>
                                <select name="result" id="" class="form-select mb-3">
                                    <option value="Godkänd">Godkänd</option>
                                    <option value="Avvikelse">Avvikelse</option>
                                    <option value="Nekad">Nekad</option>
                                </select>
                            </div>

                            <div class="introduction text grab-elements">
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Introduction Heading</p>
                                    <input type="text" name="intro_heading" value="Introduktion" required class="sign-input w-100 mb-3" placeholder="Introduction heading ">
                                </div>
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600">Introduction</p>
                                    <textarea name="intro" id="" required placeholder="Introduktion" rows="3" class="w-100 sign-textarea mb-3">Vi på Recway AB är glada över att ha fått i uppdrag av NAME OF COMPANY att genomföra ett noggrant NAME OF SERVICE. Detta är en viktig process för att säkerställa att den potentiella kandidaten är lämplig och pålitlig för tjänsten i fråga. Genom att undersöka individens kriminella historia, utbildning, anställningshistoria och ekonomiska status kan vi identifiera eventuella varningstecken och minska risken för tjänstefel. Vi är ett företag som lägger stor vikt vid integritet och säkerhet och vi kommer att genomföra denna kritiska process med största omsorg och professionalism.</textarea>
                                    <select id="" class="form-select mb-3">
                                        <option value="left">Left</option>
                                        <option value="justify">Justify</option>
                                    </select>
                                </div>
                                <div class="col-lg-12 ps-0">
                                    <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                </div>
                            </div>

                            <div class="background text grab-elements">
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Background Heading</p>
                                    <input type="text" value="Bakgrund" name="background_heading" required class="sign-input w-100 mb-3" placeholder="Background heading ">
                                </div>
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600">Background Text</p>
                                    <textarea name="background" id="" required placeholder="Background" rows="3" class="w-100 sign-textarea mb-3">Recway genomförde en bakgrundskontroll på <?php echo $candidate->name . " " . $candidate->surname ?>. Denna rapport innehåller en beskrivning av uppdraget, en sammanfattning av vår analys och en sammanfattning av den information som samlats in.</textarea>
                                    <select id="" class="form-select mb-3">
                                        <option value="left">Left</option>
                                        <option value="justify">Justify</option>
                                    </select>
                                </div>
                                <div class="col-lg-12 ps-0">
                                    <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                </div>
                            </div>

                            <div class="information text grab-elements">
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Information & Facts Heading</p>
                                    <input type="text" value="Information and Facts" name="information_heading" required class="sign-input w-100 mb-3" placeholder="Background heading ">
                                </div>
                                <div class="col-lg-12">
                                    <p class="f-16 mb-0 pb-0 w-600">Information & Facts Text</p>
                                    <textarea name="information" id="" required placeholder="Information and Facts" rows="3" class="w-100 sign-textarea mb-3">Recway är specialiserat på att samla in information från olika källor, inklusive internet, databaser, register och offentliga register som innehas av myndigheter. Deras metodik innebär att alltid samla in information från eller validera med den ursprungliga källan, för att säkerställa riktigheten av informationen som presenteras i deras rapporter. Recway gör en samlad ansträngning för att verifiera all information som leder till anteckningar genom att dubbelkontrollera den med andra källor. Det är viktigt att notera att information i olika system och databaser, även de som innehas av myndigheter, kan ha registrerats flera gånger av olika anledningar, och Recway kan inte hållas ansvarigt för eventuella faktafel i källorna. Därför är det avgörande att dubbelkontrollera all avvikande information med kandidaten för att säkerställa att den mest korrekta informationen presenteras i rapporten.</textarea>
                                    <select id="" class="form-select mb-3">
                                        <option value="left">Left</option>
                                        <option value="justify">Justify</option>
                                    </select>
                                </div>
                                <div class="col-lg-12 ps-0">
                                    <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                                </div>
                            </div>

<!--                            <div class="row social-media grab-elements mt-2">-->
<!--                                <div class="col-lg-4">-->
<!--                                    <p class="f-16 mb-0 pb-0 w-600"><i style="cursor: grab" class="bi bi-grip-vertical"></i> Facebook</p>-->
<!--                                    <input type="text" name="facebook" required class="sign-input w-100 mb-3" placeholder="Facebook profile link ">-->
<!--                                </div>-->
<!---->
<!--                                <div class="col-lg-4">-->
<!--                                    <p class="f-16 mb-0 pb-0 w-600">Instagram</p>-->
<!--                                    <input type="text" name="instagram" required class="sign-input w-100 mb-3" placeholder="Instagram profile link ">-->
<!--                                </div>-->
<!---->
<!--                                <div class="col-lg-4">-->
<!--                                    <p class="f-16 mb-0 pb-0 w-600">Twitter</p>-->
<!--                                    <input type="text" name="twitter" required class="sign-input w-100 mb-3" placeholder="Twitter profile link ">-->
<!--                                </div>-->
<!--                            </div>-->

                            <div class="pageBreak mb-3 grab-elements mt-2" style="">
                                <i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i>
                                <hr>
                                <div class="col-lg-12 ps-0">
                                    <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-page-break">Delete Page Break</button>
                                </div>
                            </div>

                            <div class="profile grab-elements my-2">
                                <p><i style="cursor: grab" class="bi bi-grip-vertical"></i> Profile</p>
                            </div>

                            <div data-table-id="tableIncome" style="overflow-x: auto" class="tableIncome grab-elements">
                                <p data-table-id="tableIncome" class="mt-2" >
                                    <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                    <strong>Inkomstippgifter</strong>
                                </p>
                                <table id="tableIncome" class="w-100" style="min-width: 722px">
                                    <thead>
                                    <tr>
                                        <th>Column 1</th>
                                        <th>Column 2</th>
                                        <th>Column 3</th>
                                        <th>Column 4</th>
                                        <th>Godkännande</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>
                                            <input type="text" value="Typ av uppgift" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" value="2022 (inkomstår 2021)" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" value="2021 (inkomstår 2020)" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" value="2020 (inkomstår 2019)" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" value="Godkännande" class="sign-input">
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Taxerad inkomst" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <select class="form-select" name="tableIncome_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Underskott av kapital" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <select class="form-select" name="tableIncome_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Slutig skatt" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <select class="form-select" name="tableIncome_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="tableIncome"></i>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                    <button type="button" class="add-row btn-fill  text-white me-1" style="width:auto; min-width:130px " data-table-id="tableIncome">Add Row</button>
                                    <button type="button" class="delete-table btn-fill  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="tableIncome">Delete Table</button>

                                </div>
                            </div>

                            <div data-table-id="table-0" style="overflow-x: auto" class="tables grab-elements">
                                <p class="mt-2" data-table-id="table-0">
                                    <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                    <strong>Juridik</strong>
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
                                            <input type="text" value="Dom tingsrätt" class="sign-input" name="table-0_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-0_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-0_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Dom hovrätt" class="sign-input" name="table-0_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-0_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-0_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Dom förvaltningsrätter" class="sign-input" name="table-0_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-0_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-0_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-0"></i>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                    <button type="button" class="add-row btn-fill  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-0">Add Row</button>
                                    <button type="button" class="delete-table btn-fill  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-0">Delete Table</button>

                                </div>
                            </div>

                            <div data-table-id="table-1" style="overflow-x: auto" class="tables grab-elements">
                                <p class="mt-2" data-table-id="table-1">
                                    <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                    <strong>Bolagsengagemang</strong>
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
                                            <input type="text" value="Styrelseledamot & Ordförande" class="sign-input" name="table-1_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-1_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-1_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-1"></i>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                    <button type="button" class="add-row btn-fill  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-1">Add Row</button>
                                    <button type="button" class="delete-table btn-fill  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-1">Delete Table</button>

                                </div>
                            </div>

                            <div data-table-id="table-2" style="overflow-x: auto" class="tables grab-elements">
                                <p class="mt-2" data-table-id="table-2">
                                    <i style="cursor: grab" class="bi bi-grip-vertical"></i>
                                    <strong>CV-kontroll arbetsgivare</strong>
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
                                            <input type="text" value="Arbetsgivare 1" class="sign-input" name="table-2_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-2_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-2_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-2"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Företag" class="sign-input" name="table-2_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-2_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-2_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-2"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Organisation nummer" class="sign-input" name="table-2_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-2_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-2_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-2"></i>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <input type="text" value="Kontroll" class="sign-input" name="table-2_col1[]">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input" name="table-2_col2[]">
                                        </td>
                                        <td>
                                            <select class="form-select" name="table-2_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
                                            </select>
                                        </td>
                                        <td>
                                            <i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="table-2"></i>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                              <div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" >
                                  <button type="button" class="add-row btn-fill  text-white me-1" style="width:auto; min-width:130px " data-table-id="table-2">Add Row</button>
                                  <button type="button" class="delete-table btn-fill  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="table-2">Delete Table</button>

                              </div>

                            </div>
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2">Table Caption</p>
                            <input type="text" name="table-caption" id="table-caption" required class="sign-input w-100 mb-3" placeholder="Table Caption ">
                        </div>

<!--                        <div class="row mx-auto">-->
                            <div class="col-lg-3 col-md-6">
                                <button type="button" id="add-table" class="btn-fill mt-1 text-white w-100 mb-3 mx-0">Add Table</button>
                            </div>

                            <div class="col-lg-3 col-md-6 ">
                                <button type="button" id="add-page-break" class="btn-fill mt-1 text-white w-100 mb-3 mx-0">Add Page Break</button>
                            </div>

                            <div class="col-lg-3 col-md-6   ">
                                <button type="button" id="add-comment" class="btn-fill mt-1 text-white w-100 mb-3 mx-0">Add Text</button>
                            </div>

                            <div class="col-lg-3 col-md-6  ">
                                <input type="file" accept="image/jpeg, image/png" id="image-input" style="display:none">
                                <button type="button" id="add-image" class="btn-fill mt-1 text-white w-100 mb-3 mx-0">Add Image</button>
                            </div>
<!--                        </div>-->

                        <div class="col-lg-4 ">
                            <button type="button" id="preview" data-bs-toggle="modal" data-bs-target="#exampleModal" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Preview Report</a></button>
                        </div>

                        <div class="col-lg-4 ">
                            <button type="button" id="generate" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Generate Report</a></button>
                        </div>

                        <div class="col-lg-4 ">
                            <button type="button" id="submit" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Submit Report</a></button>
                        </div>

                        <div class="col-lg-12 mt-4">
                            <p id="report-msg"></p>
                        </div>
                    </div>
                </form>
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

include_once ('includes/footer.php');

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    var tables = [];
    $(document).ready(function() {

        let tableCount = 5;

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
                    rowHtml = '<tr><td><input type="text" class="sign-input" name="' + tableId + '_col1[]"></td><td><input type="text" class="sign-input" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Godkänd">Godkänd</option><option value="Avvikelse">Avvikelse</option><option value="Nekad">Nekad</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
                } else {
                    rowHtml = `<tr>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <select class="form-select" name="tableIncome_col3[]">
                                                <option value="Godkänd">Godkänd</option>
                                                <option value="Avvikelse">Avvikelse</option>
                                                <option value="Nekad">Nekad</option>
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
                    rowHtml = '<tr><td><input type="text" class="sign-input" name="' + tableId + '_col1[]"></td><td><input type="text" class="sign-input" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Godkänd">Godkänd</option><option value="Avvikelse">Avvikelse</option><option value="Nekad">Nekad</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
                } else {
                    rowHtml = `<tr>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
                                        </td>
                                        <td>
                                            <input type="text" class="sign-input">
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

        $('#add-table').click(function() {
            const tableCaption = $('#table-caption').val();
            const tableId = `table-${tableCount}`;
            tableCount++;

            // Create table HTML
            let tableHtml = `<div data-table-id="${tableId}" class="tables grab-elements" style="overflow-x: auto"><p class="mt-2" data-table-id="${tableId}"><i style="cursor: grab" class="bi bi-grip-vertical"></i> <strong>${tableCaption}</strong></p>`;
            tableHtml += `<table id="${tableId}" class="w-100" style="min-width: 722px">`;
            tableHtml += '<thead><tr><th>Head</th><th>Value</th><th>Status</th></tr><th></th></thead>';
            tableHtml += '<tbody></tbody></table>';
            tableHtml += `<div class="d-flex  align-items-center flex-shrink-0 mt-4 mb-3 " style="min-width: 500px" ><button type="button" class="add-row btn-fill  text-white me-1" style="width:auto; min-width:130px "  data-table-id="${tableId}">Add Row</button>`;
            tableHtml += `<button type="button" class="delete-table btn-fill  text-white m-0 ms-1" style="width:auto; min-width:130px" data-table-id="${tableId}">Delete Table</button></div></div>`;

            // Append table to container
            $('.elements').append(tableHtml);

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
                const rowHtml = '<tr><td><input type="text" class="sign-input" name="' + tableId + '_col1[]"></td><td><input type="text" class="sign-input" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Godkänd">Godkänd</option><option value="Avvikelse">Avvikelse</option><option value="Nekad">Nekad</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
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
        });
        
        $('#add-comment').click(function () {
            var comment = `<div class="comment text mb-3 grab-elements"><div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i> Text Heading</p>
                            <input type="text" name="comment_headings[]" required class="sign-input w-100 mb-3 comment_headings" placeholder="Text heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Text Description</p>
                            <textarea name="comment_description[]" id="" required placeholder="Text description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions"></textarea>
                            <select id="" class="form-select mb-3">
                                <option value="left">Left</option>
                                <option value="justify">Justify</option>
                            </select>
                        </div>
                        <div class="col-lg-12 ps-0">
                            <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-comment">Delete Text</button>
                        </div>
                        </div>`;

            $(".elements").append(comment)
        })

        $('#add-page-break').click(function () {
            var pageBreak = `<div class="pageBreak mb-3 grab-elements mt-2"><i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i><hr>
            <div class="col-lg-12 ps-0">
                            <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-page-break">Delete Page Break</button>
                        </div></div>`;

            $(".elements").append(pageBreak)
        })

        $('#add-image').click(function () {
            $('#image-input').click();
        })

        var formats = {"image/png": "PNG", "image/jpeg": "JPG", "image/jpg": "JPG"}
        $('#image-input').change(function() {
            const file = $('#image-input')[0].files[0];

            // create a FormData object to send the file data
        //     const formData = new FormData();
        //     formData.append('report-img', file);
        //
        //     // send the file to the server using AJAX
        //     $.ajax({
        //         url: '../includes/report-upload.php',
        //         type: 'POST',
        //         data: formData,
        //         processData: false,
        //         contentType: false,
        //         success: function(response) {
        //             response = JSON.parse(response)
        //             if(response.error) {
        //                 alert(response.error)
        //             } else {
        //                 // create the image HTML
        //                 const image = `<div class="image mb-3 grab-elements"><div class="col-lg-12">
        //   <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical images-grabber"></i> Image</p>
        //   <img src="${response.src}" data-width="${response.width}" data-height="${response.height}" data-format="${response.format}" alt="" width="200" class="ms-1">
        // </div>
        // <div class="col-lg-12 ps-0">
        //   <button type="button" class="btn-fill w-25 m-0 mt-1 text-white delete-image">Delete Image</button>
        // </div>
        // </div>`;
        //
        //                 $(".elements").append(image)
        //             }
        //         },
        //         error: function(xhr, status, error) {
        //             console.error('Error uploading file');
        //             console.error('Status:', status);
        //             console.error('Error:', error);
        //         }
        //     });

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
          <button type="button" class="btn-fill w-auto px-4 m-0 mt-1 text-white delete-image">Delete Image</button>
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
        var statusColors = {"Godkänd": [60, 179, 113], "Avvikelse": [255, 165, 0], "Nekad": [255, 0, 0]};
        var statusColorsHex = {"Godkänd": "#3CB371", "Avvikelse": "#FFA500", "Nekad": "#FF0000"};
        var statusImages = {"Godkänd": "approved.png", "Avvikelse": "deviation.png", "Nekad": "denied.png"};

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
            doc.text('info@recway.nu', 5, 8)

            // Add date on right side
            doc.setTextColor("#ffffff")
            const date = new Date();
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
            const formattedDate = date.toLocaleDateString('en-US', options);
            doc.text(formattedDate, doc.internal.pageSize.width - 20, 5)
        }

        // Define footer function
        const addFooter = function() {
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.setFillColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.rect(0, doc.internal.pageSize.height - 15, doc.internal.pageSize.width, 15, "F")

            // Set font size and style for header
            doc.setFontSize(10)
            doc.setFont("Helvetica", "Bold")

            doc.setTextColor("#ffffff")
            doc.text(doc.getCurrentPageInfo().pageNumber.toString(), doc.internal.pageSize.width - 10, doc.internal.pageSize.height - footerHeight)
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
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold' },
                    result: {textColor: "#ffffff"}
                },
                didParseCell: function(data) {
                    // Check if cell is in last column
                    if (data.column.index === data.table.columns.length - 1) {
                        // Set background color
                        data.cell.styles.fillColor = statusColors[data.cell.raw];
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
                            doc.text(table.caption, leftMargin, data.settings.margin.top - 5);
                        } else {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            doc.text(table.caption, leftMargin, data.settings.startY - 5);
                        }
                        tableHasSplit = true;
                        y = data.cursor.y + 10
                    }

                },
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
                head: [{col1: 'Col1', col2: 'Col2', col3: "Col3", col4: "Col4", col5: "Godkännande"}],
                body: data,
                showHead: false,
                theme: 'grid',
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
                            doc.text(table.caption, leftMargin, data.settings.margin.top - 5);
                        } else {
                            textFont("mainHeading")
                            doc.setTextColor("#000000")
                            doc.text(table.caption, leftMargin, data.settings.startY - 5);
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
            doc.line(leftMargin, y, leftMargin + 50, y)

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
            doc.text("Personlig information", leftMargin, y)

            var data = [
                {key: "Namn", value: candidate.name + candidate.surname},
                {key: "E-post", value: candidate.email},
                {key: "Telefon", value: candidate.phone},
                {key: "Fakturamottagare", value: candidate.referensperson},
                {key: "Fakturareferens", value: candidate.reference},
                {key: "Servicetyp", value: candidate.serviceTitle},
                {key: "SSN", value: candidate.security},
                {key: "VASC ID", value: candidate.vasc_id},
                {key: "Personal", value: candidate.staffName !== null ? candidate.staffName : "Not assigned"}
            ];

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
                doc.text(heading, leftMargin, y)

                // Add Description
                y += 5
                textFont("normalText")
                doc.text(description, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: "justify"})
                y += height - 5
            } else if($(this).hasClass("tables")) {
                const tableData = [];
                const table = {}

                const tableCaption = $(this).find('strong').text()

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
                    y += imageHeight
                } else {
                    y += 5
                    doc.addImage(imageSrc, imageFormat, ((doc.internal.pageSize.width - imageWidth) / 2), y, imageWidth, imageHeight)
                    y += imageHeight + 15
                }
            }
        })

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
</script>