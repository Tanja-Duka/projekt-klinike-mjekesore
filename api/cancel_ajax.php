<?php
// ============================================================
// api/cancel_ajax.php - Anulim takimi (AJAX POST)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/email.php';

header('Content-Type: application/json');

if (!isAjaxRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => ERR_ACCESS_DENIED]);
    exit;
}

// Kërko login me rolin patient
if (!isLoggedIn() || !hasRole(ROLE_PATIENT)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => ERR_ACCESS_DENIED]);
    exit;
}

// Valido CSRF
verifyCsrfOrDie();

$appointmentId = cleanInt($_POST['appointment_id'] ?? 0);
$patientId     = getCurrentUserId();

if ($appointmentId <= 0) {
    jsonResponse(false, ERR_REQUIRED_FIELDS);
}

// Merr takimin dhe kontrollo pronësinë
$appointment = getAppointmentById($appointmentId);

if (!$appointment) {
    jsonResponse(false, 'Takimi nuk u gjet.');
}

if ((int)$appointment['patient_id'] !== $patientId) {
    jsonResponse(false, ERR_ACCESS_DENIED);
}

// Nuk mund të anulohet nëse është kryer ose tashmë i anuluar
if (in_array($appointment['status'], [STATUS_COMPLETED, STATUS_CANCELLED])) {
    jsonResponse(false, 'Ky takim nuk mund të anulohet.');
}

// UPDATE statusi → cancelled
$affected = db()->execute(
    "UPDATE appointments SET status = ? WHERE id = ? AND patient_id = ?",
    [STATUS_CANCELLED, $appointmentId, $patientId]
);

if ($affected === 0) {
    jsonResponse(false, ERR_GENERAL);
}

// Dërgo email njoftimi anulimi
$patient = getUserById($patientId);
if ($patient) {
    sendCancellationEmail($patient['email'], $patient['name'], $appointment);
}

jsonResponse(true, MSG_APPOINTMENT_CANCELLED);
