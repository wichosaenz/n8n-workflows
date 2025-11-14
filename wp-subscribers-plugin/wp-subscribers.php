<?php
/**
 * Plugin Name: WP Subscribers Manager
 * Plugin URI: https://www.wichosaenz.com
 * Description: Plugin para gestionar suscriptores con base de datos MySQL personalizada en Dreamhost. Incluye formulario personalizable con soporte multiidioma.
 * Version: 1.0.0
 * Author: Wicho Saenz
 * Author URI: https://www.wichosaenz.com
 * Text Domain: wp-subscribers
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes del plugin
define('WP_SUBSCRIBERS_VERSION', '1.0.0');
define('WP_SUBSCRIBERS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WP_SUBSCRIBERS_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Clase principal del plugin
 */
class WP_Subscribers_Manager {

    /**
     * Instancia única del plugin
     */
    private static $instance = null;

    /**
     * Obtener instancia única
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Cargar dependencias
     */
    private function load_dependencies() {
        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'includes/class-database.php';
        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'includes/class-form-handler.php';
        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'includes/class-shortcode.php';
        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'includes/class-widget.php';
        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'admin/class-admin.php';
    }

    /**
     * Inicializar hooks
     */
    private function init_hooks() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_public_assets'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('widgets_init', array($this, 'register_widgets'));

        // Activación del plugin
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }

    /**
     * Cargar traducciones
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'wp-subscribers',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Encolar assets públicos
     */
    public function enqueue_public_assets() {
        wp_enqueue_style(
            'wp-subscribers-public',
            WP_SUBSCRIBERS_PLUGIN_URL . 'assets/css/public.css',
            array(),
            WP_SUBSCRIBERS_VERSION
        );

        wp_enqueue_script(
            'wp-subscribers-public',
            WP_SUBSCRIBERS_PLUGIN_URL . 'assets/js/public.js',
            array('jquery'),
            WP_SUBSCRIBERS_VERSION,
            true
        );

        // Localizar script
        wp_localize_script('wp-subscribers-public', 'wpSubscribers', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wp_subscribers_nonce')
        ));
    }

    /**
     * Encolar assets de admin
     */
    public function enqueue_admin_assets($hook) {
        if ('toplevel_page_wp-subscribers' !== $hook) {
            return;
        }

        wp_enqueue_style(
            'wp-subscribers-admin',
            WP_SUBSCRIBERS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WP_SUBSCRIBERS_VERSION
        );
    }

    /**
     * Registrar widgets
     */
    public function register_widgets() {
        register_widget('WP_Subscribers_Widget');
    }

    /**
     * Activación del plugin
     */
    public function activate() {
        // Crear opciones por defecto
        $default_options = array(
            'db_host' => '',
            'db_name' => '',
            'db_user' => '',
            'db_password' => '',
            'db_table' => 'subscribers',
            'labels' => array(
                'form_title' => __('Suscríbete a nuestro boletín', 'wp-subscribers'),
                'name_label' => __('Nombre', 'wp-subscribers'),
                'email_label' => __('Correo electrónico', 'wp-subscribers'),
                'submit_button' => __('Suscribirse', 'wp-subscribers'),
                'success_message' => __('¡Gracias por suscribirte!', 'wp-subscribers'),
                'error_message' => __('Hubo un error. Por favor, intenta de nuevo.', 'wp-subscribers'),
                'duplicate_message' => __('Este correo ya está suscrito.', 'wp-subscribers')
            )
        );

        if (!get_option('wp_subscribers_settings')) {
            add_option('wp_subscribers_settings', $default_options);
        }
    }

    /**
     * Desactivación del plugin
     */
    public function deactivate() {
        // Limpiar tareas programadas si las hay
    }
}

/**
 * Iniciar el plugin
 */
function wp_subscribers_init() {
    return WP_Subscribers_Manager::get_instance();
}

// Iniciar el plugin
wp_subscribers_init();
