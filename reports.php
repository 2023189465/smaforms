<?php
// reports.php - Generate reports for applications

require_once 'config.php';
require_once 'training_controller.php';
require_once 'gcr_controller.php';

// Check if user is logged in and has hr or admin role
if (!isLoggedIn() || (!hasRole('hr') && !hasRole('admin'))) {
    header('Location: login.php');
    exit;
}

// Determine report type (training or gcr)
$report_type = isset($_GET['type']) && $_GET['type'] === 'gcr' ? 'gcr' : 'training';

// Get date range filters if provided
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-3 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$department_filter = isset($_GET['department']) ? $_GET['department'] : '';

// Initialize controllers
$training_controller = new TrainingController();
$gcr_controller = new GCRController();

// Get applications for reports
$applications = [];
$departments = [];
$statuses = [];
$conn = connectDB();

// Get all departments for filter
$sql = "SELECT DISTINCT department FROM users ORDER BY department";
$result = $conn->query($sql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get report data based on type
if ($report_type === 'training') {
    // Get training applications based on filters
    $sql_conditions = [];
    $params = [];
    $param_types = '';
    
    // Date range filter
    $sql_conditions[] = "t.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= 'ss';
    
    // Status filter
    if (!empty($status_filter)) {
        $sql_conditions[] = "t.status = ?";
        $params[] = $status_filter;
        $param_types .= 's';
    }
    
    // Department filter
    if (!empty($department_filter)) {
        $sql_conditions[] = "u.department = ?";
        $params[] = $department_filter;
        $param_types .= 's';
    }
    
    $where_clause = '';
    if (!empty($sql_conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $sql_conditions);
    }
    
    $sql = "SELECT t.*, 
            u.name as applicant_name, 
            u.department as applicant_department,
            TRAINING_STATUSES(t.status) as status_text
            FROM training_applications t
            LEFT JOIN users u ON t.user_id = u.id
            $where_clause
            ORDER BY t.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // Handle preparation error
        error_log("SQL Error in reports.php - Training query: " . $conn->error);
        $applications = [];
    }
    
    // Get all possible statuses for filter
    $statuses = [
        'pending_submission' => 'Draft',
        'pending_hod' => 'Pending HOD Approval',
        'pending_hr' => 'Pending HR Review',
        'pending_gm' => 'Pending GM Approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ];
    
} else {
    // Get GCR applications based on filters
    $sql_conditions = [];
    $params = [];
    $param_types = '';
    
    // Date range filter
    $sql_conditions[] = "g.created_at BETWEEN ? AND DATE_ADD(?, INTERVAL 1 DAY)";
    $params[] = $start_date;
    $params[] = $end_date;
    $param_types .= 'ss';
    
    // Status filter
    if (!empty($status_filter)) {
        $sql_conditions[] = "g.status = ?";
        $params[] = $status_filter;
        $param_types .= 's';
    }
    
    // Department filter
    if (!empty($department_filter)) {
        $sql_conditions[] = "u.department = ?";
        $params[] = $department_filter;
        $param_types .= 's';
    }
    
    $where_clause = '';
    if (!empty($sql_conditions)) {
        $where_clause = "WHERE " . implode(" AND ", $sql_conditions);
    }
    
    $sql = "SELECT g.*, 
            u.name as applicant_name, 
            u.department as applicant_department,
            GCR_STATUSES(g.status) as status_text
            FROM gcr_applications g
            LEFT JOIN users u ON g.user_id = u.id
            $where_clause
            ORDER BY g.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        
        $stmt->execute();
        $result = $stmt->get_result();
        $applications = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        // Handle preparation error
        error_log("SQL Error in reports.php - GCR query: " . $conn->error);
        $applications = [];
    }
    
    // Get all possible statuses for filter
    $statuses = [
        'pending_submission' => 'Draft',
        'pending_hr1' => 'Pending HR Verification',
        'pending_gm' => 'Pending GM Approval',
        'pending_hr2' => 'Pending HR Final Recording',
        'approved' => 'Approved',
        'rejected' => 'Rejected'
    ];
}

// Calculate summary statistics
$total_applications = count($applications);
$approved_count = 0;
$rejected_count = 0;
$pending_count = 0;

foreach ($applications as $app) {
    if ($app['status'] === 'approved') {
        $approved_count++;
    } elseif ($app['status'] === 'rejected') {
        $rejected_count++;
    } else {
        $pending_count++;
    }
}

$approval_rate = $total_applications > 0 ? round(($approved_count / $total_applications) * 100, 1) : 0;

// Group applications by department for chart
$dept_counts = [];
foreach ($applications as $app) {
    $dept = $app['applicant_department'] ?? 'Unknown';
    if (!isset($dept_counts[$dept])) {
        $dept_counts[$dept] = 0;
    }
    $dept_counts[$dept]++;
}

// Group applications by status for chart
$status_counts = [];
foreach ($applications as $app) {
    $status = $app['status'] ?? 'unknown';
    if (!isset($status_counts[$status])) {
        $status_counts[$status] = 0;
    }
    $status_counts[$status]++;
}

// Group applications by month for trend chart
$month_counts = [];
foreach ($applications as $app) {
    $month = date('M Y', strtotime($app['created_at']));
    if (!isset($month_counts[$month])) {
        $month_counts[$month] = 0;
    }
    $month_counts[$month]++;
}

// Sort month counts chronologically
ksort($month_counts);

$conn->close();

// Function to get status badge class
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
            return 'bg-success';
        case 'rejected':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Page title
$page_title = ($report_type === 'training' ? 'Training' : 'GCR') . ' Applications Report';
?>

<?php include 'header.php'; ?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <?php if ($report_type === 'training'): ?>
                                <i class="bi bi-graph-up me-2 text-primary"></i> Training Applications Report
                            <?php else: ?>
                                <i class="bi bi-graph-up me-2 text-success"></i> GCR Applications Report
                            <?php endif; ?>
                        </h4>
                        <div>
                            <a href="reports.php?type=<?php echo $report_type === 'training' ? 'gcr' : 'training'; ?>" class="btn btn-outline-secondary">
                                Switch to <?php echo $report_type === 'training' ? 'GCR' : 'Training'; ?> Report
                            </a>
                            <button type="button" class="btn btn-primary ms-2" onclick="window.print();">
                                <i class="bi bi-printer me-2"></i> Print Report
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filter Form -->
    <div class="row mb-4 print-hide">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-funnel me-2"></i> Filter Report
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3">
                        <input type="hidden" name="type" value="<?php echo $report_type; ?>">
                        
                        <div class="col-md-3">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $start_date; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $end_date; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <?php foreach ($statuses as $status_key => $status_name): ?>
                                    <option value="<?php echo $status_key; ?>" <?php echo $status_filter === $status_key ? 'selected' : ''; ?>>
                                        <?php echo $status_name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="department" class="form-label">Department</label>
                            <select class="form-select" id="department" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?php echo $department; ?>" <?php echo $department_filter === $department ? 'selected' : ''; ?>>
                                        <?php echo $department; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                            <a href="reports.php?type=<?php echo $report_type; ?>" class="btn btn-outline-secondary ms-2">Reset Filters</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card bg-primary text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Total Applications</h6>
                            <h2 class="mb-0"><?php echo $total_applications; ?></h2>
                        </div>
                        <div>
                            <i class="bi bi-file-earmark-text display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Approved</h6>
                            <h2 class="mb-0"><?php echo $approved_count; ?></h2>
                        </div>
                        <div>
                            <i class="bi bi-check-circle display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-danger text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-white-50">Rejected</h6>
                            <h2 class="mb-0"><?php echo $rejected_count; ?></h2>
                        </div>
                        <div>
                            <i class="bi bi-x-circle display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-3">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-dark-50">Approval Rate</h6>
                            <h2 class="mb-0"><?php echo $approval_rate; ?>%</h2>
                        </div>
                        <div>
                            <i class="bi bi-bar-chart display-4 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Applications by Department</h5>
                </div>
                <div class="card-body">
                    <canvas id="departmentChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6 mb-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Applications by Status</h5>
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">Monthly Trend</h5>
                </div>
                <div class="card-body">
                    <canvas id="trendChart" height="100"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Application List Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">
                        <i class="bi bi-list-ul me-2"></i> Application List
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (count($applications) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Reference</th>
                                        <?php if ($report_type === 'training'): ?>
                                            <th>Title</th>
                                            <th>Organiser</th>
                                        <?php else: ?>
                                            <th>Year</th>
                                            <th>Days</th>
                                        <?php endif; ?>
                                        <th>Applicant</th>
                                        <th>Department</th>
                                        <th>Submission Date</th>
                                        <th>Status</th>
                                        <th class="print-hide">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $application): ?>
                                        <tr>
                                            <td><?php echo $application['id']; ?></td>
                                            <td><?php echo $application['reference_number'] ?? '<span class="text-muted">Pending</span>'; ?></td>
                                            <?php if ($report_type === 'training'): ?>
                                                <td><?php echo htmlspecialchars($application['programme_title']); ?></td>
                                                <td><?php echo htmlspecialchars($application['organiser']); ?></td>
                                            <?php else: ?>
                                                <td><?php echo $application['year']; ?></td>
                                                <td>
                                                    <?php echo $application['days_requested']; ?>
                                                    <?php if ($application['status'] === 'approved' && isset($application['gm_days_approved'])): ?>
                                                        <span class="text-success">
                                                            (<?php echo $application['gm_days_approved']; ?> approved)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                            <?php endif; ?>
                                            <td><?php echo htmlspecialchars($application['applicant_name']); ?></td>
                                            <td><?php echo htmlspecialchars($application['applicant_department']); ?></td>
                                            <td><?php echo date('d M Y', strtotime($application['created_at'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadgeClass($application['status']); ?>">
                                                    <?php echo isset($application['status_text']) ? $application['status_text'] : ucwords(str_replace('_', ' ', $application['status'])); ?>
                                                </span>
                                            </td>
                                            <td class="print-hide">
                                                <a href="view_application.php?type=<?php echo $report_type; ?>&id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="bi bi-eye me-1"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i> No applications found matching the criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Department Chart
    var deptCtx = document.getElementById('departmentChart').getContext('2d');
    var deptData = <?php echo json_encode(array_values($dept_counts)); ?>;
    var deptLabels = <?php echo json_encode(array_keys($dept_counts)); ?>;
    
    var deptColors = [];
    for (var i = 0; i < deptLabels.length; i++) {
        deptColors.push(getRandomColor());
    }
    
    new Chart(deptCtx, {
        type: 'pie',
        data: {
            labels: deptLabels,
            datasets: [{
                data: deptData,
                backgroundColor: deptColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Status Chart
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusData = <?php echo json_encode(array_values($status_counts)); ?>;
    var statusLabels = [];
    var statusColors = [];
    
    <?php foreach ($status_counts as $status => $count): ?>
        statusLabels.push('<?php echo isset($statuses[$status]) ? $statuses[$status] : ucwords(str_replace('_', ' ', $status)); ?>');
        statusColors.push(getStatusColor('<?php echo $status; ?>'));
    <?php endforeach; ?>
    
    new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: statusColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // Trend Chart
    var trendCtx = document.getElementById('trendChart').getContext('2d');
    var trendData = <?php echo json_encode(array_values($month_counts)); ?>;
    var trendLabels = <?php echo json_encode(array_keys($month_counts)); ?>;
    
    new Chart(trendCtx, {
        type: 'bar',
        data: {
            labels: trendLabels,
            datasets: [{
                label: 'Applications',
                data: trendData,
                backgroundColor: '<?php echo $report_type === 'training' ? 'rgba(13, 110, 253, 0.7)' : 'rgba(25, 135, 84, 0.7)'; ?>',
                borderColor: '<?php echo $report_type === 'training' ? 'rgba(13, 110, 253, 1)' : 'rgba(25, 135, 84, 1)'; ?>',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Helper function to get random color
    function getRandomColor() {
        var letters = '0123456789ABCDEF';
        var color = '#';
        for (var i = 0; i < 6; i++) {
            color += letters[Math.floor(Math.random() * 16)];
        }
        return color;
    }
    
    // Helper function to get status color
    function getStatusColor(status) {
        switch(status) {
            case 'pending_submission':
                return '#6c757d'; // secondary
            case 'pending_hod':
                return '#0dcaf0'; // info
            case 'pending_hr':
            case 'pending_hr1':
            case 'pending_hr2':
                return '#0d6efd'; // primary
            case 'pending_gm':
                return '#ffc107'; // warning
            case 'approved':
                return '#198754'; // success
            case 'rejected':
                return '#dc3545'; // danger
            default:
                return '#6c757d'; // secondary
        }
    }
});
</script>

<style>
@media print {
    /* Hide unnecessary elements */
    .print-hide, 
    .navbar, 
    .footer, 
    button {
        display: none !important;
    }

    /* Ensure full width for better readability */
    body {
        font-size: 12pt;
        color: #000;
        background: #fff;
        margin: 0;
        padding: 10px;
    }

    /* Make charts and tables more print-friendly */
    .chart-container {
        width: 100% !important;
        height: auto !important;
        page-break-inside: avoid;
    }

    /* Improve table styling */
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }

    th, td {
        border: 1px solid #000;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #f0f0f0;
    }

    /* Ensure headers don't get cut off */
    thead {
        display: table-header-group;
    }

    /* Page break handling */
    .page-break {
        page-break-before: always;
    }

    /* Ensure charts are printed properly */
    .chart-container img {
        max-width: 100%;
        height: auto;
    }
}

</style>

<?php include 'footer.php'; ?>