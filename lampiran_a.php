<?php
// lampiran_a.php - GCR Lampiran A template filled by HR after approval

require_once 'config.php';
require_once 'gcr_controller.php';

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
$controller = new GCRController();
$application = $controller->getApplication($application_id);

// Check if application exists and is approved
if (!$application || $application['status'] !== 'approved') {
    header('Location: dashboard.php?error=invalid_application');
    exit;
}

// Process form submission for saving the completed Lampiran A
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_lampiran'])) {
    // Here you would save the filled Lampiran A data
    // This is a placeholder for actual implementation
    $success_message = 'Lampiran A has been saved successfully.';
}

// Page title
$page_title = 'Lampiran A - GCR Application';

// Format the year from application
$year = $application['year'];
// Get GM data from application
$gm_name = "DATO DR. ANDERSON TIONG ING HENG";
$gm_position = "PENGURUS BESAR, VU7";
$gm_signature = $application['signature_data'] ?? ''; // Fetch from Bahagian C (would be gm_signature in actual impl)
$gm_date = !empty($application['gm_date']) ? date('d/m/Y', strtotime($application['gm_date'])) : date('d/m/Y');
?>

<?php include 'header.php'; ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-10 mx-auto">
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

            <div class="mb-3 d-flex justify-content-between">
                <button onclick="window.print();" class="btn btn-outline-primary">
                    <i class="bi bi-printer me-2"></i> Print Lampiran A
                </button>
                
                <a href="view_application.php?type=gcr&id=<?php echo $application_id; ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i> Back to Application
                </a>
            </div>

            <form method="POST" action="" id="lampiranForm">
                <input type="hidden" name="save_lampiran" value="1">
                <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                
                <div class="card shadow lampiran-document">
                    <div class="card-body p-4">
                        <!-- Lampiran A Header -->
                        <div class="text-end mb-3">
                            <strong>Lampiran A</strong>
                        </div>
                        
                        <div class="text-center mb-4">
                            <h5 class="fw-bold text-uppercase">KEBENARAN DARIPADA KETUA JABATAN</h5>
                            <h5 class="fw-bold text-uppercase">UNTUK MENGUMPUL CUTI REHAT BAGI GCR</h5>
                            <h5 class="fw-bold text-uppercase">DI BAWAH PEKELILING PERKHIDMATAN BILANGAN 7 TAHUN 2003</h5>
                        </div>
                        
                        <div class="mb-4">
                            <p>Saya memberi kebenaran kepada undang-undang sibawah ini:
                            
                            <div class="row mb-2">
                                <div class="col-md-5">
                                    <label>Nombor Kad Pengenalan</label>
                                </div>
                                <div class="col-md-7">
                                    <div class="dotted-line">
                                        <?php echo htmlspecialchars($application['ic_number'] ?? ''); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <p><strong>(a)</strong> untuk mengumpul keseluruhan cuti rehat tahunannya sebanyak <span class="dotted-input"><?php echo htmlspecialchars($application['days_requested']); ?></span> hari sebelum akhir tahun 20<span class="dotted-input"><?php echo substr($year, 2, 2); ?></span>. Daripada jumlah hari cuti rehatnya sebanyak <span class="dotted-input">_____</span> hari yang tidak dapat dihabiskan atas kepentingan perkhidmatan, sebanyak <span class="dotted-input">_____</span> hari adalah dibenarkan dikumpul bagi GCR, manakala sebanyak <span class="dotted-input">_____</span> hari lagi adalah dibenarkan dibawa ke tahun hadapan;</p>
                                
                                <p class="text-center">ATAU*</p>
                                
                                <p><strong>(b)</strong> untuk mengumpul kesemua cuti rehat tahunan yang layak sebanyak <span class="dotted-input">_____</span> hari sebelum akhir tahun 20<span class="dotted-input">__</span>. Hal selain daripada daripada akhir perkhidmatan pegawai ini, maka seluruh <span class="dotted-input">_____</span> hari adalah dibenarkan dikumpul bagi GCR sehingga <span class="dotted-input">_____</span> hari tidak dibenarkan dibawa ke tahun 20<span class="dotted-input">__</span>.</p>
                            </div>
                            
                            <div class="row mt-5 mb-4">
                                <div class="col-md-5">
                                    <div class="signature-box mb-2">
                                        <?php if (!empty($gm_signature)): ?>
                                            <img src="<?php echo $gm_signature; ?>" alt="GM Signature" class="img-fluid">
                                        <?php else: ?>
                                            <p class="text-muted text-center">(Tandatangan Ketua Jabatan)</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-2">
                                    <label>Nama Penuh</label>
                                </div>
                                <div class="col-md-1 text-center">
                                    <strong>:</strong>
                                </div>
                                <div class="col-md-9">
                                    <strong><?php echo htmlspecialchars($gm_name); ?></strong>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-2">
                                    <label>Jawatan</label>
                                </div>
                                <div class="col-md-1 text-center">
                                    <strong>:</strong>
                                </div>
                                <div class="col-md-9">
                                    <strong><?php echo htmlspecialchars($gm_position); ?></strong>
                                </div>
                            </div>
                            
                            <div class="row mb-4">
                                <div class="col-md-2">
                                    <label>Tarikh</label>
                                </div>
                                <div class="col-md-1 text-center">
                                    <strong>:</strong>
                                </div>
                                <div class="col-md-9">
                                    <strong><?php echo $gm_date; ?></strong>
                                </div>
                            </div>
                            
                            <div class="notes mt-5 small">
                                <p><em>Nota 1: Bagi pegawai yang terakhir di bawah Sistem Saraan Baru (Peranggan 2, Lampiran D7, Pekeliling Perkhidmatan Bil. 9 Tahun 1991) dan Sistem Saraan Malaysia, baki cuti rehat tahun pertama yang tidak dihabiskan, hanya pada akhir tahun.</em></p>
                                <p><em>Nota 2: Kebenaran ini perlu dimohonkan kepada pegawai berkenaan.</em></p>
                                <p><em>Nota 3: Pengumpulan cuti rehat bagi GCR hendaklah diputuskan selepas genap 100 hari.</em></p>
                                <ul>
                                    <li><em>Potong mana yang tidak berkenaan</em></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-save me-2"></i> Save Lampiran A
                    </button>
                    
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="bi bi-house me-2"></i> Back to Dashboard
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
/* Lampiran A specific styles */
.lampiran-document {
    font-family: 'Times New Roman', Times, serif;
    font-size: 1rem;
}

.dotted-line {
    border-bottom: 1px dotted #000;
    padding-bottom: 2px;
    min-height: 1.5em;
    display: inline-block;
    width: 100%;
}

.dotted-input {
    display: inline-block;
    min-width: 30px;
    border-bottom: 1px dotted #000;
    text-align: center;
    font-weight: bold;
    padding: 0 5px;
}

.signature-box {
    border: 1px solid #ddd;
    height: 100px;
    width: 100%;
    position: relative;
    overflow: hidden;
}

.signature-box img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.notes {
    border-top: 1px solid #dee2e6;
    padding-top: 15px;
}

/* Print styles */
@media print {
    body {
        background-color: #fff;
        font-size: 12pt;
    }
    
    .container {
        max-width: 100%;
        width: 100%;
    }
    
    .card {
        border: none;
        box-shadow: none;
    }
    
    .btn, .alert, form > .d-flex, header, footer, .navbar {
        display: none !important;
    }
    
    .signature-box {
        border: none;
    }
}
</style>

<?php include 'footer.php'; ?>