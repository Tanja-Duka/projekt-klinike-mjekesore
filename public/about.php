<?php
// ============================================================
// public/about.php
// ============================================================
require_once dirname(__DIR__) . '/config/config.php';

$stats = [
    'doctors'    => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role=? AND is_active=1", [ROLE_DOCTOR])['c'],
    'patients'   => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role=? AND is_active=1", [ROLE_PATIENT])['c'],
    'experience' => 15,
];

$reserveUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$pageTitle = 'Rreth Nesh — ' . APP_NAME;
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<!-- Page header -->
<section class="page-header">
    <div class="container">
        <div class="eyebrow">Rreth nesh — Që nga 2008</div>
        <h1>Një klinikë <em>e ndërtuar mbi besim</em>.</h1>
        <p>Vitanova nisi në një kat të vetëm me tre mjekë. Sot, jemi specialistë nën të njëjtin parim: kujdes me kohën e duhur, për çdo pacient.</p>
    </div>
</section>

<section class="section">
    <div class="container">

        <!-- Historia -->
        <div class="about-grid">
            <div>
                <div class="eyebrow mb-16">Historia</div>
                <h2 class="mb-24" style="font-size:2rem;">
                    Filluam si tre mjekë <em class="serif-italic">që donin më shumë kohë</em> me pacientët.
                </h2>
                <p>Në vitin 2008, themelueset hapën një praktikë të vogël në qendër të Prishtinës me një ide të thjeshtë: takimet duhet të zgjasin sa duhen — jo sa lejon orari.</p>
                <p style="margin-top:16px;">Sot kemi tre kate, 23 specialitete dhe një laborator të integruar — por filozofia është e njëjta. Çdo pacient takohet pa nxitim, dëgjohet plotësisht dhe ndiqet derisa të kthehet në formë.</p>
                <p style="margin-top:16px;">Besojmë se mjekësia më e mirë vjen nga marrëdhënia e gjatë mjek–pacient. Prandaj 8 nga 10 pacientë tanë vijnë rregullisht, ndërsa 6 nga 10 na rekomandojnë familjarëve.</p>
            </div>
            <div class="about-image"><img src="<?= BASE_URL ?>/assets/img/interior.jpeg" alt="Klinika Vitanova" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"></div>
        </div>

        <!-- Stats -->
        <div class="about-stats">
            <div class="about-stat">
                <span class="num"><?= (int)$stats['experience'] ?><em>+</em></span>
                <span class="label">Vjet eksperiencë</span>
            </div>
            <div class="about-stat">
                <span class="num"><?= (int)$stats['doctors'] ?><em>+</em></span>
                <span class="label">Mjekë specialistë</span>
            </div>
            <div class="about-stat">
                <span class="num"><?= (int)$stats['patients'] ?><em>+</em></span>
                <span class="label">Pacientë të kujdesur</span>
            </div>
        </div>

        <!-- Vlerat -->
        <div class="about-grid">
            <div class="about-image"><img src="<?= BASE_URL ?>/assets/img/ekipi.jpeg" alt="Ekipi i Vitanova" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;"></div>
            <div>
                <div class="eyebrow mb-16">Vlerat</div>
                <h2 class="mb-24" style="font-size:2rem;">
                    Çfarë na <em class="serif-italic">përkufizon</em>.
                </h2>
                <p><strong>Pacienti i parë.</strong> Çdo vendim klinik fillon nga pyetja: çfarë është më e mirë për këtë person, sot?</p>
                <p style="margin-top:14px;"><strong>Transparencë.</strong> Çmime të qarta. Vendime të shpjeguara. Asnjë surprizë në fund të vizitës.</p>
                <p style="margin-top:14px;"><strong>Vazhdimësi.</strong> Sistemi ynë dixhital ruan historikun, kështu që ju nuk e tregoni dy herë të njëjtin tregim.</p>
                <p style="margin-top:14px;"><strong>Kujdes i kohës së duhur.</strong> Takim 30+ minuta për konsulta të para. Takim sot nëse është urgjente.</p>
            </div>
        </div>

    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Bashkohuni me familjet që <em>na besojnë shëndetin</em>.</h2>
        <div class="cta-actions">
            <a href="<?= $reserveUrl ?>" class="btn btn-cta btn-lg">Rezervo Takim</a>
            <a href="<?= BASE_URL ?>/public/contact.php" class="btn btn-outline btn-lg">Vizitoni klinikën</a>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
