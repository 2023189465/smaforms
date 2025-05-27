<?php
// update_evaluation.php
require_once 'config.php';
require_once 'TrainingEvaluationController.php';

// For security, this should only be accessible to admins or for testing
if (!isLoggedIn() || !hasRole('admin')) {
    echo "Unauthorized access";
    exit;
}

$evaluation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : 'completed';

if ($evaluation_id <= 0) {
    echo "Invalid evaluation ID";
    exit;
}

$controller = new TrainingEvaluationController();
$result = $controller->updateEvaluationStatus($evaluation_id, $status);

echo "<pre>";
print_r($result);
echo "</pre>";
?>