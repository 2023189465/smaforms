<?php
// view_evaluation.php - View training evaluation details

require_once 'config.php';
require_once 'TrainingEvaluationController.php';
require_once 'training_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

// Check if evaluation ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=no_evaluation_id');
    exit;
}

$evaluation_id = $_GET['id'];
$evaluationController = new TrainingEvaluationController();
$trainingController = new TrainingController();

$evaluation = $evaluationController->getEvaluation($evaluation_id);

// Check if evaluation exists and user has permission to view it
if (!$evaluation) {
    header('Location: dashboard.php?error=invalid_evaluation');
    exit;
}

// Staff can only view their own evaluations, others can view all
if ($_SESSION['user_role'] === 'staff' && $evaluation['user_id'] != $_SESSION['user_id']) {
    header('Location: dashboard.php?error=unauthorized_access');
    exit;
}

// Get training details
$training = $trainingController->getApplication($evaluation['training_id']);

// Page title
$page_title = 'View Training Evaluation';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="bi bi-clipboard-check me-2"></i> Training Evaluation
                        </h3>
                        <span class="badge <?php echo ($evaluation['status'] === 'completed' || $evaluation['status'] === 'submitted') ? 'bg-success' : 'bg-warning'; ?>">
                            <?php echo ucfirst($evaluation['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Training Details -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Training Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Reference Number</label>
                                    <input type="text" class="form-control" value="<?php echo $training['reference_number'] ?? 'N/A'; ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Submitted By</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($evaluation['user_name'] ?? 'Unknown'); ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label">Training Title</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($training['programme_title'] ?? $evaluation['training_title'] ?? 'Untitled Training'); ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Organiser</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($training['organiser'] ?? $evaluation['organiser'] ?? 'N/A'); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Venue</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($training['venue'] ?? $evaluation['venue'] ?? 'N/A'); ?>" readonly>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Training Date</label>
                                    <input type="text" class="form-control" value="<?php echo date('d M Y, h:i A', strtotime($training['date_time'] ?? $evaluation['training_date'] ?? date('Y-m-d'))); ?>" readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Status</label>
                                    <input type="text" class="form-control" value="<?php echo ($evaluation['status'] === 'completed' || $evaluation['status'] === 'submitted') ? 'Completed' : 'Pending'; ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($evaluation['status'] === 'completed' || $evaluation['status'] === 'submitted'): ?>
                    <!-- Evaluation Details Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Evaluation Details</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Overall Rating</label>
                                    <div>
                                        <?php 
                                        $rating = $evaluation['overall_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo '<i class="bi ' . ($i <= $rating ? 'bi-star-fill text-warning' : 'bi-star') . ' me-1"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Content Rating</label>
                                    <div>
                                        <?php 
                                        $rating = $evaluation['content_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo '<i class="bi ' . ($i <= $rating ? 'bi-star-fill text-warning' : 'bi-star') . ' me-1"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Facilitator Rating</label>
                                    <div>
                                        <?php 
                                        $rating = $evaluation['facilitator_rating'];
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo '<i class="bi ' . ($i <= $rating ? 'bi-star-fill text-warning' : 'bi-star') . ' me-1"></i>';
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Knowledge Gained</label>
                                <div class="form-control bg-light" style="min-height: 100px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($evaluation['knowledge_gained'])); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Skills Improvement</label>
                                <div class="form-control bg-light" style="min-height: 100px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($evaluation['skill_improvement'])); ?>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Application Plan</label>
                                <div class="form-control bg-light" style="min-height: 100px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($evaluation['application_plan'])); ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($evaluation['feedback'])): ?>
                            <div class="mb-3">
                                <label class="form-label">Additional Feedback</label>
                                <div class="form-control bg-light" style="min-height: 100px; overflow-y: auto;">
                                    <?php echo nl2br(htmlspecialchars($evaluation['feedback'])); ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Completion Date</label>
                                    <input type="text" class="form-control" value="<?php echo date('d M Y', strtotime($evaluation['completion_date'] ?? $evaluation['submitted_date'] ?? date('Y-m-d'))); ?>" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle me-2"></i> This evaluation is still pending. The user has not submitted their feedback yet.
                    </div>
                    <?php endif; ?>
                    
                    <!-- History Section -->
                    <?php if (!empty($evaluation['history'])): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Evaluation History</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Action</th>
                                            <th>User</th>
                                            <th>Comments</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($evaluation['history'] as $history): ?>
                                        <tr>
                                            <td><?php echo date('d M Y H:i', strtotime($history['timestamp'])); ?></td>
                                            <td><?php echo htmlspecialchars($history['action']); ?></td>
                                            <td><?php echo htmlspecialchars($history['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($history['comments']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                        <a href="dashboard.php" class="btn btn-primary px-4 py-2">
                            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                        </a>
                        
                        <?php if ($evaluation['status'] === 'pending' && $evaluation['user_id'] === $_SESSION['user_id']): ?>
                        <a href="training_evaluation_form.php?id=<?php echo $evaluation['id']; ?>" class="btn btn-success px-4 py-2">
                            <i class="bi bi-clipboard-check me-2"></i> Complete Evaluation
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php'; ?>