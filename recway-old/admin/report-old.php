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
<!--        <iframe src="" id="frame" width="100%" height="100%"></iframe>-->

        <div class="col-lg-12">
            <div class="main-heading  w-100">
                <h1 class=" mt-3 mb-4">Generate Report</h1>
            </div>
            <div class="box shadow">
                <?php echo isset($message) ? $message : '' ?>
                <form action="process.php" method="post">
                    <div class="row p-0 m-0">
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2">Introduction Heading</p>
                            <input type="text" name="intro_heading" required class="sign-input w-100 mb-3" placeholder="Introduction heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Introduction</p>
                            <textarea name="intro" id="" required placeholder="Introduction" rows="3" class="w-100 sign-textarea mb-3"></textarea>
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2">Background Heading</p>
                            <input type="text" value="Background" name="background_heading" required class="sign-input w-100 mb-3" placeholder="Background heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Background Text</p>
                            <textarea name="background" id="" required placeholder="Background" rows="3" class="w-100 sign-textarea mb-3">Recway conducted a background check on <?php echo $candidate->name . " " . $candidate->surname ?>. This report contains a description of the assignment, a summary of our analysis and a summary of the information collected.</textarea>
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2">Information & Facts Heading</p>
                            <input type="text" value="Information and Facts" name="information_heading" required class="sign-input w-100 mb-3" placeholder="Background heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Information & Facts Text</p>
                            <textarea name="information" id="" required placeholder="Information and Facts" rows="3" class="w-100 sign-textarea mb-3">Recway specializes in gathering information from various sources, including the internet, databases, registries, and public records held by authorities. Their methodology involves always collecting information from or validating with the original source, to ensure the accuracy of the information presented in their reports. Recway makes a concerted effort to verify any information that leads to notes by cross-checking it with other sources. It's important to note that information in various systems and databases, even those held by authorities, may have been recorded multiple times for various reasons, and Recway cannot be held responsible for any factual errors in the sources.Therefore, it's crucial to cross-check any divergent information with the candidate to ensure the most accurate information is presented in the report.</textarea>
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Result</p>
                            <select name="result" id="" class="form-select mb-3">
                                <option value="Approved">Approved</option>
                                <option value="Deviation">Deviation</option>
                                <option value="Denied">Denied</option>
                            </select>
                        </div>

                        <div class="col-lg-4">
                            <p class="f-16 mb-0 pb-0 w-600">Facebook</p>
                            <input type="text" name="facebook" required class="sign-input w-100 mb-3" placeholder="Facebook profile link ">
                        </div>

                        <div class="col-lg-4">
                            <p class="f-16 mb-0 pb-0 w-600">Instagram</p>
                            <input type="text" name="instagram" required class="sign-input w-100 mb-3" placeholder="Instagram profile link ">
                        </div>

                        <div class="col-lg-4">
                            <p class="f-16 mb-0 pb-0 w-600">Twitter</p>
                            <input type="text" name="twitter" required class="sign-input w-100 mb-3" placeholder="Twitter profile link ">
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Comments</p>
                        </div>

                        <div id="comments-container"></div>

                        <div class="col-lg-12 ps-0">
                            <button type="button" id="add-comment" class="btn-fill w-25 mt-1 text-white mb-3">Add Comment</button>
                        </div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Tables</p>
                        </div>

                        <div id="tables-container"></div>

                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2">Table Caption</p>
                            <input type="text" name="table-caption" id="table-caption" required class="sign-input w-100 mb-3" placeholder="Table Caption ">
                        </div>
                        <div class="col-lg-12 ps-0">
                            <button type="button" id="add-table" class="btn-fill w-25 mt-1 text-white">Add Table</button>
                        </div>

                        <div class="col-lg-12 ps-0">
                            <button type="button" id="generate" class="btn-fill w-100 mt-4"><a>Generate Report</a></button>
                        </div>
                    </div>
                </form>
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

        let tableCount = 0;

        $('#tables-container').sortable({
            items: 'div[data-table-id]',
            handle: '.bi-grip-vertical',
            axis: 'y',
            update: function(event, ui) {
                const tableId = ui.item.data('table-id');
                const tableIndex = tables.findIndex(table => table.id === tableId);
                const newIndex = ui.item.index();

                // Update the tables array to reflect the new order
                if (tableIndex !== -1) {
                    tables.splice(newIndex, 0, tables.splice(tableIndex, 1)[0]);
                }
            }
        });

        $('#comments-container').sortable({
            items: '.comment',
            handle: '.comments-grabber',
            axis: 'y',
            // update: function(event, ui) {
            //     const tableId = ui.item.data('table-id');
            //     const tableIndex = tables.findIndex(table => table.id === tableId);
            //     const newIndex = ui.item.index();
            //
            //     // Update the tables array to reflect the new order
            //     if (tableIndex !== -1) {
            //         tables.splice(newIndex, 0, tables.splice(tableIndex, 1)[0]);
            //     }
            // }
        });

        $('#add-table').click(function() {
            const tableCaption = $('#table-caption').val();
            const tableId = `table-${tableCount}`;
            tableCount++;

            // Create table HTML
            let tableHtml = `<div data-table-id="${tableId}"><p class="mt-2" data-table-id="${tableId}"><i style="cursor: grab" class="bi bi-grip-vertical"></i> <strong>${tableCaption}</strong></p>`;
            tableHtml += `<table id="${tableId}" class="w-100">`;
            tableHtml += '<thead><tr><th>Head</th><th>Value</th><th>Status</th></tr><th></th></thead>';
            tableHtml += '<tbody></tbody></table>';
            tableHtml += `<button type="button" class="add-row btn-fill w-25 text-white m-0 mt-1" data-table-id="${tableId}">Add Row</button>`;
            tableHtml += `<button type="button" class="delete-table btn-fill w-25 text-white m-0 ms-2 mt-1" data-table-id="${tableId}">Delete Table</button></div>`;

            // Append table to container
            $('#tables-container').append(tableHtml);

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
                const rowHtml = '<tr><td><input type="text" class="sign-input" name="' + tableId + '_col1[]"></td><td><input type="text" class="sign-input" name="' + tableId + '_col2[]"></td><td><select class="form-select" name="' + tableId + '_col3[]"><option value="Approved">Approved</option><option value="Deviation">Deviation</option><option value="Denied">Denied</option></select></td><td><i style="color: var(--yellow); cursor: pointer" class="delete-row bi bi-trash ms-2" data-table-id="' + tableId + '"></i></td></tr>';
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
            var comment = `<div class="comment"><div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600 mt-2"><i style="cursor: grab" class="bi bi-grip-vertical comments-grabber"></i> Comment Heading</p>
                            <input type="text" name="comment_headings[]" required class="sign-input w-100 mb-3 comment_headings" placeholder="Comment heading ">
                        </div>
                        <div class="col-lg-12">
                            <p class="f-16 mb-0 pb-0 w-600">Comment Description</p>
                            <textarea name="comment_description[]" id="" required placeholder="Comment description" rows="3" class="w-100 sign-textarea mb-3 comment_descriptions"></textarea>
                        </div>
                        <div class="col-lg-12 ps-0">
                            <button type="button" class="btn-fill w-25 m-0 mt-1 text-white delete-comment">Delete Comment</button>
                        </div>
                        </div>`;
            $("#comments-container").append(comment)
        })

        // Add delete row event handler
        $(`body`).on('click', '.delete-comment', function() {
            $(this).closest('.comment').remove();
        });
    });
</script>

<script>
    var candidate = <?php echo json_encode($candidate); ?>;

    window.jsPDF = window.jspdf.jsPDF;

    $("#generate").click(function () {
        // Create new jsPdf instance
        const doc = new jsPDF()
        var pageNumber = 1;
        var leftMargin = 15;
        var rightMargin = 15;
        var y = 35;
        var primaryColor = "#AC0206";
        var primaryColorRGB = [172, 2, 6];
        var secondaryColor = "#807D7D";
        var secondaryColorRGB = [127, 126, 126];
        var statusColors = {"Approved": [60, 179, 113], "Deviation": [255, 165, 0], "Denied": [255, 0, 0]};
        var statusColorsHex = {"Approved": "#3CB371", "Deviation": "#FFA500", "Denied": "#FF0000"};

        // Define header function
        const addHeader = function() {
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
            doc.text(pageNumber.toString(), doc.internal.pageSize.width - 10, doc.internal.pageSize.height - 7)
        }

        function getTextWidth(text, fontSize) {
            // Text width in mm
            return (doc.getStringUnitWidth(text) * fontSize) / (72/25.6)
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
            y = y !== 30 ? doc.lastAutoTable.finalY + 15 : y

            textFont("mainHeading")
            doc.setTextColor("#000000")
            doc.text(table.caption, leftMargin, y)

            var data = [];
            table.data.forEach(function (row) {
                if(row[0] !== "" || row[1] !== "") {
                    data.push({key: row[0], value: row[1], result: row[2]})
                }
            })

            y = y + 5
            doc.autoTable({
                startY: y,
                head: [{key: 'Key', value: 'Value', result: "Result"}],
                body: data,
                showHead: false,
                theme: 'grid',
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
                }
            })
        }

        // Add first page with header
        addHeader()
        addFooter()

        // Fetch Data
        // Fetch Social Media
        var facebook = $("input[name='facebook']").val()
        var instagram = $("input[name='instagram']").val()
        var twitter = $("input[name='twitter']").val()

        // Fetch Comments
        var commentHeadings = $(".comment_headings");
        var commentDescriptions = $(".comment_descriptions");

        function addInformation() {
            var orderID = candidate.order_id;
            var serviceTitle = candidate.serviceTitle;
            var result = $("select[name='result']").val()

            doc.addImage("../assets/images/logo.png", 'PNG', leftMargin, y, 50, 17)
            textFont("subHeading")
            doc.setTextColor(primaryColor)

            y = y + 3
            doc.text("Order Information", doc.internal.pageSize.width - getTextWidth("Order Information", doc.getFontSize()) - rightMargin, y)

            y = y + 5
            doc.setFontSize(10)
            doc.setFont("Helvetica", "")
            doc.setTextColor(secondaryColor)

            doc.text("Order# " + orderID, doc.internal.pageSize.width - getTextWidth("Order# " + orderID, doc.getFontSize()) - rightMargin, y)
            y = y + 5
            textFont("subHeading")
            doc.setTextColor(statusColorsHex[result])
            doc.text("Result: " + result, doc.internal.pageSize.width - getTextWidth("Result: " + result, doc.getFontSize()) - rightMargin, y)

            y = y + 20
            textFont("title")
            doc.setTextColor("#000000")
            doc.text(serviceTitle, leftMargin, y)
            y = y + 5
            doc.setLineWidth(1)
            doc.setDrawColor(primaryColorRGB[0], primaryColorRGB[1], primaryColorRGB[2])
            doc.line(leftMargin, y, leftMargin + 50, y)
        }

        addInformation()

        function addIntroduction() {
            var introHeading = $("input[name='intro_heading']").val()
            var intro = $("textarea[name='intro']").val()

            y = y + 20
            textFont("mainHeading")
            doc.text(introHeading, leftMargin, y)

            y = y + 10
            textFont("normalText")
            var lines = doc.splitTextToSize(intro, doc.internal.pageSize.width);
            doc.text(intro, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: "justify"})
            var lineHeight = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
            y += lines.length * lineHeight;

            var backgroundHeading = $("input[name='background_heading']").val()
            var background = $("textarea[name='background']").val()

            y = y + 10
            textFont("mainHeading")
            doc.text(backgroundHeading, leftMargin, y)

            y = y + 10
            textFont("normalText")
            var lines = doc.splitTextToSize(background, doc.internal.pageSize.width);
            doc.text(background, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: "justify"})
            var lineHeight = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
            y += lines.length * lineHeight;

            var informationHeading = $("input[name='information_heading']").val()
            var information = $("textarea[name='information']").val()

            y = y + 10
            textFont("mainHeading")
            doc.text(informationHeading, leftMargin, y)

            y = y + 10
            textFont("normalText")
            var lines = doc.splitTextToSize(information, doc.internal.pageSize.width);
            doc.text(information, leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: "justify"})
            var lineHeight = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
            y += lines.length * lineHeight;
        }

        addIntroduction()
        
        function addProfile() {
            doc.addPage()
            pageNumber++
            addHeader()
            addFooter()

            y = 35
            textFont("mainHeading")
            doc.setTextColor("#000000")
            doc.text("Personal Information", leftMargin, y)

            var data = [
                {key: "Name", value: candidate.name + candidate.surname},
                {key: "Email", value: candidate.email},
                {key: "Phone", value: candidate.phone},
                {key: "Invoice Recipient", value: candidate.referensperson},
                {key: "Invoice Reference", value: candidate.reference},
                {key: "Service Type", value: candidate.serviceTitle},
                {key: "SSN", value: candidate.security},
                {key: "VASC ID", value: candidate.vasc_id},
                {key: "Staff", value: candidate.staffName !== null ? candidate.staffName : "Not assigned"}
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

        addProfile();

        if(tables.length > 0) {
            doc.addPage()
            pageNumber++;
            addHeader()
            addFooter()
            y = 30;
        }
        
        function addTables() {
            // Loop through all tables
            tables.forEach(function(table) {
                const tableData = [];
                const $table = $('#' + table.id);

                // Get table rows data
                $table.find('tbody tr').each(function() {
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

                // Add table data to tables array
                table.data = tableData;

                if(table.data.length > 0 && !(table.data.length === 1 && table.data[0][0] === "" && table.data[0][1] === "")) {
                    generateTable(table)
                }
            });

        }

        addTables();

        if(commentHeadings.length > 0 || (facebook !== "" || instagram !== "" || twitter !== "")) {
            doc.addPage()
            pageNumber++;
            addHeader()
            addFooter()
            y = 30;
        }

        function addSocialMedia() {

            if(facebook !== "" || instagram !== "" || twitter !== "") {
                textFont("mainHeading")
                doc.setTextColor('#000000')
                doc.text("Social Media", leftMargin, y)

                y = y + 5
                if(facebook !== "") {
                    doc.addImage("../assets/images/facebook.png", "PNG", leftMargin, y, 10, 10)
                    doc.link(leftMargin, y, 10, 10, {url: facebook, target: '_blank'})
                }

                if(instagram !== "") {
                    doc.addImage("../assets/images/instagram.png", "PNG", leftMargin * 2, y, 10, 10)
                    doc.link(leftMargin * 2, y, 10, 10, {url: instagram, target: '_blank'})
                }

                if(twitter !== "") {
                    doc.addImage("../assets/images/twitter.png", "PNG", leftMargin * 3, y, 10, 10)
                    doc.link(leftMargin * 3, y, 10, 10, {url: twitter, target: '_blank'})
                }
            }
        }

        addSocialMedia();
        
        function addComments() {

            if(commentHeadings.length > 0) {
                // y = y + 10
                if(facebook !== "" || instagram !== "" || twitter !== "") {
                    y = y + 10
                }
                textFont("mainHeading")
                doc.setTextColor("#000000")
                // doc.text("Comments", leftMargin, y)

                commentHeadings.each(function (index, comment) {
                    y = y + 10
                    textFont("subHeading")
                    doc.text($(comment).val(), leftMargin, y)

                    y = y + 5
                    textFont("normalText")
                    var lines = doc.splitTextToSize($(commentDescriptions[index]).val(), doc.internal.pageSize.width);
                    doc.text($(commentDescriptions[index]).val(), leftMargin, y, {maxWidth: doc.internal.pageSize.width - (leftMargin*2), align: "justify"})
                    var lineHeight = doc.internal.getFontSize() / doc.internal.scaleFactor + 2;
                    y += lines.length * lineHeight;
                })
            }
        }

        addComments();

        // $('#frame').attr('src', doc.output("datauristring"))
        doc.save()
    })
</script>