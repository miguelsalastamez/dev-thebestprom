<?php
/**
 * Módulo: Asignación de Asientos — The Best Prom
 *
 * Carga las clases del módulo y registra los hooks principales.
 * Se incluye desde tbp-actividades.php en la Fase 2 del plugin.
 *
 * @package TBP_Actividades
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Cargar clases del módulo
require_once __DIR__ . '/class-tbp-asientos-db.php';
require_once __DIR__ . '/class-tbp-asientos-engine.php';
require_once __DIR__ . '/class-tbp-asientos-admin.php';
require_once __DIR__ . '/class-tbp-asientos-export.php';
require_once __DIR__ . '/class-tbp-asientos-upgrade.php';

/**
 * Devuelve la cantidad de lugares para un pedido según el egreso marcado
 * con el checkbox "Asignar Asientos" en tbp-egresos.
 *
 * Lógica:
 *  1. Busca todos los tickets en el pedido.
 *  2. Por cada ticket, lee _tbp_egresos_config.
 *  3. Suma la `cantidad` de los egresos cuyo `asignar_asientos` === '1'.
 *  4. Si no hay egreso marcado pero hay tickets, usa la cantidad de tickets como fallback.
 *
/**
 * Devuelve la cantidad de lugares para un pedido.
 * Si se pasa $proveedor_id > 0, cuenta SOLO los boletos cuyo egreso corresponda a ese proveedor
 * (ignorando la antigua casilla de "Asignar Asientos").
 * Esto iguala matemáticamente el conteo con el Reporte de Egresos.
 *
 * @param int $order_id      ID del pedido WooCommerce.
 * @param int $proveedor_id  ID del proveedor (ej. Cintermex).
 * @return int               Total de lugares asignables para ese pedido.
 */
function tbp_get_order_seat_qty( $order_id, $proveedor_id = 0 ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return 0;
    }

    $total_seats = 0;

    foreach ( $order->get_items() as $item ) {
        $product_id = $item->get_product_id();
        $item_qty   = (int) $item->get_quantity();

        // Leer la configuración de egresos del ticket/producto
        $egresos = get_post_meta( $product_id, '_tbp_egresos_config', true );

        if ( ! is_array( $egresos ) || empty( $egresos ) ) {
            continue;
        }

        foreach ( $egresos as $egreso ) {
            if ( $proveedor_id > 0 ) {
                // Nuevo comportamiento (Opción B): Filtrar por Proveedor, ignorar checkbox manual
                if ( intval( $egreso['proveedor_id'] ?? 0 ) !== $proveedor_id ) {
                    continue;
                }
            } else {
                // Comportamiento Legacy (Opción A): Filtrar por checkbox
                if ( empty( $egreso['asignar_asientos'] ) || '1' !== (string) $egreso['asignar_asientos'] ) {
                    continue;
                }
            }

            // La cantidad del egreso × la cantidad de tickets en el item
            $cantidad_raw = $egreso['cantidad'] ?? '';
            $egreso_qty   = ( $cantidad_raw === '' ) ? 1 : (int) $cantidad_raw;
            $total_seats += $egreso_qty * $item_qty;
        }
    }

    return max( 0, $total_seats );
}

/**
 * Obtiene todos los grupos y su conteo de lugares para un evento.
 *
 * @param int    $event_id     ID del evento (tribe_events).
 * @param string $group_field  Clave del metadato que define el grupo (ej. 'Grupo').
 * @param int    $proveedor_id ID del proveedor vinculado a los asientos.
 * @return array Array asociativo: [ 'Grupo 116' => 12, 'Grupo 74' => 9, ... ]
 *               Ordenado de mayor a menor cantidad de lugares.
 */
function tbp_asientos_get_event_groups( $event_id, $group_field = 'Grupo', $proveedor_id = 0 ) {
    // Obtener todos los pedidos del evento usando el motor de 7 capas existente
    $order_ids = tbp_asientos_get_orders_for_event( $event_id );

    $groups = array();

    foreach ( $order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            continue;
        }

        // Obtener el valor del campo de grupo desde los metadatos del asistente
        $group_value = tbp_asientos_get_order_group_value( $order_id, $group_field );
        if ( empty( $group_value ) ) {
            $group_value = __( 'Sin grupo', 'tbp-actividades' );
        }

        $seats = tbp_get_order_seat_qty( $order_id, $proveedor_id );
        if ( $seats <= 0 ) {
            continue;
        }

        if ( ! isset( $groups[ $group_value ] ) ) {
            $groups[ $group_value ] = 0;
        }
        $groups[ $group_value ] += $seats;
    }

    // Ordenar de mayor a menor (grupos más grandes primero = mejores zonas)
    arsort( $groups );

    return $groups;
}

/**
 * Obtiene el valor del campo de grupo de un pedido.
 * Lee los metadatos de los asistentes ET+ en el pedido.
 *
 * @param int    $order_id    ID del pedido.
 * @param string $group_field Nombre del campo (ej. 'Grupo', 'Prepa', 'Departamento').
 * @return string             Valor del campo o cadena vacía si no existe.
 */
function tbp_asientos_get_order_group_value( $order_id, $group_field ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) return '';

    if ( function_exists( 'tbp_actividades_get_order_attendees_meta' ) ) {
        $attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
        if ( ! empty( $attendees ) ) {
            $normalized_field = strtolower( sanitize_title( $group_field ) );

            foreach ( $attendees as $source_id => $meta_groups ) {
                if ( ! empty( $meta_groups ) ) {
                    reset( $meta_groups );
                    $first_key = key( $meta_groups );
                    if ( ! is_numeric( $first_key ) && $first_key !== 'Datos' ) {
                        $meta_groups = array( 'Datos' => $meta_groups );
                    }
                }

                foreach ( $meta_groups as $guest_index => $guest_data ) {
                    if ( ! is_array( $guest_data ) ) continue;
                    
                    foreach ( $guest_data as $field_key => $field_value ) {
                        $label = is_array( $field_value ) ? ( $field_value['label'] ?? '' ) : $field_key;
                        $value = is_array( $field_value ) ? ( $field_value['value'] ?? '' ) : $field_value;

                        if ( strtolower( sanitize_title( $label ) ) === $normalized_field || strtolower( sanitize_title( $field_key ) ) === $normalized_field ) {
                            return trim( $value );
                        }
                    }
                }
            }
        }
    }

    return '';
}

/**
 * Obtiene los IDs de pedidos WooCommerce asociados a un evento.
 * Reutiliza la lógica del plugin existente (motor de 7 capas).
 *
 * @param int $event_id ID del evento.
 * @return int[]        Array de order_ids.
 */
function tbp_asientos_get_orders_for_event( $event_id ) {
    global $wpdb;

    // Obtener los ticket IDs (productos) vinculados al evento de forma agresiva (7 capas)
    $ticket_ids = $wpdb->get_col( $wpdb->prepare( "
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE (meta_value = %d OR meta_value = %s)
        AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
    ", $event_id, (string)$event_id ) );

    if ( empty( $ticket_ids ) ) {
        return array();
    }

    // Buscar pedidos que contengan esos tickets (solo completados o procesando)
    $placeholders = implode( ',', array_fill( 0, count( $ticket_ids ), '%d' ) );
    $statuses     = array( 'wc-completed', 'wc-processing' );
    $status_in    = "'" . implode( "','", array_map( 'esc_sql', $statuses ) ) . "'";

    $order_ids = $wpdb->get_col( $wpdb->prepare(
        "SELECT DISTINCT o.ID
         FROM {$wpdb->posts} o
         INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON oi.order_id = o.ID
         INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim
             ON oim.order_item_id = oi.order_item_id
             AND oim.meta_key = '_product_id'
             AND oim.meta_value IN ($placeholders)
         WHERE o.post_status IN ($status_in)",
        ...$ticket_ids
    ) );

    return array_map( 'intval', $order_ids );
}

// =====================================================================
// INTEGRACIÓN CON WOOCOMMERCE Y EVENT TICKETS (FASE 5)
// =====================================================================

/**
 * 1. Mostrar mesa en "Mi Cuenta" > Ver Pedido
 */
add_action( 'woocommerce_order_details_after_order_table', 'tbp_asientos_show_seat_in_account', 10 );
function tbp_asientos_show_seat_in_account( $order = null ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    $assignment = get_post_meta( $order->get_id(), '_tbp_seat_assignment', true );
    if ( empty( $assignment ) || $assignment['status'] !== 'assigned' ) {
        return;
    }

    echo '<h2 style="margin-top:20px;">' . __( 'Asignación de Asientos', 'tbp-actividades' ) . '</h2>';
    echo '<div class="woocommerce-info" style="border-top-color:#4caf50; display:flex; flex-direction:column; gap:5px;">';
    echo '<p style="margin:0;"><strong>' . __( 'Mesa Asignada:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['mesa_numero'] ) . '</strong></p>';
    echo '<p style="margin:0; font-size:0.9em;">' . __( 'Zona:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['zona'] ) . '</p>';
    echo '<p style="margin:0; font-size:0.9em;">' . __( 'Grupo:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['grupo'] ) . '</p>';
    echo '<p style="margin:0; font-size:0.9em;">' . __( 'Lugares Reservados:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['cantidad'] ) . '</p>';
    
    // MVP: Diagrama esquemático en texto
    $zona_asignada = strtoupper( substr( trim( $assignment['zona'] ), -1 ) ); // A, B o C
    echo '<div style="margin-top:15px; padding:10px; background:#fff; border:1px solid #ddd; font-family:monospace; font-size:12px; line-height:1.5;">';
    echo '[ESCENARIO] ' . ( $zona_asignada === 'C' ? '► ZONA C ◄ (Tu mesa está aquí)' : '  ZONA C' ) . '<br>';
    echo '[PISTA    ] ' . ( $zona_asignada === 'B' ? '► ZONA B ◄ (Tu mesa está aquí)' : '  ZONA B' ) . '<br>';
    echo '[ACCESO   ] ' . ( $zona_asignada === 'A' ? '► ZONA A ◄ (Tu mesa está aquí)' : '  ZONA A' );
    echo '</div>';

    echo '</div>';
}

/**
 * 2. Mostrar mesa en Email de Confirmación de WooCommerce
 */
add_action( 'woocommerce_email_order_meta', 'tbp_asientos_email_seat_info', 10, 3 );
function tbp_asientos_email_seat_info( $order = null, $sent_to_admin = false, $plain_text = false ) {
    if ( ! is_a( $order, 'WC_Order' ) ) {
        return;
    }
    if ( $sent_to_admin ) {
        return; // Solo al cliente
    }

    $assignment = get_post_meta( $order->get_id(), '_tbp_seat_assignment', true );
    if ( empty( $assignment ) || $assignment['status'] !== 'assigned' ) {
        return;
    }

    if ( $plain_text ) {
        echo "====================================\n";
        echo "🪑 TU MESA ASIGNADA\n";
        echo "====================================\n";
        echo "Mesa: " . $assignment['mesa_numero'] . "\n";
        echo "Zona: " . $assignment['zona'] . "\n";
        echo "Grupo: " . $assignment['grupo'] . "\n";
        echo "Lugares: " . $assignment['cantidad'] . "\n\n";
    } else {
        echo '<div style="margin-bottom: 40px;">';
        echo '<h2>🪑 ' . __( 'Tu Mesa Asignada', 'tbp-actividades' ) . '</h2>';
        echo '<div style="border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px;">';
        echo '<p style="margin: 0 0 10px;"><strong>' . __( 'Mesa:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['mesa_numero'] ) . '</strong></p>';
        echo '<p style="margin: 0 0 5px;">' . __( 'Zona:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['zona'] ) . '</p>';
        echo '<p style="margin: 0 0 5px;">' . __( 'Grupo:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['grupo'] ) . '</p>';
        echo '<p style="margin: 0;">' . __( 'Lugares Reservados:', 'tbp-actividades' ) . ' ' . esc_html( $assignment['cantidad'] ) . '</p>';
        echo '</div>';
        echo '</div>';
    }
}

/**
 * 3. Mostrar mesa en Ticket (PDF/Digital) de Event Tickets Plus
 */
add_filter( 'tribe_tickets_ticket_email_attendee_list_meta', 'tbp_asientos_add_seat_to_ticket', 10, 2 );
function tbp_asientos_add_seat_to_ticket( $meta, $attendee ) {
    if ( ! isset( $attendee['order_id'] ) ) {
        return $meta;
    }

    $assignment = get_post_meta( $attendee['order_id'], '_tbp_seat_assignment', true );
    if ( ! empty( $assignment ) && $assignment['status'] === 'assigned' ) {
        $meta[] = array(
            'label' => __( '🪑 Mesa Asignada', 'tbp-actividades' ),
            'value' => $assignment['mesa_numero'] . ' (' . $assignment['zona'] . ')',
        );
        $meta[] = array(
            'label' => __( '👥 Grupo', 'tbp-actividades' ),
            'value' => $assignment['grupo'],
        );
    }
    return $meta;
}
