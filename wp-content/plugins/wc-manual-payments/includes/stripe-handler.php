<?php
/**
 * Stripe Handler and Intelligent Validator
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Intelligent Validator for Order IDs
 */
if ( ! function_exists( 'wcmp_validate_order_id' ) ) {
    function wcmp_validate_order_id( $input ) {
        // 1. Extract only digits
        $order_id = preg_replace( '/[^0-9]/', '', $input );
        
        if ( empty( $order_id ) ) {
            return false;
        }

        $order_id = (int) $order_id;

        // 2. Coherence Check (Range 25,000 to 50,000)
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
}

/**
 * Handle Stripe Webhook Data
 */
if ( ! function_exists( 'wcmp_handle_stripe_checkout_completed' ) ) {
    function wcmp_handle_stripe_checkout_completed( $session_data ) {
    $raw_order_input = '';
    
    if ( isset( $session_data['custom_fields'] ) ) {
        foreach ( $session_data['custom_fields'] as $field ) {
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

    // --- Intentar obtener terminación de tarjeta (Last 4) y Recibo ---
    $card_last4 = '';
    $receipt_url = '';
    $secret_key = get_option('wcmp_stripe_secret_key');
    
    // DEBUG: Log intent
    $debug_file = WP_CONTENT_DIR . '/wcmp-stripe-debug.log';
    $log_prefix = "[" . current_time('mysql') . "] [PI: $transaction_id] ";
    file_put_contents($debug_file, $log_prefix . "Iniciando recuperación...\n", FILE_APPEND);

    if ( ! empty($secret_key) && ! empty($transaction_id) ) {
        $api_url = "https://api.stripe.com/v1/payment_intents/{$transaction_id}?expand[]=payment_method&expand[]=latest_charge";
        $response = wp_remote_get( $api_url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $secret_key
            ),
            'timeout' => 15
        ) );

        if ( is_wp_error($response) ) {
            file_put_contents($debug_file, $log_prefix . "API Error: " . $response->get_error_message() . "\n", FILE_APPEND);
        } else {
            $body = wp_remote_retrieve_body($response);
            $pi_data = json_decode( $body, true );
            
            // Log structure for analysis
            $log_data = [
                'id' => $pi_data['id'] ?? 'N/A',
                'pm_expanded' => isset($pi_data['payment_method']['card']) ? 'YES' : 'NO',
                'charge_expanded' => isset($pi_data['latest_charge']['receipt_url']) ? 'YES' : 'NO'
            ];
            file_put_contents($debug_file, $log_prefix . "Resumen: " . json_encode($log_data) . "\n", FILE_APPEND);

            // 1. Buscar last4 en payment_method (expandido)
            if ( isset($pi_data['payment_method']['card']['last4']) ) {
                $card_last4 = $pi_data['payment_method']['card']['last4'];
            } 
            // Fallback a latest_charge (expandido)
            elseif ( isset($pi_data['latest_charge']['payment_method_details']['card']['last4']) ) {
                $card_last4 = $pi_data['latest_charge']['payment_method_details']['card']['last4'];
            }

            // 2. Extraer Receipt URL
            if ( isset($pi_data['latest_charge']['receipt_url']) ) {
                $receipt_url = $pi_data['latest_charge']['receipt_url'];
            }
        }
    }
    // --- Fin obtener Card Meta ---

    $validated_id = wcmp_validate_order_id( $raw_order_input );

    if ( $validated_id ) {
        // Fallback robust: Si no hay last4, el mensaje base al menos debe decir Pago con tarjeta
        $msg_base = $card_last4 ? sprintf('Pago con tarjeta terminación %s', $card_last4) : 'Pago con tarjeta (Stripe)';
        $note = sprintf( '%s Ref: %s', $msg_base, $transaction_id );
        
        $result = wcmp_add_order_payment( $validated_id, $amount, $note, null, $transaction_id, 'stripe', $receipt_url );

        // PROTECCIÓN v1.5.9: Si está bloqueado, enviar a huérfanos
        if ( $result === 'blocked' ) {
            wcmp_log_orphan_payment( array(
                'source' => 'Stripe',
                'amount' => $amount,
                'input'  => $raw_order_input,
                'ref'    => $transaction_id,
                'name'   => $customer_name . ' [PEDIDO YA PAGADO #' . $validated_id . ']',
                'email'  => $customer_email
            ) );
            return 'blocked';
        }

        return $result;
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
}

/**
 * Register REST API Route for Webhooks
 */
if ( ! function_exists( 'wcmp_register_webhook_route' ) ) {
    function wcmp_register_webhook_route() {
        register_rest_route( 'wcmp/v1', '/webhook', array(
            'methods'  => array( 'GET', 'POST' ),
            'callback' => 'wcmp_handle_incoming_webhook',
            'permission_callback' => '__return_true',
        ) );
    }
}
add_action( 'rest_api_init', 'wcmp_register_webhook_route' );

/**
 * Helper to process a single payment from Sheets
 */
if ( ! function_exists( 'wcmp_process_single_sheet_payment' ) ) {
    function wcmp_process_single_sheet_payment( $item ) {
        $order_id = $item['order_id'] ?? '';
        $amount   = (float) ($item['amount'] ?? 0);
        $note     = sanitize_text_field( $item['note'] ?? '' );
        $raw_date = isset($item['date']) ? sanitize_text_field($item['date']) : '';
        
        $date = current_time('Y-m-d'); // Default

        if ( ! empty($raw_date) ) {
            $timestamp = strtotime($raw_date);
            if ( ! $timestamp && strpos($raw_date, '/') !== false ) {
                $date_parts = explode('/', $raw_date);
                if ( count($date_parts) === 3 ) {
                    $raw_date = $date_parts[2] . '-' . $date_parts[1] . '-' . $date_parts[0];
                    $timestamp = strtotime($raw_date);
                }
            }
            if ( $timestamp && $timestamp > 0 ) {
                $date = date('Y-m-d', $timestamp);
            }
        }

        $validated_id = wcmp_validate_order_id( $order_id );

        if ( $validated_id && $amount > 0 ) {
            // --- VALIDACIÓN INTELIGENTE DE DUPLICADOS (HISTORIAL) ---
            $history = get_post_meta( $validated_id, '_wcmp_payments_history', true );
            if ( is_array( $history ) ) {
                foreach ( $history as $p ) {
                    if ( round((float)$p['amount'], 2) === round($amount, 2) && 
                         $p['date'] === $date && 
                         $p['note'] === $note ) {
                        
                        // Registrar intento fallido como nota de pedido
                        $order = wc_get_order( $validated_id );
                        if ( $order ) {
                            $order->add_order_note( sprintf( '⚠️ Se intentó duplicar un pago de Sheets ($%s) ignorado por el sistema.', $amount ) );
                        }
                        return array( 'status' => 'duplicate', 'order_id' => $validated_id );
                    }
                }
            }

            $sheet_tx_id = 'sheet_' . md5($order_id . $amount . $note . $date);
            $result = wcmp_add_order_payment( $validated_id, $amount, $note, $date, $sheet_tx_id, 'sheets' );
            
            if ( $result === 'blocked' ) {
                wcmp_log_orphan_payment( array(
                    'source' => 'Google Sheets',
                    'amount' => $amount,
                    'input'  => $order_id,
                    'ref'    => $note . ' [PEDIDO YA PAGADO #' . $validated_id . ']',
                    'name'   => 'Ingreso Sheets'
                ) );
                return array( 'status' => 'blocked', 'order_id' => $validated_id );
            }

            return array( 'status' => 'success', 'order_id' => $validated_id );
        } else {
            wcmp_log_orphan_payment( array(
                'source' => 'Google Sheets',
                'amount' => $amount,
                'input'  => $order_id,
                'ref'    => $note,
                'name'   => 'Ingreso Manual Sheets'
            ) );
            return array( 'status' => 'error', 'order_id' => $order_id );
        }
    }
}

/**
 * Universal Webhook Handler
 */
if ( ! function_exists( 'wcmp_handle_incoming_webhook' ) ) {
    function wcmp_handle_incoming_webhook( WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( ! $params ) {
            $params = $request->get_params();
        }
        
        $body = $request->get_body();
        
        // Update diagnostic signal
        $last_signal = array(
            'time'   => current_time('mysql'),
            'source' => $source_hint,
            'status' => 'Received'
        );
        update_option('wcmp_last_webhook_signal', $last_signal);

        $headers = array( 
            'X-WCMP-Sello' => 'valid',    // Standard
            'x-wcmp-sello' => 'valid'     // Hostinger/CDN Lowercase Compatibility
        );

        // 1. Detect Source: Stripe
        if ( strpos( $body, 'checkout.session.completed' ) !== false ) {
            $event = json_decode( $body, true );
            $session = $event['data']['object'];
            
            $stripe_result = wcmp_handle_stripe_checkout_completed( $session );

            if ( $stripe_result === true ) {
                $last_signal['status'] = 'Success (Stripe)';
                update_option('wcmp_last_webhook_signal', $last_signal);
                return new WP_REST_Response( array( 'status' => 'success', 'source' => 'stripe' ), 200, $headers );
            } elseif ( $stripe_result === 'blocked' ) {
                $last_signal['status'] = 'Blocked - Pedido ya pagado (Stripe)';
                update_option('wcmp_last_webhook_signal', $last_signal);
                return new WP_REST_Response( array( 'status' => 'blocked', 'message' => 'Order already paid' ), 409, $headers );
            } else {
                $last_signal['status'] = 'Orphan (Stripe)';
                update_option('wcmp_last_webhook_signal', $last_signal);
            }
        }

        // 2. Detect Source: Google Sheets (BATCH & SINGLE)
        if ( isset( $params['source'] ) && $params['source'] === 'google_sheets' ) {
            
            // Check for Batch
            if ( isset( $params['batch'] ) && is_array( $params['batch'] ) ) {
                $results = array();
                foreach ( $params['batch'] as $payment_item ) {
                    $results[] = wcmp_process_single_sheet_payment( $payment_item );
                }
                
                $last_signal['status'] = 'Batch Processed (' . count($results) . ' items)';
                update_option('wcmp_last_webhook_signal', $last_signal);

                return new WP_REST_Response( array( 'status' => 'batch_success', 'results' => $results ), 200, $headers );
            }
            
            // Fallback for Single
            $res = wcmp_process_single_sheet_payment( $params );
            
            if ( $res['status'] === 'success' ) {
                $last_signal['status'] = 'Success (Sheets)';
                update_option('wcmp_last_webhook_signal', $last_signal);
                return new WP_REST_Response( array( 'status' => 'success', 'source' => 'sheets' ), 200, $headers );
            } elseif ( $res['status'] === 'duplicate' ) {
                $last_signal['status'] = 'Duplicate Blocked';
                update_option('wcmp_last_webhook_signal', $last_signal);
                return new WP_REST_Response( array( 'status' => 'duplicate', 'message' => 'Payment already exists' ), 409, $headers );
            } elseif ( $res['status'] === 'blocked' ) {
                $last_signal['status'] = 'Blocked - Pedido ya pagado (Sheets)';
                update_option('wcmp_last_webhook_signal', $last_signal);
                return new WP_REST_Response( array( 'status' => 'blocked', 'message' => 'Order already paid' ), 409, $headers );
            } else {
                return new WP_REST_Response( array( 'status' => 'error', 'message' => 'Invalid order or amount' ), 400, $headers );
            }
        }

        return new WP_REST_Response( array( 'status' => 'ignored' ), 200, $headers );

    }
}

/**
 * Log Orphan Payments for Admin Review
 */
if ( ! function_exists( 'wcmp_log_orphan_payment' ) ) {
    function wcmp_log_orphan_payment( $data ) {
        $orphans = get_option( 'wcmp_orphan_payments', array() );
        if ( ! is_array( $orphans ) ) { $orphans = array(); }

        // Evitar duplicados en huérfanos usando el ID de referencia
        if ( ! empty( $data['ref'] ) ) {
            foreach ( $orphans as $o ) {
                if ( isset( $o['ref'] ) && $o['ref'] === $data['ref'] ) {
                    return; // Ya existe este registro huérfano
                }
            }
        }

        $data['date'] = current_time('mysql');
        $orphans[] = $data;
        update_option( 'wcmp_orphan_payments', $orphans );
    }
}
