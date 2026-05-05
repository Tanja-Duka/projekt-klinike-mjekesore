<?php
// ============================================================
// api/search.php - Kërkim live mjekësh dhe shërbimesh (AJAX GET)
// ============================================================

require_once dirname(__DIR__) . '/config/config.php';

header('Content-Type: application/json');

if (!isAjaxRequest()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => ERR_ACCESS_DENIED]);
    exit;
}

$q = clean($_GET['q'] ?? '');

// Minimum 2 karaktere për kërkim
if (mb_strlen($q) < 2) {
    echo json_encode(['success' => true, 'doctors' => [], 'services' => []]);
    exit;
}

$like = '%' . $q . '%';

// Kërko mjekë (emër ose specializim)
$doctors = db()->fetchAll(
    "SELECT id, name, specialization, photo_path, consultation_fee
     FROM users
     WHERE role = ? AND is_active = 1
       AND (name LIKE ? OR specialization LIKE ?)
     ORDER BY name ASC
     LIMIT 5",
    [ROLE_DOCTOR, $like, $like]
);

// Kërko shërbime
$services = db()->fetchAll(
    "SELECT id, name, category, price, icon
     FROM services
     WHERE is_active = 1
       AND (name LIKE ? OR category LIKE ?)
     ORDER BY name ASC
     LIMIT 5",
    [$like, $like]
);

// Formatimi i çmimeve para dërgimit
foreach ($doctors as &$d) {
    $d['fee_formatted'] = formatPrice((float)$d['consultation_fee']);
    $d['photo_url']     = getPhotoUrl($d['photo_path'], 'doctor');
}
foreach ($services as &$s) {
    $s['price_formatted'] = formatPrice((float)$s['price']);
}

echo json_encode([
    'success'  => true,
    'doctors'  => $doctors,
    'services' => $services,
]);
