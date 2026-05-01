<?php

// Strict file gateway for uploads and report-uploads
// Validates PHP sessions from admin2 and staff portals before streaming the file

// Use the same bootstrap/session init used by admin2/staff
@include_once __DIR__ . '/includes/functions.php';
// Ensure session is started (functions.php starts it too; this is a safe fallback)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// echo "<pre>";
//     print_r($_COOKIE);
//     echo "</pre>";
// Allow only authenticated users (admin2 or staff). Extend as needed.
$isAuthenticated = false;
if (isset($_SESSION['admin']) && ! empty($_SESSION['admin']) && isset($_SESSION['oderspi']) && $_SESSION['oderspi'] === 'check746342534634554754##546456^&390=$5904') {
    $isAuthenticated = true;
}
if (isset($_SESSION['staff']) && ! empty($_SESSION['staff']) && isset($_SESSION['oderspi']) && $_SESSION['oderspi'] === 'check746342534634554754##546456^&390=$5904') {
    $isAuthenticated = true;
}
if (
    ! $isAuthenticated &&
    (isset($_COOKIE['recwaycustomerportals_session']))
) {
    $cookie = $_COOKIE['recwaycustomerportals_session'];
    // $verifyUrl = 'https://orderspi.se/new_customer/public/verify-laravel-session'; // Laravel API route

    // $ch = curl_init($verifyUrl);
    // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // curl_setopt($ch, CURLOPT_HTTPGET, true);

    // // send the cookie like a browser would
    // $cookieHeader = "recwaycustomerportals_session=" . $cookie;
    // curl_setopt($ch, CURLOPT_HTTPHEADER, ["Cookie: $cookieHeader"]);

    // $response = curl_exec($ch);

    // curl_close($ch);

    // if (trim($response) === 'valid') {
    if (isset($_COOKIE['recwaycustomerportals_session'])) {
        $isAuthenticated = true;
    }
}

// Optional: you can also allow customer auth from legacy pages, if required
// if (isset($_SESSION['customer']) && !empty($_SESSION['customer'])) {
//     $isAuthenticated = true;
// }

if (! $isAuthenticated) {
    http_response_code(403);
    echo 'Forbidden';
    // echo "<pre>";
    // print_r($_COOKIE);
    // echo "</pre>";

    exit;
}

// Resolve requested path
$requestedPath = isset($_GET['path']) ? (string)$_GET['path'] : '';
if ($requestedPath === '') {
    http_response_code(400);
    echo 'Bad Request';
    exit;
}

// Only allow files inside these base directories
$allowedBases = [
    __DIR__ . DIRECTORY_SEPARATOR . 'uploads',
    __DIR__ . DIRECTORY_SEPARATOR . 'report-uploads',
    __DIR__ . DIRECTORY_SEPARATOR . 'new_customer' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads',
];

// Normalize path and prevent traversal
$requestedPath = str_replace(['\\', '..'], ['/', ''], $requestedPath);

// If the path starts with a leading slash, trim it
$requestedPath = ltrim($requestedPath, '/');

// Determine absolute file path
$absolutePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $requestedPath);
if (! $absolutePath) {
    $absolutePath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'new_customer' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . $requestedPath);
}

// Validate the file is under allowed bases and exists
function pathIsUnder(string $filePath, string $baseDir): bool
{
    if ($filePath === false) {
        return false;
    }
    $baseReal = realpath($baseDir);
    if ($baseReal === false) {
        return false;
    }
    return strncmp($filePath, $baseReal . DIRECTORY_SEPARATOR, strlen($baseReal . DIRECTORY_SEPARATOR)) === 0
        || $filePath === $baseReal; // allow serving the directory index if ever needed
}

$allowed = false;
if ($absolutePath !== false && is_file($absolutePath)) {

    foreach ($allowedBases as $base) {
        if (pathIsUnder($absolutePath, $base)) {
            $allowed = true;
            break;
        }
    }
}

if (! $allowed) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

// Send file with appropriate headers
$fileSize = filesize($absolutePath);
$fileName = basename($absolutePath);

// Basic content-type detection
$mimeType = 'application/octet-stream';
if (function_exists('mime_content_type')) {
    $detected = @mime_content_type($absolutePath);
    if ($detected) {
        $mimeType = $detected;
    }
}

// Prevent caching
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Content headers
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
// header('Content-Disposition: inline; filename="' . $fileName . '"');
$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if ($ext != 'pdf') {
    $mimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    header('Content-Disposition: attachment; filename="' . $fileName . '"');
} else {
    header('Content-Disposition: inline; filename="' . $fileName . '"');
}

// Stream file
$chunkSize = 8192;
$fh = fopen($absolutePath, 'rb');
if ($fh === false) {
    http_response_code(500);
    echo 'Unable to open file';
    exit;
}
while (! feof($fh)) {
    $buffer = fread($fh, $chunkSize);
    echo $buffer;
    if (function_exists('fastcgi_finish_request')) {
        // no-op; we'll flush manually
    }
    flush();
}
fclose($fh);
exit;
