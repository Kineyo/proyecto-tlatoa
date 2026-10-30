<?php
declare(strict_types=1);

/**
 * Gateway CLI de Scrapers — Pulso Cívico / Proyecto Tlatoa
 * Punto de entrada unificado para invocar extractores de datos gubernamentales.
 */

require_once __DIR__ . '/core/ScraperRouter.php';

use PulsoCivico\Tools\ScraperRouter;

$rootDir = dirname(__DIR__);
$action  = $argv[1] ?? 'help';
$target  = $argv[2] ?? '';
$extra   = $argv[3] ?? '';

$response = [
    'action'    => $action,
    'timestamp' => date('Y-m-d H:i:s'),
];

switch ($action) {
    case 'scrape':
        if ($target === '') {
            $response['ok'] = false;
            $response['error'] = 'Debe indicar la fuente o entidad a extraer (ej: sil, cdmx, jalisco). Usa "php tools/gateway.php list" para ver opciones.';
            break;
        }
        $params = [];
        if (!empty($extra)) {
            $decoded = json_decode($extra, true);
            if (is_array($decoded)) {
                $params = $decoded;
            } else {
                // Parsear formato simple "clave1=valor1,clave2=valor2"
                $pairs = explode(',', $extra);
                foreach ($pairs as $pair) {
                    if (str_contains($pair, '=')) {
                        [$k, $v] = explode('=', $pair, 2);
                        $params[trim($k)] = trim($v);
                    }
                }
            }
        }
        $res = ScraperRouter::run($rootDir, $target, $params);
        $response = array_merge($response, $res);
        break;

    case 'list':
        $response['ok'] = true;
        $response['scrapers_disponibles'] = [
            'Federal' => [
                'sil'         => 'Sistema de Información Legislativa (Diputados y Senadores)',
                'votaciones'  => 'Votaciones nominales del pleno (SITL)',
                'asistencias' => 'Asistencias por sesión de diputados federales',
            ],
            'Congresos Estatales' => [
                'aguascalientes (ags)', 'bajacalifornia (bc)', 'bajacaliforniasur (bcs)', 'campeche (cam)',
                'chiapas (chis)', 'chihuahua', 'coahuila', 'colima', 'cdmx', 'durango', 'edomex',
                'guanajuato', 'guerrero', 'hidalgo', 'jalisco (jal)', 'michoacan', 'morelos', 'nayarit',
                'nuevoleon', 'oaxaca', 'puebla', 'queretaro', 'quintanaroo (qroo)', 'sanluispotosi (slp)',
                'sinaloa', 'sonora', 'tabasco', 'tamaulipas', 'tlaxcala', 'veracruz', 'yucatan (yuc)', 'zacatecas'
            ],
            'Motores Alternativos' => [
                'scrapers/template.lua'         => 'Plantilla ultraligera en Lua 5.4',
                'tools/core/coahuila_parser.py' => 'Parser en Python para actas de Coahuila',
                'tools/core/nl_pdf_parser.py'   => 'Extractor en Python para gacetas de Nuevo León'
            ]
        ];
        break;

    case 'help':
    default:
        $response['mensaje'] = "CLI Gateway de Scrapers";
        $response['comandos'] = [
            'scrape <fuente> [params]' => 'Ejecuta extracción oficial (ej: php tools/gateway.php scrape cdmx)',
            'list'                     => 'Lista todos los scrapers disponibles por entidad y nivel',
            'help'                     => 'Muestra esta ayuda',
        ];
        break;
}

$json = json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
echo ($json !== false ? $json : json_encode(['error' => 'json_encode_error'])) . PHP_EOL;
