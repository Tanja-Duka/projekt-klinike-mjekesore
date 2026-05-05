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

    $name             = clean($_POST['name']             ?? '');
    $phone            = clean($_POST['phone']            ?? '');
    $address          = clean($_POST['address']          ?? '');
    $dateOfBirth      = clean($_POST['date_of_birth']    ?? '');
    $bloodType        = clean($_POST['blood_type']       ?? '');
    $emergencyContact = clean($_POST['emergency_contact'] ?? '');

    // Validim
    if (empty($name)) {
        $errors[] = 'Emri është i detyrueshëm.';
    }
    if (!empty($phone) && !isValidPhone($phone)) {
        $errors[] = 'Numri i telefonit nuk është i vlefshëm.';
    }
    if (!empty($dateOfBirth) && !isValidDate($dateOfBirth)) {
        $errors[] = 'Data e lindjes nuk është e vlefshme.';
    }
    if (!empty($bloodType) && !in_array($bloodType, BLOOD_TYPES)) {
        $errors[] = 'Grupi i gjakut nuk është i vlefshëm.';
    }

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET name=?, phone=?, address=?, date_of_birth=?, blood_type=?, emergency_contact=?
             WHERE id=? AND role=?",
            [$name, $phone, $address,
             $dateOfBirth ?: null,
             $bloodType   ?: null,
             $emergencyContact, $patientId, ROLE_PATIENT]
        );

        // Rifresko emrin në sesion
        $_SESSION['name'] = $name;

        setFlashMessage('success', MSG_PROFILE_UPDATED);
        redirect(BASE_URL . '/patient/profile.php');
    }

    // Mbushi formën me të dhënat e dërguara
    $user = array_merge($user, [
        'name'             => $name,
        'phone'            => $phone,
        'address'          => $address,
        'date_of_birth'    => $dateOfBirth,
        'blood_type'       => $bloodType,
        'emergency_contact' => $emergencyContact,
    ]);
}

$pageTitle  = 'Profili Im';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Profili Im</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
        <div class="error-list"><ul><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div style="max-width:640px;">
        <div class="dashboard-form">
            <h3>Të dhënat personale</h3>
            <form method="POST" action="">
                <?= csrfInput() ?>
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
                           value="<?= e($user['email'] ?? '') ?>" disabled>
                    <small class="text-muted">Email-i nuk mund të ndryshohet.</small>
                </div>

                <div class="form-group">
                    <label class="form-label">Adresa</label>
                    <input type="text" name="address" class="form-control"
                           value="<?= e($user['address'] ?? '') ?>">
                </div>

                <div class="form-section-title">Informacione Mjekësore</div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data e Lindjes</label>
                        <input type="date" name="date_of_birth" class="form-control"
                               value="<?= e($user['date_of_birth'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grupi i Gjakut</label>
                        <select name="blood_type" class="form-control">
                            <option value="">-- Zgjedh --</option>
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

                <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
            </form>
        </div>
    </div>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';


