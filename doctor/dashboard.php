<?php
// ============================================================
// doctor/dashboard.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_DOCTOR);

$doctorId = getCurrentUserId();
$today    = date('Y-m-d');

$todayAppointments = getAppointmentsByDoctor($doctorId, $today);

$nextWeekAppointments = db()->fetchAll(
    "SELECT a.*, u.name AS patient_name, u.phone AS patient_phone, s.name AS service_name
     FROM appointments a
     JOIN users u ON a.patient_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.doctor_id = ? AND a.appointment_date > ? AND a.appointment_date <= DATE_ADD(?, INTERVAL 7 DAY)
       AND a.status IN (?, ?)
     ORDER BY a.appointment_date ASC, a.time_slot ASC",
    [$doctorId, $today, $today, STATUS_PENDING, STATUS_CONFIRMED]
);

$stats = [
    'today_count'    => count($todayAppointments),
    'week_count'     => count($nextWeekAppointments),
    'total_patients' => db()->fetchOne(
        "SELECT COUNT(DISTINCT patient_id) as c FROM appointments WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
    'rx_count' => db()->fetchOne(
        "SELECT COUNT(*) as c FROM prescriptions WHERE doctor_id = ?",
        [$doctorId]
    )['c'],
];

$firstSlot = !empty($todayAppointments) ? $todayAppointments[0]['time_slot'] : null;
$lastSlot  = !empty($todayAppointments) ? end($todayAppointments)['time_slot'] : null;

$dayNames     = DAYS_SQ;
$todayDayName = $dayNames[date('l')] ?? date('l');

$pageTitle = 'Paneli Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.dr-qa-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px;}
.dr-qa{display:flex;align-items:center;gap:16px;padding:20px 22px;background:var(--page);border:1px solid var(--line);border-radius:14px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s;}
.dr-qa:hover{border-color:var(--accent);box-shadow:0 4px 18px rgba(0,0,0,.06);}
.dr-qa .icon{width:42px;height:42px;border-radius:10px;background:var(--surface,#f5f1e8);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent);}
.dr-qa h4{font-size:.9rem;font-weight:600;margin-bottom:2px;}
.dr-qa p{font-size:.76rem;color:var(--ink-3);margin:0;}

.today-row{display:grid;grid-template-columns:80px 1fr auto;gap:18px;align-items:center;padding:16px 28px;border-top:1px solid var(--line);}
.today-row:first-child{border-top:0;}
.time-box{text-align:center;background:var(--surface,#f9f7f3);border:1px solid var(--line);border-radius:10px;padding:10px 6px;}
.time-box .t{font-family:'Fraunces',serif;font-size:1.15rem;font-weight:300;color:var(--accent);line-height:1;}
.time-box .end{font-size:.6rem;color:var(--ink-3);letter-spacing:.05em;margin-top:3px;}
.pt-info strong{display:block;font-size:.9rem;font-weight:600;margin-bottom:2px;}
.pt-info span{font-size:.78rem;color:var(--ink-3);}
.pt-acts{display:flex;align-items:center;gap:8px;}

.week-row{display:grid;grid-template-columns:56px 80px 1fr auto;gap:14px;align-items:center;padding:13px 28px;border-top:1px solid var(--line);}
.week-row:first-child{border-top:0;}
.week-date{text-align:center;}
.week-date .wd{font-size:.6rem;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);}
.week-date .wn{font-family:'Fraunces',serif;font-size:1.3rem;font-weight:300;color:var(--ink-1);line-height:1;}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'doctor'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow"><?= e($todayDayName) ?>, <?= formatDateSq($today) ?></div>
            <h1>Mirë se erdhe, Dr. <em class="serif-italic"><?= e(explode(' ', $_SESSION['name'])[0]) ?></em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?php if ($firstSlot): ?>
                    Sot keni <?= (int)$stats['today_count'] ?> takim<?= $stats['today_count'] !== 1 ? 'e' : '' ?> — i pari në <?= e($firstSlot) ?>, i fundit në <?= e($lastSlot) ?>.
                <?php else: ?>
                    Nuk keni takime sot. Ditë e lirë!
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>/doctor/upload_rx.php" class="btn btn-cta">+ Lësho recetë</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- Stats row -->
    <div class="dr-stats-row" style="margin-bottom:28px;">
        <div class="dr-stat">
            <div class="lab">Sot</div>
            <div class="num"><em><?= (int)$stats['today_count'] ?></em></div>
            <div class="meta">takime të planifikuara</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Këtë javë</div>
            <div class="num"><?= (int)$stats['week_count'] ?></div>
            <div class="meta">takime të ardhshme</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Pacientë gjithsej</div>
            <div class="num"><?= (int)$stats['total_patients'] ?></div>
            <div class="meta">në bazën time</div>
        </div>
        <div class="dr-stat">
            <div class="lab">Receta të lëshuara</div>
            <div class="num"><?= (int)$stats['rx_count'] ?></div>
            <div class="meta">gjithsej</div>
        </div>
    </div>

    <!-- Today's appointments -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header">
            <h3>Sot — <?= formatDateSq($today) ?></h3>
            <a href="<?= BASE_URL ?>/doctor/schedule.php" class="btn btn-ghost btn-sm">Shiko orarin javor →</a>
        </div>

        <?php if (empty($todayAppointments)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.3"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk keni takime sot</h3>
                <p>Gjithçka është e qetë për sot.</p>
            </div>
        <?php else: ?>
            <?php foreach ($todayAppointments as $appt):
                $endTime = date('H:i', strtotime($appt['time_slot']) + 1800);
            ?>
            <div class="today-row">
                <div class="time-box">
                    <div class="t"><?= e($appt['time_slot']) ?></div>
                    <div class="end">– <?= $endTime ?></div>
                </div>
                <div class="pt-info">
                    <strong><?= e($appt['patient_name']) ?></strong>
                    <span><?= e($appt['service_name']) ?>
                        <?php if (!empty($appt['patient_phone'])): ?>
                        &nbsp;·&nbsp; <?= e($appt['patient_phone']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="pt-acts">
                    <?= getStatusBadge($appt['status']) ?>
                    <a href="<?= BASE_URL ?>/doctor/upload_rx.php?appointment_id=<?= (int)$appt['id'] ?>"
                       class="btn btn-outline btn-sm">+ Recetë</a>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Coming up this week -->
    <?php if (!empty($nextWeekAppointments)): ?>
    <div class="data-section" style="margin-bottom:28px;">
        <div class="data-section-header">
            <h3>Javën e Ardhshme</h3>
            <span style="font-size:.8rem;color:var(--ink-3);"><?= count($nextWeekAppointments) ?> takim<?= count($nextWeekAppointments) !== 1 ? 'e' : '' ?></span>
        </div>
        <?php foreach ($nextWeekAppointments as $appt):
            $d = explode('-', $appt['appointment_date']);
            $mon = mb_substr(MONTHS_SQ[(int)$d[1]] ?? '', 0, 3);
            $dayLabel = DAYS_SQ[date('l', strtotime($appt['appointment_date']))] ?? '';
        ?>
        <div class="week-row">
            <div class="week-date">
                <div class="wd"><?= mb_substr($dayLabel, 0, 3) ?></div>
                <div class="wn"><?= (int)$d[2] ?></div>
            </div>
            <span style="font-size:.78rem;color:var(--ink-3);font-family:monospace;"><?= e($appt['time_slot']) ?></span>
            <div class="pt-info">
                <strong><?= e($appt['patient_name']) ?></strong>
                <span><?= e($appt['service_name']) ?></span>
            </div>
            <?= getStatusBadge($appt['status']) ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="dr-qa-grid">
        <a href="<?= BASE_URL ?>/doctor/patients.php" class="dr-qa">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><circle cx="9" cy="7" r="4"/><path d="M3 21v-2a4 4 0 0 1 4-4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0-3-3.85"/></svg>
            </div>
            <div>
                <h4>Pacientët</h4>
                <p><?= (int)$stats['total_patients'] ?> në bazën e të dhënave</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/doctor/schedule.php" class="dr-qa">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
                <h4>Orari javor</h4>
                <p>Menaxho disponueshmërinë</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/doctor/upload_rx.php" class="dr-qa">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M12 5v14M5 12l7-7 7 7"/></svg>
            </div>
            <div>
                <h4>Lësho recetë</h4>
                <p>Ngarko PDF për pacientin</p>
            </div>
        </a>
    </div>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
