<?php
// ============================================================
// admin/doctors.php - CRUD mjekët
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors = [];
$action = clean($_POST['action'] ?? $_GET['action'] ?? '');

// ---- SHTO mjek të ri ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'add') {
    verifyCsrfOrDie();

    $name           = clean($_POST['name']              ?? '');
    $email          = cleanEmail($_POST['email']         ?? '');
    $phone          = clean($_POST['phone']             ?? '');
    $specialization = clean($_POST['specialization']    ?? '');
    $bio            = clean($_POST['bio']               ?? '');
    $fee            = cleanFloat($_POST['consultation_fee'] ?? 0);
    $password       = $_POST['password'] ?? '';

    if (empty($name) || !$email || empty($specialization) || empty($password)) {
        $errors[] = ERR_REQUIRED_FIELDS;
    }
    if ($email && emailExists($email)) {
        $errors[] = ERR_EMAIL_EXISTS;
    }
    if (!isValidPassword($password)) {
        $errors[] = 'Fjalëkalimi duhet të ketë minimum 8 karaktere, 1 shkronjë të madhe dhe 1 numër.';
    }

    if (empty($errors)) {
        db()->insert(
            "INSERT INTO users (name, email, phone, role, password_hash, specialization, bio, consultation_fee, is_active)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)",
            [$name, $email, $phone, ROLE_DOCTOR,
             password_hash($password, PASSWORD_DEFAULT),
             $specialization, $bio, $fee]
        );
        setFlashMessage('success', MSG_DOCTOR_ADDED);
        redirect(BASE_URL . '/doctor/admin/doctors.php');
    }
}

// ---- EDITO mjekun ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'edit') {
    verifyCsrfOrDie();

    $editId         = cleanInt($_POST['doctor_id']       ?? 0);
    $name           = clean($_POST['name']              ?? '');
    $phone          = clean($_POST['phone']             ?? '');
    $specialization = clean($_POST['specialization']    ?? '');
    $bio            = clean($_POST['bio']               ?? '');
    $fee            = cleanFloat($_POST['consultation_fee'] ?? 0);

    if (empty($name) || $editId <= 0) {
        $errors[] = ERR_REQUIRED_FIELDS;
    }

    if (empty($errors)) {
        db()->execute(
            "UPDATE users SET name=?, phone=?, specialization=?, bio=?, consultation_fee=?
             WHERE id=? AND role=?",
            [$name, $phone, $specialization, $bio, $fee, $editId, ROLE_DOCTOR]
        );
        setFlashMessage('success', MSG_DOCTOR_UPDATED);
        redirect(BASE_URL . '/doctor/admin/doctors.php');
    }
}

// ---- FSHIJ (soft delete) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'delete') {
    verifyCsrfOrDie();
    $deleteId = cleanInt($_POST['doctor_id'] ?? 0);
    if ($deleteId > 0) {
        db()->execute(
            "UPDATE users SET is_active = 0 WHERE id = ? AND role = ?",
            [$deleteId, ROLE_DOCTOR]
        );
        setFlashMessage('success', MSG_DOCTOR_DELETED);
    }
    redirect(BASE_URL . '/doctor/admin/doctors.php');
}

$doctors         = db()->fetchAll(
    "SELECT u.*,
            COUNT(a.id) AS total_appointments
     FROM users u
     LEFT JOIN appointments a ON a.doctor_id = u.id
     WHERE u.role = ?
     GROUP BY u.id
     ORDER BY u.is_active DESC, u.name ASC",
    [ROLE_DOCTOR]
);
$specializations = SPECIALIZATIONS;
$activeCount     = count(array_filter($doctors, fn($d) => $d['is_active']));

$pageTitle = 'Mjekët — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Menaxhim</div>
            <h1>Stafi <em class="serif-italic">mjekësor</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;"><?= $activeCount ?> mjek<?= $activeCount !== 1 ? 'ë' : '' ?> aktivë nga gjithsej <?= count($doctors) ?>.</p>
        </div>
        <button class="btn btn-cta" onclick="toggleForm('addDoctorForm')">+ Shto Mjek</button>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:24px;">
        <ul style="margin:0;padding-left:16px;">
            <?php foreach ($errors as $er): ?>
            <li><?= e($er) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Forma shto mjek (hidden by default) -->
    <div class="dashboard-form" id="addDoctorForm" style="display:none;margin-bottom:32px;">
        <h3>Shto Mjek të Ri</h3>
        <form method="POST" action="">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Emri i Plotë <span>*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="Dr. Emri Mbiemri">
                </div>
                <div class="form-group">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" name="email" class="form-control" required>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" class="form-control" placeholder="+355 6X XXX XXXX">
                </div>
                <div class="form-group">
                    <label class="form-label">Fjalëkalimi <span>*</span></label>
                    <input type="password" name="password" class="form-control" required
                           placeholder="Min. 8 karaktere, 1 majus, 1 numër">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Specializimi <span>*</span></label>
                    <select name="specialization" class="form-control" required>
                        <option value="">— Zgjedh —</option>
                        <?php foreach ($specializations as $spec): ?>
                        <option value="<?= e($spec) ?>"><?= e($spec) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tarifa konsultimi (L)</label>
                    <input type="number" name="consultation_fee" class="form-control"
                           value="0" min="0" step="100">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Bio / Përshkrim</label>
                <textarea name="bio" class="form-control" rows="3"
                          placeholder="Eksperiencë, specializime shtesë, etj."></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-cta">Shto Mjekun</button>
                <button type="button" class="btn btn-ghost" onclick="toggleForm('addDoctorForm')">Anulo</button>
            </div>
        </form>
    </div>

    <!-- Lista e mjekëve -->
    <div class="data-section">
        <?php if (empty($doctors)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <h3>Nuk ka mjekë regjistruar</h3>
                <button class="btn btn-cta" style="margin-top:12px;" onclick="toggleForm('addDoctorForm')">+ Shto Mjekun e Parë</button>
            </div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mjeku</th>
                        <th>Specializimi</th>
                        <th>Email &amp; Telefon</th>
                        <th>Takime</th>
                        <th>Tarifa</th>
                        <th>Statusi</th>
                        <th>Veprime</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($doctors as $doc): ?>
                <tr style="<?= !$doc['is_active'] ? 'opacity:0.5;' : '' ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:36px;height:36px;border-radius:50%;background:var(--accent-tint);color:var(--accent);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.8rem;flex-shrink:0;">
                                <?= e(getInitials($doc['name'])) ?>
                            </div>
                            <strong><?= e($doc['name']) ?></strong>
                        </div>
                    </td>
                    <td><?= e($doc['specialization'] ?? '—') ?></td>
                    <td>
                        <?= e($doc['email']) ?>
                        <?php if (!empty($doc['phone'])): ?>
                        <br><small style="color:var(--ink-3)"><?= e($doc['phone']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;"><?= (int)$doc['total_appointments'] ?></td>
                    <td><?= formatPrice((float)$doc['consultation_fee']) ?></td>
                    <td>
                        <span class="status-badge <?= $doc['is_active'] ? 'status-confirmed' : 'status-cancelled' ?>">
                            <?= $doc['is_active'] ? 'Aktiv' : 'Joaktiv' ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <button class="btn btn-outline btn-sm"
                                onclick="openEditForm(
                                    <?= (int)$doc['id'] ?>,
                                    '<?= e(addslashes($doc['name'])) ?>',
                                    '<?= e(addslashes($doc['phone'] ?? '')) ?>',
                                    '<?= e(addslashes($doc['specialization'] ?? '')) ?>',
                                    '<?= e(addslashes($doc['bio'] ?? '')) ?>',
                                    <?= (float)$doc['consultation_fee'] ?>
                                )">Edito</button>
                            <form method="POST" action="" style="margin:0;"
                                  onsubmit="return confirm('Mjeku do të çaktivizohet. Jeni i sigurt?')">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="doctor_id" value="<?= (int)$doc['id'] ?>">
                                <button type="submit" class="btn btn-sm" style="background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;">
                                    Fshij
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <!-- Forma edito (hidden, popullohet me JS) -->
    <div class="dashboard-form" id="editDoctorForm" style="display:none;margin-top:32px;">
        <h3>Edito Mjekun</h3>
        <form method="POST" action="">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="doctor_id" id="editDoctorId">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Emri <span>*</span></label>
                    <input type="text" name="name" id="editName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Telefon</label>
                    <input type="tel" name="phone" id="editPhone" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Specializimi</label>
                    <select name="specialization" id="editSpec" class="form-control">
                        <option value="">— Zgjedh —</option>
                        <?php foreach ($specializations as $spec): ?>
                        <option value="<?= e($spec) ?>"><?= e($spec) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Tarifa (L)</label>
                    <input type="number" name="consultation_fee" id="editFee" class="form-control" min="0" step="100">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Bio</label>
                <textarea name="bio" id="editBio" class="form-control" rows="3"></textarea>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-cta">Ruaj Ndryshimet</button>
                <button type="button" class="btn btn-ghost" onclick="toggleForm('editDoctorForm')">Anulo</button>
            </div>
        </form>
    </div>

</main>
</div>

<script>
function toggleForm(id) {
    var el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
    if (el.style.display === 'block') {
        el.scrollIntoView({behavior: 'smooth', block: 'start'});
    }
}

function openEditForm(id, name, phone, spec, bio, fee) {
    document.getElementById('editDoctorId').value = id;
    document.getElementById('editName').value     = name;
    document.getElementById('editPhone').value    = phone;
    document.getElementById('editSpec').value     = spec;
    document.getElementById('editBio').value      = bio;
    document.getElementById('editFee').value      = fee;
    document.getElementById('editDoctorForm').style.display = 'block';
    document.getElementById('editDoctorForm').scrollIntoView({behavior: 'smooth', block: 'start'});
}
</script>

<?php include BASE_PATH . '/includes/footer.php'; ?>
