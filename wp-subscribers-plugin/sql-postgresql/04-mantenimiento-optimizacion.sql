-- ============================================================================
-- MANTENIMIENTO Y OPTIMIZACIÓN POSTGRESQL
-- Plugin: WP Subscribers Manager v1.5.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- Este archivo contiene comandos para mantener y optimizar la base de datos
--
-- IMPORTANTE: Algunas operaciones pueden bloquear la tabla temporalmente
-- Ejecútalas en horarios de bajo tráfico
--
-- INSTRUCCIONES:
-- 1. Conéctate a la base de datos:
--    psql -U wp_subscribers_user -d wp_subscribers
--
-- 2. Copia y pega el comando que necesites
--
-- ============================================================================

-- Conectar a la base de datos
\c wp_subscribers

-- ============================================================================
-- ANÁLISIS Y ESTADÍSTICAS DE LA TABLA
-- ============================================================================

-- Ver tamaño de la tabla
SELECT
    pg_size_pretty(pg_total_relation_size('subscribers')) AS tamaño_total,
    pg_size_pretty(pg_relation_size('subscribers')) AS tamaño_tabla,
    pg_size_pretty(pg_total_relation_size('subscribers') - pg_relation_size('subscribers')) AS tamaño_indices;

-- Estadísticas de la tabla
SELECT
    schemaname,
    tablename,
    n_live_tup AS filas_vivas,
    n_dead_tup AS filas_muertas,
    last_vacuum,
    last_autovacuum,
    last_analyze,
    last_autoanalyze
FROM pg_stat_user_tables
WHERE tablename = 'subscribers';

-- Ver índices y su tamaño
SELECT
    indexname,
    pg_size_pretty(pg_relation_size(indexrelid::regclass)) AS tamaño
FROM pg_indexes
INNER JOIN pg_class ON pg_indexes.indexname = pg_class.relname
WHERE tablename = 'subscribers';

-- ============================================================================
-- MANTENIMIENTO BÁSICO
-- ============================================================================

-- VACUUM: Recuperar espacio y actualizar estadísticas
-- Recomendado: Ejecutar semanalmente o cuando haya muchas actualizaciones/eliminaciones
VACUUM VERBOSE ANALYZE subscribers;

-- VACUUM FULL: Recuperación completa de espacio (BLOQUEA LA TABLA)
-- Solo usar si es estrictamente necesario (tabla muy fragmentada)
-- VACUUM FULL VERBOSE ANALYZE subscribers;

-- ANALYZE: Actualizar estadísticas del query planner
-- Recomendado: Ejecutar después de cambios masivos
ANALYZE VERBOSE subscribers;

-- REINDEX: Reconstruir índices
-- Útil si los índices están fragmentados o corruptos
REINDEX TABLE VERBOSE subscribers;

-- ============================================================================
-- OPTIMIZACIÓN DE ÍNDICES
-- ============================================================================

-- Verificar uso de índices
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan AS escaneos,
    idx_tup_read AS tuplas_leidas,
    idx_tup_fetch AS tuplas_obtenidas
FROM pg_stat_user_indexes
WHERE tablename = 'subscribers'
ORDER BY idx_scan DESC;

-- Encontrar índices no utilizados (candidatos para eliminación)
SELECT
    schemaname,
    tablename,
    indexname,
    idx_scan
FROM pg_stat_user_indexes
WHERE tablename = 'subscribers'
  AND idx_scan = 0
  AND indexname NOT LIKE '%_pkey';

-- ============================================================================
-- LIMPIEZA DE DATOS
-- ============================================================================

-- Eliminar suscriptores desactivados hace más de 1 año
-- PRECAUCIÓN: Esta operación es DESTRUCTIVA
-- DELETE FROM subscribers
-- WHERE status = 'inactive'
--   AND updated_date < CURRENT_DATE - INTERVAL '1 year';

-- Eliminar suscriptores con emails rebotados hace más de 6 meses
-- PRECAUCIÓN: Esta operación es DESTRUCTIVA
-- DELETE FROM subscribers
-- WHERE status = 'bounced'
--   AND updated_date < CURRENT_DATE - INTERVAL '6 months';

-- ============================================================================
-- BACKUP Y RESTAURACIÓN
-- ============================================================================

-- BACKUP DE LA TABLA (ejecutar desde línea de comandos, no en psql):
-- pg_dump -U wp_subscribers_user -d wp_subscribers -t subscribers > backup_subscribers_$(date +%Y%m%d).sql

-- BACKUP DE TODA LA BASE DE DATOS:
-- pg_dump -U wp_subscribers_user -d wp_subscribers > backup_wp_subscribers_$(date +%Y%m%d).sql

-- BACKUP COMPRIMIDO:
-- pg_dump -U wp_subscribers_user -d wp_subscribers | gzip > backup_wp_subscribers_$(date +%Y%m%d).sql.gz

-- RESTAURAR BACKUP:
-- psql -U wp_subscribers_user -d wp_subscribers < backup_subscribers_20250115.sql

-- RESTAURAR BACKUP COMPRIMIDO:
-- gunzip -c backup_wp_subscribers_20250115.sql.gz | psql -U wp_subscribers_user -d wp_subscribers

-- ============================================================================
-- MONITOREO DE RENDIMIENTO
-- ============================================================================

-- Ver queries lentas en progreso
SELECT
    pid,
    now() - pg_stat_activity.query_start AS duration,
    query,
    state
FROM pg_stat_activity
WHERE (now() - pg_stat_activity.query_start) > interval '5 seconds'
  AND state != 'idle'
ORDER BY duration DESC;

-- Ver conexiones activas a la base de datos
SELECT
    datname,
    count(*) AS conexiones
FROM pg_stat_activity
WHERE datname = 'wp_subscribers'
GROUP BY datname;

-- Ver locks en la tabla
SELECT
    locktype,
    relation::regclass,
    mode,
    granted,
    pid
FROM pg_locks
WHERE relation = 'subscribers'::regclass;

-- ============================================================================
-- VERIFICACIÓN DE INTEGRIDAD
-- ============================================================================

-- Verificar integridad de índices
SELECT
    indexname,
    pg_relation_size(indexrelid) AS index_size
FROM pg_indexes
JOIN pg_class ON pg_indexes.indexname = pg_class.relname
WHERE tablename = 'subscribers'
ORDER BY index_size DESC;

-- Verificar constraint de email único
SELECT email, COUNT(*) AS duplicados
FROM subscribers
GROUP BY email
HAVING COUNT(*) > 1;

-- Verificar datos inválidos
SELECT
    COUNT(*) FILTER (WHERE email NOT LIKE '%@%') AS emails_invalidos,
    COUNT(*) FILTER (WHERE name IS NULL OR name = '') AS nombres_vacios,
    COUNT(*) FILTER (WHERE status NOT IN ('active', 'inactive', 'unsubscribed', 'bounced')) AS estados_invalidos
FROM subscribers;

-- ============================================================================
-- CONFIGURACIÓN AVANZADA
-- ============================================================================

-- Ver configuración de autovacuum para la tabla
SELECT
    relname,
    reloptions
FROM pg_class
WHERE relname = 'subscribers';

-- Ajustar configuración de autovacuum (si es necesario)
-- ALTER TABLE subscribers SET (
--     autovacuum_vacuum_scale_factor = 0.1,
--     autovacuum_analyze_scale_factor = 0.05
-- );

-- Ver estadísticas extendidas
SELECT * FROM pg_stats WHERE tablename = 'subscribers';

-- ============================================================================
-- RECREAR TABLA (SOLO EN EMERGENCIAS)
-- ============================================================================
-- Usar solo si la tabla está corrupta o muy fragmentada
-- PRECAUCIÓN: Requiere downtime del plugin

-- PASO 1: Hacer backup completo
-- pg_dump -U wp_subscribers_user -d wp_subscribers -t subscribers > backup_emergency.sql

-- PASO 2: Crear tabla temporal
-- CREATE TABLE subscribers_temp AS SELECT * FROM subscribers;

-- PASO 3: Eliminar tabla original
-- DROP TABLE subscribers CASCADE;

-- PASO 4: Recrear tabla (ejecutar script 01-crear-tabla-subscribers.sql)

-- PASO 5: Restaurar datos
-- INSERT INTO subscribers SELECT * FROM subscribers_temp;

-- PASO 6: Eliminar tabla temporal
-- DROP TABLE subscribers_temp;

-- PASO 7: Recrear índices y triggers
-- (Ya incluido en el script 01-crear-tabla-subscribers.sql)

-- ============================================================================
-- PROGRAMAR MANTENIMIENTO AUTOMÁTICO
-- ============================================================================
--
-- PostgreSQL tiene autovacuum habilitado por defecto, pero puedes programar
-- mantenimiento manual adicional usando cron (Linux) o Task Scheduler (Windows)
--
-- EJEMPLO DE CRON (ejecutar todos los domingos a las 3 AM):
-- 0 3 * * 0 psql -U wp_subscribers_user -d wp_subscribers -c "VACUUM ANALYZE subscribers;"
--
-- EJEMPLO DE SCRIPT BASH PARA BACKUP DIARIO:
-- #!/bin/bash
-- FECHA=$(date +%Y%m%d_%H%M%S)
-- pg_dump -U wp_subscribers_user -d wp_subscribers | gzip > /backups/wp_subscribers_$FECHA.sql.gz
-- # Eliminar backups más antiguos de 30 días
-- find /backups -name "wp_subscribers_*.sql.gz" -mtime +30 -delete
--
-- ============================================================================
-- NOTAS FINALES
-- ============================================================================
--
-- RECOMENDACIONES DE MANTENIMIENTO:
-- - VACUUM ANALYZE: Semanal
-- - Backup completo: Diario
-- - REINDEX: Mensual o cuando haya problemas de rendimiento
-- - Verificación de integridad: Mensual
-- - Limpieza de datos antiguos: Trimestral
--
-- ANTES DE CUALQUIER OPERACIÓN DESTRUCTIVA:
-- 1. Hacer backup completo
-- 2. Probar en entorno de desarrollo
-- 3. Planificar ventana de mantenimiento
-- 4. Informar a usuarios si es necesario
--
-- ============================================================================
