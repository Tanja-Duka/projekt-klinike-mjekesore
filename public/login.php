<?php
// ============================================================
// public/login.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

redirectIfLoggedIn();

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $email    = cleanEmail($_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email) {
        $errors['email'] = 'Ju lutemi vendosni një email të vlefshëm.';
    }
    if (empty($password)) {
        $errors['password'] = 'Ju lutemi vendosni fjalëkalimin.';
    }

    if (empty($errors)) {
        $user = getUserByEmail($email);

        if (!$user) {
            $errors['general'] = ERR_INVALID_CREDENTIALS;
        } elseif (!$user['is_active']) {
            $errors['general'] = ERR_ACCOUNT_INACTIVE;
        } elseif ($user['google_id'] && !$user['password_hash']) {
            $errors['general'] = 'Kjo llogari përdor hyrjen me Google. Ju lutemi klikoni "Vazhdo me Google".';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $errors['general'] = ERR_INVALID_CREDENTIALS;
        } else {
            setUserSession($user);
            logSessionAction($user['id'], $user['role'], 'login');

            $redirectTo = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);
            if ($redirectTo) redirect($redirectTo);
            redirectByRole();
        }
    }
}

$pageTitle = 'Hyr — ' . APP_NAME;
$cssFile   = 'forms.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<section class="auth-shell">

    <!-- Left: form -->
    <div class="auth-shell-left">
        <div class="auth-form-wrap">
            <div class="eyebrow">Mirë se vini sërish</div>
            <h1>Hyr në llogarinë <em class="serif-italic">tuaj</em>.</h1>
            <p>Rezervoni takime, shihni recetat dhe ndiqni historikun tuaj mjekësor në një vend të vetëm.</p>

            <?php displayFlashMessage(); ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form id="loginForm" method="POST" action="<?= BASE_URL ?>/public/login.php" novalidate>
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($email) ?>"
                           placeholder="emri@example.com"
                           autocomplete="email" required>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="form-error"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex;justify-content:space-between;">
                        <span>Fjalëkalimi</span>
                        <a href="<?= BASE_URL ?>/public/forgot-password.php"
                           style="color:var(--ink-3);font-size:0.78rem;font-weight:400;">Harruat?</a>
                    </label>
                    <input type="password" id="password" name="password" class="form-control"
                           placeholder="••••••••"
                           autocomplete="current-password" required>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="form-error"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <button class="btn btn-primary w-100" type="submit">Hyr në llogari →</button>

                <div class="or-rule">ose</div>

                <a href="<?= BASE_URL ?>/api/google_callback.php?action=login" class="btn btn-google">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18">
                        <path fill="#4285F4" d="M22 12c0-.7-.1-1.4-.2-2H12v3.8h5.6c-.2 1.3-1 2.4-2 3.1v2.6h3.3c1.9-1.8 3.1-4.4 3.1-7.5z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.4l-3.3-2.6c-.9.6-2 .9-3.3.9-2.5 0-4.7-1.7-5.4-4H3.2v2.6C4.9 19.7 8.2 22 12 22z"/>
                        <path fill="#FBBC05" d="M6.6 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.5H3.2C2.4 8.9 2 10.4 2 12s.4 3.1 1.2 4.5l3.4-2.6z"/>
                        <path fill="#EA4335" d="M12 6.4c1.4 0 2.7.5 3.7 1.4l2.8-2.8C16.9 3.4 14.7 2.4 12 2.4 8.2 2.4 4.9 4.7 3.2 7.5l3.4 2.6c.7-2.3 2.9-3.7 5.4-3.7z"/>
                    </svg>
                    Vazhdo me Google
                </a>
            </form>

            <p class="auth-foot">S'keni llogari? <a href="<?= BASE_URL ?>/public/register.php">Krijoni një tani →</a></p>
        </div>
    </div>

    <!-- Right: decorative quote -->
    <div class="auth-shell-right">
        <div class="auth-quote">
            <span class="mark">"</span>
            <h2>Më kanë dëgjuar pa nxitim. Tani familja ime vjen vetëm këtu.</h2>
            <p>Sistemi dixhital më kursen orë çdo muaj — recetat dhe takimet janë gjithmonë në xhep.</p>
            <div class="signature">— Arta S. · Pacient që nga 2019</div>
        </div>
    </div>

</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
