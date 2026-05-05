<?php
// ============================================================
// admin/patients.php - Menaxho pacientët
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Toggle aktivizim/çaktivizim
if ($_SERVER['REQUEST_METHOD'] === 'POST' && clean($_POST['action'] ?? '') === 'toggle') {
    verifyCsrfOrDie();
    $patientId = cleanInt($_POST['patient_id'] ?? 0);
    if ($patientId > 0) {
        db()->execute(
            "UPDATE users SET is_active = IF(is_active=1, 0, 1) WHERE id=? AND role=?",
            [$patientId, ROLE_PATIENT]
        );
    }
    redirect(BASE_URL . '/doctor/admin/patients.php');
}

// Shfaq historikun e një pacienti
$viewId  = cleanInt($_GET['view'] ?? 0);
$patient = null;
$history = [];

if ($viewId > 0) {
    $patient = getUserById($viewId);
    if ($patient && $patient['role'] === ROLE_PATIENT) {
        $history = db()->fetchAll(
            "SELECT a.*, d.name AS doctor_name, s.name AS service_name
             FROM appointments a
             JOIN users d ON a.doctor_id = d.id
             JOIN services s ON a.service_id = s.id
             WHERE a.patient_id = ?
             ORDER BY a.appointment_date DESC",
            [$viewId]
        );
    }
}

// Lista e gjithë pacientëve
$patients  = db()->fetchAll(
    "SELECT u.*, COUNT(a.id) AS total_appointments
     FROM users u
     LEFT JOIN appointments a ON a.patient_id = u.id
     WHERE u.role = ?
     GROUP BY u.id
     ORDER BY u.is_active DESC, u.name ASC",
    [ROLE_PATIENT]
);

$pageTitle = 'Menaxho Pacientët';
