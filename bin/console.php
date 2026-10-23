<?php
declare(strict_types=1);

// Autoload simple y nativo PSR-4 para evitar dependencias de composer en esta etapa
spl_autoload_register(function (string $class): void {
    $prefix = 'Tlatoa\\';
    $baseDir = dirname(__DIR__) . '/src/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

use Tlatoa\Core\Logger;
use Tlatoa\Core\HttpClient;
use Tlatoa\Services\SilIngestService;

$config = require dirname(__DIR__) . '/config/config.php';

// Argumentos CLI
$options = getopt('', [
    'entidad::',     // Ej: --entidad=2 (Baja California)
    'nacional',      // Ingestar las 32 entidades completas de México
    'legislatura::', // Ej: --legislatura=66
    'no-photos',     // Omitir descarga de fotos
    'help',
]);

if (isset($options['help'])) {
    echo "========================================================\n";
    echo " Proyecto Tlatoa - CLI Ingest Engine\n";
    echo "========================================================\n";
    echo "Uso:\n";
    echo "  php bin/console.php [opciones]\n\n";
    echo "Opciones:\n";
    echo "  --nacional          Descarga todas las 32 entidades federativas (628 legisladores)\n";
    echo "  --entidad=ID        ID de entidad federativa (2 = Baja California por defecto)\n";
    echo "  --legislatura=NUM   Numero de legislatura (66 por defecto)\n";
    echo "  --no-photos         No descargar fotografias oficiales\n";
    echo "  --help              Muestra esta ayuda\n";
    exit(0);
}

$esNacional = isset($options['nacional']) || (isset($options['entidad']) && strtolower((string)$options['entidad']) === 'all');
$entidadId = isset($options['entidad']) && is_numeric($options['entidad']) ? (int)$options['entidad'] : 2;
$legislatura = isset($options['legislatura']) ? (int)$options['legislatura'] : 66; // 66 = LXVI
$descargarFotos = !isset($options['no-photos']);

$logger = new Logger($config['paths']['logs'], 'CLI-INGEST');

echo "========================================================\n";
echo " PROYECTO TLATOA — MOTOR DE INGESTA GUBERNAMENTAL\n";
echo " Version: {$config['app']['version']} | Entorno: {$config['app']['env']}\n";
echo " Modo: " . ($esNacional ? "NACIONAL (32 Entidades)" : "Entidad ID {$entidadId}") . "\n";
echo "========================================================\n";
$logger->info("Iniciando tarea de ingesta", [
    'alcance' => $esNacional ? 'nacional' : 'entidad',
    'entidad_id' => $esNacional ? 'todas' : $entidadId,
    'legislatura' => $legislatura,
    'descargar_fotos' => $descargarFotos,
]);

$httpClient = new HttpClient($config['sil'], $logger);
$service = new SilIngestService($config, $httpClient, $logger);

$inicio = microtime(true);
if ($esNacional) {
    $resultados = $service->ingestNacional($legislatura, $descargarFotos);
    $duracionSeg = round(microtime(true) - $inicio, 2);

    echo "\n========================================================\n";
    echo " RESUMEN NACIONAL AUDITABLE\n";
    echo "========================================================\n";
    echo " Tiempo total: {$duracionSeg} segundos\n";
    echo " Entidades procesadas: {$resultados['entidades_procesadas']}/32\n";
    echo " Total Diputados:      {$resultados['total_diputados']}\n";
    echo " Total Senadores:      {$resultados['total_senadores']}\n";
    echo " Total Legisladores:   {$resultados['total_general']}\n";
    if (!empty($resultados['errores'])) {
        echo " Errores encontrados: " . count($resultados['errores']) . "\n";
    } else {
        echo " Estado: OK (Cero errores)\n";
    }
    echo "========================================================\n";
} else {
    $resultados = $service->ingestByEntidad($entidadId, $legislatura, $descargarFotos);
    $duracionSeg = round(microtime(true) - $inicio, 2);

    echo "\n========================================================\n";
    echo " RESUMEN DE EJECUCION AUDITABLE\n";
    echo "========================================================\n";
    echo " Tiempo total: {$duracionSeg} segundos\n";
    echo " Diputados extraidos: " . count($resultados['diputados']) . "\n";
    echo " Senadores extraidos: " . count($resultados['senadores']) . "\n";
    echo " Total legisladores:  {$resultados['total']}\n";
    if (!empty($resultados['errores'])) {
        echo " Errores encontrados: " . implode(', ', $resultados['errores']) . "\n";
    } else {
        echo " Estado: OK (Cero errores)\n";
    }
    echo "========================================================\n";
}
