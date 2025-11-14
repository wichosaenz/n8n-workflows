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
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
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
            wp_send_json_error(array('message' => 'Error al obtener suscriptores'));
        }
    }

    /**
     * AJAX: Actualizar estado de suscriptor
     */
    public function ajax_update_status() {
        check_ajax_referer('wp_subscribers_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field($_POST['notes']) : '';

        if (empty($email) || empty($status)) {
            wp_send_json_error(array('message' => 'Email y estado son requeridos'));
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
            wp_send_json_error(array('message' => 'Permisos insuficientes'));
        }

        $email = isset($_POST['email']) ? sanitize_email($_POST['email']) : '';
        $reason = isset($_POST['reason']) ? sanitize_textarea_field($_POST['reason']) : '';

        if (empty($email)) {
            wp_send_json_error(array('message' => 'Email es requerido'));
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

        $current_site_url = esc_url(home_url());
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <!-- Estadísticas -->
            <div id="subscribers-stats" class="wp-subscribers-stats-grid">
                <div class="stat-card">
                    <div class="stat-value" id="stat-total">-</div>
                    <div class="stat-label">Total</div>
                </div>
                <div class="stat-card stat-active">
                    <div class="stat-value" id="stat-active">-</div>
                    <div class="stat-label">Activos</div>
                </div>
                <div class="stat-card stat-inactive">
                    <div class="stat-value" id="stat-inactive">-</div>
                    <div class="stat-label">Inactivos</div>
                </div>
                <div class="stat-card stat-unsubscribed">
                    <div class="stat-value" id="stat-unsubscribed">-</div>
                    <div class="stat-label">Desuscritos</div>
                </div>
                <div class="stat-card stat-bounced">
                    <div class="stat-value" id="stat-bounced">-</div>
                    <div class="stat-label">Rebotados</div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="wp-subscribers-filters">
                <label>
                    <input type="radio" name="subscriber_filter" value="current_site" checked>
                    <?php _e('Solo este sitio', 'wp-subscribers'); ?>
                    <code id="current-site-url"><?php echo esc_html($current_site_url); ?></code>
                </label>
                <label>
                    <input type="radio" name="subscriber_filter" value="all">
                    <?php _e('Todos los sitios de la red', 'wp-subscribers'); ?>
                </label>
                <button type="button" id="apply-filter" class="button"><?php _e('Aplicar Filtro', 'wp-subscribers'); ?></button>
                <button type="button" id="refresh-list" class="button"><?php _e('Actualizar', 'wp-subscribers'); ?></button>
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
                            <th><?php _e('Acciones', 'wp-subscribers'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="subscribers-table-body">
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px;">
                                <span class="spinner is-active" style="float: none; margin: 0;"></span>
                                <p><?php _e('Cargando suscriptores...', 'wp-subscribers'); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal para cambiar estado -->
        <div id="status-modal" class="wp-subscribers-modal" style="display: none;">
            <div class="wp-subscribers-modal-content">
                <span class="wp-subscribers-modal-close">&times;</span>
                <h2><?php _e('Cambiar Estado del Suscriptor', 'wp-subscribers'); ?></h2>
                <form id="status-form">
                    <input type="hidden" id="modal-email" name="email">

                    <p><strong id="modal-subscriber-name"></strong> (<span id="modal-subscriber-email"></span>)</p>

                    <p>
                        <label for="modal-status"><?php _e('Nuevo Estado:', 'wp-subscribers'); ?></label>
                        <select id="modal-status" name="status" required>
                            <option value="active"><?php _e('Activo', 'wp-subscribers'); ?></option>
                            <option value="inactive"><?php _e('Inactivo', 'wp-subscribers'); ?></option>
                            <option value="unsubscribed"><?php _e('Desuscrito', 'wp-subscribers'); ?></option>
                            <option value="bounced"><?php _e('Rebotado', 'wp-subscribers'); ?></option>
                        </select>
                    </p>

                    <p>
                        <label for="modal-notes"><?php _e('Notas (opcional):', 'wp-subscribers'); ?></label>
                        <textarea id="modal-notes" name="notes" rows="3" style="width: 100%;"></textarea>
                        <small><?php _e('Estas notas se agregarán al historial del suscriptor', 'wp-subscribers'); ?></small>
                    </p>

                    <p class="submit">
                        <button type="submit" class="button button-primary"><?php _e('Actualizar Estado', 'wp-subscribers'); ?></button>
                        <button type="button" class="button modal-cancel"><?php _e('Cancelar', 'wp-subscribers'); ?></button>
                    </p>
                </form>
            </div>
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
        .wp-subscribers-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        .wp-subscribers-modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            border-radius: 4px;
            width: 80%;
            max-width: 500px;
            position: relative;
        }
        .wp-subscribers-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            line-height: 20px;
            cursor: pointer;
        }
        .wp-subscribers-modal-close:hover {
            color: #000;
        }
        .action-button {
            margin-right: 5px;
            font-size: 12px;
        }
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let currentFilter = 'current_site';

            function loadSubscribers() {
                $('#subscribers-table-body').html('<tr><td colspan="7" style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0;"></span><p>Cargando...</p></td></tr>');

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_subscribers_get_list',
                        filter: currentFilter,
                        nonce: '<?php echo wp_create_nonce('wp_subscribers_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            renderSubscribers(response.data.subscribers);
                            renderStats(response.data.stats);
                        } else {
                            $('#subscribers-table-body').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #dc3232;">Error al cargar suscriptores</td></tr>');
                        }
                    },
                    error: function() {
                        $('#subscribers-table-body').html('<tr><td colspan="7" style="text-align: center; padding: 20px; color: #dc3232;">Error de conexión</td></tr>');
                    }
                });
            }

            function renderStats(stats) {
                $('#stat-total').text(stats.total || 0);
                $('#stat-active').text(stats.active || 0);
                $('#stat-inactive').text(stats.inactive || 0);
                $('#stat-unsubscribed').text(stats.unsubscribed || 0);
                $('#stat-bounced').text(stats.bounced || 0);
            }

            function renderSubscribers(subscribers) {
                if (subscribers.length === 0) {
                    $('#subscribers-table-body').html('<tr><td colspan="7" style="text-align: center; padding: 20px;">No hay suscriptores</td></tr>');
                    return;
                }

                let html = '';
                subscribers.forEach(function(sub) {
                    let statusClass = 'status-' + sub.status;
                    let statusText = sub.status === 'active' ? 'Activo' :
                                    sub.status === 'inactive' ? 'Inactivo' :
                                    sub.status === 'unsubscribed' ? 'Desuscrito' : 'Rebotado';

                    html += '<tr>';
                    html += '<td><strong>' + escapeHtml(sub.name) + '</strong></td>';
                    html += '<td>' + escapeHtml(sub.email) + '</td>';
                    html += '<td><span class="status-badge ' + statusClass + '">' + statusText + '</span></td>';
                    html += '<td><small>' + (sub.website_url || '-') + '</small></td>';
                    html += '<td>' + formatDate(sub.subscribed_date) + '</td>';
                    html += '<td>' + (sub.source || '-') + '</td>';
                    html += '<td>';
                    html += '<button class="button button-small action-button change-status" data-email="' + sub.email + '" data-name="' + escapeHtml(sub.name) + '" data-status="' + sub.status + '">Cambiar Estado</button>';
                    if (sub.status !== 'unsubscribed') {
                        html += '<button class="button button-small action-button button-link-delete unsubscribe-all" data-email="' + sub.email + '" data-name="' + escapeHtml(sub.name) + '">Desuscribir de Todos</button>';
                    }
                    html += '</td>';
                    html += '</tr>';
                });

                $('#subscribers-table-body').html(html);
            }

            function formatDate(dateString) {
                let date = new Date(dateString);
                return date.toLocaleDateString('es-ES', { year: 'numeric', month: 'short', day: 'numeric' });
            }

            function escapeHtml(text) {
                if (!text) return '';
                return text.replace(/[&<>"']/g, function(m) {
                    return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m];
                });
            }

            // Event: Aplicar filtro
            $('#apply-filter').on('click', function() {
                currentFilter = $('input[name="subscriber_filter"]:checked').val();
                loadSubscribers();
            });

            // Event: Actualizar
            $('#refresh-list').on('click', function() {
                loadSubscribers();
            });

            // Event: Cambiar estado
            $(document).on('click', '.change-status', function() {
                let email = $(this).data('email');
                let name = $(this).data('name');
                let currentStatus = $(this).data('status');

                $('#modal-email').val(email);
                $('#modal-subscriber-name').text(name);
                $('#modal-subscriber-email').text(email);
                $('#modal-status').val(currentStatus);
                $('#modal-notes').val('');

                $('#status-modal').fadeIn();
            });

            // Event: Cerrar modal
            $('.wp-subscribers-modal-close, .modal-cancel').on('click', function() {
                $('#status-modal').fadeOut();
            });

            // Event: Submit cambiar estado
            $('#status-form').on('submit', function(e) {
                e.preventDefault();

                let email = $('#modal-email').val();
                let status = $('#modal-status').val();
                let notes = $('#modal-notes').val();

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wp_subscribers_update_status',
                        email: email,
                        status: status,
                        notes: notes,
                        nonce: '<?php echo wp_create_nonce('wp_subscribers_admin_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#status-modal').fadeOut();
                            loadSubscribers();
                            alert('Estado actualizado correctamente');
                        } else {
                            alert('Error: ' + response.data.message);
                        }
                    }
                });
            });

            // Event: Desuscribir de todos
            $(document).on('click', '.unsubscribe-all', function() {
                let email = $(this).data('email');
                let name = $(this).data('name');

                let reason = prompt('¿Por qué razón ' + name + ' se desuscribe de TODOS los sitios?\n(Esta información se guardará en las notas)');

                if (reason === null) return; // Cancelado

                if (confirm('¿Estás seguro de desuscribir a ' + name + ' (' + email + ') de TODOS los sitios de la red?\n\nEsta acción afectará a todos los sitios donde esté registrado.')) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'wp_subscribers_unsubscribe_all',
                            email: email,
                            reason: reason,
                            nonce: '<?php echo wp_create_nonce('wp_subscribers_admin_nonce'); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                loadSubscribers();
                                alert('Desuscrito exitosamente de ' + response.data.affected_rows + ' registro(s)');
                            } else {
                                alert('Error: ' + response.data.message);
                            }
                        }
                    });
                }
            });

            // Cargar inicialmente
            loadSubscribers();
        });
        </script>
        <?php
    }
}

// Inicializar
new WP_Subscribers_List_Page();
