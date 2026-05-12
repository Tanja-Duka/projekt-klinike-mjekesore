<?php
// ============================================================
// patient/reserve.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$doctors  = getAllDoctors();
$services = getAllServices();

$preselectedDoctorId = cleanInt($_GET['doctor_id'] ?? 0);

$pageTitle = 'Rezervo Takim — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Rezervim i ri</div>
            <h1>Zgjidh mjekun <em class="serif-italic">dhe orarin</em>.</h1>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <div class="reserve-container">

        <!-- Form -->
        <div class="dashboard-form">
            <h3>Plotëso formularin</h3>

            <form id="reserveForm">
                <?= csrfInput() ?>

                <div class="form-group">
                    <label class="form-label">Mjeku <span>*</span></label>
                    <select name="doctor_id" id="doctorSelect" class="form-control" required>
                        <option value="">— Zgjedh Mjekun —</option>
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

                <div class="form-group">
                    <label class="form-label">Shërbimi <span>*</span></label>
                    <select name="service_id" id="serviceSelect" class="form-control" required>
                        <option value="">— Zgjedh Shërbimin —</option>
                        <?php foreach ($services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>">
                            <?= e($svc['name']) ?> — <?= formatPrice((float)$svc['price']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Data e Takimit <span>*</span></label>
                    <input type="date" name="date" id="dateInput" class="form-control"
                           min="<?= date('Y-m-d') ?>"
                           max="<?= date('Y-m-d', strtotime('+3 months')) ?>"
                           required>
                </div>

                <div class="form-group" id="slotsContainer" style="display:none;">
                    <label class="form-label">Ora e Takimit <span>*</span></label>
                    <input type="hidden" name="time_slot" id="timeSlotInput" required>
                    <div class="time-slots" id="timeSlots"></div>
                    <p class="form-hint" id="noSlotsMsg" style="display:none;">
                        Nuk ka orare të lira për këtë datë. Ju lutemi zgjidhni datë tjetër.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shënime (opsionale)</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Simptoma, histori mjekësore ose kërkesa speciale…"></textarea>
                </div>

                <div id="reserveError" class="alert alert-error" style="display:none;"></div>

                <button type="submit" class="btn btn-cta w-100" id="submitBtn" disabled>
                    Konfirmo Rezervimin →
                </button>
                <p class="form-hint text-center mt-8">Pas rezervimit do të merrni email konfirmimi.</p>
            </form>
        </div>

        <!-- Summary sidebar -->
        <aside class="reserve-summary">
            <h4>Përmbledhje</h4>

            <div class="row">
                <span class="k">Mjeku</span>
                <span class="v" id="sumDoctor">—</span>
            </div>
            <div class="row">
                <span class="k">Shërbimi</span>
                <span class="v" id="sumService">—</span>
            </div>
            <div class="row">
                <span class="k">Data</span>
                <span class="v" id="sumDate">—</span>
            </div>
            <div class="row">
                <span class="k">Ora</span>
                <span class="v" id="sumTime">—</span>
            </div>

            <div class="total">
                <span>Totali</span>
                <span id="sumTotal">—</span>
            </div>

            <p class="form-hint mt-16" style="font-size:0.78rem;">
                Anulimi është i lirë deri 24 orë para takimit.
            </p>
        </aside>

    </div>

</main>
</div>

<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';
?>
