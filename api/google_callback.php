<?php
// ============================================================
// google_callback.php - Google OAuth2 Callback
// ============================================================

// TODO: require_once '../config/config.php'
// TODO: require_once '../config/google.php'
// TODO: Merr 'code' nga $_GET, valido 'state' anti-CSRF
// TODO: POST tek Google: këmbej code → access_token
// TODO: GET tek Google userinfo: emri, email, google_id, foto
// TODO: Kontrollo nëse email ekziston në users:
//       - PO: update google_id nëse NULL, bëj login
//       - JO: INSERT me role='patient', password_hash=NULL
// TODO: Vendos sesionin dhe ridrejto tek dashboard
