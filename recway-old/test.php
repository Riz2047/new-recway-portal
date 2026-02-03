<?php

//

//include_once "includes/functions.php";

//

//$statuses = [13,14,15,16,17,18,21];

//

//foreach ($statuses as $status) {

//    $query = "SELECT * FROM status_services WHERE status_id = $status LIMIT 1";

//    $stmt = $conn->prepare($query);

//    $stmt->execute();

//    $services = $stmt->fetchAll();

//

//    $servicesArr = [17,18,19,20];

//    foreach ($services as $service) {

//        foreach ($servicesArr as $s) {

//            $query = "INSERT INTO status_services (status_id, service_id, msg_col) VALUES (?,?,?)";

//            $stmt = $conn->prepare($query);

//            $stmt->execute([$service->status_id, $s, $service->msg_col]);

//        }

//    }

//}