<?php
header('Content-Type: application/json');
define('IS_AJAX', true);
require_once 'auth.php';
validate_csrf();
require_once '../db_connect.php';

if (
    empty($_POST['tempFilename']) || empty($_POST['title']) || empty($_POST['source']) ||
    empty($_POST['categoryId']) || empty($_POST['question']) || empty($_POST['correctAnswer']) ||
    empty($_POST['description'])
) {
    echo json_encode(['success' => false, 'error' => 'All fields are required, including the description.']);
    exit;
}

$conn = get_db_connection();

$tempFilename  = basename($_POST['tempFilename']);
$title         = $_POST['title'];
$source        = $_POST['source'];
$categoryId    = (int) $_POST['categoryId'];
$question      = $_POST['question'];
$correctAnswer = $_POST['correctAnswer'];
$description   = $_POST['description'];

$tempPath = TEMP_UPLOADS_DIR . '/' . $tempFilename;

if (!file_exists($tempPath)) {
    echo json_encode(['success' => false, 'error' => 'Temporary file not found.']);
    exit;
}

$extension     = pathinfo($tempFilename, PATHINFO_EXTENSION);
$finalFilename = 'image_' . uniqid() . '.' . $extension;
$finalPath     = IMAGES_DIR . '/' . $finalFilename;

if (!rename($tempPath, $finalPath)) {
    echo json_encode(['success' => false, 'error' => 'Could not move image to final directory.']);
    exit;
}

$imageHash = hash_file('sha256', $finalPath);

$conn->begin_transaction();
try {
    $stmt = $conn->prepare(
        'INSERT INTO images (filename, title, image_hash, description, category_id, source) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->bind_param('ssssis', $finalFilename, $title, $imageHash, $description, $categoryId, $source);
    $stmt->execute();
    $imageId = $conn->insert_id;
    $stmt->close();

    $stmt = $conn->prepare('INSERT INTO questions (image_id, question, correct_answer) VALUES (?, ?, ?)');
    $stmt->bind_param('iss', $imageId, $question, $correctAnswer);
    $stmt->execute();
    $stmt->close();

    $conn->commit();
    echo json_encode(['success' => true, 'title' => $title]);
} catch (Exception $e) {
    $conn->rollback();
    if (file_exists($finalPath)) {
        unlink($finalPath);
    }
    error_log('ajax_finalize_image error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error. Please try again.']);
}

$conn->close();
?>
