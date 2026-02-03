<?php



include('../../includes/functions.php');



if (isset($_POST['column']) && !empty($_POST['column'])) {

    $table = !empty($_POST['table']) ? $_POST['table'] : '';

    $state = !empty($_POST['state']) ? $_POST['state'] : 0;

    if (!empty($table)) {

        $columns = !empty($_POST['columns']) ? $_POST['columns'] : '';

        if (!empty($columns)) {

            $new_arr = [];

            $recent_data = getTableSettings($_POST['table']);

            if (!empty($recent_data->meta_data)) {

                $new_arr = json_decode($recent_data->meta_data, true);

            }

            $new_arr[$columns] = $state;

            $new_arr = json_encode($new_arr);

            updateTableSettings($table, $new_arr);

        }

    }

}

if (isset($_POST['add_service']) && !empty($_POST['add_service'])) {

    if (!empty($_POST['name'])) {

        $service_data = ['name' => $_POST['name']];
        if (!empty($_POST['name_sv'])) {
            $service_data['name_sv'] = $_POST['name_sv'];
        }
        $last_id = insert('service_categories', $service_data);

        echo json_encode($last_id);

    }

}

if (isset($_POST['update_service']) && !empty($_POST['update_service'])) {

    if (!empty($_POST['u_id'])) {
        $service_data = ['name' => $_POST['name']];
        if (!empty($_POST['name_sv'])) {
            $service_data['name_sv'] = $_POST['name_sv'];
        }
        update('service_categories', $service_data, 'id', $_POST['u_id']);

    }

}

if (isset($_POST['add_place']) && !empty($_POST['add_place'])) {

    if (!empty($_POST['name'])) {

        $last_id = insert('places', ['name' => $_POST['name']]);

        echo json_encode($last_id);

    }

}

if (isset($_POST['update_place']) && !empty($_POST['update_place'])) {

    if (!empty($_POST['u_id'])) {

        update('places', ['name' => $_POST['name']], 'id', $_POST['u_id']);

    }

}

if (isset($_POST['add_department']) && !empty($_POST['add_department'])) {

    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {

        if (isset($_POST['name']) && !empty($_POST['name'])) {

            $inert_data = array(

                'dep_name' => $_POST['name'],

                'dep_cus_id' => $_POST['cus_id'],

            );

            if (isset($_POST['statuses']) && !empty($_POST['statuses'])) {

                $_POST['statuses'] = implode(',', $_POST['statuses']);

                $inert_data['dep_status'] = $_POST['statuses'];

            }

            if (isset($_POST['department_child']) && !empty($_POST['department_child'])) {

                $inert_data['child_department'] = implode(',', $_POST['department_child']);

            }

            $last_id = insert('departments', $inert_data);



            if (isset($_POST['services']) && !empty($_POST['services'])) {

                foreach ($_POST['services'] as $service) {

                    insert('department_services', ['dep_service_id' => $service, 'dep_id' => $last_id]);

                }

            }

            echo json_encode(['success' => 'Department Added Successfully!', 'last_id' => $last_id]);

        } else {

            echo json_encode(['error' => 'Please Enter Department Name First!']);

        }

    } else {

        echo json_encode(['error' => 'Please Enter Department Name First!']);

    }

}

if (isset($_POST['update_department']) && !empty($_POST['update_department'])) {

    if (isset($_POST['dep_id']) && !empty($_POST['dep_id'])) {

        if (isset($_POST['name']) && !empty($_POST['name'])) {

            $update_data = array(

                'dep_name' => $_POST['name'],

            );

            if (isset($_POST['statuses']) && !empty($_POST['statuses'])) {

                $_POST['statuses'] = implode(',', $_POST['statuses']);

                $update_data['dep_status'] = $_POST['statuses'];

            }

            if (isset($_POST['department_child']) && !empty($_POST['department_child'])) {

                $update_data['child_department'] = implode(',', $_POST['department_child']);

            }

            update('departments', $update_data, 'dep_id', $_POST['dep_id']);

            if (isset($_POST['services']) && !empty($_POST['services'])) {

                delete('department_services', 'dep_id', $_POST['dep_id']);

                foreach ($_POST['services'] as $service) {

                    $last_id = insert("department_services", ["dep_id" => $_POST['dep_id'], "dep_service_id" => $service]);

                }

            }

            echo json_encode(['success' => 'Department Updated Successfully!']);

        } else {

            echo json_encode(['error' => 'Please Enter Department Name First!']);

        }

    } else {

        echo json_encode(['error' => 'Something went wrong please try again!']);

    }

}

if (isset($_POST['add_department_user']) && !empty($_POST['add_department_user'])) {

    $name = $_POST['name'];

    $dep = $_POST['department'];

    $email = $_POST['email'];

    $password = $_POST['password'];

    $per = $_POST['permissions'] ?? array();

    if (!empty($dep)) {

        if (!empty($name) && !empty($dep) && !empty($email) && !empty($password)) {

            $user = findByQuery("SELECT * FROM department_users WHERE dep_user_email = '{$email}'");

            $department = findByQuery("SELECT * FROM departments WHERE dep_id = '{$dep}'");

            if (!empty($department->dep_name)) {

                $department = $department->dep_name;

            } else {

                $department = '';

            }

            if (!empty($user)) {

                echo json_encode(['error' => 'This email already exists!']);

            } else {

                $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                $last_id = insert("department_users", ["dep_user_name" => $name, "dep_user_email" => $email, "dep_user_password" => $hashedPassword, 'dep_id' => $dep]);



                if (!empty($per)) {

                    foreach ($per as $pers) {

                        $query = 'INSERT INTO user_allowed_permissions (per_id, user_id,user_type) VALUES (?,?,?)';

                        $stmt = $conn->prepare($query);

                        $res = $stmt->execute([$pers, $last_id, 1]);

                    }

                }

                $departments = findByQuery("SELECT * FROM departments WHERE dep_id = '{$dep}'");

                if (!empty($departments->dep_cus_id)) {

                    $reg_email = findByQuery("SELECT * FROM customers WHERE id = '{$departments->dep_cus_id}'");

                    if (!empty($reg_email->reg_email)) {

                        $reg_email = $reg_email->reg_email;

                        $f_pre = "https://customer.recway.se/";

                        $f_re = "https://orderspi.se/department_user/";

                        $reg_email = str_replace($f_pre, $f_re, $reg_email);

                        $f_pre = "https://customer.recway.se";

                        $f_re = "https://orderspi.se/department_user";

                        $reg_email = str_replace($f_pre, $f_re, $reg_email);

                        $body = replace($reg_email, $name, '', '', '', '', $email, $password, '', '');

                        sendMail($body, $email, "User", "User added");

                        echo json_encode(['success' => 'User created Successfully', 'last_id' => $last_id]);

                    } else {

                        $body = "<p>You are added as a User of {$department} Department by {$_SESSION['admin']->name}. Please use following credentials to login.</p>";

                        $body .= "<br>";

                        $body .= "<strong>Email: {$email}</strong>";

                        $body .= "<br>";

                        $body .= "<strong>Password: {$password}</strong>";

                        $body .= "<br><br>";

                        $body .= "Click on the following link to access the portal";

                        $body .= "<br><br>";

                        $body .= "<a href='https://orderspi.se/department_user'>https://orderspi.se/department_user</a>";

                        sendMail($body, $email, "User", "User added");

                        echo json_encode(['success' => 'User created Successfully', 'last_id' => $last_id]);

                    }

                }

            }

        } else {

            echo json_encode(['error' => 'User is not created due to insufficient information!']);

        }

    } else {

        echo json_encode(['error' => 'Please Selece Department First!']);

    }

}

if (isset($_POST['update_department_user']) && !empty($_POST['update_department_user'])) {

    if (isset($_POST['dep_user']) && !empty($_POST['dep_user'])) {

        if (!empty($_POST['name']) && !empty($_POST['email'])) {

            $update_data = array(

                'dep_user_name' => $_POST['name'],

                'dep_id' => $_POST['department'],

            );

            update('department_users', $update_data, 'dep_user_id', $_POST['dep_user']);

            if (isset($_POST['permissions']) && !empty($_POST['permissions'])) {

                delete('user_allowed_permissions', 'user_id', $_POST['dep_user']);

                foreach ($_POST['permissions'] as $permissions) {

                    $last_id = insert("user_allowed_permissions", ["user_id" => $_POST['dep_user'], "per_id" => $permissions, 'user_type' => 1]);

                }

            }

            echo json_encode(['success' => 'User Updated Successfully!', 'last_id' => $last_id]);

        }

    }

}

if (isset($_POST['get_department_data']) && !empty($_POST['get_department_data'])) {

    if (isset($_POST['get_id']) && !empty($_POST['get_id'])) {

        if (isset($_POST['get_type']) && !empty($_POST['get_type']) && $_POST['get_type'] == 1) {

            $department = findAllByQuery("SELECT * FROM departments WHERE departments.dep_id = {$_POST['get_id']} AND departments.dep_trash = 0");

            $dep_services = findAllByQuery("SELECT * FROM department_services WHERE department_services.dep_id = {$_POST['get_id']}");

            echo json_encode(['department' => $department, 'dep_services' => $dep_services]);

        }

        if (isset($_POST['type_id']) && !empty($_POST['type_id']) && $_POST['type_id'] == 2) {

            update('departments', ['dep_trash' => 1], 'dep_id', $_POST['get_id']);

        }

    }

}

if (isset($_POST['get_user_data']) && !empty($_POST['get_user_data'])) {

    if (isset($_POST['get_id']) && !empty($_POST['get_id'])) {

        if (isset($_POST['get_type']) && !empty($_POST['get_type']) && $_POST['get_type'] == 1) {

            $department_user = findallByQuery("SELECT * FROM department_users LEFT JOIN departments ON department_users.dep_id = departments.dep_id WHERE department_users.dep_user_id = {$_POST['get_id']} AND department_users.dep_user_trash = 0");

            $allow_permissions = findallByQuery("SELECT * FROM user_allowed_permissions WHERE user_id = {$_POST['get_id']}");

            echo json_encode(['department_user' => $department_user, 'allow_permissions' => $allow_permissions]);

        }

        if (isset($_POST['type_id']) && !empty($_POST['type_id']) && $_POST['type_id'] == 2) {

            update('department_users', ['dep_user_trash' => 1], 'dep_user_id', $_POST['get_id']);

        }

    }

}

if (isset($_POST['delay_duration']) && !empty($_POST['delay_duration'])) {
    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
        if (isset($_POST['days']) && !empty($_POST['days'])) {
            update('customers', ['report_delete_duration' => $_POST['days']], 'id', $_POST['cus_id']);
            echo json_encode(['success' => 'Delete Report Duration Changed Successfully']);
        }
    }
}

if (isset($_POST['upload_pdf']) && !empty($_POST['upload_pdf'])) {

    if (isset($_POST['can_id']) && !empty($_POST['can_id'])) {

        if (isset($_FILES['file_1']['name']) && !empty($_FILES['file_1']['name'])) {

            $originalFileName = $_FILES['file_1']['name'];

            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

            $trimmedFileName = trim($originalFileName);

            $fileName = time() . '-' . uniqid() . '.' . $fileExtension;

            move_uploaded_file($_FILES['file_1']['tmp_name'], './../../uploads/' . $fileName);

            insert('uploaded_pdf_candidate', ['file_name' => $fileName, 'can_id' => $_POST['can_id'], 'file_for' => $_POST['for_type']]);

            echo json_encode(['file' => $fileName]);

        }

    }

}

if (isset($_POST['upload_cv']) && !empty($_POST['upload_cv'])) {

    if (isset($_POST['can_id']) && !empty($_POST['can_id'])) {

        if (isset($_FILES['file_1']['name']) && !empty($_FILES['file_1']['name'][0])) {
            $canId = $_POST['can_id'];
            $uploadedFiles = [];

            $stmt = $conn->prepare("SELECT cv FROM candidates WHERE id = :id");
            $stmt->execute([':id' => $canId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $existingFiles = (!empty($row['cv'])) ? explode(',', $row['cv']) : [];

            foreach ($_FILES['file_1']['name'] as $key => $originalFileName) {
                // $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);
                // $fileName = time() . '-' . uniqid() . '.' . $fileExtension;
                
                // $originalName = $_FILES['files']['name'][$i];
                $fileName = time() . '-' . str_replace(",", "", $originalFileName);
                
                if (move_uploaded_file($_FILES['file_1']['tmp_name'][$key], './../../uploads/' . $fileName)) {
                    $uploadedFiles[] = $fileName;
                    $existingFiles[] = $fileName;
                }
            }

            if (count($existingFiles) > 5) {
                $existingFiles = array_slice($existingFiles, -5);
            }

            $cvString = implode(',', $existingFiles);

            $updateStmt = $conn->prepare("UPDATE candidates SET cv = :cv WHERE id = :id");
            $updateStmt->execute([':cv' => $cvString, ':id' => $canId]);

            echo json_encode(['files' => $uploadedFiles]);
        }
    }
}
if (isset($_POST['delete_cv']) && !empty($_POST['delete_cv'])) {
    if (isset($_POST['can_id']) && !empty($_POST['can_id'])) {
        $canId = $_POST['can_id'];
        $fileName = $_POST['delete_cv'];

        $stmt = $conn->prepare("SELECT cv FROM candidates WHERE id = :id");
        $stmt->execute([':id' => $canId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row && !empty($row['cv'])) {
            $files = explode(',', $row['cv']);
            $files = array_filter($files, function ($f) use ($fileName) {
                return $f !== $fileName;
            });
            $cvString = implode(',', $files);

            $updateStmt = $conn->prepare("UPDATE candidates SET cv = :cv WHERE id = :id");
            $updateStmt->execute([':cv' => $cvString, ':id' => $canId]);

            $filePath = './../../uploads/' . $fileName;
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            echo json_encode(['deleted' => $fileName]);
            exit;
        }
    }

}

if (isset($_POST['upload_int']) && !empty($_POST['upload_int'])) {

    if (isset($_POST['can_id']) && !empty($_POST['can_id'])) {

        if (isset($_FILES['file_1']['name']) && !empty($_FILES['file_1']['name'])) {

            $originalFileName = $_FILES['file_1']['name'];

            $fileExtension = pathinfo($originalFileName, PATHINFO_EXTENSION);

            $trimmedFileName = trim($originalFileName);

            $fileName = time() . '-' . uniqid() . '.' . $fileExtension;

            move_uploaded_file($_FILES['file_1']['tmp_name'], './../../uploads/' . $fileName);

            update('candidates', ['interview_template' => $fileName], 'id', $_POST['can_id']);

            echo json_encode(['file' => $fileName]);

        }

    }

}

if (isset($_POST['add_customer_question']) && !empty($_POST['add_customer_question'])) {

    $result = findAllByQuery('SELECT * FROM customer_question WHERE cus_id = ' . $_POST["cus_id"]);

    if (!empty($result)) {

        update('customer_question', ['meta_data' => json_encode($_POST['qs'])], 'cus_id', $_POST['cus_id']);

    } else {

        insert('customer_question', ['cus_id' => $_POST['cus_id'], 'meta_data' => json_encode($_POST['qs'])]);

    }

    echo json_encode(['success' => 'Updated Successfully']);

}

if (isset($_POST['get_par_department']) && !empty($_POST['get_par_department'])) {

    $customers = findAllByQuery('SELECT * FROM customers WHERE id = ' . $_POST["id"]);

    $departments = findAllByQuery('SELECT * FROM departments WHERE dep_cus_id = ' . $_POST["id"] . ' AND dep_trash = 0');

    $services = findAllByQuery('SELECT * FROM customer_services WHERE cus_id = ' . $_POST["id"] . ' GROUP BY service_id');

    $permissions = findAllByQuery('SELECT * FROM user_allowed_permissions WHERE user_id = ' . $_POST["id"] . ' AND user_type = 2');

    echo json_encode(['customers' => $customers, 'departments' => $departments, 'services' => $services, 'permissions' => $permissions]);

}

if (isset($_POST['add_permission']) && !empty($_POST['add_permission'])) {

    if (!empty($_POST['name'])) {

        $last_id = insert('user_permissions', ['title' => $_POST['name'], 'user_type' => 3]);

        echo json_encode($last_id);

    }

}

if (isset($_POST['update_permission']) && !empty($_POST['update_permission'])) {

    if (!empty($_POST['u_id'])) {

        update('user_permissions', ['title' => $_POST['name']], 'id', $_POST['u_id']);

    }

}

if (isset($_POST['reported_to_sm']) && !empty($_POST['reported_to_sm'])) {

    if (!empty($_POST['can_id'])) {

        if (!empty($_POST['reported'] == 1)) {

            $date = date('Y-m-d H:i:s');

            $name = ' By ' . $_SESSION['admin']->name;

            $desc = 'Reported';

            $order_id = $_POST['can_id'];

            update('candidates', ['reported_to_sm' => $_POST['reported'], 'reported_to_sm_on' => date('Y-m-d')], 'id', $_POST['can_id']);

            $query = 'INSERT INTO history (`desc`,`order_id`,`date_time`,`comment`) VALUES ("' . $desc . '", "' . $order_id . '", "' . $date . '", "' . $name . '")';

            $stmt = $conn->prepare($query);

            $stmt->execute();

        } else {

            update('candidates', ['reported_to_sm' => 0, 'reported_to_sm_on' => null], 'id', $_POST['can_id']);

            $query = "DELETE FROM history WHERE `order_id` = {$_POST['can_id']} AND `desc` = 'Reported'";

            $stmt = $conn->prepare($query);

            $stmt->execute();

        }

    }

}



if (isset($_POST['delete_email']) && !empty($_POST['delete_email'])) {

    if (isset($_POST['email_id']) && !empty($_POST['email_id'])) {

        delete('emails', 'id', $_POST['email_id']);

    }

}

if (isset($_POST['interview_template']) && !empty($_POST['interview_template'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        update('customers', ['interview_template' => $_POST['check']], 'id', $_POST['id']);

    }

}
if (isset($_POST['interview_upload_allowed']) && !empty($_POST['interview_upload_allowed'])) {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        update('customers', ['interview_upload_allowed' => $_POST['check']], 'id', $_POST['id']);
        update('customers', ['interview_upload_allowed' => $_POST['check']], 'parent_id', $_POST['id']);
    }
}

if (isset($_POST['interviewed_template']) && !empty($_POST['interviewed_template'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        update('customers', ['remainder_email' => $_POST['check']], 'id', $_POST['id']);

    }

}

if (isset($_POST['interview_template']) && !empty($_POST['interview_template'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        update('customers', ['interview_template' => $_POST['check']], 'id', $_POST['id']);

    }

}

if (isset($_POST['remainder_email_template']) && !empty($_POST['remainder_email_template'])) {

    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {

        update('customers', ['remainder_email_template' => $_POST['email_body']], 'id', $_POST['cus_id']);

    }

}

if (isset($_POST['fetch_template']) && !empty($_POST['fetch_template'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        $data = findAllByQuery("SELECT remainder_email_template FROM customers WHERE id = {$_POST['id']}");

        echo json_encode($data);

    }

}
if (isset($_POST['bk_interviewed_template']) && !empty($_POST['bk_interviewed_template'])) {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        update('customers', ['bk_remainder_email' => $_POST['check']], 'id', $_POST['id']);
    }
}
if (isset($_POST['bk_remainder_email_template']) && !empty($_POST['bk_remainder_email_template'])) {
    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
        update('customers', ['bk_remainder_email_template' => $_POST['bk_email_body']], 'id', $_POST['cus_id']);
    }
}
if (isset($_POST['fetch_bk_template']) && !empty($_POST['fetch_bk_template'])) {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $data = findAllByQuery("SELECT bk_remainder_email_template FROM customers WHERE id = {$_POST['id']}");
        echo json_encode($data);
    }
}

if (isset($_POST['add_additional_customer']) && !empty($_POST['add_additional_customer'])) {

    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {

        if (isset($_POST['name']) && !empty($_POST['name'])) {

            $data = findByQuery("SELECT * FROM additional_customers WHERE email = '" . $_POST["email"] . "' AND cus_id = " . $_POST['cus_id']);

            if (!empty($data)) {

                echo json_encode(['success' => 'Email already exists']);

            } else {

                $inert_data = array(

                    'name' => $_POST['name'],

                    'email' => $_POST['email'],

                    'cus_id' => $_POST['cus_id'],

                );



                $last_id = insert('additional_customers', $inert_data);



                echo json_encode(['success' => 'Additional customer added successfully!', 'last_id' => $last_id, 'name' => $_POST['name'], 'email' => $_POST['email']]);

            }

        }

    }

}

if (isset($_POST['update_additional_customer']) && !empty($_POST['update_additional_customer'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        if (isset($_POST['name']) && !empty($_POST['name'])) {

            $update_data = array(

                'name' => $_POST['name'],

                'email' => $_POST['email'],

            );

            update('additional_customers', $update_data, 'id', $_POST['id']);



            echo json_encode(['success' => 'Updated Successfully!', 'last_id' => $_POST['id'], 'name' => $_POST['name'], 'email' => $_POST['email']]);

        } else {

            echo json_encode(['error' => 'Please Enter Name First!']);

        }

    } else {

        echo json_encode(['error' => 'Something went wrong please try again!']);

    }

}



if (isset($_POST['delete_ad_cu']) && !empty($_POST['delete_ad_cu'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        delete('additional_customers', 'id', $_POST['id']);

        echo json_encode(['success' => 'Deleted Successfully!']);

    }

}



if (isset($_POST['get_additional_customer_data']) && !empty($_POST['get_additional_customer_data'])) {

    if (isset($_POST['get_id']) && !empty($_POST['get_id'])) {

        $additional_customers = findAllByQuery("SELECT * FROM additional_customers WHERE id = {$_POST['get_id']}");

        echo json_encode(['additional_customers' => $additional_customers]);

    }

}

if (isset($_POST['get_cus_service']) && !empty($_POST['get_cus_service'])) {

    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {

        $result = findAllByQuery('SELECT cus_msg from messages WHERE cus_id = ' . $_POST['cus_id'] . ' AND interview_id = ' . $_POST['ser_id']);

        echo json_encode($result);

    }

}

if (isset($_POST['type']) && $_POST['type'] == 'update_cus') {

    $u_cus = !empty($_POST['u_cus']) ? $_POST['u_cus'] : '';

    $can_id = !empty($_POST['can_id']) ? $_POST['can_id'] : '';

    $u_cus_email = !empty($_POST['u_cus_email']) ? $_POST['u_cus_email'] : '';

    update('candidates', ['cus_id' => $_POST['u_cus']], 'order_id', $_POST['can_id']);
    $query = 'SELECT candidates.*,customers.combine_bk_and_security,customers.combine_status,customers.name as cus_name,customers.email as cus_email,customers.company,places.name as place_name,interviews.title as inter_title,interviews.service_cat_id FROM candidates LEFT JOIN customers ON candidates.cus_id = customers.id LEFT JOIN interviews ON candidates.interview_id = interviews.id LEFT JOIN places ON candidates.place = places.id WHERE order_id = ?';
    $stmt = $conn->prepare($query);

    $stmt->execute([$can_id]);

    $result = $stmt->fetch();



    if (empty($u_cus_email)) {

        $query = 'SELECT cus_msg FROM messages WHERE cus_id = ? AND interview_id = ? ';

        $stmt = $conn->prepare($query);

        $stmt->execute([$u_cus, $result->interview_id]);

        $u_cus_email = $stmt->fetch();

        $u_cus_email = $u_cus_email->cus_msg;

    }



    $query = 'SELECT * FROM service_categories WHERE id = ?';

    $stmt = $conn->prepare($query);

    $stmt->execute([$result->service_cat_id]);

    $serviceCat = $stmt->fetch();

    // Create a DateTime object for Sweden's timezone

    $swedenTimezone = new DateTimeZone('Europe/Stockholm');

    $swedenTime = new DateTime('now', $swedenTimezone);

    $currentTime = $swedenTime->format('H:i:s');

    $dayOfWeek = date('N');



    $cusBody = replace($u_cus_email, $result->cus_name, $result->name . " " . $result->surname, $result->company, $result->inter_title, '', '', '', '', '', $result->order_id, '', '', '', $result->vasc_id, $result->inter_title, !empty($result->place_name) ? $result->place_name : '');

    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {

        saveEmail("Customer", $result->cus_name, $result->order_id, 'Customer Message', $cusBody, $result->cus_email, $serviceCat->name);

        $mailMsg = sendMail($cusBody, $result->cus_email, $result->cus_name, $serviceCat->name);

    } else {

        saveEmail("Customer", $result->cus_name, $result->order_id, 'Customer Message', $cusBody, $result->cus_email, $serviceCat->name, '1');

    }

    echo json_encode($result);

}

if (isset($_POST['delete_file']) && !empty($_POST['delete_file'])) {

    if (isset($_POST['id']) && !empty($_POST['id'])) {

        update('uploaded_pdf_candidate', ['is_trash' => 1], 'file_name', $_POST['id']);

        echo json_encode(['success' => 'Deleted Successfully!']);

    }

}
if (isset($_POST['add_company_manager']) && !empty($_POST['add_company_manager'])) {
    logMessage("Add company manager request received from admin ID: " . $_SESSION['admin']->name);
    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
        $cus_id = $_POST['cus_id'];
        $email_temp = $_POST['email_temp'];
        $enable_function = $_POST['enable_function'];
        $company = trim($_POST['company_name']);

                logMessage("Processing company manager for customer ID: $cus_id, Company: $company");
        logMessage("New data received: " . print_r([
            'company' => $company,
            'cus_id' => $cus_id,
            'email_template' => $email_temp,
            'can_view_report' => $enable_function
        ], true));

        $query = "SELECT * FROM company_manager WHERE cus_id = :cus_id";
        $stmt = $conn->prepare($query);
        $stmt->execute([':cus_id' => $cus_id]);

        if ($stmt->rowCount() > 0) {
                        $oldData = $stmt->fetch(PDO::FETCH_ASSOC);
            logMessage("Old company manager data being replaced: " . print_r($oldData, true));
            $deleteQuery = "DELETE FROM company_manager WHERE cus_id = :cus_id";
            $deleteStmt = $conn->prepare($deleteQuery);
            $deleteStmt->execute([':cus_id' => $cus_id]);
        }

        $insertQuery = "INSERT INTO company_manager (company, cus_id, email_template, can_view_report) VALUES (:company, :cus_id, :email_template, :can_view_report)";
        $insertStmt = $conn->prepare($insertQuery);
        $insertStmt->execute([':company' => $company, ':cus_id' => $cus_id, ':email_template' => $email_temp, ':can_view_report' => $enable_function]);
        logMessage("Successfully added company manager for customer ID: $cus_id");
        echo json_encode(['success' => 'Record updated successfully!']);
    } else {
        echo json_encode(['error' => 'Customer ID and Company Name are required.']);
    }
}
if (isset($_POST['standard_billing_details']) && !empty($_POST['standard_billing_details'])) {
    if (isset($_POST['cus_id']) && !empty($_POST['cus_id'])) {
        $cus_id = $_POST['cus_id'];
        $company = trim($_POST['company']); // Define $company
        $pref = isset($_POST['pref']) ? trim($_POST['pref']) : null;
        $ref = isset($_POST['ref']) ? trim($_POST['ref']) : null;
        $comment = isset($_POST['comment']) ? trim($_POST['comment']) : null;

        try {
            // Check if the record exists
            $query = "SELECT * FROM standard_billing_details WHERE cus_id = :cus_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':cus_id' => $cus_id]);

            if ($stmt->rowCount() > 0) {
                // Update the existing record
                $updateQuery = "UPDATE standard_billing_details 
                                SET referenceperson = :pref, reference = :ref, comment = :comment 
                                WHERE cus_id = :cus_id";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->execute([
                    ':pref' => $pref,
                    ':ref' => $ref,
                    ':comment' => $comment,
                    ':cus_id' => $cus_id
                ]);
            } else {
                // Insert a new record if it doesn't exist
                $insertBillingQuery = "INSERT INTO standard_billing_details (cus_id, referenceperson, reference, comment) 
                                       VALUES (:cus_id, :pref, :ref, :comment)";
                $insertBillingStmt = $conn->prepare($insertBillingQuery);
                $insertBillingStmt->execute([
                    ':cus_id' => $cus_id,
                    ':pref' => $pref,
                    ':ref' => $ref,
                    ':comment' => $comment
                ]);
            }

            echo json_encode(['success' => 'Record updated successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'Customer ID and Company Name are required.']);
    }

}


if (isset($_POST['interview_report_upload']) && !empty($_POST['interview_report_upload'])) {
    if (isset($_FILES['interview_report']) && $_FILES['interview_report']['error'] == UPLOAD_ERR_OK) {
        try {
            // Validate file
            $allowedTypes = [
                'application/pdf',
                'application/msword', // .doc
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
                'application/zip', // Sometimes .docx is detected as zip
                'application/octet-stream' // Some servers return this
            ];

            $fileType = mime_content_type($_FILES['interview_report']['tmp_name']);
            $fileSize = $_FILES['interview_report']['size'];

            if (!in_array($fileType, $allowedTypes)) {
                echo json_encode(['error' => 'Invalid file type. Only PDF and Word documents are allowed.']);
                exit;
            }

            if ($fileSize > 20 * 1024 * 1024) { // 20MB size limit
                echo json_encode(['error' => 'File size exceeds the limit of 20MB.']);
                exit;
            }

            // Move uploaded file
            $uploadDir = './../../security-report/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            $fileName = uniqid() . '_' . basename($_FILES['interview_report']['name']);
            $filePath = $uploadDir . $fileName;
            if (!move_uploaded_file($_FILES['interview_report']['tmp_name'], $filePath)) {
                echo json_encode(['error' => 'Failed to save the uploaded file.']);
                exit;
            }

            // Database insert
            $can_id = isset($_POST['can_id']) ? trim($_POST['can_id']) : null;
            if (!$can_id) {
                echo json_encode(['error' => 'Candidate ID is required.']);
                exit;
            }

            $type = isset($_POST['type']) ? trim($_POST['type']) : null;
            $query = "SELECT interview_report FROM candidates WHERE id = :can_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':can_id' => $can_id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            $interviewReport = [];
            $currentValue = $row['interview_report'];
            $decoded = json_decode($currentValue, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $interviewReport = $decoded;
            } elseif (!empty($currentValue)) {
                $interviewReport['spi'] = $currentValue;
            }

            $interviewReport[$type] = $filePath;
            $updatedJson = json_encode($interviewReport);

            $updateQuery = "UPDATE candidates SET interview_report = :filePath WHERE id = :can_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->execute([
                ':filePath' => $updatedJson,
                ':can_id' => $can_id
            ]);
            $interview_commnet = 'Interview Report Uploaded';
            if ($type == 'spi') {
                $interview_commnet = 'Interview SPI Report Uploaded';
            } else if ($type == 'ellevio') {
                $interview_commnet = 'Interview Ellevio Report Uploaded';
            }else if ($type == 'timra') {
                $interview_commnet = 'Timrå Interview Report Uploaded';
            }

            $date = date('Y-m-d H:i:s');
            if (isSwedenWorkingHours() == 1) {
                $date = date('Y-m-d H:i:s');
            } else {
                $date = getNextWorkingHour()->format('Y-m-d H:i:s');
            }
            $name = ' By ' . $_SESSION['admin']->name;
            $desc = $interview_commnet;
            $order_id = $_POST['can_id'];
            $query = 'INSERT INTO history (`desc`,`order_id`,`date_time`,`comment`) VALUES ("' . $desc . '", "' . $order_id . '", "' . $date . '", "' . $name . '")';
            $stmt = $conn->prepare($query);
            $stmt->execute();

            $query = "SELECT cus_id FROM candidates WHERE id = :can_id";
            $stmt = $conn->prepare($query);
            $stmt->execute([':can_id' => $can_id]);
            $cus_id = $stmt->fetchColumn();

            if ($cus_id) {
                $query = "SELECT company FROM customers WHERE id = :cus_id";
                $stmt = $conn->prepare($query);
                $stmt->execute([':cus_id' => $cus_id]);
                $company = $stmt->fetchColumn();

                if ($company) {
                    $query = "SELECT * FROM company_manager WHERE company = :company";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([':company' => $company]);
                    $managerRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    if (!empty($managerRecords)) {
                        foreach ($managerRecords as $managerRecord) {
                            if ($managerRecord && isset($managerRecord['cus_id'])) {
                                $managerCusId = $managerRecord['cus_id'];

                                $query = "SELECT * FROM customers WHERE id = :cus_id";
                                $stmt = $conn->prepare($query);
                                $stmt->execute([':cus_id' => $managerCusId]);

                                $customerRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                                if ($customerRecord) {
                                    if (isset($managerRecord['can_view_report']) && !empty($managerRecord['can_view_report'])) {
                                        if (isset($managerRecord['email_template']) && !empty($managerRecord['email_template'])) {

                                            $query = "SELECT * FROM candidates WHERE id = :can_id";
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([':can_id' => $can_id]);
                                            $candidate = $stmt->fetch();

                                            $query = "SELECT * FROM statuses WHERE id = :status_id";
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([':status_id' => $candidate->status]);
                                            $status = $stmt->fetch();

                                            $query = "SELECT * FROM places WHERE id = :place_id";
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([':place_id' => $candidate->place]);
                                            $place = $stmt->fetch();

                                            $query = "SELECT * FROM staff WHERE id = :staff_id";
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([':staff_id' => $candidate->staff_id]);
                                            $staff = $stmt->fetch();

                                            $query = "SELECT * FROM interviews WHERE id = :interview";
                                            $stmt = $conn->prepare($query);
                                            $stmt->execute([':interview' => $candidate->interview_id]);
                                            $service = $stmt->fetch();

                                            $currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
                                            if (!empty($managerRecord['email_template']) && strlen(trim($managerRecord['email_template'])) > 0) {
                                                $body = replace($managerRecord['email_template'], $customerRecord['name'], $candidate->name . " " . $candidate->surname, $customerRecord['company'], $service->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $currentDateTime, $candidate->order_id, '', '', '', $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                                // Create a DateTime object for Sweden's timezone
                                                $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                                                $swedenTime = new DateTime('now', $swedenTimezone);
                                                $currentTime = $swedenTime->format('H:i:s');
                                                $dayOfWeek = date('N');
                                                //matching time between 8am to 5pm
                                                if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                                    saveEmail("Customer", $customerRecord['name'], $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded');
                                                    sendMail($body, $customerRecord['email'], $customerRecord['name'], 'Interview Report Uploaded');
                                                } else {
                                                    saveEmail("Customer", $customerRecord['name'], $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded', "1");
                                                }
                                            }
                                        }
                                    }
                                } else {
                                    echo "No customer found for the given customer ID from the manager record.";
                                }
                            } else {
                                echo "No cus_id found in the manager record or manager record is empty.";
                            }
                        }
                        $query = "SELECT * FROM company_manager WHERE cus_id = :cus_id";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([':cus_id' => $cus_id]);
                        $managerRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!empty($managerRecords)) {
                            foreach ($managerRecords as $managerRecord) {
                                if ($managerRecord && isset($managerRecord['cus_id'])) {
                                    $managerCusId = $managerRecord['cus_id'];

                                    $query = "SELECT * FROM customers WHERE id = :cus_id";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute([':cus_id' => $managerCusId]);

                                    $customerRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($customerRecord) {
                                        if (isset($managerRecord['can_view_report']) && !empty($managerRecord['can_view_report'])) {
                                            if (isset($managerRecord['email_template']) && !empty($managerRecord['email_template'])) {

                                                $query = "SELECT * FROM candidates WHERE id = :can_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':can_id' => $can_id]);
                                                $candidate = $stmt->fetch();

                                                $query = "SELECT * FROM statuses WHERE id = :status_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':status_id' => $candidate->status]);
                                                $status = $stmt->fetch();

                                                $query = "SELECT * FROM places WHERE id = :place_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':place_id' => $candidate->place]);
                                                $place = $stmt->fetch();

                                                $query = "SELECT * FROM staff WHERE id = :staff_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':staff_id' => $candidate->staff_id]);
                                                $staff = $stmt->fetch();

                                                $query = "SELECT * FROM interviews WHERE id = :interview";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':interview' => $candidate->interview_id]);
                                                $service = $stmt->fetch();

                                                $currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
                                                if (!empty($managerRecord['email_template']) && strlen(trim($managerRecord['email_template'])) > 0) {
                                                    $body = replace($managerRecord['email_template'], $customerRecord['name'], $candidate->name . " " . $candidate->surname, $customerRecord['company'], $service->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $currentDateTime, $candidate->order_id, '', '', '', $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                                    // Create a DateTime object for Sweden's timezone
                                                    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                                                    $swedenTime = new DateTime('now', $swedenTimezone);
                                                    $currentTime = $swedenTime->format('H:i:s');
                                                    $dayOfWeek = date('N');
                                                    //matching time between 8am to 5pm
                                                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                                        saveEmail("Customer", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded');
                                                        sendMail($body, $customerRecord['email'], $customerRecord['name'], 'Interview Report Uploaded');
                                                    } else {
                                                        saveEmail("Customer", $customerRecord['name'], $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded', "1");
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        echo "No customer found for the given customer ID from the manager record.";
                                    }
                                } else {
                                    echo "No cus_id found in the manager record or manager record is empty.";
                                }
                            }
                        }
                    } else {
                        $query = "SELECT * FROM company_manager WHERE cus_id = :cus_id";
                        $stmt = $conn->prepare($query);
                        $stmt->execute([':cus_id' => $cus_id]);
                        $managerRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        if (!empty($managerRecords)) {
                            foreach ($managerRecords as $managerRecord) {
                                if ($managerRecord && isset($managerRecord['cus_id'])) {
                                    $managerCusId = $managerRecord['cus_id'];

                                    $query = "SELECT * FROM customers WHERE id = :cus_id";
                                    $stmt = $conn->prepare($query);
                                    $stmt->execute([':cus_id' => $managerCusId]);

                                    $customerRecord = $stmt->fetch(PDO::FETCH_ASSOC);

                                    if ($customerRecord) {
                                        if (isset($managerRecord['can_view_report']) && !empty($managerRecord['can_view_report'])) {
                                            if (isset($managerRecord['email_template']) && !empty($managerRecord['email_template'])) {

                                                $query = "SELECT * FROM candidates WHERE id = :can_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':can_id' => $can_id]);
                                                $candidate = $stmt->fetch();

                                                $query = "SELECT * FROM statuses WHERE id = :status_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':status_id' => $candidate->status]);
                                                $status = $stmt->fetch();

                                                $query = "SELECT * FROM places WHERE id = :place_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':place_id' => $candidate->place]);
                                                $place = $stmt->fetch();

                                                $query = "SELECT * FROM staff WHERE id = :staff_id";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':staff_id' => $candidate->staff_id]);
                                                $staff = $stmt->fetch();

                                                $query = "SELECT * FROM interviews WHERE id = :interview";
                                                $stmt = $conn->prepare($query);
                                                $stmt->execute([':interview' => $candidate->interview_id]);
                                                $service = $stmt->fetch();

                                                $currentDateTime = (new DateTime())->format('Y-m-d H:i:s');
                                                if (!empty($managerRecord['email_template']) && strlen(trim($managerRecord['email_template'])) > 0) {
                                                    $body = replace($managerRecord['email_template'], $customerRecord['name'], $candidate->name . " " . $candidate->surname, $customerRecord['company'], $service->title, !empty($staff) ? $staff->name : '', '', '', $status->status, $currentDateTime, $candidate->order_id, '', '', '', $candidate->vasc_id, $service->title, !empty($place) ? $place->name : '');
                                                    // Create a DateTime object for Sweden's timezone
                                                    $swedenTimezone = new DateTimeZone('Europe/Stockholm');
                                                    $swedenTime = new DateTime('now', $swedenTimezone);
                                                    $currentTime = $swedenTime->format('H:i:s');
                                                    $dayOfWeek = date('N');
                                                    //matching time between 8am to 5pm
                                                    if ($dayOfWeek >= 1 && $dayOfWeek <= 5 && $currentTime > '08:00:00' && $currentTime < '18:00:00') {
                                                        saveEmail("Customer", $candidate->name . " " . $candidate->surname, $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded');
                                                        sendMail($body, $customerRecord['email'], $customerRecord['name'], 'Interview Report Uploaded');
                                                    } else {
                                                        saveEmail("Customer", $customerRecord['name'], $candidate->order_id, 'Interview Report Uploaded', $body, $customerRecord['email'], 'Interview Report Uploaded', "1");
                                                    }
                                                }
                                            }
                                        }
                                    } else {
                                        echo "No customer found for the given customer ID from the manager record.";
                                    }
                                } else {
                                    echo "No cus_id found in the manager record or manager record is empty.";
                                }
                            }
                        }
                    }
                } else {
                    echo "No company found for the given customer ID.";
                }
            } else {
                echo "No record found for the given candidate ID.";
            }

            echo json_encode(['success' => 'File uploaded successfully!']);
        } catch (PDOException $e) {
            echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['error' => 'No file uploaded or an error occurred.']);
    }
}
if (isset($_POST['update_report_upload']) && !empty($_POST['update_report_upload'])) {
    if (isset($_POST['staff_id']) && !empty($_POST['staff_id'])) {
        $updateQuery = "UPDATE staff 
        SET can_upload_report = :pref
        WHERE id = :id";
        $updateStmt = $conn->prepare($updateQuery);
        $updateStmt->execute([
            ':pref' => $_POST['check_val'],
            ':id' => $_POST['staff_id']
        ]);
    }
}


if (isset($_POST['delete_comment']) && !empty($_POST['id'])) {
    $id = $_POST['id'];

    // Fetch original comment
    $stmt = $conn->prepare("SELECT comment FROM history WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && isset($row['comment'])) {
        $originalComment = $row['comment'];

        // Keep only <br> and what follows
        $split = explode('<br>', $originalComment, 2);
        $newComment = isset($split[1]) ? '<br>' . $split[1] : '';

        // Update
        $update = $conn->prepare("UPDATE history SET comment = :comment WHERE id = :id");
        $update->execute([
            ':comment' => $newComment,
            ':id' => $id
        ]);

        // Return new comment as response
        echo $newComment;
    }
    exit;
}



