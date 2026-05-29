<?php
/**
 * Email Notification Handler for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Hooks for automatic email triggers
add_action( 'wp_insert_post', 'tbp_soporte_email_trigger_on_create', 10, 3 );
add_action( 'wp_insert_comment', 'tbp_soporte_email_trigger_on_reply', 10, 2 );

/**
 * Send HTML Email helper
 */
function tbp_soporte_send_html_email( $to, $subject, $title, $body_content, $button_url = '', $button_text = '' ) {
    // Elegant WooCommerce-like email template
    $headers = array( 'Content-Type: text/html; charset=UTF-8' );
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title><?php echo esc_html( $subject ); ?></title>
    </head>
    <body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
        <table align="center" border="0" cellpadding="0" cellspacing="0" width="600" style="border-collapse: collapse; background-color: #ffffff; margin-top: 40px; margin-bottom: 40px; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06);">
            <!-- Header -->
            <tr>
                <td bgcolor="#0073aa" style="padding: 30px 40px; text-align: center;">
                    <h1 style="color: #ffffff; margin: 0; font-size: 24px; font-weight: 600;"><?php echo esc_html( $title ); ?></h1>
                </td>
            </tr>
            <!-- Content -->
            <tr>
                <td style="padding: 40px; color: #333333; font-size: 16px; line-height: 1.6;">
                    <?php echo $body_content; ?>
                    
                    <?php if ( $button_url && $button_text ) : ?>
                        <p style="text-align: center; margin-top: 35px; margin-bottom: 10px;">
                            <a href="<?php echo esc_url( $button_url ); ?>" style="background-color: #0073aa; color: #ffffff; text-decoration: none; padding: 12px 30px; border-radius: 5px; font-weight: bold; display: inline-block; box-shadow: 0 2px 5px rgba(0,0,0,0.1);"><?php echo esc_html( $button_text ); ?></a>
                        </p>
                    <?php endif; ?>
                </td>
            </tr>
            <!-- Footer -->
            <tr>
                <td bgcolor="#f9f9f9" style="padding: 20px 40px; text-align: center; color: #888888; font-size: 12px; border-top: 1px solid #eeeeee;">
                    <p style="margin: 0;">&copy; <?php echo date( 'Y' ); ?> The Best Prom. Todos los derechos reservados.</p>
                </td>
            </tr>
        </table>
    </body>
    </html>
    <?php
    $message = ob_get_clean();

    return wp_mail( $to, $subject, $message, $headers );
}

/**
 * Trigger email when ticket is created
 */
function tbp_soporte_email_trigger_on_create( $post_id, $post, $update ) {
    // Only send on new tickets, not updates
    if ( $update || $post->post_type !== 'tbp_ticket' ) {
        return;
    }

    $order_id = get_post_meta( $post_id, '_associated_order_id', true );
    $client_name = get_the_author_meta( 'display_name', $post->post_author );
    $client_email = get_the_author_meta( 'user_email', $post->post_author );
    
    $cats = wp_get_post_terms( $post_id, 'tbp_ticket_category' );
    $category_name = ! empty( $cats ) ? $cats[0]->name : __( 'General', 'tbp-soporte' );

    $my_account_url = wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) );
    $ticket_link = add_query_arg( 'ver_ticket', $post_id, $my_account_url );
    $kanban_link = admin_url( 'admin.php?page=tbp-soporte-dashboard' );

    // 1. Send Email to Admin
    $admin_email = get_option( 'admin_email' );
    $admin_subject = sprintf( '[Soporte TBP] Nuevo Ticket #%d - %s', $post_id, $post->post_title );
    $admin_title = 'Nuevo Ticket de Soporte';
    $admin_body = sprintf(
        '<p>Hola Administrador,</p>
         <p>Se ha creado un nuevo ticket de soporte en la plataforma:</p>
         <ul>
             <li><strong>Ticket ID:</strong> #%d</li>
             <li><strong>Cliente:</strong> %s (%s)</li>
             <li><strong>Pedido WooCommerce:</strong> #%d</li>
             <li><strong>Categoría:</strong> %s</li>
             <li><strong>Asunto:</strong> %s</li>
         </ul>
         <p><strong>Descripción inicial:</strong></p>
         <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px; margin-bottom: 20px;">%s</div>',
        $post_id,
        $client_name,
        $client_email,
        $order_id,
        $category_name,
        esc_html( $post->post_title ),
        nl2br( esc_html( $post->post_content ) )
    );
    tbp_soporte_send_html_email( $admin_email, $admin_subject, $admin_title, $admin_body, $kanban_link, 'Ver en Tablero Kanban' );

    // 2. Send Confirmation Email to Client
    $client_subject = sprintf( 'Hemos recibido tu Ticket de Soporte #%d', $post_id );
    $client_title = 'Confirmación de Ticket';
    $client_body = sprintf(
        '<p>Hola %s,</p>
         <p>Queremos confirmarte que hemos recibido con éxito tu solicitud de soporte.</p>
         <ul>
             <li><strong>Ticket ID:</strong> #%d</li>
             <li><strong>Pedido Relacionado:</strong> #%d</li>
             <li><strong>Categoría:</strong> %s</li>
             <li><strong>Asunto:</strong> %s</li>
         </ul>
         <p>Nuestro equipo de atención al cliente ya está revisando tu caso y te responderá a la brevedad. Recibirás una notificación por este medio y por WhatsApp cuando haya novedades.</p>',
        $client_name,
        $post_id,
        $order_id,
        $category_name,
        esc_html( $post->post_title )
    );
    tbp_soporte_send_html_email( $client_email, $client_subject, $client_title, $client_body, $ticket_link, 'Ver Detalles del Ticket' );
}

/**
 * Trigger email when reply is submitted
 */
function tbp_soporte_email_trigger_on_reply( $comment_id, $comment ) {
    $ticket_id = $comment->comment_post_ID;
    $ticket = get_post( $ticket_id );
    
    if ( ! $ticket || $ticket->post_type !== 'tbp_ticket' ) {
        return;
    }

    // Skip internal notes completely
    $is_internal = get_comment_meta( $comment_id, '_is_internal_note', true );
    if ( ! empty( $is_internal ) ) {
        return;
    }

    $order_id = get_post_meta( $ticket_id, '_associated_order_id', true );
    $client_name = get_the_author_meta( 'display_name', $ticket->post_author );
    $client_email = get_the_author_meta( 'user_email', $ticket->post_author );

    $my_account_url = wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) );
    $ticket_link = add_query_arg( 'ver_ticket', $ticket_id, $my_account_url );
    $kanban_link = admin_url( 'admin.php?page=tbp-soporte-dashboard' );

    $is_staff = user_can( $comment->user_id, 'edit_posts' );

    if ( $is_staff ) {
        // Staff replied -> notify client
        $subject = sprintf( 'Nueva respuesta en tu Ticket #%d', $ticket_id );
        $title = 'Respuesta de Soporte';
        $body = sprintf(
            '<p>Hola %s,</p>
             <p>Nuestro equipo de soporte ha respondido a tu ticket <strong>#%d</strong>:</p>
             <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px; margin-bottom: 20px;">%s</div>
             <p>Puedes chatear con nosotros haciendo clic en el siguiente botón:</p>',
            $client_name,
            $ticket_id,
            nl2br( esc_html( $comment->comment_content ) )
        );
        tbp_soporte_send_html_email( $client_email, $subject, $title, $body, $ticket_link, 'Responder en el Ticket' );
    } else {
        // Client replied -> notify admin
        $admin_email = get_option( 'admin_email' );
        $subject = sprintf( '[Soporte TBP] Respuesta de Cliente en Ticket #%d', $ticket_id );
        $title = 'Nueva Respuesta en Ticket';
        $body = sprintf(
            '<p>Hola Administrador,</p>
             <p>El cliente <strong>%s</strong> ha dejado una nueva respuesta en el ticket <strong>#%d</strong> (Pedido #%d):</p>
             <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0073aa; border-radius: 4px; margin-bottom: 20px;">%s</div>',
            $client_name,
            $ticket_id,
            $order_id,
            nl2br( esc_html( $comment->comment_content ) )
        );
        tbp_soporte_send_html_email( $admin_email, $subject, $title, $body, $kanban_link, 'Ver en Tablero Kanban' );
    }
}
