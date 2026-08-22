<?php
/**
 * Thin wrapper around the vendored PHPMailer library (vendor/phpmailer),
 * configured from the existing Settings > Email values
 * (modules/settings/smtp.php writes them; nothing previously read them —
 * this is the first real consumer of that configuration).
 *
 * Loaded on demand, not via bootstrap.php, since only the password-reset
 * flow in modules/auth/login.php currently needs to send mail.
 */
require_once APP_ROOT . '/vendor/phpmailer/src/Exception.php';
require_once APP_ROOT . '/vendor/phpmailer/src/PHPMailer.php';
require_once APP_ROOT . '/vendor/phpmailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Sends an HTML email through the configured SMTP server.
 *
 * Returns true on success. Never throws — failures are written to the PHP
 * error log via error_log() (config/config.php routes that to
 * storage/php-error.log), but only the recipient address and the SMTP
 * library's own connection/auth error message are logged, never $htmlBody
 * or $textBody — so a one-time code or any other sensitive email content
 * this function is ever used to send never reaches the log file.
 */
function sendSystemEmail(PDO $pdo, string $toEmail, string $toName, string $subject, string $htmlBody, string $textBody = ''): bool
{
    $host       = getSetting($pdo, 'smtp_host', '');
    $port       = (int) getSetting($pdo, 'smtp_port', '587');
    $username   = getSetting($pdo, 'smtp_username', '');
    $password   = getSetting($pdo, 'smtp_password', '');
    $encryption = getSetting($pdo, 'smtp_encryption', 'tls');
    $fromName   = getSetting($pdo, 'university_name', APP_NAME);

    if ($host === '') {
        error_log('sendSystemEmail: SMTP host is not configured (Settings > Email). Email to ' . $toEmail . ' was not sent.');
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host     = $host;
        $mail->Port     = $port;
        $mail->SMTPAuth = $username !== '';
        if ($username !== '') {
            $mail->Username = $username;
            $mail->Password = $password;
        }
        if ($encryption === 'tls') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($encryption === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure  = false;
            $mail->SMTPAutoTLS = false;
        }
        $mail->Timeout = 15;

        $fromAddress = $username !== '' ? $username : ('no-reply@' . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = $textBody !== '' ? $textBody : strip_tags($htmlBody);

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('sendSystemEmail: failed to send to ' . $toEmail . ' — ' . $e->getMessage());
        return false;
    }
}
