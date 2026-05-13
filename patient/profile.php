<?php
// ============================================================
// patient/profile.php - Edito profilin e pacientit
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();
$user      = getUserById($patientId);
$errors    = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $name             = clean($_POST['name']              ?? '');
    $phone            = clean($_POST['phone']             ?? '');
    $address          = clean($_POST['address']           ?? '');
    $dateOfBirth      = clean($_POST['date_of_birth']     ?? '');
    $bloodType        = clean($_POST['blood_type']        ?? '');
    $emergencyContact = clean($_POST['emergency_contact'] ?? '');

    if (empty($name)) $errors[] = 'Emri është i detyrueshëm.';
    if (!empty($phone) && !isValidPhone($phone)) $errors[] = 'Numri i telefonit nuk është i vlefshëm.';
    if (!empty($dateOfBirth) && !isValidDate($dateOfBirth)) $errors[] = 'Data e lindjes nuk është e vlefshme.';
    if (!empty($bloodType) && !in_array($bloodType, BLOOD_TYPES)) $errors[] = 'Grupi i gjakut nuk është i vlefshëm.';

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET name=?, phone=?, address=?, date_of_birth=?, blood_type=?, emergency_contact=?
             WHERE id=? AND role=?",
            [$name, $phone, $address,
             $dateOfBirth ?: null, $bloodType ?: null,
             $emergencyContact, $patientId, ROLE_PATIENT]
        );
        $_SESSION['name'] = $name;
        setFlashMessage('success', MSG_PROFILE_UPDATED);
        redirect(BASE_URL . '/patient/profile.php');
    }

    $user = array_merge($user, [
        'name'              => $name,
        'phone'             => $phone,
        'address'           => $address,
        'date_of_birth'     => $dateOfBirth,
        'blood_type'        => $bloodType,
        'emergency_contact' => $emergencyContact,
    ]);
}

$initials  = getInitials($user['name'] ?? 'P');
$pageTitle = 'Profili Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.profile-layout{display:grid;grid-template-columns:260px 1fr;gap:28px;align-items:start;}
.profile-card{background:var(--page);border:1px solid var(--line);border-radius:16px;padding:32px 24px;text-align:center;position:sticky;top:90px;}
.profile-avatar{width:96px;height:96px;border-radius:50%;background:var(--ink-1);color:#fff;font-family:'Fraunces',serif;font-style:italic;font-size:2.2rem;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;overflow:hidden;}
.profile-avatar img{width:100%;height:100%;object-fit:cover;}
.profile-card h3{font-size:1.05rem;font-weight:600;margin-bottom:4px;}
.profile-card .role-badge{display:inline-block;font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);background:var(--surface,#f5f1e8);padding:3px 10px;border-radius:20px;margin-bottom:18px;}
.profile-card .meta-row{display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--ink-3);padding:8px 0;border-top:1px solid var(--line);text-align:left;}
.profile-card .meta-row svg{flex-shrink:0;opacity:.5;}
.form-section-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);font-weight:600;padding:16px 0 10px;border-top:1px solid var(--line);margin-top:8px;}
.form-section-label:first-child{border-top:0;padding-top:0;}
@media(max-width:860px){.profile-layout{grid-template-columns:1fr;}}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Cilësimet e llogarisë</div>
            <h1>Profili <em class="serif-italic">Im</em>.</h1>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:20px;">
        <ul style="margin:0;padding-left:16px;">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="profile-layout">

        <!-- Left: profile card -->
        <div class="profile-card">
            <div class="profile-avatar">
                <?php if (!empty($user['photo_path']) && file_exists(BASE_PATH . '/' . $user['photo_path'])): ?>
                    <img src="<?= BASE_URL . '/' . e($user['photo_path']) ?>" alt="Foto">
                <?php else: ?>
                    <?= e($initials) ?>
                <?php endif; ?>
            </div>
            <h3><?= e($user['name'] ?? '') ?></h3>
            <div class="role-badge">Pacient</div>

            <?php if (!empty($user['email'])): ?>
            <div class="meta-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="15" height="15"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                <?= e($user['email']) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($user['phone'])): ?>
            <div class="meta-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="15" height="15"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.24h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L7.91 8.8a16 16 0 0 0 7.29 7.29l1.92-1.84a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg>
                <?= e($user['phone']) ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($user['blood_type'])): ?>
            <div class="meta-row">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="15" height="15"><path d="M12 2L8 10H4l8 8 8-8h-4L12 2z"/></svg>
                Gjaku: <strong><?= e($user['blood_type']) ?></strong>
            </div>
            <?php endif; ?>
        </div>

        <!-- Right: edit form -->
        <div class="dashboard-form">
            <form method="POST" action="">
                <?= csrfInput() ?>

                <div class="form-section-label">Të dhënat personale</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Emri i Plotë <span>*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="<?= e($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?= e($user['phone'] ?? '') ?>"
                               placeholder="+355 6X XXX XXXX">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email</label>
                    <input type="email" class="form-control"
                           value="<?= e($user['email'] ?? '') ?>" disabled
                           style="background:var(--surface,#f9f7f3);color:var(--ink-3);">
                    <small class="form-hint">Email-i nuk mund të ndryshohet.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Adresa</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($user['address'] ?? '') ?>"
                           placeholder="Rruga, Qyteti">
                </div>

                <div class="form-section-label">Informacione Mjekësore</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data e Lindjes</label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="<?= e($user['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grupi i Gjakut</label>
                        <select name="blood_type" class="form-control">
                            <option value="">— Zgjedh —</option>
                            <?php foreach (BLOOD_TYPES as $bt): ?>
                            <option value="<?= e($bt) ?>" <?= ($user['blood_type'] ?? '') === $bt ? 'selected' : '' ?>>
                                <?= e($bt) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Kontakti i Urgjencës</label>
                    <input type="text" name="emergency_contact" class="form-control"
                           value="<?= e($user['emergency_contact'] ?? '') ?>"
                           placeholder="Emri dhe telefoni i personit të kontaktit">
                </div>

                <div style="display:flex;gap:10px;align-items:center;margin-top:8px;">
                    <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
                    <a href="<?= BASE_URL ?>/patient/change-password.php" class="btn btn-outline">Ndrysho Fjalëkalimin</a>
                </div>
            </form>
        </div>

    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
