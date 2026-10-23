<?php
declare(strict_types=1);

namespace Tlatoa\Core;

/**
 * Servicio criptográfico de integridad para evidencia pública.
 * Genera huellas SHA-256 de archivos/cadenas y calcula raíces de Merkle
 * para sellado inmutable en blockchain o manifiestos auditables.
 */
class EvidenceHasher
{
    /**
     * Calcula la huella SHA-256 de un archivo físico.
     */
    public static function hashFile(string $filePath): ?string
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return null;
        }

        return hash_file('sha256', $filePath) ?: null;
    }

    /**
     * Calcula la huella SHA-256 de una cadena de texto o contenido en memoria.
     */
    public static function hashContent(string $content): string
    {
        return hash('sha256', $content);
    }

    /**
     * Calcula la Raíz de Merkle (Merkle Root) a partir de una lista de hashes SHA-256.
     * Permite sellar miles de documentos/evidencias en una sola transacción o sello inmutable.
     *
     * @param string[] $hashes Lista de hashes en hexadecimal
     */
    public static function computeMerkleRoot(array $hashes): string
    {
        if (empty($hashes)) {
            return hash('sha256', '');
        }

        // Sanitizar y asegurar formato uniforme
        $currentLayer = array_values(array_filter($hashes, static fn($h) => is_string($h) && strlen($h) === 64));

        if (empty($currentLayer)) {
            return hash('sha256', '');
        }

        while (count($currentLayer) > 1) {
            $nextLayer = [];
            $count = count($currentLayer);

            for ($i = 0; $i < $count; $i += 2) {
                $left = $currentLayer[$i];
                // Si el número de elementos es impar, se duplica el último nodo
                $right = ($i + 1 < $count) ? $currentLayer[$i + 1] : $left;
                $combined = hash('sha256', hex2bin($left) . hex2bin($right));
                $nextLayer[] = $combined;
            }

            $currentLayer = $nextLayer;
        }

        return $currentLayer[0];
    }

    /**
     * Genera un manifiesto de integridad auditable y lo guarda en storage.
     *
     * @param array<int, array{id: string|int, fuente_url: string, sha256: string, tipo?: string}> $items
     * @return array{fecha: string, total_items: int, merkle_root: string, items: array}
     */
    public static function generateManifest(array $items, string $storageDir): array
    {
        $hashes = array_column($items, 'sha256');
        $merkleRoot = self::computeMerkleRoot($hashes);
        $fecha = date('Y-m-d');

        $manifest = [
            'version' => '1.0',
            'protocol' => 'Tlatoa-ProofOfExistence',
            'fecha_manifiesto' => $fecha,
            'timestamp_utc' => gmdate('Y-m-d\TH:i:s\Z'),
            'total_evidencias' => count($items),
            'merkle_root' => $merkleRoot,
            'evidencias' => $items,
        ];

        $targetDir = rtrim($storageDir, '/\\') . '/evidence';
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0775, true);
        }

        $manifestFile = $targetDir . "/manifest_{$fecha}.json";
        file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        return $manifest;
    }
}
