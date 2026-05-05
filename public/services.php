$pageTitle = 'Shërbimet — ' . APP_NAME;
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Shërbimet Tona</h1>
        <p>Kujdes mjekësor i specializuar për çdo nevojë shëndetësore</p>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <?php displayFlashMessage(); ?>

        <!-- Filter sipas kategorisë -->
        <?php
        $activeCategory = clean($_GET['category'] ?? '');
        ?>
        <div class="filter-tabs mb-24">
            <a href="<?= BASE_URL ?>/public/services.php"
               class="filter-tab <?= empty($activeCategory) ? 'active' : '' ?>">Të gjitha</a>
            <?php foreach ($categories as $cat): ?>
            <a href="<?= BASE_URL ?>/public/services.php?category=<?= urlencode($cat['category']) ?>"
               class="filter-tab <?= $activeCategory === $cat['category'] ? 'active' : '' ?>">
                <?= e($cat['category']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php
        // Filtro sipas kategorisë nëse është zgjedhur
        $displayServices = empty($activeCategory)
            ? $services
            : array_filter($services, fn($s) => $s['category'] === $activeCategory);
        ?>

        <?php if (empty($displayServices)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#43;</div>
                <h3>Nuk u gjetën shërbime</h3>
                <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-primary">Shiko të gjitha</a>
            </div>
        <?php else: ?>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:24px;">
            <?php foreach ($displayServices as $service): ?>
            <div class="service-card">
                <div class="service-icon-circle">
                    <?= !empty($service['icon']) ? e($service['icon']) : '&#43;' ?>
                </div>
                <h3><?= e($service['name']) ?></h3>
                <?php if (!empty($service['description'])): ?>
                    <p><?= e($service['description']) ?></p>
                <?php endif; ?>
                <div class="price-tag"><?= formatPrice((float)$service['price']) ?></div>
                <div class="mt-16">
                    <a href="<?= $reserveBaseUrl ?>?service_id=<?= (int)$service['id'] ?>"
                       class="btn btn-primary btn-sm">Rezervo</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php';

