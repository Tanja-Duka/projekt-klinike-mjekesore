<?php
// ============================================================
// logout.php - Dalja nga sistemi
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';

// Vetëm POST i lejuar (buton me formë + CSRF) — parandalon logout me GET/link
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(REDIRECT_HOME);
}

// Valido CSRF
verifyCsrfOrDie();

// Bëj logout (regjistron session_log + fshin sesionin)
logoutUser();

setFlashMessage('success', MSG_LOGOUT_SUCCESS);
redirect(REDIRECT_LOGIN);
