<?php
// ============================================================
// index.php - Pika hyrëse: ridrejto tek faqja kryesore
// ============================================================

require_once __DIR__ . '/config/config.php';

// Nëse useri është i loguar ridrejto direkt tek dashboard-i i tij
if (isLoggedIn()) {
    redirectByRole();
}

redirect(BASE_URL . '/public/home.php');
