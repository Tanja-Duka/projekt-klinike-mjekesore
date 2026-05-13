<?php
// ============================================================
// doctor/profile.php - Edito profilin e mjekut
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$user     = getUserById($doctorId);
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $name            = clean($_POST['name']             ?? '');
    $phone           = clean($_POST['phone']            ?? '');
    $specialization  = clean($_POST['specialization']   ?? '');
    $bio             = clean($_POST['bio']              ?? '');
    $consultationFee = cleanFloat($_POST['consultation_fee'] ?? 0);
    $photoPath       = $user['photo_path'];

    if (empty($name)) $errors[] = 'Emri është i detyrueshëm.';
    if (!empty($phone) && !isValidPhone($phone)) $errors[] = 'Numri i telefonit nuk është i vlefshëm.';
    if (!empty($specialization) && !in_array($specialization, SPECIALIZATIONS)) $errors[] = 'Specializimi i zgjedhur nuk është i vlefshëm.';
    if ($consultationFee < 0) $errors[] = 'Tarifa nuk mund të jetë negative.';

    if (!empty($_FILES['photo']['name'])) {
        $file     = $_FILES['photo'];
        $tmpPath  = $file['tmp_name'];
        $origName = $file['name'];

        if (!isAllowedFileSize($file['size'])) {
            $errors[] = ERR_FILE_SIZE;
        } elseif (!isAllowedFileType($tmpPath, $origName)) {
            $errors[] = ERR_FILE_TYPE;
        } else {
            $uniqueName = 'doctor_' . $doctorId . '_' . time() . '.' . strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $destDir    = BASE_PATH . '/uploads/photos/';
            if (!is_dir($destDir)) mkdir($destDir, 0755, true);
            if (move_uploaded_file($tmpPath, $destDir . $uniqueName)) {
                $photoPath = 'uploads/photos/' . $uniqueName;
            } else {
                $errors[] = ERR_FILE_UPLOAD;
            }
        }
    }

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET name=?, phone=?, specialization=?, bio=?, consultation_fee=?, photo_path=?
             WHERE id=? AND role=?",
            [$name, $phone, $specialization, $bio, $consultationFee, $photoPath, $doctorId, ROLE_DOCTOR]
        );
        $_SESSION['name']       = $name;
        $_SESSION['photo_path'] = $photoPath;
        setFlashMessage('success', MSG_PROFILE_UPDATED);
        redirect(BASE_URL . '/doctor/profile.php');
    }

    $user = array_merge($user, [
        'name'             => $name,
        'phone'            => $phone,
        'specialization'   => $specialization,
        'bio'              => $bio,
        'consultation_fee' => $consultationFee,
    ]);
}

$initials = getInitials($user['name'] ?? 'D');
$pageTitle = 'Profili Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.profile-layout{display:grid;grid-template-columns:260px 1fr;gap:28px;align-items:start;}
.profile-card{background:var(--page);border:1px solid var(--line);border-radius:16px;padding:32px 24px;text-align:center;position:sticky;top:90px;}
.profile-avatar{width:96px;height:96px;border-radius:50%;background:var(--ink-1);color:#fff;font-family:'Fraunces',serif;font-style:italic;font-size:2.2rem;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;overflow:hidden;cursor:pointer;position:relative;}
.profile-avatar img{width:100%;height:100%;object-fit:cover;}
.profile-avatar .overlay{position:absolute;inset:0;background:rgba(0,0,0,.45);border-radius:50%;display:flex;align-items:center;justify-content:center;opacity:0;transition:opacity .2s;}
.profile-avatar:hover .overlay{opacity:1;}
.profile-card h3{font-size:1.05rem;font-weight:600;margin-bottom:2px;}
.profile-card .spec{font-size:.8rem;color:var(--ink-3);margin-bottom:6px;}
.profile-card .role-badge{display:inline-block;font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);background:var(--surface,#f5f1e8);padding:3px 10px;border-radius:20px;margin-bottom:18px;}
.profile-card .meta-row{display:flex;align-items:center;gap:8px;font-size:.8rem;color:var(--ink-3);padding:8px 0;border-top:1px solid var(--line);text-align:left;}
.profile-card .meta-row svg{flex-shrink:0;opacity:.5;}
.fee-badge{display:inline-block;background:var(--accent-tint,#f5efe8);color:var(--accent);font-weight:700;font-size:.95rem;padding:6px 14px;border-radius:8px;margin-top:14px;}
.form-section-label{font-size:.7rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);font-weight:600;padding:16px 0 10px;border-top:1px solid var(--line);margin-top:8px;}
.form-section-label:first-child{border-top:0;padding-top:0;}
.photo-upload-zone{border:1.5px dashed var(--line);border-radius:10px;padding:18px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s;}
.photo-upload-zone:hover{border-color:var(--accent);background:var(--surface,#f9f7f3);}
.photo-upload-zone p{font-size:.8rem;color:var(--ink-3);margin:6px 0 0;}
@media(max-width:860px){.profile-layout{grid-template-columns:1fr;}}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

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
            <div class="profile-avatar" onclick="document.getElementById('photoInput').click()">
                <?php if (!empty($user['photo_path']) && file_exists(BASE_PATH . '/' . $user['photo_path'])): ?>
                    <img src="<?= BASE_URL . '/' . e($user['photo_path']) ?>" alt="Foto" id="avatarPreview">
                <?php else: ?>
                    <span id="avatarInitials"><?= e($initials) ?></span>
                <?php endif; ?>
                <div class="overlay">
                    <svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" width="22" height="22"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                </div>
            </div>
            <h3><?= e($user['name'] ?? '') ?></h3>
            <?php if (!empty($user['specialization'])): ?>
            <div class="spec"><?= e($user['specialization']) ?></div>
            <?php endif; ?>
            <div class="role-badge">Mjek</div>

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

            <?php if (!empty($user['consultation_fee'])): ?>
            <div class="fee-badge"><?= formatPrice((float)$user['consultation_fee']) ?> / vizitë</div>
            <?php endif; ?>
        </div>

        <!-- Right: edit form -->
        <div class="dashboard-form">
            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrfInput() ?>
                <input type="file" id="photoInput" name="photo" accept=".jpg,.jpeg,.png" style="display:none;">

                <div class="form-section-label">Të dhënat profesionale</div>

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

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Specializimi</label>
                        <select name="specialization" class="form-control">
                            <option value="">— Zgjedh —</option>
                            <?php foreach (SPECIALIZATIONS as $spec): ?>
                            <option value="<?= e($spec) ?>" <?= ($user['specialization'] ?? '') === $spec ? 'selected' : '' ?>>
                                <?= e($spec) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Tarifa e Konsultimit (L)</label>
                        <input type="number" name="consultation_fee" class="form-control"
                               value="<?= e($user['consultation_fee'] ?? '0') ?>" min="0" step="100">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Bio / Rreth meje</label>
                    <textarea name="bio" class="form-control" rows="4"
                              placeholder="Prezantohuni shkurtimisht për pacientët…"><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-section-label">Foto Profili</div>

                <div class="photo-upload-zone" onclick="document.getElementById('photoInput').click()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28" style="opacity:.4"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    <p id="uploadLabel">Kliko për të ngarkuar foton · JPG, PNG · Max 5MB</p>
                </div>

                <div style="display:flex;gap:10px;align-items:center;margin-top:20px;">
                    <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
                    <a href="<?= BASE_URL ?>/doctor/change-password.php" class="btn btn-outline">Ndrysho Fjalëkalimin</a>
                </div>
            </form>
        </div>

    </div>

</main>
</div>

<script>
// Show selected filename and preview avatar
document.getElementById('photoInput').addEventListener('change', function () {
    var file = this.files[0];
    if (!file) return;
    document.getElementById('uploadLabel').textContent = file.name;
    var reader = new FileReader();
    reader.onload = function (e) {
        var av = document.querySelector('.profile-avatar');
        av.innerHTML = '<img src="' + e.target.result + '" alt="Preview" style="width:100%;height:100%;object-fit:cover;">' +
                       '<div class="overlay"><svg viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="1.5" width="22" height="22"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg></div>';
    };
    reader.readAsDataURL(file);
});
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
