<?php
// ============================================================
// patient/appointments.php - Lista e të gjitha takimeve
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

// Filter: tab aktiv
$tab = in_array($_GET['tab'] ?? '', ['active', 'history']) ? clean($_GET['tab']) : 'active';

if ($tab === 'active') {
    $appointments = db()->fetchAll(
        "SELECT a.*, u.name AS doctor_name, u.specialization, u.photo_path AS doctor_photo,
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
        "SELECT a.*, u.name AS doctor_name, u.specialization, u.photo_path AS doctor_photo,
                s.name AS service_name, s.price
         FROM appointments a
         JOIN users u ON a.doctor_id = u.id
         JOIN services s ON a.service_id = s.id
         WHERE a.patient_id = ? AND a.status IN (?, ?)
         ORDER BY a.appointment_date DESC, a.time_slot DESC",
        [$patientId, STATUS_COMPLETED, STATUS_CANCELLED]
    );
}

$pageTitle  = 'Takimet e Mia';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Takimet e Mia</h1>
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">+ Rezervo të Re</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Tabs -->
    <div class="filter-tabs mb-24">
        <a href="?tab=active"  class="filter-tab <?= $tab === 'active'  ? 'active' : '' ?>">Aktive</a>
        <a href="?tab=history" class="filter-tab <?= $tab === 'history' ? 'active' : '' ?>">Histori</a>
    </div>

    <?php if (empty($appointments)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128197;</div>
            <h3><?= $tab === 'active' ? 'Nuk keni takime aktive' : 'Nuk keni histori takimesh' ?></h3>
            <?php if ($tab === 'active'): ?>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta mt-16">Rezervo Takim</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data &amp; Ora</th>
                        <th>Mjeku</th>
                        <th>Shërbimi</th>
                        <th>Çmimi</th>
                        <th>Statusi</th>
                        <th>Veprime</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($appointments as $appt): ?>
                    <tr>
                        <td>
                            <strong><?= formatDateSq($appt['appointment_date']) ?></strong><br>
                            <small class="text-muted">Ora <?= e($appt['time_slot']) ?></small>
                        </td>
                        <td>
                            <?= e($appt['doctor_name']) ?><br>
                            <small class="text-muted"><?= e($appt['specialization'] ?? '') ?></small>
                        </td>
                        <td><?= e($appt['service_name']) ?></td>
                        <td><?= formatPrice((float)$appt['price']) ?></td>
                        <td><?= getStatusBadge($appt['status']) ?></td>
                        <td>
                            <?php if (in_array($appt['status'], [STATUS_PENDING, STATUS_CONFIRMED])): ?>
                            <button class="btn btn-danger btn-sm cancel-btn"
                                    data-id="<?= (int)$appt['id'] ?>"
                                    data-csrf="<?= e(getCsrfToken()) ?>">Anulo</button>
                            <?php else: ?>
                            <span class="text-muted" style="font-size:0.82rem;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</div>
<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';

