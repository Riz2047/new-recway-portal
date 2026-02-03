function departments_data(obj) {
    var id = $(obj).attr('id');
    var name = null;
    var cus_id = null;
    var dep_id = null;
    var services = [];
    var statuses = [];
    var container = null;
    var email = null;
    var password = null;
    var department = null;
    var department_child = null;
    var permissions = [];
    var html = null;
    if (id != '') {
        if (id == 'add_department_btn') {
            container = $('#add_department');
            name = container.find('input[name="name"]').val();
            cus_id = container.find('input[name="dep_cus_id"]').val();
            department_child = container.find('select').val();
            $(container.find('input[name="services[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    services.push($(this).val())
                }
            });
            $(container.find('input[name="statuses[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    statuses.push($(this).val())
                }
            });
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    name: name,
                    services: services,
                    statuses: statuses,
                    department_child: department_child,
                    cus_id: cus_id,
                    add_department: 1,
                },
                success: function (response) {
                    response = JSON.parse(response);
                    if (response.error && response.error != '' && response.error != undefined) {
                        alert(response.error)
                    }
                    if (response.success && response.success != '' && response.success != undefined) {
                        alert(response.success)
                        html = `<tr>
                                    <td>`+ name + `</td>
                                    <td style="width:6% !important">
                                        <div class="dropdown">
                                            <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                <i class="bi bi-gear"></i>
                                            </button>
                                            <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" >
                                                <li class="mb-1"><a href="#" onclick="openCity(event, 'update_department'),get_data(this)" data-id="`+ response.last_id + `" data-type="1" class="no-decoration f-14 w-600 text-black "><i class="bi bi-pen text-black f-14 me-2"></i>
                                                        Edit</a>
                                                </li>
                                                <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" data-id="`+ response.last_id + `" data-type="2"><i class="bi bi-trash  f-14 text-black me-2"></i>
                                                         Trash</a>
                                                </li> -->
                                                <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black"><i class="bi bi-people f-14 text-black me-2"></i>
                                                        Users</a>
                                                </li> -->
                                            </ul>
                                        </div>
                                    </td>
                                </tr>`;
                        $('#departments').find('tbody').append(html)
                        var new_option = `<option value="` + response.last_id + `">` + name + `</option>`;
                        $('#department_users').find('select').append(new_option)
                        $('#add_department_users').find('select').append(new_option)
                        $('#update_department_users').find('select').append(new_option)
                        $('#departments').find('tbody').find('.no_record_found').remove();
                        container.find('.back_btn').click()
                    }
                }
            });
        }
        if (id == 'update_department_btn') {
            container = $('#update_department');
            name = container.find('input[name="name"]').val();
            dep_id = container.find('input[name="up_dep_id"]').val();
            department_child = container.find('select').val();
            $(container.find('input[name="services[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    services.push($(this).val())
                }
            });
            $(container.find('input[name="statuses[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    statuses.push($(this).val())
                }
            });
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    name: name,
                    services: services,
                    statuses: statuses,
                    department_child: department_child,
                    dep_id: dep_id,
                    update_department: 1,
                },
                success: function (response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.error && response.error != '' && response.error != undefined) {
                            alert(response.error)
                        }
                        if (response.success && response.success != '' && response.success != undefined) {
                            alert(response.success)
                            $($('#departments').find('tbody').find('tr').find('a')).each(function () {
                                if ($(this).data('id') == dep_id) {
                                    $(this).closest('tr').find('td:first').text(name);
                                }
                            })
                            $($('#department_users').find('select').find('option')).each(function () {
                                if ($(this).val() == dep_id) {
                                    $(this).html(name);
                                }
                            })
                            $($('#add_department_users').find('select').find('option')).each(function () {
                                if ($(this).val() == dep_id) {
                                    $(this).html(name);
                                }
                            })
                            $($('#update_department_users').find('select').find('option')).each(function () {
                                if ($(this).val() == dep_id) {
                                    $(this).html(name);
                                }
                            })
                            container.find('.back_btn').click()
                        }
                    }
                }
            });
        }
        if (id == 'add_dep_user_btn') {
            container = $('#add_department_users');
            name = container.find('input[name="name"]').val();
            email = container.find('input[name="email"]').val();
            password = container.find('input[name="password"]').val();
            department = container.find('select[name="department"]').find('option:selected').val();
            var department_name = container.find('select[name="department"]').find('option:selected').text();
            $(container.find('input[name="permissions[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    permissions.push($(this).val())
                }
            });
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    name: name,
                    email: email,
                    password: password,
                    department: department,
                    permissions: permissions,
                    add_department_user: 1,
                },
                success: function (response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.error && response.error != '' && response.error != undefined) {
                            alert(response.error)
                        }
                        if (response.success && response.success != '' && response.success != undefined) {
                            alert(response.success)
                            html = `<tr class="` + department_name + `">
                                                            <td>`+ name + `</td>
                                                            <td>`+ email + `</td>
                                                            <td>`+ department_name + `</td>
                                                            <td style="width:6% !important">
                                                                <div class="dropdown">
                                                                    <button class="table-menu-btn mx-auto dropdownBtn" onclick="dropdown_open(this)" type="button" aria-expanded="false">
                                                                        <i class="bi bi-gear"></i>
                                                                    </button>
                                                                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" >
                                                                        <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black " onclick="openCity(event, 'update_department_users'),get_user_data(this)" data-id="`+ response.last_id + `" data-type="1"><i class="bi bi-pen text-black f-14 me-2"></i>
                                                                                Edit</a>
                                                                        </li>
                                                                        <!-- <li class="mb-1"><a href="#" class="no-decoration f-14 w-600 text-black" data-id="`+ response.last_id + `" data-type="2"><i class="bi bi-trash f-14 text-black me-2"></i>
                                                                                 Trash</a>
                                                                         </li> -->
                                                                    </ul>
                                                                </div>
                                                            </td>
                                                        </tr>`

                            $('#department_users').find('tbody').append(html)
                            $('#department_users').find('tbody').find('.no_record_found').remove();
                            container.find('.back_btn').click()
                        }
                    }
                }
            });
        }
        if (id == 'update_dep_user_btn') {
            container = $('#update_department_users');
            name = container.find('input[name="name"]').val();
            email = container.find('input[name="email"]').val();
            department = container.find('select[name="department"]').find('option:selected').val();
            var department_name = container.find('select[name="department"]').find('option:selected').text();
            var dep_user = container.find('input[name="up_dep_user_id"]').val();
            $(container.find('input[name="permissions[]"]')).each(function () {
                if ($(this).is(':checked')) {
                    permissions.push($(this).val())
                }
            });
            $.ajax({
                type: "POST",
                url: "./includes/table_ajax.php",
                data: {
                    name: name,
                    email: email,
                    department: department,
                    permissions: permissions,
                    dep_user: dep_user,
                    update_department_user: 1,
                },
                success: function (response) {
                    if (response != '') {
                        response = JSON.parse(response);
                        if (response.error && response.error != '' && response.error != undefined) {
                            alert(response.error)
                        }
                        if (response.success && response.success != '' && response.success != undefined) {
                            alert(response.success)
                            $($('#department_users').find('tbody').find('tr').find('a')).each(function () {
                                if ($(this).data('id') == dep_user) {
                                    $(this).closest('tr').find('td:first').text(name);
                                    $(this).closest('tr').find('td:nth-child(2)').text(email);
                                    $(this).closest('tr').find('td:nth-child(3)').text(department_name);
                                }
                            })
                            container.find('.back_btn').click()
                        }
                    }
                }
            });
        }
    }
}
function get_data(obj) {
    $($('#update_department').find('input[name="statuses[]"]')).each(function () {
        $(this).prop('checked', false);
    });
    $($('#update_department').find('input[name="services[]"]')).each(function () {
        $(this).prop('checked', false);
    });
    var get_id = $(obj).data('id');
    var get_type = $(obj).data('type'); // 1 for fetch record ,2 for trash 
    $.ajax({
        type: "POST",
        url: "./includes/table_ajax.php",
        data: {
            get_type: get_type,
            get_id: get_id,
            get_department_data: 1
        },
        success: function (response) {
            if (response != '') {
                response = JSON.parse(response);
                $('#update_department').find('input[name="name"]').val(response.department[0].dep_name)
                $('#update_department').find('input[name="up_dep_id"]').val(get_id)
                var stat = response.department[0].dep_status.split(',')
                var ch_dep = response.department[0].child_department.split(',')
                if (response.dep_services[0] != null) {
                    response.dep_services.forEach(function (e) {
                        $($('#update_department').find('input[name="services[]"]')).each(function () {
                            if (e.dep_service_id == $(this).val()) {
                                $(this).prop('checked', true);
                            }
                        });
                    })

                }
                if (response.department[0].dep_status != null) {
                    stat.forEach(function (i) {
                        $($('#update_department').find('input[name="statuses[]"]')).each(function () {
                            if (i == $(this).val()) {
                                $(this).prop('checked', true);
                            }
                        });
                    })
                }
                if (ch_dep != null) {
                    ch_dep.forEach(function (a) {
                        $($('#update_department').find('select').find('option')).each(function () {
                            if (a == $(this).val()) {
                                $(this).prop('selected', true);
                            }
                        });
                    })
                    $('#update_department').find('select').trigger('change');
                }
            }
        }
    });
}
function get_user_data(obj) {
    $($('#update_department_users').find('input[name="permissions[]"]')).each(function () {
        $(this).prop('checked', false);
    });
    var get_id = $(obj).data('id');
    var get_type = $(obj).data('type'); // 1 for fetch record ,2 for trash 
    $.ajax({
        type: "POST",
        url: "./includes/table_ajax.php",
        data: {
            get_type: get_type,
            get_id: get_id,
            get_user_data: 1
        },
        success: function (response) {
            if (response != '') {
                response = JSON.parse(response);
                $('#update_department_users').find('input[name="name"]').val(response.department_user[0].dep_user_name)
                $($('#update_department_users').find('select[name="department"]').find('option')).each(function () {
                    if ($(this).val() == response.department_user[0].dep_id) {
                        $(this).prop('selected', true)
                    }
                })
                $('#update_department_users').find('input[name="up_dep_user_id"]').val(get_id)
                $('#update_department_users').find('input[name="email"]').val(response.department_user[0].dep_user_email)
                if (response.allow_permissions != null) {
                    response.allow_permissions.forEach(function (i) {
                        $($('#update_department_users').find('input[name="permissions[]"]')).each(function () {
                            if (i.per_id == $(this).val()) {
                                $(this).prop('checked', true);
                            }
                        });
                    })
                }
            }
        }
    });
}
function show_dep_users(obj) {
    var id = $(obj).find(':selected').text();
    var val_id = $(obj).find(':selected').val();
    var table = $(obj).closest('.container').find('table').find('tbody').find('tr')
    if (val_id != '') {
        if (id != '') {
            $(table).each(function () {
                if ($(this).hasClass(id)) {
                    $(this).show()
                } else {
                    $(this).hide()
                }
            })
        }
    } else {
        $(table).each(function () {
            $(this).show();
        })
    }
}