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
        'phone'   => clean($_POST['phone']   ?? ''),
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

<style>
.contact-hero{position:relative;padding:100px 0 60px;background:var(--ink-1);overflow:hidden;}
.contact-hero::before{content:'';position:absolute;inset:0;background:var(--photo) center/cover no-repeat;opacity:.22;}
.contact-hero .container{position:relative;z-index:1;}
.contact-hero .eyebrow{color:rgba(255,255,255,.6);}
.contact-hero h1{color:#fff;font-size:clamp(2rem,5vw,3.4rem);}
.contact-hero h1 em{font-style:italic;color:rgba(255,255,255,.75);}

.contact-cards{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;padding:40px 0 0;}
@media(max-width:900px){.contact-cards{grid-template-columns:repeat(2,1fr);}}
@media(max-width:520px){.contact-cards{grid-template-columns:1fr;}}
.contact-card-item{background:var(--page);border:1px solid var(--line);border-radius:14px;padding:22px 18px;display:flex;flex-direction:column;gap:6px;}
.contact-card-item .ico{width:36px;height:36px;background:var(--accent-tint,#f5efe8);border-radius:50%;display:flex;align-items:center;justify-content:center;margin-bottom:4px;}
.contact-card-item .ico svg{width:16px;height:16px;stroke:var(--accent);fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round;}
.contact-card-item .lab{font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);}
.contact-card-item .val{font-size:.95rem;font-weight:600;color:var(--ink-1);}
.contact-card-item .val a{color:inherit;text-decoration:none;}
.contact-card-item .sub{font-size:.78rem;color:var(--ink-3);}

.contact-grid{display:grid;grid-template-columns:1fr 1fr;gap:48px;padding:48px 0;}
@media(max-width:860px){.contact-grid{grid-template-columns:1fr;}}
.contact-info h3{font-size:1.4rem;font-weight:400;font-family:var(--serif);margin-bottom:12px;}
.contact-info p{color:var(--ink-2);line-height:1.7;margin-bottom:24px;}
.contact-info-list{display:flex;flex-direction:column;gap:0;}
.contact-info-item{display:grid;grid-template-columns:90px 1fr;gap:8px;padding:14px 0;border-bottom:1px solid var(--line);font-size:.88rem;}
.contact-info-item .label{color:var(--ink-3);font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;padding-top:2px;}
.contact-info-item .value{color:var(--ink-1);line-height:1.6;}
.contact-info-item .value a{color:var(--accent);}

.contact-map.real{display:flex;align-items:center;gap:10px;margin-top:24px;padding:14px 18px;background:var(--accent-tint,#f5efe8);border-radius:10px;text-decoration:none;color:var(--ink-1);font-size:.88rem;font-weight:500;transition:background .15s;}
.contact-map.real:hover{background:#ede5d8;}
.contact-map.real .pin::before{content:'📍';margin-right:6px;}

.contact-form-wrap{display:flex;flex-direction:column;gap:0;border:1px solid var(--line);border-radius:16px;overflow:hidden;}
.contact-form-img{height:200px;background:var(--photo) center/cover no-repeat;background-color:var(--ink-3);}
.contact-form{padding:32px;}
.contact-form .eyebrow{color:var(--ink-3);}
.contact-form h3{font-family:var(--serif);font-weight:400;font-size:1.5rem;margin-bottom:24px;letter-spacing:-0.015em;}

.faq-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:24px;padding-bottom:56px;}
@media(max-width:780px){.faq-grid{grid-template-columns:1fr;}}
.faq-item{padding:28px 0;border-top:2px solid var(--line);}
.faq-item .num{font-size:.72rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);margin-bottom:10px;}
.faq-item h4{font-size:1rem;font-weight:600;margin-bottom:8px;}
.faq-item p{font-size:.88rem;color:var(--ink-2);line-height:1.7;}
</style>

<!-- Hero -->
<section class="contact-hero" style="--photo: url('<?= BASE_URL ?>/assets/img/background.jpeg')">
    <div class="container">
        <div class="eyebrow">Kontakt — Të na gjeni</div>
        <h1>Vizitoni, telefononi <em>ose na shkruani</em>.</h1>
    </div>
</section>

<!-- Quick contact cards -->
<section>
    <div class="container">
        <div class="contact-cards">
            <div class="contact-card-item">
                <span class="ico"><svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                <span class="lab">Adresa</span>
                <span class="val">Rr. Fehmi Agani, nr. 24</span>
                <span class="sub">10000, Prishtinë · Kati 3</span>
            </div>
            <div class="contact-card-item">
                <span class="ico"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
                <span class="lab">Telefon</span>
                <span class="val"><a href="tel:+38344000000">+383 44 000 000</a></span>
                <span class="sub">Recepsion 24/7</span>
            </div>
            <div class="contact-card-item">
                <span class="ico"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                <span class="lab">Email</span>
                <span class="val"><a href="mailto:info@vitanova.com">info@vitanova.com</a></span>
                <span class="sub">Përgjigje brenda 24h</span>
            </div>
            <div class="contact-card-item">
                <span class="ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg></span>
                <span class="lab">Orari</span>
                <span class="val">08:00 – 20:00</span>
                <span class="sub">E Hënë – E Premte · Sht 09–14</span>
            </div>
        </div>
    </div>
</section>

<!-- Map + Info + Form -->
<section class="section-tight">
    <div class="container">

        <?php displayFlashMessage(); ?>

        <div class="contact-grid">

            <!-- Info panel -->
            <div class="contact-info">
                <div class="eyebrow mb-16">Klinika</div>
                <h3>Vitanova Clinic <em class="serif-italic">— Prishtinë</em></h3>
                <p>Klinikë private mjekësore në qendër të Prishtinës. Kati i tretë i objektit "Iliria", me akses për karroca dhe parking falas për pacientët në katin nëntokësor.</p>

                <div class="contact-info-list">
                    <div class="contact-info-item">
                        <span class="label">Transport</span>
                        <span class="value">Autobusët 1, 4 dhe 11 ndalen 80 m larg klinikës.</span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Parking</span>
                        <span class="value">Falas për pacientët në katin nëntokësor.</span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Akses</span>
                        <span class="value">Ashensor &amp; rampë për karroca. WC i përshtatur.</span>
                    </div>
                    <div class="contact-info-item">
                        <span class="label">Urgjenca</span>
                        <span class="value">Për raste urgjente jashtë orarit telefono <a href="tel:+38344000111">+383 44 000 111</a>.</span>
                    </div>
                </div>

                <a href="https://maps.google.com/?q=Rr.+Fehmi+Agani+Prishtine" target="_blank" rel="noopener" class="contact-map real">
                    <span class="pin">Hap në Google Maps</span>
                </a>
            </div>

            <!-- Form -->
            <div class="contact-form-wrap">
                <div class="contact-form-img" style="--photo: url('<?= BASE_URL ?>/assets/img/form.jpeg');background-image:var(--photo);"></div>
                <form class="contact-form" method="POST" action="<?= BASE_URL ?>/public/contact.php" novalidate>
                    <?= csrfInput() ?>

                    <?php if (!empty($errors['general'])): ?>
                        <div class="alert alert-error" style="margin-bottom:16px;"><?= e($errors['general']) ?></div>
                    <?php endif; ?>

                    <div class="eyebrow mb-16">Na shkruani</div>
                    <h3>Si mund t'ju ndihmojmë?</h3>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="name">Emri <span>*</span></label>
                            <input type="text" id="name" name="name" class="form-control<?= !empty($errors['name']) ? ' is-invalid' : '' ?>"
                                   value="<?= e($data['name'] ?? '') ?>" placeholder="Arta Sopa" required>
                            <?php if (!empty($errors['name'])): ?>
                                <div class="form-error"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="phone">Telefon</label>
                            <input type="tel" id="phone" name="phone" class="form-control"
                                   value="<?= e($data['phone'] ?? '') ?>" placeholder="+383 44 …">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email <span>*</span></label>
                        <input type="email" id="email" name="email" class="form-control<?= !empty($errors['email']) ? ' is-invalid' : '' ?>"
                               value="<?= e($data['email'] ?? '') ?>" placeholder="emri@example.com" required>
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
                        <textarea id="message" name="message" class="form-control<?= !empty($errors['message']) ? ' is-invalid' : '' ?>"
                                  rows="5" placeholder="Shkruani pyetjen tuaj…" required><?= e($data['message'] ?? '') ?></textarea>
                        <?php if (!empty($errors['message'])): ?>
                            <div class="form-error"><?= e($errors['message']) ?></div>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="btn btn-cta w-100">
                        Dërgo mesazhin
                        <svg class="i i-sm arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M13 5l7 7-7 7"/></svg>
                    </button>
                    <p class="text-muted text-center mt-16" style="font-size:0.8rem;">Përgjigjemi brenda 24 orëve. Të dhënat tuaja janë konfidenciale.</p>
                </form>
            </div>
        </div>

        <!-- FAQ -->
        <div class="faq-grid">
            <div class="faq-item">
                <div class="num">01 — Rezervime</div>
                <h4>A duhet të rezervoj paraprakisht?</h4>
                <p>Po. Rezervimet bëhen online ose me telefon. Për raste urgjente, telefononi çdo orar.</p>
            </div>
            <div class="faq-item">
                <div class="num">02 — Pagesa</div>
                <h4>Si paguhet vizita?</h4>
                <p>Në recepsion në ditën e takimit — kesh ose me kartë. Faturë mjekësore e printuar.</p>
            </div>
            <div class="faq-item">
                <div class="num">03 — Sigurime</div>
                <h4>A bashkëpunoni me sigurime?</h4>
                <p>Po — me Eurosig, Siguria dhe Insig. Sillni kartën në recepsion para vizitës.</p>
            </div>
        </div>

    </div>
</section>

<?php include BASE_PATH . '/includes/footer.php'; ?>
