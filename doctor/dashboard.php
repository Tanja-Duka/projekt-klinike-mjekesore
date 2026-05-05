<?php
// ============================================================
// doctor/dashboard.php - Paneli kryesor i mjekut
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$today    = date('Y-m-d');

// Takimet e sotme
$todayAppointments = getAppointmentsByDoctor($doctorId, $today);

// Takimet e 7 ditëve të ardhshme (pa sot)
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

// Statistika
$stats = [
    'today_count'    => count($todayAppointments),
    'week_count'     => count($nextWeekAppointments),
    'total_patients' => db()->fetchOne(
        "SELECT COUNT(DISTINCT patient_id) as c FROM appointments WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
    'completed'      => db()->fetchOne(
        "SELECT COUNT(*) as c FROM appointments WHERE doctor_id = ? AND status = ?",
        [$doctorId, STATUS_COMPLETED]
    )['c'],
];

$pageTitle  = 'Paneli Im';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Mirë se erdhe, Dr. <?= e(explode(' ', $_SESSION['name'])[0]) ?>!</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Kartelat statistikore -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['today_count'] ?></div>
                <div class="stat-label">Takime Sot</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">&#128338;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['week_count'] ?></div>
                <div class="stat-label">Takime këtë Javë</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">&#128101;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['total_patients'] ?></div>
                <div class="stat-label">Pacientë Gjithsej</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#9989;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['completed'] ?></div>
                <div class="stat-label">Vizita të Kryera</div>
            </div>
        </div>
    </div>

    <!-- Takimet e sotme -->
    <div class="data-section">
        <div class="data-section-header">
            <h3>Takimet e Sotme — <?= formatDateSq($today) ?></h3>
            <a href="<?= BASE_URL ?>/doctor/schedule.php" class="btn btn-outline btn-sm">Shiko Orarin</a>
        </div>
        <?php if (empty($todayAppointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#9989;</div>
                <h3>Nuk keni takime sot</h3>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Ora</th><th>Pacienti</th><th>Shërbimi</th><th>Statusi</th><th>Veprime</th></tr></thead>
                <tbody>
                <?php foreach ($todayAppointments as $appt): ?>
                <tr>
                    <td><strong><?= e($appt['time_slot']) ?></strong></td>
                    <td>
                        <?= e($appt['patient_name']) ?><br>
                        <small class="text-muted"><?= e($appt['patient_phone'] ?? '') ?></small>
                    </td>
                    <td><?= e($appt['service_name']) ?></td>
                    <td><?= getStatusBadge($appt['status']) ?></td>
                    <td>
                        <a href="<?= BASE_URL ?>/doctor/upload_rx.php?appointment_id=<?= (int)$appt['id'] ?>"
                           class="btn btn-primary btn-sm">+ Recetë</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Takimet e javës -->
    <?php if (!empty($nextWeekAppointments)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>7 Ditët e Ardhshme</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Data</th><th>Ora</th><th>Pacienti</th><th>Shërbimi</th><th>Statusi</th></tr></thead>
                <tbody>
                <?php foreach ($nextWeekAppointments as $appt): ?>
                <tr>
                    <td><?= formatDateSq($appt['appointment_date']) ?></td>
                    <td><?= e($appt['time_slot']) ?></td>
                    <td><?= e($appt['patient_name']) ?></td>
                    <td><?= e($appt['service_name']) ?></td>
                    <td><?= getStatusBadge($appt['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';


