<?php
/**
 * Admin Reports for TBP Actividades
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Delivery Reports Page
 */
function tbp_actividades_reportes_page() {
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'shop_manager' ) ) {
        return;
    }

    echo '<div class="wrap">';
    echo '<h1>' . __( 'Reporte de Boletos Entregados', 'tbp-actividades' ) . '</h1>';
    echo '<p>' . __( 'Aquí puedes ver el desglose de todos los pedidos a los que se les han entregado boletos físicos.', 'tbp-actividades' ) . '</p>';

    // Query WooCommerce orders that have physical deliveries
    $args = array(
        'limit'        => -1,
        'meta_key'     => '_tbp_entregas_fisicas',
        'meta_compare' => '>',
        'meta_value'   => '0',
    );
    
    $orders = wc_get_orders( $args );

    ?>
    <style>
        .tbp-report-table { width: 100%; border-collapse: collapse; margin-top: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); background: #fff; }
        .tbp-report-table th, .tbp-report-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .tbp-report-table th { background-color: #f8f9fa; font-weight: 600; color: #333; }
        .tbp-status-badge { display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 500; }
        .tbp-status-badge.completed { background-color: #e6f4ea; color: #1e8e3e; }
        .tbp-status-badge.processing { background-color: #e8f0fe; color: #1a73e8; }
        .tbp-status-badge.on-hold { background-color: #fef7e0; color: #b06000; }
        .tbp-status-badge.p-pagado { background-color: #f3e8fd; color: #6d1b7b; } 
        .tbp-qty { font-size: 16px; font-weight: bold; color: #00875a; }
    </style>

    <style>
        .tbp-filters-wrapper { margin-bottom: 20px; padding: 10px; background: #fff; border: 1px solid #ccd0d4; margin-top: 15px; border-radius: 4px; display: flex; justify-content: space-between; align-items: center; }
    </style>
    
    <div class="tbp-filters-wrapper">
        <div style="display:flex; gap:15px; align-items:center;">
            <div>
                <label for="tbp-event-filter"><strong><?php _e( 'Filtrar por Evento:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-event-filter" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 150px;">
                    <option value=""><?php _e( 'Todos los Eventos', 'tbp-actividades' ); ?></option>
                </select>
            </div>
            <div>
                <label for="tbp-status-filter"><strong><?php _e( 'Filtrar por Status:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-status-filter" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 150px;">
                    <option value=""><?php _e( 'Todos los Status', 'tbp-actividades' ); ?></option>
                </select>
            </div>
        </div>
        <div>
            <button type="button" class="button button-primary" id="tbp-btn-open-bulk" style="display:none;"><?php _e( 'Enviar Mensaje a Seleccionados', 'tbp-actividades' ); ?> (<span id="tbp-btn-sel-count">0</span>)</button>
        </div>
    </div>

    <!-- Bulk Messaging Panel -->
    <div id="tbp-bulk-msg-panel" style="display:none; background:#fff; border:1px solid #ccd0d4; padding:20px; border-radius:4px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top:0; border-bottom:1px solid #eee; padding-bottom:10px;"><?php _e( 'Centro de Mensajes Masivos', 'tbp-actividades' ); ?> (<strong id="tbp-selected-count">0</strong> <?php _e( 'seleccionados', 'tbp-actividades'); ?>)</h2>
        
        <p style="margin-bottom:10px;"><strong><?php _e( 'Asunto del Correo:', 'tbp-actividades' ); ?></strong></p>
        <input type="text" id="tbp-bulk-subject" class="regular-text" style="width:100%; margin-bottom:15px;" placeholder="Ej. Aviso Importante sobre tu Graduación">

        <?php $canc_templates = get_option( 'tbp_marketing_templates', array() ); ?>
        <p style="margin-bottom:10px;"><strong><?php _e( 'Plantilla a enviar (Opcional Canva):', 'tbp-actividades' ); ?></strong></p>
        <select id="tbp-bulk-template" style="width:100%; margin-bottom:15px; border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px;">
            <option value=""><?php _e( '-- Escribir Mensaje Manualmente (Abajo) --', 'tbp-actividades' ); ?></option>
            <?php foreach ( $canc_templates as $tid => $tdata ) : ?>
                <option value="<?php echo esc_attr( $tid ); ?>">Plantilla Canva: <?php echo esc_html( $tdata['name'] ); ?></option>
            <?php endforeach; ?>
        </select>

        <div id="tbp-manual-editor-wrap">
            <p style="margin-bottom:5px;"><strong><?php _e( 'Variables disponibles (haz clic para insertar):', 'tbp-actividades' ); ?></strong></p>
        <div style="margin-bottom:15px;">
            <button type="button" class="button button-small tbp-insert-var" data-var="[nombre]">Nombre</button>
            <button type="button" class="button button-small tbp-insert-var" data-var="[pedido]"># Pedido</button>
            <button type="button" class="button button-small tbp-insert-var" data-var="[boletos]">Cant. Boletos</button>
            <button type="button" class="button button-small tbp-insert-var" data-var="[fecha_entrega]">Fecha Entrega</button>
            <button type="button" class="button button-small tbp-insert-var" data-var="[monto]">Monto Pedido</button>
        </div>

        <?php
        wp_editor( '', 'tbp_bulk_message', array('media_buttons' => true, 'textarea_rows' => 8) );
        ?>
        </div>

        <div style="margin-top:20px; display:flex; align-items:center; gap:15px;">
            <button type="button" class="button button-primary" id="tbp-btn-send-bulk"><?php _e( '¡Enviar a Todos!', 'tbp-actividades' ); ?></button>
            <button type="button" class="button" id="tbp-btn-cancel-bulk"><?php _e( 'Cancelar', 'tbp-actividades' ); ?></button>
        </div>
        
        <!-- Progress Bar -->
        <div id="tbp-bulk-progress-wrapper" style="display:none; margin-top:20px;">
            <p><strong id="tbp-bulk-status-text" style="color:#00875a;"><?php _e( 'Enviando Correos...', 'tbp-actividades' ); ?></strong> (<span id="tbp-bulk-progress-count">0</span> / <span id="tbp-bulk-total-count">0</span>)</p>
            <div style="width:100%; background:#e9ecef; border-radius:4px; height:20px; overflow:hidden;">
                <div id="tbp-bulk-progress-bar" style="width:0%; background:#28a745; height:100%; transition:width 0.3s ease;"></div>
            </div>
            <ul id="tbp-bulk-log" style="margin-top:10px; max-height:150px; overflow-y:auto; background:#f8f9fa; padding:10px; border:1px solid #eee; font-family:monospace; font-size:12px;"></ul>
        </div>
    </div>

    <table class="tbp-report-table widefat striped">
        <thead>
            <tr>
                <th style="width: 3%; text-align:center;"><input type="checkbox" id="tbp-cb-select-all"></th>
        <th style="width: 10%;"><?php _e( '# Pedido', 'tbp-actividades' ); ?></th>
                <th style="width: 15%;"><?php _e( 'Status', 'tbp-actividades' ); ?></th>
                <th style="width: 20%;"><?php _e( 'Titular del Pedido', 'tbp-actividades' ); ?></th>
                <th style="width: 15%;"><?php _e( 'Monto', 'tbp-actividades' ); ?></th>
                <th style="width: 15%;"><?php _e( 'Fecha Entrega', 'tbp-actividades' ); ?></th>
                <th style="width: 10%;"><?php _e( 'Boletos', 'tbp-actividades' ); ?></th>
                <th style="width: 10%;"><?php _e( 'En tómbola', 'tbp-actividades' ); ?></th>
                <th><?php _e( 'Evento', 'tbp-actividades' ); ?></th>
                <th style="width: 20%;"><?php _e( 'Acciones', 'tbp-actividades' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ( empty( $orders ) ) : ?>
                <tr>
                    <td colspan="6"><?php _e( 'No hay registros de boletos entregados.', 'tbp-actividades' ); ?></td>
                </tr>
            <?php else : ?>
                <?php 
                $total_boletos = 0;
                foreach ( $orders as $order ) : 
                    $order_id = $order->get_id();
                    $raw_status = $order->get_status();
                    $status_name = wc_get_order_status_name( $raw_status );
                    $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
                    $total = wc_price( $order->get_total() );
                    $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
                    $tombola   = intval( get_post_meta( $order_id, '_tbp_boletos_tombola', true ) );
                    $total_boletos += $delivered;
                    $total_tombola = isset($total_tombola) ? $total_tombola + $tombola : $tombola;
                    $edit_url = $order->get_edit_order_url();
                    
                    // Fetch Event Name linked to Order via products
                    $event_names = array();
                    foreach ( $order->get_items() as $item ) {
                        $pid = $item->get_product_id();
                        $ev_id = get_post_meta( $pid, '_tribe_wooticket_for_event', true );
                        if ( $ev_id ) {
                            $event_names[] = get_the_title( $ev_id );
                        }
                    }
                    $evt_str = !empty($event_names) ? implode(', ', array_unique($event_names)) : 'Sin Evento';
                    
                    // WhatsApp Logic
                    $phone = $order->get_billing_phone();
                    $clean_phone = preg_replace('/[^0-9]/', '', $phone);
                    if ( strlen($clean_phone) == 10 ) {
                        $clean_phone = '52' . $clean_phone; // Mexico default
                    }

                    global $wpdb;
                    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
                    $last_delivery_date = $wpdb->get_var( $wpdb->prepare( "SELECT created_at FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC LIMIT 1", $order_id ) );
                    
                    if ( $last_delivery_date ) {
                        $date_str = date_i18n( 'd \d\e F \d\e Y', strtotime( $last_delivery_date ) );
                    } else {
                        $date_str = 'recientemente';
                    }

                    $wa_msg = sprintf(
                        "Hola estimado(a) %s\nActiva tu paquete realizando un depósito. El %s te entregamos %d boletos y no hemos recibido pago de ellos, recuerda que el dinero que recaudes por la venta de boletos sera abonado en su totalidad a tu paquete numero de pedido #%d\npuedes pagarlo de diversas formas\nI M P O R T A N T E\nCOMO REFERENCIA, AGREGA P R I M E R O TU NÚMERO DE PEDIDO, AL REALIZAR UN PAGO.\n\nBANCO BBVA\nCUENTA: 0115269059\nCLABE INTERBANCARIA: 012580001152690590\nBeneficiario de la cuenta: TERCER OCTANTE MS S DE RL DE CV\n\nPagar con tarjeta paga aquí:\nhttps://thebestprom.com\n\nDepósito en efectivo en OXXO utiliza la CLABE INTERBANCARIA: 012580001152690590\n\nEsperamos tu pago.",
                        $name,
                        $date_str,
                        $delivered,
                        $order_id
                    );
                    $wa_link = 'https://api.whatsapp.com/send?phone=' . $clean_phone . '&text=' . rawurlencode($wa_msg);


                    // Determine CSS class for badge
                    $badge_class = 'default';
                    if ( $raw_status === 'completed' ) $badge_class = 'completed';
                    elseif ( $raw_status === 'processing' ) $badge_class = 'processing';
                    elseif ( $raw_status === 'on-hold' ) $badge_class = 'on-hold';
                    elseif ( $raw_status === 'p-pagado' || strpos($raw_status, 'pagado') !== false ) $badge_class = 'p-pagado';
                ?>
                    <tr>
                        <td style="text-align:center;"><input type="checkbox" class="tbp-cb-row" value="<?php echo esc_attr( $order_id ); ?>"></td>
                        <td><strong>#<?php echo esc_html( $order_id ); ?></strong></td>
                        <td><span class="tbp-status-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $status_name ); ?></span></td>
                        <td><?php echo esc_html( $name ); ?></td>
                        <td><?php echo $total; ?></td>
                        <td><small><?php echo esc_html( $date_str ); ?></small></td>
                        <td><span class="tbp-qty"><?php echo esc_html( $delivered ); ?></span></td>
                        <td><span class="tbp-qty" style="color: #673ab7;"><?php echo esc_html( $tombola ); ?></span></td>
                        <td><?php echo esc_html( $evt_str ); ?></td>
                        <td>
                            <a href="<?php echo esc_url( $edit_url ); ?>" class="button button-small" target="_blank"><?php _e( 'Ver Pedido', 'tbp-actividades' ); ?></a>
                            
                            <button class="button button-small tbp-btn-send-reminder" data-order-id="<?php echo esc_attr( $order_id ); ?>" style="margin-top:4px; margin-bottom:4px; background:#fff3cd; color:#856404; border-color:#ffeeba;">
                                ✉️ <?php _e( 'Correo', 'tbp-actividades' ); ?>
                            </button>
                            <?php if ( $clean_phone ) : ?>
                                <a href="<?php echo esc_attr( $wa_link ); ?>" class="button button-small" target="_blank" style="margin-top:4px; margin-bottom:4px; background:#d4edda; color:#155724; border-color:#c3e6cb;">
                                    🟢 <?php _e( 'WhatsApp', 'tbp-actividades' ); ?>
                                </a>
                            <?php endif; ?>
                            
                            <button class="button button-small tbp-btn-reset-order" data-order-id="<?php echo esc_attr( $order_id ); ?>" style="margin-top:4px; margin-bottom:4px; background-color: #fff; color: #dc3232; border-color: #dc3232;" title="Borrar TODOS los boletos entregados de este pedido">
                                🗑️ <?php _e( 'Resetear', 'tbp-actividades' ); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" style="text-align: right; font-size: 14px;"><strong>Totales:</strong></th>
                <th style="font-size: 16px; color: #00875a;"><strong><?php echo isset($total_boletos) ? $total_boletos : 0; ?></strong></th>
                <th colspan="2" style="font-size: 16px; color: #673ab7;"><strong><?php echo isset($total_tombola) ? $total_tombola : 0; ?></strong></th>
            </tr>
        </tfoot>
    </table>
    </div>
    
    <!-- DataTables CDN -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <script type="text/javascript" src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <style>
        .dataTables_wrapper .dataTables_filter input { border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 8px; }
        .dataTables_wrapper .dataTables_length select { border: 1px solid #ccc; padding: 4px; border-radius: 4px; }
        .tbp-report-table { margin-top: 15px !important; margin-bottom: 15px !important; }
    </style>

    <script>
    jQuery(document).ready(function($){
        // Initialize DataTables
        var table = $('.tbp-report-table').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            "order": [[ 0, "desc" ]], // Default order by primary column
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": -1 }, // Disable sorting on actions column
                { "targets": 8, "visible": false } // Hide the Event column
            ],
            initComplete: function () {
                var api = this.api();
                var col_status = api.column(2); // Status column
                var col_event  = api.column(8); // Event column
 
                // Populate the Status filter
                col_status.data().unique().sort().each(function (d, j) {
                    var text = $('<div>').html(d).text().trim(); 
                    if(text) {
                        $('#tbp-status-filter').append('<option value="' + text + '">' + text + '</option>');
                    }
                });

                // Populate the Event filter
                col_event.data().unique().sort().each(function (d, j) {
                    var text = $('<div>').html(d).text().trim(); 
                    if(text) {
                        $('#tbp-event-filter').append('<option value="' + text + '">' + text + '</option>');
                    }
                });

                // Set up the event listeners
                $('#tbp-status-filter').on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    col_status.search(val ? '^' + val + '$' : '', true, false).draw();
                    resetBulkSelection();
                });

                $('#tbp-event-filter').on('change', function () {
                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                    col_event.search(val ? '^' + val + '$' : '', true, false).draw();
                    resetBulkSelection();
                });

                function resetBulkSelection() {
                    $('#tbp-cb-select-all').prop('checked', false);
                    $('input.tbp-cb-row').prop('checked', false);
                    updateBulkButton();
                }
            }
        });

        // Bulk Selection Logic
        $('#tbp-cb-select-all').on('click', function() {
            var is_checked = $(this).prop('checked');
            var visible_rows = table.rows({ 'search': 'applied' }).nodes();
            $('input.tbp-cb-row', visible_rows).prop('checked', is_checked);
            updateBulkButton();
        });

        $('.tbp-report-table tbody').on('change', 'input.tbp-cb-row', function() {
            updateBulkButton();
        });

        function updateBulkButton() {
            var count = table.$('input.tbp-cb-row:checked').length;
            if ( count > 0 ) {
                $('#tbp-btn-sel-count').text(count);
                $('#tbp-selected-count').text(count);
                $('#tbp-btn-open-bulk').fadeIn();
            } else {
                $('#tbp-btn-open-bulk').fadeOut();
                $('#tbp-cb-select-all').prop('checked', false);
                if ($('#tbp-bulk-msg-panel').is(':visible')) {
                    $('#tbp-bulk-msg-panel').slideUp();
                }
            }
        }

        $('#tbp-btn-open-bulk').on('click', function() {
            $('#tbp-bulk-msg-panel').slideDown();
        });
        
        $('#tbp-btn-cancel-bulk').on('click', function() {
            $('#tbp-bulk-msg-panel').slideUp();
        });

        $('#tbp-bulk-template').on('change', function() {
            if ( $(this).val() !== '' ) {
                $('#tbp-manual-editor-wrap').slideUp();
            } else {
                $('#tbp-manual-editor-wrap').slideDown();
            }
        });
        
        // Insert variables
        $('.tbp-insert-var').on('click', function() {
            var val = $(this).data('var');
            if (typeof tinymce != "undefined" && tinymce.get("tbp_bulk_message") && !tinymce.get("tbp_bulk_message").isHidden()) {
                tinymce.get("tbp_bulk_message").execCommand('mceInsertContent', false, val);
            } else {
                var $txt = $('#tbp_bulk_message');
                var caretPos = $txt[0].selectionStart || $txt.val().length;
                var textAreaTxt = $txt.val();
                $txt.val(textAreaTxt.substring(0, caretPos) + val + textAreaTxt.substring(caretPos) );
            }
        });

        // AJAX Queue System
        var bulk_queue = [];
        var bulk_total = 0;
        var bulk_subject = '';
        var bulk_message = '';
        var bulk_batch_size = 25;
        var bulk_current_batch_count = 0;
        var bulk_pause_ms = 60000; // 60 segundos
        
        $('#tbp-btn-send-bulk').on('click', function() {
            var template_id = $('#tbp-bulk-template').val();
            bulk_subject = $('#tbp-bulk-subject').val().trim();
            
            if ( template_id === '' ) {
                if (typeof tinymce != "undefined" && tinymce.get("tbp_bulk_message") && !tinymce.get("tbp_bulk_message").isHidden()) {
                    bulk_message = tinymce.get("tbp_bulk_message").getContent();
                } else {
                    bulk_message = $('#tbp_bulk_message').val();
                }
                
                if (!bulk_subject || !bulk_message) {
                    alert('El asunto y el mensaje manual son obligatorios.');
                    return;
                }
            } else {
                bulk_message = '';
                if (!bulk_subject) {
                    alert('El asunto es obligatorio.');
                    return;
                }
            }
            
            if (!confirm('¿Estás seguro de enviar este correo a (' + table.$('input.tbp-cb-row:checked').length + ') alumnos seleccionados?')) return;
            
            bulk_queue = [];
            table.$('input.tbp-cb-row:checked').each(function(){
                bulk_queue.push($(this).val());
            });
            
            bulk_total = bulk_queue.length;
            if (bulk_total === 0) return;
            
            $('#tbp-btn-send-bulk, #tbp-btn-cancel-bulk').prop('disabled', true);
            $('#tbp-bulk-progress-wrapper').slideDown();
            $('#tbp-bulk-status-text').text('Enviando...');
            $('#tbp-bulk-total-count').text(bulk_total);
            $('#tbp-bulk-progress-count').text('0');
            $('#tbp-bulk-progress-bar').css('width', '0%');
            $('#tbp-bulk-log').empty();
            
            bulk_current_batch_count = 0;
            processNextBulkEmail();
        });
        
        function processNextBulkEmail() {
            if ( bulk_queue.length === 0 ) {
                $('#tbp-bulk-status-text').text('¡Envío Finalizado con Éxito!');
                $('#tbp-bulk-log').prepend('<li style="color:green;">*** Proceso de envío completado ***</li>');
                $('#tbp-btn-send-bulk, #tbp-btn-cancel-bulk').prop('disabled', false);
                return;
            }
            
            var order_id = bulk_queue.shift();
            var current_index = bulk_total - bulk_queue.length;
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_send_bulk_message',
                    order_id: order_id,
                    subject: bulk_subject,
                    message: bulk_message,
                    template_id: $('#tbp-bulk-template').val(),
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_bulk_msg"); ?>'
                },
                success: function(res) {
                    if(res.success) {
                        $('#tbp-bulk-log').prepend('<li style="color:green;">+ Pedido #' + order_id + ': ' + res.data + '</li>');
                    } else {
                        $('#tbp-bulk-log').prepend('<li style="color:red;">- Pedido #' + order_id + ': Error - ' + (res.data || 'Desc') + '</li>');
                    }
                },
                error: function() {
                    $('#tbp-bulk-log').prepend('<li style="color:red;">- Pedido #' + order_id + ': Error de conexión o servidor.</li>');
                },
                complete: function() {
                    $('#tbp-bulk-progress-count').text(current_index);
                    var pct = (current_index / bulk_total) * 100;
                    $('#tbp-bulk-progress-bar').css('width', pct + '%');
                    
                    bulk_current_batch_count++;
                    
                    if ( bulk_current_batch_count >= bulk_batch_size && bulk_queue.length > 0 ) {
                        bulk_current_batch_count = 0; // Reseteamos contador para el siguiente bloque
                        var seconds_left = bulk_pause_ms / 1000;
                        
                        $('#tbp-bulk-status-text').text('Pausando envío para evitar filtros de SPAM... (' + seconds_left + 's)');
                        $('#tbp-bulk-log').prepend('<li style="color:#d39e00;">--- Pausa de enfriamiento (Rate Limit). Esperando ' + seconds_left + ' segundos ---</li>');
                        
                        var countdown = setInterval(function(){
                            seconds_left--;
                            if ( seconds_left <= 0 ) {
                                clearInterval(countdown);
                                $('#tbp-bulk-status-text').text('Enviando bloque nuevo...');
                                $('#tbp-bulk-log').prepend('<li style="color:#d39e00;">--- Reanudando envíos ---</li>');
                                processNextBulkEmail();
                            } else {
                                $('#tbp-bulk-status-text').text('Pausando envío para evitar filtros de SPAM... (' + seconds_left + 's)');
                            }
                        }, 1000);
                    } else {
                        // Flujo normal sin límite alcanzado
                        setTimeout(processNextBulkEmail, 400);
                    }
                }
            });
        }

        // AJAX for Send Reminder
        $('.tbp-btn-send-reminder').on('click', function(e){
            e.preventDefault();
            var btn = $(this);
            var order_id = btn.data('order-id');
            
            if(!confirm('¿Enviar recordatorio de pago a este alumno?')) return;
            
            btn.prop('disabled', true).text('Enviando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_send_payment_reminder',
                    order_id: order_id,
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_send_reminder"); ?>'
                },
                success: function(res) {
                    if(res.success) {
                        btn.text('¡Enviado!');
                        btn.css({'background':'#d4edda', 'color':'#155724', 'border-color':'#c3e6cb'});
                    } else {
                        alert('Error: ' + (res.data || 'Problema desconocido'));
                        btn.prop('disabled', false).text('Enviar Cobro');
                    }
                },
                error: function() {
                    alert('Error de conexión.');
                    btn.prop('disabled', false).text('Enviar Cobro');
                }
            });
        });

        // AJAX for Reset Order Deliveries
        $('.tbp-btn-reset-order').on('click', function(e){
            e.preventDefault();
            var btn = $(this);
            var order_id = btn.data('order-id');
            
            if(!confirm('¡ATENCIÓN! ¿Estás completamente seguro de que deseas BORRAR TODOS los registros de boletos entregados para el pedido #' + order_id + '? Esto lo pondrá en cero y desaparecerá de este reporte.')) return;
            
            btn.prop('disabled', true).text('Borrando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_reset_order_deliveries',
                    order_id: order_id,
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_reset_deliveries"); ?>'
                },
                success: function(res) {
                    if(res.success) {
                        btn.text('¡Borrado!');
                        // Remove the row visually from DataTable
                        table.row(btn.parents('tr')).remove().draw(false);
                    } else {
                        alert('Error: ' + (res.data || 'Problema desconocido'));
                        btn.prop('disabled', false).html('🗑️ Resetear');
                    }
                },
                error: function() {
                    alert('Error de conexión.');
                    btn.prop('disabled', false).html('🗑️ Resetear');
                }
            });
        });

    });
    </script>
    <?php
}

/**
 * Render the Premiaciones Reports Page
 */
function tbp_actividades_premiaciones_report_page() {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'shop_manager' ) ) {
        return;
    }

    $events = get_posts( array(
        'post_type' => 'tribe_events',
        'posts_per_page' => -1,
        'post_status' => array( 'publish', 'private' )
    ) );

    echo '<div class="wrap">';
    echo '<h1>' . __( 'Reporte de Premiaciones', 'tbp-actividades' ) . '</h1>';
    
    ?>
    <div class="tbp-filters-wrapper" style="display: block; padding: 20px;">
        <form id="tbp-prem-report-filters" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
            <div>
                <label><strong><?php _e( 'Evento:', 'tbp-actividades' ); ?></strong></label><br>
                <select name="event_id" id="tbp-filter-event" style="min-width: 200px;">
                    <option value=""><?php _e( 'Todos los eventos', 'tbp-actividades' ); ?></option>
                    <?php foreach ($events as $event) : ?>
                        <option value="<?php echo $event->ID; ?>"><?php echo esc_html($event->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label><strong><?php _e( 'Premiación:', 'tbp-actividades' ); ?></strong></label><br>
                <select name="premiacion_id" id="tbp-filter-prem" style="min-width: 200px;">
                    <option value=""><?php _e( 'Selecciona evento primero', 'tbp-actividades' ); ?></option>
                </select>
            </div>
            <div>
                <button type="button" id="tbp-btn-filter-prem" class="button button-primary"><?php _e( 'Filtrar', 'tbp-actividades' ); ?></button>
                <button type="button" id="tbp-btn-export-prem" class="button"><?php _e( 'Descargar XLSX (CSV)', 'tbp-actividades' ); ?></button>
            </div>
        </form>
    </div>

    <div id="tbp-prem-report-results" style="margin-top: 20px; background: #fff; padding: 20px; border: 1px solid #ccd0d4;">
        <p><?php _e( 'Usa los filtros de arriba para generar el reporte.', 'tbp-actividades' ); ?></p>
    </div>

    <script>
    jQuery(document).ready(function($) {
        $('#tbp-filter-event').on('change', function() {
            var event_id = $(this).val();
            var prem_select = $('#tbp-filter-prem');
            
            prem_select.html('<option value=""><?php _e('Cargando...', 'tbp-actividades'); ?></option>');
            
            $.ajax({
                url: ajaxurl,
                data: {
                    action: 'tbp_actividades_get_prems_by_event',
                    event_id: event_id
                },
                success: function(response) {
                    if (response.success) {
                        var html = '<option value=""><?php _e('Todas las premiaciones', 'tbp-actividades'); ?></option>';
                        $.each(response.data, function(id, title) {
                            html += '<option value="'+id+'">'+title+'</option>';
                        });
                        prem_select.html(html);
                    } else {
                        prem_select.html('<option value=""><?php _e('Sin premiaciones', 'tbp-actividades'); ?></option>');
                    }
                }
            });
        });

        $('#tbp-btn-filter-prem').on('click', function() {
            var data = $('#tbp-prem-report-filters').serialize();
            $('#tbp-prem-report-results').html('<p><?php _e('Generando reporte...', 'tbp-actividades'); ?></p>');
            
            $.ajax({
                url: ajaxurl,
                data: data + '&action=tbp_actividades_get_prem_report_table',
                success: function(response) {
                    $('#tbp-prem-report-results').html(response);
                }
            });
        });

        $('#tbp-btn-export-prem').on('click', function() {
            var data = $('#tbp-prem-report-filters').serialize();
            window.location.href = ajaxurl + '?action=tbp_actividades_export_prem_report&' + data;
        });
    });
    </script>
    </div>
    <?php
}

/**
 * AJAX Handlers for Reports
 */
add_action( 'wp_ajax_tbp_actividades_get_prems_by_event', 'tbp_actividades_get_prems_by_event_ajax' );
function tbp_actividades_get_prems_by_event_ajax() {
    $event_id = intval( $_GET['event_id'] );
    $prems = get_posts( array(
        'post_type' => 'tbp_premiaciones',
        'meta_key' => '_tbp_event_id',
        'meta_value' => $event_id,
        'posts_per_page' => -1
    ) );

    $data = [];
    foreach ( $prems as $p ) {
        $data[$p->ID] = $p->post_title;
    }

    if ( $data ) wp_send_json_success( $data );
    else wp_send_json_error();
}

add_action( 'wp_ajax_tbp_actividades_get_prem_report_table', 'tbp_actividades_get_prem_report_table_ajax' );
function tbp_actividades_get_prem_report_table_ajax() {
    global $wpdb;
    $table = $wpdb->prefix . 'tbp_premiaciones_votos';
    $event_id = intval( $_GET['event_id'] );
    $prem_id = intval( $_GET['premiacion_id'] );

    $where = "WHERE 1=1";
    if ( $event_id ) $where .= $wpdb->prepare( " AND event_id = %d", $event_id );
    if ( $prem_id ) $where .= $wpdb->prepare( " AND premiacion_id = %d", $prem_id );

    $results = $wpdb->get_results( "
        SELECT premiacion_id, category_id, nominee_name, group_name, COUNT(*) as vote_count 
        FROM $table 
        $where 
        GROUP BY premiacion_id, category_id, nominee_name, group_name
        ORDER BY premiacion_id, category_id, vote_count DESC
    " );

    if ( ! $results ) {
        echo '<p>' . __( 'No se encontraron votos con los filtros seleccionados.', 'tbp-actividades' ) . '</p>';
        wp_die();
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>' . __( 'Premiación', 'tbp-actividades' ) . '</th>';
    echo '<th>' . __( 'Categoría', 'tbp-actividades' ) . '</th>';
    echo '<th>' . __( 'Nominado', 'tbp-actividades' ) . '</th>';
    echo '<th>' . __( 'Grupo', 'tbp-actividades' ) . '</th>';
    echo '<th>' . __( 'Votos', 'tbp-actividades' ) . '</th>';
    echo '</tr></thead><tbody>';

    foreach ( $results as $row ) {
        $prem_title = get_the_title( $row->premiacion_id );
        $categories = get_post_meta( $row->premiacion_id, '_tbp_categories', true );
        $cat_title = $categories[$row->category_id]['title'] ?? $row->category_id;

        echo '<tr>';
        echo '<td>' . esc_html( $prem_title ) . '</td>';
        echo '<td>' . esc_html( $cat_title ) . '</td>';
        echo '<td><strong>' . esc_html( $row->nominee_name ) . '</strong></td>';
        echo '<td>' . esc_html( $row->group_name ) . '</td>';
        echo '<td>' . esc_html( $row->vote_count ) . '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    wp_die();
}

/**
 * Export to CSV/XLSX handler
 */
add_action( 'wp_ajax_tbp_actividades_export_prem_report', 'tbp_actividades_export_prem_report_ajax' );
function tbp_actividades_export_prem_report_ajax() {
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'shop_manager' ) ) {
        wp_die( 'No tienes permiso.' );
    }

    global $wpdb;
    $table = $wpdb->prefix . 'tbp_premiaciones_votos';
    $event_id = intval( $_GET['event_id'] );
    $prem_id = intval( $_GET['premiacion_id'] );

    $where = "WHERE 1=1";
    if ( $event_id ) $where .= $wpdb->prepare( " AND event_id = %d", $event_id );
    if ( $prem_id ) $where .= $wpdb->prepare( " AND premiacion_id = %d", $prem_id );

    $results = $wpdb->get_results( "
        SELECT premiacion_id, category_id, nominee_name, group_name, COUNT(*) as vote_count 
        FROM $table 
        $where 
        GROUP BY premiacion_id, category_id, nominee_name, group_name
        ORDER BY premiacion_id, category_id, vote_count DESC
    " );

    header( 'Content-Type: text/csv; charset=utf-8' );
    header( 'Content-Disposition: attachment; filename=reporte-premiaciones-' . date('Y-m-d') . '.csv' );

    $output = fopen( 'php://output', 'w' );
    fprintf( $output, chr(0xEF).chr(0xBB).chr(0xBF) ); // UTF-8 BOM
    fputcsv( $output, array( 'Premiacion', 'Categoria', 'Nominado', 'Grupo', 'Votos' ) );

    foreach ( $results as $row ) {
        $prem_title = get_the_title( $row->premiacion_id );
        $categories = get_post_meta( $row->premiacion_id, '_tbp_categories', true );
        $cat_title = $categories[$row->category_id]['title'] ?? $row->category_id;

        fputcsv( $output, array(
            $prem_title,
            $cat_title,
            $row->nominee_name,
            $row->group_name,
            $row->vote_count
        ) );
    }

    fclose( $output );
    exit;
}
