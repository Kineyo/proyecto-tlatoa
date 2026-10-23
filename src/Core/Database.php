<?php
declare(strict_types=1);

namespace Tlatoa\Core;

use PDO;
use PDOException;

/**
 * Conector de Base de Datos Agnóstico y Portátil.
 * Soporta SQLite (cero configuración: archivo local) o MySQL/MariaDB (vía .env).
 * Provee inicialización automática del esquema para colaboradores locales.
 */
class Database
{
    private static ?PDO $connection = null;

    /**
     * Obtiene la instancia activa de conexión PDO (Singleton).
     */
    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $driver = strtolower((string)(getenv('DB_DRIVER') ?: 'sqlite'));

        if ($driver === 'sqlite') {
            $dbPath = (string)(getenv('DB_SQLITE_PATH') ?: dirname(__DIR__, 2) . '/storage/database.sqlite');
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }

            $isNew = !file_exists($dbPath);
            self::$connection = new PDO("sqlite:{$dbPath}", null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);

            // Habilitar claves foráneas y WAL mode para concurrencia en SQLite
            self::$connection->exec('PRAGMA foreign_keys = ON;');
            self::$connection->exec('PRAGMA journal_mode = WAL;');

            if ($isNew) {
                self::initSqliteSchema(self::$connection);
            }
        } else {
            $host = (string)(getenv('DB_HOST') ?: '127.0.0.1');
            $port = (string)(getenv('DB_PORT') ?: '3306');
            $database = (string)(getenv('DB_DATABASE') ?: 'tlatoa');
            $username = (string)(getenv('DB_USERNAME') ?: 'root');
            $password = (string)(getenv('DB_PASSWORD') ?: '');

            $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";
            self::$connection = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }

    /**
     * Inicializa las tablas básicas con columnas de integridad criptográfica para SQLite.
     */
    private static function initSqliteSchema(PDO $pdo): void
    {
        $sql = <<<SQL
        CREATE TABLE IF NOT EXISTS politicos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            slug TEXT UNIQUE NOT NULL,
            nombre_completo TEXT NOT NULL,
            partido TEXT NOT NULL,
            camara TEXT NOT NULL,
            entidad TEXT NOT NULL,
            distrito TEXT,
            referencia_sil TEXT,
            foto_local_path TEXT,
            sha256_hash TEXT,
            source_url TEXT,
            archive_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS votaciones (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            id_votacion_origen INTEGER NOT NULL,
            camara TEXT NOT NULL,
            legislatura INTEGER NOT NULL,
            fecha TEXT NOT NULL,
            titulo TEXT NOT NULL,
            tema_clave TEXT,
            sha256_hash TEXT,
            source_url TEXT,
            archive_url TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS evidencia_manifiestos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            fecha TEXT NOT NULL,
            merkle_root TEXT NOT NULL,
            total_evidencias INTEGER NOT NULL,
            blockchain_tx TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_politicos_slug ON politicos(slug);
        CREATE INDEX IF NOT EXISTS idx_politicos_sil ON politicos(referencia_sil);
        SQL;

        $pdo->exec($sql);
    }
}
