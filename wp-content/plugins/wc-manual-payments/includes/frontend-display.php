<?php
/**
 * Frontend Display for Manual Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Display balance and payments in "My Account > View Order"
 */
function wcmp_display_order_balance_frontend( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $payments = get_post_meta( $order_id, '_wcmp_payments_history', true ) ?: array();
    $total_order = (float) $order->get_total();
    $total_paid = wcmp_get_order_payments_total( $order_id );
    $balance = $total_order - $total_paid;

    if ( empty( $payments ) ) return; // Don't show if no manual payments exist
    ?>
    <section class="woocommerce-customer-details wcmp-payments-section" style="margin-top: 2em; padding: 20px; border: 1px solid #e5e5e5; border-radius: 5px;">
        <h2 class="woocommerce-column__title"><?php _e( 'Historial de Pagos y Saldo', 'wc-manual-payments' ); ?></h2>
        
        <table class="woocommerce-table woocommerce-table--order-details shop_table order_details">
            <thead>
                <tr>
                    <th class="woocommerce-table__product-name product-name"><?php _e( 'Fecha', 'wc-manual-payments' ); ?></th>
                    <th class="woocommerce-table__product-total product-total"><?php _e( 'Concepto', 'wc-manual-payments' ); ?></th>
                    <th class="woocommerce-table__product-total product-total"><?php _e( 'Abono', 'wc-manual-payments' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $payments as $payment ) : ?>
                    <tr>
                        <td><?php echo esc_html( $payment['date'] ); ?></td>
                        <td><?php echo esc_html( $payment['note'] ); ?></td>
                        <td><?php echo wc_price( $payment['amount'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th colspan="2" scope="row"><?php _e( 'Total Pagado:', 'wc-manual-payments' ); ?></th>
                    <td><?php echo wc_price( $total_paid ); ?></td>
                </tr>
                <tr>
                    <th colspan="2" scope="row"><?php _e( 'Saldo Pendiente:', 'wc-manual-payments' ); ?></th>
                    <td style="font-weight: bold; color: <?php echo $balance > 0 ? '#d9534f' : '#5cb85c'; ?>;">
                        <?php echo wc_price( $balance ); ?>
                    </td>
                </tr>
            </tfoot>
        </table>
    </section>
    <?php
}
add_action( 'woocommerce_view_order', 'wcmp_display_order_balance_frontend', 20 );

/**
 * Optional: Inject balance in the order list (My Account > Orders)
 */
function wcmp_display_balance_in_orders_list( $order ) {
    $total_order = (float) $order->get_total();
    $total_paid = wcmp_get_order_payments_total( $order->get_id() );
    $balance = $total_order - $total_paid;

    if ( $total_paid > 0 ) {
        echo '<br><small style="color: #666;">' . sprintf( __( 'Pagado: %s', 'wc-manual-payments' ), wc_price( $total_paid ) ) . '</small>';
        if ( $balance > 0 ) {
            echo '<br><small style="color: #d9534f;">' . sprintf( __( 'Saldo: %s', 'wc-manual-payments' ), wc_price( $balance ) ) . '</small>';
        }
    }
}
add_action( 'woocommerce_my_account_my_orders_column_order-total', 'wcmp_display_balance_in_orders_list' );
