<?php
// ============================================================
// patient/prescriptions.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

// Secure file download
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

$prescriptions = getPrescriptionsByPatient($patientId);

$pageTitle = 'Recetat e Mia — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Recetat dixhitale</div>
            <h1>Recetat <em class="serif-italic">e mia</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Të gjitha recetat e lëshuara nga mjekët tuaj.</p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (empty($prescriptions)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            <h3>Nuk keni receta ende</h3>
            <p>Recetat tuaja dixhitale do të shfaqen këtu pas vizitave.</p>
        </div>
    <?php else: ?>
        <div class="rx-detail-grid">
        <?php foreach ($prescriptions as $rx): ?>
        <div class="rx-detail-card">
            <div class="rx-detail-head">
                <div>
                    <div class="eyebrow" style="font-size:0.68rem;"><?= formatDateSq($rx['appointment_date']) ?></div>
                    <h4><?= e($rx['service_name']) ?></h4>
                    <p style="color:var(--ink-3);font-size:0.85rem;margin-top:4px;">
                        Dr. <?= e($rx['doctor_name']) ?>
                        <?= !empty($rx['specialization']) ? '· ' . e($rx['specialization']) : '' ?>
                    </p>
                </div>
                <a href="<?= BASE_URL ?>/patient/prescriptions.php?download=<?= (int)$rx['id'] ?>"
                   class="btn btn-outline btn-sm">↓ Shkarko</a>
            </div>

            <?php if (!empty($rx['medications'])): ?>
            <div class="rx-detail-body">
                <div class="eyebrow" style="font-size:0.68rem;margin-bottom:10px;">Barnat</div>
                <?php
                $meds = is_string($rx['medications']) ? json_decode($rx['medications'], true) : $rx['medications'];
                if (is_array($meds)):
                    foreach ($meds as $med):
                ?>
                <div class="rx-med">
                    <strong><?= e($med['name'] ?? $med) ?></strong>
                    <?php if (!empty($med['dose'])): ?>
                    <span class="rx-med-dose"><?= e($med['dose']) ?></span>
                    <?php endif; ?>
                </div>
                <?php
                    endforeach;
                endif;
                ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($rx['notes'])): ?>
            <div class="rx-notes">
                <span class="eyebrow" style="font-size:0.68rem;">Shënime</span>
                <p><?= e($rx['notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
