<?php

class QStashService {
    private static $qstashUrl = 'http://127.0.0.1:8080'; 
    private static $token = null;

    public static function getToken() {
        if (self::$token) return self::$token;
        // Try ENV, then fallback to dev token
        return getenv('QSTASH_TOKEN') ?: ($_ENV['QSTASH_TOKEN'] ?? 'eyJVc2VySUQiOiJkZWZhdWx0VXNlciIsIlBhc3N3b3JkIjoiZGVmYXVsdFBhc3N3b3JkIn0=');
    }

    public static function setUrl($url) {
        self::$qstashUrl = $url;
    }

    public static function setToken($token) {
        self::$token = $token;
    }

    /**
     * Schedule a message
     * 
     * @param string $destinationUrl The URL QStash should hit
     * @param array $payload JSON serializable data
     * @param int $delaySeconds Delay in seconds (0 for immediate)
     * @return mixed Response from QStash
     */
    public static function schedule($destinationUrl, $payload, $delaySeconds = 0) {
        $url = self::$qstashUrl . '/v2/publish/' . $destinationUrl;
        
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::getToken()
        ];

        if ($delaySeconds > 0) {
            $headers[] = 'Upstash-Delay: ' . $delaySeconds . 's';
        }

        $headers[] = 'Upstash-Retries: 3'; // Retry 3 times on failure

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            error_log("QStash Error: HTTP $httpCode - $response");
            return false;
        }
    }
}
?>
