<?php
/**
 * Order Integration for TBP Actividades
 * Adds a meta box to the WooCommerce order edit screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register Meta Box for Orders
 */
add_action( 'add_meta_boxes', 'tbp_actividades_add_order_meta_box' );
function tbp_actividades_add_order_meta_box() {
    // Normal Shop Order (Legacy)
    $screens = array( 'shop_order' );
    
    // HPOS and newer compatibility
    if ( function_exists( 'wc_get_page_screen_id' ) ) {
        $hpos_screen = wc_get_page_screen_id( 'shop-order' );
        if ( $hpos_screen && ! in_array( $hpos_screen, $screens ) ) {
            $screens[] = $hpos_screen;
        }
    }

    foreach ( $screens as $screen ) {
        add_meta_box(
            'tbp_actividades_order_box',
            __( 'ACTIVIDADES DEL EVENTO', 'tbp-actividades' ),
            'tbp_actividades_order_meta_box_html',
            $screen,
            'normal',
            'high'
        );
    }
}

/**
 * Meta Box HTML
 */
function tbp_actividades_order_meta_box_html( $post_or_order ) {
    // Handle both WP_Post (legacy) and WC_Order (HPOS)
    $order = false;
    if ( $post_or_order instanceof WC_Order ) {
        $order = $post_or_order;
    } elseif ( $post_or_order instanceof WP_Post ) {
        $order = wc_get_order( $post_or_order->ID );
    } elseif ( is_numeric( $post_or_order ) ) {
        $order = wc_get_order( $post_or_order );
    }

    if ( ! $order ) return;

    $order_id = $order->get_id();

    ?>
    <div class="tbp-order-actividades-wrapper">
        <style>
            .tbp-order-section { margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
            .tbp-order-section:last-child { border-bottom: none; }
            .tbp-order-section h3 { color: #f39c12; text-transform: uppercase; font-size: 14px; margin-bottom: 10px; display: flex; align-items: center; font-family: sans-serif; }
            .tbp-order-section h3 span { margin-right: 8px; }
            .tbp-history-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-family: sans-serif; }
            .tbp-history-table th { text-align: left; background: #f8f8f8; padding: 8px; border: 1px solid #eee; font-size: 11px; color: #555; }
            .tbp-history-table td { padding: 8px; border: 1px solid #eee; font-size: 12px; }
            .tbp-badge-placeholder { background: #eee; color: #777; padding: 2px 6px; border-radius: 4px; font-size: 10px; font-weight: normal; margin-left: 10px; }
        </style>

        <!-- 1. RIFA -->
        <div class="tbp-order-section">
            <h3><span>🎫</span> <?php _e( 'Rifa', 'tbp-actividades' ); ?></h3>
            
            <?php
            global $wpdb;
            $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
            $logs_raffle = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_logs WHERE order_id = %d AND (type IN ('physical', 'tombola') OR type IS NULL OR type = '') ORDER BY created_at DESC", $order_id ) );

            if ( $logs_raffle ) : ?>
                <table class="tbp-history-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'FECHA DE ENTREGA', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'CANTIDAD DE BOLETOS', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'COSTO DEL BOLETO', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'CANTIDAD EQUIVALENTE', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'ACCIONES', 'tbp-actividades' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs_raffle as $log ) : 
                            $costo = floatval( get_post_meta( $log->rifa_id, '_tbp_cost_physical', true ) );
                            $total = $costo * $log->amount;
                            $fecha = date_i18n( 'd/F/Y', strtotime( $log->created_at ) );
                            $log_id = intval($log->id); // Primary Key of tbp_actividades_logs
                        ?>
                        <tr id="tbp-log-row-<?php echo $log_id; ?>">
                            <td><?php echo strtoupper($fecha); ?></td>
                            <td><?php echo $log->amount; ?></td>
                            <td><?php echo wc_price( $costo ); ?></td>
                            <td><strong><?php echo wc_price( $total ); ?></strong></td>
                            <td style="text-align:center;">
                                <button type="button" class="button tbp-btn-delete-log" data-log-id="<?php echo $log_id; ?>" title="<?php _e( 'Borrar este registro de entrega', 'tbp-actividades' ); ?>" style="color:#dc3232; border-color:#dc3232; background:transparent; padding:2px 6px; font-size:14px;">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><small><?php _e( 'No hay registros de boletos de rifa físicos para este pedido.', 'tbp-actividades' ); ?></small></p>
            <?php endif; ?>
        </div>

        <!-- 2. ENTREGAS FÍSICAS (QR) -->
        <div class="tbp-order-section">
            <h3><span>📦</span> <?php _e( 'Entregas Físicas (QR)', 'tbp-actividades' ); ?></h3>
            
            <?php
            $logs_qr = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_logs WHERE order_id = %d AND type IN ('qr_delivery', 'delivery_items') ORDER BY created_at DESC", $order_id ) );

            if ( $logs_qr ) : ?>
                <table class="tbp-history-table">
                    <thead>
                        <tr>
                            <th><?php _e( 'FECHA DE ENTREGA ACTIVA', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'TIPO DE ENTREGA', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'UNIDADES ENTREGADAS', 'tbp-actividades' ); ?></th>
                            <th><?php _e( 'ACCIONES', 'tbp-actividades' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $logs_qr as $log ) : 
                            $fecha = date_i18n( 'd/F/Y g:i a', strtotime( $log->created_at ) );
                            $log_id = intval($log->id);
                            
                            $rule_name = ( $log->type === 'delivery_items' ) ? __( 'Entrega Física (Manual)', 'tbp-actividades' ) : __( 'Entrega Física (QR)', 'tbp-actividades' );
                            $rule = tbp_actividades_get_rule_by_hash( $order_id, $log->rifa_id );
                            if ( $rule && ! empty( $rule['delivery_type'] ) ) {
                                $rule_name = $rule['delivery_type'];
                            }
                        ?>
                        <tr id="tbp-log-row-<?php echo $log_id; ?>">
                            <td><?php echo strtoupper($fecha); ?></td>
                            <td><strong><?php echo esc_html( $rule_name ); ?></strong></td>
                            <td><?php echo intval( $log->amount ); ?> u</td>
                            <td style="text-align:center;">
                                <button type="button" class="button tbp-btn-delete-log" data-log-id="<?php echo $log_id; ?>" title="<?php _e( 'Borrar este registro de entrega', 'tbp-actividades' ); ?>" style="color:#dc3232; border-color:#dc3232; background:transparent; padding:2px 6px; font-size:14px;">🗑️</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><small><?php _e( 'No hay registros de entrega física para este pedido.', 'tbp-actividades' ); ?></small></p>
            <?php endif; ?>
            
            <script>
            jQuery(document).ready(function($){
                $(document).on('click', '.tbp-btn-delete-log', function(e){
                    e.preventDefault();
                    var log_id = $(this).data('log-id');
                    
                    if(!confirm('¿Estás SEGURO de que deseas borrar este registro de entrega?')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true).text('...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tbp_actividades_delete_single_log',
                            log_id: log_id,
                            _ajax_nonce: '<?php echo wp_create_nonce("tbp_delete_log"); ?>'
                        },
                        success: function(res) {
                            if(res.success) {
                                $('#tbp-log-row-' + log_id).fadeOut(300, function(){ $(this).remove(); });
                                setTimeout(function(){
                                    location.reload();
                                }, 500);
                            } else {
                                alert('Error: ' + (res.data || 'Problema desconocido'));
                                btn.prop('disabled', false).text('🗑️');
                            }
                        },
                        error: function() {
                            alert('Error de conexión.');
                            btn.prop('disabled', false).text('🗑️');
                        }
                    });
                });

                $(document).on('click', '#tbp-btn-clear-debug', function(e){
                    e.preventDefault();
                    if(!confirm('¿Estás seguro de que deseas limpiar todo el historial de diagnósticos?')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true).text('Limpiando...');
                    
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tbp_actividades_clear_debug_logs',
                            _ajax_nonce: '<?php echo wp_create_nonce("tbp_clear_debug"); ?>'
                        },
                        success: function(res) {
                            if(res.success) {
                                location.reload();
                            } else {
                                alert('Error al limpiar logs: ' + (res.data || 'Error desconocido'));
                                btn.prop('disabled', false).text('🧹 Limpiar Historial de Diagnóstico');
                            }
                        },
                        error: function() {
                            alert('Error de conexión.');
                            btn.prop('disabled', false).text('🧹 Limpiar Historial de Diagnóstico');
                        }
                    });
                });
            });
            </script>
        </div>

        <!-- 🔍 DIAGNÓSTICO DE ESCANEOS QR (REAL-TIME DEBUG) -->
        <div class="tbp-order-section" style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 4px; margin-top: 15px;">
            <h3 style="color: #475569; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; cursor: pointer; display: flex; align-items: center; justify-content: space-between;" onclick="jQuery('#tbp-debug-log-box').toggle();">
                <span style="display: flex; align-items: center;"><span>🔍</span> <?php _e( 'Diagnóstico de Escaneos QR (Real-time Debug)', 'tbp-actividades' ); ?></span>
                <span style="font-size: 10px; color: #94a3b8; font-weight: normal; border: 1px solid #cbd5e1; padding: 2px 6px; border-radius: 3px; background: #fff;"><?php _e( 'Ver / Contraer', 'tbp-actividades' ); ?></span>
            </h3>
            
            <div id="tbp-debug-log-box" style="margin-top: 10px;">
                <?php
                $debug_logs = get_option( 'tbp_delivery_debug_logs', [] );
                if ( ! empty( $debug_logs ) && is_array( $debug_logs ) ) : ?>
                    <div style="max-height: 250px; overflow-y: auto; background: #0f172a; color: #f8fafc; font-family: monospace; font-size: 11px; padding: 10px; border-radius: 4px; line-height: 1.5; border: 1px solid #1e293b;">
                        <?php foreach ( $debug_logs as $index => $log_entry ) : 
                            $log_time = esc_html( $log_entry['time'] ?? '' );
                            $log_msg  = esc_html( $log_entry['message'] ?? '' );
                            
                            // Style logs dynamically based on success/error/warning
                            $msg_color = '#38bdf8'; // Blue info/warning
                            if ( strpos( $log_msg, 'ÉXITO' ) !== false ) {
                                $msg_color = '#4ade80'; // Green success
                            } elseif ( strpos( $log_msg, 'Cancelado' ) !== false || strpos( $log_msg, 'Falla' ) !== false ) {
                                $msg_color = '#f87171'; // Red cancel/error
                            } elseif ( strpos( $log_msg, 'Evitado' ) !== false ) {
                                $msg_color = '#facc15'; // Yellow duplicate warning
                            }
                        ?>
                            <div style="border-bottom: 1px solid #1e293b; padding: 6px 0; display: flex; flex-direction: column;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; width: 100%;">
                                    <span style="color: #64748b; min-width: 135px; flex-shrink: 0;">[<?php echo $log_time; ?>]</span>
                                    <span style="color: <?php echo $msg_color; ?>; flex-grow: 1; margin-left: 8px; font-weight: 600;"><?php echo $log_msg; ?></span>
                                    <button type="button" onclick="jQuery('#tbp-debug-data-<?php echo $index; ?>').toggle();" style="background: #334155; color: #f1f5f9; border: 1px solid #475569; padding: 1px 6px; border-radius: 3px; font-size: 9px; cursor: pointer; flex-shrink: 0; margin-left: 10px;"><?php _e( 'Ver Datos', 'tbp-actividades' ); ?></button>
                                </div>
                                <div id="tbp-debug-data-<?php echo $index; ?>" style="display: none; background: #020617; padding: 8px; border-radius: 3px; margin-top: 5px; color: #a7f3d0; white-space: pre-wrap; word-break: break-all; border: 1px solid #1e293b; line-height: 1.4;">
                                    <?php echo esc_html( json_encode( $log_entry['data'] ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) ); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else : ?>
                    <p style="margin: 0; color: #64748b; background: #f1f5f9; padding: 10px; border-radius: 4px; border: 1px dashed #cbd5e1;"><small><?php _e( 'Aún no se han capturado intentos de escaneo de códigos QR para diagnósticos. Realiza un escaneo o check-in para ver los datos fluir en tiempo real aquí.', 'tbp-actividades' ); ?></small></p>
                <?php endif; ?>
                
                <div style="margin-top: 10px; display: flex; justify-content: space-between; align-items: center;">
                    <button type="button" id="tbp-btn-clear-debug" class="button" style="font-size: 11px; color: #dc3232; border-color: #dc3232; background: transparent;"><?php _e( '🧹 Limpiar Historial', 'tbp-actividades' ); ?></button>
                    <button type="button" onclick="location.reload();" class="button button-primary" style="font-size: 11px;"><?php _e( '🔄 Recargar Diagnósticos', 'tbp-actividades' ); ?></button>
                </div>
            </div>
        </div>
        </div>

        <!-- 2. ASIGNACIÓN DE ASIENTOS -->
        <div class="tbp-order-section">
            <h3><span>🪑</span> <?php _e( 'Asignación de Asientos', 'tbp-actividades' ); ?></h3>
            <?php
            $assignment = function_exists( 'tbp_asientos_get_clean_order_assignment' ) ? tbp_asientos_get_clean_order_assignment( $order_id ) : get_post_meta( $order_id, '_tbp_seat_assignment', true );
            if ( ! empty( $assignment ) && isset( $assignment['status'] ) && $assignment['status'] === 'assigned' ) {
                echo '<div style="background:#e8f5e9; border:1px solid #4caf50; padding:10px; border-radius:4px;">';
                echo '<p style="margin:0 0 5px 0;"><strong>✅ Mesa Asignada: ' . esc_html( $assignment['mesa_numero'] ) . '</strong></p>';
                echo '<p style="margin:0 0 5px 0; font-size:12px;">Zona: ' . esc_html( $assignment['zona'] ) . '</p>';
                echo '<p style="margin:0 0 5px 0; font-size:12px;">Grupo: ' . esc_html( $assignment['grupo'] ) . '</p>';
                echo '<p style="margin:0; font-size:12px;">Lugares: ' . esc_html( $assignment['cantidad'] ) . '</p>';
                echo '</div>';
            } else {
                echo '<p><small>' . __( 'Aún no hay mesa asignada para este pedido.', 'tbp-actividades' ) . '</small></p>';
            }
            ?>
        </div>

        <!-- 3. TOMA DE FOTOGRAFÍA (Placeholder) -->
        <div class="tbp-order-section">
            <h3><span>📸</span> <?php _e( 'Toma de Fotografía', 'tbp-actividades' ); ?> <span class="tbp-badge-placeholder"><?php _e( 'PRÓXIMAMENTE', 'tbp-actividades' ); ?></span></h3>
            <p><small><?php _e( 'Estado de fotos y citas se mostrará aquí en la Fase 2.', 'tbp-actividades' ); ?></small></p>
        </div>

        <!-- 4. DATOS DE ASISTENTES (Event Tickets Plus) -->
        <div class="tbp-order-section">
            <h3><span>👥</span> <?php _e( 'Datos de Asistentes', 'tbp-actividades' ); ?></h3>
            <?php tbp_actividades_render_attendee_editor( $order_id, $order ); ?>
        </div>
    </div>
    <?php
}

/**
 * Fetch all attendee metadata for this order, searching everywhere.
 */
function tbp_actividades_get_order_attendees_meta( $order_id, $order ) {
    global $wpdb;
    $attendees = [];
    $sourced_products = [];

    // 1. Check Database deep links (Attendee Posts/Tables) - THE SOURCE OF TRUTH
    $search_values = array( $order_id, strval($order_id) );
    $order_items_ids = [];
    foreach( $order->get_items() as $item_id => $item ) {
        $search_values[] = $item_id;
        $search_values[] = strval($item_id);
        $order_items_ids[] = intval($item_id);
    }
    
    // NEW: Check Tribe dedicated table if it exists (modern ET+)
    $tec_table = $wpdb->prefix . 'tec_tickets_attendees';
    $tec_meta_table = $wpdb->prefix . 'tec_tickets_attendees_meta';
    $tec_results = [];
    $tec_table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table);
    $tec_meta_table_exists = ($wpdb->get_var("SHOW TABLES LIKE '$tec_meta_table'") === $tec_meta_table);

    if ( $tec_table_exists ) {
        $item_placeholders = !empty($order_items_ids) ? implode(',', array_fill(0, count($order_items_ids), '%d')) : '0';
        $tec_query = $wpdb->prepare("
            SELECT id, post_id, product_id
            FROM $tec_table 
            WHERE order_id = %d 
            OR order_item_id IN ($item_placeholders)
        ", $order_id, ...$order_items_ids);
        $tec_raw = $wpdb->get_results($tec_query);
        
        foreach ( $tec_raw as $row ) {
            if ( $row->post_id ) $tec_results[] = $row->post_id;
            
            // If meta table exists, fetch fields directly
            if ( $tec_meta_table_exists ) {
                $t_meta = $wpdb->get_results( $wpdb->prepare("SELECT meta_key, meta_value FROM $tec_meta_table WHERE attendee_id = %d", $row->id) );
                if ( ! empty($t_meta) ) {
                    $fields = [];
                    foreach ( $t_meta as $m ) {
                        $label = str_replace(array('tribe-tickets-meta-', '-'), array('', ' '), $m->meta_key);
                        $fields[ucfirst($label)] = $m->meta_value;
                    }
                    $attendees['tec_' . $row->id] = array( 'Datos' => $fields );
                    if ( $row->product_id ) $sourced_products[] = intval($row->product_id);
                }
            }
        }
    }

    $placeholders = implode(',', array_fill(0, count($search_values), '%s'));
    $linked_posts = $wpdb->get_col( $wpdb->prepare("
        SELECT DISTINCT pm.post_id 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_value IN ($placeholders)
        AND p.post_type IN ('tribe_wooticket', 'tribe_rsvp', 'attendee', 'tec_attendee')
        AND pm.meta_key IN ('_tribe_wooticket_order', '_tribe_tickets_item_id', '_tribe_tickets_order')
    ", ...$search_values) );
    
    $direct_posts = get_posts( array(
        'post_type'      => array('tribe_wooticket', 'tribe_rsvp', 'attendee', 'tec_attendee'), 
        'meta_query'     => array(
            'relation' => 'OR',
            array(
                'key'   => '_tribe_wooticket_order',
                'value' => $order_id
            ),
            array(
                'key'   => '_tribe_tickets_order',
                'value' => $order_id
            ),
            array(
                'key'   => '_tec_tickets_commerce_order',
                'value' => $order_id
            )
        ),
        'fields'         => 'ids',
        'posts_per_page' => -1,
        'post_status'    => 'any'
    ) );

    $all_post_ids = array_unique(array_merge($linked_posts, $direct_posts, $tec_results));
    
    // VALIDATION: Only accept posts that belong to products in this order
    $order_product_ids = [];
    foreach( $order->get_items() as $item ) {
        $order_product_ids[] = intval($item->get_product_id());
        $order_product_ids[] = intval($item->get_variation_id());
    }
    $order_product_ids = array_filter(array_unique($order_product_ids));

    foreach ( $all_post_ids as $p_id ) {
        $prod_id = get_post_meta( $p_id, '_tribe_wooticket_product', true );
        
        // Fallback checks for product ID
        if ( ! $prod_id ) $prod_id = get_post_meta( $p_id, '_tribe_tickets_product', true );
        if ( ! $prod_id ) {
            $item_id = get_post_meta( $p_id, 'order_item_id', true );
            if ( ! $item_id ) $item_id = get_post_meta( $p_id, '_tribe_wooticket_order_item', true );
            if ( $item_id ) {
                $item = $order->get_item( $item_id );
                if ( $item ) $prod_id = $item->get_product_id();
            }
        }

        // CRITICAL CHECK: Ignore attendee if it doesn't belong to any product in THIS order
        if ( ! empty($order_product_ids) && ( ! $prod_id || ! in_array( intval($prod_id), $order_product_ids ) ) ) {
            continue; 
        }

        $meta = get_post_meta( $p_id, '_tribe_tickets_meta', true );
        if ( is_array( $meta ) && !empty($meta) ) {
            // Normalize: If it's a single guest (flat array), wrap it
            reset($meta);
            $first_key = key($meta);
            if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                $meta = array( 'Datos' => $meta );
            }
            $attendees[$p_id] = $meta;
            if ( $prod_id ) $sourced_products[] = intval($prod_id);
        }
    }

    // 2. Check Order Meta directly (Pending/Draft orders) - ONLY if not already sourced deeper
    $order_direct_meta = get_post_meta( $order_id, '_tribe_tickets_meta', true );
    if ( is_array( $order_direct_meta ) && !empty($order_direct_meta) ) {
        foreach ( $order_direct_meta as $prod_id => $guests ) {
            if ( is_array($guests) && !in_array(intval($prod_id), $sourced_products) ) {
                // Already standardized by Tribe in Order Meta
                $attendees["order_" . $order_id . "_prod_" . $prod_id] = $guests;
                $sourced_products[] = intval($prod_id);
            }
        }
    }

    // 3. Fallback: Check order items directly
    foreach ( $order->get_items() as $item_id => $item ) {
        $pid = $item->get_product_id();
        if ( in_array(intval($pid), $sourced_products) ) continue;
        
        $raw_tickets_meta = wc_get_order_item_meta( $item_id, '_tribe_tickets_meta', true );
        if ( is_array( $raw_tickets_meta ) && !empty($raw_tickets_meta) ) {
            // Normalize
            reset($raw_tickets_meta);
            $first_key = key($raw_tickets_meta);
            if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) {
                $raw_tickets_meta = array( 'Datos' => $raw_tickets_meta );
            }
            $attendees['item_' . $item_id] = $raw_tickets_meta;
        }
    }
    
    return $attendees;
}

/**
 * Optimized version for batch processing (XLSX Export)
 * Returns a map of [order_id => [attendees]]
 * v6.8.8: Added $filter_product_ids to respect UI filters
 */
function tbp_actividades_get_batch_attendees_meta( $order_ids, $filter_product_ids = array() ) {
    global $wpdb;
    if ( empty($order_ids) ) return [];

    $results_map = [];
    $order_ids = array_map('intval', $order_ids);
    $filter_product_ids = array_filter(array_map('intval', (array)$filter_product_ids));
    
    // 1. Fetch Order Item IDs to increase search surface
    $order_ids_sql = implode(',', $order_ids);
    $item_ids = $wpdb->get_col("SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_id IN ($order_ids_sql)");
    
    $search_values = array_merge($order_ids, $item_ids);
    if ( empty($search_values) ) return [];

    $placeholders = implode(',', array_fill(0, count($search_values), '%s'));
    
    // 2. Fetch all linked Attendee Post IDs
    $linked_data = $wpdb->get_results( $wpdb->prepare("
        SELECT pm.post_id, pm.meta_value as linked_id
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_value IN ($placeholders)
        AND p.post_type IN ('tribe_wooticket', 'tribe_rsvp', 'attendee', 'tec_attendee')
        AND pm.meta_key IN ('_tribe_wooticket_order', '_tribe_tickets_item_id')
        GROUP BY pm.post_id
    ", ...$search_values) );

    $item_to_order = [];
    if ( ! empty($item_ids) ) {
        $item_to_order_rows = $wpdb->get_results("SELECT order_item_id, order_id FROM {$wpdb->prefix}woocommerce_order_items WHERE order_item_id IN (".implode(',', $item_ids).")");
        foreach($item_to_order_rows as $it) $item_to_order[$it->order_item_id] = $it->order_id;
    }

    $all_att_post_ids = [];
    foreach ( $linked_data as $row ) {
        $oid = false;
        if ( in_array(intval($row->linked_id), $order_ids) ) {
            $oid = intval($row->linked_id);
        } else if ( isset($item_to_order[$row->linked_id]) ) {
            $oid = intval($item_to_order[$row->linked_id]);
        }
        
        if ( $oid ) {
            $all_att_post_ids[] = $row->post_id;
            if ( ! isset($results_map[$oid]) ) $results_map[$oid] = [];
            $results_map[$oid][$row->post_id] = null;
        }
    }

    // 3. Bulk fetch Metadata AND Product IDs for verification
    if ( ! empty($all_att_post_ids) ) {
        $meta_data = $wpdb->get_results("SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} WHERE post_id IN (".implode(',', $all_att_post_ids).") AND meta_key IN ('_tribe_tickets_meta', '_tribe_wooticket_product', '_tribe_tickets_product')");
        
        $meta_cache = [];
        $prod_cache = [];
        foreach($meta_data as $m) {
            if ($m->meta_key === '_tribe_tickets_meta') $meta_cache[$m->post_id] = maybe_unserialize($m->meta_value);
            else $prod_cache[$m->post_id] = intval($m->meta_value);
        }

        // Pre-fetch order products for validation
        $order_products_map = [];
        $order_items_raw = $wpdb->get_results("SELECT order_id, meta_value as prod_id FROM {$wpdb->prefix}woocommerce_order_items oi JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id WHERE oi.order_id IN ($order_ids_sql) AND oim.meta_key IN ('_product_id', '_variation_id')");
        foreach($order_items_raw as $oir) {
            if (!isset($order_products_map[$oir->order_id])) $order_products_map[$oir->order_id] = [];
            $order_products_map[$oir->order_id][] = intval($oir->prod_id);
        }

        foreach ( $results_map as $oid => &$group ) {
            foreach ( $group as $att_id => $val ) {
                $att_prod_id = $prod_cache[$att_id] ?? 0;
                $valid_prods = $order_products_map[$oid] ?? [];
                
                // CRITICAL CHECK 1: Ignore if product doesn't match order context
                if ( ! empty($valid_prods) && ( ! $att_prod_id || ! in_array($att_prod_id, $valid_prods) ) ) {
                    unset($group[$att_id]);
                    continue;
                }

                // CRITICAL CHECK 2: Respect UI Filter if active (v6.8.8)
                if ( ! empty($filter_product_ids) && ! in_array($att_prod_id, $filter_product_ids) ) {
                    unset($group[$att_id]);
                    continue;
                }

                if ( isset($meta_cache[$att_id]) ) {
                    $group[$att_id] = $meta_cache[$att_id];
                } else {
                    unset($group[$att_id]);
                }
            }
        }
    }

    // 4. Batch Check direct Order Meta as fallback
    $order_meta_rows = $wpdb->get_results("SELECT post_id as order_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($order_ids_sql) AND meta_key = '_tribe_tickets_meta'");
    foreach ( $order_meta_rows as $om ) {
        $o_id = intval($om->order_id);
        if ( ! empty($results_map[$o_id]) ) continue;
        
        $meta = maybe_unserialize($om->meta_value);
        if ( is_array($meta) && !empty($meta) ) {
            $results_map[$o_id] = [];
            foreach ( $meta as $prod_id => $guests ) {
                $p_id = intval($prod_id);
                // Respect UI Filter if active (v6.8.8)
                if ( ! empty($filter_product_ids) && ! in_array($p_id, $filter_product_ids) ) {
                    continue;
                }

                if ( is_array($guests) ) {
                    $results_map[$o_id]["order_" . $o_id . "_prod_" . $p_id] = $guests;
                }
            }
        }
    }

    return $results_map;
}

/**
 * Renders the HTML form
 */
function tbp_actividades_render_attendee_editor( $order_id, $order ) {
    $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
    
    echo '<div style="margin-bottom:10px; font-size:9px; color:#ccc; text-align:right;">Engine v6.8.8</div>';

    // TOOLS FOR ADMINS: Reset/Repair
    if ( current_user_can('manage_options') ) {
        echo '<div style="background:#f8f9fa; border:1px solid #ddd; padding:10px; border-radius:4px; margin-bottom:15px; display:flex; justify-content:space-between; align-items:center;">';
        echo '<span style="font-size:11px; color:#666;">🔧 <strong>Herramientas Admin:</strong></span>';
        echo '<div>';
        if ( !empty($attendees) ) {
            echo '<button type="button" class="button button-link tbp-btn-reset-attendees" data-order-id="' . $order_id . '" style="color:#dc3232; font-size:11px; text-decoration:none;">🗑️ BORRAR Y RESETEAR TODO</button>';
        } else {
             echo '<button type="button" class="button button-secondary tbp-btn-init-attendees" data-order-id="' . $order_id . '" style="font-size:11px;">[+] INICIALIZAR CAMPOS</button>';
        }
        echo '</div>';
        echo '</div>';
    }

    if ( empty( $attendees ) ) {
        echo '<div style="background:#fff5f5; border:1px solid #feb2b2; padding:15px; border-radius:4px;">';
        echo '<p style="margin:0; color:#c53030; font-weight:600;"><small><strong>Aviso:</strong> No hay datos de asistentes capturados todavía o el formato no es compatible.</small></p>';
        if ( current_user_can('manage_options') ) {
             echo '<p style="margin:5px 0 10px 0; font-size:11px; color:#718096;">🔍 Diag para Admin: No se detectaron posts vinculados ni meta en items para ID #' . $order_id . '</p>';
        }
        echo '</div>';
        ?>
        <script>
        jQuery(document).ready(function($){
            $('.tbp-btn-init-attendees').on('click', function(e){
                e.preventDefault();
                var btn = $(this);
                if(!confirm('¿Deseas inicializar manualmente los campos de asistente para este pedido?')) return;
                btn.prop('disabled', true).text('Cargando...');
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'tbp_actividades_init_manual_attendees', order_id: btn.data('order-id'), _ajax_nonce: '<?php echo wp_create_nonce("tbp_init_att"); ?>' },
                    success: function(res) {
                        if(res.success) { alert('Campos inicializados.'); location.reload(); }
                        else { alert('Error: ' + res.data); btn.prop('disabled', false).text('[+] INICIALIZAR CAMPOS'); }
                    }
                });
            });
        });
        </script>
        <?php
        return;
    }

    if ( current_user_can('manage_options') ) : ?>
        <script>
        jQuery(document).ready(function($){
            $('.tbp-btn-reset-attendees').on('click', function(e){
                e.preventDefault();
                var btn = $(this);
                if(!confirm('¡PELIGRO! Esto borrará permanentemente todos los datos de asistentes. ¿Estás SEGURO?')) return;
                btn.prop('disabled', true).text('Borrando...');
                $.ajax({
                    url: ajaxurl, type: 'POST',
                    data: { action: 'tbp_actividades_force_reset_attendees', order_id: btn.data('order-id'), _ajax_nonce: '<?php echo wp_create_nonce("tbp_reset_att"); ?>' },
                    success: function(res) {
                        if(res.success) { alert('Tickets borrados.'); location.reload(); }
                        else { alert('Error: ' + res.data); btn.prop('disabled', false).text('🗑️ BORRAR Y RESETEAR TODO'); }
                    }
                });
            });
        });
        </script>
    <?php endif;

    echo '<div style="background:#fdfdfd; border:1px solid #ddd; padding:10px; border-radius:4px;">';
    wp_nonce_field( 'tbp_save_attendees_nonce', 'tbp_attendees_nonce' );

    $counter = 1;
    foreach ( $attendees as $source_id => $meta_groups ) {
        try {
            $product_id = false;
            if ( strpos($source_id, 'order_') === 0 && strpos($source_id, '_prod_') !== false ) {
                preg_match('/_prod_(\d+)/', $source_id, $m);
                if (!empty($m)) $product_id = intval($m[1]);
            } else if ( strpos($source_id, 'item_') === 0 ) {
                $item_id = str_replace('item_', '', $source_id);
                $item = $order->get_item($item_id);
                if ($item) $product_id = $item->get_product_id();
            } else {
                $product_id = get_post_meta( $source_id, '_tribe_wooticket_product', true );
            }
            
            $schema = [];
            if ( $product_id ) {
                $raw_schema = get_post_meta( $product_id, '_tribe_tickets_meta', true );
                if ( is_array($raw_schema) ) {
                    foreach($raw_schema as $def) {
                        if ( ! is_array($def) ) continue;
                        $slug = isset($def['slug']) ? sanitize_title($def['slug']) : sanitize_title($def['label'] ?? '');
                        if ( empty($slug) && isset($def['label']) ) $slug = sanitize_title($def['label']);
                        $schema[$slug] = $def;
                        if(isset($def['label'])) $schema[sanitize_title($def['label'])] = $def;
                    }
                }
            }

            if ( ! empty($meta_groups) ) {
                reset($meta_groups);
                $first_key = key($meta_groups);
                if ( ! is_numeric($first_key) && $first_key !== 'Datos' ) $meta_groups = array( 'Datos' => $meta_groups );
            }

            foreach ( $meta_groups as $guest_index => $guest_data ) {
                if ( ! is_array($guest_data) ) { $guest_data = [ $guest_index => $guest_data ]; $guest_index = 'Datos'; }
                
                echo '<div style="margin-bottom:15px; padding-bottom:10px; border-bottom:1px dashed #ccc;">';
                echo '<strong style="color:#0073aa;font-size:13px;">🎟️ Asistente ' . $counter . ' <span style="font-size:10px;color:#999;">(Ref DB: ' . esc_html($source_id) . ')</span></strong>';
                echo '<table class="form-table" style="margin-top:5px; width:100%;">';
                
                foreach ( $guest_data as $field_key => $field_value ) {
                    if ( ! is_string($field_key) && ! is_numeric($field_key) ) continue;
                    $display_key = strval($field_key);
                    $actual_value = $field_value;
                    $is_complex_array = false;

                    if ( is_array($field_value) && isset($field_value['label']) ) {
                        $display_key = strval($field_value['label']);
                        $actual_value = $field_value['value'] ?? '';
                        $is_complex_array = true;
                    } else if ( is_array($field_value) ) {
                        $actual_value = wp_json_encode($field_value);
                    } else {
                        $display_key = ucwords(str_replace(['-', '_'], ' ', $display_key));
                    }
                    
                    $field_type = 'text';
                    $field_options = [];
                    $lookup_key = strtolower(sanitize_title($field_key));
                    $field_def = $schema[$lookup_key] ?? ($schema[str_replace('-', '_', $lookup_key)] ?? ($schema[$field_key] ?? false));
                    
                    if ( is_array($field_def) ) {
                        $field_type = $field_def['type'] ?? 'text';
                        $raw_opts = $field_def['extra']['options'] ?? ($field_def['extra'] ?? ($field_def['options'] ?? ($field_def['values'] ?? '')));
                        $field_options = is_array($raw_opts) ? $raw_opts : ( !empty($raw_opts) ? array_map('trim', explode("\n", strval(maybe_unserialize($raw_opts)))) : [] );
                        $norm_opts = [];
                        foreach($field_options as $k => $v) {
                            if (is_array($v)) { $l=$v['label']??($v['name']??$k); $val=$v['value']??($v['slug']??$l); $norm_opts[trim(strval($val))]=trim(strval($l)); }
                            else { $norm_opts[trim(strval($v))]=trim(strval($v)); }
                        }
                        $field_options = $norm_opts;
                    }
                    
                    $input_name = $is_complex_array ? sprintf('tbp_att_meta[%s][%s][%s][value]', $source_id, $guest_index, $field_key) : sprintf('tbp_att_meta[%s][%s][%s]', $source_id, $guest_index, $field_key);
                    $actual_vals = is_array($actual_value) ? $actual_value : (strpos(strval($actual_value), ',')!==false && $field_type==='checkbox' ? array_map('trim', explode(',', strval($actual_value))) : [$actual_value]);
                    $actual_vals = array_map(function($v){ return is_scalar($v)?trim(strval($v)):''; }, $actual_vals);
                    $val_str = implode(', ', $actual_vals);

                    echo '<tr><td style="padding:4px 0;width:40%;font-size:12px;font-weight:600;">'.esc_html($display_key).'</td><td>';
                    if ( in_array($field_type, ['select', 'dropdown']) ) {
                        echo '<select name="'.$input_name.'" style="width:100%;font-size:12px;"><option value="">Selecciona...</option>';
                        foreach($field_options as $ov=>$ol) { $sel=in_array($ov, $actual_vals)||in_array($ol, $actual_vals)?'selected':''; echo '<option value="'.esc_attr($ov).'" '.$sel.'>'.esc_html($ol).'</option>'; }
                        echo '</select>';
                    } else if ($field_type==='radio' || $field_type==='checkbox') {
                        $suffix = $field_type==='checkbox' && !empty($field_options) ? '[]' : '';
                        foreach($field_options as $ov=>$ol) { $sel=in_array($ov, $actual_vals)||in_array($ol, $actual_vals)?'checked':''; echo '<label style="display:block;font-size:11px;"><input type="'.$field_type.'" name="'.$input_name.$suffix.'" value="'.esc_attr($ov).'" '.$sel.' /> '.esc_html($ol).'</label>'; }
                        if(empty($field_options)) { $sel=!empty($val_str)?'checked':''; echo '<input type="checkbox" name="'.$input_name.'" value="1" '.$sel.' />'; }
                    } else if ($field_type==='textarea') { echo '<textarea name="'.$input_name.'" style="width:100%;font-size:12px;" rows="2">'.esc_textarea($val_str).'</textarea>'; }
                    else { $type = in_array($field_type, ['date','number','email','url']) ? $field_type : 'text'; echo '<input type="'.$type.'" name="'.$input_name.'" value="'.esc_attr($val_str).'" style="width:100%;font-size:12px;" />'; }
                    echo '</td></tr>';
                }
                echo '</table></div>';
                $counter++;
            }
        } catch (\Throwable $e) { echo '<div style="color:red;">Error: '.esc_html($e->getMessage()).'</div>'; $counter++; }
    }
    echo '<p><small>Haz clic en <strong>Actualizar</strong> para guardar.</small></p></div>';
}

/**
 * Save Handler
 */
add_action( 'woocommerce_process_shop_order_meta', 'tbp_actividades_save_attendee_editor', 60, 1 );
function tbp_actividades_save_attendee_editor( $post_id ) {
    if ( ! current_user_can( 'edit_shop_order', $post_id ) ) return;
    if ( ! isset( $_POST['tbp_attendees_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_attendees_nonce'], 'tbp_save_attendees_nonce' ) ) return;
    if ( ! isset( $_POST['tbp_att_meta'] ) || ! is_array( $_POST['tbp_att_meta'] ) ) return;

    foreach ( $_POST['tbp_att_meta'] as $source_id => $guest_groups ) {
        if ( strpos($source_id, 'order_') === 0 && strpos($source_id, '_prod_') !== false ) {
            preg_match('/order_(\d+)_prod_(\d+)/', $source_id, $matches);
            if ( !empty($matches) ) {
                $o_id = intval($matches[1]); $p_id = intval($matches[2]);
                $original_meta = get_post_meta( $o_id, '_tribe_tickets_meta', true );
                if ( ! is_array( $original_meta ) ) $original_meta = [];
                foreach ( $guest_groups as $gi => $fields ) {
                    if ( ! isset($original_meta[$p_id][$gi]) ) $original_meta[$p_id][$gi] = [];
                    foreach( $fields as $k => $v ) {
                         if ( is_array($v) && isset($v['value']) ) { $original_meta[$p_id][$gi][$k]['value'] = sanitize_text_field($v['value']); }
                         else { $original_meta[$p_id][$gi][$k] = sanitize_text_field($v); }
                    }
                }
                update_post_meta( $o_id, '_tribe_tickets_meta', $original_meta );
            }
            continue;
        }

        $is_item = strpos($source_id, 'item_') === 0;
        $id = $is_item ? str_replace('item_', '', $source_id) : intval($source_id);
        $original_meta = $is_item ? wc_get_order_item_meta( $id, '_tribe_tickets_meta', true ) : get_post_meta( $id, '_tribe_tickets_meta', true );
        if ( ! is_array( $original_meta ) ) $original_meta = [];

        foreach ( $guest_groups as $gi => $fields ) {
            $dest =& $original_meta;
            if ( $gi !== 'Datos' ) { if(!isset($dest[$gi])) $dest[$gi]=[]; $dest =& $dest[$gi]; }
            foreach( $fields as $k => $v ) {
                if ( is_array($v) && isset($v['value']) ) { if(!is_array($dest[$k])) $dest[$k]=[]; $dest[$k]['value'] = sanitize_text_field($v['value']); }
                else { $dest[$k] = sanitize_text_field($v); }
            }
        }
        
        if ( $is_item ) wc_update_order_item_meta( $id, '_tribe_tickets_meta', $original_meta );
        else {
            update_post_meta( $id, '_tribe_tickets_meta', $original_meta );
            global $wpdb;
            $tec_table = $wpdb->prefix . 'tec_tickets_attendees_meta';
            if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) {
                foreach ( $guest_groups as $gi => $fields ) {
                    foreach($fields as $k => $v) { $wpdb->update( $tec_table, [ 'meta_value' => sanitize_text_field($v) ], [ 'attendee_id' => $id, 'meta_key' => $k ], [ '%s' ], [ '%d', '%s' ] ); }
                }
            }
        }
    }
}

add_action( 'wp_ajax_tbp_actividades_init_manual_attendees', 'tbp_actividades_init_manual_attendees_ajax' );
function tbp_actividades_init_manual_attendees_ajax() {
    check_ajax_referer( 'tbp_init_att', '_ajax_nonce' );
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permisos');
    $order_id = intval($_POST['order_id']); $order = wc_get_order($order_id);
    if ( ! $order ) wp_send_json_error('No order');
    $init_meta = [];
    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $schema = get_post_meta($product_id, '_tribe_tickets_meta', true);
        if ( is_array($schema) ) {
            $qty = $item->get_quantity();
            if ( ! isset($init_meta[$product_id]) ) $init_meta[$product_id] = [];
            for ($i = 0; $i < $qty; $i++) {
                $guest_data = [];
                foreach($schema as $field) { if(isset($field['label'])) $guest_data[$field['label']] = ''; }
                if ( !empty($guest_data) ) $init_meta[$product_id][] = $guest_data;
            }
        }
    }
    if ( empty($init_meta) ) wp_send_json_error('No schema');
    update_post_meta( $order_id, '_tribe_tickets_meta', $init_meta );
    wp_send_json_success();
}

add_action( 'wp_ajax_tbp_actividades_force_reset_attendees', 'tbp_actividades_force_reset_attendees_ajax' );
function tbp_actividades_force_reset_attendees_ajax() {
    check_ajax_referer( 'tbp_reset_att', '_ajax_nonce' );
    if ( ! current_user_can('manage_options') ) wp_send_json_error('Permisos');
    $order_id = intval($_POST['order_id']); $order = wc_get_order($order_id);
    if ( ! $order ) wp_send_json_error('No order');
    $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
    foreach ( $attendees as $sid => $data ) { if ( is_numeric($sid) ) wp_delete_post( $sid, true ); }
    delete_post_meta( $order_id, '_tribe_tickets_meta' );
    delete_post_meta( $order_id, '_tribe_tickets_event' );
    foreach ( $order->get_items() as $iid => $item ) { wc_delete_order_item_meta( $item->get_id(), '_tribe_tickets_meta' ); wc_delete_order_item_meta( $item->get_id(), '_tribe_tickets_generated' ); }
    global $wpdb;
    $tec_table = $wpdb->prefix . 'tec_tickets_attendees';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) $wpdb->delete( $tec_table, [ 'order_id' => $order_id ], [ '%d' ] );
    wp_send_json_success();
}

/**
 * 7-LAYER DISCOVERY ENGINE: Finds the Tribe Event ID from a WooCommerce Order ID.
 * Ported from the Premiaciones module for universal reliability.
 */
function tbp_actividades_discover_event_id_from_order( $order_id ) {
    static $event_cache = [];
    if ( isset($event_cache[$order_id]) ) return $event_cache[$order_id];

    $order = wc_get_order( $order_id );
    if ( ! $order ) return 0;

    $tribe_event_id = 0;

    // Layer 1: Order Meta (Standard & Legacy)
    $tribe_event_id = get_post_meta($order_id, '_tribe_tickets_event', true); 
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_tribe_wooticket_event', true);
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_event_id', true);
    if (!$tribe_event_id) $tribe_event_id = get_post_meta($order_id, '_tribe_wooticket_for_event', true);

    // Layer 2: Order Item Meta
    if (!$tribe_event_id) {
        foreach($order->get_items() as $item_id => $item) {
            $tribe_event_id = wc_get_order_item_meta( $item_id, '_tribe_tickets_event', true );
            if (!$tribe_event_id) $tribe_event_id = wc_get_order_item_meta( $item_id, '_event_id', true );
            if (!$tribe_event_id) $tribe_event_id = wc_get_order_item_meta( $item_id, '_tribe_wooticket_event', true );
            if ($tribe_event_id) break;
        }
    }

    // Layer 3: Product Meta (Variation and Parent)
    if (!$tribe_event_id) {
        foreach($order->get_items() as $item) {
            $p_id = $item->get_product_id();
            $v_id = $item->get_variation_id();
            
            // Try product first
            $tribe_event_id = get_post_meta($p_id, '_tribe_tickets_event', true);
            if (!$tribe_event_id) $tribe_event_id = get_post_meta($p_id, '_tribe_wooticket_event', true);
            if (!$tribe_event_id) $tribe_event_id = get_post_meta($p_id, '_tribe_wooticket_for_event', true);
            
            // Try variation if applicable
            if (!$tribe_event_id && $v_id) {
                $tribe_event_id = get_post_meta($v_id, '_tribe_tickets_event', true);
            }
            
            if ($tribe_event_id) break;
        }
    }

    // Layer 4: Attendee Posts Search (Deep SQL)
    if (!$tribe_event_id) {
        global $wpdb;
        $attendee_id = $wpdb->get_var( $wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_tribe_wooticket_order', '_tribe_tickets_order') AND (meta_value = %s OR meta_value = %d) LIMIT 1", (string)$order_id, $order_id ) );
        if ($attendee_id) {
            $tribe_event_id = get_post_meta($attendee_id, '_tribe_tickets_event', true);
            if (!$tribe_event_id) $tribe_event_id = get_post_meta($attendee_id, '_event_id', true);
        }
    }

    // Layer 5: Modern TEC Tables (Attendee Table)
    if (!$tribe_event_id) {
        global $wpdb;
        $tec_table = $wpdb->prefix . 'tec_tickets_attendees';
        if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) {
            $tribe_event_id = $wpdb->get_var( $wpdb->prepare("SELECT event_id FROM $tec_table WHERE order_id = %d LIMIT 1", $order_id) );
        }
    }

    // Layer 6: Reverse Product Lookup (Event -> Ticket)
    if (!$tribe_event_id) {
        global $wpdb;
        $product_ids = [];
        foreach ($order->get_items() as $item) { $product_ids[] = $item->get_product_id(); }
        if (!empty($product_ids)) {
            $placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $event_id_found = $wpdb->get_var( $wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('_tribe_wooticket', '_tribe_tickets_list') AND meta_value IN ($placeholders) LIMIT 1", ...$product_ids) );
            if ($event_id_found) $tribe_event_id = $event_id_found;
        }
    }

    // Layer 7: Post Parent Logic (If product is child of event)
    if (!$tribe_event_id) {
        foreach ($order->get_items() as $item) {
            $p_id = $item->get_product_id();
            $parent = wp_get_post_parent_id($p_id);
            if ($parent && get_post_type($parent) === 'tribe_events') {
                $tribe_event_id = $parent;
                break;
            }
        }
    }

    $event_cache[$order_id] = intval($tribe_event_id);
    return intval($tribe_event_id);
}

/**
 * Resolves a delivery rule by its CRC32 hash and order ID.
 */
function tbp_actividades_get_rule_by_hash( $order_id, $rifa_id ) {
    $event_id = tbp_actividades_discover_event_id_from_order( $order_id );
    if ( ! $event_id ) {
        return null;
    }
    
    $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
    if ( is_array( $rules ) ) {
        foreach ( $rules as $rule ) {
            $hash = crc32( $rule['id'] );
            if ( $hash === intval( $rifa_id ) || sprintf('%u', $hash) === sprintf('%u', $rifa_id) ) {
                return $rule;
            }
        }
    }
    return null;
}

/**
 * AJAX Action to clear debug logs.
 */
add_action( 'wp_ajax_tbp_actividades_clear_debug_logs', 'tbp_actividades_clear_debug_logs_ajax' );
function tbp_actividades_clear_debug_logs_ajax() {
    check_ajax_referer( 'tbp_clear_debug', '_ajax_nonce' );
    if ( ! current_user_can('manage_options') ) {
        wp_send_json_error( 'Permisos denegados.' );
    }
    
    update_option( 'tbp_delivery_debug_logs', [] );
    wp_send_json_success();
}
