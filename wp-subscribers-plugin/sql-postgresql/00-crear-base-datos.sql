-- ============================================================================
-- SCRIPT DE CREACIÓN DE BASE DE DATOS Y USUARIO POSTGRESQL
-- Plugin: WP Subscribers Manager v1.5.0
-- Autor: Wicho Saenz (www.wichosaenz.com)
-- ============================================================================
--
-- IMPORTANTE: Este script debe ejecutarse con privilegios de superusuario
-- o con un usuario que tenga permisos CREATEDB y CREATEROLE
--
-- INSTRUCCIONES:
-- 1. Conéctate a PostgreSQL como superusuario:
--    psql -U postgres
--
-- 2. Copia y pega este script completo
--
-- 3. Presiona Enter para ejecutar
--
-- 4. Guarda las credenciales generadas para configurar el plugin
--
-- ============================================================================

-- ============================================================================
-- PASO 1: CREAR BASE DE DATOS
-- ============================================================================

-- Crear la base de datos para suscriptores
CREATE DATABASE wp_subscribers
    WITH
    OWNER = postgres
    ENCODING = 'UTF8'
    LC_COLLATE = 'es_ES.UTF-8'
    LC_CTYPE = 'es_ES.UTF-8'
    TABLESPACE = pg_default
    CONNECTION LIMIT = -1;

COMMENT ON DATABASE wp_subscribers
    IS 'Base de datos para el plugin WP Subscribers Manager';

-- ============================================================================
-- PASO 2: CREAR USUARIO
-- ============================================================================

-- Crear usuario para la aplicación WordPress
-- IMPORTANTE: Cambia 'tu_password_seguro' por una contraseña fuerte
CREATE USER wp_subscribers_user WITH
    LOGIN
    NOSUPERUSER
    NOCREATEDB
    NOCREATEROLE
    INHERIT
    NOREPLICATION
    CONNECTION LIMIT -1
    PASSWORD 'tu_password_seguro';

COMMENT ON ROLE wp_subscribers_user
    IS 'Usuario para el plugin WP Subscribers Manager';

-- ============================================================================
-- PASO 3: OTORGAR PERMISOS A LA BASE DE DATOS
-- ============================================================================

-- Conectar a la base de datos recién creada
\c wp_subscribers

-- Otorgar permisos de conexión
GRANT CONNECT ON DATABASE wp_subscribers TO wp_subscribers_user;

-- Otorgar permisos en el schema public
GRANT USAGE ON SCHEMA public TO wp_subscribers_user;
GRANT CREATE ON SCHEMA public TO wp_subscribers_user;

-- Otorgar permisos para tablas futuras (muy importante)
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT SELECT, INSERT, UPDATE, DELETE ON TABLES TO wp_subscribers_user;

-- Otorgar permisos para secuencias futuras (para SERIAL)
ALTER DEFAULT PRIVILEGES IN SCHEMA public
    GRANT USAGE, SELECT ON SEQUENCES TO wp_subscribers_user;

-- ============================================================================
-- PASO 4: VERIFICAR CREACIÓN
-- ============================================================================

-- Listar bases de datos
\l wp_subscribers

-- Listar usuarios
\du wp_subscribers_user

-- ============================================================================
-- CONFIGURACIÓN DE ACCESO REMOTO (OPCIONAL)
-- ============================================================================
--
-- Si necesitas acceso remoto desde WordPress, debes editar dos archivos:
--
-- 1. postgresql.conf:
--    Encuentra la línea: #listen_addresses = 'localhost'
--    Cámbiala a: listen_addresses = '*'
--    (O especifica IPs permitidas: listen_addresses = '192.168.1.100,10.0.0.50')
--
-- 2. pg_hba.conf:
--    Agrega al final del archivo:
--    # Permitir conexión desde WordPress
--    host    wp_subscribers    wp_subscribers_user    0.0.0.0/0    md5
--
--    Para mayor seguridad, reemplaza 0.0.0.0/0 con la IP de tu servidor WordPress:
--    host    wp_subscribers    wp_subscribers_user    192.168.1.100/32    md5
--
-- 3. Reiniciar PostgreSQL:
--    sudo systemctl restart postgresql
--    (O en Debian/Ubuntu: sudo service postgresql restart)
--
-- ============================================================================
-- CREDENCIALES PARA CONFIGURAR EN WORDPRESS
-- ============================================================================
--
-- Guarda esta información para configurar el plugin:
--
-- Host:       localhost (o la IP de tu servidor PostgreSQL)
-- Puerto:     5432
-- Base de Datos:   wp_subscribers
-- Usuario:    wp_subscribers_user
-- Contraseña: [la que pusiste en PASO 2]
-- Tabla:      subscribers
--
-- ============================================================================
-- PRÓXIMOS PASOS
-- ============================================================================
--
-- 1. Ejecuta el script: 01-crear-tabla-subscribers.sql
-- 2. (OPCIONAL) Ejecuta el script: 02-datos-de-ejemplo.sql
-- 3. Configura el plugin en WordPress con estas credenciales
-- 4. Prueba el formulario de suscripción
--
-- ============================================================================
