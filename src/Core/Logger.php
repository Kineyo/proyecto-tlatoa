<?php
declare(strict_types=1);

namespace PulsoCivico\Core;

/**
 * Logger estructurado auditable estilo Google Cloud Logging.
 * Registra eventos en formato JSONL y muestra resumen en consola.
 */
class Logger
{
    private string $logFile;
    private string $component;

    public function __construct(string $logDir, string $component = 'ETL')
    {
        if (!is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }
        $this->logFile = rtrim($logDir, '/\\') . '/ingest_' . date('Y-m-d') . '.log';
        $this->component = $component;
    }

    public function info(string $message, array $context = []): void
    {
        $this->log('INFO', $message, $context);
    }

    public function warning(string $message, array $context = []): void
    {
        $this->log('WARNING', $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->log('ERROR', $message, $context);
    }

    public function success(string $message, array $context = []): void
    {
        $this->log('SUCCESS', $message, $context);
    }

    private function log(string $severity, string $message, array $context): void
    {
        $timestamp = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $record = [
            'timestamp' => $timestamp,
            'severity' => $severity,
            'component' => $this->component,
            'message' => $message,
            'context' => $context,
        ];

        // Guardar JSONLine auditable
        file_put_contents($this->logFile, json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);

        // Salida limpia por terminal
        $tag = match ($severity) {
            'INFO' => '[INFO]',
            'SUCCESS' => '[OK]',
            'WARNING' => '[WARN]',
            'ERROR' => '[ERR]',
            default => "[$severity]",
        };
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        echo sprintf("%s %s %s%s\n", date('H:i:s'), $tag, $message, $contextStr);
    }
}
