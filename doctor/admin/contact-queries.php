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
$readCount   = count(array_filter($queries, fn($q) => $q['status'] === QUERY_READ));
$resolvedCount = count(array_filter($queries, fn($q) => $q['status'] === QUERY_RESOLVED));

$pageTitle = 'Mesazhet e Kontaktit — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.filter-bar{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;}
.filter-chip{display:inline-flex;align-items:center;gap:6px;padding:7px 14px;border:1.5px solid var(--line);border-radius:20px;font-size:.8rem;text-decoration:none;color:var(--ink-2);background:var(--page);transition:border-color .15s,background .15s;}
.filter-chip:hover{border-color:var(--accent);color:var(--ink-1);}
.filter-chip.active{border-color:var(--accent);background:var(--accent-tint,#f5efe8);color:var(--ink-1);font-weight:600;}
.filter-chip span{display:inline-flex;align-items:center;justify-content:center;min-width:18px;height:18px;padding:0 5px;background:var(--line);border-radius:9px;font-size:.68rem;font-weight:700;color:var(--ink-2);}
.filter-chip.active span{background:var(--accent);color:#fff;}

.cq-grid{display:grid;grid-template-columns:340px 1fr;gap:0;border:1px solid var(--line);border-radius:14px;overflow:hidden;min-height:520px;}
.cq-list{border-right:1px solid var(--line);overflow-y:auto;max-height:680px;}
.cq-item{display:block;padding:14px 16px;border-bottom:1px solid var(--line);text-decoration:none;color:inherit;transition:background .15s;cursor:pointer;border-left:3px solid transparent;}
.cq-item:hover{background:var(--accent-tint,#faf7f2);}
.cq-item.active{background:#f5f1e8;border-left-color:var(--accent);}
.cq-item .hd{display:flex;align-items:center;justify-content:space-between;margin-bottom:3px;}
.cq-item .hd strong{font-size:.86rem;font-weight:600;color:var(--ink-1);}
.cq-item .hd .dt{font-size:.68rem;color:var(--ink-3);white-space:nowrap;}
.cq-item.unread .hd strong::before{content:"";display:inline-block;width:7px;height:7px;background:var(--accent);border-radius:50%;margin-right:6px;vertical-align:middle;}
.cq-item .sub{font-size:.8rem;color:var(--ink-2);font-weight:500;margin-bottom:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.cq-item .pv{font-size:.74rem;color:var(--ink-3);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}

.cq-detail{padding:28px 30px;display:flex;flex-direction:column;gap:18px;}
.cq-detail h3{margin:0;font-size:1.15rem;font-weight:600;color:var(--ink-1);}
.cq-detail .meta{font-size:.82rem;color:var(--ink-3);line-height:1.6;}
.cq-detail .meta strong{color:var(--ink-2);}
.cq-detail .body{background:var(--surface,#f9f7f3);border-radius:10px;padding:18px 20px;line-height:1.8;font-size:.9rem;white-space:pre-wrap;color:var(--ink-1);}
.cq-actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center;}
.cq-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--ink-3);gap:12px;padding:40px;}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Komunikim</div>
            <h1>Mesazhet e <em class="serif-italic">kontaktit</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?= count($queries) ?> mesazhe<?= $unreadCount > 0 ? ' · <strong>' . $unreadCount . ' të palexuara</strong>' : '' ?>.
            </p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Filter chips -->
    <div class="filter-bar">
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php"
           class="filter-chip <?= $filterStatus === '' ? 'active' : '' ?>">
            Të gjitha <span><?= count($queries) ?></span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_UNREAD) ?>"
           class="filter-chip <?= $filterStatus === QUERY_UNREAD ? 'active' : '' ?>">
            Të palexuara <span><?= $unreadCount ?></span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_READ) ?>"
           class="filter-chip <?= $filterStatus === QUERY_READ ? 'active' : '' ?>">
            Të lexuara <span><?= $readCount ?></span>
        </a>
        <a href="<?= BASE_URL ?>/doctor/admin/contact-queries.php?status=<?= urlencode(QUERY_RESOLVED) ?>"
           class="filter-chip <?= $filterStatus === QUERY_RESOLVED ? 'active' : '' ?>">
            Të zgjidhura <span><?= $resolvedCount ?></span>
        </a>
    </div>

    <!-- Two-panel layout -->
    <div class="cq-grid">

        <!-- Left: lista e mesazheve -->
        <div class="cq-list">
            <?php if (empty($queries)): ?>
            <div style="padding:40px;text-align:center;color:var(--ink-3);">
                <p style="font-size:.88rem;">Nuk ka mesazhe.</p>
            </div>
            <?php else: ?>
            <?php foreach ($queries as $q): ?>
            <a href="?<?= http_build_query(array_merge(
                empty($filterStatus) ? [] : ['status' => $filterStatus],
                ['view' => $q['id']]
            )) ?>"
               class="cq-item <?= $q['status'] === QUERY_UNREAD ? 'unread' : '' ?> <?= $viewId === (int)$q['id'] ? 'active' : '' ?>">
                <div class="hd">
                    <strong><?= e($q['name']) ?></strong>
                    <span class="dt"><?= e(date('d/m/Y', strtotime($q['created_at']))) ?></span>
                </div>
                <div class="sub"><?= e($q['subject']) ?></div>
                <div class="pv"><?= e(mb_substr($q['message'], 0, 70)) ?>…</div>
            </a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Right: detaji -->
        <div class="cq-detail">
            <?php if ($viewQuery): ?>

            <h3><?= e($viewQuery['subject']) ?></h3>

            <div class="meta">
                <strong><?= e($viewQuery['name']) ?></strong>
                &lt;<?= e($viewQuery['email']) ?>&gt;
                <?php if (!empty($viewQuery['phone'])): ?>
                · <?= e($viewQuery['phone']) ?>
                <?php endif; ?>
                <br>
                <?= formatDateTimeSq($viewQuery['created_at']) ?>
                &nbsp;·&nbsp;
                <?php if ($viewQuery['status'] === QUERY_UNREAD): ?>
                    <span style="color:var(--accent);font-weight:600;">E palexuar</span>
                <?php elseif ($viewQuery['status'] === QUERY_RESOLVED): ?>
                    <span style="color:var(--success,#27ae60);font-weight:600;">E zgjidhur</span>
                <?php else: ?>
                    <span>E lexuar</span>
                <?php endif; ?>
            </div>

            <div class="body"><?= e($viewQuery['message']) ?></div>

            <div class="cq-actions">
                <?php if ($viewQuery['status'] !== QUERY_RESOLVED): ?>
                <form method="POST" action="" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="resolve">
                    <input type="hidden" name="query_id" value="<?= (int)$viewQuery['id'] ?>">
                    <button type="submit" class="btn btn-cta btn-sm">Shëno si Zgjidhur</button>
                </form>
                <?php endif; ?>
                <?php if ($viewQuery['status'] === QUERY_UNREAD): ?>
                <form method="POST" action="" style="margin:0;">
                    <?= csrfInput() ?>
                    <input type="hidden" name="action" value="read">
                    <input type="hidden" name="query_id" value="<?= (int)$viewQuery['id'] ?>">
                    <button type="submit" class="btn btn-outline btn-sm">Shëno si i Lexuar</button>
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
                   class="btn btn-ghost btn-sm" style="margin-left:auto;">← Kthehu</a>
            </div>

            <?php else: ?>
            <div class="cq-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.25"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p style="font-size:.88rem;text-align:center;">Zgjidhni një mesazh nga lista për ta lexuar.</p>
            </div>
            <?php endif; ?>
        </div>

    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
