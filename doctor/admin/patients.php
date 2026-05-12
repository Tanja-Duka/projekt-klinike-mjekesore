<?php
// ============================================================
// admin/patients.php - Menaxho pacientët
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Toggle aktivizim / çaktivizim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && clean($_POST['action'] ?? '') === 'toggle') {
    verifyCsrfOrDie();
    $patientId = cleanInt($_POST['patient_id'] ?? 0);
    if ($patientId > 0) {
        db()->execute(
            "UPDATE users SET is_active = IF(is_active=1, 0, 1) WHERE id=? AND role=?",
            [$patientId, ROLE_PATIENT]
        );
    }
    redirect(BASE_URL . '/doctor/admin/patients.php');
}

// Shfaq historikun e një pacienti
$viewId  = cleanInt($_GET['view'] ?? 0);
$patient = null;
$history = [];

if ($viewId > 0) {
    $patient = getUserById($viewId);
    if ($patient && $patient['role'] === ROLE_PATIENT) {
        $history = db()->fetchAll(
            "SELECT a.*, d.name AS doctor_name, s.name AS service_name
             FROM appointments a
             JOIN users d ON a.doctor_id = d.id
             JOIN services s ON a.service_id = s.id
             WHERE a.patient_id = ?
             ORDER BY a.appointment_date DESC",
            [$viewId]
        );
    } else {
        $patient = null;
    }
}

// Search
$search = clean($_GET['q'] ?? '');

// Lista e gjithë pacientëve
$sql    = "SELECT u.*,
                  COUNT(a.id) AS total_appointments,
                  MAX(a.appointment_date) AS last_visit
           FROM users u
           LEFT JOIN appointments a ON a.patient_id = u.id
           WHERE u.role = ?";
$params = [ROLE_PATIENT];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $like     = '%' . $search . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

$sql .= " GROUP BY u.id ORDER BY u.is_active DESC, u.name ASC";

$patients      = db()->fetchAll($sql, $params);
$activeCount   = count(array_filter($patients, fn($p) => $p['is_active']));
$totalVisits   = array_sum(array_column($patients, 'total_appointments'));

$pageTitle = 'Pacientët — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Menaxhim</div>
            <h1>Lista e <em class="serif-italic">pacientëve</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= $activeCount ?> pacientë aktivë · <?= $totalVisits ?> vizita gjithsej.</p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if ($patient): ?>
    <!-- Historia e pacientit -->
    <div style="margin-bottom:16px;">
        <a href="<?= BASE_URL ?>/doctor/admin/patients.php" class="btn btn-ghost btn-sm">← Kthehu te lista</a>
    </div>

    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header">
            <h3>
                <span style="display:inline-flex;align-items:center;gap:10px;">
                    <span style="width:36px;height:36px;border-radius:50%;background:var(--accent-tint);color:var(--accent);display:inline-flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;">
                        <?= e(getInitials($patient['name'])) ?>
                    </span>
                    <?= e($patient['name']) ?>
                </span>
            </h3>
        </div>
        <div style="padding:16px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;border-bottom:1px solid var(--line);">
            <div>
                <div style="font-size:.75rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;">Email</div>
                <div><?= e($patient['email']) ?></div>
            </div>
            <div>
                <div style="font-size:.75rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;">Telefon</div>
                <div><?= e($patient['phone'] ?? '—') ?></div>
            </div>
            <?php if (!empty($patient['date_of_birth'])): ?>
            <div>
                <div style="font-size:.75rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;">Datëlindja</div>
                <div><?= formatDateSq($patient['date_of_birth']) ?></div>
            </div>
            <?php endif; ?>
            <div>
                <div style="font-size:.75rem;color:var(--ink-3);text-transform:uppercase;letter-spacing:.05em;">Regjistruar</div>
                <div><?= formatDateSq($patient['created_at']) ?></div>
            </div>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data &amp; Ora</th>
                        <th>Mjeku</th>
                        <th>Shërbimi</th>
                        <th>Statusi</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($history)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--ink-3);padding:24px;">Ky pacient nuk ka takime.</td></tr>
                <?php else: ?>
                <?php foreach ($history as $h): ?>
                <tr>
                    <td>
                        <strong><?= formatDateSq($h['appointment_date']) ?></strong>
                        <br><small style="color:var(--ink-3)"><?= e($h['time_slot']) ?></small>
                    </td>
                    <td><?= e($h['doctor_name']) ?></td>
                    <td><?= e($h['service_name']) ?></td>
                    <td><?= getStatusBadge($h['status']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php else: ?>
    <!-- Lista e pacientëve + search -->
    <form method="GET" action="" style="margin-bottom:20px;display:flex;gap:10px;max-width:420px;">
        <input type="text" name="q" class="form-control" placeholder="Kërko sipas emrit, emailit ose telefonit…"
               value="<?= e($search) ?>">
        <button type="submit" class="btn btn-cta" style="white-space:nowrap;">Kërko</button>
        <?php if (!empty($search)): ?>
        <a href="<?= BASE_URL ?>/doctor/admin/patients.php" class="btn btn-ghost">×</a>
        <?php endif; ?>
    </form>

    <div class="data-section">
        <?php if (empty($patients)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <h3>Nuk u gjetën pacientë</h3>
                <?php if (!empty($search)): ?>
                <a href="<?= BASE_URL ?>/doctor/admin/patients.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">Pastro kërkimin</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Pacienti</th>
                        <th>Email &amp; Telefon</th>
                        <th>Datëlindja</th>
                        <th>Vizita gjithsej</th>
                        <th>Vizita e fundit</th>
                        <th>Statusi</th>
                        <th>Veprime</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($patients as $pat): ?>
                <tr style="<?= !$pat['is_active'] ? 'opacity:0.5;' : '' ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:34px;height:34px;border-radius:50%;background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.75rem;flex-shrink:0;">
                                <?= e(getInitials($pat['name'])) ?>
                            </div>
                            <strong><?= e($pat['name']) ?></strong>
                        </div>
                    </td>
                    <td>
                        <?= e($pat['email']) ?>
                        <?php if (!empty($pat['phone'])): ?>
                        <br><small style="color:var(--ink-3)"><?= e($pat['phone']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= !empty($pat['date_of_birth']) ? formatDateSq($pat['date_of_birth']) : '—' ?></td>
                    <td style="text-align:center;"><?= (int)$pat['total_appointments'] ?></td>
                    <td><?= !empty($pat['last_visit']) ? formatDateSq($pat['last_visit']) : '—' ?></td>
                    <td>
                        <span class="status-badge <?= $pat['is_active'] ? 'status-confirmed' : 'status-cancelled' ?>">
                            <?= $pat['is_active'] ? 'Aktiv' : 'Joaktiv' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="?view=<?= (int)$pat['id'] ?>" class="btn btn-outline btn-sm">Historia</a>
                            <form method="POST" action="" style="margin:0;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="patient_id" value="<?= (int)$pat['id'] ?>">
                                <button type="submit" class="btn btn-sm"
                                    style="<?= $pat['is_active']
                                        ? 'background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;'
                                        : 'background:var(--success-bg,#f0fff4);color:var(--success,#27ae60);border:1px solid currentColor;' ?>">
                                    <?= $pat['is_active'] ? 'Çaktivizo' : 'Aktivizo' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:10px 16px;font-size:.82rem;color:var(--ink-3);border-top:1px solid var(--line);">
            <?= count($patients) ?> pacient<?= count($patients) !== 1 ? 'ë' : '' ?> gjithsej
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
