<?php
header('Content-Type: application/json');
define('IS_AJAX', true);
require_once dirname(__DIR__, 2) . '/config.php';
require_once 'auth.php';
validate_csrf();

if (empty($_POST['tempFilename'])) {
    echo json_encode(['success' => false, 'error' => 'No filename provided.']);
    exit;
}

$tempFilename = basename($_POST['tempFilename']);
$tempPath     = TEMP_UPLOADS_DIR . '/' . $tempFilename;

if (file_exists($tempPath)) {
    unlink($tempPath);
}

echo json_encode(['success' => true]);
?>
