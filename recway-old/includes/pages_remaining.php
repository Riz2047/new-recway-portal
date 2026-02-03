


// Places DataTable AJAX endpoint

if (isset($_POST['action']) && $_POST['action'] == 'get_places_data') {

    // Authentication check

    if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {

        http_response_code(401);

        echo json_encode(['error' => 'Unauthorized']);

        exit;

    }



    header('Content-Type: application/json');

    ob_clean();



    try {

        // Get DataTable parameters

        $draw = intval($_POST['draw']);

        $start = intval($_POST['start']);

        $length = intval($_POST['length']);

        $searchValue = $_POST['search']['value'] ?? '';

        $orderColumn = intval($_POST['order'][0]['column'] ?? 0);

        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';



        // Build base query

        $baseQuery = 'SELECT * FROM places';

        $countQuery = 'SELECT COUNT(*) as total FROM places';



        $whereConditions = [];

        $params = [];



        // Apply search filter

        if (!empty($searchValue)) {

            $whereConditions[] = 'name LIKE ?';

            $params[] = '%' . $searchValue . '%';

        }



        // Add WHERE clause if conditions exist

        if (!empty($whereConditions)) {

            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);

            $baseQuery .= $whereClause;

            $countQuery .= $whereClause;

        }



        // Get total count

        $stmt = $conn->prepare($countQuery);

        $stmt->execute($params);

        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



        // Define columns for ordering

        $columns = [

            0 => 'id',

            1 => 'name',

            2 => 'name'

        ];



        // Add ORDER BY clause

        if (isset($columns[$orderColumn])) {

            $baseQuery .= ' ORDER BY ' . $columns[$orderColumn] . ' ' . strtoupper($orderDir);

        } else {

            $baseQuery .= ' ORDER BY name ASC';

        }



        // Add LIMIT for pagination

        $baseQuery .= ' LIMIT ' . $start . ', ' . $length;



        // Execute main query

        $stmt = $conn->prepare($baseQuery);

        $stmt->execute($params);

        $places = $stmt->fetchAll(PDO::FETCH_ASSOC);



        // Format data for DataTable

        $data = [];

        foreach ($places as $index => $place) {

            $data[] = [

                '<div class="dropdown">

                    <button class="table-menu-btn mx-auto dropdownBtn" type="button" data-bs-toggle="dropdown" id="dropdownMenuButton' . $place['id'] . '" aria-expanded="false">

                        <i class="bi bi-gear"></i>

                    </button>

                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $place['id'] . '">

                        <input type="hidden" class="u_id" value="' . $place['id'] . '">

                        <input type="hidden" class="u_name" value="' . htmlspecialchars($place['name']) . '">

                        <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen text-black f-14 me-2"></i>Edit</a></li>

                        <li class="mb-1"><a href="?delete=' . $place['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-trash text-black f-14 me-2"></i>Delete</a></li>

                    </ul>

                </div>',

                $start + $index + 1, // Row number

                $place['name']

            ];

        }



        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => $totalRecords,

            'recordsFiltered' => $totalRecords,

            'data' => $data

        ]);



    } catch (Exception $e) {

        error_log("Places query error: " . $e->getMessage());

        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => 0,

            'recordsFiltered' => 0,

            'data' => [],

            'error' => 'Database error occurred'

        ]);

    }

    exit;

}



// Email Logs DataTable AJAX endpoint

if (isset($_POST['action']) && $_POST['action'] == 'get_email_logs_data') {

    // Authentication check

    if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {

        http_response_code(401);

        echo json_encode(['error' => 'Unauthorized']);

        exit;

    }



    header('Content-Type: application/json');

    ob_clean();



    try {

        // Get DataTable parameters

        $draw = intval($_POST['draw']);

        $start = intval($_POST['start']);

        $length = intval($_POST['length']);

        $searchValue = $_POST['search']['value'] ?? '';

        $orderColumn = intval($_POST['order'][0]['column'] ?? 0);

        $orderDir = $_POST['order'][0]['dir'] ?? 'desc';



        // Build base query - only show last month's emails

        $currentDate = date('Y-m-d');

        $lastMonth = date('Y-m-d', strtotime('-1 month', strtotime($currentDate)));

        

        $baseQuery = 'SELECT * FROM emails WHERE created >= ?';

        $countQuery = 'SELECT COUNT(*) as total FROM emails WHERE created >= ?';

        

        $params = [$lastMonth . ' 00:00:00'];

        $countParams = [$lastMonth . ' 00:00:00'];



        // Apply search filter

        if (!empty($searchValue)) {

            $baseQuery .= ' AND (order_id LIKE ? OR msg_type LIKE ? OR email LIKE ?)';

            $countQuery .= ' AND (order_id LIKE ? OR msg_type LIKE ? OR email LIKE ?)';

            $searchParam = '%' . $searchValue . '%';

            $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);

            $countParams = array_merge($countParams, [$searchParam, $searchParam, $searchParam]);

        }



        // Get total count

        $stmt = $conn->prepare($countQuery);

        $stmt->execute($countParams);

        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



        // Define columns for ordering

        $columns = [

            0 => 'order_id',

            1 => 'msg_type',

            2 => 'email',

            3 => 'email_delay',

            4 => 'created'

        ];



        // Add ORDER BY clause

        if (isset($columns[$orderColumn])) {

            $baseQuery .= ' ORDER BY ' . $columns[$orderColumn] . ' ' . strtoupper($orderDir);

        } else {

            $baseQuery .= ' ORDER BY id DESC';

        }



        // Add LIMIT for pagination

        $baseQuery .= ' LIMIT ' . $start . ', ' . $length;



        // Execute main query

        $stmt = $conn->prepare($baseQuery);

        $stmt->execute($params);

        $emailLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);



        // Format data for DataTable

        $data = [];

        foreach ($emailLogs as $emailLog) {

            $status = empty($emailLog['email_delay']) ? 

                '<span class="badge badge-success">Sended</span>' : 

                '<span class="badge badge-danger">Pending</span>';

                

            $actionButton = '';

            if (!empty($emailLog['email_delay'])) {

                $actionButton = '<input type="hidden" class="email_id" value="' . $emailLog['id'] . '">

                    <button type="button" class="btn btn-danger btn-sm m-0" onclick="delete_email(this)">

                        <i class="fas fa-trash"></i>

                    </button>';

            }



            $data[] = [

                $emailLog['order_id'],

                $emailLog['msg_type'],

                $emailLog['email'],

                $status,

                $emailLog['created'],

                $actionButton

            ];

        }



        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => $totalRecords,

            'recordsFiltered' => $totalRecords,

            'data' => $data

        ]);



    } catch (Exception $e) {

        error_log("Email Logs query error: " . $e->getMessage());

        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => 0,

            'recordsFiltered' => 0,

            'data' => [],

            'error' => 'Database error occurred'

        ]);

    }

    exit;

}



// Services DataTable AJAX endpoint

if (isset($_POST['action']) && $_POST['action'] == 'get_services_data') {

    // Authentication check

    if (!isset($_SESSION['admin']->id) && !isset($_SESSION['staff']->id)) {

        http_response_code(401);

        echo json_encode(['error' => 'Unauthorized']);

        exit;

    }



    header('Content-Type: application/json');

    ob_clean();



    try {

        // Get DataTable parameters

        $draw = intval($_POST['draw']);

        $start = intval($_POST['start']);

        $length = intval($_POST['length']);

        $searchValue = $_POST['search']['value'] ?? '';

        $orderColumn = intval($_POST['order'][0]['column'] ?? 0);

        $orderDir = $_POST['order'][0]['dir'] ?? 'asc';



        // Build base query

        $baseQuery = 'SELECT * FROM service_categories';

        $countQuery = 'SELECT COUNT(*) as total FROM service_categories';



        $whereConditions = [];

        $params = [];



        // Apply search filter

        if (!empty($searchValue)) {

            $whereConditions[] = '(name LIKE ? OR name_sv LIKE ?)';

            $searchParam = '%' . $searchValue . '%';

            $params = array_merge($params, [$searchParam, $searchParam]);

        }



        // Add WHERE clause if conditions exist

        if (!empty($whereConditions)) {

            $whereClause = ' WHERE ' . implode(' AND ', $whereConditions);

            $baseQuery .= $whereClause;

            $countQuery .= $whereClause;

        }



        // Get total count

        $stmt = $conn->prepare($countQuery);

        $stmt->execute($params);

        $totalRecords = $stmt->fetch(PDO::FETCH_ASSOC)['total'];



        // Define columns for ordering

        $columns = [

            0 => 'id',

            1 => 'name',

            2 => 'name',

            3 => 'name_sv'

        ];



        // Add ORDER BY clause

        if (isset($columns[$orderColumn])) {

            $baseQuery .= ' ORDER BY ' . $columns[$orderColumn] . ' ' . strtoupper($orderDir);

        } else {

            $baseQuery .= ' ORDER BY name ASC';

        }



        // Add LIMIT for pagination

        $baseQuery .= ' LIMIT ' . $start . ', ' . $length;



        // Execute main query

        $stmt = $conn->prepare($baseQuery);

        $stmt->execute($params);

        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);



        // Format data for DataTable

        $data = [];

        foreach ($services as $index => $service) {

            $data[] = [

                '<div class="dropdown">

                    <button class="table-menu-btn mx-auto dropdownBtn" type="button" data-bs-toggle="dropdown" id="dropdownMenuButton' . $service['id'] . '" aria-expanded="false">

                        <i class="bi bi-gear"></i>

                    </button>

                    <ul class="dropdown-menu p-2 ps-3 table-menu-btn-list" aria-labelledby="dropdownMenuButton' . $service['id'] . '">

                        <input type="hidden" class="u_id" value="' . $service['id'] . '">

                        <input type="hidden" class="u_name" value="' . htmlspecialchars($service['name']) . '">

                        <input type="hidden" class="u_name_sv" value="' . htmlspecialchars($service['name_sv'] ?? '') . '">

                        <li class="mb-1"><a href="#update_section" onclick="update_s(this)" class="no-decoration f-14 w-600 text-black"><i class="bi bi-pen text-black f-14 me-2"></i>Edit</a></li>

                        <li class="mb-1"><a href="?delete=' . $service['id'] . '" class="no-decoration f-14 w-600 text-black"><i class="bi bi-trash text-black f-14 me-2"></i>Delete</a></li>

                    </ul>

                </div>',

                $start + $index + 1, // Row number

                '<a class="no-decoration text-black name_text" href="interviews.php?id=' . $service['id'] . '">' . $service['name'] . '</a>',

                $service['name_sv'] ?? '-'

            ];

        }



        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => $totalRecords,

            'recordsFiltered' => $totalRecords,

            'data' => $data

        ]);



    } catch (Exception $e) {

        error_log("Services query error: " . $e->getMessage());

        echo json_encode([

            'draw' => $draw,

            'recordsTotal' => 0,

            'recordsFiltered' => 0,

            'data' => [],

            'error' => 'Database error occurred'

        ]);

    }

    exit;

}

