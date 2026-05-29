<?php
/**
 * Advanced Financial Reports for TBP Egresos
 * v5.4.0 - Pagos Efectuados & Ingresos vs Egresos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * 1. REPORT: PAGOS EFECTUADOS (Historical Disbursements)
 */
function tbp_egresos_pagos_history_page_content() {
    global $wpdb;

    $date_from = isset($_GET['f_date_from']) ? sanitize_text_field($_GET['f_date_from']) : '';
    $date_to   = isset($_GET['f_date_to']) ? sanitize_text_field($_GET['f_date_to']) : '';
    $event_id  = isset($_GET['f_event']) ? intval($_GET['f_event']) : 0;
    $po_id     = isset($_GET['f_po']) ? intval($_GET['f_po']) : 0;

    // Filters for Query
    $meta_query = array('relation' => 'AND');
    if ( $po_id ) {
        $meta_query[] = array( 'key' => '_tbp_po_id', 'value' => $po_id, 'compare' => '=' );
    }

    if ( $date_from ) {
        $meta_query[] = array( 'key' => '_tbp_fecha_pago', 'value' => $date_from, 'compare' => '>=', 'type' => 'DATE' );
    }
    if ( $date_to ) {
        $meta_query[] = array( 'key' => '_tbp_fecha_pago', 'value' => $date_to, 'compare' => '<=', 'type' => 'DATE' );
    }

    $args = array(
        'post_type'      => 'tbp_pago',
        'posts_per_page' => 100,
        'post_status'    => 'publish',
        'meta_key'       => '_tbp_fecha_pago',
        'orderby'        => 'meta_value',
        'order'          => 'DESC',
        'meta_query'     => $meta_query
    );

    $payments_raw = get_posts( $args );
    $results = array();

    foreach ( $payments_raw as $p ) {
        $curr_po_id = get_post_meta( $p->ID, '_tbp_po_id', true );
        if ( $event_id ) {
            $po_event = get_post_meta( $curr_po_id, '_tbp_event_id', true );
            if ( intval($po_event) !== $event_id ) continue;
        }

        $raw_date = get_post_meta( $p->ID, '_tbp_fecha_pago', true );
        $formatted_date = $raw_date ? date('d/m/Y', strtotime($raw_date)) : 'N/A';

        $results[] = array(
            'id'       => $p->ID,
            'date'     => $formatted_date,
            'monto'    => get_post_meta( $p->ID, '_tbp_monto', true ),
            'metodo'   => get_post_meta( $p->ID, '_tbp_forma_pago', true ),
            'po'       => get_the_title( $curr_po_id ),
            'po_id'    => $curr_po_id,
            'event'    => get_the_title( get_post_meta( $curr_po_id, '_tbp_event_id', true ) ),
            'provider' => get_the_title( get_post_meta( $curr_po_id, '_tbp_proveedor_id', true ) ),
            'forced'   => get_post_meta( $p->ID, '_tbp_forced_by', true ),
            'file'     => get_post_meta( $p->ID, '_tbp_file_evidencia', true ),
            'ref'      => get_post_meta( $p->ID, '_tbp_concepto_pago', true )
        );
    }

    ?>
    <div class="wrap" style="background: #f0f2f5; padding: 20px; border-radius: 8px;">
        <h1 style="font-weight: 800; color: #1a1a1a; margin-bottom: 25px;">📊 Pagos Efectuados (Historial Maestro)</h1>

        <!-- FILTERS -->
        <div style="background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 25px;">
            <form method="get" style="display: flex; gap: 15px; flex-wrap: wrap; align-items: flex-end;">
                <input type="hidden" name="page" value="tbp-egresos-pagos-historial">
                
                <div style="flex: 1; min-width: 200px;">
                    <label style="display:block; font-size:11px; font-weight:bold; color:#666; margin-bottom:5px;">EVENTO</label>
                    <select name="f_event" style="width:100%; height:40px; border-radius:6px; border:1px solid #ddd;">
                        <option value="">-- Todos los Eventos --</option>
                        <?php
                        $events = get_posts( array( 'post_type' => 'tribe_events', 'posts_per_page' => -1 ) );
                        foreach ( $events as $ev ) {
                            echo '<option value="' . $ev->ID . '" ' . selected($event_id, $ev->ID, false) . '>' . esc_html($ev->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>

                <div style="width: 150px;">
                    <label style="display:block; font-size:11px; font-weight:bold; color:#666; margin-bottom:5px;">DESDE</label>
                    <input type="date" name="f_date_from" value="<?php echo $date_from; ?>" style="width:100%; height:40px; border-radius:6px; border:1px solid #ddd;">
                </div>

                <div style="width: 150px;">
                    <label style="display:block; font-size:11px; font-weight:bold; color:#666; margin-bottom:5px;">HASTA</label>
                    <input type="date" name="f_date_to" value="<?php echo $date_to; ?>" style="width:100%; height:40px; border-radius:6px; border:1px solid #ddd;">
                </div>

                <button type="submit" class="button button-primary" style="height:40px; padding: 0 25px; background: #2271b1; border-radius: 6px; font-weight: bold;">FILTRAR RESULTADOS</button>
            </form>
        </div>

        <table class="wp-list-table widefat fixed striped" style="border-radius: 12px; overflow: hidden; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
            <thead>
                <tr style="background: #2271b1; color: white;">
                    <th style="padding: 15px; color: white !important;">Fecha</th>
                    <th style="padding: 15px; color: white !important;">Evento</th>
                    <th style="padding: 15px; color: white !important;">Proveedor / O.C</th>
                    <th style="padding: 15px; color: white !important;">Forma de Pago</th>
                    <th style="padding: 15px; color: white !important;">Estado Seguridad</th>
                    <th style="padding: 15px; color: white !important; text-align: right;">Monto ($)</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty($results) ) : ?>
                    <tr><td colspan="6" style="padding:30px; text-align:center; color:#999;">No se encontraron pagos con estos filtros.</td></tr>
                <?php else : ?>
                    <?php 
                    $total_sum = 0;
                    foreach ( $results as $r ) : 
                        $total_sum += floatval($r['monto']);
                    ?>
                        <tr>
                            <td style="padding: 15px;"><strong><?php echo $r['date']; ?></strong></td>
                            <td style="padding: 15px;"><?php echo esc_html($r['event']); ?></td>
                            <td style="padding: 15px;">
                                <div style="font-weight:bold; color:#2271b1;"><?php echo esc_html($r['provider']); ?></div>
                                <div style="font-size:11px; color:#666;"><?php echo esc_html($r['po']); ?></div>
                            </td>
                            <td style="padding: 15px; text-transform:uppercase; font-size:11px;"><?php echo esc_html($r['metodo']); ?></td>
                            <td style="padding: 15px;">
                                <?php if ( $r['forced'] ) : 
                                    $admin = get_userdata($r['forced']);
                                ?>
                                    <span style="background: #fff1f0; color: #d32f2f; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; border: 1px solid #ffa39e;">
                                        🛡️ FIRMA: <?php echo esc_html($admin->display_name); ?>
                                    </span>
                                <?php else : ?>
                                    <span style="background: #f6ffed; color: #52c41a; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: bold; border: 1px solid #b7eb8f;">
                                        ✅ ESTÁNDAR
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 15px; text-align: right; font-weight: 800; font-size: 15px; color: #155724;">
                                <?php echo wc_price($r['monto']); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="background: #f8f9fa;">
                    <th colspan="5" style="padding: 20px; text-align: right; font-size: 16px; text-transform: uppercase;">Total Desembolsado:</th>
                    <th style="padding: 20px; text-align: right; font-size: 20px; color: #d32f2f; font-weight: 900;"><?php echo wc_price($total_sum ?? 0); ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
}

/**
 * 2. REPORT: INGRESOS VS EGRESOS (Master Balance)
 */
function tbp_egresos_balance_global_page_content() {
    global $wpdb;

    $event_id = isset($_GET['f_event']) ? intval($_GET['f_event']) : 0;
    
    // 1. Get Events
    $events_args = array( 'post_type' => 'tribe_events', 'posts_per_page' => -1 );
    if ( $event_id ) $events_args['post__in'] = array($event_id);
    $events = get_posts( $events_args );

    $report_data = array();

    foreach ( $events as $ev ) {
        // --- INGRESOS LOGIC (WC + WCMP) ---
        $inc_proyectado = 0;
        $inc_pagado = 0;

        // Find products linked to this event
        $linked_products = $wpdb->get_col( $wpdb->prepare("
            SELECT post_id FROM {$wpdb->postmeta} 
            WHERE meta_key LIKE '%event%' AND meta_value = %d
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type='product')
        ", $ev->ID) );

        if ( ! empty($linked_products) ) {
            $p_ids_sql = implode(',', $linked_products);
            $orders_ids = $wpdb->get_col("
                SELECT DISTINCT order_id FROM {$wpdb->prefix}woocommerce_order_items 
                WHERE order_item_id IN (SELECT order_item_id FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE meta_key IN ('_product_id', '_variation_id') AND meta_value IN ($p_ids_sql))
            ");

            if ( ! empty($orders_ids) ) {
                foreach ( $orders_ids as $oid ) {
                    $order = wc_get_order($oid);
                    if ( ! $order || is_a($order, 'WC_Order_Refund') ) continue;
                    
                    $total = floatval($order->get_total());
                    $inc_proyectado += $total;

                    // Calculate paid (wcmp logic)
                    $wcmp_payments = 0;
                    if ( function_exists('wcmp_get_order_payments_total') ) {
                        $wcmp_payments = wcmp_get_order_payments_total($oid);
                    }
                    
                    if ( in_array($order->get_status(), array('processing', 'completed')) && $wcmp_payments <= 0 ) {
                        $inc_pagado += $total;
                    } else {
                        $inc_pagado += $wcmp_payments;
                    }
                }
            }
        }

        // --- EGRESOS LOGIC (TBP) ---
        $eg_proyectado = 0;
        $eg_autorizado = 0;
        $eg_pagado = 0;

        $pos = get_posts( array(
            'post_type' => 'tbp_orden_compra',
            'posts_per_page' => -1,
            'meta_query' => array( array( 'key' => '_tbp_event_id', 'value' => $ev->ID, 'compare' => '=' ) )
        ) );

        foreach ( $pos as $po ) {
            $eg_proyectado += floatval( get_post_meta($po->ID, '_tbp_cantidad_proyectada', true) );
            $eg_autorizado += floatval( get_post_meta($po->ID, '_tbp_cantidad_autorizada', true) );
            $eg_pagado     += floatval( get_post_meta($po->ID, '_tbp_total_pagado', true) );
        }

        $report_data[] = array(
            'event' => $ev->post_title,
            'inc_proj' => $inc_proyectado,
            'inc_paid' => $inc_pagado,
            'inc_saldo' => $inc_proyectado - $inc_pagado,
            'eg_proj' => $eg_proyectado,
            'eg_auth' => $eg_autorizado,
            'eg_paid' => $eg_pagado,
            'eg_saldo' => $eg_autorizado - $eg_pagado,
            'margin' => $inc_pagado - $eg_autorizado
        );
    }

    ?>
    <div class="wrap" style="background: #f4f7f9; padding: 25px; border-radius: 12px; font-family: 'Inter', sans-serif;">
        <h1 style="color: #2c3e50; font-weight: 900; letter-spacing: -1px; margin-bottom: 30px; font-size: 28px;">💎 Balance Maestro: Ingresos vs Egresos</h1>

        <?php if ( $event_id ) : ?>
            <div style="margin-bottom: 20px;">
                <a href="<?php echo admin_url('admin.php?page=tbp-egresos-balance-global'); ?>" class="button button-secondary">⬅️ Ver Todos los Eventos</a>
            </div>
        <?php endif; ?>

        <div style="display:grid; grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); gap: 30px;">
            <?php foreach ( $report_data as $data ) : ?>
                <div style="background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,0.06); overflow: hidden; border: 1px solid #eef2f6;">
                    <div style="background: #2c3e50; padding: 20px; color: #fff; display: flex; justify-content: space-between; align-items: center;">
                        <h2 style="margin:0; font-size: 20px; color: #fff;"><?php echo esc_html($data['event']); ?></h2>
                        <div style="background: rgba(255,255,255,0.1); padding: 8px 15px; border-radius: 8px; font-weight: bold; font-size: 14px;">
                            MARGEN ACTUAL: <?php echo wc_price($data['margin']); ?>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1.5fr; gap: 2px; background: #eee;">
                        
                        <!-- INGRESOS COLUMN -->
                        <div style="background: #fff; padding: 25px;">
                            <h3 style="color: #27ae60; text-transform: uppercase; font-size: 12px; margin-top: 0; letter-spacing: 1px;">💰 Ingresos (Recaudación)</h3>
                            <div style="margin-top: 15px;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                    <span style="color:#666; font-size:13px;">Proyectado (Ventas):</span>
                                    <span style="font-weight:bold;"><?php echo wc_price($data['inc_proj']); ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                                    <span style="color:#666; font-size:13px;">Pagado (Ingreso Real):</span>
                                    <span style="font-weight:bold; color:#27ae60; font-size: 16px;"><?php echo wc_price($data['inc_paid']); ?></span>
                                </div>
                                <div style="display:flex; justify-content:space-between; padding-top:10px; border-top: 1px solid #f0f0f0;">
                                    <span style="color:#d32f2f; font-size:13px; font-weight:bold;">Saldo por Cobrar:</span>
                                    <span style="font-weight:bold; color:#d32f2f;"><?php echo wc_price($data['inc_saldo']); ?></span>
                                </div>
                            </div>
                        </div>

                        <!-- EGRESOS COLUMN -->
                        <div style="background: #fff; padding: 25px; border-left: 1px solid #eee;">
                            <h3 style="color: #e67e22; text-transform: uppercase; font-size: 12px; margin-top: 0; letter-spacing: 1px;">💸 Egresos (Dispersión)</h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                                <div>
                                    <div style="color:#666; font-size:11px; text-transform:uppercase; margin-bottom:5px;">Proyectado</div>
                                    <div style="font-weight:bold; font-size:15px;"><?php echo wc_price($data['eg_proj']); ?></div>
                                </div>
                                <div>
                                    <div style="color:#666; font-size:11px; text-transform:uppercase; margin-bottom:5px;">Autorizado</div>
                                    <div style="font-weight:bold; font-size:15px; color:#2271b1;"><?php echo wc_price($data['eg_auth']); ?></div>
                                </div>
                                <div style="grid-column: span 2; background: #fffcf0; padding: 15px; border-radius: 10px; border: 1px solid #ffeeb4; margin-top: 10px;">
                                    <div style="display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <div style="color:#856404; font-size:11px; font-weight:bold; text-transform:uppercase;">Egresado Real (Salida)</div>
                                            <div style="font-weight:900; font-size:22px; color: #d32f2f;"><?php echo wc_price($data['eg_paid']); ?></div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="color:#666; font-size:11px; text-transform:uppercase;">Saldo Pendiente OC</div>
                                            <div style="font-weight:bold; font-size:16px;"><?php echo wc_price($data['eg_saldo']); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    
                    <!-- FOOTER CALL TO ACTION -->
                    <div style="background: #f8f9fa; padding: 12px 20px; border-top: 1px solid #eee; text-align: right;">
                        <a href="<?php echo admin_url('admin.php?page=tbp-egresos-pagos-historial&f_event='.$ev->ID); ?>" style="font-size: 12px; color: #2271b1; text-decoration: none; font-weight: bold;">🔍 Ver Detalle de Pagos →</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php
}
