<?php
/**
 * Local Raffle Handler for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hook into WooCommerce Order Status Change
 */
add_action( 'woocommerce_order_status_completed', 'tbp_actividades_handle_local_raffle_sale', 10, 1 );
add_action( 'woocommerce_order_status_processing', 'tbp_actividades_handle_local_raffle_sale', 10, 1 );

function tbp_actividades_handle_local_raffle_sale( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Avoid duplicate processing
    if ( $order->get_meta( '_tbp_raffle_processed' ) ) {
        return;
    }

    $items = $order->get_items();
    foreach ( $items as $item ) {
        $product_id = $item->get_product_id();
        
        // Find if this product is linked to any Rifa as a local event
        // We first need to find if the product IS a ticket for a specific event
        $event_id = get_post_meta( $product_id, '_tribe_tickets_for_event', true );
        
        if ( ! $event_id ) continue;

        // Search for a Rifa CPT that has this event_id as _tbp_local_raffle_id
        $rifas = get_posts( array(
            'post_type' => 'tbp_rifas',
            'meta_key' => '_tbp_local_raffle_id',
            'meta_value' => $event_id,
            'posts_per_page' => 1
        ) );

        if ( ! empty( $rifas ) ) {
            $rifa_id = $rifas[0]->ID;
            $credit_per_ticket = floatval( get_post_meta( $rifa_id, '_tbp_cost_virtual', true ) );
            $quantity = $item->get_quantity();
            $total_credit = $credit_per_ticket * $quantity;

            // Try to find the Student Order ID in the order metadata
            // We search for common keys used in the checkout
            $student_order_id = tbp_actividades_extract_student_order_id( $order );

            if ( $student_order_id && function_exists( 'wcmp_add_order_payment' ) ) {
                $note = sprintf( 
                    __( "Abono por Rifa Local (Pedido Rifa: #%s, Qty: %d).", 'tbp-actividades' ),
                    $order_id,
                    $quantity
                );

                $success = wcmp_add_order_payment( $student_order_id, $total_credit, $note, null, 'LOCAL_RAFFLE_' . $order_id );
                
                if ( $success ) {
                    $order->update_meta_data( '_tbp_raffle_processed', '1' );
                    $order->save();
                    
                    // Notify student
                    tbp_actividades_add_custom_order_note( $student_order_id, $quantity, 'virtual' );
                }
            }
        }
    }
}

/**
 * Extract Student Order ID from Order Meta
 */
function tbp_actividades_extract_student_order_id( $order ) {
    // List of possible meta keys where the "Pedido del Alumno" might be stored
    $possible_keys = array(
        '_billing_pedido', 
        'billing_pedido', 
        'pedido_del_alumno', 
        '_pedido_del_alumno',
        'numero_de_pedido',
        '_numero_de_pedido'
    );

    foreach ( $possible_keys as $key ) {
        $val = $order->get_meta( $key );
        if ( ! empty( $val ) ) {
            return preg_replace( '/[^0-9]/', '', $val );
        }
    }

    // Also check attendee meta if Event Tickets Plus is used
    // (This part might need more specific logic depending on how the field is registered)
    
    return false;
}
