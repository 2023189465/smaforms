<?php
// download_document.php - Handle document downloads and previews

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if document ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=no_document_id');
    exit;
}

$document_id = $_GET['id'];
$controller = new TrainingController();
$document = $controller->getDocument($document_id);

// Check if document exists and user has permission to access it
if (!$document) {
    header('Location: dashboard.php?error=document_not_found');
    exit;
}

// Check if this is a preview request
$is_preview = isset($_GET['preview']) && $_GET['preview'] === 'true';

// Get the file extension
$file_extension = strtolower(pathinfo($document['file_name'], PATHINFO_EXTENSION));

// Set appropriate content type based on file extension
switch ($file_extension) {
    case 'pdf':
        $content_type = 'application/pdf';
        break;
    case 'jpg':
    case 'jpeg':
        $content_type = 'image/jpeg';
        break;
    case 'png':
        $content_type = 'image/png';
        break;
    case 'gif':
        $content_type = 'image/gif';
        break;
    case 'doc':
        $content_type = 'application/msword';
        break;
    case 'docx':
        $content_type = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'xls':
        $content_type = 'application/vnd.ms-excel';
        break;
    case 'xlsx':
        $content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        break;
    default:
        $content_type = 'application/octet-stream';
}

// Ensure the file exists before attempting to serve it
if (!file_exists($document['file_path'])) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found on server';
    exit;
}

// Set appropriate headers
header('Content-Description: File Transfer');
header('Content-Type: ' . $content_type);

if ($is_preview) {
    // For preview, use inline disposition
    header('Content-Disposition: inline; filename="' . basename($document['file_name']) . '"');
} else {
    // For download, use attachment disposition
    header('Content-Disposition: attachment; filename="' . basename($document['file_name']) . '"');
}

header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($document['file_path']));

// Output file content
readfile($document['file_path']);
exit;
?>