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
function wcmp_add_reports_menu() {
    add_submenu_page(
        'woocommerce',
        __( 'Reportes de Conciliación', 'wc-manual-payments' ),
        __( 'Conciliación Pagos', 'wc-manual-payments' ),
        'manage_woocommerce',
        'wcmp-reports',
        'wcmp_render_reports_page'
    );
}
add_action( 'admin_menu', 'wcmp_add_reports_menu', 60 );

/**
 * Render Reports Page
 */
function wcmp_render_reports_page() {
    // 1. Get All Orders with Payments
    $args = array(
        'limit' => -1,
        'return' => 'ids',
        'meta_key' => '_wcmp_payments_history',
        'meta_compare' => 'EXISTS',
    );
    $order_ids = wc_get_orders( $args );
    
    $all_payments = array();
    
    foreach ( $order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        $history = get_post_meta( $order_id, '_wcmp_payments_history', true ) ?: array();
        
        foreach ( $history as $payment ) {
            $payment['order_id'] = $order_id;
            $payment['customer'] = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
            $all_payments[] = $payment;
        }
    }

    // 2. Sort by Date Descending
    usort( $all_payments, function($a, $b) {
        return strtotime($b['date']) - strtotime($a['date']);
    });

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php _e( 'Conciliación de Pagos (Tarjeta y Manual)', 'wc-manual-payments' ); ?></h1>
        <hr class="wp-header-end">

        <h2 class="title"><?php _e( 'Historial Consolidado', 'wc-manual-payments' ); ?></h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 120px;"><?php _e( 'Fecha', 'wc-manual-payments' ); ?></th>
                    <th style="width: 100px;"><?php _e( 'Pedido', 'wc-manual-payments' ); ?></th>
                    <th><?php _e( 'Cliente', 'wc-manual-payments' ); ?></th>
                    <th><?php _e( 'Concepto / Nota', 'wc-manual-payments' ); ?></th>
                    <th style="width: 150px;"><?php _e( 'Método', 'wc-manual-payments' ); ?></th>
                    <th style="width: 120px;"><?php _e( 'Abono', 'wc-manual-payments' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $all_payments ) ) : ?>
                    <tr><td colspan="6"><?php _e( 'No hay pagos registrados aún.', 'wc-manual-payments' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $all_payments as $p ) : ?>
                        <?php 
                            $is_stripe = (stripos($p['note'], '[STRIPE]') !== false);
                            $method = $is_stripe ? '<span class="dashicons dashicons-cart" style="color:#6772e5;"></span> Stripe' : '<span class="dashicons dashicons-bank" style="color:#d9534f;"></span> Manual/Banco';
                        ?>
                        <tr>
                            <td><?php echo esc_html( $p['date'] ); ?></td>
                            <td><a href="<?php echo get_edit_post_link( $p['order_id'] ); ?>"><strong>#<?php echo $p['order_id']; ?></strong></a></td>
                            <td><?php echo esc_html( $p['customer'] ); ?></td>
                            <td><?php echo esc_html( $p['note'] ); ?></td>
                            <td><?php echo $method; ?></td>
                            <td style="font-weight: bold; color: green;"><?php echo wc_price( $p['amount'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Orchard (Orphan) Payments Section -->
        <?php $orphans = get_option('wcmp_orphan_payments', array()); ?>
        <?php if ( ! empty( $orphans ) ) : ?>
            <div style="margin-top: 40px; background: #fff; border-left: 4px solid #f0ad4e; padding: 20px; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                <h2 style="color: #8a6d3b;"><span class="dashicons dashicons-warning" style="margin-top:4px;"></span> <?php _e( 'Pagos Huérfanos (Requieren Revisión)', 'wc-manual-payments' ); ?></h2>
                <p class="description"><?php _e( 'Estos pagos fueron recibidos en Stripe o Sheets pero el cliente no ingresó un número de pedido válido o coherente.', 'wc-manual-payments' ); ?></p>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e( 'Origen', 'wc-manual-payments' ); ?></th>
                            <th><?php _e( 'Lo que puso el cliente', 'wc-manual-payments' ); ?></th>
                            <th><?php _e( 'Monto', 'wc-manual-payments' ); ?></th>
                            <th><?php _e( 'Referencia', 'wc-manual-payments' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( array_reverse($orphans) as $o ) : ?>
                            <tr>
                                <td><strong><?php echo esc_html($o['source']); ?></strong></td>
                                <td style="color: #d9534f; font-weight: bold;"><?php echo esc_html($o['input']); ?></td>
                                <td><?php echo wc_price($o['amount']); ?></td>
                                <td><?php echo esc_html($o['ref'] ?? ''); ?> (<?php echo esc_html($o['name'] ?? ''); ?>)</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php
}
