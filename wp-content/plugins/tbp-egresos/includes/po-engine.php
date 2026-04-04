<?php
/**
 * PO Generation & Automation Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook into WooCommerce order status changes
 * Supported triggers for PO updates
 */
add_action( 'woocommerce_order_status_changed', 'tbp_egresos_process_order_status_change', 20, 4 );

function tbp_egresos_process_order_status_change( $order_id, $status_from, $status_to, $order ) {
    $events = tbp_egresos_get_events_from_order( $order );
    if ( empty( $events ) ) return;

    $affected_pos = array();

    foreach ( $events as $event_id => $config_per_product ) {
        foreach ( $config_per_product as $product_id => $egresos_config ) {
            foreach ( $egresos_config as $config ) {
                $proveedor_id = intval( $config['proveedor_id'] );
                if ( ! $proveedor_id ) continue;

                $po_id = tbp_egresos_get_or_create_po( $event_id, $proveedor_id );
                if ( $po_id ) {
                    $affected_pos[] = $po_id;
                }
            }
        }
    }

    // Recompute all affected POs
    $affected_pos = array_unique( $affected_pos );
    foreach ( $affected_pos as $po_id ) {
        tbp_egresos_recompute_po_totals( $po_id );
    }
}

/**
 * Helper to extract all Event IDs and Egresos Config from an Order
 * Uses the same aggressive search as the manual scan.
 */
function tbp_egresos_get_events_from_order( $order ) {
    $results = array();
    $items = $order->get_items();

    foreach ( $items as $item ) {
        $product_id = $item->get_product_id();
        $egresos_config = get_post_meta( $product_id, '_tbp_egresos_config', true );
        
        if ( empty( $egresos_config ) || ! is_array( $egresos_config ) ) continue;

        // Aggressive Ticket -> Event search
        $event_id = get_post_meta( $product_id, '_tribe_tickets_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_tribe_wooticket_for_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_event_id', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, 'event_id', true );

        if ( $event_id ) {
            $event_id = intval( $event_id );
            $results[$event_id][$product_id] = $egresos_config;
        }
    }
    return $results;
}

/**
 * Get existing PO for Event+Provider or create a new one
 */
function tbp_egresos_get_or_create_po( $event_id, $proveedor_id ) {
    $args = array(
        'post_type'  => 'tbp_orden_compra',
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key'   => '_tbp_event_id',
                'value' => $event_id,
            ),
            array(
                'key'   => '_tbp_proveedor_id',
                'value' => $proveedor_id,
            ),
        ),
        'posts_per_page' => 1,
        'fields'         => 'ids',
        'post_status'    => array('publish', 'draft', 'private', 'pending')
    );

    $pos = get_posts( $args );

    if ( ! empty( $pos ) ) {
        return $pos[0];
    }

    // Create new PO
    $event_title = get_the_title( $event_id );
    $proveedor_title = get_the_title( $proveedor_id );
    
    $po_data = array(
        'post_title'  => $event_title . ' | ' . $proveedor_title,
        'post_status' => 'publish',
        'post_type'   => 'tbp_orden_compra',
    );

    $po_id = wp_insert_post( $po_data );

    if ( $po_id ) {
        update_post_meta( $po_id, '_tbp_event_id', $event_id );
        update_post_meta( $po_id, '_tbp_proveedor_id', $proveedor_id );
        update_post_meta( $po_id, '_tbp_cantidad_proyectada', 0 );
        update_post_meta( $po_id, '_tbp_cantidad_autorizada', 0 );
        return $po_id;
    }

    return false;
}

/**
 * Auto-recompute when an order is saved or changes status
 */
add_action( 'save_post_shop_order', 'tbp_egresos_trigger_recompute_from_order', 10, 1 );
add_action( 'woocommerce_order_status_changed', 'tbp_egresos_trigger_recompute_from_order_status', 10, 3 );

function tbp_egresos_trigger_recompute_from_order( $order_id ) {
    if ( ! $order_id ) return;
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $items = $order->get_items();
    $affected_pos = array();

    foreach ( $items as $item ) {
        $product_id = $item->get_product_id();
        $egresos_config = get_post_meta( $product_id, '_tbp_egresos_config', true );
        if ( empty( $egresos_config ) || ! is_array( $egresos_config ) ) continue;

        // Get Event ID from ticket-product
        $event_id = get_post_meta( $product_id, '_tribe_tickets_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_tribe_wooticket_for_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_event_id', true );

        if ( ! $event_id ) continue;

        foreach ( $egresos_config as $config ) {
            $proveedor_id = intval( $config['proveedor_id'] );
            if ( $proveedor_id ) {
                $po_id = tbp_egresos_get_or_create_po( $event_id, $proveedor_id );
                if ( $po_id ) $affected_pos[] = $po_id;
            }
        }
    }

    foreach ( array_unique( $affected_pos ) as $po_id ) {
        tbp_egresos_recompute_po_totals( $po_id );
    }
}

function tbp_egresos_trigger_recompute_from_order_status( $order_id, $old_status, $new_status ) {
    tbp_egresos_trigger_recompute_from_order( $order_id );
}

/**
 * Recompute PO Totals (Projected & Authorized)
 */
function tbp_egresos_recompute_po_totals( $po_id ) {
    $event_id = intval( get_post_meta( $po_id, '_tbp_event_id', true ) );

    // SMART LINKER: Check if 0 but title has "Event | Provider" pattern
    if ( $event_id === 0 ) {
        $title = get_the_title( $po_id );
        if ( strpos( $title, '|' ) !== false ) {
            $parts = explode( '|', $title );
            $event_name = trim( $parts[0] );
            $event = get_page_by_title( $event_name, OBJECT, 'tribe_events' );
            if ( $event ) {
                $event_id = $event->ID;
                update_post_meta( $po_id, '_tbp_event_id', $event_id );
            }
        }
    }

    $proveedor_id = get_post_meta( $po_id, '_tbp_proveedor_id', true );

    if ( ! $proveedor_id ) return;

    // SI NO HAY EVENTO, ES UNA PO MANUAL DE OPERACIÓN
    // CALCULAMOS PROYECCIÓN BASADA EN VIGENCIA (DURACIÓN TOTAL DEL CONTRATO)
    if ( ! $event_id ) {
        $monto_fijo = floatval( get_post_meta( $po_id, '_tbp_monto_fijo', true ) );
        $start = get_post_meta( $po_id, '_tbp_contract_start', true );
        $end = get_post_meta( $po_id, '_tbp_contract_end', true );
        $frecuencia = get_post_meta( $po_id, '_tbp_frecuencia', true );

        $num_periodos = 1;
        if ( $start && $end ) {
            $d1 = new DateTime($start);
            $d2 = new DateTime($end);
            $diff = $d1->diff($d2);
            
            switch ($frecuencia) {
                case 'Semanal':
                    $num_periodos = ceil($diff->days / 7);
                    break;
                case 'Mensual':
                    $num_periodos = ($diff->y * 12) + $diff->m;
                    if ($diff->d > 0) $num_periodos++; 
                    break;
                case 'Bimestral':
                    $num_periodos = ceil((($diff->y * 12) + $diff->m) / 2);
                    break;
                case 'Anual':
                    $num_periodos = $diff->y;
                    if ($diff->m > 0 || $diff->d > 0) $num_periodos++;
                    break;
                default:
                    $num_periodos = 1;
            }
        }
        
        if ($num_periodos <= 0) $num_periodos = 1;

        $total_proyectado = $monto_fijo * $num_periodos;
        update_post_meta( $po_id, '_tbp_cantidad_proyectada', $total_proyectado );
        
        // For manual POs, pieces = number of periods (service units)
        update_post_meta( $po_id, '_tbp_piezas_proyectadas', $num_periodos );
        
        // ALSO Recompute Manual Authorization and Pieces
        tbp_egresos_recompute_manual_po_autorizacion( $po_id );
        return; 
    }

    // 1. Get all products linked to this event (Aggressive Multi-key support)
    global $wpdb;
    $ticket_ids = $wpdb->get_col( $wpdb->prepare( "
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE (meta_value = %d OR meta_value = %s)
        AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_event_id', 'event_id')
    ", $event_id, (string)$event_id ) );

    if ( empty( $ticket_ids ) ) return;

    $total_proyectado = 0;
    $total_autorizado = 0;
    $piezas_proyectadas = 0;
    $piezas_autorizadas = 0;

    // Status groups as per user requirements (Robust check for prefixes)
    $proyectado_keys = array( 'p-pagado', 'wc-p-pagado', 'completed', 'wc-completed', 'processing', 'wc-processing' );
    $autorizado_keys = array( 'completed', 'wc-completed', 'processing', 'wc-processing' );

    $ticket_ids_str = implode( ',', array_map( 'intval', $ticket_ids ) );
    
    $order_items = $wpdb->get_results( "
        SELECT oim_pid.meta_value as product_id, oim_qty.meta_value as qty, p.post_status as order_status
        FROM {$wpdb->prefix}woocommerce_order_items oi
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_pid ON oi.order_item_id = oim_pid.order_item_id AND oim_pid.meta_key = '_product_id'
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim_qty ON oi.order_item_id = oim_qty.order_item_id AND oim_qty.meta_key = '_qty'
        JOIN {$wpdb->posts} p ON oi.order_id = p.ID
        WHERE oim_pid.meta_value IN ($ticket_ids_str)
        AND p.post_type = 'shop_order'
        AND p.post_status NOT IN ('wc-cancelled', 'wc-refunded', 'wc-failed', 'trash', 'auto-draft')
    " );

    foreach ( $order_items as $oi ) {
        $product_id = intval( $oi->product_id );
        $qty = floatval( $oi->qty );
        $status = $oi->order_status;

        $config = get_post_meta( $product_id, '_tbp_egresos_config', true );
        if ( ! is_array( $config ) ) continue;

        foreach ( $config as $egreso ) {
            if ( intval( $egreso['proveedor_id'] ) === intval( $proveedor_id ) ) {
                $item_cantidad = isset( $egreso['cantidad'] ) ? floatval( $egreso['cantidad'] ) : 1;
                $item_precio = isset( $egreso['precio_unitario'] ) ? floatval( $egreso['precio_unitario'] ) : 0;
                $monto_item = $item_cantidad * $item_precio * $qty;
                $piezas_item = $item_cantidad * $qty;
                
                // Normalize status (remove wc- prefix for check if needed, but we check both anyway)
                $clean_status = str_replace( 'wc-', '', $status );

                // Cantidad Proyectada
                if ( in_array( $status, $proyectado_keys ) || in_array( $clean_status, $proyectado_keys ) ) {
                    $total_proyectado   += floatval($monto_item);
                    $piezas_proyectadas += floatval($piezas_item);
                }

                // Cantidad Autorizada
                if ( in_array( $status, $autorizado_keys ) || in_array( $clean_status, $autorizado_keys ) ) {
                    $total_autorizado   += floatval($monto_item);
                    $piezas_autorizadas += floatval($piezas_item);
                }
            }
        }
    }

    // Ensure we save numbers (not empty strings)
    update_post_meta( $po_id, '_tbp_cantidad_proyectada', round($total_proyectado, 2) );
    update_post_meta( $po_id, '_tbp_cantidad_autorizada', round($total_autorizado, 2) );
    update_post_meta( $po_id, '_tbp_piezas_proyectadas', round($piezas_proyectadas, 0) );
    update_post_meta( $po_id, '_tbp_piezas_autorizadas', round($piezas_autorizadas, 0) );
    
    // Update simple paid total too for the summary box
    $total_paid = tbp_egresos_get_po_total_paid( $po_id );
    update_post_meta( $po_id, '_tbp_total_pagado', $total_paid );
}

/**
 * Perform a full scan of all WooCommerce orders to regenerate/sync all POs
 */
function tbp_egresos_full_sync_retroactive() {
    global $wpdb;
    
    // 1. Get all products that have an Egresos config
    $products = $wpdb->get_results( "
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE meta_key = '_tbp_egresos_config'
    " );

    if ( empty( $products ) ) return 0;

    $affected_pos = array();

    foreach ( $products as $p ) {
        $product_id = intval( $p->post_id );
        $egresos_config = get_post_meta( $product_id, '_tbp_egresos_config', true );
        if ( empty( $egresos_config ) || ! is_array( $egresos_config ) ) continue;

        // Get Event ID
        $event_id = get_post_meta( $product_id, '_tribe_tickets_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_tribe_wooticket_for_event', true );
        if ( ! $event_id ) $event_id = get_post_meta( $product_id, '_event_id', true );

        if ( ! $event_id ) continue;

        foreach ( $egresos_config as $config ) {
            $proveedor_id = intval( $config['proveedor_id'] );
            if ( ! $proveedor_id ) continue;

            $po_id = tbp_egresos_get_or_create_po( $event_id, $proveedor_id );
            if ( $po_id ) {
                $affected_pos[] = $po_id;
            }
        }
    }

    // 2. Recompute all
    $unique_pos = array_unique( $affected_pos );
    foreach ( $unique_pos as $po_id ) {
        tbp_egresos_recompute_po_totals( $po_id );
    }

    return count( $unique_pos );
}

/**
 * Get total payments already made for a PO
 */
function tbp_egresos_get_po_total_paid( $po_id ) {
    global $wpdb;
    
    // Sum from tbp_pago CPT linked to this PO
    $total_paid = $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(pm.meta_value) 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE p.post_type = 'tbp_pago'
        AND p.post_status = 'publish'
        AND pm.meta_key = '_tbp_monto'
        AND pm.post_id IN (
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key = '_tbp_po_id' 
            AND meta_value = %d
        )
    ", $po_id ) );

    return floatval( $total_paid );
}

/**
 * Get remaining authorized balance for a PO
 */
function tbp_egresos_get_po_balance( $po_id ) {
    $authorized = floatval( get_post_meta( $po_id, '_tbp_cantidad_autorizada', true ) );
    $paid = tbp_egresos_get_po_total_paid( $po_id );
    
    return max( 0, $authorized - $paid );
}

/**
 * PO Manual Columns & Display in Admin
 */
add_action( 'add_meta_boxes_tbp_orden_compra', 'tbp_egresos_add_po_meta_boxes' );
function tbp_egresos_add_po_meta_boxes() {
    add_meta_box(
        'tbp_po_totals',
        __( 'Resumen Financiero', 'tbp-egresos' ),
        'tbp_egresos_po_totals_html',
        'tbp_orden_compra',
        'side',
        'high'
    );
}

function tbp_egresos_po_totals_html( $post ) {
    $proyectada = get_post_meta( $post->ID, '_tbp_cantidad_proyectada', true );
    $autorizada = get_post_meta( $post->ID, '_tbp_cantidad_autorizada', true );
    $pagado = get_post_meta( $post->ID, '_tbp_total_pagado', true ); // Updated in Phase 4
    
    $saldo = floatval( $autorizada ) - floatval( $pagado );

    ?>
    <div class="po-totals-box">
        <p><strong><?php _e( 'Cantidad Proyectada:', 'tbp-egresos' ); ?></strong><br/><span style="font-size: 1.2em;"><?php echo wc_price( $proyectada ); ?></span></p>
        <p><strong><?php _e( 'Cantidad Autorizada:', 'tbp-egresos' ); ?></strong><br/><span style="font-size: 1.5em; color: #2271b1;"><?php echo wc_price( $autorizada ); ?></span></p>
        <hr/>
        <p><strong><?php _e( 'Pagado:', 'tbp-egresos' ); ?></strong><br/><span><?php echo wc_price( $pagado ); ?></span></p>
        <p><strong><?php _e( 'Saldo Autorizado:', 'tbp-egresos' ); ?></strong><br/><span style="font-weight: bold; <?php echo ($saldo < 0) ? 'color:red;' : ''; ?>"><?php echo wc_price( $saldo ); ?></span></p>
        
        <hr/>
        <div style="margin-top: 15px; text-align: center;">
            <a href="<?php echo add_query_arg( 'tbp_recompute_po', '1', get_edit_post_link($post->ID) ); ?>" class="button button-secondary" style="width: 100%; font-size: 11px;">
                <span class="dashicons dashicons-update" style="font-size: 14px; vertical-align: middle; margin-right: 5px;"></span>
                🔄 RECALCULAR TODO
            </a>
            <small style="display:block; color:#888; font-size:9px; margin-top:5px;">Úsalo si las PIEZAS o MONTOS están en 0.</small>
        </div>
    </div>
    <style>
        .po-totals-box p { margin-bottom: 10px; }
    </style>
    <?php
}

/**
 * Recompute Manual PO Authorization based on past periods
 */
function tbp_egresos_recompute_manual_po_autorizacion( $po_id ) {
    $monto_fijo = floatval( get_post_meta( $po_id, '_tbp_monto_fijo', true ) );
    $start = get_post_meta( $po_id, '_tbp_contract_start', true );
    $frecuencia = get_post_meta( $po_id, '_tbp_frecuencia', true );
    $today = date('Y-m-d');

    if ( $monto_fijo > 0 && $start ) {
        $d1 = new DateTime($start);
        $d2 = new DateTime($today);
        $diff = $d1->diff($d2);
        
        $num_periodos = 0;
        if ( $d1 <= $d2 ) {
            switch ($frecuencia) {
                case 'Semanal':
                    $num_periodos = floor($diff->days / 7) + 1;
                    break;
                case 'Mensual':
                    $num_periodos = ($diff->y * 12) + $diff->m + 1;
                    break;
                case 'Bimestral':
                    $num_periodos = floor((($diff->y * 12) + $diff->m) / 2) + 1;
                    break;
                case 'Anual':
                    $num_periodos = $diff->y + 1;
                    break;
                default:
                    $num_periodos = 1;
            }
        }
        
        if ($num_periodos <= 0) $num_periodos = 1;

        $new_auth = $monto_fijo * $num_periodos;
        update_post_meta( $po_id, '_tbp_cantidad_autorizada', $new_auth );
        update_post_meta( $po_id, '_tbp_piezas_autorizadas', $num_periodos );

        // Update the next renewal date based on this calculation
        $next_date = tbp_egresos_calculate_next_date( $today, $frecuencia );
        update_post_meta( $po_id, '_tbp_next_renewal', $next_date );

        return $new_auth;
    }
    return 0;
}
