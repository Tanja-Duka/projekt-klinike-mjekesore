<?php
// ============================================================
// public/doctors.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$filterSpec = clean($_GET['specialization'] ?? '');

if (!empty($filterSpec)) {
    $doctors = getDoctorsBySpecialization($filterSpec);
} else {
    $doctors = getAllDoctors();
}

$specializations = db()->fetchAll(
    "SELECT DISTINCT specialization FROM users
     WHERE role = 'doctor' AND is_active = 1 AND specialization IS NOT NULL
     ORDER BY specialization ASC"
);

$reserveBaseUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$pageTitle = 'Mjekët Tanë — ' . APP_NAME;
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<!-- Page header -->
<section class="page-header">
    <div class="container">
        <div class="eyebrow">Ekipi — Mjekët tanë</div>
        <h1>Specialistë <em>të kujdesshëm</em>.</h1>
        <p>Çdo mjek në Vitanova vjen me përvojë të dëshmuar dhe me një filozofi të përbashkët: koha e duhur për çdo pacient.</p>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <?php displayFlashMessage(); ?>

        <!-- Filter tabs -->
        <div class="filter-tabs mb-24">
            <a href="<?= BASE_URL ?>/public/doctors.php"
               class="filter-tab <?= empty($filterSpec) ? 'active' : '' ?>">Të gjithë</a>
            <?php foreach ($specializations as $spec): ?>
            <a href="<?= BASE_URL ?>/public/doctors.php?specialization=<?= urlencode($spec['specialization']) ?>"
               class="filter-tab <?= $filterSpec === $spec['specialization'] ? 'active' : '' ?>">
                <?= e($spec['specialization']) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($doctors)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">&#128101;</div>
                <h3>Nuk u gjetën mjekë</h3>
                <p>Nuk ka mjekë për specializimin e zgjedhur.</p>
                <a href="<?= BASE_URL ?>/public/doctors.php" class="btn btn-primary">Shiko të gjithë</a>
            </div>
        <?php else: ?>
        <div class="doctors-grid">
            <?php foreach ($doctors as $doctor): ?>
            <div class="doctor-card">
                <div class="doctor-photo-wrap">
                    <?php if (!empty($doctor['photo_path']) && file_exists(BASE_PATH . '/' . $doctor['photo_path'])): ?>
                        <img src="<?= BASE_URL . '/' . e($doctor['photo_path']) ?>"
                             alt="<?= e($doctor['name']) ?>" class="doctor-photo">
                    <?php else: ?>
                        <div class="placeholder"><?= e(getInitials($doctor['name'])) ?></div>
                    <?php endif; ?>
                    <span class="spec-tag"><?= e($doctor['specialization'] ?? '') ?></span>
                </div>
                <h3><?= e($doctor['name']) ?></h3>
                <p class="doctor-spec"><?= e($doctor['specialization'] ?? '') ?></p>
                <?php if (!empty($doctor['bio'])): ?>
                    <p class="doctor-bio"><?= e(mb_substr($doctor['bio'], 0, 100)) ?>...</p>
                <?php endif; ?>
                <div class="doctor-meta">
                    <span class="fee">
                        <em>Konsultim</em><?= formatPrice((float)$doctor['consultation_fee']) ?>
                    </span>
                    <a href="<?= $reserveBaseUrl ?>?doctor_id=<?= (int)$doctor['id'] ?>"
                       class="btn btn-ghost btn-sm">Rezervo &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
