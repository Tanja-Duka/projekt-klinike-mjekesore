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

// Map icon names → SVG paths + accent color
$iconMap = [
    'heart' => [
        'color' => '#e8454a',
        'bg'    => '#fff0f0',
        'svg'   => '<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>',
    ],
    'brain' => [
        'color' => '#7c5cbf',
        'bg'    => '#f3eeff',
        'svg'   => '<path d="M9.5 2a4.5 4.5 0 0 1 4.48 4.05A4 4 0 0 1 17 10a4 4 0 0 1-.5 1.94A4.5 4.5 0 0 1 14 20H9a5 5 0 0 1-1-9.9V10a4.5 4.5 0 0 1 1.5-8.07V2zm5 0a4.5 4.5 0 0 1 1.5 8.07V10a4.5 4.5 0 0 1-1.5 8.07"/><line x1="12" y1="10" x2="12" y2="20"/>',
    ],
    'skin'  => [
        'color' => '#c97d3a',
        'bg'    => '#fdf3e8',
        'svg'   => '<circle cx="12" cy="12" r="3"/><path d="M12 1v4M12 19v4M4.22 4.22l2.83 2.83M16.95 16.95l2.83 2.83M1 12h4M19 12h4M4.22 19.78l2.83-2.83M16.95 7.05l2.83-2.83"/>',
    ],
    'bone'  => [
        'color' => '#3a7abf',
        'bg'    => '#e8f2ff',
        'svg'   => '<path d="M17 2a3 3 0 0 1 0 6h-1v8h1a3 3 0 0 1 0 6 3 3 0 0 1-3-3v-1H9v1a3 3 0 0 1-3 3 3 3 0 0 1 0-6h1V8H6a3 3 0 0 1 0-6 3 3 0 0 1 3 3v1h6V5a3 3 0 0 1 3-3z"/>',
    ],
    'child' => [
        'color' => '#3aab6d',
        'bg'    => '#e8faf2',
        'svg'   => '<circle cx="12" cy="7" r="4"/><path d="M12 11v10M8 15l4-2 4 2M7 21h10"/>',
    ],
    'flask' => [
        'color' => '#1a8fa0',
        'bg'    => '#e6f7fa',
        'svg'   => '<path d="M9 3h6M9 3v8L5.5 17A2 2 0 0 0 7.35 20h9.3a2 2 0 0 0 1.85-3L15 11V3"/><line x1="6" y1="14" x2="18" y2="14"/>',
    ],
    'xray'  => [
        'color' => '#5a6a8a',
        'bg'    => '#edf0f7',
        'svg'   => '<rect x="3" y="3" width="18" height="18" rx="2"/><line x1="9" y1="9" x2="9" y2="15"/><line x1="15" y1="9" x2="15" y2="15"/><path d="M9 12h6M7 9h2M15 9h2M7 15h2M15 15h2"/>',
    ],
];

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
                $iconKey  = strtolower(trim($s['icon'] ?? ''));
                $ico      = $iconMap[$iconKey] ?? ['color'=>'var(--accent)','bg'=>'var(--surface,#f5f1e8)','svg'=>'<path d="M12 5v14M5 12h14"/>'];
            ?>
            <div class="service-card">
                <div class="service-body">
                    <div class="svc-icon" style="background:<?= $ico['bg'] ?>;color:<?= $ico['color'] ?>;">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"
                             stroke-linecap="round" stroke-linejoin="round" width="28" height="28">
                            <?= $ico['svg'] ?>
                        </svg>
                    </div>
                    <div class="svc-cat"><?= e($s['category']) ?></div>
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
