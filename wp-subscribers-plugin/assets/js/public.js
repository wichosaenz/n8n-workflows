/**
 * JavaScript público para el formulario de suscriptores
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Manejar envío del formulario
        $('.wp-subscribers-form').on('submit', function(e) {
            e.preventDefault();

            var $form = $(this);
            var $button = $form.find('.wp-subscribers-button');
            var $message = $form.find('.wp-subscribers-message');
            var buttonText = $button.text();

            // Obtener datos del formulario
            var formData = {
                action: 'wp_subscribers_submit',
                nonce: wpSubscribers.nonce,
                name: $form.find('input[name="name"]').val(),
                email: $form.find('input[name="email"]').val()
            };

            // Deshabilitar botón
            $button.prop('disabled', true).text('Enviando...');
            $message.hide().removeClass('success error');

            // Enviar petición AJAX
            $.ajax({
                url: wpSubscribers.ajaxUrl,
                type: 'POST',
                data: formData,
                success: function(response) {
                    if (response.success) {
                        // Éxito
                        $message
                            .addClass('success')
                            .html(response.data.message)
                            .fadeIn();

                        // Limpiar formulario
                        $form[0].reset();

                        // Ocultar mensaje después de 5 segundos
                        setTimeout(function() {
                            $message.fadeOut();
                        }, 5000);
                    } else {
                        // Error
                        $message
                            .addClass('error')
                            .html(response.data.message)
                            .fadeIn();
                    }
                },
                error: function() {
                    $message
                        .addClass('error')
                        .html('Error de conexión. Por favor, intenta de nuevo.')
                        .fadeIn();
                },
                complete: function() {
                    // Re-habilitar botón
                    $button.prop('disabled', false).text(buttonText);
                }
            });
        });

        // Validación básica en tiempo real
        $('.wp-subscribers-form input[type="email"]').on('blur', function() {
            var email = $(this).val();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (email && !emailRegex.test(email)) {
                $(this).css('border-color', '#dc3232');
            } else {
                $(this).css('border-color', '');
            }
        });

        // Limpiar borde de error al escribir
        $('.wp-subscribers-form input').on('input', function() {
            $(this).css('border-color', '');
        });
    });

})(jQuery);
