<?php
// ============================================================
// admin/contact-queries.php - Menaxho mesazhet e kontaktit
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    $action  = clean($_POST['action']   ?? '');
    $queryId = cleanInt($_POST['query_id'] ?? 0);

    if ($queryId > 0) {
        if ($action === 'read') {
            db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_READ, $queryId]);
        } elseif ($action === 'resolve') {
            db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_RESOLVED, $queryId]);
        } elseif ($action === 'delete') {
            db()->execute("DELETE FROM contact_queries WHERE id = ?", [$queryId]);
        }
    }
    redirect(BASE_URL . '/doctor/admin/contact-queries.php');
}

// Shfaq mesazhin e plotë
$viewId       = cleanInt($_GET['view'] ?? 0);
$viewQuery    = null;
if ($viewId > 0) {
    $viewQuery = db()->fetchOne("SELECT * FROM contact_queries WHERE id = ?", [$viewId]);
    // Shëno automatikisht si lexuar kur hapet
    if ($viewQuery && $viewQuery['status'] === QUERY_UNREAD) {
        db()->execute("UPDATE contact_queries SET status = ? WHERE id = ?", [QUERY_READ, $viewId]);
        $viewQuery['status'] = QUERY_READ;
    }
}

// Filtër sipas statusit
$filterStatus = clean($_GET['status'] ?? '');
$sql = "SELECT * FROM contact_queries";
$params = [];
if (in_array($filterStatus, [QUERY_UNREAD, QUERY_READ, QUERY_RESOLVED])) {
    $sql .= " WHERE status = ?";
    $params[] = $filterStatus;
}
$sql .= " ORDER BY created_at DESC";

$queries   = db()->fetchAll($sql, $params);
$pageTitle = 'Mesazhet e Kontaktit';
