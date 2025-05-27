<?php
// hr_reports.php - Reports page for HR

// Start session before any output
session_start();

require_once 'config.php';

// Check if user is logged in and has HR role
if (!isLoggedIn() || !hasRole('hr')) {
    header('Location: login.php');
    exit;
}

// Initialize database connection
$conn = connectDB();

// Initialize variables
$error_message = '';
$success_message = '';
$report_data = [];
$report_type = '';
$date_from = '';
$date_to = '';
$department = '';
$show_report = false;

// Process report generation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_report'])) {
    $report_type = $_POST['report_type'];
    $date_from = $_POST['date_from'];
    $date_to = $_POST['date_to'];
    $department = isset($_POST['department']) ? $_POST['department'] : '';
    
    // Validate inputs
    if (empty($report_type) || empty($date_from) || empty($date_to)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        // Format dates for SQL query
        $formatted_date_from = date('Y-m-d', strtotime($date_from));
        $formatted_date_to = date('Y-m-d', strtotime($date_to));
        
        try {
            // Generate report based on type
            switch ($report_type) {
                case 'training_summary':
                    // Modified to work with the actual structure
                    // Since there's no direct link to training_documents, we'll work with training_applications directly
                    if (!empty($department)) {
                        $sql = "SELECT 
                                    ta.programme_title AS training_name,
                                    COUNT(ta.id) AS total_applications,
                                    SUM(CASE WHEN ta.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                                    SUM(CASE WHEN ta.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                                    SUM(CASE WHEN ta.status IN ('pending_submission', 'pending_hod', 'pending_hr', 'pending_gm') THEN 1 ELSE 0 END) AS pending
                                FROM 
                                    training_applications ta
                                JOIN
                                    users u ON ta.user_id = u.id
                                WHERE 
                                    ta.created_at BETWEEN ? AND ?
                                    AND u.department = ?
                                GROUP BY 
                                    ta.programme_title
                                ORDER BY 
                                    COUNT(ta.id) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        if (!$stmt->bind_param('sss', $formatted_date_from, $formatted_date_to, $department)) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                    } else {
                        $sql = "SELECT 
                                    ta.programme_title AS training_name,
                                    COUNT(ta.id) AS total_applications,
                                    SUM(CASE WHEN ta.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                                    SUM(CASE WHEN ta.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                                    SUM(CASE WHEN ta.status IN ('pending_submission', 'pending_hod', 'pending_hr', 'pending_gm') THEN 1 ELSE 0 END) AS pending
                                FROM 
                                    training_applications ta
                                WHERE 
                                    ta.created_at BETWEEN ? AND ?
                                GROUP BY 
                                    ta.programme_title
                                ORDER BY 
                                    COUNT(ta.id) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        if (!$stmt->bind_param('ss', $formatted_date_from, $formatted_date_to)) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                    }
                    break;
                    
                case 'gcr_status':
                    // This section is already correct, using applicant_position from gcr_applications
                    if (!empty($department)) {
                        $sql = "SELECT 
                                    applicant_position AS gcr_type,
                                    COUNT(id) AS total_applications,
                                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                                    SUM(CASE WHEN status IN ('pending_submission','pending_hr1','pending_gm','pending_hr2') THEN 1 ELSE 0 END) AS pending
                                FROM 
                                    gcr_applications
                                WHERE 
                                    created_at BETWEEN ? AND ?
                                    AND applicant_department = ?
                                GROUP BY 
                                    applicant_position
                                ORDER BY 
                                    COUNT(id) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        if (!$stmt->bind_param('sss', $formatted_date_from, $formatted_date_to, $department)) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                    } else {
                        $sql = "SELECT 
                                    applicant_position AS gcr_type,
                                    COUNT(id) AS total_applications,
                                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) AS approved,
                                    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                                    SUM(CASE WHEN status IN ('pending_submission','pending_hr1','pending_gm','pending_hr2') THEN 1 ELSE 0 END) AS pending
                                FROM 
                                    gcr_applications
                                WHERE 
                                    created_at BETWEEN ? AND ?
                                GROUP BY 
                                    applicant_position
                                ORDER BY 
                                    COUNT(id) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        if (!$stmt->bind_param('ss', $formatted_date_from, $formatted_date_to)) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                    }
                    break;
                    
                case 'department_training':
                    // Modified to use unit_division instead of department
                    $sql = "SELECT 
                                ta.unit_division AS department,
                                COUNT(ta.id) AS total_applications,
                                SUM(CASE WHEN ta.status = 'approved' THEN 1 ELSE 0 END) AS approved,
                                SUM(CASE WHEN ta.status = 'rejected' THEN 1 ELSE 0 END) AS rejected,
                                SUM(CASE WHEN ta.status IN ('pending_submission', 'pending_hod', 'pending_hr', 'pending_gm') THEN 1 ELSE 0 END) AS pending
                            FROM 
                                training_applications ta
                            WHERE 
                                ta.created_at BETWEEN ? AND ?
                            GROUP BY 
                                ta.unit_division
                            ORDER BY 
                                COUNT(ta.id) DESC";
                                
                    if (!$stmt = $conn->prepare($sql)) {
                        throw new Exception("Prepare failed: " . $conn->error);
                    }
                    
                    if (!$stmt->bind_param('ss', $formatted_date_from, $formatted_date_to)) {
                        throw new Exception("Binding parameters failed: " . $stmt->error);
                    }
                    break;
                    
                case 'user_activity':
                    if (!empty($department)) {
                        $sql = "SELECT 
                                    u.name,
                                    u.department,
                                    u.position,
                                    (SELECT COUNT(*) FROM training_applications ta WHERE ta.user_id = u.id) AS training_applications,
                                    (SELECT COUNT(*) FROM gcr_applications g WHERE g.user_id = u.id) AS gcr_applications
                                FROM 
                                    users u
                                WHERE 
                                    u.department = ?
                                ORDER BY 
                                    (training_applications + gcr_applications) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                        
                        if (!$stmt->bind_param('s', $department)) {
                            throw new Exception("Binding parameters failed: " . $stmt->error);
                        }
                    } else {
                        $sql = "SELECT 
                                    u.name,
                                    u.department,
                                    u.position,
                                    (SELECT COUNT(*) FROM training_applications ta WHERE ta.user_id = u.id) AS training_applications,
                                    (SELECT COUNT(*) FROM gcr_applications g WHERE g.user_id = u.id) AS gcr_applications
                                FROM 
                                    users u
                                ORDER BY 
                                    (training_applications + gcr_applications) DESC";
                                    
                        if (!$stmt = $conn->prepare($sql)) {
                            throw new Exception("Prepare failed: " . $conn->error);
                        }
                    }
                    break;
                    
                default:
                    throw new Exception('Invalid report type selected.');
                    break;
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $result = $stmt->get_result();
            $report_data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            if (count($report_data) > 0) {
                $show_report = true;
                $success_message = 'Report generated successfully.';
            } else {
                $error_message = 'No data found for the selected criteria.';
            }
        } catch (Exception $e) {
            $error_message = 'Error generating report: ' . $e->getMessage();
        }
    }
}

// Get list of departments for filter - with error handling
$departments = [];
try {
    $dept_sql = "SELECT DISTINCT department FROM users WHERE department IS NOT NULL AND department != '' ORDER BY department";
    $dept_result = $conn->query($dept_sql);
    
    if (!$dept_result) {
        throw new Exception("Error loading departments: " . $conn->error);
    }
    
    while ($row = $dept_result->fetch_assoc()) {
        $departments[] = $row['department'];
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
}

// Page title
$page_title = 'HR Reports';

// Include header
include 'header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-file-earmark-bar-graph me-2"></i> HR Reports
                    </h3>
                </div>
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="bi bi-exclamation-triangle-fill me-2"></i> <?php echo htmlspecialchars($error_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="bi bi-check-circle-fill me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card border">
                                <div class="card-header bg-light">
                                    <h5 class="mb-0">Generate Report</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="needs-validation" novalidate>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="report_type" class="form-label required-field">Report Type</label>
                                                <select class="form-select" id="report_type" name="report_type" required>
                                                    <option value="" <?php echo empty($report_type) ? 'selected' : ''; ?>>Select Report Type</option>
                                                    <option value="training_summary" <?php echo $report_type === 'training_summary' ? 'selected' : ''; ?>>Training Summary</option>
                                                    <option value="gcr_status" <?php echo $report_type === 'gcr_status' ? 'selected' : ''; ?>>GCR Applications Status</option>
                                                    <option value="department_training" <?php echo $report_type === 'department_training' ? 'selected' : ''; ?>>Department Training Analysis</option>
                                                    <option value="user_activity" <?php echo $report_type === 'user_activity' ? 'selected' : ''; ?>>User Activity Report</option>
                                                </select>
                                                <div class="invalid-feedback">Please select a report type.</div>
                                            </div>
                                            <div class="col-md-4 mb-3" id="department_filter_container">
                                                <label for="department" class="form-label">Department Filter</label>
                                                <select class="form-select" id="department" name="department">
                                                    <option value="">All Departments</option>
                                                    <?php foreach ($departments as $dept): ?>
                                                        <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo $department === $dept ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($dept); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-4 mb-3">
                                                <label for="date_from" class="form-label required-field">Date From</label>
                                                <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo $date_from; ?>" required>
                                                <div class="invalid-feedback">Please select a start date.</div>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label for="date_to" class="form-label required-field">Date To</label>
                                                <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo $date_to; ?>" required>
                                                <div class="invalid-feedback">Please select an end date.</div>
                                            </div>
                                            <div class="col-md-4 d-flex align-items-end">
                                                <button type="submit" name="generate_report" class="btn btn-primary w-100">
                                                    <i class="bi bi-file-earmark-bar-graph me-2"></i> Generate Report
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($show_report): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header d-flex justify-content-between align-items-center bg-light">
                                        <h5 class="mb-0">
                                            <i class="bi bi-table me-2"></i>
                                            <?php
                                                switch ($report_type) {
                                                    case 'training_summary':
                                                        echo 'Training Summary Report';
                                                        break;
                                                    case 'gcr_status':
                                                        echo 'GCR Applications Status Report';
                                                        break;
                                                    case 'department_training':
                                                        echo 'Department Training Analysis';
                                                        break;
                                                    case 'user_activity':
                                                        echo 'User Activity Report';
                                                        break;
                                                }
                                            ?>
                                        </h5>
                                        <button class="btn btn-sm btn-outline-secondary" id="export_report">
                                            <i class="bi bi-download me-1"></i> Export as Excel
                                        </button>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <?php if ($report_type === 'training_summary'): ?>
                                                <table class="table table-hover table-bordered" id="report_table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Training Name</th>
                                                            <th class="text-center">Total Applications</th>
                                                            <th class="text-center">Approved</th>
                                                            <th class="text-center">Rejected</th>
                                                            <th class="text-center">Pending</th>
                                                            <th class="text-center">Approval Rate</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($report_data as $row): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['training_name']); ?></td>
                                                                <td class="text-center"><?php echo $row['total_applications']; ?></td>
                                                                <td class="text-center text-success"><?php echo $row['approved']; ?></td>
                                                                <td class="text-center text-danger"><?php echo $row['rejected']; ?></td>
                                                                <td class="text-center text-warning"><?php echo $row['pending']; ?></td>
                                                                <td class="text-center">
                                                                    <?php 
                                                                        $approval_rate = ($row['total_applications'] > 0) ? 
                                                                            round(($row['approved'] / ($row['approved'] + $row['rejected'] + $row['pending'])) * 100, 1) : 0;
                                                                        echo $approval_rate . '%';
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php elseif ($report_type === 'gcr_status'): ?>
                                                <!-- GCR Status table here - similar to training_summary -->
                                                <table class="table table-hover table-bordered" id="report_table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>GCR Type</th>
                                                            <th class="text-center">Total Applications</th>
                                                            <th class="text-center">Approved</th>
                                                            <th class="text-center">Rejected</th>
                                                            <th class="text-center">Pending</th>
                                                            <th class="text-center">Approval Rate</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($report_data as $row): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['gcr_type']); ?></td>
                                                                <td class="text-center"><?php echo $row['total_applications']; ?></td>
                                                                <td class="text-center text-success"><?php echo $row['approved']; ?></td>
                                                                <td class="text-center text-danger"><?php echo $row['rejected']; ?></td>
                                                                <td class="text-center text-warning"><?php echo $row['pending']; ?></td>
                                                                <td class="text-center">
                                                                    <?php 
                                                                        $approval_rate = ($row['total_applications'] > 0) ? 
                                                                            round(($row['approved'] / ($row['approved'] + $row['rejected'] + $row['pending'])) * 100, 1) : 0;
                                                                        echo $approval_rate . '%';
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php elseif ($report_type === 'department_training'): ?>
                                                <!-- Department Training table here -->
                                                <table class="table table-hover table-bordered" id="report_table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>Department</th>
                                                            <th class="text-center">Total Applications</th>
                                                            <th class="text-center">Approved</th>
                                                            <th class="text-center">Rejected</th>
                                                            <th class="text-center">Pending</th>
                                                            <th class="text-center">Approval Rate</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($report_data as $row): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                                <td class="text-center"><?php echo $row['total_applications']; ?></td>
                                                                <td class="text-center text-success"><?php echo $row['approved']; ?></td>
                                                                <td class="text-center text-danger"><?php echo $row['rejected']; ?></td>
                                                                <td class="text-center text-warning"><?php echo $row['pending']; ?></td>
                                                                <td class="text-center">
                                                                    <?php 
                                                                        $approval_rate = ($row['total_applications'] > 0) ? 
                                                                            round(($row['approved'] / ($row['approved'] + $row['rejected'] + $row['pending'])) * 100, 1) : 0;
                                                                        echo $approval_rate . '%';
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php elseif ($report_type === 'user_activity'): ?>
                                                <!-- User Activity table here -->
                                                <table class="table table-hover table-bordered" id="report_table">
                                                    <thead class="table-light">
                                                        <tr>
                                                            <th>User Name</th>
                                                            <th>Department</th>
                                                            <th>Position</th>
                                                            <th class="text-center">Training Applications</th>
                                                            <th class="text-center">GCR Applications</th>
                                                            <th class="text-center">Total Activity</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($report_data as $row): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['department']); ?></td>
                                                                <td><?php echo htmlspecialchars($row['position']); ?></td>
                                                                <td class="text-center"><?php echo $row['training_applications']; ?></td>
                                                                <td class="text-center"><?php echo $row['gcr_applications']; ?></td>
                                                                <td class="text-center fw-bold">
                                                                    <?php echo $row['training_applications'] + $row['gcr_applications']; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Charts section - simplified to avoid JS errors -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="card border">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0">
                                            <i class="bi bi-bar-chart-line me-2"></i> Data Visualization
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Summary Chart</h6>
                                                        <canvas id="summaryChart" width="400" height="300"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-6 mb-4">
                                                <div class="card h-100">
                                                    <div class="card-body">
                                                        <h6 class="card-title">Status Distribution</h6>
                                                        <canvas id="distributionChart" width="400" height="300"></canvas>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.0/dist/xlsx.full.min.js"></script>

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
    
    // Show/hide department filter based on report type
    const reportTypeSelect = document.getElementById('report_type');
    const departmentFilterContainer = document.getElementById('department_filter_container');
    
    if (reportTypeSelect && departmentFilterContainer) {
        reportTypeSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue === 'department_training') {
                departmentFilterContainer.style.display = 'none';
            } else {
                departmentFilterContainer.style.display = 'block';
            }
        });
        
        // Initial check
        if (reportTypeSelect.value === 'department_training') {
            departmentFilterContainer.style.display = 'none';
        }
    }
    
    // Export to Excel functionality
    const exportButton = document.getElementById('export_report');
    if (exportButton) {
        exportButton.addEventListener('click', function() {
            const table = document.getElementById('report_table');
            if (table) {
                const wb = XLSX.utils.table_to_book(table, {sheet: "Report"});
                
                // Generate filename based on report type and date
                const reportTypeText = {
                    'training_summary': 'Training_Summary',
                    'gcr_status': 'GCR_Status',
                    'department_training': 'Department_Training',
                    'user_activity': 'User_Activity'
                };
                
                // Get the current report type from the PHP variable or default to 'Report'
                let reportType = '<?php echo !empty($report_type) ? $report_type : "Report"; ?>';
                const dateStr = new Date().toISOString().split('T')[0];
                const filename = `${reportTypeText[reportType] || 'Report'}_${dateStr}.xlsx`;
                
                XLSX.writeFile(wb, filename);
            }
        });
    }
    
    <?php if (isset($show_report) && $show_report): ?>
    
    // Initialize charts with simple implementation to avoid errors
    try {
        // Get elements
        const summaryCtx = document.getElementById('summaryChart').getContext('2d');
        const distributionCtx = document.getElementById('distributionChart').getContext('2d');
        
        <?php if (isset($report_type) && $report_type === 'training_summary'): ?>
            // Summary Chart for Training Summary
            const summaryLabels = [<?php echo implode(', ', array_map(function($row) { return '"' . addslashes($row['training_name']) . '"'; }, $report_data)); ?>];
            const summaryData = [<?php echo implode(', ', array_map(function($row) { return $row['total_applications']; }, $report_data)); ?>];
            
            new Chart(summaryCtx, {
                type: 'bar',
                data: {
                    labels: summaryLabels,
                    datasets: [{
                        label: 'Total Applications',
                        data: summaryData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Distribution Chart for Training Summary
            const totalApproved = <?php echo array_sum(array_column($report_data, 'approved')); ?>;
            const totalRejected = <?php echo array_sum(array_column($report_data, 'rejected')); ?>;
            const totalPending = <?php echo array_sum(array_column($report_data, 'pending')); ?>;
            
            new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Rejected', 'Pending'],
                    datasets: [{
                        data: [totalApproved, totalRejected, totalPending],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php elseif (isset($report_type) && $report_type === 'gcr_status'): ?>
            // Summary Chart for GCR Status
            const summaryLabels = [<?php echo implode(', ', array_map(function($row) { return '"' . addslashes($row['gcr_type']) . '"'; }, $report_data)); ?>];
            const summaryData = [<?php echo implode(', ', array_map(function($row) { return $row['total_applications']; }, $report_data)); ?>];
            
            new Chart(summaryCtx, {
                type: 'bar',
                data: {
                    labels: summaryLabels,
                    datasets: [{
                        label: 'Total Applications',
                        data: summaryData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Distribution Chart for GCR Status
            const totalApproved = <?php echo array_sum(array_column($report_data, 'approved')); ?>;
            const totalRejected = <?php echo array_sum(array_column($report_data, 'rejected')); ?>;
            const totalPending = <?php echo array_sum(array_column($report_data, 'pending')); ?>;
            
            new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Rejected', 'Pending'],
                    datasets: [{
                        data: [totalApproved, totalRejected, totalPending],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php elseif (isset($report_type) && $report_type === 'department_training'): ?>
            // Summary Chart for Department Training
            const summaryLabels = [<?php echo implode(', ', array_map(function($row) { return '"' . addslashes($row['department']) . '"'; }, $report_data)); ?>];
            const summaryData = [<?php echo implode(', ', array_map(function($row) { return $row['total_applications']; }, $report_data)); ?>];
            
            new Chart(summaryCtx, {
                type: 'bar',
                data: {
                    labels: summaryLabels,
                    datasets: [{
                        label: 'Total Applications',
                        data: summaryData,
                        backgroundColor: 'rgba(54, 162, 235, 0.5)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Distribution Chart for Department Training
            const totalApproved = <?php echo array_sum(array_column($report_data, 'approved')); ?>;
            const totalRejected = <?php echo array_sum(array_column($report_data, 'rejected')); ?>;
            const totalPending = <?php echo array_sum(array_column($report_data, 'pending')); ?>;
            
            new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: ['Approved', 'Rejected', 'Pending'],
                    datasets: [{
                        data: [totalApproved, totalRejected, totalPending],
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(255, 206, 86, 0.7)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 99, 132, 1)',
                            'rgba(255, 206, 86, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php elseif (isset($report_type) && $report_type === 'user_activity'): ?>
            // Summary Chart for User Activity - top 10 users
            const userLabels = [<?php echo implode(', ', array_map(function($row) { return '"' . addslashes($row['name']) . '"'; }, array_slice($report_data, 0, 10))); ?>];
            const trainingData = [<?php echo implode(', ', array_map(function($row) { return $row['training_applications']; }, array_slice($report_data, 0, 10))); ?>];
            const gcrData = [<?php echo implode(', ', array_map(function($row) { return $row['gcr_applications']; }, array_slice($report_data, 0, 10))); ?>];
            
            new Chart(summaryCtx, {
                type: 'bar',
                data: {
                    labels: userLabels,
                    datasets: [
                        {
                            label: 'Training Applications',
                            data: trainingData,
                            backgroundColor: 'rgba(54, 162, 235, 0.5)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1
                        },
                        {
                            label: 'GCR Applications',
                            data: gcrData,
                            backgroundColor: 'rgba(255, 99, 132, 0.5)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
            
            // Distribution Chart for User Activity
            const totalTraining = <?php echo array_sum(array_column($report_data, 'training_applications')); ?>;
            const totalGCR = <?php echo array_sum(array_column($report_data, 'gcr_applications')); ?>;
            
            new Chart(distributionCtx, {
                type: 'pie',
                data: {
                    labels: ['Training Applications', 'GCR Applications'],
                    datasets: [{
                        data: [totalTraining, totalGCR],
                        backgroundColor: [
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 99, 132, 0.7)'
                        ],
                        borderColor: [
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        <?php endif; ?>
    } catch (error) {
        console.error('Error initializing charts:', error);
    }
    <?php endif; ?>
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

/* Enhanced card styling */
.card {
    transition: box-shadow 0.3s ease-in-out;
}

.card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

/* Table improvements */
.table-hover tbody tr {
    transition: background-color 0.2s ease-in-out;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 123, 255, 0.05);
    transform: translateY(-2px);
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

/* Badge and button styling */
.badge {
    font-size: 0.75em;
    padding: 0.35em 0.6em;
    border-radius: 0.25rem;
    font-weight: 500;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.btn-group .btn {
    border-radius: 4px;
    margin-right: 2px;
}

/* Charts container */
.chart-container {
    position: relative;
    height: 300px;
    width: 100%;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .table-responsive {
        font-size: 0.9rem;
    }
    
    .card-title {
        font-size: 1rem;
    }
}

/* Form control improvements */
.form-control:focus, .form-select:focus {
    border-color: var(--bs-primary);
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}

/* Header and section styling */
.card-header {
    padding: 1rem;
    align-items: center;
}

/* Export button styling */
#export_report {
    transition: all 0.3s ease;
}

#export_report:hover {
    background-color: var(--bs-primary);
    color: white;
}
</style>

<?php include 'footer.php'; ?>