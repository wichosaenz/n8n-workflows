<?php
/**
 * Clase para manejar la conexión a la base de datos PostgreSQL
 * Versión 1.5.0 - Migrado de MySQL a PostgreSQL
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
     * Obtener conexión a la base de datos PostgreSQL
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
            // Puerto por defecto de PostgreSQL
            $port = isset($this->settings['db_port']) ? intval($this->settings['db_port']) : 5432;

            // Construir DSN para PostgreSQL
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;options=\'--client_encoding=UTF8\'',
                $this->settings['db_host'],
                $port,
                $this->settings['db_name']
            );

            // Crear conexión PDO
            $this->connection = new PDO(
                $dsn,
                $this->settings['db_user'],
                $this->settings['db_password'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                )
            );

        } catch (PDOException $e) {
            error_log('WP Subscribers PostgreSQL Error: ' . $e->getMessage());
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

        // Validar email
        if (!is_email($email)) {
            return array('success' => false, 'message' => 'error_invalid_email');
        }

        // Verificar si ya existe
        if ($this->subscriber_exists($email)) {
            return array('success' => false, 'message' => 'duplicate');
        }

        try {
            // Obtener URL del sitio web actual (para múltiples sitios)
            $website_url = esc_url(home_url());
            $table = $this->settings['db_table'];

            $query = "INSERT INTO {$table} (name, email, subscribed_date, ip_address, website_url)
                      VALUES (:name, :email, NOW(), :ip_address, :website_url)";

            $stmt = $connection->prepare($query);
            $stmt->execute(array(
                ':name' => sanitize_text_field($name),
                ':email' => sanitize_email($email),
                ':ip_address' => $_SERVER['REMOTE_ADDR'],
                ':website_url' => $website_url
            ));

            return array('success' => true, 'message' => 'success');

        } catch (PDOException $e) {
            error_log('WP Subscribers Insert Error: ' . $e->getMessage());
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

        try {
            $table = $this->settings['db_table'];
            $query = "SELECT COUNT(*) as count FROM {$table} WHERE email = :email";

            $stmt = $connection->prepare($query);
            $stmt->execute(array(':email' => sanitize_email($email)));

            $row = $stmt->fetch();
            return $row['count'] > 0;

        } catch (PDOException $e) {
            error_log('WP Subscribers Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Crear tabla si no existe
     */
    public function create_table_if_not_exists() {
        $connection = $this->get_connection();

        if (!$connection) {
            return false;
        }

        try {
            $table = $this->settings['db_table'];

            $query = "CREATE TABLE IF NOT EXISTS {$table} (
                id SERIAL PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL UNIQUE,
                subscribed_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                ip_address VARCHAR(45),
                status VARCHAR(20) DEFAULT 'active',
                updated_date TIMESTAMP NULL,
                source VARCHAR(100) NULL,
                website_url VARCHAR(255) NULL,
                notes TEXT NULL
            )";

            $connection->exec($query);

            // Crear índices
            $indexes = array(
                "CREATE INDEX IF NOT EXISTS idx_{$table}_email ON {$table} (email)",
                "CREATE INDEX IF NOT EXISTS idx_{$table}_status ON {$table} (status)",
                "CREATE INDEX IF NOT EXISTS idx_{$table}_subscribed_date ON {$table} (subscribed_date)",
                "CREATE INDEX IF NOT EXISTS idx_{$table}_status_date ON {$table} (status, subscribed_date)",
                "CREATE INDEX IF NOT EXISTS idx_{$table}_website_url ON {$table} (website_url)"
            );

            foreach ($indexes as $index_query) {
                $connection->exec($index_query);
            }

            // Crear trigger para updated_date
            $trigger = "CREATE OR REPLACE FUNCTION update_{$table}_timestamp()
                        RETURNS TRIGGER AS $$
                        BEGIN
                            NEW.updated_date = CURRENT_TIMESTAMP;
                            RETURN NEW;
                        END;
                        $$ LANGUAGE plpgsql;

                        DROP TRIGGER IF EXISTS trigger_update_{$table}_timestamp ON {$table};

                        CREATE TRIGGER trigger_update_{$table}_timestamp
                        BEFORE UPDATE ON {$table}
                        FOR EACH ROW
                        EXECUTE FUNCTION update_{$table}_timestamp();";

            $connection->exec($trigger);

            return true;

        } catch (PDOException $e) {
            error_log('WP Subscribers Create Table Error: ' . $e->getMessage());
            return false;
        }
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

        try {
            // Puerto por defecto de PostgreSQL
            $port = isset($this->settings['db_port']) ? intval($this->settings['db_port']) : 5432;

            // Construir DSN para PostgreSQL
            $dsn = sprintf(
                'pgsql:host=%s;port=%d;dbname=%s;options=\'--client_encoding=UTF8\'',
                $this->settings['db_host'],
                $port,
                $this->settings['db_name']
            );

            // Intentar conexión
            $conn = new PDO(
                $dsn,
                $this->settings['db_user'],
                $this->settings['db_password'],
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );

            // Verificar tabla
            $table = $this->settings['db_table'];
            $check_table = $conn->query("SELECT to_regclass('{$table}')");
            $table_exists = $check_table->fetchColumn() !== null;

            if (!$table_exists) {
                // Crear tabla si no existe
                $this->connection = $conn;
                if ($this->create_table_if_not_exists()) {
                    return array(
                        'success' => true,
                        'user_message' => '✅ Conexión exitosa y tabla creada correctamente',
                        'debug_message' => "Capa 6 (Verificación Tabla): Conexión OK - Tabla '{$table}' creada"
                    );
                } else {
                    return array(
                        'success' => false,
                        'user_message' => "❌ Conexión exitosa pero error al crear tabla. Verifica que el usuario tenga privilegios CREATE TABLE.",
                        'debug_message' => "Capa 6 (Verificación Tabla): Error al crear tabla"
                    );
                }
            }

            return array(
                'success' => true,
                'user_message' => '✅ Conexión exitosa y tabla verificada correctamente',
                'debug_message' => "Capa 6 (Verificación Tabla): Conexión OK - Tabla '{$table}' existe"
            );

        } catch (PDOException $e) {
            $error_code = $e->getCode();
            $error_message = $e->getMessage();

            $user_message = '';
            $debug_message = '';

            // Análisis por capas de error PostgreSQL
            if (strpos($error_message, 'could not translate host name') !== false ||
                strpos($error_message, 'could not connect to server') !== false) {
                // CAPA 1: Red / DNS
                $user_message = "❌ Error de Red/Host: No se pudo contactar al servidor PostgreSQL. Verifica:\n" .
                              "1. Que el 'Host' sea correcto\n" .
                              "2. Que tu IP esté autorizada en el servidor PostgreSQL (pg_hba.conf)\n" .
                              "3. Que el servidor PostgreSQL esté activo\n" .
                              "4. Que el puerto {$this->settings['db_port']} esté abierto";
                $debug_message = "Capa 1 (Red/DNS): $error_message";

            } elseif (strpos($error_message, 'password authentication failed') !== false ||
                      $error_code === '28P01') {
                // CAPA 2: Autenticación
                $user_message = "❌ Error de Autenticación: Usuario o contraseña incorrectos. Verifica:\n" .
                              "1. Que el 'Usuario' sea exacto (sensible a mayúsculas)\n" .
                              "2. Que la 'Contraseña' sea correcta\n" .
                              "3. Que el usuario tenga permisos en PostgreSQL";
                $debug_message = "Capa 2 (Autenticación): $error_message";

            } elseif (strpos($error_message, 'database') !== false && strpos($error_message, 'does not exist') !== false) {
                // CAPA 3: Nombre de Base de Datos
                $user_message = "❌ Error de Base de Datos: La base de datos no existe. Verifica:\n" .
                              "1. Que el 'Nombre de Base de Datos' sea exacto\n" .
                              "2. Que la base de datos exista en PostgreSQL\n" .
                              "3. Que el usuario tenga acceso a esta base de datos";
                $debug_message = "Capa 3 (Nombre BD): $error_message";

            } elseif (strpos($error_message, 'permission denied') !== false) {
                // CAPA 4: Permisos
                $user_message = "❌ Error de Permisos: El usuario no tiene permisos en la base de datos. Verifica:\n" .
                              "1. Que el usuario tenga permisos CONNECT en la base de datos\n" .
                              "2. Que el usuario tenga permisos suficientes (SELECT, INSERT, UPDATE, DELETE, CREATE)";
                $debug_message = "Capa 4 (Permisos BD): $error_message";

            } else {
                // CAPA X: Otros errores
                $user_message = "❌ Error de PostgreSQL inesperado. Revisa el mensaje de debug técnico.";
                $debug_message = "Capa Desconocida: (Code: $error_code) $error_message";
            }

            return array(
                'success' => false,
                'user_message' => $user_message,
                'debug_message' => $debug_message
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

        try {
            $table = $this->settings['db_table'];
            $where_clauses = array();
            $params = array();

            // Filtro por sitio web
            if ($filter === 'current_site' && $website_url) {
                $where_clauses[] = "website_url = :website_url";
                $params[':website_url'] = $website_url;
            }

            // Construir WHERE
            $where_sql = '';
            if (!empty($where_clauses)) {
                $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
            }

            // Query con paginación
            $query = "SELECT id, name, email, subscribed_date, ip_address, status, website_url, source, notes, updated_date
                      FROM {$table}
                      {$where_sql}
                      ORDER BY subscribed_date DESC
                      LIMIT :limit OFFSET :offset";

            $stmt = $connection->prepare($query);

            // Bind parámetros
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            $stmt->execute();
            $subscribers = $stmt->fetchAll();

            // Contar total
            $count_query = "SELECT COUNT(*) as total FROM {$table} {$where_sql}";
            $count_stmt = $connection->prepare($count_query);

            foreach ($params as $key => $value) {
                $count_stmt->bindValue($key, $value);
            }

            $count_stmt->execute();
            $total = $count_stmt->fetchColumn();

            return array(
                'success' => true,
                'data' => $subscribers,
                'total' => $total
            );

        } catch (PDOException $e) {
            error_log('WP Subscribers Get Subscribers Error: ' . $e->getMessage());
            return array('success' => false, 'data' => array());
        }
    }

    /**
     * Obtener estadísticas de suscriptores
     */
    public function get_statistics($website_url = null) {
        $connection = $this->get_connection();

        if (!$connection) {
            return array();
        }

        try {
            $table = $this->settings['db_table'];
            $where = '';
            $params = array();

            if ($website_url) {
                $where = "WHERE website_url = :website_url";
                $params[':website_url'] = $website_url;
            }

            $query = "SELECT
                        COUNT(*) as total,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) as inactive,
                        SUM(CASE WHEN status = 'unsubscribed' THEN 1 ELSE 0 END) as unsubscribed,
                        SUM(CASE WHEN status = 'bounced' THEN 1 ELSE 0 END) as bounced
                      FROM {$table} {$where}";

            $stmt = $connection->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            return $stmt->fetch();

        } catch (PDOException $e) {
            error_log('WP Subscribers Get Statistics Error: ' . $e->getMessage());
            return array();
        }
    }

    /**
     * Actualizar estado de un suscriptor
     */
    public function update_subscriber_status($email, $new_status, $notes = '') {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'message' => 'Error de conexión');
        }

        try {
            $table = $this->settings['db_table'];

            // Preparar actualización de notas
            if (!empty($notes)) {
                $timestamp = current_time('mysql');
                $note_entry = "\n[{$timestamp}] Status cambiado a {$new_status}: {$notes}";

                $query = "UPDATE {$table}
                          SET status = :status,
                              notes = COALESCE(notes, '') || :note
                          WHERE email = :email";

                $stmt = $connection->prepare($query);
                $stmt->execute(array(
                    ':status' => sanitize_text_field($new_status),
                    ':note' => sanitize_textarea_field($note_entry),
                    ':email' => sanitize_email($email)
                ));
            } else {
                $query = "UPDATE {$table}
                          SET status = :status
                          WHERE email = :email";

                $stmt = $connection->prepare($query);
                $stmt->execute(array(
                    ':status' => sanitize_text_field($new_status),
                    ':email' => sanitize_email($email)
                ));
            }

            return array('success' => true, 'message' => 'Estado actualizado correctamente');

        } catch (PDOException $e) {
            error_log('WP Subscribers Update Status Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'Error al actualizar: ' . $e->getMessage());
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

        try {
            $table = $this->settings['db_table'];
            $timestamp = current_time('mysql');

            // Preparar nota de desuscripción
            $unsubscribe_note = "[{$timestamp}] Desuscrito de toda la red";
            if (!empty($reason)) {
                $unsubscribe_note .= " - Razón: {$reason}";
            }

            $query = "UPDATE {$table}
                      SET status = 'unsubscribed',
                          notes = COALESCE(notes, '') || :note
                      WHERE email = :email";

            $stmt = $connection->prepare($query);
            $stmt->execute(array(
                ':note' => sanitize_textarea_field("\n" . $unsubscribe_note),
                ':email' => sanitize_email($email)
            ));

            $affected = $stmt->rowCount();

            return array(
                'success' => true,
                'message' => 'Desuscrito exitosamente de todos los sitios',
                'affected_rows' => $affected
            );

        } catch (PDOException $e) {
            error_log('WP Subscribers Unsubscribe Error: ' . $e->getMessage());
            return array('success' => false, 'message' => 'Error al desuscribir: ' . $e->getMessage());
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

        try {
            $table = $this->settings['db_table'];
            $query = "SELECT * FROM {$table} WHERE email = :email LIMIT 1";

            $stmt = $connection->prepare($query);
            $stmt->execute(array(':email' => sanitize_email($email)));

            $result = $stmt->fetch();
            return $result ? $result : null;

        } catch (PDOException $e) {
            error_log('WP Subscribers Get Subscriber Error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Cerrar conexión
     */
    public function close_connection() {
        $this->connection = null;
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->close_connection();
    }
}
