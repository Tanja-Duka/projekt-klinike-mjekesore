<?php
// ============================================================
// patient/dashboard.php
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

requireRole(ROLE_PATIENT);

$patientId = getCurrentUserId();

$upcomingAppointments = db()->fetchAll(
    "SELECT a.*, u.name AS doctor_name, u.specialization, s.name AS service_name, s.price
     FROM appointments a
     JOIN users u ON a.doctor_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.patient_id = ? AND a.status IN (?, ?)
       AND a.appointment_date >= CURDATE()
     ORDER BY a.appointment_date ASC, a.time_slot ASC
     LIMIT 5",
    [$patientId, STATUS_PENDING, STATUS_CONFIRMED]
);

$recentPrescriptions = db()->fetchAll(
    "SELECT pr.*, u.name AS doctor_name, a.appointment_date, s.name AS service_name
     FROM prescriptions pr
     JOIN users u ON pr.doctor_id = u.id
     JOIN appointments a ON pr.appointment_id = a.id
     JOIN services s ON a.service_id = s.id
     WHERE pr.patient_id = ?
     ORDER BY pr.uploaded_at DESC
     LIMIT 3",
    [$patientId]
);

$stats = [
    'total'     => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ?", [$patientId])['c'],
    'upcoming'  => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status IN (?,?) AND appointment_date >= CURDATE()", [$patientId, STATUS_PENDING, STATUS_CONFIRMED])['c'],
    'completed' => db()->fetchOne("SELECT COUNT(*) as c FROM appointments WHERE patient_id = ? AND status = ?", [$patientId, STATUS_COMPLETED])['c'],
    'rx_count'  => db()->fetchOne("SELECT COUNT(*) as c FROM prescriptions WHERE patient_id = ?", [$patientId])['c'],
];

$nextAppt = !empty($upcomingAppointments) ? $upcomingAppointments[0] : null;

$pageTitle = 'Paneli Im — ' . APP_NAME;
$cssFile   = 'dashboard.css';
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.pt-kpi-row{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border:1px solid var(--line);border-radius:14px;background:var(--page);overflow:hidden;margin-bottom:28px;}
.pt-kpi{padding:22px 24px;border-right:1px solid var(--line);}
.pt-kpi:last-child{border-right:0;}
.pt-kpi .lab{font-size:.68rem;text-transform:uppercase;letter-spacing:.1em;color:var(--ink-3);margin-bottom:8px;}
.pt-kpi .num{font-family:'Fraunces',serif;font-size:2.2rem;font-weight:300;color:var(--ink-1);line-height:1;}
.pt-kpi .num em{font-style:italic;color:var(--accent);}
.pt-kpi .sub{font-size:.76rem;color:var(--ink-3);margin-top:5px;}

.next-appt-banner{display:flex;align-items:center;gap:20px;background:var(--ink-1);color:#fff;border-radius:14px;padding:22px 28px;margin-bottom:28px;}
.next-appt-banner .date-pill{background:rgba(255,255,255,.12);border-radius:10px;padding:12px 18px;text-align:center;flex-shrink:0;}
.next-appt-banner .date-pill .day{font-family:'Fraunces',serif;font-size:2rem;font-weight:300;line-height:1;}
.next-appt-banner .date-pill .mon{font-size:.65rem;letter-spacing:.1em;text-transform:uppercase;opacity:.6;margin-top:2px;}
.next-appt-banner .info{flex:1;}
.next-appt-banner .info strong{display:block;font-size:1rem;font-weight:600;margin-bottom:3px;}
.next-appt-banner .info span{font-size:.82rem;opacity:.65;}
.next-appt-banner .appt-pill{background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);border-radius:8px;padding:6px 14px;font-size:.8rem;opacity:.85;}

.qa-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:28px;}
.qa-card{display:flex;align-items:center;gap:16px;padding:20px 22px;background:var(--page);border:1px solid var(--line);border-radius:14px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s;}
.qa-card:hover{border-color:var(--accent);box-shadow:0 4px 18px rgba(0,0,0,.06);}
.qa-card .icon{width:42px;height:42px;border-radius:10px;background:var(--surface,#f5f1e8);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--accent);}
.qa-card h4{font-size:.9rem;font-weight:600;margin-bottom:2px;}
.qa-card p{font-size:.76rem;color:var(--ink-3);margin:0;}

.appt-row{display:grid;grid-template-columns:68px 1fr auto;gap:18px;align-items:center;padding:16px 28px;border-top:1px solid var(--line);}
.appt-row:first-child{border-top:0;}
.date-box{text-align:center;background:var(--surface,#f9f7f3);border:1px solid var(--line);border-radius:10px;padding:8px 6px;}
.date-box .d{font-family:'Fraunces',serif;font-size:1.5rem;font-weight:300;line-height:1;color:var(--ink-1);}
.date-box .m{font-size:.6rem;text-transform:uppercase;letter-spacing:.08em;color:var(--ink-3);margin-top:2px;}
.appt-info strong{display:block;font-size:.9rem;font-weight:600;margin-bottom:2px;}
.appt-info span{font-size:.78rem;color:var(--ink-3);}
.appt-acts{display:flex;align-items:center;gap:8px;}

.rx-row{display:grid;grid-template-columns:40px 1fr auto;gap:16px;align-items:center;padding:14px 28px;border-top:1px solid var(--line);}
.rx-row:first-child{border-top:0;}
.rx-icon{width:40px;height:40px;border-radius:50%;background:var(--surface,#f5f1e8);display:flex;align-items:center;justify-content:center;color:var(--accent);}
.rx-info strong{display:block;font-size:.88rem;font-weight:600;margin-bottom:1px;}
.rx-info span{font-size:.76rem;color:var(--ink-3);}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'patient'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Paneli i pacientit</div>
            <h1>Mirë se erdhe, <em class="serif-italic"><?= e(explode(' ', $_SESSION['name'])[0]) ?></em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">Ja një përmbledhje e shpejtë e takimeve dhe recetave tuaja.</p>
        </div>
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta">+ Rezervo Takim</a>
    </div>

    <?php displayFlashMessage(); ?>

    <!-- KPI row -->
    <div class="pt-kpi-row">
        <div class="pt-kpi">
            <div class="lab">Takime Gjithsej</div>
            <div class="num"><em><?= (int)$stats['total'] ?></em></div>
            <div class="sub">të gjitha periudhat</div>
        </div>
        <div class="pt-kpi">
            <div class="lab">Të Ardhshme</div>
            <div class="num"><em><?= (int)$stats['upcoming'] ?></em></div>
            <div class="sub">të planifikuara</div>
        </div>
        <div class="pt-kpi">
            <div class="lab">Vizita të Kryera</div>
            <div class="num"><?= (int)$stats['completed'] ?></div>
            <div class="sub">me sukses</div>
        </div>
        <div class="pt-kpi">
            <div class="lab">Receta</div>
            <div class="num"><?= (int)$stats['rx_count'] ?></div>
            <div class="sub">dixhitale</div>
        </div>
    </div>

    <!-- Next appointment highlight -->
    <?php if ($nextAppt):
        $d = explode('-', $nextAppt['appointment_date']);
        $mon = mb_substr(MONTHS_SQ[(int)$d[1]], 0, 3);
    ?>
    <div class="next-appt-banner">
        <div class="date-pill">
            <div class="day"><?= (int)$d[2] ?></div>
            <div class="mon"><?= $mon ?></div>
        </div>
        <div class="info">
            <strong>Takimi juaj i ardhshëm</strong>
            <span>Dr. <?= e($nextAppt['doctor_name']) ?> · <?= e($nextAppt['service_name']) ?> · <?= e($nextAppt['time_slot']) ?></span>
        </div>
        <span class="appt-pill"><?= getStatusBadge($nextAppt['status']) ?></span>
    </div>
    <?php endif; ?>

    <!-- Quick actions -->
    <div class="qa-grid">
        <a href="<?= BASE_URL ?>/patient/reserve.php" class="qa-card">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18M12 14v4M10 16h4"/></svg>
            </div>
            <div>
                <h4>Rezervo Takim</h4>
                <p>Zgjidh mjekun dhe orarin</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/patient/appointments.php" class="qa-card">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 12h6M9 16h4"/></svg>
            </div>
            <div>
                <h4>Takimet e Mia</h4>
                <p>Historiku i vizitave</p>
            </div>
        </a>
        <a href="<?= BASE_URL ?>/patient/prescriptions.php" class="qa-card">
            <div class="icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="20" height="20"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><circle cx="11" cy="14" r="2"/><path d="m13 16 2 2"/></svg>
            </div>
            <div>
                <h4>Recetat e Mia</h4>
                <p>Shkarko recetat dixhitale</p>
            </div>
        </a>
    </div>

    <!-- Upcoming appointments -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header">
            <h3>Takimet e Ardhshme</h3>
            <a href="<?= BASE_URL ?>/patient/appointments.php" class="btn btn-outline btn-sm">Shiko të gjitha →</a>
        </div>

        <?php if (empty($upcomingAppointments)): ?>
            <div class="empty-state" style="padding:48px 0;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" width="40" height="40" style="opacity:.3"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                <h3>Nuk keni takime të ardhshme</h3>
                <p>Rezervoni takimin tuaj të parë tani.</p>
                <a href="<?= BASE_URL ?>/patient/reserve.php" class="btn btn-cta" style="margin-top:16px;">Rezervo Takim →</a>
            </div>
        <?php else: ?>
            <?php foreach ($upcomingAppointments as $appt):
                $d = explode('-', $appt['appointment_date']);
                $mon = mb_substr(MONTHS_SQ[(int)$d[1]], 0, 3);
            ?>
            <div class="appt-row">
                <div class="date-box">
                    <div class="d"><?= (int)$d[2] ?></div>
                    <div class="m"><?= $mon ?></div>
                </div>
                <div class="appt-info">
                    <strong>Dr. <?= e($appt['doctor_name']) ?></strong>
                    <span><?= e($appt['service_name']) ?> &nbsp;·&nbsp; ora <?= e($appt['time_slot']) ?>
                        <?php if (!empty($appt['specialization'])): ?>
                        &nbsp;·&nbsp; <?= e($appt['specialization']) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="appt-acts">
                    <?= getStatusBadge($appt['status']) ?>
                    <?php if (in_array($appt['status'], [STATUS_PENDING, STATUS_CONFIRMED])): ?>
                    <button class="btn btn-sm cancel-btn"
                            style="background:var(--error-bg,#fff0f0);color:var(--error,#c0392b);border:1px solid currentColor;"
                            data-id="<?= (int)$appt['id'] ?>"
                            data-csrf="<?= e(getCsrfToken()) ?>">Anulo</button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Recent prescriptions -->
    <?php if (!empty($recentPrescriptions)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Recetat e Fundit</h3>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php" class="btn btn-outline btn-sm">Shiko të gjitha →</a>
        </div>
        <?php foreach ($recentPrescriptions as $rx): ?>
        <div class="rx-row">
            <div class="rx-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="18" height="18"><path d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
            </div>
            <div class="rx-info">
                <strong><?= e($rx['service_name']) ?></strong>
                <span>Dr. <?= e($rx['doctor_name']) ?> &nbsp;·&nbsp; <?= formatDateSq($rx['appointment_date']) ?></span>
            </div>
            <a href="<?= BASE_URL ?>/patient/prescriptions.php?download=<?= (int)$rx['id'] ?>"
               class="btn btn-outline btn-sm">↓ Shkarko</a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</main>
</div>

<?php
$extraJs = ['reserve.js'];
include BASE_PATH . '/includes/footer.php';
?>
