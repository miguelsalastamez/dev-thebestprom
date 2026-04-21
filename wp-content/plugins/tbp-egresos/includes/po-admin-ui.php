<?php
/**
 * PO Admin UI & Meta Boxes
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'add_meta_boxes_tbp_orden_compra', 'tbp_egresos_po_admin_meta_boxes' );

function tbp_egresos_po_admin_meta_boxes() {
    // Remove default title if we want, but we'll keep it for now
    
    add_meta_box(
        'tbp_po_details',
        __( 'Detalles de la Orden de Compra', 'tbp-egresos' ),
        'tbp_egresos_po_details_html',
        'tbp_orden_compra',
        'normal',
        'high'
    );
}

/**
 * Add Sync Button to the PO list table
 */
add_action( 'manage_posts_extra_tablenav', 'tbp_egresos_add_sync_button_to_list' );
function tbp_egresos_add_sync_button_to_list( $which ) {
    global $post_type;
    if ( $post_type !== 'tbp_orden_compra' || $which !== 'top' ) return;

    $url = wp_nonce_url( add_query_arg( 'tbp_sync_pos', '1' ), 'tbp_sync_pos_nonce' );
    echo '<div class="alignleft actions"><a href="' . esc_url( $url ) . '" class="button button-primary" style="background:#2271b1 !important; border-color:#2271b1 !important;">🔎 Sincronizar Historial de Ventas</a></div>';
}

/**
 * Handle Sync Action
 */
add_action( 'admin_init', 'tbp_egresos_handle_sync_action' );
function tbp_egresos_handle_sync_action() {
    if ( isset( $_GET['tbp_sync_pos'] ) && check_admin_referer( 'tbp_sync_pos_nonce' ) ) {
        if ( ! current_user_can( 'manage_woocommerce' ) ) return;

        $count = tbp_egresos_full_sync_retroactive();
        
        add_settings_error( 
            'tbp_egresos_sync', 
            'sync_done', 
            sprintf( __( 'Sincronización completada. Se han procesado %d órdenes de compra.', 'tbp-egresos' ), $count ), 
            'success' 
        );
        
        set_transient( 'tbp_egresos_sync_msg', true, 30 );
        wp_redirect( remove_query_arg( array( 'tbp_sync_pos', '_wpnonce' ) ) );
        exit;
    }
}

/**
 * Show Sync Result Notice
 */
add_action( 'admin_notices', 'tbp_egresos_sync_admin_notice' );
function tbp_egresos_sync_admin_notice() {
    if ( get_transient( 'tbp_egresos_sync_msg' ) ) {
        delete_transient( 'tbp_egresos_sync_msg' );
        settings_errors( 'tbp_egresos_sync' );
    }
}

function tbp_egresos_po_details_html( $post ) {
    wp_nonce_field( 'tbp_po_save', 'tbp_po_nonce' );

    $event_id = get_post_meta( $post->ID, '_tbp_event_id', true );
    $proveedor_id = get_post_meta( $post->ID, '_tbp_proveedor_id', true );
    $expiry_alert = get_post_meta( $post->ID, '_tbp_expiry_alert', true );
    
    // Display Expiry Alert if exists
    if ( $expiry_alert ) {
        echo '<div style="background: #fff5f5; border-left: 4px solid #d32f2f; padding: 15px; margin: 10px 0 20px 0; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">';
        echo '<strong style="color: #d32f2f; font-size: 14px;">🚨 ' . esc_html( $expiry_alert ) . '</strong>';
        echo '</div>';
    }
    
    $proyectada = get_post_meta( $post->ID, '_tbp_cantidad_proyectada', true ) ?: 0;
    $autorizada = get_post_meta( $post->ID, '_tbp_cantidad_autorizada', true ) ?: 0;
    $pagado = tbp_egresos_get_po_total_paid( $post->ID );
    $saldo = tbp_egresos_get_po_balance( $post->ID );

    // Get History of payments for this PO
    global $wpdb;
    $payments = $wpdb->get_results( $wpdb->prepare( "
        SELECT p.ID, p.post_title, 
               m_monto.meta_value as monto, 
               m_fecha.meta_value as fecha, 
               m_forma.meta_value as forma, 
               m_ref.meta_value as concepto,
               m_file.meta_value as file_url
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} m_monto ON p.ID = m_monto.post_id AND m_monto.meta_key = '_tbp_monto'
        LEFT JOIN {$wpdb->postmeta} m_fecha ON p.ID = m_fecha.post_id AND m_fecha.meta_key = '_tbp_fecha_pago'
        LEFT JOIN {$wpdb->postmeta} m_forma ON p.ID = m_forma.post_id AND m_forma.meta_key = '_tbp_forma_pago'
        LEFT JOIN {$wpdb->postmeta} m_ref ON p.ID = m_ref.post_id AND m_ref.meta_key = '_tbp_concepto_pago'
        LEFT JOIN {$wpdb->postmeta} m_file ON p.ID = m_file.post_id AND m_file.meta_key = '_tbp_file_evidencia'
        WHERE p.post_type = 'tbp_pago' 
        AND p.post_status = 'publish'
        AND p.ID IN (SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tbp_po_id' AND meta_value = %d)
        ORDER BY p.ID DESC
    ", $post->ID ) );

    $event_title = $event_id ? get_the_title( $event_id ) : 'No asignado';
    $proveedor_title = $proveedor_id ? get_the_title( $proveedor_id ) : 'No asignado';

    wp_enqueue_media(); // For file upload
    ?>
    <style>
        .tbp-po-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; padding: 10px; }
        .tbp-po-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; padding: 10px; }
        .tbp-po-field { margin-bottom: 15px; }
        .tbp-po-field label { display: block; font-weight: bold; margin-bottom: 5px; color: #555; text-transform: uppercase; font-size: 11px; }
        .tbp-po-value { font-size: 16px; padding: 10px; background: #f8f9fa; border: 1px solid #ddd; border-radius: 4px; display: block; }
        .tbp-po-total-box { padding: 12px; border-radius: 6px; text-align: center; }
        .tbp-po-proyectada { background: #fff8e1; border: 1px solid #ffe082; }
        .tbp-po-autorizada { background: #e3f2fd; border: 1px solid #bbdefb; }
        .tbp-po-saldo { background: #e8f5e9; border: 1px solid #a5d6a7; }
        .tbp-po-amount { font-size: 22px; font-weight: bold; display: block; margin-top: 5px; }
        .tbp-po-actions { margin-top: 20px; padding: 20px; border-top: 1px solid #eee; text-align: center; }
        .tbp-payment-form { background: #f9f9f9; padding: 15px; border: 1px solid #eee; border-radius: 6px; margin-top: 15px; text-align: left; }
        
        .tbp-history-table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 12px; }
        .tbp-history-table th, .tbp-history-table td { border: 1px solid #ccc; padding: 8px; text-align: left; }
        .tbp-history-table th { background: #f0f0f0; text-transform: uppercase; font-weight: bold; }
        .tbp-history-table .total-row { font-weight: bold; background: #fafafa; }
        .tbp-btn-social { text-decoration: none; font-size: 10px; display: block; margin-bottom: 3px; color: #2271b1; }
        .tbp-btn-social:hover { text-decoration: underline; }
        .tbp-btn-social:hover { text-decoration: underline; }

        /* Signature Modal Styles */
        .tbp-signature-modal { display:none; position:fixed; z-index:99999; left:0; top:0; width:100%; height:100%; background:rgba(0,0,0,0.7); backdrop-filter: blur(4px); }
        .tbp-signature-content { background:#fff; margin:10% auto; padding:30px; border-radius:12px; width:100%; max-width:400px; box-shadow:0 10px 30px rgba(0,0,0,0.3); border:1px solid #ddd; position:relative; animation: tbpFadeIn 0.3s ease-out; }
        @keyframes tbpFadeIn { from { opacity:0; transform: translateY(-20px); } to { opacity:1; transform: translateY(0); } }
        .tbp-signature-header { text-align:center; margin-bottom:20px; }
        .tbp-signature-header i { font-size:40px; color:#d32f2f; margin-bottom:10px; display:block; }
        .tbp-signature-header h2 { margin:0; font-size:20px; color:#333; }
        .tbp-signature-body label { display:block; margin-bottom:5px; font-weight:bold; font-size:12px; color:#666; }
        .tbp-signature-body input { width:100%; margin-bottom:15px; padding:10px; border:1px solid #ccc; border-radius:4px; font-size:14px; }
        .tbp-signature-footer { display:flex; gap:10px; justify-content:flex-end; margin-top:10px; }
        .tbp-signature-footer .button { padding:8px 20px; }
    </style>

    <!-- Digital Signature Modal -->
    <div id="tbp-signature-modal" class="tbp-signature-modal">
        <div class="tbp-signature-content">
            <div class="tbp-signature-header">
                <span style="font-size:40px; display:block; margin-bottom:10px;">🛡️</span>
                <h2>FIRMA DIGITAL REQUERIDA</h2>
                <p style="font-size:13px; color:#666;">Se requiere autorización del Administrador General para exceder el límite financiero.</p>
            </div>
            <div class="tbp-signature-body">
                <label>Usuario Administrador</label>
                <input type="text" id="tbp_sig_user" placeholder="Admin username...">
                <label>Contraseña de Seguridad</label>
                <input type="password" id="tbp_sig_pass" placeholder="••••••••">
            </div>
            <div id="tbp-sig-msg" style="margin-bottom:15px; font-size:12px; color:#d32f2f; display:none; text-align:center; padding:8px; background:#fff1f0; border-radius:4px;"></div>
            <div class="tbp-signature-footer">
                <button type="button" class="button" onclick="jQuery('#tbp-signature-modal').hide();">Cancelar</button>
                <button type="button" class="button button-primary" id="tbp-btn-confirm-signature" style="background:#d32f2f !important; border-color:#b71c1c !important;">✍️ Firmar y Autorizar</button>
            </div>
        </div>
    </div>

    <div class="tbp-po-grid">
        <div class="tbp-po-field">
            <label>Evento (ID: <?php echo $event_id; ?>)</label>
            <div class="tbp-po-value">
                <?php if ( $event_id ) : ?>
                    <?php echo esc_html( $event_title ); ?>
                <?php else : ?>
                    <span style="color:#d32f2f; font-weight:bold;">CUENTA GENERAL / OPERACIÓN</span>
                <?php endif; ?>
            </div>
        </div>
        <div class="tbp-po-field">
            <label>Proveedor (ID: <?php echo $proveedor_id; ?>)</label>
            <div class="tbp-po-value" style="padding: 0; background: transparent; border: none;">
                <select name="tbp_proveedor_id" class="widefat" style="height: 48px; font-size: 16px;">
                    <option value="0">-- Seleccionar Proveedor --</option>
                    <?php
                    $proveedores = get_posts(array('post_type' => 'tbp_proveedor', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC'));
                    foreach ($proveedores as $prov) {
                        printf('<option value="%d" %s>%s</option>', 
                            $prov->ID, 
                            selected($proveedor_id, $prov->ID, false), 
                            esc_html($prov->post_title)
                        );
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <?php if ( ! $event_id ) : ?>
        <!-- CONFIGURACIÓN ADMINISTRATIVA (Sólo si no hay evento) -->
        <div style="background: #fff; border: 1px solid #ccd0d4; padding: 15px; margin: 10px; border-radius: 4px;">
            <h4 style="margin-top:0; color:#2271b1; border-bottom: 1px solid #eee; padding-bottom: 10px;">📋 Configuración de Gasto Operativo</h4>
            <div class="tbp-po-grid-3">
                <div class="tbp-po-field">
                    <label>Categoría</label>
                    <select name="tbp_categoria" class="widefat">
                        <option value="Administrativo" <?php selected(get_post_meta($post->ID, '_tbp_categoria', true), 'Administrativo'); ?>>Administrativo</option>
                        <option value="Nómina" <?php selected(get_post_meta($post->ID, '_tbp_categoria', true), 'Nómina'); ?>>Nómina</option>
                        <option value="Marketing" <?php selected(get_post_meta($post->ID, '_tbp_categoria', true), 'Marketing'); ?>>Marketing</option>
                        <option value="Servicios Públicos" <?php selected(get_post_meta($post->ID, '_tbp_categoria', true), 'Servicios Públicos'); ?>>Servicios Públicos</option>
                    </select>
                </div>
                <div class="tbp-po-field">
                    <label>Modo Presupuesto</label>
                    <select name="tbp_po_mode" class="widefat">
                        <option value="Fijo" <?php selected(get_post_meta($post->ID, '_tbp_po_mode', true), 'Fijo'); ?>>Tope Fijo (Reseteo)</option>
                        <option value="Acumulable" <?php selected(get_post_meta($post->ID, '_tbp_po_mode', true), 'Acumulable'); ?>>Acumulable (Bolsa)</option>
                        <option value="Manual" <?php selected(get_post_meta($post->ID, '_tbp_po_mode', true), 'Manual'); ?>>Variable (Manual)</option>
                    </select>
                </div>
                <div class="tbp-po-field">
                    <label>Monto Fijo Periodo ($)</label>
                    <input type="number" step="0.01" name="tbp_monto_fijo" value="<?php echo esc_attr(get_post_meta($post->ID, '_tbp_monto_fijo', true)); ?>" class="widefat">
                </div>
            </div>
            <div class="tbp-po-grid-3">
                <div class="tbp-po-field">
                    <label>Frecuencia de Renovación</label>
                    <select name="tbp_frecuencia" class="widefat">
                        <option value="Único" <?php selected(get_post_meta($post->ID, '_tbp_frecuencia', true), 'Único'); ?>>Pago Único / No recurrente</option>
                        <option value="Semanal" <?php selected(get_post_meta($post->ID, '_tbp_frecuencia', true), 'Semanal'); ?>>Semanal</option>
                        <option value="Mensual" <?php selected(get_post_meta($post->ID, '_tbp_frecuencia', true), 'Mensual'); ?>>Mensual</option>
                        <option value="Bimestral" <?php selected(get_post_meta($post->ID, '_tbp_frecuencia', true), 'Bimestral'); ?>>Bimestral (Luz/Agua)</option>
                        <option value="Anual" <?php selected(get_post_meta($post->ID, '_tbp_frecuencia', true), 'Anual'); ?>>Anual</option>
                    </select>
                </div>
                <div class="tbp-po-field">
                    <label>Inicio Contrato</label>
                    <input type="date" name="tbp_contract_start" value="<?php echo esc_attr(get_post_meta($post->ID, '_tbp_contract_start', true)); ?>" class="widefat">
                </div>
                <div class="tbp-po-field" style="border: 2px solid #ffcc80; border-radius: 4px; padding: 5px; background: #fffde7;">
                    <label style="color: #ef6c00;">Vencimiento Contrato</label>
                    <input type="date" name="tbp_contract_end" value="<?php echo esc_attr(get_post_meta($post->ID, '_tbp_contract_end', true)); ?>" class="widefat">
                </div>
            </div>
            <div class="tbp-po-grid">
                <div class="tbp-po-field">
                    <label>Próxima Renovación de Saldo</label>
                    <input type="date" name="tbp_next_renewal" value="<?php echo esc_attr(get_post_meta($post->ID, '_tbp_next_renewal', true)); ?>" class="widefat">
                    <small>El Cron Job actualizará el saldo en esta fecha.</small>
                </div>
                <div class="tbp-po-field">
                    <label>Incremento Previsto (%)</label>
                    <input type="number" step="0.1" name="tbp_incremento_pct" value="<?php echo esc_attr(get_post_meta($post->ID, '_tbp_incremento_pct', true)); ?>" class="widefat" placeholder="Ej: 6%">
                    <small>Se aplicará al vencer el contrato.</small>
                </div>
            </div>
            
            <div style="margin-top: 15px; padding: 10px; background: #f0f6fb; border-radius: 4px; border: 1px solid #c3d4e5;">
                <button type="button" id="tbp-btn-sync-overdue" class="button button-secondary" style="background: #2271b1 !important; color: white !important; border: none !important;">
                    🔄 Poner al día (Cargar Periodos Vencidos)
                </button>
                <p style="font-size: 11px; margin: 5px 0 0 0; color: #666;">
                    * Usa este botón si el contrato empezó meses atrás y necesitas registrar pagos antiguos. Autorizará todos los periodos desde la Fecha de Inicio hasta hoy.
                </p>
            </div>
        </div>
<?php endif; ?>

    <?php
    $piezas_proj = get_post_meta( $post->ID, '_tbp_piezas_proyectadas', true );
    $piezas_auth = get_post_meta( $post->ID, '_tbp_piezas_autorizadas', true );
    ?>
    <div class="tbp-po-grid-3">
        <div class="tbp-po-total-box tbp-po-proyectada">
            <label>Proyectada</label>
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #856404;">PIEZAS: <?php echo number_format(floatval($piezas_proj), 0); ?></div>
            <span class="tbp-po-amount"><?php echo wc_price( $proyectada ); ?></span>
            <small style="color:#666; font-size: 10px;">Inc: Parcial, Tarjeta, Comp.</small>
        </div>
        <div class="tbp-po-total-box tbp-po-autorizada">
            <label>Autorizada</label>
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #145c91;">PIEZAS: <?php echo number_format(floatval($piezas_auth), 0); ?></div>
            <span class="tbp-po-amount" style="color: #2271b1;"><?php echo wc_price( $autorizada ); ?></span>
            <small style="color:#666; font-size: 10px;">Inc: Tarjeta, Comp.</small>
        </div>
        <div class="tbp-po-total-box tbp-po-saldo">
            <label>Saldo por Pagar</label>
            <div style="font-size: 11px; font-weight: bold; margin-bottom: 5px; color: #155724;">&nbsp;</div>
            <span class="tbp-po-amount" style="color: #2e7d32;"><?php echo wc_price( $saldo ); ?></span>
            <small style="color:#666; font-size: 10px;">Pagado: <?php echo wc_price($pagado); ?></small>
        </div>
    </div>

    <div class="tbp-po-actions" style="border-bottom: 2px solid #ccc; padding-bottom: 40px;">
        <button type="button" class="button button-primary" id="tbp-btn-toggle-payment" style="padding: 8px 30px; font-size: 15px; background:#2271b1;">💰 REGISTRAR PAGO</button>
        
        <div id="tbp-payment-panel" style="display:none;" class="tbp-payment-form">
            <h3 style="margin-top:0; border-bottom: 1px solid #ddd; padding-bottom: 10px;">📝 Registrar / Editar Desembolso</h3>
            <input type="hidden" id="tbp_edit_pago_id" value="0">
            <div class="tbp-po-grid">
                <div>
                    <label>Fecha del Pago</label>
                    <input type="date" id="tbp_payment_date" class="widefat" value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div>
                    <label>Forma de Pago</label>
                    <select id="tbp_payment_method" class="widefat">
                        <option value="Transferencia Cuenta BBVA">Transferencia Cuenta BBVA</option>
                        <option value="Tarjeta Crédito/Débito">Tarjeta Crédito/Débito</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Intercambio / Otros">Intercambio / Otros</option>
                    </select>
                </div>
            </div>
            <div class="tbp-po-grid" style="grid-template-columns: 2.5fr 1fr;">
                <div>
                    <label>Concepto (Descripción del SPEI o Referencia)</label>
                    <textarea id="tbp_payment_ref" class="widefat" rows="3" placeholder="Ej: SPEI ENVIADO BANORTE/0049358130..."></textarea>
                </div>
                <div>
                    <label>Monto a Pagar ($)</label>
                    <input type="number" step="0.01" id="tbp_payment_amount" class="widefat" placeholder="0.00" style="font-size:22px; font-weight:bold; height:auto;">
                </div>
            </div>
            <div style="margin-top:15px; border-top: 1px solid #ddd; padding-top: 15px;">
                <label>Subir Evidencia (JPG o PDF)</label>
                <div style="display:flex; gap:10px;">
                    <input type="text" id="tbp_payment_file_url" class="widefat" placeholder="URL del archivo..." readonly>
                    <button type="button" class="button" id="tbp_btn_upload_evidence">Seleccionar Archivo</button>
                </div>
            </div>
            <div style="margin-top:20px; text-align:right;">
                <button type="button" class="button" onclick="jQuery('#tbp-payment-panel').slideUp();" style="padding:5px 20px;">Cancelar</button>
                <button type="button" class="button button-primary" id="tbp-btn-save-payment" style="background:#008a00 !important; border-color:#007000 !important; padding:5px 30px;">💾 Guardar Pago</button>
            </div>
        </div>
    </div>

    <!-- RELACIÓN DE PAGOS EFECTUADOS -->
    <div style="margin-top: 30px;">
        <h4 style="text-transform: uppercase; margin-bottom: 10px; color: #333;">Relación de Pagos Efectuados</h4>
        <table class="tbp-history-table">
            <thead>
                <tr>
                    <th width="15%">Fecha</th>
                    <th width="20%">Forma de Pago</th>
                    <th width="30%">Concepto</th>
                    <th width="12%">Monto</th>
                    <th width="12%">Evidencia</th>
                    <th width="11%">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $payments ) ) : ?>
                    <tr><td colspan="6" style="text-align:center; color:#888;">No hay pagos registrados aún.</td></tr>
                <?php else : ?>
                    <?php foreach ( $payments as $p ) : ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($p->fecha ?: 'now')); ?></td>
                            <td style="text-transform:uppercase;">
                                <?php echo esc_html( $p->forma ?: 'N/A' ); ?>
                                <?php 
                                $forced_by = get_post_meta( $p->ID, '_tbp_forced_by', true );
                                if ( $forced_by ) {
                                    $admin = get_userdata( $forced_by );
                                    echo '<br/><span style="color:#d32f2f; font-size:9px; font-weight:bold;">🛡️ AUTORIZACIÓN ESPECIAL: ' . esc_html( $admin->display_name ) . '</span>';
                                }
                                ?>
                            </td>
                            <td><?php echo nl2br(esc_html( $p->concepto ?: 'Sin concepto' )); ?></td>
                            <td style="font-weight:bold;"><?php echo wc_price( $p->monto ); ?></td>
                            <td style="text-align:center;">
                                <?php if ( $p->file_url ) : ?>
                                    <a href="<?php echo esc_url( $p->file_url ); ?>" target="_blank" class="button button-small">Ver Archivo</a>
                                <?php else : ?>
                                    <span style="color:#aaa;">Sin archivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 5px; align-items: center; justify-content: center;">
                                    <button type="button" class="tbp-edit-payment button-link" data-id="<?php echo $p->ID; ?>" title="Editar Pago" style="cursor:pointer; font-size: 16px;">✏️</button>
                                    <button type="button" class="tbp-delete-payment button-link" data-id="<?php echo $p->ID; ?>" style="color: #a00; cursor:pointer; font-size: 16px;" title="Eliminar Pago">🗑️</button>
                                    <a href="https://wa.me/?text=<?php echo urlencode('Hola, te envío el comprobante de pago de '.$proveedor_title.' por '.strip_tags(wc_price($p->monto))); ?>" target="_blank" title="Compartir WhatsApp" style="text-decoration:none; font-size: 16px;">📱</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr class="total-row">
                    <td colspan="3" style="text-align:right; font-weight:bold; text-transform:uppercase;">Total Pagado</td>
                    <td colspan="3" style="font-size: 16px; color: #2e7d32;"><?php echo wc_price( $pagado ); ?></td>
                </tr>
            </tbody>
        </table>
        <p style="margin-top: 15px; font-size: 11px; color: #d32f2f; font-weight: bold; text-transform: uppercase;">NOTA: EL TOTAL PAGADO NUNCA DEBE SER MAYOR A LA CANTIDAD AUTORIZADA A PAGAR</p>
    </div>

    <script>
    jQuery(document).ready(function($){
        // Media Uploader
        $('#tbp_btn_upload_evidence').on('click', function(e) {
            e.preventDefault();
            var frame = wp.media({
                title: 'Seleccionar Evidencia de Pago',
                multiple: false,
                button: { text: 'Usar este archivo' }
            });
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#tbp_payment_file_url').val(attachment.url);
            });
            frame.open();
        });

        $('#tbp-btn-toggle-payment').on('click', function(){
            $('#tbp-payment-panel').slideToggle();
        });
        
        $('#tbp-btn-save-payment').on('click', function(){
            var btn = $(this);
            var amount = parseFloat($('#tbp_payment_amount').val());
            var balance = <?php echo floatval($saldo); ?>;
            var po_id = <?php echo $post->ID; ?>;
            var edit_id = $('#tbp_edit_pago_id').val();
            
            if ( isNaN(amount) || amount <= 0 ) {
                alert('Ingresa un monto válido.');
                return;
            }

            // --- SECURITY LOGIC v5.3.0 ---
            // If amount > balance, we need a signature
            if ( edit_id == 0 && amount > (balance + 0.10) ) {
                $('#tbp-signature-modal').fadeIn();
                return;
            }
            
            savePaymentProcess(0); // Standard save
        });

        // Handler for Signature Confirmation
        $('#tbp-btn-confirm-signature').on('click', function(){
            var btnSig = $(this);
            var user = $('#tbp_sig_user').val();
            var pass = $('#tbp_sig_pass').val();
            
            if ( !user || !pass ) {
                $('#tbp-sig-msg').text('Ingresa tus credenciales completas.').show();
                return;
            }

            btnSig.prop('disabled', true).text('Validando Firma...');
            $('#tbp-sig-msg').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_egresos_verify_signature',
                    user: user,
                    pass: pass,
                    nonce: '<?php echo wp_create_nonce("tbp_egresos_payment_nonce"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        $('#tbp-sig-msg').css({'color':'#52c41a', 'background':'#f6ffed'}).text('✅ Firma validada: ' + res.data.user_name).show();
                        setTimeout(function(){
                            $('#tbp-signature-modal').hide();
                            savePaymentProcess(res.data.user_id); // Save with forced ID
                        }, 800);
                    } else {
                        $('#tbp-sig-msg').text('🚫 Error: ' + res.data).show();
                        btnSig.prop('disabled', false).text('✍️ Firmar y Autorizar');
                    }
                },
                error: function() {
                    $('#tbp-sig-msg').text('Error de conexión.').show();
                    btnSig.prop('disabled', false).text('✍️ Firmar y Autorizar');
                }
            });
        });

        function savePaymentProcess(forcedById) {
            var btn = $('#tbp-btn-save-payment');
            var edit_id = $('#tbp_edit_pago_id').val();
            var action = (edit_id > 0) ? 'tbp_egresos_update_payment' : 'tbp_egresos_register_payment';

            var data = {
                action: action,
                pago_id: edit_id,
                po_id: <?php echo $post->ID; ?>,
                amount: parseFloat($('#tbp_payment_amount').val()),
                date: $('#tbp_payment_date').val(),
                method: $('#tbp_payment_method').val(),
                ref: $('#tbp_payment_ref').val(),
                file_url: $('#tbp_payment_file_url').val(),
                forced_by: forcedById,
                nonce: '<?php echo wp_create_nonce("tbp_egresos_payment_nonce"); ?>'
            };

            if ( !confirm('¿Estás seguro de registrar esta operación?') ) return;

            btn.prop('disabled', true).text('Guardando...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function(res) {
                    if ( res.success ) {
                        alert('✅ ' + (edit_id > 0 ? 'Pago actualizado' : 'Pago registrado') + ' con éxito.');
                        location.reload();
                    } else {
                        alert('Error: ' + res.data);
                        btn.prop('disabled', false).text(edit_id > 0 ? '🔄 Actualizar Pago' : '💾 Guardar Pago');
                    }
                },
                error: function() {
                    alert('Error de conexión al servidor.');
                    btn.prop('disabled', false).text(edit_id > 0 ? '🔄 Actualizar Pago' : '💾 Guardar Pago');
                }
            });
        }

        // Edit Payment Action
        $('.tbp-edit-payment').on('click', function(){
            var id = $(this).data('id');
            $('#tbp-payment-panel').slideDown();
            $('#tbp_edit_pago_id').val(id);
            $('#tbp-btn-save-payment').text('🔄 Actualizar Pago');
            $('#tbp-btn-cancel-edit').show();
            
            // Scroll to form
            $('html, body').animate({ scrollTop: $('#tbp-payment-panel').offset().top - 100 }, 500);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_egresos_get_payment_data',
                    pago_id: id,
                    nonce: '<?php echo wp_create_nonce("tbp_egresos_payment_nonce"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        $('#tbp_payment_date').val(res.data.date);
                        $('#tbp_payment_amount').val(res.data.amount);
                        $('#tbp_payment_method').val(res.data.method);
                        $('#tbp_payment_ref').val(res.data.ref);
                        $('#tbp_payment_file_url').val(res.data.file_url);
                    }
                }
            });
        });

        $('#tbp-btn-cancel-edit').on('click', function(){
            $('#tbp_edit_pago_id').val(0);
            $('#tbp-btn-save-payment').text('💾 Guardar Pago');
            $(this).hide();
            // Reset fields
            $('#tbp_payment_amount').val('');
            $('#tbp_payment_ref').val('');
            $('#tbp-payment-panel').slideUp();
        });

        // Delete Payment Action
        $('.tbp-delete-payment').on('click', function(){
            var id = $(this).data('id');
            if (!confirm('¿ESTÁS SEGURO? Se eliminará este pago permanentemente y se ajustará el saldo de la Orden de Compra.')) return;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_egresos_delete_payment',
                    pago_id: id,
                    nonce: '<?php echo wp_create_nonce("tbp_egresos_payment_nonce"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        location.reload();
                    } else {
                        alert(res.data);
                    }
                }
            });
        });

        $('#tbp-btn-sync-overdue').on('click', function(){
            var btn = $(this);
            if (!confirm('¿Deseas autorizar todos los periodos vencidos desde la fecha de inicio del contrato?')) return;
            
            btn.prop('disabled', true).text('Sincronizando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_egresos_sync_overdue_po',
                    po_id: <?php echo $post->ID; ?>,
                    nonce: '<?php echo wp_create_nonce("tbp_egresos_sync_overdue_nonce"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        alert('✅ Saldo sincronizado. Nuevo total autorizado: $' + res.data.new_auth);
                        location.reload();
                    } else {
                        alert('Error: ' + res.data);
                        btn.prop('disabled', false).text('🔄 Poner al día');
                    }
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Save PO Metadata
 */
add_action( 'save_post_tbp_orden_compra', 'tbp_egresos_save_po_metadata' );
function tbp_egresos_save_po_metadata( $post_id ) {
    if ( ! isset( $_POST['tbp_po_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_po_nonce'], 'tbp_po_save' ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    $event_id = isset( $_POST['tbp_event_id'] ) ? intval( $_POST['tbp_event_id'] ) : 0;
    
    // Check if we are forcing an event link from a manual PO
    if ( $event_id === 0 && !empty($_POST['tbp_force_event_id']) ) {
        $event_id = intval($_POST['tbp_force_event_id']);
    }

    $proveedor_id = isset( $_POST['tbp_proveedor_id'] ) ? intval( $_POST['tbp_proveedor_id'] ) : 0;
    
    update_post_meta( $post_id, '_tbp_event_id', $event_id );
    update_post_meta( $post_id, '_tbp_proveedor_id', $proveedor_id );

    // Admin Fields for Manual POs (Operation)
    if ( isset( $_POST['tbp_categoria'] ) ) {
        update_post_meta( $post_id, '_tbp_categoria', sanitize_text_field($_POST['tbp_categoria']) );
    }
    if ( isset( $_POST['tbp_po_mode'] ) ) {
        update_post_meta( $post_id, '_tbp_po_mode', sanitize_text_field($_POST['tbp_po_mode']) );
    }
    if ( isset( $_POST['tbp_monto_fijo'] ) ) {
        $monto_fijo = floatval($_POST['tbp_monto_fijo']);
        update_post_meta( $post_id, '_tbp_monto_fijo', $monto_fijo );
        
        // Initial authorization for manual POs
        $current_auth = get_post_meta( $post_id, '_tbp_cantidad_autorizada', true );
        if ( ! $event_id && ( empty($current_auth) || $current_auth == 0 ) ) {
            update_post_meta( $post_id, '_tbp_cantidad_autorizada', $monto_fijo );
        }
    }
    if ( isset( $_POST['tbp_frecuencia'] ) ) {
        update_post_meta( $post_id, '_tbp_frecuencia', sanitize_text_field($_POST['tbp_frecuencia']) );
    }
    if ( isset( $_POST['tbp_contract_start'] ) ) {
        update_post_meta( $post_id, '_tbp_contract_start', sanitize_text_field($_POST['tbp_contract_start']) );
    }
    if ( isset( $_POST['tbp_contract_end'] ) ) {
        update_post_meta( $post_id, '_tbp_contract_end', sanitize_text_field($_POST['tbp_contract_end']) );
    }
    if ( isset( $_POST['tbp_incremento_pct'] ) ) {
        update_post_meta( $post_id, '_tbp_incremento_pct', floatval($_POST['tbp_incremento_pct']) );
    }
    if ( isset( $_POST['tbp_next_renewal'] ) ) {
        update_post_meta( $post_id, '_tbp_next_renewal', sanitize_text_field($_POST['tbp_next_renewal']) );
    }
    
    // Trigger recompute if it's a Manual PO to update Projections
    if ( ! $event_id ) {
        tbp_egresos_recompute_po_totals( $post_id );
    }
}
