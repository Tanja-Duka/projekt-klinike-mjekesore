<?php
// ============================================================
// admin/schedules.php - Cakto oraret e mjekëve
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors         = [];
$selectedDoctor = cleanInt($_GET['doctor_id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $action    = clean($_POST['action']      ?? '');
    $doctorId  = cleanInt($_POST['doctor_id']   ?? 0);
    $dayOfWeek = clean($_POST['day_of_week']    ?? '');
    $startTime = clean($_POST['start_time']     ?? '');
    $endTime   = clean($_POST['end_time']       ?? '');

    $validDays = array_keys(DAYS_SQ);

    if ($action === 'add') {
        if ($doctorId <= 0 || !in_array($dayOfWeek, $validDays) || empty($startTime) || empty($endTime)) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } elseif ($startTime >= $endTime) {
            $errors[] = 'Ora e fillimit duhet të jetë para orës së mbarimit.';
        } else {
            $existing = db()->fetchOne(
                "SELECT id FROM schedules WHERE doctor_id = ? AND day_of_week = ?",
                [$doctorId, $dayOfWeek]
            );
            if ($existing) {
                db()->execute(
                    "UPDATE schedules SET start_time=?, end_time=?, is_available=1 WHERE id=?",
                    [$startTime, $endTime, $existing['id']]
                );
            } else {
                db()->insert(
                    "INSERT INTO schedules (doctor_id, day_of_week, start_time, end_time, is_available)
                     VALUES (?, ?, ?, ?, 1)",
                    [$doctorId, $dayOfWeek, $startTime, $endTime]
                );
            }
            setFlashMessage('success', 'Orari u ruajt me sukses!');
            redirect(BASE_URL . '/doctor/admin/schedules.php?doctor_id=' . $doctorId);
        }
    }

    if ($action === 'delete') {
        $scheduleId = cleanInt($_POST['schedule_id'] ?? 0);
        if ($scheduleId > 0) {
            db()->execute("DELETE FROM schedules WHERE id = ?", [$scheduleId]);
            setFlashMessage('success', 'Orari u fshi.');
        }
        redirect(BASE_URL . '/doctor/admin/schedules.php?doctor_id=' . $doctorId);
    }
}

$schedule = $selectedDoctor > 0 ? getDoctorSchedule($selectedDoctor) : [];
$doctors  = getAllDoctors();
$days     = DAYS_SQ;

// Index schedule by day for grid lookup
$schedByDay = [];
foreach ($schedule as $slot) {
    $schedByDay[$slot['day_of_week']] = $slot;
}

$selectedDoc = null;
foreach ($doctors as $d) {
    if ((int)$d['id'] === $selectedDoctor) { $selectedDoc = $d; break; }
}

$pageTitle = 'Oraret e Mjekëve — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.sched-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:12px;margin-bottom:32px;}
.sched-day-card{border:1px solid var(--line);border-radius:12px;overflow:hidden;}
.sched-day-header{background:var(--ink-1);color:var(--page);padding:10px 14px;font-family:'Fraunces',serif;font-size:.82rem;font-weight:600;letter-spacing:.04em;text-align:center;}
.sched-day-body{padding:14px;min-height:80px;display:flex;flex-direction:column;justify-content:center;align-items:center;gap:8px;}
.sched-day-body.work{background:#f5f1e8;color:var(--ink-1);}
.sched-day-body.off{background:#fbfaf7;color:var(--ink-3);}
.sched-time{font-size:.78rem;font-weight:600;text-align:center;line-height:1.5;}
.sched-off-label{font-size:.75rem;font-style:italic;color:var(--ink-3);}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Menaxhim</div>
            <h1>Oraret <em class="serif-italic">javore</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Cakto dhe ndrysho oraret e punës për çdo mjek.</p>
        </div>
    </div>

    <?php displayFlashMessage(); ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-error" style="margin-bottom:24px;">
        <ul style="margin:0;padding-left:16px;">
            <?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Zgjidh mjekun -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header"><h3>Zgjidh Mjekun</h3></div>
        <form method="GET" action="" style="padding:16px;display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
            <div class="form-group" style="margin:0;min-width:280px;flex:1;">
                <label class="form-label" style="font-size:.8rem;">Mjeku</label>
                <select name="doctor_id" class="form-control" onchange="this.form.submit()">
                    <option value="">— Zgjidh mjekun —</option>
                    <?php foreach ($doctors as $doc): ?>
                    <option value="<?= (int)$doc['id'] ?>" <?= $selectedDoctor === (int)$doc['id'] ? 'selected' : '' ?>>
                        <?= e($doc['name']) ?><?= !empty($doc['specialization']) ? ' — ' . e($doc['specialization']) : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <?php if ($selectedDoctor > 0): ?>

    <!-- Orari — pamja javore (grid) -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header">
            <h3>
                Orari i
                <strong><?= e($selectedDoc ? $selectedDoc['name'] : 'Mjekut') ?></strong>
            </h3>
            <span style="font-size:.8rem;color:var(--ink-3);"><?= count($schedByDay) ?> ditë pune</span>
        </div>
        <div style="padding:20px;">
            <div class="sched-grid">
                <?php foreach ($days as $dayKey => $dayLabel): ?>
                <?php $slot = $schedByDay[$dayKey] ?? null; ?>
                <div class="sched-day-card">
                    <div class="sched-day-header"><?= e($dayLabel) ?></div>
                    <div class="sched-day-body <?= $slot ? 'work' : 'off' ?>">
                        <?php if ($slot): ?>
                            <div class="sched-time">
                                <?= e(substr($slot['start_time'], 0, 5)) ?><br>—<br><?= e(substr($slot['end_time'], 0, 5)) ?>
                            </div>
                            <form method="POST" action="" style="margin:0;" onsubmit="return confirm('Fshij orarin e kësaj dite?')">
                                <?= csrfInput() ?>
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="doctor_id" value="<?= (int)$selectedDoctor ?>">
                                <input type="hidden" name="schedule_id" value="<?= (int)$slot['id'] ?>">
                                <button type="submit" class="btn btn-sm"
                                    style="background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;font-size:.7rem;padding:2px 8px;">
                                    Fshij
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="sched-off-label">Ditë pushimi</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Forma shto / ndrysho orar -->
    <div class="dashboard-form" id="addScheduleForm">
        <h3>Shto ose Ndrysho Orar</h3>
        <p style="color:var(--ink-3);font-size:.88rem;margin-bottom:20px;">
            Nëse dita ekziston tashmë, orari do të përditësohet automatikisht.
        </p>
        <form method="POST" action="">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="doctor_id" value="<?= (int)$selectedDoctor ?>">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Dita e Javës <span>*</span></label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">— Zgjidh ditën —</option>
                        <?php foreach ($days as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><!-- spacer --></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Ora e Fillimit <span>*</span></label>
                    <input type="time" name="start_time" class="form-control" required value="08:00">
                </div>
                <div class="form-group">
                    <label class="form-label">Ora e Mbarimit <span>*</span></label>
                    <input type="time" name="end_time" class="form-control" required value="16:00">
                </div>
            </div>
            <button type="submit" class="btn btn-cta">Ruaj Orarin</button>
        </form>
    </div>

    <?php else: ?>
    <div class="data-section">
        <div class="empty-state" style="padding:64px 0;">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.3"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
            <h3>Zgjidh një mjek</h3>
            <p style="color:var(--ink-3);font-size:.9rem;margin-top:4px;">Zgjidhni një mjek nga lista e mësipërme për të parë ose ndryshuar orarin.</p>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
