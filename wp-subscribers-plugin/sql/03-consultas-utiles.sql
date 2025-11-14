-- ============================================================================
-- CONSULTAS ÚTILES PARA GESTIÓN DE SUSCRIPTORES
-- Plugin: WP Subscribers Manager v1.2.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- Este archivo contiene consultas SQL útiles para gestionar y analizar
-- tus suscriptores. Puedes copiar y ejecutar cualquiera de estas consultas
-- en phpMyAdmin.
--
-- INSTRUCCIONES:
-- 1. Copia la consulta que necesites
-- 2. Pégala en la pestaña "SQL" de phpMyAdmin
-- 3. Haz clic en "Continuar"
--
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- CONSULTAS DE LISTADO
-- ============================================================================

-- 1. VER TODOS LOS SUSCRIPTORES ACTIVOS
-- ----------------------------------------------------------------------------
SELECT
    `id` AS 'ID',
    `name` AS 'Nombre',
    `email` AS 'Email',
    DATE_FORMAT(`subscribed_date`, '%d/%m/%Y %H:%i') AS 'Fecha de Suscripción',
    `source` AS 'Origen'
FROM `subscribers`
WHERE `status` = 'active'
ORDER BY `subscribed_date` DESC;


-- 2. VER ÚLTIMOS 50 SUSCRIPTORES
-- ----------------------------------------------------------------------------
SELECT
    `id`,
    `name`,
    `email`,
    `status`,
    DATE_FORMAT(`subscribed_date`, '%d/%m/%Y %H:%i') AS 'fecha_suscripcion'
FROM `subscribers`
ORDER BY `subscribed_date` DESC
LIMIT 50;


-- 3. BUSCAR SUSCRIPTOR POR EMAIL
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'correo@ejemplo.com' con el email que buscas
SELECT * FROM `subscribers`
WHERE `email` = 'correo@ejemplo.com';


-- 4. BUSCAR SUSCRIPTORES POR NOMBRE (coincidencia parcial)
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'Juan' con el nombre que buscas
SELECT
    `id`,
    `name`,
    `email`,
    `status`,
    DATE_FORMAT(`subscribed_date`, '%d/%m/%Y') AS 'fecha'
FROM `subscribers`
WHERE `name` LIKE '%Juan%'
ORDER BY `name`;


-- ============================================================================
-- CONSULTAS DE ESTADÍSTICAS
-- ============================================================================

-- 5. CONTAR TOTAL DE SUSCRIPTORES
-- ----------------------------------------------------------------------------
SELECT COUNT(*) AS 'Total de Suscriptores' FROM `subscribers`;


-- 6. CONTAR SUSCRIPTORES POR ESTADO
-- ----------------------------------------------------------------------------
SELECT
    `status` AS 'Estado',
    COUNT(*) AS 'Cantidad',
    CONCAT(ROUND((COUNT(*) * 100.0 / (SELECT COUNT(*) FROM `subscribers`)), 2), '%') AS 'Porcentaje'
FROM `subscribers`
GROUP BY `status`
ORDER BY COUNT(*) DESC;


-- 7. SUSCRIPTORES POR MES
-- ----------------------------------------------------------------------------
SELECT
    DATE_FORMAT(`subscribed_date`, '%Y-%m') AS 'Mes',
    COUNT(*) AS 'Nuevos Suscriptores'
FROM `subscribers`
GROUP BY DATE_FORMAT(`subscribed_date`, '%Y-%m')
ORDER BY `Mes` DESC
LIMIT 12;


-- 8. SUSCRIPTORES POR ORIGEN
-- ----------------------------------------------------------------------------
SELECT
    COALESCE(`source`, 'No especificado') AS 'Origen',
    COUNT(*) AS 'Cantidad'
FROM `subscribers`
GROUP BY `source`
ORDER BY COUNT(*) DESC;


-- 9. ESTADÍSTICAS GENERALES
-- ----------------------------------------------------------------------------
SELECT
    (SELECT COUNT(*) FROM `subscribers`) AS 'Total',
    (SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'active') AS 'Activos',
    (SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'inactive') AS 'Inactivos',
    (SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'unsubscribed') AS 'Dados de Baja',
    (SELECT COUNT(*) FROM `subscribers` WHERE `status` = 'bounced') AS 'Rebotados',
    (SELECT COUNT(*) FROM `subscribers` WHERE DATE(`subscribed_date`) = CURDATE()) AS 'Hoy',
    (SELECT COUNT(*) FROM `subscribers` WHERE DATE(`subscribed_date`) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) AS 'Últimos 7 días',
    (SELECT COUNT(*) FROM `subscribers` WHERE DATE(`subscribed_date`) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) AS 'Últimos 30 días';


-- ============================================================================
-- CONSULTAS POR SITIO WEB (NUEVO EN v1.2.0)
-- ============================================================================

-- 9A. SUSCRIPTORES POR SITIO WEB
-- ----------------------------------------------------------------------------
SELECT
    COALESCE(`website_url`, 'No especificado') AS 'Sitio Web',
    COUNT(*) AS 'Total Suscriptores',
    SUM(CASE WHEN `status` = 'active' THEN 1 ELSE 0 END) AS 'Activos',
    SUM(CASE WHEN `status` = 'inactive' THEN 1 ELSE 0 END) AS 'Inactivos'
FROM `subscribers`
GROUP BY `website_url`
ORDER BY COUNT(*) DESC;


-- 9B. VER SUSCRIPTORES DE UN SITIO WEB ESPECÍFICO
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'https://tusitio.com' con la URL de tu sitio
SELECT
    `name`,
    `email`,
    `status`,
    DATE_FORMAT(`subscribed_date`, '%d/%m/%Y') AS 'fecha'
FROM `subscribers`
WHERE `website_url` = 'https://tusitio.com'
ORDER BY `subscribed_date` DESC;


-- 9C. ESTADÍSTICAS POR SITIO WEB Y ESTADO
-- ----------------------------------------------------------------------------
SELECT
    COALESCE(`website_url`, 'No especificado') AS 'Sitio',
    `status` AS 'Estado',
    COUNT(*) AS 'Cantidad'
FROM `subscribers`
GROUP BY `website_url`, `status`
ORDER BY `website_url`, `status`;


-- 9D. SITIOS WEB CON MÁS SUSCRIPTORES ACTIVOS
-- ----------------------------------------------------------------------------
SELECT
    `website_url` AS 'Sitio Web',
    COUNT(*) AS 'Suscriptores Activos'
FROM `subscribers`
WHERE `status` = 'active'
AND `website_url` IS NOT NULL
GROUP BY `website_url`
ORDER BY COUNT(*) DESC
LIMIT 10;


-- ============================================================================
-- CONSULTAS DE EXPORTACIÓN
-- ============================================================================

-- 10. EXPORTAR LISTA DE EMAILS ACTIVOS (para newsletter)
-- ----------------------------------------------------------------------------
SELECT `email`
FROM `subscribers`
WHERE `status` = 'active'
ORDER BY `email`;


-- 11. EXPORTAR LISTA COMPLETA CON NOMBRE Y EMAIL
-- ----------------------------------------------------------------------------
SELECT
    `name` AS 'Nombre',
    `email` AS 'Email'
FROM `subscribers`
WHERE `status` = 'active'
ORDER BY `name`;


-- 12. EXPORTAR DATOS COMPLETOS PARA BACKUP
-- ----------------------------------------------------------------------------
SELECT
    `id`,
    `name`,
    `email`,
    `subscribed_date`,
    `ip_address`,
    `status`,
    `source`,
    `notes`
FROM `subscribers`
ORDER BY `id`;


-- ============================================================================
-- CONSULTAS DE MODIFICACIÓN
-- ============================================================================

-- 13. CAMBIAR ESTADO DE UN SUSCRIPTOR A INACTIVO
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'correo@ejemplo.com' con el email del suscriptor
-- UPDATE `subscribers`
-- SET `status` = 'inactive'
-- WHERE `email` = 'correo@ejemplo.com';


-- 14. MARCAR SUSCRIPTOR COMO DADO DE BAJA
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'correo@ejemplo.com' con el email del suscriptor
-- UPDATE `subscribers`
-- SET `status` = 'unsubscribed'
-- WHERE `email` = 'correo@ejemplo.com';


-- 15. REACTIVAR UN SUSCRIPTOR
-- ----------------------------------------------------------------------------
-- REEMPLAZA 'correo@ejemplo.com' con el email del suscriptor
-- UPDATE `subscribers`
-- SET `status` = 'active'
-- WHERE `email` = 'correo@ejemplo.com';


-- 16. AGREGAR NOTA A UN SUSCRIPTOR
-- ----------------------------------------------------------------------------
-- REEMPLAZA los valores según necesites
-- UPDATE `subscribers`
-- SET `notes` = 'Tu nota aquí'
-- WHERE `email` = 'correo@ejemplo.com';


-- 17. CAMBIAR ESTADO DE MÚLTIPLES SUSCRIPTORES
-- ----------------------------------------------------------------------------
-- Ejemplo: Marcar como inactivos todos los suscriptores sin actividad
-- UPDATE `subscribers`
-- SET `status` = 'inactive'
-- WHERE `status` = 'active'
-- AND `subscribed_date` < DATE_SUB(NOW(), INTERVAL 1 YEAR);


-- ============================================================================
-- CONSULTAS DE LIMPIEZA
-- ============================================================================

-- 18. ELIMINAR SUSCRIPTORES DUPLICADOS (mantener el más reciente)
-- ----------------------------------------------------------------------------
-- ADVERTENCIA: Esto eliminará registros. Haz un backup primero.
-- DELETE s1 FROM `subscribers` s1
-- INNER JOIN `subscribers` s2
-- WHERE s1.`id` < s2.`id`
-- AND s1.`email` = s2.`email`;


-- 19. ELIMINAR SUSCRIPTORES CON EMAIL REBOTADO
-- ----------------------------------------------------------------------------
-- ADVERTENCIA: Esto eliminará registros permanentemente.
-- DELETE FROM `subscribers`
-- WHERE `status` = 'bounced';


-- 20. ELIMINAR SUSCRIPTOR POR EMAIL
-- ----------------------------------------------------------------------------
-- ADVERTENCIA: Esto eliminará el registro permanentemente.
-- REEMPLAZA 'correo@ejemplo.com' con el email a eliminar
-- DELETE FROM `subscribers`
-- WHERE `email` = 'correo@ejemplo.com';


-- ============================================================================
-- CONSULTAS AVANZADAS
-- ============================================================================

-- 21. ENCONTRAR POSIBLES DUPLICADOS POR NOMBRE
-- ----------------------------------------------------------------------------
SELECT
    `name`,
    COUNT(*) AS 'veces_repetido',
    GROUP_CONCAT(`email` SEPARATOR ', ') AS 'emails'
FROM `subscribers`
GROUP BY `name`
HAVING COUNT(*) > 1
ORDER BY COUNT(*) DESC;


-- 22. SUSCRIPTORES MÁS RECIENTES (últimas 24 horas)
-- ----------------------------------------------------------------------------
SELECT
    `name`,
    `email`,
    DATE_FORMAT(`subscribed_date`, '%d/%m/%Y %H:%i:%s') AS 'fecha_hora',
    `ip_address`,
    `source`
FROM `subscribers`
WHERE `subscribed_date` >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY `subscribed_date` DESC;


-- 23. DOMINIOS DE EMAIL MÁS COMUNES
-- ----------------------------------------------------------------------------
SELECT
    SUBSTRING_INDEX(`email`, '@', -1) AS 'dominio',
    COUNT(*) AS 'cantidad'
FROM `subscribers`
GROUP BY SUBSTRING_INDEX(`email`, '@', -1)
ORDER BY COUNT(*) DESC
LIMIT 10;


-- 24. TASA DE CRECIMIENTO MENSUAL
-- ----------------------------------------------------------------------------
SELECT
    DATE_FORMAT(`subscribed_date`, '%Y-%m') AS 'mes',
    COUNT(*) AS 'nuevos_suscriptores',
    SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(`subscribed_date`, '%Y-%m')) AS 'total_acumulado'
FROM `subscribers`
GROUP BY DATE_FORMAT(`subscribed_date`, '%Y-%m')
ORDER BY `mes` DESC
LIMIT 12;


-- 25. VERIFICAR INTEGRIDAD DE LA TABLA
-- ----------------------------------------------------------------------------
SELECT
    'Total de registros' AS 'Verificación',
    COUNT(*) AS 'Resultado'
FROM `subscribers`
UNION ALL
SELECT
    'Emails únicos',
    COUNT(DISTINCT `email`)
FROM `subscribers`
UNION ALL
SELECT
    'Registros con email NULL',
    COUNT(*)
FROM `subscribers`
WHERE `email` IS NULL OR `email` = ''
UNION ALL
SELECT
    'Registros con nombre NULL',
    COUNT(*)
FROM `subscribers`
WHERE `name` IS NULL OR `name` = '';


-- ============================================================================
-- NOTAS IMPORTANTES
-- ============================================================================
--
-- 1. Las consultas con comentarios (--) están deshabilitadas por seguridad.
--    Elimina el -- al inicio de la línea para activarlas.
--
-- 2. Las consultas UPDATE y DELETE son peligrosas. Siempre:
--    - Haz un backup antes de ejecutarlas
--    - Pruébalas primero con SELECT para verificar qué registros afectarán
--    - Verifica con WHERE que estás afectando los registros correctos
--
-- 3. Para exportar resultados en phpMyAdmin:
--    - Ejecuta la consulta
--    - Haz clic en "Exportar" en los resultados
--    - Elige el formato (CSV, Excel, etc.)
--
-- 4. Guarda las consultas que uses frecuentemente como "favoritas" en phpMyAdmin
--
-- ============================================================================
