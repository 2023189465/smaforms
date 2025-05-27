<?php
// Process HR review for training application

require_once 'config.php';
require_once 'training_controller.php';

// Check if user is logged in and has HR role
if (!isLoggedIn() || !hasRole('hr')) {
    header('Location: login.php');
    exit;
}

// Check if application ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: dashboard.php?error=no_application_id');
    exit;
}

$application_id = $_GET['id'];
$controller = new TrainingController();
$application = $controller->getApplication($application_id);

// Check if application exists and is pending HR review
if (!$application || $application['status'] !== 'pending_hr') {
    header('Location: dashboard.php?error=invalid_application');
    exit;
}

$error_message = '';
$success_message = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug log
    error_log("HR form submitted: " . print_r($_POST, true));
    
    $result = $controller->processHRDecision($_POST);
    
    if ($result['success']) {
        $success_message = $result['message'];
        
        // Redirect after successful processing
        header('Location: dashboard.php?success=' . urlencode($success_message));
        exit;
    } else {
        $error_message = $result['message'];
    }
}

// Ensure reference number is properly defined
$reference_number = '';
if (isset($application['reference_number']) && !empty($application['reference_number'])) {
    $reference_number = $application['reference_number'];
} else {
    $reference_number = generateReferenceNumber('training');
}

// Page title
$page_title = 'HR Review - Training Application';
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="bi bi-briefcase me-2"></i> HR Review - Training Application
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
                    
                    <form id="hrReviewForm" method="POST" action="" class="needs-validation" novalidate>
                        <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                        
                        <!-- Display Application Details (Read-only) -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-info-circle me-2"></i> Application Details
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold">Reference Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control border-primary" name="reference_number" value="SMA/TRN-2025-" required>
                                        <div class="form-text">Format: SMA/TRN-2025-XXXX (Required for application processing)</div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Submission Date</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo date('d M Y', strtotime($application['created_at'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Training Title</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($application['programme_title']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Organiser</label>
                                        <input type="text" class="form-control bg-light" value="<?php echo htmlspecialchars($application['organiser']); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Venue</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['venue']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Date & Time</label>
                                        <input type="text" class="form-control" value="<?php echo date('d M Y, h:i A', strtotime($application['date_time'])); ?>" readonly>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Fee (MYR)</label>
                                        <input type="text" class="form-control" value="<?php echo number_format($application['fee'], 2); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Applicant</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['applicant_name']); ?>" readonly>
                                    </div>
                                </div>
                                
                                <!-- HOD Decision -->
                                <div class="row mt-3">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">HOD Decision</label>
                                        <input type="text" class="form-control <?php echo $application['hod_decision'] === 'recommended' ? 'bg-success text-white' : 'bg-danger text-white'; ?>" value="<?php echo ucfirst($application['hod_decision']); ?>" readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">HOD</label>
                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($application['hod_name'] ?? 'N/A'); ?>" readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label">HOD Comments</label>
                                    <div class="form-control bg-light" style="min-height: 80px; overflow-y: auto;">
                                        <?php echo nl2br(htmlspecialchars($application['hod_comments'] ?? 'No comments')); ?>
                                    </div>
                                </div>
                                
                                <!-- Justification -->
                                <div class="mb-3">
                                    <label class="form-label">Justification</label>
                                    <div class="form-control bg-light" style="min-height: 100px; overflow-y: auto;">
                                        <?php echo nl2br(htmlspecialchars($application['justification'])); ?>
                                    </div>
                                </div>
                                
                                <!-- Attachments -->
                                <?php if (!empty($application['documents'])): ?>
                                    <div class="mt-3">
                                        <label class="form-label">Attachments</label>
                                        <div class="list-group">
                                            <?php foreach ($application['documents'] as $document): ?>
                                                <a href="download_document.php?id=<?php echo $document['id']; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                                    <span>
                                                        <i class="bi bi-file-earmark me-2"></i> 
                                                        <?php echo htmlspecialchars($document['file_name']); ?>
                                                    </span>
                                                    <span class="badge bg-primary rounded-pill">
                                                        <i class="bi bi-download"></i>
                                                    </span>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- HR Review Section -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-clipboard-check me-2"></i> HR Review
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="hr_comments" class="form-label required-field">Comments (250 words max)</label>
                                    <textarea class="form-control" id="hr_comments" name="hr_comments" rows="3" maxlength="1250" required></textarea>
                                    <small class="text-muted" id="hr_comments_counter">0/250 words</small>
                                    <div class="invalid-feedback">Please provide your comments.</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Budget Available</label>
                                        <div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="budget_status" id="budget_yes" value="yes" required>
                                                <label class="form-check-label" for="budget_yes">Yes</label>
                                            </div>
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="budget_status" id="budget_no" value="no" required>
                                                <label class="form-check-label" for="budget_no">No</label>
                                            </div>
                                            <div class="invalid-feedback">Please select budget status.</div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6 mb-3">
                                        <label for="credit_hours" class="form-label required-field">Credit Hours</label>
                                        <input type="number" class="form-control" id="credit_hours" name="credit_hours" min="0" required>
                                        <div class="invalid-feedback">Please enter credit hours.</div>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="budget_comments" class="form-label">Budget Comments (250 words max)</label>
                                    <textarea class="form-control" id="budget_comments" name="budget_comments" rows="2" maxlength="1250"></textarea>
                                    <small class="text-muted" id="budget_comments_counter">0/250 words</small>
                                </div>
                            </div>
                        </div>

                        <!-- HR Signature Section -->
                        <div class="form-section mb-4">
                            <div class="section-header">
                                <i class="bi bi-pen me-2"></i> HR Signature
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">HR Officer Name</label>
                                        <input type="text" class="form-control bg-light" name="hr_officer_name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>" required readonly>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label required-field">Date</label>
                                        <input type="date" class="form-control bg-light" name="hr_date" value="<?php echo date('Y-m-d'); ?>" required readonly>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label class="form-label required-field">Signature:</label>
                                    <div class="signature-area">
                                        <canvas id="signatureCanvas"></canvas>
                                        <input type="hidden" id="signature_data" name="signature_data">
                                    </div>
                                    <div class="invalid-feedback">Please provide your signature.</div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" id="clearSignature">
                                            <i class="bi bi-eraser me-1"></i> Clear Signature
                                        </button>
                                    </div>
                                    <div class="form-text">Signing as: <?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center mt-4">
                            <a href="dashboard.php" class="btn btn-outline-secondary me-md-2 px-4 py-2">
                                <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary px-4 py-2">
                                <i class="bi bi-check-circle me-2"></i> Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
// Enhanced signature pad implementation
// Full width signature pad implementation
document.addEventListener('DOMContentLoaded', function() {
    // Initialize signature pad
    const canvas = document.getElementById('signatureCanvas');
    let signaturePad = null;
    
    // Set canvas to full width of container
    function setCanvasSize() {
        const container = canvas.parentElement;
        const containerWidth = container.clientWidth;
        
        // Set canvas CSS width to 100% of container
        canvas.style.width = '100%';
        canvas.style.height = '150px';
        
        // Set canvas actual dimensions (important for proper drawing)
        // Use higher resolution for better quality
        const dpr = window.devicePixelRatio || 1;
        canvas.width = containerWidth * dpr;
        canvas.height = 150 * dpr;
        
        // Scale the context
        const ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);
    }
    
    // Initialize signature pad with better settings
    function initSignaturePad() {
        if (signaturePad) {
            signaturePad.clear();
            signaturePad.off();
        }
        
        // First set the canvas size
        setCanvasSize();
        
        // Then initialize the signature pad
        signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'black',
            velocityFilterWeight: 0.5,
            minWidth: 0.8,
            maxWidth: 3.0,
            throttle: 16,
            minDistance: 1
        });
    }
    
    // Clear signature button
    document.getElementById('clearSignature').addEventListener('click', function() {
        if (signaturePad) {
            signaturePad.clear();
        }
    });
    
    // Form validation
    const form = document.getElementById('hrReviewForm');
    
    form.addEventListener('submit', function(event) {
        // Check if signature is empty
        if (signaturePad && signaturePad.isEmpty()) {
            event.preventDefault();
            alert('Please provide your signature');
            return;
        }
        
        // Save signature data to hidden field
        if (signaturePad) {
            document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
        }
        
        // Check form validity
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        
        this.classList.add('was-validated');
    });
    
    // Initialize on page load
    initSignaturePad();
    
    // Handle window resize to maintain full width
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Save signature data
            const signatureData = signaturePad && !signaturePad.isEmpty() ? 
                signaturePad.toData() : null;
            
            // Resize and reinitialize
            initSignaturePad();
            
            // Restore signature data if available
            if (signatureData && signatureData.length) {
                signaturePad.fromData(signatureData);
            }
        }, 200);
    });
    
    // Add visual cues to show the signature area is active
    canvas.parentElement.style.border = "1px dashed #007bff";
    canvas.parentElement.style.width = "100%";
    
    // Optionally add a hint text below the canvas
    const hintText = document.createElement('div');
    hintText.textContent = "Sign here using mouse or touch";
    hintText.style.fontSize = "12px";
    hintText.style.color = "#6c757d";
    hintText.style.textAlign = "center";
    hintText.style.marginTop = "5px";
    canvas.parentElement.appendChild(hintText);
});
</script>

<script>
// Function to count words in a textarea
function countWords(text) {
    return text.trim().split(/\s+/).filter(function(word) {
        return word.length > 0;
    }).length;
}

// Update the word counter for a textarea
function updateWordCounter(textarea, counter, maxWords) {
    const wordCount = countWords(textarea.value);
    counter.textContent = wordCount + '/' + maxWords + ' words';
    
    // Add visual indication when approaching/exceeding limit
    if (wordCount > maxWords) {
        counter.classList.add('text-danger');
        counter.classList.remove('text-muted');
    } else if (wordCount > maxWords * 0.8) {
        counter.classList.add('text-warning');
        counter.classList.remove('text-muted', 'text-danger');
    } else {
        counter.classList.add('text-muted');
        counter.classList.remove('text-warning', 'text-danger');
    }
}

// Set up word counters for both textareas
document.addEventListener('DOMContentLoaded', function() {
    const hr_comments = document.getElementById('hr_comments');
    const hr_counter = document.getElementById('hr_comments_counter');
    const budget_comments = document.getElementById('budget_comments');
    const budget_counter = document.getElementById('budget_comments_counter');
    const maxWords = 250;
    
    if (hr_comments && hr_counter) {
        updateWordCounter(hr_comments, hr_counter, maxWords);
        hr_comments.addEventListener('input', function() {
            updateWordCounter(hr_comments, hr_counter, maxWords);
        });
    }
    
    if (budget_comments && budget_counter) {
        updateWordCounter(budget_comments, budget_counter, maxWords);
        budget_comments.addEventListener('input', function() {
            updateWordCounter(budget_comments, budget_counter, maxWords);
        });
    }
});
</script>

<style>
/* Custom styles for the form */
.section-header {
    background-color: #f8f9fa;
    padding: 10px 15px;
    border-radius: 4px;
    margin-bottom: 15px;
    font-weight: 600;
    border-left: 4px solid #0d6efd; /* Blue color for Training forms */
}

.form-section {
    border: 1px solid #dee2e6;
    border-radius: 5px;
    margin-bottom: 20px;
}

.required-field::after {
    content: " *";
    color: #dc3545;
}

.signature-area {
    background-color: #fff;
    width: 100%;
    padding: 10px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    position: relative;
    display: block;
}

#signatureCanvas {
    width: 100% !important; /* Force full width */
    height: 150px !important;
    border: none;
    display: block;
    touch-action: none; /* Improves drawing on touchscreens */
    cursor: crosshair;  /* Shows user they can draw here */
    background-color: rgba(248, 249, 250, 0.3); /* Very light gray background */
}

/* Animation to indicate the canvas is ready for signing */
@keyframes signHere {
    0% { box-shadow: 0 0 0 rgba(13, 110, 253, 0); }
    50% { box-shadow: 0 0 10px rgba(13, 110, 253, 0.5); }
    100% { box-shadow: 0 0 0 rgba(13, 110, 253, 0); }
}

.signature-area:hover {
    animation: signHere 2s ease-in-out;
}
</style>

<?php include 'footer.php'; ?>