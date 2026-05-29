<?php
/**
 * Admin Dashboard and Kanban Board for TBP Soporte
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Register Admin Menus
add_action( 'admin_menu', 'tbp_soporte_register_admin_menus' );
function tbp_soporte_register_admin_menus() {
    // Parent Menu
    add_menu_page(
        __( 'Soporte TBP', 'tbp-soporte' ),
        __( 'Soporte TBP', 'tbp-soporte' ),
        'edit_posts',
        'tbp-soporte-dashboard',
        'tbp_soporte_kanban_page_content',
        'dashicons-tickets-alt',
        27
    );

    // Submenu 1: Kanban Board
    add_submenu_page(
        'tbp-soporte-dashboard',
        __( 'Tablero Kanban', 'tbp-soporte' ),
        __( 'Tablero Kanban', 'tbp-soporte' ),
        'edit_posts',
        'tbp-soporte-dashboard',
        'tbp_soporte_kanban_page_content'
    );

    // Submenu 2: Categories (Taxonomy)
    add_submenu_page(
        'tbp-soporte-dashboard',
        __( 'Categorías', 'tbp-soporte' ),
        __( 'Categorías', 'tbp-soporte' ),
        'edit_posts',
        'edit-tags.php?taxonomy=tbp_ticket_category&post_type=tbp_ticket'
    );

    // Submenu 3: Settings (UltraMsg)
    add_submenu_page(
        'tbp-soporte-dashboard',
        __( 'Ajustes WhatsApp', 'tbp-soporte' ),
        __( 'Ajustes WhatsApp', 'tbp-soporte' ),
        'edit_posts',
        'tbp-soporte-settings',
        'tbp_soporte_settings_page_content'
    );
}

/**
 * Settings Page Content
 */
function tbp_soporte_settings_page_content() {
    // Save settings if submitted
    if ( isset( $_POST['tbp_soporte_save_settings'] ) && check_admin_referer( 'tbp_soporte_settings_nonce' ) ) {
        update_option( 'tbp_soporte_ultramsg_url', esc_url_raw( $_POST['ultramsg_url'] ) );
        update_option( 'tbp_soporte_ultramsg_token', sanitize_text_field( $_POST['ultramsg_token'] ) );
        update_option( 'tbp_soporte_admin_phone', sanitize_text_field( $_POST['admin_phone'] ) );
        echo '<div class="updated"><p>' . esc_html__( 'Ajustes guardados correctamente.', 'tbp-soporte' ) . '</p></div>';
    }

    $ultramsg_url = get_option( 'tbp_soporte_ultramsg_url', '' );
    $ultramsg_token = get_option( 'tbp_soporte_ultramsg_token', '' );
    $admin_phone = get_option( 'tbp_soporte_admin_phone', '' );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Ajustes de Soporte - WhatsApp (UltraMsg)', 'tbp-soporte' ); ?></h1>
        <p><?php esc_html_e( 'Configura los accesos a la pasarela UltraMsg para enviar alertas automatizadas por WhatsApp tanto a clientes como administradores.', 'tbp-soporte' ); ?></p>
        
        <form method="POST" action="">
            <?php wp_nonce_field( 'tbp_soporte_settings_nonce' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'URL de API UltraMsg', 'tbp-soporte' ); ?></th>
                    <td>
                        <input type="text" name="ultramsg_url" value="<?php echo esc_attr( $ultramsg_url ); ?>" class="regular-text" placeholder="https://api.ultramsg.com/instanceXXXXX/" required />
                        <p class="description"><?php esc_html_e( 'URL de la instancia provista por UltraMsg (debe terminar en diagonal /)', 'tbp-soporte' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Token de Instancia UltraMsg', 'tbp-soporte' ); ?></th>
                    <td>
                        <input type="password" name="ultramsg_token" value="<?php echo esc_attr( $ultramsg_token ); ?>" class="regular-text" required />
                        <p class="description"><?php esc_html_e( 'Token único de autenticación de tu instancia.', 'tbp-soporte' ); ?></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php esc_html_e( 'Teléfono de Administrador (Alertas)', 'tbp-soporte' ); ?></th>
                    <td>
                        <input type="text" name="admin_phone" value="<?php echo esc_attr( $admin_phone ); ?>" class="regular-text" placeholder="521XXXXXXXXXX" />
                        <p class="description"><?php esc_html_e( 'Ingresa el teléfono celular al cual se enviarán alertas de nuevos tickets creados por clientes. Incluir código de país sin espacios ni símbolos (ej: 521 para México, luego los 10 dígitos).', 'tbp-soporte' ); ?></p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <input type="submit" name="tbp_soporte_save_settings" class="button button-primary" value="<?php esc_attr_e( 'Guardar Ajustes', 'tbp-soporte' ); ?>" />
            </p>
        </form>
    </div>
    <?php
}

/**
 * Kanban Board Page Content (Admin Wrapper)
 */
function tbp_soporte_kanban_page_content() {
    tbp_soporte_render_kanban_board( 'admin' );
}

/**
 * Render the full Kanban Board UI
 * @param string $context Can be 'admin' or 'frontend'
 */
function tbp_soporte_render_kanban_board( $context = 'admin' ) {
    // Query all tickets
    $tickets_query = new WP_Query( array(
        'post_type'      => 'tbp_ticket',
        'post_status'    => 'private',
        'posts_per_page' => -1,
        'orderby'        => 'modified',
        'order'          => 'DESC'
    ) );

    $tickets_by_status = array(
        'enviado'           => array(),
        'en_revision'       => array(),
        'esperando_cliente' => array(),
        'solucionado'       => array()
    );

    if ( $tickets_query->have_posts() ) {
        while ( $tickets_query->have_posts() ) {
            $tickets_query->the_post();
            $tid = get_the_ID();
            $status = get_post_meta( $tid, '_ticket_status', true );
            if ( ! isset( $tickets_by_status[$status] ) ) {
                $status = 'enviado';
            }
            
            $order_id = get_post_meta( $tid, '_associated_order_id', true );
            $author_id = get_the_author_meta( 'ID' );
            $author_name = get_the_author_meta( 'display_name' );
            
            $cats = wp_get_post_terms( $tid, 'tbp_ticket_category' );
            $cat_name = ! empty( $cats ) ? $cats[0]->name : __( 'Sin categoría', 'tbp-soporte' );
            
            $tickets_by_status[$status][] = array(
                'id'          => $tid,
                'title'       => get_the_title(),
                'order_id'    => $order_id,
                'author_id'   => $author_id,
                'author_name' => $author_name,
                'category'    => $cat_name,
                'modified'    => get_the_modified_date( 'd M, H:i' ),
            );
        }
        wp_reset_postdata();
    }

    ?>
    <div class="wrap tbp-soporte-admin-wrap">
        <h1 class="wp-heading-inline"><?php esc_html_e( 'Tablero Kanban de Soporte', 'tbp-soporte' ); ?></h1>
        <hr class="wp-header-end">

        <!-- Kanban Filters -->
        <div class="tbp-soporte-kanban-filters" style="background: #fff; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); margin-bottom: 20px; display: flex; gap: 15px; align-items: center; border: 1px solid #ccd0d4;">
            <div class="tbp-filter-group" style="flex: 1;">
                <label for="tbp-kanban-search" style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Buscar Ticket', 'tbp-soporte' ); ?></label>
                <input type="text" id="tbp-kanban-search" placeholder="<?php esc_attr_e( 'Buscar por ID, Cliente, Pedido o Asunto...', 'tbp-soporte' ); ?>" style="width: 100%; max-width: 400px; padding: 6px 10px;" />
            </div>
            
            <div class="tbp-filter-group">
                <label for="tbp-kanban-category" style="font-weight: 600; display: block; margin-bottom: 5px;"><?php esc_html_e( 'Categoría', 'tbp-soporte' ); ?></label>
                <select id="tbp-kanban-category" style="min-width: 200px;">
                    <option value=""><?php esc_html_e( 'Todas las categorías', 'tbp-soporte' ); ?></option>
                    <?php
                    $categories = get_terms( array( 'taxonomy' => 'tbp_ticket_category', 'hide_empty' => false ) );
                    if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                        foreach ( $categories as $cat ) {
                            echo '<option value="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>

            <div class="tbp-filter-group" style="margin-top: 22px;">
                <button type="button" id="tbp-kanban-clear-filters" class="button" style="display: none;"><?php esc_html_e( 'Limpiar Filtros', 'tbp-soporte' ); ?></button>
            </div>
        </div>

        <!-- Kanban Board Grid -->
        <div class="tbp-soporte-kanban-board">
            
            <!-- Column 1: Enviado -->
            <div class="tbp-soporte-kanban-column" data-status="enviado">
                <div class="tbp-soporte-column-header col-enviado">
                    <h3><?php esc_html_e( 'Enviado (Nuevos)', 'tbp-soporte' ); ?> <span class="count"><?php echo count( $tickets_by_status['enviado'] ); ?></span></h3>
                </div>
                <div class="tbp-soporte-column-cards" id="cards-enviado">
                    <?php tbp_soporte_render_kanban_cards( $tickets_by_status['enviado'], $context ); ?>
                </div>
            </div>

            <!-- Column 2: En Revisión -->
            <div class="tbp-soporte-kanban-column" data-status="en_revision">
                <div class="tbp-soporte-column-header col-revision">
                    <h3><?php esc_html_e( 'En Revisión', 'tbp-soporte' ); ?> <span class="count"><?php echo count( $tickets_by_status['en_revision'] ); ?></span></h3>
                </div>
                <div class="tbp-soporte-column-cards" id="cards-en_revision">
                    <?php tbp_soporte_render_kanban_cards( $tickets_by_status['en_revision'], $context ); ?>
                </div>
            </div>

            <!-- Column 3: Esperando Respuesta -->
            <div class="tbp-soporte-kanban-column" data-status="esperando_cliente">
                <div class="tbp-soporte-column-header col-esperando">
                    <h3><?php esc_html_e( 'Esperando Respuesta', 'tbp-soporte' ); ?> <span class="count"><?php echo count( $tickets_by_status['esperando_cliente'] ); ?></span></h3>
                </div>
                <div class="tbp-soporte-column-cards" id="cards-esperando_cliente">
                    <?php tbp_soporte_render_kanban_cards( $tickets_by_status['esperando_cliente'], $context ); ?>
                </div>
            </div>

            <!-- Column 4: Solucionado -->
            <div class="tbp-soporte-kanban-column" data-status="solucionado">
                <div class="tbp-soporte-column-header col-solucionado">
                    <h3><?php esc_html_e( 'Solucionado', 'tbp-soporte' ); ?> <span class="count"><?php echo count( $tickets_by_status['solucionado'] ); ?></span></h3>
                </div>
                <div class="tbp-soporte-column-cards" id="cards-solucionado">
                    <?php tbp_soporte_render_kanban_cards( $tickets_by_status['solucionado'], $context ); ?>
                </div>
            </div>

        </div>

        <!-- Detail Slide-out Chat Panel -->
        <div id="tbp-soporte-slide-panel" class="tbp-soporte-slide-panel">
            <div class="tbp-soporte-panel-header">
                <span class="tbp-soporte-panel-close-btn">&times;</span>
                <h3 id="panel-ticket-title"><?php esc_html_e( 'Detalles del Ticket', 'tbp-soporte' ); ?></h3>
                <div class="panel-meta-bar">
                    <span id="panel-ticket-id"></span> | 
                    <select id="panel-ticket-category-select" style="-webkit-appearance: menulist; appearance: menulist; font-size:12px; padding:0 10px 0 5px; margin: 0 5px; height: 24px; vertical-align: middle; background-color: #fff; color: #333; border: 1px solid #ccc; border-radius: 3px;">
                        <?php
                        $categories = get_terms( array( 'taxonomy' => 'tbp_ticket_category', 'hide_empty' => false ) );
                        if ( ! is_wp_error( $categories ) && ! empty( $categories ) ) {
                            foreach ( $categories as $cat ) {
                                echo '<option value="' . esc_attr( $cat->term_id ) . '" data-name="' . esc_attr( $cat->name ) . '">' . esc_html( $cat->name ) . '</option>';
                            }
                        }
                        ?>
                    </select> | 
                    <span id="panel-ticket-author"></span>
                </div>
            </div>
            
            <div class="tbp-soporte-panel-content">
                <!-- Original Description -->
                <div class="tbp-soporte-original-content-box">
                    <strong><?php esc_html_e( 'Descripción inicial del cliente:', 'tbp-soporte' ); ?></strong>
                    <div id="panel-ticket-description"></div>
                    <div id="panel-ticket-attachments"></div>
                    
                    <!-- Order Summary Box -->
                    <div id="panel-ticket-order-summary" style="display:none; margin-top: 15px; padding: 10px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 13px;"></div>
                </div>

                <!-- Chat Messages and Notes Thread -->
                <div class="tbp-soporte-admin-chat-thread" id="panel-chat-thread">
                    <!-- Loaded dynamically via AJAX -->
                </div>
            </div>

            <div class="tbp-soporte-panel-footer">
                <!-- Reply Form inside admin panel -->
                <form id="tbp-soporte-admin-reply-form">
                    <textarea name="reply_content" id="panel_reply_content" placeholder="<?php esc_attr_e( 'Escribe una respuesta o nota interna...', 'tbp-soporte' ); ?>" required></textarea>
                    
                    <div class="tbp-soporte-admin-form-actions">
                        <label class="tbp-soporte-note-toggle">
                            <input type="checkbox" name="is_internal" id="panel_is_internal" value="1" />
                            <span>🔒 <?php esc_html_e( 'Nota Interna (Oculta al cliente)', 'tbp-soporte' ); ?></span>
                        </label>
                        
                        <div class="tbp-soporte-action-buttons">
                            <button type="button" class="button" id="panel-solved-btn"><?php esc_html_e( 'Marcar Solucionado', 'tbp-soporte' ); ?></button>
                            <button type="submit" class="button button-primary" id="panel-send-btn"><?php esc_html_e( 'Enviar', 'tbp-soporte' ); ?></button>
                        </div>
                    </div>
                    <input type="hidden" name="ticket_id" id="panel_ticket_id_input" />
                </form>
            </div>
        </div>
        <div id="tbp-soporte-panel-overlay" class="tbp-soporte-panel-overlay"></div>
    </div>

    <!-- Hidden template fields for dynamic data fetching on card click -->
    <script type="text/javascript">
        var tbp_soporte_tickets_data = <?php 
            // Compile all detail data for quick loading on admin side
            $details = array();
            if ( $tickets_query->have_posts() ) {
                while ( $tickets_query->have_posts() ) {
                    $tickets_query->the_post();
                    $tid = get_the_ID();
                    
                    $comments = get_comments( array(
                        'post_id' => $tid,
                        'order'   => 'ASC'
                    ) );
                    
                    $comments_data = array();
                    foreach ( $comments as $comment ) {
                        $is_internal = get_comment_meta( $comment->comment_ID, '_is_internal_note', true );
                        $att_url = get_comment_meta( $comment->comment_ID, '_comment_attachment_url', true );
                        $att_name = get_comment_meta( $comment->comment_ID, '_comment_attachment_name', true );
                        
                        $comments_data[] = array(
                            'author'       => $comment->comment_author,
                            'content'      => wpautop( esc_html( $comment->comment_content ) ),
                            'time'         => get_comment_date( 'd M, H:i', $comment ),
                            'is_internal'  => ! empty( $is_internal ) ? true : false,
                            'attachment_url'  => $att_url,
                            'attachment_name' => $att_name
                        );
                    }

                    $attachments = get_posts( array(
                        'post_type'   => 'attachment',
                        'post_parent' => $tid,
                        'numberposts' => -1
                    ) );
                    
                    $atts_data = array();
                    foreach ( $attachments as $attachment ) {
                        $atts_data[] = array(
                            'name' => get_the_title( $attachment->ID ),
                            'url'  => wp_get_attachment_url( $attachment->ID )
                        );
                    }

                    $order_id = get_post_meta( $tid, '_associated_order_id', true );
                    $order_url = '';
                    $order_summary = null;
                    if ( $order_id ) {
                        $order_url = get_edit_post_link( $order_id );
                        
                        // Opcion B: Obtener detalles del pedido para lectura rápida en frontend
                        $order = wc_get_order( $order_id );
                        if ( $order ) {
                            $items = array();
                            foreach ( $order->get_items() as $item_id => $item ) {
                                $item_name = $item->get_name() . ' (x' . $item->get_quantity() . ')';
                                
                                // Fetch meta data
                                $meta_html = '';
                                $formatted_meta = $item->get_formatted_meta_data();
                                foreach ( $formatted_meta as $meta_id => $meta ) {
                                    $meta_html .= '<br><small style="color:#64748b;">- <em>' . wp_kses_post( $meta->display_key ) . ':</em> ' . wp_kses_post( $meta->display_value ) . '</small>';
                                }
                                $items[] = '<div style="margin-bottom:8px;"><strong>' . $item_name . '</strong>' . $meta_html . '</div>';
                            }

                            // Fetch TBP Metrics (Boletos, Tómbola, Paquetes)
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
                            
                            // 🚀 Novedad: Extraer datos de los asistentes usando el core de TBP Actividades
                            $attendees_html = '';
                            if ( function_exists( 'tbp_actividades_get_order_attendees_meta' ) ) {
                                $attendees_data = tbp_actividades_get_order_attendees_meta( $order_id, $order );
                                if ( ! empty( $attendees_data ) ) {
                                    $attendees_html .= '<div style="margin-top:10px; border-top: 1px solid #e2e8f0; padding-top:10px;">';
                                    $attendees_html .= '<strong style="display:block; margin-bottom:5px; color:#475569;">👥 Datos de Asistentes:</strong>';
                                    $att_count = 1;
                                    foreach ( $attendees_data as $source_id => $meta_groups ) {
                                        if ( ! empty($meta_groups) ) {
                                            reset($meta_groups);
                                            $first_key = key($meta_groups);
                                            if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                                                $meta_groups = array( 'Datos' => $meta_groups );
                                            }
                                        }
                                        
                                        foreach ( $meta_groups as $guest_index => $guest_data ) {
                                            $attendees_html .= '<div style="margin-bottom:8px; background:#f1f5f9; padding:6px; border-radius:4px; font-size:11px;">';
                                            $attendees_html .= '<strong>Asistente ' . $att_count . '</strong><br>';
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
                                                    $attendees_html .= '- <em>' . esc_html( $display_key ) . ':</em> <strong>' . esc_html( $actual_value ) . '</strong><br>';
                                                }
                                            }
                                            $attendees_html .= '</div>';
                                            $att_count++;
                                        }
                                    }
                                    $attendees_html .= '</div>';
                                }
                            }

                            $order_summary = array(
                                'status'        => wc_get_order_status_name( $order->get_status() ),
                                'total'         => $order->get_formatted_order_total(),
                                'items'         => $items,
                                'billing_email' => $order->get_billing_email(),
                                'billing_phone' => $order->get_billing_phone(),
                                'shipping_addr' => $order->get_formatted_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address(),
                                'tbp_metrics'   => $tbp_metrics,
                                'attendees_html'=> $attendees_html // Passed separate to inject
                            );
                        }
                    }

                    $details[$tid] = array(
                        'description'   => wpautop( esc_html( get_the_content() ) ),
                        'order_id'      => $order_id,
                        'order_url'     => $order_url,
                        'order_summary' => $order_summary,
                        'attachments'   => $atts_data,
                        'comments'      => $comments_data
                    );
                }
                wp_reset_postdata();
            }
            echo json_encode( $details );
        ?>;
    </script>
    <?php
}

/**
 * Render Kanban Cards for specific column
 */
function tbp_soporte_render_kanban_cards( $cards, $context = 'admin' ) {
    if ( empty( $cards ) ) {
        echo '<div class="tbp-soporte-empty-col-message">' . esc_html__( 'No hay tickets', 'tbp-soporte' ) . '</div>';
        return;
    }

    foreach ( $cards as $card ) {
        ?>
        <div class="tbp-soporte-kanban-card" draggable="true" data-id="<?php echo esc_attr( $card['id'] ); ?>" id="card-<?php echo esc_attr( $card['id'] ); ?>">
            <div class="card-meta">
                <span class="card-id">#<?php echo esc_html( $card['id'] ); ?></span>
                <span class="card-category"><?php echo esc_html( $card['category'] ); ?></span>
            </div>
            
            <h4 class="card-title"><?php echo esc_html( $card['title'] ); ?></h4>
            
            <div class="card-details">
                <span class="card-author">👤 <?php echo esc_html( $card['author_name'] ); ?></span>
                <?php if ( $card['order_id'] ) : ?>
                    <span class="card-order">📦 <?php esc_html_e( 'Pedido:', 'tbp-soporte' ); ?> <strong>#<?php echo esc_html( $card['order_id'] ); ?></strong></span>
                <?php endif; ?>
            </div>

            <div class="card-footer">
                <span class="card-time">🕒 <?php echo esc_html( $card['modified'] ); ?></span>
                <div class="card-actions">
                    <?php if ( $card['order_id'] && $card['order_url'] && $context === 'admin' ) : ?>
                        <a href="<?php echo esc_url( $card['order_url'] ); ?>" class="button button-small card-btn-view-order" title="<?php esc_attr_e( 'Ver Pedido WooCommerce en Backend', 'tbp-soporte' ); ?>">📦</a>
                    <?php endif; ?>
                    <button type="button" class="button button-small button-primary card-btn-view-ticket" data-id="<?php echo esc_attr( $card['id'] ); ?>">
                        <?php esc_html_e( 'Ver y Chatear', 'tbp-soporte' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
}
