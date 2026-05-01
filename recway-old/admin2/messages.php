<?php

$activeLink = "messages";

include_once('includes/header.php');

$query = 'SELECT * FROM customers';

$stmt = $conn->prepare($query);

$stmt->execute();

$customers = $stmt->fetchAll();

$query = 'SELECT * FROM interviews';

$stmt = $conn->prepare($query);

$stmt->execute();

$services = $stmt->fetchAll();

$parent_customer = findallByQuery("SELECT * FROM customers");

?>

<div class="mx-lg-4 main-content">

    <div class="container">



        <!-- table row -->

        <div class="row">

            <div class="col-lg-12">

                <div class="table-div">



                    <form action="" method="post" id="d-form">

                        <div class="card card-cascade narrower mb-4">



                            <!--Card image-->

                            <div

                                class="view view-cascade gradient-card-header blue-gradient narrower py-2 mx-4 d-flex justify-content-between align-items-center">

                                <a href="#" class="white-text mx-3">Messages</a>

                            </div>

                            <div id="messages" class="tabcontent ">

                                <div class="container">

                                    <div class="row">

                                        <div class="col-lg-12">

                                            <div class="row">

                                                <div class="col-lg-6 mb-3">

                                                    <label class="form-label">Customer</label>

                                                    <select onchange="fetch_services();fetch_messages();" id="cus_id"

                                                        class="form-control filter-select">

                                                        <option value="">-Select Customer-</option>

                                                        <?php if (! empty($parent_customer)) { ?>

                                                            <?php foreach ($parent_customer as $par_customer) { ?>

                                                                <option value="<?= $par_customer->id ?>">

                                                                    <?= $par_customer->name ?>

                                                                </option>

                                                            <?php } ?>

                                                        <?php } ?>

                                                    </select>

                                                </div>

                                                <?php if (! empty($services)): ?>

                                                    <div class="col-lg-6 mb-3">

                                                        <label>Service Type</label>

                                                        <select class="form-control filter-select" name="services"

                                                            id="service-messages" onchange="fetch_messages()">

                                                            <?php if ($services) { ?>

                                                                <?php foreach ($services as $cus_ser) { ?>

                                                                    <option value="<?= $cus_ser->id ?>"><?= $cus_ser->title ?>

                                                                    </option>

                                                                <?php } ?>

                                                            <?php } ?>

                                                        </select>

                                                    </div>

                                                <?php endif; ?>



                                                <div class="col-lg-6 mb-3">

                                                    <label class="form-label">Copy From Customer</label>

                                                    <select onchange="fetch_messages()" id="copy_from_cus"

                                                        class="form-control filter-select">

                                                        <option value="">-Select Customer-</option>

                                                        <?php if (! empty($parent_customer)) { ?>

                                                            <?php foreach ($parent_customer as $par_customer) { ?>

                                                                <option value="<?= $par_customer->id ?>">

                                                                    <?= $par_customer->name ?>

                                                                </option>

                                                            <?php } ?>

                                                        <?php } ?>

                                                    </select>

                                                </div>

                                                <?php if (! empty($services)): ?>

                                                    <div class="col-lg-6 mb-3">

                                                        <label>Copy From Service Type</label>

                                                        <select class="form-control filter-select" id="copy-messages"

                                                            onchange="fetch_messages()">

                                                            <option value="">Select Copy Customer Service Type</option>

                                                            <?php if ($services) { ?>

                                                                <?php foreach ($services as $cus_ser) { ?>

                                                                    <option value="<?= $cus_ser->id ?>"><?= $cus_ser->title ?>

                                                                    </option>

                                                                <?php } ?>

                                                            <?php } ?>

                                                        </select>

                                                    </div>

                                                <?php endif; ?>

                                            </div>

                                            <form action="" method="post">

                                                <div class="row">

                                                    <div class="row messages m-0 p-0">

                                                    </div>



                                                    <div id="update_message_msg" class="text-center"></div>



                                                    <div class="d-flex justify-content-end">

                                                        <button id="update_msg_btn" type="submit" name="update"

                                                            class="btn-primary bg-primary">Update</button>

                                                    </div>

                                                </div>

                                            </form>

                                        </div>



                                    </div>

                                </div>

                            </div>



                        </div>

                    </form>

                </div>

            </div>

        </div>

    </div>

</div>

<script type="text/template" id="messageTemplate">

    <div class="col-lg-6 mb-3">

        <label class="form-label">{col}</label>

        <div class="position-relative">

            <textarea rows="5" class="sign-textarea w-100" name="{col}">{message}</textarea>

            <?php include "./comments-dropdown.php" ?>

        </div>

    </div>

</script>

<?php

include_once('includes/footer.php');

?>

<script>

    function fetch_services() {

        $("#update_customer_msg").html($("#spinner").html())

        var id = $('#cus_id').val()

        var formData = new FormData();

        formData.append('type', 'fetch_service_cus');

        formData.append('id', id);

        if (id) {

            $.ajax({

                type: "POST",

                url: "../includes/pages.php",

                data: formData,

                processData: false,

                contentType: false,

                dataType: "json",

                success: function (response) {

                    if (response.success) {

                        const services = response.services;



                        $('#service-messages').empty();

                        $('#copy-messages').empty();



                        $('#copy-messages').append('<option value="">Select Copy Customer Service Type</option>');



                        if (services.length > 0) {

                            services.forEach(service => {

                                const option = `<option value="${service.id}">${service.title}</option>`;

                                $('#service-messages').append(option);

                                $('#copy-messages').append(option);

                            });

                        }

                    } else {

                        flash("errorMsg", "Error fetching data!");

                    }

                },



                error: function (e) {

                    alert("AJAX request failed!");

                }

            });

        }

    }

    function fetch_messages() {

        $("#update_customer_msg").html($("#spinner").html())

        var id = $('#cus_id').val()

        var sid = $('#service-messages').val()

        var cusid = $('#copy_from_cus').val()

        var copyid = $('#copy-messages').val()

        var formData = new FormData();

        formData.append('type', 'fetch_messages_cus');

        formData.append('id', id);

        formData.append('sid', sid);

        formData.append('cusid', cusid);

        formData.append('copyid', copyid);



        $.ajax({

            type: "POST",

            url: "../includes/pages.php",

            data: formData,

            processData: false,

            contentType: false,

            dataType: "json",

            success: function (response) {

                if (response.success) {

                    if (response.messages) {

                        $(".messages").empty()

                        for (const col in response.messages) {

                            var messageTemplate = $("#messageTemplate").html()

                            messageTemplate = messageTemplate.replaceAll("{col}", col)

                                .replace("{message}", response.messages[col])

                            $(".messages").append(messageTemplate)

                        }

                    }

                } else {

                    flash("errorMsg", "Error fetching data!")

                }

            },

            error: function (e) {

                alert("AJAX request failed!");

            }

        });

    }



    $("#update_msg_btn").on("click", function (e) {

        e.preventDefault()

        $(this).prop("disabled", true);

        $("#update_message_msg").html($("#spinner").html())

        var sid = $("#service-messages").val()
        var cm = $('#copy-messages').val()

        var id = $('#cus_id').val()

        var formData = new FormData($(this).closest("form")[0]);

        formData.append('type', 'update_messages');

        formData.append('id', id);

        formData.append('sid', sid);
        formData.append('cm', cm);

        // Send the data to the server

        var that = $(this)

        $.ajax({

            type: "POST",

            url: "../includes/pages.php",

            data: formData,

            contentType: false,

            processData: false,

            dataType: "json",

            success: function (response) {

                if (response.success) {

                    flash("successMsg", "Messages updated successfully!")

                    that.prop("disabled", false);

                    $("#update_message_msg").html("")

                } else {

                    flash("errorMsg", "Error saving data!")

                }

            },

            error: function (e) {

                console.log(e.responseText)

                alert("AJAX request failed!");

            }

        });

    })

$(document).ready(function(){

    $('.filter-select').select2();

})

</script>