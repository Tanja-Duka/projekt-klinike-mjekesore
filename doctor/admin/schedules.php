<?php
// ============================================================
// admin/schedules.php - Cakto oraret e mjekëve
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors = [];

// Week navigation — offset in weeks from current Monday
$weekOffset = cleanInt($_GET['week'] ?? 0);
$monday = new DateTime('monday this week');
if ($weekOffset !== 0) {
    $monday->modify($weekOffset . ' weeks');
}

$weekDays = [];
for ($i = 0; $i < 7; $i++) {
    $d = clone $monday;
    $d->modify("+{$i} days");
    $weekDays[] = $d;
}

// Day-of-week keys that match DAYS_SQ array keys
$dayKeys = array_keys(DAYS_SQ);   // Monday, Tuesday, …
$dayLabels = [
    'Monday' => 'Hën', 'Tuesday' => 'Mar', 'Wednesday' => 'Mër',
    'Thursday' => 'Enj', 'Friday' => 'Pre', 'Saturday' => 'Sht', 'Sunday' => 'Die',
];

// POST: add or delete schedule slot
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
            redirect(BASE_URL . '/doctor/admin/schedules.php?week=' . $weekOffset);
        }
    }

    if ($action === 'delete') {
        $scheduleId = cleanInt($_POST['schedule_id'] ?? 0);
        if ($scheduleId > 0) {
            db()->execute("DELETE FROM schedules WHERE id = ?", [$scheduleId]);
            setFlashMessage('success', 'Orari u fshi.');
        }
        redirect(BASE_URL . '/doctor/admin/schedules.php?week=' . $weekOffset);
    }
}

// Load all active doctors + their schedules
$doctors = getAllDoctors();

// Fetch all schedules, index by doctor_id → day_of_week
$allSchedules = db()->fetchAll(
    "SELECT * FROM schedules WHERE is_available = 1 ORDER BY doctor_id, FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday')"
);
$schedIndex = [];
foreach ($allSchedules as $slot) {
    $schedIndex[$slot['doctor_id']][$slot['day_of_week']] = $slot;
}

$pageTitle = 'Oraret e Mjekëve — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.sched-grid{display:grid;grid-template-columns:160px repeat(7,1fr);gap:1px;background:var(--line);border:1px solid var(--line);border-radius:14px;overflow:hidden;margin-bottom:8px;}
.sched-grid > div{background:var(--page);padding:10px 12px;}
.sched-grid .sh{background:var(--ink-1);color:rgba(255,255,255,.65);font-size:.68rem;letter-spacing:.1em;text-transform:uppercase;text-align:center;padding:14px 8px;}
.sched-grid .sh em{font-style:normal;display:block;font-family:var(--serif,Georgia,serif);font-size:1.3rem;margin-top:3px;color:#fff;letter-spacing:-.01em;}
.sched-doc{background:var(--surface,#f9f7f3);display:flex;flex-direction:column;justify-content:center;gap:2px;padding:12px 14px;}
.sched-doc strong{font-size:.85rem;font-weight:600;}
.sched-doc small{font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-3);}
.sched-cell{min-height:60px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;font-size:.78rem;text-align:center;padding:8px 6px;}
.sched-cell.work{background:#f5f1e8;color:var(--ink-1);font-weight:500;}
.sched-cell.off{background:var(--page);color:var(--ink-3);font-style:italic;font-size:.72rem;}
.sched-cell .del-btn{font-size:.6rem;padding:2px 6px;background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;border-radius:4px;cursor:pointer;display:none;}
.sched-cell.work:hover .del-btn{display:block;}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Operacione</div>
            <h1>Oraret e <em class="serif-italic">mjekëve</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                Java <?= $weekDays[0]->format('d') ?>–<?= $weekDays[6]->format('d M Y') ?>
            </p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="?week=<?= $weekOffset - 1 ?>" class="btn btn-outline">‹ Java e kaluar</a>
            <?php if ($weekOffset !== 0): ?>
            <a href="?week=0" class="btn btn-ghost">Sot</a>
            <?php endif; ?>
            <a href="?week=<?= $weekOffset + 1 ?>" class="btn btn-outline">Java tjetër ›</a>
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

    <!-- Weekly grid -->
    <div class="data-section" style="margin-bottom:24px;">
        <div style="overflow-x:auto;padding:20px;">
            <?php if (empty($doctors)): ?>
            <div class="empty-state" style="padding:32px 0;"><h3>Nuk ka mjekë regjistruar.</h3></div>
            <?php else: ?>
            <div class="sched-grid">
                <!-- Header row -->
                <div class="sh" style="text-align:left;padding-left:14px;">Mjeku</div>
                <?php foreach ($weekDays as $i => $day): ?>
                <div class="sh">
                    <?= $dayLabels[$day->format('l')] ?>
                    <em><?= $day->format('d') ?></em>
                </div>
                <?php endforeach; ?>

                <!-- Doctor rows -->
                <?php foreach ($doctors as $doc): ?>
                <div class="sched-doc">
                    <strong><?= e($doc['name']) ?></strong>
                    <small><?= e($doc['specialization'] ?? '') ?></small>
                </div>
                <?php foreach ($weekDays as $day):
                    $dayKey = $day->format('l'); // Monday, Tuesday, etc.
                    $slot = $schedIndex[$doc['id']][$dayKey] ?? null;
                ?>
                <div class="sched-cell <?= $slot ? 'work' : 'off' ?>">
                    <?php if ($slot): ?>
                        <?= e(substr($slot['start_time'], 0, 5)) ?>–<?= e(substr($slot['end_time'], 0, 5)) ?>
                        <form method="POST" action="" style="margin:0;" onsubmit="return confirm('Fshij orarin?')">
                            <?= csrfInput() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="doctor_id" value="<?= (int)$doc['id'] ?>">
                            <input type="hidden" name="schedule_id" value="<?= (int)$slot['id'] ?>">
                            <button type="submit" class="del-btn">Fshij</button>
                        </form>
                    <?php else: ?>
                        —
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
            <p style="font-size:.78rem;color:var(--ink-3);margin-top:10px;">Rri mbi një qelizë pune për të fshirë atë orar.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add/edit form -->
    <div class="dashboard-form" id="addScheduleForm">
        <h3>Shto ose Ndrysho Orar</h3>
        <p style="color:var(--ink-3);font-size:.88rem;margin-bottom:20px;">Nëse dita ekziston tashmë për këtë mjek, orari do të përditësohet automatikisht.</p>
        <form method="POST" action="?week=<?= $weekOffset ?>">
            <?= csrfInput() ?>
            <input type="hidden" name="action" value="add">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mjeku <span>*</span></label>
                    <select name="doctor_id" class="form-control" required>
                        <option value="">— Zgjidh mjekun —</option>
                        <?php foreach ($doctors as $doc): ?>
                        <option value="<?= (int)$doc['id'] ?>">
                            <?= e($doc['name']) ?><?= !empty($doc['specialization']) ? ' — ' . e($doc['specialization']) : '' ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Dita e Javës <span>*</span></label>
                    <select name="day_of_week" class="form-control" required>
                        <option value="">— Zgjidh ditën —</option>
                        <?php foreach (DAYS_SQ as $key => $label): ?>
                        <option value="<?= e($key) ?>"><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
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

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
