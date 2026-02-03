<?php
include_once('../includes/functions.php');

// Check if the connection is valid
if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Database connection is not established"]));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $key = $_POST['key'] ?? '';
    $en = $_POST['en'] ?? '';
    $swg = $_POST['swg'] ?? '';

    // Check if required fields are empty
    if (empty($key) || empty($en) || empty($swg)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    // Check if key already exists
    $checkQuery = 'SELECT id FROM customer_languages WHERE `keys` = ?';
    $checkStmt = $conn->prepare($checkQuery);
    if (!$checkStmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare check query"]);
        exit();
    }
    
    $checkStmt->execute([$key]);
    if ($checkStmt->fetch()) {
        echo json_encode(["status" => "error", "message" => "Language key already exists"]);
        exit();
    }

    // Encode the values as JSON
    $value = json_encode(["en" => $en, "swg" => $swg], JSON_UNESCAPED_UNICODE);

    // Prepare the insert query
    $query = 'INSERT INTO customer_languages (`keys`, value) VALUES (?, ?)';
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query"]);
        exit();
    }

    // Execute the query
    $res = $stmt->execute([$key, $value]);
    if ($res) {
        // Also add to the language files
        $enFile = '../new_customer/lang/en/messages.php';
        $swgFile = '../new_customer/lang/swg/messages.php';
        $enJsonFile = '../new_customer/lang/en.json';
        $swgJsonFile = '../new_customer/lang/swg.json';
        
        // Add to English file
        if (file_exists($enFile)) {
            $enContent = file_get_contents($enFile);
            // Escape single quotes in values
            $enEscaped = str_replace("'", "\\'", $en);
            // Replace the closing bracket with new entry + closing bracket
            $newEnContent = str_replace("\n];", "\n    '$key' => '$enEscaped',\n];", $enContent);
            file_put_contents($enFile, $newEnContent);
        }
        
        // Add to Swedish file
        if (file_exists($swgFile)) {
            $swgContent = file_get_contents($swgFile);
            // Escape single quotes in values
            $swgEscaped = str_replace("'", "\\'", $swg);
            // Replace the closing bracket with new entry + closing bracket
            $newSwgContent = str_replace("\n];", "\n    '$key' => '$swgEscaped',\n];", $swgContent);
            file_put_contents($swgFile, $newSwgContent);
        }
        
        // Add/update English JSON using ENGLISH STRING as the key
        if (file_exists($enJsonFile)) {
            $json = file_get_contents($enJsonFile);
            $map = json_decode($json, true);
            if (!is_array($map)) { $map = []; }
            $map[$en] = $en; // key: English text, value: English text
            file_put_contents($enJsonFile, json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        
        // Add/update Swedish JSON using ENGLISH STRING as the key
        if (file_exists($swgJsonFile)) {
            $json = file_get_contents($swgJsonFile);
            $map = json_decode($json, true);
            if (!is_array($map)) { $map = []; }
            $map[$en] = $swg; // key: English text, value: Swedish text
            file_put_contents($swgJsonFile, json_encode($map, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
        echo json_encode(["status" => "success", "message" => "Language string added successfully"]);
    } else {
        // Get error info for debugging
        echo json_encode([
            "status" => "error",
            "message" => "Failed to add language string",
            "error_info" => $stmt->errorInfo()
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
}
?>
