<?php
// ============================================================
// auth.php - Kontrolli i autentikimit dhe aksesit sipas rolit
// ============================================================

// ---- Kontrollo nëse përdoruesi është i loguar ----
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// ---- Kthe rolin e përdoruesit aktual ----
function getCurrentRole(): string|null {
    return $_SESSION['role'] ?? null;
}

// ---- Kthe ID-në e përdoruesit aktual ----
function getCurrentUserId(): int|null {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

// ---- Kthe të gjitha të dhënat e sesionit të userit ----
function getCurrentUser(): array|null {
    if (!isLoggedIn()) return null;
    return [
        'id'         => $_SESSION['user_id'],
        'name'       => $_SESSION['name']       ?? '',
        'email'      => $_SESSION['email']      ?? '',
        'role'       => $_SESSION['role']        ?? '',
        'photo_path' => $_SESSION['photo_path'] ?? null,
    ];
}

// ---- Kontrollo nëse useri ka rolin e kërkuar ----
function hasRole(string $role): bool {
    return isLoggedIn() && $_SESSION['role'] === $role;
}

// ---- Kërko login — ridrejto nëse nuk është i loguar ----
function requireLogin(): void {
    if (!isLoggedIn()) {
        // Ruaj URL-në ku donte të shkonte (për ridrejtim pas login)
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        setFlashMessage('error', ERR_ACCESS_DENIED);
        header('Location: ' . REDIRECT_LOGIN);
        exit;
    }
}

// ---- Kërko rol specifik — ridrejto nëse roli nuk përputhet ----
function requireRole(string $role): void {
    requireLogin();

    if ($_SESSION['role'] !== $role) {
        setFlashMessage('error', ERR_ACCESS_DENIED);

        // Ridrejto tek dashboard-i i rolit të tij
        switch ($_SESSION['role']) {
            case ROLE_PATIENT:
                header('Location: ' . REDIRECT_PATIENT);
                break;
            case ROLE_DOCTOR:
                header('Location: ' . REDIRECT_DOCTOR);
                break;
            case ROLE_ADMIN:
                header('Location: ' . REDIRECT_ADMIN);
                break;
            default:
                header('Location: ' . REDIRECT_LOGIN);
        }
        exit;
    }
}

// ---- Ridrejto nëse tashmë është i loguar (për login/register page) ----
function redirectIfLoggedIn(): void {
    if (!isLoggedIn()) return;

    switch ($_SESSION['role']) {
        case ROLE_PATIENT:
            header('Location: ' . REDIRECT_PATIENT);
            break;
        case ROLE_DOCTOR:
            header('Location: ' . REDIRECT_DOCTOR);
            break;
        case ROLE_ADMIN:
            header('Location: ' . REDIRECT_ADMIN);
            break;
    }
    exit;
}

// ---- Vendos sesionin pas login të suksesshëm ----
function setUserSession(array $user): void {
    // Rigjeneroj session ID për të parandaluar Session Fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int)$user['id'];
    $_SESSION['name']       = $user['name'];
    $_SESSION['email']      = $user['email'];
    $_SESSION['role']       = $user['role'];
    $_SESSION['photo_path'] = $user['photo_path'] ?? null;
    $_SESSION['login_time'] = time();
}

// ---- Kontrollo Referrer (Referral Check) ----
// Faqet e brendshme aksesohen vetëm brenda aplikacionit
function checkReferrer(): void {
    // Lejon akses direkt vetëm për faqet publike
    if (empty($_SERVER['HTTP_REFERER'])) return;

    $referer = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $host    = parse_url(BASE_URL, PHP_URL_HOST);

    if ($referer !== $host) {
        setFlashMessage('error', ERR_ACCESS_DENIED);
        header('Location: ' . REDIRECT_HOME);
        exit;
    }
}

// ---- Logout: fshij sesionin plotësisht ----
function logoutUser(): void {
    // Regjistro kohën e logout në session_logs
    if (isLoggedIn()) {
        logSessionAction(getCurrentUserId(), getCurrentRole(), 'logout');
    }

    $_SESSION = [];

    // Fshij cookie-n e sesionit
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}

// ---- Regjistro veprimet e sesionit (login/logout) në DB ----
function logSessionAction(int $userId, string $role, string $action): void {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        // Mbështet IPv6 dhe proxy
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        $ip = filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '0.0.0.0';

        db()->execute(
            "INSERT INTO session_logs (user_id, role, action, ip_address)
             VALUES (?, ?, ?, ?)",
            [$userId, $role, $action, $ip]
        );
    } catch (Exception $e) {
        // Nuk ndalo ekzekutimin nëse logging dështon
        error_log('Session log error: ' . $e->getMessage());
    }
}
