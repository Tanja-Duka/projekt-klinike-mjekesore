<?php
// ============================================================
// public/home.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$services = getAllServices();
$doctors  = getAllDoctors();

$stats = [
    'doctors'    => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'doctor' AND is_active = 1")['c'],
    'patients'   => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'patient' AND is_active = 1")['c'],
    'services'   => db()->fetchOne("SELECT COUNT(*) as c FROM services WHERE is_active = 1")['c'],
    'experience' => 15,
];

$reserveUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

// First doctor for badge card
$featuredDoctor = !empty($doctors) ? $doctors[0] : null;

$pageTitle = 'Klinika Mjekësore — Kujdes për Shëndetin Tuaj';
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<!-- ================================================================
     HERO
     ================================================================ -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">

            <!-- Left: copy -->
            <div>
                <span class="eyebrow hero-eyebrow">Klinikë e certifikuar &middot; Që nga 2008</span>
                <h1 class="hero-title">
                    Kujdes <em>i butë,</em><br>
                    në çdo hap të<br>
                    jetës suaj.
                </h1>
                <p class="hero-sub">
                    Njëzet e tre specialitete mjekësore nën një çati — nga
                    diagnostikimi tek trajtimi. Ekipi ynë është këtu për ju
                    dhe familjen tuaj, me kohë, kujdes dhe dinjitet.
                </p>
                <div class="hero-actions">
                    <a href="<?= $reserveUrl ?>" class="btn btn-cta">
                        Rezervo Takim
                        <svg class="i i-sm" style="margin-left:6px" viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </a>
                    <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-ghost">Shiko shërbimet &rarr;</a>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <span class="num"><?= (int)$stats['doctors'] ?><em>+</em></span>
                        <span class="label">Mjekë specialistë</span>
                    </div>
                    <div class="hero-stat">
                        <span class="num"><?= (int)$stats['patients'] ?><em>+</em></span>
                        <span class="label">Pacientë të kujdesur</span>
                    </div>
                    <div class="hero-stat">
                        <span class="num"><?= (int)$stats['services'] ?><em>+</em></span>
                        <span class="label">Shërbime mjekësore</span>
                    </div>
                    <div class="hero-stat">
                        <span class="num"><?= (int)$stats['experience'] ?><em>+</em></span>
                        <span class="label">Vjet eksperiencë</span>
                    </div>
                </div>
            </div>

            <!-- Right: visual -->
            <div class="hero-visual">
                <?php
                $clinicImg = BASE_PATH . '/assets/img/clinic-building.jpg';
                if (file_exists($clinicImg)): ?>
                    <img src="<?= BASE_URL ?>/assets/img/clinic-building.jpg" alt="Vitanova Clinic">
                <?php else: ?>
                    <div class="hero-visual-placeholder">[ Foto e klinikës ]</div>
                <?php endif; ?>

                <?php if ($featuredDoctor): ?>
                <div class="badge-card">
                    <div class="pic"><?= e(getInitials($featuredDoctor['name'])) ?></div>
                    <div>
                        <div class="label">Konsultim sot</div>
                        <div class="name"><?= e($featuredDoctor['name']) ?> &middot; 14:30</div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</section>

<!-- ================================================================
     VALUES
     ================================================================ -->
<section class="values">
    <div class="container values-grid">
        <div class="value-item">
            <h4><span class="value-num">01</span> Pacienti në qendër</h4>
            <p>Çdo plan trajtimi fillon me dëgjim. Ne ndërtojmë kujdesin rreth jush — jo anasjelltas.</p>
        </div>
        <div class="value-item">
            <h4><span class="value-num">02</span> Cilësi &amp; siguri</h4>
            <p>Standarde ndërkombëtare, pajisje moderne dhe protokolle klinike të dokumentuara.</p>
        </div>
        <div class="value-item">
            <h4><span class="value-num">03</span> Për të gjithë</h4>
            <p>Njëzet e tre specialitete nën një çati — për fëmijët, të rriturit dhe familjet.</p>
        </div>
    </div>
</section>

<!-- ================================================================
     SERVICES — editorial list
     ================================================================ -->
<section class="section services-section">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Shërbimet — Çfarë ofrojmë</span>
            <h2>Specialitete të zgjedhura, <em class="serif-italic">të lidhura me kujdes</em>.</h2>
            <p>Një drejtim i vetëm për gjithçka që ju dhe familja juaj keni nevojë — nga vizita të rregullta deri tek konsulta të specializuara.</p>
        </div>

        <?php if (!empty($services)): ?>
        <div class="services-list">
            <?php foreach (array_slice($services, 0, 6) as $i => $svc): ?>
            <a class="service-row" href="<?= BASE_URL ?>/public/services.php">
                <span class="num"><?= str_pad($i + 1, 2, '0', STR_PAD_LEFT) ?></span>
                <span class="name"><?= e($svc['name']) ?></span>
                <span class="desc"><?= e($svc['description'] ?? $svc['category'] ?? '') ?></span>
                <span class="price"><?= formatPrice((float)$svc['price']) ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="text-center mt-40">
            <a href="<?= BASE_URL ?>/public/services.php" class="btn btn-outline">Të gjitha shërbimet</a>
        </div>
    </div>
</section>

<!-- ================================================================
     DOCTORS
     ================================================================ -->
<?php if (!empty($doctors)): ?>
<section class="section" style="background:var(--color-bg);">
    <div class="container">
        <div class="section-head">
            <span class="eyebrow">Ekipi ynë — Mjekët</span>
            <h2>Specialistë të kujdesshëm, <em class="serif-italic">me përvojë në çdo fushë</em>.</h2>
        </div>

        <div class="doctors-grid">
            <?php foreach (array_slice($doctors, 0, 4) as $doctor): ?>
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
                <div class="doctor-meta">
                    <span class="fee">
                        <em>Konsultim</em><?= formatPrice((float)$doctor['consultation_fee']) ?>
                    </span>
                    <a href="<?= $reserveUrl ?>?doctor_id=<?= (int)$doctor['id'] ?>"
                       class="btn btn-ghost btn-sm">Rezervo &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center mt-40">
            <a href="<?= BASE_URL ?>/public/doctors.php" class="btn btn-outline">Shiko të gjithë mjekët</a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ================================================================
     CTA
     ================================================================ -->
<section class="cta-section">
    <div class="container">
        <h2>Kujdesi që meritoni — <em>vetëm një rezervim larg</em>.</h2>
        <p>Hapni një llogari falas dhe rezervoni takimin tuaj online në më pak se 60 sekonda.</p>
        <div class="cta-actions">
            <a href="<?= $reserveUrl ?>" class="btn btn-cta btn-lg">
                Rezervo Takim
                <svg class="i i-sm" style="margin-left:6px" viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
            </a>
            <a href="<?= BASE_URL ?>/public/contact.php" class="btn btn-outline btn-lg">Na kontaktoni</a>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
