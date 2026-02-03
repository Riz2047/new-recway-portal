$(document).ready(function () {

    $('#sidebarCollapse').on('click', function () {

        $('#sidebar').toggleClass('active');

        $('#sidebar').toggleClass('active-small');

        $("#content").toggleClass('ml');

        $("#content").toggleClass('ml-small');

        $(".header").toggleClass('lg-desc-2');



    });

});



// Data Table

var table;

var columnsRemove = [0, 1, 8, 9, 10];

var hiddenColumns = {};

hiddenColumns[6] = "VASC ID";

hiddenColumns[8] = "SSN";

// hiddenColumns[9] = "Company";

// hiddenColumns[10] = "Customer";

// hiddenColumns[11] = "Staff";

// hiddenColumns[13] = "Reported";

// hiddenColumns[14] = "Invoice Sent";

// hiddenColumns[15] = "Economy";

// hiddenColumns[16] = "Criminal Record";

// hiddenColumns[17] = "Social Media";

hiddenColumns[19] = "Background Date";

hiddenColumns[20] = "Invoice Date";

// hiddenColumns[20] = "Order Created";

// hiddenColumns[21] = "Interview Date";

hiddenColumns[23] = "Delivery Date";

hiddenColumns[24] = "Service Type";



function format(data, index) {



    var toShow = `

        <div class="accordian-drop" style=""><div class="row mt-1 f-14" style="width: 90% !important;">`;

    for (var key in hiddenColumns) {

        toShow += `<div class="col-md-6">

                    <p class="">

                        <strong><span class="f-16">‣</span> ${hiddenColumns[key]}: </strong><span class="${key == 19 ? 'invoice_date ' : ''} ${key == 18 ? 'background_check_date ' : ''}  dt-${hiddenColumns[key].toLowerCase().replace(/ /g, '')}">${(data[key])}</span>

                    </p>

                </div>`;

    }

    toShow += `</div></div>`;

    return toShow;

}



$(document).ready(function () {

    $.fn.dataTable.ext.order['checkbox'] = function (settings, col) {

        return this.api().column(col, { order: 'index' }).nodes().map(function (td, i) {

            return $('input', td).prop('checked') ? '1' : '0';

        });

    };



    // Surface DataTables errors to console to debug init/AJAX issues
    if ($.fn && $.fn.dataTable) {
        $.fn.dataTable.ext.errMode = 'console';
    }

    var $tbl = $('#dataTable');
    if ($tbl.length === 0) {
        console.warn('DataTable element #dataTable not found');
        return;
    }

    if ($tbl.attr('data-table') === 'candidate') {

        // Clear any pre-rendered rows to switch to server-side mode cleanly
        $tbl.find('tbody').empty();

        console.log('Initializing staff candidates DataTable (server-side)');
        table = $tbl.DataTable({

            language: { search: "", searchPlaceholder: "Search..." },

            dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>t<'row'<'col-sm-6'i><'col-sm-6'p>><'row'<'col-sm-12'B>>",

            buttons: [
                {
                    extend: 'excelHtml5',
                    text: 'Export All',
                    titleAttr: 'Export all filtered rows',
                    action: function () {
                        var url = '../includes/pages.php';
                        var form = document.createElement('form');
                        form.method = 'POST';
                        form.action = url;
                        var add = function(name, value){ var i=document.createElement('input'); i.type='hidden'; i.name=name; i.value=value; form.appendChild(i); };
                        add('action','export_candidates_excel');
                        var urlParams = new URLSearchParams(window.location.search);
                        add('service', urlParams.get('service') || '');
                        add('status', urlParams.get('status') || '');
                        // staff view is scoped by session on server, no need to send staff_id
                        add('fil_place', $('#fil_place').val() || '');
                        add('fil_can', $('#fil_can').val() || '');
                        add('fil_com', $('#fil_com').val() || '0');
                        add('fil_cus', $('#fil_cus').val() || '');
                        add('order_created_from', $('#order_created_from').val() || '');
                        add('order_created_to', $('#order_created_to').val() || '');
                        add('interview_date_from', $('#interview_date_from').val() || '');
                        add('interview_date_to', $('#interview_date_to').val() || '');
                        add('fil_status', $('#fil_status').val() || '');
                        var $container = $(table.table().container());
                        var searchVal = $container.find('.custom-search-input').val() || '';
                        add('search_value', searchVal);
                        document.body.appendChild(form);
                        form.submit();
                        setTimeout(function(){ document.body.removeChild(form); }, 1000);
                    }
                }
            ],

            scrollX: true,
            "order": [[14, 'desc']],
            pageLength: 10,
            processing: true,
            serverSide: true,
            search: false,
            ajax: {
                url: '../includes/pages.php',
                type: 'POST',
                data: function (d) {
                    console.log('DT ajax.data preparing payload');
                    d.action = 'get_staff_candidate_data';
                    var urlParams = new URLSearchParams(window.location.search);
                    d.service = urlParams.get('service') || '';
                    d.status = urlParams.get('status') || '';
                    
                    d.fil_place = $('#fil_place').val() || '';
                    d.fil_can = $('#fil_can').val() || '';
                    d.fil_com = $('#fil_com').val() || '';
                    d.fil_cus = $('#fil_cus').val() || '';
                    d.order_created_from = $('#order_created_from').val() || '';
                    d.order_created_to = $('#order_created_to').val() || '';
                    d.interview_date_from = $('#interview_date_from').val() || '';
                    d.interview_date_to = $('#interview_date_to').val() || '';
                    d.fil_status = $('#fil_status').val() || '';

                    return d;
                },
                dataSrc: function(json) {
                    console.log('DT ajax.dataSrc raw', json);
                    try {
                        if (json && typeof json === 'string') {
                            json = JSON.parse(json);
                        }
                    } catch(e) {
                        console.error('Failed to parse DataTables JSON', e);
                        return [];
                    }
                    if (!json || typeof json !== 'object') {
                        console.error('Invalid JSON from server', json);
                        return [];
                    }
                    if (json.error) {
                        console.error('Server error:', json.error);
                        return [];
                    }
                    if (json.recordsTotal !== undefined) {
                        var v = document.getElementById('total-orders');
                        if (v) v.textContent = json.recordsTotal;
                    }
                    console.log('DT ajax.dataSrc final length', (json.data||[]).length);
                    return json.data || [];
                },
                error: function (xhr, error, thrown) {
                    console.error('DataTable AJAX error:', error, thrown);
                    console.error('Response text:', xhr.responseText);
                    alert('Failed to load candidates. Check console for details.');
                },
                complete: function() {
                    console.log('DT ajax complete');
                    $('.dropdownBtn').each(function() {
                        if (!$(this).data('bs.dropdown')) {
                            new bootstrap.Dropdown($(this)[0]);
                        }
                    });
                }
            },
            columnDefs: [
                {
                    type: 'checkbox',
                    targets: [10, 13]
                },
                {
                    className: 'dt-control',

                    orderable: false,
                    targets: 0
                },
                {
                    orderable: false,
                    targets: 1
                },
                {
                    // Hide Background Check Date column (index 17) by default
                    visible: false,
                    targets: 17
                },
                // No custom renderers required when server returns staff-ordered columns
            ],
            createdRow: function(row, data, dataIndex) {
                $(row).find('td').each(function(index) {
                    var $cell = $(this);
                    if (index !== 0 && index !== 2) {
                        $cell.addClass('f-14');
                    }
                    switch(index) {
                        case 0: $cell.addClass('dt-control'); break;
                        case 5: $cell.addClass('place_show'); break;
                        case 6:
                            $cell.addClass('name_show');
                            // Attach tooltip handlers lazily (no pre-render thousands)
                            var $span = $cell.find('.name_tooltip');
                            if ($span.length) {
                                $span.on('mouseenter', function(){ tooltipHover(this); });
                                $span.on('mouseleave', function(){ tooltipLeave(this); });
                            }
                            break;
                        case 7: $cell.addClass('customer_show'); break;
                        case 8: $cell.addClass('company_show'); break;
                        case 9: $cell.addClass('staff_show'); break;
                        case 10: $cell.addClass('reported_sm_show'); break;
                        case 11: $cell.addClass('d-flex justify-content-center status_show'); break;
                        // case 12: /* Identity */ break;
                        case 12: $cell.addClass('invoice_sent_show'); break;
                        case 13: $cell.addClass('interview_date_show'); break;
                        case 14: $cell.addClass('economy_show'); break;
                        case 15: $cell.addClass('criminal_record_show'); break;
                        case 16: $cell.addClass('social_record_show'); break;
                        case 17: $cell.addClass('background_check_date'); break;
                        case 18: $cell.addClass('order_created_show'); break;
                        case 19: $cell.addClass('archive_date_show'); break;
                        case 20: /* Delivery Date */ break;
                        case 21: /* Service Type */ break;
                    }
                });
            }
        });

        // Bind select-all functionality
        table.on('init.dt', function(){ 
            updateSelectAllState();
            // Check for service=3 and hide/show columns after table initialization
            var url = new URL(window.location.href);
            var serviceID = url.searchParams.get('service');
            var serviceIDNum = serviceID ? parseInt(serviceID, 10) : null;
            if (serviceIDNum === 3) {
                // Hide Place column (index 5) for service=3
                table.column(5).visible(false);
                // Hide Interview Date column (index 13) for service=3
                table.column(13).visible(false);
                // Show Background Check Date column (index 17) for service=3
                table.column(17).visible(true);
                // Show Delivery Date column (index 20) for service=3
                table.column(20).visible(true);
            } else if (serviceIDNum !== null && serviceIDNum !== '' && serviceIDNum !== 3) {
                // Hide Background Check Date column when a specific service (other than 3) is selected
                table.column(17).visible(false);
                // Hide Delivery Date column when a specific service (other than 3) is selected
                table.column(20).visible(false);
            } else {
                // Show Delivery Date column in "All orders" view (keep Background Check Date hidden)
                table.column(17).visible(false);
                table.column(20).visible(true);
            }
        });
        table.on('draw.dt', function(){ 
            updateSelectAllState();
            // Ensure columns stay hidden/shown for service=3 after each draw
            var url = new URL(window.location.href);
            var serviceID = url.searchParams.get('service');
            var serviceIDNum = serviceID ? parseInt(serviceID, 10) : null;
            if (serviceIDNum === 3) {
                table.column(5).visible(false);
                // Hide Interview Date column (index 13) for service=3
                table.column(13).visible(false);
                table.column(17).visible(true);
                table.column(20).visible(true);
            } else if (serviceIDNum !== null && serviceIDNum !== '' && serviceIDNum !== 3) {
                // Hide columns when a specific service (other than 3) is selected
                table.column(17).visible(false);
                table.column(20).visible(false);
            } else {
                // Show Delivery Date column in "All orders" view (keep Background Check Date hidden)
                table.column(17).visible(false);
                table.column(20).visible(true);
            }
        });
        // Do not toggle columns by fixed indices; staff header is fixed and mapped server-side.



        // Add event listener for opening and closing details

        table.on('click', 'td.dt-control', function (e) {

            let tr = e.target.closest('tr');

            let row = table.row(tr);



            if (row.child.isShown()) {

                // This row is already open - close it

                row.child.hide();

            }

            else {

                // Open this row

                row.child(format(row.data(), row.index())).show();

            }

        });

    } else if ($('#dataTable').attr('data-table') === 'customer') {

        // Switch to server-side for customers
        var $ct = $('#dataTable');
        $ct.find('tbody').empty();
        table = $ct.DataTable({
            language: { search: "", searchPlaceholder: "Search..." },

            scrollX: true,
            order: [],
            pageLength: 10,
            processing: true,
            serverSide: true,
            ajax: {
                url: '../includes/pages.php',
                type: 'POST',
                data: function (d) {
                    d.action = 'get_staff_customers_data';
                    return d;
                },
                error: function (xhr, error, thrown) {
                    console.error('Customers DT AJAX error:', error, thrown);
                    console.error('Response text:', xhr.responseText);
                }
            },
            columnDefs: [
                { orderable: false, targets: 0 }
            ]
        });
        table.on('init.dt', function(){ updateSelectAllState(); });
        table.on('draw.dt', function(){ updateSelectAllState(); });

    } else {

        // Default tables: switch to server-side for places view
        var $dt = $('#dataTable');
        if (window.location.pathname.endsWith('/staff/places.php')) {
            $dt.find('tbody').empty();
            table = $dt.DataTable({
                language: { search: "", searchPlaceholder: "Search..." },
                dom: "<'row'<'col-sm-6'l><'col-sm-6'f>>t<'row'<'col-sm-6'i><'col-sm-6'p>><'row'<'col-sm-12'B>>",
                scrollX: false,
                order: [[2,'asc']],
                pageLength: 10,
                processing: true,
                serverSide: true,
                buttons: [
                    {
                        extend: 'excelHtml5',
                        text: 'Export All',
                        titleAttr: 'Export all places',
                        action: function () {
                            var url = '../includes/pages.php';
                            var form = document.createElement('form');
                            form.method = 'POST';
                            form.action = url;
                            var add = function(name, value){ var i=document.createElement('input'); i.type='hidden'; i.name=name; i.value=value; form.appendChild(i); };
                            add('action','export_places_excel');
                            // include search if using custom input
                            var $container = $(table.table().container());
                            var searchVal = $container.find('.custom-search-input').length ? $container.find('.custom-search-input').val() : '';
                            add('search_value', searchVal || '');
                            document.body.appendChild(form);
                            form.submit();
                            setTimeout(function(){ document.body.removeChild(form); }, 1000);
                        }
                    }
                ],
                ajax: {
                    url: '../includes/pages.php',
                    type: 'POST',
                    data: function(d){ d.action = 'get_staff_places_data'; return d; },
                    error: function(xhr, error, thrown){ console.error('Places DT error:', error, thrown); console.error(xhr.responseText); }
                }
            });
        } else {
            table = $dt.DataTable({
                language: { search: "", searchPlaceholder: "Search..." },
                scrollX: false,
                order: [],
                pageLength: 100
            });
        }

    }

    // Lazy tooltip loader for Name column (staff) – mirrors admin2 behavior
    $(document).on('mouseenter', '.name_tooltip, [data-tool-id]', function () {
        var $t = $(this);
        var tipId = $t.data('tool-id');
        var candidateId = $t.data('order-id');
        var $existing = $('#' + tipId);
        if ($existing.length === 0 && candidateId) {
            $.ajax({
                type: 'POST',
                url: '../includes/pages.php',
                data: { action: 'get_candidate_history', id: candidateId },
                success: function (resp) {
                    try { if (typeof resp === 'string') resp = JSON.parse(resp); } catch (e) { resp = []; }
                    var html = '<span class="his_tooltiptext text-left pl-4 pr-3 pt-2 pb-2" id="' + tipId + '">';
                    html += '<h5><b><u>Order History</u></b></h5>';
                    if (resp && resp.length) {
                        resp.forEach(function (h) {
                            html += '<div class="mt-3 mb-3">';
                            html += '<div class="time">' + h.time + '</div>';
                            html += '<p class="m-0">' + h.desc + '</p>';
                            if (h.comment) html += '<i><small class="m-0 p-0">Comment: ' + h.comment + '</small></i>';
                            html += '</div>';
                        });
                    }
                    html += '</span>';
                    $('body').append(html);
                    // Bind hide on leave directly for dynamically created card
                    $('#' + tipId).on('mouseleave', function(){
                        var $card = $(this);
                        var $trigger = $('[data-tool-id="' + tipId + '"]');
                        if (!$trigger.is(':hover')) {
                            $card.stop(true, true).animate({ opacity: 0 }, {
                                duration: 200,
                                step: function(now){ $(this).css('transform','translateX(' + (now*100) + '%)'); },
                                complete: function(){ $(this).css('visibility','hidden'); }
                            });
                        }
                    });
                    // Show immediately on first load
                    var $newTip = $('#' + tipId);
                    $newTip.css('visibility', 'visible').animate({ opacity: 1 }, {
                        duration: 300,
                        step: function (now) { $(this).css('transform', 'translateX(' + (1 - now) * 100 + '%)'); }
                    });
                }
            });
        } else if ($existing.length) {
            // If tooltip already exists, just show it again
            $existing.stop(true, true).css('visibility', 'visible').animate({ opacity: 1 }, {
                duration: 200,
                step: function (now) { $(this).css('transform', 'translateX(' + (1 - now) * 100 + '%)'); }
            });
        }
    });
    // Also ensure hide when mouse leaves any visible card and not on trigger
    $(document).on('mouseleave', '.his_tooltiptext', function(){
        var id = $(this).attr('id');
        var $trigger = $('[data-tool-id="' + id + '"]');
        if (!$trigger.is(':hover')) {
            $(this).stop(true, true).animate({ opacity: 0 }, {
                duration: 200,
                step: function(now){ $(this).css('transform','translateX(' + (now*100) + '%)'); },
                complete: function(){ $(this).css('visibility','hidden'); }
            });
        }
    });

});



$(document).ready(function () {

    let btn = $('.state__list');



    btn.click(function (e) {

        $('.state__list button').removeClass("active-port");

        e.target.classList.add("active-port");



        let selector = $(e.target).attr('data-filter');

        $('.reports .grid').isotope({

            filter: selector

        });

        return false;

    });



});



// Tooltip

$(function () {

    $('[data-toggle="tooltip"]').tooltip()

})



$('.sidebar-content a').each(function () {

    var current_path = window.location.href.split('/').pop();

    if (current_path == "") {

        $('.sidebar-content li:eq(0)').addClass('active');

    }

    if (current_path === "index.php") {

        if ($(this).attr('href') === current_path) {

            $(this).find('.menu-icon').addClass('text-dark-blue');

            $(this).find('.menu-title').addClass('text-dark-blue');

        }

    } else {

        if ($(this).attr('href') === current_path) {

            $(this).find('.menu-icon').addClass('text-dark-blue');

            $(this).find('.menu-title').addClass('text-dark-blue');



            $('.menu-icon:eq(0)').find('path:eq(0)').css('fill', 'var(--light-blue)')

            $('.menu-icon:eq(0)').find('path:eq(1)').css('fill', '#bebebe')

            $('.menu-icon:eq(0)').find('path:eq(2)').css('fill', 'var(--light-blue)')

            $('.menu-icon:eq(0)').find('path:eq(3)').css('fill', '#bebebe')

            $('.menu-title:eq(0)').css('color', 'var(--light-blue)')

        }

    }

})



// Delete Candidates (Select-all: only current page, maintain indeterminate state)
function updateSelectAllState() {
	if (!table) { return; }
	var rows = table.rows({ page: 'current' }).nodes();
	var $checks = $('input.d-check:not(#delete-all):not(:disabled)', rows);
	var total = $checks.length;
	var checked = $checks.filter(':checked').length;
	var selectAll = document.getElementById('delete-all');
	if (!selectAll) { return; }
	selectAll.checked = (total > 0 && checked === total);
	selectAll.indeterminate = (checked > 0 && checked < total);
}

$(document).on('click', '#delete-all', function () {
	if (!table) { return; }
	var rows = table.rows({ page: 'current' }).nodes();
	$('input.d-check:not(#delete-all):not(:disabled)', rows).prop('checked', this.checked);
	updateSelectAllState();
});

// Sync header checkbox when any row checkbox on current page changes
$(document).on('change', 'input.d-check', function () {
	if ($(this).attr('id') === 'delete-all') { return; }
	updateSelectAllState();
});

// Re-evaluate header checkbox state on each draw (pagination, filter, sort)
$(document).on('draw.dt', function () {
	updateSelectAllState();
});



$(".d-check").on("click", function () {

    var bulb = false;

    $(".d-check").each(function () {

        if ($(this).is(":checked")) {

            $(".d-text").css("display", "flex");

            $(".d-text2").css("display", "flex");

            $(".d-text3").css("display", "flex");

            $(".d-text4").css("display", "flex");

            $(".d-group").css("display", "flex");

            $(".d-parent").css("display", "flex");

            bulb = true;

        }

    })



    if (!bulb) {

        $(".d-text").css("display", "none");

        $(".d-text2").css("display", "none");

        $(".d-text3").css("display", "none");

        $(".d-text4").css("display", "none");

        $(".d-group").css("display", "none");

        $(".d-parent").css("display", "none");

    }

})



$('.d-text').on('click', function () {

    if (confirm("Are you sure you want to delete?")) {

        $('#d-form').submit();

    }

})



$('.d-text2').on('click', function () {

    $('#d-form').attr('action', 'change-staff-all.php')

    $('#d-form').submit();

})



$('.d-text3').on('click', function () {

    $('#d-form').attr('action', 'change-customer-all.php')

    $('#d-form').submit();

})

$('.d-text4').on('click', function () {

    $('#d-form').attr('action', 'change_status_all.php')

    $('#d-form').submit();

})

$('.d-group').on('click', function () {

    $('#d-form').attr('action', 'groups.php')

    $('#d-form').submit();

})

$('.d-parent').on('click', function () {

    $('#d-form').attr('action', 'change_parent_customer.php')

    $('#d-form').submit();

})



// var $wrapper = $('#change-status');

// var selected = $wrapper.val();

// var opts_list = $wrapper.find('option');

//

// opts_list.sort( function (a, b) {

//   return +a.getAttribute('data-sort') - +b.getAttribute('data-sort');

// })

// $wrapper.html('').append( opts_list );

// $wrapper.val(selected)



function readCookie(name) {

    var c = document.cookie.split('; '),

        cookies = {}, i, C;



    for (i = c.length - 1; i >= 0; i--) {

        C = c[i].split('=');

        cookies[C[0]] = C[1];

    }



    return cookies[name];

}



var lang_en = document.querySelector('#lang-en');

var lang_sv = document.querySelector('#lang-sv');



if (readCookie('googtrans') == undefined) {

    lang_en.style.pointerEvents = 'none';

    lang_en.classList.add('black-white')



} else {

    lang_sv.style.pointerEvents = 'none';

    lang_sv.classList.add('black-white')

}



lang_en.addEventListener('click', function () {

    lang_en.style.pointerEvents = 'none';

    lang_en.classList.add('black-white')

    lang_sv.style.pointerEvents = '';

    lang_sv.classList.remove('black-white')

})

lang_sv.addEventListener('click', function () {

    lang_sv.style.pointerEvents = 'none';

    lang_sv.classList.add('black-white')

    lang_en.style.pointerEvents = '';

    lang_en.classList.remove('black-white')

})





function googleTranslateElementInit2() {

    new google.translate.TranslateElement({

        pageLanguage: 'en',

        autoDisplay: false

    }, 'google_translate_element2');

}



/* <![CDATA[ */

eval(function (p, a, c, k, e, r) {

    e = function (c) {

        return (c < a ? '' : e(parseInt(c / a))) + ((c = c % a) > 35 ? String.fromCharCode(c + 29) : c.toString(36))

    };

    if (!''.replace(/^/, String)) {

        while (c--) r[e(c)] = k[c] || e(c);

        k = [function (e) {

            return r[e]

        }];

        e = function () {

            return '\\w+'

        };

        c = 1

    }

    while (c--) if (k[c]) p = p.replace(new RegExp('\\b' + e(c) + '\\b', 'g'), k[c]);

    return p

}('6 7(a,b){n{4(2.9){3 c=2.9("o");c.p(b,f,f);a.q(c)}g{3 c=2.r();a.s(\'t\'+b,c)}}u(e){}}6 h(a){4(a.8)a=a.8;4(a==\'\')v;3 b=a.w(\'|\')[1];3 c;3 d=2.x(\'y\');z(3 i=0;i<d.5;i++)4(d[i].A==\'B-C-D\')c=d[i];4(2.j(\'k\')==E||2.j(\'k\').l.5==0||c.5==0||c.l.5==0){F(6(){h(a)},G)}g{c.8=b;7(c,\'m\');7(c,\'m\')}}', 43, 43, '||document|var|if|length|function|GTranslateFireEvent|value|createEvent||||||true|else|doGTranslate||getElementById|google_translate_element2|innerHTML|change|try|HTMLEvents|initEvent|dispatchEvent|createEventObject|fireEvent|on|catch|return|split|getElementsByTagName|select|for|className|goog|te|combo|null|setTimeout|500'.split('|'), 0, {}))

/* ]]> */



if ((window.location.href).split('/').pop() !== "messages.php") {

    localStorage.removeItem("posStorage");

}



$(function () {

    if ($("#stats_date").length > 0) {

        // alert()

        var picker = $('#stats_date').daterangepicker({

            autoUpdateInput: false,

            // opens: 'embed'

        }, function (start, end, label) {

            // console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));

            var id = $('#cus_id').val();

            $.ajax({

                type: "POST",

                url: '../includes/ajax.php',

                data: { "filter": true, "id": id, "start": start.format('YYYY-MM-DD'), "end": end.format('YYYY-MM-DD') },

                success: function (response) {

                    // console.log(response)

                    if (response != null || response != "") {

                        response = JSON.parse(response);

                        // console.log(response)

                        $('#total_orders_count').text(response.total)

                        $('#pending > td').text(response.pending)

                        $('#booked > td').text(response.booked)

                        $('#approved > td').text(response.approved)

                        $('#interrupted > td').text(response.interrupted)

                        $('#investigation > td').text(response.investigation)

                        $('#denied > td').text(response.denied)

                        $('#show > td').text(response.show)

                        $('#canceled > td').text(response.canceled)

                        $('#answer > td').text(response.answer)

                    }

                    // $('#total_orders_count').text(response);

                }

            });

        });

    }



    // picker.data('daterangepicker').show();

});



$(function () {

    if ($("#history_date").length) {

        var picker = $('#history_date').daterangepicker({

            autoUpdateInput: false,

            // opens: 'embed'

        }, function (start, end, label) {

            // console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));

            var id = $('#cus_id').val();

            $.ajax({

                type: "POST",

                url: '../includes/ajax.php',

                data: { "history": true, "start": start.format('YYYY-MM-DD'), "end": end.format('YYYY-MM-DD') },

                success: function (response) {

                    var data = JSON.parse(response);

                    $('.history-data .col-lg-12').remove()

                    data.forEach(function (datum) {

                        var invoice_date = datum.invoice_date != null ? $.datepicker.formatDate('M dd, yy', new Date(datum.invoice_date)) : 'Null'



                        $('.history-data').append('' +

                            '<div class="col-lg-12 mt-3">\n' +

                            '                                    <a href="history-detail.php?id=' + datum.id + '" style="text-decoration: none">\n' +

                            '                                        <div class="total-card shadow-sm text-dark">\n' +

                            '                                            <div class="d-flex history-cards justify-content-between align-items-center">\n' +

                            '                                                <p class="w-500 p-0 m-0">' + datum.company + '</p>\n' +

                            '                                                <p class="w-500 p-0 m-0">' + datum.title + '</p>\n' +

                            '                                                <p class="w-500 p-0 m-0">' + datum.order_id + '</p>\n' +

                            '                                                <div>\n' +

                            '                                                    <p class="p-0 m-0">Invoice Date</p>\n' +

                            '                                                    <h1 class="p-0 m-0 f-16 w-800">' + invoice_date + '</h1>\n' +

                            '                                                </div>\n' +

                            '                                                <div>\n' +

                            '                                                    <p class="p-0 m-0">Created</p>\n' +

                            '                                                    <h1 class="p-0 m-0 f-16 w-800">' + $.datepicker.formatDate('M dd, yy', new Date(datum.created)) + '</h1>\n' +

                            '                                                </div>\n' +

                            '                                                <div>\n' +

                            '                                                    <p class="p-0 m-0">' + statuses[datum.status] + '</p>\n' +

                            '                                                    <h1 class="p-0 m-0 f-16 w-800">' + $.datepicker.formatDate('M dd, yy', new Date(datum.status_date)) + '</h1>\n' +

                            '                                                </div>\n' +

                            '                                            </div>\n' +

                            '                                        </div>\n' +

                            '                                    </a>\n' +

                            '                                </div>')

                    })

                }

            });

        });

    }



    // picker.data('daterangepicker').show();

});



// In your Javascript (external .js resource or <script> tag)

$(document).ready(function () {



    if ($(".filter-select").length) {

        $('.filter-select').select2();

    }

    $('.select2tag').select2({

        tags: true

    });



    // $('.candidates-table').find('.row').eq(0).append('<input class="sign-input w-25 ms-2 mb-1 mt-0" type="text" placeholder="Filter Invoice Date" name="stats_date" id="invoice_date_filter">')

    // $('.history-table').find('.row').eq(0).append('<input class="sign-input w-25 ms-2 mb-1 mt-0" type="text" placeholder="Filter Invoice Date" name="stats_date" id="invoice_date_filter">')



    // Filter Interview Date

    $('#invoice_date_filter').ready(function () {

        $(function () {

            if ($("#invoice_date_filter").length) {

                var picker = $('#invoice_date_filter').daterangepicker({

                    autoUpdateInput: false,

                    // opens: 'embed'

                }, function (start, end, label) {

                    startInvoice = start;

                    endInvoice = end;

                    filterCol = 19;

                    table.draw()

                });

            }



            // picker.data('daterangepicker').show();

        });

    });



    $('#created_date_filter').ready(function () {

        $(function () {

            if ($("#created_date_filter").length) {

                var picker = $('#created_date_filter').daterangepicker({

                    autoUpdateInput: false,

                    // opens: 'embed'

                }, function (start, end, label) {

                    startInvoice = start;

                    endInvoice = end;

                    filterCol = 18

                    table.draw()

                });

            }



            // picker.data('daterangepicker').show();

        });

    });

});



// $('body').append('<input type="text" id="min" name="min">')

// $('body').append('<input type="text" id="max" name="max">')



// Create date inputs

var minDate;

var maxDate;



// minDate = new DateTime($('#min'), {

//   format: 'MMMM Do YYYY'

// });

// maxDate = new DateTime($('#max'), {

//   format: 'MMMM Do YYYY'

// });

//

// $('#min, #max').on('change', function () {

//   table.draw()

// })



var startInvoice = null;

var endInvoice = null;

var filterCol = null;

$.fn.dataTable.ext.search.push(

    function (settings, data, dataIndex) {

        // startInvoice = minDate.val();

        // endInvoice = maxDate.val();



        var date = new Date(data[filterCol]);



        if (

            (startInvoice === null && endInvoice === null) ||

            (startInvoice === null && date <= endInvoice) ||

            (startInvoice <= date && endInvoice === null) ||

            (startInvoice <= date && date <= endInvoice)

        ) {

            return true;

        }

        return false;

    }

);



$(document).on('click', '.dropdown-menu', function (e) {

    e.stopPropagation();

});



$('#cv').on('change', function () {



    if (this.files[0].size > 5242880) {

        alert("File must not be more than 5mb");

        $(this).val('');

    } else {

        var filenames = '';

        var totalFiles = $(this).get(0).files.length;

        for (var i = 0; i < totalFiles; i++) {

            filenames += $(this).get(0).files[i].name + ', ';

        }

        $('.success').empty()

        $('.success').append('<p class="p-0 m-0">' + filenames + '</p>')

        $('.success').css('display', 'inline-block');

        $('.file-icon').css('display', 'none');

        $('.default').css('display', 'none');

        $('.file-dummy').css('border-color', 'rgba(0, 255, 0, 0.4)');

        $('.file-dummy').css('background-color', 'rgba(0, 255, 0, 0.3)');

    }



})



$('.reported').each(function () {

    $(this).on('change', function () {

        $.ajax({

            type: "POST",

            url: '../includes/ajax.php',

            data: { "reported": true, "id": $(this).attr('data-id'), "checked": $(this).is(":checked") },

            success: function (response) {

                // console.log(response)

                // var jsonData = JSON.parse(response);

                // console.log(jsonData);

            }

        });

    })

})



$('.invoice_sent').each(function () {

    $(this).on('change', function () {

        var that = $(this)

        $.ajax({

            type: "POST",

            url: '../includes/ajax.php',

            data: { "invoice_sent": true, "id": $(this).attr('data-id'), "checked": $(this).is(":checked") },

            success: function (response) {

                if (that.closest('tr').length) {

                    that.closest('tr').find('.invoice_date').text(response)

                    // that.closest('tr').next().find('.invoice_date').text(response)

                }

                if (that.closest('.record').length) {

                    that.closest('.record').find('.invoice_date').text(response)

                }

            }

        });

    })

})

function fun_invoice_date(obj) {

    var that = $(obj)

    $.ajax({

        type: "POST",

        url: '../includes/ajax.php',

        data: { "invoice_sent": true, "id": $(obj).attr('data-id'), "checked": $(obj).is(":checked") },

        success: function (response) {

            if (that.is(':checked')) {

                $('input[type="checkbox"][data-id="' + $(obj).attr('data-id') + '"]').prop('checked', true)

            } else {

                $('input[type="checkbox"][data-id="' + $(obj).attr('data-id') + '"]').prop('checked', false)

            }

            if (that.closest('tr').length) {

                that.closest('tr').find('.invoice_date').text(response)

                // that.closest('tr').next().find('.invoice_date').text(response)

            }

            if (that.closest('.record').length) {

                that.closest('.record').find('.invoice_date').text(response)

            }

        }

    });

}

$('body').on('click', '.uncheck_economy', function () {

    backgroundCheck('economy', $(this).attr('data-id'), 0, $(this), '.economy-radio');

})



$('body').on('click', '.check_economy', function () {

    backgroundCheck('economy', $(this).attr('data-id'), 1, $(this), '.economy2-radio');

})



$('body').on('click', '.uncheck_criminal', function () {

    backgroundCheck('criminal_record', $(this).attr('data-id'), 0, $(this), '.economy-radio');

})



$('body').on('click', '.check_criminal', function () {

    backgroundCheck('criminal_record', $(this).attr('data-id'), 1, $(this), '.economy2-radio');

})

$('body').on('click', '.uncheck_social', function () {

    backgroundCheck('social', $(this).attr('data-id'), 0, $(this), '.economy-radio');

})



$('body').on('click', '.check_social', function () {

    backgroundCheck('social', $(this).attr('data-id'), 1, $(this), '.economy2-radio');

})



function backgroundCheck(typeCheck, id, checked, that, input_class) {



    $.ajax({

        type: "POST",

        url: '../includes/ajax.php',

        dataType: "json",

        data: { "background_check": true, 'type': typeCheck, "id": id, "checked": checked },

        success: function (response) {

            $('input[type="radio"][name="' + that.closest('label').find('input[type = "radio"]').attr('name') + '"]' + input_class).prop('checked', true);

            if (that.closest('tr').length) {

                that.closest('tr').find('.background_check_date').text(response.date)

                that.closest('tr').next().find('.background_check_date').text(response.date)

            }

            if (that.closest(".record-main").length) {

                that.closest('.record-main').find('.background_check_date').text(response.date)

            }

            candidate = response.candidate

        }

    });

}



// Bootstrap Icons

$(function () {

    if ($(".iconpicker").length) {

        $('.iconpicker').iconpicker();

    }

});



// Show/Hide Buttons when services are selected

$('.services-btns').on('click', function () {

    var btnsID = $(this).attr('data-catid');



    $('.service-btns-inner').addClass('d-none');

    $(".service-" + btnsID).removeClass('d-none');

    $('.services-btns').removeClass('active-service');

    $(this).addClass('active-service');

})



// Keep dropdown open in sidebar

$(".sub-menu.menu-item").each(function () {

    var menuActive = $(this).find(".text-dark-blue")

    if (menuActive.length > 0) {

        $(this).find('.menu-icon:first').addClass('text-dark-blue')

        $(this).find('.menu-title:first').addClass('text-dark-blue')

        $(this).addClass('open')

        $(this).find('.sub-menu-list').css('display', 'block')

    }

})



function formatDate(dateString) {

    const options = {

        year: 'numeric',

        month: 'short',

        day: '2-digit',

        hour: 'numeric',

        minute: '2-digit',

        hour12: true,

    };



    return new Date(dateString).toLocaleString(undefined, options);

}



function flash(msgType, msg) {

    $("." + msgType).find("i").after("<span>&nbsp;" + msg + "</span>")

    $("." + msgType).removeClass("d-none")



    setTimeout(() => {

        $("." + msgType).find("i").next().remove()

        $("." + msgType).addClass("d-none")

    }, 6000)

}

function show_card(obj) {



    if ($(obj).closest('.card').find('.card-body').is(':hidden')) {

        $(obj).closest('.card').find('.card-body').slideDown(500);

    } else {

        $(obj).closest('.card').find('.card-body').slideUp(500);

    }

}

function show_add_card(obj) {



    if ($('#show_add_card').is(':hidden')) {

        $('#show_add_card').slideDown(500);

    } else {

        $('#show_add_card').slideUp(500);

    }

}

function add_service() {

    var name = $('#name').val();

    var b_length = $('#dataTable').find('tbody').find('tr').length

    count = b_length;

    if (b_length % 2 == 0) {

        b_length = 'even';

    } else {

        b_length = 'odd';

    }

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                add_service: 1,

                name: name

            },

            success: function (response) {

                response = JSON.parse(response)

                $('#name').val(null);

                html = `<tr class="` + b_length + `">

                    <td>

                        <div class="dropdown">

                            <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">

                                <i class="bi bi-gear"></i>

                            </button>

                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                <input type="hidden" class="u_id" value="`+ response + `">

                                <input type="hidden" class="u_name" value="`+ name + `">

                                <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                    Edit</a>

                                </li>

                                <li class="mb-1"><a href="?delete=`+ response + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                    Delete</a>

                                </li>

                            </ul>

                        </div>

                    </td>

                    <td class="f-14">`+ ++count + `</td>

                    <td class="f-14"><a class="no-decoration text-black" href="interviews.php?id=`+ response + `">` + name + `</a></td>

                </tr>`

                $('#dataTable').find('tbody').append(html)

                alert('Service added successfully')

            }

        });

    }

}

function update_service() {

    var name = $('#main_u_name').val();

    var id = $('#main_u_id').val();

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                update_service: 1,

                name: name,

                u_id: id

            },

            success: function (response) {

                $('.u_id').each(function () {

                    if ($(this).val() == id) {

                        $(this).closest('tr').find('.u_name').val(name)

                        $(this).closest('tr').find('.name_text').text(name)

                    }

                })

                $('#name').val(name);

                alert('Service Updated successfully')

            }

        });

    }

}

function add_place() {

    var name = $('#name').val();

    var b_length = $('#dataTable').find('tbody').find('tr').length

    count = b_length;

    if (b_length % 2 == 0) {

        b_length = 'even';

    } else {

        b_length = 'odd';

    }

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                add_place: 1,

                name: name

            },

            success: function (response) {

                response = JSON.parse(response)

                $('#name').val(null);

                html = `<tr class="` + b_length + `">

                    <td>

                        <div class="dropdown">

                            <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">

                                <i class="bi bi-gear"></i>

                            </button>

                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                <input type="hidden" class="u_id" value="`+ response + `">

                                <input type="hidden" class="u_name" value="`+ name + `">

                                <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                    Edit</a>

                                </li>

                                <li class="mb-1"><a href="?delete=`+ response + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                    Delete</a>

                                </li>

                            </ul>

                        </div>

                    </td>

                    <td class="f-14">`+ ++count + `</td>

                    <td class="f-14"><a class="no-decoration text-black" href="interviews.php?id=`+ response + `">` + name + `</a></td>

                </tr>`

                $('#dataTable').find('tbody').append(html)

                alert('Place added successfully')

            }

        });

    }

}



function update_place() {

    var name = $('#main_u_name').val();

    var id = $('#main_u_id').val();

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                update_place: 1,

                name: name,

                u_id: id

            },

            success: function (response) {

                $('.u_id').each(function () {

                    if ($(this).val() == id) {

                        $(this).closest('tr').find('.u_name').val(name)

                        $(this).closest('tr').find('.name_text').text(name)

                    }

                })

                $('#name').val(name);

                alert('Place Updated successfully')

            }

        });

    }

}

function add_permission() {

    var name = $('#name').val();

    var b_length = $('#dataTable').find('tbody').find('tr').length

    count = b_length;

    if (b_length % 2 == 0) {

        b_length = 'even';

    } else {

        b_length = 'odd';

    }

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                add_permission: 1,

                name: name

            },

            success: function (response) {

                response = JSON.parse(response)

                $('#name').val(null);

                html = `<tr class="` + b_length + `">

                    <td>

                        <div class="dropdown">

                            <button class="table-menu-btn mx-auto dropdownBtn" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-expanded="false">

                                <i class="bi bi-gear"></i>

                            </button>

                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton1">

                                <input type="hidden" class="u_id" value="`+ response + `">

                                <input type="hidden" class="u_name" value="`+ name + `">

                                <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>

                                    Edit</a>

                                </li>

                                <li class="mb-1"><a href="?delete=`+ response + `" class="no-decoration f-14 w-600 text-black "><i class="bi bi-trash text-black f-14 me-2"></i>

                                    Delete</a>

                                </li>

                            </ul>

                        </div>

                    </td>

                    <td class="f-14"><a class="no-decoration text-black" href="interviews.php?id=`+ response + `">` + name + `</a></td>

                </tr>`

                $('#dataTable').find('tbody').append(html)

                alert('Added successfully')

            }

        });

    }

}

function update_permission() {

    var name = $('#main_u_name').val();

    var id = $('#main_u_id').val();

    if (name != '') {

        $.ajax({

            type: "POST",

            url: "./includes/table_ajax.php",

            data: {

                update_permission: 1,

                name: name,

                u_id: id

            },

            success: function (response) {

                $('.u_id').each(function () {

                    if ($(this).val() == id) {

                        $(this).closest('tr').find('.u_name').val(name)

                        $(this).closest('tr').find('.name_text').text(name)

                    }

                })

                $('#name').val(name);

                alert('Updated successfully')

            }

        });

    }

}

function update_s(obj) {

    var id = $(obj).closest('ul').find('.u_id').val()

    var name = $(obj).closest('ul').find('.u_name').val()

    if ($('#update_service_card').is(':hidden')) {

        $('#update_service_card').slideDown(500);

        $('#main_u_id').val(id)

        $('#main_u_name').val(name)

    } else {

        $('#update_service_card').slideUp(500);

        $('#main_u_id').val('')

        $('#main_u_name').val('')

    }

}

// $('.delay_set_id').on('click', function () {
$(document).on('click', '.delay_set_id', function () {

    $('#delay_duration').val('14');

    $('#delay_cus_id').val('');

    var id = $(this).closest('tr').find('.open-customer').data('id');

    var days = $(this).closest('tr').find('.open-customer').attr('data-days')

    if (id != undefined && id != null && id != '') {

        $('#delay_cus_id').val(id);

    } else {

        alert('Something went wrong! Please try again')

    }

    if (days != undefined && days != null && days != '') {

        $('#delay_duration').val(days);

    } else {

        $('#delay_duration').val('14');

    }

    id = 0;

    days = 0

})

function check_date(obj) {

    var days = $(obj).val();

    if (days != '') {

        if (days > 0) {



        } else {

            alert("Duration Days can't be less than 1");

            $(obj).val(1)

        }

    } else {

        alert("Something went wrong please try again!");

        $(obj).val(1)

    }

}

function setDurationDays(obj) {

    var days = $('#delay_duration').val();

    var id = $('#delay_cus_id').val();

    $.ajax({

        type: "POST",

        url: "./includes/table_ajax.php",

        data: {

            'cus_id': id,

            'days': days,

            'delay_duration': 1

        },

        success: function (response) {

            response = JSON.parse(response)

            if (response.success && response.success != '' && response.success != undefined) {

                alert(response.success)

                $('.open-customer').each(function () {

                    if ($(this).data('id') == id) {

                        $(this).attr('data-days', days)

                        $('#delay_duration').val(days)

                    }

                })

            }

        }

    });



}

$(document).ready(function () {

    $('.summernote').summernote()

})

function add_question_type_base() {

    if ($('#add-question').is(':hidden')) {

        $('#add-question').slideDown(500);

    } else {

        $('#add-question').slideUp(500);

    }

}

function add_question() {

    var type = '';

    var html = '';

    var i = $('.question_count').length + 1;

    $('.question_type').each(function () {

        if ($(this).is(':checked')) {

            type = $(this).val();

        }

    })

    if (type == 'free_text') {

        html = `<div class="col-lg-12 mt-4 question_count">

                                    <label for="">New Question :</label><button type="button" class="btn btn-danger ml-5 mb-2" onclick="remove_question(this)"><span class="bi bi-trash"></span></button>

                                    <input type="text" name="qs[`+ i + `][qs]" class="form-control">

                                    <input type="hidden" name="qs[`+ i + `][type]" value="free_text">

                                </div>`

    }

    if (type == 'radio_opt') {

        html = `<div class="col-lg-12 mt-4 question_count">

                                    <label for="">New Question :</label><button type="button" class="btn btn-danger ml-5 mb-2" onclick="remove_question(this)"><span class="bi bi-trash"></span></button>

                                    <input type="text" name="qs[`+ i + `][qs]" class="form-control">

                                    <input type="hidden" name="qs[`+ i + `][type]" value="radio">

                                    <div class="row">

                                        <div class="col-md-4">

                                            <h5>Answers</h5>

                                        </div>

                                        <div class="col-md-8">

                                            <button type="button" class="btn btn-primary" onclick="add_option(this)" data-id="`+ i + `"><i class="bi bi-plus-lg"></i></button>

                                        </div>

                                        <div class="col-md-12">

                                            <div class="row">

                                                <div class="col-md-4">

                                                    <input type="text" name="qs[`+ i + `][option][]" class="form-control">

                                                </div>

                                                <div class="col-md-8">

                                                    <button type="button" class="btn btn-danger mt-0" onclick="remove_option(this)"><i class="bi bi-trash"></i></button>

                                                </div>

                                            </div>

                                        </div>

                                    </div>

                                </div>`

    }

    $('.questions-row').append(html)

}

function add_option(obj) {

    var i = $(obj).data('id');

    var html = '';

    html = `<div class="col-md-12">

                                            <div class="row">

                                                <div class="col-md-4">

                                                    <input type="text" name="qs[`+ i + `][option][]" class="form-control">

                                                </div>

                                                <div class="col-md-8">

                                                    <button type="button" class="btn btn-danger mt-0" onclick="remove_option(this)"><i class="bi bi-trash"></i></button>

                                                </div>

                                            </div>

                                        </div>`

    $(obj).closest('.row').append(html)

}

function remove_option(obj) {

    $(obj).closest('.row').remove()

}

function remove_question(obj) {

    $(obj).closest('.question_count').remove()

}

function reinitiateDataTable() {

    var checkboxes = $('input[type="checkbox"][onclick="columns_check(this)"]');

    checkboxes.each(function () {

        var show_hide_id = $(this).attr('data-id');

        if ($(this).is(':checked')) {

            $("." + show_hide_id).removeClass('custom_hide');

        } else {

            $("." + show_hide_id).addClass('custom_hide');

        }

        var dataTable = $('#dataTable').DataTable();

        dataTable.columns('.toggle-column').visible(!$(this).is(":checked"));

    });

    $('.paginate_button').each(function (i, v) {

        $(this).attr('onclick', 'reinitiateDataTable()')

    })

}


// Filter functions for candidates table
function filter_data() {
    // Reload DataTable with new filters
    if (typeof table !== 'undefined' && table) {
        table.ajax.reload();
    }
}

function reset_filters() {
    // Clear all filter inputs
    $('#fil_place').val('');
    $('#fil_can').val('');
    $('#fil_com').val('0');
    $('#fil_cus').val('');
    $('#order_created_from').val('');
    $('#order_created_to').val('');
    $('#interview_date_from').val('');
    $('#interview_date_to').val('');
    
    // Reload DataTable
    if (typeof table !== 'undefined' && table) {
        table.ajax.reload();
    }
}