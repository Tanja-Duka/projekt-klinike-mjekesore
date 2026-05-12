<?php
// ============================================================
// admin/dashboard.php - Paneli kryesor i administratorit
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$stats = getDashboardStats();

$recentAppointments = db()->fetchAll(
    "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
     FROM appointments a
     JOIN users p ON a.patient_id = p.id
     JOIN users d ON a.doctor_id  = d.id
     JOIN services s ON a.service_id = s.id
     ORDER BY a.created_at DESC
     LIMIT 10"
);

$recentQueries = db()->fetchAll(
    "SELECT * FROM contact_queries ORDER BY created_at DESC LIMIT 5"
);

$today = date('Y-m-d');
$dayNames = DAYS_SQ;
$todayDayName = $dayNames[date('l')] ?? date('l');

$pageTitle = 'Paneli Admin — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow"><?= e($todayDayName) ?>, <?= formatDateSq($today) ?></div>
            <h1>Paneli i <em class="serif-italic">Administratorit</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Pasqyrë e gjendjes së klinikës sot.</p>
        </div>
        <a href="<?= BASE_URL ?>/doctor/admin/doctors.php" class="btn btn-cta">+ Shto Mjek</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Stats row -->
    <div class="dr-stats-row">
        <div class="dr-stat">
            <div class="lab">Mjekë</div>
            <div class="num"><em><?= (int)$stats['total_doctors'] ?></em></div>
            <div class="meta">aktivë në sistem</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Pacientë</div>
            <div class="num"><?= (int)$stats['total_patients'] ?></div>
            <div class="meta">të regjistruar</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Sot</div>
            <div class="num"><?= (int)$stats['appointments_today'] ?></div>
            <div class="meta">takime të planifikuara</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Mesazhe</div>
            <div class="num"><?= (int)$stats['pending_queries'] ?></div>
            <div class="meta">të palexuara</div>
        </div>
    </div>

    <!-- Takimet e fundit -->
    <div class="data-section" style="margin-bottom:32px;">
        <div class="data-section-header">
            <h3>Takimet e Fundit</h3>
            <a href="<?= BASE_URL ?>/doctor/admin/appointments.php" class="btn btn-ghost btn-sm">Shiko të gjitha →</a>
        </div>

        <?php if (empty($recentAppointments)): ?>
            <div class="empty-state">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk ka takime ende</h3>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data &amp; Ora</th>
                        <th>Pacienti</th>
                        <th>Mjeku</th>
                        <th>Shërbimi</th>
                        <th>Statusi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentAppointments as $appt): ?>
                <tr>
                    <td>
                        <strong><?= formatDateSq($appt['appointment_date']) ?></strong>
                        <br><small style="color:var(--ink-3)"><?= e($appt['time_slot']) ?></small>
                    </td>
                    <td><?= e($appt['patient_name']) ?></td>
                    <td><?= e($appt['doctor_name']) ?></td>
                    <td><?= e($appt['service_name']) ?></td>
                    <td><?= getStatusBadge($appt['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Mesazhet e kontaktit -->
    <?php if (!empty($recentQueries)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Mesazhet e Kontaktit</h3>
            <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php" class="btn btn-ghost btn-sm">Shiko të gjitha →</a>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Emri &amp; Email</th>
                        <th>Subjekti</th>
                        <th>Data</th>
                        <th>Statusi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentQueries as $q): ?>
                <tr>
                    <td>
                        <strong><?= e($q['name']) ?></strong>
                        <br><small style="color:var(--ink-3)"><?= e($q['email']) ?></small>
                    </td>
                    <td><?= e($q['subject']) ?></td>
                    <td><?= formatDateTimeSq($q['created_at']) ?></td>
                    <td>
                        <span class="status-badge <?= $q['status'] === QUERY_UNREAD ? 'status-pending' : ($q['status'] === QUERY_RESOLVED ? 'status-completed' : 'status-confirmed') ?>">
                            <?= $q['status'] === QUERY_UNREAD ? 'E palexuar' : ($q['status'] === QUERY_RESOLVED ? 'Zgjidhur' : 'Lexuar') ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- Quick links -->
    <div class="quick-actions" style="margin-top:32px;">
        <a href="<?= BASE_URL ?>/doctor/admin/doctors.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
            </span>
            <span class="qa-text">
                <h4>Mjekët</h4>
                <p><?= (int)$stats['total_doctors'] ?> aktivë</p>
            </span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/patients.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><circle cx="12" cy="8" r="4"/><path d="M4 20v-2a8 8 0 0 1 16 0v2"/></svg>
            </span>
            <span class="qa-text">
                <h4>Pacientët</h4>
                <p><?= (int)$stats['total_patients'] ?> të regjistruar</p>
            </span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/prices.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><rect x="2" y="6" width="20" height="12" rx="2"/><path d="M12 12h.01M6 12h.01M18 12h.01"/></svg>
            </span>
            <span class="qa-text">
                <h4>Shërbime &amp; Çmime</h4>
                <p>Menaxho katalogun</p>
            </span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php" class="quick-action">
            <span class="qa-mark">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            </span>
            <span class="qa-text">
                <h4>Mesazhet</h4>
                <p><?= (int)$stats['pending_queries'] ?> të palexuara</p>
            </span>
        </a>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
