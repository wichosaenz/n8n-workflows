<?php
/**
 * Clase para manejar el formulario de suscripción
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Form_Handler {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('wp_ajax_wp_subscribers_submit', array($this, 'handle_submission'));
        add_action('wp_ajax_nopriv_wp_subscribers_submit', array($this, 'handle_submission'));
    }

    /**
     * Manejar envío del formulario
     */
    public function handle_submission() {
        // Verificar nonce
        if (!check_ajax_referer('wp_subscribers_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Sesión inválida. Por favor, recarga la página.', 'wp-subscribers')
            ));
        }

        // Obtener datos del formulario
        $name = isset($_POST['name']) ? sanitize_text_field($_POST['name']) : '';
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';

        // Validar campos
        if (empty($name) || empty($email)) {
            wp_send_json_error(array(
                'message' => __('Por favor, completa todos los campos.', 'wp-subscribers')
            ));
        }

        if (!is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Por favor, ingresa un correo electrónico válido.', 'wp-subscribers')
            ));
        }

        // Insertar en la base de datos
        $db = new WP_Subscribers_Database();
        $result = $db->insert_subscriber($name, $email);

        // Obtener configuración de labels
        $settings = get_option('wp_subscribers_settings', array());
        $labels = isset($settings['labels']) ? $settings['labels'] : array();

        if ($result['success']) {
            $message = isset($labels['success_message']) ?
                       $labels['success_message'] :
                       __('¡Gracias por suscribirte!', 'wp-subscribers');

            wp_send_json_success(array('message' => $message));
        } else {
            // Determinar mensaje de error
            switch ($result['message']) {
                case 'duplicate':
                    $message = isset($labels['duplicate_message']) ?
                               $labels['duplicate_message'] :
                               __('Este correo ya está suscrito.', 'wp-subscribers');
                    break;
                case 'error_connection':
                    $message = __('Error de conexión a la base de datos.', 'wp-subscribers');
                    break;
                default:
                    $message = isset($labels['error_message']) ?
                               $labels['error_message'] :
                               __('Hubo un error. Por favor, intenta de nuevo.', 'wp-subscribers');
            }

            wp_send_json_error(array('message' => $message));
        }
    }

    /**
     * Renderizar formulario
     */
    public static function render_form($atts = array()) {
        $settings = get_option('wp_subscribers_settings', array());
        $labels = isset($settings['labels']) ? $settings['labels'] : array();

        // Valores por defecto para labels
        $defaults = array(
            'form_title' => __('Suscríbete a nuestro boletín', 'wp-subscribers'),
            'name_label' => __('Nombre', 'wp-subscribers'),
            'email_label' => __('Correo electrónico', 'wp-subscribers'),
            'submit_button' => __('Suscribirse', 'wp-subscribers')
        );

        $labels = wp_parse_args($labels, $defaults);

        // Atributos personalizados del shortcode
        $atts = shortcode_atts(array(
            'title' => $labels['form_title'],
            'show_title' => 'yes'
        ), $atts);

        ob_start();
        ?>
        <div class="wp-subscribers-form-wrapper">
            <?php if ($atts['show_title'] === 'yes') : ?>
                <h3 class="wp-subscribers-form-title"><?php echo esc_html($atts['title']); ?></h3>
            <?php endif; ?>

            <form class="wp-subscribers-form" method="post">
                <div class="wp-subscribers-field">
                    <label for="wp-subscribers-name">
                        <?php echo esc_html($labels['name_label']); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="text"
                        id="wp-subscribers-name"
                        name="name"
                        class="wp-subscribers-input"
                        required
                    />
                </div>

                <div class="wp-subscribers-field">
                    <label for="wp-subscribers-email">
                        <?php echo esc_html($labels['email_label']); ?>
                        <span class="required">*</span>
                    </label>
                    <input
                        type="email"
                        id="wp-subscribers-email"
                        name="email"
                        class="wp-subscribers-input"
                        required
                    />
                </div>

                <div class="wp-subscribers-submit">
                    <button type="submit" class="wp-subscribers-button">
                        <?php echo esc_html($labels['submit_button']); ?>
                    </button>
                </div>

                <div class="wp-subscribers-message" style="display: none;"></div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializar el manejador de formularios
new WP_Subscribers_Form_Handler();
