<?php
/**
 * Order Logic for Manual Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Get total payments for an order
 */
function wcmp_get_order_payments_total( $order_id ) {
    $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
    if ( ! is_array( $payments ) ) {
        return 0;
    }

    $total = 0;
    foreach ( $payments as $payment ) {
        $total += (float) $payment['amount'];
    }

    return $total;
}

/**
 * Update order status based on payments
 */
function wcmp_update_order_status_by_balance( $order_id, $total_paid = null ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    $total_order = (float) $order->get_total();
    if ( is_null( $total_paid ) ) {
        $total_paid = wcmp_get_order_payments_total( $order_id );
    }
    $current_status = $order->get_status();

    if ( $total_paid <= 0 ) {
        return;
    }

    if ( $total_paid >= $total_order ) {
        if ( 'completed' !== $current_status ) {
            $order->update_status( 'completed', __( 'Pago total recibido (Manual).', 'wc-manual-payments' ) );
        }
    } elseif ( $total_paid > 0 ) {
        // Use the custom status slug: p-pagado
        if ( 'p-pagado' !== $current_status ) {
            $order->update_status( 'p-pagado', __( 'Pago parcial recibido (Manual).', 'wc-manual-payments' ) );
        }
    }
}

/**
 * Notify customer about payment
 */
function wcmp_notify_customer_payment( $order_id, $payment_data ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    $date = $payment_data['date'];
    $note = $payment_data['note'];
    $amount_formatted = wc_price( $payment_data['amount'] );
    
    // Build the message requested by user
    $message = "Estimado Usuario, te informamos que hemos recibido y acreditado un pago con los siguientes datos:\n";
    $message .= "{$date} {$note} {$amount_formatted}\n\n";
    $message .= "Tu pedido cambiará de estatus según los pagos registrados.\n";
    $message .= "Estos son los estatus:\n";
    $message .= "*En Espera: El pedido se realizó con éxito pero no hemos recibido su pago.\n";
    $message .= "*Parcialmente pagado: cuando recibimos un pago parcial de su pedido.\n";
    $message .= "*Completado: Cuando hemos recibido el monto total de su pedido.\n\n";
    $message .= "Los pagos que cubren el monto total del valor de tu pedido hechos con tarjeta, son acreditados automáticamente y su estatus es Pagado con Tarjeta ó completado.\n";
    $message .= "Para ver detalles de tu pedido ingresa a\n";
    $message .= "https://thebestprom.com/mi-cuenta\n\n";
    $message .= "Nota: En tus próximos pagos No Olvides poner tu número de pedido:\n";
    $message .= "EJEMPLO:(P-{$order->get_order_number()})\n\n";
    $message .= "Atte. Equipo The Best Prom .";

    // Add customer note
    $order->add_order_note( $message, 1 ); // 1 = is_customer_note
}
