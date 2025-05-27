<?php
// all_submissions.php - Simple categorized view of all submitted forms for HR and GM users

require_once 'config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if user has permission to view all submissions (HR or GM only)
$user_role = $_SESSION['user_role'];
if (!in_array($user_role, ['admin', 'hr', 'gm'])) {
    header('Location: dashboard.php?error=unauthorized');
    exit;
}

// Get the active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'training';

// Connect to database
$conn = connectDB();

// Function to get form status badge
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending_submission':
            return 'bg-secondary';
        case 'pending_hod':
            return 'bg-info';
        case 'pending_hr':
        case 'pending_hr1':
        case 'pending_hr2':
            return 'bg-primary';
        case 'pending_gm':
            return 'bg-warning';
        case 'approved':
        case 'completed':
        case 'submitted':
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Get training applications - Updated to match the correct schema
$training_sql = "SELECT ta.id, ta.reference_number, ta.programme_title, ta.venue, 
                ta.organiser, ta.date_time, ta.fee, ta.status, ta.created_at, 
                ta.evaluation_status, u.name as requestor_name
                FROM training_applications ta 
                LEFT JOIN users u ON ta.user_id = u.id 
                ORDER BY ta.created_at DESC";
$training_result = $conn->query($training_sql);
$training_applications = [];
if ($training_result) {
    while ($row = $training_result->fetch_assoc()) {
        $row['status_text'] = ucwords(str_replace('_', ' ', $row['status']));
        $training_applications[] = $row;
    }
}

// Get GCR applications - Updated to match the correct schema
$gcr_sql = "SELECT g.id, g.user_id, g.year, g.applicant_name, g.applicant_position, 
            g.applicant_department, g.days_requested, g.status, g.created_at,
            g.gm_decision, g.gm_days_approved
            FROM gcr_applications g 
            ORDER BY g.created_at DESC";
$gcr_result = $conn->query($gcr_sql);
$gcr_applications = [];
if ($gcr_result) {
    while ($row = $gcr_result->fetch_assoc()) {
        $row['status_text'] = ucwords(str_replace('_', ' ', $row['status']));
        $gcr_applications[] = $row;
    }
}

// Get training evaluations - Updated to use direct ID matching
$evaluation_sql = "SELECT e.*, u.name as user_name, 
                  ta.programme_title, ta.reference_number as training_reference
                  FROM training_evaluations e
                  LEFT JOIN users u ON e.user_id = u.id
                  LEFT JOIN training_applications ta ON e.id = ta.id
                  ORDER BY e.created_at DESC";
$evaluation_result = $conn->query($evaluation_sql);
$evaluations = [];
if ($evaluation_result) {
    while ($row = $evaluation_result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

$conn->close();

// Page title
$page_title = 'All Submissions';
?>

<?php include 'header.php'; ?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-list-ul me-2"></i> All Submissions
                        </h4>
                        <div>
                            <a href="dashboard.php" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab Navigation -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <ul class="nav nav-tabs card-header-tabs">
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'training' ? 'active' : ''; ?>" href="?tab=training">
                                <i class="bi bi-mortarboard me-1"></i> Training Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'gcr' ? 'active' : ''; ?>" href="?tab=gcr">
                                <i class="bi bi-file-text me-1"></i> GCR Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $active_tab === 'evaluation' ? 'active' : ''; ?>" href="?tab=evaluation">
                                <i class="bi bi-clipboard-check me-1"></i> Training Evaluations
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <?php if ($active_tab === 'training'): ?>
                        <!-- Training Applications -->
                        <?php if (empty($training_applications)): ?>
                            <div class="text-center py-5">
                                <div class="display-1 text-muted mb-3">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <h4>No Training Applications</h4>
                                <p class="text-muted">There are no training applications in the system.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Programme Title</th>
                                            <th>Staff Name</th>
                                            <th>Status</th>
                                            <th>Evaluation Status</th>
                                            <th>Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($training_applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['reference_number'] ?? 'Not Assigned'); ?></td>
                                                <td><?php echo htmlspecialchars($app['programme_title']); ?></td>
                                                <td><?php echo htmlspecialchars($app['requestor_name']); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                                        <?php echo htmlspecialchars($app['status_text']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($app['evaluation_status']): ?>
                                                    <span class="badge <?php echo $app['evaluation_status'] === 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($app['evaluation_status']); ?>
                                                    </span>
                                                    <?php else: ?>
                                                    <span class="badge bg-secondary">Not Started</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                                                <td class="text-end">
                                                    <a href="view_application.php?type=training&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                    <a href="print_training.php?id=<?php echo $app['id']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php elseif ($active_tab === 'gcr'): ?>
                        <!-- GCR Applications -->
                        <?php if (empty($gcr_applications)): ?>
                            <div class="text-center py-5">
                                <div class="display-1 text-muted mb-3">
                                    <i class="bi bi-file-text-fill"></i>
                                </div>
                                <h4>No GCR Applications</h4>
                                <p class="text-muted">There are no GCR applications in the system.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Year</th>
                                            <th>Staff Name</th>
                                            <th>Department</th>
                                            <th>Position</th>
                                            <th>Days Requested</th>
                                            <th>Days Approved</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($gcr_applications as $app): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($app['year']); ?></td>
                                                <td><?php echo htmlspecialchars($app['applicant_name']); ?></td>
                                                <td><?php echo htmlspecialchars($app['applicant_department']); ?></td>
                                                <td><?php echo htmlspecialchars($app['applicant_position']); ?></td>
                                                <td><?php echo htmlspecialchars($app['days_requested']); ?> days</td>
                                                <td>
                                                    <?php if ($app['status'] === 'approved'): ?>
                                                        <?php echo htmlspecialchars($app['gm_days_approved']); ?> days
                                                    <?php else: ?>
                                                        -
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadgeClass($app['status']); ?>">
                                                        <?php echo htmlspecialchars($app['status_text']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d M Y', strtotime($app['created_at'])); ?></td>
                                                <td class="text-end">
                                                    <a href="view_application.php?type=gcr&id=<?php echo $app['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                    <a href="print_gcr.php?id=<?php echo $app['id']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <!-- Training Evaluations -->
                        <?php if (empty($evaluations)): ?>
                            <div class="text-center py-5">
                                <div class="display-1 text-muted mb-3">
                                    <i class="bi bi-clipboard-check-fill"></i>
                                </div>
                                <h4>No Training Evaluations</h4>
                                <p class="text-muted">There are no training evaluations in the system.</p>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Reference</th>
                                            <th>Training Title</th>
                                            <th>Participant</th>
                                            <th>Position/Division</th>
                                            <th>Training Date</th>
                                            <th>Status</th>
                                            <th>Submission Date</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluations as $eval): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($eval['training_reference'] ?? 'N/A'); ?></td>
                                                <td><?php echo htmlspecialchars($eval['training_title'] ?? $eval['programme_title'] ?? 'Untitled Training'); ?></td>
                                                <td><?php echo htmlspecialchars($eval['participant_name'] ?? $eval['user_name']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($eval['participant_position'] ?? 'N/A'); ?> / 
                                                    <?php echo htmlspecialchars($eval['division'] ?? 'N/A'); ?>
                                                </td>
                                                <td><?php echo !empty($eval['training_date']) ? date('d M Y', strtotime($eval['training_date'])) : 'N/A'; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $eval['status'] === 'completed' || $eval['status'] === 'submitted' ? 'bg-success' : 'bg-warning'; ?>">
                                                        <?php echo ucfirst($eval['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo !empty($eval['submitted_date']) ? date('d M Y', strtotime($eval['submitted_date'])) : date('d M Y', strtotime($eval['created_at'])); ?></td>
                                                <td class="text-end">
                                                    <a href="view_evaluation.php?id=<?php echo $eval['id']; ?>" class="btn btn-sm btn-primary">
                                                        <i class="bi bi-eye me-1"></i> View
                                                    </a>
                                                    <?php if ($eval['status'] === 'completed' || $eval['status'] === 'submitted'): ?>
                                                    <a href="print_evaluation.php?id=<?php echo $eval['id']; ?>" target="_blank" class="btn btn-sm btn-info">
                                                        <i class="bi bi-printer me-1"></i> Print
                                                    </a>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Export Section -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="bi bi-download me-2"></i> Export Options</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-mortarboard-fill text-primary display-4 mb-3"></i>
                                    <h5>Training Applications</h5>
                                    <p class="text-muted">Export all training applications to PDF or Excel</p>
                                    <div class="d-grid gap-2">
                                        <a href="export.php?type=training&format=pdf" class="btn btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Export to PDF
                                        </a>
                                        <a href="export.php?type=training&format=excel" class="btn btn-outline-success">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-file-text-fill text-success display-4 mb-3"></i>
                                    <h5>GCR Applications</h5>
                                    <p class="text-muted">Export all GCR applications to PDF or Excel</p>
                                    <div class="d-grid gap-2">
                                        <a href="export.php?type=gcr&format=pdf" class="btn btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Export to PDF
                                        </a>
                                        <a href="export.php?type=gcr&format=excel" class="btn btn-outline-success">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card h-100">
                                <div class="card-body text-center">
                                    <i class="bi bi-clipboard-check-fill text-info display-4 mb-3"></i>
                                    <h5>Training Evaluations</h5>
                                    <p class="text-muted">Export all training evaluations to PDF or Excel</p>
                                    <div class="d-grid gap-2">
                                        <a href="export.php?type=evaluation&format=pdf" class="btn btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Export to PDF
                                        </a>
                                        <a href="export.php?type=evaluation&format=excel" class="btn btn-outline-success">
                                            <i class="bi bi-file-earmark-excel me-1"></i> Export to Excel
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom styles */
.nav-tabs .nav-link {
    color: #495057;
}
.nav-tabs .nav-link.active {
    font-weight: 600;
    color: #0d6efd;
}
.table th {
    font-weight: 600;
    color: #495057;
}
.badge {
    font-weight: 500;
    padding: 0.4em 0.6em;
}
</style>

<?php include 'footer.php'; ?>