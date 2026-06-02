<?php
/**
 * Módulo: Upgrade de Pedidos — The Best Prom
 *
 * Gestiona el flujo para permitir a los clientes mejorar sus boletos y agregar extras.
 *
 * @package TBP_Actividades
 * @since   11.9.81
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// =====================================================================
// HOOKS DE FRONTEND
// =====================================================================

/**
 * Añade la acción "Mejorar Boletos" en el listado de pedidos de Mi Cuenta.
 */
add_filter( 'woocommerce_my_account_my_orders_actions', 'tbp_asientos_upgrade_orders_actions', 20, 2 );
function tbp_asientos_upgrade_orders_actions( $actions, $order ) {
    if ( ! in_array( $order->get_status(), array( 'completed', 'processing' ) ) ) {
        return $actions;
    }

    $event_id = tbp_asientos_get_order_event_id( $order->get_id() );
    if ( ! $event_id ) {
        return $actions;
    }

    $actions['tbp_upgrade'] = array(
        'url'  => '#',
        'name' => __( '⚡ Mejorar Boletos', 'tbp-actividades' )
    );

    return $actions;
}

/**
 * Agrega la clase identificadora de upgrade a los botones de Mi Cuenta para JS.
 */
add_filter( 'woocommerce_my_account_my_orders_actions', 'tbp_asientos_inject_upgrade_button_meta', 99, 2 );
function tbp_asientos_inject_upgrade_button_meta( $actions, $order ) {
    if ( isset( $actions['tbp_upgrade'] ) ) {
        // Guardamos el ID del pedido para que la acción JS lo lea al hacer clic
        $actions['tbp_upgrade']['url'] = 'javascript:void(0);';
        // Añadimos datos HTML en la clase/atributos en la medida de lo posible
    }
    return $actions;
}

/**
 * Muestra el banner de Upgrade en la página del evento de The Events Calendar.
 */
add_filter( 'the_content', 'tbp_asientos_event_upgrade_banner' );
function tbp_asientos_event_upgrade_banner( $content ) {
    if ( ! is_singular( 'tribe_events' ) || ! is_user_logged_in() ) {
        return $content;
    }

    $event_id = get_the_ID();
    $current_user_id = get_current_user_id();

    // Buscar pedidos de este usuario para este evento
    $order_ids = tbp_asientos_get_user_orders_for_event( $current_user_id, $event_id );
    if ( empty( $order_ids ) ) {
        return $content;
    }

    // Tomar el primer pedido activo
    $order_id = $order_ids[0];
    
    ob_start();
    ?>
    <div class="tbp-upgrade-banner" style="background:linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); color:#fff; padding:20px 24px; border-radius:10px; margin-bottom:25px; box-shadow:0 4px 15px rgba(34,113,177,0.25); font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
        <div style="flex:1; min-width:280px;">
            <h4 style="margin:0; color:#fff; font-size:16px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <span>🎟️</span> ¡Ya tienes boletos para este evento!
            </h4>
            <p style="margin:4px 0 0 0; font-size:13px; color:rgba(255,255,255,0.85);">
                Pedido: <strong>#<?php echo $order_id; ?></strong>. ¿Deseas mejorar tu paquete actual a VIP o agregar platillos adicionales para tus acompañantes?
            </p>
        </div>
        <button type="button" class="tbp-btn-trigger-upgrade button" data-order-id="<?php echo $order_id; ?>" style="background:#fff; color:#1e3a5f; border:none; font-weight:700; font-size:13px; border-radius:6px; padding:10px 20px; cursor:pointer; box-shadow:0 2px 6px rgba(0,0,0,0.15); transition:transform 0.15s, box-shadow 0.15s; outline:none; height:auto; line-height:1.2;">
            ⚡ Mejorar Boletos / Extras
        </button>
    </div>
    <?php
    $banner = ob_get_clean();

    return $banner . $content;
}

/**
 * Obtiene el ID del evento asociado a un pedido (7 capas de detección).
 */
function tbp_asientos_get_order_event_id( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return 0;
    }

    // 1. Verificar metadata directa del pedido
    $event_id = (int) get_post_meta( $order_id, '_tribe_tickets_event', true );
    if ( ! $event_id ) {
        $event_id = (int) get_post_meta( $order_id, '_tribe_wooticket_event', true );
    }
    if ( ! $event_id ) {
        $event_id = (int) get_post_meta( $order_id, '_event_id', true );
    }
    if ( ! $event_id ) {
        $event_id = (int) get_post_meta( $order_id, 'event_id', true );
    }

    // 2. Verificar metadata en los order items
    if ( ! $event_id ) {
        foreach ( $order->get_items() as $item_id => $item ) {
            $event_id = (int) wc_get_order_item_meta( $item_id, '_tribe_tickets_event', true );
            if ( ! $event_id ) {
                $event_id = (int) wc_get_order_item_meta( $item_id, '_tribe_wooticket_event', true );
            }
            if ( ! $event_id ) {
                $event_id = (int) wc_get_order_item_meta( $item_id, '_event_id', true );
            }
            if ( $event_id ) {
                break;
            }
        }
    }

    // 3. Verificar metadata en los productos del pedido
    if ( ! $event_id ) {
        foreach ( $order->get_items() as $item ) {
            $p_id = $item->get_product_id();
            $event_id = (int) get_post_meta( $p_id, '_tribe_tickets_event', true );
            if ( ! $event_id ) {
                $event_id = (int) get_post_meta( $p_id, '_tribe_wooticket_event', true );
            }
            if ( ! $event_id ) {
                $event_id = (int) get_post_meta( $p_id, '_tribe_wooticket_for_event', true );
            }
            if ( ! $event_id ) {
                $event_id = (int) get_post_meta( $p_id, '_event_id', true );
            }
            if ( ! $event_id ) {
                $event_id = (int) get_post_meta( $p_id, 'event_id', true );
            }
            if ( $event_id ) {
                break;
            }
        }
    }

    // 4. Búsqueda inversa en postmeta de productos del pedido vinculados a tribe_events
    if ( ! $event_id ) {
        global $wpdb;
        $product_ids = array();
        foreach ( $order->get_items() as $item ) {
            $product_ids[] = (int) $item->get_product_id();
        }
        if ( ! empty( $product_ids ) ) {
            $placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );
            $event_id = (int) $wpdb->get_var( $wpdb->prepare( "
                SELECT DISTINCT meta_value FROM {$wpdb->postmeta}
                WHERE post_id IN ($placeholders)
                AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
                LIMIT 1
            ", ...$product_ids ) );
        }
    }

    return $event_id;
}

/**
 * Obtiene los pedidos válidos del usuario asociados a un evento específico.
 */
function tbp_asientos_get_user_orders_for_event( $user_id, $event_id ) {
    global $wpdb;

    // Obtener los ticket IDs (productos) vinculados al evento
    $ticket_ids = $wpdb->get_col( $wpdb->prepare( "
        SELECT post_id FROM {$wpdb->postmeta} 
        WHERE (meta_value = %d OR meta_value = %s)
        AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
    ", $event_id, (string)$event_id ) );

    if ( empty( $ticket_ids ) ) {
        return array();
    }

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
         WHERE o.post_status IN ($status_in)
         AND o.post_author = %d",
        array_merge( $ticket_ids, array( $user_id ) )
    ) );

    return array_map( 'intval', $order_ids );
}

/**
 * Devuelve el config_id de asientos para un pedido WooCommerce.
 */
function tbp_get_order_event_config_id( $order_id ) {
    $event_id = tbp_asientos_get_order_event_id( $order_id );
    if ( ! $event_id ) {
        return 0;
    }

    global $wpdb;
    $config_id = $wpdb->get_var( $wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}tbp_seat_configurations WHERE event_id = %d LIMIT 1",
        $event_id
    ) );

    return (int) $config_id;
}


// =====================================================================
// DEBUG INTEGRATION
// =====================================================================
add_action( 'wp_footer', 'tbp_asientos_upgrade_debug_output' );
function tbp_asientos_upgrade_debug_output() {
    if ( empty( $_GET['debug_upgrade'] ) ) {
        return;
    }

    $order_id = 36200; // Pedido de la captura
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        echo '<div style="background:#fff; color:#000; padding:20px; border:2px solid red; z-index:999999; position:relative;">No order found for ID 36200</div>';
        return;
    }

    $status = $order->get_status();
    $event_id = tbp_asientos_get_order_event_id( $order_id );
    $config_id = tbp_get_order_event_config_id( $order_id );

    global $wpdb;
    $all_configs = $wpdb->get_results( "SELECT id, event_id, nombre, status FROM {$wpdb->prefix}tbp_seat_configurations" );

    $items_info = array();
    foreach ( $order->get_items() as $item ) {
        $p_id = $item->get_product_id();
        $product = wc_get_product( $p_id );
        
        $meta = array();
        $raw_meta = get_post_meta( $p_id );
        foreach ( $raw_meta as $k => $v ) {
            if ( strpos($k, 'ticket') !== false || strpos($k, 'event') !== false || strpos($k, 'tribe') !== false ) {
                $meta[$k] = $v;
            }
        }

        $items_info[] = array(
            'product_id' => $p_id,
            'name'       => $item->get_name(),
            'price'      => $product ? $product->get_price() : 'N/A',
            'filtered_meta' => $meta
        );
    }

    echo '<div style="background:#fff; color:#000; padding:20px; border:3px solid blue; z-index:999999; position:relative; margin-top:50px; font-family:monospace; font-size:12px;">';
    echo '<h3>DEBUG UPGRADE: Pedido #' . $order_id . '</h3>';
    echo '<p><strong>Estado WooCommerce:</strong> ' . esc_html( $status ) . '</p>';
    echo '<p><strong>Event ID detectado:</strong> ' . esc_html( $event_id ) . '</p>';
    echo '<p><strong>Config ID detectado:</strong> ' . esc_html( $config_id ) . '</p>';
    echo '<h4>Configuraciones de Asientos en BD:</h4>';
    echo '<pre>' . esc_html( print_r( $all_configs, true ) ) . '</pre>';
    echo '<h4>Items del Pedido:</h4>';
    echo '<pre>' . esc_html( print_r( $items_info, true ) ) . '</pre>';
    echo '</div>';
}


// =====================================================================
// RENDERIZADO DEL MODAL EN EL PIE DE PÁGINA
// =====================================================================

add_action( 'wp_footer', 'tbp_asientos_upgrade_modal_html' );
function tbp_asientos_upgrade_modal_html() {
    // Solo cargar en Mi Cuenta o en post del evento singular
    if ( ! is_account_page() && ! is_singular( 'tribe_events' ) ) {
        return;
    }
    ?>
    <!-- Modal: Mejorar Boletos / Upgrade -->
    <div id="tbp-upgrade-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:999999; background:rgba(15,23,42,0.75); backdrop-filter:blur(4px); align-items:center; justify-content:center; padding:16px; font-family:-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,sans-serif;">
        <div style="background:#fff; border-radius:12px; width:450px; max-width:100%; box-shadow:0 25px 50px -12px rgba(0,0,0,0.25); border:1px solid #e2e8f0; overflow:hidden; display:flex; flex-direction:column; max-height:90vh;">
            <!-- Header -->
            <div style="background:linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); padding:16px 20px; color:#fff; display:flex; justify-content:space-between; align-items:center; flex-shrink:0;">
                <h3 style="margin:0; font-size:15px; font-weight:700; color:#fff; display:flex; align-items:center; gap:8px;">
                    <span>⚡</span> Mejorar Pedido #<span id="tbp_upgrade_order_title"></span>
                </h3>
                <button type="button" id="tbp_close_upgrade_modal" style="background:none; border:none; color:#fff; font-size:18px; cursor:pointer; opacity:0.8; padding:0; outline:none; box-shadow:none;">✕</button>
            </div>
            
            <!-- Body -->
            <div id="tbp_upgrade_modal_body" style="padding:20px; overflow-y:auto; flex:1; font-size:13px; color:#334155; line-height:1.5;">
                <!-- Loader -->
                <div id="tbp_upgrade_loader" style="text-align:center; padding:40px 0;">
                    <div style="border:4px solid #f3f3f3; border-top:4px solid #2271b1; border-radius:50%; width:30px; height:30px; animation:spin 1s linear infinite; margin:0 auto 12px;"></div>
                    <span>Cargando opciones de mejora...</span>
                </div>
                
                <div id="tbp_upgrade_content" style="display:none;">
                    <input type="hidden" id="tbp_upgrade_order_id" value="">
                    
                    <!-- Resumen del pedido actual -->
                    <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:12px; margin-bottom:16px;">
                        <span style="display:block; font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; margin-bottom:4px;">Tu compra actual:</span>
                        <div id="tbp_upgrade_current_items" style="font-weight:700; color:#0f172a; font-size:13px;"></div>
                    </div>
                    
                    <!-- Selección de Upgrade de Boleto Principal -->
                    <div id="tbp_upgrade_package_section" style="margin-bottom:16px; display:none;">
                        <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:6px; font-size:12px;">📈 Subir de Paquete / Nivel de Boleto:</label>
                        <select id="tbp_upgrade_package_select" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px; font-size:13px; color:#0f172a; background-color:#fff;"></select>
                        <p style="font-size:11px; color:#64748b; margin:4px 0 0; line-height:1.3;">Se descontará el monto que ya pagaste por tu boleto anterior.</p>
                    </div>
                    
                    <!-- Adición de Extras (Add-ons) -->
                    <div id="tbp_upgrade_extras_section" style="margin-bottom:20px; display:none;">
                        <label style="display:block; font-weight:700; color:#1e293b; margin-bottom:8px; font-size:12px;">➕ Agregar Boletos o Extras Adicionales:</label>
                        <div id="tbp_upgrade_extras_list" style="border:1px solid #e2e8f0; border-radius:8px; padding:4px; max-height:160px; overflow-y:auto; background:#fcfcfc;"></div>
                    </div>
                    
                    <!-- Desglose de Totales -->
                    <div style="border-top:2px dashed #e2e8f0; padding-top:12px; margin-top:16px;">
                        <div style="display:flex; justify-content:space-between; margin-bottom:4px; font-size:12px;">
                            <span>Nuevo total del pedido:</span>
                            <span id="tbp_upgrade_total_new" style="font-weight:600;">$0.00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; margin-bottom:6px; font-size:12px; color:#16a34a;">
                            <span>Abono pago anterior:</span>
                            <span id="tbp_upgrade_credit_old" style="font-weight:600;">-$0.00</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; border-top:1px solid #e2e8f0; padding-top:8px; font-size:15px; font-weight:800; color:#0f172a;">
                            <span>Diferencia a Pagar:</span>
                            <span id="tbp_upgrade_difference" style="color:#2271b1;">$0.00</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <div style="background:#f8fafc; border-top:1px solid #e2e8f0; padding:12px 20px; display:flex; justify-content:flex-end; gap:8px; flex-shrink:0;">
                <button type="button" id="tbp_btn_upgrade_cancel" style="background:#fff; border:1px solid #cbd5e1; color:#475569; padding:8px 16px; border-radius:6px; font-weight:600; font-size:12px; cursor:pointer; outline:none; height:auto; line-height:1.2;">Cancelar</button>
                <button type="button" id="tbp_btn_upgrade_confirm" style="background:#2271b1; border:none; color:#fff; padding:8px 20px; border-radius:6px; font-weight:700; font-size:12px; cursor:pointer; display:flex; align-items:center; gap:6px; outline:none; height:auto; line-height:1.2;" disabled>⚡ Pagar Diferencia</button>
            </div>
        </div>
    </div>
    
    <style>
    @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .tbp-upgrade-extra-item { display:flex; justify-content:space-between; align-items:center; padding:8px; border-bottom:1px solid #f1f5f9; font-size:12px; }
    .tbp-upgrade-extra-item:last-child { border-bottom:none; }
    .tbp-upgrade-extra-qty { width:45px; border:1px solid #cbd5e1; border-radius:4px; padding:3px; text-align:center; font-size:12px; font-weight:600; }
    </style>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Enlazar los botones en Mi Cuenta y el banner de Evento
        $(document).on('click', '.woocommerce-MyAccount-orders .woocommerce-button.tbp_upgrade, .tbp-btn-trigger-upgrade', function(e) {
            e.preventDefault();
            var orderId = 0;
            
            if ($(this).hasClass('tbp-btn-trigger-upgrade')) {
                orderId = parseInt($(this).data('order-id'));
            } else {
                // Leer ID del pedido desde la columna de WooCommerce
                var $row = $(this).closest('tr');
                // WooCommerce usa clases o data-title para la columna de pedido
                var orderText = $row.find('.woocommerce-orders-table__cell-order-number a').text().trim();
                orderId = parseInt(orderText.replace('#', '')) || 0;
            }

            if (orderId > 0) {
                openUpgradeModal(orderId);
            }
        });

        $('#tbp_close_upgrade_modal, #tbp_btn_upgrade_cancel').on('click', function() {
            $('#tbp-upgrade-modal').hide();
            $('body').css('overflow', '');
        });

        var upgradeDataCache = null;

        function openUpgradeModal(orderId) {
            $('#tbp_upgrade_order_title').text(orderId);
            $('#tbp_upgrade_order_id').val(orderId);
            $('#tbp_upgrade_loader').show();
            $('#tbp_upgrade_content').hide();
            $('#tbp_btn_upgrade_confirm').prop('disabled', true).text('⚡ Pagar Diferencia');
            $('#tbp-upgrade-modal').css('display', 'flex');
            $('body').css('overflow', 'hidden');

            $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                action: 'tbp_asientos_load_upgrade_options',
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(res) {
                $('#tbp_upgrade_loader').hide();
                if (!res.success) {
                    alert('❌ ' + res.data);
                    $('#tbp-upgrade-modal').hide();
                    $('body').css('overflow', '');
                    return;
                }

                upgradeDataCache = res.data;
                renderUpgradeUI();
            }).fail(function() {
                alert('❌ Error de conexión al cargar opciones.');
                $('#tbp-upgrade-modal').hide();
                $('body').css('overflow', '');
            });
        }

        function renderUpgradeUI() {
            var data = upgradeDataCache;
            if (!data) return;

            // Mostrar el resumen actual
            $('#tbp_upgrade_current_items').text(data.current.product_name + ' (Cant: ' + data.current.quantity + ')');

            // Renderizar selector de Upgrades
            var $pkgSelect = $('#tbp_upgrade_package_select');
            $pkgSelect.empty();
            $pkgSelect.append('<option value="" data-price="' + data.current.price + '">Conservar paquete actual (' + formatPrice(data.current.price) + ')</option>');

            var hasPackages = false;
            if (data.upgrades && data.upgrades.length > 0) {
                data.upgrades.forEach(function(pkg) {
                    var diff = pkg.price - data.current.price;
                    if (diff > 0) {
                        $pkgSelect.append('<option value="' + pkg.id + '" data-price="' + pkg.price + '">' + pkg.name + ' (+ ' + formatPrice(diff) + ')</option>');
                        hasPackages = true;
                    }
                });
            }
            if (hasPackages) {
                $('#tbp_upgrade_package_section').show();
            } else {
                $('#tbp_upgrade_package_section').hide();
            }

            // Renderizar lista de Extras
            var $extrasList = $('#tbp_upgrade_extras_list');
            $extrasList.empty();

            var hasExtras = false;
            if (data.extras && data.extras.length > 0) {
                data.extras.forEach(function(el) {
                    var isSameProduct = el.id === data.current.id;
                    var label = isSameProduct ? 'Agregar boletos adicionales' : el.name;
                    $extrasList.append(
                        '<div class="tbp-upgrade-extra-item">' +
                            '<div>' +
                                '<div style="font-weight:600; color:#334155;">' + label + '</div>' +
                                '<div style="font-size:10px; color:#64748b;">Precio: ' + formatPrice(el.price) + '</div>' +
                            '</div>' +
                            '<input type="number" class="tbp-upgrade-extra-qty" data-product-id="' + el.id + '" data-price="' + el.price + '" min="0" value="0">' +
                        '</div>'
                    );
                    hasExtras = true;
                });
            }

            if (hasExtras) {
                $('#tbp_upgrade_extras_section').show();
            } else {
                $('#tbp_upgrade_extras_section').hide();
            }

            $('#tbp_upgrade_content').show();
            calculateUpgradeTotals();
        }

        // Recalcular montos al cambiar opciones
        $(document).on('change input', '#tbp_upgrade_package_select, .tbp-upgrade-extra-qty', function() {
            calculateUpgradeTotals();
        });

        function calculateUpgradeTotals() {
            var data = upgradeDataCache;
            if (!data) return;

            var orderId = $('#tbp_upgrade_order_id').val();
            
            // 1. Obtener precio del paquete nuevo
            var $selectedPkg = $('#tbp_upgrade_package_select option:selected');
            var isUpgradeSelected = $('#tbp_upgrade_package_select').val() !== "";
            var pkgPrice = parseFloat($selectedPkg.data('price')) || data.current.price;
            
            // 2. Sumar extras
            var extrasTotal = 0;
            var hasAnyQty = false;
            $('.tbp-upgrade-extra-qty').each(function() {
                var qty = parseInt($(this).val()) || 0;
                var price = parseFloat($(this).data('price')) || 0;
                if (qty > 0) {
                    extrasTotal += qty * price;
                    hasAnyQty = true;
                }
            });

            // 3. Totales finales
            // El abono/crédito que ya pagó es el precio original multiplicado por la cantidad contratada
            var totalAbono = data.current.price * data.current.quantity;
            
            // El nuevo total del paquete principal (con su cantidad) más los extras
            var nuevoTotalPrincipal = pkgPrice * data.current.quantity;
            var nuevoTotalCompleto = nuevoTotalPrincipal + extrasTotal;

            // La diferencia neta a cobrar
            var diferencia = nuevoTotalCompleto - totalAbono;

            $('#tbp_upgrade_total_new').text(formatPrice(nuevoTotalCompleto));
            $('#tbp_upgrade_credit_old').text('-' + formatPrice(totalAbono));
            $('#tbp_upgrade_difference').text(formatPrice(Math.max(0, diferencia)));

            // Activar/desactivar botón de pago (debe haber diferencia positiva y cambios seleccionados)
            var hasChanges = isUpgradeSelected || hasAnyQty;
            $('#tbp_upgrade_confirm').prop('disabled', !hasChanges || diferencia <= 0);
            $('#tbp_btn_upgrade_confirm').prop('disabled', !hasChanges || diferencia <= 0);
        }

        function formatPrice(val) {
            return '$' + parseFloat(val).toFixed(2);
        }

        // CONFIRMAR UPGRADE
        $('#tbp_btn_upgrade_confirm').on('click', function() {
            var orderId = parseInt($('#tbp_upgrade_order_id').val());
            if (!orderId) return;

            var newPackageId = $('#tbp_upgrade_package_select').val();
            var extras = [];
            $('.tbp-upgrade-extra-qty').each(function() {
                var qty = parseInt($(this).val()) || 0;
                var pid = parseInt($(this).data('product-id'));
                if (qty > 0 && pid) {
                    extras.push({ product_id: pid, quantity: qty });
                }
            });

            var $btn = $(this);
            $btn.prop('disabled', true).text('⏳ Preparando pago...');

            $.post('<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>', {
                action: 'tbp_asientos_prepare_order_upgrade',
                order_id: orderId,
                new_package_id: newPackageId,
                extras: extras,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(res) {
                if (res.success) {
                    // Redirigir al checkout de pago de WooCommerce
                    window.location.href = res.data.payment_url;
                } else {
                    alert('❌ Error: ' + res.data);
                    $btn.prop('disabled', false).text('⚡ Pagar Diferencia');
                }
            }).fail(function() {
                alert('❌ Error de conexión al procesar el upgrade.');
                $btn.prop('disabled', false).text('⚡ Pagar Diferencia');
            });
        });
    });
    </script>
    <?php
}


// =====================================================================
// ENDPOINTS AJAX Y PROCESAMIENTO DE UPGRADE
// =====================================================================

/**
 * AJAX: Cargar opciones de upgrades y extras para un pedido.
 */
add_action( 'wp_ajax_tbp_asientos_load_upgrade_options', 'tbp_asientos_ajax_load_upgrade_options' );
function tbp_asientos_ajax_load_upgrade_options() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );

    $order_id = (int) ( $_POST['order_id'] ?? 0 );
    if ( ! $order_id ) {
        wp_send_json_error( 'ID de pedido inválido.' );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'No se encontró el pedido.' );
    }

    // Verificar que el usuario actual es el dueño del pedido o administrador
    if ( ! current_user_can( 'manage_options' ) && get_current_user_id() !== (int) $order->get_customer_id() ) {
        wp_send_json_error( 'No tienes permisos para mejorar este pedido.' );
    }

    // Obtener los productos en el pedido vinculados a un evento
    $event_id = tbp_asientos_get_order_event_id( $order_id );
    if ( ! $event_id ) {
        wp_send_json_error( 'Este pedido no está asociado a ningún evento.' );
    }

    global $wpdb;
    $current_item = null;

    foreach ( $order->get_items() as $item ) {
        $pid = (int) $item->get_product_id();
        // Verificar si este producto está vinculado al evento detectado
        $is_ticket = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE post_id = %d
            AND meta_value = %d
            AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
        ", $pid, $event_id ) );

        if ( $is_ticket ) {
            $current_item = array(
                'id'           => $pid,
                'product_name' => $item->get_name(),
                'price'        => (float) wc_get_product( $pid )->get_price(),
                'quantity'     => (int) $item->get_quantity(),
                'event_id'     => $event_id,
            );
            break; // Tomamos el primer boleto del evento principal
        }
    }

    if ( ! $current_item ) {
        wp_send_json_error( 'Este pedido no contiene boletos activos para mejoras.' );
    }

    // Obtener todos los productos (tickets) asociados al mismo evento
    $event_tickets = $wpdb->get_results( $wpdb->prepare( "
        SELECT post_id FROM {$wpdb->postmeta}
        WHERE (meta_value = %d OR meta_value = %s)
        AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
    ", $event_id, (string)$event_id ) );

    $upgrades = array();
    $extras = array();

    foreach ( $event_tickets as $t ) {
        $ticket_pid = (int) $t->post_id;
        $product = wc_get_product( $ticket_pid );
        if ( ! $product || ! $product->is_purchasable() ) {
            continue;
        }

        $price = (float) $product->get_price();
        $name = $product->get_name();

        // Si es el mismo producto o un boleto diferente
        if ( $ticket_pid === $current_item['id'] ) {
            $extras[] = array(
                'id'    => $ticket_pid,
                'name'  => $name,
                'price' => $price,
            );
        } else {
            // Upgrade si su precio es mayor que el actual
            if ( $price > $current_item['price'] ) {
                $upgrades[] = array(
                    'id'    => $ticket_pid,
                    'name'  => $name,
                    'price' => $price,
                );
            }
            // Todo boleto del mismo evento puede agregarse como extra
            $extras[] = array(
                'id'    => $ticket_pid,
                'name'  => $name,
                'price' => $price,
            );
        }
    }

    wp_send_json_success( array(
        'current'  => $current_item,
        'upgrades' => $upgrades,
        'extras'   => $extras,
    ) );
}

/**
 * AJAX: Procesar y preparar el pedido para el upgrade.
 */
add_action( 'wp_ajax_tbp_asientos_prepare_order_upgrade', 'tbp_asientos_ajax_prepare_order_upgrade' );
function tbp_asientos_ajax_prepare_order_upgrade() {
    check_ajax_referer( 'tbp_asientos_nonce', 'nonce' );

    $order_id       = (int) ( $_POST['order_id'] ?? 0 );
    $new_package_id = (int) ( $_POST['new_package_id'] ?? 0 );
    $extras         = isset( $_POST['extras'] ) && is_array( $_POST['extras'] ) ? $_POST['extras'] : array();

    if ( ! $order_id ) {
        wp_send_json_error( 'ID de pedido inválido.' );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'No se encontró el pedido.' );
    }

    // Validar permisos
    if ( ! current_user_can( 'manage_options' ) && get_current_user_id() !== (int) $order->get_customer_id() ) {
        wp_send_json_error( 'No tienes permisos para modificar este pedido.' );
    }

    // 1. Encontrar el producto original del evento en el pedido
    $event_id = tbp_asientos_get_order_event_id( $order_id );
    if ( ! $event_id ) {
        wp_send_json_error( 'Este pedido no está asociado a ningún evento.' );
    }

    global $wpdb;
    $original_item_id = 0;
    $original_product_id = 0;
    $original_qty = 0;
    $original_price = 0.0;

    foreach ( $order->get_items() as $item_id => $item ) {
        $pid = (int) $item->get_product_id();
        $is_ticket = $wpdb->get_var( $wpdb->prepare( "
            SELECT COUNT(*) FROM {$wpdb->postmeta}
            WHERE post_id = %d
            AND meta_value = %d
            AND meta_key IN ('_tribe_tickets_event', '_tribe_wooticket_for_event', '_tribe_wooticket_event', '_event_id', 'event_id')
        ", $pid, $event_id ) );

        if ( $is_ticket ) {
            $original_item_id = $item_id;
            $original_product_id = $pid;
            $original_qty = (int) $item->get_quantity();
            $original_price = (float) wc_get_product( $pid )->get_price();
            break;
        }
    }

    // 2. Realizar copia de seguridad de los ítems del pedido original por seguridad
    $backup_data = array(
        'original_product_id' => $original_product_id,
        'original_qty'        => $original_qty,
        'original_price'      => $original_price,
        'new_package_id'      => $new_package_id,
        'extras'              => $extras,
        'pre_upgrade_total'   => (float) $order->get_total(),
        'date'                => time()
    );
    update_post_meta( $order_id, '_tbp_pre_upgrade_backup', $backup_data );

    // 3. Modificar ítems del pedido
    // Si hay upgrade de paquete, removemos el ítem original y añadimos el nuevo
    $pkg_id_to_charge = $original_product_id;
    if ( $new_package_id > 0 && $new_package_id !== $original_product_id ) {
        // Validar que sea upgrade (precio mayor)
        $new_product = wc_get_product( $new_package_id );
        if ( ! $new_product || (float) $new_product->get_price() <= $original_price ) {
            wp_send_json_error( 'El nuevo paquete debe ser de un valor superior.' );
        }

        // Remover el producto original del pedido
        $order->remove_item( $original_item_id );
        
        // Agregar el nuevo producto
        $order->add_product( $new_product, $original_qty );
        $pkg_id_to_charge = $new_package_id;
    }

    // Agregar extras seleccionados
    if ( ! empty( $extras ) ) {
        foreach ( $extras as $e ) {
            $extra_pid = (int) $e['product_id'];
            $extra_qty = (int) $e['quantity'];
            if ( $extra_qty > 0 && $extra_pid ) {
                $extra_product = wc_get_product( $extra_pid );
                if ( $extra_product ) {
                    $order->add_product( $extra_product, $extra_qty );
                }
            }
        }
    }

    // Recalcular primer total con los nuevos productos añadidos
    $order->calculate_totals();

    // 4. Agregar abono/descuento correspondiente al pago que ya realizó anteriormente
    // El crédito neta es el total del pedido antes del upgrade
    $credit_amount = $backup_data['pre_upgrade_total'];

    // Para descontar este abono y cobrar solo la diferencia, añadimos una tasa/descuento negativo
    $item_fee = new WC_Order_Item_Fee();
    $item_fee->set_name( 'Abono Pago Anterior Registrado' );
    $item_fee->set_amount( -$credit_amount );
    $item_fee->set_total( -$credit_amount );
    $order->add_item( $item_fee );

    // Recalcular nuevamente totales para que el neto a pagar sea la diferencia
    $order->calculate_totals();

    // 5. Cambiar estado a pendiente de pago
    $order->set_status( 'pending', 'Modificación de pedido para upgrade iniciada.' );
    $order->save();

    // 6. Retornar URL de pago nativa de WooCommerce
    wp_send_json_success( array(
        'payment_url' => $order->get_checkout_payment_url()
    ) );
}


// =====================================================================
// HOOK POSTERIOR AL PAGO: RESTAURACIÓN Y REGISTRO
// =====================================================================

add_action( 'woocommerce_payment_complete', 'tbp_asientos_complete_upgrade_process' );
add_action( 'woocommerce_order_status_completed', 'tbp_asientos_complete_upgrade_process' );
add_action( 'woocommerce_order_status_processing', 'tbp_asientos_complete_upgrade_process' );
function tbp_asientos_complete_upgrade_process( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Verificar si este pedido proviene de un upgrade activo (leyendo backup metadata)
    $backup = get_post_meta( $order_id, '_tbp_pre_upgrade_backup', true );
    if ( ! is_array( $backup ) || empty( $backup ) ) {
        return; // No es un upgrade
    }

    // Evitar bucles infinitos desactivando el gancho temporalmente
    remove_action( 'woocommerce_order_status_completed', 'tbp_asientos_complete_upgrade_process' );
    remove_action( 'woocommerce_order_status_processing', 'tbp_asientos_complete_upgrade_process' );

    // 1. Localizar y eliminar la tarifa/fee negativa de "Abono Pago Anterior Registrado"
    foreach ( $order->get_fees() as $item_id => $fee ) {
        if ( $fee->get_name() === 'Abono Pago Anterior Registrado' ) {
            $order->remove_item( $item_id );
            break;
        }
    }

    // 2. Recalcular totales para reflejar el costo total completo del nuevo paquete
    $order->calculate_totals();
    
    // Obtener total final del pedido y la diferencia pagada en esta transacción
    $final_total = (float) $order->get_total();
    $diferencia_pagada = $final_total - (float) $backup['pre_upgrade_total'];

    // 3. Eliminar backup para finalizar el ciclo de mejora
    delete_post_meta( $order_id, '_tbp_pre_upgrade_backup' );

    // 4. Agregar notas del pedido detalladas e instructivas
    $note = sprintf(
        "⚡ **Upgrade Exitoso**: El pedido fue mejorado desde Boletos Originales (#%d) a la nueva configuración del evento.\n" .
        "• Diferencia de Pago Abonada: $%s USD.\n" .
        "• Total Consolidado en Cuenta: $%s USD.",
        $backup['original_product_id'],
        number_format( $diferencia_pagada, 2 ),
        number_format( $final_total, 2 )
    );
    $order->add_order_note( $note );
    $order->save();

    // 5. Ajustar asignación de asientos y mesa si ya tiene mesa asignada
    $config_id = tbp_get_order_event_config_id( $order_id );
    if ( $config_id ) {
        global $wpdb;
        $assignment = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tbp_seat_assignments WHERE config_id = %d AND order_id = %d",
            $config_id, $order_id
        ) );

        if ( $assignment ) {
            $config = tbp_asientos_get_config( $config_id );
            $proveedor_id = $config ? (int) ( $config->proveedor_id ?? 0 ) : 0;
            $new_qty = tbp_get_order_seat_qty( $order_id, $proveedor_id );
            $old_qty = (int) $assignment->cantidad;
            $delta = $new_qty - $old_qty;

            if ( $delta !== 0 ) {
                $mesa = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM {$wpdb->prefix}tbp_seat_tables WHERE id = %d",
                    $assignment->mesa_id
                ) );

                if ( $mesa ) {
                    $new_table_used = (int) $mesa->capacidad_usada + $delta;

                    // Actualizar capacidad usada de la mesa
                    tbp_asientos_update_table_used( $mesa->id, $delta );

                    // Actualizar cantidad de la asignación
                    $wpdb->update(
                        $wpdb->prefix . 'tbp_seat_assignments',
                        array( 'cantidad' => $new_qty ),
                        array( 'id' => (int) $assignment->id ),
                        array( '%d' ),
                        array( '%d' )
                    );

                    $seating_note = sprintf(
                        "🪑 **Actualización de Asientos por Upgrade**:\n" .
                        "• Lugares anteriores: %d\n" .
                        "• Nuevos lugares: %d\n" .
                        "• Mesa: %s (Ocupación nueva: %d/%d).",
                        $old_qty,
                        $new_qty,
                        $mesa->numero,
                        $new_table_used,
                        $mesa->capacidad
                    );

                    // Validar límite máximo de 12 personas o capacidad de mesa
                    if ( $new_table_used > 12 ) {
                        $warning_msg = sprintf(
                            "⚠️ **ALERTA DE CAPACIDAD**: El upgrade del pedido #%d incrementó la cantidad de lugares asignados a la Mesa %s. " .
                            "La ocupación actual es de %d personas, lo cual supera el límite máximo de 12 personas establecido para mesas.",
                            $order_id,
                            $mesa->numero,
                            $new_table_used
                        );
                        $seating_note .= "\n\n" . $warning_msg;

                        // Notificar por correo al administrador
                        $admin_email = get_option( 'admin_email' );
                        if ( $admin_email ) {
                            $subject = sprintf( '[Alerta de Capacidad] Mesa %s superó límite de 12 personas (Pedido #%d)', $mesa->numero, $order_id );
                            $body = sprintf(
                                "Hola Organizador,\n\n" .
                                "Te informamos que el cliente del pedido #%d ha realizado un upgrade o agregado extras.\n" .
                                "Como el pedido ya tenía asignada la Mesa %s, se incrementó su cantidad de lugares en la mesa.\n\n" .
                                "Detalles:\n" .
                                "- Mesa: %s\n" .
                                "- Capacidad de Mesa: %d personas\n" .
                                "- Nueva Ocupación Total de la Mesa: %d personas (Supera el límite de 12 personas)\n" .
                                "- Nuevo total de lugares de este cliente: %d\n\n" .
                                "Por favor, ingresa al panel de administración de asientos para verificar y reubicar si es necesario.\n\n" .
                                "Saludos,\nEl sistema de asientos.",
                                $order_id,
                                $mesa->numero,
                                $mesa->numero,
                                $mesa->capacidad,
                                $new_table_used,
                                $new_qty
                            );
                            wp_mail( $admin_email, $subject, $body );
                        }
                    } elseif ( $new_table_used > $mesa->capacidad ) {
                        $warning_msg = sprintf(
                            "⚠️ **ALERTA DE CAPACIDAD**: El upgrade del pedido #%d incrementó la cantidad de lugares asignados a la Mesa %s. " .
                            "La ocupación actual es de %d personas, lo cual supera la capacidad original de la mesa (%d).",
                            $order_id,
                            $mesa->numero,
                            $new_table_used,
                            $mesa->capacidad
                        );
                        $seating_note .= "\n\n" . $warning_msg;

                        // Notificar por correo al administrador
                        $admin_email = get_option( 'admin_email' );
                        if ( $admin_email ) {
                            $subject = sprintf( '[Alerta de Capacidad] Mesa %s superó su capacidad (Pedido #%d)', $mesa->numero, $order_id );
                            $body = sprintf(
                                "Hola Organizador,\n\n" .
                                "Te informamos que el cliente del pedido #%d ha realizado un upgrade o agregado extras.\n" .
                                "Como el pedido ya tenía asignada la Mesa %s, se incrementó su cantidad de lugares en la mesa.\n\n" .
                                "Detalles:\n" .
                                "- Mesa: %s\n" .
                                "- Capacidad original de Mesa: %d personas\n" .
                                "- Nueva Ocupación Total de la Mesa: %d personas\n" .
                                "- Nuevo total de lugares de este cliente: %d\n\n" .
                                "Por favor, ingresa al panel de administración de asientos para verificar y reubicar si es necesario.\n\n" .
                                "Saludos,\nEl sistema de asientos.",
                                $order_id,
                                $mesa->numero,
                                $mesa->numero,
                                $mesa->capacidad,
                                $new_table_used,
                                $new_qty
                            );
                            wp_mail( $admin_email, $subject, $body );
                        }
                    }

                    $order->add_order_note( $seating_note );
                }
            }
        }

        tbp_asientos_generate_public_snapshot( $config_id );
    }

    // Volver a añadir las acciones
    add_action( 'woocommerce_order_status_completed', 'tbp_asientos_complete_upgrade_process' );
    add_action( 'woocommerce_order_status_processing', 'tbp_asientos_complete_upgrade_process' );
}
