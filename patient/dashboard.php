<?php
// ============================================================
// patient/dashboard.php - Paneli kryesor i pacientit
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

// Merr takimet e ardhshme (pending + confirmed)
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

// Merr recetat e fundit
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

// Statistika të shpejta
$stats = [
    'total'     => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ?", [$patientId])['c'],
    'upcoming'  => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status IN (?,?) AND appointment_date >= CURDATE()", [$patientId, STATUS_PENDING, STATUS_CONFIRMED])['c'],
    'completed' => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status = ?", [$patientId, STATUS_COMPLETED])['c'],
    'rx_count'  => db()->fetchOne("SELECT COUNT(*) as c FROM prescriptions WHERE patient_id = ?", [$patientId])['c'],
];


$pageTitle  = 'Paneli Im';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php
// Sidebar i pacientit
$sidebarRole = 'patient';
include BASE_PATH . '/includes/sidebar.php';
?>
<main class="main-content">
    <div class="content-header">
        <h1>Mirë se erdhe, <?= e(explode(' ', $_SESSION['name'])[0]) ?>!</h1>
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">+ Rezervo Takim të Ri</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Kartelat statistikore -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['total'] ?></div>
                <div class="stat-label">Takime Gjithsej</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">&#9989;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['upcoming'] ?></div>
                <div class="stat-label">Takime të Ardhshme</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">&#128203;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['completed'] ?></div>
                <div class="stat-label">Vizita të Kryera</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">&#128138;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['rx_count'] ?></div>
                <div class="stat-label">Receta</div>
            </div>
        </div>
    </div>

    <!-- Takimet e ardhshme -->
    <div class="data-section">
        <div class="data-section-header">
            <h3>Takimet e Ardhshme</h3>
            <a href="<?= BASE_URL ?>/patient/appointments.php" class="btn btn-outline btn-sm">Shiko të gjitha</a>
        </div>
        <?php if (empty($upcomingAppointments)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128197;</div>
                <h3>Nuk keni takime të ardhshme</h3>
                <p>Rezervoni takimin tuaj të parë tani.</p>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">Rezervo Takim</a>
            </div>
        <?php else: ?>
            <div style="padding:16px;">
            <?php foreach ($upcomingAppointments as $appt): ?>
            <?php
                $dateParts  = explode('-', $appt['appointment_date']);
                $monthNames = MONTHS_SQ;
                $day        = (int)$dateParts[2];
                $monthName  = $monthNames[(int)$dateParts[1]];
            ?>
            <div class="appointment-card status-<?= e($appt['status']) ?>">
                <div class="appt-date-box">
                    <div class="appt-date-day"><?= $day ?></div>
                    <div class="appt-date-month"><?= mb_substr($monthName, 0, 3) ?></div>
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
            </div>
        <?php endif; ?>
    </div>

    <!-- Recetat e fundit -->
    <?php if (!empty($recentPrescriptions)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Recetat e Fundit</h3>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php" class="btn btn-outline btn-sm">Shiko të gjitha</a>
        </div>
        <div style="padding:16px;">
        <?php foreach ($recentPrescriptions as $rx): ?>
        <div class="prescription-card">
            <div class="rx-icon">&#128138;</div>
            <div class="rx-info">
                <h4><?= e($rx['service_name']) ?></h4>
                <p>Dr. <?= e($rx['doctor_name']) ?> · <?= formatDateSq($rx['appointment_date']) ?></p>
            </div>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php?download=<?= (int)$rx['id'] ?>"
               class="btn btn-primary btn-sm">&#8595; Shkarko</a>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>
<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';