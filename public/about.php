<?php
// Mos include header/navbar sërish — janë bërë sipër
include BASE_PATH . '/includes/footer.php';
// Mos ekzekuto kodi tjetër
exit;


$pageTitle = 'Rreth Nesh';
$cssFile   = 'home.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Rreth Nesh</h1>
        <p>Misioni ynë është të ofrojmë kujdes shëndetësor të cilësisë së lartë për të gjithë</p>
    </div>
</div>

<section class="section" style="background:#fff;">
    <div class="container">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:60px;align-items:center;">
            <div>
                <h2>Historia jonë</h2>
                <p>Vitanova Clinic u themelua me misionin për të ofruar kujdes shëndetësor të përshtatshëm, cilësor dhe të aksesueshëm për të gjithë. Klinika jonë bashkon specialistë të fushave të ndryshme mjekësore nën një çati, duke ofruar shërbime gjithëpërfshirëse.</p>
                <p>Me ekip të dedikuar mjekësh dhe infermierësh, ne punojmë çdo ditë për të përmirësuar shëndetin dhe cilësinë e jetës së pacientëve tanë.</p>
                <div class="hero-divider mt-16 mb-16"></div>
                <h3>Misioni ynë</h3>
                <p>Të ofrojmë kujdes shëndetësor me cilësi të lartë, me respekt dhe integritet, duke vënë pacientin në qendër të çdo vendimi.</p>
            </div>
            <div class="about-stats">
                <div class="about-stat">
                    <span class="about-stat-number">15+</span>
                    <p>Vite Eksperiencë</p>
                </div>
                <div class="about-stat">
                    <span class="about-stat-number"><?= (int)$stats['doctors'] ?>+</span>
                    <p>Mjekë Specialistë</p>
                </div>
                <div class="about-stat">
                    <span class="about-stat-number"><?= (int)$stats['patients'] ?>+</span>
                    <p>Pacientë të Trajtuar</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Vlerat -->
<section class="values-banner">
    <div class="container values-grid">
        <div class="value-item">
            <div class="value-icon">&#10084;</div>
            <div class="value-text">
                <h4>Kujdes me Zemër</h4>
                <p>Çdo pacient trajtohet me vëmendje, respekt dhe profesionalizëm.</p>
            </div>
        </div>
        <div class="value-item">
            <div class="value-icon">&#127891;</div>
            <div class="value-text">
                <h4>Ekspertizë e Lartë</h4>
                <p>Mjekë të certifikuar me trajnime ndërkombëtare dhe eksperiencë të gjerë.</p>
            </div>
        </div>
        <div class="value-item">
            <div class="value-icon">&#128269;</div>
            <div class="value-text">
                <h4>Teknologji Moderne</h4>
                <p>Pajisje diagnostike dhe mjekësore të nivelit të lartë.</p>
            </div>
        </div>
    </div>
</section>

<!-- Teknologjitë e përdorura (seksion akademik) -->
<section class="section" style="background:#fff;">
    <div class="container text-center">
        <h2>Teknologjitë e Projektit</h2>
        <p class="section-subtitle">Sistemi u ndërtua me teknologji moderne web</p>
        <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:20px;margin-top:32px;">
            <?php foreach (['PHP 8', 'MySQL', 'PDO', 'jQuery', 'HTML5', 'CSS3', 'PHPMailer', 'Google OAuth'] as $tech): ?>
            <span style="background:rgba(30,107,114,0.08);color:var(--color-primary);padding:8px 20px;border-radius:20px;font-weight:600;font-size:0.9rem;">
                <?= e($tech) ?>
            </span>
            <?php endforeach; ?>
        </div>
    </div>
</section>

