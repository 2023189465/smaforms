<?php
// hr_users.php - User management page for HR

require_once 'config.php';

// Check if user is logged in and has HR role
if (!isLoggedIn() || !hasRole('hr')) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Handle form submissions
$error_message = '';
$success_message = '';

// Add new user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add_user') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param('s', $email);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $error_message = 'Email already exists in the system.';
        } else {
            // Insert new user
            $sql = "INSERT INTO users (name, email, password, role, department, position) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ssssss', $name, $email, $password, $role, $department, $position);
            
            if ($stmt->execute()) {
                $success_message = 'User added successfully.';
            } else {
                $error_message = 'Failed to add user. Please try again.';
            }
            $stmt->close();
        }
        $check_stmt->close();
    }
}

// Edit user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit_user') {
    $user_id = $_POST['user_id'];
    $name = $_POST['name'];
    $role = $_POST['role'];
    $department = $_POST['department'];
    $position = $_POST['position'];
    
    // Update user details
    $sql = "UPDATE users SET name = ?, role = ?, department = ?, position = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ssssi', $name, $role, $department, $position, $user_id);
    
    if ($stmt->execute()) {
        $success_message = 'User updated successfully.';
    } else {
        $error_message = 'Failed to update user. Please try again.';
    }
    $stmt->close();
}

// Reset password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'reset_password') {
    $user_id = $_POST['user_id'];
    $password = password_hash('password', PASSWORD_DEFAULT); // Default reset password
    
    $sql = "UPDATE users SET password = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('si', $password, $user_id);
    
    if ($stmt->execute()) {
        $success_message = 'Password reset successfully.';
    } else {
        $error_message = 'Failed to reset password. Please try again.';
    }
    $stmt->close();
}

// Delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'delete_user') {
    $user_id = $_POST['user_id'];
    
    // Prevent deleting users with existing applications
    $check_sql = "SELECT COUNT(*) as count FROM training_applications WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $training_count = $result->fetch_assoc()['count'];
    $check_stmt->close();
    
    $check_sql = "SELECT COUNT(*) as count FROM gcr_applications WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param('i', $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    $gcr_count = $result->fetch_assoc()['count'];
    $check_stmt->close();
    
    if ($training_count > 0 || $gcr_count > 0) {
        $error_message = 'Cannot delete user with existing applications. Consider updating their status instead.';
    } else {
        $sql = "DELETE FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        
        if ($stmt->execute()) {
            $success_message = 'User deleted successfully.';
        } else {
            $error_message = 'Failed to delete user. Please try again.';
        }
        $stmt->close();
    }
}

// Fetch all users
$users_sql = "SELECT * FROM users ORDER BY role, name";
$users_result = $conn->query($users_sql);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Define role options
$role_options = [
    'staff' => 'Staff',
    'hod' => 'Head of Department',
    'hr' => 'HR',
    'gm' => 'General Manager'
];

// Page title
$page_title = 'User Management';
?>

<?php include 'header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-people me-2"></i> User Management
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo $error_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo $success_message; ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h4 class="mb-0">Current Users</h4>
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="bi bi-person-plus me-2"></i> Add New User
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="badge <?php 
                                            switch ($user['role']) {
                                                case 'gm': echo 'bg-primary';
                                                    break;
                                                case 'hr': echo 'bg-info';
                                                    break;
                                                case 'hod': echo 'bg-warning';
                                                    break;
                                                default: echo 'bg-secondary';
                                            }
                                        ?>">
                                            <?php echo $role_options[$user['role']]; ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['department']); ?></td>
                                    <td><?php echo htmlspecialchars($user['position']); ?></td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <button class="btn btn-sm btn-outline-primary edit-user" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editUserModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['name']); ?>"
                                                data-role="<?php echo $user['role']; ?>"
                                                data-department="<?php echo htmlspecialchars($user['department']); ?>"
                                                data-position="<?php echo htmlspecialchars($user['position']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-warning reset-password" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#resetPasswordModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                <i class="bi bi-key"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger delete-user" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#deleteUserModal"
                                                data-id="<?php echo $user['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($user['name']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="form_action" value="add_user">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-person-plus me-2"></i> Add New User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="name" class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label required-field">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label required-field">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password" required>
                                <button class="btn btn-outline-secondary toggle-password" type="button">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback">Please enter a password.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="role" class="form-label required-field">Role</label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">Select Role</option>
                                <?php foreach ($role_options as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="department" name="department">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="position" name="position">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="" class="needs-validation" novalidate>
                <input type="hidden" name="form_action" value="edit_user">
                <input type="hidden" name="user_id" id="edit_user_id">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-pencil me-2"></i> Edit User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_name" class="form-label required-field">Full Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                            <div class="invalid-feedback">Please enter full name.</div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_role" class="form-label required-field">Role</label>
                            <select class="form-select" id="edit_role" name="role" required>
                                <?php foreach ($role_options as $key => $label): ?>
                                    <option value="<?php echo $key; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Please select a role.</div>
                        </div>
                        </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="edit_department" class="form-label">Department</label>
                            <input type="text" class="form-control" id="edit_department" name="department">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="edit_position" class="form-label">Position</label>
                            <input type="text" class="form-control" id="edit_position" name="position">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i> Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="form_action" value="reset_password">
                <input type="hidden" name="user_id" id="reset_user_id">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title">
                        <i class="bi bi-key me-2"></i> Reset Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Are you sure you want to reset the password for 
                        <strong id="reset_user_name"></strong>?
                    </div>
                    <p>The password will be reset to the default password: <code>password</code></p>
                    <p class="text-muted">The user will need to change this password upon their next login.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-2"></i> Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete User Modal -->
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="form_action" value="delete_user">
                <input type="hidden" name="user_id" id="delete_user_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="bi bi-trash me-2"></i> Delete User
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        Are you sure you want to delete the user: 
                        <strong id="delete_user_name"></strong>?
                    </div>
                    <p class="text-muted">
                        <small>This action cannot be undone. All associated data will be permanently removed.</small>
                    </p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                        <i class="bi bi-x-circle me-2"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-trash me-2"></i> Confirm Delete
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Form validation
    var forms = document.querySelectorAll('.needs-validation');
    
    Array.prototype.slice.call(forms).forEach(function(form) {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            form.classList.add('was-validated');
        }, false);
    });
    
    // Password toggle functionality
    const passwordToggles = document.querySelectorAll('.toggle-password');
    passwordToggles.forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const type = passwordInput.type === 'password' ? 'text' : 'password';
            passwordInput.type = type;
            
            // Toggle eye icon
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    });
    
    // Edit user modal population
    const editUserButtons = document.querySelectorAll('.edit-user');
    
    editUserButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const name = this.getAttribute('data-name');
            const role = this.getAttribute('data-role');
            const department = this.getAttribute('data-department');
            const position = this.getAttribute('data-position');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_name').value = name;
            document.getElementById('edit_role').value = role;
            document.getElementById('edit_department').value = department;
            document.getElementById('edit_position').value = position;
        });
    });
    
    // Reset password modal population
    const resetPasswordButtons = document.querySelectorAll('.reset-password');
    
    resetPasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');
            
            document.getElementById('reset_user_id').value = userId;
            document.getElementById('reset_user_name').textContent = userName;
        });
    });
    
    // Delete user modal population
    const deleteUserButtons = document.querySelectorAll('.delete-user');
    
    deleteUserButtons.forEach(button => {
        button.addEventListener('click', function() {
            const userId = this.getAttribute('data-id');
            const userName = this.getAttribute('data-name');
            
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;
        });
    });
});
</script>

<style>
/* Required field indicator */
.required-field::after {
    content: " *";
    color: var(--bs-danger);
    margin-left: 4px;
    font-weight: bold;
}

/* Improved table styling */
.table-hover tbody tr {
    transition: background-color 0.2s ease-in-out;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* Enhanced badge styles */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.6em;
    border-radius: 0.25rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Modal improvements */
.modal-header {
    padding: 1rem;
    align-items: center;
}

.modal-header .btn-close {
    margin: 0;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .btn-group {
        flex-direction: column;
        gap: 0.25rem;
    }
    
    .btn-group > .btn {
        width: 100%;
    }
}

/* Input group and form control enhancements */
.input-group .toggle-password {
    background-color: var(--bs-gray-200);
    border-color: var(--bs-gray-300);
}

.input-group .toggle-password:hover {
    background-color: var(--bs-gray-300);
}

/* Icons */
.modal-header i, 
.table-responsive .btn-group i {
    margin-right: 0.25rem;
    vertical-align: middle;
}
</style>

<?php include 'footer.php'; ?>