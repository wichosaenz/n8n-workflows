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
        add_action('wp_ajax_wp_subscribers_test_email', array($this, 'test_email'));
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

        // Sanitizar configuración de base de datos PostgreSQL
        $sanitized['db_host'] = isset($input['db_host']) ? sanitize_text_field($input['db_host']) : '';
        $sanitized['db_port'] = isset($input['db_port']) ? absint($input['db_port']) : 5432;
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

        // Sanitizar configuración SMTP
        $sanitized['smtp_host'] = isset($input['smtp_host']) ? sanitize_text_field($input['smtp_host']) : '';
        $sanitized['smtp_port'] = isset($input['smtp_port']) ? absint($input['smtp_port']) : 587;
        $sanitized['smtp_security'] = isset($input['smtp_security']) ? sanitize_text_field($input['smtp_security']) : 'tls';
        $sanitized['smtp_user'] = isset($input['smtp_user']) ? sanitize_email($input['smtp_user']) : '';
        $sanitized['smtp_password'] = isset($input['smtp_password']) ? $input['smtp_password'] : '';
        $sanitized['smtp_from_name'] = isset($input['smtp_from_name']) ? sanitize_text_field($input['smtp_from_name']) : get_bloginfo('name');

        // Sanitizar configuración de notificaciones
        $sanitized['email_notifications_enabled'] = isset($input['email_notifications_enabled']) ? 'yes' : 'no';
        $sanitized['notification_recipients'] = isset($input['notification_recipients']) ? sanitize_textarea_field($input['notification_recipients']) : '';
        $sanitized['email_subject'] = isset($input['email_subject']) ? sanitize_text_field($input['email_subject']) : '🎉 Nueva suscripción en ' . get_bloginfo('name');

        return $sanitized;
    }

    /**
     * Probar conexión a la base de datos
     */
    public function test_connection() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'user_message' => 'Permisos insuficientes',
                'debug_message' => 'Usuario no tiene capacidad manage_options'
            ));
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
     * Enviar email de prueba
     */
    public function test_email() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => 'Permisos insuficientes'
            ));
        }

        $test_recipient = isset($_POST['test_email']) ? sanitize_email($_POST['test_email']) : '';

        if (empty($test_recipient) || !is_email($test_recipient)) {
            wp_send_json_error(array(
                'message' => 'Email de prueba inválido'
            ));
        }

        require_once WP_SUBSCRIBERS_PLUGIN_DIR . 'includes/class-email-notifications.php';
        $email_notifications = new WP_Subscribers_Email_Notifications();
        $result = $email_notifications->send_test_email($test_recipient);

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
                'db_port' => isset($_POST['db_port']) ? absint($_POST['db_port']) : 5432,
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
                ),
                // Configuración SMTP
                'smtp_host' => isset($_POST['smtp_host']) ? sanitize_text_field($_POST['smtp_host']) : '',
                'smtp_port' => isset($_POST['smtp_port']) ? absint($_POST['smtp_port']) : 587,
                'smtp_security' => isset($_POST['smtp_security']) ? sanitize_text_field($_POST['smtp_security']) : 'tls',
                'smtp_user' => isset($_POST['smtp_user']) ? sanitize_email($_POST['smtp_user']) : '',
                'smtp_password' => isset($_POST['smtp_password']) ? $_POST['smtp_password'] : '',
                'smtp_from_name' => isset($_POST['smtp_from_name']) ? sanitize_text_field($_POST['smtp_from_name']) : get_bloginfo('name'),
                // Configuración de notificaciones
                'email_notifications_enabled' => isset($_POST['email_notifications_enabled']) ? 'yes' : 'no',
                'notification_recipients' => isset($_POST['notification_recipients']) ? sanitize_textarea_field($_POST['notification_recipients']) : '',
                'email_subject' => isset($_POST['email_subject']) ? sanitize_text_field($_POST['email_subject']) : '🎉 Nueva suscripción en ' . get_bloginfo('name')
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
                        <h2><?php _e('Configuración de Base de Datos PostgreSQL', 'wp-subscribers'); ?></h2>
                        <p class="description">
                            <?php _e('Configura los parámetros de conexión a tu base de datos PostgreSQL.', 'wp-subscribers'); ?>
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
                                        placeholder="localhost"
                                    />
                                    <p class="description">
                                        <?php _e('Ejemplo: localhost, 192.168.1.100, postgres.example.com', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="db_port"><?php _e('Puerto PostgreSQL', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="number"
                                        id="db_port"
                                        name="db_port"
                                        value="<?php echo esc_attr(isset($settings['db_port']) ? $settings['db_port'] : '5432'); ?>"
                                        class="small-text"
                                        placeholder="5432"
                                        min="1"
                                        max="65535"
                                    />
                                    <p class="description">
                                        <?php _e('Puerto por defecto de PostgreSQL: 5432', 'wp-subscribers'); ?>
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

                    <!-- Configuración SMTP -->
                    <div class="wp-subscribers-section">
                        <h2><?php _e('Configuración SMTP (para notificaciones por email)', 'wp-subscribers'); ?> 📧</h2>
                        <p class="description">
                            <?php _e('Configura los parámetros de tu servidor SMTP para enviar notificaciones cuando haya nuevas suscripciones.', 'wp-subscribers'); ?>
                        </p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="smtp_host"><?php _e('Servidor SMTP', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="smtp_host"
                                        name="smtp_host"
                                        value="<?php echo esc_attr(isset($settings['smtp_host']) ? $settings['smtp_host'] : ''); ?>"
                                        class="regular-text"
                                        placeholder="smtp.gmail.com"
                                    />
                                    <p class="description">
                                        <?php _e('Ejemplo: smtp.gmail.com, smtp.dreamhost.com, smtp.office365.com', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_port"><?php _e('Puerto SMTP', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="number"
                                        id="smtp_port"
                                        name="smtp_port"
                                        value="<?php echo esc_attr(isset($settings['smtp_port']) ? $settings['smtp_port'] : '587'); ?>"
                                        class="small-text"
                                    />
                                    <p class="description">
                                        <?php _e('Puerto común: 587 (TLS) o 465 (SSL)', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_security"><?php _e('Seguridad/Encriptación', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <select id="smtp_security" name="smtp_security">
                                        <option value="tls" <?php selected(isset($settings['smtp_security']) ? $settings['smtp_security'] : 'tls', 'tls'); ?>>TLS</option>
                                        <option value="ssl" <?php selected(isset($settings['smtp_security']) ? $settings['smtp_security'] : 'tls', 'ssl'); ?>>SSL</option>
                                        <option value="" <?php selected(isset($settings['smtp_security']) ? $settings['smtp_security'] : 'tls', ''); ?>><?php _e('Sin encriptación', 'wp-subscribers'); ?></option>
                                    </select>
                                    <p class="description">
                                        <?php _e('Recomendado: TLS (puerto 587)', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_user"><?php _e('Usuario / Email SMTP', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="email"
                                        id="smtp_user"
                                        name="smtp_user"
                                        value="<?php echo esc_attr(isset($settings['smtp_user']) ? $settings['smtp_user'] : ''); ?>"
                                        class="regular-text"
                                        placeholder="tu-email@ejemplo.com"
                                    />
                                    <p class="description">
                                        <?php _e('Tu email completo (será el remitente de las notificaciones)', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_password"><?php _e('Contraseña SMTP', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="password"
                                        id="smtp_password"
                                        name="smtp_password"
                                        value="<?php echo esc_attr(isset($settings['smtp_password']) ? $settings['smtp_password'] : ''); ?>"
                                        class="regular-text"
                                        autocomplete="new-password"
                                    />
                                    <p class="description">
                                        <?php _e('Contraseña de tu cuenta de email o contraseña de aplicación', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="smtp_from_name"><?php _e('Nombre del Remitente', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="smtp_from_name"
                                        name="smtp_from_name"
                                        value="<?php echo esc_attr(isset($settings['smtp_from_name']) ? $settings['smtp_from_name'] : get_bloginfo('name')); ?>"
                                        class="regular-text"
                                        placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>"
                                    />
                                    <p class="description">
                                        <?php _e('Nombre que aparecerá como remitente de los emails', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>

                        <p>
                            <input type="email" id="test-email-address" placeholder="tu-email@ejemplo.com" class="regular-text" style="margin-right: 10px;" />
                            <button type="button" id="test-email" class="button button-secondary">
                                <?php _e('Enviar Email de Prueba', 'wp-subscribers'); ?>
                            </button>
                            <span id="email-status"></span>
                        </p>
                    </div>

                    <!-- Configuración de Notificaciones -->
                    <div class="wp-subscribers-section">
                        <h2><?php _e('Configuración de Notificaciones por Email', 'wp-subscribers'); ?> 🔔</h2>
                        <p class="description">
                            <?php _e('Configura quién recibirá notificaciones cuando haya nuevas suscripciones.', 'wp-subscribers'); ?>
                        </p>

                        <table class="form-table">
                            <tr>
                                <th scope="row">
                                    <label for="email_notifications_enabled"><?php _e('Activar Notificaciones', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <label>
                                        <input
                                            type="checkbox"
                                            id="email_notifications_enabled"
                                            name="email_notifications_enabled"
                                            value="yes"
                                            <?php checked(isset($settings['email_notifications_enabled']) ? $settings['email_notifications_enabled'] : 'no', 'yes'); ?>
                                        />
                                        <?php _e('Enviar email cada vez que alguien se suscriba', 'wp-subscribers'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Marca esta casilla para recibir notificaciones automáticas', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="notification_recipients"><?php _e('Destinatarios de Notificaciones', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <textarea
                                        id="notification_recipients"
                                        name="notification_recipients"
                                        rows="3"
                                        class="large-text"
                                        placeholder="admin@ejemplo.com, ventas@ejemplo.com, marketing@ejemplo.com"
                                    ><?php echo esc_textarea(isset($settings['notification_recipients']) ? $settings['notification_recipients'] : ''); ?></textarea>
                                    <p class="description">
                                        <?php _e('Emails separados por comas que recibirán las notificaciones de nuevas suscripciones', 'wp-subscribers'); ?>
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="email_subject"><?php _e('Asunto del Email', 'wp-subscribers'); ?></label>
                                </th>
                                <td>
                                    <input
                                        type="text"
                                        id="email_subject"
                                        name="email_subject"
                                        value="<?php echo esc_attr(isset($settings['email_subject']) ? $settings['email_subject'] : '🎉 Nueva suscripción en ' . get_bloginfo('name')); ?>"
                                        class="large-text"
                                    />
                                    <p class="description">
                                        <?php _e('Asunto que aparecerá en los emails de notificación', 'wp-subscribers'); ?>
                                    </p>
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
                            var html = '';

                            if (response.success) {
                                // Mensaje de éxito
                                var userMsg = response.data.user_message || response.data.message || 'Conexión exitosa';
                                var debugMsg = response.data.debug_message || '';

                                html = '<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px;">';
                                html += '<span style="color: #155724; font-weight: bold;">✓ ' + userMsg + '</span>';

                                if (debugMsg) {
                                    html += '<div style="margin-top: 8px; padding: 8px; background: #e7f3e7; border-left: 3px solid #28a745; font-size: 12px; font-family: monospace; color: #155724;">';
                                    html += '<strong>Debug técnico:</strong><br>' + debugMsg;
                                    html += '</div>';
                                }
                                html += '</div>';

                            } else {
                                // Mensaje de error
                                var userMsg = response.data.user_message || response.data.message || 'Error de conexión';
                                var debugMsg = response.data.debug_message || '';

                                html = '<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">';
                                html += '<div style="color: #721c24; white-space: pre-line; line-height: 1.6;">' + userMsg + '</div>';

                                if (debugMsg) {
                                    html += '<div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; font-family: monospace; color: #856404;">';
                                    html += '<strong>🔍 Debug técnico (copia esto si necesitas soporte):</strong><br>';
                                    html += '<code style="background: #fff; padding: 2px 4px; border-radius: 2px; display: inline-block; margin-top: 4px;">' + debugMsg + '</code>';
                                    html += '</div>';
                                }
                                html += '</div>';
                            }

                            status.html(html);
                        },
                        error: function() {
                            var html = '<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px;">';
                            html += '<span style="color: #721c24;">✗ <?php _e('Error al probar la conexión', 'wp-subscribers'); ?></span>';
                            html += '<div style="margin-top: 8px; padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; font-size: 12px; font-family: monospace; color: #856404;">';
                            html += '<strong>Debug técnico:</strong><br>Error de comunicación AJAX con el servidor';
                            html += '</div>';
                            html += '</div>';
                            status.html(html);
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Probar Conexión', 'wp-subscribers'); ?>');
                        }
                    });
                });

                // Test Email
                $('#test-email').on('click', function() {
                    var button = $(this);
                    var status = $('#email-status');
                    var testEmail = $('#test-email-address').val();

                    if (!testEmail) {
                        alert('<?php _e('Por favor ingresa un email de prueba', 'wp-subscribers'); ?>');
                        return;
                    }

                    button.prop('disabled', true).text('<?php _e('Enviando...', 'wp-subscribers'); ?>');
                    status.html('');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_subscribers_test_email',
                            test_email: testEmail,
                            nonce: '<?php echo wp_create_nonce('wp_subscribers_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            var html = '';

                            if (response.success) {
                                html = '<div style="padding: 10px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 4px; margin-top: 10px;">';
                                html += '<span style="color: #155724; font-weight: bold;">✓ ' + (response.data.message || 'Email enviado correctamente') + '</span>';
                                html += '<p style="margin: 5px 0 0 0; color: #155724; font-size: 13px;">Revisa la bandeja de entrada de <strong>' + testEmail + '</strong></p>';
                                html += '</div>';
                            } else {
                                html = '<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-top: 10px;">';
                                html += '<span style="color: #721c24;">✗ ' + (response.data.message || 'Error al enviar email') + '</span>';
                                html += '<p style="margin: 5px 0 0 0; color: #721c24; font-size: 12px;">Verifica la configuración SMTP e intenta nuevamente</p>';
                                html += '</div>';
                            }

                            status.html(html);
                        },
                        error: function() {
                            var html = '<div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; margin-top: 10px;">';
                            html += '<span style="color: #721c24;">✗ <?php _e('Error al procesar la solicitud', 'wp-subscribers'); ?></span>';
                            html += '</div>';
                            status.html(html);
                        },
                        complete: function() {
                            button.prop('disabled', false).text('<?php _e('Enviar Email de Prueba', 'wp-subscribers'); ?>');
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
