<?php
/**
 * Marketing Module for TBP Actividades
 * Handling XLSX/CSV imports for external campaigns
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render the Marketing Page
 */
function tbp_actividades_marketing_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        return;
    }

    ?>
    <div class="wrap">
        <h1><span class="dashicons dashicons-megaphone" style="font-size: 30px; width: 30px; height: 30px; margin-top: 5px;"></span> <?php _e( 'MARKETING - Centro de Campañas Externas', 'tbp-actividades' ); ?></h1>
        <p><?php _e( 'Sube una lista de contactos (XLSX o CSV) para enviar mensajes personalizados sin necesidad de que sean clientes registrados.', 'tbp-actividades' ); ?></p>

        <!-- List Management -->
        <div id="wcmp-mkt-list-manager" style="background: #f0f0f1; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h3><?php _e( '📂 Gestión de Listas de Contactos', 'tbp-actividades' ); ?></h3>
            <div style="display:flex; gap:20px; align-items:flex-end;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e( 'Seleccionar Lista Existente:', 'tbp-actividades' ); ?></label>
                    <select id="wcmp-mkt-active-list" style="width:100%;">
                        <option value=""><?php _e( '-- Nueva Lista --', 'tbp-actividades' ); ?></option>
                        <?php 
                        global $wpdb;
                        $table_lists = $wpdb->prefix . 'tbp_marketing_lists';
                        $lists = $wpdb->get_results( "SELECT * FROM $table_lists ORDER BY created_at DESC" );
                        foreach ( $lists as $list ) {
                            echo '<option value="' . esc_attr( $list->id ) . '">' . esc_html( $list->name ) . ' (' . date('d/M/y', strtotime($list->created_at)) . ')</option>';
                        }
                        ?>
                    </select>
                </div>
                <div style="flex:1;" id="wcmp-mkt-new-list-wrap">
                    <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e( 'Nombre de la Nueva Lista:', 'tbp-actividades' ); ?></label>
                    <input type="text" id="wcmp-mkt-new-list-name" class="regular-text" style="width:100%;" placeholder="Ej: Prospectos Florida 2024">
                </div>
                <button type="button" class="button" id="wcmp-mkt-btn-manage-contacts" style="display:none;"><?php _e( 'Ver Contactos', 'tbp-actividades' ); ?></button>
            </div>
        </div>

        <!-- Sandbox Mode: Always Visible -->
        <div id="wcmp-mkt-sandbox-wrap" style="background: #fff8e1; border: 1px solid #ffe082; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h3 style="margin-top:0;">🧪 <?php _e( 'Modo Sandbox: Validar Diseño y Entregabilidad', 'tbp-actividades' ); ?></h3>
            <p><?php _e( 'Usa esta sección para enviarte correos de prueba. Verifica cómo se ven tus plantillas de Canva o tus mensajes manuales antes de lanzarlos.', 'tbp-actividades' ); ?></p>
            
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div style="flex:1; min-width:300px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e( 'Asunto de Prueba:', 'tbp-actividades' ); ?></label>
                    <input type="text" id="wcmp-mkt-subject-test" class="regular-text" style="width:100%;" placeholder="Ej: Prueba de diseño">
                </div>
                <div style="flex:1; min-width:300px;">
                    <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e( 'Enviar a:', 'tbp-actividades' ); ?></label>
                    <div style="display:flex; gap:10px;">
                        <input type="email" id="wcmp-mkt-test-email" class="regular-text" style="flex:1;" placeholder="tu-correo@ejemplo.com">
                        <button type="button" class="button button-secondary" id="wcmp-mkt-btn-send-test"><?php _e( 'Enviar Prueba', 'tbp-actividades' ); ?></button>
                    </div>
                </div>
            </div>
            
            <div style="margin-top:15px;">
                <label style="display:block; margin-bottom:5px; font-weight:600;"><?php _e( 'Mensaje o Plantilla a Probar:', 'tbp-actividades' ); ?></label>
                <?php $mkt_templates = get_option( 'tbp_marketing_templates', array() ); ?>
                <select id="wcmp-mkt-template-test" style="width:100%; margin-bottom:10px;">
                    <option value=""><?php _e( '-- Usar Editor Manual (Abajo) --', 'tbp-actividades' ); ?></option>
                    <?php if ( ! empty( $mkt_templates ) ) : ?>
                        <?php foreach ( $mkt_templates as $tid => $tdata ) : ?>
                            <option value="<?php echo esc_attr( $tid ); ?>">Plantilla Canva: <?php echo esc_html( $tdata['name'] ); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
                <div id="wcmp-mkt-editor-test-wrap">
                    <?php wp_editor( '', 'wcmp_mkt_message_test', array('media_buttons' => true, 'textarea_rows' => 5) ); ?>
                </div>
            </div>
        </div>

        <!-- Step 1: Upload -->
        <div id="wcmp-mkt-step-1" style="background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h3><?php _e( '1. Subir archivo de contactos', 'tbp-actividades' ); ?></h3>
            <input type="file" id="wcmp-mkt-file" accept=".xlsx, .csv" style="margin-bottom: 15px;">
            <p class="description"><?php _e( 'El archivo debe contener al menos una columna de Nombre y una de Email.', 'tbp-actividades' ); ?></p>
            <button type="button" class="button button-primary" id="wcmp-mkt-btn-upload"><?php _e( 'Procesar Archivo', 'tbp-actividades' ); ?></button>
        </div>

        <!-- Step 2: Mapping (Hidden) -->
        <div id="wcmp-mkt-step-2" style="display:none; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h3><?php _e( '2. Mapear Columnas', 'tbp-actividades' ); ?></h3>
            <p><?php _e( 'Indica al sistema qué columna corresponde a cada dato:', 'tbp-actividades' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label><?php _e( 'Columna Nombre:', 'tbp-actividades' ); ?></label></th>
                    <td><select id="wcmp-mkt-map-name" class="wcmp-mkt-col-map"></select></td>
                </tr>
                <tr>
                    <th><label><?php _e( 'Columna Email:', 'tbp-actividades' ); ?></label></th>
                    <td><select id="wcmp-mkt-map-email" class="wcmp-mkt-col-map"></select></td>
                </tr>
                <tr>
                    <th><label><?php _e( 'Columna Celular (Opcional):', 'tbp-actividades' ); ?></label></th>
                    <td><select id="wcmp-mkt-map-phone" class="wcmp-mkt-col-map"></select></td>
                </tr>
            </table>
            <button type="button" class="button button-primary" id="wcmp-mkt-btn-confirm-map"><?php _e( 'Confirmar y Continuar', 'tbp-actividades' ); ?></button>
        </div>

        <!-- Step 3: Messaging (Hidden) -->
        <div id="wcmp-mkt-step-3" style="display:none; background: #fff; border: 1px solid #ccd0d4; padding: 20px; border-radius: 4px; margin-top: 20px;">
            <h2 style="color:#673ab7;"><?php _e( '3. Configurar Mensaje Masivo', 'tbp-actividades' ); ?> (<strong id="wcmp-mkt-count">0</strong> <?php _e( 'contactos detectados', 'tbp-actividades'); ?>)</h2>
            
            <p><strong><?php _e( 'Asunto del Correo:', 'tbp-actividades' ); ?></strong></p>
            <input type="text" id="wcmp-mkt-subject" class="regular-text" style="width:100%; margin-bottom:15px;" placeholder="Ej. ¡Tenemos una promoción para ti!">

            <?php $mkt_templates = get_option( 'tbp_marketing_templates', array() ); ?>
            <p><strong><?php _e( 'Plantilla a enviar (Opcional Canva):', 'tbp-actividades' ); ?></strong></p>
            <select id="wcmp-mkt-template" style="width:100%; margin-bottom:15px;">
                <option value=""><?php _e( '-- Escribir Mensaje Manualmente (Abajo) --', 'tbp-actividades' ); ?></option>
                <?php if ( ! empty( $mkt_templates ) ) : ?>
                    <?php foreach ( $mkt_templates as $tid => $tdata ) : ?>
                        <option value="<?php echo esc_attr( $tid ); ?>">Plantilla Canva: <?php echo esc_html( $tdata['name'] ); ?></option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>

            <div id="wcmp-mkt-editor-wrap">
                <p><strong><?php _e( 'Etiquetas disponibles:', 'tbp-actividades' ); ?></strong> <code>[nombre]</code></p>
                <?php wp_editor( '', 'wcmp_mkt_message', array('media_buttons' => true, 'textarea_rows' => 8) ); ?>
            </div>

            <div style="margin-top:20px; padding: 15px; background: #f1f8ff; border: 1px solid #c2e0ff; border-radius: 4px;">
                <p style="margin-top:0;"><strong>📅 <?php _e( 'Programación (Opcional)', 'tbp-actividades' ); ?></strong></p>
                <div style="display:flex; gap:10px; align-items:center;">
                    <label style="cursor:pointer;"><input type="checkbox" id="wcmp-mkt-schedule-check"> <?php _e( 'Programar para después', 'tbp-actividades' ); ?></label>
                    <input type="datetime-local" id="wcmp-mkt-schedule-time" style="display:none; height: 30px;">
                </div>
                <p class="description"><?php _e( 'Si no programas, el envío comenzará inmediatamente por lotes.', 'tbp-actividades' ); ?></p>
            </div>

            <div style="margin-top:20px;">
                <button type="button" class="button button-primary" id="wcmp-mkt-btn-send" style="background:#673ab7; border-color:#512da8; height: 40px; padding: 0 30px; font-weight: bold;"><?php _e( '🚀 ¡Iniciar Envío de Campaña!', 'tbp-actividades' ); ?></button>
            </div>

            <!-- Progress -->
            <div id="wcmp-mkt-progress-wrapper" style="display:none; margin-top:25px; padding: 20px; background: #f8f9fa; border: 1px solid #eee;">
                <p><strong id="wcmp-mkt-status-text"><?php _e( 'Enviando...', 'tbp-actividades' ); ?></strong> (<span id="wcmp-mkt-progress-count">0</span> / <span id="wcmp-mkt-total-count">0</span>)</p>
                <div style="width:100%; background:#e9ecef; border-radius:4px; height:20px; overflow:hidden;">
                    <div id="wcmp-mkt-progress-bar" style="width:0%; background:#28a745; height:100%; transition:width 0.3s ease;"></div>
                </div>
                <ul id="wcmp-mkt-log" style="margin-top:15px; max-height:150px; overflow-y:auto; font-family:monospace; font-size:12px; border-top:1px solid #ddd; padding-top:10px;"></ul>
            </div>
        </div>

        <!-- Dashboard: Recents Campaigns -->
        <div style="margin-top:40px;">
            <h2>📊 <?php _e( 'Resumen de Campañas Recientes', 'tbp-actividades' ); ?></h2>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e( 'Fecha', 'tbp-actividades' ); ?></th>
                        <th><?php _e( 'Asunto / Campaña', 'tbp-actividades' ); ?></th>
                        <th><?php _e( 'Lista', 'tbp-actividades' ); ?></th>
                        <th><?php _e( 'Estatus', 'tbp-actividades' ); ?></th>
                        <th style="text-align:center;"><?php _e( 'Aperturas', 'tbp-actividades' ); ?></th>
                        <th style="text-align:center;"><?php _e( 'Clics', 'tbp-actividades' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $table_camps = $wpdb->prefix . 'tbp_marketing_campaigns';
                    $table_lists = $wpdb->prefix . 'tbp_marketing_lists';
                    $recent_camps = $wpdb->get_results( "SELECT c.*, l.name as list_name FROM $table_camps c LEFT JOIN $table_lists l ON c.list_id = l.id ORDER BY c.created_at DESC LIMIT 10" );
                    
                    if ( empty( $recent_camps ) ) {
                        echo '<tr><td colspan="6" style="text-align:center; padding:20px; color:#999;">' . __( 'Aún no has enviado campañas.', 'tbp-actividades' ) . '</td></tr>';
                    } else {
                        foreach ( $recent_camps as $camp ) {
                            $status_label = ($camp->status == 'sent') ? '<span style="color:#1e8c38; font-weight:bold;">✅ Enviada</span>' : (($camp->status == 'sending') ? '<span style="color:#673ab7; font-weight:bold;">⚡ Procesando...</span>' : '<span style="color:#2271b1; font-weight:bold;">📅 Programada</span>');
                            echo '<tr>';
                            echo '<td>' . date('d/M H:i', strtotime($camp->created_at)) . '</td>';
                            echo '<td><strong>' . esc_html($camp->subject) . '</strong></td>';
                            echo '<td>' . esc_html($camp->list_name) . '</td>';
                            echo '<td>' . $status_label . '</td>';
                            echo '<td style="text-align:center; font-size:16px;"><strong>' . intval($camp->stats_opened) . '</strong></td>';
                            echo '<td style="text-align:center; font-size:16px;"><strong>' . intval($camp->stats_clicked) . '</strong></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- XLSX/CSV Processing Logic -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    jQuery(document).ready(function($) {
        var raw_data = [];
        var headers = [];

        // List Management UI
        $('#wcmp-mkt-active-list').on('change', function() {
            if ($(this).val() !== '') {
                $('#wcmp-mkt-new-list-wrap').fadeOut();
                $('#wcmp-mkt-btn-manage-contacts').fadeIn();
            } else {
                $('#wcmp-mkt-new-list-wrap').fadeIn();
                $('#wcmp-mkt-btn-manage-contacts').fadeOut();
            }
        });

        $('#wcmp-mkt-schedule-check').on('change', function() {
            if ($(this).is(':checked')) $('#wcmp-mkt-schedule-time').fadeIn();
            else $('#wcmp-mkt-schedule-time').fadeOut();
        });

        // File Processing
        $('#wcmp-mkt-btn-upload').on('click', function() {
            var file = $('#wcmp-mkt-file')[0].files[0];
            if (!file) { alert('Por favor selecciona un archivo.'); return; }

            var reader = new FileReader();
            reader.onload = function(e) {
                var data = new Uint8Array(e.target.result);
                var workbook = XLSX.read(data, {type: 'array'});
                var first_sheet = workbook.Sheets[workbook.SheetNames[0]];
                raw_data = XLSX.utils.sheet_to_json(first_sheet, {header: 1});

                if (raw_data.length < 2) { alert('El archivo parece estar vacío.'); return; }

                headers = raw_data[0];
                var options = '<option value="-1">-- Seleccionar --</option>';
                headers.forEach(function(h, i) {
                    options += '<option value="' + i + '">' + h + '</option>';
                });

                $('.wcmp-mkt-col-map').html(options);
                $('#wcmp-mkt-step-1').slideUp();
                $('#wcmp-mkt-step-2').slideDown();
            };
            reader.readAsArrayBuffer(file);
        });

        // Mapping Confirmation & Saving to DB
        $('#wcmp-mkt-btn-confirm-map').on('click', function() {
            var name_col = $('#wcmp-mkt-map-name').val();
            var email_col = $('#wcmp-mkt-map-email').val();
            var phone_col = $('#wcmp-mkt-map-phone').val();
            var list_id = $('#wcmp-mkt-active-list').val();
            var list_name = $('#wcmp-mkt-new-list-name').val().trim();
            
            if (name_col == -1 || email_col == -1) {
                alert('Debes mapear al menos el Nombre y el Email.');
                return;
            }

            if (!list_id && !list_name) {
                alert('Ingresa un nombre para la nueva lista o selecciona una existente.');
                return;
            }

            $(this).prop('disabled', true).text('Guardando en Base de Datos...');
            var $btn = $(this);

            // Prepare Contacts to save
            var contacts = [];
            for (var i = 1; i < raw_data.length; i++) {
                contacts.push({
                    name: raw_data[i][name_col],
                    email: raw_data[i][email_col],
                    phone: phone_col != -1 ? raw_data[i][phone_col] : ''
                });
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_save_mkt_contacts',
                    list_id: list_id,
                    list_name: list_name,
                    contacts: JSON.stringify(contacts),
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_ext_mkt"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        $('#wcmp-mkt-active-list').append('<option value="'+res.data.list_id+'" selected>'+res.data.list_name+'</option>').trigger('change');
                        $('#wcmp-mkt-count').text(contacts.length);
                        $('#wcmp-mkt-step-2').slideUp();
                        $('#wcmp-mkt-step-3').slideDown();
                    } else {
                        alert('Error al guardar: ' + res.data);
                    }
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Confirmar e Importar');
                }
            });
        });

        $('#wcmp-mkt-template').on('change', function() {
            if ($(this).val() !== '') $('#wcmp-mkt-editor-wrap').slideUp();
            else $('#wcmp-mkt-editor-wrap').slideDown();
        });

        // Campaign Sending Engine
        var batch_size = 25;
        var pause_ms = 60000;
        var current_batch = 0;
        var queue = [];
        var total_to_send = 0;
        var active_campaign_id = 0;

        $('#wcmp-mkt-btn-send').on('click', function() {
            var subject = $('#wcmp-mkt-subject').val().trim();
            if (!subject) { alert('El asunto es obligatorio.'); return; }

            if (!confirm('¿Estás seguro de enviar esta campaña a ' + (raw_data.length - 1) + ' contactos externos?')) return;

            // 1. Iniciar Campaña en DB
            $(this).prop('disabled', true).text('Iniciando...');
            var $btn = $(this);
            var is_scheduled = $('#wcmp-mkt-schedule-check').is(':checked');
            var schedule_time = $('#wcmp-mkt-schedule-time').val();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_start_mkt_campaign',
                    list_id: $('#wcmp-mkt-active-list').val(),
                    subject: subject,
                    template_id: $('#wcmp-mkt-template').val(),
                    message: (typeof tinymce != "undefined" && tinymce.get("wcmp_mkt_message")) ? tinymce.get("wcmp_mkt_message").getContent() : $('#wcmp_mkt_message').val(),
                    scheduled_at: is_scheduled ? schedule_time : '',
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_ext_mkt"); ?>'
                },
                success: function(res) {
                    if (res.success) {
                        active_campaign_id = res.data.campaign_id;
                        
                        if (is_scheduled) {
                            alert('¡Campaña programada con éxito para el ' + schedule_time + '! El sistema se encargará del envío automáticamente.');
                            location.reload();
                            return;
                        }

                        queue = raw_data.slice(1);
                        total_to_send = queue.length;
                        $('#wcmp-mkt-progress-wrapper').slideDown();
                        $('#wcmp-mkt-total-count').text(total_to_send);
                        processNextMkt();
                    } else {
                        alert('Error al iniciar campaña: ' + res.data);
                        $btn.prop('disabled', false).text('🚀 ¡Iniciar Envío de Campaña!');
                    }
                }
            });
        });

        $('#wcmp-mkt-btn-send-test').on('click', function() {
            var email = $('#wcmp-mkt-test-email').val().trim();
            var subject = $('#wcmp-mkt-subject-test').val().trim();
            var tid = $('#wcmp-mkt-template-test').val();
            
            if (!email || !subject) {
                alert('Ingresa un correo de prueba y un asunto.');
                return;
            }

            $(this).prop('disabled', true).text('Enviando...');
            var $btn = $(this);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_send_test_mkt',
                    email: email,
                    subject: subject,
                    template_id: tid,
                    message: (typeof tinymce != "undefined" && tinymce.get("wcmp_mkt_message_test")) ? tinymce.get("wcmp_mkt_message_test").getContent() : $('#wcmp_mkt_message_test').val(),
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_ext_mkt"); ?>'
                },
                success: function(res) {
                    if (res.success) alert('¡Correo de prueba enviado con éxito!');
                    else alert('Error: ' + res.data);
                },
                complete: function() {
                    $btn.prop('disabled', false).text('Enviar Prueba');
                }
            });
        });

        $('#wcmp-mkt-template-test').on('change', function() {
            if ($(this).val() !== '') $('#wcmp-mkt-editor-test-wrap').slideUp();
            else $('#wcmp-mkt-editor-test-wrap').slideDown();
        });

        function processNextMkt() {
            if (queue.length === 0) {
                $('#wcmp-mkt-status-text').text('¡Campaña Finalizada!');
                alert('La campaña se ha enviado con éxito. ID: #' + active_campaign_id);
                return;
            }

            var row = queue.shift();
            var name = row[$('#wcmp-mkt-map-name').val()];
            var email = row[$('#wcmp-mkt-map-email').val()];
            var current_idx = total_to_send - queue.length;

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'tbp_actividades_send_external_mkt',
                    campaign_id: active_campaign_id,
                    name: name,
                    email: email,
                    subject: $('#wcmp-mkt-subject').val(),
                    template_id: $('#wcmp-mkt-template').val(),
                    message: (typeof tinymce != "undefined" && tinymce.get("wcmp_mkt_message")) ? tinymce.get("wcmp_mkt_message").getContent() : $('#wcmp_mkt_message').val(),
                    _ajax_nonce: '<?php echo wp_create_nonce("tbp_ext_mkt"); ?>'
                },
                complete: function() {
                    $('#wcmp-mkt-progress-count').text(current_idx);
                    var pct = (current_idx / total_to_send) * 100;
                    $('#wcmp-mkt-progress-bar').css('width', pct + '%');
                    
                    current_batch++;
                    if (current_batch >= batch_size && queue.length > 0) {
                        current_batch = 0;
                        pauseMkt();
                    } else {
                        setTimeout(processNextMkt, 400);
                    }
                }
            });
        }

        function pauseMkt() {
            var sec = pause_ms / 1000;
            var timer = setInterval(function() {
                sec--;
                $('#wcmp-mkt-status-text').text('Pausa Anti-Spam: Reanudando en ' + sec + 's...');
                if (sec <= 0) {
                    clearInterval(timer);
                    processNextMkt();
                }
            }, 1000);
        }
    });
    </script>
    <?php
}

/**
 * AJAX Handler for External Marketing Sending
 */
add_action( 'wp_ajax_tbp_actividades_send_external_mkt', 'tbp_actividades_send_external_mkt_ajax' );
function tbp_actividades_send_external_mkt_ajax() {
    check_ajax_referer( 'tbp_ext_mkt' );

    $email   = sanitize_email( $_POST['email'] );
    $name    = sanitize_text_field( $_POST['name'] );
    $subject = sanitize_text_field( stripslashes( $_POST['subject'] ) );
    $message = wp_kses_post( stripslashes( $_POST['message'] ) );
    $tid     = sanitize_text_field( $_POST['template_id'] );
    $cid     = intval( $_POST['campaign_id'] );

    if ( ! $email ) wp_send_json_error( 'Email inválido' );

    $final_subject = str_ireplace( '[nombre]', $name, $subject );
    
    // Tracking Pixel Logic
    $tracking_pixel = '';
    if ( $cid > 0 ) {
        global $wpdb;
        $table_contacts = $wpdb->prefix . 'tbp_marketing_contacts';
        $contact_id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_contacts WHERE email = %s ORDER BY id DESC LIMIT 1", $email ) );
        $pixel_url = get_rest_url( null, 'tbp-mkt/v1/track-open' ) . '?c=' . $cid . '&u=' . ( $contact_id ? $contact_id : 0 );
        $tracking_pixel = '<img src="' . esc_url( $pixel_url ) . '" width="1" height="1" style="display:none !important;" />';
    }

    if ( ! empty( $tid ) ) {
        $templates = get_option( 'tbp_marketing_templates', array() );
        $final_message = str_ireplace( '[nombre]', $name, stripslashes( $templates[$tid]['html'] ) );
        $final_message .= $tracking_pixel;
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail( $email, $final_subject, $final_message, $headers );
    } else {
        $final_message = str_ireplace( '[nombre]', $name, $message );
        ob_start();
        ?>
        <div style="font-family: inherit; line-height: 1.6;">
            <?php echo $final_message; ?>
            <hr style="border: none; border-top: 1px solid #eee; margin-top: 40px; margin-bottom: 20px;">
            <p style="font-size: 12px; color: #999; text-align: center;">Equipo The Best Prom</p>
            <?php echo $tracking_pixel; ?>
        </div>
        <?php
        $body = ob_get_clean();
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        if ( function_exists('WC') && WC()->mailer() ) {
            $mailer = WC()->mailer();
            $wrapped = $mailer->wrap_message( $final_subject, $body );
            $sent = wp_mail( $email, $final_subject, $wrapped, $headers );
        } else {
            $sent = wp_mail( $email, $final_subject, $body, $headers );
        }
    }

    if ( $sent ) wp_send_json_success( 'Enviado' );
    else wp_send_json_error( 'Error de envío' );
}

/**
 * AJAX Handler to save contacts to DB
 */
add_action( 'wp_ajax_tbp_actividades_save_mkt_contacts', 'tbp_actividades_save_mkt_contacts_ajax' );
function tbp_actividades_save_mkt_contacts_ajax() {
    check_ajax_referer( 'tbp_ext_mkt' );

    global $wpdb;
    $list_id   = intval( $_POST['list_id'] );
    $list_name = sanitize_text_field( $_POST['list_name'] );
    $contacts  = json_decode( stripslashes( $_POST['contacts'] ), true );

    if ( empty( $contacts ) ) wp_send_json_error( 'No hay contactos para guardar' );

    $table_lists = $wpdb->prefix . 'tbp_marketing_lists';
    $table_contacts = $wpdb->prefix . 'tbp_marketing_contacts';

    // 1. Manejar Lista
    if ( ! $list_id && ! empty( $list_name ) ) {
        $wpdb->insert( $table_lists, array( 'name' => $list_name ) );
        $list_id = $wpdb->insert_id;
    }

    if ( ! $list_id ) wp_send_json_error( 'Error al identificar la lista' );

    // 2. Guardar Contactos (Bulk)
    $inserted = 0;
    foreach ( $contacts as $c ) {
        // Evitar duplicados exactos en la misma lista
        $exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table_contacts WHERE list_id = %d AND email = %s", $list_id, $c['email'] ) );
        if ( ! $exists ) {
            $wpdb->insert( $table_contacts, array(
                'list_id'   => $list_id,
                'full_name' => sanitize_text_field( $c['name'] ),
                'email'     => sanitize_email( $c['email'] ),
                'phone'     => sanitize_text_field( $c['phone'] )
            ) );
            $inserted++;
        }
    }

    // Obtener nombre final de la lista
    $final_name = $wpdb->get_var( $wpdb->prepare( "SELECT name FROM $table_lists WHERE id = %d", $list_id ) );

    wp_send_json_success( array(
        'list_id'   => $list_id,
        'list_name' => $final_name,
        'inserted'  => $inserted
    ) );
}

/**
 * AJAX Handler to start a campaign tracking
 */
add_action( 'wp_ajax_tbp_actividades_start_mkt_campaign', 'tbp_actividades_start_mkt_campaign_ajax' );
function tbp_actividades_start_mkt_campaign_ajax() {
    check_ajax_referer( 'tbp_ext_mkt' );

    global $wpdb;
    $list_id     = intval( $_POST['list_id'] );
    $subject     = sanitize_text_field( stripslashes( $_POST['subject'] ) );
    $message     = wp_kses_post( stripslashes( $_POST['message'] ) );
    $template_id = sanitize_text_field( $_POST['template_id'] );
    $sched_at    = sanitize_text_field( $_POST['scheduled_at'] );

    $table_campaigns = $wpdb->prefix . 'tbp_marketing_campaigns';

    $status = ! empty( $sched_at ) ? 'scheduled' : 'sending';

    $wpdb->insert( $table_campaigns, array(
        'list_id'     => $list_id,
        'subject'     => $subject,
        'message'     => $message,
        'template_id' => $template_id,
        'status'      => $status,
        'created_at'  => ! empty( $sched_at ) ? $sched_at : current_time( 'mysql' )
    ) );

    $campaign_id = $wpdb->insert_id;

    if ( $campaign_id ) {
        wp_send_json_success( array( 'campaign_id' => $campaign_id ) );
    } else {
        wp_send_json_error( 'Error al registrar campaña' );
    }
}

/**
 * AJAX Handler for Test Marketing Sending
 */
add_action( 'wp_ajax_tbp_actividades_send_test_mkt', 'tbp_actividades_send_test_mkt_ajax' );
function tbp_actividades_send_test_mkt_ajax() {
    check_ajax_referer( 'tbp_ext_mkt' );

    $email   = sanitize_email( $_POST['email'] );
    $subject = sanitize_text_field( stripslashes( $_POST['subject'] ) );
    $message = wp_kses_post( stripslashes( $_POST['message'] ) );
    $tid     = sanitize_text_field( $_POST['template_id'] );

    if ( ! $email ) wp_send_json_error( 'Email de prueba inválido' );

    $final_subject = '[PRUEBA] ' . $subject;
    $name = 'Miguel (PRUEBA)';
    
    if ( ! empty( $tid ) ) {
        $templates = get_option( 'tbp_marketing_templates', array() );
        $final_message = str_ireplace( '[nombre]', $name, stripslashes( $templates[$tid]['html'] ) );
        $headers = array('Content-Type: text/html; charset=UTF-8');
        $sent = wp_mail( $email, $final_subject, $final_message, $headers );
    } else {
        $final_message = str_ireplace( '[nombre]', $name, $message );
        ob_start();
        ?>
        <div style="font-family: inherit; line-height: 1.6;">
            <?php echo $final_message; ?>
            <hr style="border: none; border-top: 1px solid #eee; margin-top: 40px; margin-bottom: 20px;">
            <p style="font-size: 12px; color: #999; text-align: center;">EQUIPO DE PRUEBAS - The Best Prom</p>
        </div>
        <?php
        $body = ob_get_clean();
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        if ( function_exists('WC') && WC()->mailer() ) {
            $mailer = WC()->mailer();
            $wrapped = $mailer->wrap_message( $final_subject, $body );
            $sent = wp_mail( $email, $final_subject, $wrapped, $headers );
        } else {
            $sent = wp_mail( $email, $final_subject, $body, $headers );
        }
    }

    if ( $sent ) wp_send_json_success( 'Prueba enviada' );
    else wp_send_json_error( 'Error de envío de prueba' );
}
