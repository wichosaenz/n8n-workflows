-- ============================================================================
-- SCRIPT DE DATOS DE EJEMPLO (OPCIONAL)
-- Plugin: WP Subscribers Manager v1.0.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- ADVERTENCIA: Este script es OPCIONAL y solo para fines de prueba.
-- Solo ejecuta este script si deseas tener datos de ejemplo en tu base de datos.
--
-- INSTRUCCIONES:
-- 1. Asegúrate de haber ejecutado primero: 01-crear-tabla-subscribers.sql
-- 2. Copia y pega este script en phpMyAdmin
-- 3. Haz clic en "Continuar" para ejecutar
--
-- NOTA: Los datos insertados son ficticios y solo para pruebas.
-- Puedes eliminarlos después con: DELETE FROM subscribers WHERE source = 'ejemplo';
--
-- ============================================================================

SET NAMES utf8mb4;

-- ============================================================================
-- INSERTAR DATOS DE EJEMPLO
-- ============================================================================

INSERT INTO `subscribers` (`name`, `email`, `subscribed_date`, `ip_address`, `status`, `source`, `notes`)
VALUES
    -- Suscriptores activos
    ('Juan Pérez', 'juan.perez@ejemplo.com', '2024-01-15 10:30:00', '192.168.1.100', 'active', 'ejemplo', 'Suscriptor de ejemplo activo'),
    ('María García', 'maria.garcia@ejemplo.com', '2024-01-20 14:45:00', '192.168.1.101', 'active', 'ejemplo', 'Suscriptor de ejemplo activo'),
    ('Carlos López', 'carlos.lopez@ejemplo.com', '2024-02-05 09:15:00', '192.168.1.102', 'active', 'ejemplo', 'Suscriptor de ejemplo activo'),
    ('Ana Martínez', 'ana.martinez@ejemplo.com', '2024-02-10 16:20:00', '192.168.1.103', 'active', 'ejemplo', 'Suscriptor de ejemplo activo'),
    ('Luis Rodríguez', 'luis.rodriguez@ejemplo.com', '2024-03-01 11:00:00', '192.168.1.104', 'active', 'ejemplo', 'Suscriptor de ejemplo activo'),

    -- Suscriptores inactivos
    ('Pedro Sánchez', 'pedro.sanchez@ejemplo.com', '2024-01-10 08:30:00', '192.168.1.105', 'inactive', 'ejemplo', 'Suscriptor de ejemplo inactivo'),
    ('Laura Fernández', 'laura.fernandez@ejemplo.com', '2024-01-12 12:45:00', '192.168.1.106', 'inactive', 'ejemplo', 'Suscriptor de ejemplo inactivo'),

    -- Suscriptores que se dieron de baja
    ('Miguel Torres', 'miguel.torres@ejemplo.com', '2024-01-05 10:00:00', '192.168.1.107', 'unsubscribed', 'ejemplo', 'Se dio de baja el 2024-02-15'),
    ('Elena Ruiz', 'elena.ruiz@ejemplo.com', '2024-01-08 15:30:00', '192.168.1.108', 'unsubscribed', 'ejemplo', 'Se dio de baja el 2024-02-20'),

    -- Suscriptor con email rebotado
    ('Jorge Díaz', 'jorge.diaz@ejemplo.com', '2024-01-03 09:00:00', '192.168.1.109', 'bounced', 'ejemplo', 'Email rebotó en envío del 2024-02-10')
ON DUPLICATE KEY UPDATE
    -- Si el email ya existe, no hacer nada (evitar duplicados)
    `id` = `id`;

-- ============================================================================
-- VERIFICAR DATOS INSERTADOS
-- ============================================================================

-- Mostrar todos los suscriptores de ejemplo
SELECT * FROM `subscribers` WHERE `source` = 'ejemplo' ORDER BY `subscribed_date` DESC;

-- Contar suscriptores por estado
SELECT
    `status` AS 'Estado',
    COUNT(*) AS 'Cantidad'
FROM `subscribers`
WHERE `source` = 'ejemplo'
GROUP BY `status`
ORDER BY `status`;

-- ============================================================================
-- LIMPIEZA (SOLO SI QUIERES ELIMINAR LOS DATOS DE EJEMPLO)
-- ============================================================================

-- DESCOMENTA Y EJECUTA LA SIGUIENTE LÍNEA PARA ELIMINAR LOS DATOS DE EJEMPLO:
-- DELETE FROM `subscribers` WHERE `source` = 'ejemplo';

-- ============================================================================
-- MENSAJE DE ÉXITO
-- ============================================================================
-- Si llegaste hasta aquí sin errores, ¡los datos de ejemplo se insertaron!
--
-- Ahora puedes:
-- 1. Ver los datos en phpMyAdmin
-- 2. Practicar consultas con el script: 03-consultas-utiles.sql
-- 3. Eliminar estos datos cuando estés listo para usar datos reales
--
-- ============================================================================
