-- ============================================================
-- SETUP DATABASE PARA NEWSLETTER MENSUAL AUTOMATIZADO
-- ============================================================
--
-- Este script crea las tablas necesarias para el workflow
-- de newsletter mensual con RAG y envío automatizado
--
-- Ejecutar en tu servidor MySQL antes de activar el workflow
-- ============================================================

-- 1. TABLA DE SUSCRIPTORES
-- ============================================================

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    nombre VARCHAR(255) NOT NULL,
    grupo_investigador ENUM(
        'Distribución Minorista',
        'Industria de Transporte',
        'Inversiones Chinas en México',
        'Sector Autopartes',
        'General'
    ) DEFAULT 'General',
    activo TINYINT(1) DEFAULT 1,
    idioma_preferido ENUM('es', 'en') DEFAULT 'en',
    fecha_suscripcion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    token_desuscripcion VARCHAR(64) UNIQUE,
    metadata JSON,

    INDEX idx_activo (activo),
    INDEX idx_grupo_investigador (grupo_investigador),
    INDEX idx_email (email),
    INDEX idx_token (token_desuscripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 2. TRIGGER PARA GENERAR TOKEN DE DESUSCRIPCIÓN
-- ============================================================

DELIMITER $$

CREATE TRIGGER generate_unsubscribe_token
BEFORE INSERT ON newsletter_subscribers
FOR EACH ROW
BEGIN
    IF NEW.token_desuscripcion IS NULL THEN
        SET NEW.token_desuscripcion = SHA2(CONCAT(NEW.email, UUID(), NOW()), 256);
    END IF;
END$$

DELIMITER ;


-- 3. DATOS DE PRUEBA (OPCIONAL)
-- ============================================================

-- Insertar suscriptores de prueba
-- ¡REEMPLAZA LOS EMAILS CON TUS EMAILS DE PRUEBA!

INSERT INTO newsletter_subscribers (email, nombre, grupo_investigador, idioma_preferido, activo) VALUES
    ('test1@tudominio.com', 'María González', 'Distribución Minorista', 'es', 1),
    ('test2@tudominio.com', 'John Smith', 'Industria de Transporte', 'en', 1),
    ('test3@tudominio.com', 'Carlos Hernández', 'Inversiones Chinas en México', 'es', 1),
    ('test4@tudominio.com', 'Sarah Johnson', 'Sector Autopartes', 'en', 1),
    ('test5@tudominio.com', 'Roberto Pérez', 'General', 'es', 1)
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    grupo_investigador = VALUES(grupo_investigador);


-- 4. TABLA DE HISTORIAL DE NEWSLETTERS (OPCIONAL)
-- ============================================================
-- Esta tabla almacena el historial de newsletters enviados
-- útil para auditoría y re-envíos

CREATE TABLE IF NOT EXISTS newsletter_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fecha_envio DATE NOT NULL,
    titulo VARCHAR(500),
    contenido_html LONGTEXT,
    contenido_texto TEXT,
    imagen_url VARCHAR(1000),
    total_destinatarios INT DEFAULT 0,
    total_enviados INT DEFAULT 0,
    total_fallidos INT DEFAULT 0,
    pinecone_query_params JSON,
    claude_prompt_tokens INT,
    claude_completion_tokens INT,
    metadata JSON,

    INDEX idx_fecha_envio (fecha_envio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 5. TABLA DE LOGS DE ENVÍO (ALTERNATIVA A GOOGLE SHEETS)
-- ============================================================
-- Si prefieres usar MySQL en lugar de Google Sheets para logs

CREATE TABLE IF NOT EXISTS newsletter_envio_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    newsletter_id INT,
    email VARCHAR(255) NOT NULL,
    estado ENUM('Enviado', 'Fallido', 'Pendiente', 'Rebotado') DEFAULT 'Pendiente',
    mensaje_id VARCHAR(500),
    error_mensaje TEXT,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_apertura TIMESTAMP NULL,
    fecha_click TIMESTAMP NULL,

    INDEX idx_newsletter_id (newsletter_id),
    INDEX idx_email (email),
    INDEX idx_estado (estado),
    INDEX idx_fecha_envio (fecha_envio),

    FOREIGN KEY (newsletter_id) REFERENCES newsletter_history(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 6. VISTAS ÚTILES PARA ANÁLISIS
-- ============================================================

-- Vista de suscriptores activos por grupo
CREATE OR REPLACE VIEW v_suscriptores_por_grupo AS
SELECT
    grupo_investigador,
    COUNT(*) as total_suscriptores,
    SUM(CASE WHEN idioma_preferido = 'es' THEN 1 ELSE 0 END) as suscriptores_es,
    SUM(CASE WHEN idioma_preferido = 'en' THEN 1 ELSE 0 END) as suscriptores_en
FROM newsletter_subscribers
WHERE activo = 1
GROUP BY grupo_investigador;

-- Vista de estadísticas de envíos por newsletter
CREATE OR REPLACE VIEW v_stats_newsletters AS
SELECT
    nh.id,
    nh.fecha_envio,
    nh.titulo,
    nh.total_destinatarios,
    COUNT(CASE WHEN nel.estado = 'Enviado' THEN 1 END) as enviados_exitosos,
    COUNT(CASE WHEN nel.estado = 'Fallido' THEN 1 END) as envios_fallidos,
    COUNT(CASE WHEN nel.fecha_apertura IS NOT NULL THEN 1 END) as aperturas,
    COUNT(CASE WHEN nel.fecha_click IS NOT NULL THEN 1 END) as clicks,
    ROUND(COUNT(CASE WHEN nel.fecha_apertura IS NOT NULL THEN 1 END) * 100.0 / COUNT(*), 2) as tasa_apertura,
    ROUND(COUNT(CASE WHEN nel.fecha_click IS NOT NULL THEN 1 END) * 100.0 / COUNT(*), 2) as tasa_click
FROM newsletter_history nh
LEFT JOIN newsletter_envio_logs nel ON nh.id = nel.newsletter_id
GROUP BY nh.id, nh.fecha_envio, nh.titulo, nh.total_destinatarios;


-- 7. STORED PROCEDURES ÚTILES
-- ============================================================

-- Procedimiento para desactivar suscriptor por token
DELIMITER $$

CREATE PROCEDURE sp_desuscribir_por_token(IN p_token VARCHAR(64))
BEGIN
    UPDATE newsletter_subscribers
    SET activo = 0,
        fecha_actualizacion = NOW(),
        metadata = JSON_SET(
            COALESCE(metadata, '{}'),
            '$.fecha_desuscripcion',
            NOW()
        )
    WHERE token_desuscripcion = p_token;

    SELECT ROW_COUNT() as filas_afectadas;
END$$

DELIMITER ;

-- Procedimiento para obtener estadísticas del último mes
DELIMITER $$

CREATE PROCEDURE sp_stats_ultimo_mes()
BEGIN
    SELECT
        DATE_FORMAT(fecha_envio, '%Y-%m') as mes,
        COUNT(*) as newsletters_enviados,
        SUM(total_enviados) as emails_enviados,
        SUM(total_fallidos) as emails_fallidos,
        ROUND(AVG(total_enviados * 100.0 / total_destinatarios), 2) as tasa_exito_promedio
    FROM newsletter_history
    WHERE fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)
    GROUP BY DATE_FORMAT(fecha_envio, '%Y-%m');
END$$

DELIMITER ;


-- 8. FUNCIONES ÚTILES
-- ============================================================

-- Función para validar formato de email
DELIMITER $$

CREATE FUNCTION fn_validar_email(p_email VARCHAR(255))
RETURNS TINYINT(1)
DETERMINISTIC
BEGIN
    RETURN p_email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\\.[A-Za-z]{2,}$';
END$$

DELIMITER ;


-- 9. ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================================

-- Índice compuesto para consultas frecuentes
ALTER TABLE newsletter_subscribers
ADD INDEX idx_activo_grupo (activo, grupo_investigador);

-- Índice fulltext para búsquedas de nombres (opcional)
-- ALTER TABLE newsletter_subscribers
-- ADD FULLTEXT INDEX ft_nombre (nombre);


-- 10. CONFIGURACIÓN DE PERMISOS (OPCIONAL)
-- ============================================================

-- Crear usuario específico para n8n (más seguro)
-- ¡REEMPLAZA 'tu_password_seguro' con una contraseña fuerte!

-- CREATE USER 'n8n_newsletter'@'%' IDENTIFIED BY 'tu_password_seguro';
-- GRANT SELECT, INSERT, UPDATE ON newsletter_subscribers TO 'n8n_newsletter'@'%';
-- GRANT SELECT, INSERT ON newsletter_history TO 'n8n_newsletter'@'%';
-- GRANT SELECT, INSERT ON newsletter_envio_logs TO 'n8n_newsletter'@'%';
-- FLUSH PRIVILEGES;


-- ============================================================
-- FIN DEL SCRIPT DE SETUP
-- ============================================================

-- Para verificar que todo se creó correctamente:
SELECT
    'Suscriptores activos' as tipo,
    COUNT(*) as cantidad
FROM newsletter_subscribers
WHERE activo = 1

UNION ALL

SELECT
    'Newsletters enviados' as tipo,
    COUNT(*) as cantidad
FROM newsletter_history;


-- ============================================================
-- QUERIES ÚTILES PARA MANTENIMIENTO
-- ============================================================

-- Ver suscriptores por grupo
-- SELECT grupo_investigador, COUNT(*)
-- FROM newsletter_subscribers
-- WHERE activo = 1
-- GROUP BY grupo_investigador;

-- Desactivar suscriptores inactivos (sin aperturas en 6 meses)
-- UPDATE newsletter_subscribers ns
-- SET activo = 0
-- WHERE NOT EXISTS (
--     SELECT 1
--     FROM newsletter_envio_logs nel
--     WHERE nel.email = ns.email
--       AND nel.fecha_apertura IS NOT NULL
--       AND nel.fecha_envio >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
-- );

-- Limpiar logs antiguos (> 1 año)
-- DELETE FROM newsletter_envio_logs
-- WHERE fecha_envio < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);
