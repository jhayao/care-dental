<?php
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

// Load .env if not already loaded
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

class QStashService {
    public static function schedule($url, $payload = [], $delaySeconds = 0) {
        $token = $_ENV['QSTASH_TOKEN'] ?? getenv('QSTASH_TOKEN');
        
        if (!$token) {
            error_log("QStash Token not found!");
            return false;
        }

        $headers = [
            "Authorization: Bearer $token",
            "Content-Type: application/json",
            "Upstash-Forward-Content-Type: application/json"
        ];

        if ($delaySeconds > 0) {
            $headers[] = "Upstash-Delay: {$delaySeconds}s";
        }

        $ch = curl_init("https://qstash.upstash.io/v1/publish/" . $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        } else {
            error_log("QStash Error ($httpCode): $response - $error");
            return false;
        }
    }
}
?>
