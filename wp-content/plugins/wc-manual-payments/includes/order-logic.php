<?php
/**
 * Order Logic for Manual Payments
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Central function to add a payment to an order
 */
if ( ! function_exists( 'wcmp_add_order_payment' ) ) {
    /**
     * @param string $source  'admin' (metabox), 'stripe', 'sheets'. Admin no es bloqueado.
     * @return true|'blocked'|false
     */
    function wcmp_add_order_payment( $order_id, $amount, $note, $date = null, $transaction_id = '', $source = 'admin', $receipt_url = '' ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return false;

        // PROTECCIÓN v1.5.9: Bloquear pagos automáticos a pedidos ya pagados/procesando
        $protected_statuses = array( 'processing', 'completed' );
        if ( $source !== 'admin' && in_array( $order->get_status(), $protected_statuses ) ) {
            return 'blocked'; // Será enviado a huérfanos por el caller
        }

        if ( ! $date ) {
            $date = current_time('Y-m-d');
        }

        $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
        if ( ! is_array( $payments ) ) {
            $payments = array();
        }
        
        // 1. DUPLICATE CHECK: Verificamos si este Transaction ID ya existe en el historial
        if ( ! empty( $transaction_id ) ) {
            foreach ( $payments as $p ) {
                if ( isset( $p['transaction_id'] ) && $p['transaction_id'] === $transaction_id ) {
                    return true; // Ya procesado, no duplicamos pero retornamos true (éxito)
                }
            }
        }

        $new_payment = array(
            'date'           => sanitize_text_field( $date ),
            'note'           => sanitize_text_field( $note ),
            'amount'         => (float) $amount,
            'transaction_id' => sanitize_text_field( $transaction_id ),
            'source'         => sanitize_text_field( $source ),
            'receipt_url'    => esc_url_raw( $receipt_url )
        );

        $payments[] = $new_payment;
        update_post_meta( $order_id, '_wcmp_payments_history', $payments );
        
        // Mantener rastro de la última transacción para diagnósticos rápidos
        if ( ! empty( $transaction_id ) ) {
            update_post_meta( $order_id, '_wcmp_last_transaction_id', $transaction_id );
        }

        // Clear cache
        clean_post_cache( $order_id );

        // Update status (Force override as it is a NEW payment)
        wcmp_update_order_status_by_balance( $order_id, null, true );

        // Notify customer
        wcmp_notify_customer_payment( $order_id, $new_payment );

        return true;
    }
}

/**
 * Get total payments for an order
 */
if ( ! function_exists( 'wcmp_get_order_payments_total' ) ) {
    function wcmp_get_order_payments_total( $order_id ) {
        $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
        $total_manual = 0;
        if ( is_array( $payments ) ) {
            foreach ( $payments as $payment ) {
                $total_manual += (float) $payment['amount'];
            }
        }

        // RETRO-COMPATIBILIDAD v1.8.21:
        // Si el pedido ya está en estado "Procesando" (Pagado con Tarjeta) o "Completado" 
        // pero no tiene historial manual, devolvemos el total del pedido para que el saldo sea $0.
        if ( $total_manual <= 0 ) {
            $order = wc_get_order( $order_id );
            if ( $order && in_array( $order->get_status(), array( 'processing', 'completed' ) ) ) {
                return (float)$order->get_total();
            }
        }

        return $total_manual;
    }
}

/**
 * Update order status based on payments
 */
if ( ! function_exists( 'wcmp_update_order_status_by_balance' ) ) {
    function wcmp_update_order_status_by_balance( $order_id, $total_paid = null, $force = false ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $total_order = round((float)$order->get_total(), 2);
        if ( is_null( $total_paid ) ) {
            $total_paid = round(wcmp_get_order_payments_total( $order_id ), 2);
        } else {
            $total_paid = round((float)$total_paid, 2);
        }
        
        $current_status = $order->get_status();

        // LÓGICA DE ESTADOS CIRCULAR v1.5.8
        if ( $total_paid <= 0 ) {
            // Si no hay pagos y el estado actual es uno de los nuestros, regresamos a "En Espera"
            if ( in_array( $current_status, array( 'p-pagado', 'completed' ) ) ) {
                $order->update_status( 'on-hold', __( 'Sin pagos registrados. Regresando a estado base.', 'wc-manual-payments' ) );
            }
            return;
        }

        // EXCEPCIÓN v1.5.5/v1.5.6: Solo respetamos el estado "En Espera" (On Hold) si NO es un pago forzado (edición simple)
        if ( 'on-hold' === $current_status && ! $force ) {
            return;
        }

        if ( $total_paid >= $total_order ) {
            if ( 'completed' !== $current_status ) {
                $order->update_status( 'completed', __( 'Pago total recibido (Manual).', 'wc-manual-payments' ) );
            }
        } else {
            // Use the custom status slug: p-pagado
            if ( 'p-pagado' !== $current_status ) {
                $order->update_status( 'p-pagado', __( 'Pago parcial recibido (Manual).', 'wc-manual-payments' ) );
            }
        }
    }
}

/**
 * Notify customer about payment
 */
if ( ! function_exists( 'wcmp_notify_customer_payment' ) ) {
    function wcmp_notify_customer_payment( $order_id, $payment_data ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $date = $payment_data['date'];
        $note = $payment_data['note'];
        $amount_formatted = wc_price( $payment_data['amount'] );
        $customer_name = $order->get_billing_first_name();
        
        $total_order    = (float)$order->get_total();
        $total_paid     = wcmp_get_order_payments_total( $order_id );
        $is_fully_paid  = ( $total_paid >= ($total_order - 0.01) ); // Tolerancia de centavos

        // Nuevo Formato Dinámico v1.5.4
        $message = sprintf( __( "¡Buenas noticias, %s! 🚀\nHemos recibido y acreditado tu pago con éxito. Aquí tienes el resumen:\n\n", 'wc-manual-payments' ), $customer_name );
        $message .= sprintf( "📅 Fecha: %s\n", $date );
        $message .= sprintf( "💰 Monto: %s\n", strip_tags($amount_formatted) );
        $message .= sprintf( "📝 Concepto: %s\n\n", $note );
        
        $message .= "📦 Estatus de tu pedido\n";
        $message .= "Tu pedido se actualizará automáticamente:\n\n";
        $message .= "✅ Completado: Si el monto cubre el total.\n";
        $message .= "⏳ Parcialmente pagado: Si aún queda un saldo pendiente.\n\n";
        
        if ( $is_fully_paid ) {
            $message .= "🎉 ¡FELICIDADES! Tu pedido está COMPLETAMENTE PAGADO.\n\n";
        } else {
            $balance = $total_order - $total_paid;
            $message .= sprintf( "⚠️ Saldo restante: %s\n\n", strip_tags(wc_price($balance)) );
        }

        $message .= sprintf( "Recuerda: Para tus próximos pagos, usa siempre tu número de pedido (#%s) como referencia para agilizar el proceso.\n\n", $order->get_order_number() );
        
        $message .= "Puedes ver los detalles aquí:\n";
        $message .= "👉 " . home_url('/mi-cuenta') . "\n\n";
        
        $message .= "¡Gracias por tu compra!\n";
        $message .= "Equipo The Best Prom.";

        // Agregar como nota al cliente (esto dispara el email de WC si está configurado)
        $order->add_order_note( $message, 1 ); 
    }
}

/**
 * Link an orphan payment to a real order
 */
if ( ! function_exists( 'wcmp_link_orphan_payment' ) ) {
    function wcmp_link_orphan_payment( $orphan_index, $target_order_id ) {
        $orphans = get_option( 'wcmp_orphan_payments', array() );
        
        if ( ! isset( $orphans[ $orphan_index ] ) ) {
            return false;
        }

        $orphan = $orphans[ $orphan_index ];
        $amount = (float) $orphan['amount'];
        $note   = $orphan['ref'] . ' (' . $orphan['name'] . ') [VINCULADO]';
        $date   = date('Y-m-d', strtotime($orphan['date'] ?? current_time('mysql')));

        // Add as a normal payment
        if ( wcmp_add_order_payment( $target_order_id, $amount, $note, $date ) ) {
            // Remove from orphans
            unset( $orphans[ $orphan_index ] );
            update_option( 'wcmp_orphan_payments', array_values( $orphans ) );
            return true;
        }

        return false;
    }
}

/**
 * AJAX Handler to get all filtered Order IDs
 */
add_action( 'wp_ajax_wcmp_get_all_filtered_order_ids', 'wcmp_get_all_filtered_order_ids_ajax' );
function wcmp_get_all_filtered_order_ids_ajax() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Acceso denegado' );
    }

    if ( ! function_exists( 'wcmp_get_baseline_orders_data' ) ) {
        require_once plugin_dir_path( __FILE__ ) . 'admin-order-reports.php';
    }

    $all_ids = wcmp_get_baseline_orders_data( true ); // true = ids_only
    
    if ( is_array( $all_ids ) ) {
        wp_send_json_success( $all_ids );
    } else {
        wp_send_json_error( 'Error al obtener IDs filtrados' );
    }
}

/**
 * Auto-record global payments from standard gateways
 * v1.8.20: Ensures balance shows $0 when paid via Stripe/Standard Gateway
 */
add_action( 'woocommerce_order_status_processing', 'wcmp_auto_record_gateway_payment', 10, 1 );
add_action( 'woocommerce_order_status_completed', 'wcmp_auto_record_gateway_payment', 10, 1 );

function wcmp_auto_record_gateway_payment( $order_id ) {
    $payments = get_post_meta( $order_id, '_wcmp_payments_history', true );
    
    // Solo actuamos si NO hay historial de pagos registrados por nuestro plugin
    if ( empty( $payments ) ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) return;

        $total = (float)$order->get_total();
        if ( $total > 0 ) {
            $gateway_title = $order->get_payment_method_title() ?: 'Pasarela Estándar';
            $note = sprintf( __( 'Acreditación automática: Pago vía %s', 'wc-manual-payments' ), $gateway_title );
            
            $date_paid = $order->get_date_paid();
            $date_str = $date_paid ? $date_paid->date('Y-m-d') : current_time('Y-m-d');
            
            // Evitar recursión infinita deteniendo los hooks si es necesario, 
            // aunque wcmp_add_order_payment llama a wcmp_update_order_status_by_balance que no dispara statuses de nuevo usualmente
            wcmp_add_order_payment( $order_id, $total, $note, $date_str, 'GATEWAY_' . $order_id, 'system' );
        }
    }
}
