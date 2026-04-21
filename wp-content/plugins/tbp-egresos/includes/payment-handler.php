<?php
/**
 * Payment Handling & Security for TBP Egresos
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Global Sync: Update PO total when a Payment is saved/edited externally
 */
add_action( 'save_post_tbp_pago', 'tbp_egresos_sync_po_on_pago_save', 10, 3 );
function tbp_egresos_sync_po_on_pago_save( $post_id, $post, $update ) {
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( $post->post_type !== 'tbp_pago' ) return;

    $po_id = get_post_meta( $post_id, '_tbp_po_id', true );
    if ( $po_id ) {
        tbp_egresos_update_po_paid_total( $po_id );
    }
}

/**
 * Add "GENERAR PAGO" button to PO edit screen
 */
add_action( 'post_submitbox_start', 'tbp_egresos_add_pago_button_to_po' );
function tbp_egresos_add_pago_button_to_po( $post ) {
    if ( $post->post_type !== 'tbp_orden_compra' ) return;
    
    $po_id = $post->ID;
    $autorizado = floatval( get_post_meta( $po_id, '_tbp_cantidad_autorizada', true ) );
    $pagado = floatval( get_post_meta( $po_id, '_tbp_total_pagado', true ) );
    
    if ( $autorizado <= 0 ) {
        echo '<div class="notice notice-warning inline"><p>No hay montos autorizados para pagar aún.</p></div>';
        return;
    }

    $url = admin_url( 'post-new.php?post_type=tbp_pago&po_id=' . $po_id );
    echo '<div style="margin-bottom: 10px;">';
    echo '<a href="' . esc_url( $url ) . '" class="button button-primary button-large" style="width:100%; text-align:center; background:#2271b1; border-color:#2271b1;">💰 GENERAR PAGO</a>';
    echo '</div>';
}

/**
 * Handle Pago pre-fill from URL
 */
add_action( 'load-post-new.php', 'tbp_egresos_prefill_pago_from_po' );
function tbp_egresos_prefill_pago_from_po() {
    if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === 'tbp_pago' && isset( $_GET['po_id'] ) ) {
        add_filter( 'default_title', function() {
            return 'Pago a PO #' . intval( $_GET['po_id'] ) . ' - ' . date('d/m/Y');
        } );
    }
}

/**
 * Metaboxes for Pago
 */
add_action( 'add_meta_boxes_tbp_pago', 'tbp_egresos_add_pago_meta_boxes' );
function tbp_egresos_add_pago_meta_boxes() {
    add_meta_box(
        'tbp_pago_details',
        __( 'Detalles de la Transacción', 'tbp-egresos' ),
        'tbp_egresos_pago_details_html',
        'tbp_pago',
        'normal',
        'high'
    );
}

function tbp_egresos_pago_details_html( $post ) {
    wp_nonce_field( 'tbp_pago_save', 'tbp_pago_nonce' );

    $po_id = isset( $_GET['po_id'] ) ? intval( $_GET['po_id'] ) : get_post_meta( $post->ID, '_tbp_po_id', true );
    $monto = get_post_meta( $post->ID, '_tbp_monto_pago', true );
    $forma_pago = get_post_meta( $post->ID, '_tbp_forma_pago', true );
    $concepto = get_post_meta( $post->ID, '_tbp_concepto_pago', true );
    
    $pos = get_posts( array( 'post_type' => 'tbp_orden_compra', 'posts_per_page' => -1 ) );

    ?>
    <table class="form-table">
        <tr>
            <th><label>Orden de Compra Relacionada</label></th>
            <td>
                <select name="tbp_po_id" id="tbp_po_id_selector">
                    <option value=""><?php _e( 'Seleccionar PO', 'tbp-egresos' ); ?></option>
                    <?php foreach ( $pos as $po ) : ?>
                        <option value="<?php echo $po->ID; ?>" <?php selected( $po_id, $po->ID ); ?>><?php echo $po->post_title; ?></option>
                    <?php endforeach; ?>
                </select>
                <div id="po-auth-info" style="margin-top: 5px; font-weight: bold; color: #2271b1;"></div>
            </td>
        </tr>
        <tr>
            <th><label>Monto a Pagar</label></th>
            <td><input type="number" step="0.01" name="tbp_monto_pago" value="<?php echo esc_attr( $monto ); ?>" class="regular-text" /></td>
        </tr>
        <tr>
            <th><label>Forma de Pago</label></th>
            <td>
                <select name="tbp_forma_pago">
                    <option value="Transferencia" <?php selected( $forma_pago, 'Transferencia' ); ?>>Transferencia</option>
                    <option value="Tarjeta" <?php selected( $forma_pago, 'Tarjeta' ); ?>>Tarjeta</option>
                    <option value="Efectivo" <?php selected( $forma_pago, 'Efectivo' ); ?>>Efectivo</option>
                    <option value="Intercambio" <?php selected( $forma_pago, 'Intercambio' ); ?>>Intercambio</option>
                </select>
            </td>
        </tr>
        <tr>
            <th><label>Concepto</label></th>
            <td><input type="text" name="tbp_concepto_pago" value="<?php echo esc_attr( $concepto ); ?>" class="large-text" /></td>
        </tr>
    </table>
    
    <h3>Evidencias y Repositorio Fiscal</h3>
    <p><em>(Sube los archivos necesarios en la Galería de Medios y pega el link abajo, o usaremos el uploader de WP en la siguiente fase)</em></p>
    <table class="form-table">
        <tr>
            <th><label>Comprobante Pago (JPG/PDF)</label></th>
            <td><input type="text" name="tbp_file_comprobante" value="<?php echo esc_attr( get_post_meta($post->ID, '_tbp_file_comprobante', true) ); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label>Factura PDF (Obligatorio)</label></th>
            <td><input type="text" name="tbp_file_factura_pdf" value="<?php echo esc_attr( get_post_meta($post->ID, '_tbp_file_factura_pdf', true) ); ?>" class="large-text" /></td>
        </tr>
        <tr>
            <th><label>Factura XML (Obligatorio)</label></th>
            <td><input type="text" name="tbp_file_factura_xml" value="<?php echo esc_attr( get_post_meta($post->ID, '_tbp_file_factura_xml', true) ); ?>" class="large-text" /></td>
        </tr>
    </table>

    <script>
        jQuery(document).ready(function($) {
            function checkPO() {
                var poId = $('#tbp_po_id_selector').val();
                if (!poId) return;
                // Simplified display of info if we could fetch it via AJAX or just inject it
                // For now, assume we have it or the user knows it.
            }
            $('#tbp_po_id_selector').on('change', checkPO);
            checkPO();
        });
    </script>
    <?php
}

/**
 * Save Pago with STRICT SECURITY RULE
 */
add_action( 'save_post_tbp_pago', 'tbp_egresos_save_pago_handler' );
function tbp_egresos_save_pago_handler( $post_id ) {
    if ( ! isset( $_POST['tbp_pago_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_pago_nonce'], 'tbp_pago_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    $po_id = intval( $_POST['tbp_po_id'] );
    $nuevo_monto = floatval( $_POST['tbp_monto_pago'] );

    if ( ! $po_id || $nuevo_monto <= 0 ) return;

    // Security Rule Check
    $autorizado = floatval( get_post_meta( $po_id, '_tbp_cantidad_autorizada', true ) );
    
    // Sum current payments for this PO (excluding this one if editing)
    global $wpdb;
    $current_paid = $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(pm.meta_value) 
        FROM {$wpdb->postmeta} pm
        JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = '_tbp_monto_pago'
        AND p.post_type = 'tbp_pago'
        AND p.post_status = 'publish'
        AND p.ID != %d
        AND p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tbp_po_id' AND meta_value = %d)
    ", $post_id, $po_id ) );

    $total_new = floatval( $current_paid ) + $nuevo_monto;

    if ( $total_new > $autorizado ) {
        // FAIL PROTECTION
        wp_die( "<h1>ERROR DE SEGURIDAD FINANCIERA</h1><p>El monto total de pagos ($" . number_format($total_new, 2) . ") superaría la cantidad autorizada de la PO (#$po_id: $" . number_format($autorizado, 2) . "). <br/><br/><strong>No se permite registrar este pago.</strong></p><p><a href='javascript:history.back()'>Regrear y corregir</a></p>" );
    }

    // Save Meta
    update_post_meta( $post_id, '_tbp_po_id', $po_id );
    update_post_meta( $post_id, '_tbp_monto_pago', $nuevo_monto );
    update_post_meta( $post_id, '_tbp_forma_pago', sanitize_text_field( $_POST['tbp_forma_pago'] ) );
    update_post_meta( $post_id, '_tbp_concepto_pago', sanitize_text_field( $_POST['tbp_concepto_pago'] ) );
    update_post_meta( $post_id, '_tbp_file_comprobante', sanitize_text_field( $_POST['tbp_file_comprobante'] ) );
    update_post_meta( $post_id, '_tbp_file_factura_pdf', sanitize_text_field( $_POST['tbp_file_factura_pdf'] ) );
    update_post_meta( $post_id, '_tbp_file_factura_xml', sanitize_text_field( $_POST['tbp_file_factura_xml'] ) );

    // Update PO Global Total Paid
    tbp_egresos_update_po_paid_total( $po_id );
}

/**
 * AJAX Handler to register a payment from the PO screen
 */
add_action( 'wp_ajax_tbp_egresos_register_payment', 'tbp_egresos_ajax_register_payment' );
add_action( 'wp_ajax_tbp_egresos_get_payment_data', 'tbp_egresos_ajax_get_payment_data' );
add_action( 'wp_ajax_tbp_egresos_update_payment', 'tbp_egresos_ajax_update_payment' );
add_action( 'wp_ajax_tbp_egresos_delete_payment', 'tbp_egresos_ajax_delete_payment' );
add_action( 'wp_ajax_tbp_egresos_sync_overdue_po', 'tbp_egresos_ajax_sync_overdue_po' );

/**
 * AJAX Handler: Get Payment Data for Editing
 */
function tbp_egresos_ajax_get_payment_data() {
    check_ajax_referer( 'tbp_egresos_payment_nonce', 'nonce' );
    $id = intval( $_POST['pago_id'] );
    
    $pago = array(
        'amount'   => get_post_meta( $id, '_tbp_monto', true ),
        'date'     => get_post_meta( $id, '_tbp_fecha_pago', true ),
        'method'   => get_post_meta( $id, '_tbp_forma_pago', true ),
        'ref'      => get_post_meta( $id, '_tbp_concepto_pago', true ),
        'file_url' => get_post_meta( $id, '_tbp_file_evidencia', true ),
    );
    
    wp_send_json_success( $pago );
}

/**
 * AJAX Handler: Update Payment
 */
function tbp_egresos_ajax_update_payment() {
    check_ajax_referer( 'tbp_egresos_payment_nonce', 'nonce' );
    
    $id = intval( $_POST['pago_id'] );
    $po_id = intval( $_POST['po_id'] );
    $amount = floatval( $_POST['amount'] );
    $date = sanitize_text_field( $_POST['date'] );
    $method = sanitize_text_field( $_POST['method'] );
    $ref = sanitize_textarea_field( $_POST['ref'] );
    $file_url = esc_url_raw( $_POST['file_url'] );

    // Security Rule Check (Total Excluding this payment + new amount)
    global $wpdb;
    $other_payments_total = $wpdb->get_var( $wpdb->prepare( "
        SELECT SUM(m.meta_value) 
        FROM {$wpdb->postmeta} m
        JOIN {$wpdb->posts} p ON m.post_id = p.ID
        WHERE m.meta_key = '_tbp_monto'
        AND p.post_type = 'tbp_pago'
        AND p.post_status = 'publish'
        AND p.ID != %d
        AND p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tbp_po_id' AND meta_value = %d)
    ", $id, $po_id ) );

    $autorizado = floatval( get_post_meta( $po_id, '_tbp_cantidad_autorizada', true ) );
    
    if ( ( floatval($other_payments_total) + $amount ) > ( $autorizado + 0.10 ) ) {
        wp_send_json_error( '🚫 SEGURIDAD FINANCIERA: El nuevo monto excede el saldo autorizado.' );
    }

    // Update Meta
    update_post_meta( $id, '_tbp_monto', $amount );
    update_post_meta( $id, '_tbp_monto_pago', $amount );
    update_post_meta( $id, '_tbp_fecha_pago', $date );
    update_post_meta( $id, '_tbp_forma_pago', $method );
    update_post_meta( $id, '_tbp_concepto_pago', $ref );
    update_post_meta( $id, '_tbp_file_evidencia', $file_url );

    // Update PO Global Total
    tbp_egresos_update_po_paid_total( $po_id );

    wp_send_json_success( 'Pago actualizado correctamente.' );
}

/**
 * AJAX Handler: Delete Payment
 */
function tbp_egresos_ajax_delete_payment() {
    check_ajax_referer( 'tbp_egresos_payment_nonce', 'nonce' );
    
    $id = intval( $_POST['pago_id'] );
    $po_id = get_post_meta( $id, '_tbp_po_id', true );

    if ( wp_trash_post( $id ) ) {
        tbp_egresos_update_po_paid_total( $po_id );
        wp_send_json_success( 'Pago eliminado correctamente.' );
    }
    
    wp_send_json_error( 'No se pudo eliminar el pago.' );
}

/**
 * AJAX Handler for Syncing Overdue Balance
 */
function tbp_egresos_ajax_sync_overdue_po() {
    check_ajax_referer( 'tbp_egresos_sync_overdue_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Permisos insuficientes.' );
    }

    $po_id = isset( $_POST['po_id'] ) ? intval( $_POST['po_id'] ) : 0;
    if ( ! $po_id ) wp_send_json_error( 'ID de PO inválido.' );

    $new_auth = tbp_egresos_recompute_manual_po_autorizacion( $po_id );

    wp_send_json_success( array( 'new_auth' => $new_auth ) );
}

function tbp_egresos_ajax_register_payment() {
    check_ajax_referer( 'tbp_egresos_payment_nonce', 'nonce' );
    
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_send_json_error( 'Permiso denegado.' );
    }

    $po_id = intval( $_POST['po_id'] );
    $amount = floatval( $_POST['amount'] );
    $date = sanitize_text_field( $_POST['date'] );
    $method = sanitize_text_field( $_POST['method'] );
    $ref = sanitize_textarea_field( $_POST['ref'] );
    $file_url = esc_url_raw( $_POST['file_url'] );
    
    if ( ! $po_id || $amount <= 0 ) {
        wp_send_json_error( 'Datos de pago inválidos.' );
    }

    // Server-Side Security Check
    $balance = tbp_egresos_get_po_balance( $po_id );
    $forced_by = isset( $_POST['forced_by'] ) ? intval( $_POST['forced_by'] ) : 0;

    if ( $amount > ( $balance + 0.10 ) && ! $forced_by ) { 
        wp_send_json_error( '🚫 SEGURIDAD FINANCIERA: El monto excede el saldo autorizado.' );
    }

    // Create the Pago post
    $pago_id = wp_insert_post( array(
        'post_type'   => 'tbp_pago',
        'post_status' => 'publish',
        'post_title'  => 'Pago a PO #' . $po_id . ' - ' . date('d/m/Y', strtotime($date)) . ( $forced_by ? ' [AUTORIZACIÓN ESPECIAL]' : '' ),
    ) );

    if ( is_wp_error( $pago_id ) ) {
        wp_send_json_error( 'Error al crear el registro de pago.' );
    }

    // Save Meta
    update_post_meta( $pago_id, '_tbp_po_id', $po_id );
    update_post_meta( $pago_id, '_tbp_monto', $amount );
    update_post_meta( $pago_id, '_tbp_monto_pago', $amount );
    update_post_meta( $pago_id, '_tbp_fecha_pago', $date );
    update_post_meta( $pago_id, '_tbp_forma_pago', $method );
    update_post_meta( $pago_id, '_tbp_concepto_pago', $ref );
    update_post_meta( $pago_id, '_tbp_file_evidencia', $file_url );

    if ( $forced_by ) {
        update_post_meta( $pago_id, '_tbp_forced_by', $forced_by );
        update_post_meta( $pago_id, '_tbp_signature_date', current_time('mysql') );
    }

    // Trigger PO recompute of paid total
    tbp_egresos_update_po_paid_total( $po_id );

    wp_send_json_success( 'Pago registrado con éxito.' );
}

/**
 * AJAX Handler: Verify Admin Signature (Digital re-auth)
 */
add_action( 'wp_ajax_tbp_egresos_verify_signature', 'tbp_egresos_ajax_verify_signature' );
function tbp_egresos_ajax_verify_signature() {
    check_ajax_referer( 'tbp_egresos_payment_nonce', 'nonce' );
    
    $user_login = sanitize_text_field( $_POST['user'] );
    $user_pass = $_POST['pass'];
    
    $user = wp_authenticate( $user_login, $user_pass );
    
    if ( is_wp_error( $user ) ) {
        wp_send_json_error( 'Credenciales incorrectas. Firma inválida.' );
    }
    
    if ( ! user_can( $user, 'manage_options' ) ) {
        wp_send_json_error( 'El usuario no tiene rango administrativo para firmar.' );
    }
    
    wp_send_json_success( array(
        'user_id'   => $user->ID,
        'user_name' => $user->display_name
    ) );
}

function tbp_egresos_update_po_paid_total( $po_id ) {
    $total = tbp_egresos_get_po_total_paid( $po_id );
    update_post_meta( $po_id, '_tbp_total_pagado', $total );
}
