<?php
// diagnostic_gcr.php - For troubleshooting only
require_once 'config.php';

// Check connection
$conn = connectDB();
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
echo "Database connection successful<br>";

// Get application ID from URL
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
echo "Checking for application ID: " . $id . "<br>";

// Simple direct query
$sql = "SELECT * FROM gcr_applications WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Application found!<br>";
    $application = $result->fetch_assoc();
    echo "<pre>";
    print_r($application);
    echo "</pre>";
} else {
    echo "No application found with ID: " . $id;
}

$stmt->close();
$conn->close();
?>