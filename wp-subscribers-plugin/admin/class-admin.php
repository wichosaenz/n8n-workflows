<?php
/**
 * Clase para manejar el panel de administración
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Admin {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_wp_subscribers_test_connection', array($this, 'test_connection'));
    }

    /**
     * Agregar menú de administración
     */
    public function add_admin_menu() {
        add_menu_page(
            __('WP Subscribers', 'wp-subscribers'),
            __('Suscriptores', 'wp-subscribers'),
            'manage_options',
            'wp-subscribers',
            array($this, 'render_admin_page'),
            'dashicons-email-alt',
            30
        );
    }

    /**
     * Registrar configuraciones
     */
    public function register_settings() {
        register_setting('wp_subscribers_settings', 'wp_subscribers_settings', array(
            'sanitize_callback' => array($this, 'sanitize_settings')
        ));
    }

    /**
     * Sanitizar configuraciones
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        // Sanitizar configuración de base de datos
        $sanitized['db_host'] = isset($input['db_host']) ? sanitize_text_field($input['db_host']) : '';
        $sanitized['db_name'] = isset($input['db_name']) ? sanitize_text_field($input['db_name']) : '';
        $sanitized['db_user'] = isset($input['db_user']) ? sanitize_text_field($input['db_user']) : '';
        $sanitized['db_password'] = isset($input['db_password']) ? $input['db_password'] : '';
        $sanitized['db_table'] = isset($input['db_table']) ? sanitize_text_field($input['db_table']) : 'subscribers';

        // Sanitizar labels
        $sanitized['labels'] = array(
            'form_title' => isset($input['labels']['form_title']) ? sanitize_text_field($input['labels']['form_title']) : '',
            'name_label' => isset($input['labels']['name_label']) ? sanitize_text_field($input['labels']['name_label']) : '',
            'email_label' => isset($input['labels']['email_label']) ? sanitize_text_field($input['labels']['email_label']) : '',
            'submit_button' => isset($input['labels']['submit_button']) ? sanitize_text_field($input['labels']['submit_button']) : '',
            'success_message' => isset($input['labels']['success_message']) ? sanitize_text_field($input['labels']['success_message']) : '',
            'error_message' => isset($input['labels']['error_message']) ? sanitize_text_field($input['labels']['error_message']) : '',
            'duplicate_message' => isset($input['labels']['duplicate_message']) ? sanitize_text_field($input['labels']['duplicate_message']) : ''
        );

        return $sanitized;
    }

    /**
     * Probar conexión a la base de datos
     */
    public function test_connection() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $db = new WP_Subscribers_Database();
        $result = $db->test_connection();

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Renderizar página de administración
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Guardar configuración
        if (isset($_POST['wp_subscribers_settings_submit'])) {
            check_admin_referer('wp_subscribers_settings_update', 'wp_subscribers_settings_nonce');

            $settings = array(
                'db_host' => sanitize_text_field($_POST['db_host']),
                'db_name' => sanitize_text_field($_POST['db_name']),
                'db_user' => sanitize_text_field($_POST['db_user']),
                'db_password' => $_POST['db_password'],
                'db_table' => sanitize_text_field($_POST['db_table']),
                'labels' => array(
                    'form_title' => sanitize_text_field($_POST['labels']['form_title']),
                    'name_label' => sanitize_text_field($_POST['labels']['name_label']),
                    'email_label' => sanitize_text_field($_POST['labels']['email_label']),
                    'submit_button' => sanitize_text_field($_POST['labels']['submit_button']),
                    'success_message' => sanitize_text_field($_POST['labels']['success_message']),
                    'error_message' => sanitize_text_field($_POST['labels']['error_message']),
                    'duplicate_message' => sanitize_text_field($_POST['labels']['duplicate_message'])
                )
            );

            update_option('wp_subscribers_settings', $settings);
            echo '<div class="notice notice-success"><p>' . __('Configuración guardada correctamente.', 'wp-subscribers') . '</p></div>';
        }

        $settings = get_option('wp_subscribers_settings', array());
        $labels = isset($settings['labels']) ? $settings['labels'] : array();

        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="wp-subscribers-admin-container">
                <form method="post" action="">
                    <?php wp_nonce_field('wp_subscribers_settings_update', 'wp_subscribers_settings_nonce'); ?>

                    <!-- Configuración de Base de Datos -->
                    <div class="wp-subscribers-section">
                        <h2><?php _e('Configuración de Base de Datos', 'wp-subscribers'); ?></h2>
                        <p class="description">
                            <?php _e('Configura los parámetros de conexión a tu base de datos MySQL en Dreamhost.', 'wp-subscribers'); ?>
                        </p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="db_host"><?php _e('Host de Base de Datos', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="db_host"
                                        name="db_host"
                                        value="<?php echo esc_attr(isset($settings['db_host']) ? $settings['db_host'] : ''); ?>"
                                        class="regular-text"
                                        placeholder="mysql.example.dreamhosters.com"
                                    />
                                    <p class="description">
                                        <?php _e('Ejemplo: mysql.example.dreamhosters.com', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="db_name"><?php _e('Nombre de Base de Datos', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="db_name"
                                        name="db_name"
                                        value="<?php echo esc_attr(isset($settings['db_name']) ? $settings['db_name'] : ''); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="db_user"><?php _e('Usuario de Base de Datos', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="db_user"
                                        name="db_user"
                                        value="<?php echo esc_attr(isset($settings['db_user']) ? $settings['db_user'] : ''); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="db_password"><?php _e('Contraseña de Base de Datos', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="password"
                                        id="db_password"
                                        name="db_password"
                                        value="<?php echo esc_attr(isset($settings['db_password']) ? $settings['db_password'] : ''); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="db_table"><?php _e('Nombre de Tabla', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="db_table"
                                        name="db_table"
                                        value="<?php echo esc_attr(isset($settings['db_table']) ? $settings['db_table'] : 'subscribers'); ?>"
                                        class="regular-text"
                                    />
                                    <p class="description">
                                        <?php _e('Nombre de la tabla donde se guardarán los suscriptores. Por defecto: subscribers', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p>
                            <button type="button" id="test-connection" class="button button-secondary">
                                <?php _e('Probar Conexión', 'wp-subscribers'); ?>
                            </button>
                            <span id="connection-status"></span>
                        </p>
                    </div>

                    <!-- Configuración de Labels -->
                    <div class="wp-subscribers-section">
                        <h2><?php _e('Configuración de Etiquetas (Labels)', 'wp-subscribers'); ?></h2>
                        <p class="description">
                            <?php _e('Personaliza los textos del formulario. Puedes configurarlos en español o inglés.', 'wp-subscribers'); ?>
                        </p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="label_form_title"><?php _e('Título del Formulario', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_form_title"
                                        name="labels[form_title]"
                                        value="<?php echo esc_attr(isset($labels['form_title']) ? $labels['form_title'] : 'Suscríbete a nuestro boletín'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_name"><?php _e('Etiqueta de Nombre', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_name"
                                        name="labels[name_label]"
                                        value="<?php echo esc_attr(isset($labels['name_label']) ? $labels['name_label'] : 'Nombre'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_email"><?php _e('Etiqueta de Email', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_email"
                                        name="labels[email_label]"
                                        value="<?php echo esc_attr(isset($labels['email_label']) ? $labels['email_label'] : 'Correo electrónico'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_submit"><?php _e('Texto del Botón', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_submit"
                                        name="labels[submit_button]"
                                        value="<?php echo esc_attr(isset($labels['submit_button']) ? $labels['submit_button'] : 'Suscribirse'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_success"><?php _e('Mensaje de Éxito', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_success"
                                        name="labels[success_message]"
                                        value="<?php echo esc_attr(isset($labels['success_message']) ? $labels['success_message'] : '¡Gracias por suscribirte!'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_error"><?php _e('Mensaje de Error', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_error"
                                        name="labels[error_message]"
                                        value="<?php echo esc_attr(isset($labels['error_message']) ? $labels['error_message'] : 'Hubo un error. Por favor, intenta de nuevo.'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="label_duplicate"><?php _e('Mensaje de Email Duplicado', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="label_duplicate"
                                        name="labels[duplicate_message]"
                                        value="<?php echo esc_attr(isset($labels['duplicate_message']) ? $labels['duplicate_message'] : 'Este correo ya está suscrito.'); ?>"
                                        class="regular-text"
                                    />
                                </td>
                            </tr>
                        </table>
                    </div>

                    <!-- Instrucciones de Uso -->
                    <div class="wp-subscribers-section">
                        <h2><?php _e('Cómo Usar el Plugin', 'wp-subscribers'); ?></h2>

                        <h3><?php _e('1. Usando Shortcode', 'wp-subscribers'); ?></h3>
                        <p><?php _e('Copia y pega el siguiente shortcode en cualquier página o entrada:', 'wp-subscribers'); ?></p>
                        <code>[wp_subscribers_form]</code>
                        <p><?php _e('También puedes personalizar el título:', 'wp-subscribers'); ?></p>
                        <code>[wp_subscribers_form title="Tu título personalizado" show_title="yes"]</code>

                        <h3><?php _e('2. Usando Widget', 'wp-subscribers'); ?></h3>
                        <p>
                            <?php _e('Ve a', 'wp-subscribers'); ?>
                            <a href="<?php echo admin_url('widgets.php'); ?>"><?php _e('Apariencia > Widgets', 'wp-subscribers'); ?></a>
                            <?php _e('y arrastra el widget "Formulario de Suscriptores" al área de widgets deseada.', 'wp-subscribers'); ?>
                        </p>

                        <h3><?php _e('3. Estructura de la Base de Datos', 'wp-subscribers'); ?></h3>
                        <p><?php _e('El plugin creará automáticamente una tabla con la siguiente estructura:', 'wp-subscribers'); ?></p>
                        <pre>
id (INT, AUTO_INCREMENT)
name (VARCHAR 255)
email (VARCHAR 255, UNIQUE)
subscribed_date (DATETIME)
ip_address (VARCHAR 45)
status (VARCHAR 20, default: 'active')
                        </pre>
                    </div>

                    <?php submit_button(__('Guardar Configuración', 'wp-subscribers'), 'primary', 'wp_subscribers_settings_submit'); ?>
                </form>
            </div>
        </div>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                $('#test-connection').on('click', function() {
                    var button = $(this);
                    var status = $('#connection-status');

                    button.prop('disabled', true).text('<?php _e('Probando...', 'wp-subscribers'); ?>');
                    status.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_subscribers_test_connection',
                            nonce: '<?php echo wp_create_nonce('wp_subscribers_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                status.html('<span style="color: green;">✓ ' + response.data.message + '</span>');
                            } else {
                                status.html('<span style="color: red;">✗ ' + response.data.message + '</span>');
                            }
                        },
                        error: function() {
                            status.html('<span style="color: red;">✗ <?php _e('Error al probar la conexión', 'wp-subscribers'); ?></span>');
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Probar Conexión', 'wp-subscribers'); ?>');
                        }
                    });
                });
            });
        </script>
        <?php
    }
}

// Inicializar el admin
new WP_Subscribers_Admin();
