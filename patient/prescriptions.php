$prescriptions = getPrescriptionsByPatient($patientId);

$pageTitle  = 'Recetat e Mia';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Recetat e Mia</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (empty($prescriptions)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128138;</div>
            <h3>Nuk keni receta ende</h3>
            <p>Recetat tuaja dixhitale do të shfaqen këtu pas vizitave.</p>
        </div>
    <?php else: ?>
        <div>
        <?php foreach ($prescriptions as $rx): ?>
        <div class="prescription-card">
            <div class="rx-icon">&#128138;</div>
            <div class="rx-info">
                <h4><?= e($rx['service_name']) ?></h4>
                <p>
                    Dr. <?= e($rx['doctor_name']) ?>
                    <?= !empty($rx['specialization']) ? '· ' . e($rx['specialization']) : '' ?>
                    · <?= formatDateSq($rx['appointment_date']) ?>
                </p>
            </div>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php?download=<?= (int)$rx['id'] ?>"
               class="btn btn-primary btn-sm">&#8595; Shkarko</a>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';

<?php
// ============================================================
// patient/prescriptions.php - Recetat dixhitale të pacientit
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

// Veprim shkarkimi i sigurt
if (isset($_GET['download'])) {
    $prescriptionId = cleanInt($_GET['download']);

    $rx = db()->fetchOne(
        "SELECT * FROM prescriptions WHERE id = ? AND patient_id = ?",
        [$prescriptionId, $patientId]
    );

    if (!$rx || !file_exists(BASE_PATH . '/' . $rx['file_path'])) {
        setFlashMessage('error', 'Receta nuk u gjet.');
        redirect(BASE_URL . '/patient/prescriptions.php');
    }

    // Shërbej skedarin me headers të sigurt
    $filePath = BASE_PATH . '/' . $rx['file_path'];
    $ext      = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimeMap  = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png'];
    $mime     = $mimeMap[$ext] ?? 'application/octet-stream';

    header('Content-Type: ' . $mime);
    header('Content-Disposition: attachment; filename="receta_' . $prescriptionId . '.' . $ext . '"');
    header('Content-Length: ' . filesize($filePath));
    header('X-Content-Type-Options: nosniff');
    readfile($filePath);
    exit;
}

// Merr të gjitha recetat e pacientit
$prescriptions = getPrescriptionsByPatient($patientId);

$pageTitle = 'Recetat e Mia';
