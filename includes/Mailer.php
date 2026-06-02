<?php
// includes/Mailer.php
require_once __DIR__ . '/../config.php';

class Mailer {
    public static function sendPin($email, $callsign, $pin) {
        global $pdo;

        // Ambil konfigurasi SMTP dari database
        $stmt = $pdo->query("SELECT * FROM smtp_config LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        $subject = "YB DX Contest - Your Access Code (PIN)";
        $body = "Hello $callsign,\n\n";
        $body .= "Thank you for submitting your Cabrillo log to the YB DX Contest.\n";
        $body .= "Your 6-digit Access Code (PIN) is: $pin\n\n";
        $body .= "Please keep this PIN safe. You will need it to view your detailed score or to re-submit your log.\n\n";
        $body .= "73,\nYB DX Contest Committee";

        // Jika PHPMailer tersedia (misalnya pengguna menginstal melalui composer)
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            // Asumsi PHPMailer telah terinstal:
            /*
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = $config['host'];
                $mail->SMTPAuth   = true;
                $mail->Username   = $config['username'];
                $mail->Password   = $config['password'];
                $mail->SMTPSecure = $config['encryption'];
                $mail->Port       = $config['port'];
                $mail->setFrom($config['from_email'], $config['from_name']);
                $mail->addAddress($email, $callsign);
                $mail->Subject = $subject;
                $mail->Body    = $body;
                $mail->send();
                return true;
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
                return false;
            }
            */
        }
        
        // Simulasi pengiriman email untuk lingkungan pengembangan (Development)
        $mock_log = __DIR__ . '/../mail_mock.log';
        $log_entry = "--- EMAIL TO: $email ($callsign) ---\nSUBJECT: $subject\nBODY:\n$body\n\n";
        file_put_contents($mock_log, $log_entry, FILE_APPEND);
        
        return true;
    }
}
?>
