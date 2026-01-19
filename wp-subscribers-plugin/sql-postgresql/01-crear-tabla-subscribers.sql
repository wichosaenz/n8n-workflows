-- ============================================================================
-- SCRIPT DE CREACIÓN DE TABLA DE SUSCRIPTORES POSTGRESQL
-- Plugin: WP Subscribers Manager v1.5.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- PRERREQUISITO: Debes haber ejecutado primero el script:
--                00-crear-base-datos.sql
--
-- INSTRUCCIONES:
-- 1. Conéctate a la base de datos wp_subscribers:
--    psql -U wp_subscribers_user -d wp_subscribers
--    (O si eres superusuario: psql -U postgres -d wp_subscribers)
--
-- 2. Copia y pega este script completo
--
-- 3. Presiona Enter para ejecutar
--
-- ============================================================================

-- Conectar a la base de datos (si no estás conectado ya)
\c wp_subscribers

-- Establecer el schema
SET search_path TO public;

-- ============================================================================
-- PASO 1: CREAR TABLA DE SUSCRIPTORES (subscribers)
-- ============================================================================
-- Esta es la tabla principal que almacenará todos los datos de suscriptores
-- ============================================================================

CREATE TABLE IF NOT EXISTS subscribers (
    -- Identificador único autoincremental (SERIAL es equivalente a INT + SEQUENCE)
    id SERIAL PRIMARY KEY,

    -- Nombre del suscriptor
    name VARCHAR(255) NOT NULL,

    -- Email del suscriptor (debe ser único)
    email VARCHAR(255) NOT NULL UNIQUE,

    -- Fecha y hora de suscripción
    subscribed_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,

    -- Dirección IP del suscriptor (útil para auditoría)
    ip_address VARCHAR(45) NULL,

    -- Estado del suscriptor
    status VARCHAR(20) NOT NULL DEFAULT 'active'
        CHECK (status IN ('active', 'inactive', 'unsubscribed', 'bounced')),

    -- Fecha de última modificación (se actualiza automáticamente con trigger)
    updated_date TIMESTAMP NULL,

    -- Origen del suscriptor (opcional para tracking)
    source VARCHAR(100) NULL,

    -- URL del sitio web donde se registró el suscriptor
    website_url VARCHAR(255) NULL,

    -- Notas adicionales
    notes TEXT NULL
);

-- Comentarios en la tabla y columnas
COMMENT ON TABLE subscribers IS 'Tabla de suscriptores del sitio web';
COMMENT ON COLUMN subscribers.id IS 'Identificador único autoincremental';
COMMENT ON COLUMN subscribers.name IS 'Nombre completo del suscriptor';
COMMENT ON COLUMN subscribers.email IS 'Correo electrónico del suscriptor';
COMMENT ON COLUMN subscribers.subscribed_date IS 'Fecha de suscripción';
COMMENT ON COLUMN subscribers.ip_address IS 'Dirección IP del suscriptor (soporta IPv4 e IPv6)';
COMMENT ON COLUMN subscribers.status IS 'Estado: active, inactive, unsubscribed, bounced';
COMMENT ON COLUMN subscribers.updated_date IS 'Fecha de última actualización';
COMMENT ON COLUMN subscribers.source IS 'Origen de la suscripción: widget, shortcode, manual, import';
COMMENT ON COLUMN subscribers.website_url IS 'URL del sitio WordPress donde se registró (para múltiples sitios)';
COMMENT ON COLUMN subscribers.notes IS 'Notas adicionales sobre el suscriptor';

-- ============================================================================
-- PASO 2: CREAR ÍNDICES PARA OPTIMIZAR CONSULTAS
-- ============================================================================

-- Índice único para email (ya creado implícitamente por UNIQUE, pero lo hacemos explícito)
CREATE UNIQUE INDEX IF NOT EXISTS idx_subscribers_email_unique ON subscribers (email);

-- Índice para búsquedas por estado
CREATE INDEX IF NOT EXISTS idx_subscribers_status ON subscribers (status);

-- Índice para búsquedas por fecha de suscripción
CREATE INDEX IF NOT EXISTS idx_subscribers_subscribed_date ON subscribers (subscribed_date DESC);

-- Índice compuesto para filtrado común (estado + fecha)
CREATE INDEX IF NOT EXISTS idx_subscribers_status_date ON subscribers (status, subscribed_date DESC);

-- Índice para búsquedas por sitio web
CREATE INDEX IF NOT EXISTS idx_subscribers_website_url ON subscribers (website_url);

-- Índice para búsquedas por origen
CREATE INDEX IF NOT EXISTS idx_subscribers_source ON subscribers (source);

-- ============================================================================
-- PASO 3: CREAR FUNCIÓN Y TRIGGER PARA UPDATED_DATE
-- ============================================================================
-- Este trigger actualiza automáticamente updated_date cuando se modifica un registro
-- ============================================================================

-- Crear función que actualiza el timestamp
CREATE OR REPLACE FUNCTION update_subscribers_timestamp()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_date = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Comentario en la función
COMMENT ON FUNCTION update_subscribers_timestamp() IS 'Función que actualiza automáticamente updated_date';

-- Eliminar trigger si existe (para poder recrearlo)
DROP TRIGGER IF EXISTS trigger_update_subscribers_timestamp ON subscribers;

-- Crear trigger que ejecuta la función antes de cada UPDATE
CREATE TRIGGER trigger_update_subscribers_timestamp
    BEFORE UPDATE ON subscribers
    FOR EACH ROW
    EXECUTE FUNCTION update_subscribers_timestamp();

-- Comentario en el trigger
COMMENT ON TRIGGER trigger_update_subscribers_timestamp ON subscribers
    IS 'Trigger que actualiza updated_date automáticamente en cada UPDATE';

-- ============================================================================
-- PASO 4: OTORGAR PERMISOS AL USUARIO
-- ============================================================================
-- Asegurar que el usuario tenga todos los permisos necesarios
-- ============================================================================

-- Otorgar permisos completos sobre la tabla
GRANT SELECT, INSERT, UPDATE, DELETE ON TABLE subscribers TO wp_subscribers_user;

-- Otorgar permisos sobre la secuencia (para SERIAL)
GRANT USAGE, SELECT ON SEQUENCE subscribers_id_seq TO wp_subscribers_user;

-- ============================================================================
-- PASO 5: VERIFICAR CREACIÓN DE LA TABLA
-- ============================================================================

-- Mostrar la estructura de la tabla creada
\d+ subscribers

-- Listar índices
\di subscribers*

-- Listar triggers
\dS trigger_update_subscribers_timestamp

-- ============================================================================
-- PASO 6: VERIFICAR PERMISOS
-- ============================================================================

-- Ver permisos de la tabla
\dp subscribers

-- ============================================================================
-- CONSULTAS DE VERIFICACIÓN (OPCIONAL)
-- ============================================================================

-- Contar registros (debería ser 0 si es nueva instalación)
SELECT COUNT(*) AS total_subscribers FROM subscribers;

-- Ver estructura completa
SELECT
    column_name,
    data_type,
    character_maximum_length,
    is_nullable,
    column_default
FROM information_schema.columns
WHERE table_name = 'subscribers'
ORDER BY ordinal_position;

-- ============================================================================
-- MENSAJE DE ÉXITO
-- ============================================================================
-- Si llegaste hasta aquí sin errores, ¡la tabla se creó correctamente!
--
-- PRÓXIMOS PASOS:
-- 1. (OPCIONAL) Ejecuta el script: 02-datos-de-ejemplo.sql
-- 2. Configura el plugin en WordPress con los datos de conexión:
--    - Host: localhost (o IP del servidor PostgreSQL)
--    - Puerto: 5432
--    - Base de Datos: wp_subscribers
--    - Usuario: wp_subscribers_user
--    - Contraseña: [tu contraseña]
--    - Tabla: subscribers
--
-- 3. Usa el botón "Probar Conexión" en WordPress para verificar
-- 4. Prueba el formulario de suscripción
--
-- ============================================================================
