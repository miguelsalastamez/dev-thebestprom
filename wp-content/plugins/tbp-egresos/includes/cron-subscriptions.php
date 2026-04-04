<?php
/**
 * Cron Job logic for PO Subscriptions & Administrative Budgets
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Schedule the cron if not already scheduled
if ( ! wp_next_scheduled( 'tbp_egresos_subscription_renewal' ) ) {
    wp_schedule_event( time(), 'daily', 'tbp_egresos_subscription_renewal' );
}

add_action( 'tbp_egresos_subscription_renewal', 'tbp_egresos_process_subscriptions' );

/**
 * Process all Manual POs and renew their authorized amounts
 */
function tbp_egresos_process_subscriptions() {
    $today = date('Y-m-d');
    
    $args = array(
        'post_type'      => 'tbp_orden_compra',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'meta_query'     => array(
            'relation' => 'AND',
            array(
                'key'     => '_tbp_event_id',
                'value'   => '0',
                'compare' => '='
            ),
            array(
                'key'     => '_tbp_next_renewal',
                'value'   => $today,
                'compare' => '<='
            ),
            array(
                'key'     => '_tbp_frecuencia',
                'value'   => 'Único',
                'compare' => '!='
            )
        )
    );

    $pos = get_posts( $args );

    foreach ( $pos as $po ) {
        $po_id = $po->ID;
        $monto_fijo = floatval( get_post_meta( $po_id, '_tbp_monto_fijo', true ) );
        $mode = get_post_meta( $po_id, '_tbp_po_mode', true ); // Fijo, Acumulable
        $frecuencia = get_post_meta( $po_id, '_tbp_frecuencia', true );
        
        $current_auth = floatval( get_post_meta( $po_id, '_tbp_cantidad_autorizada', true ) );
        $new_auth = $current_auth;

        if ( $mode === 'Acumulable' ) {
            $new_auth += $monto_fijo;
        } else {
            // Fijo (Reseteo)
            $new_auth = $monto_fijo;
        }

        // Update Authorized amount
        update_post_meta( $po_id, '_tbp_cantidad_autorizada', $new_auth );

        // Calculate next renewal date
        $next_date = tbp_egresos_calculate_next_date( $today, $frecuencia );
        update_post_meta( $po_id, '_tbp_next_renewal', $next_date );

        // Log the event (Audit)
        $audit_log = "Sistema: Renovación automática ({$frecuencia}). Saldo anterior: {$current_auth}, Nuevo saldo: {$new_auth}. Próxima fecha: {$next_date}";
        update_post_meta( $po_id, '_tbp_last_audit_log', $audit_log );

        // Check for contract expiry alert (within 30 days)
        $contract_end = get_post_meta( $po_id, '_tbp_contract_end', true );
        if ( $contract_end ) {
            $end_date = new DateTime($contract_end);
            $today_date = new DateTime($today);
            $interval = $today_date->diff($end_date);
            
            if ( $interval->invert === 0 && $interval->days <= 30 ) {
                $expiry_msg = "⚠️ ALERTA DE VENCIMIENTO: El contrato vence en {$interval->days} días (Fecha: {$contract_end}). Favor de revisar incremento de inflación/6%.";
                update_post_meta( $po_id, '_tbp_expiry_alert', $expiry_msg );
            } else if ( $interval->invert === 1 ) {
                update_post_meta( $po_id, '_tbp_expiry_alert', "🚫 CONTRATO VENCIDO el {$contract_end}. Se requiere renovación inmediata." );
            } else {
                delete_post_meta( $po_id, '_tbp_expiry_alert' );
            }
        }
    }
}

/**
 * Helper to calculate next date based on frequency
 */
function tbp_egresos_calculate_next_date( $start_date, $frequency ) {
    $date = new DateTime( $start_date );
    
    switch ( $frequency ) {
        case 'Semanal':
            $date->modify( '+1 week' );
            break;
        case 'Mensual':
            $date->modify( '+1 month' );
            break;
        case 'Bimestral':
            $date->modify( '+2 months' );
            break;
        case 'Anual':
            $date->modify( '+1 year' );
            break;
        default:
            return ''; // No repeat
    }
    
    return $date->format( 'Y-m-d' );
}
