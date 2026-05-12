<?php
// ============================================================
// doctor/schedule.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();

// Week offset (0 = this week, -1 = last, +1 = next)
$weekOffset = cleanInt($_GET['week'] ?? 0);

// Monday of the target week
$monday = new DateTime();
$monday->modify('monday this week');
$monday->modify(($weekOffset >= 0 ? '+' : '') . $weekOffset . ' weeks');

$sunday = clone $monday;
$sunday->modify('+6 days');

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = clone $monday;
    $d->modify("+$i days");
    $weekDays[] = $d;
}

// Fetch appointments for the week
$appointments = db()->fetchAll(
    "SELECT a.appointment_date, a.time_slot, a.status,
            u.name AS patient_name, s.name AS service_name, a.id
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.doctor_id = ?
       AND a.appointment_date >= ?
       AND a.appointment_date <= ?
       AND a.status IN (?, ?)
     ORDER BY a.time_slot ASC",
    [$doctorId, $monday->format('Y-m-d'), $sunday->format('Y-m-d'), STATUS_PENDING, STATUS_CONFIRMED]
);

// Index by date+time
$byDateSlot = [];
foreach ($appointments as $a) {
    $byDateSlot[$a['appointment_date']][$a['time_slot']] = $a;
}

// Working time slots to display
$timeSlots = ['09:00','10:00','11:00','13:00','14:00','15:00','16:00','17:00'];

$today = date('Y-m-d');
$dayLabels = ['Hën','Mar','Mër','Enj','Pre','Sht','Die'];

$totalAppts = count($appointments);

$pageTitle = 'Orari Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow"><?= $monday->format('j') ?> — <?= $sunday->format('j M Y') ?></div>
            <h1>Orari <em class="serif-italic">i javës</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= $totalAppts ?> takime gjithsej këtë javë.</p>
        </div>
        <div style="display:flex;gap:8px;">
            <a href="?week=<?= $weekOffset - 1 ?>" class="btn btn-outline btn-sm">‹ Java e kaluar</a>
            <a href="?week=0" class="btn btn-outline btn-sm">Sot</a>
            <a href="?week=<?= $weekOffset + 1 ?>" class="btn btn-outline btn-sm">Java tjetër ›</a>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="schedule-grid">
        <!-- Header row -->
        <div class="schedule-cell schedule-row-head">Ora</div>
        <?php foreach ($weekDays as $i => $day): ?>
        <div class="schedule-cell schedule-col-head <?= $day->format('Y-m-d') === $today ? 'today' : '' ?>">
            <?= $dayLabels[$i] ?> <strong><?= $day->format('j') ?></strong>
        </div>
        <?php endforeach; ?>

        <!-- Time rows -->
        <?php foreach ($timeSlots as $slot): ?>
        <div class="schedule-cell schedule-row-head"><?= e($slot) ?></div>
        <?php foreach ($weekDays as $day): ?>
        <?php
            $dateKey = $day->format('Y-m-d');
            $appt = $byDateSlot[$dateKey][$slot] ?? null;
        ?>
        <div class="schedule-cell">
            <?php if ($appt): ?>
            <div class="schedule-event <?= e($appt['status']) ?>">
                <strong><?= e(explode(' ', $appt['patient_name'])[0]) ?> <?= e(explode(' ', $appt['patient_name'])[1][0] ?? '') ?>.</strong><?= e($appt['service_name']) ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
        <?php endforeach; ?>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
