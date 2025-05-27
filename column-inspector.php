<?php
// column-inspector.php - A helper script to inspect database columns

require_once 'config.php';

// Check if user is logged in and has HR role
if (!isLoggedIn() || !hasRole('hr')) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Helper function to show table schema
function showTableSchema($conn, $tableName) {
    echo "<h2>Table: $tableName</h2>";
    
    $sql = "DESCRIBE $tableName";
    $result = $conn->query($sql);
    
    if (!$result) {
        echo "<p>Error: " . $conn->error . "</p>";
        return;
    }
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
}

// List of tables to inspect
$tables = [
    'training_documents',
    'training_applications',
    'gcr_applications',
    'users'
];

// Display page
echo "<!DOCTYPE html>";
echo "<html lang='en'>";
echo "<head>";
echo "<meta charset='UTF-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
echo "<title>Database Column Inspector</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    table { border-collapse: collapse; margin-bottom: 30px; }
    th { background-color: #f2f2f2; }
    td, th { padding: 8px; text-align: left; }
    h1 { color: #333; }
    h2 { color: #0066cc; margin-top: 30px; }
</style>";
echo "</head>";
echo "<body>";

echo "<h1>Database Schema Inspector</h1>";
echo "<p>This tool displays the schema for tables in your database.</p>";

// Show schema for each table
foreach ($tables as $table) {
    showTableSchema($conn, $table);
    
    // Additionally show a sample row to understand data format
    echo "<h3>Sample Data:</h3>";
    $sampleSql = "SELECT * FROM $table LIMIT 1";
    $sampleResult = $conn->query($sampleSql);
    
    if ($sampleResult && $sampleResult->num_rows > 0) {
        $sampleRow = $sampleResult->fetch_assoc();
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr>";
        foreach ($sampleRow as $key => $value) {
            echo "<th>$key</th>";
        }
        echo "</tr>";
        echo "<tr>";
        foreach ($sampleRow as $value) {
            echo "<td>" . (is_null($value) ? "NULL" : htmlspecialchars($value)) . "</td>";
        }
        echo "</tr>";
        echo "</table>";
    } else {
        echo "<p>No data found in table or error retrieving sample.</p>";
    }
    
    echo "<hr>";
}

echo "</body>";
echo "</html>";

// Close connection
$conn->close();
?>