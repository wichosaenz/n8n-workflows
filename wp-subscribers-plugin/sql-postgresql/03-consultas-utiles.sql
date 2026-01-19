-- ============================================================================
-- CONSULTAS ÚTILES PARA POSTGRESQL
-- Plugin: WP Subscribers Manager v1.5.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- Este archivo contiene consultas útiles para administrar y analizar
-- los datos de suscriptores en PostgreSQL
--
-- INSTRUCCIONES:
-- 1. Conéctate a la base de datos:
--    psql -U wp_subscribers_user -d wp_subscribers
--
-- 2. Copia y pega la consulta que necesites
--
-- 3. Modifica los parámetros según tus necesidades
--
-- ============================================================================

-- Conectar a la base de datos
\c wp_subscribers

-- ============================================================================
-- CONSULTAS DE ESTADÍSTICAS GENERALES
-- ============================================================================

-- Total de suscriptores
SELECT COUNT(*) AS total_suscriptores FROM subscribers;

-- Suscriptores por estado
SELECT
    status,
    COUNT(*) AS cantidad,
    ROUND(100.0 * COUNT(*) / (SELECT COUNT(*) FROM subscribers), 2) AS porcentaje
FROM subscribers
GROUP BY status
ORDER BY cantidad DESC;

-- Suscriptores activos vs inactivos
SELECT
    CASE
        WHEN status = 'active' THEN 'Activos'
        ELSE 'No activos'
    END AS categoria,
    COUNT(*) AS cantidad
FROM subscribers
GROUP BY categoria;

-- ============================================================================
-- CONSULTAS POR FECHA
-- ============================================================================

-- Suscriptores de hoy
SELECT name, email, subscribed_date
FROM subscribers
WHERE DATE(subscribed_date) = CURRENT_DATE
ORDER BY subscribed_date DESC;

-- Suscriptores de la última semana
SELECT
    DATE(subscribed_date) AS fecha,
    COUNT(*) AS nuevos_suscriptores
FROM subscribers
WHERE subscribed_date >= CURRENT_DATE - INTERVAL '7 days'
GROUP BY DATE(subscribed_date)
ORDER BY fecha DESC;

-- Suscriptores del último mes
SELECT COUNT(*) AS suscriptores_ultimo_mes
FROM subscribers
WHERE subscribed_date >= CURRENT_DATE - INTERVAL '30 days';

-- Suscriptores por mes (últimos 12 meses)
SELECT
    TO_CHAR(subscribed_date, 'YYYY-MM') AS mes,
    COUNT(*) AS nuevos_suscriptores
FROM subscribers
WHERE subscribed_date >= CURRENT_DATE - INTERVAL '12 months'
GROUP BY TO_CHAR(subscribed_date, 'YYYY-MM')
ORDER BY mes DESC;

-- ============================================================================
-- CONSULTAS POR ORIGEN
-- ============================================================================

-- Suscriptores por origen
SELECT
    COALESCE(source, 'Sin especificar') AS origen,
    COUNT(*) AS cantidad
FROM subscribers
GROUP BY source
ORDER BY cantidad DESC;

-- Tasa de conversión por origen (activos vs total)
SELECT
    COALESCE(source, 'Sin especificar') AS origen,
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS activos,
    ROUND(100.0 * SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) / COUNT(*), 2) AS tasa_activos
FROM subscribers
GROUP BY source
ORDER BY total DESC;

-- ============================================================================
-- CONSULTAS POR SITIO WEB (MULTI-SITIO)
-- ============================================================================

-- Suscriptores por sitio web
SELECT
    COALESCE(website_url, 'Sin especificar') AS sitio,
    COUNT(*) AS suscriptores,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS activos
FROM subscribers
GROUP BY website_url
ORDER BY suscriptores DESC;

-- Buscar suscriptores de un sitio específico
SELECT name, email, status, subscribed_date
FROM subscribers
WHERE website_url = 'https://www.example.com'
ORDER BY subscribed_date DESC;

-- ============================================================================
-- CONSULTAS DE BÚSQUEDA Y FILTRADO
-- ============================================================================

-- Buscar suscriptor por email
SELECT * FROM subscribers
WHERE email = 'juan.perez@example.com';

-- Buscar suscriptores por nombre (parcial)
SELECT name, email, status, subscribed_date
FROM subscribers
WHERE name ILIKE '%juan%'
ORDER BY name;

-- Buscar por dominio de email
SELECT name, email, status
FROM subscribers
WHERE email LIKE '%@gmail.com'
ORDER BY email;

-- Suscriptores con notas
SELECT name, email, notes
FROM subscribers
WHERE notes IS NOT NULL AND notes != ''
ORDER BY subscribed_date DESC;

-- ============================================================================
-- CONSULTAS DE CALIDAD DE DATOS
-- ============================================================================

-- Emails duplicados (no debería haber debido a UNIQUE constraint)
SELECT email, COUNT(*) AS cantidad
FROM subscribers
GROUP BY email
HAVING COUNT(*) > 1;

-- Suscriptores sin IP
SELECT COUNT(*) AS sin_ip
FROM subscribers
WHERE ip_address IS NULL OR ip_address = '';

-- Suscriptores sin origen especificado
SELECT COUNT(*) AS sin_origen
FROM subscribers
WHERE source IS NULL OR source = '';

-- ============================================================================
-- CONSULTAS DE ACTIVIDAD RECIENTE
-- ============================================================================

-- Últimos 10 suscriptores
SELECT name, email, status, subscribed_date
FROM subscribers
ORDER BY subscribed_date DESC
LIMIT 10;

-- Últimas actualizaciones (cambios de estado, notas, etc.)
SELECT name, email, status, updated_date, notes
FROM subscribers
WHERE updated_date IS NOT NULL
ORDER BY updated_date DESC
LIMIT 20;

-- Suscriptores que cambiaron de estado recientemente
SELECT name, email, status, updated_date
FROM subscribers
WHERE updated_date >= CURRENT_DATE - INTERVAL '7 days'
ORDER BY updated_date DESC;

-- ============================================================================
-- CONSULTAS ANALÍTICAS AVANZADAS
-- ============================================================================

-- Crecimiento mensual de suscriptores
WITH monthly_subscribers AS (
    SELECT
        TO_CHAR(subscribed_date, 'YYYY-MM') AS mes,
        COUNT(*) AS nuevos
    FROM subscribers
    GROUP BY TO_CHAR(subscribed_date, 'YYYY-MM')
)
SELECT
    mes,
    nuevos,
    SUM(nuevos) OVER (ORDER BY mes) AS total_acumulado
FROM monthly_subscribers
ORDER BY mes DESC
LIMIT 12;

-- Tasa de retención (activos del mes pasado que siguen activos)
SELECT
    COUNT(*) FILTER (WHERE subscribed_date < CURRENT_DATE - INTERVAL '30 days' AND status = 'active') AS activos_antiguos,
    COUNT(*) FILTER (WHERE subscribed_date < CURRENT_DATE - INTERVAL '30 days') AS total_antiguos,
    ROUND(100.0 *
        COUNT(*) FILTER (WHERE subscribed_date < CURRENT_DATE - INTERVAL '30 days' AND status = 'active') /
        NULLIF(COUNT(*) FILTER (WHERE subscribed_date < CURRENT_DATE - INTERVAL '30 days'), 0),
    2) AS tasa_retencion
FROM subscribers;

-- Top 10 dominios de email más comunes
SELECT
    SUBSTRING(email FROM '@(.*)$') AS dominio,
    COUNT(*) AS cantidad
FROM subscribers
GROUP BY dominio
ORDER BY cantidad DESC
LIMIT 10;

-- Distribución por hora del día (cuándo se suscriben más)
SELECT
    EXTRACT(HOUR FROM subscribed_date) AS hora,
    COUNT(*) AS suscripciones
FROM subscribers
GROUP BY hora
ORDER BY hora;

-- Distribución por día de la semana
SELECT
    TO_CHAR(subscribed_date, 'Day') AS dia_semana,
    COUNT(*) AS suscripciones
FROM subscribers
GROUP BY TO_CHAR(subscribed_date, 'Day'), EXTRACT(DOW FROM subscribed_date)
ORDER BY EXTRACT(DOW FROM subscribed_date);

-- ============================================================================
-- EXPORTAR DATOS
-- ============================================================================

-- Exportar lista completa a CSV (ejecutar desde psql)
-- \copy (SELECT * FROM subscribers ORDER BY subscribed_date DESC) TO '/tmp/subscribers.csv' CSV HEADER

-- Exportar solo activos
-- \copy (SELECT name, email FROM subscribers WHERE status = 'active' ORDER BY name) TO '/tmp/subscribers_activos.csv' CSV HEADER

-- Exportar estadísticas
-- \copy (SELECT status, COUNT(*) as cantidad FROM subscribers GROUP BY status) TO '/tmp/estadisticas.csv' CSV HEADER

-- ============================================================================
-- NOTAS
-- ============================================================================
--
-- RECORDATORIOS:
-- - Estas consultas son de solo lectura (SELECT)
-- - Para modificar datos, usa las funciones del plugin en WordPress
-- - Para backups, usa: pg_dump -U wp_subscribers_user -d wp_subscribers > backup.sql
-- - Para restaurar: psql -U wp_subscribers_user -d wp_subscribers < backup.sql
--
-- ============================================================================
