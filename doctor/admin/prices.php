<?php
// ============================================================
// admin/prices.php - Menaxho shërbimet dhe çmimet
// ============================================================

require_once dirname(__DIR__, 2) . '/config/config.php';

requireRole(ROLE_ADMIN);

$errors = [];
$action = clean($_POST['action'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfOrDie();

    // ---- SHTO shërbim ----
    if ($action === 'add') {
        $name        = clean($_POST['name']        ?? '');
        $description = clean($_POST['description'] ?? '');
        $category    = clean($_POST['category']    ?? '');
        $price       = cleanFloat($_POST['price']  ?? 0);
        $icon        = clean($_POST['icon']        ?? '');

        if (empty($name) || empty($category) || $price <= 0) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } else {
            db()->insert(
                "INSERT INTO services (name, description, category, price, icon, is_active)
                 VALUES (?, ?, ?, ?, ?, 1)",
                [$name, $description, $category, $price, $icon]
            );
            setFlashMessage('success', 'Shërbimi u shtua me sukses!');
            redirect(BASE_URL . '/doctor/admin/prices.php');
        }
    }

    // ---- EDITO shërbim ----
    if ($action === 'edit') {
        $serviceId   = cleanInt($_POST['service_id'] ?? 0);
        $name        = clean($_POST['name']          ?? '');
        $description = clean($_POST['description']   ?? '');
        $category    = clean($_POST['category']      ?? '');
        $price       = cleanFloat($_POST['price']    ?? 0);
        $icon        = clean($_POST['icon']          ?? '');

        if ($serviceId <= 0 || empty($name) || $price <= 0) {
            $errors[] = ERR_REQUIRED_FIELDS;
        } else {
            db()->execute(
                "UPDATE services SET name=?, description=?, category=?, price=?, icon=?
                 WHERE id=?",
                [$name, $description, $category, $price, $icon, $serviceId]
            );
            setFlashMessage('success', MSG_PRICE_UPDATED);
            redirect(BASE_URL . '/doctor/admin/prices.php');
        }
    }

    // ---- TOGGLE aktivizim ----
    if ($action === 'toggle') {
        $serviceId = cleanInt($_POST['service_id'] ?? 0);
        if ($serviceId > 0) {
            db()->execute(
                "UPDATE services SET is_active = IF(is_active=1, 0, 1) WHERE id=?",
                [$serviceId]
            );
        }
        redirect(BASE_URL . '/doctor/admin/prices.php');
    }
}

// Të gjitha shërbimet (aktive + joaktive)
$services  = db()->fetchAll("SELECT * FROM services ORDER BY is_active DESC, category ASC, name ASC");
$pageTitle = 'Shërbimet dhe Çmimet';

