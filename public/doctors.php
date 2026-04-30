<?php
// ============================================================
// public/doctors.php - LOGJIKA PHP (backend)
// 
// frontend shton HTML/CSS poshtë këtij kodi
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// ---- Filtrim sipas specializimit (nga URL ?specialization=Kardiologji) ----
$filterSpec = clean($_GET['specialization'] ?? '');

// ---- Merr mjekët nga DB ----
if (!empty($filterSpec)) {
    $doctors = getDoctorsBySpecialization($filterSpec);
} else {
    $doctors = getAllDoctors();
}

// ---- Specializimet unike për filter dropdown ----
$specializations = db()->fetchAll(
    "SELECT DISTINCT specialization
     FROM users
     WHERE role = 'doctor' AND is_active = 1
     AND specialization IS NOT NULL
     ORDER BY specialization ASC"
);

// ---- URL rezervimi sipas rolit ----
// Përdoret kështu në HTML:
// <a href="<?= $reserveBaseUrl ?>?doctor_id=<?= $doctor['id'] ?>">Rezervo</a>
$reserveBaseUrl = (isLoggedIn() && hasRole(ROLE_PATIENT))
    ? BASE_URL . '/patient/reserve.php'
    : BASE_URL . '/public/register.php';

$pageTitle = 'Mjekët Tanë — ' . APP_NAME;
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';

// ============================================================
// FRONTEND SHTON HTML KËTU
// Variablat e disponueshme:
//   $doctors        → array me mjekët (filtrim ose të gjithë)
//   $specializations → array për dropdown filter
//   $filterSpec     → specializimi aktual i zgjedhur
//   $reserveBaseUrl → URL bazë për butonin Rezervo
//
// Çdo $doctor ka: id, name, email, phone, specialization,
//                 bio, photo_path, consultation_fee
//
// Funksione të gatshme:
//   e($str)             → XSS safe output
//   formatPrice($price) → "3,000 L"
//   getInitials($name)  → "AH"
//   getPhotoUrl($path)  → URL foto ose placeholder
//   displayFlashMessage() → shfaq mesazhet flash
// ============================================================

include BASE_PATH . '/includes/footer.php';