<?php
/**
 * Stripe Handler and Intelligent Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intelligent Validator for Order IDs
 * Cleans strings like "P-28500", "28500", "Matricula 123"
 */
function wcmp_validate_order_id( $input ) {
    // 1. Extract only digits
    $order_id = preg_replace( '/[^0-9]/', '', $input );
    
    if ( empty( $order_id ) ) {
        return false;
    }

    $order_id = (int) $order_id;

    // 2. Coherence Check (Range 28,000 to 40,000)
    // Adjust these numbers based on your actual order sequence
    if ( $order_id < 25000 || $order_id > 50000 ) {
        return false;
    }

    // 3. Existence and Type Check
    $order = wc_get_order( $order_id );
    if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
        return false;
    }

    // 4. Status Check
    if ( in_array( $order->get_status(), array( 'cancelled', 'trash' ) ) ) {
        return false;
    }

    return $order_id;
}

/**
 * Handle Stripe Webhook Data
 */
function wcmp_handle_stripe_checkout_completed( $session_data ) {
    $raw_order_input = '';
    
    // Look for the custom field "Ingresa: P-Número de Pedido OBLIGATORIO"
    if ( isset( $session_data['custom_fields'] ) ) {
        foreach ( $session_data['custom_fields'] as $field ) {
            // Match by label or internal ID if known
            if ( stripos( $field['label']['custom'], 'Pedido' ) !== false ) {
                $raw_order_input = $field['text']['value'];
                break;
            }
        }
    }

    $amount = $session_data['amount_total'] / 100; // Stripe uses cents
    $transaction_id = $session_data['payment_intent'];
    $customer_email = $session_data['customer_details']['email'];
    $customer_name = $session_data['customer_details']['name'];

    $validated_id = wcmp_validate_order_id( $raw_order_input );

    if ( $validated_id ) {
        $note = sprintf( 
            __( '[STRIPE] Pago automático de %s (%s). Ref: %s', 'wc-manual-payments' ),
            $customer_name,
            $customer_email,
            $transaction_id
        );
        
        return wcmp_add_order_payment( $validated_id, $amount, $note );
    } else {
        // Handle Orphan Payment
        wcmp_log_orphan_payment( array(
            'source' => 'Stripe',
            'amount' => $amount,
            'input'  => $raw_order_input,
            'ref'    => $transaction_id,
            'name'   => $customer_name,
            'email'  => $customer_email
        ) );
        return false;
    }
}

/**
 * Register REST API Route for Webhooks (Stripe and Google Sheets)
 */
function wcmp_register_webhook_route() {
    register_rest_route( 'wcmp/v1', '/webhook', array(
        'methods'  => 'POST',
        'callback' => 'wcmp_handle_incoming_webhook',
        'permission_callback' => '__return_true', // Validation happens inside the callback
    ) );
}
add_action( 'rest_api_init', 'wcmp_register_webhook_route' );

/**
 * Universal Webhook Handler
 */
function wcmp_handle_incoming_webhook( WP_REST_Request $request ) {
    $params = $request->get_params();
    $body = $request->get_body();
    
    // 1. Detect Source: Stripe
    if ( strpos( $body, 'checkout.session.completed' ) !== false ) {
        $event = json_decode( $body, true );
        $session = $event['data']['object'];
        
        if ( wcmp_handle_stripe_checkout_completed( $session ) ) {
            return new WP_REST_Response( array( 'status' => 'success', 'source' => 'stripe' ), 200 );
        }
    }

    // 2. Detect Source: Google Sheets (Expected params: date, amount, note, order_id)
    if ( isset( $params['source'] ) && $params['source'] === 'google_sheets' ) {
        $order_id = $params['order_id'];
        $amount = (float) $params['amount'];
        $note = sanitize_text_field( $params['note'] );
        $date = sanitize_text_field( $params['date'] );

        $validated_id = wcmp_validate_order_id( $order_id );

        if ( $validated_id && $amount > 0 ) {
            $full_note = '[BANCO/SHEETS] ' . $note;
            wcmp_add_order_payment( $validated_id, $amount, $full_note, $date );
            return new WP_REST_Response( array( 'status' => 'success', 'source' => 'sheets' ), 200 );
        } else {
            // Log Orphan from Sheets
            wcmp_log_orphan_payment( array(
                'source' => 'Google Sheets',
                'amount' => $amount,
                'input'  => $order_id,
                'ref'    => $note,
                'name'   => 'Ingreso Manual Sheets'
            ) );
        }
    }

    return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
}

/**
 * Log Orphan Payments for Admin Review
 */
function wcmp_log_orphan_payment( $data ) {
    $orphans = get_option( 'wcmp_orphan_payments', array() );
    $data['date'] = current_time('mysql');
    $orphans[] = $data;
    update_option( 'wcmp_orphan_payments', $orphans );
}
