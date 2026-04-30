<?php
// ============================================================
// login.php - Autentikimi i përdoruesve (të gjitha rolet)
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// Nëse tashmë është i loguar → ridrejto tek dashboard-i i tij
redirectIfLoggedIn();

$errors = [];
$email  = '';

// ---- Trajto POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Valido CSRF
    verifyCsrfOrDie();

    // 2. Merr dhe pastro input-in
    $email    = cleanEmail($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 3. Validim bazë
    if (!$email) {
        $errors['email'] = 'Ju lutemi vendosni një email të vlefshëm.';
    }
    if (empty($password)) {
        $errors['password'] = 'Ju lutemi vendosni fjalëkalimin.';
    }

    // 4. Nëse nuk ka gabime → kontrollo në DB
    if (empty($errors)) {
        $user = getUserByEmail($email);

        if (!$user) {
            $errors['general'] = ERR_INVALID_CREDENTIALS;
        } elseif (!$user['is_active']) {
            $errors['general'] = ERR_ACCOUNT_INACTIVE;
        } elseif ($user['google_id'] && !$user['password_hash']) {
            // Llogari Google — nuk ka fjalëkalim
            $errors['general'] = 'Kjo llogari përdor hyrjen me Google. Ju lutemi klikoni "Hyr me Google".';
        } elseif (!password_verify($password, $user['password_hash'])) {
            $errors['general'] = ERR_INVALID_CREDENTIALS;
        } else {
            // ✅ Login i suksesshëm
            setUserSession($user);
            logSessionAction($user['id'], $user['role'], 'login');

            // Ridrejto tek URL-ja ku donte të shkonte (nëse ekziston)
            $redirectTo = $_SESSION['redirect_after_login'] ?? null;
            unset($_SESSION['redirect_after_login']);

            if ($redirectTo) {
                redirect($redirectTo);
            }

            redirectByRole();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sq">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= getCsrfToken() ?>">
    <title>Hyr — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
</head>
<body class="form-page">

<?php include BASE_PATH . '/includes/navbar.php'; ?>

<main class="form-wrapper">
    <div class="form-container">

        <div class="form-header">
            <div class="form-logo">🏥</div>
            <h1 class="form-title">Hyr në Llogarinë Tënde</h1>
            <p class="form-subtitle">Mirë se u ktheve!</p>
        </div>

        <!-- Mesazhi flash -->
        <?php displayFlashMessage(); ?>

        <!-- Gabim i përgjithshëm -->
        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <!-- Forma e login-it -->
        <form id="loginForm" method="POST" action="login.php" novalidate>
            <?= csrfInput() ?>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= e($email) ?>"
                    placeholder="emri@email.com"
                    autocomplete="email"
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">
                    Fjalëkalimi
                    <a href="forgot-password.php" class="form-label-link">Harrova fjalëkalimin</a>
                </label>
                <div class="input-password-wrap">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                        placeholder="••••••••"
                        autocomplete="current-password"
                        required
                    >
                    <button type="button" class="toggle-password" data-target="password">
                        <span class="eye-icon">👁️</span>
                    </button>
                </div>
                <?php if (!empty($errors['password'])): ?>
                    <div class="form-error"><?= e($errors['password']) ?></div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                Hyr
            </button>
        </form>

        <!-- Ndarës -->
        <div class="form-divider"><span>ose</span></div>

        <!-- Google Login -->
        <a href="<?= BASE_URL ?>/api/google_callback.php?action=login" class="btn btn-google btn-block">
            <img src="<?= BASE_URL ?>/assets/img/google-icon.svg" alt="Google" width="20">
            Hyr me Google
        </a>

        <p class="form-footer-text">
            Nuk keni llogari?
            <a href="register.php">Regjistrohu falas</a>
        </p>

    </div>
</main>

<script src="<?= BASE_URL ?>/assets/js/validate.js"></script>
<script>
// ---- Toggle fjalëkalimit ----
document.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', function () {
        const input = document.getElementById(this.dataset.target);
        input.type = input.type === 'password' ? 'text' : 'password';
        this.querySelector('.eye-icon').textContent = input.type === 'password' ? '👁️' : '🙈';
    });
});

// ---- Validim frontend ----
document.getElementById('loginForm').addEventListener('submit', function (e) {
    let valid = true;

    const email = document.getElementById('email');
    const pass  = document.getElementById('password');

    clearAllErrors(this);

    if (!validateEmail(email.value.trim())) {
        showError(email, 'Ju lutemi vendosni një email të vlefshëm.');
        valid = false;
    }

    if (!pass.value.trim()) {
        showError(pass, 'Ju lutemi vendosni fjalëkalimin.');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        return;
    }

    // Shfaq loading në buton
    const btn = document.getElementById('loginBtn');
    btn.textContent = 'Duke hyrë...';
    btn.disabled = true;
});
</script>
</body>
</html>
