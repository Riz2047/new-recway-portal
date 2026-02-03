// Open candidate modal
$("body").on("click", ".open-candidate", function (e) {
    e.preventDefault();
    const sno = $(this).data("sno")
    const id = $(this).data("id")
    const status = $(this).data("status")
    const url = "pages/invoice.php?sno=" + sno + "&id=" + id + (status !== '' ? '&status=' + status : '')

    $("#content-modal .modal-title").text("Invoice")
    $("#content-modal .modal-body").empty();
    $("#content-modal .modal-body").html($("#spinner").html());
    $("#content-modal").modal("show")

    // Use AJAX to load content from the PHP file
    $.ajax({
        url: url, // Assuming your PHP files have the same name as data-page attribute
        type: "GET",
        success: function (data) {
            // Replace the content of the main section with the loaded data
            $("#content-modal .modal-body").empty();
            $("#content-modal .modal-body").html(data);
        },
        error: function () {
            // Handle errors if the PHP file couldn't be loaded
            alert("Error loading content.");
        }
    });
});

// Open Background Report modal
$("body").on("click", ".open-report", function (e) {
    e.preventDefault();
    const id = $(this).data("id")
    const lang = $(this).data("lang")
    var url = ""
    if (lang === "en") {
        url = "pages/report.php?id=" + id
    } else {
        url = "pages/report-sv.php?id=" + id
    }

    $("#backgroundReportContentModal .modal-body").empty();
    $("#backgroundReportContentModal .modal-body").html($("#spinner").html());
    $("#backgroundReportContentModal").modal("show")

    // Use AJAX to load content from the PHP file
    $.ajax({
        url: url, // Assuming your PHP files have the same name as data-page attribute
        type: "GET",
        success: function (data) {
            // Replace the content of the main section with the loaded data
            $("#backgroundReportContentModal .modal-body").empty();
            $("#backgroundReportContentModal .modal-body").html(data);
        },
        error: function () {
            // Handle errors if the PHP file couldn't be loaded
            alert("Error loading content.");
        }
    });
});

// Open Customer modal
$("body").on("click", ".open-customer", function (e) {
    e.preventDefault();
    const id = $(this).data("id")
    const url = "pages/update-customer.php?id=" + id

    $("#customer-modal .modal-title").text("Customer")
    $("#customer-modal .modal-body").empty();
    $("#customer-modal .modal-body").html($("#spinner").html());
    $("#customer-modal").modal("show")

    // Use AJAX to load content from the PHP file
    $.ajax({
        url: url, // Assuming your PHP files have the same name as data-page attribute
        type: "GET",
        success: function (data) {
            // Replace the content of the main section with the loaded data
            $("#customer-modal .modal-body").empty();
            $("#customer-modal .modal-body").html(data);
            var scriptElements = document.querySelectorAll('script[src="assets/js/department_ajex.js"]');
            scriptElements.forEach(function (existingScript) {
                existingScript.parentNode.removeChild(existingScript);
            });
            var script = document.createElement('script');
            script.src = 'assets/js/department_ajex.js';
            document.head.appendChild(script);
            $('#customer-modal .select2').select2();
        },
        error: function () {
            // Handle errors if the PHP file couldn't be loaded
            alert("Error loading content.");
        }
    });
});

// Remove Interview Report Section
$("body").on('click', '#content-modal .btn-close', function () {
    // $("#report-section").remove()
    $("body").off("click", ".report-btn2");
})