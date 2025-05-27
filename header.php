<?php
// Get notifications count for the logged-in user if available
$notification_count = 0;
if (isLoggedIn()) {
    // Check if the NotificationManager class exists and is available
    if (file_exists('notification_manager.php')) {
        require_once 'notification_manager.php';
        
        try {
            $notification_manager = new NotificationManager();
            $notification_count = $notification_manager->getUnreadCount($_SESSION['user_id']);
        } catch (Exception $e) {
            // Silently fail and set notification count to 0
            error_log("Error getting notification count: " . $e->getMessage());
            $notification_count = 0;
        }
    } else {
        // Fallback to the global function if it exists
        if (function_exists('countUnreadNotifications')) {
            $notification_count = countUnreadNotifications($_SESSION['user_id']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>SMA Forms System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light sticky-top shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
                <img src="assets/images/sma-logo.png" alt="SMA Logo" class="me-2">
                <span class="fw-bold text-white">SMA Forms</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-lg-center">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="bi bi-grid-1x2 me-1"></i> Dashboard
                            </a>
                        </li>
                        
                        <!-- Forms Dropdown with Evaluation Form Added -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="formsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-file-earmark-text me-1"></i> Forms
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="formsDropdown">
                                <li>
                                    <a class="dropdown-item" href="training_form.php">
                                        <i class="bi bi-journal-text me-2"></i> Training Application
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="gcr_form.php">
                                        <i class="bi bi-file-earmark-text me-2"></i> GCR Application
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="training_evaluation_form.php">
                                        <i class="bi bi-clipboard-check me-2"></i> Training Evaluation
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="my_applications.php">
                                        <i class="bi bi-list-check me-2"></i> My Applications
                                    </a>
                                </li>
                            </ul>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link position-relative <?php echo basename($_SERVER['PHP_SELF']) === 'notifications.php' ? 'active' : ''; ?>" href="notifications.php">
                                <i class="bi bi-bell me-1"></i> Notifications
                                <?php if ($notification_count > 0): ?>
                                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger notification-badge">
                                        <?php echo $notification_count > 99 ? '99+' : $notification_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <?php if (hasRole('hr')): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="hrDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-gear me-1"></i> HR Tools
                                </a>
                                <ul class="dropdown-menu" aria-labelledby="hrDropdown">
                                    <li>
                                        <a class="dropdown-item" href="hr_users.php">
                                            <i class="bi bi-people me-2"></i> Manage Users
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="hr_reports.php">
                                            <i class="bi bi-file-earmark-bar-graph me-2"></i> Reports
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="all_submissions.php">
                                            <i class="bi bi-clipboard-check me-2"></i> All Submissions
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item dropdown ms-lg-3">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="avatar bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center me-2" style="width:32px;height:32px;font-size:1.2rem;">
                                    <i class="bi bi-person"></i>
                                </span>
                                <span class="fw-semibold text-white"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li>
                                    <a class="dropdown-item" href="profile.php">
                                        <i class="bi bi-person me-2"></i> My Profile
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item" href="logout.php">
                                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Let Bootstrap handle dropdowns naturally
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });
    });
    </script>