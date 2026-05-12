<?php
// ============================================================
// doctor/upload_rx.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $appointmentId = cleanInt($_POST['appointment_id'] ?? 0);
    $appointment   = getAppointmentById($appointmentId);

    if (!$appointment || (int)$appointment['doctor_id'] !== $doctorId) {
        setFlashMessage('error', ERR_ACCESS_DENIED);
        redirect(BASE_URL . '/doctor/upload_rx.php');
    }

    if ($appointment['status'] !== STATUS_COMPLETED) {
        $errors[] = 'Mund të ngarkoni recetë vetëm për takime të kryera.';
    }

    if (empty($_FILES['prescription']['name'])) {
        $errors[] = 'Ju lutemi zgjidhni një skedar recete.';
    } else {
        $file     = $_FILES['prescription'];
        $tmpPath  = $file['tmp_name'];
        $origName = $file['name'];
        $fileSize = $file['size'];

        if (!isAllowedFileSize($fileSize))         $errors[] = ERR_FILE_SIZE;
        if (!isAllowedFileType($tmpPath, $origName)) $errors[] = ERR_FILE_TYPE;
    }

    if (empty($errors)) {
        $uniqueName   = generateUniqueFilename($origName);
        $destPath     = UPLOAD_PATH . $uniqueName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = ERR_FILE_UPLOAD;
        } else {
            $relativePath = 'uploads/prescriptions/' . $uniqueName;

            db()->insert(
                "INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, file_path)
                 VALUES (?, ?, ?, ?)",
                [$appointmentId, $doctorId, $appointment['patient_id'], $relativePath]
            );

            $patient = getUserById((int)$appointment['patient_id']);
            if ($patient) {
                @include_once BASE_PATH . '/includes/email.php';
                if (function_exists('sendPrescriptionEmail')) {
                    sendPrescriptionEmail($patient['email'], $patient['name'], $appointment);
                }
            }

            setFlashMessage('success', MSG_RX_UPLOADED);
            redirect(BASE_URL . '/doctor/upload_rx.php');
        }
    }
}

$preselect = cleanInt($_GET['appointment_id'] ?? 0);

$completedAppointments = db()->fetchAll(
    "SELECT a.id, a.appointment_date, a.time_slot,
            u.name AS patient_name, s.name AS service_name,
            pr.id AS has_prescription
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     JOIN services s ON a.service_id = s.id
     LEFT JOIN prescriptions pr ON pr.appointment_id = a.id
     WHERE a.doctor_id = ? AND a.status = ?
     ORDER BY a.appointment_date DESC",
    [$doctorId, STATUS_COMPLETED]
);

// Stats for sidebar
$rxStats = [
    'today' => db()->fetchOne(
        "SELECT COUNT(*) as c FROM prescriptions WHERE doctor_id = ? AND DATE(uploaded_at) = CURDATE()",
        [$doctorId]
    )['c'],
    'week'  => db()->fetchOne(
        "SELECT COUNT(*) as c FROM prescriptions WHERE doctor_id = ? AND uploaded_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
        [$doctorId]
    )['c'],
    'total' => db()->fetchOne(
        "SELECT COUNT(*) as c FROM prescriptions WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
];

$recentRx = db()->fetchAll(
    "SELECT pr.uploaded_at, u.name AS patient_name, a.appointment_date
     FROM prescriptions pr
     JOIN users u ON pr.patient_id = u.id
     JOIN appointments a ON pr.appointment_id = a.id
     WHERE pr.doctor_id = ?
     ORDER BY pr.uploaded_at DESC
     LIMIT 3",
    [$doctorId]
);

$pageTitle = 'Lësho Recetë — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Veprim i ri</div>
            <h1>Lësho një <em class="serif-italic">recetë</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Ngarkoni dokumentin si PDF dhe lidheni me një takim të kryer. Pacienti do ta marrë automatikisht.</p>
        </div>
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

    <div class="reserve-container">

        <!-- Form -->
        <div class="dashboard-form">
            <h3>Detajet e recetës</h3>

            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label">Takimi i kryer <span>*</span></label>
                    <select name="appointment_id" class="form-control" required>
                        <option value="">— Zgjedh takimin —</option>
                        <?php foreach ($completedAppointments as $appt): ?>
                        <option value="<?= (int)$appt['id'] ?>"
                            <?= $preselect === (int)$appt['id'] ? 'selected' : '' ?>>
                            <?= formatDateSq($appt['appointment_date']) ?> · <?= e($appt['time_slot']) ?> —
                            <?= e($appt['patient_name']) ?> (<?= e($appt['service_name']) ?>)
                            <?= $appt['has_prescription'] ? ' ✓' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-hint">Receta do të shfaqet bashkë me detajet e këtij takimi.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Dokumenti PDF ose imazh <span>*</span></label>
                    <div class="upload-zone" id="uploadZone" onclick="document.getElementById('rxFile').click()">
                        <div class="ico">
                            <svg class="i" viewBox="0 0 24 24" style="width:28px;height:28px;stroke:currentColor;fill:none;stroke-width:1.5"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
                        </div>
                        <h3 id="uploadLabel">Tërhiq dokumentin këtu</h3>
                        <p>ose <u>kërko në kompjuter</u> · PDF, JPG, PNG · maks 5MB</p>
                    </div>
                    <input type="file" id="rxFile" name="prescription"
                           accept=".pdf,.jpg,.jpeg,.png"
                           style="display:none;" required
                           onchange="document.getElementById('uploadLabel').textContent = this.files[0]?.name || 'Tërhiq dokumentin këtu'">
                    <p class="form-hint">Ngarkimi sigurohet me TLS dhe ruhet i kriptuar.</p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shënime për pacientin</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="P.sh. 'Mat tensionin çdo mëngjes, kontrollë pas 30 ditësh.'"></textarea>
                </div>

                <?php if (empty($completedAppointments)): ?>
                <div class="alert alert-info" style="margin-bottom:16px;">
                    Nuk keni takime të kryera ende. Recetat mund të ngarkohen vetëm për vizita të kryera.
                </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-cta w-100"
                    <?= empty($completedAppointments) ? 'disabled' : '' ?>>
                    Lësho recetën →
                </button>
            </form>
        </div>

        <!-- Summary sidebar -->
        <aside class="reserve-summary">
            <h4>Recetat e fundit</h4>

            <div class="row">
                <span class="k">Sot</span>
                <span class="v"><?= (int)$rxStats['today'] ?></span>
            </div>
            <div class="row">
                <span class="k">Këtë javë</span>
                <span class="v"><?= (int)$rxStats['week'] ?></span>
            </div>
            <div class="row">
                <span class="k">Gjithsej</span>
                <span class="v"><?= (int)$rxStats['total'] ?></span>
            </div>

            <?php if (!empty($recentRx)): ?>
            <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--line);">
                <?php foreach ($recentRx as $rx): ?>
                <div class="rx-med" style="padding:10px 0;border-bottom:1px solid var(--line);">
                    <strong><?= e($rx['patient_name']) ?></strong>
                    <p style="color:var(--ink-3);font-size:0.82rem;margin:2px 0 0;">
                        <?= formatDateSq($rx['appointment_date']) ?>
                    </p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </aside>

    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
