-- ============================================================================
-- INSTALACIÓN RÁPIDA - TODO EN UNO
-- Plugin: WP Subscribers Manager v1.0.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- Este script contiene TODO lo necesario para la instalación inicial.
-- Es la forma más rápida de configurar la base de datos.
--
-- INSTRUCCIONES:
-- 1. Accede a phpMyAdmin en Dreamhost
-- 2. Selecciona tu base de datos
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega TODO este archivo
-- 5. Haz clic en "Continuar"
-- 6. ¡Listo! La tabla está creada y verificada
--
-- ============================================================================

-- Configurar charset
SET NAMES utf8mb4;
SET CHARACTER_SET_CLIENT = utf8mb4;
SET CHARACTER_SET_RESULTS = utf8mb4;

-- ============================================================================
-- CREAR TABLA PRINCIPAL
-- ============================================================================

CREATE TABLE IF NOT EXISTS `subscribers` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre completo del suscriptor',
    `email` VARCHAR(255) NOT NULL COMMENT 'Correo electrónico del suscriptor',
    `subscribed_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de suscripción',
    `ip_address` VARCHAR(45) NULL COMMENT 'Dirección IP del suscriptor (soporta IPv4 e IPv6)',
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Estado: active, inactive, unsubscribed, bounced',
    `updated_date` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',
    `source` VARCHAR(100) NULL COMMENT 'Origen de la suscripción: widget, shortcode, manual, import',
    `notes` TEXT NULL COMMENT 'Notas adicionales sobre el suscriptor',
    PRIMARY KEY (`id`),
    UNIQUE KEY `idx_email_unique` (`email`),
    KEY `idx_status` (`status`),
    KEY `idx_subscribed_date` (`subscribed_date`),
    KEY `idx_status_date` (`status`, `subscribed_date`)
) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla de suscriptores del sitio web';

-- ============================================================================
-- VERIFICAR INSTALACIÓN
-- ============================================================================

-- Mostrar estructura de la tabla
SELECT
    'Tabla creada correctamente' AS 'Estado',
    COUNT(*) AS 'Registros actuales'
FROM `subscribers`;

-- Mostrar información de la tabla
SELECT
    TABLE_NAME AS 'Tabla',
    ENGINE AS 'Motor',
    TABLE_ROWS AS 'Filas',
    TABLE_COLLATION AS 'Collation',
    CREATE_TIME AS 'Fecha de creación'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'subscribers';

-- Mostrar índices creados
SELECT
    INDEX_NAME AS 'Índice',
    GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX SEPARATOR ', ') AS 'Columnas',
    CASE WHEN NON_UNIQUE = 0 THEN 'UNIQUE' ELSE 'INDEX' END AS 'Tipo'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'subscribers'
GROUP BY INDEX_NAME, NON_UNIQUE
ORDER BY INDEX_NAME;

-- ============================================================================
-- MENSAJE FINAL
-- ============================================================================

SELECT
    '✓ INSTALACIÓN COMPLETADA EXITOSAMENTE' AS 'Resultado',
    'Ahora puedes configurar el plugin en WordPress' AS 'Siguiente Paso',
    'Ve a: WordPress Admin > Suscriptores' AS 'Ubicación';

-- ============================================================================
-- INFORMACIÓN IMPORTANTE
-- ============================================================================
--
-- ✅ La tabla 'subscribers' ha sido creada
-- ✅ Todos los índices han sido configurados
-- ✅ El charset UTF-8 está configurado correctamente
--
-- PRÓXIMOS PASOS:
--
-- 1. Ir a WordPress Admin > Suscriptores
-- 2. Configurar los parámetros de conexión:
--    - Host: [tu hostname de Dreamhost]
--    - Base de datos: [nombre de tu BD]
--    - Usuario: [tu usuario]
--    - Contraseña: [tu contraseña]
--    - Tabla: subscribers
-- 3. Hacer clic en "Probar Conexión"
-- 4. Guardar la configuración
-- 5. Insertar el shortcode en una página: [wp_subscribers_form]
-- 6. ¡Probar el formulario!
--
-- SCRIPTS ADICIONALES DISPONIBLES:
--
-- - 02-datos-de-ejemplo.sql
--   Para insertar datos de prueba (OPCIONAL)
--
-- - 03-consultas-utiles.sql
--   Consultas para gestión diaria (REFERENCIA)
--
-- - 04-mantenimiento-optimizacion.sql
--   Mantenimiento periódico (EJECUTAR MENSUALMENTE)
--
-- - README-SQL.md
--   Documentación completa de SQL
--
-- SOPORTE:
-- Desarrollador: Wicho Saenz
-- Sitio Web: www.wichosaenz.com
--
-- ============================================================================
