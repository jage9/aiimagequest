<?php
header('Content-Type: application/json');
define('IS_AJAX', true);
require_once dirname(__DIR__, 2) . '/config.php';
require_once 'auth.php';
validate_csrf();

if (!isset($_FILES['imageFile']) || $_FILES['imageFile']['error'] !== UPLOAD_ERR_OK) {
    $code = isset($_FILES['imageFile']['error']) ? $_FILES['imageFile']['error'] : 'Not set';
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error. Code: ' . $code]);
    exit;
}

$tempFile = $_FILES['imageFile']['tmp_name'];

$allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($tempFile);
if (!in_array($mime, $allowedMimes, true)) {
    echo json_encode(['success' => false, 'error' => 'Invalid file type. JPEG, PNG, WebP, and GIF only.']);
    exit;
}

if ($_FILES['imageFile']['size'] > 10 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'File too large. Maximum 10MB.']);
    exit;
}

$extension   = strtolower(pathinfo($_FILES['imageFile']['name'], PATHINFO_EXTENSION));
$newFilename = 'temp_' . uniqid() . '.' . $extension;
$destination = TEMP_UPLOADS_DIR . '/' . $newFilename;

if (move_uploaded_file($tempFile, $destination)) {
    echo json_encode(['success' => true, 'filename' => $newFilename]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
}
?>
