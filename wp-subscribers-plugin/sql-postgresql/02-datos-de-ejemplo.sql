-- ============================================================================
-- SCRIPT DE DATOS DE EJEMPLO POSTGRESQL
-- Plugin: WP Subscribers Manager v1.5.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- IMPORTANTE: Este script es OPCIONAL y solo para pruebas/desarrollo
-- NO ejecutes esto en producción si ya tienes datos reales
--
-- PRERREQUISITO: Debes haber ejecutado primero:
--                00-crear-base-datos.sql
--                01-crear-tabla-subscribers.sql
--
-- INSTRUCCIONES:
-- 1. Conéctate a la base de datos:
--    psql -U wp_subscribers_user -d wp_subscribers
--
-- 2. Copia y pega este script completo
--
-- 3. Presiona Enter para ejecutar
--
-- ============================================================================

-- Conectar a la base de datos
\c wp_subscribers

-- ============================================================================
-- PASO 1: LIMPIAR DATOS ANTERIORES (OPCIONAL)
-- ============================================================================
-- Descomenta la siguiente línea si quieres empezar desde cero
-- TRUNCATE TABLE subscribers RESTART IDENTITY CASCADE;

-- ============================================================================
-- PASO 2: INSERTAR DATOS DE EJEMPLO
-- ============================================================================

-- Insertar suscriptores de ejemplo con diferentes estados y orígenes

INSERT INTO subscribers (name, email, subscribed_date, ip_address, status, source, website_url, notes) VALUES
    ('Juan Pérez', 'juan.perez@example.com', '2025-01-01 10:30:00', '192.168.1.100', 'active', 'shortcode', 'https://www.example.com', 'Suscriptor desde el inicio'),
    ('María González', 'maria.gonzalez@example.com', '2025-01-02 14:20:00', '192.168.1.101', 'active', 'widget', 'https://www.example.com', NULL),
    ('Carlos Rodríguez', 'carlos.rodriguez@example.com', '2025-01-03 09:15:00', '192.168.1.102', 'active', 'shortcode', 'https://www.example.com', NULL),
    ('Ana Martínez', 'ana.martinez@example.com', '2025-01-04 16:45:00', '192.168.1.103', 'inactive', 'import', 'https://www.example.com', 'Importado desde lista antigua'),
    ('Luis Fernández', 'luis.fernandez@example.com', '2025-01-05 11:00:00', '192.168.1.104', 'active', 'shortcode', 'https://www.example.com', NULL),
    ('Laura Sánchez', 'laura.sanchez@example.com', '2025-01-06 13:30:00', '192.168.1.105', 'unsubscribed', 'widget', 'https://www.example.com', E'\n[2025-01-15 10:00:00] Desuscrito - Ya no quiere recibir emails'),
    ('Pedro López', 'pedro.lopez@example.com', '2025-01-07 08:20:00', '192.168.1.106', 'active', 'manual', 'https://www.example.com', 'Agregado manualmente por administrador'),
    ('Carmen Díaz', 'carmen.diaz@example.com', '2025-01-08 15:10:00', '192.168.1.107', 'bounced', 'shortcode', 'https://www.example.com', E'\n[2025-01-20 09:30:00] Email rebotado - dominio no existe'),
    ('Miguel Torres', 'miguel.torres@example.com', '2025-01-09 12:40:00', '192.168.1.108', 'active', 'widget', 'https://www.example.com', NULL),
    ('Isabel Ramírez', 'isabel.ramirez@example.com', '2025-01-10 10:55:00', '192.168.1.109', 'active', 'shortcode', 'https://www.example.com', NULL),

    -- Suscriptores de otro sitio web (multi-sitio)
    ('Roberto Castro', 'roberto.castro@example.com', '2025-01-11 14:15:00', '192.168.2.100', 'active', 'shortcode', 'https://www.otro-sitio.com', NULL),
    ('Patricia Morales', 'patricia.morales@example.com', '2025-01-12 09:25:00', '192.168.2.101', 'active', 'widget', 'https://www.otro-sitio.com', NULL),
    ('Francisco Jiménez', 'francisco.jimenez@example.com', '2025-01-13 16:00:00', '192.168.2.102', 'inactive', 'import', 'https://www.otro-sitio.com', 'Importado de campaña anterior'),

    -- Suscriptores recientes
    ('Sofía Ruiz', 'sofia.ruiz@example.com', NOW() - INTERVAL '2 days', '192.168.1.110', 'active', 'shortcode', 'https://www.example.com', NULL),
    ('Diego Navarro', 'diego.navarro@example.com', NOW() - INTERVAL '1 day', '192.168.1.111', 'active', 'widget', 'https://www.example.com', NULL),
    ('Valentina Cruz', 'valentina.cruz@example.com', NOW() - INTERVAL '12 hours', '192.168.1.112', 'active', 'shortcode', 'https://www.example.com', 'Nueva suscriptora muy reciente'),
    ('Andrés Ortiz', 'andres.ortiz@example.com', NOW() - INTERVAL '6 hours', '192.168.1.113', 'active', 'widget', 'https://www.example.com', NULL),
    ('Camila Vega', 'camila.vega@example.com', NOW() - INTERVAL '2 hours', '192.168.1.114', 'active', 'shortcode', 'https://www.example.com', NULL),
    ('Mateo Silva', 'mateo.silva@example.com', NOW() - INTERVAL '1 hour', '192.168.1.115', 'active', 'widget', 'https://www.example.com', NULL),
    ('Emma Mendoza', 'emma.mendoza@example.com', NOW() - INTERVAL '30 minutes', '192.168.1.116', 'active', 'shortcode', 'https://www.example.com', 'Suscriptora más reciente')
ON CONFLICT (email) DO NOTHING;

-- ============================================================================
-- PASO 3: VERIFICAR INSERCIÓN
-- ============================================================================

-- Contar total de registros insertados
SELECT COUNT(*) AS total_insertados FROM subscribers;

-- Ver los primeros 10 suscriptores
SELECT id, name, email, status, source, subscribed_date
FROM subscribers
ORDER BY subscribed_date DESC
LIMIT 10;

-- ============================================================================
-- PASO 4: ESTADÍSTICAS DE LOS DATOS INSERTADOS
-- ============================================================================

-- Estadísticas por estado
SELECT
    status AS estado,
    COUNT(*) AS cantidad,
    ROUND(100.0 * COUNT(*) / (SELECT COUNT(*) FROM subscribers), 2) AS porcentaje
FROM subscribers
GROUP BY status
ORDER BY cantidad DESC;

-- Estadísticas por origen
SELECT
    source AS origen,
    COUNT(*) AS cantidad
FROM subscribers
WHERE source IS NOT NULL
GROUP BY source
ORDER BY cantidad DESC;

-- Estadísticas por sitio web
SELECT
    website_url AS sitio_web,
    COUNT(*) AS suscriptores
FROM subscribers
WHERE website_url IS NOT NULL
GROUP BY website_url
ORDER BY suscriptores DESC;

-- Suscriptores por día (últimos 7 días)
SELECT
    DATE(subscribed_date) AS fecha,
    COUNT(*) AS nuevos_suscriptores
FROM subscribers
WHERE subscribed_date >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY DATE(subscribed_date)
ORDER BY fecha DESC;

-- ============================================================================
-- MENSAJE DE ÉXITO
-- ============================================================================
-- Si llegaste hasta aquí sin errores, los datos de ejemplo se insertaron correctamente!
--
-- DATOS INSERTADOS:
-- - 20 suscriptores de ejemplo
-- - Diferentes estados: active, inactive, unsubscribed, bounced
-- - Diferentes orígenes: shortcode, widget, manual, import
-- - Dos sitios web diferentes (multi-sitio)
-- - Algunos con notas adicionales
--
-- PRÓXIMOS PASOS:
-- 1. Configura el plugin en WordPress
-- 2. Ve al panel de administración del plugin
-- 3. Verifica que veas la lista de suscriptores
-- 4. Prueba los filtros por estado y sitio web
--
-- CONSULTAS ÚTILES:
-- Ver el script: 03-consultas-utiles.sql para más ejemplos
--
-- ============================================================================
