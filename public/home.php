<?php
// ============================================================
// public/home.php - LOGJIKA PHP (backend)
// Shoqja e frontend shton HTML/CSS poshtë këtij kodi
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// ---- Të dhënat nga DB ----
$services = getAllServices();
$doctors  = getAllDoctors();

$stats = [
    'doctors'    => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'doctor' AND is_active = 1")['c'],
    'patients'   => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = 'patient' AND is_active = 1")['c'],
    'services'   => db()->fetchOne("SELECT COUNT(*) as c FROM services WHERE is_active = 1")['c'],
    'experience' => 15,
];

// ---- URL e butonit "Rezervo" sipas statusit të loginit ----
// Nëse është pacient i loguar → reserve.php
// Nëse jo → register.php
$reserveUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$pageTitle = 'Klinika Mjekësore — Kujdes për Shëndetin Tuaj';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>


// ============================================================
// FRONTEND SHTON HTML KËTU
// Variablat e disponueshme:
//   $services   → array me të gjitha shërbimet aktive
//   $doctors    → array me të gjithë mjekët aktivë
//   $stats      → ['doctors', 'patients', 'services', 'experience']
//   $reserveUrl → URL e duhur për butonin Rezervo
//
// Çdo $service ka: id, name, description, category, price, icon
// Çdo $doctor  ka: id, name, specialization, bio, photo_path,
//                  consultation_fee
//
// Funksione të gatshme për template:
//   e($str)               → shfaq string i sigurt (XSS)
//   formatPrice($price)   → "3,000 L"
//   getInitials($name)    → "AH" nga "Arben Hoxha"
//   getPhotoUrl($path)    → URL e fotos ose placeholder
//   isLoggedIn()          → true/false
//   hasRole(ROLE_PATIENT) → true/false
// ============================================================


<section class="hero-vitanova">
    <div class="container">
        <div class="hero-flex">
            <div class="hero-text">
                <h1>Kujdes i plotë për çdo hap të jetës.</h1>
                <p>Shërbime për të gjithë që ju dhe familja juaj keni nevojë – në çdo vend.</p>
                <div class="hero-btns">
                    <a href="<?= $reserveUrl ?>" class="btn-vitanova">Rezervo Takim</a>
                </div>
            </div>
            <div class="hero-image">
                <img src="<?= BASE_URL ?>/assets/images/vitanova-building.jpg" alt="Vitanova Clinic">
            </div>
        </div>
    </div>
</section>

<section class="stats-bar">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-user-friends"></i></div>
                <div class="stat-info">
                    <h4>PACIENTI NË QENDËR</h4>
                    <p>Kujdes i personalizuar për çdo kërkesë.</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-check-circle"></i></div>
                <div class="stat-info">
                    <h4>CILËSI & SIGURI</h4>
                    <p>Standardet më të larta mjekësore.</p>
                </div>
            </div>
            <div class="stat-item">
                <div class="stat-icon"><i class="fas fa-users"></i></div>
                <div class="stat-info">
                    <h4>PËR TË GJITHË</h4>
                    <p>Mjekësi e aksesueshme për çdo moshë.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php';