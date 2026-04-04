<?php
/**
 * Automated Messaging for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add a custom order note with the requested tone
 * 
 * @param int    $order_id
 * @param int    $amount   Quantity of tickets or amount of credit
 * @param string $type     'physical' or 'virtual'
 */
function tbp_actividades_add_custom_order_note( $order_id, $amount, $type = 'physical' ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $first_name = $order->get_billing_first_name();
    $order_number = $order->get_order_number();

    $unit = ( $type === 'physical' ) ? __( 'boletos', 'tbp-actividades' ) : __( 'abonos', 'tbp-actividades' );

    $message = sprintf(
        "¡Hola %s! 🎉 Tenemos excelentes noticias sobre tu pedido #%s.\n\n" .
        "Se han registrado %s %s a tu cuenta. ¡Felicidades, con esto tu paquete se paga prácticamente solo! 🎓✨\n\n" .
        "**Nota:** No olvides que todos tus pagos deben incluir tu número de pedido como referencia. Puedes pagar vía Tarjeta, Transferencia o en OXXO.\n\n" .
        "Atte. El equipo de **The Best Prom** - El Ticketmaster de las Graduaciones.",
        $first_name,
        $order_number,
        $amount,
        $unit
    );

    // Add as a customer note (this triggers an email if configured in WC)
    $order->add_order_note( $message, 1 );
}

/**
 * AJAX Handler to Send Pending Payment Reminder
 */
add_action( 'wp_ajax_tbp_actividades_send_payment_reminder', 'tbp_actividades_send_payment_reminder_ajax' );
function tbp_actividades_send_payment_reminder_ajax() {
    check_ajax_referer( 'tbp_send_reminder' );
    
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'shop_manager' ) ) {
        wp_send_json_error( 'Permisos insuficientes' );
    }

    $order_id = intval( $_POST['order_id'] );
    $order = wc_get_order( $order_id );
    
    if ( ! $order ) {
        wp_send_json_error( 'Pedido no encontrado' );
    }

    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
    if ( $delivered <= 0 ) {
        wp_send_json_error( 'Este pedido no tiene boletos entregados.' );
    }
    
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $last_delivery_date = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC LIMIT 1", $order_id ) );
    
    if ( $last_delivery_date ) {
        $date_str = date_i18n( 'd \d\e F \d\e Y', strtotime( $last_delivery_date ) );
    } else {
        $date_str = __( 'recientemente', 'tbp-actividades' );
    }

    $customer_name = $order->get_billing_first_name();
    $customer_email = $order->get_billing_email();
    $subject = 'Aviso Importante - Activa tu Paquete The Best Prom';

    ob_start();
    ?>
    <div style="font-family: Arial, sans-serif; color: #333; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 20px; background: #fff;">
        <p>Hola estimado(a) <strong><?php echo esc_html( $customer_name ); ?></strong>,</p>
        
        <p>Activa tu paquete realizando un depósito. El <strong><?php echo esc_html( $date_str ); ?></strong> te entregamos <strong><?php echo esc_html( $delivered ); ?> boletos</strong> y no hemos recibido pago de ellos, recuerda que el dinero que recaudes por la venta de boletos será abonado en su totalidad a tu paquete número de pedido <strong>#<?php echo esc_html( $order_id ); ?></strong>. Puedes pagarlo de diversas formas:</p>
        
        <div style="background-color: #fdf2f2; border: 1px solid #f5c6cb; padding: 15px; border-radius: 5px; margin: 25px 0; text-align: center;">
            <p style="margin: 0; color: #721c24; font-weight: bold; font-size: 16px;">I M P O R T A N T E</p>
            <p style="margin: 10px 0 0 0; color: #721c24; font-size: 14px;">COMO REFERENCIA, AGREGA P R I M E R O TU NÚMERO DE PEDIDO (#<?php echo esc_html( $order_id ); ?>), AL REALIZAR UN PAGO.</p>
        </div>

        <p><strong>BANCO BBVA</strong><br>
        <strong>CUENTA:</strong> 0115269059<br>
        <strong>CLABE INTERBANCARIA:</strong> 012580001152690590<br>
        <strong>Beneficiario de la cuenta:</strong> TERCER OCTANTE MS S DE RL DE CV</p>

        <p style="text-align: center; margin: 30px 0;">
            <a href="https://thebestprom.com" style="display: inline-block; padding: 12px 24px; background-color: #635bff; color: #ffffff; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">Pagar con tarjeta paga aquí</a>
        </p>

        <p>Depósito en efectivo en <strong>OXXO</strong> utiliza la CLABE INTERBANCARIA: <strong>012580001152690590</strong>.</p>

        <p>Esperamos tu pago.</p>
        
        <hr style="border: none; border-top: 1px solid #eee; margin-top: 40px; margin-bottom: 20px;">
        <p style="font-size: 12px; color: #999; text-align: center;">Equipo The Best Prom</p>
    </div>
    <?php
    $message = ob_get_clean();
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    if ( function_exists('WC') && WC()->mailer() ) {
        $mailer = WC()->mailer();
        $wrapped_message = $mailer->wrap_message( $subject, $message );
        $sent = wp_mail( $customer_email, $subject, $wrapped_message, $headers );
    } else {
        $sent = wp_mail( $customer_email, $subject, $message, $headers );
    }

    if ( $sent ) {
        $order->add_order_note( sprintf( __( 'Recordatorio de pago enviado (On-Hold) para %d boletos entregados.', 'tbp-actividades' ), $delivered ) );
        wp_send_json_success( 'Correo enviado exitosamente.' );
    } else {
        wp_send_json_error( 'El servidor no pudo enviar el correo.' );
    }
}

/**
 * AJAX Handler for Bulk Mass Messaging Array Action
 */
add_action( 'wp_ajax_tbp_actividades_send_bulk_message', 'tbp_actividades_send_bulk_message_ajax' );
function tbp_actividades_send_bulk_message_ajax() {
    check_ajax_referer( 'tbp_bulk_msg' );
    
    if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( 'Permisos insuficientes' );
    }

    $order_id = intval( $_POST['order_id'] );
    $subject  = sanitize_text_field( stripslashes( $_POST['subject'] ) );
    
    $template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( $_POST['template_id'] ) : '';
    
    if ( ! empty( $template_id ) ) {
        $templates = get_option( 'tbp_marketing_templates', array() );
        if ( isset( $templates[ $template_id ] ) ) {
            // Revert backslashes added by WordPress database saving just in case
            $message = stripslashes( $templates[ $template_id ]['html'] );
        } else {
            wp_send_json_error( 'Plantilla no encontrada en la BD.' );
        }
    } else {
        $message = wp_kses_post( stripslashes( $_POST['message'] ) );
    }
    
    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_send_json_error( 'Pedido no encontrado' );

    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
    $total_val = $order->get_total();
    $total_txt = '$' . number_format( (float) $total_val, 2 );
    
    $name      = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    $email     = $order->get_billing_email();

    if ( ! $email || empty( $email ) ) {
        wp_send_json_error( 'El pedido no tiene un correo válido.' );
    }

    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $last_delivery_date = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC LIMIT 1", $order_id ) );
    $date_str = $last_delivery_date ? date_i18n( 'd \d\e F \d\e Y', strtotime( $last_delivery_date ) ) : __( 'recientemente', 'tbp-actividades' );

    // Shortcode Replacements
    $search  = array( '[nombre]', '[pedido]', '[boletos]', '[fecha_entrega]', '[monto]' );
    $replace = array( $name, '#' . $order_id, $delivered, $date_str, $total_txt );
    
    $final_subject = str_ireplace( $search, $replace, $subject );
    $final_message = str_ireplace( $search, $replace, $message );
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    if ( ! empty( $template_id ) ) {
        // Send raw HTML since Canva provides a full <html> document.
        $sent = wp_mail( $email, $final_subject, $final_message, $headers );
    } else {
        // Add minimalist WP styling wrapper for manual messages
        ob_start();
        ?>
        <div style="font-family: inherit; color: inherit; line-height: 1.6; display: block; width: 100%;">
            <?php echo $final_message; ?>
            <hr style="border: none; border-top: 1px solid #eee; margin-top: 40px; margin-bottom: 20px;">
            <p style="font-size: 12px; color: #999; text-align: center;">Equipo The Best Prom</p>
        </div>
        <?php
        $html_body = ob_get_clean();
        
        if ( function_exists('WC') && WC()->mailer() ) {
            $mailer = WC()->mailer();
            $wrapped_message = $mailer->wrap_message( $final_subject, $html_body );
            $sent = wp_mail( $email, $final_subject, $wrapped_message, $headers );
        } else {
            $sent = wp_mail( $email, $final_subject, $html_body, $headers );
        }
    }

    if ( $sent ) {
        $order->add_order_note( sprintf( __( 'Mensaje masivo enviado: Asunto: "%s"', 'tbp-actividades' ), $final_subject ) );
        wp_send_json_success( 'Enviado a ' . $email );
    } else {
        wp_send_json_error( 'Falló envío - Posible error SMTP en el servidor.' );
    }
}
