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
    $photoPath       = $user['photo_path']; // mban foton ekzistuese si default

    // Validim
    if (empty($name)) {
        $errors[] = 'Emri është i detyrueshëm.';
    }
    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = 'Numri i telefonit nuk është i vlefshëm.';
    }
    if (!empty($specialization) && !in_array($specialization, SPECIALIZATIONS)) {
        $errors[] = 'Specializimi i zgjedhur nuk është i vlefshëm.';
    }
    if ($consultationFee < 0) {
        $errors[] = 'Tarifa e konsultimit nuk mund të jetë negative.';
    }

    // Trajtim foto (opsionale)
    if (!empty($_FILES['photo']['name'])) {
        $file    = $_FILES['photo'];
        $tmpPath = $file['tmp_name'];
        $origName = $file['name'];

        if (!isAllowedFileSize($file['size'])) {
            $errors[] = ERR_FILE_SIZE;
        } elseif (!isAllowedFileType($tmpPath, $origName)) {
            $errors[] = ERR_FILE_TYPE;
        } else {
            $uniqueName = 'doctor_' . $doctorId . '_' . time() . '.' . strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $destDir    = BASE_PATH . '/uploads/photos/';

            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }

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

$specializations = SPECIALIZATIONS;
$pageTitle       = 'Profili Im';
$cssFile         = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Profili Im</h1>
    </div>

    <?php displayFlashMessage(); ?>
    <?php if (!empty($errors)): ?>
        <div class="error-list"><ul><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div style="max-width:640px;">
        <div class="dashboard-form">
            <h3>Të dhënat profesionale</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Emri i Plotë <span>*</span></label>
                        <input type="text" name="name" class="form-control" value="<?= e($user['name'] ?? '') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Telefon</label>
                        <input type="tel" name="phone" class="form-control" value="<?= e($user['phone'] ?? '') ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Specializimi</label>
                    <select name="specialization" class="form-control">
                        <option value="">-- Zgjedh --</option>
                        <?php foreach ($specializations as $spec): ?>
                        <option value="<?= e($spec) ?>" <?= ($user['specialization'] ?? '') === $spec ? 'selected' : '' ?>>
                            <?= e($spec) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Bio / Rreth meje</label>
                    <textarea name="bio" class="form-control" rows="4"><?= e($user['bio'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Tarifa e Konsultimit (Lekë)</label>
                    <input type="number" name="consultation_fee" class="form-control"
                           value="<?= e($user['consultation_fee'] ?? '0') ?>" min="0" step="100">
                </div>

                <div class="form-group">
                    <label class="form-label">Foto Profili (opsionale)</label>
                    <input type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png">
                    <small class="text-muted">Formatet: JPG, PNG · Max: 5MB</small>
                </div>

                <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';
