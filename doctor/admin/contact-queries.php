<?php
// ============================================================
// admin/contact-queries.php - Menaxho mesazhet e kontaktit
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $action  = clean($_POST['action']    ?? '');
    $queryId = cleanInt($_POST['query_id'] ?? 0);

    if ($queryId > 0) {
        if ($action === 'read') {
            db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_READ, $queryId]);
        } elseif ($action === 'resolve') {
            db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_RESOLVED, $queryId]);
        } elseif ($action === 'delete') {
            db()->execute("DELETE FROM contact_queries WHERE id = ?", [$queryId]);
            setFlashMessage('success', 'Mesazhi u fshi.');
            redirect(BASE_URL . '/doctor/admin/contact-queries.php');
        }
    }
    redirect(BASE_URL . '/doctor/admin/contact-queries.php' .
        (!empty($_GET['view']) ? '?view=' . (int)$_GET['view'] : '') .
        (!empty($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
}

// Mesazhi i zgjedhur
$viewId    = cleanInt($_GET['view'] ?? 0);
$viewQuery = null;
if ($viewId > 0) {
    $viewQuery = db()->fetchOne("SELECT * FROM contact_queries WHERE id = ?", [$viewId]);
    if ($viewQuery && $viewQuery['status'] === QUERY_UNREAD) {
        db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_READ, $viewId]);
        $viewQuery['status'] = QUERY_READ;
    }
}

// Filtër sipas statusit
$filterStatus = clean($_GET['status'] ?? '');
$sql    = "SELECT * FROM contact_queries";
$params = [];
if (in_array($filterStatus, [QUERY_UNREAD, QUERY_READ, QUERY_RESOLVED])) {
    $sql .= " WHERE status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY created_at DESC";

$queries     = db()->fetchAll($sql, $params);
$unreadCount = count(array_filter($queries, fn($q) => $q['status'] === QUERY_UNREAD));

$pageTitle = 'Mesazhet e Kontaktit — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.cq-grid{display:grid;grid-template-columns:360px 1fr;gap:0;border:1px solid var(--line);border-radius:14px;overflow:hidden;min-height:520px;}
.cq-list{border-right:1px solid var(--line);overflow-y:auto;max-height:660px;}
.cq-item{display:block;padding:14px 16px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:background .15s;cursor:pointer;border-left:3px solid transparent;}
.cq-item:hover{background:var(--accent-tint,#faf7f2);}
.cq-item.active{background:#f5f1e8;border-left-color:var(--accent);}
.cq-item.unread .cq-item-name::before{content:"";display:inline-block;width:7px;height:7px;background:var(--accent);border-radius:50%;margin-right:7px;vertical-align:middle;}
.cq-item-name{font-size:.88rem;font-weight:600;margin-bottom:2px;}
.cq-item-subject{font-size:.8rem;color:var(--ink-2);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cq-item-meta{font-size:.72rem;color:var(--ink-3);margin-top:4px;}
.cq-detail{padding:28px;display:flex;flex-direction:column;gap:20px;}
.cq-detail-header h3{margin:0 0 4px;font-size:1.1rem;}
.cq-detail-meta{font-size:.82rem;color:var(--ink-3);}
.cq-body{background:var(--surface,#f9f7f3);border-radius:10px;padding:18px;line-height:1.8;font-size:.9rem;white-space:pre-wrap;}
.cq-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--ink-3);gap:10px;}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Komunikim</div>
            <h1>Mesazhet e <em class="serif-italic">kontaktit</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= count($queries) ?> mesazhe<?= $unreadCount > 0 ? ' · <strong>' . $unreadCount . ' të palexuara</strong>' : '' ?>.</p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Filter chips -->
    <div class="filter-tabs" style="margin-bottom:20px;">
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php"
           class="filter-tab <?= $filterStatus === '' ? 'active' : '' ?>">Të gjitha</a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_UNREAD) ?>"
           class="filter-tab <?= $filterStatus === QUERY_UNREAD ? 'active' : '' ?>">Të palexuara</a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_READ) ?>"
           class="filter-tab <?= $filterStatus === QUERY_READ ? 'active' : '' ?>">Të lexuara</a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_RESOLVED) ?>"
           class="filter-tab <?= $filterStatus === QUERY_RESOLVED ? 'active' : '' ?>">Të zgjidhura</a>
    </div>

    <!-- Two-panel layout -->
    <div class="cq-grid">

        <!-- Left: lista e mesazheve -->
        <div class="cq-list">
            <?php if (empty($queries)): ?>
            <div style="padding:40px;text-align:center;color:var(--ink-3);">
                <p>Nuk ka mesazhe.</p>
            </div>
            <?php else: ?>
            <?php foreach ($queries as $q): ?>
            <a href="?<?= http_build_query(array_merge(
                empty($filterStatus) ? [] : ['status' => $filterStatus],
                ['view' => $q['id']]
            )) ?>"
               class="cq-item <?= $q['status'] === QUERY_UNREAD ? 'unread' : '' ?> <?= $viewId === (int)$q['id'] ? 'active' : '' ?>">
                <div class="cq-item-name"><?= e($q['name']) ?></div>
                <div class="cq-item-subject"><?= e($q['subject']) ?></div>
                <div class="cq-item-meta">
                    <?= formatDateTimeSq($q['created_at']) ?>
                    &nbsp;·&nbsp;
                    <?php if ($q['status'] === QUERY_UNREAD): ?>
                        <span style="color:var(--accent);font-weight:600;">E palexuar</span>
                    <?php elseif ($q['status'] === QUERY_RESOLVED): ?>
                        <span style="color:var(--success,#27ae60);">E zgjidhur</span>
                    <?php else: ?>
                        <span>E lexuar</span>
                    <?php endif; ?>
                </div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right: detaji -->
        <div class="cq-detail">
            <?php if ($viewQuery): ?>

            <div class="cq-detail-header">
                <h3><?= e($viewQuery['subject']) ?></h3>
                <div class="cq-detail-meta">
                    <strong><?= e($viewQuery['name']) ?></strong>
                    &lt;<?= e($viewQuery['email']) ?>&gt;
                    <?php if (!empty($viewQuery['phone'])): ?>
                    · <?= e($viewQuery['phone']) ?>
                    <?php endif; ?>
                    &nbsp;·&nbsp; <?= formatDateTimeSq($viewQuery['created_at']) ?>
                </div>
            </div>

            <div class="cq-body"><?= e($viewQuery['message']) ?></div>

            <div style="display:flex;gap:10px;flex-wrap:wrap;">
                <?php if ($viewQuery['status'] !== QUERY_RESOLVED): ?>
                <form method="POST" action="" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="query_id" value="<?= (int)$viewQuery['id'] ?>">
                    <input type="hidden" name="view" value="<?= (int)$viewId ?>">
                    <button type="submit" class="btn btn-cta btn-sm">Shëno si Zgjidhur</button>
                </form>
                <?php endif; ?>
                <form method="POST" action="" style="margin:0;" onsubmit="return confirm('Fshij mesazhin?')">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="query_id" value="<?= (int)$viewQuery['id'] ?>">
                    <button type="submit" class="btn btn-sm"
                        style="background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;">
                        Fshij
                    </button>
                </form>
                <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php<?= $filterStatus ? '?status=' . urlencode($filterStatus) : '' ?>"
                   class="btn btn-ghost btn-sm">← Kthehu</a>
            </div>

            <?php else: ?>
            <div class="cq-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="36" height="36" style="opacity:.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p style="font-size:.88rem;">Zgjidhni një mesazh nga lista për ta lexuar.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
