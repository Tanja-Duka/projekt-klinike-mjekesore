<?php
// ============================================================
// functions.php - Funksione ndihmëse të përgjithshme
// ============================================================

// ==============================================================
// FLASH MESSAGES
// ==============================================================

// ---- Vendos mesazh flash në sesion ----
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,   // 'success' | 'error' | 'info' | 'warning'
        'message' => $message
    ];
}

// ---- Merr dhe fshi mesazhin flash nga sesioni ----
function getFlashMessage(): array|null {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ---- Shfaq mesazhin flash si HTML ----
function displayFlashMessage(): void {
    $flash = getFlashMessage();
    if (!$flash) return;

    $type = e($flash['type']);
    $msg  = e($flash['message']);
    echo "<div class=\"alert alert-{$type}\" role=\"alert\">{$msg}</div>";
}

// ==============================================================
// RIDREJTIM
// ==============================================================

// ---- Ridrejto tek URL dhe ndalo ekzekutimin ----
function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

// ---- Ridrejto sipas rolit të userit ----
function redirectByRole(): void {
    switch (getCurrentRole()) {
        case ROLE_PATIENT: redirect(REDIRECT_PATIENT); break;
        case ROLE_DOCTOR:  redirect(REDIRECT_DOCTOR);  break;
        case ROLE_ADMIN:   redirect(REDIRECT_ADMIN);   break;
        default:           redirect(REDIRECT_LOGIN);
    }
}

// ==============================================================
// USERS
// ==============================================================

// ---- Merr userin sipas ID ----
function getUserById(int $id): array|false {
    return db()->fetchOne(
        "SELECT * FROM users WHERE id = ? AND is_active = 1",
        [$id]
    );
}

// ---- Merr userin sipas email ----
function getUserByEmail(string $email): array|false {
    return db()->fetchOne(
        "SELECT * FROM users WHERE email = ?",
        [strtolower($email)]
    );
}

// ---- Kontrollo nëse emaili ekziston ----
function emailExists(string $email): bool {
    $result = db()->fetchOne(
        "SELECT id FROM users WHERE email = ?",
        [strtolower($email)]
    );
    return !empty($result);
}

// ==============================================================
// DOCTORS
// ==============================================================

// ---- Merr të gjithë mjekët aktivë ----
function getAllDoctors(): array {
    return db()->fetchAll(
        "SELECT id, name, email, phone, specialization, bio,
                photo_path, consultation_fee
         FROM users
         WHERE role = ? AND is_active = 1
         ORDER BY name ASC",
        [ROLE_DOCTOR]
    );
}

// ---- Merr mjekët sipas specializimit ----
function getDoctorsBySpecialization(string $spec): array {
    return db()->fetchAll(
        "SELECT id, name, specialization, photo_path, consultation_fee
         FROM users
         WHERE role = ? AND specialization = ? AND is_active = 1
         ORDER BY name ASC",
        [ROLE_DOCTOR, $spec]
    );
}

// ---- Merr mjekun sipas ID ----
function getDoctorById(int $id): array|false {
    return db()->fetchOne(
        "SELECT * FROM users WHERE id = ? AND role = ? AND is_active = 1",
        [$id, ROLE_DOCTOR]
    );
}

// ==============================================================
// PATIENTS
// ==============================================================

// ---- Merr të gjithë pacientët aktivë ----
function getAllPatients(): array {
    return db()->fetchAll(
        "SELECT id, name, email, phone, date_of_birth,
                blood_type, address, created_at
         FROM users
         WHERE role = ? AND is_active = 1
         ORDER BY name ASC",
        [ROLE_PATIENT]
    );
}

// ---- Merr pacientin sipas ID ----
function getPatientById(int $id): array|false {
    return db()->fetchOne(
        "SELECT * FROM users WHERE id = ? AND role = ? AND is_active = 1",
        [$id, ROLE_PATIENT]
    );
}

// ==============================================================
// APPOINTMENTS
// ==============================================================

// ---- Merr takimet e pacientit ----
function getAppointmentsByPatient(int $patientId, string $status = ''): array {
    $sql = "SELECT a.*,
                   u.name AS doctor_name,
                   u.specialization,
                   u.photo_path AS doctor_photo,
                   s.name AS service_name,
                   s.price
            FROM appointments a
            JOIN users u ON a.doctor_id = u.id
            JOIN services s ON a.service_id = s.id
            WHERE a.patient_id = ?";

    $params = [$patientId];

    if (!empty($status)) {
        $sql .= " AND a.status = ?";
        $params[] = $status;
    }

    $sql .= " ORDER BY a.appointment_date DESC, a.time_slot DESC";

    return db()->fetchAll($sql, $params);
}

// ---- Merr takimet e mjekut ----
function getAppointmentsByDoctor(int $doctorId, string $date = ''): array {
    $sql = "SELECT a.*,
                   u.name AS patient_name,
                   u.phone AS patient_phone,
                   s.name AS service_name
            FROM appointments a
            JOIN users u ON a.patient_id = u.id
            JOIN services s ON a.service_id = s.id
            WHERE a.doctor_id = ?";

    $params = [$doctorId];

    if (!empty($date)) {
        $sql .= " AND a.appointment_date = ?";
        $params[] = $date;
    }

    $sql .= " ORDER BY a.appointment_date ASC, a.time_slot ASC";

    return db()->fetchAll($sql, $params);
}

// ---- Merr një takim sipas ID ----
function getAppointmentById(int $id): array|false {
    return db()->fetchOne(
        "SELECT a.*,
                p.name AS patient_name, p.email AS patient_email,
                p.phone AS patient_phone,
                d.name AS doctor_name, d.specialization,
                s.name AS service_name, s.price
         FROM appointments a
         JOIN users p ON a.patient_id = p.id
         JOIN users d ON a.doctor_id  = d.id
         JOIN services s ON a.service_id = s.id
         WHERE a.id = ?",
        [$id]
    );
}

// ==============================================================
// PRESCRIPTIONS
// ==============================================================

// ---- Merr recetat e pacientit ----
function getPrescriptionsByPatient(int $patientId): array {
    return db()->fetchAll(
        "SELECT pr.*,
                u.name AS doctor_name,
                u.specialization,
                a.appointment_date,
                s.name AS service_name
         FROM prescriptions pr
         JOIN users u ON pr.doctor_id = u.id
         JOIN appointments a ON pr.appointment_id = a.id
         JOIN services s ON a.service_id = s.id
         WHERE pr.patient_id = ?
         ORDER BY pr.uploaded_at DESC",
        [$patientId]
    );
}

// ==============================================================
// SERVICES
// ==============================================================

// ---- Merr të gjitha shërbimet aktive ----
function getAllServices(): array {
    return db()->fetchAll(
        "SELECT * FROM services WHERE is_active = 1 ORDER BY category ASC, name ASC"
    );
}

// ---- Merr shërbimin sipas ID ----
function getServiceById(int $id): array|false {
    return db()->fetchOne(
        "SELECT * FROM services WHERE id = ? AND is_active = 1",
        [$id]
    );
}

// ==============================================================
// SCHEDULES
// ==============================================================

// ---- Merr orarin e mjekut ----
function getDoctorSchedule(int $doctorId): array {
    return db()->fetchAll(
        "SELECT * FROM schedules
         WHERE doctor_id = ? AND is_available = 1
         ORDER BY FIELD(day_of_week,
            'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'
         )",
        [$doctorId]
    );
}

// ---- Kontrollo nëse mjeku është i disponueshëm në një datë ----
function isDoctorAvailable(int $doctorId, string $date): bool {
    $dayOfWeek = date('l', strtotime($date)); // p.sh. 'Monday'
    $result = db()->fetchOne(
        "SELECT id FROM schedules
         WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1",
        [$doctorId, $dayOfWeek]
    );
    return !empty($result);
}

// ---- Merr slot-et e zëna për një mjek + datë ----
function getTakenSlots(int $doctorId, string $date): array {
    $rows = db()->fetchAll(
        "SELECT time_slot FROM appointments
         WHERE doctor_id = ? AND appointment_date = ?
         AND status IN (?, ?)",
        [$doctorId, $date, STATUS_PENDING, STATUS_CONFIRMED]
    );
    return array_column($rows, 'time_slot');
}

// ---- Gjenero slot-et e lira (çdo 30 min) ----
function getAvailableSlots(int $doctorId, string $date): array {
    $dayOfWeek = date('l', strtotime($date));

    $schedule = db()->fetchOne(
        "SELECT start_time, end_time FROM schedules
         WHERE doctor_id = ? AND day_of_week = ? AND is_available = 1",
        [$doctorId, $dayOfWeek]
    );

    if (!$schedule) return [];

    $takenSlots = getTakenSlots($doctorId, $date);
    $slots      = [];

    $start = strtotime($schedule['start_time']);
    $end   = strtotime($schedule['end_time']);

    while ($start < $end) {
        $slotTime = date('H:i', $start);
        if (!in_array($slotTime, $takenSlots)) {
            $slots[] = $slotTime;
        }
        $start += 30 * 60; // +30 minuta
    }

    return $slots;
}

// ==============================================================
// STATISTIKA (për admin dashboard)
// ==============================================================

function getDashboardStats(): array {
    return [
        'total_doctors'      => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = ? AND is_active = 1", [ROLE_DOCTOR])['c'],
        'total_patients'     => db()->fetchOne("SELECT COUNT(*) as c FROM users WHERE role = ? AND is_active = 1", [ROLE_PATIENT])['c'],
        'appointments_today' => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE appointment_date = CURDATE()")['c'],
        'pending_queries'    => db()->fetchOne("SELECT COUNT(*) as c FROM contact_queries WHERE status = ?", [QUERY_UNREAD])['c'],
    ];
}

// ==============================================================
// FORMAT & DISPLAY
// ==============================================================

// ---- Formato datën në shqip ----
function formatDateSq(string $date): string {
    if (empty($date)) return '-';
    $months = MONTHS_SQ;
    $ts     = strtotime($date);
    return date('d', $ts) . ' ' . $months[(int)date('m', $ts)] . ' ' . date('Y', $ts);
}

// ---- Formato datë + orë ----
function formatDateTimeSq(string $datetime): string {
    if (empty($datetime)) return '-';
    $ts = strtotime($datetime);
    return formatDateSq(date('Y-m-d', $ts)) . ' ' . date('H:i', $ts);
}

// ---- Formato çmimin ----
function formatPrice(float $price): string {
    return number_format($price, 0, ',', '.') . ' L';
}

// ---- Kthe badge HTML për statusin e takimit ----
function getStatusBadge(string $status): string {
    $labels = [
        STATUS_PENDING   => ['label' => 'Në pritje',   'class' => 'status-pending'],
        STATUS_CONFIRMED => ['label' => 'Konfirmuar',  'class' => 'status-confirmed'],
        STATUS_COMPLETED => ['label' => 'Kryer',       'class' => 'status-completed'],
        STATUS_CANCELLED => ['label' => 'Anuluar',     'class' => 'status-cancelled'],
    ];

    $s = $labels[$status] ?? ['label' => $status, 'class' => 'status-unknown'];
    return '<span class="status-badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

// ---- Kthe inicialet nga emri i plotë ----
function getInitials(string $name): string {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach ($parts as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return substr($initials, 0, 2);
}

// ---- Kthe URL të sigurt të fotos ose placeholder ----
function getPhotoUrl(?string $photoPath, string $type = 'doctor'): string {
    if (!empty($photoPath) && file_exists(BASE_PATH . '/' . $photoPath)) {
        return BASE_URL . '/' . $photoPath;
    }
    // Placeholder sipas tipit
    return BASE_URL . '/assets/img/placeholder-' . $type . '.svg';
}

// ---- Paginacion ----
function paginate(int $totalRecords, int $currentPage, int $perPage = RECORDS_PER_PAGE): array {
    $totalPages = (int) ceil($totalRecords / $perPage);
    $offset     = ($currentPage - 1) * $perPage;

    return [
        'total'        => $totalRecords,
        'per_page'     => $perPage,
        'current_page' => $currentPage,
        'total_pages'  => $totalPages,
        'offset'       => $offset,
        'has_prev'     => $currentPage > 1,
        'has_next'     => $currentPage < $totalPages,
    ];
}

// ---- Kthe përgjigje JSON (për AJAX) ----
function jsonResponse(bool $success, string $message, array $data = []): void {
    header('Content-Type: application/json');
    echo json_encode(array_merge(
        ['success' => $success, 'message' => $message],
        $data
    ));
    exit;
}
