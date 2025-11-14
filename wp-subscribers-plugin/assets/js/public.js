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
                    } else if (response.data && response.data.already_subscribed) {
                        // El suscriptor ya existe - mostrar formulario de desuscripción
                        var email = response.data.email;
                        var $wrapper = $form.closest('.wp-subscribers-form-wrapper');
                        var $unsubscribeForm = $wrapper.find('.wp-subscribers-unsubscribe-form');

                        // Ocultar formulario de suscripción
                        $form.slideUp(300, function() {
                            // Mostrar formulario de desuscripción
                            $unsubscribeForm.slideDown(300);

                            // Guardar el email en un atributo de datos para usarlo luego
                            $unsubscribeForm.data('email', email);
                        });
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

        // Manejar botón "Cancelar" en formulario de desuscripción
        $(document).on('click', '.wp-subscribers-unsubscribe-cancel', function() {
            var $unsubscribeForm = $(this).closest('.wp-subscribers-unsubscribe-form');
            var $wrapper = $unsubscribeForm.closest('.wp-subscribers-form-wrapper');
            var $subscribeForm = $wrapper.find('.wp-subscribers-form');

            // Ocultar formulario de desuscripción
            $unsubscribeForm.slideUp(300, function() {
                // Limpiar textarea de razón
                $unsubscribeForm.find('#wp-subscribers-unsubscribe-reason').val('');
                // Ocultar mensaje de resultado
                $unsubscribeForm.find('.wp-subscribers-unsubscribe-result').hide().removeClass('success error').html('');

                // Mostrar formulario de suscripción nuevamente
                $subscribeForm.slideDown(300);

                // Limpiar el formulario de suscripción
                $subscribeForm[0].reset();
            });
        });

        // Manejar botón "Desuscribirme de Todos los Sitios"
        $(document).on('click', '.wp-subscribers-unsubscribe-confirm', function() {
            var $button = $(this);
            var $unsubscribeForm = $button.closest('.wp-subscribers-unsubscribe-form');
            var $resultDiv = $unsubscribeForm.find('.wp-subscribers-unsubscribe-result');
            var email = $unsubscribeForm.data('email');
            var reason = $unsubscribeForm.find('#wp-subscribers-unsubscribe-reason').val();
            var buttonText = $button.text();

            // Confirmar acción
            if (!confirm('⚠️ ADVERTENCIA: Esto te desuscribirá de TODOS los sitios de nuestra red (aproximadamente 20 sitios web).\n\n¿Estás seguro de que deseas continuar?')) {
                return;
            }

            // Deshabilitar botón
            $button.prop('disabled', true).text('Procesando...');
            $resultDiv.hide().removeClass('success error');

            // Enviar petición AJAX
            $.ajax({
                url: wpSubscribers.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'wp_subscribers_unsubscribe_public',
                    nonce: wpSubscribers.nonce,
                    email: email,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        // Éxito
                        $resultDiv
                            .addClass('success')
                            .html('<strong>✅ ' + response.data.message + '</strong>')
                            .fadeIn();

                        // Ocultar el formulario de desuscripción después de 2 segundos
                        setTimeout(function() {
                            $unsubscribeForm.slideUp(300, function() {
                                var $wrapper = $unsubscribeForm.closest('.wp-subscribers-form-wrapper');
                                var $subscribeForm = $wrapper.find('.wp-subscribers-form');

                                // Limpiar campos
                                $unsubscribeForm.find('#wp-subscribers-unsubscribe-reason').val('');
                                $resultDiv.hide().removeClass('success error').html('');

                                // Mostrar formulario de suscripción
                                $subscribeForm.slideDown(300);
                                $subscribeForm[0].reset();
                            });
                        }, 3000);
                    } else {
                        // Error
                        $resultDiv
                            .addClass('error')
                            .html('<strong>❌ ' + response.data.message + '</strong>')
                            .fadeIn();
                    }
                },
                error: function() {
                    $resultDiv
                        .addClass('error')
                        .html('<strong>❌ Error de conexión. Por favor, intenta de nuevo.</strong>')
                        .fadeIn();
                },
                complete: function() {
                    // Re-habilitar botón
                    $button.prop('disabled', false).text(buttonText);
                }
            });
        });
    });

})(jQuery);
