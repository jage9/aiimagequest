<?php
require_once dirname(__DIR__, 2) . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['admin_authenticated'])) {
    http_response_code(401);
    exit;
}

if (!isset($_GET['file'])) {
    http_response_code(400);
    exit;
}

$filename = basename($_GET['file']);
$filePath = TEMP_UPLOADS_DIR . '/' . $filename;

if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'Image not found.';
    exit;
}

$mimeType = mime_content_type($filePath);
header('Content-Type: ' . $mimeType);
readfile($filePath);
?>
