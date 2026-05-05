<?php
// ============================================================
// admin/session-logs.php - Loget e sesioneve (login/logout)
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

// Filtrat
$filterRole = clean($_GET['role']      ?? '');
$dateFrom   = clean($_GET['date_from'] ?? '');
$dateTo     = clean($_GET['date_to']   ?? '');
$page       = max(1, cleanInt($_GET['page'] ?? 1));

$sql    = "SELECT sl.*, u.name AS user_name, u.email
           FROM session_logs sl
           JOIN users u ON sl.user_id = u.id
           WHERE 1=1";
$params = [];

if (in_array($filterRole, [ROLE_PATIENT, ROLE_DOCTOR, ROLE_ADMIN])) {
    $sql .= " AND sl.role = ?";
    $params[] = $filterRole;
}
if (!empty($dateFrom) && isValidDate($dateFrom)) {
    $sql .= " AND DATE(sl.created_at) >= ?";
    $params[] = $dateFrom;
}
if (!empty($dateTo) && isValidDate($dateTo)) {
    $sql .= " AND DATE(sl.created_at) <= ?";
    $params[] = $dateTo;
}

// Numëro totalet për paginacion
$countSql    = str_replace("SELECT sl.*, u.name AS user_name, u.email", "SELECT COUNT(*) as c", $sql);
$total       = db()->fetchOne($countSql, $params)['c'];
$pagination  = paginate((int)$total, $page);

$sql .= " ORDER BY sl.created_at DESC LIMIT " . RECORDS_PER_PAGE . " OFFSET " . $pagination['offset'];

$logs      = db()->fetchAll($sql, $params);
$pageTitle = 'Loget e Sesioneve';

