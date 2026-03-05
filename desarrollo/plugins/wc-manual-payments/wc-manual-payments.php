<?php
/**
 * Plugin Name: WooCommerce Manual Payments Tracking
 * Description: Permite registrar abonos manuales en pedidos de WooCommerce, controlando el saldo y actualizando el estado automáticamente.
 * Version: 1.1.1
 * Author: Antigravity
 * Text Domain: wc-manual-payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include required files
require_once plugin_dir_path( __FILE__ ) . 'includes/order-logic.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/admin-order-metabox.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/frontend-display.php';

/**
 * Register Custom Order Status: Parcialmente Pagado
 */
function wcmp_register_partially_paid_status() {
    register_post_status( 'wc-p-pagado', array(
        'label'                     => _x( 'Pagado Parcialmente', 'Order status', 'wc-manual-payments' ),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop( 'Pagado Parcialmente <span class="count">(%s)</span>', 'Pagado Parcialmente <span class="count">(%s)</span>', 'wc-manual-payments' )
    ) );
}
add_action( 'init', 'wcmp_register_partially_paid_status' );

/**
 * Add custom status to WooCommerce
 */
function wcmp_add_partially_paid_to_order_statuses( $order_statuses ) {
    $new_order_statuses = array();

    foreach ( $order_statuses as $key => $status ) {
        $new_order_statuses[ $key ] = $status;
        if ( 'wc-on-hold' === $key ) {
            $new_order_statuses['wc-p-pagado'] = _x( 'Pagado Parcialmente', 'Order status', 'wc-manual-payments' );
        }
    }

    return $new_order_statuses;
}
add_filter( 'wc_order_statuses', 'wcmp_add_partially_paid_to_order_statuses' );
