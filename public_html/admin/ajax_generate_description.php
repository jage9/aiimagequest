<?php
header('Content-Type: application/json');
define('IS_AJAX', true);
require_once dirname(__DIR__, 2) . '/config.php';
require_once 'auth.php';
validate_csrf();

if (!isset($_POST['tempFilename'])) {
    echo json_encode(['success' => false, 'error' => 'No filename provided.']);
    exit;
}

$filename   = basename($_POST['tempFilename']);
$imagePath  = TEMP_UPLOADS_DIR . '/' . $filename;
$scriptPath = SCRIPTS_DIR . '/generate_single_desc.py';

if (!file_exists($imagePath)) {
    echo json_encode(['success' => false, 'error' => 'Temporary image file not found.']);
    exit;
}

$command     = escapeshellcmd(PYTHON_BIN . ' ' . $scriptPath . ' ' . escapeshellarg($imagePath)) . ' 2>&1';
$description = shell_exec($command);

if ($description && !preg_match('/Traceback|Error|ImportError/i', $description)) {
    echo json_encode(['success' => true, 'description' => trim($description)]);
} else {
    error_log('generate_single_desc.py failed: ' . ($description ?? 'no output'));
    echo json_encode(['success' => false, 'error' => 'Description generation failed. Please try again.']);
}
?>
