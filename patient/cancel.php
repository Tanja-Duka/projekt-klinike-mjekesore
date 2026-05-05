<?php
// ============================================================
// patient/cancel.php - Anulo rezervim (POST-only, fallback pa JS)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/email.php';

requireRole(ROLE_PATIENT);

// Vetëm POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/patient/appointments.php');
}

verifyCsrfOrDie();

$appointmentId = cleanInt($_POST['appointment_id'] ?? 0);
$patientId     = getCurrentUserId();

if ($appointmentId <= 0) {
    setFlashMessage('error', ERR_REQUIRED_FIELDS);
    redirect(BASE_URL . '/patient/appointments.php');
}

// Merr takimin dhe kontrollo pronësinë
$appointment = getAppointmentById($appointmentId);

if (!$appointment || (int)$appointment['patient_id'] !== $patientId) {
    setFlashMessage('error', ERR_ACCESS_DENIED);
    redirect(BASE_URL . '/patient/appointments.php');
}

if (in_array($appointment['status'], [STATUS_COMPLETED, STATUS_CANCELLED])) {
    setFlashMessage('error', 'Ky takim nuk mund të anulohet.');
    redirect(BASE_URL . '/patient/appointments.php');
}

// UPDATE statusi → cancelled
db()->execute(
    "UPDATE appointments SET status = ? WHERE id = ? AND patient_id = ?",
    [STATUS_CANCELLED, $appointmentId, $patientId]
);

// Dërgo email
$patient = getUserById($patientId);
if ($patient) {
    sendCancellationEmail($patient['email'], $patient['name'], $appointment);
}

setFlashMessage('success', MSG_APPOINTMENT_CANCELLED);
redirect(BASE_URL . '/patient/appointments.php');
