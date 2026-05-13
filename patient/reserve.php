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

<style>
.reserve-steps{display:flex;gap:0;margin-bottom:28px;border:1px solid var(--line);border-radius:12px;overflow:hidden;}
.reserve-step{flex:1;display:flex;align-items:center;gap:12px;padding:16px 20px;background:var(--page);border-right:1px solid var(--line);font-size:.82rem;}
.reserve-step:last-child{border-right:none;}
.reserve-step .num{width:32px;height:32px;border-radius:50%;background:var(--line);color:var(--ink-3);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;flex-shrink:0;}
.reserve-step .lab{font-size:.68rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-3);}
.reserve-step .name{font-weight:600;color:var(--ink-2);}
.reserve-step.active .num{background:var(--accent);color:#fff;}
.reserve-step.active .name{color:var(--ink-1);}
.reserve-step.done .num{background:var(--ink-1);color:#fff;}

.doctor-chips{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:24px;}
.doctor-chip{display:flex;align-items:center;gap:10px;padding:10px 14px;border:1.5px solid var(--line);border-radius:10px;cursor:pointer;transition:border-color .15s,background .15s;background:var(--page);}
.doctor-chip:hover{border-color:var(--accent);background:var(--accent-tint,#faf7f2);}
.doctor-chip.selected{border-color:var(--accent);background:var(--accent-tint,#f5efe8);}
.doctor-chip .av{width:36px;height:36px;border-radius:50%;background:var(--ink-1);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.72rem;flex-shrink:0;}
.doctor-chip .info{display:flex;flex-direction:column;gap:1px;}
.doctor-chip .info strong{font-size:.85rem;line-height:1.2;}
.doctor-chip .info span{font-size:.72rem;color:var(--ink-3);}
.doctor-chip .price{margin-left:auto;font-size:.82rem;font-weight:600;color:var(--accent);white-space:nowrap;}

.doctor-mini{display:flex;align-items:center;gap:12px;padding-bottom:16px;margin-bottom:16px;border-bottom:1px solid var(--line);}
.doctor-mini .av{width:40px;height:40px;border-radius:50%;background:var(--ink-1);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.78rem;flex-shrink:0;}
.doctor-mini div strong{display:block;font-size:.9rem;}
.doctor-mini div span{font-size:.75rem;color:var(--ink-3);}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Rezervim i ri</div>
            <h1>Rezervo një <em class="serif-italic">takim</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Tre hapa të thjeshtë. Pas konfirmimit do të merrni email me të gjitha detajet.</p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Progress steps -->
    <div class="reserve-steps">
        <div class="reserve-step active">
            <span class="num">01</span>
            <span><span class="lab">Hapi 01</span><span class="name">Mjeku &amp; shërbimi</span></span>
        </div>
        <div class="reserve-step">
            <span class="num">02</span>
            <span><span class="lab">Hapi 02</span><span class="name">Data &amp; ora</span></span>
        </div>
        <div class="reserve-step">
            <span class="num">03</span>
            <span><span class="lab">Hapi 03</span><span class="name">Konfirmo</span></span>
        </div>
    </div>

    <div class="reserve-container">

        <!-- Form -->
        <div class="dashboard-form">
            <h3>Zgjedh mjekun</h3>

            <!-- Doctor chips -->
            <div class="doctor-chips" id="doctorChips">
                <?php foreach ($doctors as $doc):
                    $initials = getInitials($doc['name']);
                    $isSelected = $preselectedDoctorId === (int)$doc['id'];
                ?>
                <div class="doctor-chip <?= $isSelected ? 'selected' : '' ?>"
                     data-id="<?= (int)$doc['id'] ?>"
                     data-name="<?= e($doc['name']) ?>"
                     data-spec="<?= e($doc['specialization'] ?? '') ?>"
                     data-fee="<?= (float)$doc['consultation_fee'] ?>"
                     onclick="selectDoctor(this)">
                    <span class="av"><?= e($initials) ?></span>
                    <span class="info">
                        <strong><?= e($doc['name']) ?></strong>
                        <span><?= e($doc['specialization'] ?? '—') ?></span>
                    </span>
                    <span class="price"><?= formatPrice((float)$doc['consultation_fee']) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <form id="reserveForm">
                <?= csrfInput() ?>

                <!-- Hidden doctor id — updated by chip click -->
                <input type="hidden" name="doctor_id" id="doctorSelect"
                       value="<?= $preselectedDoctorId ?: '' ?>" required>

                <div class="form-group">
                    <label class="form-label">Shërbimi <span>*</span></label>
                    <select name="service_id" id="serviceSelect" class="form-control" required>
                        <option value="">— Zgjedh Shërbimin —</option>
                        <?php foreach ($services as $svc): ?>
                        <option value="<?= (int)$svc['id'] ?>"
                                data-price="<?= (float)$svc['price'] ?>"
                                data-name="<?= e($svc['name']) ?>">
                            <?= e($svc['name']) ?> — <?= formatPrice((float)$svc['price']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Data e takimit <span>*</span></label>
                        <input type="date" name="date" id="dateInput" class="form-control"
                               min="<?= date('Y-m-d') ?>"
                               max="<?= date('Y-m-d', strtotime('+3 months')) ?>"
                               required>
                    </div>
                    <div class="form-group"><!-- spacer --></div>
                </div>

                <div class="form-group" id="slotsContainer" style="display:none;">
                    <label class="form-label">Ora <span>*</span></label>
                    <input type="hidden" name="time_slot" id="timeSlotInput" required>
                    <div class="time-slots" id="timeSlots"></div>
                    <p class="form-hint" id="noSlotsMsg" style="display:none;">
                        Nuk ka orare të lira për këtë datë. Ju lutemi zgjidhni datë tjetër.
                    </p>
                </div>

                <div class="form-group">
                    <label class="form-label">Shënime (opsionale)</label>
                    <textarea name="notes" class="form-control" rows="3"
                              placeholder="Simptoma, historia mjekësore ose kërkesa speciale…"></textarea>
                </div>

                <div id="reserveError" class="alert alert-error" style="display:none;"></div>

                <div style="display:flex;gap:10px;align-items:center;">
                    <button class="btn btn-cta" type="submit" style="flex:1;" id="submitBtn" disabled>
                        Konfirmo rezervimin →
                    </button>
                </div>
                <p class="form-hint text-center mt-16">Pas rezervimit do të merrni email konfirmimi. Mund të anuloni deri 24h para takimit.</p>
            </form>
        </div>

        <!-- Summary sidebar -->
        <aside class="reserve-summary">
            <h4>Përmbledhje</h4>

            <div class="doctor-mini" id="sumDoctorMini" style="display:none;">
                <span class="av" id="sumDoctorAv">—</span>
                <div>
                    <strong id="sumDoctorName">—</strong>
                    <span id="sumDoctorSpec">—</span>
                </div>
            </div>

            <div class="row"><span class="k">Shërbimi</span><span class="v" id="sumService">—</span></div>
            <div class="row"><span class="k">Data</span><span class="v" id="sumDate">—</span></div>
            <div class="row"><span class="k">Ora</span><span class="v" id="sumTime">—</span></div>
            <div class="row"><span class="k">Vendndodhja</span><span class="v">Kati 3</span></div>
            <div class="row"><span class="k">Kohëzgjatja</span><span class="v">~30 min</span></div>

            <div class="total">
                <span>Totali</span>
                <span id="sumTotal">—</span>
            </div>

            <p class="form-hint mt-16">Pagesa kryhet në recepsion në ditën e takimit. Pranojmë kesh dhe kartë.</p>
        </aside>

    </div>

</main>
</div>

<script>
function selectDoctor(el) {
    document.querySelectorAll('.doctor-chip').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');

    const id   = el.dataset.id;
    const name = el.dataset.name;
    const spec = el.dataset.spec;
    const fee  = parseFloat(el.dataset.fee);

    document.getElementById('doctorSelect').value = id;

    // Update summary
    var mini = document.getElementById('sumDoctorMini');
    mini.style.display = 'flex';
    document.getElementById('sumDoctorAv').textContent   = el.querySelector('.av').textContent;
    document.getElementById('sumDoctorName').textContent = name;
    document.getElementById('sumDoctorSpec').textContent = spec;

    // Re-check slots if date already chosen
    var dateVal = document.getElementById('dateInput').value;
    if (dateVal) loadSlots(id, dateVal);

    updateTotal();
    // Trigger reserve.js doctor change if it listens
    var evt = new Event('change');
    document.getElementById('doctorSelect').dispatchEvent(evt);
}

function updateTotal() {
    var svc = document.getElementById('serviceSelect');
    if (svc.selectedIndex > 0) {
        var price = parseFloat(svc.options[svc.selectedIndex].dataset.price) || 0;
        document.getElementById('sumTotal').textContent = price.toLocaleString('sq') + ' L';
        document.getElementById('sumService').textContent = svc.options[svc.selectedIndex].dataset.name || '—';
    }
}

document.getElementById('serviceSelect').addEventListener('change', updateTotal);

// Pre-select doctor if passed via URL
<?php if ($preselectedDoctorId > 0): ?>
(function(){
    var chip = document.querySelector('.doctor-chip[data-id="<?= $preselectedDoctorId ?>"]');
    if (chip) selectDoctor(chip);
})();
<?php endif; ?>
</script>

<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';
?>
