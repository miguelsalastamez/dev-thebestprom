<?php
/**
 * Automates accreditation of Virtual Raffle Tickets to Graduate Orders.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'woocommerce_order_status_processing', 'wcmp_process_virtual_raffle_payment', 10, 2 );
add_action( 'woocommerce_order_status_completed',  'wcmp_process_virtual_raffle_payment', 10, 2 );

/**
 * Process virtual raffle ticket orders and accredit the amount to the graduate's order.
 * 
 * @param int $order_id
 * @param WC_Order $order
 * @param int|null $forced_graduate_id Optional forced graduate ID from the metabox
 */
function wcmp_process_virtual_raffle_payment( $order_id, $order, $forced_graduate_id = null ) {
    $already_processed = get_post_meta( $order_id, '_wcmp_virtual_raffle_processed', true );
    if ( $already_processed ) {
        return;
    }

    $raffle_event_id = 28876;
    $virtual_ticket_cost = 200;
    
    $has_raffle_tickets = false;
    $total_tickets = 0;
    $debug_log = [];

    // Search through order items for the raffle tickets
    foreach ( $order->get_items() as $item_id => $item ) {
        $product_id = $item->get_product_id();
        $event_id = get_post_meta( $product_id, '_tribe_tickets_for_event', true ); 
        if ( ! $event_id ) $event_id = get_post_meta( $item_id, '_tribe_tickets_meta_event_id', true );
        
        $debug_log[] = "Prod:$product_id|Ev:$event_id";
        
        if ( $product_id == $raffle_event_id || $event_id == $raffle_event_id || $item->get_name() === 'Rifa a beneficio Graduados Prepa 15' || strpos($item->get_name(), 'Rifa') !== false ) {
            $qty = $item->get_quantity();
            $total_tickets += $qty;
            $has_raffle_tickets = true;
        }
    }

    if ( ! $has_raffle_tickets ) {
        return;
    }

    // Determine the graduate ID (either forced or auto-detected)
    $graduate_order_id = $forced_graduate_id ? $forced_graduate_id : wcmp_find_graduate_id_for_order( $order_id, $order );

    // DEBUG NOTE TO USER
    if ( defined('WP_DEBUG') || $order_id == 30299 ) {
        $order->add_order_note("Debug Interno Rifa -> Items escaneados: " . implode(', ', $debug_log) . " | Tiene Rifa?: Si | GraduadoID: " . ($graduate_order_id ?: 'Ninguno'));
    }

    if ( $graduate_order_id ) {
        $graduate_order = wc_get_order( $graduate_order_id );
        if ( ! $graduate_order ) {
            $order->add_order_note( sprintf( 'Automatización Rifa: Se intentó abonar pero el pedido de graduado #%s no existe en el sistema.', $graduate_order_id ) );
            return;
        }

        $amount_to_credit = $total_tickets * $virtual_ticket_cost;
        $note = sprintf( '%d boletos amparados por el pedido #%s', $total_tickets, $order->get_order_number() );
        $date = current_time('Y-m-d');
        $transaction_id = 'raffle_' . $order->get_order_number() . '_' . time();

        if ( function_exists( 'wcmp_add_order_payment' ) ) {
            $success = wcmp_add_order_payment( $graduate_order_id, $amount_to_credit, $note, $date, $transaction_id, 'automation' );
            if ( $success ) {
                update_post_meta( $order_id, '_wcmp_virtual_raffle_processed', 'yes' );
                $message_for_buyer = sprintf( 'Se ha abonado la cantidad de %s correspondiente a %d boletos de rifa al pedido #%s de manera exitosa.', wc_price($amount_to_credit), $total_tickets, $graduate_order_id );
                $order->add_order_note( $message_for_buyer, 1 ); 
                if ( $order->get_status() !== 'completed' ) {
                    $order->update_status( 'completed', 'Automatización de Rifa Virtual completada.' );
                }
            } else {
                $order->add_order_note( sprintf( 'Automatización Rifa: No se pudo abonar el pago al pedido #%s por razones internas del plugin de pagos.', $graduate_order_id ) );
            }
        }
    }
}

/**
 * Searches all possible WooCommerce and Event Tickets Plus locations for the graduate ID
 */
function wcmp_find_graduate_id_for_order( $order_id, $order ) {
    $graduate_order_id = null;
    
    // 0. Check order item _tribe_tickets_meta
    foreach ( $order->get_items() as $item_id => $item ) {
        $raw_tickets_meta = wc_get_order_item_meta( $item_id, '_tribe_tickets_meta', true );
        if ( is_array( $raw_tickets_meta ) ) {
            foreach ( $raw_tickets_meta as $guest_data ) {
                if ( is_array($guest_data) ) {
                    foreach ( $guest_data as $key => $val ) {
                        if ( stripos( $key, 'graduado' ) !== false || stripos( $key, 'pedido' ) !== false ) {
                            $cleaned = preg_replace( '/[^0-9]/', '', $val );
                            if ( $cleaned ) return intval($cleaned);
                        }
                    }
                }
            }
        }
        foreach ( $item->get_meta_data() as $m ) {
            if ( stripos( $m->key, 'graduado' ) !== false || stripos( $m->key, 'pedido' ) !== false ) {
                $cleaned = preg_replace( '/[^0-9]/', '', $m->value );
                if ( $cleaned ) return intval( $cleaned );
            }
        }
    }

    global $wpdb;

    // 1. Universal ET+ Attendee Finder via Order & Item relationships
    $search_values = array( $order_id, strval($order_id) );
    foreach( $order->get_items() as $item_id => $item ) {
        $search_values[] = $item_id;
        $search_values[] = strval($item_id);
    }
    
    $placeholders = implode(',', array_fill(0, count($search_values), '%s'));
    $query = $wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_value IN ($placeholders)", ...$search_values);
    $linked_posts = $wpdb->get_col( $query );
    
    if ( !empty($linked_posts) ) {
        foreach ( $linked_posts as $p_id ) {
            $meta = get_post_meta( $p_id, '_tribe_tickets_meta', true );
            if ( is_array( $meta ) ) {
                foreach ( $meta as $guest_index => $guest_data ) {
                    if ( is_array($guest_data) ) {
                        foreach ( $guest_data as $sub_k => $sub_v ) {
                            if ( is_string($sub_k) && is_string($sub_v) && (stripos( $sub_k, 'graduado' ) !== false || stripos( $sub_k, 'pedido' ) !== false) ) {
                                $cleaned = preg_replace( '/[^0-9]/', '', $sub_v );
                                if ( $cleaned ) return intval($cleaned);
                            }
                        }
                    } elseif ( is_string($guest_index) && is_string($guest_data) && (stripos( $guest_index, 'graduado' ) !== false || stripos( $guest_index, 'pedido' ) !== false) ) {
                        $cleaned = preg_replace( '/[^0-9]/', '', $guest_data );
                        if ( $cleaned ) return intval($cleaned);
                    }
                }
            }
        }
    }

    // 2. Check ET+ Attendee Posts metadata broadly
    $attendees = get_posts( array(
        'post_type'      => array('tribe_wooticket', 'tribe_rsvp', 'attendee'), 
        'meta_query'     => array(
            array(
                'key'   => '_tribe_wooticket_order',
                'value' => $order_id
            )
        ),
        'posts_per_page' => -1,
        'post_status'    => 'any'
    ) );

    foreach ( $attendees as $att ) {
        $all_meta = get_post_meta( $att->ID );
        foreach ( $all_meta as $mk => $mv ) {
            $val = maybe_unserialize($mv[0]);
            if ( is_array($val) ) {
                foreach($val as $sub_k => $sub_v) {
                    if ( is_string($sub_k) && is_string($sub_v) && (stripos( $sub_k, 'graduado' ) !== false || stripos( $sub_k, 'pedido' ) !== false) ) {
                        $cleaned = preg_replace( '/[^0-9]/', '', $sub_v );
                        if ( $cleaned ) return intval($cleaned);
                    }
                }
            } else if ( is_string($mk) && is_string($val) && (stripos( $mk, 'graduado' ) !== false || stripos( $mk, 'pedido' ) !== false) ) {
                $cleaned = preg_replace( '/[^0-9]/', '', $val );
                if ( $cleaned ) return intval($cleaned);
            }
        }
    }

    // 3. Fallback: Check order metadata globally 
    $possible_keys = array('_billing_pedido', 'billing_pedido', 'pedido_del_alumno', '_pedido_del_alumno', 'numero_de_pedido', '_numero_de_pedido');
    foreach ( $possible_keys as $key ) {
        $val = $order->get_meta( $key );
        if ( ! empty( $val ) ) {
            $cleaned = preg_replace( '/[^0-9]/', '', $val );
            if( !empty($cleaned) ) return intval($cleaned);
        }
    }

    return null;
}
