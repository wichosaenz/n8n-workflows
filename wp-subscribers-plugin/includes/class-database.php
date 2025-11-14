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

        $table = $this->settings['db_table'];
        $query = "INSERT INTO `{$table}` (name, email, subscribed_date, ip_address)
                  VALUES ('{$name}', '{$email}', NOW(), '{$_SERVER['REMOTE_ADDR']}')";

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
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            subscribed_date DATETIME NOT NULL,
            ip_address VARCHAR(45),
            status VARCHAR(20) DEFAULT 'active',
            INDEX idx_email (email),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        return $connection->query($query);
    }

    /**
     * Probar conexión
     */
    public function test_connection() {
        $connection = $this->get_connection();

        if (!$connection) {
            return array('success' => false, 'message' => 'No se pudo conectar a la base de datos');
        }

        // Intentar crear la tabla
        if ($this->create_table_if_not_exists()) {
            return array('success' => true, 'message' => 'Conexión exitosa y tabla verificada');
        } else {
            return array('success' => false, 'message' => 'Conexión exitosa pero no se pudo crear/verificar la tabla');
        }
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
