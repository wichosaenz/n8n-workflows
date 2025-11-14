<?php
/**
 * Clase para manejar el shortcode del formulario
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Shortcode {

    /**
     * Constructor
     */
    public function __construct() {
        add_shortcode('wp_subscribers_form', array($this, 'render_shortcode'));
    }

    /**
     * Renderizar shortcode
     *
     * Uso: [wp_subscribers_form title="Título personalizado" show_title="yes"]
     */
    public function render_shortcode($atts) {
        return WP_Subscribers_Form_Handler::render_form($atts);
    }
}

// Inicializar el shortcode
new WP_Subscribers_Shortcode();
