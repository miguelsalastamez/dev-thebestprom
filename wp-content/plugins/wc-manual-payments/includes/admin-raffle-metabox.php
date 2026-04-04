<?php
/**
 * Admin Meta Box for Virtual Raffle Attendee Data
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes', 'wcmp_add_raffle_metabox' );

function wcmp_add_raffle_metabox() {
    add_meta_box(
        'wcmp_raffle_box',
        __( 'TBP - Datos de Asistente (Rifa Virtual)', 'wc-manual-payments' ),
        'wcmp_raffle_metabox_content',
        'shop_order',
        'side',
        'high'
    );
}

function wcmp_raffle_metabox_content( $post ) {
    $order_id = $post->ID;
    $order = wc_get_order( $order_id );
    if ( ! $order ) return;

    // Check if order has raffle tickets
    $has_raffle = false;
    foreach( $order->get_items() as $item ) {
        if ( $item->get_product_id() == 28878 || $item->get_product_id() == 28876 || strpos($item->get_name(), 'Rifa') !== false ) {
            $has_raffle = true;
            break;
        }
    }

    if ( ! $has_raffle ) {
        echo '<p>Este pedido no contiene boletos de rifa virtual.</p>';
        return;
    }

    // Attempt to auto-find the Graduate ID via our universal logic
    $auto_id = wcmp_find_graduate_id_for_order( $order_id, $order );
    
    // Check if it was already credited manually via this metabox
    $forced_id = get_post_meta( $order_id, '_wcmp_forced_raffle_graduate_id', true );
    $current_id = $forced_id ? $forced_id : $auto_id;

    $already_processed = get_post_meta( $order_id, '_wcmp_virtual_raffle_processed', true );

    wp_nonce_field( 'wcmp_save_raffle_meta', 'wcmp_raffle_nonce' );
    ?>
    <div style="background: #f9f9f9; padding: 12px; border: 1px solid #ddd; border-radius: 4px;">
        <p><strong>Pedido del Graduado (Beneficiado):</strong></p>
        <input type="number" name="wcmp_raffle_graduate_id" value="<?php echo esc_attr( $current_id ); ?>" style="width: 100%;" placeholder="Ej. 30298">
        
        <?php if ( $already_processed ) : ?>
            <p style="color: green; font-weight: bold;">✅ Abono Procesado Exitosamente</p>
            <p class="description">El saldo ya fue transferido al pedido <?php echo esc_html($current_id); ?>.</p>
        <?php else : ?>
            <p style="color: #d63638; font-weight: bold;">⏳ Pendiente de Abono</p>
            <p class="description">Guarda el pedido para intentar el abono automático a este ID.</p>
        <?php endif; ?>

        <hr style="margin-top: 15px;">
        <p style="font-size: 11px; color: #666;">
            <strong>Diagnóstico de Event Tickets Plus:</strong><br>
            <textarea readonly style="width:100%; height: 80px; font-size:10px; background:#fff;"><?php echo esc_textarea( wcmp_debug_all_et_meta( $order_id ) ); ?></textarea>
        </p>
    </div>
    <?php
}

function wcmp_save_raffle_metabox_data( $post_id ) {
    if ( ! isset( $_POST['wcmp_raffle_nonce'] ) || ! wp_verify_nonce( $_POST['wcmp_raffle_nonce'], 'wcmp_save_raffle_meta' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['wcmp_raffle_graduate_id'] ) ) {
        $forced_id = intval( $_POST['wcmp_raffle_graduate_id'] );
        if ( $forced_id > 0 ) {
            update_post_meta( $post_id, '_wcmp_forced_raffle_graduate_id', $forced_id );
            
            // Try pushing the credit if changing status or saving
            $order = wc_get_order( $post_id );
            if ( $order && in_array( $order->get_status(), ['processing', 'completed'] ) ) {
                wcmp_process_virtual_raffle_payment( $post_id, $order, $forced_id );
            }
        }
    }
}
add_action( 'woocommerce_process_shop_order_meta', 'wcmp_save_raffle_metabox_data', 55 );

/**
 * Super Debugger to show all ET+ meta tied to order
 */
function wcmp_debug_all_et_meta( $order_id ) {
    global $wpdb;
    $log = [];
    
    // Scan postmeta
    $scan = $wpdb->get_results($wpdb->prepare("SELECT meta_key, meta_value FROM {$wpdb->postmeta} WHERE meta_value LIKE %s OR post_id = %d LIMIT 20", "%{$order_id}%", $order_id));
    foreach($scan as $s) {
        if ( stripos($s->meta_key, 'tribe') !== false || stripos($s->meta_key, 'attendee') !== false || stripos($s->meta_value, 'graduado') !== false ) {
            $log[] = "{$s->meta_key}: " . print_r(maybe_unserialize($s->meta_value), true);
        }
    }

    // Check TEC custom tables
    $tec_table = $wpdb->prefix . 'tec_tickets_attendees_meta';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) {
        $q = $wpdb->prepare("SELECT a.order_id, m.meta_key, m.meta_value FROM $tec_table m JOIN {$wpdb->prefix}tec_tickets_attendees a ON a.id = m.attendee_id WHERE a.order_id = %d", $order_id);
        $res = $wpdb->get_results($q);
        foreach($res as $r) {
            $log[] = "TEC_TABLE->{$r->meta_key}: {$r->meta_value}";
        }
    }
    
    return implode("\n", $log);
}
