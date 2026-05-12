<?php
// ============================================================
// admin/session-logs.php - Loget e sesioneve (login/logout)
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$filterRole = clean($_GET['role']      ?? '');
$filterAction = clean($_GET['action_type'] ?? '');
$dateFrom   = clean($_GET['date_from'] ?? '');
$dateTo     = clean($_GET['date_to']   ?? '');
$page       = max(1, cleanInt($_GET['page'] ?? 1));

$sql    = "SELECT sl.*, u.name AS user_name, u.email
           FROM session_logs sl
           JOIN users u ON sl.user_id = u.id
           WHERE 1=1";
$params = [];

if (in_array($filterRole, [ROLE_PATIENT, ROLE_DOCTOR, ROLE_ADMIN])) {
    $sql .= " AND sl.role = ?";
    $params[] = $filterRole;
}
if (!empty($filterAction)) {
    $sql .= " AND sl.action = ?";
    $params[] = $filterAction;
}
if (!empty($dateFrom) && isValidDate($dateFrom)) {
    $sql .= " AND DATE(sl.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo) && isValidDate($dateTo)) {
    $sql .= " AND DATE(sl.created_at) <= ?";
    $params[] = $dateTo;
}

$countSql   = str_replace("SELECT sl.*, u.name AS user_name, u.email", "SELECT COUNT(*) as c", $sql);
$total      = db()->fetchOne($countSql, $params)['c'];
$pagination = paginate((int)$total, $page);

$sql .= " ORDER BY sl.created_at DESC LIMIT " . RECORDS_PER_PAGE . " OFFSET " . $pagination['offset'];
$logs = db()->fetchAll($sql, $params);

$hasFilters = !empty($filterRole) || !empty($filterAction) || !empty($dateFrom) || !empty($dateTo);

// Distinct action types for filter chips
$actionTypes = db()->fetchAll("SELECT DISTINCT action FROM session_logs ORDER BY action ASC");

$pageTitle = 'Loget e Sesioneve — ' . APP_NAME;
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
            <div class="eyebrow">Admin — Siguri</div>
            <h1>Loget e <em class="serif-italic">sesioneve</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?= number_format((int)$total) ?> regjistrime<?= $hasFilters ? ' (të filtruara)' : '' ?>.
            </p>
        </div>
        <?php if ($hasFilters): ?>
        <a href="<?= BASE_URL ?>/doctor/admin/session-logs.php" class="btn btn-ghost">Pastro filtrat ×</a>
        <?php endif; ?>
    </div>

    <!-- Filtër -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header"><h3>Filtro</h3></div>
        <form method="GET" action="" style="padding:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;min-width:130px;">
                <label class="form-label" style="font-size:.8rem;">Roli</label>
                <select name="role" class="form-control">
                    <option value="">— Të gjithë —</option>
                    <option value="<?= ROLE_PATIENT ?>" <?= $filterRole === ROLE_PATIENT ? 'selected' : '' ?>>Pacient</option>
                    <option value="<?= ROLE_DOCTOR ?>"  <?= $filterRole === ROLE_DOCTOR  ? 'selected' : '' ?>>Mjek</option>
                    <option value="<?= ROLE_ADMIN ?>"   <?= $filterRole === ROLE_ADMIN   ? 'selected' : '' ?>>Admin</option>
                </select>
            </div>
            <?php if (!empty($actionTypes)): ?>
            <div class="form-group" style="margin:0;min-width:140px;">
                <label class="form-label" style="font-size:.8rem;">Veprimi</label>
                <select name="action_type" class="form-control">
                    <option value="">— Të gjitha —</option>
                    <?php foreach ($actionTypes as $at): ?>
                    <option value="<?= e($at['action']) ?>" <?= $filterAction === $at['action'] ? 'selected' : '' ?>>
                        <?= e($at['action']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="form-group" style="margin:0;min-width:140px;">
                <label class="form-label" style="font-size:.8rem;">Nga data</label>
                <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
            </div>
            <div class="form-group" style="margin:0;min-width:140px;">
                <label class="form-label" style="font-size:.8rem;">Deri në datë</label>
                <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-cta">Filtro →</button>
        </form>
    </div>

    <!-- Tabela -->
    <div class="data-section">
        <?php if (empty($logs)): ?>
        <div class="empty-state" style="padding:48px 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="38" height="38" style="opacity:.3"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
            <h3>Nuk u gjetën log-je</h3>
            <?php if ($hasFilters): ?>
            <a href="<?= BASE_URL ?>/doctor/admin/session-logs.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">Pastro filtrat</a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Përdoruesi</th>
                        <th>Email</th>
                        <th>Roli</th>
                        <th>Veprimi</th>
                        <th>IP Adresa</th>
                        <th>Data &amp; Ora</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td><strong><?= e($log['user_name']) ?></strong></td>
                    <td><small style="color:var(--ink-3)"><?= e($log['email']) ?></small></td>
                    <td>
                        <?php
                        if ($log['role'] === ROLE_ADMIN) {
                            $roleClass = 'status-pending'; $roleLabel = 'Admin';
                        } elseif ($log['role'] === ROLE_DOCTOR) {
                            $roleClass = 'status-confirmed'; $roleLabel = 'Mjek';
                        } else {
                            $roleClass = 'status-completed'; $roleLabel = 'Pacient';
                        }
                        ?>
                        <span class="status-badge <?= $roleClass ?>"><?= $roleLabel ?></span>
                    </td>
                    <td>
                        <code style="font-size:.78rem;background:var(--surface,#f5f3ef);padding:2px 6px;border-radius:4px;">
                            <?= e($log['action'] ?? '—') ?>
                        </code>
                    </td>
                    <td><small style="color:var(--ink-3)"><?= e($log['ip_address'] ?? '—') ?></small></td>
                    <td><small><?= formatDateTimeSq($log['created_at']) ?></small></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Faqëzim -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-top:1px solid var(--line);font-size:.82rem;color:var(--ink-3);">
            <span>
                Faqja <?= $pagination['current_page'] ?> nga <?= $pagination['total_pages'] ?>
                &nbsp;·&nbsp; <?= number_format((int)$total) ?> regjistrime gjithsej
            </span>
            <div style="display:flex;gap:8px;">
                <?php if ($pagination['current_page'] > 1): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>"
                   class="btn btn-outline btn-sm">← Para</a>
                <?php endif; ?>
                <?php if ($pagination['current_page'] < $pagination['total_pages']): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>"
                   class="btn btn-outline btn-sm">Pas →</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
