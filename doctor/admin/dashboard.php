<?php
// ============================================================
// admin/dashboard.php - Paneli kryesor i administratorit
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Kartelat kryesore të statistikave
$stats = getDashboardStats();

// 10 takimet e fundit (të gjitha statuset)
$recentAppointments = db()->fetchAll(
    "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
     FROM appointments a
     JOIN users p ON a.patient_id = p.id
     JOIN users d ON a.doctor_id  = d.id
     JOIN services s ON a.service_id = s.id
     ORDER BY a.created_at DESC
     LIMIT 10"
);

// 5 query-t e fundit nga kontakti (të palexuara më parë)
$recentQueries = db()->fetchAll(
    "SELECT * FROM contact_queries ORDER BY created_at DESC LIMIT 5"
);

$pageTitle  = 'Paneli i Administratorit';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Paneli i Administratorit</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">&#128101;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['total_doctors'] ?></div>
                <div class="stat-label">Mjekë Aktivë</div>
            </div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">&#128101;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['total_patients'] ?></div>
                <div class="stat-label">Pacientë</div>
            </div>
        </div>
        <div class="stat-card orange">
            <div class="stat-icon">&#128197;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['appointments_today'] ?></div>
                <div class="stat-label">Takime Sot</div>
            </div>
        </div>
        <div class="stat-card red">
            <div class="stat-icon">&#9993;</div>
            <div class="stat-info">
                <div class="stat-number"><?= (int)$stats['pending_queries'] ?></div>
                <div class="stat-label">Mesazhe të Reja</div>
            </div>
        </div>
    </div>

    <!-- 10 takimet e fundit -->
    <div class="data-section mb-24">
        <div class="data-section-header">
            <h3>Takimet e Fundit</h3>
            <a href="<?= BASE_URL ?>/doctor/admin/appointments.php" class="btn btn-outline btn-sm">Shiko të gjitha</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Data</th><th>Pacienti</th><th>Mjeku</th><th>Shërbimi</th><th>Statusi</th></tr></thead>
                <tbody>
                <?php foreach ($recentAppointments as $appt): ?>
                <tr>
                    <td><?= formatDateSq($appt['appointment_date']) ?> <?= e($appt['time_slot']) ?></td>
                    <td><?= e($appt['patient_name']) ?></td>
                    <td><?= e($appt['doctor_name']) ?></td>
                    <td><?= e($appt['service_name']) ?></td>
                    <td><?= getStatusBadge($appt['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Mesazhet e fundit -->
    <?php if (!empty($recentQueries)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Mesazhet e Kontaktit</h3>
            <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php" class="btn btn-outline btn-sm">Shiko të gjitha</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Emri</th><th>Subjekti</th><th>Data</th><th>Statusi</th></tr></thead>
                <tbody>
                <?php foreach ($recentQueries as $q): ?>
                <tr>
                    <td><?= e($q['name']) ?><br><small class="text-muted"><?= e($q['email']) ?></small></td>
                    <td><?= e($q['subject']) ?></td>
                    <td><?= formatDateTimeSq($q['created_at']) ?></td>
                    <td>
                        <span class="status-badge <?= $q['status'] === 'unread' ? 'status-pending' : ($q['status'] === 'resolved' ? 'status-completed' : 'status-confirmed') ?>">
                            <?= e($q['status']) ?>
                        </span>
                    </td>
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

