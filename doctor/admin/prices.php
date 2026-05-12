<?php
// ============================================================
// admin/prices.php - Menaxho shërbimet dhe çmimet
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors = [];
$action = clean($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    // ---- SHTO shërbim ----
    if ($action === 'add') {
        $name        = clean($_POST['name']        ?? '');
        $description = clean($_POST['description'] ?? '');
        $category    = clean($_POST['category']    ?? '');
        $price       = cleanFloat($_POST['price']  ?? 0);
        $icon        = clean($_POST['icon']        ?? '');

        if (empty($name) || empty($category) || $price <= 0) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } else {
            db()->insert(
                "INSERT INTO services (name, description, category, price, icon, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)",
                [$name, $description, $category, $price, $icon]
            );
            setFlashMessage('success', 'Shërbimi u shtua me sukses!');
            redirect(BASE_URL . '/doctor/admin/prices.php');
        }
    }

    // ---- EDITO shërbim ----
    if ($action === 'edit') {
        $serviceId   = cleanInt($_POST['service_id'] ?? 0);
        $name        = clean($_POST['name']          ?? '');
        $description = clean($_POST['description']   ?? '');
        $category    = clean($_POST['category']      ?? '');
        $price       = cleanFloat($_POST['price']    ?? 0);
        $icon        = clean($_POST['icon']          ?? '');

        if ($serviceId <= 0 || empty($name) || $price <= 0) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } else {
            db()->execute(
                "UPDATE services SET name=?, description=?, category=?, price=?, icon=?
                 WHERE id=?",
                [$name, $description, $category, $price, $icon, $serviceId]
            );
            setFlashMessage('success', MSG_PRICE_UPDATED);
            redirect(BASE_URL . '/doctor/admin/prices.php');
        }
    }

    // ---- TOGGLE aktivizim ----
    if ($action === 'toggle') {
        $serviceId = cleanInt($_POST['service_id'] ?? 0);
        if ($serviceId > 0) {
            db()->execute(
                "UPDATE services SET is_active = IF(is_active=1, 0, 1) WHERE id=?",
                [$serviceId]
            );
        }
        redirect(BASE_URL . '/doctor/admin/prices.php');
    }
}

// Filtro sipas kategorisë
$filterCat = clean($_GET['category'] ?? '');

$services = db()->fetchAll(
    "SELECT * FROM services ORDER BY is_active DESC, category ASC, name ASC"
);

$categories   = array_unique(array_column($services, 'category'));
sort($categories);

$displaySvcs  = empty($filterCat)
    ? $services
    : array_filter($services, fn($s) => $s['category'] === $filterCat);

$activeCount  = count(array_filter($services, fn($s) => $s['is_active']));

$pageTitle = 'Shërbime & Çmime — ' . APP_NAME;
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
            <h1>Shërbime &amp; <em class="serif-italic">çmime</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= $activeCount ?> shërbime aktive nga gjithsej <?= count($services) ?>.</p>
        </div>
        <button class="btn btn-cta" onclick="toggleForm('addServiceForm')">+ Shto Shërbim</button>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:24px;">
        <ul style="margin:0;padding-left:16px;">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Forma shto shërbim (hidden by default) -->
    <div class="dashboard-form" id="addServiceForm" style="display:none;margin-bottom:32px;">
        <h3>Shto Shërbim të Ri</h3>
        <form method="POST" action="">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Emri i Shërbimit <span>*</span></label>
                    <input type="text" name="name" class="form-control" required
                           placeholder="p.sh. Elektrokardiogram">
                </div>
                <div class="form-group">
                    <label class="form-label">Kategoria <span>*</span></label>
                    <input type="text" name="category" class="form-control" required
                           placeholder="p.sh. Kardiologji"
                           list="categoryList">
                    <datalist id="categoryList">
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat) ?>">
                        <?php endforeach; ?>
                    </datalist>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Çmimi (L) <span>*</span></label>
                    <input type="number" name="price" class="form-control" min="0" step="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ikona (emoji)</label>
                    <input type="text" name="icon" class="form-control" placeholder="🩺">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Përshkrim</label>
                <textarea name="description" class="form-control" rows="2"
                          placeholder="Përshkrim i shkurtër i shërbimit…"></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-cta">Shto Shërbimin</button>
                <button type="button" class="btn btn-ghost" onclick="toggleForm('addServiceForm')">Anulo</button>
            </div>
        </form>
    </div>

    <!-- Category filter chips -->
    <?php if (!empty($categories)): ?>
    <div class="filter-tabs" style="margin-bottom:20px;">
        <a href="<?= BASE_URL ?>/doctor/admin/prices.php"
           class="filter-tab <?= empty($filterCat) ? 'active' : '' ?>">Të gjitha</a>
        <?php foreach ($categories as $cat): ?>
        <a href="<?= BASE_URL ?>/doctor/admin/prices.php?category=<?= urlencode($cat) ?>"
           class="filter-tab <?= $filterCat === $cat ? 'active' : '' ?>">
            <?= e($cat) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Services table -->
    <div class="data-section">
        <?php if (empty($displaySvcs)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <h3>Nuk u gjetën shërbime</h3>
                <?php if (!empty($filterCat)): ?>
                <a href="<?= BASE_URL ?>/doctor/admin/prices.php" class="btn btn-ghost btn-sm" style="margin-top:12px;">Shiko të gjitha</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Shërbimi</th>
                        <th>Kategoria</th>
                        <th>Çmimi</th>
                        <th>Statusi</th>
                        <th>Veprime</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($displaySvcs as $svc): ?>
                <tr style="<?= !$svc['is_active'] ? 'opacity:0.5;' : '' ?>">
                    <td>
                        <?php if (!empty($svc['icon'])): ?>
                        <span style="margin-right:8px;font-size:1.1rem;"><?= e($svc['icon']) ?></span>
                        <?php endif; ?>
                        <strong><?= e($svc['name']) ?></strong>
                        <?php if (!empty($svc['description'])): ?>
                        <br><small style="color:var(--ink-3)"><?= e(mb_strimwidth($svc['description'], 0, 60, '…')) ?></small>
                        <?php endif; ?>
                    </td>
                    <td><?= e($svc['category'] ?? '—') ?></td>
                    <td><strong><?= formatPrice((float)$svc['price']) ?></strong></td>
                    <td>
                        <span class="status-badge <?= $svc['is_active'] ? 'status-confirmed' : 'status-cancelled' ?>">
                            <?= $svc['is_active'] ? 'Aktiv' : 'Joaktiv' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-outline btn-sm"
                                onclick="openEditService(
                                    <?= (int)$svc['id'] ?>,
                                    '<?= e(addslashes($svc['name'])) ?>',
                                    '<?= e(addslashes($svc['description'] ?? '')) ?>',
                                    '<?= e(addslashes($svc['category'] ?? '')) ?>',
                                    <?= (float)$svc['price'] ?>,
                                    '<?= e(addslashes($svc['icon'] ?? '')) ?>'
                                )">Edito</button>
                            <form method="POST" action="" style="margin:0;">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="service_id" value="<?= (int)$svc['id'] ?>">
                                <button type="submit" class="btn btn-sm"
                                    style="<?= $svc['is_active']
                                        ? 'background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;'
                                        : 'background:var(--success-bg,#f0fff4);color:var(--success,#27ae60);border:1px solid currentColor;' ?>">
                                    <?= $svc['is_active'] ? 'Çaktivizo' : 'Aktivizo' ?>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Forma edito shërbim (hidden, popullohet me JS) -->
    <div class="dashboard-form" id="editServiceForm" style="display:none;margin-top:32px;">
        <h3>Edito Shërbimin</h3>
        <form method="POST" action="">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="service_id" id="editServiceId">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Emri <span>*</span></label>
                    <input type="text" name="name" id="editSvcName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Kategoria <span>*</span></label>
                    <input type="text" name="category" id="editSvcCategory" class="form-control" required list="categoryList">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Çmimi (L) <span>*</span></label>
                    <input type="number" name="price" id="editSvcPrice" class="form-control" min="0" step="100" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ikona</label>
                    <input type="text" name="icon" id="editSvcIcon" class="form-control">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Përshkrim</label>
                <textarea name="description" id="editSvcDesc" class="form-control" rows="2"></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
                <button type="button" class="btn btn-ghost" onclick="toggleForm('editServiceForm')">Anulo</button>
            </div>
        </form>
    </div>

</main>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    if (el.style.display === 'block') {
        el.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
}

function openEditService(id, name, desc, category, price, icon) {
    document.getElementById('editServiceId').value   = id;
    document.getElementById('editSvcName').value     = name;
    document.getElementById('editSvcDesc').value     = desc;
    document.getElementById('editSvcCategory').value = category;
    document.getElementById('editSvcPrice').value    = price;
    document.getElementById('editSvcIcon').value     = icon;
    document.getElementById('editServiceForm').style.display = 'block';
    document.getElementById('editServiceForm').scrollIntoView({behavior: 'smooth', block: 'start'});
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
