<?php
// ============================================================
// public/services.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$services = getAllServices();

$categories = db()->fetchAll(
    "SELECT DISTINCT category FROM services WHERE is_active = 1 ORDER BY category ASC"
);

$reserveBaseUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$activeCategory = clean($_GET['category'] ?? '');

$displayServices = empty($activeCategory)
    ? $services
    : array_filter($services, fn($s) => $s['category'] === $activeCategory);

$pageTitle = 'Shërbimet — ' . APP_NAME;
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<!-- ==================== PAGE HEADER ==================== -->
<section class="page-header">
    <div class="container">
        <div class="eyebrow">Shërbimet — Çfarë ofrojmë</div>
        <h1>Kujdes i specializuar, <em>për çdo nevojë</em>.</h1>
        <p>Filtrimi sipas kategorisë ju ndihmon të gjeni shërbimin e duhur. Të gjitha shërbimet kryhen nga specialistët tanë të certifikuar.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container">

        <?php displayFlashMessage(); ?>

        <!-- Category filter -->
        <div class="filter-tabs">
            <a href="<?= BASE_URL ?>/public/services.php"
               class="filter-tab <?= empty($activeCategory) ? 'active' : '' ?>">Të gjitha</a>
            <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/public/services.php?category=<?= urlencode($cat['category']) ?>"
               class="filter-tab <?= $activeCategory === $cat['category'] ? 'active' : '' ?>">
                <?= e($cat['category']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($displayServices)): ?>
            <div class="empty-state" style="margin-top:48px;">
                <h3>Nuk u gjetën shërbime</h3>
                <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-cta" style="margin-top:16px;">Shiko të gjitha</a>
            </div>
        <?php else: ?>
        <div class="services-grid">
            <?php foreach ($displayServices as $s):
                $hasPhoto = !empty($s['photo_path']);
            ?>
            <div class="service-card<?= $hasPhoto ? ' has-photo' : '' ?>"
                 <?= $hasPhoto ? 'style="--photo: url(\'' . BASE_URL . '/' . e($s['photo_path']) . '\')"' : '' ?>>

                <?php if ($hasPhoto): ?>
                <div class="service-photo"><span class="photo-tag"><?= e($s['name']) ?></span></div>
                <?php endif; ?>

                <div class="service-body">
                    <span class="ico">
                        <?php if (!empty($s['icon'])): ?>
                            <span style="font-size:1.4rem;line-height:1;"><?= $s['icon'] ?></span>
                        <?php else: ?>
                        <svg class="i" viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>
                        <?php endif; ?>
                    </span>
                    <h3><?= e($s['name']) ?></h3>
                    <?php if (!empty($s['description'])): ?>
                    <p><?= e($s['description']) ?></p>
                    <?php endif; ?>
                    <div class="meta">
                        <span class="price-tag"><em>nga</em><?= formatPrice((float)$s['price']) ?></span>
                        <a href="<?= $reserveBaseUrl ?>?service_id=<?= (int)$s['id'] ?>"
                           class="btn btn-ghost btn-sm">Rezervo →</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
