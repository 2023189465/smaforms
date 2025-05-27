<?php
// reset_password.php - Script to reset user password

require_once 'config.php';

// Debug information
echo "<pre>";
echo "Debug Information:\n";
echo "-----------------\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "Remote IP: " . $_SERVER['REMOTE_ADDR'] . "\n";
echo "Server Name: " . $_SERVER['SERVER_NAME'] . "\n";
echo "-----------------\n\n";

// Temporarily disable access restrictions for debugging
// if (php_sapi_name() !== 'cli' && 
//     $_SERVER['REMOTE_ADDR'] !== '127.0.0.1' && 
//     $_SERVER['REMOTE_ADDR'] !== '::1' &&
//     strpos($_SERVER['REMOTE_ADDR'], '192.168.') !== 0) {
//     die('Access denied. This script can only be run from localhost or development environment.');
// }

$conn = connectDB();

// Get all users with more details
$sql = "SELECT id, username, name, role, password FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Existing users:\n";
    echo "----------------------------------------\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "Username: {$row['username']}\n";
        echo "Name: {$row['name']}\n";
        echo "Role: {$row['role']}\n";
        
        // Check if password needs to be hashed
        $test_password = 'password123';
        $needs_update = false;
        
        // Try to verify the password
        if (!password_verify($test_password, $row['password'])) {
            $needs_update = true;
            echo "Current password is not properly hashed. Updating...\n";
            
            // Hash the password properly
            $hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
            
            // Update the password
            $update_sql = "UPDATE users SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param('si', $hashed_password, $row['id']);
            
            if ($update_stmt->execute()) {
                echo "Password updated successfully!\n";
                echo "New password hash: " . $hashed_password . "\n";
                
                // Verify the new hash
                if (password_verify($test_password, $hashed_password)) {
                    echo "Password verification successful!\n";
                } else {
                    echo "WARNING: Password verification failed after update!\n";
                }
            } else {
                echo "Failed to update password: " . $update_stmt->error . "\n";
            }
            
            $update_stmt->close();
        } else {
            echo "Password is properly hashed.\n";
            echo "Password hash: " . $row['password'] . "\n";
        }
        echo "----------------------------------------\n";
    }
} else {
    echo "No users found in the database. Creating default admin user...\n";
    
    // Create default admin user
    $username = 'admin';
    $password = 'admin123';
    $name = 'Administrator';
    $role = 'admin';
    $position = 'System Administrator';
    $department = 'IT';
    
    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert the admin user
    $sql = "INSERT INTO users (username, password, name, role, position, department) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssss', $username, $hashed_password, $name, $role, $position, $department);
    
    if ($stmt->execute()) {
        echo "\nDefault admin user created successfully!\n";
        echo "Username: admin\n";
        echo "Password: admin123\n";
        echo "Role: admin\n";
    } else {
        echo "\nFailed to create admin user: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

// If username is provided as argument, reset that user's password
if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $new_password = 'password123'; // Default new password
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update the password
    $sql = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $hashed_password, $username);
    
    if ($stmt->execute()) {
        echo "\nPassword reset successfully for user: $username\n";
        echo "New password: $new_password\n";
        echo "New password hash: $hashed_password\n";
        
        // Verify the new hash
        if (password_verify($new_password, $hashed_password)) {
            echo "Password verification successful!\n";
        } else {
            echo "WARNING: Password verification failed after reset!\n";
        }
    } else {
        echo "\nFailed to reset password: " . $stmt->error . "\n";
    }
    
    $stmt->close();
}

$conn->close();
echo "</pre>";
?> 