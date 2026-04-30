<?php
// ============================================================
// sanitize.php - Pastrimi dhe sigurimi i input-it (XSS)
// ============================================================

// ---- Pastro string të përgjithshëm ----
// trim + strip_tags + htmlspecialchars
function clean(string $input): string {
    $input = trim($input);
    $input = strip_tags($input);
    return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

// ---- Pastro dhe kthe integer ----
function cleanInt(mixed $input): int {
    return (int) filter_var($input, FILTER_SANITIZE_NUMBER_INT);
}

// ---- Pastro dhe kthe float ----
function cleanFloat(mixed $input): float {
    return (float) filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
}

// ---- Valido dhe pastro email ----
function cleanEmail(string $input): string|false {
    $email = trim(strtolower($input));
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : false;
}

// ---- Pastro emrin e skedarit (për upload) ----
// Heq karaktere të rrezikshme, ruan vetëm alfanumerike, dash, underscore, pikë
function cleanFilename(string $filename): string {
    // Merr ekstensionin
    $ext  = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $name = pathinfo($filename, PATHINFO_FILENAME);

    // Zëvendëso hapësirat me underscore
    $name = str_replace(' ', '_', $name);

    // Hiq çdo karakter jo-alfanumerik (përveç - dhe _)
    $name = preg_replace('/[^a-zA-Z0-9_\-]/', '', $name);

    // Limit gjatësinë
    $name = substr($name, 0, 50);

    return $name . '.' . $ext;
}

// ---- Gjenero emër unik skedari ----
function generateUniqueFilename(string $originalFilename): string {
    $ext  = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
    return uniqid('rx_', true) . '.' . $ext;
}

// ---- Apliko clean() në çdo element të një array ----
function sanitizeArray(array $data): array {
    $clean = [];
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $clean[$key] = sanitizeArray($value);
        } else {
            $clean[$key] = clean((string)$value);
        }
    }
    return $clean;
}

// ---- Valido datë ----
function isValidDate(string $date, string $format = 'Y-m-d'): bool {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// ---- Valido datë të ardhshme (për rezervime) ----
function isFutureDate(string $date): bool {
    if (!isValidDate($date)) return false;
    return strtotime($date) >= strtotime(date('Y-m-d'));
}

// ---- Valido kohë (format HH:MM) ----
function isValidTime(string $time): bool {
    return (bool) preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time);
}

// ---- Valido fjalëkalim ----
// Min 8 karaktere, 1 shkronjë e madhe, 1 numër
function isValidPassword(string $password): bool {
    return strlen($password) >= 8
        && preg_match('/[A-Z]/', $password)
        && preg_match('/[0-9]/', $password);
}

// ---- Valido numër telefoni shqiptar ----
function isValidPhone(string $phone): bool {
    // Format: +355 6X XXX XXXX ose 06X XXXXXXX
    $phone = preg_replace('/\s+/', '', $phone);
    return (bool) preg_match('/^(\+3556[0-9]{8}|06[0-9]{8})$/', $phone);
}

// ---- Kontrollo tipin e skedarit (MIME) ----
function isAllowedFileType(string $tmpPath, string $filename): bool {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($ext, ALLOWED_EXTENSIONS)) return false;

    // Kontrollo MIME type reale (jo vetëm ekstensionin)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);

    return in_array($mimeType, ALLOWED_MIME_TYPES);
}

// ---- Kontrollo madhësinë e skedarit ----
function isAllowedFileSize(int $fileSize): bool {
    return $fileSize <= MAX_FILE_SIZE;
}

// ---- Shfaq vlerën e pastër për HTML (për template) ----
function e(string $value): string {
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}
