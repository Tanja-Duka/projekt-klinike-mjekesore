$pageTitle = 'Klinika Mjekësore — Kujdes për Shëndetin Tuaj';
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<!-- ================================================================
     HERO SECTION
     ================================================================ -->
<section class="hero">
    <div class="container hero-inner">
        <div class="hero-content">
            <div class="hero-badge">&#43; Klinikë e Certifikuar</div>
            <h1 class="hero-title">
                Kujdes i plotë<br>
                <span>për çdo hap</span> të jetës.
            </h1>
            <div class="hero-divider"></div>
            <p class="hero-subtitle">
                Shërbime mjekësore për të gjithë që ju dhe familja juaj keni nevojë — në çdo vend.
            </p>
            <div class="hero-actions">
                <a href="<?= $reserveUrl ?>" class="btn btn-cta">Rezervo Takim</a>
                <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-outline">Shiko Shërbimet</a>
            </div>
        </div>
        <div class="hero-image">
            <img src="<?= BASE_URL ?>/assets/img/clinic-building.jpg"
                 alt="Vitanova Clinic"
                 onerror="this.style.display='none'">
            <div class="hero-image-overlay"></div>
        </div>
    </div>
</section>

<!-- ================================================================
     VALUES BANNER
     ================================================================ -->
<section class="values-banner">
    <div class="container values-grid">
        <div class="value-item">
            <div class="value-icon">&#128101;</div>
            <div class="value-text">
                <h4>Pacienti në Qendër</h4>
                <p>Kujdes dhe vëmendje për çdo pacient dhe familjen e tyre.</p>
            </div>
        </div>
        <div class="value-item">
            <div class="value-icon">&#10004;</div>
            <div class="value-text">
                <h4>Cilësi &amp; Siguri</h4>
                <p>Shërbime në nivel të lartë dhe norma të sigurisë me standardet botërore.</p>
            </div>
        </div>
        <div class="value-item">
            <div class="value-icon">&#127968;</div>
            <div class="value-text">
                <h4>Për të Gjithë</h4>
                <p>Njëzet dhe tre specialitete mjekësore nën një çati.</p>
            </div>
        </div>
    </div>
</section>

<!-- ================================================================
     SERVICES SECTION
     ================================================================ -->
<section class="section services-section">
    <div class="container">
        <h2 class="section-title">Shërbime për të gjithë</h2>
        <p class="section-subtitle">Gjithçka që ju dhe familja juaj keni nevojë — në një vend.</p>

        <?php if (!empty($services)): ?>
        <div class="services-grid">
            <?php foreach (array_slice($services, 0, 10) as $service): ?>
            <a href="<?= BASE_URL ?>/public/services.php" class="service-icon-card">
                <div class="service-icon-circle">
                    <?= !empty($service['icon']) ? e($service['icon']) : '&#43;' ?>
                </div>
                <h4><?= e($service['name']) ?></h4>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-24">
            <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-outline">Shiko të gjitha shërbimet</a>
        </div>
    </div>
</section>

<!-- ================================================================
     DOCTORS SECTION
     ================================================================ -->
<?php if (!empty($doctors)): ?>
<section class="section" style="background:#fff;">
    <div class="container">
        <h2 class="section-title">Mjekët Tanë</h2>
        <p class="section-subtitle">Ekip i kualifikuar i specialistëve shëndetësorë.</p>

        <div class="doctors-grid">
            <?php foreach (array_slice($doctors, 0, 4) as $doctor): ?>
            <div class="doctor-card">
                <?php if (!empty($doctor['photo_path'])): ?>
                    <img src="<?= BASE_URL . '/' . e($doctor['photo_path']) ?>"
                         alt="<?= e($doctor['name']) ?>"
                         class="doctor-photo">
                <?php else: ?>
                    <div class="doctor-initials"><?= e(getInitials($doctor['name'])) ?></div>
                <?php endif; ?>
                <h3><?= e($doctor['name']) ?></h3>
                <p class="doctor-spec"><?= e($doctor['specialization'] ?? '') ?></p>
                <p class="doctor-fee">Konsultim: <?= formatPrice((float)$doctor['consultation_fee']) ?></p>
                <a href="<?= $reserveUrl ?>?doctor_id=<?= (int)$doctor['id'] ?>" class="btn btn-primary btn-sm">Rezervo</a>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-24">
            <a href="<?= BASE_URL ?>/public/doctors.php" class="btn btn-outline">Shiko të gjithë mjekët</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================================================================
     CTA SECTION
     ================================================================ -->
<section class="cta-section">
    <div class="container cta-inner">
        <div>
            <h2>Kujdesi i plotë shëndetësor që ju meritoni.</h2>
            <p>Rezervoni takimin tuaj sot dhe merrni kujdesin që meritoni.</p>
        </div>
        <a href="<?= $reserveUrl ?>" class="btn btn-cta btn-lg">Rezervo Takim</a>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php';
