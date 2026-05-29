<?php
/**
 * AJAX Handlers for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Client hooks
add_action( 'wp_ajax_tbp_soporte_create_ticket', 'tbp_soporte_ajax_create_ticket' );
add_action( 'wp_ajax_tbp_soporte_submit_reply', 'tbp_soporte_ajax_submit_reply' );

// Admin hooks
add_action( 'wp_ajax_tbp_soporte_change_status', 'tbp_soporte_ajax_change_status' );
add_action( 'wp_ajax_tbp_soporte_add_internal_note', 'tbp_soporte_ajax_add_internal_note' );
add_action( 'wp_ajax_tbp_soporte_change_category', 'tbp_soporte_ajax_change_category' );
add_action( 'wp_ajax_tbp_soporte_staff_create_ticket', 'tbp_soporte_ajax_staff_create_ticket' );
add_action( 'wp_ajax_tbp_soporte_search_orders', 'tbp_soporte_ajax_search_orders' );
add_action( 'wp_ajax_tbp_soporte_get_order_details', 'tbp_soporte_ajax_get_order_details' );

/**
 * Handle Ticket Creation via AJAX
 */
function tbp_soporte_ajax_create_ticket() {
    check_ajax_referer( 'tbp_soporte_nonce', 'nonce' );

    $current_user_id = get_current_user_id();
    if ( ! $current_user_id ) {
        wp_send_json_error( array( 'message' => __( 'Debes iniciar sesión para crear un ticket.', 'tbp-soporte' ) ) );
    }

    $order_id   = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $category   = isset( $_POST['category'] ) ? sanitize_text_field( $_POST['category'] ) : '';
    $title      = isset( $_POST['title'] ) ? sanitize_text_field( $_POST['title'] ) : '';
    $description = isset( $_POST['description'] ) ? sanitize_textarea_field( $_POST['description'] ) : '';

    // Validate WooCommerce Order belongs to user (or user is administrator)
    if ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order || ( (int) $order->get_customer_id() !== $current_user_id && ! current_user_can( 'edit_posts' ) ) ) {
            wp_send_json_error( array( 'message' => __( 'Pedido no válido.', 'tbp-soporte' ) ) );
        }
    } else {
        wp_send_json_error( array( 'message' => __( 'Es necesario vincular un pedido.', 'tbp-soporte' ) ) );
    }

    if ( empty( $category ) || empty( $title ) || empty( $description ) ) {
        wp_send_json_error( array( 'message' => __( 'Por favor completa todos los campos requeridos.', 'tbp-soporte' ) ) );
    }

    // Check if category exists
    $term = get_term_by( 'slug', $category, 'tbp_ticket_category' );
    if ( ! $term ) {
        wp_send_json_error( array( 'message' => __( 'Categoría de soporte no válida.', 'tbp-soporte' ) ) );
    }

    // Create the ticket CPT
    $ticket_id = wp_insert_post( array(
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'private',
        'post_type'    => 'tbp_ticket',
        'post_author'  => $current_user_id
    ) );

    if ( is_wp_error( $ticket_id ) || ! $ticket_id ) {
        wp_send_json_error( array( 'message' => __( 'Error al crear el ticket. Inténtalo de nuevo.', 'tbp-soporte' ) ) );
    }

    // Set taxonomy
    wp_set_post_terms( $ticket_id, array( $term->term_id ), 'tbp_ticket_category' );

    // Set post metas
    update_post_meta( $ticket_id, '_associated_order_id', $order_id );
    update_post_meta( $ticket_id, '_ticket_status', 'enviado' );
    update_post_meta( $ticket_id, '_priority', 'media' ); // default priority

    // Handle File Upload if exists
    if ( ! empty( $_FILES['ticket_file'] ) && ! empty( $_FILES['ticket_file']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Allowed file types check (security)
        $allowed = array( 'jpg', 'jpeg', 'png', 'pdf' );
        $ext = pathinfo( $_FILES['ticket_file']['name'], PATHINFO_EXTENSION );
        if ( ! in_array( strtolower( $ext ), $allowed ) ) {
            wp_send_json_error( array( 'message' => __( 'Solo se permiten imágenes (JPG, PNG) y archivos PDF.', 'tbp-soporte' ) ) );
        }

        // Upload and attach to post
        $attachment_id = media_handle_upload( 'ticket_file', $ticket_id );
        if ( is_wp_error( $attachment_id ) ) {
            wp_send_json_error( array( 'message' => __( 'Error al subir el archivo adjunto.', 'tbp-soporte' ) ) );
        }
    }

    // Trigger WhatsApp notification for administrator
    if ( function_exists( 'tbp_soporte_trigger_whatsapp_notification' ) ) {
        tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'created_admin' );
        tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'created_client' );
    }

    wp_send_json_success( array(
        'message'      => __( '¡Ticket creado exitosamente!', 'tbp-soporte' ),
        'redirect_url' => add_query_arg( 'ver_ticket', $ticket_id, wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) ) )
    ) );
}

/**
 * Handle Reply (Comment) submission via AJAX
 */
function tbp_soporte_ajax_submit_reply() {
    // Permitir tanto el nonce del frontend como el del dashboard de admin
    $nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : '';
    if ( ! wp_verify_nonce( $nonce, 'tbp_soporte_nonce' ) && ! wp_verify_nonce( $nonce, 'tbp_soporte_admin_nonce' ) ) {
        wp_send_json_error( array( 'message' => __( 'Sesión caducada o error de seguridad. Recarga la página.', 'tbp-soporte' ) ) );
    }

    $current_user_id = get_current_user_id();
    if ( ! $current_user_id ) {
        wp_send_json_error( array( 'message' => __( 'Debes iniciar sesión para enviar una respuesta.', 'tbp-soporte' ) ) );
    }

    $ticket_id       = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
    $comment_content = isset( $_POST['comment_content'] ) ? sanitize_textarea_field( $_POST['comment_content'] ) : '';

    $ticket = get_post( $ticket_id );
    if ( ! $ticket || $ticket->post_type !== 'tbp_ticket' ) {
        wp_send_json_error( array( 'message' => __( 'Ticket no válido.', 'tbp-soporte' ) ) );
    }

    // Security check: only author or administrator can comment
    $is_admin = current_user_can( 'edit_posts' );
    if ( (int) $ticket->post_author !== $current_user_id && ! $is_admin ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos para responder en este ticket.', 'tbp-soporte' ) ) );
    }

    // Check if ticket is solved
    $status = get_post_meta( $ticket_id, '_ticket_status', true );
    if ( $status === 'solucionado' ) {
        wp_send_json_error( array( 'message' => __( 'Este ticket está solucionado y cerrado.', 'tbp-soporte' ) ) );
    }

    if ( empty( $comment_content ) ) {
        wp_send_json_error( array( 'message' => __( 'El mensaje no puede estar vacío.', 'tbp-soporte' ) ) );
    }

    $user = wp_get_current_user();

    // Insert comment
    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $comment_content,
        'user_id'              => $current_user_id,
        'comment_approved'     => 1,
    ) );

    if ( ! $comment_id ) {
        wp_send_json_error( array( 'message' => __( 'Error al guardar la respuesta.', 'tbp-soporte' ) ) );
    }

    // Update ticket status
    if ( $is_admin ) {
        // If staff responds, status is waiting for client
        update_post_meta( $ticket_id, '_ticket_status', 'esperando_cliente' );
    } else {
        // If client responds, status is set to revision
        update_post_meta( $ticket_id, '_ticket_status', 'en_revision' );
    }
    
    // Touch post to update date modified
    wp_update_post( array(
        'ID'            => $ticket_id,
        'post_modified' => current_time( 'mysql' ),
        'post_modified_gmt' => current_time( 'mysql', 1 )
    ) );

    // Handle File Upload if exists
    $attachment_url = '';
    $attachment_name = '';
    if ( ! empty( $_FILES['reply_file'] ) && ! empty( $_FILES['reply_file']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $allowed = array( 'jpg', 'jpeg', 'png', 'pdf' );
        $ext = pathinfo( $_FILES['reply_file']['name'], PATHINFO_EXTENSION );
        if ( in_array( strtolower( $ext ), $allowed ) ) {
            $attachment_id = media_handle_upload( 'reply_file', $ticket_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                $attachment_url = wp_get_attachment_url( $attachment_id );
                $attachment_name = get_the_title( $attachment_id );
                
                // Add attachment details to comment meta
                update_comment_meta( $comment_id, '_comment_attachment_url', $attachment_url );
                update_comment_meta( $comment_id, '_comment_attachment_name', $attachment_name );
            }
        }
    }

    // Trigger WhatsApp notification
    if ( function_exists( 'tbp_soporte_trigger_whatsapp_notification' ) ) {
        if ( $is_admin ) {
            // Notify client of staff reply
            tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'reply_client', $comment_id );
        } else {
            // Notify staff/admin of client reply
            tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'reply_admin', $comment_id );
        }
    }

    // Return HTML of new message to insert dynamically
    $author_label = $is_admin ? __( 'Soporte', 'tbp-soporte' ) : __( 'Tú', 'tbp-soporte' );
    $msg_class = $is_admin ? 'tbp-soporte-message-staff' : 'tbp-soporte-message-client';

    ob_start();
    ?>
    <div class="tbp-soporte-message <?php echo esc_attr( $msg_class ); ?>">
        <div class="tbp-soporte-message-author">
            <?php echo esc_html( $user->display_name ); ?> (<?php echo esc_html( $author_label ); ?>)
        </div>
        <div class="tbp-soporte-message-content">
            <?php echo wpautop( esc_html( $comment_content ) ); ?>
            <?php if ( $attachment_url ) : ?>
                <div class="tbp-soporte-message-attachments">
                    <strong><?php esc_html_e( 'Adjunto:', 'tbp-soporte' ); ?></strong> 
                    <a href="<?php echo esc_url( $attachment_url ); ?>" target="_blank"><?php echo esc_html( $attachment_name ); ?></a>
                </div>
            <?php endif; ?>
        </div>
        <div class="tbp-soporte-message-time"><?php echo esc_html( current_time( 'H:i' ) ); ?></div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'    => $html,
        'message' => __( 'Mensaje enviado correctamente.', 'tbp-soporte' )
    ) );
}

/**
 * Handle Ticket Status Change from Kanban Dashboard via AJAX
 */
function tbp_soporte_ajax_change_status() {
    check_ajax_referer( 'tbp_soporte_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos de administrador.', 'tbp-soporte' ) ) );
    }

    $ticket_id  = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
    $new_status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

    $valid_statuses = array( 'enviado', 'en_revision', 'esperando_cliente', 'solucionado' );
    if ( ! in_array( $new_status, $valid_statuses ) ) {
        wp_send_json_error( array( 'message' => __( 'Estado no válido.', 'tbp-soporte' ) ) );
    }

    $ticket = get_post( $ticket_id );
    if ( ! $ticket || $ticket->post_type !== 'tbp_ticket' ) {
        wp_send_json_error( array( 'message' => __( 'Ticket no válido.', 'tbp-soporte' ) ) );
    }

    $old_status = get_post_meta( $ticket_id, '_ticket_status', true );
    if ( $old_status === $new_status ) {
        wp_send_json_success();
    }

    // Update status
    update_post_meta( $ticket_id, '_ticket_status', $new_status );
    
    // Touch post modification date
    wp_update_post( array(
        'ID'            => $ticket_id,
        'post_modified' => current_time( 'mysql' ),
        'post_modified_gmt' => current_time( 'mysql', 1 )
    ) );

    // Trigger WhatsApp notification to client if status is Solucionado
    if ( $new_status === 'solucionado' && function_exists( 'tbp_soporte_trigger_whatsapp_notification' ) ) {
        tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'status_solved' );
    }

    wp_send_json_success( array( 'message' => __( 'Estado actualizado.', 'tbp-soporte' ) ) );
}

/**
 * Handle Add Internal Note via AJAX
 */
function tbp_soporte_ajax_add_internal_note() {
    check_ajax_referer( 'tbp_soporte_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'tbp-soporte' ) ) );
    }

    $ticket_id    = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
    $note_content = isset( $_POST['note_content'] ) ? sanitize_textarea_field( $_POST['note_content'] ) : '';

    $ticket = get_post( $ticket_id );
    if ( ! $ticket || $ticket->post_type !== 'tbp_ticket' ) {
        wp_send_json_error( array( 'message' => __( 'Ticket no válido.', 'tbp-soporte' ) ) );
    }

    if ( empty( $note_content ) ) {
        wp_send_json_error( array( 'message' => __( 'La nota no puede estar vacía.', 'tbp-soporte' ) ) );
    }

    $user = wp_get_current_user();

    // Insert comment as internal note
    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $note_content,
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
    ) );

    if ( ! $comment_id ) {
        wp_send_json_error( array( 'message' => __( 'Error al guardar la nota interna.', 'tbp-soporte' ) ) );
    }

    // Set comment meta as internal note
    update_comment_meta( $comment_id, '_is_internal_note', '1' );

    // Return HTML of new note to insert dynamically
    ob_start();
    ?>
    <div class="tbp-soporte-message tbp-soporte-message-internal-note" id="comment-<?php echo esc_attr( $comment_id ); ?>">
        <div class="tbp-soporte-message-author">
            📝 <?php echo esc_html( $user->display_name ); ?> (<?php esc_html_e( 'Nota Interna', 'tbp-soporte' ); ?>)
        </div>
        <div class="tbp-soporte-message-content">
            <?php echo wpautop( esc_html( $note_content ) ); ?>
        </div>
        <div class="tbp-soporte-message-time"><?php echo esc_html( current_time( 'H:i' ) ); ?></div>
    </div>
    <?php
    $html = ob_get_clean();

    wp_send_json_success( array(
        'html'    => $html,
        'message' => __( 'Nota interna guardada.', 'tbp-soporte' )
    ) );
}
/**
 * Handle Ticket Creation by Staff via AJAX
 */
function tbp_soporte_ajax_staff_create_ticket() {
    check_ajax_referer( 'tbp_soporte_staff_nonce', 'staff_nonce' );

    $user = wp_get_current_user();
    if ( ! $user->exists() || in_array( 'customer', (array) $user->roles, true ) || in_array( 'subscriber', (array) $user->roles, true ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos para realizar esta acción.', 'tbp-soporte' ) ) );
    }
    $current_user_id = $user->ID;

    $order_id   = isset( $_POST['staff_order_id'] ) ? intval( $_POST['staff_order_id'] ) : 0;
    $incidence  = isset( $_POST['staff_incidence_type'] ) ? sanitize_text_field( $_POST['staff_incidence_type'] ) : '';
    $comments   = isset( $_POST['staff_comments'] ) ? sanitize_textarea_field( $_POST['staff_comments'] ) : '';

    if ( ! $order_id || empty( $incidence ) || empty( $comments ) ) {
        wp_send_json_error( array( 'message' => __( 'Por favor completa todos los campos requeridos.', 'tbp-soporte' ) ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( array( 'message' => __( 'El número de pedido no existe.', 'tbp-soporte' ) ) );
    }

    $customer_id = $order->get_customer_id();
    if ( ! $customer_id ) {
        wp_send_json_error( array( 'message' => __( 'El pedido no tiene un cliente registrado válido.', 'tbp-soporte' ) ) );
    }

    // Assign to category "Entregas"
    $term = get_term_by( 'name', 'Entregas', 'tbp_ticket_category' );
    if ( ! $term ) {
        // Fallback: create category if not exists
        $term_info = wp_insert_term( 'Entregas', 'tbp_ticket_category' );
        if ( ! is_wp_error( $term_info ) ) {
            $term = get_term( $term_info['term_id'], 'tbp_ticket_category' );
        }
    }

    $title = sprintf( 'Incidencia en Entrega: %s', $incidence );
    $description = sprintf( "Reportado por equipo de entregas.\n\nTipo de Incidencia: %s\n\nComentarios:\n%s", $incidence, $comments );

    // Create the ticket CPT, assigning author to the CUSTOMER, not the staff
    $ticket_id = wp_insert_post( array(
        'post_title'   => $title,
        'post_content' => $description,
        'post_status'  => 'private',
        'post_type'    => 'tbp_ticket',
        'post_author'  => $customer_id // Important: Customer owns the ticket
    ) );

    if ( is_wp_error( $ticket_id ) || ! $ticket_id ) {
        wp_send_json_error( array( 'message' => __( 'Error al crear el ticket.', 'tbp-soporte' ) ) );
    }

    if ( $term ) {
        wp_set_post_terms( $ticket_id, array( $term->term_id ), 'tbp_ticket_category' );
    }

    // Set post metas
    update_post_meta( $ticket_id, '_associated_order_id', $order_id );
    update_post_meta( $ticket_id, '_ticket_status', 'en_revision' ); // Directly to en_revision
    update_post_meta( $ticket_id, '_priority', 'alta' ); // Delivery issues are usually high priority

    // Add Internal Note from Staff
    $user = wp_get_current_user();
    $internal_note = sprintf( "Ticket levantado en campo por el staff: %s.", $user->display_name );
    $comment_id = wp_insert_comment( array(
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_content'      => $internal_note,
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
    ) );
    if ( $comment_id ) {
        update_comment_meta( $comment_id, '_is_internal_note', '1' );
    }

    // Handle File Upload if exists
    if ( ! empty( $_FILES['staff_ticket_file'] ) && ! empty( $_FILES['staff_ticket_file']['name'] ) ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $allowed = array( 'jpg', 'jpeg', 'png', 'pdf' );
        $ext = pathinfo( $_FILES['staff_ticket_file']['name'], PATHINFO_EXTENSION );
        if ( in_array( strtolower( $ext ), $allowed ) ) {
            media_handle_upload( 'staff_ticket_file', $ticket_id );
        }
    }

    // Trigger Notifications
    if ( function_exists( 'tbp_soporte_trigger_whatsapp_notification' ) ) {
        // Use a special action for staff created
        tbp_soporte_trigger_whatsapp_notification( $ticket_id, 'created_by_staff' );
    }

    wp_send_json_success( array(
        'message' => __( '¡Ticket generado y cliente notificado correctamente!', 'tbp-soporte' )
    ) );
}

/**
 * Handle changing category via AJAX (Admin / Frontend Staff)
 */
function tbp_soporte_ajax_change_category() {
    check_ajax_referer( 'tbp_soporte_admin_nonce', 'nonce' );

    $ticket_id = isset( $_POST['ticket_id'] ) ? intval( $_POST['ticket_id'] ) : 0;
    $category_id = isset( $_POST['category_id'] ) ? intval( $_POST['category_id'] ) : 0;

    if ( ! $ticket_id || ! $category_id ) {
        wp_send_json_error( array( 'message' => __( 'Datos incompletos.', 'tbp-soporte' ) ) );
    }

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'tbp-soporte' ) ) );
    }

    $term = get_term( $category_id, 'tbp_ticket_category' );
    if ( ! $term || is_wp_error( $term ) ) {
        wp_send_json_error( array( 'message' => __( 'Categoría inválida.', 'tbp-soporte' ) ) );
    }

    // Set the category
    wp_set_post_terms( $ticket_id, array( $category_id ), 'tbp_ticket_category', false );

    // Add an internal note tracking the change
    $user = wp_get_current_user();
    $note_content = sprintf( __( 'Categoría actualizada a: %s por %s', 'tbp-soporte' ), $term->name, $user->display_name );
    
    $commentdata = array(
        'comment_post_ID'      => $ticket_id,
        'comment_author'       => 'Sistema',
        'comment_content'      => $note_content,
        'comment_type'         => 'tbp_internal_note',
        'user_id'              => $user->ID,
        'comment_approved'     => 1,
    );
    
    $comment_id = wp_insert_comment( $commentdata );
    if ( $comment_id ) {
        add_comment_meta( $comment_id, '_is_internal_note', 'yes' );
    }

    wp_send_json_success( array( 
        'message' => __( 'Categoría actualizada.', 'tbp-soporte' ),
        'category_name' => $term->name
    ) );
}

/**
 * AJAX: Buscar pedidos por ID, Nombre o Correo (Staff Only)
 */
function tbp_soporte_ajax_search_orders() {
    check_ajax_referer( 'tbp_soporte_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'tbp-soporte' ) ) );
    }

    $term = isset( $_POST['term'] ) ? sanitize_text_field( $_POST['term'] ) : '';
    if ( empty( $term ) ) {
        wp_send_json_success( array( 'results' => array() ) );
    }

    $args = array(
        'limit' => 10,
        'return' => 'ids',
    );

    if ( is_numeric( $term ) ) {
        // Search by ID
        $args['post__in'] = array( (int) $term );
    } else {
        // Search by customer name, email or billing fields
        // We will use standard WP_Query on shop_order post type for simplicity and speed
        global $wpdb;
        $search = '%' . $wpdb->esc_like( $term ) . '%';
        
        $order_ids = $wpdb->get_col( $wpdb->prepare( "
            SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order' 
            AND p.post_status != 'trash'
            AND (
                p.post_excerpt LIKE %s OR 
                ( pm.meta_key IN ('_billing_first_name', '_billing_last_name', '_billing_email', '_billing_phone') AND pm.meta_value LIKE %s )
            )
            ORDER BY p.post_date DESC
            LIMIT 15
        ", $search, $search ) );
        
        if ( ! empty( $order_ids ) ) {
            $args['post__in'] = $order_ids;
            $args['orderby'] = 'post__in';
        } else {
            // No results
            wp_send_json_success( array( 'results' => array() ) );
        }
    }

    $orders = wc_get_orders( $args );
    $results = array();

    foreach ( $orders as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) continue;
        
        $results[] = array(
            'id' => $order->get_id(),
            'name' => $order->get_formatted_billing_full_name(),
            'email' => $order->get_billing_email(),
            'status' => wc_get_order_status_name( $order->get_status() ),
            'date' => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd/m/Y' ) : '',
            'total' => $order->get_formatted_order_total()
        );
    }

    wp_send_json_success( array( 'results' => $results ) );
}

/**
 * AJAX: Obtener detalles profundos del pedido (Order Lookup)
 */
function tbp_soporte_ajax_get_order_details() {
    check_ajax_referer( 'tbp_soporte_admin_nonce', 'nonce' );

    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_send_json_error( array( 'message' => __( 'No tienes permisos.', 'tbp-soporte' ) ) );
    }

    $order_id = isset( $_POST['order_id'] ) ? intval( $_POST['order_id'] ) : 0;
    $order = wc_get_order( $order_id );

    if ( ! $order ) {
        wp_send_json_error( array( 'message' => __( 'Pedido no encontrado.', 'tbp-soporte' ) ) );
    }

    $items = array();
    foreach ( $order->get_items() as $item_id => $item ) {
        $items[] = esc_html( $item->get_name() ) . ' x ' . esc_html( $item->get_quantity() );
    }

    // TBP Metrics
    $tbp_metrics = array();
    $tombola = get_post_meta( $order_id, '_tbp_boletos_tombola', true );
    $fisicas = get_post_meta( $order_id, '_tbp_entregas_fisicas', true );
    $paquetes = get_post_meta( $order_id, '_tbp_entrega_paquetes', true );
    
    $fisicas_count = is_array( $fisicas ) ? count( $fisicas ) : (int) $fisicas;
    $paquetes_count = is_array( $paquetes ) ? count( $paquetes ) : (int) $paquetes;
    $tombola_count = (int) $tombola;

    if ( $fisicas_count > 0 ) $tbp_metrics[] = '🎟️ Boletos Entregados: <strong>' . $fisicas_count . '</strong>';
    if ( $tombola_count > 0 ) $tbp_metrics[] = '🗳️ Boletos en Tómbola: <strong>' . $tombola_count . '</strong>';
    if ( $paquetes_count > 0 ) $tbp_metrics[] = '🛍️ Paquetes Entregados: <strong>' . $paquetes_count . '</strong>';

    // Attendees Data
    $attendees_html = '';
    if ( function_exists( 'tbp_actividades_get_order_attendees_meta' ) ) {
        $attendees_data = tbp_actividades_get_order_attendees_meta( $order_id, $order );
        if ( ! empty( $attendees_data ) ) {
            $attendees_html .= '<div style="margin-top:15px; border-top: 1px solid #e2e8f0; padding-top:10px;">';
            $attendees_html .= '<strong style="display:block; margin-bottom:10px; color:#1e293b; font-size: 14px;">👥 Asistentes:</strong>';
            $att_count = 1;
            
            // Flex grid for attendees
            $attendees_html .= '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px;">';
            foreach ( $attendees_data as $source_id => $meta_groups ) {
                if ( ! empty($meta_groups) ) {
                    reset($meta_groups);
                    $first_key = key($meta_groups);
                    if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                        $meta_groups = array( 'Datos' => $meta_groups );
                    }
                }
                
                foreach ( $meta_groups as $guest_index => $guest_data ) {
                    $attendees_html .= '<div style="background:#f8fafc; padding:10px; border:1px solid #cbd5e1; border-radius:6px; font-size:12px;">';
                    $attendees_html .= '<strong style="color:#0f172a; border-bottom:1px solid #e2e8f0; padding-bottom:5px; margin-bottom:5px; display:block;">Asistente ' . $att_count . '</strong>';
                    foreach ( $guest_data as $field_key => $field_value ) {
                        if ( ! is_string($field_key) && ! is_numeric($field_key) ) continue;
                        $display_key = strval($field_key);
                        $actual_value = is_array($field_value) && isset($field_value['value']) ? $field_value['value'] : $field_value;
                        $actual_value = is_array($actual_value) ? implode(', ', $actual_value) : $actual_value;
                        
                        if ( is_array($field_value) && isset($field_value['label']) ) {
                            $display_key = strval($field_value['label']);
                        } else {
                            $display_key = ucwords(str_replace(['-', '_'], ' ', $display_key));
                        }
                        
                        if ( ! empty( $actual_value ) ) {
                            $attendees_html .= '<div style="margin-bottom:3px;"><span style="color:#64748b;">' . esc_html( $display_key ) . ':</span> <strong style="color:#334155;">' . esc_html( $actual_value ) . '</strong></div>';
                        }
                    }
                    $attendees_html .= '</div>';
                    $att_count++;
                }
            }
            $attendees_html .= '</div></div>';
        }
    }
    
    // Order Notes (Public and Private)
    $notes_html = '';
    $order_notes = wc_get_order_notes( array( 'order_id' => $order_id ) );
    if ( ! empty( $order_notes ) ) {
        $notes_html .= '<div style="margin-top:15px; border-top: 1px solid #e2e8f0; padding-top:10px;">';
        $notes_html .= '<strong style="display:block; margin-bottom:10px; color:#1e293b; font-size: 14px;">📝 Notas del Pedido:</strong>';
        $notes_html .= '<div style="max-height: 200px; overflow-y: auto; background:#f8fafc; border:1px solid #e2e8f0; border-radius:4px; padding:10px;">';
        foreach ( $order_notes as $note ) {
            $is_customer_note = $note->customer_note;
            $bg_color = $is_customer_note ? '#e0f2fe' : '#fef3c7';
            $border_color = $is_customer_note ? '#bae6fd' : '#fde68a';
            $notes_html .= '<div style="margin-bottom:8px; background:' . $bg_color . '; border:1px solid ' . $border_color . '; padding:8px; border-radius:4px; font-size:12px;">';
            $notes_html .= '<div style="color:#475569; font-size:10px; margin-bottom:4px;">' . esc_html( $note->date_created->date_i18n( 'd/m/Y H:i' ) ) . '</div>';
            $notes_html .= wp_kses_post( wpautop( $note->content ) );
            $notes_html .= '</div>';
        }
        $notes_html .= '</div></div>';
    }

    $order_details = array(
        'id'            => $order->get_id(),
        'status'        => wc_get_order_status_name( $order->get_status() ),
        'date'          => $order->get_date_created() ? $order->get_date_created()->date_i18n( 'd/F/Y H:i' ) : '',
        'total'         => $order->get_formatted_order_total(),
        'customer'      => array(
            'name'  => $order->get_formatted_billing_full_name(),
            'email' => $order->get_billing_email(),
            'phone' => $order->get_billing_phone(),
            'address' => $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address(),
        ),
        'items'         => $items,
        'tbp_metrics'   => $tbp_metrics,
        'attendees_html'=> $attendees_html,
        'notes_html'    => $notes_html
    );

    wp_send_json_success( array( 'details' => $order_details ) );
}
