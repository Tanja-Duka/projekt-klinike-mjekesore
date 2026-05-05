<?php
// ============================================================
// patient/reserve.php - Rezervo takim të ri
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

// Merr të gjithë mjekët aktivë dhe shërbimet për dropdownat
$doctors  = getAllDoctors();
$services = getAllServices();

// Nëse vjen me ?doctor_id nga butonin "Rezervo" tek mjeku
$preselectedDoctorId = cleanInt($_GET['doctor_id'] ?? 0);


$pageTitle  = 'Rezervo Takim';
$cssFile    = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
?>
<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>
<main class="main-content">
    <div class="content-header">
        <h1>Rezervo Takim të Ri</h1>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="reserve-container">
        <div class="dashboard-form">
            <h3>Plotëso formularin e rezervimit</h3>
            <form id="reserveForm">
                <?= csrfInput() ?>

                <!-- Zgjedh Mjekun -->
                <div class="form-group">
                    <label class="form-label">Mjeku <span>*</span></label>
                    <select name="doctor_id" id="doctorSelect" class="form-control" required>
                        <option value="">-- Zgjedh Mjekun --</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int)$doc['id'] ?>"
                            <?= $preselectedDoctorId === (int)$doc['id'] ? 'selected' : '' ?>>
                            Dr. <?= e($doc['name']) ?>
                            <?= !empty($doc['specialization']) ? '(' . e($doc['specialization']) . ')' : '' ?>
                            — <?= formatPrice((float)$doc['consultation_fee']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Zgjedh Shërbimin -->
                <div class="form-group">
                    <label class="form-label">Shërbimi <span>*</span></label>
                    <select name="service_id" id="serviceSelect" class="form-control" required>
                        <option value="">-- Zgjedh Shërbimin --</option>
                        <?php foreach ($services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>">
                            <?= e($svc['name']) ?> — <?= formatPrice((float)$svc['price']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Zgjedh Datën -->
                <div class="form-group">
                    <label class="form-label">Data e Takimit <span>*</span></label>
                    <input type="date" name="date" id="dateInput" class="form-control"
                           min="<?= date('Y-m-d') ?>"
                           max="<?= date('Y-m-d', strtotime('+3 months')) ?>"
                           required>
                </div>

                <!-- Slot-et e disponueshme -->
                <div class="form-group" id="slotsContainer" style="display:none;">
                    <label class="form-label">Ora e Takimit <span>*</span></label>
                    <input type="hidden" name="time_slot" id="timeSlotInput" required>
                    <div class="time-slots" id="timeSlots"></div>
                    <p class="text-muted mt-8" id="noSlotsMsg" style="display:none;">
                        Nuk ka orare të lira për këtë datë. Ju lutemi zgjidhni datë tjetër.
                    </p>
                </div>

                <!-- Shënime opsionale -->
                <div class="form-group">
                    <label class="form-label">Shënime (opsionale)</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Simptoma, historia mjekësore ose kërkesa speciale..."></textarea>
                </div>

                <div id="reserveError" class="alert alert-error" style="display:none;"></div>

                <button type="submit" class="btn btn-cta w-100" id="submitBtn" disabled>
                    Konfirmo Rezervimin
                </button>
                <p class="text-muted text-center mt-8" style="font-size:0.84rem;">
                    Pas rezervimit do të merrni email konfirmimi.
                </p>
            </form>
        </div>
    </div>
</main>
</div>
<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';

