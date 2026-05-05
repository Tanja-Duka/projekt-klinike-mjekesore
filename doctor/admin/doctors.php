<?php
// ============================================================
// admin/doctors.php - CRUD mjekët
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors  = [];
$success = '';
$action  = clean($_POST['action'] ?? $_GET['action'] ?? '');

// ---- SHTO mjek të ri ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    verifyCsrfOrDie();

    $name           = clean($_POST['name']             ?? '');
    $email          = cleanEmail($_POST['email']        ?? '');
    $phone          = clean($_POST['phone']            ?? '');
    $specialization = clean($_POST['specialization']   ?? '');
    $bio            = clean($_POST['bio']              ?? '');
    $fee            = cleanFloat($_POST['consultation_fee'] ?? 0);
    $password       = $_POST['password'] ?? '';

    if (empty($name) || !$email || empty($specialization) || empty($password)) {
        $errors[] = ERR_REQUIRED_FIELDS;
    }
    if ($email && emailExists($email)) {
        $errors[] = ERR_EMAIL_EXISTS;
    }
    if (!isValidPassword($password)) {
        $errors[] = 'Fjalëkalimi duhet të ketë minimum 8 karaktere, 1 shkronjë të madhe dhe 1 numër.';
    }

    if (empty($errors)) {
        db()->insert(
            "INSERT INTO users (name, email, phone, role, password_hash, specialization, bio, consultation_fee, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$name, $email, $phone, ROLE_DOCTOR, password_hash($password, PASSWORD_DEFAULT),
             $specialization, $bio, $fee]
        );
        setFlashMessage('success', MSG_DOCTOR_ADDED);
        redirect(BASE_URL . '/doctor/admin/doctors.php');
    }
}

// ---- EDITO mjekun ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    verifyCsrfOrDie();

    $editId         = cleanInt($_POST['doctor_id']      ?? 0);
    $name           = clean($_POST['name']             ?? '');
    $phone          = clean($_POST['phone']            ?? '');
    $specialization = clean($_POST['specialization']   ?? '');
    $bio            = clean($_POST['bio']              ?? '');
    $fee            = cleanFloat($_POST['consultation_fee'] ?? 0);

    if (empty($name) || $editId <= 0) {
        $errors[] = ERR_REQUIRED_FIELDS;
    }

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET name=?, phone=?, specialization=?, bio=?, consultation_fee=?
             WHERE id=? AND role=?",
            [$name, $phone, $specialization, $bio, $fee, $editId, ROLE_DOCTOR]
        );
        setFlashMessage('success', MSG_DOCTOR_UPDATED);
        redirect(BASE_URL . '/doctor/admin/doctors.php');
    }
}

// ---- FSHIJ (soft delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrfOrDie();
    $deleteId = cleanInt($_POST['doctor_id'] ?? 0);
    if ($deleteId > 0) {
        db()->execute(
            "UPDATE users SET is_active = 0 WHERE id = ? AND role = ?",
            [$deleteId, ROLE_DOCTOR]
        );
        setFlashMessage('success', MSG_DOCTOR_DELETED);
    }
    redirect(BASE_URL . '/doctor/admin/doctors.php');
}

// Lista e të gjithë mjekëve (aktivë + joaktivë)
$doctors         = db()->fetchAll(
    "SELECT * FROM users WHERE role = ? ORDER BY is_active DESC, name ASC",
    [ROLE_DOCTOR]
);
$specializations = SPECIALIZATIONS;
$pageTitle       = 'Menaxho Mjekët';

