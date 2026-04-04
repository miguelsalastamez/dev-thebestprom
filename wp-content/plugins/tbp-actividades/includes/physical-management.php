<?php
/**
 * Physical Ticket Management for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Helper: Is User Staff?
 */
function tbp_actividades_user_is_staff() {
    if ( ! is_user_logged_in() ) return false;
    $user = wp_get_current_user();
    // Allow all except "customer" role
    return ! in_array( 'customer', (array) $user->roles );
}

/**
 * Shortcode [tbp_gestion_rifas]
 */
function tbp_actividades_gestion_rifas_shortcode() {
    if ( ! tbp_actividades_user_is_staff() ) {
        return '<p>' . __( 'Acceso restringido a personal autorizado.', 'tbp-actividades' ) . '</p>';
    }

    ob_start();
    ?>
    <div id="tbp-gestion-rifas-hub" class="tbp-actividades-container">
        <h2><?php _e( 'Gestión de Entrega Física de Boletos', 'tbp-actividades' ); ?></h2>
        
        <div class="tbp-search-section">
            <label for="tbp_rifa_select"><?php _e( '1. Selecciona la Rifa:', 'tbp-actividades' ); ?></label>
            <select id="tbp_rifa_select">
                <option value=""><?php _e( 'Seleccionar Rifa...', 'tbp-actividades' ); ?></option>
                <?php
                $rifas = get_posts( array( 'post_type' => 'tbp_rifas', 'posts_per_page' => -1 ) );
                foreach ( $rifas as $rifa ) {
                    echo '<option value="' . $rifa->ID . '">' . esc_html( $rifa->post_title ) . '</option>';
                }
                ?>
            </select>

            <br><br>

            <label for="tbp_order_search"><?php _e( '2. Ingresa # de Pedido:', 'tbp-actividades' ); ?></label>
            <input type="number" id="tbp_order_search" placeholder="Ej. 25001" />
            <button id="tbp_btn_search" class="button button-primary"><?php _e( 'Buscar Alumno', 'tbp-actividades' ); ?></button>
        </div>

        <div id="tbp_search_results" style="margin-top: 20px;">
            <!-- Dynamic Content -->
        </div>
    </div>

    <style>
        .tbp-actividades-container { max-width: 800px; margin: 20px auto; padding: 20px; background: #fff; border: 1px solid #ddd; border-radius: 8px; font-family: sans-serif; }
        .tbp-search-section { padding: 15px; background: #f9f9f9; border-radius: 5px; }
        .tbp-status-success { color: green; font-weight: bold; }
        .tbp-status-error { color: red; font-weight: bold; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('#tbp_btn_search').on('click', function() {
            var order_id = $('#tbp_order_search').val();
            var rifa_id = $('#tbp_rifa_select').val();

            if (!order_id || !rifa_id) {
                alert('Por favor selecciona una rifa e ingresa un número de pedido.');
                return;
            }

            $('#tbp_search_results').html('<p>Buscando...</p>');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_search_order',
                    order_id: order_id,
                    rifa_id: rifa_id
                },
                success: function(response) {
                    $('#tbp_search_results').html(response);
                }
            });
        });

        $(document).on('click', '#tbp_btn_deliver', function() {
            var order_id = $(this).data('order-id');
            var rifa_id = $(this).data('rifa-id');
            var quantity = $('#tbp_deliver_qty').val();

            if (!quantity || quantity <= 0) {
                alert('Ingresa una cantidad válida.');
                return;
            }

            if (!confirm('¿Confirmas la entrega de ' + quantity + ' boletos?')) return;

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_deliver_tickets',
                    order_id: order_id,
                    rifa_id: rifa_id,
                    quantity: quantity
                },
                success: function(response) {
                    $('#tbp_search_results').html(response);
                }
            });
        });
        $(document).on('click', '#tbp_btn_tombola', function() {
            var order_id = $(this).data('order-id');
            var rifa_id = $(this).data('rifa-id');
            var quantity = $('#tbp_tombola_qty').val();

            if (!quantity || quantity <= 0) {
                alert('Ingresa una cantidad válida.');
                return;
            }

            if (!confirm('¿Confirmas el ingreso de ' + quantity + ' boletos a tómbola?')) return;

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_receive_tombola',
                    order_id: order_id,
                    rifa_id: rifa_id,
                    quantity: quantity
                },
                success: function(response) {
                    $('#tbp_search_results').html(response);
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'tbp_gestion_rifas', 'tbp_actividades_gestion_rifas_shortcode' );

/**
 * AJAX Search Order
 */
function tbp_actividades_search_order_ajax() {
    if ( ! tbp_actividades_user_is_staff() ) {
        echo '<p class="tbp-status-error">Acceso denegado.</p>';
        wp_die();
    }

    $order_id = intval( $_POST['order_id'] );
    $rifa_id = intval( $_POST['rifa_id'] );

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        echo '<p class="tbp-status-error">Pedido no encontrado.</p>';
        wp_die();
    }

    // Validation: Event Match
    $linked_event_id = get_post_meta( $rifa_id, '_tbp_event_id', true );
    
    // Check if any product in order is linked to this event (Event Tickets Plus standard)
    $order_items = $order->get_items();
    $matches_event = false;
    foreach ( $order_items as $item ) {
        $product_id = $item->get_product_id();
        
        // Comprehensive list of possible meta keys for Event -> Ticket/Product relationship
        $possible_event_keys = array(
            '_tribe_wooticket_for_event', // Event Tickets Plus (WooCommerce)
            '_tribe_tickets_for_event',   // Event Tickets Base
            '_tribe_event_id',            // Generic Tribe
            '_event_id'                   // Generic fallback
        );

        $event_id = 0;
        foreach ( $possible_event_keys as $key ) {
            $val = get_post_meta( $product_id, $key, true );
            if ( $val ) {
                $event_id = $val;
                break;
            }
        }
        
        // Fallback: Check Post Parent (Some tickets use the event as parent)
        if ( ! $event_id ) {
            $product_post = get_post( $product_id );
            if ( $product_post && $product_post->post_parent > 0 ) {
                $event_id = $product_post->post_parent;
            }
        }

        if ( $event_id == $linked_event_id ) {
            $matches_event = true;
            break;
        }
    }

    // Fallback: Check if the order has meta for event
    if ( ! $matches_event ) {
        $possible_order_keys = array(
            '_tribe_tickets_meta_event',
            '_tribe_wooticket_event',
            '_tribe_event_id'
        );

        foreach ( $possible_order_keys as $key ) {
            $val = get_post_meta( $order_id, $key, true );
            if ( $val == $linked_event_id ) {
                $matches_event = true;
                break;
            }
        }
    }

    // Fallback: Parent Order (Deposits)
    if ( ! $matches_event && $order->get_parent_id() ) {
        $parent_order = wc_get_order( $order->get_parent_id() );
        if ( $parent_order ) {
            $parent_event_id = get_post_meta( $parent_order->get_id(), '_tribe_tickets_meta_event', true );
            if ( $parent_event_id == $linked_event_id ) {
                $matches_event = true;
            }
        }
    }

    if ( ! $matches_event ) {
        $debug_info = '';
        if ( current_user_can( 'manage_options' ) ) {
            $found_ids = array();
            foreach ( $order_items as $item ) {
                $pid = $item->get_product_id();
                $p_post = get_post($pid);
                $meta_woo = get_post_meta($pid, '_tribe_wooticket_for_event', true);
                $meta_base = get_post_meta($pid, '_tribe_tickets_for_event', true);
                $meta_ti = get_post_meta($pid, '_tribe_event_id', true);
                $parent = ($p_post) ? $p_post->post_parent : 0;
                
                $found_ids[] = "P:$pid (ET Plus:$meta_woo, ET Base:$meta_base, Parent:$parent)";
            }
            $debug_info = '<br><small style="color:#999">Debug: Looking for ' . $linked_event_id . '. Found in order: ' . implode(', ', $found_ids) . '</small>';
        }

        echo '<p class="tbp-status-error">' . __( 'El pedido no pertenece al evento ligado a esta rifa.', 'tbp-actividades' ) . $debug_info . '</p>';
        wp_die();
    }

    $cost_physical = floatval( get_post_meta( $rifa_id, '_tbp_cost_physical', true ) );
    if ( $cost_physical <= 0 ) {
        echo '<p class="tbp-status-error">Error: Costo físico de la rifa no configurado.</p>';
        wp_die();
    }

    $order_total = floatval( $order->get_total() );
    $rights = floor( $order_total / $cost_physical );
    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
    $pending = $rights - $delivered;

    ?>
    <div class="tbp-results-card" style="border: 1px solid #eee; padding: 15px; border-radius: 5px;">
        <h3><?php printf( __( 'Alumno: %s', 'tbp-actividades' ), $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ); ?></h3>
        <p><strong><?php _e( 'Estado del Pedido:', 'tbp-actividades' ); ?></strong> <mark style="background: #e1f5fe; padding: 2px 5px; border-radius: 3px;"><?php echo wc_get_order_status_name( $order->get_status() ); ?></mark></p>
        <p><strong><?php _e( 'Total Pedido:', 'tbp-actividades' ); ?></strong> <?php echo wc_price( $order_total ); ?></p>
        <p><strong><?php _e( 'Derechos Totales:', 'tbp-actividades' ); ?></strong> <?php echo $rights; ?> <?php _e( 'boletos', 'tbp-actividades' ); ?></p>
        <p><strong><?php _e( 'Entregados:', 'tbp-actividades' ); ?></strong> <?php echo $delivered; ?></p>
        <p><strong><?php _e( 'Pendientes:', 'tbp-actividades' ); ?></strong> <span style="font-size: 1.2em; color: <?php echo ($pending > 0) ? 'green' : 'red'; ?>"><?php echo $pending; ?></span></p>

        <?php if ( $pending > 0 ) : ?>
            <div style="margin-top: 15px;">
                <label for="tbp_deliver_qty"><?php _e( 'Cantidad a entregar ahora:', 'tbp-actividades' ); ?></label>
                <input type="number" id="tbp_deliver_qty" value="<?php echo $pending; ?>" max="<?php echo $pending; ?>" min="1" />
                <button id="tbp_btn_deliver" class="button button-success" data-order-id="<?php echo $order_id; ?>" data-rifa-id="<?php echo $rifa_id; ?>"><?php _e( 'Registrar Entrega', 'tbp-actividades' ); ?></button>
            </div>
        <?php else : ?>
            <p class="tbp-status-success"><?php _e( '¡Todos los boletos han sido entregados!', 'tbp-actividades' ); ?></p>
        <?php endif; ?>

        <hr style="margin: 20px 0; border: 0; border-top: 1px solid #eee;">
        
        <h4><?php _e( 'Control de Tómbola', 'tbp-actividades' ); ?></h4>
        <?php
        $tombola_count = intval( get_post_meta( $order_id, '_tbp_boletos_tombola', true ) );
        $can_receive_tombola = ( $delivered > 0 && ( $order->has_status( 'completed' ) || $order->has_status( 'processing' ) ) );
        $tombola_pending = $delivered - $tombola_count;
        ?>
        <p><strong><?php _e( 'En Tómbola:', 'tbp-actividades' ); ?></strong> <span style="font-size: 1.2em; color: #673ab7;"><?php echo $tombola_count; ?></span> / <?php echo $delivered; ?></p>
        
        <?php if ( ! $can_receive_tombola ) : ?>
            <p class="tbp-status-error"><?php _e( 'El pedido debe estar 100% liquidado y tener boletos entregados para habilitar tómbola.', 'tbp-actividades' ); ?></p>
        <?php elseif ( $tombola_pending <= 0 ) : ?>
            <p class="tbp-status-success"><?php _e( '¡Todos los boletos entregados ya están en tómbola!', 'tbp-actividades' ); ?></p>
        <?php else : ?>
            <div style="margin-top: 10px; background: #f3e5f5; padding: 10px; border-radius: 5px;">
                <label for="tbp_tombola_qty"><?php _e( 'Ingresar boletos a tómbola:', 'tbp-actividades' ); ?></label>
                <input type="number" id="tbp_tombola_qty" value="<?php echo $tombola_pending; ?>" max="<?php echo $tombola_pending; ?>" min="1" />
                <button id="tbp_btn_tombola" class="button button-primary" data-order-id="<?php echo $order_id; ?>" data-rifa-id="<?php echo $rifa_id; ?>" style="background: #673ab7; border-color: #5e35b1;"><?php _e( 'Registrar en Tómbola', 'tbp-actividades' ); ?></button>
            </div>
        <?php endif; ?>
    </div>
    <?php
    wp_die();
}
add_action( 'wp_ajax_tbp_actividades_search_order', 'tbp_actividades_search_order_ajax' );

/**
 * AJAX Deliver Tickets
 */
function tbp_actividades_deliver_tickets_ajax() {
    if ( ! tbp_actividades_user_is_staff() ) {
        wp_die( 'Acceso denegado' );
    }

    $order_id = intval( $_POST['order_id'] );
    $rifa_id = intval( $_POST['rifa_id'] );
    $quantity = intval( $_POST['quantity'] );

    if ( $quantity <= 0 ) wp_die( 'Cantidad inválida' );

    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';

    // 1. Log the delivery
    $wpdb->insert( $table_logs, array(
        'staff_id' => get_current_user_id(),
        'order_id' => $order_id,
        'rifa_id'  => $rifa_id,
        'amount'   => $quantity,
        'type'     => 'physical'
    ) );

    // 2. Update order meta
    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
    update_post_meta( $order_id, '_tbp_entregas_fisicas', $delivered + $quantity );

    // 3. Automated Messaging
    tbp_actividades_add_custom_order_note( $order_id, $quantity, 'physical' );

    echo '<p class="tbp-status-success">' . sprintf( __( 'Se han registrado %d boletos entregados correctamente.', 'tbp-actividades' ), $quantity ) . '</p>';
    echo '<button onclick="location.reload()" class="button">' . __( 'Volver', 'tbp-actividades' ) . '</button>';
    wp_die();
}
add_action( 'wp_ajax_tbp_actividades_deliver_tickets', 'tbp_actividades_deliver_tickets_ajax' );

/**
 * AJAX Receive Tombola
 */
function tbp_actividades_receive_tombola_ajax() {
    if ( ! tbp_actividades_user_is_staff() ) {
        wp_die( 'Acceso denegado' );
    }

    $order_id = intval( $_POST['order_id'] );
    $rifa_id  = intval( $_POST['rifa_id'] );
    $quantity = intval( $_POST['quantity'] );

    if ( $quantity <= 0 ) wp_die( 'Cantidad inválida' );

    $order = wc_get_order( $order_id );
    if ( ! $order ) wp_die( 'Pedido no encontrado' );

    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
    $current_tombola = intval( get_post_meta( $order_id, '_tbp_boletos_tombola', true ) );

    if ( ( $current_tombola + $quantity ) > $delivered ) {
        wp_die( 'No puedes ingresar más boletos de los que fueron entregados físicamente.' );
    }

    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';

    // 1. Log the tombola reception
    $wpdb->insert( $table_logs, array(
        'staff_id' => get_current_user_id(),
        'order_id' => $order_id,
        'rifa_id'  => $rifa_id,
        'amount'   => $quantity,
        'type'     => 'tombola'
    ) );

    // 2. Update order meta
    update_post_meta( $order_id, '_tbp_boletos_tombola', $current_tombola + $quantity );

    // 3. Automated Messaging (Custom Order Note)
    $msg = sprintf( 
        "¡Hola %s! 🎉 Confirmamos la recepción de %d boletos (talones) para la tómbola de tu rifa. ¡Ya están listos para el sorteo! 🎓✨\n\nAtte. El equipo de The Best Prom.",
        $order->get_billing_first_name(),
        $quantity
    );
    $order->add_order_note( $msg, 1 );

    echo '<p class="tbp-status-success" style="color: #673ab7; font-weight: bold;">' . sprintf( __( 'Se han registrado %d boletos en tómbola correctamente.', 'tbp-actividades' ), $quantity ) . '</p>';
    echo '<button onclick="location.reload()" class="button">' . __( 'Volver', 'tbp-actividades' ) . '</button>';
    wp_die();
}
add_action( 'wp_ajax_tbp_actividades_receive_tombola', 'tbp_actividades_receive_tombola_ajax' );

/**
 * Handle Deleting a Single Delivery Log
 */
function tbp_actividades_delete_single_log_ajax() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Permiso denegado.' );
    }

    if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'tbp_delete_log' ) ) {
        wp_send_json_error( 'Seguridad fallida.' );
    }

    $log_id = intval( $_POST['log_id'] );
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';

    $log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE id = %d LIMIT 1", $log_id ) );
    
    if ( ! $log ) {
        wp_send_json_error( 'Registro no encontrado.' );
    }

    $deleted = $wpdb->delete(
        $table_logs,
        array( 'id' => $log_id ),
        array( '%d' )
    );

    if ( $deleted ) {
        // Recalculate total physical deliveries for this order
        $order_id = $log->order_id;
        $total_remaining = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(amount) FROM $table_logs WHERE order_id = %d AND type = 'physical'", $order_id ) );
        
        $total_remaining = intval( $total_remaining );
        update_post_meta( $order_id, '_tbp_entregas_fisicas', $total_remaining );

        $order = wc_get_order( $order_id );
        if ( $order ) {
            $user = wp_get_current_user();
            $order->add_order_note( sprintf( __( 'Se borró manualmente un registro histórico de entrega de %d boletos físicos (Operador: %s).', 'tbp-actividades' ), intval($log->amount), $user->display_name ) );
        }

        wp_send_json_success( 'Registro eliminado con éxito.' );
    } else {
        wp_send_json_error( 'Error de base de datos al eliminar.' );
    }
}
add_action( 'wp_ajax_tbp_actividades_delete_single_log', 'tbp_actividades_delete_single_log_ajax' );

/**
 * Handle Resetting ALL Deliveries for an Order (from Reports)
 */
function tbp_actividades_reset_order_deliveries_ajax() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Permiso denegado.' );
    }

    if ( ! isset( $_POST['_ajax_nonce'] ) || ! wp_verify_nonce( $_POST['_ajax_nonce'], 'tbp_reset_deliveries' ) ) {
        wp_send_json_error( 'Seguridad fallida.' );
    }

    $order_id = intval( $_POST['order_id'] );
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';

    // Delete ALL physical logs for this order
    $deleted = $wpdb->delete(
        $table_logs,
        array( 'order_id' => $order_id, 'type' => 'physical' ),
        array( '%d', '%s' )
    );

    if ( $deleted !== false ) {
        // Reset the meta
        update_post_meta( $order_id, '_tbp_entregas_fisicas', 0 );

        $order = wc_get_order( $order_id );
        if ( $order ) {
            $user = wp_get_current_user();
            $order->add_order_note( sprintf( __( 'Se resetearon/vaciaron TODAS las entregas físicas de la base de datos para este pedido desde el reporte (Operador: %s).', 'tbp-actividades' ), $user->display_name ) );
        }

        wp_send_json_success( 'Entregas reseteadas con éxito.' );
    } else {
        wp_send_json_error( 'Error de base de datos al limpiar registros.' );
    }
}
add_action( 'wp_ajax_tbp_actividades_reset_order_deliveries', 'tbp_actividades_reset_order_deliveries_ajax' );
