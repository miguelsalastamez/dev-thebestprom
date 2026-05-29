<?php
/**
 * WhatsApp Notification Handler using UltraMsg for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Base helper to send a WhatsApp message using wp_remote_post
 */
function tbp_soporte_send_whatsapp( $to, $message ) {
    $api_url = get_option( 'tbp_soporte_ultramsg_url', '' );
    $token   = get_option( 'tbp_soporte_ultramsg_token', '' );

    if ( empty( $api_url ) || empty( $token ) || empty( $to ) || empty( $message ) ) {
        return false;
    }

    // Clean phone number (leave only numbers)
    $clean_to = preg_replace( '/[^0-9]/', '', $to );
    
    // Ensure Mexico prefix or other country prefix has no leading + or 00
    if ( strlen( $clean_to ) === 10 ) {
        // If it's a 10 digit Mexican number, prefix with 521 for WhatsApp compatibility
        $clean_to = '521' . $clean_to;
    }

    // UltraMsg chat endpoint
    $endpoint = rtrim( $api_url, '/' ) . '/messages/chat';

    $body = array(
        'token' => $token,
        'to'    => $clean_to,
        'body'  => $message
    );

    $response = wp_remote_post( $endpoint, array(
        'body'      => $body,
        'timeout'   => 15,
        'blocking'  => false, // Non-blocking so it doesn't slow down AJAX responses
    ) );

    return ! is_wp_error( $response );
}

/**
 * Trigger WhatsApp notifications based on actions
 */
function tbp_soporte_trigger_whatsapp_notification( $ticket_id, $action, $comment_id = 0 ) {
    $ticket = get_post( $ticket_id );
    if ( ! $ticket || $ticket->post_type !== 'tbp_ticket' ) {
        return false;
    }

    $order_id = get_post_meta( $ticket_id, '_associated_order_id', true );
    $order = wc_get_order( $order_id );
    
    // Client info
    $client_name = get_the_author_meta( 'display_name', $ticket->post_author );
    $client_phone = '';
    if ( $order ) {
        $client_phone = $order->get_billing_phone();
    }
    
    $cats = wp_get_post_terms( $ticket_id, 'tbp_ticket_category' );
    $category_name = ! empty( $cats ) ? $cats[0]->name : __( 'General', 'tbp-soporte' );
    
    $admin_phone = get_option( 'tbp_soporte_admin_phone', '' );
    
    // Links
    $my_account_url = wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) );
    $ticket_link = add_query_arg( 'ver_ticket', $ticket_id, $my_account_url );
    $kanban_link = admin_url( 'admin.php?page=tbp-soporte-dashboard' );
    
    // Get comment content if reply
    $comment_text = '';
    if ( $comment_id ) {
        $comment = get_comment( $comment_id );
        if ( $comment ) {
            $comment_text = wp_strip_all_tags( $comment->comment_content );
            // Limit message snippet size
            if ( strlen( $comment_text ) > 150 ) {
                $comment_text = substr( $comment_text, 0, 147 ) . '...';
            }
        }
    }

    $message = '';
    $to = '';

    switch ( $action ) {
        case 'created_admin':
            if ( empty( $admin_phone ) ) return false;
            $to = $admin_phone;
            $message = sprintf(
                "📢 *Nuevo Ticket de Soporte TBP*\n\nSe ha creado el ticket *#%d*.\n*Cliente:* %s\n*Pedido:* #%d\n*Asunto:* %s\n*Categoría:* %s\n\nVer en Kanban: %s",
                $ticket_id,
                $client_name,
                $order_id,
                $ticket->post_title,
                $category_name,
                $kanban_link
            );
            break;

        case 'created_client':
            if ( empty( $client_phone ) ) return false;
            $to = $client_phone;
            $message = sprintf(
                "👋 Hola %s,\n\nTu ticket de soporte *#%d* ha sido recibido con éxito.\n*Categoría:* %s\n*Asunto:* %s\n\nTe notificaremos por este medio cuando nuestro equipo te responda. Puedes ver el estado de tu ticket aquí: %s",
                $client_name,
                $ticket_id,
                $category_name,
                $ticket->post_title,
                $ticket_link
            );
            break;

        case 'created_by_staff':
            if ( empty( $client_phone ) ) return false;
            $to = $client_phone;
            $message = sprintf(
                "📦 Hola %s,\n\nNuestro equipo de logística en sitio ha registrado una incidencia con tu entrega (Ticket *#%d*).\n*Detalle:* %s\n\nYa estamos revisando este reporte para darle solución a la brevedad. Puedes dar seguimiento aquí: %s",
                $client_name,
                $ticket_id,
                $ticket->post_title,
                $ticket_link
            );
            // También notificar al admin
            if ( ! empty( $admin_phone ) ) {
                $admin_msg = sprintf( "🚨 *Ticket Creado por Staff*\n\nTicket: *#%d*\nCliente: %s\nPedido: #%d\nAsunto: %s\n\nVer Kanban: %s", $ticket_id, $client_name, $order_id, $ticket->post_title, $kanban_link );
                tbp_soporte_send_whatsapp( $admin_phone, $admin_msg );
            }
            break;

        case 'reply_client':
            if ( empty( $client_phone ) ) return false;
            $to = $client_phone;
            $message = sprintf(
                "💬 Hola %s,\n\nNuestro equipo de soporte ha respondido a tu ticket *#%d*:\n\n_\"%s\"_\n\nPara responder o ver los detalles, ingresa aquí: %s",
                $client_name,
                $ticket_id,
                $comment_text,
                $ticket_link
            );
            break;

        case 'reply_admin':
            if ( empty( $admin_phone ) ) return false;
            $to = $admin_phone;
            $message = sprintf(
                "💬 *Respuesta en Ticket #%d*\n\nEl cliente *%s* ha respondido a su ticket de soporte:\n\n_\"%s\"_\n\nVer conversación: %s",
                $ticket_id,
                $client_name,
                $comment_text,
                $kanban_link
            );
            break;

        case 'status_solved':
            if ( empty( $client_phone ) ) return false;
            $to = $client_phone;
            $message = sprintf(
                "🎉 Hola %s,\n\nTu ticket de soporte *#%d* relacionado con el pedido *#%d* ha sido marcado como *SOLUCIONADO*.\n\nGracias por tu confianza. Si tienes alguna duda, puedes contactarnos de nuevo. ¡Que tengas un excelente día! 🌟",
                $client_name,
                $ticket_id,
                $order_id
            );
            break;
            
        default:
            return false;
    }

    return tbp_soporte_send_whatsapp( $to, $message );
}
