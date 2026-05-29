<?php
/**
 * Admin Reports for Manual and Automated Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Add Menu Item to WooCommerce
 */
if ( ! function_exists( 'wcmp_add_reports_menu' ) ) {
    function wcmp_add_reports_menu() {
        add_menu_page(
            __( 'Conciliación Pagos', 'wc-manual-payments' ),
            __( 'Conciliación Pagos', 'wc-manual-payments' ),
            'manage_woocommerce',
            'wcmp-reports',
            'wcmp_render_reports_page',
            'dashicons-chart-bar',
            58
        );
    }
}
add_action( 'admin_menu', 'wcmp_add_reports_menu', 99 );

/**
 * Render Reports Page
 */
if ( ! function_exists( 'wcmp_render_reports_page' ) ) {
    function wcmp_render_reports_page() {
        // 0. Handle Actions
        if ( isset( $_GET['action'] ) ) {
            if ( $_GET['action'] === 'clear_orphans' ) {
                check_admin_referer( 'wcmp_clear_orphans' );
                
                $orphans = get_option( 'wcmp_orphan_payments', array() );
                $cleared_history = get_option( 'wcmp_cleared_orphans_history', array() );
                if ( ! is_array($cleared_history) ) $cleared_history = array();

                // Mover todos los huérfanos actuales a la lista de bloqueados antes de borrar
                foreach ( $orphans as $o ) {
                    $o_id = $o['orphan_id'] ?? md5( ($o['source'] ?? '') . '|' . ($o['amount'] ?? 0) . '|' . ($o['input'] ?? '') . '|' . ($o['ref'] ?? '') );
                    $cleared_history[$o_id] = current_time('mysql');
                }

                // Mantener la lista de historial manejable (últimos 1000)
                if ( count($cleared_history) > 1000 ) {
                    $cleared_history = array_slice($cleared_history, -1000, 1000, true);
                }

                update_option( 'wcmp_cleared_orphans_history', $cleared_history );
                update_option( 'wcmp_orphan_payments', array() );
                
                wp_redirect( admin_url( 'admin.php?page=wcmp-reports&cleared=1' ) );
                exit;
            }

            if ( $_GET['action'] === 'link_orphan' && isset($_POST['target_order_id']) ) {
                check_admin_referer( 'wcmp_link_orphan' );
                $index = (int) $_GET['index'];
                $order_id = (int) $_POST['target_order_id'];
                
                // Antes de borrar, obtener los datos para marcarlos como "gestionados"
                $orphans = get_option( 'wcmp_orphan_payments', array() );
                if ( isset($orphans[$index]) ) {
                    $o = $orphans[$index];
                    $o_id = $o['orphan_id'] ?? md5( ($o['source'] ?? '') . '|' . ($o['amount'] ?? 0) . '|' . ($o['input'] ?? '') . '|' . ($o['ref'] ?? '') );
                    $cleared_history = get_option( 'wcmp_cleared_orphans_history', array() );
                    if ( ! is_array($cleared_history) ) $cleared_history = array();
                    $cleared_history[$o_id] = current_time('mysql');
                    update_option( 'wcmp_cleared_orphans_history', $cleared_history );
                }

                if ( wcmp_link_orphan_payment( $index, $order_id ) ) {
                    wp_redirect( admin_url( 'admin.php?page=wcmp-reports&linked=1' ) );
                    exit;
                } else {
                    echo '<div class="error"><p>Error: ID de pedido inválido o no encontrado.</p></div>';
                }
            }
            if ( $_GET['action'] === 'sync_all_statuses' ) {
                check_admin_referer( 'wcmp_sync_all' );
                
                $args = array(
                    'limit' => -1,
                    'return' => 'ids',
                    'meta_key' => '_wcmp_payments_history',
                    'meta_compare' => 'EXISTS',
                );
                $order_ids = wc_get_orders( $args );
                $count = 0;

                foreach ( $order_ids as $id ) {
                    wcmp_update_order_status_by_balance( $id, null, true );
                    $count++;
                }

                wp_redirect( admin_url( 'admin.php?page=wcmp-reports&synced=' . $count ) );
                exit;
            }
        }

        if ( isset($_GET['synced']) ) echo '<div class="updated"><p>Se han sincronizado ' . (int)$_GET['synced'] . ' pedidos correctamente.</p></div>';
        if ( isset($_GET['linked']) ) echo '<div class="updated"><p>Pago vinculado con éxito.</p></div>';
        if ( isset($_GET['cleared']) ) echo '<div class="updated"><p>Lista de huérfanos limpiada.</p></div>';

        // 1. Get Params
        $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
        $date_from = isset($_GET['date_from']) ? sanitize_text_field($_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field($_GET['date_to']) : '';
        $orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'date';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'desc';

        // 2. Get All Orders with Payments - OPTIMIZATION v1.8.14
        $is_filtered = ( ! empty($search) || ! empty($date_from) || ! empty($date_to) );
        $query_limit = $is_filtered ? -1 : 50;

        $args = array(
            'limit' => $query_limit,
            'return' => 'ids',
            'meta_key' => '_wcmp_payments_history',
            'meta_compare' => 'EXISTS',
            'orderby' => 'date',
            'order' => 'DESC'
        );
        $order_ids = wc_get_orders( $args );
        
        $all_payments = array();
        
        foreach ( $order_ids as $order_id ) {
            $order_o = wc_get_order( $order_id );
            if ( ! $order_o ) continue;

            $history = get_post_meta( $order_id, '_wcmp_payments_history', true ) ?: array();
            $customer_name = $order_o->get_billing_first_name() . ' ' . $order_o->get_billing_last_name();
            
            foreach ( $history as $payment ) {
                $payment['order_id'] = $order_id;
                $payment['customer'] = $customer_name;
                
                // --- FILTERS ---
                // Search Filter
                if ( ! empty($search) ) {
                    $haystack = strtolower($payment['order_id'] . ' ' . $payment['customer'] . ' ' . $payment['note']);
                    if ( strpos($haystack, strtolower($search)) === false ) continue;
                }

                // Date Filters
                if ( ! empty($date_from) && $payment['date'] < $date_from ) continue;
                if ( ! empty($date_to) && $payment['date'] > $date_to ) continue;

                $all_payments[] = $payment;
            }
        }

        // 3. Sorting Logic
        usort( $all_payments, function($a, $b) use ($orderby, $order) {
            $val_a = isset($a[$orderby]) ? $a[$orderby] : '';
            $val_b = isset($b[$orderby]) ? $b[$orderby] : '';

            if ( $orderby === 'date' ) {
                $comp = strtotime($val_a) - strtotime($val_b);
            } elseif ( $orderby === 'amount' || $orderby === 'order_id' ) {
                $comp = (float)$val_a - (float)$val_b;
            } else {
                $comp = strcasecmp($val_a, $val_b);
            }

            return ($order === 'asc') ? $comp : -$comp;
        });

        ?>
        <div class="wrap wcmp-reports-wrap">
            <style>
                .wcmp-filter-bar { background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: flex-end; gap: 15px; flex-wrap: wrap; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
                .wcmp-filter-group { display: flex; flex-direction: column; gap: 5px; }
                .wcmp-filter-group label { font-weight: 600; font-size: 12px; color: #50575e; }
                .wcmp-filter-group input { height: 32px !important; }
                .wcmp-sort-link { text-decoration: none; color: #2c3338; display: flex; align-items: center; gap: 4px; }
                .wcmp-sort-link:hover { color: #2271b1; }
                .wcmp-active-sort { color: #2271b1; font-weight: bold; }
            </style>

            <h1 class="wp-heading-inline"><?php _e( 'Conciliación de Pagos (Tarjeta y Manual)', 'wc-manual-payments' ); ?></h1>
            <a href="<?php echo wp_nonce_url( admin_url('admin.php?page=wcmp-reports&action=sync_all_statuses'), 'wcmp_sync_all' ); ?>" class="page-title-action">
                <span class="dashicons dashicons-update" style="font-size: 16px; margin-top:2px;"></span> <?php _e( 'Sincronizar todos los estados', 'wc-manual-payments' ); ?>
            </a>
            <hr class="wp-header-end">

            <!-- Diagnostic Section -->
            <?php $last_signal = get_option('wcmp_last_webhook_signal'); ?>
            <div style="background: #f0f6fb; border-left: 4px solid #007cba; padding: 12px 15px; margin: 20px 0; display: flex; align-items: center; gap: 20px;">
                <div style="flex-grow: 1;">
                    <p style="margin: 0; font-size: 13px;">
                        <strong><span class="dashicons dashicons-rss" style="font-size: 16px; margin-top:2px;"></span> <?php _e( 'Estado Webhook:', 'wc-manual-payments' ); ?></strong>
                        <?php if ( $last_signal ) : ?>
                            <?php echo esc_html( $last_signal['time'] ); ?> | 
                            <?php echo esc_html( $last_signal['source'] ); ?> : 
                            <span style="color: <?php echo strpos($last_signal['status'], 'Success') !== false ? '#2271b1' : '#d63638'; ?>; font-weight: bold;">
                                <?php echo esc_html( $last_signal['status'] ); ?>
                            </span>
                        <?php else : ?>
                            <?php _e( 'Sin actividad reciente.', 'wc-manual-payments' ); ?>
                        <?php endif; ?>
                    </p>
                </div>
                <div style="font-size: 11px; opacity: 0.7;">
                    URL: <code><?php echo home_url('/wp-json/wcmp/v1/webhook'); ?></code>
                </div>
            </div>

            <h2 class="title" style="margin-bottom: 5px;"><?php _e( 'Historial Consolidado', 'wc-manual-payments' ); ?></h2>
            <?php if ( ! $is_filtered ) : ?>
                <p class="description" style="margin-bottom: 15px; background: #fffbe5; padding: 5px 10px; border-left: 4px solid #ffb900;">
                    <span class="dashicons dashicons-info" style="font-size: 16px; margin-top:2px;"></span> 
                    <?php _e( 'Mostrando los últimos 50 pedidos con pagos. Usa los filtros para buscar rangos mayores.', 'wc-manual-payments' ); ?>
                </p>
            <?php endif; ?>
            
            <!-- Filter Bar -->
            <form method="get" class="wcmp-filter-bar">
                <input type="hidden" name="page" value="wcmp-reports">
                
                <div class="wcmp-filter-group">
                    <label><?php _e( 'Buscar:', 'wc-manual-payments' ); ?></label>
                    <input type="text" name="s" value="<?php echo esc_attr($search); ?>" placeholder="ID, nombre, nota..." style="width: 250px;">
                </div>

                <div class="wcmp-filter-group">
                    <label><?php _e( 'Desde:', 'wc-manual-payments' ); ?></label>
                    <input type="date" name="date_from" value="<?php echo esc_attr($date_from); ?>">
                </div>

                <div class="wcmp-filter-group">
                    <label><?php _e( 'Hasta:', 'wc-manual-payments' ); ?></label>
                    <input type="date" name="date_to" value="<?php echo esc_attr($date_to); ?>">
                </div>

                <button type="submit" class="button button-primary" style="height: 32px;"><?php _e( 'Filtrar', 'wc-manual-payments' ); ?></button>
                <a href="<?php echo admin_url('admin.php?page=wcmp-reports'); ?>" class="button" style="height: 32px; line-height: 30px;"><?php _e( 'Limpiar', 'wc-manual-payments' ); ?></a>
            </form>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php 
                            $sort_order = ($order === 'asc' && $orderby === 'date') ? 'desc' : 'asc';
                            $date_arr = ($orderby === 'date') ? ($order === 'asc' ? '▴' : '▾') : '';
                        ?>
                        <th style="width: 120px;">
                            <a href="<?php echo add_query_arg(array('orderby' => 'date', 'order' => $sort_order)); ?>" class="wcmp-sort-link <?php if($orderby === 'date') echo 'wcmp-active-sort'; ?>">
                                <?php _e( 'Fecha', 'wc-manual-payments' ); ?> <?php echo $date_arr; ?>
                            </a>
                        </th>
                        
                        <?php 
                            $sort_id = ($order === 'asc' && $orderby === 'order_id') ? 'desc' : 'asc';
                            $id_arr = ($orderby === 'order_id') ? ($order === 'asc' ? '▴' : '▾') : '';
                        ?>
                        <th style="width: 100px;">
                            <a href="<?php echo add_query_arg(array('orderby' => 'order_id', 'order' => $sort_id)); ?>" class="wcmp-sort-link <?php if($orderby === 'order_id') echo 'wcmp-active-sort'; ?>">
                                <?php _e( 'Pedido', 'wc-manual-payments' ); ?> <?php echo $id_arr; ?>
                            </a>
                        </th>

                        <th><?php _e( 'Cliente', 'wc-manual-payments' ); ?></th>
                        <th><?php _e( 'Concepto / Nota', 'wc-manual-payments' ); ?></th>
                        <th style="width: 150px;"><?php _e( 'Método', 'wc-manual-payments' ); ?></th>
                        <th style="width: 120px; text-align: right;"><?php _e( 'Abono', 'wc-manual-payments' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $all_payments ) ) : ?>
                        <tr><td colspan="6" style="padding: 20px; text-align: center; color: #666; font-style: italic;"><?php _e( 'No se encontraron pagos con esos filtros.', 'wc-manual-payments' ); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ( $all_payments as $p ) : ?>
                            <?php 
                                // Detección mejorada para v1.8.5: Soportar "Stripe", "tarjeta" o flag source
                                $is_stripe = (stripos($p['note'], 'Stripe') !== false || stripos($p['note'], 'tarjeta') !== false || (isset($p['source']) && $p['source'] === 'stripe'));
                                $method = $is_stripe ? '<span class="dashicons dashicons-cart" style="color:#6772e5; font-size:16px;"></span> Stripe' : '<span class="dashicons dashicons-bank" style="color:#d9534f; font-size:16px;"></span> Manual';
                            ?>
                            <tr>
                                <td><?php echo esc_html( $p['date'] ); ?></td>
                                <td><a href="<?php echo get_edit_post_link( $p['order_id'] ); ?>"><strong>#<?php echo $p['order_id']; ?></strong></a></td>
                                <td><?php echo esc_html( $p['customer'] ); ?></td>
                                <td style="font-size: 12px;"><?php echo esc_html( $p['note'] ); ?></td>
                                <td><?php echo $method; ?></td>
                                <td style="font-weight: bold; color: #2271b1; text-align: right; font-family: monospace; font-size: 14px;"><?php echo wc_price( $p['amount'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Orchard (Orphan) Payments Section -->
            <?php 
            $orphans = get_option('wcmp_orphan_payments', array()); 
            if ( ! is_array( $orphans ) ) {
                $orphans = array();
            }
            if ( ! empty( $orphans ) ) : ?>
                <div style="margin-top: 40px; background: #fff; border-left: 4px solid #f0ad4e; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <h2 style="color: #8a6d3b; margin: 0;"><span class="dashicons dashicons-warning" style="margin-top:4px;"></span> <?php _e( 'Pagos Huérfanos (Requieren Revisión)', 'wc-manual-payments' ); ?></h2>
                        <a href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wcmp-reports&action=clear_orphans' ), 'wcmp_clear_orphans' ); ?>" class="button" onclick="return confirm('¿Seguro que quieres borrar estos registros?');"><?php _e( 'Limpiar Todo', 'wc-manual-payments' ); ?></a>
                    </div>
                    <p class="description"><?php _e( 'Estos pagos fueron recibidos en Stripe o Sheets pero el cliente no ingresó un número de pedido válido o coherente.', 'wc-manual-payments' ); ?></p>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th><?php _e( 'Origen', 'wc-manual-payments' ); ?></th>
                                <th><?php _e( 'Lo que puso el cliente', 'wc-manual-payments' ); ?></th>
                                <th><?php _e( 'Monto', 'wc-manual-payments' ); ?></th>
                                <th><?php _e( 'Referencia', 'wc-manual-payments' ); ?></th>
                                <th style="width: 250px;"><?php _e( 'Acción', 'wc-manual-payments' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $orphans_list = array_values($orphans); // Ensure sequential keys
                            $orphans_reversed = array_reverse($orphans_list, true);
                            foreach ( $orphans_reversed as $index => $o ) : 
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($o['source']); ?></strong></td>
                                    <td style="color: #d9534f; font-weight: bold;"><?php echo esc_html($o['input']); ?></td>
                                    <td><?php echo wc_price($o['amount']); ?></td>
                                    <td><?php echo esc_html($o['ref'] ?? ''); ?> (<?php echo esc_html($o['name'] ?? ''); ?>)</td>
                                    <td>
                                        <form method="post" action="<?php echo wp_nonce_url( admin_url( 'admin.php?page=wcmp-reports&action=link_orphan&index=' . $index ), 'wcmp_link_orphan' ); ?>" style="display: flex; gap: 5px;">
                                            <input type="number" name="target_order_id" placeholder="ID Pedido" style="width: 100px; height: 30px;" required>
                                            <button type="submit" class="button button-small" onclick="return confirm('¿Vincular este pago al pedido?');"><?php _e( 'Vincular', 'wc-manual-payments' ); ?></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }
}
