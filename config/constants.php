<?php
// ============================================================
// constants.php - Konstantet e roleve, statuseve dhe mesazheve
// ============================================================

// ---- Rolet e përdoruesve ----
define('ROLE_PATIENT', 'patient');
define('ROLE_DOCTOR',  'doctor');
define('ROLE_ADMIN',   'admin');

// ---- Statuset e takimeve (appointments) ----
define('STATUS_PENDING',   'pending');    // Rezervuar, pret konfirmim
define('STATUS_CONFIRMED', 'confirmed'); // Konfirmuar nga admin/mjeku
define('STATUS_COMPLETED', 'completed'); // Vizita u krye
define('STATUS_CANCELLED', 'cancelled'); // Anuluar nga pacienti ose admini

// ---- Statuset e query-ve të kontaktit ----
define('QUERY_UNREAD',   'unread');
define('QUERY_READ',     'read');
define('QUERY_RESOLVED', 'resolved');

// ---- Llojet e lejuara për upload recetash ----
define('RX_ALLOWED_EXT', ['pdf', 'jpg', 'jpeg', 'png']);
define('RX_MAX_SIZE', 5 * 1024 * 1024); // 5MB

// ---- Faqosja (paginacion) ----
define('RECORDS_PER_PAGE', 20);

// ---- Ridrejtimet sipas rolit ----
define('REDIRECT_PATIENT', BASE_URL . '/patient/dashboard.php');
define('REDIRECT_DOCTOR',  BASE_URL . '/doctor/dashboard.php');
define('REDIRECT_ADMIN',   BASE_URL . '/doctor/admin/dashboard.php');
define('REDIRECT_LOGIN',   BASE_URL . '/public/login.php');
define('REDIRECT_HOME',    BASE_URL . '/public/home.php');

// ---- Mesazhet flash (sukses) ----
define('MSG_LOGIN_SUCCESS',      'Jeni identifikuar me sukses!');
define('MSG_LOGOUT_SUCCESS',     'Jeni çidentifikuar me sukses.');
define('MSG_REGISTER_SUCCESS',   'Llogaria u krijua me sukses! Mund të identifikoheni tani.');
define('MSG_PROFILE_UPDATED',    'Profili u përditësua me sukses!');
define('MSG_PASSWORD_CHANGED',   'Fjalëkalimi u ndryshua me sukses!');
define('MSG_APPOINTMENT_BOOKED', 'Takimi u rezervua me sukses! Email konfirmimi u dërgua.');
define('MSG_APPOINTMENT_CANCELLED', 'Takimi u anulua. Email konfirmimi u dërgua.');
define('MSG_DOCTOR_ADDED',       'Mjeku u shtua me sukses!');
define('MSG_DOCTOR_UPDATED',     'Të dhënat e mjekut u përditësuan!');
define('MSG_DOCTOR_DELETED',     'Mjeku u fshi me sukses!');
define('MSG_RX_UPLOADED',        'Receta u ngarkua me sukses!');
define('MSG_PRICE_UPDATED',      'Çmimi u përditësua me sukses!');
define('MSG_CONTACT_SENT',       'Mesazhi u dërgua me sukses!');

// ---- Mesazhet flash (gabim) ----
define('ERR_INVALID_CREDENTIALS', 'Email ose fjalëkalim i gabuar.');
define('ERR_ACCOUNT_INACTIVE',    'Llogaria juaj është çaktivizuar. Kontaktoni administratorin.');
define('ERR_EMAIL_EXISTS',        'Ky email është i regjistruar tashmë.');
define('ERR_PASSWORDS_MISMATCH',  'Fjalëkalimet nuk përputhen.');
define('ERR_INVALID_PASSWORD',    'Fjalëkalimi aktual është i gabuar.');
define('ERR_CSRF_INVALID',        'Kërkesa nuk është e vlefshme. Provoni përsëri.');
define('ERR_ACCESS_DENIED',       'Nuk keni leje për të aksesuar këtë faqe.');
define('ERR_SLOT_TAKEN',          'Ky orar është i zënë. Ju lutemi zgjidhni një orar tjetër.');
define('ERR_SLOT_UNAVAILABLE',    'Mjeku nuk është i disponueshëm në këtë datë/orar.');
define('ERR_FILE_TYPE',           'Tipi i skedarit nuk lejohet. Lejohen: PDF, JPG, PNG.');
define('ERR_FILE_SIZE',           'Skedari është shumë i madh. Madhësia maksimale: 5MB.');
define('ERR_FILE_UPLOAD',         'Gabim gjatë ngarkimit të skedarit. Provoni përsëri.');
define('ERR_REQUIRED_FIELDS',     'Ju lutemi plotësoni të gjitha fushat e detyrueshme.');
define('ERR_INVALID_DATE',        'Data e zgjedhur nuk është e vlefshme.');
define('ERR_PAST_DATE',           'Nuk mund të rezervoni takim për një datë të kaluar.');
define('ERR_GENERAL',             'Ndodhi një gabim. Ju lutemi provoni përsëri.');

// ---- Ditët e javës (shqip) ----
define('DAYS_SQ', [
    'Monday'    => 'E Hënë',
    'Tuesday'   => 'E Martë',
    'Wednesday' => 'E Mërkurë',
    'Thursday'  => 'E Enjte',
    'Friday'    => 'E Premte',
    'Saturday'  => 'E Shtunë',
    'Sunday'    => 'E Diel',
]);

// ---- Muajt (shqip) ----
define('MONTHS_SQ', [
    1  => 'Janar',   2  => 'Shkurt',  3  => 'Mars',
    4  => 'Prill',   5  => 'Maj',     6  => 'Qershor',
    7  => 'Korrik',  8  => 'Gusht',   9  => 'Shtator',
    10 => 'Tetor',   11 => 'Nëntor',  12 => 'Dhjetor',
]);

// ---- Grupet e gjakut ----
define('BLOOD_TYPES', ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']);

// ---- Specializimet e mjekëve ----
define('SPECIALIZATIONS', [
    'Kardiologji',
    'Neurologji',
    'Pediatri',
    'Ortopedi',
    'Dermatologji',
    'Oftalmologji',
    'Gjinekologji',
    'Kirurgji',
    'Endokrinologji',
    'Psikiatri',
    'Radiologji',
    'Laborator',
    'Mjekësi e Përgjithshme',
]);
