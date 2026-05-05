<?php
// ============================================================
// admin/appointments.php - Menaxho të gjitha takimet
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Ndrysho statusin e takimit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && clean($_POST['action'] ?? '') === 'status') {
    verifyCsrfOrDie();
    $apptId    = cleanInt($_POST['appointment_id'] ?? 0);
    $newStatus = clean($_POST['status'] ?? '');
    $allowed   = [STATUS_PENDING, STATUS_CONFIRMED, STATUS_COMPLETED, STATUS_CANCELLED];

    if ($apptId > 0 && in_array($newStatus, $allowed)) {
        db()->execute(
            "UPDATE appointments SET status = ? WHERE id = ?",
            [$newStatus, $apptId]
        );
    }
    redirect(BASE_URL . '/doctor/admin/appointments.php?' . http_build_query($_GET));
}

// Filtrat
$dateFrom = clean($_GET['date_from'] ?? '');
$dateTo   = clean($_GET['date_to']   ?? '');
$status   = clean($_GET['status']    ?? '');
$doctorId = cleanInt($_GET['doctor_id'] ?? 0);

$sql    = "SELECT a.*, p.name AS patient_name, d.name AS doctor_name, s.name AS service_name
           FROM appointments a
           JOIN users p ON a.patient_id = p.id
           JOIN users d ON a.doctor_id  = d.id
           JOIN services s ON a.service_id = s.id
           WHERE 1=1";
$params = [];

if (!empty($dateFrom) && isValidDate($dateFrom)) {
    $sql .= " AND a.appointment_date >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo) && isValidDate($dateTo)) {
    $sql .= " AND a.appointment_date <= ?";
    $params[] = $dateTo;
}
if (!empty($status)) {
    $sql .= " AND a.status = ?";
    $params[] = $status;
}
if ($doctorId > 0) {
    $sql .= " AND a.doctor_id = ?";
    $params[] = $doctorId;
}

$sql .= " ORDER BY a.appointment_date DESC, a.time_slot DESC";

$appointments = db()->fetchAll($sql, $params);
$doctors      = getAllDoctors();
$pageTitle    = 'Të Gjitha Takimet';

