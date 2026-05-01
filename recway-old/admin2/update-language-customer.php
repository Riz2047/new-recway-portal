<?php

include_once('../includes/functions.php');

// Check if the connection is valid
if (! $conn) {
    die(json_encode(["status" => "error", "message" => "Database connection is not established"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $en = $_POST['en'] ?? '';
    $swg = $_POST['swg'] ?? '';

    // Check if required fields are empty
    if (empty($id) || empty($en) || empty($swg)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    // Encode the new values as JSON
    $new_value = json_encode(["en" => $en, "swg" => $swg], JSON_UNESCAPED_UNICODE);

    // Prepare the query
    $query = 'UPDATE customer_languages SET value = ? WHERE id = ?';
    $stmt = $conn->prepare($query);
    if (! $stmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query"]);
        exit();
    }

    // Execute the query
    $res = $stmt->execute([$new_value, $id]);

    if ($res) {
        echo json_encode(["status" => "success", "message" => "Language updated successfully"]);
    } else {
        // Get error info for debugging
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update language",
            "error_info" => $stmt->errorInfo(),
        ]);
    }
}
