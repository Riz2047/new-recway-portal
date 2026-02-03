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

$customer = findByQuery("SELECT company FROM customers WHERE id = {$candidate->cus_id}");

?>


    <div class="row">

        <div class="col-lg-12">
            <div class="main-heading  w-100">
                <h1 class=" mt-3 mb-4">Generate Report</h1>
            </div>
            <div class="box shadow">
                    <div class="row p-0 m-0">
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Comment</p>
                            <textarea id="comment" placeholder="Comment" rows="3" class="w-100 sign-textarea mb-3"></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-lg-4 ">
                            <button type="button" id="preview" data-bs-toggle="modal" data-bs-target="#exampleModal" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Preview Report</a></button>
                        </div>

                        <div class="col-lg-4 ">
                            <button type="button" id="generate" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Generate Report</a></button>
                        </div>

                        <div class="col-lg-4 ">
                            <button type="button" id="submit" class="btn-fill w-100 mt-4 mx-0 report-btn"><a>Submit Report</a></button>
                        </div>
                    </div>

                    <div class="col-lg-12 mt-4">
                        <p id="report-msg"></p>
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

include_once ('includes/footer.php');

?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script>
    var candidate = <?php echo json_encode($candidate); ?>;

    window.jsPDF = window.jspdf.jsPDF;

    $(window).on('load', function() {
        $("#preview").click()
    })

    $(".report-btn").click(function () {
        // Create new jsPdf instance
        const doc = new jsPDF()
        var x = 10;
        var y = 5;
        var leftMargin = 10;
        var rightMargin = 10;

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
            const options = { day: 'numeric', month: 'short', year: 'numeric' };
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
            table.forEach(function (row) {
                data.push({key: row[0], value: row[1]})
            })

            doc.autoTable({
                startY: y,
                margin: {top: 25, bottom: 25},
                head: [{key: 'Key', value: 'Value'}],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold', cellWidth: 90, fillColor: '#DBE5F1' },
                },
                didParseCell: function(data) {

                }
            })
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
                margin: {top: 25, bottom: 25},
                head: [{key: 'Key', col1: 'Col1', col2: 'Col2', col3: 'Col3'}],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold', cellWidth: 90, fillColor: '#DBE5F1' },
                },
                didParseCell: function(data) {
                    console.log(data)
                    if (data.row.index > 2 && data.column.index === 1) {
                        data.cell.colSpan = 2;
                    }
                }
            });
        }

        const addTable3 = function(table) {
            y += 3
            var data = [];
            table.forEach(function (row) {
                data.push({key: row[0], value: row[1]})
            })

            doc.autoTable({
                startY: y,
                margin: {top: 25, bottom: 25},
                head: [{key: 'Key', col1: 'Col1', col2: "Col2"}],
                body: data,
                showHead: false,
                theme: 'grid',
                // pageBreak: 'avoid',
                columnStyles: {
                    key: { textColor: 0, fontStyle: 'bold', cellWidth: 120, fillColor: '#DBE5F1' },
                },
                didParseCell: function(data) {

                }
            })
        }

        function getTextWidth(text, fontSize) {
            // Text width in mm
            return (doc.getStringUnitWidth(text) * fontSize) / (72/25.6)
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
        doc.text(para, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: 'left'})

        y += 33;
        para = `This form must be used when reporting back after a basic investigation has been completed.
        With basic investigation according to ch. 3 Section 3 of the Swedish Protective Security Act (2018:585) refers to an investigation into personal circumstances of importance for the security vetting. The investigation shall include grades, certificates, references and information provided by the person to whom the examination applies, as well as other information to the extent that it is relevant to the examination. The detailed requirements can be found in Vattenfall's requirements specification for Security Vetting.`;
        doc.text(para, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: 'left'})

        // Generate Table
        y += 35
        const table = [];
        var caption = "Beställare av säkerhetsprövningen (på Vattenfall)";
        table.push(["Namn & användarnamn/ Name & User-ID", ""])
        table.push(["E-post / E-mail", ""])
        table.push(["Företag /Company", ""])
        addTable(caption, table)

        y += 28
        table.length = 0
        caption = "Bakgrundskontroll genomförd av / Basic investigation conducted by"
        table.push(["Namn / Name", ""])
        table.push(["Telefonnummer / Telephone number", ""])
        table.push(["E-post / E-mail", ""])
        table.push(["Företag / Company", ""])
        addTable(caption, table)

        y += 37
        table.length = 0
        caption = "Intervjuarens uppgifter / Information about the interviewer"
        table.push(["Namn / Name", ""])
        table.push(["Telefonnummer / Telephone number", ""])
        table.push(["E-post / E-mail", ""])
        table.push(["Företag / Company", ""])
        addTable(caption, table)

        y += 37
        table.length = 0
        caption = "Kandidatens uppgifter / Information about the vetted candidate"
        table.push(["Namn / Name", ""])
        table.push(["Personnummer (ååmmdd-xxxx)Birth date (yymmdd-xxxx)", ""])
        table.push(["VASC-ID", ""])
        addTable(caption, table)

        y += 35
        doc.setDrawColor(0,0,0)
        // doc.setFillColor(0,0,0)
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 25)
        para = `Svaren i personbedömningen vidimeras genom undertecknande på sida två.
Formuläret skickas via mail till: securityvetting@vattenfall.com
The answers in the vetting is authenticated by signing the form on page two.
The form sends by e-mail to: securityvetting@vattenfall.com`;
        doc.setFontSize(12)
        doc.setFont("Helvetica", "")
        doc.text(para, leftMargin + 5, y + 7, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: 'left'})

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
        doc.text(para, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: 'left'})

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
        addTable3(table)

        y = doc.lastAutoTable.finalY + 2;
        doc.rect(leftMargin, y, doc.internal.pageSize.width - (leftMargin * 2), 15)
        doc.text("Om ”nej” ovan, ange anledning / If ”no” above, state reason: ", leftMargin + 2, y + 4)
        doc.line(leftMargin + 2, y + 6, leftMargin + 76, y + 6)

        y += 19
        doc.text(`Datum för bakgrundskontroll /
Date for the background check`, leftMargin, y)
        const date = new Date();
        const options = { day: 'numeric', month: 'short', year: 'numeric' };
        const formattedDate = date.toLocaleDateString('en-US', options);
        doc.setFont("Helvetica", "Bold")
        doc.text(formattedDate, leftMargin, y + 6)

        y += 10
        doc.setFont("Helvetica", "")
        doc.text(`Datum för intervjun / Date for the interview`, leftMargin, y)
        doc.setFont("Helvetica", "Bold")
        doc.text(formattedDate, leftMargin, y + 3)

        y -= 10
        doc.text(`Vidimering av genomförd grundutredning`, doc.internal.pageSize.width - 65, y)
        doc.setFont("Helvetica", "")
        doc.text(`Ort / City : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text("Stockholm", doc.internal.pageSize.width - 51, y + 3)

        y += 3
        doc.setFont("Helvetica", "")
        doc.text(`Datum / Date : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text(formattedDate, doc.internal.pageSize.width - 45, y + 3)

        y += 3
        doc.setFont("Helvetica", "")
        doc.text(`Signatur/ansvarig för genomförd
grundutredning : `, doc.internal.pageSize.width - 65, y + 3)
        doc.setFont("Helvetica", "Bold")
        doc.text("Sign", doc.internal.pageSize.width - 43, y + 6.5)

        y += 12
        doc.setFontSize(8)
        doc.setFont("Helvetica", "")
        doc.text(`* Dessa kontroller utförs av Vattenfall i fall av nyrekryteringar. Vid konsult/entreprenörsuppdrag utförs de av leverantören själv.
   These controls are carried out by Vattenfall, in cases of recruitments. For consultants, they are carried out by the supplier itself.`, leftMargin, y)

        var blobPDF = new Blob([doc.output('blob')], {type: "application/pdf"})
        var blobURL = URL.createObjectURL(blobPDF)
        if($(this).attr("id") === "preview") {
            $('#frame').attr('src', blobURL)
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