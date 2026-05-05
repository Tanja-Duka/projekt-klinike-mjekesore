<?php
// ============================================================
// admin/reports.php - Raportet e klinikës (GET-only, pa CSRF)
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Periudha e filtrimit
$dateFrom = clean($_GET['date_from'] ?? date('Y-m-01')); // fillim muaji aktual si default
$dateTo   = clean($_GET['date_to']   ?? date('Y-m-d'));

// Valido datat (kthimi tek default nëse janë të gabuara)
if (!isValidDate($dateFrom)) $dateFrom = date('Y-m-01');
if (!isValidDate($dateTo))   $dateTo   = date('Y-m-d');

// Raport 1: Takimet sipas periudhës
$appointmentReport = db()->fetchAll(
    "SELECT a.appointment_date, COUNT(*) AS total,
            SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN a.status IN (?,?) THEN 1 ELSE 0 END) AS pending
     FROM appointments a
     WHERE a.appointment_date BETWEEN ? AND ?
     GROUP BY a.appointment_date
     ORDER BY a.appointment_date ASC",
    [STATUS_COMPLETED, STATUS_CANCELLED, STATUS_PENDING, STATUS_CONFIRMED, $dateFrom, $dateTo]
);

// Raport 2: Të ardhurat (vetëm takimet e kryera)
$revenueReport = db()->fetchOne(
    "SELECT COUNT(*) AS total_completed,
            SUM(s.price) AS total_revenue
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.status = ? AND a.appointment_date BETWEEN ? AND ?",
    [STATUS_COMPLETED, $dateFrom, $dateTo]
);

// Raport 3: TOP 5 mjekë (sipas numrit të takimeve të kryera)
$topDoctors = db()->fetchAll(
    "SELECT u.name, u.specialization,
            COUNT(a.id) AS total_appointments,
            SUM(s.price) AS revenue
     FROM appointments a
     JOIN users u ON a.doctor_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.status = ? AND a.appointment_date BETWEEN ? AND ?
     GROUP BY u.id, u.name, u.specialization
     ORDER BY total_appointments DESC
     LIMIT 5",
    [STATUS_COMPLETED, $dateFrom, $dateTo]
);

// Raport 4: Shërbimet më të kërkuara
$topServices = db()->fetchAll(
    "SELECT s.name, s.category, COUNT(a.id) AS bookings, s.price
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.appointment_date BETWEEN ? AND ?
     GROUP BY s.id, s.name, s.category, s.price
     ORDER BY bookings DESC
     LIMIT 5",
    [$dateFrom, $dateTo]
);

$pageTitle = 'Raportet';

