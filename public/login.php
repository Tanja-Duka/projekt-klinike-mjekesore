<?php
// ============================================================
// login.php - Hyrja në sistem (të gjitha rolet)
// ============================================================

// TODO: Nëse i loguar tashmë → ridrejto tek dashboard sipas rolit
// TODO: Forma: email + password + CSRF token
// TODO: Validim JS (frontend)
// TODO: PHP: SELECT FROM users WHERE email=? → password_verify()
// TODO: $_SESSION: user_id, name, email, role, photo_path
// TODO: logUserSession($id, 'login')
// TODO: Ridrejto: patient/ | doctor/ | admin/dashboard.php
// TODO: Butoni "Hyr me Google" → Google OAuth flow
