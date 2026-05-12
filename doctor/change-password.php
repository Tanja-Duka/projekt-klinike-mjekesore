<?php
// ============================================================
// doctor/change-password.php - Ndrysho fjalëkalimin (mjek)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$user     = getUserById($doctorId);
$errors   = [];
$isGoogle = empty($user['password_hash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    if ($isGoogle) {
        setFlashMessage('error', 'Llogaria juaj u krijua me Google. Nuk mund të ndryshoni fjalëkalim.');
        redirect(BASE_URL . '/doctor/change-password.php');
    }

    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword     = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (!password_verify($currentPassword, $user['password_hash'])) {
        $errors[] = ERR_INVALID_PASSWORD;
    }
    if (!isValidPassword($newPassword)) {
        $errors[] = 'Fjalëkalimi i ri duhet të ketë minimum 8 karaktere, 1 shkronjë të madhe dhe 1 numër.';
    }
    if ($newPassword !== $confirmPassword) {
        $errors[] = ERR_PASSWORDS_MISMATCH;
    }

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET password_hash = ? WHERE id = ?",
            [password_hash($newPassword, PASSWORD_DEFAULT), $doctorId]
        );
        setFlashMessage('success', MSG_PASSWORD_CHANGED);
        redirect(BASE_URL . '/doctor/change-password.php');
    }
}

$pageTitle  = 'Ndrysho Fjalëkalimin';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header"><h1>Ndrysho Fjalëkalimin</h1></div>

    <?php displayFlashMessage(); ?>

    <div style="max-width:480px;">
        <?php if ($isGoogle): ?>
            <div class="alert alert-info">Llogaria juaj u krijua me Google. Nuk mund të vendosni fjalëkalim manual.</div>
        <?php else: ?>
        <?php if (!empty($errors)): ?>
            <div class="error-list"><ul><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>
        <div class="dashboard-form">
            <h3>Ndrysho fjalëkalimin</h3>
            <form method="POST" action="">
                <?= csrfInput() ?>
                <div class="form-group">
                    <label class="form-label">Fjalëkalimi Aktual <span>*</span></label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Fjalëkalimi i Ri <span>*</span></label>
                    <input type="password" name="new_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Konfirmo Fjalëkalimin e Ri <span>*</span></label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-cta w-100">Ndrysho Fjalëkalimin</button>
            </form>
        </div>
        <?php endif; ?>
    </div>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';
