<?php
/**
 * Clase para manejar la conexión a la base de datos personalizada
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Database {

    /**
     * Conexión a la base de datos
     */
    private $connection = null;

    /**
     * Configuración de la base de datos
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('wp_subscribers_settings', array());
    }

    /**
     * Obtener conexión a la base de datos
     */
    public function get_connection() {
        if ($this->connection !== null) {
            return $this->connection;
        }

        // Validar que existan las credenciales
        if (empty($this->settings['db_host']) ||
            empty($this->settings['db_name']) ||
            empty($this->settings['db_user'])) {
            return false;
        }

        try {
            $this->connection = new mysqli(
                $this->settings['db_host'],
                $this->settings['db_user'],
                $this->settings['db_password'],
                $this->settings['db_name']
            );

            if ($this->connection->connect_error) {
                error_log('WP Subscribers DB Error: ' . $this->connection->connect_error);
                return false;
            }

            $this->connection->set_charset('utf8mb4');

        } catch (Exception $e) {
            error_log('WP Subscribers DB Exception: ' . $e->getMessage());
            return false;
        }

        return $this->connection;
    }

    /**
     * Insertar suscriptor
     */
    public function insert_subscriber($name, $email) {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'message' => 'error_connection');
        }

        // Sanitizar datos
        $name = $connection->real_escape_string(sanitize_text_field($name));
        $email = $connection->real_escape_string(sanitize_email($email));

        // Validar email
        if (!is_email($email)) {
            return array('success' => false, 'message' => 'error_invalid_email');
        }

        // Verificar si ya existe
        if ($this->subscriber_exists($email)) {
            return array('success' => false, 'message' => 'duplicate');
        }

        // Obtener URL del sitio web actual (para múltiples sitios)
        $website_url = $connection->real_escape_string(esc_url(home_url()));

        $table = $this->settings['db_table'];
        $query = "INSERT INTO `{$table}` (name, email, subscribed_date, ip_address, website_url)
                  VALUES ('{$name}', '{$email}', NOW(), '{$_SERVER['REMOTE_ADDR']}', '{$website_url}')";

        if ($connection->query($query)) {
            return array('success' => true, 'message' => 'success');
        } else {
            error_log('WP Subscribers Insert Error: ' . $connection->error);
            return array('success' => false, 'message' => 'error_database');
        }
    }

    /**
     * Verificar si un suscriptor ya existe
     */
    public function subscriber_exists($email) {
        $connection = $this->get_connection();

        if (!$connection) {
            return false;
        }

        $email = $connection->real_escape_string(sanitize_email($email));
        $table = $this->settings['db_table'];

        $query = "SELECT COUNT(*) as count FROM `{$table}` WHERE email = '{$email}'";
        $result = $connection->query($query);

        if ($result) {
            $row = $result->fetch_assoc();
            return $row['count'] > 0;
        }

        return false;
    }

    /**
     * Crear tabla si no existe
     */
    public function create_table_if_not_exists() {
        $connection = $this->get_connection();

        if (!$connection) {
            return false;
        }

        $table = $this->settings['db_table'];

        $query = "CREATE TABLE IF NOT EXISTS `{$table}` (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            subscribed_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            status VARCHAR(20) DEFAULT 'active',
            updated_date DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            source VARCHAR(100) NULL,
            website_url VARCHAR(255) NULL COMMENT 'URL del sitio WordPress (para múltiples sitios)',
            notes TEXT NULL,
            INDEX idx_email (email),
            INDEX idx_status (status),
            INDEX idx_subscribed_date (subscribed_date),
            INDEX idx_status_date (status, subscribed_date),
            INDEX idx_website_url (website_url(100))
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $connection->query($query);
    }

    /**
     * Probar conexión con debug detallado por capas
     */
    public function test_connection() {
        // Validar que existan las credenciales
        if (empty($this->settings['db_host']) ||
            empty($this->settings['db_name']) ||
            empty($this->settings['db_user'])) {
            return array(
                'success' => false,
                'user_message' => 'Faltan configuraciones: Por favor completa todos los campos (Host, Nombre BD, Usuario).',
                'debug_message' => 'Capa 0 (Validación): Configuración incompleta - host, nombre BD o usuario vacíos'
            );
        }

        // Intentar conexión con manejo de errores por capas
        try {
            $conn = new mysqli(
                $this->settings['db_host'],
                $this->settings['db_user'],
                $this->settings['db_password'],
                $this->settings['db_name']
            );

            // Verificar si hay error de conexión
            if ($conn->connect_error) {
                $codigo_error = $conn->connect_errno;
                $mensaje_error_nativo = $conn->connect_error;

                $user_message = '';
                $debug_message = '';

                // Análisis por capas de error
                switch ($codigo_error) {
                    // CAPA 1: Red / Firewall / DNS
                    case 2002: // Can't connect to server
                    case 2003: // Can't connect to MySQL server
                    case 2005: // Unknown MySQL server host
                    case 2006: // MySQL server has gone away
                        $user_message = "❌ Error de Red/Host: No se pudo contactar al servidor MySQL. Verifica:\n" .
                                      "1. Que el 'Host' sea correcto (ej: mysql.tudominio.dreamhosters.com)\n" .
                                      "2. Que tu IP esté autorizada en DreamHost (Panel > MySQL > Hostnames Allowed)\n" .
                                      "3. Que el servidor MySQL esté activo";
                        $debug_message = "Capa 1 (Red/Firewall/DNS): (Err: $codigo_error) $mensaje_error_nativo";
                        break;

                    // CAPA 2: Autenticación
                    case 1045: // Access denied for user
                        $user_message = "❌ Error de Autenticación: Usuario o contraseña incorrectos. Verifica:\n" .
                                      "1. Que el 'Usuario' sea exacto (sensible a mayúsculas)\n" .
                                      "2. Que la 'Contraseña' sea correcta\n" .
                                      "3. Que el usuario tenga permisos en DreamHost";
                        $debug_message = "Capa 2 (Autenticación): (Err: $codigo_error) $mensaje_error_nativo";
                        break;

                    // CAPA 3: Nombre de Base de Datos
                    case 1049: // Unknown database
                        $user_message = "❌ Error de Base de Datos: La base de datos no existe. Verifica:\n" .
                                      "1. Que el 'Nombre de Base de Datos' sea exacto\n" .
                                      "2. Que la base de datos exista en DreamHost (Panel > MySQL > Databases)\n" .
                                      "3. Que el usuario tenga acceso a esta base de datos";
                        $debug_message = "Capa 3 (Nombre BD): (Err: $codigo_error) $mensaje_error_nativo";
                        break;

                    // CAPA 4: Permisos
                    case 1044: // Access denied for user to database
                        $user_message = "❌ Error de Permisos: El usuario no tiene permisos en la base de datos. Verifica:\n" .
                                      "1. Que el usuario esté asignado a esta base de datos en DreamHost\n" .
                                      "2. Que el usuario tenga permisos suficientes (SELECT, INSERT, UPDATE, DELETE, CREATE)";
                        $debug_message = "Capa 4 (Permisos BD): (Err: $codigo_error) $mensaje_error_nativo";
                        break;

                    // CAPA 5: Timeout / Sobrecarga
                    case 2013: // Lost connection to MySQL server
                        $user_message = "❌ Error de Timeout: Se perdió la conexión con el servidor. Posibles causas:\n" .
                                      "1. El servidor está sobrecargado\n" .
                                      "2. Timeout de conexión muy corto\n" .
                                      "3. Problemas de red intermitentes";
                        $debug_message = "Capa 5 (Timeout/Sobrecarga): (Err: $codigo_error) $mensaje_error_nativo";
                        break;

                    // CAPA X: Otros errores no categorizados
                    default:
                        $user_message = "❌ Error de MySQL inesperado. Revisa el mensaje de debug técnico o contacta a soporte de DreamHost.";
                        $debug_message = "Capa Desconocida (Default): (Err: $codigo_error) $mensaje_error_nativo";
                }

                return array(
                    'success' => false,
                    'user_message' => $user_message,
                    'debug_message' => $debug_message
                );
            }

            // Conexión exitosa - Configurar charset
            $conn->set_charset('utf8mb4');

            // CAPA 6: Verificación de tabla
            $table = $this->settings['db_table'];
            $query = "CREATE TABLE IF NOT EXISTS `{$table}` (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                subscribed_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                status VARCHAR(20) DEFAULT 'active',
                updated_date DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                source VARCHAR(100) NULL,
                website_url VARCHAR(255) NULL COMMENT 'URL del sitio WordPress (para múltiples sitios)',
                notes TEXT NULL,
                INDEX idx_email (email),
                INDEX idx_status (status),
                INDEX idx_subscribed_date (subscribed_date),
                INDEX idx_status_date (status, subscribed_date),
                INDEX idx_website_url (website_url(100))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

            if ($conn->query($query)) {
                $conn->close();
                return array(
                    'success' => true,
                    'user_message' => '✅ Conexión exitosa y tabla verificada correctamente',
                    'debug_message' => "Capa 6 (Verificación Tabla): Conexión OK - Tabla '{$table}' verificada/creada"
                );
            } else {
                $error_tabla = $conn->error;
                $conn->close();
                return array(
                    'success' => false,
                    'user_message' => "❌ Conexión exitosa pero error al crear/verificar tabla. Puede que el usuario no tenga privilegios CREATE TABLE.",
                    'debug_message' => "Capa 6 (Verificación Tabla): Error al crear tabla: $error_tabla"
                );
            }

        } catch (Exception $e) {
            return array(
                'success' => false,
                'user_message' => '❌ Excepción de PHP al intentar conectar. Revisa los logs del servidor.',
                'debug_message' => 'Capa Excepción (PHP): ' . $e->getMessage()
            );
        }
    }

    /**
     * Obtener lista de suscriptores con filtros
     */
    public function get_subscribers($filter = 'all', $website_url = null, $limit = 100, $offset = 0) {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'data' => array());
        }

        $table = $this->settings['db_table'];
        $where_clauses = array();

        // Filtro por sitio web
        if ($filter === 'current_site' && $website_url) {
            $website_url_escaped = $connection->real_escape_string($website_url);
            $where_clauses[] = "website_url = '{$website_url_escaped}'";
        }

        // Construir WHERE
        $where_sql = '';
        if (!empty($where_clauses)) {
            $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
        }

        // Query con paginación
        $query = "SELECT id, name, email, subscribed_date, ip_address, status, website_url, source, notes, updated_date
                  FROM `{$table}`
                  {$where_sql}
                  ORDER BY subscribed_date DESC
                  LIMIT {$limit} OFFSET {$offset}";

        $result = $connection->query($query);

        if (!$result) {
            return array('success' => false, 'data' => array());
        }

        $subscribers = array();
        while ($row = $result->fetch_assoc()) {
            $subscribers[] = $row;
        }

        // Contar total
        $count_query = "SELECT COUNT(*) as total FROM `{$table}` {$where_sql}";
        $count_result = $connection->query($count_query);
        $total = $count_result ? $count_result->fetch_assoc()['total'] : 0;

        return array(
            'success' => true,
            'data' => $subscribers,
            'total' => $total
        );
    }

    /**
     * Obtener estadísticas de suscriptores
     */
    public function get_statistics($website_url = null) {
        $connection = $this->get_connection();

        if (!$connection) {
            return array();
        }

        $table = $this->settings['db_table'];
        $where = '';

        if ($website_url) {
            $website_url_escaped = $connection->real_escape_string($website_url);
            $where = "WHERE website_url = '{$website_url_escaped}'";
        }

        $query = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
                    SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
                  FROM `{$table}` {$where}";

        $result = $connection->query($query);

        if ($result) {
            return $result->fetch_assoc();
        }

        return array();
    }

    /**
     * Actualizar estado de un suscriptor
     */
    public function update_subscriber_status($email, $new_status, $notes = '') {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'message' => 'Error de conexión');
        }

        $email = $connection->real_escape_string(sanitize_email($email));
        $new_status = $connection->real_escape_string(sanitize_text_field($new_status));
        $notes = $connection->real_escape_string(sanitize_textarea_field($notes));

        $table = $this->settings['db_table'];

        // Preparar actualización de notas
        $notes_update = '';
        if (!empty($notes)) {
            $notes_update = ", notes = CONCAT(COALESCE(notes, ''), '\n[" . current_time('mysql') . "] Status cambiado a {$new_status}: {$notes}')";
        }

        $query = "UPDATE `{$table}`
                  SET status = '{$new_status}'{$notes_update}
                  WHERE email = '{$email}'";

        if ($connection->query($query)) {
            return array('success' => true, 'message' => 'Estado actualizado correctamente');
        } else {
            return array('success' => false, 'message' => 'Error al actualizar: ' . $connection->error);
        }
    }

    /**
     * Desuscribir de TODOS los sitios web (red completa)
     */
    public function unsubscribe_from_all_sites($email, $reason = '') {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'message' => 'Error de conexión');
        }

        $email = $connection->real_escape_string(sanitize_email($email));
        $reason = $connection->real_escape_string(sanitize_textarea_field($reason));
        $table = $this->settings['db_table'];

        // Preparar nota de desuscripción
        $unsubscribe_note = "[" . current_time('mysql') . "] Desuscrito de toda la red";
        if (!empty($reason)) {
            $unsubscribe_note .= " - Razón: {$reason}";
        }

        $query = "UPDATE `{$table}`
                  SET status = 'unsubscribed',
                      notes = CONCAT(COALESCE(notes, ''), '\n{$unsubscribe_note}')
                  WHERE email = '{$email}'";

        if ($connection->query($query)) {
            $affected = $connection->affected_rows;
            return array(
                'success' => true,
                'message' => 'Desuscrito exitosamente de todos los sitios',
                'affected_rows' => $affected
            );
        } else {
            return array('success' => false, 'message' => 'Error al desuscribir: ' . $connection->error);
        }
    }

    /**
     * Verificar si un email ya existe y obtener su estado
     */
    public function get_subscriber_by_email($email) {
        $connection = $this->get_connection();

        if (!$connection) {
            return null;
        }

        $email = $connection->real_escape_string(sanitize_email($email));
        $table = $this->settings['db_table'];

        $query = "SELECT * FROM `{$table}` WHERE email = '{$email}' LIMIT 1";
        $result = $connection->query($query);

        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }

        return null;
    }

    /**
     * Cerrar conexión
     */
    public function close_connection() {
        if ($this->connection !== null) {
            $this->connection->close();
            $this->connection = null;
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close_connection();
    }
}
