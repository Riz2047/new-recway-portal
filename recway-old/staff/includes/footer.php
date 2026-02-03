</div>

</div>



<div class='successMsg d-none' id='msg-flash'>

    <i style='color: #00D26A' class='bi bi-check-circle-fill'></i>

</div>



<div class='errorMsg d-none' id='msg-flash'>

    <i style='color: #ff4d40' class='bi bi-x-circle-fill'></i>

</div>



<div class="backdrop"></div>



<script type="text/template" id="spinner">

    <div class="spinner-border text-secondary d-flex m-auto" role="status"></div>

</script>



<!--Modals-->

<?php

include_once "includes/models.php";

?>



<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js" integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js" integrity="sha384-fbbOQedDUMZZ5KreZpsbe1LCZPVmfTnH7ois6mU1QK+m14rQ1l2bGBq41eYeM/fS" crossorigin="anonymous"></script>

<script src="https://cdn.datatables.net/1.13.3/js/jquery.dataTables.min.js"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/datetime/1.1.2/js/dataTables.dateTime.min.js"></script>

<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>

<script type="text/javascript" src="https://cdn.datatables.net/buttons/2.3.4/js/buttons.html5.min.js"></script>

<script src="../iconpicker/dist/js/bootstrapicon-iconpicker.js"></script>

<script type="text/javascript" src="https://translate.google.com/translate_a/element.js?cb=googleTranslateElementInit2"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>

<script src="https://unpkg.com/jspdf-autotable@3.5.28/dist/jspdf.plugin.autotable.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js" integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui-touch-punch/0.2.3/jquery.ui.touch-punch.min.js" integrity="sha512-0bEtK0USNd96MnO4XhH8jhv3nyRF0eK87pJke6pkYf3cM0uDIhNJy9ltuzqgypoIFXw3JSuiy04tVk4AjpZdZw==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>



<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js" integrity="sha512-ElRFoEQdI5Ht6kZvyzXhYG9NqjtkmlkfYk0wr6wHxU9JEHakS7UJZNeml5ALk+8IKlU6jDgMabC3vkumRokgJA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

<script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>

<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-adapter-moment/1.0.0/chartjs-adapter-moment.min.js" integrity="sha512-oh5t+CdSBsaVVAvxcZKy3XJdP7ZbYUBSRCXDTVn0ODewMDDNnELsrG9eDm8rVZAQg7RsDD/8K3MjPAFB13o6eA==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/docxtemplater/3.45.0/docxtemplater.js"></script>
<script src="https://unpkg.com/pizzip@3.1.6/dist/pizzip.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/FileSaver.js/1.3.8/FileSaver.js"></script>
<script src="https://unpkg.com/pizzip@3.1.6/dist/pizzip-utils.js"></script>

<script src="assets/select2/dist/js/select2.full.min.js"></script>

<script src="assets/js/app.js"></script>

<script src="assets/js/app2.js"></script>

<script src="assets/js/custom_dropdown.js"></script>

<script src="assets/js/department_ajex.js"></script>

<script src="assets/js/pages.js"></script>

<script src="assets/calendar/js/main.js"></script>

<script src="assets/summernote/summernote-bs4.min.js"></script>

<script>

    function columns_check(obj) {

        var state = 0;

        var show_hide_id = $(obj).attr('data-id');

        if ($(obj).is(":checked") == true) {

            state = 1;

            $("." + show_hide_id).removeClass('custom_hide');

        } else {

            $("." + show_hide_id).addClass('custom_hide');

        }



        // Toggle a custom class to hide/show columns

        var dataTable = $('#dataTable').DataTable();

        dataTable.columns('.toggle-column').visible(!$(obj).is(":checked"));



        var table = $(obj)[0].name;

        var id = $('#table_id').val();

        table = table.split(/\[|\]/).filter(Boolean);

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                state: state,

                column: 1,

                table: id,

                columns: table[2],

            },

            success: function(response) {}

        });

    }

    $(document).ready(function() {

        $('.paginate_button').each(function(i, v) {

            $(this).attr('onclick', 'reinitiateDataTable()')

        })

        $('select[name="dataTable_length"]').attr('onchange', 'reinitiateDataTable()')

        $('#dataTable_filter').find('input[type="search"]').attr('oninput', 'reinitiateDataTable()')

        $('.sorting').each(function() {

            $(this).attr('onclick', 'reinitiateDataTable()');

        })

    });

</script>

</body>



</html>