<?php
include_once('../includes/functions.php');

// Check if the connection is valid
if (!$conn) {
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
    if (!$stmt) {
        echo json_encode(["status" => "error", "message" => "Failed to prepare SQL query"]);
        exit();
    }

    // Execute the query
    $res = $stmt->execute([$new_value, $id]);

    if ($res) {
        // Also update the language files
        $enFile = '../new_customer/lang/en/messages.php';
        $swgFile = '../new_customer/lang/swg/messages.php';
        
        // Get the key for this language entry
        $keyQuery = 'SELECT `keys` FROM customer_languages WHERE id = ?';
        $keyStmt = $conn->prepare($keyQuery);
        $keyStmt->execute([$id]);
        $key = $keyStmt->fetchColumn();
        
        if ($key) {
            // Update English file
            if (file_exists($enFile)) {
                $enContent = file_get_contents($enFile);
                // Escape single quotes in values
                $enEscaped = str_replace("'", "\\'", $en);
                // Replace the existing entry
                $pattern = "/'$key'\s*=>\s*'[^']*',/";
                $replacement = "'$key' => '$enEscaped',";
                $enContent = preg_replace($pattern, $replacement, $enContent);
                file_put_contents($enFile, $enContent);
            }
            
            // Update Swedish file
            if (file_exists($swgFile)) {
                $swgContent = file_get_contents($swgFile);
                // Escape single quotes in values
                $swgEscaped = str_replace("'", "\\'", $swg);
                // Replace the existing entry
                $pattern = "/'$key'\s*=>\s*'[^']*',/";
                $replacement = "'$key' => '$swgEscaped',";
                $swgContent = preg_replace($pattern, $replacement, $swgContent);
                file_put_contents($swgFile, $swgContent);
            }
        }
        echo json_encode(["status" => "success", "message" => "Language updated successfully"]);
    } else {
        // Get error info for debugging
        echo json_encode([
            "status" => "error",
            "message" => "Failed to update language",
            "error_info" => $stmt->errorInfo()
        ]);
    }
}
?>
