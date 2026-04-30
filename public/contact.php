<?php
// ============================================================
// public/contact.php - Forma e kontaktit
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$errors = [];
$data   = [];

// ---- Trajto POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verifyCsrfOrDie();

    $data = [
        'name'    => clean($_POST['name']    ?? ''),
        'email'   => cleanEmail($_POST['email'] ?? ''),
        'subject' => clean($_POST['subject'] ?? ''),
        'message' => clean($_POST['message'] ?? ''),
    ];

    // Validim
    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors['name'] = 'Ju lutemi vendosni emrin tuaj.';
    }
    if (!$data['email']) {
        $errors['email'] = 'Ju lutemi vendosni një email të vlefshëm.';
    }
    if (empty($data['subject']) || strlen($data['subject']) < 3) {
        $errors['subject'] = 'Ju lutemi vendosni subjektin.';
    }
    if (empty($data['message']) || strlen($data['message']) < 10) {
        $errors['message'] = 'Mesazhi duhet të ketë të paktën 10 karaktere.';
    }

    if (empty($errors)) {
        try {
            db()->insert(
                "INSERT INTO contact_queries (name, email, subject, message, status)
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $data['name'],
                    $data['email'],
                    $data['subject'],
                    $data['message'],
                    QUERY_UNREAD,
                ]
            );

            // Dërgo email konfirmimi tek dërguesi (opsionale)
            // sendContactConfirmationEmail($data['email'], $data['name']);

            setFlashMessage('success', MSG_CONTACT_SENT);
            redirect(BASE_URL . '/public/contact.php');

        } catch (Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $errors['general'] = ERR_GENERAL;
        }
    }
}

$pageTitle = 'Kontakt — ' . APP_NAME;
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<main class="page-main">
    <div class="container">

        <div class="page-header">
            <div class="section-tag">Na Kontaktoni</div>
            <h1 class="page-title">Na Shkruani</h1>
            <p class="page-subtitle">
                Jemi këtu për t'ju ndihmuar. Dërgoni mesazhin tuaj dhe do t'ju
                përgjigjemi sa më shpejt.
            </p>
        </div>

        <div class="contact-layout">

            <!-- Forma -->
            <div class="contact-form-wrap">

                <?php displayFlashMessage(); ?>

                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error"><?= e($errors['general']) ?></div>
                <?php endif; ?>

                <form id="contactForm" method="POST" action="contact.php" novalidate>
                    <?= csrfInput() ?>

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
                            <label class="form-label" for="email">Email *</label>
                            <input
                                type="email"
                                id="email"
                                name="email"
                                class="form-control <?= !empty($errors['email']) ? 'is-invalid' : '' ?>"
                                value="<?= e($data['email'] ?? '') ?>"
                                placeholder="emri@email.com"
                                required
                            >
                            <?php if (!empty($errors['email'])): ?>
                                <div class="form-error"><?= e($errors['email']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="subject">Subjekti *</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            class="form-control <?= !empty($errors['subject']) ? 'is-invalid' : '' ?>"
                            value="<?= e($data['subject'] ?? '') ?>"
                            placeholder="Pyetje, ankesë, sugjerim..."
                            required
                        >
                        <?php if (!empty($errors['subject'])): ?>
                            <div class="form-error"><?= e($errors['subject']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="message">Mesazhi *</label>
                        <textarea
                            id="message"
                            name="message"
                            class="form-control <?= !empty($errors['message']) ? 'is-invalid' : '' ?>"
                            rows="6"
                            placeholder="Shkruani mesazhin tuaj këtu..."
                            required
                        ><?= e($data['message'] ?? '') ?></textarea>
                        <?php if (!empty($errors['message'])): ?>
                            <div class="form-error"><?= e($errors['message']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                        📨 Dërgo Mesazhin
                    </button>
                </form>
            </div>

            <!-- Info kontakti -->
            <div class="contact-info-wrap">
                <h3 class="contact-info-title">Informacioni i Kontaktit</h3>

                <div class="contact-info-item">
                    <div class="contact-info-icon">📍</div>
                    <div>
                        <strong>Adresa</strong>
                        <p>Rruga e Durrësit, Nr. 42<br>Tiranë, Shqipëri</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">📞</div>
                    <div>
                        <strong>Telefoni</strong>
                        <p>+355 69 123 4567</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">✉️</div>
                    <div>
                        <strong>Email</strong>
                        <p>info@klinikamjekesore.al</p>
                    </div>
                </div>

                <div class="contact-info-item">
                    <div class="contact-info-icon">🕐</div>
                    <div>
                        <strong>Orari</strong>
                        <p>E Hënë – E Shtunë<br>08:00 – 20:00</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php include BASE_PATH . '/includes/footer.php'; ?>

<script>
document.getElementById('contactForm').addEventListener('submit', function (e) {
    let valid = true;
    clearAllErrors(this);

    const name    = document.getElementById('name');
    const email   = document.getElementById('email');
    const subject = document.getElementById('subject');
    const message = document.getElementById('message');

    if (!name.value.trim() || name.value.trim().length < 2) {
        showError(name, 'Ju lutemi vendosni emrin tuaj.');
        valid = false;
    }
    if (!validateEmail(email.value.trim())) {
        showError(email, 'Ju lutemi vendosni një email të vlefshëm.');
        valid = false;
    }
    if (!subject.value.trim() || subject.value.trim().length < 3) {
        showError(subject, 'Ju lutemi vendosni subjektin.');
        valid = false;
    }
    if (!message.value.trim() || message.value.trim().length < 10) {
        showError(message, 'Mesazhi duhet të ketë të paktën 10 karaktere.');
        valid = false;
    }

    if (!valid) {
        e.preventDefault();
        return;
    }

    const btn = document.getElementById('submitBtn');
    btn.textContent = 'Duke dërguar...';
    btn.disabled = true;
});
</script>
