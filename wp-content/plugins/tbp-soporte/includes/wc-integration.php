<?php
/**
 * WooCommerce My Account Integration for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Register My Account Endpoint
add_action( 'init', 'tbp_soporte_add_endpoint' );
function tbp_soporte_add_endpoint() {
    add_rewrite_endpoint( 'soporte', EP_ROOT | EP_PAGES );
}

// 2. Add Query Var
add_filter( 'query_vars', 'tbp_soporte_query_vars', 0 );
function tbp_soporte_query_vars( $vars ) {
    $vars[] = 'soporte';
    return $vars;
}

// 3. Add to WooCommerce Account Menu Links
add_filter( 'woocommerce_account_menu_items', 'tbp_soporte_add_menu_link' );
function tbp_soporte_add_menu_link( $items ) {
    // Insert before Logout
    $logout = isset( $items['customer-logout'] ) ? $items['customer-logout'] : '';
    unset( $items['customer-logout'] );
    
    $items['soporte'] = __( 'Soporte y Ayuda', 'tbp-soporte' );
    
    if ( $logout ) {
        $items['customer-logout'] = $logout;
    }
    
    return $items;
}

// 4. Render Endpoint Content
add_action( 'woocommerce_account_soporte_endpoint', 'tbp_soporte_endpoint_content' );
function tbp_soporte_endpoint_content() {
    $current_user_id = get_current_user_id();
    if ( ! $current_user_id ) {
        echo '<p>' . esc_html__( 'Debes iniciar sesión para ver tus tickets.', 'tbp-soporte' ) . '</p>';
        return;
    }

    // Check if we are viewing a specific ticket
    if ( isset( $_GET['ver_ticket'] ) ) {
        $ticket_id = intval( $_GET['ver_ticket'] );
        $ticket = get_post( $ticket_id );
        
        // Security check: only author or administrator can view
        if ( ! $ticket || ( (int) $ticket->post_author !== $current_user_id && ! current_user_can( 'edit_posts' ) ) ) {
            echo '<div class="woocommerce-error">' . esc_html__( 'No tienes permisos para ver este ticket.', 'tbp-soporte' ) . '</div>';
            echo '<p><a href="' . esc_url( wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) ) ) . '" class="button">' . esc_html__( 'Volver al listado', 'tbp-soporte' ) . '</a></p>';
            return;
        }
        
        tbp_soporte_render_ticket_detail( $ticket );
        return;
    }

    // Otherwise, render list of tickets
    tbp_soporte_render_tickets_list( $current_user_id );
}

/**
 * Render List of User Tickets
 */
function tbp_soporte_render_tickets_list( $user_id ) {
    $args = array(
        'post_type'      => 'tbp_ticket',
        'post_status'    => 'private', // We save tickets as private
        'author'         => $user_id,
        'posts_per_page' => -1,
        'orderby'        => 'date',
        'order'          => 'DESC'
    );
    
    $tickets = get_posts( $args );
    
    echo '<h3>' . esc_html__( 'Mis Tickets de Soporte', 'tbp-soporte' ) . '</h3>';
    echo '<p>' . esc_html__( 'Aquí puedes ver el historial de tus solicitudes de soporte y dar seguimiento a los problemas de tus pedidos.', 'tbp-soporte' ) . '</p>';
    
    if ( empty( $tickets ) ) {
        echo '<div class="woocommerce-info">' . esc_html__( 'No tienes ningún ticket de soporte abierto.', 'tbp-soporte' ) . '</div>';
        return;
    }
    
    echo '<table class="woocommerce-orders-table woocommerce-MyAccount-orders shop_table shop_table_responsive my_account_orders account-orders-table tbp-soporte-table">';
    echo '<thead>';
    echo '<tr>';
    echo '<th class="woocommerce-orders-table__header"><span class="nobr">' . esc_html__( 'Ticket', 'tbp-soporte' ) . '</span></th>';
    echo '<th class="woocommerce-orders-table__header"><span class="nobr">' . esc_html__( 'Pedido', 'tbp-soporte' ) . '</span></th>';
    echo '<th class="woocommerce-orders-table__header"><span class="nobr">' . esc_html__( 'Categoría', 'tbp-soporte' ) . '</span></th>';
    echo '<th class="woocommerce-orders-table__header"><span class="nobr">' . esc_html__( 'Estado', 'tbp-soporte' ) . '</span></th>';
    echo '<th class="woocommerce-orders-table__header"><span class="nobr">' . esc_html__( 'Última actualización', 'tbp-soporte' ) . '</span></th>';
    echo '<th class="woocommerce-orders-table__header"></th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ( $tickets as $ticket ) {
        $order_id = get_post_meta( $ticket->ID, '_associated_order_id', true );
        $status = get_post_meta( $ticket->ID, '_ticket_status', true );
        if ( ! $status ) {
            $status = 'enviado';
        }
        
        $categories = wp_get_post_terms( $ticket->ID, 'tbp_ticket_category' );
        $category_name = ! empty( $categories ) ? $categories[0]->name : esc_html__( 'Sin categoría', 'tbp-soporte' );
        
        $status_labels = array(
            'enviado'           => __( 'Enviado', 'tbp-soporte' ),
            'en_revision'       => __( 'En Revisión', 'tbp-soporte' ),
            'esperando_cliente' => __( 'Esperando Respuesta', 'tbp-soporte' ),
            'solucionado'       => __( 'Solucionado', 'tbp-soporte' )
        );
        $status_label = isset( $status_labels[$status] ) ? $status_labels[$status] : $status;
        
        $view_url = add_query_arg( 'ver_ticket', $ticket->ID, wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) ) );
        
        echo '<tr class="woocommerce-orders-table__row">';
        echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Ticket', 'tbp-soporte' ) . '"><strong>#' . esc_html( $ticket->ID ) . ' - ' . esc_html( $ticket->post_title ) . '</strong></td>';
        echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Pedido', 'tbp-soporte' ) . '">';
        if ( $order_id ) {
            $order = wc_get_order( $order_id );
            if ( $order ) {
                echo '<a href="' . esc_url( $order->get_view_order_url() ) . '">#' . esc_html( $order_id ) . '</a>';
            } else {
                echo esc_html( $order_id );
            }
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Categoría', 'tbp-soporte' ) . '">' . esc_html( $category_name ) . '</td>';
        echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Estado', 'tbp-soporte' ) . '"><span class="tbp-soporte-status tbp-soporte-status-' . esc_attr( $status ) . '">' . esc_html( $status_label ) . '</span></td>';
        echo '<td class="woocommerce-orders-table__cell" data-title="' . esc_attr__( 'Última actualización', 'tbp-soporte' ) . '">' . esc_html( get_the_modified_date( 'd/m/Y H:i', $ticket ) ) . '</td>';
        echo '<td class="woocommerce-orders-table__cell" style="text-align: right;"><a href="' . esc_url( $view_url ) . '" class="woocommerce-button button view">' . esc_html__( 'Ver y Chatear', 'tbp-soporte' ) . '</a></td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
}

/**
 * Render Ticket Details and Conversation Chat
 */
function tbp_soporte_render_ticket_detail( $ticket ) {
    $order_id = get_post_meta( $ticket->ID, '_associated_order_id', true );
    $status = get_post_meta( $ticket->ID, '_ticket_status', true );
    if ( ! $status ) {
        $status = 'enviado';
    }
    
    $status_labels = array(
        'enviado'           => __( 'Enviado', 'tbp-soporte' ),
        'en_revision'       => __( 'En Revisión', 'tbp-soporte' ),
        'esperando_cliente' => __( 'Esperando tu Respuesta', 'tbp-soporte' ),
        'solucionado'       => __( 'Solucionado', 'tbp-soporte' )
    );
    $status_label = isset( $status_labels[$status] ) ? $status_labels[$status] : $status;
    
    // Fetch comments (messages) of the ticket (exclude internal notes for customers)
    $comments = get_comments( array(
        'post_id' => $ticket->ID,
        'order'   => 'ASC',
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key'     => '_is_internal_note',
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key'     => '_is_internal_note',
                'value'   => '0'
            )
        )
    ) );
    
    ?>
    <div class="tbp-soporte-ticket-detail-container">
        <div class="tbp-soporte-ticket-header">
            <div class="tbp-soporte-ticket-title-row">
                <h3>Ticket #<?php echo esc_html( $ticket->ID ); ?>: <?php echo esc_html( $ticket->post_title ); ?></h3>
                <span class="tbp-soporte-status-badge tbp-soporte-status-<?php echo esc_attr( $status ); ?>">
                    <?php echo esc_html( $status_label ); ?>
                </span>
            </div>
            
            <div class="tbp-soporte-ticket-meta-info">
                <?php if ( $order_id ) : ?>
                    <span><strong><?php esc_html_e( 'Pedido relacionado:', 'tbp-soporte' ); ?></strong> 
                        <a href="<?php echo esc_url( wc_get_endpoint_url( 'view-order', $order_id ) ); ?>">#<?php echo esc_html( $order_id ); ?></a>
                    </span>
                <?php endif; ?>
                <span><strong><?php esc_html_e( 'Creado:', 'tbp-soporte' ); ?></strong> <?php echo esc_html( get_the_date( 'd/m/Y H:i', $ticket ) ); ?></span>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="tbp-soporte-chat-box">
            <!-- First Message: Original Description -->
            <div class="tbp-soporte-message tbp-soporte-message-client">
                <div class="tbp-soporte-message-author">
                    <?php echo esc_html( get_the_author_meta( 'display_name', $ticket->post_author ) ); ?> (Cliente)
                </div>
                <div class="tbp-soporte-message-content">
                    <?php echo wpautop( esc_html( $ticket->post_content ) ); ?>
                    
                    <!-- Original attachments if any -->
                    <?php 
                    $attachments = get_posts( array(
                        'post_type'   => 'attachment',
                        'post_parent' => $ticket->ID,
                        'numberposts' => -1
                    ) );
                    if ( ! empty( $attachments ) ) : 
                        echo '<div class="tbp-soporte-message-attachments"><strong>' . esc_html__( 'Adjuntos:', 'tbp-soporte' ) . '</strong><ul>';
                        foreach ( $attachments as $attachment ) {
                            $url = wp_get_attachment_url( $attachment->ID );
                            $name = get_the_title( $attachment->ID );
                            echo '<li><a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $name ) . '</a></li>';
                        }
                        echo '</ul></div>';
                    endif;
                    ?>
                </div>
                <div class="tbp-soporte-message-time"><?php echo esc_html( get_the_date( 'H:i', $ticket ) ); ?></div>
            </div>

            <!-- Thread of replies -->
            <?php if ( ! empty( $comments ) ) : ?>
                <?php foreach ( $comments as $comment ) : 
                    $is_staff = user_can( $comment->user_id, 'edit_posts' );
                    $msg_class = $is_staff ? 'tbp-soporte-message-staff' : 'tbp-soporte-message-client';
                    $author_label = $is_staff ? __( 'Soporte', 'tbp-soporte' ) : __( 'Tú', 'tbp-soporte' );
                    ?>
                    <div class="tbp-soporte-message <?php echo esc_attr( $msg_class ); ?>">
                        <div class="tbp-soporte-message-author">
                            <?php echo esc_html( $comment->comment_author ); ?> (<?php echo esc_html( $author_label ); ?>)
                        </div>
                        <div class="tbp-soporte-message-content">
                            <?php echo wpautop( esc_html( $comment->comment_content ) ); ?>
                            
                            <!-- Check for attachment for comments if any -->
                            <?php
                            $comment_attachment_url = get_comment_meta( $comment->comment_ID, '_comment_attachment_url', true );
                            $comment_attachment_name = get_comment_meta( $comment->comment_ID, '_comment_attachment_name', true );
                            if ( $comment_attachment_url ) :
                                echo '<div class="tbp-soporte-message-attachments">';
                                echo '<strong>' . esc_html__( 'Adjunto:', 'tbp-soporte' ) . '</strong> ';
                                echo '<a href="' . esc_url( $comment_attachment_url ) . '" target="_blank">' . esc_html( $comment_attachment_name ) . '</a>';
                                echo '</div>';
                            endif;
                            ?>
                        </div>
                        <div class="tbp-soporte-message-time"><?php echo esc_html( get_comment_date( 'H:i', $comment ) ); ?></div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Reply Form -->
        <?php if ( $status !== 'solucionado' ) : ?>
            <div class="tbp-soporte-reply-form-container">
                <form id="tbp-soporte-reply-form" method="POST" enctype="multipart/form-data">
                    <textarea name="comment_content" id="comment_content" rows="4" placeholder="<?php esc_attr_e( 'Escribe tu respuesta aquí...', 'tbp-soporte' ); ?>" required></textarea>
                    
                    <div class="tbp-soporte-reply-footer">
                        <div class="tbp-soporte-file-upload">
                            <label for="reply_file" class="button secondary">📎 <?php esc_html_e( 'Adjuntar imagen/PDF', 'tbp-soporte' ); ?></label>
                            <input type="file" name="reply_file" id="reply_file" accept="image/*,application/pdf" style="display:none;" />
                            <span id="reply_file_name" style="margin-left: 10px; font-size: 0.9em; color: #666;"></span>
                        </div>
                        
                        <input type="hidden" name="ticket_id" value="<?php echo esc_attr( $ticket->ID ); ?>" />
                        <button type="submit" class="button alt" id="submit-reply-btn"><?php esc_html_e( 'Enviar Mensaje', 'tbp-soporte' ); ?></button>
                    </div>
                </form>
            </div>
        <?php else : ?>
            <div class="woocommerce-info" style="margin-top: 20px;">
                <?php esc_html_e( 'Este ticket está Solucionado. Si tienes más dudas sobre este pedido, puedes abrir un nuevo ticket.', 'tbp-soporte' ); ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 20px;">
            <a href="<?php echo esc_url( wc_get_endpoint_url( 'soporte', '', wc_get_page_permalink( 'myaccount' ) ) ); ?>" class="button secondary"><?php esc_html_e( '← Volver a Mis Tickets', 'tbp-soporte' ); ?></a>
        </p>
    </div>
    <?php
}

// 5. Add "Solicitar Ayuda" Button on My Orders list page
add_filter( 'woocommerce_my_account_my_orders_actions', 'tbp_soporte_add_order_action_button', 10, 2 );
function tbp_soporte_add_order_action_button( $actions, $order = null ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return $actions;
    }
    
    $actions['solicitar_soporte'] = array(
        'url'  => '#solicitar-soporte-' . $order->get_id(),
        'name' => __( 'Solicitar Ayuda', 'tbp-soporte' )
    );
    
    return $actions;
}

// 6. Add "Solicitar Ayuda" Button inside Order Details Page
add_action( 'woocommerce_order_details_after_order_table', 'tbp_soporte_add_order_details_button' );
function tbp_soporte_add_order_details_button( $order = null ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    
    echo '<div style="margin-top: 15px; margin-bottom: 25px;">';
    echo '<a href="#" class="button tbp-soporte-open-modal-btn alt" data-order-id="' . esc_attr( $order->get_id() ) . '" style="background-color: #0073aa; color: white;">';
    echo esc_html__( 'Solicitar Ayuda (Generar Ticket)', 'tbp-soporte' );
    echo '</a>';
    echo '</div>';
}

// 7. Inject Modal Markup in footer for Account Pages
add_action( 'wp_footer', 'tbp_soporte_inject_modal' );
function tbp_soporte_inject_modal() {
    if ( ! is_account_page() ) {
        return;
    }
    
    // Get all available ticket categories
    $categories = get_terms( array(
        'taxonomy'   => 'tbp_ticket_category',
        'hide_empty' => false,
    ) );
    
    ?>
    <!-- Support Ticket Modal -->
    <div id="tbp-soporte-modal" class="tbp-soporte-modal-overlay" style="display:none;">
        <div class="tbp-soporte-modal-content">
            <div class="tbp-soporte-modal-header">
                <h3><?php esc_html_e( 'Crear Ticket de Soporte', 'tbp-soporte' ); ?></h3>
                <span class="tbp-soporte-modal-close">&times;</span>
            </div>
            <form id="tbp-soporte-create-form" enctype="multipart/form-data">
                <div class="tbp-soporte-field">
                    <label for="tbp_order_id"><?php esc_html_e( 'ID del Pedido', 'tbp-soporte' ); ?></label>
                    <input type="text" id="tbp_order_id" name="order_id" readonly />
                </div>
                
                <div class="tbp-soporte-field">
                    <label for="tbp_category"><?php esc_html_e( 'Categoría de Soporte', 'tbp-soporte' ); ?></label>
                    <select id="tbp_category" name="category" required>
                        <option value=""><?php esc_html_e( 'Selecciona una categoría...', 'tbp-soporte' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat->slug ); ?>"><?php echo esc_html( $cat->name ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="tbp-soporte-field">
                    <label for="tbp_title"><?php esc_html_e( 'Título / Asunto', 'tbp-soporte' ); ?></label>
                    <input type="text" id="tbp_title" name="title" placeholder="<?php esc_attr_e( 'Ej: Retraso en entrega de mi pedido', 'tbp-soporte' ); ?>" required />
                </div>
                
                <div class="tbp-soporte-field">
                    <label for="tbp_description"><?php esc_html_e( 'Comentarios / Descripción del problema', 'tbp-soporte' ); ?></label>
                    <textarea id="tbp_description" name="description" rows="5" placeholder="<?php esc_attr_e( 'Ingresa tus comentarios detallados aquí...', 'tbp-soporte' ); ?>" required></textarea>
                </div>
                
                <div class="tbp-soporte-field">
                    <label><?php esc_html_e( 'Adjuntar imagen o PDF (Opcional)', 'tbp-soporte' ); ?></label>
                    <label for="tbp_file" class="tbp-file-label">📁 <?php esc_html_e( 'Seleccionar archivo...', 'tbp-soporte' ); ?></label>
                    <input type="file" id="tbp_file" name="ticket_file" accept="image/*,application/pdf" style="display:none;" />
                    <span id="tbp-file-selected-name" style="margin-left: 10px; font-size: 0.9em; color: #555;"></span>
                </div>
                
                <div class="tbp-soporte-modal-footer">
                    <button type="button" class="button secondary tbp-soporte-modal-cancel"><?php esc_html_e( 'Cancelar', 'tbp-soporte' ); ?></button>
                    <button type="submit" class="button alt" id="tbp-soporte-submit-btn"><?php esc_html_e( 'Enviar Ticket', 'tbp-soporte' ); ?></button>
                </div>
            </form>
        </div>
    </div>
    <?php
}
