<?php
/**
 * Plugin Name: WP Network Recommendations
 * Plugin URI: https://github.com/wichosaenz/wp-network-recs
 * Description: Recibe y muestra artículos relacionados de una red de sitios WordPress mediante integración con n8n
 * Version: 1.0.0
 * Author: Network Media Team
 * Author URI: https://github.com/wichosaenz
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: network-recs
 */

// Evitar acceso directo
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Clase principal del plugin
 */
class WP_Network_Recommendations {

    /**
     * Constructor
     */
    public function __construct() {
        // Registrar endpoint REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));

        // Registrar shortcode
        add_shortcode('network_recs', array($this, 'render_shortcode'));

        // Registrar widget
        add_action('widgets_init', array($this, 'register_widget'));
    }

    /**
     * Registrar rutas REST API
     */
    public function register_rest_routes() {
        register_rest_route('network-recs/v1', '/update', array(
            'methods'             => 'POST',
            'callback'            => array($this, 'handle_update'),
            'permission_callback' => array($this, 'check_permissions'),
        ));
    }

    /**
     * Verificar permisos para actualizar
     */
    public function check_permissions($request) {
        // Verificar que el usuario esté autenticado y tenga capacidad de administrador
        return current_user_can('manage_options');
    }

    /**
     * Manejar actualización de artículos
     */
    public function handle_update($request) {
        try {
            $params = $request->get_json_params();

            // Validar que se recibió un array
            if (!is_array($params)) {
                return new WP_REST_Response(array(
                    'success' => false,
                    'message' => 'Se esperaba un array de artículos'
                ), 400);
            }

            // Sanitizar y validar cada artículo
            $articles = array();
            foreach ($params as $article) {
                if (!isset($article['titulo']) || !isset($article['url'])) {
                    continue; // Saltar artículos sin datos esenciales
                }

                $articles[] = array(
                    'titulo'        => sanitize_text_field($article['titulo'] ?? ''),
                    'url'           => esc_url_raw($article['url'] ?? ''),
                    'nombre_sitio'  => sanitize_text_field($article['nombre_sitio'] ?? ''),
                    'cita_directa'  => sanitize_textarea_field($article['cita_directa'] ?? ''),
                    'dato_clave'    => sanitize_text_field($article['dato_clave'] ?? ''),
                );
            }

            // Guardar en wp_options
            $updated = update_option('network_recs_data', $articles, false);
            update_option('network_recs_last_update', current_time('mysql'), false);

            return new WP_REST_Response(array(
                'success'   => true,
                'message'   => 'Artículos actualizados correctamente',
                'count'     => count($articles),
                'timestamp' => current_time('mysql')
            ), 200);

        } catch (Exception $e) {
            return new WP_REST_Response(array(
                'success' => false,
                'message' => 'Error al procesar la solicitud: ' . $e->getMessage()
            ), 500);
        }
    }

    /**
     * Renderizar shortcode
     */
    public function render_shortcode($atts) {
        // Atributos del shortcode
        $atts = shortcode_atts(array(
            'limit' => 6,
            'title' => 'Artículos Relacionados de la Red'
        ), $atts, 'network_recs');

        $articles = get_option('network_recs_data', array());

        if (empty($articles)) {
            return '<div class="network-recs-empty"><p>No hay artículos disponibles en este momento.</p></div>';
        }

        // Limitar cantidad de artículos
        $articles = array_slice($articles, 0, intval($atts['limit']));

        // Generar HTML
        ob_start();
        ?>
        <div class="network-recs-container">
            <?php if (!empty($atts['title'])): ?>
            <h2 class="network-recs-title"><?php echo esc_html($atts['title']); ?></h2>
            <?php endif; ?>

            <div class="network-recs-grid">
                <?php foreach ($articles as $article): ?>
                <article class="network-rec-card">
                    <div class="network-rec-header">
                        <?php if (!empty($article['nombre_sitio'])): ?>
                        <span class="network-rec-site"><?php echo esc_html($article['nombre_sitio']); ?></span>
                        <?php endif; ?>
                    </div>

                    <h3 class="network-rec-article-title">
                        <a href="<?php echo esc_url($article['url']); ?>" target="_blank" rel="noopener noreferrer">
                            <?php echo esc_html($article['titulo']); ?>
                        </a>
                    </h3>

                    <?php if (!empty($article['cita_directa'])): ?>
                    <p class="network-rec-excerpt">
                        <?php echo esc_html(wp_trim_words($article['cita_directa'], 30, '...')); ?>
                    </p>
                    <?php endif; ?>

                    <?php if (!empty($article['dato_clave'])): ?>
                    <div class="network-rec-key-data">
                        <svg class="network-rec-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 11 12 14 22 4"></polyline>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                        <span><?php echo esc_html($article['dato_clave']); ?></span>
                    </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url($article['url']); ?>" class="network-rec-link" target="_blank" rel="noopener noreferrer">
                        Leer más →
                    </a>
                </article>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .network-recs-container {
                margin: 2rem 0;
                padding: 0;
            }

            .network-recs-title {
                font-size: 1.75rem;
                font-weight: 700;
                margin-bottom: 1.5rem;
                color: #1a1a1a;
                border-bottom: 3px solid #2563eb;
                padding-bottom: 0.5rem;
            }

            .network-recs-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
                gap: 1.5rem;
                margin: 0;
            }

            .network-rec-card {
                background: #ffffff;
                border-radius: 8px;
                padding: 1.5rem;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                display: flex;
                flex-direction: column;
                border: 1px solid #e5e7eb;
            }

            .network-rec-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
            }

            .network-rec-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 0.75rem;
            }

            .network-rec-site {
                font-size: 0.75rem;
                font-weight: 600;
                text-transform: uppercase;
                color: #2563eb;
                background: #eff6ff;
                padding: 0.25rem 0.75rem;
                border-radius: 12px;
                letter-spacing: 0.05em;
            }

            .network-rec-article-title {
                font-size: 1.125rem;
                font-weight: 700;
                margin: 0 0 0.75rem 0;
                line-height: 1.4;
            }

            .network-rec-article-title a {
                color: #1a1a1a;
                text-decoration: none;
                transition: color 0.2s ease;
            }

            .network-rec-article-title a:hover {
                color: #2563eb;
            }

            .network-rec-excerpt {
                font-size: 0.875rem;
                color: #6b7280;
                line-height: 1.6;
                margin: 0 0 1rem 0;
                flex-grow: 1;
            }

            .network-rec-key-data {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.8125rem;
                color: #059669;
                background: #f0fdf4;
                padding: 0.5rem 0.75rem;
                border-radius: 6px;
                margin-bottom: 1rem;
                font-weight: 500;
            }

            .network-rec-icon {
                flex-shrink: 0;
            }

            .network-rec-link {
                font-size: 0.875rem;
                font-weight: 600;
                color: #2563eb;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                margin-top: auto;
                transition: color 0.2s ease;
            }

            .network-rec-link:hover {
                color: #1d4ed8;
            }

            .network-recs-empty {
                padding: 2rem;
                text-align: center;
                background: #f9fafb;
                border-radius: 8px;
                color: #6b7280;
            }

            @media (max-width: 768px) {
                .network-recs-grid {
                    grid-template-columns: 1fr;
                }

                .network-recs-title {
                    font-size: 1.5rem;
                }
            }
        </style>
        <?php
        return ob_get_clean();
    }

    /**
     * Registrar widget
     */
    public function register_widget() {
        register_widget('Network_Recs_Widget');
    }
}

/**
 * Widget de Artículos de la Red
 */
class Network_Recs_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'network_recs_widget',
            'Artículos de la Red',
            array('description' => 'Muestra artículos relacionados de la red de sitios')
        );
    }

    public function widget($args, $instance) {
        echo $args['before_widget'];

        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }

        $limit = !empty($instance['limit']) ? intval($instance['limit']) : 3;

        echo do_shortcode('[network_recs limit="' . $limit . '" title=""]');

        echo $args['after_widget'];
    }

    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : 'Artículos Relacionados';
        $limit = !empty($instance['limit']) ? $instance['limit'] : 3;
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">Título:</label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text"
                   value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">Cantidad de artículos:</label>
            <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('limit')); ?>"
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" type="number"
                   step="1" min="1" max="12" value="<?php echo esc_attr($limit); ?>" size="3">
        </p>
        <?php
    }

    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['limit'] = (!empty($new_instance['limit'])) ? intval($new_instance['limit']) : 3;
        return $instance;
    }
}

// Inicializar el plugin
new WP_Network_Recommendations();
