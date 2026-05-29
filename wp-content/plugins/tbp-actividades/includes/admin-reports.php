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

    // Optimization: Cache event names for products to avoid repeated meta lookups
    $product_event_cache = array();
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';

    // We no longer query orders here, DataTables will fetch via AJAX
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
        .tbp-type-badge { display: inline-block; padding: 3px 7px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .tbp-type-badge.rifa { background: #fff3cd; color: #856404; }
        .tbp-type-badge.paquete { background: #d1ecf1; color: #0c5460; }
        .tbp-type-badge.pending { background: #e2e8f0; color: #475569; }
        .tbp-qty { font-size: 16px; font-weight: bold; color: #00875a; }
        .tbp-qty.pending { color: #64748b; }
    </style>

    <style>
        .tbp-stats-dashboard { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 25px; margin-top: 10px; }
        .tbp-stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px rgba(0,0,0,0.02); display: flex; align-items: center; gap: 15px; }
        .tbp-stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        .tbp-stat-info h3 { margin: 0; font-size: 13px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .tbp-stat-info p { margin: 5px 0 0; font-size: 24px; font-weight: 800; color: #1e293b; }
        
        .tbp-stat-card.total .tbp-stat-icon { background: #eff6ff; color: #2563eb; }
        .tbp-stat-card.delivered .tbp-stat-icon { background: #f0fdf4; color: #16a34a; }
        .tbp-stat-card.pending .tbp-stat-icon { background: #fff7ed; color: #ea580c; }
    </style>

    <!-- DEBUG PANEL (remove after diagnosis) -->
    <div id="tbp-debug-panel" style="background:#fff8f8; border:2px solid #e53e3e; border-radius:8px; padding:12px 16px; margin-bottom:15px; font-family:monospace; font-size:12px; line-height:1.6; display:none;">
        <strong style="color:#e53e3e;">🔍 DEBUG TRACE (v11.9.41-debug)</strong>
        <div id="tbp-debug-content" style="margin-top:8px; white-space:pre-wrap; color:#333;"></div>
    </div>

    <div class="tbp-stats-dashboard">
        <div class="tbp-stat-card total">
            <div class="tbp-stat-icon">🛒</div>
            <div class="tbp-stat-info">
                <h3>Total Pedidos</h3>
                <p id="tbp-stat-total">0</p>
            </div>
        </div>
        <div class="tbp-stat-card delivered">
            <div class="tbp-stat-icon">✅</div>
            <div class="tbp-stat-info">
                <h3>Entregados</h3>
                <p id="tbp-stat-delivered">0</p>
            </div>
        </div>
        <div class="tbp-stat-card pending">
            <div class="tbp-stat-icon">⏳</div>
            <div class="tbp-stat-info">
                <h3>Pendientes</h3>
                <p id="tbp-stat-pending">0</p>
            </div>
        </div>
    </div>

    <div class="tbp-filters-wrapper">
        <div style="display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
            <div>
                <label for="tbp-type-filter"><strong><?php _e( 'Filtrar por Tipo:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-type-filter" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 150px;">
                    <option value=""><?php _e( 'Todos los Tipos', 'tbp-actividades' ); ?></option>
                    <option value="RIFA"><?php _e( 'Solo Rifa', 'tbp-actividades' ); ?></option>
                    <option value="PAQUETE"><?php _e( 'Solo Paquetes', 'tbp-actividades' ); ?></option>
                </select>
            </div>
            <div>
                <label for="tbp-event-filter"><strong><?php _e( 'Filtrar por Evento:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-event-filter" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 150px;">
                    <option value=""><?php _e( 'Todos los Eventos', 'tbp-actividades' ); ?></option>
                </select>
            </div>
            <div id="tbp-rule-filter-container" style="display:none;">
                <label for="tbp-rule-filter"><strong><?php _e( 'Fase:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-rule-filter" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 150px;">
                    <option value=""><?php _e( 'Todas las fases', 'tbp-actividades' ); ?></option>
                </select>
            </div>
            <div>
                <label for="tbp-status-filter"><strong><?php _e( 'Filtrar por Status:', 'tbp-actividades' ); ?></strong></label>
                <select id="tbp-status-filter" class="wc-enhanced-select" multiple="multiple" data-placeholder="<?php _e( 'Todos los Status', 'tbp-actividades' ); ?>" style="border: 1px solid #ccc; padding: 4px 8px; border-radius: 4px; margin-left: 10px; min-width: 250px;">
                    <!-- Opciones se cargan vía AJAX -->
                </select>
            </div>
        </div>
        <div style="display:flex; gap:10px; align-items:center; margin-top:10px;">
            <button type="button" class="button button-primary" id="tbp-btn-open-bulk" style="display:none;"><?php _e( 'Enviar Mensaje a Seleccionados', 'tbp-actividades' ); ?> (<span id="tbp-btn-sel-count">0</span>)</button>
            <select id="tbp-export-scope" style="border:1px solid #ccc; padding:5px 8px; border-radius:4px; min-width:180px;">
                <option value="entregas">Solo Entregas</option>
                <option value="pendientes">Pendientes de Entrega</option>
                <option value="todos">Todos los Pedidos</option>
                <option value="ambos">Ambos (con estado entrega)</option>
            </select>
            <button type="button" class="button button-primary" id="tbp-btn-download-csv" style="background:#10b981; border-color:#059669; color:white; font-weight:bold; padding:6px 16px;">📊 Descargar Reporte (CSV)</button>
            <span id="tbp-download-status" style="display:none; color:#666; font-style:italic;">Generando reporte...</span>
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
                <th style="width: 8%;"><?php _e( 'Tipo', 'tbp-actividades' ); ?></th>
                <th style="width: 10%;"><?php _e( '# Pedido', 'tbp-actividades' ); ?></th>
                <th style="width: 12%;"><?php _e( 'Status', 'tbp-actividades' ); ?></th>
                <th style="width: 18%;"><?php _e( 'Titular del Pedido', 'tbp-actividades' ); ?></th>
                <th style="width: 12%;"><?php _e( 'Monto', 'tbp-actividades' ); ?></th>
                <th style="width: 12%;"><?php _e( 'Fecha Entrega', 'tbp-actividades' ); ?></th>
                <th style="width: 8%;"><?php _e( 'Cant', 'tbp-actividades' ); ?></th>
                <th><?php _e( 'Evento', 'tbp-actividades' ); ?></th>
                <th style="width: 17%;"><?php _e( 'Acciones', 'tbp-actividades' ); ?></th>
            </tr>
        </thead>
        <tbody>
        <tbody>
            <!-- Loaded via AJAX -->
        </tbody>
        <tfoot>
            <tr>
                <th colspan="7" style="text-align: right; font-size: 14px;"><strong>Totales:</strong></th>
                <th style="font-size: 16px; color: #00875a;" id="tbp-report-total-footer"><strong>0</strong></th>
                <th colspan="2"></th>
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

        // === SERVER-SIDE CSV DOWNLOAD ===
        $('#tbp-btn-download-csv').on('click', function() {
            var $btn = $(this);
            var $status = $('#tbp-download-status');
            var params = new URLSearchParams();
            params.append('action', 'tbp_actividades_export_csv');
            params.append('f_type', $('#tbp-type-filter').val() || '');
            params.append('f_event', $('#tbp-event-filter').val() || '');
            params.append('f_status', ($('#tbp-status-filter').val() || []).join(','));
            params.append('f_rule', $('#tbp-rule-filter').val() || '');
            params.append('f_scope', $('#tbp-export-scope').val() || 'entregas');
            params.append('_wpnonce', '<?php echo wp_create_nonce("tbp_export_csv"); ?>');
            
            var url = ajaxurl + '?' + params.toString();
            $btn.prop('disabled', true).text('⏳ Generando...');
            $status.show();
            
            // Direct navigation triggers the download
            window.location.href = url;
            
            // Re-enable button after a few seconds
            setTimeout(function() {
                $btn.prop('disabled', false).text('📊 Descargar Reporte (CSV)');
                $status.hide();
            }, 5000);
        });

        // Initialize DataTables with Server-Side Processing
        var table = $('.tbp-report-table').DataTable({
            "dom": '<"top"lf>rt<"bottom"ip><"clear">',
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
            },
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": ajaxurl,
                "type": "POST",
                "data": function(d) {
                    d.action = 'tbp_actividades_get_reports_data_ajax';
                    d.f_type = $('#tbp-type-filter').val();
                    d.f_event = $('#tbp-event-filter').val();
                    d.f_status = ($('#tbp-status-filter').val() || []).join(',');
                    d.f_rule = $('#tbp-rule-filter').val();
                    d.f_scope = $('#tbp-export-scope').val();
                },
                "dataSrc": function(json) {
                    // Update Dashboard Stats
                    $('#tbp-stat-total').text(json.stats_total || 0);
                    $('#tbp-stat-delivered').text(json.stats_delivered || 0);
                    $('#tbp-stat-pending').text(json.stats_pending || 0);
                    
                    if (json.footer_total !== undefined) {
                        $('#tbp-report-total-footer').html('<strong>' + json.footer_total + '</strong>');
                    }

                    // === DEBUG: Show server trace in panel ===
                    if (json._debug) {
                        var d = json._debug;
                        var lines = [];
                        lines.push('📦 Plugin Version: ' + (d.plugin_version || '?'));
                        if (d.php_received) {
                            lines.push('📨 f_scope RAW: "' + d.php_received.f_scope_raw + '"  →  CLEAN: "' + d.php_received.f_scope_clean + '"');
                            lines.push('   f_type: "' + d.php_received.f_type + '" | f_event: "' + d.php_received.f_event + '" | f_rule: "' + d.php_received.f_rule + '"');
                        }
                        if (d.scope_analysis) {
                            var s = d.scope_analysis;
                            lines.push('🔀 Branch ejecutado: ' + s.branch_executed);
                            lines.push('   base_ids: ' + s.all_base_ids_count + ' | delivered_ids: ' + s.delivered_ids_count + ' | order_ids (tabla): ' + s.order_ids_count);
                            lines.push('   stats → total: ' + s.stats_total + ' | entregados: ' + s.stats_delivered + ' | pendientes: ' + s.stats_pending);
                        }
                        if (d.response_values) {
                            lines.push('📊 Respuesta → recordsTotal: ' + d.response_values.recordsTotal + ' | recordsFiltered: ' + d.response_values.recordsFiltered + ' | filas: ' + d.response_values.data_rows);
                        }
                        lines.push('🖥️ JS envió f_scope: "' + $('#tbp-export-scope').val() + '"');
                        $('#tbp-debug-content').text(lines.join('\n'));
                        $('#tbp-debug-panel').show();
                        console.log('[TBP-DEBUG] Server response _debug:', d);
                    }
                    // === END DEBUG ===

                    return json.data;
                }
            },
            "order": [[ 2, "desc" ]], // Default order by Order ID column
            "pageLength": 25,
            "columnDefs": [
                { "orderable": false, "targets": [0, -1] }
            ],
            "drawCallback": function(settings) {
                // Update the total in footer from the AJAX response
                if (settings.json && settings.json.footer_total !== undefined) {
                    $('#tbp-report-total-footer').html('<strong>' + settings.json.footer_total + '</strong>');
                }
                resetBulkSelection();
            }
        });

        // Event listeners for filters
        $('#tbp-type-filter, #tbp-status-filter, #tbp-rule-filter, #tbp-export-scope').on('change', function () {
            table.draw();
        });

        $('#tbp-event-filter').on('change', function () {
            var event_id = $(this).val();
            var $rule_filter = $('#tbp-rule-filter');
            var $rule_container = $('#tbp-rule-filter-container');

            if (event_id) {
                // Fetch rules for this event
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'tbp_actividades_get_event_products', // Re-use the existing endpoint
                        event_id: event_id
                    },
                    success: function(res) {
                        if (res.success && res.data.rules_html) {
                            $rule_filter.html('<option value="">Todas las fases</option>' + res.data.rules_html.replace('<option value="">Seleccionar Fase...</option>', ''));
                            $rule_container.fadeIn();
                        } else {
                            $rule_container.hide();
                            $rule_filter.val('');
                        }
                        table.draw();
                    }
                });
            } else {
                $rule_container.hide();
                $rule_filter.val('');
                table.draw();
            }
        });

        // Initialize Filter Options (Event & Status) via AJAX on first load
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: { action: 'tbp_actividades_get_report_filters' },
            success: function(res) {
                if (res.success) {
                    $.each(res.data.events, function(k, v) {
                        $('#tbp-event-filter').append('<option value="'+v.id+'">'+v.title+'</option>');
                    });
                    $.each(res.data.statuses, function(k, v) {
                        $('#tbp-status-filter').append('<option value="'+v+'">'+v+'</option>');
                    });
                    // Initialize Select2 after options are added (if not already handled by WC)
                    if ($.fn.select2) {
                        $('#tbp-status-filter').select2({ placeholder: "Todos los Status" });
                    }
                }
            }
        });

        function resetBulkSelection() {
            $('#tbp-cb-select-all').prop('checked', false);
            $('input.tbp-cb-row').prop('checked', false);
            updateBulkButton();
        }

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

        // AJAX for Send Reminder (Delegated)
        $(document).on('click', '.tbp-btn-send-reminder', function(e){
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

        // AJAX for Reset Order Deliveries (Delegated)
        $(document).on('click', '.tbp-btn-reset-order', function(e){
            e.preventDefault();
            var btn = $(this);
            var order_id = btn.data('order-id');
            var type = btn.data('type') || 'paquete';
            var type_label = (type === 'rifa') ? 'BOLETOS (RIFA)' : 'PAQUETES';
            
            if(!confirm('¡ATENCIÓN! ¿Estás completamente seguro de que deseas BORRAR los registros de ' + type_label + ' entregados para el pedido #' + order_id + '?')) return;
            
            btn.prop('disabled', true).text('Borrando...');
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_reset_order_deliveries',
                    order_id: order_id,
                    reset_type: type,
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_reset_deliveries"); ?>'
                },
                success: function(res) {
                    if(res.success) {
                        // Refresh the whole table and stats
                        table.ajax.reload(null, false);
                    } else {
                        alert('Error: ' + (res.data || 'Problema desconocido'));
                        btn.prop('disabled', false).html('🗑️ Reset');
                    }
                },
                error: function() {
                    alert('Error de conexión.');
                    btn.prop('disabled', false).html('🗑️ Reset');
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

/**
 * AJAX: Get unique filters for the reports (Event & Status)
 */
add_action( 'wp_ajax_tbp_actividades_get_report_filters', 'tbp_actividades_get_report_filters_ajax' );
function tbp_actividades_get_report_filters_ajax() {
    global $wpdb;
    
    // Statuses
    $statuses = wc_get_order_statuses();
    $status_names = array_values($statuses);

    // Events (Fetch from Tribe Events)
    $events_posts = get_posts(array(
        'post_type' => 'tribe_events',
        'posts_per_page' => -1,
        'post_status' => array('publish', 'private'),
        'orderby' => 'title',
        'order' => 'ASC'
    ));
    $events = array();
    foreach ($events_posts as $e) { 
        $events[] = array('id' => $e->ID, 'title' => $e->post_title); 
    }
    
    wp_send_json_success(array(
        'statuses' => $status_names,
        'events'   => $events
    ));
}

/**
 * AJAX: Main Data Processor for Server-Side DataTables
 */
add_action( 'wp_ajax_tbp_actividades_get_reports_data_ajax', 'tbp_actividades_get_reports_data_ajax' );
function tbp_actividades_get_reports_data_ajax() {
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    
    $start  = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $search = sanitize_text_field($_POST['search']['value'] ?? '');
    
    $f_type   = sanitize_text_field($_POST['f_type'] ?? '');
    $f_event  = sanitize_text_field($_POST['f_event'] ?? '');
    
    // Parse comma-separated string back to array
    $f_status_raw = sanitize_text_field($_POST['f_status'] ?? '');
    $f_status = !empty($f_status_raw) ? array_map('trim', explode(',', $f_status_raw)) : array();
    
    $f_rule   = sanitize_text_field($_POST['f_rule'] ?? '');
    $f_scope  = sanitize_text_field($_POST['f_scope'] ?? 'entregas');

    // === DEBUG TRACE (remove after diagnosis) ===
    $__debug = array();
    $__debug['plugin_version'] = '11.9.41-debug';
    $__debug['php_received'] = array(
        'f_scope_raw'  => isset($_POST['f_scope']) ? $_POST['f_scope'] : '(NOT SET)',
        'f_scope_clean'=> $f_scope,
        'f_type'       => $f_type,
        'f_event'      => $f_event,
        'f_rule'       => $f_rule,
        'f_status'     => $f_status_raw,
        'start'        => $start,
        'length'       => $length,
        'draw'         => intval($_POST['draw'] ?? 0),
    );
    error_log('[TBP-DEBUG] AJAX call received: f_scope_raw=' . (isset($_POST['f_scope']) ? $_POST['f_scope'] : 'NOT_SET') . ' | f_scope_clean=' . $f_scope . ' | f_type=' . $f_type . ' | f_event=' . $f_event . ' | f_rule=' . $f_rule);
    // === END DEBUG INPUT ===

    // 1. Determine Event ID (with backward compatibility)
    $ev_id = 0;
    if ( !empty($f_event) ) {
        if ( is_numeric($f_event) ) {
            $ev_id = intval($f_event);
        } else {
            // Backward compatibility: Find ID by title
            $ev_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'tribe_events' LIMIT 1", $f_event));
        }
    }

    // 2. Build Base SQL for the selected Event and Status
    $status_where = "";
    if ( !empty($f_status) ) {
        $statuses = wc_get_order_statuses();
        $selected_slugs = array();
        $status_arr = is_array($f_status) ? $f_status : array($f_status);
        
        foreach($statuses as $slug => $name) {
            if ( in_array($name, $status_arr) ) { 
                $selected_slugs[] = $slug; 
            }
        }
        
        if ( !empty($selected_slugs) ) {
            $slugs_placeholder = implode( ',', array_fill( 0, count($selected_slugs), '%s' ) );
            $status_where = $wpdb->prepare(" AND p.post_status IN ($slugs_placeholder)", ...$selected_slugs);
        }
    }

    // Find all products associated with the graduation event
    $pkg_pids = array();
    $raffle_pids = array();
    if ( $ev_id > 0 ) {
        $pkg_pids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') AND meta_value = %s", $ev_id ) );
        $pkg_pids = array_map('intval', $pkg_pids);

        // Find all local raffle event IDs associated with the graduation event
        $raffle_event_ids = $wpdb->get_col( $wpdb->prepare( "
            SELECT pm2.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'tbp_rifas'
              AND pm1.meta_key = '_tbp_event_id' AND pm1.meta_value = %s
              AND pm2.meta_key = '_tbp_local_raffle_id' AND pm2.meta_value > 0
        ", $ev_id ) );
        
        if ( !empty($raffle_event_ids) ) {
            $raffle_event_ids_str = implode(',', array_map('intval', $raffle_event_ids));
            $raffle_pids = $wpdb->get_col( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') 
                  AND meta_value IN ($raffle_event_ids_str)
            " );
            $raffle_pids = array_map('intval', $raffle_pids);
        }
    } else {
        // Retrieve all raffle product IDs in the system
        $all_raffle_event_ids = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tbp_local_raffle_id' AND meta_value > 0" );
        if ( !empty($all_raffle_event_ids) ) {
            $all_raffle_event_ids_str = implode(',', array_map('intval', $all_raffle_event_ids));
            $raffle_pids = $wpdb->get_col( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') 
                  AND meta_value IN ($all_raffle_event_ids_str)
            " );
            $raffle_pids = array_map('intval', $raffle_pids);
        }
    }

    $event_join = "";
    $event_where = "";
    if ( $ev_id > 0 ) {
        // Find products for this event
        $p_ids = $pkg_pids;
        
        // If a specific rule (fase) is selected, restrict to only the enabled product IDs
        if ( !empty($f_rule) && $f_rule !== 'legacy_phase' && !empty($p_ids) ) {
            $rules = get_post_meta( $ev_id, '_tbp_event_delivery_rules', true );
            if ( is_array($rules) ) {
                foreach ( $rules as $rule ) {
                    if ( isset($rule['id']) && $rule['id'] === $f_rule && isset($rule['tickets']) ) {
                        $enabled_pids = array();
                        foreach ( $rule['tickets'] as $pid => $ticket_cfg ) {
                            if ( !empty($ticket_cfg['apply']) ) {
                                $enabled_pids[] = intval($pid);
                            }
                        }
                        if ( !empty($enabled_pids) ) {
                            $p_ids = array_values(array_intersect(array_map('intval', $p_ids), $enabled_pids));
                        }
                        break;
                    }
                }
            }
        }

        // Apply type filter to product selection
        if ( $f_type === 'PAQUETE' ) {
            $p_ids = array_diff($p_ids, $raffle_pids);
        } elseif ( $f_type === 'RIFA' ) {
            $p_ids = array_intersect($p_ids, $raffle_pids);
            if ( empty($p_ids) ) {
                $p_ids = $raffle_pids;
            }
            if ( empty($p_ids) ) {
                // Fallback to all event products if no local raffle event configuration matches
                $p_ids = $pkg_pids;
            }
        }
        
        if ( !empty($p_ids) ) {
            $p_ids_str = implode(',', array_map('intval', $p_ids));
            $event_join = " INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id 
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id ";
            $event_where = " AND oi.order_item_type = 'line_item' AND oim.meta_key = '_product_id' AND oim.meta_value IN ($p_ids_str) ";
        } else {
            // No products found for event, results will be empty
            wp_send_json(array("draw"=>intval($_POST['draw']??0),"recordsTotal"=>0,"recordsFiltered"=>0,"data"=>array(),"footer_total"=>0,"stats_total"=>0,"stats_delivered"=>0,"stats_pending"=>0));
            return;
        }
    }

    // 3. Calculate Stats directly via SQL (MUCH faster for 13k+ orders)
    $sql_base = "SELECT p.ID FROM {$wpdb->posts} p $event_join WHERE p.post_type = 'shop_order' $status_where $event_where";
    $all_base_ids = $wpdb->get_col("SELECT DISTINCT ID FROM ($sql_base) as base");
    if (empty($all_base_ids)) $all_base_ids = array(0);

    // Refine all_base_ids in PHP to prevent mixing and handle type boundaries accurately
    if ( !empty($all_base_ids) && $all_base_ids[0] !== 0 ) {
        $base_ids_str = implode(',', array_map('intval', $all_base_ids));
        
        $order_items_db = $wpdb->get_results("
            SELECT oi.order_id, oim.meta_value as product_id
            FROM {$wpdb->prefix}woocommerce_order_items oi
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE oi.order_id IN ($base_ids_str)
              AND oi.order_item_type = 'line_item'
              AND oim.meta_key = '_product_id'
        ");
        
        $order_to_pids = array();
        foreach ($order_items_db as $oi) {
            $oid = intval($oi->order_id);
            $pid = intval($oi->product_id);
            $order_to_pids[$oid][] = $pid;
        }

        $order_meta_db = $wpdb->get_results("
            SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id IN ($base_ids_str)
              AND meta_key IN ('_tbp_entregas_fisicas', '_tbp_entrega_paquetes')
        ");
        $order_to_meta = array();
        foreach ($order_meta_db as $om) {
            $oid = intval($om->post_id);
            $order_to_meta[$oid][$om->meta_key] = $om->meta_value;
        }

        $filtered_base_ids = array();
        foreach ($all_base_ids as $oid) {
            $oid = intval($oid);
            $pids = $order_to_pids[$oid] ?? array();
            
            $has_pkg_items = false;
            $has_raffle_items = false;
            
            foreach ($pids as $pid) {
                if ( in_array($pid, $raffle_pids) ) {
                    $has_raffle_items = true;
                } else {
                    if ( empty($pkg_pids) || in_array($pid, $pkg_pids) ) {
                        $has_pkg_items = true;
                    }
                }
            }
            
            $delivered_fisicas = intval($order_to_meta[$oid]['_tbp_entregas_fisicas'] ?? 0);
            $pkg_delivered = $order_to_meta[$oid]['_tbp_entrega_paquetes'] ?? '';
            $has_pkg_delivery = ($pkg_delivered === '1' || $pkg_delivered === 'yes');
            $has_raffle_delivery = ($delivered_fisicas > 0);
            
            $is_pkg_order = ($has_pkg_items || $has_pkg_delivery);
            $is_rifa_order = ($has_raffle_items || $has_raffle_delivery);
            
            if ( $f_type === 'PAQUETE' ) {
                if ( $is_pkg_order ) {
                    $filtered_base_ids[] = $oid;
                }
            } elseif ( $f_type === 'RIFA' ) {
                if ( $is_rifa_order ) {
                    $filtered_base_ids[] = $oid;
                }
            } else {
                $filtered_base_ids[] = $oid;
            }
        }
        
        $all_base_ids = !empty($filtered_base_ids) ? $filtered_base_ids : array(0);
        $base_ids_str = implode(',', array_map('intval', $all_base_ids));
    }

    // Stats: Total
    $stats_total = count($all_base_ids);
    if ($all_base_ids[0] === 0) $stats_total = 0;

    // Stats: Delivered (Filtered by Type & Rule)
    $delivered_where = "";
    if ( !empty($f_rule) ) {
        if ($f_rule === 'legacy_phase') {
            // Find orders that have general delivery flag but NO specific rule assigned yet
            $delivered_where = " AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes')) AND post_id NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_tbp_delivery_rule_id') ";
        } else {
            $delivered_where = $wpdb->prepare(" AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE (meta_key = '_tbp_delivery_rule_id' AND meta_value = %s) OR (meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes'))) ", $f_rule);
        }
    } else {
        if ( $f_type === 'RIFA' ) {
            $delivered_where = " AND meta_key = '_tbp_entregas_fisicas' AND meta_value > 0 ";
        } elseif ( $f_type === 'PAQUETE' ) {
            $delivered_where = " AND meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes') ";
        } else {
            $delivered_where = " AND ((meta_key = '_tbp_entregas_fisicas' AND meta_value > 0) OR (meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes'))) ";
        }
    }
    
    $delivered_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE post_id IN ($base_ids_str) $delivered_where");
    $stats_delivered = count($delivered_ids);
    $stats_pending = $stats_total - $stats_delivered;

    // Determine which order IDs to query based on scope
    $__scope_branch = 'none';
    if ( $f_scope === 'todos' || $f_scope === 'ambos' ) {
        $order_ids = ($all_base_ids[0] === 0) ? array() : $all_base_ids;
        $__scope_branch = 'todos/ambos';
    } elseif ( $f_scope === 'pendientes' ) {
        if ($all_base_ids[0] === 0) {
            $order_ids = array();
            $__scope_branch = 'pendientes (empty base)';
        } else {
            $order_ids = array_diff($all_base_ids, $delivered_ids);
            $__scope_branch = 'pendientes (diff)';
        }
    } else {
        // 'entregas' (default)
        $order_ids = $delivered_ids;
        $__scope_branch = 'entregas (default), f_scope_value="' . $f_scope . '"';
    }

    // === DEBUG TRACE (scope results) ===
    $__debug['scope_analysis'] = array(
        'f_scope_value'      => $f_scope,
        'branch_executed'    => $__scope_branch,
        'all_base_ids_count' => count($all_base_ids),
        'delivered_ids_count'=> count($delivered_ids),
        'order_ids_count'    => count($order_ids),
        'stats_total'        => $stats_total,
        'stats_delivered'    => $stats_delivered,
        'stats_pending'      => $stats_pending,
    );
    error_log('[TBP-DEBUG] Scope: f_scope=' . $f_scope . ' | branch=' . $__scope_branch . ' | base=' . count($all_base_ids) . ' | delivered=' . count($delivered_ids) . ' | order_ids=' . count($order_ids));
    // === END DEBUG SCOPE ===

    if (empty($order_ids)) {
        wp_send_json(array(
            "draw"=>intval($_POST['draw']??0),
            "recordsTotal"=>0,
            "recordsFiltered"=>0,
            "data"=>array(),
            "footer_total"=>0,
            "stats_total" => $stats_total,
            "stats_delivered" => $stats_delivered,
            "stats_pending" => $stats_pending
        ));
        return;
    }

    // Apply search filter if not empty
    if ( !empty($search) ) {
        $search_term = '%' . $wpdb->esc_like( $search ) . '%';
        $matching_search_ids = $wpdb->get_col( $wpdb->prepare("
            SELECT DISTINCT p.ID 
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
              AND (
                p.ID LIKE %s 
                OR (pm.meta_key IN ('_billing_first_name', '_billing_last_name', '_billing_email') AND pm.meta_value LIKE %s)
              )
        ", $search_term, $search_term) );
        
        $order_ids = array_intersect(array_map('intval', $order_ids), array_map('intval', $matching_search_ids));
    }

    if (empty($order_ids)) {
        wp_send_json(array(
            "draw"=>intval($_POST['draw']??0),
            "recordsTotal"=>0,
            "recordsFiltered"=>0,
            "data"=>array(),
            "footer_total"=>0,
            "stats_total" => $stats_total,
            "stats_delivered" => $stats_delivered,
            "stats_pending" => $stats_pending
        ));
        return;
    }

    // Sort order IDs descending to show newest first
    rsort( $order_ids, SORT_NUMERIC );

    $filtered_total = count( $order_ids );

    // Safety fallback: if start is out of bounds (e.g. from cached/saved state of a previous large dataset)
    if ( $start >= $filtered_total ) {
        $start = 0;
    }

    // Slice the IDs for pagination
    $sliced_ids = array_slice( $order_ids, $start, $length );
    
    $orders = array();
    foreach ( $sliced_ids as $oid ) {
        $order = wc_get_order( $oid );
        if ( $order ) {
            $orders[] = $order;
        }
    }
    
    $data = array();
    $footer_total = 0;
    $product_event_cache = array();
    
    foreach ( $orders as $order ) {
        if ( ! is_a( $order, 'WC_Order' ) ) continue;
        
        $order_id = $order->get_id();
        $delivered = intval( get_post_meta( $order_id, '_tbp_entregas_fisicas', true ) );
        $pkg_meta = get_post_meta( $order_id, '_tbp_entrega_paquetes', true );
        
        $is_rifa = $delivered > 0;
        
        $delivered_rules = get_post_meta( $order_id, '_tbp_delivery_rule_id' );
        $is_paquete_meta = ($pkg_meta === '1' || $pkg_meta === 1 || $pkg_meta === 'yes');
        if ( !empty($f_rule) ) {
            if ($f_rule === 'legacy_phase') {
                $is_paquete = $is_paquete_meta && empty($delivered_rules);
            } else {
                $is_paquete = in_array( $f_rule, $delivered_rules ) || $is_paquete_meta;
            }
        } else {
            $is_paquete = $is_paquete_meta;
        }
        
        $raw_status = $order->get_status();
        $status_name = wc_get_order_status_name( $raw_status );
        $name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
        $total = strip_tags(wc_price( $order->get_total() ));
        
        $event_names = array();
        foreach ( $order->get_items() as $item ) {
            $pid = $item->get_product_id();
            if ( ! isset( $product_event_cache[$pid] ) ) {
                $ev_id_meta = get_post_meta( $pid, '_tribe_wooticket_for_event', true );
                if ( ! $ev_id_meta ) $ev_id_meta = get_post_meta( $pid, '_tribe_tickets_for_event', true );
                $product_event_cache[$pid] = $ev_id_meta ? get_the_title( $ev_id_meta ) : false;
            }
            if ( $product_event_cache[$pid] ) $event_names[] = $product_event_cache[$pid];
        }
        $evt_str = !empty($event_names) ? implode(', ', array_unique($event_names)) : 'Sin Evento';
        
        if ( !empty($f_rule) && $f_rule !== 'legacy_phase' ) {
            $rule_numeric = crc32($f_rule);
            $last_log = $wpdb->get_row( $wpdb->prepare( "SELECT created_at FROM $table_logs WHERE order_id = %d AND rifa_id = %u ORDER BY created_at DESC LIMIT 1", $order_id, $rule_numeric ) );
        } else {
            $last_log = $wpdb->get_row( $wpdb->prepare( "SELECT created_at FROM $table_logs WHERE order_id = %d ORDER BY created_at DESC LIMIT 1", $order_id ) );
        }
        $date_str = $last_log ? date_i18n( 'd M Y', strtotime( $last_log->created_at ) ) : '---';
        
        $types = [];
        $qty_val = 0;
        
        if ( $f_type === 'PAQUETE' ) {
            $types[] = 'PAQUETE';
            $qty_val = $is_paquete ? '1' : '0';
            if ( $is_paquete ) {
                $footer_total += 1;
            }
        } elseif ( $f_type === 'RIFA' ) {
            $types[] = 'RIFA';
            $qty_val = $delivered;
            $footer_total += $delivered;
        } else {
            // Todos los tipos (vacío)
            if ($is_rifa) { $types[] = 'RIFA'; }
            if ($is_paquete) { $types[] = 'PAQUETE'; }
            
            if ($is_rifa) {
                $qty_val = $delivered;
                $footer_total += $delivered;
            } elseif ($is_paquete) {
                $qty_val = '1';
                $footer_total += 1;
            } else {
                $qty_val = '0';
            }
        }
        
        $type_str = !empty($types) ? implode(' / ', $types) : '---';
        $main_type = !empty($types) ? strtolower($types[0]) : 'pending';
        
        $badge_class = 'default';
        if ( $raw_status === 'completed' ) $badge_class = 'completed';
        elseif ( $raw_status === 'processing' ) $badge_class = 'processing';
        elseif ( $raw_status === 'on-hold' ) $badge_class = 'on-hold';
        elseif ( $raw_status === 'p-pagado' || strpos($raw_status, 'tarjeta') !== false ) $badge_class = 'p-pagado';

        $edit_url = $order->get_edit_order_url();
        $actions = '<a href="'.esc_url($edit_url).'" class="button button-small" target="_blank">Pedido</a> ';
        if ($is_rifa) $actions .= '<button class="button button-small tbp-btn-send-reminder" data-order-id="'.$order_id.'" style="background:#fff3cd; color:#856404; border-color:#ffeeba;">✉️ Cobro</button> ';
        
        if ( $is_rifa || $is_paquete ) {
            if ($is_rifa && $is_paquete) {
                $actions .= '<button class="button button-small tbp-btn-reset-order" data-order-id="'.$order_id.'" data-type="paquete" style="color: #dc3232; border-color: #dc3232;" title="Resetear solo Paquete">📦 Reset P.</button> ';
                $actions .= '<button class="button button-small tbp-btn-reset-order" data-order-id="'.$order_id.'" data-type="rifa" style="color: #dc3232; border-color: #dc3232;" title="Resetear solo Boletos">🎟️ Reset B.</button>';
            } else {
                $reset_btn_type = $is_rifa ? 'rifa' : 'paquete';
                $actions .= '<button class="button button-small tbp-btn-reset-order" data-order-id="'.$order_id.'" data-type="'.$reset_btn_type.'" style="color: #dc3232; border-color: #dc3232;">🗑️ Reset</button>';
            }
        }

        $data[] = array(
            '<input type="checkbox" class="tbp-cb-row" value="'.$order_id.'">',
            '<span class="tbp-type-badge '.$main_type.'">'.esc_html($type_str).'</span>',
            '<strong>#'.$order_id.'</strong>',
            '<span class="tbp-status-badge '.$badge_class.'">'.esc_html($status_name).'</span>',
            esc_html($name),
            $total,
            '<small>'.$date_str.'</small>',
            '<span class="tbp-qty '.((!$is_rifa && !$is_paquete) ? 'pending' : '').'">'.esc_html($qty_val).'</span>',
            esc_html($evt_str),
            $actions
        );
    }
    
    // === DEBUG TRACE (final response) ===
    $__debug['response_values'] = array(
        'recordsTotal'    => count($order_ids),
        'recordsFiltered' => $filtered_total,
        'data_rows'       => count($data),
        'footer_total'    => $footer_total,
    );
    error_log('[TBP-DEBUG] Response: recordsTotal=' . count($order_ids) . ' | recordsFiltered=' . $filtered_total . ' | data_rows=' . count($data));
    // === END DEBUG RESPONSE ===

    wp_send_json(array(
        "draw"            => intval($_POST['draw'] ?? 0),
        "recordsTotal"    => count($order_ids),
        "recordsFiltered" => $filtered_total,
        "data"            => $data,
        "footer_total"    => $footer_total,
        "stats_total"     => $stats_total,
        "stats_delivered" => $stats_delivered,
        "stats_pending"   => $stats_pending,
        "_debug"          => $__debug
    ));
}

/**
 * AJAX: Export CSV directly from PHP (server-side generation)
 */
add_action( 'wp_ajax_tbp_actividades_export_csv', 'tbp_actividades_export_csv' );
function tbp_actividades_export_csv() {
    // Verify nonce
    if ( ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'tbp_export_csv' ) ) {
        wp_die( 'Nonce inválido' );
    }
    
    // Check permissions
    if ( ! current_user_can( 'manage_options' ) && ! current_user_can( 'shop_manager' ) ) {
        wp_die( 'Sin permisos' );
    }
    
    // CRITICAL: Clean all output buffers and extend time limit
    while ( ob_get_level() ) {
        ob_end_clean();
    }
    @set_time_limit(300); // 5 minutes max
    @ini_set('memory_limit', '512M');
    
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    
    $f_type   = sanitize_text_field($_GET['f_type'] ?? '');
    $f_event  = sanitize_text_field($_GET['f_event'] ?? '');
    $f_rule   = sanitize_text_field($_GET['f_rule'] ?? '');
    
    $f_status_raw = sanitize_text_field($_GET['f_status'] ?? '');
    $f_status = !empty($f_status_raw) ? array_map('trim', explode(',', $f_status_raw)) : array();
    
    $f_scope = sanitize_text_field($_GET['f_scope'] ?? 'entregas'); // entregas, todos, ambos
    
    // Determine Event ID
    $ev_id = 0;
    if ( !empty($f_event) ) {
        if ( is_numeric($f_event) ) {
            $ev_id = intval($f_event);
        } else {
            $ev_id = $wpdb->get_var($wpdb->prepare("SELECT ID FROM $wpdb->posts WHERE post_title = %s AND post_type = 'tribe_events' LIMIT 1", $f_event));
        }
    }
    
    // Build SQL
    $status_where = "";
    if ( !empty($f_status) ) {
        $statuses = wc_get_order_statuses();
        $selected_slugs = array();
        foreach($statuses as $slug => $name) {
            if ( in_array($name, $f_status) ) { 
                $selected_slugs[] = $slug; 
            }
        }
        if ( !empty($selected_slugs) ) {
            $slugs_placeholder = implode( ',', array_fill( 0, count($selected_slugs), '%s' ) );
            $status_where = $wpdb->prepare(" AND p.post_status IN ($slugs_placeholder)", ...$selected_slugs);
        }
    }
    
    // Find all products associated with the graduation event
    $pkg_pids = array();
    $raffle_pids = array();
    if ( $ev_id > 0 ) {
        $pkg_pids = $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') AND meta_value = %s", $ev_id ) );
        $pkg_pids = array_map('intval', $pkg_pids);

        // Find all local raffle event IDs associated with the graduation event
        $raffle_event_ids = $wpdb->get_col( $wpdb->prepare( "
            SELECT pm2.meta_value 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm1 ON p.ID = pm1.post_id
            INNER JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id
            WHERE p.post_type = 'tbp_rifas'
              AND pm1.meta_key = '_tbp_event_id' AND pm1.meta_value = %s
              AND pm2.meta_key = '_tbp_local_raffle_id' AND pm2.meta_value > 0
        ", $ev_id ) );
        
        if ( !empty($raffle_event_ids) ) {
            $raffle_event_ids_str = implode(',', array_map('intval', $raffle_event_ids));
            $raffle_pids = $wpdb->get_col( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') 
                  AND meta_value IN ($raffle_event_ids_str)
            " );
            $raffle_pids = array_map('intval', $raffle_pids);
        }
    } else {
        // Retrieve all raffle product IDs in the system
        $all_raffle_event_ids = $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_tbp_local_raffle_id' AND meta_value > 0" );
        if ( !empty($all_raffle_event_ids) ) {
            $all_raffle_event_ids_str = implode(',', array_map('intval', $all_raffle_event_ids));
            $raffle_pids = $wpdb->get_col( "
                SELECT post_id FROM {$wpdb->postmeta} 
                WHERE meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_for_event') 
                  AND meta_value IN ($all_raffle_event_ids_str)
            " );
            $raffle_pids = array_map('intval', $raffle_pids);
        }
    }

    $event_join = "";
    $event_where = "";
    if ( $ev_id > 0 ) {
        // Find products for this event
        $p_ids = $pkg_pids;
        
        // If a specific rule (fase) is selected, restrict to only the enabled product IDs
        if ( !empty($f_rule) && $f_rule !== 'legacy_phase' && !empty($p_ids) ) {
            $rules = get_post_meta( $ev_id, '_tbp_event_delivery_rules', true );
            if ( is_array($rules) ) {
                foreach ( $rules as $rule ) {
                    if ( isset($rule['id']) && $rule['id'] === $f_rule && isset($rule['tickets']) ) {
                        $enabled_pids = array();
                        foreach ( $rule['tickets'] as $pid => $ticket_cfg ) {
                            if ( !empty($ticket_cfg['apply']) ) {
                                $enabled_pids[] = intval($pid);
                            }
                        }
                        if ( !empty($enabled_pids) ) {
                            // Intersect: keep only products that exist in event AND are enabled in the rule
                            $p_ids = array_values(array_intersect(array_map('intval', $p_ids), $enabled_pids));
                        }
                        break;
                    }
                }
            }
        }

        // Apply type filter to product selection for base query
        if ( $f_type === 'PAQUETE' ) {
            $p_ids = array_diff($p_ids, $raffle_pids);
        } elseif ( $f_type === 'RIFA' ) {
            $p_ids = array_intersect($p_ids, $raffle_pids);
            if ( empty($p_ids) ) {
                $p_ids = $raffle_pids;
            }
            if ( empty($p_ids) ) {
                $p_ids = $pkg_pids;
            }
        }
        
        if ( !empty($p_ids) ) {
            $p_ids_str = implode(',', array_map('intval', $p_ids));
            $event_join = " INNER JOIN {$wpdb->prefix}woocommerce_order_items oi ON p.ID = oi.order_id 
                            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id ";
            $event_where = " AND oi.order_item_type = 'line_item' AND oim.meta_key = '_product_id' AND oim.meta_value IN ($p_ids_str) ";
        } else {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="reporte_entregas_vacio.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, array('Sin resultados para los filtros seleccionados'));
            fclose($output);
            exit;
        }
    }
    
    // Get base IDs (all orders matching event + status + product filters)
    $sql_base = "SELECT p.ID FROM {$wpdb->posts} p $event_join WHERE p.post_type = 'shop_order' $status_where $event_where";
    $all_base_ids = $wpdb->get_col("SELECT DISTINCT ID FROM ($sql_base) as base");
    if (empty($all_base_ids)) $all_base_ids = array(0);

    // Refine base IDs in PHP to prevent mixing
    if ( !empty($all_base_ids) && $all_base_ids[0] !== 0 ) {
        $base_ids_str = implode(',', array_map('intval', $all_base_ids));
        
        $order_items_db = $wpdb->get_results("
            SELECT oi.order_id, oim.meta_value as product_id
            FROM {$wpdb->prefix}woocommerce_order_items oi
            INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
            WHERE oi.order_id IN ($base_ids_str)
              AND oi.order_item_type = 'line_item'
              AND oim.meta_key = '_product_id'
        ");
        
        $order_to_pids = array();
        foreach ($order_items_db as $oi) {
            $oid = intval($oi->order_id);
            $pid = intval($oi->product_id);
            $order_to_pids[$oid][] = $pid;
        }

        $order_meta_db = $wpdb->get_results("
            SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE post_id IN ($base_ids_str)
              AND meta_key IN ('_tbp_entregas_fisicas', '_tbp_entrega_paquetes')
        ");
        $order_to_meta = array();
        foreach ($order_meta_db as $om) {
            $oid = intval($om->post_id);
            $order_to_meta[$oid][$om->meta_key] = $om->meta_value;
        }

        $filtered_base_ids = array();
        foreach ($all_base_ids as $oid) {
            $oid = intval($oid);
            $pids = $order_to_pids[$oid] ?? array();
            
            $has_pkg_items = false;
            $has_raffle_items = false;
            
            foreach ($pids as $pid) {
                if ( in_array($pid, $raffle_pids) ) {
                    $has_raffle_items = true;
                } else {
                    if ( empty($pkg_pids) || in_array($pid, $pkg_pids) ) {
                        $has_pkg_items = true;
                    }
                }
            }
            
            $delivered_fisicas = intval($order_to_meta[$oid]['_tbp_entregas_fisicas'] ?? 0);
            $pkg_delivered = $order_to_meta[$oid]['_tbp_entrega_paquetes'] ?? '';
            $has_pkg_delivery = ($pkg_delivered === '1' || $pkg_delivered === 'yes');
            $has_raffle_delivery = ($delivered_fisicas > 0);
            
            $is_pkg_order = ($has_pkg_items || $has_pkg_delivery);
            $is_rifa_order = ($has_raffle_items || $has_raffle_delivery);
            
            if ( $f_type === 'PAQUETE' ) {
                if ( $is_pkg_order ) {
                    $filtered_base_ids[] = $oid;
                }
            } elseif ( $f_type === 'RIFA' ) {
                if ( $is_rifa_order ) {
                    $filtered_base_ids[] = $oid;
                }
            } else {
                $filtered_base_ids[] = $oid;
            }
        }
        
        $all_base_ids = !empty($filtered_base_ids) ? $filtered_base_ids : array(0);
        $base_ids_str = implode(',', array_map('intval', $all_base_ids));
    }
    
    // Get delivered IDs
    $delivered_where = "";
    if ( !empty($f_rule) ) {
        if ($f_rule === 'legacy_phase') {
            $delivered_where = " AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes')) AND post_id NOT IN (SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_tbp_delivery_rule_id') ";
        } else {
            $delivered_where = $wpdb->prepare(" AND post_id IN (SELECT post_id FROM $wpdb->postmeta WHERE (meta_key = '_tbp_delivery_rule_id' AND meta_value = %s) OR (meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes'))) ", $f_rule);
        }
    } else {
        if ( $f_type === 'RIFA' ) {
            $delivered_where = " AND meta_key = '_tbp_entregas_fisicas' AND meta_value > 0 ";
        } elseif ( $f_type === 'PAQUETE' ) {
            $delivered_where = " AND meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes') ";
        } else {
            $delivered_where = " AND ((meta_key = '_tbp_entregas_fisicas' AND meta_value > 0) OR (meta_key = '_tbp_entrega_paquetes' AND meta_value IN ('1', 'yes'))) ";
        }
    }
    
    $delivered_ids = $wpdb->get_col("SELECT DISTINCT post_id FROM $wpdb->postmeta WHERE post_id IN ($base_ids_str) $delivered_where");
    $delivered_set = array_flip($delivered_ids); // For quick lookups
    
    // Determine which order IDs to export based on scope
    if ( $f_scope === 'todos' ) {
        $order_ids = ($all_base_ids[0] === 0) ? array() : $all_base_ids;
    } elseif ( $f_scope === 'ambos' ) {
        $order_ids = ($all_base_ids[0] === 0) ? array() : $all_base_ids;
    } elseif ( $f_scope === 'pendientes' ) {
        if ($all_base_ids[0] === 0) {
            $order_ids = array();
        } else {
            $order_ids = array_diff($all_base_ids, $delivered_ids);
        }
    } else {
        // 'entregas' (default)
        $order_ids = $delivered_ids;
    }
    
    if (empty($order_ids)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_vacio.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, array('Sin resultados para los filtros seleccionados'));
        fclose($output);
        exit;
    }
    
    // === ULTRA-FAST: Pure SQL approach - NO wc_get_order() objects ===
    $ids_str = implode(',', array_map('intval', $order_ids));
    
    // If a specific rule is set, check if it's assigned to the order in the pivot query
    $rule_select = "";
    $rule_meta_in = "";
    if ( !empty($f_rule) ) {
        $rule_meta_in = ",'_tbp_delivery_rule_id'";
        if ($f_rule === 'legacy_phase') {
            $rule_select = ", MAX(CASE WHEN pm.meta_key = '_tbp_delivery_rule_id' THEN 'yes' END) as has_any_rule";
        } else {
            $rule_select = $wpdb->prepare(", MAX(CASE WHEN pm.meta_key = '_tbp_delivery_rule_id' AND pm.meta_value = %s THEN 'yes' END) as has_matching_rule", $f_rule);
        }
    }
    
    // 1. Core order data via SQL pivot (ONE query for all 990 orders)
    $order_rows = $wpdb->get_results("
        SELECT p.ID as order_id, p.post_status,
            MAX(CASE WHEN pm.meta_key = '_billing_first_name' THEN pm.meta_value END) as first_name,
            MAX(CASE WHEN pm.meta_key = '_billing_last_name' THEN pm.meta_value END) as last_name,
            MAX(CASE WHEN pm.meta_key = '_order_total' THEN pm.meta_value END) as order_total,
            MAX(CASE WHEN pm.meta_key = '_tbp_entregas_fisicas' THEN pm.meta_value END) as entregas_fisicas,
            MAX(CASE WHEN pm.meta_key = '_tbp_entrega_paquetes' THEN pm.meta_value END) as entrega_paquetes
            $rule_select
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id 
            AND pm.meta_key IN ('_billing_first_name','_billing_last_name','_order_total','_tbp_entregas_fisicas','_tbp_entrega_paquetes' $rule_meta_in)
        WHERE p.ID IN ($ids_str)
        GROUP BY p.ID
        ORDER BY p.ID DESC
    ");
    
    if (empty($order_rows)) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_vacio.csv"');
        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, array('Sin resultados'));
        fclose($output);
        exit;
    }
    
    // 2. Delivery log dates (ONE query)
    $rule_filter_sql = "";
    if ( !empty($f_rule) && $f_rule !== 'legacy_phase' ) {
        $rule_numeric = crc32($f_rule);
        $rule_filter_sql = $wpdb->prepare(" AND rifa_id = %u ", $rule_numeric);
    }
    
    $log_dates = $wpdb->get_results("
        SELECT order_id, MAX(created_at) as last_date
        FROM $table_logs WHERE order_id IN ($ids_str) $rule_filter_sql
        GROUP BY order_id
    ", OBJECT_K);
    
    // 3. Event names via product links (TWO queries)
    $pi_rows = $wpdb->get_results("
        SELECT oi.order_id, oim.meta_value as product_id
        FROM {$wpdb->prefix}woocommerce_order_items oi
        JOIN {$wpdb->prefix}woocommerce_order_itemmeta oim ON oi.order_item_id = oim.order_item_id
        WHERE oi.order_id IN ($ids_str) AND oi.order_item_type = 'line_item' AND oim.meta_key = '_product_id'
    ");
    $all_pids = array_unique(array_map('intval', array_column($pi_rows, 'product_id')));
    $prod_event_map = array();
    if (!empty($all_pids)) {
        $pids_str = implode(',', $all_pids);
        $ev_links = $wpdb->get_results("SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE post_id IN ($pids_str) AND meta_key IN ('_tribe_wooticket_for_event','_tribe_tickets_for_event')");
        foreach ($ev_links as $el) $prod_event_map[intval($el->post_id)] = get_the_title($el->meta_value);
    }
    $order_evt_map = array();
    foreach ($pi_rows as $pi) {
        $oid = intval($pi->order_id); $pid = intval($pi->product_id);
        if (isset($prod_event_map[$pid])) { 
            if (!isset($order_evt_map[$oid])) $order_evt_map[$oid] = array();
            $order_evt_map[$oid][] = $prod_event_map[$pid];
        }
    }
    
    // 4. Batch attendee metadata (~5 queries via optimized function)
    $batch_meta = array();
    if (function_exists('tbp_actividades_get_batch_attendees_meta')) {
        $batch_meta = tbp_actividades_get_batch_attendees_meta($order_ids);
    }
    
    $label_map = array('0'=>'Grupo','1'=>'Talla de Camisa','group'=>'Grupo','grupo'=>'Grupo','shirt_size'=>'Talla de Camisa','talla'=>'Talla de Camisa');
    $wc_statuses = wc_get_order_statuses();
    
    // === Process rows + discover meta labels ===
    $all_meta_labels = array();
    $orders_data = array();
    
    foreach ($order_rows as $r) {
        $oid = intval($r->order_id);
        $del = intval($r->entregas_fisicas);
        $pkg = $r->entrega_paquetes;
        $is_rifa = $del > 0;
        
        $is_pkg_meta = ($pkg === '1' || $pkg === 'yes');
        if ( !empty($f_rule) ) {
            if ($f_rule === 'legacy_phase') {
                $is_pkg = $is_pkg_meta && ($r->has_any_rule !== 'yes');
            } else {
                $is_pkg = ($r->has_matching_rule === 'yes') || $is_pkg_meta;
            }
        } else {
            $is_pkg = $is_pkg_meta;
        }
        $is_del = isset($delivered_set[$oid]);
        
        $types = [];
        $qty_val = 0;
        if ( $f_type === 'PAQUETE' ) {
            $types[] = 'PAQUETE';
            $qty_val = $is_pkg ? '1' : '0';
        } elseif ( $f_type === 'RIFA' ) {
            $types[] = 'RIFA';
            $qty_val = $del;
        } else {
            if ($is_rifa) $types[] = 'RIFA';
            if ($is_pkg) $types[] = 'PAQUETE';
            $qty_val = $is_rifa ? $del : ($is_pkg ? '1' : '0');
        }
        
        $types_str = !empty($types) ? implode(' / ', $types) : '---';
        
        $evts = isset($order_evt_map[$oid]) ? array_unique($order_evt_map[$oid]) : array();
        $log = $log_dates[$oid] ?? null;
        
        // Extract attendee meta from batch
        $mf = array();
        $oatt = $batch_meta[$oid] ?? array();
        if (!empty($oatt)) {
            foreach ($oatt as $sid => $mg) {
                if ($mg === null) continue;
                if (!empty($mg) && !is_array(current($mg))) $mg = array(0 => $mg);
                foreach ($mg as $gd) {
                    if (!is_array($gd)) continue;
                    foreach ((array)$gd as $k => $v) {
                        $rl = is_array($v) ? ($v['label'] ?? $k) : $k;
                        $vl = is_array($v) ? ($v['value'] ?? '') : $v;
                        if (is_array($vl)) $vl = implode(', ', $vl);
                        $vl = trim(strval($vl));
                        if (empty($vl) || strpos($k, '_') === 0) continue;
                        $kl = strtolower(str_replace(array('-','_',' '), '', strval($rl)));
                        $lb = $label_map[$kl] ?? $label_map[strval($rl)] ?? ucwords(str_replace(array('-','_'), ' ', strtolower(strval($rl))));
                        if (!in_array($lb, $all_meta_labels)) $all_meta_labels[] = $lb;
                        if (isset($mf[$lb]) && $mf[$lb] !== $vl) { $mf[$lb] .= ' | ' . $vl; } else { $mf[$lb] = $vl; }
                    }
                }
            }
        }
        
        $orders_data[] = array(
            't' => $types_str,
            'id' => $oid,
            's' => $wc_statuses[$r->post_status] ?? $r->post_status,
            'n' => trim(($r->first_name ?? '') . ' ' . ($r->last_name ?? '')),
            'm' => '$' . number_format(floatval($r->order_total), 2),
            'd' => $log ? date_i18n('d M Y', strtotime($log->last_date)) : '---',
            'q' => $qty_val,
            'e' => !empty($evts) ? implode(', ', $evts) : 'Sin Evento',
            'dl' => $is_del,
            'mf' => $mf,
        );
    }
    
    // === OUTPUT CSV ===
    $sl = $f_scope === 'todos' ? 'TodosPedidos' : ($f_scope === 'ambos' ? 'PedidosYEntregas' : ($f_scope === 'pendientes' ? 'PendientesEntrega' : 'Entregas'));
    $filename = 'Reporte_' . $sl . '_TBP_' . date('Y-m-d_His') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    $headers = array('Tipo', '# Pedido', 'Status', 'Titular del Pedido', 'Monto', 'Fecha Entrega', 'Cant', 'Evento');
    if ($f_scope === 'ambos' || $f_scope === 'todos' || $f_scope === 'pendientes') $headers[] = 'Entregado';
    foreach ($all_meta_labels as $ml) $headers[] = $ml;
    fputcsv($output, $headers);
    
    foreach ($orders_data as $od) {
        $row = array($od['t'], '#'.$od['id'], $od['s'], $od['n'], $od['m'], $od['d'], $od['q'], $od['e']);
        if ($f_scope === 'ambos' || $f_scope === 'todos' || $f_scope === 'pendientes') $row[] = $od['dl'] ? 'SI' : 'NO';
        foreach ($all_meta_labels as $ml) $row[] = $od['mf'][$ml] ?? '';
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

