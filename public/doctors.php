$pageTitle = 'Mjekët Tanë — ' . APP_NAME;
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Mjekët Tanë</h1>
        <p>Ekip i kualifikuar specialistësh shëndetësorë në shërbimin tuaj</p>
    </div>
</div>

<section class="section-sm">
    <div class="container">
        <?php displayFlashMessage(); ?>

        <!-- Filter sipas specializimit -->
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
                <?php if (!empty($doctor['photo_path'])): ?>
                    <img src="<?= BASE_URL . '/' . e($doctor['photo_path']) ?>"
                         alt="<?= e($doctor['name']) ?>" class="doctor-photo">
                <?php else: ?>
                    <div class="doctor-initials"><?= e(getInitials($doctor['name'])) ?></div>
                <?php endif; ?>

                <h3><?= e($doctor['name']) ?></h3>
                <p class="doctor-spec"><?= e($doctor['specialization'] ?? '') ?></p>
                <?php if (!empty($doctor['bio'])): ?>
                    <p style="font-size:0.84rem;margin-bottom:12px;"><?= e(mb_substr($doctor['bio'], 0, 80)) ?>...</p>
                <?php endif; ?>
                <p class="doctor-fee">Konsultim: <strong><?= formatPrice((float)$doctor['consultation_fee']) ?></strong></p>
                <a href="<?= $reserveBaseUrl ?>?doctor_id=<?= (int)$doctor['id'] ?>" class="btn btn-primary btn-sm">
                    Rezervo Takim
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php';
