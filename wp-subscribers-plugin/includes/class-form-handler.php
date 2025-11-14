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
        add_action('wp_ajax_wp_subscribers_unsubscribe_public', array($this, 'handle_unsubscribe'));
        add_action('wp_ajax_nopriv_wp_subscribers_unsubscribe_public', array($this, 'handle_unsubscribe'));
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

        // Primero verificar si el suscriptor ya existe
        $existing_subscriber = $db->get_subscriber_by_email($email);

        if ($existing_subscriber) {
            // El suscriptor ya existe - mostrar opción de desuscribirse
            wp_send_json(array(
                'success' => false,
                'data' => array(
                    'already_subscribed' => true,
                    'email' => $email,
                    'name' => $name,
                    'status' => $existing_subscriber['status'],
                    'message' => __('Este correo ya está suscrito. Si deseas, puedes desuscribirte de todos los sitios.', 'wp-subscribers')
                )
            ));
        }

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
     * Manejar desuscripción desde el frontend
     */
    public function handle_unsubscribe() {
        // Verificar nonce
        if (!check_ajax_referer('wp_subscribers_nonce', 'nonce', false)) {
            wp_send_json_error(array(
                'message' => __('Sesión inválida. Por favor, recarga la página.', 'wp-subscribers')
            ));
        }

        // Obtener datos
        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        // Validar email
        if (empty($email) || !is_email($email)) {
            wp_send_json_error(array(
                'message' => __('Por favor, proporciona un correo electrónico válido.', 'wp-subscribers')
            ));
        }

        // Desuscribir de todos los sitios
        $db = new WP_Subscribers_Database();
        $result = $db->unsubscribe_from_all_sites($email, $reason);

        if ($result['success']) {
            wp_send_json_success(array(
                'message' => __('Has sido desuscrito exitosamente de todos los sitios. ¡Esperamos verte de nuevo pronto!', 'wp-subscribers'),
                'affected_rows' => $result['affected_rows']
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Hubo un error al procesar tu desuscripción. Por favor, intenta de nuevo.', 'wp-subscribers')
            ));
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

            <!-- Formulario de desuscripción (oculto inicialmente) -->
            <div class="wp-subscribers-unsubscribe-form" style="display: none;">
                <div class="wp-subscribers-unsubscribe-message">
                    <p><strong>⚠️ Ya estás suscrito con este correo electrónico.</strong></p>
                    <p>Si deseas desuscribirte, esto te eliminará de <strong>TODOS los sitios de nuestra red</strong> (aproximadamente 20 sitios web).</p>
                </div>

                <div class="wp-subscribers-field">
                    <label for="wp-subscribers-unsubscribe-reason">
                        <?php _e('¿Por qué deseas desuscribirte? (opcional)', 'wp-subscribers'); ?>
                    </label>
                    <textarea
                        id="wp-subscribers-unsubscribe-reason"
                        name="unsubscribe_reason"
                        class="wp-subscribers-textarea"
                        rows="4"
                        placeholder="Por ejemplo: Recibo demasiados correos, ya no me interesa el contenido, etc."
                    ></textarea>
                </div>

                <div class="wp-subscribers-unsubscribe-actions">
                    <button type="button" class="wp-subscribers-button-danger wp-subscribers-unsubscribe-confirm">
                        <?php _e('Desuscribirme de Todos los Sitios', 'wp-subscribers'); ?>
                    </button>
                    <button type="button" class="wp-subscribers-button-secondary wp-subscribers-unsubscribe-cancel">
                        <?php _e('Cancelar', 'wp-subscribers'); ?>
                    </button>
                </div>

                <div class="wp-subscribers-unsubscribe-result" style="display: none;"></div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

// Inicializar el manejador de formularios
new WP_Subscribers_Form_Handler();
