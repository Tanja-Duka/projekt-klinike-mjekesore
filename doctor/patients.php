<?php
// ============================================================
// doctor/patients.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();

$viewPatientId  = cleanInt($_GET['patient_id'] ?? 0);
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

$patients = db()->fetchAll(
    "SELECT u.id, u.name, u.email, u.phone, u.blood_type,
            u.date_of_birth,
            MAX(a.appointment_date) AS last_visit,
            MIN(CASE WHEN a.status IN (?,?) AND a.appointment_date >= CURDATE() THEN a.appointment_date END) AS next_visit,
            COUNT(a.id) AS total_visits,
            SUM(CASE WHEN a.status IN (?,?) AND a.appointment_date >= CURDATE() THEN 1 ELSE 0 END) AS active_count
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     WHERE a.doctor_id = ?
     GROUP BY u.id, u.name, u.email, u.phone, u.blood_type, u.date_of_birth
     ORDER BY last_visit DESC",
    [STATUS_PENDING, STATUS_CONFIRMED, STATUS_PENDING, STATUS_CONFIRMED, $doctorId]
);

$totalPatients  = count($patients);
$activePatients = count(array_filter($patients, fn($p) => $p['active_count'] > 0));
$newThisMonth   = count(array_filter($patients, fn($p) =>
    $p['last_visit'] && substr($p['last_visit'], 0, 7) === date('Y-m')
));

$pageTitle = 'Pacientët e Mi — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <?php if ($selectedPatient): ?>

    <!-- Patient history view -->
    <div class="content-header">
        <div>
            <div class="eyebrow">Historia e pacientit</div>
            <h1><?= e($selectedPatient['name']) ?> <em class="serif-italic">— vizitat</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?= e($selectedPatient['email']) ?>
                <?php if (!empty($selectedPatient['phone'])): ?> · <?= e($selectedPatient['phone']) ?><?php endif; ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>/doctor/patients.php" class="btn btn-outline btn-sm">← Kthehu</a>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="data-section">
        <?php if (empty($patientHistory)): ?>
            <div class="empty-state">
                <h3>Nuk ka historik vizitash</h3>
            </div>
        <?php else: ?>
        <div class="data-section-body" style="padding:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Ora</th>
                        <th>Shërbimi</th>
                        <th>Statusi</th>
                        <th>Recetë</th>
                    </tr>
                </thead>
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
                               class="btn btn-outline btn-sm">+ Ngarko</a>
                        <?php else: ?>
                            <span style="color:var(--ink-3)">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <?php else: ?>

    <!-- Patient list view -->
    <div class="content-header">
        <div>
            <div class="eyebrow">Baza ime</div>
            <h1><?= $totalPatients ?> <em class="serif-italic">pacientë</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= $newThisMonth ?> të rinj këtë muaj · <?= $activePatients ?> me takime aktive.</p>
        </div>
        <div style="position:relative;min-width:260px;">
            <input class="form-control" id="patientSearch" placeholder="Kërko sipas emrit ose telefonit…">
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="filter-bar">
        <a href="?filter=all"    class="filter-chip <?= ($_GET['filter'] ?? 'all') === 'all'    ? 'active' : '' ?>">Të gjithë <span><?= $totalPatients ?></span></a>
        <a href="?filter=active" class="filter-chip <?= ($_GET['filter'] ?? '') === 'active' ? 'active' : '' ?>">Aktive <span><?= $activePatients ?></span></a>
        <a href="?filter=new"    class="filter-chip <?= ($_GET['filter'] ?? '') === 'new'    ? 'active' : '' ?>">Të rinj <span><?= $newThisMonth ?></span></a>
    </div>

    <?php if (empty($patients)): ?>
        <div class="empty-state">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/></svg>
            <h3>Nuk keni pacientë ende</h3>
        </div>
    <?php else: ?>
    <div class="data-section">
        <div class="data-section-body" style="padding:0;">
            <table class="data-table" id="patientTable">
                <thead>
                    <tr>
                        <th>Pacienti</th>
                        <th>Mosha</th>
                        <th>Vizita e fundit</th>
                        <th>Tjetra</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $p):
                    $age = $p['date_of_birth']
                        ? (int)((time() - strtotime($p['date_of_birth'])) / 31536000)
                        : null;
                    $isActive = $p['active_count'] > 0;
                    $isNew    = $p['last_visit'] && substr($p['last_visit'], 0, 7) === date('Y-m');
                    $filter   = $_GET['filter'] ?? 'all';
                    if ($filter === 'active' && !$isActive) continue;
                    if ($filter === 'new'    && !$isNew) continue;
                ?>
                <tr class="patient-row">
                    <td>
                        <div class="pname">
                            <span class="av"><?= mb_strtoupper(mb_substr($p['name'], 0, 1)) ?></span>
                            <div>
                                <strong><?= e($p['name']) ?></strong>
                                <span class="em"><?= e($p['phone'] ?? $p['email']) ?></span>
                            </div>
                        </div>
                    </td>
                    <td><?= $age ?? '—' ?></td>
                    <td><?= $p['last_visit'] ? formatDateSq($p['last_visit']) : '—' ?></td>
                    <td><?= $p['next_visit'] ? formatDateSq($p['next_visit']) : '—' ?></td>
                    <td>
                        <?php if ($isNew): ?>
                            <span class="status-badge status-pending">e re</span>
                        <?php elseif ($isActive): ?>
                            <span class="status-badge status-confirmed">aktive</span>
                        <?php else: ?>
                            <span class="status-badge status-completed">joaktive</span>
                        <?php endif; ?>
                    </td>
                    <td><a href="?patient_id=<?= (int)$p['id'] ?>" class="btn btn-ghost btn-sm">Hap →</a></td>
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

<?php include BASE_PATH . '/includes/footer.php'; ?>

<script>
const s = document.getElementById('patientSearch');
if (s) {
    s.addEventListener('input', () => {
        const q = s.value.toLowerCase();
        document.querySelectorAll('#patientTable .patient-row').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
        });
    });
}
</script>
