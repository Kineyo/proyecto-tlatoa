<?php
declare(strict_types=1);

namespace PulsoCivico\Core;

/**
 * Cliente HTTP resiliente con reintentos exponenciales, control de tasa y auditoría de peticiones.
 */
class HttpClient
{
    private string $userAgent;
    private int $timeout;
    private int $rateLimitMs;
    private Logger $logger;
    private bool $verifySsl;

    public function __construct(array $config, Logger $logger)
    {
        $this->userAgent = $config['user_agent'] ?? 'Mozilla/5.0';
        $this->timeout = $config['timeout'] ?? 30;
        $this->rateLimitMs = $config['rate_limit_ms'] ?? 500;
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? false);
        $this->logger = $logger;
    }

    /**
     * Ejecuta una petición HTTP con reintentos automáticos.
     */
    public function request(string $method, string $url, array $params = [], array $headers = [], int $maxRetries = 5): array
    {
        $method = strtoupper($method);
        $attempt = 0;
        $backoffSeconds = 1;

        while ($attempt < $maxRetries) {
            $attempt++;
            $startTime = microtime(true);

            // Rate limiting preventivo con jitter aleatorio para simular comportamiento humano
            if ($this->rateLimitMs > 0) {
                $jitter = random_int(50, 250);
                usleep(($this->rateLimitMs + $jitter) * 1000);
            }

            $ch = curl_init();
            $defaultHeaders = [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: es-MX,es;q=0.9,en-US;q=0.8,en;q=0.7',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
            ];
            $mergedHeaders = array_merge($defaultHeaders, $headers);

            $cookieFile = dirname(__DIR__, 2) . '/storage/raw/cookie.txt';
            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_USERAGENT => $this->userAgent,
                CURLOPT_SSL_VERIFYPEER => $this->verifySsl,
                CURLOPT_SSL_VERIFYHOST => $this->verifySsl ? 2 : 0,
                CURLOPT_HTTPHEADER => $mergedHeaders,
                CURLOPT_COOKIEFILE => $cookieFile,
                CURLOPT_COOKIEJAR => $cookieFile,
            ];

            if ($method === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = http_build_query($params);
                $curlOptions[CURLOPT_REFERER] = 'http://sil.gobernacion.gob.mx/Reportes/Integracion/HCongreso/IntegHCongreso.php';
            } elseif ($method === 'GET' && !empty($params)) {
                $curlOptions[CURLOPT_URL] = $url . '?' . http_build_query($params);
                $curlOptions[CURLOPT_REFERER] = 'http://sil.gobernacion.gob.mx/';
            }

            curl_setopt_array($ch, $curlOptions);

            $body = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);
            $error = curl_error($ch);
            curl_close($ch);

            // Detección de página de bloqueo/error del WAF gubernamental (incluso si devuelve HTTP 200)
            $esErrorWaf = is_string($body) && (
                stripos($body, "This page can't be displayed") !== false ||
                stripos($body, "The incident ID is") !== false ||
                (stripos($body, "<title>Error</title>") !== false && strlen($body) < 2000)
            );

            if ($httpCode >= 200 && $httpCode < 300 && is_string($body) && !$esErrorWaf) {
                return [
                    'status' => $httpCode,
                    'duration_ms' => $durationMs,
                    'body' => $body,
                    'bytes' => strlen($body),
                ];
            }

            // Alerta especial de baneo o limitación (HTTP 429, 403 o respuesta WAF)
            if ($esErrorWaf || $httpCode === 429 || $httpCode === 403) {
                $wafCooldown = 15 + ($attempt * 10);
                $this->logger->warning("Detector anti-baneo activado: WAF gubernamental detectado. Enfriando {$wafCooldown} segundos...", [
                    'url' => $url,
                    'es_error_waf' => $esErrorWaf,
                    'http_code' => $httpCode,
                    'intento' => $attempt,
                    'cooldown_seg' => $wafCooldown,
                ]);
                sleep($wafCooldown);
            }

            $this->logger->warning("Fallo en peticion HTTP (Intento $attempt/$maxRetries)", [
                'url' => $url,
                'method' => $method,
                'http_code' => $httpCode,
                'error' => $error,
                'duration_ms' => $durationMs,
            ]);

            if ($attempt < $maxRetries) {
                sleep($backoffSeconds);
                $backoffSeconds *= 2; // Exponencial 1s, 2s, 4s...
            }
        }

        throw new \RuntimeException("Fallo HTTP permanente tras $maxRetries intentos hacia: $url");
    }

    /**
     * Descarga y guarda un archivo binario (como fotos oficiales), omitiendo si ya existe en disco.
     */
    public function downloadFile(string $url, string $destinationPath): bool
    {
        try {
            // Protección anti-baneo y aceleración: Si el archivo ya existe y es válido, omitir petición
            if (file_exists($destinationPath) && filesize($destinationPath) > 500) {
                return true;
            }

            $response = $this->request('GET', $url);
            $dir = dirname($destinationPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
            return file_put_contents($destinationPath, $response['body']) !== false;
        } catch (\Throwable $e) {
            $this->logger->warning("No se pudo descargar archivo: $url", ['error' => $e->getMessage()]);
            return false;
        }
    }
}
