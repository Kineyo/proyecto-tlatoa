-- ========================================================================
-- PROYECTO TLATOA — MIGRACIÓN 002: ASISTENCIAS, VOTACIONES NOMINALES Y KPIs
-- Motor: MySQL 8.0 / MariaDB 10.11+
-- Ámbito: Cámara de Diputados y Cámara de Senadores (Congreso de la Unión)
-- ========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------
-- 1. SESIONES DEL PLENO (Diputados y Senadores)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `sesiones_pleno` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `legislatura_id` SMALLINT UNSIGNED NOT NULL,
    `camara` ENUM('DIPUTADOS', 'SENADORES') NOT NULL,
    `ano_ejercicio` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `periodo` ENUM('PRIMER_ORDINARIO', 'SEGUNDO_ORDINARIO', 'EXTRAORDINARIO', 'RECESO_PERMANENTE') NOT NULL,
    `numero_sesion` SMALLINT UNSIGNED NOT NULL,
    `tipo_sesion` ENUM('ORDINARIA', 'EXTRAORDINARIA', 'SOLEMNE', 'CONSTITUTIVA', 'PREVIA') NOT NULL DEFAULT 'ORDINARIA',
    `fecha` DATE NOT NULL,
    `hora_inicio` TIME NULL,
    `hora_cierre` TIME NULL,
    `quorum_inicial` SMALLINT UNSIGNED NULL,
    `referencia_origen` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_sesion_camara_num` (`legislatura_id`, `camara`, `ano_ejercicio`, `periodo`, `numero_sesion`),
    INDEX `idx_sesiones_fecha` (`fecha`),
    INDEX `idx_sesiones_camara_leg` (`legislatura_id`, `camara`),
    CONSTRAINT `fk_sesiones_legislatura` FOREIGN KEY (`legislatura_id`) REFERENCES `legislaturas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 2. ASISTENCIAS POR SESIÓN (Pase de lista individual)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `asistencias_sesion` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sesion_id` INT UNSIGNED NOT NULL,
    `mandato_id` INT UNSIGNED NOT NULL,
    `estatus` ENUM('ASISTENCIA', 'JUSTIFICADA', 'INASISTENCIA', 'COMISION_OFICIAL', 'CEDULA_PERMISO') NOT NULL DEFAULT 'ASISTENCIA',
    `nota_oficial` VARCHAR(255) NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_asistencia_sesion_mandato` (`sesion_id`, `mandato_id`),
    INDEX `idx_asistencias_mandato` (`mandato_id`),
    INDEX `idx_asistencias_estatus` (`estatus`),
    CONSTRAINT `fk_asistencias_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones_pleno` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_asistencias_mandato` FOREIGN KEY (`mandato_id`) REFERENCES `mandatos_legislativos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 3. VOTACIONES NOMINALES DEL PLENO
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `votaciones_pleno` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sesion_id` INT UNSIGNED NOT NULL,
    `numero_votacion` SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    `tipo_votacion` ENUM('DICTAMEN_GENERAL', 'DICTAMEN_PARTICULAR', 'PUNTO_ACUERDO', 'ELECCION_MESA', 'OTRO') NOT NULL DEFAULT 'DICTAMEN_GENERAL',
    `titulo_asunto` TEXT NOT NULL,
    `tema_clave` VARCHAR(100) NULL, -- Ej: 'Reforma Judicial', 'Presupuesto 2025', 'Guardia Nacional'
    `resultado` ENUM('APROBADO', 'DESECHADO', 'EMPATE', 'RETIRADO') NOT NULL DEFAULT 'APROBADO',
    `total_favor` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `total_contra` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `total_abstencion` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `total_quorum_presentes` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `referencia_gaceta` VARCHAR(255) NULL,
    `fecha_hora` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_votaciones_sesion` (`sesion_id`),
    INDEX `idx_votaciones_tema` (`tema_clave`),
    INDEX `idx_votaciones_resultado` (`resultado`),
    CONSTRAINT `fk_votaciones_sesion` FOREIGN KEY (`sesion_id`) REFERENCES `sesiones_pleno` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 4. VOTOS INDIVIDUALES (Sentido del voto por legislador)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `votos_individuales` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `votacion_id` INT UNSIGNED NOT NULL,
    `mandato_id` INT UNSIGNED NOT NULL,
    `sentido` ENUM('A_FAVOR', 'EN_CONTRA', 'ABSTENCION', 'NO_VOTO', 'AUSENTE') NOT NULL,
    `es_rebelion_partido` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_voto_votacion_mandato` (`votacion_id`, `mandato_id`),
    INDEX `idx_votos_mandato` (`mandato_id`),
    INDEX `idx_votos_sentido` (`sentido`),
    INDEX `idx_votos_rebelion` (`es_rebelion_partido`),
    CONSTRAINT `fk_votos_votacion` FOREIGN KEY (`votacion_id`) REFERENCES `votaciones_pleno` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_votos_mandato` FOREIGN KEY (`mandato_id`) REFERENCES `mandatos_legislativos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 5. MATERIALIZACIÓN DE KPIs EN TABLA POLÍTICOS Y MANDATOS (Zero-Compute en Frontend)
-- ------------------------------------------------------------------------
-- Columnas analíticas añadidas de forma segura si no existen
SET @dbname = DATABASE();

-- Métricas en tabla politicos
SET @pre_check_asistencias = (
    SELECT COUNT(*) FROM information_schema.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = 'politicos' AND COLUMN_NAME = 'asistencia_sesiones_total'
);

-- Si no existen las columnas de KPI, agregarlas de forma idempotente
DROP PROCEDURE IF EXISTS `sp_agregar_kpis_politicos`;
DELIMITER //
CREATE PROCEDURE `sp_agregar_kpis_politicos`()
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'politicos' AND COLUMN_NAME = 'asistencia_sesiones_total') THEN
        ALTER TABLE `politicos`
            ADD COLUMN `asistencia_sesiones_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `asistencia_historica_pct`,
            ADD COLUMN `asistencia_faltas_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `asistencia_sesiones_total`,
            ADD COLUMN `asistencia_justificadas_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `asistencia_faltas_total`,
            ADD COLUMN `disciplina_partidaria_pct` DECIMAL(5,2) NULL AFTER `asistencia_justificadas_total`,
            ADD COLUMN `votos_totales_emitidos` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `disciplina_partidaria_pct`,
            ADD COLUMN `votos_rebeliones_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER `votos_totales_emitidos`;
    END IF;

    IF NOT EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'mandatos_legislativos' AND COLUMN_NAME = 'asistencia_pct') THEN
        ALTER TABLE `mandatos_legislativos`
            ADD COLUMN `asistencia_pct` DECIMAL(5,2) NULL AFTER `estatus`,
            ADD COLUMN `disciplina_partidaria_pct` DECIMAL(5,2) NULL AFTER `asistencia_pct`;
    END IF;
END //
DELIMITER ;

CALL `sp_agregar_kpis_politicos`();
DROP PROCEDURE IF EXISTS `sp_agregar_kpis_politicos`;

SET FOREIGN_KEY_CHECKS = 1;
