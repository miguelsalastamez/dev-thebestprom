<?php
/**
 * Custom Post Type: tbp_rifas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function tbp_actividades_register_cpt_rifas() {
    $labels = array(
        'name'               => _x( 'Rifas', 'post type general name', 'tbp-actividades' ),
        'singular_name'      => _x( 'Rifa', 'post type singular name', 'tbp-actividades' ),
        'menu_name'          => _x( 'Rifas', 'admin menu', 'tbp-actividades' ),
        'add_new'            => _x( 'Crear Rifa', 'rifa', 'tbp-actividades' ),
        'add_new_item'       => __( 'Nueva Rifa', 'tbp-actividades' ),
        'edit_item'          => __( 'Editar Rifa', 'tbp-actividades' ),
        'new_item'           => __( 'Nueva Rifa', 'tbp-actividades' ),
        'all_items'          => __( 'Todas las Rifas', 'tbp-actividades' ),
        'view_item'          => __( 'Ver Rifa', 'tbp-actividades' ),
        'search_items'       => __( 'Buscar Rifas', 'tbp-actividades' ),
        'not_found'          => __( 'No se encontraron Rifas', 'tbp-actividades' ),
        'not_found_in_trash' => __( 'No se encontraron Rifas en la papelera', 'tbp-actividades' ),
        'parent_item_colon'  => '',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => false, // Handled in main.php
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'tbp-rifas' ),
        'capability_type'    => 'post',
        'has_archive'        => false,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array( 'title' ),
    );

    register_post_type( 'tbp_rifas', $args );
}
add_action( 'init', 'tbp_actividades_register_cpt_rifas' );

function tbp_actividades_add_rifa_meta_boxes() {
    add_meta_box(
        'tbp_rifa_settings',
        __( 'Configuración de la Rifa', 'tbp-actividades' ),
        'tbp_actividades_rifa_settings_callback',
        'tbp_rifas',
        'normal',
        'high'
    );

    add_meta_box(
        'tbp_rifa_import',
        __( 'Carga Masiva de Entregas (Histórico)', 'tbp-actividades' ),
        'tbp_actividades_rifa_import_callback',
        'tbp_rifas',
        'side',
        'default'
    );
}
add_action( 'add_meta_boxes', 'tbp_actividades_add_rifa_meta_boxes' );

/**
 * HTML for Batch Import Meta Box
 */
function tbp_actividades_rifa_import_callback( $post ) {
    ?>
    <div class="tbp-batch-import-wrapper">
        <p><small><?php _e( 'Pega aquí los datos de tu Excel (Pedido y Cantidad). Formato: uno por línea.', 'tbp-actividades' ); ?></small></p>
        <textarea name="tbp_batch_import_data" rows="8" style="width:100%; font-family:monospace; font-size:12px;" placeholder="28532, 10&#10;28535, 5"></textarea>
        <p class="description"><?php _e( 'Ejemplo: 28532, 35 (Orden, Cantidad)', 'tbp-actividades' ); ?></p>
        <hr>
        <p style="color:red; font-size:11px;">⚠️ <?php _e( 'Esta acción registrará las entregas inmediatamente al guardar la Rifa.', 'tbp-actividades' ); ?></p>
    </div>
    <?php
}

function tbp_actividades_rifa_settings_callback( $post ) {
    wp_nonce_field( 'tbp_rifa_settings_save', 'tbp_rifa_settings_nonce' );

    $event_id = get_post_meta( $post->ID, '_tbp_event_id', true );
    $local_raffle_id = get_post_meta( $post->ID, '_tbp_local_raffle_id', true );
    $eventbrite_id = get_post_meta( $post->ID, '_tbp_eventbrite_id', true );
    $cost_physical = get_post_meta( $post->ID, '_tbp_cost_physical', true );
    $cost_virtual = get_post_meta( $post->ID, '_tbp_cost_virtual', true );

    // Get Tribe Events for selector
    $events = get_posts( array(
        'post_type' => 'tribe_events',
        'posts_per_page' => -1,
        'post_status' => array( 'publish', 'private' )
    ) );

    ?>
    <table class="form-table">
        <tr>
            <th><label for="tbp_event_id"><?php _e( 'Evento de Graduación (Beneficiado)', 'tbp-actividades' ); ?></label></th>
            <td>
                <select id="tbp_event_id" name="tbp_event_id">
                    <option value=""><?php _e( 'Seleccionar Evento...', 'tbp-actividades' ); ?></option>
                    <?php foreach ( $events as $event ) : ?>
                        <option value="<?php echo $event->ID; ?>" <?php selected( $event_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?> (ID: <?php echo $event->ID; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e( 'A este evento pertenecen los alumnos que recibirán el crédito.', 'tbp-actividades' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="tbp_local_raffle_id"><?php _e( 'Evento de Venta Local (Rifa en Web)', 'tbp-actividades' ); ?></label></th>
            <td>
                <select id="tbp_local_raffle_id" name="tbp_local_raffle_id">
                    <option value=""><?php _e( 'Seleccionar Evento de Rifa...', 'tbp-actividades' ); ?></option>
                    <?php foreach ( $events as $event ) : ?>
                        <option value="<?php echo $event->ID; ?>" <?php selected( $local_raffle_id, $event->ID ); ?>><?php echo esc_html( $event->post_title ); ?> (ID: <?php echo $event->ID; ?>)</option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e( 'Si vendes boletos en esta web, selecciona el evento que representa la rifa online.', 'tbp-actividades' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="tbp_eventbrite_id"><?php _e( 'ID Eventbrite (Opcional)', 'tbp-actividades' ); ?></label></th>
            <td>
                <input type="text" id="tbp_eventbrite_id" name="tbp_eventbrite_id" value="<?php echo esc_attr( $eventbrite_id ); ?>" class="regular-text" />
                <p class="description"><?php _e( 'Solo si usas Eventbrite como "espejo" externo. Es el ID numérico de la URL de Eventbrite.', 'tbp-actividades' ); ?></p>
            </td>
        </tr>
        <tr>
            <th><label for="tbp_cost_physical"><?php _e( 'Costo Boleto Físico (Rifas para pagar paquete)', 'tbp-actividades' ); ?></label></th>
            <td><input type="number" step="0.01" id="tbp_cost_physical" name="tbp_cost_physical" value="<?php echo esc_attr( $cost_physical ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label for="tbp_cost_virtual"><?php _e( 'Costo Boleto Virtual (Crédito Neto)', 'tbp-actividades' ); ?></label></th>
            <td><input type="number" step="0.01" id="tbp_cost_virtual" name="tbp_cost_virtual" value="<?php echo esc_attr( $cost_virtual ); ?>" class="regular-text" /></td>
        </tr>
    </table>
    <?php
}

/**
 * Save Meta Box Data
 */
function tbp_actividades_save_rifa_settings( $post_id ) {
    if ( ! isset( $_POST['tbp_rifa_settings_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_rifa_settings_nonce'], 'tbp_rifa_settings_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( isset( $_POST['tbp_event_id'] ) ) {
        update_post_meta( $post_id, '_tbp_event_id', sanitize_text_field( $_POST['tbp_event_id'] ) );
    }
    if ( isset( $_POST['tbp_local_raffle_id'] ) ) {
        update_post_meta( $post_id, '_tbp_local_raffle_id', sanitize_text_field( $_POST['tbp_local_raffle_id'] ) );
    }
    if ( isset( $_POST['tbp_eventbrite_id'] ) ) {
        update_post_meta( $post_id, '_tbp_eventbrite_id', sanitize_text_field( $_POST['tbp_eventbrite_id'] ) );
    }
    if ( isset( $_POST['tbp_cost_physical'] ) ) {
        update_post_meta( $post_id, '_tbp_cost_physical', sanitize_text_field( $_POST['tbp_cost_physical'] ) );
    }
    if ( isset( $_POST['tbp_cost_virtual'] ) ) {
        update_post_meta( $post_id, '_tbp_cost_virtual', sanitize_text_field( $_POST['tbp_cost_virtual'] ) );
    }

    // --- BATCH IMPORT LOGIC ---
    if ( ! empty( $_POST['tbp_batch_import_data'] ) ) {
        $import_data = sanitize_textarea_field( $_POST['tbp_batch_import_data'] );
        $lines = explode( "\n", str_replace( "\r", "", $import_data ) );
        $success_count = 0;
        
        foreach ( $lines as $line ) {
            $line = trim($line);
            if ( empty($line) ) continue;

            $parts = preg_split( '/[\s,]+/', $line );
            if ( count( $parts ) >= 2 ) {
                $order_id = intval( $parts[0] );
                $qty = intval( $parts[1] );
                
                if ( $order_id > 0 && $qty > 0 ) {
                    global $wpdb;
                    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
                    
                    // 1. Log Delivery
                    $wpdb->insert( $table_logs, array(
                        'staff_id' => get_current_user_id(),
                        'order_id' => $order_id,
                        'rifa_id'  => $post_id,
                        'amount'   => $qty
                    ) );

                    // 2. Update Order Meta
                    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
                    update_post_meta( $order_id, '_tbp_entregas_fisicas', $delivered + $qty );
                    
                    // 3. Automated Note
                    if ( function_exists( 'tbp_actividades_add_custom_order_note' ) ) {
                        tbp_actividades_add_custom_order_note( $order_id, $qty, 'physical' );
                    }
                    $success_count++;
                }
            }
        }

        if ( $success_count > 0 ) {
            set_transient( 'tbp_import_success_' . get_current_user_id(), $success_count, 30 );
        }
    }
}
add_action( 'save_post_tbp_rifas', 'tbp_actividades_save_rifa_settings' );

/**
 * Admin Notice for Import
 */
add_action( 'admin_notices', 'tbp_actividades_import_admin_notice' );
function tbp_actividades_import_admin_notice() {
    $count = get_transient( 'tbp_import_success_' . get_current_user_id() );
    if ( $count ) {
        delete_transient( 'tbp_import_success_' . get_current_user_id() );
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php printf( __( '✅ ¡Éxito! Se han importado %d entregas de boletos correctamente.', 'tbp-actividades' ), $count ); ?></p>
        </div>
        <?php
    }
}
