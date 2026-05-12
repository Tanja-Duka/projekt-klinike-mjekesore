<?php
// ============================================================
// patient/appointments.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

$tab = in_array($_GET['tab'] ?? '', ['active', 'history']) ? clean($_GET['tab']) : 'active';

if ($tab === 'active') {
    $appointments = db()->fetchAll(
        "SELECT a.*, u.name AS doctor_name, u.specialization,
                s.name AS service_name, s.price
         FROM appointments a
         JOIN users u ON a.doctor_id = u.id
         JOIN services s ON a.service_id = s.id
         WHERE a.patient_id = ? AND a.status IN (?, ?)
         ORDER BY a.appointment_date ASC, a.time_slot ASC",
        [$patientId, STATUS_PENDING, STATUS_CONFIRMED]
    );
} else {
    $appointments = db()->fetchAll(
        "SELECT a.*, u.name AS doctor_name, u.specialization,
                s.name AS service_name, s.price
         FROM appointments a
         JOIN users u ON a.doctor_id = u.id
         JOIN services s ON a.service_id = s.id
         WHERE a.patient_id = ? AND a.status IN (?, ?)
         ORDER BY a.appointment_date DESC, a.time_slot DESC",
        [$patientId, STATUS_COMPLETED, STATUS_CANCELLED]
    );
}

// Group by year for visual sections
$byYear = [];
foreach ($appointments as $a) {
    $y = substr($a['appointment_date'], 0, 4);
    $byYear[$y][] = $a;
}
krsort($byYear);

$pageTitle = 'Takimet e Mia — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Takimet e mia</div>
            <h1>Historiku <em class="serif-italic">i takimeve</em>.</h1>
        </div>
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">Rezervo të Re →</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Filter bar -->
    <div class="filter-bar">
        <a href="?tab=active"
           class="filter-chip <?= $tab === 'active'  ? 'active' : '' ?>">Aktive</a>
        <a href="?tab=history"
           class="filter-chip <?= $tab === 'history' ? 'active' : '' ?>">Histori</a>
    </div>

    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <h3><?= $tab === 'active' ? 'Nuk keni takime aktive' : 'Nuk keni histori takimesh' ?></h3>
            <?php if ($tab === 'active'): ?>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta" style="margin-top:16px;">Rezervo Takim →</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <?php foreach ($byYear as $year => $appts): ?>
        <div class="data-section">
            <div class="data-section-header">
                <h3><?= e($year) ?></h3>
                <span class="eyebrow" style="font-size:0.7rem;"><?= count($appts) ?> takim<?= count($appts) !== 1 ? 'e' : '' ?></span>
            </div>

            <?php foreach ($appts as $appt):
                $d = explode('-', $appt['appointment_date']);
                $monthNames = MONTHS_SQ;
                $day = (int)$d[2];
                $mon = mb_substr($monthNames[(int)$d[1]], 0, 3);
            ?>
            <div class="appointment-card status-<?= e($appt['status']) ?>">
                <div class="appt-date-box">
                    <div class="appt-date-day"><?= $day ?></div>
                    <div class="appt-date-month"><?= $mon ?></div>
                </div>
                <div class="appt-info">
                    <h4><?= e($appt['doctor_name']) ?>
                        <?php if (!empty($appt['specialization'])): ?>
                        <span style="font-weight:400;color:var(--ink-3);font-size:0.85em;">· <?= e($appt['specialization']) ?></span>
                        <?php endif; ?>
                    </h4>
                    <p><?= e($appt['service_name']) ?> · Ora <?= e($appt['time_slot']) ?> · <?= formatPrice((float)$appt['price']) ?></p>
                </div>
                <div class="appt-actions">
                    <?= getStatusBadge($appt['status']) ?>
                    <?php if (in_array($appt['status'], [STATUS_PENDING, STATUS_CONFIRMED])): ?>
                    <button class="btn btn-danger btn-sm cancel-btn"
                            data-id="<?= (int)$appt['id'] ?>"
                            data-csrf="<?= e(getCsrfToken()) ?>">Anulo</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>

</main>
</div>

<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';
?>
