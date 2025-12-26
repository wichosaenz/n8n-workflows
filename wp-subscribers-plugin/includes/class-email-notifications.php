<?php
/**
 * Clase para manejar notificaciones por email
 */

if (!defined('ABSPATH')) {
    exit;
}

class WP_Subscribers_Email_Notifications {

    /**
     * Configuración de email
     */
    private $settings = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->settings = get_option('wp_subscribers_settings', array());
    }

    /**
     * Enviar notificación de nuevo suscriptor
     */
    public function send_new_subscriber_notification($subscriber_data) {
        // Verificar si las notificaciones están activadas
        if (!$this->is_notifications_enabled()) {
            return array('success' => false, 'message' => 'Notificaciones desactivadas');
        }

        // Obtener destinatarios
        $recipients = $this->get_notification_recipients();
        if (empty($recipients)) {
            return array('success' => false, 'message' => 'No hay destinatarios configurados');
        }

        // Preparar datos del email
        $subject = $this->get_email_subject();
        $message = $this->build_email_html($subscriber_data);
        $headers = $this->get_email_headers();

        // Configurar PHPMailer con SMTP
        add_action('phpmailer_init', array($this, 'configure_smtp'));

        // Enviar email a cada destinatario
        $results = array();
        foreach ($recipients as $recipient) {
            $sent = wp_mail($recipient, $subject, $message, $headers);
            $results[$recipient] = $sent;
        }

        // Remover hook
        remove_action('phpmailer_init', array($this, 'configure_smtp'));

        // Verificar si al menos uno se envió correctamente
        $success = in_array(true, $results, true);

        return array(
            'success' => $success,
            'message' => $success ? 'Email enviado correctamente' : 'Error al enviar email',
            'details' => $results
        );
    }

    /**
     * Configurar PHPMailer para usar SMTP
     */
    public function configure_smtp($phpmailer) {
        // Verificar si SMTP está configurado
        if (empty($this->settings['smtp_host']) || empty($this->settings['smtp_user'])) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host = $this->settings['smtp_host'];
        $phpmailer->SMTPAuth = true;
        $phpmailer->Username = $this->settings['smtp_user'];
        $phpmailer->Password = $this->settings['smtp_password'];
        $phpmailer->SMTPSecure = isset($this->settings['smtp_security']) ? $this->settings['smtp_security'] : 'tls';
        $phpmailer->Port = isset($this->settings['smtp_port']) ? intval($this->settings['smtp_port']) : 587;
        $phpmailer->From = $this->settings['smtp_user'];
        $phpmailer->FromName = isset($this->settings['smtp_from_name']) ? $this->settings['smtp_from_name'] : get_bloginfo('name');
        $phpmailer->CharSet = 'UTF-8';

        // Debug opcional (solo para desarrollo)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $phpmailer->SMTPDebug = 0; // 0 = off, 1 = client, 2 = client and server
        }
    }

    /**
     * Construir HTML del email
     */
    private function build_email_html($subscriber_data) {
        $site_name = get_bloginfo('name');
        $site_url = home_url();

        // Formatear fecha en español
        $fecha_hora = date_i18n('l, j \d\e F \d\e Y \a \l\a\s g:i A', strtotime($subscriber_data['subscribed_date']));

        $html = '<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nueva Suscripción</title>
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 8px 8px 0 0;">
                            <h1 style="margin: 0; color: #ffffff; font-size: 28px;">🎉 Nueva Suscripción</h1>
                            <p style="margin: 10px 0 0 0; color: #f0f0f0; font-size: 14px;">' . esc_html($site_name) . '</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                                ¡Hola! 👋
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 16px; color: #333333; line-height: 1.6;">
                                Una nueva persona se ha suscrito a tu sitio web. A continuación encontrarás los detalles:
                            </p>

                            <!-- Subscriber Details -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8f9fa; border-radius: 6px; margin: 20px 0;">
                                <tr>
                                    <td style="padding: 20px;">
                                        <table cellpadding="8" cellspacing="0" border="0" width="100%">
                                            <tr>
                                                <td style="font-weight: bold; color: #666666; width: 40%;">👤 Nombre:</td>
                                                <td style="color: #333333;">' . esc_html($subscriber_data['name']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #666666;">📧 Email:</td>
                                                <td style="color: #333333;"><a href="mailto:' . esc_attr($subscriber_data['email']) . '" style="color: #667eea; text-decoration: none;">' . esc_html($subscriber_data['email']) . '</a></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #666666;">📅 Fecha y Hora:</td>
                                                <td style="color: #333333;">' . esc_html($fecha_hora) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #666666;">🌐 Sitio Web:</td>
                                                <td style="color: #333333;"><a href="' . esc_url($subscriber_data['website_url']) . '" style="color: #667eea; text-decoration: none;">' . esc_html($subscriber_data['website_url']) . '</a></td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #666666;">🖥️ IP:</td>
                                                <td style="color: #333333;">' . esc_html($subscriber_data['ip_address']) . '</td>
                                            </tr>
                                            <tr>
                                                <td style="font-weight: bold; color: #666666;">📍 Origen:</td>
                                                <td style="color: #333333;">' . esc_html($subscriber_data['source'] ?? 'shortcode') . '</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table cellpadding="0" cellspacing="0" border="0" width="100%" style="margin: 30px 0;">
                                <tr>
                                    <td align="center">
                                        <a href="' . admin_url('admin.php?page=wp-subscribers-list') . '" style="display: inline-block; padding: 14px 30px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">Ver Lista de Suscriptores</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8f9fa; padding: 20px 30px; text-align: center; border-radius: 0 0 8px 8px; border-top: 1px solid #e0e0e0;">
                            <p style="margin: 0; font-size: 13px; color: #666666;">
                                Este es un email automático generado por <strong>WP Subscribers Manager</strong>
                            </p>
                            <p style="margin: 10px 0 0 0; font-size: 12px; color: #999999;">
                                Puedes desactivar estas notificaciones desde el panel de administración
                            </p>
                        </td>
                    </tr>
                </table>

                <!-- Copyright -->
                <table cellpadding="0" cellspacing="0" border="0" width="600" style="margin-top: 20px;">
                    <tr>
                        <td style="text-align: center; padding: 10px;">
                            <p style="margin: 0; font-size: 12px; color: #999999;">
                                © ' . date('Y') . ' ' . esc_html($site_name) . ' - Todos los derechos reservados
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';

        return $html;
    }

    /**
     * Enviar email de prueba
     */
    public function send_test_email($test_recipient) {
        $test_data = array(
            'name' => 'Juan Pérez (Prueba)',
            'email' => 'test@ejemplo.com',
            'subscribed_date' => current_time('mysql'),
            'website_url' => home_url(),
            'ip_address' => '192.168.1.1',
            'source' => 'Test Email'
        );

        // Configurar PHPMailer con SMTP
        add_action('phpmailer_init', array($this, 'configure_smtp'));

        $subject = '[PRUEBA] ' . $this->get_email_subject();
        $message = $this->build_email_html($test_data);
        $headers = $this->get_email_headers();

        $sent = wp_mail($test_recipient, $subject, $message, $headers);

        // Remover hook
        remove_action('phpmailer_init', array($this, 'configure_smtp'));

        return array(
            'success' => $sent,
            'message' => $sent ? 'Email de prueba enviado correctamente' : 'Error al enviar email de prueba'
        );
    }

    /**
     * Verificar si las notificaciones están activadas
     */
    private function is_notifications_enabled() {
        return isset($this->settings['email_notifications_enabled']) &&
               $this->settings['email_notifications_enabled'] === 'yes';
    }

    /**
     * Obtener destinatarios de notificaciones
     */
    private function get_notification_recipients() {
        if (!isset($this->settings['notification_recipients']) || empty($this->settings['notification_recipients'])) {
            return array();
        }

        $recipients_string = $this->settings['notification_recipients'];
        $recipients = array_map('trim', explode(',', $recipients_string));

        // Validar emails
        $valid_recipients = array();
        foreach ($recipients as $recipient) {
            if (is_email($recipient)) {
                $valid_recipients[] = $recipient;
            }
        }

        return $valid_recipients;
    }

    /**
     * Obtener asunto del email
     */
    private function get_email_subject() {
        $default_subject = '🎉 Nueva suscripción en ' . get_bloginfo('name');
        return isset($this->settings['email_subject']) && !empty($this->settings['email_subject'])
            ? $this->settings['email_subject']
            : $default_subject;
    }

    /**
     * Obtener headers del email
     */
    private function get_email_headers() {
        $from_name = isset($this->settings['smtp_from_name']) ? $this->settings['smtp_from_name'] : get_bloginfo('name');
        $from_email = isset($this->settings['smtp_user']) ? $this->settings['smtp_user'] : get_bloginfo('admin_email');

        return array(
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $from_name . ' <' . $from_email . '>'
        );
    }

    /**
     * Verificar configuración SMTP (método público)
     */
    public function is_smtp_configured() {
        return !empty($this->settings['smtp_host']) &&
               !empty($this->settings['smtp_user']) &&
               !empty($this->settings['smtp_password']);
    }

    /**
     * Verificar si las notificaciones están activadas (método público)
     */
    public function notifications_enabled() {
        return $this->is_notifications_enabled();
    }
}
