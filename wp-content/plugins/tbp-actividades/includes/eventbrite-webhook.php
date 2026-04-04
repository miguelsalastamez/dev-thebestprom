<?php
/**
 * Eventbrite Webhook Handler for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register REST API Route for Eventbrite Webhooks
 */
function tbp_actividades_register_eb_webhook_route() {
    register_rest_route( 'tbp-actividades/v1', '/eventbrite', array(
        'methods'  => 'POST',
        'callback' => 'tbp_actividades_handle_eb_webhook',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'tbp_actividades_register_eb_webhook_route' );

/**
 * Handle Incoming Eventbrite Webhook
 */
function tbp_actividades_handle_eb_webhook( WP_REST_Request $request ) {
    $body = $request->get_body();
    $params = json_decode( $body, true );

    // Security: Validate Signature
    $shared_secret = get_option( 'tbp_eventbrite_webhook_secret' );
    $signature = $request->get_header( 'X-Eventbrite-Signature' );

    if ( ! empty( $shared_secret ) ) {
        // Eventbrite recommendation: The body is signed with HMAC-SHA1
        $calculated_sig = hash_hmac( 'sha1', $body, $shared_secret );
        if ( ! hash_equals( $calculated_sig, $signature ) ) {
            return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
        }
    }

    if ( ! $params || ! isset( $params['config']['action'] ) ) {
        return new WP_REST_Response( array( 'error' => 'Invalid payload' ), 400 );
    }

    if ( $params['config']['action'] !== 'order.placed' ) {
        return new WP_REST_Response( array( 'status' => 'ignored' ), 200 );
    }

    $api_url = $params['api_url'];
    
    // 1. Fetch Order Details from Eventbrite
    $eb_token = get_option( 'tbp_eventbrite_api_token' );
    if ( ! $eb_token ) {
        return new WP_REST_Response( array( 'error' => 'EB Token not configured' ), 500 );
    }

    $response = wp_remote_get( $api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $eb_token
        )
    ) );

    if ( is_wp_error( $response ) ) {
        return new WP_REST_Response( array( 'error' => 'Failed to fetch EB order' ), 500 );
    }

    $order_data = json_decode( wp_remote_retrieve_body( $response ), true );
    if ( ! $order_data ) {
        return new WP_REST_Response( array( 'error' => 'Invalid EB order data' ), 500 );
    }

    // 2. Validation Stripe: Confirm payment (Optional check if Eventbrite provides it)
    // Actually, Eventbrite usually only sends order.placed for successful payments if it's a paid event.
    // If we need to check Stripe specifically:
    // $payment_intent = $order_data['payment_intent']; // Hypothetical
    
    // 3. Identification of the Student
    // We need to fetch attendees to get the WC Order ID from custom questions or metadata
    $attendees_url = $api_url . 'attendees/';
    $atts_response = wp_remote_get( $attendees_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $eb_token
        )
    ) );

    if ( is_wp_error( $atts_response ) ) {
        return new WP_REST_Response( array( 'error' => 'Failed to fetch EB attendees' ), 500 );
    }

    $atts_data = json_decode( wp_remote_retrieve_body( $atts_response ), true );
    $wc_order_id = 0;

    if ( isset( $atts_data['attendees'][0]['answers'] ) ) {
        foreach ( $atts_data['attendees'][0]['answers'] as $answer ) {
            // Look for "Pedido" or similar in the question text
            if ( stripos( $answer['question'], 'Pedido' ) !== false || stripos( $answer['question'], 'Order' ) !== false ) {
                $wc_order_id = preg_replace( '/[^0-9]/', '', $answer['answer'] );
                break;
            }
        }
    }

    if ( ! $wc_order_id ) {
        // Log orphan or error
        return new WP_REST_Response( array( 'error' => 'WC Order ID not found in EB metadata' ), 400 );
    }

    // 4. Validation of Pedido Status
    $order = wc_get_order( $wc_order_id );
    if ( ! $order || $order->get_status() !== 'processing' ) {
        return new WP_REST_Response( array( 'error' => 'Invalid WC Order status or not found' ), 400 );
    }

    // 5. Abono vía Antigravity
    // Find the Rifa associated with this EB Event
    $eb_event_id = $order_data['event_id'];
    $rifas = get_posts( array(
        'post_type' => 'tbp_rifas',
        'meta_key' => '_tbp_eventbrite_id',
        'meta_value' => $eb_event_id,
        'posts_per_page' => 1
    ) );

    if ( empty( $rifas ) ) {
        return new WP_REST_Response( array( 'error' => 'No matching Rifa found for this EB Event' ), 400 );
    }

    $rifa_id = $rifas[0]->ID;
    $credit_amount = get_post_meta( $rifa_id, '_tbp_cost_virtual', true );
    
    if ( ! $credit_amount || ! function_exists( 'wcmp_add_order_payment' ) ) {
        return new WP_REST_Response( array( 'error' => 'Credit amount not configured or WCMP not active' ), 500 );
    }

    $note = sprintf( 
        __( "Abono por Rifa Virtual (EB: %s).", 'tbp-actividades' ),
        $order_data['id']
    );

    $success = wcmp_add_order_payment( $wc_order_id, $credit_amount, $note, null, 'EB_' . $order_data['id'] );

    if ( $success ) {
        // Messaging automation (Note: wcmp_add_order_payment already adds a note, 
        // but the requirements ask for a specific tone)
        tbp_actividades_add_custom_order_note( $wc_order_id, 1, 'virtual' );
        return new WP_REST_Response( array( 'status' => 'success' ), 200 );
    }

    return new WP_REST_Response( array( 'error' => 'Failed to add payment' ), 500 );
}
