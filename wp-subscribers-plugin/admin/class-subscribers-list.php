<?php
/**
 * Clase para la página de lista de suscriptores
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_List_Page {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_submenu_page'), 20);
        add_action('wp_ajax_wp_subscribers_get_list', array($this, 'ajax_get_subscribers'));
        add_action('wp_ajax_wp_subscribers_update_status', array($this, 'ajax_update_status'));
        add_action('wp_ajax_wp_subscribers_unsubscribe_all', array($this, 'ajax_unsubscribe_all'));
    }

    /**
     * Agregar submenú
     */
    public function add_submenu_page() {
        add_submenu_page(
            'wp-subscribers',
            __('Lista de Suscriptores', 'wp-subscribers'),
            __('Ver Suscriptores', 'wp-subscribers'),
            'manage_options',
            'wp-subscribers-list',
            array($this, 'render_list_page')
        );
    }

    /**
     * AJAX: Obtener lista de suscriptores
     */
    public function ajax_get_subscribers() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'wp-subscribers')));
        }

        $filter = isset($_POST['filter']) ? sanitize_text_field($_POST['filter']) : 'all';
        $current_site_url = esc_url(home_url());

        $db = new WP_Subscribers_Database();

        $website_url = ($filter === 'current_site') ? $current_site_url : null;
        $result = $db->get_subscribers($filter, $website_url, 500, 0);
        $stats = $db->get_statistics($website_url);

        if ($result['success']) {
            wp_send_json_success(array(
                'subscribers' => $result['data'],
                'total' => $result['total'],
                'stats' => $stats,
                'current_site' => $current_site_url
            ));
        } else {
            wp_send_json_error(array('message' => __('Error al obtener suscriptores', 'wp-subscribers')));
        }
    }

    /**
     * AJAX: Actualizar estado de suscriptor
     */
    public function ajax_update_status() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'wp-subscribers')));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($email) || empty($status)) {
            wp_send_json_error(array('message' => __('Email y estado son requeridos', 'wp-subscribers')));
        }

        $db = new WP_Subscribers_Database();
        $result = $db->update_subscriber_status($email, $status, $notes);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * AJAX: Desuscribir de todos los sitios
     */
    public function ajax_unsubscribe_all() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permisos insuficientes', 'wp-subscribers')));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (empty($email)) {
            wp_send_json_error(array('message' => __('Email es requerido', 'wp-subscribers')));
        }

        $db = new WP_Subscribers_Database();
        $result = $db->unsubscribe_from_all_sites($email, $reason);

        if ($result['success']) {
            wp_send_json_success($result);
        } else {
            wp_send_json_error($result);
        }
    }

    /**
     * Renderizar página de lista
     */
    public function render_list_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Obtener filtro actual
        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'current_site';
        $current_site_url = esc_url(home_url());

        // Cargar datos directamente en PHP
        $db = new WP_Subscribers_Database();
        $website_url = ($filter === 'current_site') ? $current_site_url : null;
        $result = $db->get_subscribers($filter, $website_url, 500, 0);
        $stats = $db->get_statistics($website_url);

        $subscribers = $result['success'] ? $result['data'] : array();
        $total = $result['total'];
        $has_connection = $db->get_connection() !== false;
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <?php if (!$has_connection): ?>
                <div class="notice notice-error">
                    <p><strong><?php _e('Error de conexión a la base de datos', 'wp-subscribers'); ?></strong></p>
                    <p><?php _e('No se pudo conectar a la base de datos. Por favor verifica tu configuración.', 'wp-subscribers'); ?></p>
                    <p><a href="<?php echo admin_url('admin.php?page=wp-subscribers'); ?>" class="button"><?php _e('Ir a Configuración', 'wp-subscribers'); ?></a></p>
                </div>
            <?php else: ?>

                <!-- Estadísticas -->
                <div class="wp-subscribers-stats-grid">
                    <div class="stat-card">
                        <div class="stat-value"><?php echo esc_html($stats['total'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Total', 'wp-subscribers'); ?></div>
                    </div>
                    <div class="stat-card stat-active">
                        <div class="stat-value"><?php echo esc_html($stats['active'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Activos', 'wp-subscribers'); ?></div>
                    </div>
                    <div class="stat-card stat-inactive">
                        <div class="stat-value"><?php echo esc_html($stats['inactive'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Inactivos', 'wp-subscribers'); ?></div>
                    </div>
                    <div class="stat-card stat-unsubscribed">
                        <div class="stat-value"><?php echo esc_html($stats['unsubscribed'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Desuscritos', 'wp-subscribers'); ?></div>
                    </div>
                    <div class="stat-card stat-bounced">
                        <div class="stat-value"><?php echo esc_html($stats['bounced'] ?? 0); ?></div>
                        <div class="stat-label"><?php _e('Rebotados', 'wp-subscribers'); ?></div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="wp-subscribers-filters">
                    <form method="get" action="">
                        <input type="hidden" name="page" value="wp-subscribers-list">
                        <label>
                            <input type="radio" name="filter" value="current_site" <?php checked($filter, 'current_site'); ?>>
                            <?php _e('Solo este sitio', 'wp-subscribers'); ?>
                            <code><?php echo esc_html($current_site_url); ?></code>
                        </label>
                        <label>
                            <input type="radio" name="filter" value="all" <?php checked($filter, 'all'); ?>>
                            <?php _e('Todos los sitios de la red', 'wp-subscribers'); ?>
                        </label>
                        <button type="submit" class="button"><?php _e('Aplicar Filtro', 'wp-subscribers'); ?></button>
                    </form>
                </div>

                <!-- Tabla de suscriptores -->
                <div class="wp-subscribers-table-container">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e('Nombre', 'wp-subscribers'); ?></th>
                                <th><?php _e('Email', 'wp-subscribers'); ?></th>
                                <th><?php _e('Estado', 'wp-subscribers'); ?></th>
                                <th><?php _e('Sitio Web', 'wp-subscribers'); ?></th>
                                <th><?php _e('Fecha', 'wp-subscribers'); ?></th>
                                <th><?php _e('Origen', 'wp-subscribers'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($subscribers)): ?>
                                <tr>
                                    <td colspan="6" style="text-align: center; padding: 40px;">
                                        <?php _e('No hay suscriptores', 'wp-subscribers'); ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($subscribers as $subscriber): ?>
                                    <tr>
                                        <td><strong><?php echo esc_html($subscriber['name']); ?></strong></td>
                                        <td><?php echo esc_html($subscriber['email']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'status-' . $subscriber['status'];
                                            $status_labels = array(
                                                'active' => __('Activo', 'wp-subscribers'),
                                                'inactive' => __('Inactivo', 'wp-subscribers'),
                                                'unsubscribed' => __('Desuscrito', 'wp-subscribers'),
                                                'bounced' => __('Rebotado', 'wp-subscribers')
                                            );
                                            $status_text = $status_labels[$subscriber['status']] ?? $subscriber['status'];
                                            ?>
                                            <span class="status-badge <?php echo esc_attr($status_class); ?>">
                                                <?php echo esc_html($status_text); ?>
                                            </span>
                                        </td>
                                        <td><small><?php echo esc_html($subscriber['website_url'] ?? '-'); ?></small></td>
                                        <td><?php echo esc_html(mysql2date('j M Y', $subscriber['subscribed_date'])); ?></td>
                                        <td><?php echo esc_html($subscriber['source'] ?? '-'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($total > 0): ?>
                        <p class="description" style="margin-top: 15px;">
                            <?php printf(__('Mostrando %d suscriptores', 'wp-subscribers'), count($subscribers)); ?>
                            <?php if ($total > count($subscribers)): ?>
                                <?php printf(__('de %d totales', 'wp-subscribers'), $total); ?>
                            <?php endif; ?>
                        </p>
                    <?php endif; ?>
                </div>

            <?php endif; ?>
        </div>

        <style>
        .wp-subscribers-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .stat-card {
            background: #fff;
            padding: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card.stat-active { border-left: 4px solid #46b450; }
        .stat-card.stat-inactive { border-left: 4px solid #ffb900; }
        .stat-card.stat-unsubscribed { border-left: 4px solid #dc3232; }
        .stat-card.stat-bounced { border-left: 4px solid #826eb4; }
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: #23282d;
        }
        .stat-label {
            font-size: 14px;
            color: #50575e;
            margin-top: 5px;
        }
        .wp-subscribers-filters {
            background: #fff;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .wp-subscribers-filters label {
            margin-right: 20px;
            display: inline-block;
        }
        .wp-subscribers-filters code {
            background: #f0f0f1;
            padding: 2px 6px;
            border-radius: 2px;
        }
        .wp-subscribers-table-container {
            background: #fff;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .status-badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: 500;
            display: inline-block;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #fff3cd; color: #856404; }
        .status-unsubscribed { background: #f8d7da; color: #721c24; }
        .status-bounced { background: #e2d9f3; color: #4a148c; }
        </style>
        <?php
    }
}

// Inicializar
new WP_Subscribers_List_Page();
