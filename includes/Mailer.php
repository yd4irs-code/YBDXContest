<?php
// includes/Mailer.php
require_once __DIR__ . '/../config.php';

class Mailer {
    public static function sendPin($email, $callsign, $pin) {
        global $pdo;

        // Tarik settingan SMTP dari database cuy
        $stmt = $pdo->query("SELECT * FROM smtp_config LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);

        $subject = "YB DX Contest - Your Access Code (PIN)";
        $body = "Hello $callsign,\n\n";
        $body .= "Thank you for submitting your Cabrillo log to the YB DX Contest.\n";
        $body .= "Your 6-digit Access Code (PIN) is: $pin\n\n";
        $body .= "Please keep this PIN safe. You will need it to view your detailed score or to re-submit your log.\n\n";
        $body .= "73,\nYB DX Contest Committee";

        // Kalo library PHPMailer-nya ada (misal si user udah install via composer)
        if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
            require_once __DIR__ . '/../vendor/autoload.php';
            // Anggep aja PHPMailer udah kepasang:
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
        
        // Bohong-bohongan ngirim email dulu buat di lokal (development)
        $mock_log = __DIR__ . '/../mail_mock.log';
        $log_entry = "--- EMAIL TO: $email ($callsign) ---\nSUBJECT: $subject\nBODY:\n$body\n\n";
        file_put_contents($mock_log, $log_entry, FILE_APPEND);
        
        return true;
    }
}
?>
