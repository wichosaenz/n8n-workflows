-- ============================================================================
-- SCRIPT DE MANTENIMIENTO Y OPTIMIZACIÓN
-- Plugin: WP Subscribers Manager v1.2.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- Este script contiene consultas para mantenimiento y optimización de la
-- tabla de suscriptores. Ejecuta estas consultas periódicamente para
-- mantener un rendimiento óptimo.
--
-- FRECUENCIA RECOMENDADA:
-- - Optimización de tablas: Mensual
-- - Análisis de tablas: Quincenal
-- - Verificación de integridad: Semanal
-- - Limpieza de datos: Según necesidad
--
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- 1. ANÁLISIS DE LA TABLA
-- ============================================================================
-- Analiza y almacena la distribución de claves para la tabla.
-- Esto ayuda a MySQL a optimizar las consultas.
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

ANALYZE TABLE `subscribers`;


-- ============================================================================
-- 2. OPTIMIZACIÓN DE LA TABLA
-- ============================================================================
-- Reorganiza el almacenamiento físico de la tabla y reconstruye índices.
-- Recomendado ejecutar cuando la tabla ha tenido muchas operaciones de
-- INSERT, UPDATE o DELETE.
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

OPTIMIZE TABLE `subscribers`;


-- ============================================================================
-- 3. VERIFICAR ESTADO DE LA TABLA
-- ============================================================================
-- Verifica que la tabla no tenga errores o corrupción.
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

CHECK TABLE `subscribers`;


-- ============================================================================
-- 4. REPARAR TABLA (SOLO SI HAY ERRORES)
-- ============================================================================
-- Ejecuta esta consulta SOLO si CHECK TABLE reportó errores.
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

-- DESCOMENTA LA SIGUIENTE LÍNEA SOLO SI ES NECESARIO:
-- REPAIR TABLE `subscribers`;


-- ============================================================================
-- 5. ESTADÍSTICAS DE LA TABLA
-- ============================================================================
-- Muestra información sobre el tamaño y estado de la tabla
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

SELECT
    TABLE_NAME AS 'Tabla',
    TABLE_ROWS AS 'Filas',
    ROUND(DATA_LENGTH / 1024 / 1024, 2) AS 'Tamaño Datos (MB)',
    ROUND(INDEX_LENGTH / 1024 / 1024, 2) AS 'Tamaño Índices (MB)',
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Tamaño Total (MB)',
    ROUND(DATA_FREE / 1024 / 1024, 2) AS 'Espacio Libre (MB)',
    ENGINE AS 'Motor',
    TABLE_COLLATION AS 'Collation'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'subscribers';


-- ============================================================================
-- 6. INFORMACIÓN DE ÍNDICES
-- ============================================================================
-- Muestra todos los índices de la tabla y su cardinalidad
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

SHOW INDEX FROM `subscribers`;


-- ============================================================================
-- 7. LIMPIEZA DE DATOS DUPLICADOS
-- ============================================================================
-- Identifica emails duplicados (no debería haber por el índice UNIQUE)
-- ----------------------------------------------------------------------------

SELECT
    `email`,
    COUNT(*) AS 'duplicados'
FROM `subscribers`
GROUP BY `email`
HAVING COUNT(*) > 1;

-- Si hay duplicados, puedes eliminarlos manteniendo el registro más reciente:
-- ADVERTENCIA: Haz backup antes de ejecutar
-- DESCOMENTA LAS SIGUIENTES LÍNEAS SOLO SI HAY DUPLICADOS:

-- DELETE s1 FROM `subscribers` s1
-- INNER JOIN `subscribers` s2
-- WHERE s1.`id` < s2.`id`
-- AND s1.`email` = s2.`email`;


-- ============================================================================
-- 8. LIMPIEZA DE DATOS INVÁLIDOS
-- ============================================================================
-- Encuentra registros con datos potencialmente inválidos
-- ----------------------------------------------------------------------------

-- Emails vacíos o NULL
SELECT * FROM `subscribers`
WHERE `email` IS NULL OR `email` = '' OR `email` NOT LIKE '%@%.%';

-- Nombres vacíos o NULL
SELECT * FROM `subscribers`
WHERE `name` IS NULL OR `name` = '' OR LENGTH(TRIM(`name`)) < 2;

-- Estados inválidos
SELECT DISTINCT `status` FROM `subscribers`;

-- Si encuentras estados incorrectos, puedes corregirlos:
-- DESCOMENTA Y MODIFICA SEGÚN NECESITES:

-- UPDATE `subscribers`
-- SET `status` = 'active'
-- WHERE `status` NOT IN ('active', 'inactive', 'unsubscribed', 'bounced');


-- ============================================================================
-- 9. LIMPIEZA DE REGISTROS ANTIGUOS
-- ============================================================================
-- Encuentra suscriptores que se dieron de baja hace más de 1 año
-- (podrías querer eliminarlos por políticas de privacidad como GDPR)
-- ----------------------------------------------------------------------------

SELECT
    COUNT(*) AS 'Registros a eliminar',
    MIN(`subscribed_date`) AS 'Más antiguo',
    MAX(`subscribed_date`) AS 'Más reciente'
FROM `subscribers`
WHERE `status` = 'unsubscribed'
AND `subscribed_date` < DATE_SUB(NOW(), INTERVAL 1 YEAR);

-- Para eliminar estos registros (GDPR/privacidad):
-- ADVERTENCIA: Esto es permanente. Haz backup primero.
-- DESCOMENTA SOLO SI ESTÁS SEGURO:

-- DELETE FROM `subscribers`
-- WHERE `status` = 'unsubscribed'
-- AND `subscribed_date` < DATE_SUB(NOW(), INTERVAL 1 YEAR);


-- ============================================================================
-- 10. NORMALIZACIÓN DE DATOS
-- ============================================================================
-- Limpia y normaliza datos existentes
-- SIN PRIVILEGIOS SUPER: Estas consultas son seguras
-- ----------------------------------------------------------------------------

-- Convertir emails a minúsculas
UPDATE `subscribers`
SET `email` = LOWER(`email`)
WHERE `email` != LOWER(`email`);

-- Eliminar espacios en blanco de emails
UPDATE `subscribers`
SET `email` = TRIM(`email`)
WHERE `email` != TRIM(`email`);

-- Capitalizar nombres (primera letra mayúscula)
-- NOTA: Esta consulta solo funciona para nombres simples
UPDATE `subscribers`
SET `name` = CONCAT(UPPER(SUBSTRING(`name`, 1, 1)), LOWER(SUBSTRING(`name`, 2)))
WHERE `name` = UPPER(`name`) OR `name` = LOWER(`name`);


-- ============================================================================
-- 11. BACKUP DE DATOS (CREAR TABLA DE RESPALDO)
-- ============================================================================
-- Crea una copia de seguridad de la tabla con fecha
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

-- IMPORTANTE: Cambia la fecha en el nombre de la tabla
-- Ejemplo: subscribers_backup_20240314

-- CREATE TABLE `subscribers_backup_20240314` LIKE `subscribers`;
-- INSERT INTO `subscribers_backup_20240314` SELECT * FROM `subscribers`;

-- Para verificar el backup:
-- SELECT COUNT(*) FROM `subscribers_backup_20240314`;


-- ============================================================================
-- 12. RESTAURAR DESDE BACKUP
-- ============================================================================
-- Restaura datos desde una tabla de backup
-- ADVERTENCIA: Esto sobrescribirá los datos actuales
-- ----------------------------------------------------------------------------

-- DESCOMENTA Y MODIFICA LA FECHA DEL BACKUP:

-- TRUNCATE TABLE `subscribers`;
-- INSERT INTO `subscribers` SELECT * FROM `subscribers_backup_20240314`;


-- ============================================================================
-- 13. VACIAR TABLA COMPLETAMENTE
-- ============================================================================
-- ADVERTENCIA EXTREMA: Esto eliminará TODOS los datos
-- Solo usa esto si estás absolutamente seguro
-- ----------------------------------------------------------------------------

-- DESCOMENTA SOLO SI QUIERES ELIMINAR TODO:
-- TRUNCATE TABLE `subscribers`;


-- ============================================================================
-- 14. RECONSTRUIR TABLA (ALTERNATIVA A OPTIMIZE)
-- ============================================================================
-- Reconstruye la tabla desde cero para máxima optimización
-- Útil si la tabla está muy fragmentada
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

-- ALTER TABLE `subscribers` ENGINE=InnoDB;


-- ============================================================================
-- 15. ACTUALIZAR ESTADÍSTICAS DE AUTO_INCREMENT
-- ============================================================================
-- Si has eliminado muchos registros y quieres resetear el contador
-- SIN PRIVILEGIOS SUPER: Esta consulta es segura
-- ----------------------------------------------------------------------------

-- Primero verifica el ID máximo actual:
SELECT MAX(`id`) AS 'Máximo ID actual' FROM `subscribers`;

-- Luego resetea el AUTO_INCREMENT al siguiente valor
-- DESCOMENTA Y AJUSTA EL VALOR:
-- ALTER TABLE `subscribers` AUTO_INCREMENT = 1001;


-- ============================================================================
-- 16. VERIFICACIÓN DE RENDIMIENTO
-- ============================================================================
-- Muestra consultas lentas o problemáticas (requiere acceso a logs)
-- ----------------------------------------------------------------------------

-- Mostrar el tamaño de los índices
SELECT
    INDEX_NAME AS 'Índice',
    SEQ_IN_INDEX AS 'Posición',
    COLUMN_NAME AS 'Columna',
    CARDINALITY AS 'Cardinalidad',
    INDEX_TYPE AS 'Tipo'
FROM information_schema.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'subscribers'
ORDER BY INDEX_NAME, SEQ_IN_INDEX;


-- ============================================================================
-- 17. MONITOREO DE CRECIMIENTO
-- ============================================================================
-- Rastrea el crecimiento de la tabla para planificación
-- ----------------------------------------------------------------------------

SELECT
    DATE_FORMAT(`subscribed_date`, '%Y-%m') AS 'Mes',
    COUNT(*) AS 'Nuevos',
    SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(`subscribed_date`, '%Y-%m')) AS 'Total Acumulado',
    AVG(LENGTH(`name`)) AS 'Promedio longitud nombre',
    AVG(LENGTH(`email`)) AS 'Promedio longitud email'
FROM `subscribers`
GROUP BY DATE_FORMAT(`subscribed_date`, '%Y-%m')
ORDER BY `Mes` DESC
LIMIT 12;


-- ============================================================================
-- CHECKLIST DE MANTENIMIENTO
-- ============================================================================
--
-- □ SEMANAL:
--   □ Ejecutar CHECK TABLE
--   □ Verificar datos inválidos (consulta 8)
--   □ Revisar estadísticas (consulta 5)
--
-- □ QUINCENAL:
--   □ Ejecutar ANALYZE TABLE
--   □ Revisar índices (consulta 6)
--
-- □ MENSUAL:
--   □ Ejecutar OPTIMIZE TABLE
--   □ Crear backup (consulta 11)
--   □ Normalizar datos (consulta 10)
--   □ Revisar crecimiento (consulta 17)
--
-- □ TRIMESTRAL:
--   □ Revisar y limpiar datos antiguos (consulta 9)
--   □ Evaluar necesidad de nuevos índices
--
-- □ ANUAL:
--   □ Revisar políticas de retención de datos
--   □ Actualizar documentación
--   □ Auditoría de seguridad
--
-- ============================================================================
-- NOTAS SOBRE PRIVILEGIOS
-- ============================================================================
--
-- OPERACIONES SEGURAS SIN SUPER PRIVILEGE:
-- ✓ ANALYZE TABLE
-- ✓ OPTIMIZE TABLE
-- ✓ CHECK TABLE
-- ✓ REPAIR TABLE
-- ✓ SELECT, INSERT, UPDATE, DELETE
-- ✓ CREATE TABLE, ALTER TABLE, DROP TABLE
-- ✓ CREATE INDEX, DROP INDEX
--
-- OPERACIONES QUE REQUIEREN SUPER PRIVILEGE:
-- ✗ CREATE FUNCTION/PROCEDURE con DETERMINISTIC
-- ✗ CREATE TRIGGER
-- ✗ SET GLOBAL
-- ✗ CHANGE MASTER TO
-- ✗ PURGE BINARY LOGS
--
-- NOTA: Todas las consultas en este script están diseñadas para funcionar
-- sin privilegios SUPER en Dreamhost con binary logging habilitado.
--
-- ============================================================================
