<?php
/**
 * Product & Event Integration for TBP Egresos
 * Refined UI: WooCommerce Tab & Event Ticket Accordion
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// --- 1. WOOCOMMERCE PRODUCT DATA TAB ---

add_filter( 'woocommerce_product_data_tabs', 'tbp_egresos_add_product_tab' );
function tbp_egresos_add_product_tab( $tabs ) {
    $tabs['tbp_egresos'] = array(
        'label'    => __( 'Egresos', 'tbp-egresos' ),
        'target'   => 'tbp_egresos_product_data',
        'class'    => array( 'show_if_simple', 'show_if_variable' ),
        'priority' => 100, // Put it after Deposits (usually around 90)
    );
    return $tabs;
}

add_action( 'woocommerce_product_data_panels', 'tbp_egresos_product_panel_content' );
function tbp_egresos_product_panel_content() {
    global $post;
    echo '<div id="tbp_egresos_product_data" class="panel woocommerce_options_panel">';
    tbp_egresos_render_repeater_html( $post->ID );
    echo '</div>';
}

// --- 2. EVENT TICKETS PLUS ACCORDION SECTION ---

/**
 * Hook específico para el nuevo editor de tickets (v2) de The Events Calendar.
 * Inyecta una sección adicional en el acordeón del formulario de edición.
 */
add_action( 'tribe_events_tickets_metabox_edit_accordion_content', 'tbp_egresos_add_et_accordion_section', 20, 2 );
function tbp_egresos_add_et_accordion_section( $post_id, $ticket_id ) {
    ?>
    <button class="accordion-header tbp-egresos-accordion-header" type="button" aria-expanded="false" role="tab" style="display: flex; justify-content: space-between; align-items: center; width: 100%; border: none; background: #f6f7f7; padding: 10px 15px; margin-top: 5px; cursor: pointer; text-align: left; font-weight: bold; border-top: 1px solid #dcdcde;">
        <span><?php _e( '[EGRESOS DEL TICKET]', 'tbp-egresos' ); ?></span>
        <span class="dashicons dashicons-arrow-down-alt2"></span>
    </button>
    <section class="accordion-content tbp-egresos-accordion-content" role="tabpanel" style="padding: 15px; border: 1px solid #dcdcde; border-top: none; display: none;">
        <h4 class="accordion-label screen_reader_text"><?php _e( '[EGRESOS DEL TICKET]', 'tbp-egresos' ); ?></h4>
        <?php tbp_egresos_render_repeater_html( $ticket_id ); ?>
    </section>

    <script>
    jQuery(document).ready(function($) {
        // Toggle para nuestro acordeón con prevención de propagación para evitar conflictos con TEC
        $(document).on('click', '.tbp-egresos-accordion-header', function(e) {
            e.preventDefault();
            e.stopPropagation();
            e.stopImmediatePropagation();
            
            var $content = $(this).next('.tbp-egresos-accordion-content');
            var isExpanded = $(this).attr('aria-expanded') === 'true';
            
            $(this).attr('aria-expanded', !isExpanded);
            $content.slideToggle(200);
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down-alt2 dashicons-arrow-up-alt2');
        });
    });
    </script>
    <?php
}

// --- 3. REPEATER RENDERING LOGIC ---

// --- 3. REPEATER RENDERING LOGIC ---

function tbp_egresos_render_repeater_html( $object_id ) {
    wp_nonce_field( 'tbp_ticket_egresos_save', 'tbp_ticket_egresos_nonce' );

    $egresos = get_post_meta( $object_id, '_tbp_egresos_config', true );
    if ( ! is_array( $egresos ) ) {
        $egresos = array();
    }

    $proveedores = get_posts( array(
        'post_type'      => 'tbp_proveedor',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC'
    ) );

    // Get price for utility calculation
    $current_price = 0;
    $product = wc_get_product( $object_id );
    if ( $product ) {
        $current_price = $product->get_price();
    }
    ?>
    <div class="tbp-egresos-wrapper" data-object-id="<?php echo $object_id; ?>">
        <style>
            .tbp-egresos-wrapper { padding: 10px; }
            .tbp-egreso-row, .tbp-egreso-header { 
                display: grid; 
                grid-template-columns: 2fr 2fr 1fr 1.5fr 1.5fr 1fr 30px; 
                gap: 10px; 
                align-items: center; 
                margin-bottom: 8px;
            }
            .tbp-egreso-header { 
                font-weight: bold; 
                font-size: 11px; 
                color: #555; 
                text-transform: uppercase; 
                border-bottom: 2px solid #ddd; 
                padding-bottom: 5px;
                margin-bottom: 12px;
            }
            .tbp-egreso-row { 
                padding-bottom: 8px; 
                border-bottom: 1px solid #eee; 
            }
            .tbp-egreso-row .field-group { display: block; }
            .tbp-egreso-row input, .tbp-egreso-row select { 
                width: 100% !important; 
                margin: 0 !important; 
                font-size: 12px !important; 
                height: 32px !important;
                padding: 4px 8px !important;
            }
            .remove-egreso { 
                color: #a00; 
                text-decoration: none; 
                font-size: 20px; 
                cursor: pointer; 
                text-align: center;
                font-weight: bold;
            }
            .remove-egreso:hover { color: red; }
            .tbp-utility-summary { 
                background: #f8f9fa; 
                padding: 15px; 
                border-radius: 4px; 
                margin-top: 15px; 
                display: flex; 
                justify-content: space-between; 
                align-items: center; 
                border: 1px solid #ddd; 
            }
            .tbp-utility-summary .utility-value { font-size: 18px; font-weight: bold; color: #2271b1; }
            .tbp-utility-summary .utility-label { font-size: 13px; font-weight: bold; }
            input.egreso-total-row { 
                background: transparent !important; 
                border: none !important; 
                box-shadow: none !important; 
                font-weight: bold; 
                text-align: right;
                pointer-events: none;
            }
        </style>

        <div class="tbp-egreso-header">
            <div>Proveedor</div>
            <div>Título / Concepto</div>
            <div>Cantidad</div>
            <div>Precio Unit.</div>
            <div>Impuestos</div>
            <div style="text-align: right;">Total</div>
            <div></div>
        </div>

        <div class="egresos-list">
            <?php foreach ( $egresos as $index => $egreso ) : ?>
                <div class="tbp-egreso-row">
                    <div class="field-group">
                        <select name="tbp_egresos_meta[<?php echo $object_id; ?>][<?php echo $index; ?>][proveedor_id]">
                            <option value=""><?php _e( 'Seleccionar', 'tbp-egresos' ); ?></option>
                            <?php foreach ( $proveedores as $prov ) : ?>
                                <option value="<?php echo $prov->ID; ?>" <?php selected( $egreso['proveedor_id'], $prov->ID ); ?>><?php echo esc_html( $prov->post_title ); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <input type="text" name="tbp_egresos_meta[<?php echo $object_id; ?>][<?php echo $index; ?>][titulo]" value="<?php echo esc_attr( $egreso['titulo'] ); ?>" placeholder="Concepto" />
                    </div>
                    <div class="field-group">
                        <input type="number" step="1" name="tbp_egresos_meta[<?php echo $object_id; ?>][<?php echo $index; ?>][cantidad]" value="<?php echo esc_attr( $egreso['cantidad'] ); ?>" class="egreso-qty" />
                    </div>
                    <div class="field-group">
                        <input type="number" step="0.01" name="tbp_egresos_meta[<?php echo $object_id; ?>][<?php echo $index; ?>][precio_unitario]" value="<?php echo esc_attr( $egreso['precio_unitario'] ); ?>" class="egreso-price" />
                    </div>
                    <div class="field-group">
                        <select name="tbp_egresos_meta[<?php echo $object_id; ?>][<?php echo $index; ?>][impuesto]" class="egreso-tax">
                            <option value="exento" <?php selected( $egreso['impuesto'], 'exento' ); ?>>Exento</option>
                            <option value="iva16" <?php selected( $egreso['impuesto'], 'iva16' ); ?>>IVA 16%</option>
                            <option value="retencion" <?php selected( $egreso['impuesto'], 'retencion' ); ?>>Retención</option>
                        </select>
                    </div>
                    <div class="field-group">
                        <input type="text" readonly class="egreso-total-row" value="<?php echo number_format($egreso['cantidad'] * $egreso['precio_unitario'], 2); ?>" />
                    </div>
                    <a class="remove-egreso" title="Eliminar">×</a>
                </div>
            <?php endforeach; ?>
        </div>

        <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
            <button type="button" class="button add-egreso"><?php _e( '+ Añadir Egreso', 'tbp-egresos' ); ?></button>
            <button type="button" id="tbp-egresos-save-btn" class="button button-primary" style="background:#2271b1; border-color:#2271b1; font-weight:bold;">💾 Guardar Cambios en Egresos (ID: <?php echo $object_id; ?>)</button>
        </div>

        <!-- Panel de Diagnóstico -->
        <div id="tbp-diag-panel" style="margin-top:15px; background:#f0f0f1; padding:8px; border-left:3px solid #666; font-family:monospace; font-size:10px; color:#666;">
            <strong>DIAGNÓSTICO TBP:</strong><br>
            - Post ID: <span class="diag-id"><?php echo $object_id; ?></span><br>
            - Post Type: <span class="diag-type"><?php echo get_post_type($object_id); ?></span><br>
            - Last Sync: <span class="diag-status">v5.1 Blindada por ID</span>
        </div>

        <div class="tbp-utility-summary">
            <div>
                <span class="utility-label">Precio de Venta:</span> 
                <span class="tbp-display-price" style="margin-left: 10px; font-weight:bold;">$ <?php echo number_format($current_price, 2); ?></span>
            </div>
            <div>
                <span class="utility-label">Utilidad Estimada:</span>
                <span class="utility-value tbp-utility-value">$ 0.00</span>
            </div>
        </div>

        <script>
            jQuery(document).ready(function($) {
                const $wrapper = $('.tbp-egresos-wrapper[data-object-id="<?php echo $object_id; ?>"]');
                const postID = <?php echo $object_id; ?>;

                function calculateUtility() {
                    let totalEgresos = 0;
                    $wrapper.find('.tbp-egreso-row').each(function() {
                        const qty = parseFloat($(this).find('.egreso-qty').val()) || 0;
                        const price = parseFloat($(this).find('.egreso-price').val()) || 0;
                        const totalRow = qty * price;
                        $(this).find('.egreso-total-row').val(totalRow.toFixed(2));
                        totalEgresos += totalRow;
                    });

                    // Price detection
                    let salePrice = parseFloat($('#_regular_price').val()) || parseFloat($('#_sale_price').val());
                    if (!salePrice) {
                        salePrice = <?php echo (float)$current_price; ?>;
                    }
                    
                    const utility = salePrice - totalEgresos;
                    $wrapper.find('.tbp-utility-value').text('$ ' + utility.toLocaleString('en-US', {minimumFractionDigits: 2}));
                    $wrapper.find('.tbp-display-price').text('$ ' + salePrice.toFixed(2));
                    $wrapper.find('.tbp-utility-value').css('color', (utility < 0) ? '#a00' : '#2271b1');
                }

                $wrapper.on('click', '.add-egreso', function() {
                    const index = $wrapper.find('.tbp-egreso-row').length;
                    const row = `
                        <div class="tbp-egreso-row">
                            <div class="field-group">
                                <select name="tbp_egresos_meta[${postID}][${index}][proveedor_id]">
                                    <option value="">Seleccionar Proveedor</option>
                                    <?php foreach ( $proveedores as $prov ) : ?>
                                        <option value="<?php echo $prov->ID; ?>"><?php echo esc_html( $prov->post_title ); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="field-group">
                                <input type="text" name="tbp_egresos_meta[${postID}][${index}][titulo]" placeholder="Concepto" />
                            </div>
                            <div class="field-group">
                                <input type="number" step="1" name="tbp_egresos_meta[${postID}][${index}][cantidad]" value="1" class="egreso-qty" />
                            </div>
                            <div class="field-group">
                                <input type="number" step="0.01" name="tbp_egresos_meta[${postID}][${index}][precio_unitario]" class="egreso-price" />
                            </div>
                            <div class="field-group">
                                <select name="tbp_egresos_meta[${postID}][${index}][impuesto]" class="egreso-tax">
                                    <option value="exento">Exento</option>
                                    <option value="iva16">IVA 16%</option>
                                    <option value="retencion">Retención</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <input type="text" readonly class="egreso-total-row" />
                            </div>
                            <a class="remove-egreso">×</a>
                        </div>`;
                    $wrapper.find('.egresos-list').append(row);
                });

                $wrapper.on('click', '.remove-egreso', function(e) {
                    e.preventDefault();
                    $(this).closest('.tbp-egreso-row').remove();
                    calculateUtility();
                });

                $wrapper.on('input', '.egreso-qty, .egreso-price', function() {
                    calculateUtility();
                });

                $wrapper.find('#tbp-egresos-save-btn').on('click', function(e) {
                    e.preventDefault();
                    var $btn = $(this);
                    
                    $btn.prop('disabled', true).text('Guardando...');
                    $wrapper.find('.diag-status').text('Sincronizando con servidor...');

                    const egresosData = [];
                    $wrapper.find('.tbp-egreso-row').each(function() {
                        egresosData.push({
                            proveedor_id: $(this).find('select[name*="proveedor_id"]').val(),
                            titulo: $(this).find('input[name*="titulo"]').val(),
                            cantidad: $(this).find('.egreso-qty').val(),
                            precio_unitario: $(this).find('.egreso-price').val(),
                            impuesto: $(this).find('.egreso-tax').val()
                        });
                    });

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'tbp_egresos_save_ajax',
                            nonce: $('#tbp_ticket_egresos_nonce').val(),
                            post_id: postID,
                            tbp_egresos_data: { [postID]: egresosData }
                        },
                        success: function(response) {
                            $btn.prop('disabled', false).text('💾 Guardar Cambios en Egresos (ID: ' + postID + ')');
                            if (response.success) {
                                $wrapper.find('.diag-status').html('<span style="color:green;">✓ Guardado con éxito</span>');
                                alert('Egresos guardados correctamente.');
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                });

                calculateUtility();
            });
        </script>
    </div>
    <?php
}

// --- 4. SAVE LOGIC (Unified Integration v5.1) ---

add_action( 'save_post', 'tbp_egresos_save_unified_config' );
add_action( 'event_tickets_after_save_ticket', 'tbp_egresos_save_ticket_ajax_config', 10, 3 );
add_action( 'wp_ajax_tbp_egresos_save_ajax', 'tbp_egresos_ajax_save_handler' );

function tbp_egresos_save_ticket_ajax_config( $post_id, $ticket, $data ) {
    $ticket_id = is_object($ticket) ? ($ticket->ID ?? $post_id) : $post_id;
    tbp_egresos_process_save( $ticket_id );
}

function tbp_egresos_ajax_save_handler() {
    check_ajax_referer( 'tbp_ticket_egresos_save', 'nonce' );
    $post_id = intval( $_POST['post_id'] );
    if ( $post_id && get_post_type($post_id) === 'product' ) {
        tbp_egresos_process_save( $post_id );
        wp_send_json_success( 'Egresos guardados vía AJAX' );
    }
    wp_send_json_error( 'ID no válido' );
}

function tbp_egresos_save_unified_config( $post_id ) {
    if ( get_post_type( $post_id ) !== 'product' ) return;
    if ( ! isset( $_POST['tbp_ticket_egresos_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_ticket_egresos_nonce'], 'tbp_ticket_egresos_save' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

    tbp_egresos_process_save( $post_id );
}

function tbp_egresos_process_save( $post_id ) {
    if ( get_post_type( $post_id ) !== 'product' ) return;

    // v5.1: Blindaje por ID Único. Solo guardamos si el post_id actual está en el mapa de egresos.
    if ( isset( $_POST['tbp_egresos_meta'][$post_id] ) && is_array( $_POST['tbp_egresos_meta'][$post_id] ) ) {
        $incoming = $_POST['tbp_egresos_meta'][$post_id];
    } elseif ( isset( $_POST['tbp_egresos_data'][$post_id] ) && is_array( $_POST['tbp_egresos_data'][$post_id] ) ) {
        $incoming = $_POST['tbp_egresos_data'][$post_id];
    } else {
        // MUY IMPORTANTE: Si NO viene el ID en el POST, no tocamos nada para evitar borrados accidentales.
        return;
    }

    $egresos = array();
    foreach ( $incoming as $egreso ) {
        if ( ! empty( $egreso['proveedor_id'] ) ) {
            $egresos[] = array(
                'proveedor_id'    => intval( $egreso['proveedor_id'] ),
                'titulo'          => sanitize_text_field( $egreso['titulo'] ),
                'cantidad'        => floatval( $egreso['cantidad'] ),
                'precio_unitario' => floatval( $egreso['precio_unitario'] ),
                'impuesto'        => sanitize_text_field( $egreso['impuesto'] )
            );
        }
    }
    update_post_meta( $post_id, '_tbp_egresos_config', $egresos );
}
