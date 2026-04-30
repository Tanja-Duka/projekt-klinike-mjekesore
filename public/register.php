<?php
// ============================================================
// register.php - Regjistrim i pacientit të ri
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// Nëse tashmë është i loguar → ridrejto
redirectIfLoggedIn();

$errors = [];
$data   = [];

// ---- Trajto POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. Valido CSRF
    verifyCsrfOrDie();

    // 2. Merr dhe pastro të gjitha fushat
    $data = [
        'name'              => clean($_POST['name']              ?? ''),
        'email'             => cleanEmail($_POST['email']        ?? ''),
        'password'          => $_POST['password']                ?? '',
        'confirm_password'  => $_POST['confirm_password']        ?? '',
        'phone'             => clean($_POST['phone']             ?? ''),
        'date_of_birth'     => clean($_POST['date_of_birth']     ?? ''),
        'blood_type'        => clean($_POST['blood_type']        ?? ''),
        'address'           => clean($_POST['address']           ?? ''),
        'emergency_contact' => clean($_POST['emergency_contact'] ?? ''),
    ];

    // 3. Validim
    if (empty($data['name']) || strlen($data['name']) < 3) {
        $errors['name'] = 'Emri duhet të ketë të paktën 3 karaktere.';
    }

    if (!$data['email']) {
        $errors['email'] = 'Ju lutemi vendosni një email të vlefshëm.';
    } elseif (emailExists($data['email'])) {
        $errors['email'] = ERR_EMAIL_EXISTS;
    }

    if (!isValidPassword($data['password'])) {
        $errors['password'] = 'Fjalëkalimi duhet të ketë min. 8 karaktere, 1 shkronjë të madhe dhe 1 numër.';
    }

    if ($data['password'] !== $data['confirm_password']) {
        $errors['confirm_password'] = ERR_PASSWORDS_MISMATCH;
    }

    if (!empty($data['phone']) && !isValidPhone($data['phone'])) {
        $errors['phone'] = 'Numri i telefonit nuk është i vlefshëm (p.sh. 0691234567).';
    }

    if (!empty($data['date_of_birth']) && !isValidDate($data['date_of_birth'])) {
        $errors['date_of_birth'] = 'Data e lindjes nuk është e vlefshme.';
    }

    if (!empty($data['blood_type']) && !in_array($data['blood_type'], BLOOD_TYPES)) {
        $errors['blood_type'] = 'Grupi i gjakut nuk është i vlefshëm.';
    }

    // 4. Nëse nuk ka gabime → INSERT në DB
    if (empty($errors)) {
        try {
            db()->beginTransaction();

            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

            $userId = db()->insert(
                "INSERT INTO users
                    (name, email, password_hash, role, phone,
                     date_of_birth, blood_type, address, emergency_contact,
                     is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [
                    $data['name'],
                    $data['email'],
                    $passwordHash,
                    ROLE_PATIENT,
                    $data['phone']             ?: null,
                    $data['date_of_birth']     ?: null,
                    $data['blood_type']        ?: null,
                    $data['address']           ?: null,
                    $data['emergency_contact'] ?: null,
                ]
            );

            db()->commit();

            // Dërgo email mirëseardhjeje (nuk ndal nëse dështon)
            sendWelcomeEmail($data['email'], $data['name']);

            setFlashMessage('success', MSG_REGISTER_SUCCESS);
            redirect(BASE_URL . '/public/login.php');

        } catch (Exception $e) {
            db()->rollBack();
            error_log('Register error: ' . $e->getMessage());
            $errors['general'] = ERR_GENERAL;
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
    <title>Regjistrohu — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/forms.css">
</head>
<body class="form-page">

<?php include BASE_PATH . '/includes/navbar.php'; ?>

<main class="form-wrapper">
    <div class="form-container form-container-lg">

        <div class="form-header">
            <div class="form-logo">🏥</div>
            <h1 class="form-title">Krijo Llogarinë</h1>
            <p class="form-subtitle">Regjistrohu si pacient falas</p>
        </div>

        <?php displayFlashMessage(); ?>

        <?php if (!empty($errors['general'])): ?>
            <div class="alert alert-error"><?= e($errors['general']) ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="register.php" novalidate>
            <?= csrfInput() ?>

            <!-- Seksioni 1: Informacioni kryesor -->
            <div class="form-section-title">Informacioni Kryesor</div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="name">Emri i plotë *</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="form-control <?= !empty($errors['name']) ? 'is-invalid' : '' ?>"
                        value="<?= e($data['name'] ?? '') ?>"
                        placeholder="Emri Mbiemri"
                        required
                    >
                    <?php if (!empty($errors['name'])): ?>
                        <div class="form-error"><?= e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="phone">Telefoni</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        class="form-control <?= !empty($errors['phone']) ? 'is-invalid' : '' ?>"
                        value="<?= e($data['phone'] ?? '') ?>"
                        placeholder="0691234567"
                    >
                    <?php if (!empty($errors['phone'])): ?>
                        <div class="form-error"><?= e($errors['phone']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="email">
                    Email *
                    <span class="email-check-indicator" id="emailIndicator"></span>
                </label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                    value="<?= e($data['email'] ?? '') ?>"
                    placeholder="emri@email.com"
                    autocomplete="email"
                    required
                >
                <?php if (!empty($errors['email'])): ?>
                    <div class="form-error"><?= e($errors['email']) ?></div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="password">Fjalëkalimi *</label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            class="form-control <?= !empty($errors['password']) ? 'is-invalid' : '' ?>"
                            placeholder="Min. 8 karaktere"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="password">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <div class="password-strength" id="passwordStrength"></div>
                    <?php if (!empty($errors['password'])): ?>
                        <div class="form-error"><?= e($errors['password']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="confirm_password">Konfirmo Fjalëkalimin *</label>
                    <div class="input-password-wrap">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            class="form-control <?= !empty($errors['confirm_password']) ? 'is-invalid' : '' ?>"
                            placeholder="Përsërit fjalëkalimin"
                            autocomplete="new-password"
                            required
                        >
                        <button type="button" class="toggle-password" data-target="confirm_password">
                            <span class="eye-icon">👁️</span>
                        </button>
                    </div>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <div class="form-error"><?= e($errors['confirm_password']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Seksioni 2: Informacioni mjekësor -->
            <div class="form-section-title">Informacioni Mjekësor <span class="optional">(opsional)</span></div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="date_of_birth">Data e Lindjes</label>
                    <input
                        type="date"
                        id="date_of_birth"
                        name="date_of_birth"
                        class="form-control <?= !empty($errors['date_of_birth']) ? 'is-invalid' : '' ?>"
                        value="<?= e($data['date_of_birth'] ?? '') ?>"
                        max="<?= date('Y-m-d') ?>"
                    >
                    <?php if (!empty($errors['date_of_birth'])): ?>
                        <div class="form-error"><?= e($errors['date_of_birth']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="blood_type">Grupi i Gjakut</label>
                    <select
                        id="blood_type"
                        name="blood_type"
                        class="form-control <?= !empty($errors['blood_type']) ? 'is-invalid' : '' ?>"
                    >
                        <option value="">-- Zgjidh --</option>
                        <?php foreach (BLOOD_TYPES as $bt): ?>
                            <option value="<?= $bt ?>" <?= ($data['blood_type'] ?? '') === $bt ? 'selected' : '' ?>>
                                <?= $bt ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (!empty($errors['blood_type'])): ?>
                        <div class="form-error"><?= e($errors['blood_type']) ?></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="address">Adresa</label>
                <input
                    type="text"
                    id="address"
                    name="address"
                    class="form-control"
                    value="<?= e($data['address'] ?? '') ?>"
                    placeholder="Rruga, Qyteti"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="emergency_contact">Kontakti i Urgjencës</label>
                <input
                    type="text"
                    id="emergency_contact"
                    name="emergency_contact"
                    class="form-control"
                    value="<?= e($data['emergency_contact'] ?? '') ?>"
                    placeholder="Emri dhe numri i telefonit"
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="registerBtn">
                Krijo Llogarinë
            </button>
        </form>

        <!-- Ndarës -->
        <div class="form-divider"><span>ose</span></div>

        <!-- Google Register -->
        <a href="<?= BASE_URL ?>/api/google_callback.php?action=register" class="btn btn-google btn-block">
            <img src="<?= BASE_URL ?>/assets/img/google-icon.svg" alt="Google" width="20">
            Regjistrohu me Google
        </a>

        <p class="form-footer-text">
            Keni llogari tashmë?
            <a href="login.php">Hyr këtu</a>
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

// ---- Password strength indicator ----
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    const indicator = document.getElementById('passwordStrength');
    let strength = 0;
    let label = '';
    let cls = '';

    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^a-zA-Z0-9]/.test(val)) strength++;

    if (val.length === 0) { indicator.innerHTML = ''; return; }

    switch (strength) {
        case 1: label = 'Shumë i dobët'; cls = 'strength-weak'; break;
        case 2: label = 'I dobët';        cls = 'strength-fair'; break;
        case 3: label = 'I mirë';         cls = 'strength-good'; break;
        case 4: label = 'Shumë i fortë';  cls = 'strength-strong'; break;
    }
    indicator.innerHTML = `<span class="${cls}">${label}</span>`;
});

// ---- AJAX check email ----
let emailTimeout;
document.getElementById('email').addEventListener('input', function () {
    clearTimeout(emailTimeout);
    const email = this.value.trim();
    const indicator = document.getElementById('emailIndicator');

    if (!validateEmail(email)) { indicator.innerHTML = ''; return; }

    emailTimeout = setTimeout(() => {
        fetch('<?= BASE_URL ?>/api/check_email.php?email=' + encodeURIComponent(email), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.exists) {
                indicator.innerHTML = '<span class="email-taken">✗ Email i zënë</span>';
                showError(document.getElementById('email'), ERR_EMAIL_EXISTS || 'Ky email është i regjistruar.');
            } else {
                indicator.innerHTML = '<span class="email-free">✓ Email i disponueshëm</span>';
                clearError(document.getElementById('email'));
            }
        })
        .catch(() => { indicator.innerHTML = ''; });
    }, 400);
});

// ---- Validim frontend i formës ----
document.getElementById('registerForm').addEventListener('submit', function (e) {
    let valid = true;
    clearAllErrors(this);

    const name    = document.getElementById('name');
    const email   = document.getElementById('email');
    const pass    = document.getElementById('password');
    const confirm = document.getElementById('confirm_password');

    if (!name.value.trim() || name.value.trim().length < 3) {
        showError(name, 'Emri duhet të ketë të paktën 3 karaktere.');
        valid = false;
    }

    if (!validateEmail(email.value.trim())) {
        showError(email, 'Ju lutemi vendosni një email të vlefshëm.');
        valid = false;
    }

    if (!validatePassword(pass.value)) {
        showError(pass, 'Min. 8 karaktere, 1 shkronjë e madhe, 1 numër.');
        valid = false;
    }

    if (pass.value !== confirm.value) {
        showError(confirm, 'Fjalëkalimet nuk përputhen.');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        return;
    }

    const btn = document.getElementById('registerBtn');
    btn.textContent = 'Duke krijuar llogarinë...';
    btn.disabled = true;
});
</script>
</body>
</html>
