<?php
// ============================================================
// patient/dashboard.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

$upcomingAppointments = db()->fetchAll(
    "SELECT a.*, u.name AS doctor_name, u.specialization, s.name AS service_name, s.price
     FROM appointments a
     JOIN users u ON a.doctor_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.patient_id = ? AND a.status IN (?, ?)
       AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC, a.time_slot ASC
     LIMIT 5",
    [$patientId, STATUS_PENDING, STATUS_CONFIRMED]
);

$recentPrescriptions = db()->fetchAll(
    "SELECT pr.*, u.name AS doctor_name, a.appointment_date, s.name AS service_name
     FROM prescriptions pr
     JOIN users u ON pr.doctor_id = u.id
     JOIN appointments a ON pr.appointment_id = a.id
     JOIN services s ON a.service_id = s.id
     WHERE pr.patient_id = ?
     ORDER BY pr.uploaded_at DESC
     LIMIT 3",
    [$patientId]
);

$stats = [
    'total'     => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ?", [$patientId])['c'],
    'upcoming'  => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status IN (?,?) AND appointment_date >= CURDATE()", [$patientId, STATUS_PENDING, STATUS_CONFIRMED])['c'],
    'completed' => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status = ?", [$patientId, STATUS_COMPLETED])['c'],
    'rx_count'  => db()->fetchOne("SELECT COUNT(*) as c FROM prescriptions WHERE patient_id = ?", [$patientId])['c'],
];

$pageTitle = 'Paneli Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Paneli i pacientit</div>
            <h1>Mirë se erdhe, <em class="serif-italic"><?= e(explode(' ', $_SESSION['name'])[0]) ?></em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Ja një përmbledhje e shpejtë e takimeve dhe recetave tuaja.</p>
        </div>
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">Rezervo Takim të Ri →</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Quick actions -->
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="quick-action">
            <div class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="M12 14v4M10 16h4"/></svg>
            </div>
            <div class="qa-text">
                <h4>Rezervo Takim</h4>
                <p>Zgjidh mjekun dhe orarin</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/patient/appointments.php" class="quick-action">
            <div class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            </div>
            <div class="qa-text">
                <h4>Takimet e Mia</h4>
                <p>Shiko historikun e vizitave</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/patient/prescriptions.php" class="quick-action">
            <div class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><circle cx="11" cy="14" r="2"/><path d="m13 16 2 2"/></svg>
            </div>
            <div class="qa-text">
                <h4>Recetat e Mia</h4>
                <p>Shkarko recetat dixhitale</p>
            </div>
        </a>
    </div>

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['total'] ?></div>
            <div class="stat-label">Takime Gjithsej</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['upcoming'] ?></div>
            <div class="stat-label">Takime të Ardhshme</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['completed'] ?></div>
            <div class="stat-label">Vizita të Kryera</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?= (int)$stats['rx_count'] ?></div>
            <div class="stat-label">Receta</div>
        </div>
    </div>

    <!-- Upcoming appointments -->
    <div class="data-section">
        <div class="data-section-header">
            <h3>Takimet e Ardhshme</h3>
            <a href="<?= BASE_URL ?>/patient/appointments.php" class="btn btn-outline btn-sm">Shiko të gjitha →</a>
        </div>

        <?php if (empty($upcomingAppointments)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk keni takime të ardhshme</h3>
                <p>Rezervoni takimin tuaj të parë tani.</p>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta" style="margin-top:16px;">Rezervo Takim →</a>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingAppointments as $appt):
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
                    <h4><?= e($appt['doctor_name']) ?></h4>
                    <p><?= e($appt['service_name']) ?> · Ora <?= e($appt['time_slot']) ?></p>
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
        <?php endif; ?>
    </div>

    <!-- Recent prescriptions -->
    <?php if (!empty($recentPrescriptions)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Recetat e Fundit</h3>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php" class="btn btn-outline btn-sm">Shiko të gjitha →</a>
        </div>

        <?php foreach ($recentPrescriptions as $rx): ?>
        <div class="appointment-card">
            <div class="appt-date-box" style="background:var(--bg-sunk);">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="22" height="22" style="color:var(--accent)"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            </div>
            <div class="appt-info">
                <h4><?= e($rx['service_name']) ?></h4>
                <p>Dr. <?= e($rx['doctor_name']) ?> · <?= formatDateSq($rx['appointment_date']) ?></p>
            </div>
            <div class="appt-actions">
                <a href="<?= BASE_URL ?>/patient/prescriptions.php?download=<?= (int)$rx['id'] ?>"
                   class="btn btn-outline btn-sm">↓ Shkarko</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>
</div>

<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';
?>
