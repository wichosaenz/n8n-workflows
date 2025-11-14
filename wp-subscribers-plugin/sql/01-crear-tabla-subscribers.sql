-- ============================================================================
-- SCRIPT DE CREACIÓN DE TABLA DE SUSCRIPTORES
-- Plugin: WP Subscribers Manager v1.2.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- IMPORTANTE: Este script está diseñado para funcionar en Dreamhost
-- sin privilegios SUPER y con binary logging habilitado.
--
-- INSTRUCCIONES:
-- 1. Accede a phpMyAdmin en tu panel de Dreamhost
-- 2. Selecciona tu base de datos
-- 3. Ve a la pestaña "SQL"
-- 4. Copia y pega este script completo
-- 5. Haz clic en "Continuar" para ejecutar
--
-- ============================================================================

-- Establecer el conjunto de caracteres para la sesión
SET NAMES utf8mb4;
SET CHARACTER_SET_CLIENT = utf8mb4;
SET CHARACTER_SET_RESULTS = utf8mb4;

-- ============================================================================
-- PASO 1: CREAR TABLA DE SUSCRIPTORES (subscribers)
-- ============================================================================
-- Esta es la tabla principal que almacenará todos los datos de suscriptores
-- ============================================================================

CREATE TABLE IF NOT EXISTS `subscribers` (
    -- Identificador único autoincremental
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,

    -- Nombre del suscriptor
    `name` VARCHAR(255) NOT NULL COMMENT 'Nombre completo del suscriptor',

    -- Email del suscriptor (debe ser único)
    `email` VARCHAR(255) NOT NULL COMMENT 'Correo electrónico del suscriptor',

    -- Fecha y hora de suscripción
    `subscribed_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha de suscripción',

    -- Dirección IP del suscriptor (útil para auditoría)
    `ip_address` VARCHAR(45) NULL COMMENT 'Dirección IP del suscriptor (soporta IPv4 e IPv6)',

    -- Estado del suscriptor
    `status` VARCHAR(20) NOT NULL DEFAULT 'active' COMMENT 'Estado: active, inactive, unsubscribed, bounced',

    -- Fecha de última modificación
    `updated_date` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP COMMENT 'Fecha de última actualización',

    -- Origen del suscriptor (opcional para tracking)
    `source` VARCHAR(100) NULL COMMENT 'Origen de la suscripción: widget, shortcode, manual, import',

    -- URL del sitio web donde se registró el suscriptor
    `website_url` VARCHAR(255) NULL COMMENT 'URL del sitio WordPress donde se registró (para múltiples sitios)',

    -- Notas adicionales
    `notes` TEXT NULL COMMENT 'Notas adicionales sobre el suscriptor',

    -- Clave primaria
    PRIMARY KEY (`id`),

    -- Índice único para evitar emails duplicados
    UNIQUE KEY `idx_email_unique` (`email`),

    -- Índice para búsquedas por estado
    KEY `idx_status` (`status`),

    -- Índice para búsquedas por fecha de suscripción
    KEY `idx_subscribed_date` (`subscribed_date`),

    -- Índice compuesto para filtrado común
    KEY `idx_status_date` (`status`, `subscribed_date`),

    -- Índice para búsquedas por sitio web
    KEY `idx_website_url` (`website_url`(100))

) ENGINE=InnoDB
  DEFAULT CHARSET=utf8mb4
  COLLATE=utf8mb4_unicode_ci
  COMMENT='Tabla de suscriptores del sitio web';

-- ============================================================================
-- PASO 2: VERIFICAR CREACIÓN DE LA TABLA
-- ============================================================================

-- Mostrar la estructura de la tabla creada
SHOW CREATE TABLE `subscribers`;

-- Mostrar las columnas de la tabla
DESCRIBE `subscribers`;

-- ============================================================================
-- MENSAJE DE ÉXITO
-- ============================================================================
-- Si llegaste hasta aquí sin errores, ¡la tabla se creó correctamente!
--
-- PRÓXIMOS PASOS:
-- 1. Ejecuta el script: 02-datos-de-ejemplo.sql (OPCIONAL)
-- 2. Configura el plugin en WordPress con los datos de conexión
-- 3. Prueba el formulario de suscripción
--
-- ============================================================================
