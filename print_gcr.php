<?php
require_once 'config.php';
require_once 'gcr_controller.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$controller = new GCRController();
$application_id = $_GET['id'] ?? null;

if (!$application_id) {
    die("Invalid request: Application ID is required.");
}

// Fetch application data
$application = $controller->getApplication($application_id);

if (!$application) {
    die("Application not found.");
}

// Check if application is approved
$warning = null;
if ($application['status'] !== 'approved') {
    $warning = "Note: This application hasn't been fully approved yet.";
}

// Get Lampiran A data if available
$lampiran_a = null;
if (method_exists($controller, 'getLampiranA')) {
    $lampiran_a = $controller->getLampiranA($application_id);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>GCR Application Print</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px;
            font-size: 12pt;
        }
        .container { 
            width: 90%; 
            margin: auto; 
        }
        .logo-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .logo {
            max-height: 80px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            border: 1px solid #dee2e6;
        }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            border: 2px solid #198754;
            border-radius: 8px;
            page-break-inside: avoid;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .section-header {
            background-color: #f8f9fa;
            padding: 12px 20px;
            margin: -20px -20px 20px -20px;
            font-weight: 600;
            border-bottom: 2px solid #198754;
            border-radius: 6px 6px 0 0;
        }
        .signature-img {
            max-height: 100px;
            border: 1px solid #dee2e6;
            padding: 5px;
            background: #fff;
        }
        .print-btn {
            display: block; 
            text-align: center; 
            margin: 30px 0; 
        }
        .form-row {
            margin-bottom: 15px;
        }
        .form-label {
            font-weight: 600;
        }
        .form-value {
            padding: 5px 0;
        }
        @media print {
            .print-btn {
                display: none;
            }
            .section {
                break-inside: avoid;
                border: 2px solid #198754;
            }
            .section-header {
                background-color: #f0f0f0 !important;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="logo-header">
            <img src="assets/images/sma-logo.png" alt="Sarawak Multimedia Authority Logo" class="logo">
            <div>
                <h5>SARAWAK MULTIMEDIA AUTHORITY</h5>
                <small>Level 5, Bangunan Yayasan Sarawak, Jalan Masjid, 93000 Kuching, Sarawak</small>
            </div>
        </div>
        
        <div class="header">
            <h3>PERMOHONAN PENGUMPULAN BAKI CUTI BAGI FAEDAH GANTIAN CUTI REHAT (GCR)</h3>
            <h4>TAHUN <?php echo htmlspecialchars($application['year']); ?></h4>
        </div>
        
        <?php if (isset($warning)): ?>
        <div class="alert alert-warning">
            <?php echo $warning; ?>
        </div>
        <?php endif; ?>
        
        <!-- Section A: Applicant Details -->
        <div class="section">
            <div class="section-header">
                <i class="bi bi-person-badge me-2"></i> Bahagian A (Diisi oleh Pemohon)
            </div>
            <div>
                <p><strong>Kepada:</strong> DATO DR. ANDERSON TIONG ING HENG (Ketua Jabatan)</p>
                
                <p><strong>Tuan,</strong></p>
                <p>Dengan hormatnya saya merujuk kepada perkara dia atas dan ingin memohon kelulusan tuan/puan untuk saya mengumpul baki cuti yang tidak dapat dihabiskan pada tahun <strong><?php echo htmlspecialchars($application['year']); ?></strong> bagi tujuan faedah gentian cuti rehat (GCR).</p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <p><strong>Nama:</strong> <?php echo htmlspecialchars($application['applicant_name']); ?></p>
                        <p><strong>Jawatan/Gred:</strong> <?php echo htmlspecialchars($application['applicant_position']); ?></p>
                        <p><strong>Pejabat:</strong> <?php echo htmlspecialchars($application['applicant_department']); ?></p>
                    </div>
                    
                    <div class="col-md-6">
                        <p><strong>Jumlah Hari:</strong> <?php echo (int)$application['days_requested']; ?></p>
                        <p><strong>Tarikh:</strong> <?php echo date('d-m-Y', strtotime($application['created_at'])); ?></p>
                        
                        <?php if (!empty($application['signature_data'])): ?>
                        <p><strong>Tandatangan:</strong></p>
                        <div>
                            <img src="<?php echo $application['signature_data']; ?>" alt="Applicant Signature" class="signature-img">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section B: HR Verification -->
        <div class="section">
            <div class="section-header">
                <i class="bi bi-people me-2"></i> Bahagian B (Diisi oleh Pegawai Yang Diberi Kuasa)
            </div>
            <div>
                <p><strong>PENGESAHAN/KEBENARAN KETUA JABATAN/BAHAGIAN</strong></p>
                <p>Saya mengesahkan bahawa <strong><?php echo htmlspecialchars($application['applicant_name']); ?></strong> dibenarkan untuk mengumpul baki cuti sebanyak <strong><?php echo (int)$application['days_requested']; ?></strong> hari dari tahun <strong><?php echo htmlspecialchars($application['year']); ?></strong> yang tidak dapat dihabiskan atas kepentingan perkhidmatan bagi faedah gantian cuti rehat (GCR) iaitu mengikut kelayakan beliau.</p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Nama:</div>
                            <div class="form-value"><strong><?php echo htmlspecialchars($application['hr1_name'] ?? 'SOPHIA BINTI IDRIS'); ?></strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Jawatan/Gred:</div>
                            <div class="form-value"><strong>PEGAWAI TADBIR, N41</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Pejabat:</div>
                            <div class="form-value"><strong>SUMBER MANUSIA</strong></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Tarikh:</div>
                            <div class="form-value"><strong><?php echo isset($application['hr1_date']) ? date('d-m-Y', strtotime($application['hr1_date'])) : '-'; ?></strong></div>
                        </div>
                        
                        <?php if (!empty($application['hr1_signature'])): ?>
                        <div class="form-row">
                            <div class="form-label">Tandatangan:</div>
                            <div>
                                <img src="<?php echo $application['hr1_signature']; ?>" alt="HR Signature" class="signature-img">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section C: GM Decision -->
        <div class="section">
            <div class="section-header">
                <i class="bi bi-clipboard-check me-2"></i> Bahagian C (Diisi oleh Ketua Jabatan/Bahagian)
            </div>
            <div>
                <p><strong>KEPUTUSAN PERMOHONAN GCR</strong></p>
                <p>Permohonan GCR <strong><?php echo htmlspecialchars($application['applicant_name']); ?></strong> 
                <?php if ($application['gm_decision'] === 'approved'): ?>
                    diluluskan 🗹 sebanyak <strong><?php echo (int)$application['gm_days_approved']; ?></strong> hari / <strike>ditolak* ☐</strike>
                <?php elseif ($application['gm_decision'] === 'rejected'): ?>
                    <strike>diluluskan ☐ sebanyak ...... hari</strike> / ditolak* 🗹 
                <?php else: ?>
                    diluluskan ☐ sebanyak ...... hari / ditolak* ☐
                <?php endif; ?>
                <?php if (!empty($application['gm_comments'])): ?>
                    kerana <?php echo htmlspecialchars($application['gm_comments']); ?>
                <?php endif; ?>
                </p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Nama:</div>
                            <div class="form-value"><strong>DATO DR. ANDERSON TIONG ING HENG</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Jawatan/Gred:</div>
                            <div class="form-value"><strong>PENGURUS BESAR, VU7</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Pejabat:</div>
                            <div class="form-value"><strong>SARAWAK MULTIMEDIA AUTHORITY</strong></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Tarikh:</div>
                            <div class="form-value"><strong><?php echo isset($application['gm_date']) ? date('d-m-Y', strtotime($application['gm_date'])) : '-'; ?></strong></div>
                        </div>
                        
                        <?php if (!empty($application['gm_signature'])): ?>
                        <div class="form-row">
                            <div class="form-label">Tandatangan:</div>
                            <div>
                                <img src="<?php echo $application['gm_signature']; ?>" alt="GM Signature" class="signature-img">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Section D: HR Final Recording -->
        <div class="section">
            <div class="section-header">
                <i class="bi bi-check-circle me-2"></i> Bahagian D (Diisi oleh Pegawai Yang Mengurus Cuti & GCR)
            </div>
            <div>
                <p><strong>REKOD KELULUSAN GCR</strong></p>
                <p>Telah direkodkan dalam Penyata Cuti Rehat Pegawai.</p>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Nama:</div>
                            <div class="form-value"><strong><?php echo htmlspecialchars($application['hr2_name'] ?? 'HAMISIAH BINTI USUP'); ?></strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Jawatan/Gred:</div>
                            <div class="form-value"><strong>PENOLONG PEGAWAI TADBIR, N32</strong></div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Tarikh:</div>
                            <div class="form-value"><strong><?php echo isset($application['hr2_date']) ? date('d-m-Y', strtotime($application['hr2_date'])) : '-'; ?></strong></div>
                        </div>
                        
                        <?php if (!empty($application['hr2_signature'])): ?>
                        <div class="form-row">
                            <div class="form-label">Tandatangan:</div>
                            <div>
                                <img src="<?php echo $application['hr2_signature']; ?>" alt="HR2 Signature" class="signature-img">
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mt-3">
                    <p class="small">s.k. Setiausaha Kerajaan Negeri [UPSM (u.p. Seksyen Kemudahan)] & Pemohon</p>
                    <p class="small">Nota: *mana yang berkenaan</p>
                </div>
            </div>
        </div>

        <!-- Section E: Lampiran A -->
        <?php if ($lampiran_a): ?>
        <div class="section">
            <div class="section-header">
                <i class="bi bi-file-earmark-text me-2"></i> Bahagian E: LAMPIRAN A
            </div>
            <div>
                <div class="mb-3">
                    <p class="fw-bold text-center">PENYATA PENGUMPULAN CUTI REHAT BAGI FAEDAH GANTIAN CUTI REHAT (GCR)</p>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tr>
                            <td colspan="2">
                                <div class="row">
                                    <div class="col-md-3"><strong>Nama:</strong></div>
                                    <div class="col-md-9"><?php echo htmlspecialchars($application['applicant_name']); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div class="row">
                                    <div class="col-md-4"><strong>No. Pekerja:</strong></div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($lampiran_a['employee_id'] ?? ''); ?></div>
                                </div>
                            </td>
                            <td>
                                <div class="row">
                                    <div class="col-md-4"><strong>Jawatan/Gred:</strong></div>
                                    <div class="col-md-8"><?php echo htmlspecialchars($application['applicant_position']); ?></div>
                                </div>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2">
                                <div class="row">
                                    <div class="col-md-3"><strong>Pejabat:</strong></div>
                                    <div class="col-md-9"><?php echo htmlspecialchars($application['applicant_department']); ?></div>
                                </div>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <div class="table-responsive mt-3">
                    <table class="table table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th>BIL</th>
                                <th>TAHUN</th>
                                <th>JUMLAH HARI BAKI</th>
                                <th>JUMLAH HARI DILULUSKAN UNTUK GCR</th>
                                <th>JUMLAH HARI BAKI SELEPAS GCR</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>1</td>
                                <td><?php echo htmlspecialchars($application['year']); ?></td>
                                <td><?php echo (int)($lampiran_a['total_days_balance'] ?? 0); ?></td>
                                <td><?php echo (int)($lampiran_a['gc_days_approved'] ?? 0); ?></td>
                                <td><?php echo (int)($lampiran_a['remaining_days'] ?? 0); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Disediakan Oleh:</div>
                            <div class="form-value"><strong>OINIE ZAPHIA ANAK SAMAT</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Jawatan:</div>
                            <div class="form-value"><strong>HR OFFICER</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Tarikh:</div>
                            <div class="form-value">
                                <strong><?php echo isset($lampiran_a['verified_date']) ? date('d-m-Y', strtotime($lampiran_a['verified_date'])) : date('d-m-Y'); ?></strong>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Tandatangan:</div>
                            <?php if (!empty($application['hr3_signature'])): ?>
                                <div>
                                    <img src="<?php echo $application['hr3_signature']; ?>" alt="HR3 Signature" class="signature-img">
                                </div>
                            <?php elseif (!empty($lampiran_a['hr3_signature'])): ?>
                                <div>
                                    <img src="<?php echo $lampiran_a['hr3_signature']; ?>" alt="HR3 Signature" class="signature-img">
                                </div>
                            <?php else: ?>
                                <div class="form-value"><em>No signature available</em></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="form-row">
                            <div class="form-label">Disahkan Oleh:</div>
                            <div class="form-value"><strong>DATO DR. ANDERSON TIONG ING HENG</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Jawatan:</div>
                            <div class="form-value"><strong>PENGURUS BESAR</strong></div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Tarikh:</div>
                            <div class="form-value">
                                <strong><?php echo isset($application['gm_final_date']) ? date('d-m-Y', strtotime($application['gm_final_date'])) : '-'; ?></strong>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-label">Tandatangan:</div>
                            <?php if (!empty($application['gm_final_signature'])): ?>
                                <div>
                                    <img src="<?php echo $application['gm_final_signature']; ?>" alt="GM Final Signature" class="signature-img">
                                </div>
                            <?php else: ?>
                                <div class="form-value"><em>No signature available</em></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="print-btn">
            <button class="btn btn-primary" onclick="window.print();">
                <i class="bi bi-printer me-1"></i> Print this document
            </button>
            <a href="dashboard.php" class="btn btn-secondary ms-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <script>
         Auto-print when page loads (uncomment if needed)
         window.onload = function() {
             window.print();
         };
    </script>
</body>
</html>