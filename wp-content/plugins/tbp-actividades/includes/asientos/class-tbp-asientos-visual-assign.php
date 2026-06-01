<?php
/**
 * Visual Assign Editor
 */

if ( ! defined( 'ABSPATH' ) ) exit;

function tbp_asientos_render_visual_assign_view( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) return;

    global $wpdb;
    
    // 1. Get Tables and Elements for SVG
    $table_elements = $wpdb->prefix . 'tbp_seat_elements';
    $table_seats = $wpdb->prefix . 'tbp_seat_tables';
    
    $elements = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_elements WHERE config_id = %d", $config_id ) );
    $mesas = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_seats WHERE config_id = %d", $config_id ) );

    // 2. Get Unassigned or Partially Assigned Orders
    $order_ids = tbp_asientos_get_orders_for_event( $config->event_id );
    $proveedor_id = (int) ($config->proveedor_id ?? 0);
    
    $unassigned_orders = [];
    foreach ( $order_ids as $oid ) {
        $qty = tbp_get_order_seat_qty( $oid, $proveedor_id );
        if ( $qty <= 0 ) continue;
        
        // How many assigned?
        $assigned = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(cantidad) FROM {$wpdb->prefix}tbp_seat_assignments WHERE config_id = %d AND order_id = %d",
            $config_id, $oid
        ) );
        $assigned = (int) $assigned;
        $pending = $qty - $assigned;
        
        if ( $pending > 0 ) {
            $orden = wc_get_order( $oid );
            $grupo = tbp_asientos_get_order_group_value( $oid, $config->group_field );
            if ( empty( $grupo ) ) $grupo = 'Sin grupo';
            
            $unassigned_orders[] = array(
                'order_id' => $oid,
                'grupo'    => $grupo,
                'pending'  => $pending,
                'total'    => $qty,
                'nombre'   => $orden ? $orden->get_billing_first_name() . ' ' . $orden->get_billing_last_name() : 'Desconocido'
            );
        }
    }
    
    usort($unassigned_orders, function($a, $b) {
        return strcmp($a['grupo'], $b['grupo']);
    });
    
    ?>
    <style>
    .tbp-va-container { display:flex; height:85vh; gap:20px; margin-top:20px; }
    .tbp-va-sidebar { width: 320px; background:#fff; border:1px solid #ccd0d4; border-radius:4px; display:flex; flex-direction:column; }
    .tbp-va-main { flex: 1; background:#fff; border:1px solid #ccd0d4; border-radius:4px; overflow:hidden; display:flex; flex-direction:column; position:relative; }
    .tbp-va-sidebar-header { padding:15px; border-bottom:1px solid #eee; background:#f8f9fa; }
    .tbp-va-sidebar-content { flex:1; overflow-y:auto; padding:10px; }
    .tbp-va-list-item { border:1px solid #eee; padding:10px; border-radius:4px; margin-bottom:10px; background:#fafafa; transition:all 0.2s; }
    .tbp-va-list-item:hover { border-color:#2271b1; }
    .tbp-va-list-item.active { background:#eef4f9; border-color:#2271b1; border-width:2px; }
    .btn-assign-mode { display:block; text-align:center; margin-top:10px; width:100%; }
    .tbp-va-main-header { padding:10px 15px; background:#f8f9fa; border-bottom:1px solid #eee; display:flex; justify-content:space-between; align-items:center; }
    .tbp-va-canvas-container { flex:1; background:#f0f2f5; overflow:auto; position:relative; }
    #tbp-va-canvas { background:#fff; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin:20px auto; display:block; cursor:default; }
    .table-interactive { cursor:pointer; transition:all 0.2s; }
    .table-interactive:hover { opacity:0.8; stroke-width:3px; stroke:#2271b1 !important; }
    .table-disabled { opacity:0.4; cursor:not-allowed; }
    </style>

    <div class="wrap tbp-asientos-wrap">
        <h1 class="wp-heading-inline">Asignación Visual en Plano: <?php echo esc_html($config->nombre); ?></h1>
        <a href="?page=tbp-actividades-asientos&action=view&id=<?php echo $config_id; ?>" class="page-title-action">← Volver a Resultados</a>

        <div class="tbp-va-container">
            <div class="tbp-va-sidebar">
                <div class="tbp-va-sidebar-header">
                    <h3 style="margin:0;">Pedidos Pendientes</h3>
                    <p style="margin:5px 0 0 0; font-size:12px; color:#666;">Selecciona un grupo para asignarlo.</p>
                </div>
                <div class="tbp-va-sidebar-content">
                    <?php if (empty($unassigned_orders)): ?>
                        <div style="padding:20px; text-align:center; color:#2e7d32;">
                            <p>🎉 ¡Todo está asignado!</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($unassigned_orders as $ord): ?>
                            <div class="tbp-va-list-item" id="va-order-<?php echo $ord['order_id']; ?>" data-id="<?php echo $ord['order_id']; ?>" data-pending="<?php echo $ord['pending']; ?>" data-grupo="<?php echo esc_attr($ord['grupo']); ?>" data-nombre="<?php echo esc_attr($ord['nombre']); ?>">
                                <div style="display:flex; justify-content:space-between;">
                                    <strong><?php echo esc_html($ord['grupo']); ?></strong>
                                    <span style="background:#ff9800; color:#fff; padding:2px 6px; border-radius:10px; font-size:11px; font-weight:bold;"><?php echo $ord['pending']; ?> faltan</span>
                                </div>
                                <div style="font-size:12px; color:#666; margin-top:5px;">
                                    (Pedido #<?php echo $ord['order_id']; ?>) <?php echo esc_html($ord['nombre']); ?>
                                </div>
                                <button type="button" class="button btn-assign-mode">👉 Asignar en Plano</button>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tbp-va-main">
                <div class="tbp-va-main-header">
                    <div>
                        <strong>Estado: </strong>
                        <span id="va-status-text" style="color:#666;">Selecciona un pedido de la lista...</span>
                    </div>
                    <div>
                        <button type="button" class="button" id="btn-cancel-assign" style="display:none;">Cancelar Asignación</button>
                    </div>
                </div>
                <div class="tbp-va-canvas-container">
                    <svg id="tbp-va-canvas" width="2000" height="2000"></svg>
                </div>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        const configId = <?php echo intval($config_id); ?>;
        const elementsData = <?php echo json_encode($elements); ?>;
        const mesasData = <?php echo json_encode($mesas); ?>;
        
        let activeOrder = null; // { id, pending, name, group }

        const $svg = $('#tbp-va-canvas');
        
        // 1. Render Canvas
        function renderCanvas() {
            $svg.empty();
            
            // Draw elements (bg)
            elementsData.forEach(el => {
                let node = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                if (el.tipo === 'round') {
                    node = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    node.setAttribute('cx', parseInt(el.pos_x) + (parseInt(el.width)/2));
                    node.setAttribute('cy', parseInt(el.pos_y) + (parseInt(el.width)/2));
                    node.setAttribute('r', parseInt(el.width)/2);
                } else {
                    node.setAttribute('x', el.pos_x);
                    node.setAttribute('y', el.pos_y);
                    node.setAttribute('width', el.width);
                    node.setAttribute('height', el.height);
                }
                node.setAttribute('fill', el.color || '#334155');
                node.setAttribute('opacity', '0.2');
                $svg.append(node);
                
                if (el.label) {
                    let text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                    text.setAttribute('x', parseInt(el.pos_x) + (parseInt(el.width)/2));
                    text.setAttribute('y', parseInt(el.pos_y) + (parseInt(el.height)/2));
                    text.setAttribute('text-anchor', 'middle');
                    text.setAttribute('dominant-baseline', 'central');
                    text.setAttribute('fill', '#334155');
                    text.setAttribute('font-size', '12px');
                    text.setAttribute('font-weight', 'bold');
                    text.textContent = el.label;
                    $svg.append(text);
                }
            });

            // Draw tables
            mesasData.forEach(mesa => {
                let isBlocked = mesa.tipo === 'bloqueada';
                let capacity = parseInt(mesa.capacidad);
                let used = parseInt(mesa.capacidad_usada);
                let available = capacity - used;
                let color = mesa.color || '#2271b1';
                if (isBlocked) color = '#f44336';
                if (!isBlocked && available <= 0) color = '#4caf50'; // full

                let group = document.createElementNS("http://www.w3.org/2000/svg", "g");
                group.setAttribute('class', 'tbp-mesa-group');
                group.setAttribute('data-id', mesa.id);
                group.setAttribute('data-available', available);
                group.setAttribute('data-capacity', capacity);
                group.setAttribute('data-numero', mesa.numero);
                
                let shape = document.createElementNS("http://www.w3.org/2000/svg", "rect");
                if (mesa.tipo === 'round') {
                    shape = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                    shape.setAttribute('cx', parseInt(mesa.pos_x) + (parseInt(mesa.width)/2));
                    shape.setAttribute('cy', parseInt(mesa.pos_y) + (parseInt(mesa.width)/2));
                    shape.setAttribute('r', parseInt(mesa.width)/2);
                } else {
                    shape.setAttribute('x', mesa.pos_x);
                    shape.setAttribute('y', mesa.pos_y);
                    shape.setAttribute('width', mesa.width);
                    shape.setAttribute('height', mesa.height);
                }
                shape.setAttribute('fill', color);
                shape.setAttribute('stroke', '#000');
                shape.setAttribute('stroke-width', '1');
                shape.setAttribute('class', 'shape-node');
                group.appendChild(shape);

                let text = document.createElementNS("http://www.w3.org/2000/svg", "text");
                text.setAttribute('x', parseInt(mesa.pos_x) + (parseInt(mesa.width)/2));
                text.setAttribute('y', parseInt(mesa.pos_y) + (parseInt(mesa.height)/2));
                text.setAttribute('text-anchor', 'middle');
                text.setAttribute('dominant-baseline', 'central');
                text.setAttribute('fill', '#fff');
                text.setAttribute('font-size', '14px');
                text.setAttribute('font-weight', 'bold');
                text.setAttribute('class', 'text-node');
                
                if (isBlocked) {
                    text.textContent = '🚫 ' + mesa.numero;
                } else {
                    text.textContent = mesa.numero + ' (' + used + '/' + capacity + ')';
                }
                group.appendChild(text);

                $svg.append(group);
            });
        }

        // 2. Interaction Logic
        $('.btn-assign-mode').on('click', function() {
            $('.tbp-va-list-item').removeClass('active');
            let $item = $(this).closest('.tbp-va-list-item');
            $item.addClass('active');

            activeOrder = {
                id: $item.data('id'),
                pending: parseInt($item.data('pending')),
                grupo: $item.data('grupo'),
                nombre: $item.data('nombre')
            };

            $('#va-status-text').html(`Asignando <strong>${activeOrder.pending} lugares</strong> del grupo <strong>${activeOrder.grupo}</strong>. Haz clic en una mesa del plano.`);
            $('#btn-cancel-assign').show();

            // Highlight available tables
            $('.tbp-mesa-group').each(function() {
                let avail = parseInt($(this).attr('data-available'));
                if (avail > 0) {
                    $(this).addClass('table-interactive').removeClass('table-disabled');
                } else {
                    $(this).addClass('table-disabled').removeClass('table-interactive');
                }
            });
        });

        $('#btn-cancel-assign').on('click', function() {
            activeOrder = null;
            $('.tbp-va-list-item').removeClass('active');
            $('#va-status-text').text('Selecciona un pedido de la lista...');
            $(this).hide();
            $('.tbp-mesa-group').removeClass('table-interactive table-disabled');
        });

        // Click on Table
        $(document).on('click', '.table-interactive', function() {
            if (!activeOrder) return;
            
            let mesaId = $(this).attr('data-id');
            let mesaNum = $(this).attr('data-numero');
            let available = parseInt($(this).attr('data-available'));
            let capacity = parseInt($(this).attr('data-capacity'));

            let qtyToAssign = Math.min(activeOrder.pending, available);
            let changeCapacity = false;

            if (activeOrder.pending > available) {
                if (confirm(`El grupo requiere ${activeOrder.pending} lugares, pero la Mesa ${mesaNum} solo tiene ${available} libres.\n\n¿Deseas AMPLIAR la capacidad de la mesa a ${capacity + (activeOrder.pending - available)} para acomodarlos a todos juntos?\n\n(Haz clic en Cancelar para dividir el grupo y asignar solo ${available} a esta mesa)`)) {
                    changeCapacity = true;
                    qtyToAssign = activeOrder.pending;
                }
            }

            $(this).css('opacity', '0.5');

            $.post(ajaxurl, {
                action: 'tbp_asientos_visual_assign',
                config_id: configId,
                order_id: activeOrder.id,
                mesa_id: mesaId,
                qty: qtyToAssign,
                change_capacity: changeCapacity ? 1 : 0,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(res) {
                if (res.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (res.data.message || res.data));
                    renderCanvas(); // reload visuals
                }
            });
        });

        renderCanvas();
    });
    </script>
    <?php
}
