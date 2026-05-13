<?php
// ============================================================
// admin/reports.php - Raportet e klinikës (GET-only)
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$dateFrom = clean($_GET['date_from'] ?? date('Y-m-01'));
$dateTo   = clean($_GET['date_to']   ?? date('Y-m-d'));

if (!isValidDate($dateFrom)) $dateFrom = date('Y-m-01');
if (!isValidDate($dateTo))   $dateTo   = date('Y-m-d');

// Raport 1: Takimet sipas ditës
$appointmentReport = db()->fetchAll(
    "SELECT a.appointment_date, COUNT(*) AS total,
            SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS completed,
            SUM(CASE WHEN a.status = ? THEN 1 ELSE 0 END) AS cancelled,
            SUM(CASE WHEN a.status IN (?,?) THEN 1 ELSE 0 END) AS pending
     FROM appointments a
     WHERE a.appointment_date BETWEEN ? AND ?
     GROUP BY a.appointment_date
     ORDER BY a.appointment_date ASC",
    [STATUS_COMPLETED, STATUS_CANCELLED, STATUS_PENDING, STATUS_CONFIRMED, $dateFrom, $dateTo]
);

// Raport 2: Të ardhurat
$revenueReport = db()->fetchOne(
    "SELECT COUNT(*) AS total_completed, SUM(s.price) AS total_revenue
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.status = ? AND a.appointment_date BETWEEN ? AND ?",
    [STATUS_COMPLETED, $dateFrom, $dateTo]
);

// Raport 3: TOP 5 mjekë
$topDoctors = db()->fetchAll(
    "SELECT u.name, u.specialization,
            COUNT(a.id) AS total_appointments,
            SUM(s.price) AS revenue
     FROM appointments a
     JOIN users u ON a.doctor_id = u.id
     JOIN services s ON a.service_id = s.id
     WHERE a.status = ? AND a.appointment_date BETWEEN ? AND ?
     GROUP BY u.id, u.name, u.specialization
     ORDER BY total_appointments DESC
     LIMIT 5",
    [STATUS_COMPLETED, $dateFrom, $dateTo]
);

// Raport 4: Shërbimet më të kërkuara
$topServices = db()->fetchAll(
    "SELECT s.name, s.category, COUNT(a.id) AS bookings, s.price
     FROM appointments a
     JOIN services s ON a.service_id = s.id
     WHERE a.appointment_date BETWEEN ? AND ?
     GROUP BY s.id, s.name, s.category, s.price
     ORDER BY bookings DESC
     LIMIT 5",
    [$dateFrom, $dateTo]
);

// KPI sums
$totalAppointments  = array_sum(array_column($appointmentReport, 'total'));
$totalCompleted     = (int)($revenueReport['total_completed'] ?? 0);
$totalRevenue       = (float)($revenueReport['total_revenue'] ?? 0);
$activeDays         = count($appointmentReport);

// Bar chart — max value for scaling
$barMax = $totalAppointments > 0
    ? max(array_column($appointmentReport, 'total'))
    : 1;

// Top doctors/services max for bar scaling
$docMax = !empty($topDoctors) ? max(array_column($topDoctors, 'total_appointments')) : 1;
$svcMax = !empty($topServices) ? max(array_column($topServices, 'bookings')) : 1;

$pageTitle = 'Raportet — ' . APP_NAME;
$cssFile   = 'dashboard.css';
$extraCss  = ['forms.css'];
include BASE_PATH . '/includes/header.php';
include BASE_PATH . '/includes/navbar.php';
?>

<style>
.kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px;}
@media(max-width:900px){.kpi-grid{grid-template-columns:repeat(2,1fr);}}
.kpi{background:var(--page);border:1px solid var(--line);border-radius:14px;padding:22px 20px;}
.kpi .lab{font-size:.72rem;text-transform:uppercase;letter-spacing:.07em;color:var(--ink-3);margin-bottom:8px;}
.kpi .val{font-family:'Fraunces',serif;font-size:2rem;font-weight:300;color:var(--ink-1);line-height:1.1;}
.kpi .val em{font-style:normal;color:var(--accent);}
.kpi .sub{font-size:.76rem;color:var(--ink-3);margin-top:6px;}
.kpi .delta{display:inline-flex;align-items:center;gap:3px;font-size:.72rem;font-weight:600;margin-top:6px;padding:2px 7px;border-radius:20px;}
.kpi .delta.up{background:#edfaf3;color:#1a7a47;}
.kpi .delta.dn{background:#fff0f0;color:#c0392b;}

.bar-chart{display:flex;align-items:flex-end;gap:5px;height:160px;overflow-x:auto;padding-bottom:0;}
.bar{display:flex;flex-direction:column;align-items:center;justify-content:flex-end;flex:1;min-width:26px;background:linear-gradient(180deg,var(--accent),#b8825c);border-radius:4px 4px 0 0;font-size:.6rem;font-weight:600;color:#fff;padding-bottom:3px;position:relative;cursor:default;transition:opacity .18s;}
.bar:hover{opacity:.8;}
.bar .lb{position:absolute;bottom:-18px;left:50%;transform:translateX(-50%);font-size:.58rem;color:var(--ink-3);white-space:nowrap;font-weight:400;}

.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}
@media(max-width:780px){.split-grid{grid-template-columns:1fr;}}
.legend-section{background:var(--page);border:1px solid var(--line);border-radius:14px;padding:22px 20px;}
.legend-section h3{font-size:.9rem;font-weight:600;margin:0 0 16px;text-transform:uppercase;letter-spacing:.05em;color:var(--ink-2);}
.legend-row{display:flex;align-items:center;gap:10px;margin-bottom:11px;}
.legend-row span:first-child{flex:0 0 130px;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--ink-2);}
.bar-mini{flex:1;height:5px;background:var(--line);border-radius:3px;overflow:hidden;}
.bar-mini > span{display:block;height:100%;background:var(--accent);border-radius:3px;}
.legend-row strong{flex:0 0 28px;text-align:right;font-size:.78rem;color:var(--ink-1);}
</style>

<div class="dashboard-wrapper">
<?php $sidebarRole = 'admin'; include BASE_PATH . '/includes/sidebar.php'; ?>

<main class="main-content">

    <div class="content-header">
        <div>
            <div class="eyebrow">Admin — Analitikë</div>
            <h1>Raportet e <em class="serif-italic">klinikës</em>.</h1>
            <p style="color:var(--ink-2);margin-top:6px;">
                <?= formatDateSq($dateFrom) ?> — <?= formatDateSq($dateTo) ?>
            </p>
        </div>
        <button class="btn btn-outline" onclick="window.print()" style="white-space:nowrap;">Printo Raportin</button>
    </div>

    <!-- Filtër datash -->
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header"><h3>Periudha</h3></div>
        <div class="data-section-body" style="padding:16px;">
            <form method="GET" action="" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
                <div class="form-group" style="margin:0;min-width:150px;">
                    <label class="form-label" style="font-size:.8rem;">Nga data</label>
                    <input type="date" name="date_from" class="form-control" value="<?= e($dateFrom) ?>">
                </div>
                <div class="form-group" style="margin:0;min-width:150px;">
                    <label class="form-label" style="font-size:.8rem;">Deri në datë</label>
                    <input type="date" name="date_to" class="form-control" value="<?= e($dateTo) ?>">
                </div>
                <button type="submit" class="btn btn-cta">Gjenero →</button>
            </form>
        </div>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi">
            <div class="lab">Të ardhura</div>
            <div class="val"><em><?= number_format($totalRevenue, 0, ',', ' ') ?></em> L</div>
            <div class="sub">nga vizitat e kryera</div>
        </div>
        <div class="kpi">
            <div class="lab">Vizita të kryera</div>
            <div class="val"><em><?= $totalCompleted ?></em></div>
            <div class="sub">nga <?= $totalAppointments ?> gjithsej</div>
        </div>
        <div class="kpi">
            <div class="lab">Gjithsej takime</div>
            <div class="val"><em><?= $totalAppointments ?></em></div>
            <div class="sub">në periudhën e zgjedhur</div>
        </div>
        <div class="kpi">
            <div class="lab">Ditë aktive</div>
            <div class="val"><em><?= $activeDays ?></em></div>
            <div class="sub">ditë me takime</div>
        </div>
    </div>

    <!-- Bar chart: takime sipas ditës -->
    <?php if (!empty($appointmentReport)): ?>
    <div class="data-section" style="margin-bottom:24px;">
        <div class="data-section-header"><h3>Takime sipas Ditës</h3></div>
        <div class="data-section-body" style="padding:24px 20px 32px;">
            <div class="bar-chart">
                <?php foreach ($appointmentReport as $row):
                    $pct = $barMax > 0 ? round(($row['total'] / $barMax) * 100) : 0;
                    $label = date('d/m', strtotime($row['appointment_date']));
                ?>
                <div class="bar" style="height:<?= max(8, $pct) ?>%;" title="<?= (int)$row['total'] ?> takime">
                    <?= (int)$row['total'] ?><span class="lb"><?= e($label) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Split: Top mjekë + Top shërbime -->
    <div class="split-grid">

        <!-- Top 5 Mjekë -->
        <div class="legend-section">
            <h3>Top 5 Mjekët</h3>
            <?php if (empty($topDoctors)): ?>
                <p style="color:var(--ink-3);font-size:.85rem;">Nuk ka të dhëna.</p>
            <?php else: ?>
            <?php foreach ($topDoctors as $doc):
                $pct = $docMax > 0 ? round(($doc['total_appointments'] / $docMax) * 100) : 0;
            ?>
            <div class="legend-row">
                <span title="<?= e($doc['name']) ?>"><?= e($doc['name']) ?></span>
                <div class="bar-mini"><span style="width:<?= $pct ?>%;"></span></div>
                <strong><?= (int)$doc['total_appointments'] ?></strong>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Top 5 Shërbime -->
        <div class="legend-section">
            <h3>Top 5 Shërbimet</h3>
            <?php if (empty($topServices)): ?>
                <p style="color:var(--ink-3);font-size:.85rem;">Nuk ka të dhëna.</p>
            <?php else: ?>
            <?php foreach ($topServices as $svc):
                $pct = $svcMax > 0 ? round(($svc['bookings'] / $svcMax) * 100) : 0;
            ?>
            <div class="legend-row">
                <span title="<?= e($svc['name']) ?>"><?= e($svc['name']) ?></span>
                <div class="bar-mini"><span style="width:<?= $pct ?>%;"></span></div>
                <strong><?= (int)$svc['bookings'] ?></strong>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <!-- Tabela e plotë: takimet sipas ditës -->
    <?php if (!empty($appointmentReport)): ?>
    <div class="data-section">
        <div class="data-section-header">
            <h3>Detajet sipas Ditës</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Gjithsej</th>
                        <th>Të Kryera</th>
                        <th>Të Anulura</th>
                        <th>Në Pritje</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($appointmentReport as $row): ?>
                <tr>
                    <td><?= formatDateSq($row['appointment_date']) ?></td>
                    <td><strong><?= (int)$row['total'] ?></strong></td>
                    <td><?= (int)$row['completed'] ?></td>
                    <td><?= (int)$row['cancelled'] ?></td>
                    <td><?= (int)$row['pending'] ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

</main>
</div>

<?php include BASE_PATH . '/includes/footer.php'; ?>
