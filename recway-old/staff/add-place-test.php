<?php

$activeLink = "places";

include_once ('includes/header.php');

?>

<button id="load" type="button">Load</button>

<div id="main-content"></div>

<?php

include_once ('includes/footer.php');

?>

<script>
    $(document).ready(function () {
        // Handle click events on sidebar options
        $("#load").click(function (e) {
            e.preventDefault();

            // Get the data-page attribute to determine which PHP file to load
            var page = $(this).data("page");

            // Use AJAX to load content from the PHP file
            $.ajax({
                url: "add-place.php", // Assuming your PHP files have the same name as data-page attribute
                type: "GET",
                success: function (data) {
                    // Replace the content of the main section with the loaded data
                    $("#main-content").html(data);
                },
                error: function () {
                    // Handle errors if the PHP file couldn't be loaded
                    alert("Error loading content.");
                }
            });
        });
    });

</script>
