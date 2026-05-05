<?php
// ============================================================
// api/reserve_ajax.php - Rezervim takimi (AJAX POST)
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

// Merr dhe pastro inputin
$doctorId  = cleanInt($_POST['doctor_id']  ?? 0);
$serviceId = cleanInt($_POST['service_id'] ?? 0);
$date      = clean($_POST['date']          ?? '');
$timeSlot  = clean($_POST['time_slot']     ?? '');
$notes     = clean($_POST['notes']         ?? '');
$patientId = getCurrentUserId();

// Valido fushat e detyrueshme
if ($doctorId <= 0 || $serviceId <= 0 || empty($date) || empty($timeSlot)) {
    jsonResponse(false, ERR_REQUIRED_FIELDS);
}

// Valido datën
if (!isValidDate($date)) {
    jsonResponse(false, ERR_INVALID_DATE);
}
if (!isFutureDate($date)) {
    jsonResponse(false, ERR_PAST_DATE);
}

// Valido kohën
if (!isValidTime($timeSlot)) {
    jsonResponse(false, 'Ora e zgjedhur nuk është e vlefshme.');
}

// Kontrollo nëse mjeku ekziston
$doctor = getDoctorById($doctorId);
if (!$doctor) {
    jsonResponse(false, 'Mjeku nuk u gjet.');
}

// Kontrollo nëse shërbimi ekziston
$service = getServiceById($serviceId);
if (!$service) {
    jsonResponse(false, 'Shërbimi nuk u gjet.');
}

// Kontrollo nëse mjeku punon atë ditë
if (!isDoctorAvailable($doctorId, $date)) {
    jsonResponse(false, ERR_SLOT_UNAVAILABLE);
}

// Kontrollo nëse slot-i është ende i lirë
$takenSlots = getTakenSlots($doctorId, $date);
if (in_array($timeSlot, $takenSlots)) {
    jsonResponse(false, ERR_SLOT_TAKEN);
}

// Kontrollo nëse pacienti nuk ka takim tjetër po atë orë
$existing = db()->fetchOne(
    "SELECT id FROM appointments
     WHERE patient_id = ? AND appointment_date = ? AND time_slot = ?
     AND status IN (?, ?)",
    [$patientId, $date, $timeSlot, STATUS_PENDING, STATUS_CONFIRMED]
);
if ($existing) {
    jsonResponse(false, 'Keni tashmë një takim rezervuar për këtë orë.');
}

// INSERT takimin
$appointmentId = db()->insert(
    "INSERT INTO appointments (patient_id, doctor_id, service_id, appointment_date, time_slot, status, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?)",
    [$patientId, $doctorId, $serviceId, $date, $timeSlot, STATUS_PENDING, $notes]
);

if (!$appointmentId) {
    jsonResponse(false, ERR_GENERAL);
}

// Dërgo email konfirmimi
$patient = getUserById($patientId);
if ($patient) {
    sendConfirmationEmail($patient['email'], $patient['name'], [
        'doctor_name'      => $doctor['name'],
        'service_name'     => $service['name'],
        'appointment_date' => $date,
        'time_slot'        => $timeSlot,
    ]);
}

jsonResponse(true, MSG_APPOINTMENT_BOOKED, ['appointment_id' => (int)$appointmentId]);
