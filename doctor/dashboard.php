<?php
// ============================================================
// doctor/dashboard.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$today    = date('Y-m-d');

$todayAppointments = getAppointmentsByDoctor($doctorId, $today);

$nextWeekAppointments = db()->fetchAll(
    "SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, s.name AS service_name
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.doctor_id = ? AND a.appointment_date > ? AND a.appointment_date <= DATE_ADD(?, INTERVAL 7 DAY)
       AND a.status IN (?, ?)
     ORDER BY a.appointment_date ASC, a.time_slot ASC",
    [$doctorId, $today, $today, STATUS_PENDING, STATUS_CONFIRMED]
);

$stats = [
    'today_count'    => count($todayAppointments),
    'week_count'     => count($nextWeekAppointments),
    'total_patients' => db()->fetchOne(
        "SELECT COUNT(DISTINCT patient_id) as c FROM appointments WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
    'rx_count' => db()->fetchOne(
        "SELECT COUNT(*) as c FROM prescriptions WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
];

// First and last appointment times today
$firstSlot = !empty($todayAppointments) ? $todayAppointments[0]['time_slot'] : null;
$lastSlot  = !empty($todayAppointments) ? end($todayAppointments)['time_slot'] : null;

$dayNames = DAYS_SQ;
$todayDayName = $dayNames[date('l')] ?? date('l');

$pageTitle = 'Paneli Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow"><?= e($todayDayName) ?>, <?= formatDateSq($today) ?></div>
            <h1>Mirë se erdhe, Dr. <em class="serif-italic"><?= e(explode(' ', $_SESSION['name'])[0]) ?></em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?php if ($firstSlot): ?>
                    Sot keni <?= (int)$stats['today_count'] ?> takim<?= $stats['today_count'] !== 1 ? 'e' : '' ?> — i pari në <?= e($firstSlot) ?>, i fundit në <?= e($lastSlot) ?>.
                <?php else: ?>
                    Nuk keni takime sot.
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>/doctor/upload_rx.php" class="btn btn-cta">+ Lësho recetë</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Stats row -->
    <div class="dr-stats-row">
        <div class="dr-stat">
            <div class="lab">Sot</div>
            <div class="num"><em><?= (int)$stats['today_count'] ?></em></div>
            <div class="meta">takime të planifikuara</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Këtë javë</div>
            <div class="num"><?= (int)$stats['week_count'] ?></div>
            <div class="meta">takime të ardhshme</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Pacientë gjithsej</div>
            <div class="num"><?= (int)$stats['total_patients'] ?></div>
            <div class="meta">në bazën time</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Receta të lëshuara</div>
            <div class="num"><?= (int)$stats['rx_count'] ?></div>
            <div class="meta">gjithsej</div>
        </div>
    </div>

    <!-- Today's appointments -->
    <div class="data-section">
        <div class="data-section-header">
            <h3>Sot — <?= formatDateSq($today) ?></h3>
            <a href="<?= BASE_URL ?>/doctor/schedule.php" class="btn btn-ghost btn-sm">Shiko orarin javor →</a>
        </div>

        <?php if (empty($todayAppointments)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk keni takime sot</h3>
            </div>
        <?php else: ?>
            <?php foreach ($todayAppointments as $appt): ?>
            <div class="appointment-card status-<?= e($appt['status']) ?>">
                <div class="appt-date-box">
                    <div class="appt-date-day"><?= e($appt['time_slot']) ?></div>
                    <div class="appt-date-month">— <?= date('H:i', strtotime($appt['time_slot']) + 1800) ?></div>
                </div>
                <div class="appt-info">
                    <h4><?= e($appt['patient_name']) ?></h4>
                    <p><?= e($appt['service_name']) ?>
                        <?php if (!empty($appt['patient_phone'])): ?>
                        · <span style="color:var(--ink-3)"><?= e($appt['patient_phone']) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="appt-actions">
                    <?= getStatusBadge($appt['status']) ?>
                    <a href="<?= BASE_URL ?>/doctor/upload_rx.php?appointment_id=<?= (int)$appt['id'] ?>"
                       class="btn btn-outline btn-sm">+ Recetë</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick actions -->
    <div class="quick-actions">
        <a href="<?= BASE_URL ?>/doctor/patients.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
            </span>
            <span class="qa-text">
                <h4>Pacientët</h4>
                <p><?= (int)$stats['total_patients'] ?> në bazën e të dhënave</p>
            </span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/schedule.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <span class="qa-text">
                <h4>Orari javor</h4>
                <p>Menaxho disponueshmërinë</p>
            </span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/upload_rx.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
            </span>
            <span class="qa-text">
                <h4>Lësho recetë</h4>
                <p>Ngarko PDF për pacientin</p>
            </span>
        </a>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
