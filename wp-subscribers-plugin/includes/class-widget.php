<?php
/**
 * Widget de formulario de suscriptores
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Widget extends WP_Widget {

    /**
     * Constructor del widget
     */
    public function __construct() {
        parent::__construct(
            'wp_subscribers_widget',
            __('Formulario de Suscriptores', 'wp-subscribers'),
            array(
                'description' => __('Muestra el formulario de suscripción a tu boletín', 'wp-subscribers')
            )
        );
    }

    /**
     * Front-end del widget
     */
    public function widget($args, $instance) {
        echo $args['before_widget'];

        $title = !empty($instance['title']) ? $instance['title'] : '';
        $show_title = !empty($instance['show_title']) ? $instance['show_title'] : 'yes';

        if (!empty($title) && $show_title === 'yes') {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        // Renderizar formulario
        $form_atts = array(
            'title' => $title,
            'show_title' => 'no' // Ya mostramos el título con el formato del widget
        );

        echo WP_Subscribers_Form_Handler::render_form($form_atts);

        echo $args['after_widget'];
    }

    /**
     * Backend del widget (formulario de configuración)
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : __('Suscríbete', 'wp-subscribers');
        $show_title = !empty($instance['show_title']) ? $instance['show_title'] : 'yes';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php _e('Título:', 'wp-subscribers'); ?>
            </label>
            <input
                class="widefat"
                id="<?php echo esc_attr($this->get_field_id('title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('title')); ?>"
                type="text"
                value="<?php echo esc_attr($title); ?>"
            />
        </p>
        <p>
            <input
                class="checkbox"
                type="checkbox"
                <?php checked($show_title, 'yes'); ?>
                id="<?php echo esc_attr($this->get_field_id('show_title')); ?>"
                name="<?php echo esc_attr($this->get_field_name('show_title')); ?>"
                value="yes"
            />
            <label for="<?php echo esc_attr($this->get_field_id('show_title')); ?>">
                <?php _e('Mostrar título', 'wp-subscribers'); ?>
            </label>
        </p>
        <?php
    }

    /**
     * Actualizar configuración del widget
     */
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
        $instance['show_title'] = !empty($new_instance['show_title']) ? 'yes' : 'no';
        return $instance;
    }
}
