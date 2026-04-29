<?php
// ============================================================
// public/services.php - LOGJIKA PHP (backend)
// frontend shton HTML/CSS poshtë këtij kodi
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// ---- Merr të gjitha shërbimet aktive ----
$services = getAllServices();

// ---- Kategorite unike për filter butonë ----
$categories = db()->fetchAll(
    "SELECT DISTINCT category FROM services
     WHERE is_active = 1
     ORDER BY category ASC"
);

// ---- URL rezervimi sipas rolit ----
// Përdoret kështu në HTML:
// <a href="<?= $reserveBaseUrl ?>?service_id=<?= $service['id'] ?>">Rezervo</a>
$reserveBaseUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$pageTitle = 'Shërbimet — ' . APP_NAME;
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';

// ============================================================
// FRONTEND SHTON HTML KËTU
// Variablat e disponueshme:
//   $services       → array me të gjitha shërbimet aktive
//   $categories     → array me kategorite unike
//   $reserveBaseUrl → URL bazë për butonin Rezervo
//
// Çdo $service ka: id, name, description, category, price, icon
//
// Funksione të gatshme:
//   e($str)             → XSS safe output
//   formatPrice($price) → "3,000 L"
//   displayFlashMessage() → shfaq mesazhet flash
// ============================================================

include BASE_PATH . '/includes/footer.php';
