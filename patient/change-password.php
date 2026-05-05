<?php
// ============================================================
// patient/change-password.php - Ndrysho fjalëkalimin
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();
$user      = getUserById($patientId);
$errors    = [];
$isGoogle  = empty($user['password_hash']); // Llogaritë Google nuk kanë fjalëkalim

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    if ($isGoogle) {
        setFlashMessage('error', 'Llogaria juaj u krijua me Google. Nuk mund të ndryshoni fjalëkalim.');
        redirect(BASE_URL . '/patient/change-password.php');
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Valido fjalëkalimin aktual
    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors[] = ERR_INVALID_PASSWORD;
    }

    // Valido fjalëkalimin e ri
    if (!isValidPassword($newPassword)) {
        $errors[] = 'Fjalëkalimi i ri duhet të ketë minimum 8 karaktere, 1 shkronjë të madhe dhe 1 numër.';
    }

    if ($newPassword !== $confirmPassword) {
        $errors[] = ERR_PASSWORDS_MISMATCH;
    }

    if (empty($errors)) {
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        db()->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [$hash, $patientId]
        );
        setFlashMessage('success', MSG_PASSWORD_CHANGED);
        redirect(BASE_URL . '/patient/change-password.php');
    }
}

$pageTitle = 'Ndrysho Fjalëkalimin';

