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

// Top doctors max visits for bar scaling
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
.kpi .lab{font-size:.75rem;text-transform:uppercase;letter-spacing:.06em;color:var(--ink-3);margin-bottom:6px;}
.kpi .val{font-family:'Fraunces',serif;font-size:2.2rem;font-weight:300;color:var(--ink-1);line-height:1;}
.kpi .sub{font-size:.78rem;color:var(--ink-3);margin-top:4px;}
.bar-section{background:var(--page);border:1px solid var(--line);border-radius:14px;padding:22px 20px;margin-bottom:24px;}
.bar-chart{display:flex;align-items:flex-end;gap:6px;height:180px;overflow-x:auto;padding-bottom:4px;}
.bar-col{display:flex;flex-direction:column;align-items:center;gap:4px;flex:1;min-width:28px;}
.bar{width:100%;background:linear-gradient(180deg,var(--accent),#b8825c);border-radius:4px 4px 0 0;transition:opacity .2s;}
.bar:hover{opacity:.8;}
.bar-label{font-size:.6rem;color:var(--ink-3);white-space:nowrap;transform:rotate(-40deg);transform-origin:top right;margin-top:2px;}
.split-grid{display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:24px;}
@media(max-width:780px){.split-grid{grid-template-columns:1fr;}}
.legend-section{background:var(--page);border:1px solid var(--line);border-radius:14px;padding:22px 20px;}
.legend-section h3{font-size:.95rem;font-weight:600;margin:0 0 16px;}
.legend-row{display:flex;align-items:center;gap:10px;margin-bottom:12px;}
.legend-row .name{flex:0 0 140px;font-size:.82rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
.legend-row .bar-track{flex:1;height:6px;background:var(--line);border-radius:3px;overflow:hidden;}
.legend-row .bar-fill{height:100%;background:var(--accent);border-radius:3px;}
.legend-row .count{flex:0 0 32px;text-align:right;font-size:.78rem;font-weight:600;color:var(--ink-2);}
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
        <form method="GET" action="" style="padding:16px;display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end;">
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

    <!-- KPI Cards -->
    <div class="kpi-grid">
        <div class="kpi">
            <div class="lab">Të ardhura</div>
            <div class="val"><?= formatPrice($totalRevenue) ?></div>
            <div class="sub">nga vizitat e kryera</div>
        </div>
        <div class="kpi">
            <div class="lab">Vizita të kryera</div>
            <div class="val"><?= $totalCompleted ?></div>
            <div class="sub">nga <?= $totalAppointments ?> gjithsej</div>
        </div>
        <div class="kpi">
            <div class="lab">Gjithsej takime</div>
            <div class="val"><?= $totalAppointments ?></div>
            <div class="sub">në periudhën e zgjedhur</div>
        </div>
        <div class="kpi">
            <div class="lab">Ditë aktive</div>
            <div class="val"><?= $activeDays ?></div>
            <div class="sub">ditë me takime</div>
        </div>
    </div>

    <!-- Bar chart: takime sipas ditës -->
    <?php if (!empty($appointmentReport)): ?>
    <div class="bar-section">
        <h3 style="font-size:.95rem;font-weight:600;margin:0 0 20px;">Takime sipas Ditës</h3>
        <div class="bar-chart">
            <?php foreach ($appointmentReport as $row):
                $pct = $barMax > 0 ? round(($row['total'] / $barMax) * 100) : 0;
            ?>
            <div class="bar-col">
                <div class="bar" style="height:<?= max(4, $pct) ?>%;" title="<?= (int)$row['total'] ?> takime"></div>
                <span class="bar-label"><?= e(date('d/m', strtotime($row['appointment_date']))) ?></span>
            </div>
            <?php endforeach; ?>
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
                <span class="name" title="<?= e($doc['name']) ?>"><?= e($doc['name']) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%;"></span></span>
                <span class="count"><?= (int)$doc['total_appointments'] ?></span>
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
                <span class="name" title="<?= e($svc['name']) ?>"><?= e($svc['name']) ?></span>
                <span class="bar-track"><span class="bar-fill" style="width:<?= $pct ?>%;"></span></span>
                <span class="count"><?= (int)$svc['bookings'] ?></span>
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
