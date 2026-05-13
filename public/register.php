<?php
// ============================================================
// public/register.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

redirectIfLoggedIn();

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $data = [
        'name'             => clean($_POST['name']             ?? ''),
        'email'            => cleanEmail($_POST['email']       ?? ''),
        'password'         => $_POST['password']               ?? '',
        'confirm_password' => $_POST['confirm_password']       ?? '',
        'phone'            => clean($_POST['phone']            ?? ''),
        'date_of_birth'    => clean($_POST['date_of_birth']    ?? ''),
        'blood_type'       => clean($_POST['blood_type']       ?? ''),
        'address'          => clean($_POST['address']          ?? ''),
        'emergency_contact'=> clean($_POST['emergency_contact']?? ''),
    ];

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
        $errors['phone'] = 'Numri i telefonit nuk është i vlefshëm.';
    }

    if (empty($errors)) {
        try {
            db()->beginTransaction();

            $userId = db()->insert(
                "INSERT INTO users
                    (name, email, password_hash, role, phone,
                     date_of_birth, blood_type, address, emergency_contact,
                     is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())",
                [
                    $data['name'],
                    $data['email'],
                    password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]),
                    ROLE_PATIENT,
                    $data['phone']             ?: null,
                    $data['date_of_birth']     ?: null,
                    $data['blood_type']        ?: null,
                    $data['address']           ?: null,
                    $data['emergency_contact'] ?: null,
                ]
            );

            db()->commit();
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

$pageTitle = 'Krijo Llogari — ' . APP_NAME;
$cssFile   = 'forms.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<section class="auth-shell">

    <!-- Left: form -->
    <div class="auth-shell-left">
        <div class="auth-form-wrap">
            <div class="eyebrow">Krijo llogari</div>
            <h1>Bashkohuni në <em class="serif-italic">Vitanova</em>.</h1>
            <p>Krijimi i llogarisë merr 30 sekonda. Pastaj rezervoni takimin tuaj të parë me dy klikime.</p>

            <?php displayFlashMessage(); ?>
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error"><?= e($errors['general']) ?></div>
            <?php endif; ?>

            <form id="registerForm" method="POST" action="<?= BASE_URL ?>/public/register.php" novalidate>
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label" for="name">Emri i plotë <span>*</span></label>
                    <input type="text" id="name" name="name" class="form-control"
                           value="<?= e($data['name'] ?? '') ?>"
                           placeholder="Arta Sopa" required>
                    <?php if (!empty($errors['name'])): ?>
                        <div class="form-error"><?= e($errors['name']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email <span>*</span></label>
                        <input type="email" id="email" name="email" class="form-control"
                               value="<?= e($data['email'] ?? '') ?>"
                               placeholder="emri@example.com"
                               autocomplete="email" required>
                        <?php if (!empty($errors['email'])): ?>
                            <div class="form-error"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               value="<?= e($data['phone'] ?? '') ?>"
                               placeholder="+383 44 …">
                        <?php if (!empty($errors['phone'])): ?>
                            <div class="form-error"><?= e($errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="password">Fjalëkalimi <span>*</span></label>
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Min. 8 karaktere"
                               autocomplete="new-password" required>
                        <?php if (!empty($errors['password'])): ?>
                            <div class="form-error"><?= e($errors['password']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Konfirmo <span>*</span></label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control"
                               placeholder="••••••••"
                               autocomplete="new-password" required>
                        <?php if (!empty($errors['confirm_password'])): ?>
                            <div class="form-error"><?= e($errors['confirm_password']) ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <p class="form-hint mb-16">
                    Duke krijuar llogari pranoni
                    <a href="#" style="color:var(--accent);">Kushtet e Përdorimit</a> dhe
                    <a href="#" style="color:var(--accent);">Politikën e Privatësisë</a>.
                </p>

                <button class="btn btn-cta w-100" type="submit">Krijo llogari →</button>

                <div class="or-rule">ose</div>

                <a href="<?= BASE_URL ?>/api/google_callback.php?action=register" class="btn btn-google">
                    <svg viewBox="0 0 24 24" fill="none" width="18" height="18">
                        <path fill="#4285F4" d="M22 12c0-.7-.1-1.4-.2-2H12v3.8h5.6c-.2 1.3-1 2.4-2 3.1v2.6h3.3c1.9-1.8 3.1-4.4 3.1-7.5z"/>
                        <path fill="#34A853" d="M12 22c2.7 0 5-.9 6.6-2.4l-3.3-2.6c-.9.6-2 .9-3.3.9-2.5 0-4.7-1.7-5.4-4H3.2v2.6C4.9 19.7 8.2 22 12 22z"/>
                        <path fill="#FBBC05" d="M6.6 13.9c-.2-.6-.3-1.2-.3-1.9s.1-1.3.3-1.9V7.5H3.2C2.4 8.9 2 10.4 2 12s.4 3.1 1.2 4.5l3.4-2.6z"/>
                        <path fill="#EA4335" d="M12 6.4c1.4 0 2.7.5 3.7 1.4l2.8-2.8C16.9 3.4 14.7 2.4 12 2.4 8.2 2.4 4.9 4.7 3.2 7.5l3.4 2.6c.7-2.3 2.9-3.7 5.4-3.7z"/>
                    </svg>
                    Regjistrohu me Google
                </a>
            </form>

            <p class="auth-foot">Keni llogari? <a href="<?= BASE_URL ?>/public/login.php">Hyni këtu →</a></p>
        </div>
    </div>

    <!-- Right: feature list -->
    <div class="auth-shell-right">
        <div class="auth-quote">
            <span class="mark">"</span>
            <h2>Çfarë merrni me llogari falas?</h2>
            <ul style="margin-top:24px;display:flex;flex-direction:column;gap:14px;">
                <li style="display:flex;gap:12px;color:var(--ink-2);">
                    <span style="color:var(--accent);font-family:var(--serif);font-style:italic;">01.</span>
                    Rezervim online 24/7 me specialistë
                </li>
                <li style="display:flex;gap:12px;color:var(--ink-2);">
                    <span style="color:var(--accent);font-family:var(--serif);font-style:italic;">02.</span>
                    Receta dixhitale të aksesueshme nga telefoni
                </li>
                <li style="display:flex;gap:12px;color:var(--ink-2);">
                    <span style="color:var(--accent);font-family:var(--serif);font-style:italic;">03.</span>
                    Historik i plotë mjekësor në një vend
                </li>
                <li style="display:flex;gap:12px;color:var(--ink-2);">
                    <span style="color:var(--accent);font-family:var(--serif);font-style:italic;">04.</span>
                    Kujtues me email para çdo takimi
                </li>
            </ul>
        </div>
    </div>

</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>

<script src="<?= BASE_URL ?>/assets/js/validate.js"></script>
<script>
document.getElementById('password').addEventListener('input', function () {
    const val = this.value;
    let hint = '';
    if (val.length > 0 && val.length < 8) hint = 'Shumë i shkurtër';
    else if (val.length >= 8 && !/[A-Z]/.test(val)) hint = 'Shto një shkronjë të madhe';
    else if (val.length >= 8 && !/[0-9]/.test(val)) hint = 'Shto një numër';
    const el = this.nextElementSibling;
    if (el && el.classList.contains('form-error')) return;
    let tip = document.getElementById('pwTip');
    if (!tip) { tip = document.createElement('div'); tip.id = 'pwTip'; tip.className = 'form-hint'; this.parentNode.appendChild(tip); }
    tip.textContent = hint;
});
</script>
