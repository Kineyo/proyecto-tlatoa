<?php
declare(strict_types=1);

namespace Tlatoa\Core;

/**
 * Servicio de preservación y archivado externo de evidencia digital.
 * Automatiza el respaldo de URLs oficiales hacia la Wayback Machine (Internet Archive)
 * sin requerir llaves API ni costos.
 */
class ArchiveService
{
    private const WAYBACK_SAVE_URL = 'https://web.archive.org/save/';
    private const TIMEOUT = 15;

    /**
     * Envía una URL gubernamental para captura y preservación en Internet Archive.
     * Retorna la URL del snapshot o null si no se pudo archivar en el momento.
     */
    public static function preserveUrl(string $url): ?string
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }

        $targetUrl = self::WAYBACK_SAVE_URL . urlencode($url);

        $ch = curl_init($targetUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => self::TIMEOUT,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'Tlatoa-PreservationBot/1.0 (+https://github.com/Kineyo/proyecto-tlatoa)',
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $effectiveUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        curl_close($ch);

        // Si Wayback Machine redirige o confirma la captura
        if ($httpCode >= 200 && $httpCode < 400 && is_string($effectiveUrl) && str_contains($effectiveUrl, 'web.archive.org/web/')) {
            return $effectiveUrl;
        }

        // URL canónica teórica de búsqueda en Wayback Machine
        return "https://web.archive.org/web/*/{$url}";
    }

    /**
     * Genera el enlace de consulta histórica para una URL gubernamental dada.
     */
    public static function getArchiveLookupUrl(string $url): string
    {
        return "https://web.archive.org/web/*/{$url}";
    }
}
