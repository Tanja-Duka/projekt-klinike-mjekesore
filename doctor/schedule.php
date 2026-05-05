<?php
// ============================================================
// doctor/schedule.php - Orari i vizitave (pamje lexim-vetëm)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();

// Merr orarin e punës nga tabela schedules
$schedule = getDoctorSchedule($doctorId);

// Merr takimet e 14 ditëve të ardhshme me emrat e pacientëve
$upcomingAppointments = db()->fetchAll(
    "SELECT a.appointment_date, a.time_slot, a.status,
            u.name AS patient_name, u.phone AS patient_phone,
            s.name AS service_name, a.id
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.doctor_id = ?
       AND a.appointment_date >= CURDATE()
       AND a.appointment_date <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
       AND a.status IN (?, ?)
     ORDER BY a.appointment_date ASC, a.time_slot ASC",
    [$doctorId, STATUS_PENDING, STATUS_CONFIRMED]
);

// Grupo takimet sipas datës për t'i shfaqur si kalendarik
$appointmentsByDate = [];
foreach ($upcomingAppointments as $appt) {
    $appointmentsByDate[$appt['appointment_date']][] = $appt;
}

$days = DAYS_SQ;


$pageTitle  = 'Orari Im';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Orari Im</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Oraret e punës -->
    <div class="data-section mb-24">
        <div class="data-section-header">
            <h3>Oraret e Punës (sipas ditëve)</h3>
        </div>
        <?php if (empty($schedule)): ?>
            <div class="empty-state"><p>Nuk keni orar të caktuar ende. Kontaktoni administratorin.</p></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Dita</th><th>Ora Fillimit</th><th>Ora Mbarimit</th></tr></thead>
                <tbody>
                <?php foreach ($schedule as $s): ?>
                <tr>
                    <td><strong><?= e($days[$s['day_of_week']] ?? $s['day_of_week']) ?></strong></td>
                    <td><?= e($s['start_time']) ?></td>
                    <td><?= e($s['end_time']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Takimet e 14 ditëve të ardhshme -->
    <div class="data-section">
        <div class="data-section-header">
            <h3>Takimet e 14 Ditëve të Ardhshme</h3>
        </div>
        <?php if (empty($appointmentsByDate)): ?>
            <div class="empty-state"><p>Nuk keni takime të planifikuara.</p></div>
        <?php else: ?>
            <div style="padding:16px;">
            <?php foreach ($appointmentsByDate as $date => $appts): ?>
                <h4 style="margin-bottom:10px;color:var(--color-primary);"><?= formatDateSq($date) ?></h4>
                <?php foreach ($appts as $appt): ?>
                <div class="appointment-card mb-8">
                    <div class="appt-date-box" style="background:rgba(30,107,114,0.08);">
                        <div class="appt-date-day" style="font-size:1.1rem;"><?= e($appt['time_slot']) ?></div>
                    </div>
                    <div class="appt-info">
                        <h4><?= e($appt['patient_name']) ?></h4>
                        <p><?= e($appt['service_name']) ?> · <?= e($appt['patient_phone'] ?? '') ?></p>
                    </div>
                    <?= getStatusBadge($appt['status']) ?>
                </div>
                <?php endforeach; ?>
                <hr style="border:none;border-top:1px solid var(--color-border);margin:16px 0;">
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';

