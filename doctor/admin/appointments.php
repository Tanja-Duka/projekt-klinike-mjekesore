<?php
// ============================================================
// admin/appointments.php - Menaxho të gjitha takimet
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Ndrysho statusin e takimit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && clean($_POST['action'] ?? '') === 'status') {
    verifyCsrfOrDie();
    $apptId    = cleanInt($_POST['appointment_id'] ?? 0);
    $newStatus = clean($_POST['status'] ?? '');
    $allowed   = [STATUS_PENDING, STATUS_CONFIRMED, STATUS_COMPLETED, STATUS_CANCELLED];

    if ($apptId > 0 && in_array($newStatus, $allowed)) {
        db()->execute(
            "UPDATE appointments SET status = ? WHERE id = ?",
            [$newStatus, $apptId]
        );
    }
    redirect(BASE_URL . '/doctor/admin/appointments.php?' . http_build_query($_GET));
}

// Filtrat
$dateFrom  = clean($_GET['date_from'] ?? '');
$dateTo    = clean($_GET['date_to']   ?? '');
$filterStatus = clean($_GET['status']    ?? '');
$filterDoctor = cleanInt($_GET['doctor_id'] ?? 0);

$sql    = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
           FROM appointments a
           JOIN users p ON a.patient_id = p.id
           JOIN users d ON a.doctor_id  = d.id
           JOIN services s ON a.service_id = s.id
           WHERE 1=1";
$params = [];

if (!empty($dateFrom) && isValidDate($dateFrom)) {
    $sql .= " AND a.appointment_date >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo) && isValidDate($dateTo)) {
    $sql .= " AND a.appointment_date <= ?";
    $params[] = $dateTo;
}
if (!empty($filterStatus)) {
    $sql .= " AND a.status = ?";
    $params[] = $filterStatus;
}
if ($filterDoctor > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $filterDoctor;
}

$sql .= " ORDER BY a.appointment_date DESC, a.time_slot DESC";

$appointments = db()->fetchAll($sql, $params);
$doctors      = getAllDoctors();
$hasFilters   = !empty($dateFrom) || !empty($dateTo) || !empty($filterStatus) || $filterDoctor > 0;

$pageTitle = 'Takimet — ' . APP_NAME;
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
            <h1>Të gjitha <em class="serif-italic">takimet</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?= count($appointments) ?> takim<?= count($appointments) !== 1 ? 'e' : '' ?> gjithsej<?= $hasFilters ? ' (të filtruara)' : '' ?>.
            </p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Filter form -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header">
            <h3>Filtro takimet</h3>
            <?php if ($hasFilters): ?>
            <a href="<?= BASE_URL ?>/doctor/admin/appointments.php" class="btn btn-ghost btn-sm">Pastro filtrat ×</a>
            <?php endif; ?>
        </div>
        <form method="GET" action="" style="padding:0 0 16px;">
            <div style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;padding:16px 0 0;">
                <div class="form-group" style="margin:0;min-width:140px;flex:1;">
                    <label class="form-label" style="font-size:.8rem;">Nga data</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
                </div>
                <div class="form-group" style="margin:0;min-width:140px;flex:1;">
                    <label class="form-label" style="font-size:.8rem;">Deri në datë</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
                </div>
                <div class="form-group" style="margin:0;min-width:140px;flex:1;">
                    <label class="form-label" style="font-size:.8rem;">Statusi</label>
                    <select name="status" class="form-control">
                        <option value="">— Të gjitha —</option>
                        <option value="<?= STATUS_PENDING ?>"   <?= $filterStatus === STATUS_PENDING   ? 'selected' : '' ?>>Në pritje</option>
                        <option value="<?= STATUS_CONFIRMED ?>" <?= $filterStatus === STATUS_CONFIRMED ? 'selected' : '' ?>>Konfirmuar</option>
                        <option value="<?= STATUS_COMPLETED ?>" <?= $filterStatus === STATUS_COMPLETED ? 'selected' : '' ?>>Kryer</option>
                        <option value="<?= STATUS_CANCELLED ?>" <?= $filterStatus === STATUS_CANCELLED ? 'selected' : '' ?>>Anuluar</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;min-width:160px;flex:1;">
                    <label class="form-label" style="font-size:.8rem;">Mjeku</label>
                    <select name="doctor_id" class="form-control">
                        <option value="">— Të gjithë —</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int)$doc['id'] ?>" <?= $filterDoctor === (int)$doc['id'] ? 'selected' : '' ?>>
                            <?= e($doc['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-cta" style="white-space:nowrap;">Filtro →</button>
            </div>
        </form>
    </div>

    <!-- Appointments table -->
    <div class="data-section">
        <?php if (empty($appointments)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.35"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk u gjetën takime</h3>
                <?php if ($hasFilters): ?>
                <a href="<?= BASE_URL ?>/doctor/admin/appointments.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">Pastro filtrat</a>
                <?php endif; ?>
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
                        <th>Ndrysho status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($appointments as $appt): ?>
                <tr class="status-<?= e($appt['status']) ?>">
                    <td>
                        <strong><?= formatDateSq($appt['appointment_date']) ?></strong>
                        <br><small style="color:var(--ink-3)"><?= e($appt['time_slot']) ?></small>
                    </td>
                    <td><?= e($appt['patient_name']) ?></td>
                    <td><?= e($appt['doctor_name']) ?></td>
                    <td><?= e($appt['service_name']) ?></td>
                    <td><?= getStatusBadge($appt['status']) ?></td>
                    <td>
                        <form method="POST" action="" style="display:flex;gap:6px;align-items:center;">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="status">
                            <input type="hidden" name="appointment_id" value="<?= (int)$appt['id'] ?>">
                            <select name="status" class="form-control" style="padding:4px 8px;font-size:.82rem;min-width:110px;">
                                <option value="<?= STATUS_PENDING ?>"   <?= $appt['status'] === STATUS_PENDING   ? 'selected' : '' ?>>Në pritje</option>
                                <option value="<?= STATUS_CONFIRMED ?>" <?= $appt['status'] === STATUS_CONFIRMED ? 'selected' : '' ?>>Konfirmuar</option>
                                <option value="<?= STATUS_COMPLETED ?>" <?= $appt['status'] === STATUS_COMPLETED ? 'selected' : '' ?>>Kryer</option>
                                <option value="<?= STATUS_CANCELLED ?>" <?= $appt['status'] === STATUS_CANCELLED ? 'selected' : '' ?>>Anuluar</option>
                            </select>
                            <button type="submit" class="btn btn-outline btn-sm">Ruaj</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="padding:10px 16px;font-size:.82rem;color:var(--ink-3);border-top:1px solid var(--line);">
            <?= count($appointments) ?> takim<?= count($appointments) !== 1 ? 'e' : '' ?> gjithsej
        </div>
        <?php endif; ?>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
