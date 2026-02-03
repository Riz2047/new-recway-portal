var refEle = $('.box.shadow').first();







// $('.no-orders').css('min-height', ($('.most-orders').outerHeight()-7));



// $('.no-orders').css('max-height', ($('.most-orders').outerHeight()-7));







$('.no-orders').css('min-height', 295);



$('.no-orders').css('max-height', 295);







$('.customer-select').on('change', function () {



    if ($(this).val() != 0) {



        $('.company-select').prop('disabled', true)



    } else {



        $('.company-select').prop('disabled', false)



    }



})







$('.company-select').on('change', function () {



    if ($(this).val() != 0) {



        $('.customer-select').prop('disabled', true)



    } else {



        $('.customer-select').prop('disabled', false)



    }



})







var applyClicked = false;



$('.apply').on('click', function () {



    $('#Range').hide();



    $('#Filter').hide();



    $('#Customize').hide();



    $('.active-tab').removeClass('active-tab')







    applyClicked = true;



    initiateChart('created', 'createdChart');



    initiateChart('approved', 'approvedChart');



    initiateChart('booked', 'bookedChart');



    initiateChart('canceled', 'canceledChart');



    initiateChart('customerMost', 'customerMostChart', 'bar');



    initiateChart('customerLeast', 'customerLeastChart', 'bar');



    initiateChart('company', 'companyChart', 'verticalBar');



    initiateChart('singleCompany', 'singleCompanyChart',);



})







$('#cancel-date').on('click', function () {



    $('#Range').hide();



    $('#Filter').hide();



    $('#Customize').hide();



    $('.active-tab').removeClass('active-tab')



})







$('#cancel-filter').on('click', function () {



    $('#Range').hide();



    $('#Filter').hide();



    $('#Customize').hide();



    $('.active-tab').removeClass('active-tab')



})







$('#reset-filters').on('click', function () {



    $('.customer-select').prop('selectedIndex', 0)



    $('.company-select').prop('selectedIndex', 0)



    $('.customer-select').prop('disabled', false)



    $('.company-select').prop('disabled', false)



    $('.apply').click()



})







function dateSelected(start, end) {



    $('#startDate').val(start.year + '-' + (start.month + 1) + '-' + start.date);



    $('#endDate').val(end.year + '-' + (end.month + 1) + '-' + end.date);



}







initiateChart('created', 'createdChart');



initiateChart('approved', 'approvedChart');



initiateChart('booked', 'bookedChart');



initiateChart('canceled', 'canceledChart');



initiateChart('customerMost', 'customerMostChart', 'bar');



initiateChart('customerLeast', 'customerLeastChart', 'bar');



initiateChart('company', 'companyChart', 'verticalBar');



initiateChart('singleCompany', 'singleCompanyChart',);







function initiateChart(status, chart, chartType = 'line') {



    var startDate = $('#startDate').val();



    var endDate = $('#endDate').val();



    var customerSelected = $('.customer-select').val()



    var companySelected = $('.company-select').val()



    $.ajax({



        url: '../includes/ajax.php',



        type: 'POST',



        data: { status: status, startDate, endDate, customerSelected, companySelected },



        success: function (response) {



            var jsonData = JSON.parse(response);



            if (jsonData.name) {



                createdChart(chart, jsonData.name, jsonData.orders, chartType)



            } else if (jsonData.customers) {



                $('.noorders-users').empty()



                if (jsonData.customers != 'no-data') {



                    jsonData.customers.forEach(function (customer) {



                        $('.noorders-users').append('' +



                            '<div class="d-flex justify-content-between">' +



                            '<p class="text-left f-12 w-100">' + customer.name + '</p>' +



                            '<p class="text-left f-12 w-100">' + customer.email + '</p>' +



                            '</div>')



                    })



                }



            } else if (jsonData.companies) {



                // createdChart(chart, jsonData.companies, jsonData.companyOrders, chartType)



                $('.companies-table').empty()



                if (jsonData.companies != 'no-data') {



                    jsonData.companies.forEach(function (company, index) {



                        $('.companies-table').append('' +



                            '<div class="d-flex justify-content-between">' +



                            '<p class="text-center f-14 w-100"><a href="update-customer.php?id=' + jsonData.companyLinks[index] + '">' + company + '</a></p>' +



                            '<p class="text-center f-14 w-100" style="font-weight: bold">' + jsonData.companyOrders[index] + '</p>' +



                            '</div>')



                    })



                }



            } else {



                var dataKeys = [];



                var dataValues = [];



                if (jsonData.created != null && jsonData.created != undefined) {



                    dataKeys = Object.keys(jsonData.created);



                    dataValues = Object.keys(jsonData.created).map(key => jsonData.created[key]);



                }







                // $('.created-title-date').text(dataKeys[0] + ' - ' + dataKeys[dataKeys.length - 1])



                $('.created-title-date').text($.datepicker.formatDate('d M', new Date(startDate)) + ' - ' + $.datepicker.formatDate('d M', new Date(endDate)))



                if (applyClicked) {



                    // $('.rangeTabLink').html('<i class="bi bi-calendar2-check me-2"></i>' + dataKeys[0] + ' - ' + dataKeys[dataKeys.length - 1])



                    $('.rangeTabLink').html('<i class="bi bi-calendar2-check me-2"></i>' + $.datepicker.formatDate('d M', new Date(startDate)) + ' - ' + $.datepicker.formatDate('d M', new Date(endDate)))



                }



                createdChart(chart, dataKeys, dataValues, chartType)



            }



        }



    })



}







var style = getComputedStyle(document.body);



var color = style.getPropertyValue('--text-light')



var colors = ['#80A488', '#44598A', '#C3AECE', '#F6B3B1', '#5EF4A9', '#7F8DFF']



var counts;







var initiatedCharts = { 'createdChart': '', 'approvedChart': '', 'bookedChart': '', 'canceledChart': '', 'customerMostChart': '', 'customerLeastChart': '', 'companyChart': '', 'singleCompanyChart': '' };



function createdChart(chart, xValues, yValues, chartType) {



    if (!Array.isArray(yValues)) {



        yValues = [yValues];



    }



    counts = document.getElementsByClassName('count')



    for (const element of counts) {



        var chartCount = element.getAttribute('data-chart')



        if (chartCount == chart) {



            document.querySelector('h1[data-chart="' + chartCount + '"]').innerText = yValues.reduce((a, b) => a + b, 0)



        }



    }







    if (initiatedCharts[chart] !== '') {



        initiatedCharts[chart].destroy()



    }



    var chartType2;



    if (chartType === 'verticalBar') {



        chartType = 'bar';



        chartType2 = 'verticalBar';



    }



    initiatedCharts[chart] = new Chart(chart, {



        type: chartType,



        data: {



            labels: xValues,



            datasets: [{



                fill: false,



                lineTension: 0,



                backgroundColor: color,



                borderColor: color,



                data: yValues,



                pointRadius: 3,



                barThickness: chartType === 'bar' && chartType2 !== 'verticalBar' ? 80 : 20,



                // borderRadius: chartType === 'bar' && chartType2 !== 'verticalBar' ? 80 : 20,



            }]



        },



        options: {



            indexAxis: chartType === 'bar' && chartType2 !== 'verticalBar' ? 'y' : 'x',



            plugins: {



                legend: {



                    display: false,



                }



            },



            scales: {



                x: {



                    type: 'time',



                    time: {



                        unit: 'day',



                        stepSize: 5,



                        tooltipFormat: 'D MMM'



                    }



                },



                y: {



                    ticks: {



                        min: 0,



                        max: yValues[yValues.length],



                        stepSize: 1,



                    },



                },



            }



        }



    });







    if (chartType === 'bar') {



        initiatedCharts[chart].options.scales.y = {



            type: 'category',



            labels: [xValues]



        };



        initiatedCharts[chart].options.scales.x = {



            ticks: {



                min: 0,



                max: yValues[yValues.length],



                stepSize: 1,



            },



        };



        initiatedCharts[chart].update()



    }







    if (chartType2 === 'verticalBar') {



        initiatedCharts[chart].options.scales.x = {



            type: 'category',



            labels: xValues



        };



        initiatedCharts[chart].options.scales.y = {



            ticks: {



                min: 0,



                max: yValues[yValues.length],



                stepSize: 1,



            },



        };



        initiatedCharts[chart].update()



    }



}







xValues = [100, 200, 300, 400, 500, 600, 700, 800, 900, 1000];







function bookedChart() {



    new Chart("myChart2", {



        type: "line",



        data: {



            labels: xValues,



            datasets: [{



                data: [860, 1140, 1060, 1060, 1070, 1110, 1330, 2210, 7830, 2478],



                borderColor: "red",



                fill: false



            }, {



                data: [1600, 1700, 1700, 1900, 2000, 2700, 4000, 5000, 6000, 7000],



                borderColor: "green",



                fill: false



            }, {



                data: [300, 700, 2000, 5000, 6000, 4000, 2000, 1000, 200, 100],



                borderColor: "blue",



                fill: false



            }]



        },



        options: {



            legend: { display: false }



        }



    });



}







$(document).click(function (event) {



    if (



        !$(event.target).closest('#Range').length



        && !$(event.target).closest('#Filter').length



        && !$(event.target).closest('#Customize').length



        && !$(event.target).hasClass('rangeTabLink')



        && !$(event.target).hasClass('filterTabLink')



        && !$(event.target).hasClass('customizeTabLink')



        && !$(event.target).parent('a').hasClass('rangeTabLink')



        && !$(event.target).parent('a').hasClass('filterTabLink')



        && !$(event.target).parent('a').hasClass('customizeTabLink')



    ) {



        if ($('#Range').css('display') === 'block') {



            $('#Range').hide();



        }







        if ($('#Filter').css('display') === 'block') {



            $('#Filter').hide();



        }







        if ($('#Customize').css('display') === 'block') {



            $('#Customize').hide();



        }







        $('.active-tab').removeClass('active-tab')



    }



});







function toggleRangeTab(e) {



    $('#Filter').css('display', 'none');



    $('#Customize').css('display', 'none');



    $('.active-tab').removeClass('active-tab')



    if ($('#Range').css('display') === 'block') {



        $('#Range').css('display', 'none');



        $(e.target).removeClass('active-tab')



    } else {



        $('#Range').css('display', 'block')



        $(e.target).addClass('active-tab')



    }



    $('.bi').removeClass('active-tab')



}







function toggleFilterTab(e) {



    $('#Range').css('display', 'none');



    $('#Customize').css('display', 'none');



    $('.active-tab').removeClass('active-tab')



    if ($('#Filter').css('display') === 'block') {



        $('#Filter').css('display', 'none');



        $(e.target).removeClass('active-tab')



    } else {



        $('#Filter').css('display', 'block')



        $(e.target).addClass('active-tab')



    }



    $('.bi').removeClass('active-tab')



}







function toggleCustomizeTab(e) {



    $('#Range').css('display', 'none');



    $('#Filter').css('display', 'none');



    $('.active-tab').removeClass('active-tab')



    if ($('#Customize').css('display') === 'block') {



        $('#Customize').css('display', 'none');



        $(e.target).removeClass('active-tab')



    } else {



        $('#Customize').css('display', 'block')



        $(e.target).addClass('active-tab')



    }



    $('.bi').removeClass('active-tab')



}







function openTab(evt, tabName) {



    var i, tabcontent, tablinks;



    tabcontent = document.getElementsByClassName("tabcontent");



    for (i = 0; i < tabcontent.length; i++) {



        tabcontent[i].style.display = "none";



    }



    tablinks = document.getElementsByClassName("tablinks");



    for (i = 0; i < tablinks.length; i++) {



        tablinks[i].className = tablinks[i].className.replace(" active-tab", "");



    }



    document.getElementById(tabName).style.display = "block";



    evt.currentTarget.className += " active-tab";



}







$('.export').click(function () {







    var startDate = $('#startDate').val();



    var endDate = $('#endDate').val();



    var customerSelected = $('.customer-select').val()



    var companySelected = $('.company-select').val()







    $.ajax({



        url: '../includes/ajax.php',



        type: 'POST',



        dataType: 'json',



        data: { export: 'true', startDate, endDate, customerSelected, companySelected },



        success: function (response) {



            var $a = $("<a>");



            $a.attr("href", response.file);



            $("body").append($a);



            $a.attr("download", "export.xlsx");



            $a[0].click();



            $a.remove();



        }



    })



})



$('.new_export').click(function () {



    var formData = new FormData();



    var customerSelected = $('.customer-select').val()



    var companySelected = $('.company-select').val()



    var service = $('.service-select').val()



    var lastStatus = $('.lastStatus-select').val()



    var create_from = $('#order_created_from').val();



    var create_to = $('#order_created_to').val();



    var startDate = $('#startDate').val();



    var endDate = $('#endDate').val();



    var columns = $('input[name="customize_columns[]"]');



    var a = 0;



    var alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';



    



    $(columns).each(function (i, v) {

if ($(this).is(':checked') && $(this).parent().parent().css('display') !== 'none') {

        if ($(this).is(':checked')) {



            var id = $(this).data('id');



            if (id != '') {



                id = `[` + id + `]`



            }



            if (id === undefined) {



                id = '';



            }



            var letter = '';



    



            // Get the first letter



            letter += alphabet.charAt(a % 26);



    



            // If a is greater than or equal to 26, add a second letter



            if (a >= 26) {



                letter = alphabet.charAt(Math.floor(a / 26) - 1) + letter;



            }



    



            formData.append('columns_arr[' + letter + ']' + id, $(this).val());



            a++;



            id = '';



        }
        }



    });



    



    formData.append('new_export', 'true');



    formData.append('startDate', startDate);



    formData.append('endDate', endDate);



    formData.append('create_from', create_from);



    formData.append('create_to', create_to);



    formData.append('service_category', service);



    formData.append('customerSelected', customerSelected);



    formData.append('companySelected', companySelected);



    formData.append('lastStatus', lastStatus);



    $.ajax({



        type: 'POST',



        url: '../includes/ajax.php',



        dataType: 'json',



        contentType: false,



        processData: false,

 

        data: formData,



        success: function (response) {

    var start = startDate || "start";

    var end = endDate || "end";

    var filename = "";



    if (companySelected && companySelected != 0 && companySelected.trim() !== "") {

        filename = companySelected + "_" + start + "_to_" + end + ".xlsx";

    } else {

        filename = "all_export_" + start + "_to_" + end + ".xlsx";

    }

            var $a = $("<a>");



            $a.attr("href", response.file);



            $("body").append($a);



            $a.attr("download", filename);



            $a[0].click();



            $a.remove();



        }



    })



})