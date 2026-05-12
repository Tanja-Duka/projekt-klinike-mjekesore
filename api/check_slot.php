<?php
// ============================================================
// api/check_slot.php - Kthe slot-et e lira për mjek + datë (AJAX GET)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

// Vetëm kërkesa AJAX lejohen
if (!isAjaxRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => ERR_ACCESS_DENIED]);
    exit;
}

// Merr dhe valido parametrat
$doctorId = cleanInt($_GET['doctor_id'] ?? 0);
$date     = clean($_GET['date'] ?? '');

if ($doctorId <= 0 || empty($date)) {
    echo json_encode(['success' => false, 'message' => ERR_REQUIRED_FIELDS, 'slots' => []]);
    exit;
}

// Valido formatin e datës
if (!isValidDate($date)) {
    echo json_encode(['success' => false, 'message' => ERR_INVALID_DATE, 'slots' => []]);
    exit;
}

// Nuk lejohen datat e kaluara
if (!isFutureDate($date)) {
    echo json_encode(['success' => false, 'message' => ERR_PAST_DATE, 'slots' => []]);
    exit;
}

// Kontrollo nëse mjeku ekziston
$doctor = getDoctorById($doctorId);
if (!$doctor) {
    echo json_encode(['success' => false, 'message' => 'Mjeku nuk u gjet.', 'slots' => []]);
    exit;
}

// Gjenero slot-et e disponueshme (çdo 30 min sipas orarit të mjekut)
$slots = getAvailableSlots($doctorId, $date);

echo json_encode([
    'success'   => true,
    'slots'     => $slots,
    'available' => count($slots) > 0,
]);
