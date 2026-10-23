<?php
declare(strict_types=1);

namespace Tlatoa\Tools;

class Linter
{
    /**
     * Revisa la sintaxis de uno o varios archivos PHP.
     * @param string[] $files
     * @return array{ok: bool, checked: int, errors: array<string, string>}
     */
    public static function checkFiles(array $files): array
    {
        $errors = [];
        $checked = 0;

        foreach ($files as $file) {
            $file = trim($file);
            if ($file === '' || !file_exists($file)) {
                continue;
            }

            // Validar solo archivos PHP
            if (pathinfo($file, PATHINFO_EXTENSION) !== 'php') {
                continue;
            }

            $checked++;
            $escaped = escapeshellarg($file);
            $phpBinary = escapeshellarg(PHP_BINARY);
            $output = [];
            $returnCode = 0;
            exec("{$phpBinary} -l {$escaped} 2>&1", $output, $returnCode);

            if ($returnCode !== 0) {
                $errors[$file] = implode("\n", $output);
            }
        }

        return [
            'ok' => empty($errors),
            'checked' => $checked,
            'errors' => $errors,
        ];
    }

    /**
     * Escanea recursivamente un directorio y valida todos los archivos PHP.
     */
    public static function checkDirectory(string $dir): array
    {
        if (!is_dir($dir)) {
            return ['ok' => false, 'checked' => 0, 'errors' => [$dir => 'El directorio no existe']];
        }

        $files = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $item) {
            if ($item->isFile() && $item->getExtension() === 'php') {
                $files[] = $item->getPathname();
            }
        }

        return self::checkFiles($files);
    }
}
