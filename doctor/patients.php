<?php
// ============================================================
// doctor/patients.php - Historia e pacientëve
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();

// Nëse klikohet mbi pacient → shfaq historinë e tij
$viewPatientId = cleanInt($_GET['patient_id'] ?? 0);
$patientHistory = [];
$selectedPatient = null;

if ($viewPatientId > 0) {
    $selectedPatient = getPatientById($viewPatientId);
    if ($selectedPatient) {
        $patientHistory = db()->fetchAll(
            "SELECT a.*, s.name AS service_name, s.price,
                    pr.id AS prescription_id
             FROM appointments a
             JOIN services s ON a.service_id = s.id
             LEFT JOIN prescriptions pr ON pr.appointment_id = a.id AND pr.doctor_id = ?
             WHERE a.doctor_id = ? AND a.patient_id = ?
             ORDER BY a.appointment_date DESC, a.time_slot DESC",
            [$doctorId, $doctorId, $viewPatientId]
        );
    }
}

// Lista e pacientëve unikë (me vizitën e fundit)
$patients = db()->fetchAll(
    "SELECT u.id, u.name, u.email, u.phone, u.blood_type,
            MAX(a.appointment_date) AS last_visit,
            COUNT(a.id) AS total_visits
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id = ?
     GROUP BY u.id, u.name, u.email, u.phone, u.blood_type
     ORDER BY last_visit DESC",
    [$doctorId]
);




$pageTitle  = 'Pacientët e Mi';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Pacientët e Mi</h1>
    </div>

    <?php if ($selectedPatient && !empty($patientHistory)): ?>
    <!-- Histori e pacientit -->
    <div class="mb-16">
        <a href="<?= BASE_URL ?>/doctor/patients.php" class="btn btn-outline btn-sm">&#8592; Kthehu</a>
    </div>
    <div class="dashboard-form">
        <h3><?= e($selectedPatient['name']) ?> — Historia e Vizitave</h3>
        <p class="text-muted">Email: <?= e($selectedPatient['email']) ?> · Tel: <?= e($selectedPatient['phone'] ?? '—') ?></p>
    </div>
    <div class="data-section">
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Data</th><th>Ora</th><th>Shërbimi</th><th>Statusi</th><th>Recetë</th></tr></thead>
                <tbody>
                <?php foreach ($patientHistory as $h): ?>
                <tr>
                    <td><?= formatDateSq($h['appointment_date']) ?></td>
                    <td><?= e($h['time_slot']) ?></td>
                    <td><?= e($h['service_name']) ?> — <?= formatPrice((float)$h['price']) ?></td>
                    <td><?= getStatusBadge($h['status']) ?></td>
                    <td>
                        <?php if ($h['prescription_id']): ?>
                            <span class="status-badge status-completed">Ka recetë</span>
                        <?php elseif ($h['status'] === STATUS_COMPLETED): ?>
                            <a href="<?= BASE_URL ?>/doctor/upload_rx.php?appointment_id=<?= (int)$h['id'] ?>"
                               class="btn btn-primary btn-sm">+ Ngarko</a>
                        <?php else: ?>
                            <span class="text-muted">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <!-- Lista e pacientëve -->
    <?php if (empty($patients)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">&#128101;</div>
            <h3>Nuk keni pacientë ende</h3>
        </div>
    <?php else: ?>
    <div class="data-section">
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Pacienti</th><th>Telefon</th><th>Grupi i Gjakut</th><th>Vizita Gjithsej</th><th>Vizita e Fundit</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($patients as $p): ?>
                <tr>
                    <td>
                        <strong><?= e($p['name']) ?></strong><br>
                        <small class="text-muted"><?= e($p['email']) ?></small>
                    </td>
                    <td><?= e($p['phone'] ?? '—') ?></td>
                    <td><?= e($p['blood_type'] ?? '—') ?></td>
                    <td><?= (int)$p['total_visits'] ?></td>
                    <td><?= formatDateSq($p['last_visit']) ?></td>
                    <td>
                        <a href="?patient_id=<?= (int)$p['id'] ?>" class="btn btn-outline btn-sm">Historia</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';

