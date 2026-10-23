-- ========================================================================
-- PROYECTO TLATOA — ESQUEMA RELACIONAL Y GRAFO DE CONOCIMIENTO LEGISLATIVO
-- Motor: MySQL 8.0 / MariaDB 10.11+
-- Collation: utf8mb4_unicode_ci
-- ========================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ------------------------------------------------------------------------
-- 1. CATÁLOGO: ESTADOS / ENTIDADES FEDERATIVAS (32 estados)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `estados` (
    `id` TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `nombre` VARCHAR(60) NOT NULL,
    `slug` VARCHAR(60) NOT NULL UNIQUE,
    `abreviatura` VARCHAR(10) NOT NULL,
    `diputados_mr_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 2. CATÁLOGO: PARTIDOS POLÍTICOS
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `partidos` (
    `id` SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `sigla` VARCHAR(20) NOT NULL UNIQUE,
    `nombre_completo` VARCHAR(120) NOT NULL,
    `slug` VARCHAR(40) NOT NULL UNIQUE,
    `color_hex` VARCHAR(7) NOT NULL DEFAULT '#718096',
    `logo_path` VARCHAR(255) NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 3. CATÁLOGO: LEGISLATURAS
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `legislaturas` (
    `id` SMALLINT UNSIGNED NOT NULL, -- Ej: 66
    `numero_romano` VARCHAR(10) NOT NULL, -- LXVI
    `slug` VARCHAR(30) NOT NULL UNIQUE, -- lxvi-2024-2027
    `ano_inicio` SMALLINT UNSIGNED NOT NULL,
    `ano_fin` SMALLINT UNSIGNED NOT NULL,
    `es_actual` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 4. NODO CENTRAL: POLÍTICOS (Persona Invariante a lo largo del tiempo)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `politicos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `slug` VARCHAR(150) NOT NULL UNIQUE,
    `nombre_completo` VARCHAR(200) NOT NULL,
    `nombres` VARCHAR(100) NULL,
    `apellido_paterno` VARCHAR(80) NULL,
    `apellido_materno` VARCHAR(80) NULL,
    `fecha_nacimiento` DATE NULL,
    `genero` ENUM('M', 'F', 'OTRO') NULL,
    `escolaridad_maxima` VARCHAR(100) NULL,
    `preparacion_academica` TEXT NULL,
    `foto_oficial_url` VARCHAR(255) NULL,
    `foto_local_path` VARCHAR(255) NULL,
    `asistencia_historica_pct` DECIMAL(5,2) NULL,
    `leyes_aprobadas_total` SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_politicos_nombre` (`nombre_completo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 5. MANDATOS LEGISLATIVOS (Cargo Temporal / Propietario vs Suplente)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `mandatos_legislativos` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `politico_id` INT UNSIGNED NOT NULL,
    `legislatura_id` SMALLINT UNSIGNED NOT NULL,
    `camara` ENUM('DIPUTADOS', 'SENADORES') NOT NULL,
    `partido_id` SMALLINT UNSIGNED NOT NULL,
    `estado_id` TINYINT UNSIGNED NOT NULL,
    `distrito_federal` SMALLINT UNSIGNED NULL,
    `principio_eleccion` ENUM('MAYORIA_RELATIVA', 'REPRESENTACION_PROPORCIONAL', 'PRIMERA_MINORIA') NOT NULL,
    `tipo_cargo` ENUM('PROPIETARIO', 'SUPLENTE') NOT NULL DEFAULT 'PROPIETARIO',
    `titular_mandato_id` INT UNSIGNED NULL, -- Si es suplente, apunta al mandato titular
    `estatus` ENUM('EN_FUNCIONES', 'CON_LICENCIA', 'BAJA', 'SUPLENTE_EN_ESPERA') NOT NULL DEFAULT 'EN_FUNCIONES',
    `referencia_sil` VARCHAR(20) NULL,
    `correo_institucional` VARCHAR(120) NULL,
    `telefono_oficina` VARCHAR(80) NULL,
    `ubicacion_oficina` VARCHAR(150) NULL,
    `fecha_inicio` DATE NULL,
    `fecha_fin` DATE NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    INDEX `idx_mandatos_politico` (`politico_id`),
    INDEX `idx_mandatos_leg_camara` (`legislatura_id`, `camara`),
    INDEX `idx_mandatos_estado` (`estado_id`),
    INDEX `idx_mandatos_partido` (`partido_id`),
    INDEX `idx_mandatos_titular` (`titular_mandato_id`),
    INDEX `idx_mandatos_referencia_sil` (`referencia_sil`),
    CONSTRAINT `fk_mandatos_politico` FOREIGN KEY (`politico_id`) REFERENCES `politicos` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_mandatos_legislatura` FOREIGN KEY (`legislatura_id`) REFERENCES `legislaturas` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mandatos_partido` FOREIGN KEY (`partido_id`) REFERENCES `partidos` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mandatos_estado` FOREIGN KEY (`estado_id`) REFERENCES `estados` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_mandatos_titular` FOREIGN KEY (`titular_mandato_id`) REFERENCES `mandatos_legislativos` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 6. NODO: COMISIONES LEGISLATIVAS
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comisiones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `legislatura_id` SMALLINT UNSIGNED NOT NULL,
    `camara` ENUM('DIPUTADOS', 'SENADORES', 'CONGRESO_UNION') NOT NULL,
    `nombre` VARCHAR(200) NOT NULL,
    `slug` VARCHAR(220) NOT NULL UNIQUE,
    `tipo` ENUM('ORDINARIA', 'ESPECIAL', 'BICAMARAL', 'COMITE') NOT NULL DEFAULT 'ORDINARIA',
    PRIMARY KEY (`id`),
    INDEX `idx_comisiones_leg_camara` (`legislatura_id`, `camara`),
    CONSTRAINT `fk_comisiones_legislatura` FOREIGN KEY (`legislatura_id`) REFERENCES `legislaturas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 7. ARISTA: INTEGRANTES DE COMISIÓN (Mandato ↔ Comisión)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `comision_integrantes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `comision_id` INT UNSIGNED NOT NULL,
    `mandato_id` INT UNSIGNED NOT NULL,
    `puesto` ENUM('PRESIDENTE', 'SECRETARIO', 'INTEGRANTE') NOT NULL DEFAULT 'INTEGRANTE',
    `fecha_inicio` DATE NULL,
    `fecha_fin` DATE NULL,
    `estatus` ENUM('ACTIVO', 'BAJA') NOT NULL DEFAULT 'ACTIVO',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_comision_mandato` (`comision_id`, `mandato_id`),
    INDEX `idx_integrantes_mandato` (`mandato_id`),
    CONSTRAINT `fk_integrantes_comision` FOREIGN KEY (`comision_id`) REFERENCES `comisiones` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_integrantes_mandato` FOREIGN KEY (`mandato_id`) REFERENCES `mandatos_legislativos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 8. TRAYECTORIAS Y DETECTOR DE CHAPULINEO
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `trayectorias` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `politico_id` INT UNSIGNED NOT NULL,
    `tipo` ENUM('POLITICA', 'LEGISLATIVA', 'ADMINISTRATIVA', 'ACADEMICA', 'OTRO') NOT NULL,
    `del_ano` SMALLINT UNSIGNED NULL,
    `al_ano` SMALLINT UNSIGNED NULL,
    `cargo_experiencia` TEXT NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_trayectorias_politico` (`politico_id`),
    CONSTRAINT `fk_trayectorias_politico` FOREIGN KEY (`politico_id`) REFERENCES `politicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 9. INICIATIVAS DE LEY Y PUNTOS DE ACUERDO
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iniciativas` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `legislatura_id` SMALLINT UNSIGNED NOT NULL,
    `camara` ENUM('DIPUTADOS', 'SENADORES') NOT NULL,
    `tipo` ENUM('LEY_NUEVA', 'REFORMA_CONSTITUCIONAL', 'REFORMA_LEGAL', 'PUNTO_ACUERDO') NOT NULL,
    `titulo` TEXT NOT NULL,
    `estatus` ENUM('APROBADA', 'PENDIENTE', 'DESECHADA', 'RETIRADA') NOT NULL DEFAULT 'PENDIENTE',
    `fecha_presentacion` DATE NULL,
    `referencia_sil` VARCHAR(30) NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_iniciativas_leg` (`legislatura_id`),
    CONSTRAINT `fk_iniciativas_legislatura` FOREIGN KEY (`legislatura_id`) REFERENCES `legislaturas` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 10. ARISTA: CO-FIRMANTES DE INICIATIVAS (Grafo de Pactos Interpartidistas)
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `iniciativa_firmantes` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `iniciativa_id` INT UNSIGNED NOT NULL,
    `mandato_id` INT UNSIGNED NOT NULL,
    `rol` ENUM('INICIANTE', 'COFIPROPIETARIO', 'ADHERENTE') NOT NULL DEFAULT 'COFIPROPIETARIO',
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_iniciativa_mandato` (`iniciativa_id`, `mandato_id`),
    INDEX `idx_firmantes_mandato` (`mandato_id`),
    CONSTRAINT `fk_firmantes_iniciativa` FOREIGN KEY (`iniciativa_id`) REFERENCES `iniciativas` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_firmantes_mandato` FOREIGN KEY (`mandato_id`) REFERENCES `mandatos_legislativos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- 11. RADAR DE PRENSA Y MENCIONES
-- ------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `noticias_radar` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `medio_nombre` VARCHAR(80) NOT NULL,
    `sesgo_editorial` ENUM('OFICIALISTA', 'CENTRO', 'CRITICO') NOT NULL DEFAULT 'CENTRO',
    `titular` VARCHAR(255) NOT NULL,
    `url_noticia` VARCHAR(500) NOT NULL,
    `extracto` TEXT NULL,
    `fecha_publicacion` DATETIME NOT NULL,
    PRIMARY KEY (`id`),
    INDEX `idx_noticias_fecha` (`fecha_publicacion`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `noticia_menciones` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `noticia_id` INT UNSIGNED NOT NULL,
    `politico_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_noticia_politico` (`noticia_id`, `politico_id`),
    INDEX `idx_menciones_politico` (`politico_id`),
    CONSTRAINT `fk_menciones_noticia` FOREIGN KEY (`noticia_id`) REFERENCES `noticias_radar` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_menciones_politico` FOREIGN KEY (`politico_id`) REFERENCES `politicos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------------------
-- DATOS SEMILLA: LEGISLATURAS
-- ------------------------------------------------------------------------
INSERT INTO `legislaturas` (`id`, `numero_romano`, `slug`, `ano_inicio`, `ano_fin`, `es_actual`) VALUES
(64, 'LXIV', 'lxiv-2018-2021', 2018, 2021, 0),
(65, 'LXV',  'lxv-2021-2024',  2021, 2024, 0),
(66, 'LXVI', 'lxvi-2024-2027', 2024, 2027, 1)
ON DUPLICATE KEY UPDATE `es_actual` = VALUES(`es_actual`);

-- ------------------------------------------------------------------------
-- DATOS SEMILLA: PARTIDOS POLÍTICOS PRINCIPALES
-- ------------------------------------------------------------------------
INSERT INTO `partidos` (`sigla`, `nombre_completo`, `slug`, `color_hex`) VALUES
('Morena', 'Movimiento Regeneración Nacional', 'morena', '#B5261E'),
('PAN', 'Partido Acción Nacional', 'pan', '#004A99'),
('PRI', 'Partido Revolucionario Institucional', 'pri', '#00953A'),
('PVEM', 'Partido Verde Ecologista de México', 'pvem', '#50B748'),
('PT', 'Partido del Trabajo', 'pt', '#DA251C'),
('MC', 'Movimiento Ciudadano', 'mc', '#FF8300'),
('PRD', 'Partido de la Revolución Democrática', 'prd', '#FFCC00'),
('Sin Partido', 'Legislador Sin Partido / Independiente', 'sin-partido', '#888888')
ON DUPLICATE KEY UPDATE `color_hex` = VALUES(`color_hex`);

-- ------------------------------------------------------------------------
-- DATOS SEMILLA: ESTADOS DE MÉXICO (Muestra inicial clave)
-- ------------------------------------------------------------------------
INSERT INTO `estados` (`id`, `nombre`, `slug`, `abreviatura`, `diputados_mr_total`) VALUES
(1, 'Aguascalientes', 'aguascalientes', 'AGS', 3),
(2, 'Baja California', 'baja-california', 'BC', 9),
(3, 'Baja California Sur', 'baja-california-sur', 'BCS', 2),
(4, 'Campeche', 'campeche', 'CAMP', 2),
(5, 'Coahuila', 'coahuila', 'COAH', 8),
(6, 'Colima', 'colima', 'COL', 2),
(7, 'Chiapas', 'chiapas', 'CHIS', 13),
(8, 'Chihuahua', 'chihuahua', 'CHIH', 9),
(9, 'Ciudad de México', 'ciudad-de-mexico', 'CDMX', 22),
(10, 'Durango', 'durango', 'DGO', 4),
(11, 'Guanajuato', 'guanajuato', 'GTO', 15),
(12, 'Guerrero', 'guerrero', 'GRO', 8),
(13, 'Hidalgo', 'hidalgo', 'HGO', 7),
(14, 'Jalisco', 'jalisco', 'JAL', 20),
(15, 'México', 'mexico', 'MEX', 40),
(16, 'Michoacán', 'michoacan', 'MICH', 11),
(17, 'Morelos', 'morelos', 'MOR', 5),
(18, 'Nayarit', 'nayarit', 'NAY', 3),
(19, 'Nuevo León', 'nuevo-leon', 'NL', 14),
(20, 'Oaxaca', 'oaxaca', 'OAX', 10),
(21, 'Puebla', 'puebla', 'PUE', 16),
(22, 'Querétaro', 'queretaro', 'QRO', 6),
(23, 'Quintana Roo', 'quintana-roo', 'QROO', 4),
(24, 'San Luis Potosí', 'san-luis-potosi', 'SLP', 7),
(25, 'Sinaloa', 'sinaloa', 'SIN', 7),
(26, 'Sonora', 'sonora', 'SON', 7),
(27, 'Tabasco', 'tabasco', 'TAB', 6),
(28, 'Tamaulipas', 'tamaulipas', 'TAMPS', 8),
(29, 'Tlaxcala', 'tlaxcala', 'TLAX', 3),
(30, 'Veracruz', 'veracruz', 'VER', 19),
(31, 'Yucatán', 'yucatan', 'YUC', 6),
(32, 'Zacatecas', 'zacatecas', 'ZAC', 4)
ON DUPLICATE KEY UPDATE `diputados_mr_total` = VALUES(`diputados_mr_total`);

SET FOREIGN_KEY_CHECKS = 1;
