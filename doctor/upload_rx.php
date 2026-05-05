<?php
// ============================================================
// doctor/upload_rx.php - Ngarko recetë dixhitale
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';
require_once BASE_PATH . '/includes/email.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$errors   = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $appointmentId = cleanInt($_POST['appointment_id'] ?? 0);

    // Kontrollo pronësinë e takimit dhe statusin
    $appointment = getAppointmentById($appointmentId);

    if (!$appointment || (int)$appointment['doctor_id'] !== $doctorId) {
        setFlashMessage('error', ERR_ACCESS_DENIED);
        redirect(BASE_URL . '/doctor/upload_rx.php');
    }

    if ($appointment['status'] !== STATUS_COMPLETED) {
        $errors[] = 'Mund të ngarkoni recetë vetëm për takime të kryera.';
    }

    // Kontrollo skedarin
    if (empty($_FILES['prescription']['name'])) {
        $errors[] = 'Ju lutemi zgjidhni një skedar recete.';
    } else {
        $file     = $_FILES['prescription'];
        $tmpPath  = $file['tmp_name'];
        $origName = $file['name'];
        $fileSize = $file['size'];

        if (!isAllowedFileSize($fileSize)) {
            $errors[] = ERR_FILE_SIZE;
        }
        if (!isAllowedFileType($tmpPath, $origName)) {
            $errors[] = ERR_FILE_TYPE;
        }
    }

    if (empty($errors)) {
        // Gjenero emër unik dhe lëviz skedarin
        $uniqueName = generateUniqueFilename($origName);
        $destPath   = UPLOAD_PATH . $uniqueName;

        if (!move_uploaded_file($tmpPath, $destPath)) {
            $errors[] = ERR_FILE_UPLOAD;
        } else {
            $relativePath = 'uploads/prescriptions/' . $uniqueName;

            // INSERT recetën
            db()->insert(
                "INSERT INTO prescriptions (appointment_id, doctor_id, patient_id, file_path)
                 VALUES (?, ?, ?, ?)",
                [$appointmentId, $doctorId, $appointment['patient_id'], $relativePath]
            );

            // Dërgo email njoftimi tek pacienti
            $patient = getUserById((int)$appointment['patient_id']);
            if ($patient) {
                sendPrescriptionEmail($patient['email'], $patient['name'], $appointment);
            }

            setFlashMessage('success', MSG_RX_UPLOADED);
            redirect(BASE_URL . '/doctor/upload_rx.php');
        }
    }
}

// Merr takimet e kryera pa recetë (ose me recetë) për dropdown
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



$pageTitle  = 'Ngarko Recetë';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Ngarko Recetë Dixhitale</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
        <div class="error-list"><ul><?php foreach ($errors as $er): ?><li><?= htmlspecialchars($er, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div style="max-width:600px;">
        <div class="dashboard-form">
            <h3>Ngarko recetë për pacientin</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label">Zgjedh Takimin e Kryer <span>*</span></label>
                    <select name="appointment_id" class="form-control" required>
                        <option value="">-- Zgjedh Takimin --</option>
                        <?php foreach ($completedAppointments as $appt): ?>
                        <?php
                        // Preselecto nëse vjen nga URL
                        $preselect = cleanInt($_GET['appointment_id'] ?? 0);
                        ?>
                        <option value="<?= (int)$appt['id'] ?>"
                            <?= $preselect === (int)$appt['id'] ? 'selected' : '' ?>>
                            <?= formatDateSq($appt['appointment_date']) ?> · <?= e($appt['time_slot']) ?> —
                            <?= e($appt['patient_name']) ?> (<?= e($appt['service_name']) ?>)
                            <?= $appt['has_prescription'] ? ' ✓ Ka recetë' : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Skedari i Recetës <span>*</span></label>
                    <input type="file" name="prescription" class="form-control"
                           accept=".pdf,.jpg,.jpeg,.png" required>
                    <small class="text-muted">Formatet: PDF, JPG, PNG · Madhësia max: 5MB</small>
                </div>

                <button type="submit" class="btn btn-cta">Ngarko Recetën</button>
            </form>
        </div>

        <?php if (empty($completedAppointments)): ?>
            <div class="alert alert-info">
                Nuk keni takime të kryera ende. Recetat mund të ngarkohen vetëm për vizita të kryera.
            </div>
        <?php endif; ?>
    </div>
</main>
</div>
<?php include BASE_PATH . '/includes/footer.php';


