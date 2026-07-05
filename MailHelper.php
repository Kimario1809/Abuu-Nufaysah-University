<?php
namespace App\Helpers;

class MailHelper {
    public static function send($to, $subject, $message, $fromAddress = null, $fromName = null) {
        $host = $_ENV['MAIL_HOST'] ?? getenv('MAIL_HOST') ?: 'smtp.gmail.com';
        $port = intval($_ENV['MAIL_PORT'] ?? getenv('MAIL_PORT') ?: 587);
        $username = $_ENV['MAIL_USERNAME'] ?? getenv('MAIL_USERNAME') ?: '';
        $password = $_ENV['MAIL_PASSWORD'] ?? getenv('MAIL_PASSWORD') ?: '';
        $encryption = strtolower($_ENV['MAIL_ENCRYPTION'] ?? getenv('MAIL_ENCRYPTION') ?: 'tls');
        $from = $fromAddress ?: ($_ENV['MAIL_FROM_ADDRESS'] ?? getenv('MAIL_FROM_ADDRESS') ?: 'noreply@example.com');
        $name = $fromName ?: ($_ENV['MAIL_FROM_NAME'] ?? getenv('MAIL_FROM_NAME') ?: 'Abuu Nufay\'sah University');

        if (empty($username) || empty($password)) {
            return ['success' => false, 'message' => 'Mail credentials are not configured.'];
        }

        $socket = @fsockopen(($encryption === 'ssl' ? 'ssl://' : '') . $host, $port, $errno, $errstr, 15);
        if (!$socket) {
            return ['success' => false, 'message' => 'Unable to connect to mail server: ' . $errstr];
        }

        try {
            $response = self::readResponse($socket);
            if (substr($response, 0, 3) !== '220') {
                throw new \Exception('SMTP error: ' . $response);
            }

            self::sendCommand($socket, 'EHLO localhost');

            if ($encryption === 'tls') {
                self::sendCommand($socket, 'STARTTLS');
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new \Exception('Unable to enable TLS.');
                }
                self::sendCommand($socket, 'EHLO localhost');
            }

            self::sendCommand($socket, 'AUTH LOGIN');
            self::sendCommand($socket, base64_encode($username));
            self::sendCommand($socket, base64_encode($password));

            self::sendCommand($socket, 'MAIL FROM:<'. $from .'>');
            self::sendCommand($socket, 'RCPT TO:<'. $to .'>');
            self::sendCommand($socket, 'DATA');

            $headers = "From: {$name} <{$from}>\r\n";
            $headers .= "Reply-To: {$from}\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Subject: {$subject}\r\n\r\n";
            $body = $message . "\r\n.\r\n";

            fwrite($socket, $headers . $body);
            self::readResponse($socket);

            self::sendCommand($socket, 'QUIT');
            fclose($socket);

            return ['success' => true];
        } catch (\Exception $e) {
            @fclose($socket);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private static function sendCommand($socket, $command) {
        fwrite($socket, $command . "\r\n");
        $response = self::readResponse($socket);

        if (in_array(substr($response, 0, 3), ['421', '454', '501', '503', '535'])) {
            throw new \Exception('SMTP error: ' . $response);
        }

        return $response;
    }

    private static function readResponse($socket) {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }
}
