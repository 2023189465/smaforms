<?php
// print_training.php - Print-friendly view for training applications

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check required parameters
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=invalid_parameters');
    exit;
}

$application_id = $_GET['id'];
$controller = new TrainingController();
$application = $controller->getApplication($application_id);

// Check if application exists
if (!$application) {
    header('Location: dashboard.php?error=application_not_found');
    exit;
}

// Function to format date/time
function formatDateTime($dateTime, $format = 'd M Y, h:i A') {
    return date($format, strtotime($dateTime));
}

// Function to get badge class for status
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'pending_submission':
            return 'secondary';
        case 'pending_hod':
            return 'info';
        case 'pending_hr':
            return 'primary';
        case 'pending_gm':
            return 'warning';
        case 'approved':
            return 'success';
        case 'rejected':
            return 'danger';
        default:
            return 'secondary';
    }
}

// Get status text
function getStatusText($status) {
    switch($status) {
        case 'pending_submission': return 'Draft';
        case 'pending_hod': return 'Pending HOD Approval';
        case 'pending_hr': return 'Pending HR Review';
        case 'pending_gm': return 'Pending GM Approval';
        case 'approved': return 'Approved';
        case 'rejected': return 'Rejected';
        default: return ucwords(str_replace('_', ' ', $status));
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Application <?php echo htmlspecialchars($application['reference_number'] ?? 'Print View'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
            background-color: white;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 0 15px;
        }
        
        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 100;
        }
        
        /* Custom header layout */
        .page-header {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .logo-container {
            width: 80px;
        }
        
        .header-logo {
            height: 45px;
        }
        
        .title-container {
            flex-grow: 1;
            text-align: center;
        }
        
        .title-container h1 {
            font-size: 18pt;
            color: #0d6efd;
            margin: 0;
            font-weight: 600;
            line-height: 1.2;
        }
        
        .title-container h2 {
            font-size: 14pt;
            color: #555;
            margin: 5px 0 0;
            font-weight: normal;
        }
        
        .date-container {
            width: 80px;
            text-align: right;
            font-size: 9pt;
            color: #6c757d;
        }
        
        /* Horizontal line below header */
        .header-line {
            height: 2px;
            background-color: #0d6efd;
            margin-bottom: 20px;
        }
        
        /* Application info section */
        .application-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        
        .ref-container {
            display: flex;
            flex-direction: column;
        }
        
        .reference-number {
            font-size: 12pt;
            font-weight: bold;
        }
        
        .application-date {
            font-size: 10pt;
            color: #555;
        }
        
        .status-container {
            text-align: right;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 4px;
            font-weight: bold;
            color: white;
            background-color: #198754;
        }
        
        /* Section styling */
        .section {
            margin-bottom: 20px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        
        .section-header {
            background-color: #f8f9fa;
            padding: 8px 15px;
            font-weight: 600;
            border-bottom: 1px solid #dee2e6;
            border-left: 4px solid #0d6efd;
        }
        
        .section-body {
            padding: 15px;
        }
        
        .data-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .data-label {
            font-weight: bold;
            min-width: 150px;
            padding-right: 15px;
        }
        
        .data-value {
            flex: 1;
        }
        
        .highlight-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }
        
        .document-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
        }
        
        .document-list li {
            padding: 6px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .document-list li:last-child {
            border-bottom: none;
        }
        
        /* Notes and footer */
        .form-notes {
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            font-size: 9pt;
            color: #6c757d;
            margin-top: 30px;
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        
        .page-footer {
            border-top: 1px solid #dee2e6;
            padding-top: 10px;
            text-align: center;
            font-size: 9pt;
            color: #6c757d;
            margin-top: 20px;
        }
        
        /* Decision colors */
        .text-approved {
            color: #198754;
            font-weight: bold;
        }
        
        .text-rejected {
            color: #dc3545;
            font-weight: bold;
        }
        
        .text-available {
            color: #198754;
            font-weight: bold;
        }
        
        .text-not-available {
            color: #dc3545;
            font-weight: bold;
        }
        
        /* Print-specific styles */
        @media print {
            body {
                min-height: 100vh;
                display: flex;
                flex-direction: column;
            }
            
            .print-btn {
                display: none;
            }
            
            /* Set page margins */
            @page {
                margin: 0.5in;
                size: portrait;
            }
            
            /* Fix header and footer on each page */
            .page-header {
                position: fixed;
                top: 0.5in;
                left: 0.5in;
                right: 0.5in;
                height: 70px;
                background-color: white;
                z-index: 100;
            }
            
            .header-line {
                position: fixed;
                top: calc(0.5in + 70px);
                left: 0.5in;
                right: 0.5in;
                z-index: 100;
                background-color: white;
            }
            
            /* Fixed footer positioning for print only */
            .page-footer {
                position: relative; /* Not fixed! */
                margin-top: auto; /* Push to bottom */
                page-break-after: avoid;
                page-break-before: auto;
            }
            
            /* Add space for fixed elements */
            .content-area {
                flex: 1; /* Take all available space */
                margin-top: 90px; /* Space for header */
            }
            
            /* Force backgrounds to print */
            .section-header, 
            .highlight-box, 
            .status-badge {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
            
            /* Make sure status colors print properly */
            .status-badge.bg-success {
                background-color: #198754 !important;
                color: white !important;
            }
            
            .status-badge.bg-danger {
                background-color: #dc3545 !important;
                color: white !important;
            }
            
            .status-badge.bg-warning {
                background-color: #ffc107 !important;
                color: #212529 !important;
            }
            
            .status-badge.bg-info {
                background-color: #0dcaf0 !important;
                color: #212529 !important;
            }
            
            .status-badge.bg-secondary {
                background-color: #6c757d !important;
                color: white !important;
            }
            
            /* No margins for container in print */
            .container {
                max-width: 100%;
                padding: 0;
                margin: 0;
            }
            
            /* Ensure page breaks are handled correctly */
            .section {
                page-break-inside: avoid;
            }
            
            .form-notes {
                margin-bottom: 50px; /* Extra space before footer */
            }
           
            html, body {
                height: 100%;
            }
        }
    </style>
</head>
<body>
    <button class="btn btn-primary print-btn" onclick="window.print()">
        <i class="bi bi-printer"></i> Print
    </button>
    
    
    <!-- Special header just for printing -->
    <div class="header-for-print">
        <div style="display: flex; align-items: center;">
            <img src="assets/images/sma-logo.png" alt="SMA Logo" style="height: 40px; margin-right: 15px;">
            <div style="flex-grow: 1; text-align: center;">
                <h1 style="font-size: 16pt; color: #0d6efd; margin: 0;">EXTERNAL TRAINING APPLICATION FORM</h1>
                <h2 style="font-size: 12pt; color: #555; margin: 0;">Sarawak Multimedia Authority</h2>
            </div>
            <div style="text-align: right; font-size: 9pt; color: #6c757d;">
                <?php echo date('d/m/Y, H:i'); ?>
            </div>
        </div>
    </div>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="application-info">
                <div>
                    <div class="reference-number">
                        SMA/500-<?php echo htmlspecialchars($application['reference_number'] ?? '________'); ?>
                    </div>
                    <div>Submission Date: <?php echo formatDateTime($application['created_at'], 'd M Y'); ?></div>
                </div>
                <div class="application-status">
                    <div class="status-badge bg-<?php echo getStatusBadgeClass($application['status']); ?>">
                        <?php echo getStatusText($application['status']); ?>
                    </div>
                </div>
            </div>
            
            <!-- Section A: Programme Details -->
            <div class="section">
                <div class="section-header">SECTION A: PROGRAMME DETAILS</div>
                <div class="section-body">
                    <div class="data-row">
                        <div class="data-label">Training Title:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['programme_title']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Venue:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['venue']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Organiser:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['organiser']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Date & Time:</div>
                        <div class="data-value"><?php echo formatDateTime($application['date_time']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Fee (MYR):</div>
                        <div class="data-value"><?php echo number_format($application['fee'], 2); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Section B: Requestor Details -->
            <div class="section">
                <div class="section-header">SECTION B: REQUESTOR DETAILS</div>
                <div class="section-body">
                    <div class="data-row">
                        <div class="data-label">Name:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['requestor_name']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Position/Grade:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['post_grade']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Unit/Division:</div>
                        <div class="data-value"><?php echo htmlspecialchars($application['unit_division']); ?></div>
                    </div>
                    
                    <div class="data-row">
                        <div class="data-label">Submission Date:</div>
                        <div class="data-value"><?php echo formatDateTime($application['requestor_date'], 'd M Y'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Section C: Justifications -->
            <div class="section">
                <div class="section-header">SECTION C: JUSTIFICATION(S) FOR ATTENDING</div>
                <div class="section-body">
                    <div class="highlight-box">
                        <?php echo nl2br(htmlspecialchars($application['justification'])); ?>
                    </div>
                    
                    <?php if (!empty($application['documents'])): ?>
                    <div class="data-row">
                        <div class="data-label">Attached Documents:</div>
                        <div class="data-value">
                            <ul class="document-list">
                                <?php foreach ($application['documents'] as $document): ?>
                                    <li>
                                        <i class="bi bi-file-earmark"></i> 
                                        <?php echo htmlspecialchars($document['file_name']); ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section D: HOD Recommendation -->
            <div class="section">
                <div class="section-header">SECTION D: RECOMMENDATION BY HEAD OF DIVISION</div>
                <div class="section-body">
                    <?php if (isset($application['hod_decision']) && !empty($application['hod_decision'])): ?>
                        <div class="data-row">
                            <div class="data-label">Decision:</div>
                            <div class="data-value">
                                <?php if ($application['hod_decision'] === 'recommended'): ?>
                                    <span class="text-approved">Recommended</span>
                                <?php else: ?>
                                    <span class="text-rejected">Not Recommended</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($application['hod_comments']) && !empty($application['hod_comments'])): ?>
                            <div class="data-row">
                                <div class="data-label">Comments:</div>
                                <div class="data-value highlight-box">
                                    <?php echo nl2br(htmlspecialchars($application['hod_comments'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <div class="data-label">HOD Name:</div>
                            <div class="data-value"><?php echo htmlspecialchars($application['hod_name'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <?php if (isset($application['hod_date']) && !empty($application['hod_date'])): ?>
                            <div class="data-row">
                                <div class="data-label">Date:</div>
                                <div class="data-value"><?php echo formatDateTime($application['hod_date'], 'd M Y'); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted text-center py-3">This section has not been completed yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section E: HR Comments -->
            <div class="section">
                <div class="section-header">SECTION E: COMMENTS BY HUMAN RESOURCE UNIT</div>
                <div class="section-body">
                    <?php if (isset($application['hr_comments']) || isset($application['budget_status'])): ?>
                        <?php if (isset($application['reference_number']) && !empty($application['reference_number'])): ?>
                            <div class="data-row">
                                <div class="data-label">Reference Number:</div>
                                <div class="data-value"><strong><?php echo htmlspecialchars($application['reference_number']); ?></strong></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($application['hr_comments']) && !empty($application['hr_comments'])): ?>
                            <div class="data-row">
                                <div class="data-label">Comments:</div>
                                <div class="data-value highlight-box">
                                    <?php echo nl2br(htmlspecialchars($application['hr_comments'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($application['budget_status']) && !empty($application['budget_status'])): ?>
                            <div class="data-row">
                                <div class="data-label">Budget:</div>
                                <div class="data-value">
                                    <?php if ($application['budget_status'] === 'yes'): ?>
                                        <span class="text-available">Available</span>
                                    <?php else: ?>
                                        <span class="text-not-available">Not Available</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($application['credit_hours']) && !empty($application['credit_hours'])): ?>
                            <div class="data-row">
                                <div class="data-label">Credit Hours:</div>
                                <div class="data-value"><?php echo htmlspecialchars($application['credit_hours']); ?></div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($application['budget_comments']) && !empty($application['budget_comments'])): ?>
                            <div class="data-row">
                                <div class="data-label">Budget Comments:</div>
                                <div class="data-value highlight-box">
                                    <?php echo nl2br(htmlspecialchars($application['budget_comments'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <div class="data-label">HR Officer:</div>
                            <div class="data-value"><?php echo htmlspecialchars($application['hr_name'] ?? 'Not specified'); ?></div>
                        </div>
                        
                        <?php if (isset($application['hr_date']) && !empty($application['hr_date'])): ?>
                            <div class="data-row">
                                <div class="data-label">Date:</div>
                                <div class="data-value"><?php echo formatDateTime($application['hr_date'], 'd M Y'); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted text-center py-3">This section has not been completed yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Section F: GM Approval -->
            <div class="section">
                <div class="section-header">SECTION F: APPROVAL BY GENERAL MANAGER</div>
                <div class="section-body">
                    <?php if (isset($application['gm_decision']) && !empty($application['gm_decision'])): ?>
                        <div class="data-row">
                            <div class="data-label">Decision:</div>
                            <div class="data-value">
                                <?php if ($application['gm_decision'] === 'approved'): ?>
                                    <span class="text-approved">Approved</span>
                                <?php else: ?>
                                    <span class="text-rejected">Rejected</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($application['gm_comments']) && !empty($application['gm_comments'])): ?>
                            <div class="data-row">
                                <div class="data-label">Comments:</div>
                                <div class="data-value highlight-box">
                                    <?php echo nl2br(htmlspecialchars($application['gm_comments'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <div class="data-row">
                            <div class="data-label">General Manager:</div>
                            <div class="data-value">Dato Dr. Anderson Tiong Ing Heng</div>
                        </div>
                        
                        <?php if (isset($application['gm_date']) && !empty($application['gm_date'])): ?>
                            <div class="data-row">
                                <div class="data-label">Date:</div>
                                <div class="data-value"><?php echo formatDateTime($application['gm_date'], 'd M Y'); ?></div>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="text-muted text-center py-3">This section has not been completed yet.</div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="form-notes" style="margin-bottom: 50px;">
                <p><strong>Note:</strong> This is an official document. Keep for your records. Training attendance should be in accordance with the details provided.</p>
            </div>
        </div>
    </div>
    
     <!-- Footer that shows on screen -->
     <div class="page-footer">
        <p>Printed on: <?php echo date('d M Y, h:i A'); ?> | SMA Forms System v1.0 | Generated by: <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    </div>
    
    <!-- Special footer just for printing -->
    <div class="footer-for-print">
        <p>Printed on: <?php echo date('d M Y, h:i A'); ?> | SMA Forms System v1.0 | Generated by: <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
    </div>
    
    <script>
        // Automatically print when the page loads
        window.onload = function() {
            // Delay to ensure content and styles are loaded
            setTimeout(function() {
                window.print();
            }, 800);
        };
    </script>
</body>
</html>