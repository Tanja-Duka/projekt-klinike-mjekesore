<?php
// ============================================================
// api/google_callback.php - Google OAuth2 Callback Handler
// ============================================================
defined('BASE_PATH') or define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/config/config.php';
require_once BASE_PATH . '/config/google.php';

// ---- Nëse klikoi butonin "Hyr me Google" / "Regjistrohu me Google" ----
// Ridrejto tek Google për autorizim
if (isset($_GET['action'])) {
    $action = in_array($_GET['action'], ['login', 'register']) ? $_GET['action'] : 'login';
    redirect(getGoogleAuthUrl($action));
}

// ---- Callback nga Google ----

// 1. Kontrollo nëse Google ktheu gabim
if (isset($_GET['error'])) {
    setFlashMessage('error', 'Hyrja me Google u anulua. Ju lutemi provoni përsëri.');
    redirect(REDIRECT_LOGIN);
}

// 2. Kontrollo që kemi 'code' dhe 'state'
if (empty($_GET['code']) || empty($_GET['state'])) {
    setFlashMessage('error', ERR_GENERAL);
    redirect(REDIRECT_LOGIN);
}

// 3. Valido state token (mbrojtje CSRF për OAuth)
if (empty($_SESSION['google_state']) || $_GET['state'] !== $_SESSION['google_state']) {
    unset($_SESSION['google_state']);
    setFlashMessage('error', ERR_CSRF_INVALID);
    redirect(REDIRECT_LOGIN);
}
unset($_SESSION['google_state']);

// 4. Merr action-in e ruajtur
$action = $_SESSION['google_action'] ?? 'login';
unset($_SESSION['google_action']);

// 5. Këmbej code → access token
$tokenData = getGoogleAccessToken($_GET['code']);
if (!$tokenData) {
    setFlashMessage('error', 'Nuk u mor token nga Google. Provoni përsëri.');
    redirect(REDIRECT_LOGIN);
}

// 6. Merr të dhënat e userit nga Google
$googleUser = getGoogleUserInfo($tokenData['access_token']);
if (!$googleUser) {
    setFlashMessage('error', 'Nuk u morën të dhënat nga Google. Provoni përsëri.');
    redirect(REDIRECT_LOGIN);
}

// 7. Pastro të dhënat nga Google
$googleId = clean($googleUser['sub']);           // ID unike e Google
$email    = cleanEmail($googleUser['email']);     // Email i verifikuar
$name     = clean($googleUser['name'] ?? '');    // Emri i plotë
$photo    = clean($googleUser['picture'] ?? ''); // URL e fotos (opsionale)

if (!$email) {
    setFlashMessage('error', 'Email i pavlefshëm nga Google.');
    redirect(REDIRECT_LOGIN);
}

// 8. Kontrollo nëse ky user ekziston tashmë në DB
$existingUser = getUserByEmail($email);

if ($existingUser) {
    // ---- USER EKZISTON ----

    // Kontrollo nëse llogaria është aktive
    if (!$existingUser['is_active']) {
        setFlashMessage('error', ERR_ACCOUNT_INACTIVE);
        redirect(REDIRECT_LOGIN);
    }

    // Nëse llogaria ekziston por është krijuar me fjalëkalim (jo Google)
    // Lidhe llogarinë me Google duke shtuar google_id
    if (empty($existingUser['google_id'])) {
        db()->execute(
            "UPDATE users SET google_id = ? WHERE id = ?",
            [$googleId, $existingUser['id']]
        );
    }

    // Bëj login
    setUserSession($existingUser);
    logSessionAction($existingUser['id'], $existingUser['role'], 'login');
    setFlashMessage('success', MSG_LOGIN_SUCCESS);
    redirectByRole();

} else {
    // ---- USER NUK EKZISTON — KRIJO LLOGARI TË RE ----

    // Nëse action është 'login' dhe useri nuk ekziston → sugjerimi
    if ($action === 'login') {
        setFlashMessage('error', 'Nuk u gjet llogari me këtë email Google. Ju lutemi regjistrojuni së pari.');
        redirect(REDIRECT_LOGIN);
    }

    // Krijo llogari të re si pacient
    try {
        db()->beginTransaction();

        $userId = db()->insert(
            "INSERT INTO users
                (name, email, password_hash, role, google_id,
                 photo_path, is_active, created_at)
             VALUES (?, ?, NULL, ?, ?, ?, 1, NOW())",
            [
                $name,
                $email,
                ROLE_PATIENT,
                $googleId,
                $photo ?: null,
            ]
        );

        db()->commit();

        // Dërgo email mirëseardhjeje
        sendWelcomeEmail($email, $name);

        // Merr userin e ri nga DB dhe bëj login
        $newUser = getUserById((int)$userId);
        if (!$newUser) throw new Exception('User not found after insert');

        setUserSession($newUser);
        logSessionAction($newUser['id'], $newUser['role'], 'login');

        setFlashMessage('success', MSG_REGISTER_SUCCESS);
        redirect(REDIRECT_PATIENT);

    } catch (Exception $e) {
        db()->rollBack();
        error_log('Google register error: ' . $e->getMessage());
        setFlashMessage('error', ERR_GENERAL);
        redirect(REDIRECT_LOGIN);
    }
}
