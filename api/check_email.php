<?php
// ============================================================
// api/check_email.php - AJAX: Kontrollo nëse emaili ekziston
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// Vetëm kërkesa AJAX
if (!isAjaxRequest()) {
    http_response_code(403);
    exit;
}

$email = cleanEmail($_GET['email'] ?? '');

if (!$email) {
    jsonResponse(false, 'Email i pavlefshëm.', ['exists' => false]);
}

jsonResponse(true, '', ['exists' => emailExists($email)]);