<?php
// ============================================================
// csrf.php - Mbrojtja CSRF (Cross-Site Request Forgery)
// ============================================================

// ---- Gjenero token të ri CSRF dhe ruaje në sesion ----
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// ---- Kthe tokenin aktual (gjenero nëse nuk ekziston) ----
function getCsrfToken(): string {
    return generateCsrfToken();
}

// ---- Valido tokenin e dërguar nga forma ----
function validateCsrfToken(string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    // hash_equals parandalon Timing Attack
    return hash_equals($_SESSION['csrf_token'], $token);
}

// ---- Kthe HTML input të fshehur me token ----
// Përdorim: echo csrfInput(); brenda çdo forme
function csrfInput(): string {
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(getCsrfToken(), ENT_QUOTES, 'UTF-8')
         . '">';
}

// ---- Valido CSRF ose ndalo ekzekutimin ----
// Thirret në krye të çdo skripti që trajton POST
function verifyCsrfOrDie(): void {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

    if (!validateCsrfToken($token)) {
        // Nëse është kërkesë AJAX, kthe JSON
        if (isAjaxRequest()) {
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => ERR_CSRF_INVALID
            ]);
            exit;
        }
        // Nëse jo AJAX, ridrejto me mesazh gabimi
        setFlashMessage('error', ERR_CSRF_INVALID);
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? REDIRECT_HOME));
        exit;
    }
}

// ---- Rifresko tokenin pas çdo POST të suksesshëm ----
// Opsionale — rrit sigurinë por mund të shkaktojë probleme me tab të shumta
function refreshCsrfToken(): void {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// ---- Kontrollo nëse kërkesa është AJAX ----
function isAjaxRequest(): bool {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
