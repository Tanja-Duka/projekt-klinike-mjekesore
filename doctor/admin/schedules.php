<?php
// ============================================================
// admin/schedules.php - Cakto oraret e mjekëve
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors        = [];
$selectedDoctor = cleanInt($_GET['doctor_id'] ?? 0);

// Shto / Ndrysho orar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $action    = clean($_POST['action']    ?? '');
    $doctorId  = cleanInt($_POST['doctor_id']  ?? 0);
    $dayOfWeek = clean($_POST['day_of_week']   ?? '');
    $startTime = clean($_POST['start_time']    ?? '');
    $endTime   = clean($_POST['end_time']      ?? '');

    $validDays = array_keys(DAYS_SQ);

    if ($action === 'add') {
        if ($doctorId <= 0 || !in_array($dayOfWeek, $validDays) || empty($startTime) || empty($endTime)) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } elseif ($startTime >= $endTime) {
            $errors[] = 'Ora e fillimit duhet të jetë para orës së mbarimit.';
        } else {
            // Kontrollo konflikte — nëse ekziston tashmë ky ditë+mjek, update
            $existing = db()->fetchOne(
                "SELECT id FROM schedules WHERE doctor_id = ? AND day_of_week = ?",
                [$doctorId, $dayOfWeek]
            );
            if ($existing) {
                db()->execute(
                    "UPDATE schedules SET start_time=?, end_time=?, is_available=1 WHERE id=?",
                    [$startTime, $endTime, $existing['id']]
                );
            } else {
                db()->insert(
                    "INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, is_available)
                     VALUES (?, ?, ?, ?, 1)",
                    [$doctorId, $dayOfWeek, $startTime, $endTime]
                );
            }
            setFlashMessage('success', 'Orari u ruajt me sukses!');
            redirect(BASE_URL . '/doctor/admin/schedules.php?doctor_id=' . $doctorId);
        }
    }

    if ($action === 'delete') {
        $scheduleId = cleanInt($_POST['schedule_id'] ?? 0);
        if ($scheduleId > 0) {
            db()->execute("DELETE FROM schedules WHERE id = ?", [$scheduleId]);
            setFlashMessage('success', 'Orari u fshi.');
        }
        redirect(BASE_URL . '/doctor/admin/schedules.php?doctor_id=' . $doctorId);
    }
}

// Oraret e mjekut të zgjedhur
$schedule = $selectedDoctor > 0 ? getDoctorSchedule($selectedDoctor) : [];
$doctors  = getAllDoctors();
$days     = DAYS_SQ;
$pageTitle = 'Oraret e Mjekëve';

