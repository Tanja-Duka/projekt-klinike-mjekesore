<?php
// ============================================================
// email.php - Dërgimi i emaileve me PHPMailer
// ============================================================

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once BASE_PATH . '/vendor/autoload.php';

// ---- Konfiguro PHPMailer ----
function createMailer(): PHPMailer {
    $mail = new PHPMailer(true);

    $mail->isSMTP();
    $mail->Host       = MAIL_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = MAIL_USER;
    $mail->Password   = MAIL_PASS;
    $mail->SMTPSecure = MAIL_ENCRYPTION;
    $mail->Port       = MAIL_PORT;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);

    return $mail;
}

// ---- Email mirëseardhjeje pas regjistrimit ----
function sendWelcomeEmail(string $to, string $name): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Mirë se erdhe në ' . APP_NAME . '!';
        $mail->Body    = emailTemplate('welcome', [
            'name'     => $name,
            'base_url' => BASE_URL,
            'app_name' => APP_NAME,
        ]);
        $mail->AltBody = "Përshëndetje $name,\nMirë se erdhe në " . APP_NAME . "!\nRegjistrim i suksesshëm.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email error (welcome): ' . $e->getMessage());
        return false;
    }
}

// ---- Email konfirmimi rezervimi ----
function sendConfirmationEmail(string $to, string $name, array $appointment): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Konfirmim Rezervimi — ' . APP_NAME;
        $mail->Body    = emailTemplate('confirmation', [
            'name'        => $name,
            'doctor_name' => $appointment['doctor_name'],
            'service'     => $appointment['service_name'],
            'date'        => formatDateSq($appointment['appointment_date']),
            'time'        => $appointment['time_slot'],
            'base_url'    => BASE_URL,
            'app_name'    => APP_NAME,
        ]);
        $mail->AltBody = "Takimi juaj u konfirmua.\nMjeku: {$appointment['doctor_name']}\nData: {$appointment['appointment_date']} ora {$appointment['time_slot']}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email error (confirmation): ' . $e->getMessage());
        return false;
    }
}

// ---- Email konfirmimi anulimi ----
function sendCancellationEmail(string $to, string $name, array $appointment): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Anulim Rezervimi — ' . APP_NAME;
        $mail->Body    = emailTemplate('cancellation', [
            'name'        => $name,
            'doctor_name' => $appointment['doctor_name'],
            'service'     => $appointment['service_name'],
            'date'        => formatDateSq($appointment['appointment_date']),
            'time'        => $appointment['time_slot'],
            'base_url'    => BASE_URL,
            'app_name'    => APP_NAME,
        ]);
        $mail->AltBody = "Takimi juaj u anulua.\nMjeku: {$appointment['doctor_name']}\nData: {$appointment['appointment_date']} ora {$appointment['time_slot']}";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email error (cancellation): ' . $e->getMessage());
        return false;
    }
}

// ---- Email njoftimi për recetë të gatshme ----
function sendPrescriptionEmail(string $to, string $name, array $appointment): bool {
    try {
        $mail = createMailer();
        $mail->addAddress($to, $name);
        $mail->isHTML(true);
        $mail->Subject = 'Receta juaj është gati — ' . APP_NAME;
        $mail->Body    = emailTemplate('prescription', [
            'name'        => $name,
            'doctor_name' => $appointment['doctor_name'],
            'date'        => formatDateSq($appointment['appointment_date']),
            'rx_url'      => BASE_URL . '/patient/prescriptions.php',
            'base_url'    => BASE_URL,
            'app_name'    => APP_NAME,
        ]);
        $mail->AltBody = "Receta juaj nga {$appointment['doctor_name']} është gati. Hyni në llogarinë tuaj për ta shkarkuar.";
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Email error (prescription): ' . $e->getMessage());
        return false;
    }
}

// ---- Template HTML për emailet ----
function emailTemplate(string $type, array $data): string {
    $appName = e($data['app_name']);
    $name    = e($data['name']);
    $baseUrl = $data['base_url'];

    // Stilet e përbashkëta
    $style = '
        font-family: Arial, sans-serif;
        max-width: 600px;
        margin: 0 auto;
        background: #ffffff;
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #e2e8f0;
    ';
    $headerStyle = '
        background: #0A5C7A;
        color: white;
        padding: 30px 40px;
        text-align: center;
    ';
    $bodyStyle  = 'padding: 30px 40px;';
    $footerStyle = '
        background: #f7fafc;
        padding: 20px 40px;
        text-align: center;
        font-size: 12px;
        color: #718096;
        border-top: 1px solid #e2e8f0;
    ';

    switch ($type) {

        case 'welcome':
            $content = "
                <h2 style='color:#0A5C7A;margin-top:0;'>Mirë se erdhe, $name!</h2>
                <p style='color:#4a5568;line-height:1.6;'>Llogaria juaj në <strong>$appName</strong> u krijua me sukses.</p>
                <p style='color:#4a5568;line-height:1.6;'>Tani mund të:</p>
                <ul style='color:#4a5568;line-height:1.8;'>
                    <li>Rezervoni takime me mjekët tanë specialistë</li>
                    <li>Shikoni historikun e vizitave tuaja</li>
                    <li>Shkarkoni recetat dixhitale</li>
                </ul>
                <div style='text-align:center;margin-top:30px;'>
                    <a href='{$baseUrl}/public/login.php'
                       style='background:#0A5C7A;color:white;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:bold;'>
                        Hyr në Llogarinë Tënde
                    </a>
                </div>
            ";
            break;

        case 'confirmation':
            $doctor  = e($data['doctor_name']);
            $service = e($data['service']);
            $date    = e($data['date']);
            $time    = e($data['time']);
            $content = "
                <h2 style='color:#0A5C7A;margin-top:0;'>Takimi u Konfirmua ✓</h2>
                <p style='color:#4a5568;'>Përshëndetje <strong>$name</strong>,</p>
                <p style='color:#4a5568;line-height:1.6;'>Rezervimi juaj u krye me sukses. Detajet e takimit:</p>
                <div style='background:#f0f9ff;border-left:4px solid #0A5C7A;padding:20px;border-radius:4px;margin:20px 0;'>
                    <p style='margin:5px 0;color:#2d3748;'><strong>👨‍⚕️ Mjeku:</strong> $doctor</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>⚕️ Shërbimi:</strong> $service</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>📅 Data:</strong> $date</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>🕐 Ora:</strong> $time</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>💳 Pagesa:</strong> Cash në klinikë</p>
                </div>
                <p style='color:#718096;font-size:14px;'>Nëse dëshironi të anuloni takimin, hyni në llogarinë tuaj.</p>
                <div style='text-align:center;margin-top:20px;'>
                    <a href='{$baseUrl}/patient/appointments.php'
                       style='background:#0A5C7A;color:white;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:bold;'>
                        Shiko Takimet e Mia
                    </a>
                </div>
            ";
            break;

        case 'cancellation':
            $doctor  = e($data['doctor_name']);
            $service = e($data['service']);
            $date    = e($data['date']);
            $time    = e($data['time']);
            $content = "
                <h2 style='color:#c53030;margin-top:0;'>Takimi u Anulua</h2>
                <p style='color:#4a5568;'>Përshëndetje <strong>$name</strong>,</p>
                <p style='color:#4a5568;line-height:1.6;'>Takimi juaj i mëposhtëm u anulua:</p>
                <div style='background:#fff5f5;border-left:4px solid #c53030;padding:20px;border-radius:4px;margin:20px 0;'>
                    <p style='margin:5px 0;color:#2d3748;'><strong>👨‍⚕️ Mjeku:</strong> $doctor</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>⚕️ Shërbimi:</strong> $service</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>📅 Data:</strong> $date</p>
                    <p style='margin:5px 0;color:#2d3748;'><strong>🕐 Ora:</strong> $time</p>
                </div>
                <p style='color:#4a5568;'>Mund të rezervoni një takim të ri në çdo kohë.</p>
                <div style='text-align:center;margin-top:20px;'>
                    <a href='{$baseUrl}/patient/reserve.php'
                       style='background:#0A5C7A;color:white;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:bold;'>
                        Rezervo Takim të Ri
                    </a>
                </div>
            ";
            break;

        case 'prescription':
            $doctor = e($data['doctor_name']);
            $date   = e($data['date']);
            $rxUrl  = $data['rx_url'];
            $content = "
                <h2 style='color:#0A5C7A;margin-top:0;'>Receta juaj është gati 💊</h2>
                <p style='color:#4a5568;'>Përshëndetje <strong>$name</strong>,</p>
                <p style='color:#4a5568;line-height:1.6;'>
                    Mjeku <strong>$doctor</strong> nga vizita e datës <strong>$date</strong>
                    ka ngarkuar recetën tuaj dixhitale.
                </p>
                <div style='text-align:center;margin-top:30px;'>
                    <a href='$rxUrl'
                       style='background:#E8A020;color:white;padding:12px 30px;border-radius:25px;text-decoration:none;font-weight:bold;'>
                        📥 Shkarko Recetën
                    </a>
                </div>
            ";
            break;

        default:
            $content = "<p>Mesazh nga $appName.</p>";
    }

    return "
        <div style='$style'>
            <div style='$headerStyle'>
                <h1 style='margin:0;font-size:22px;'>🏥 $appName</h1>
            </div>
            <div style='$bodyStyle'>$content</div>
            <div style='$footerStyle'>
                © " . date('Y') . " $appName · Tirana Medical Center<br>
                <a href='$baseUrl' style='color:#0A5C7A;'>$baseUrl</a>
            </div>
        </div>
    ";
}
