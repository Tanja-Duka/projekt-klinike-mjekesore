<?php
// ============================================================
// public/contact.php
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

$errors = [];
$data   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $data = [
        'name'    => clean($_POST['name']    ?? ''),
        'email'   => cleanEmail($_POST['email'] ?? ''),
        'subject' => clean($_POST['subject'] ?? ''),
        'message' => clean($_POST['message'] ?? ''),
    ];

    if (empty($data['name']) || strlen($data['name']) < 2) {
        $errors['name'] = 'Ju lutemi vendosni emrin tuaj.';
    }
    if (!$data['email']) {
        $errors['email'] = 'Ju lutemi vendosni një email të vlefshëm.';
    }
    if (empty($data['subject'])) {
        $errors['subject'] = 'Ju lutemi zgjidhni temën.';
    }
    if (empty($data['message']) || strlen($data['message']) < 10) {
        $errors['message'] = 'Mesazhi duhet të ketë të paktën 10 karaktere.';
    }

    if (empty($errors)) {
        try {
            db()->insert(
                "INSERT INTO contact_queries (name, email, subject, message, status)
                 VALUES (?, ?, ?, ?, ?)",
                [$data['name'], $data['email'], $data['subject'], $data['message'], QUERY_UNREAD]
            );
            setFlashMessage('success', MSG_CONTACT_SENT);
            redirect(BASE_URL . '/public/contact.php');
        } catch (Exception $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $errors['general'] = ERR_GENERAL;
        }
    }
}

$pageTitle = 'Kontakt — ' . APP_NAME;
$cssFile   = 'home.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<section class="page-header">
    <div class="container">
        <div class="eyebrow">Kontakt — Të na gjeni</div>
        <h1>Vizitoni, telefononi <em>ose na shkruani</em>.</h1>
        <p>Jemi këtu çdo ditë pune nga 08:00 deri 20:00. Telefoni i recepsionit pranon thirrje urgjente edhe jashtë orarit.</p>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="contact-grid">

            <!-- Info panel -->
            <div class="contact-info">
                <h3>Vitanova Clinic</h3>
                <p>Klinikë private mjekësore. Kati i tretë i objektit "Iliria", me akses për karroca dhe parking falas për pacientët.</p>

                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <span class="label">Adresa</span>
                        <span class="value">Rr. Fehmi Agani, nr. 24<br>10000, Prishtinë</span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Telefon</span>
                        <span class="value"><a href="tel:+38344000000">+383 44 000 000</a></span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Email</span>
                        <span class="value"><a href="mailto:info@vitanova.com">info@vitanova.com</a></span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Orari</span>
                        <span class="value">E Hënë – E Premte: 08:00 – 20:00<br>E Shtunë: 09:00 – 14:00<br>E Dielë: e mbyllur</span>
                    </div>
                </div>

                <div class="contact-map">[ Hartë · Google Maps ]</div>
            </div>

            <!-- Contact form -->
            <form class="contact-form" method="POST" action="<?= BASE_URL ?>/public/contact.php" novalidate>
                <?= csrfInput() ?>

                <?php displayFlashMessage(); ?>
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-error"><?= e($errors['general']) ?></div>
                <?php endif; ?>

                <div class="eyebrow mb-24">Na shkruani</div>
                <h3 style="font-family:var(--serif);font-weight:400;font-size:1.6rem;margin-bottom:24px;">
                    Si mund t'ju ndihmojmë?
                </h3>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="name">Emri <span>*</span></label>
                        <input type="text" id="name" name="name" class="form-control"
                               value="<?= e($data['name'] ?? '') ?>"
                               placeholder="Arta Sopa" required>
                        <?php if (!empty($errors['name'])): ?>
                            <div class="form-error"><?= e($errors['name']) ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Telefon</label>
                        <input type="tel" id="phone" name="phone" class="form-control"
                               placeholder="+383 44 …">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="email">Email <span>*</span></label>
                    <input type="email" id="email" name="email" class="form-control"
                           value="<?= e($data['email'] ?? '') ?>"
                           placeholder="emri@example.com" required>
                    <?php if (!empty($errors['email'])): ?>
                        <div class="form-error"><?= e($errors['email']) ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-group">
                    <label class="form-label" for="subject">Tema</label>
                    <select id="subject" name="subject" class="form-control">
                        <option value="Pyetje e përgjithshme" <?= ($data['subject'] ?? '') === 'Pyetje e përgjithshme' ? 'selected' : '' ?>>Pyetje e përgjithshme</option>
                        <option value="Rezervim takimi" <?= ($data['subject'] ?? '') === 'Rezervim takimi' ? 'selected' : '' ?>>Rezervim takimi</option>
                        <option value="Pyetje rreth një shërbimi" <?= ($data['subject'] ?? '') === 'Pyetje rreth një shërbimi' ? 'selected' : '' ?>>Pyetje rreth një shërbimi</option>
                        <option value="Bashkëpunim / shtyp" <?= ($data['subject'] ?? '') === 'Bashkëpunim / shtyp' ? 'selected' : '' ?>>Bashkëpunim / shtyp</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label" for="message">Mesazhi <span>*</span></label>
                    <textarea id="message" name="message" class="form-control"
                              rows="5" placeholder="Shkruani pyetjen tuaj…" required><?= e($data['message'] ?? '') ?></textarea>
                    <?php if (!empty($errors['message'])): ?>
                        <div class="form-error"><?= e($errors['message']) ?></div>
                    <?php endif; ?>
                </div>

                <button type="submit" class="btn btn-cta w-100">
                    Dërgo mesazhin
                    <svg class="i i-sm arrow" viewBox="0 0 24 24"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                </button>
                <p class="text-muted text-center mt-16" style="font-size:0.8rem;">Përgjigjemi brenda 24 orëve.</p>
            </form>

        </div>
    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
