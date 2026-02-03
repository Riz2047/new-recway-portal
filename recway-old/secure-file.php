<?php
// Strict file gateway for uploads and report-uploads
// Validates PHP sessions from admin2 and staff portals before streaming the file

// Use the same bootstrap/session init used by admin2/staff
@include_once __DIR__ . '/includes/functions.php';
// Ensure session is started (functions.php starts it too; this is a safe fallback)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Allow only authenticated users (admin2, staff, or Laravel customers)
$isAuthenticated = false;
if (isset($_SESSION['admin']) && !empty($_SESSION['admin'])) {
    $isAuthenticated = true;
}
if (isset($_SESSION['staff']) && !empty($_SESSION['staff'])) {
    $isAuthenticated = true;
}

// Optional: you can also allow customer auth from legacy pages, if required
if (isset($_SESSION['customer']) && !empty($_SESSION['customer'])) {
    $isAuthenticated = true;
}

// Check for Laravel authentication
if (!$isAuthenticated) {
    // Check if Laravel session cookie exists
    $laravelSessionCookie = null;
    if (isset($_COOKIE['laravel_session'])) {
        $laravelSessionCookie = $_COOKIE['laravel_session'];
    } elseif (isset($_COOKIE['recway_session'])) {
        $laravelSessionCookie = $_COOKIE['recway_session'];
    }
    
    if ($laravelSessionCookie) {
        // Try to validate Laravel session by checking session file
        $sessionPath = __DIR__ . DIRECTORY_SEPARATOR . 'new_customer' . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'sessions';
        $sessionFile = $sessionPath . DIRECTORY_SEPARATOR . 'sess_' . $laravelSessionCookie;
        
        if (file_exists($sessionFile)) {
            // Read session data to check if user is authenticated
            $sessionData = file_get_contents($sessionFile);
            if ($sessionData && (strpos($sessionData, 'login_web_') !== false || strpos($sessionData, 'auth') !== false)) {
                $isAuthenticated = true;
            }
        }
    }
}

if (!$isAuthenticated) {
    http_response_code(403);
    echo 'Forbidden';
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

// Try to find the file in multiple locations
$absolutePath = null;

// First, try the main uploads directory
$testPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . $requestedPath);
if ($testPath !== false && is_file($testPath)) {
    $absolutePath = $testPath;
} else {
    // If not found, try Laravel public/uploads directory
    $laravelPath = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'new_customer' . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $requestedPath);
    if ($laravelPath !== false && is_file($laravelPath)) {
        $absolutePath = $laravelPath;
    }
}

// Validate the file is under allowed bases and exists
function pathIsUnder(string $filePath, string $baseDir): bool {
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

if (!$allowed) {
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
header('Content-Disposition: inline; filename="' . $fileName . '"');

// Stream file
$chunkSize = 8192;
$fh = fopen($absolutePath, 'rb');
if ($fh === false) {
    http_response_code(500);
    echo 'Unable to open file';
    exit;
}
while (!feof($fh)) {
    $buffer = fread($fh, $chunkSize);
    echo $buffer;
    if (function_exists('fastcgi_finish_request')) {
        // no-op; we'll flush manually
    }
    flush();
}
fclose($fh);
exit;


