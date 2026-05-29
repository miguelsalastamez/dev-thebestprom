<?php
/**
 * Event Delivery Rules Module - The Best Prom
 * Version: 11.9.1
 * Author: Antigravity Team
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// 1. Meta Box Registration
add_action( 'add_meta_boxes', 'tbp_event_delivery_rules_register_meta_box' );
function tbp_event_delivery_rules_register_meta_box() {
    add_meta_box(
        'tbp_event_delivery_rules_box',
        __( 'Entregas del Evento', 'tbp-actividades' ),
        'tbp_event_delivery_rules_render_meta_box',
        'tribe_events',
        'normal',
        'high'
    );
}

// 2. Render Meta Box UI (Beautiful Card-based UI with Glassmorphism styles and Javascript rules CRUD)
function tbp_event_delivery_rules_render_meta_box( $post ) {
    // Nonce for security
    wp_nonce_field( 'tbp_event_delivery_rules_save', 'tbp_event_delivery_rules_nonce' );

    // Get rules
    $rules = get_post_meta( $post->ID, '_tbp_event_delivery_rules', true );
    if ( ! is_array( $rules ) ) {
        $rules = [];
    }

    // Get event tickets
    if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
        $tickets_objs = Tribe__Tickets__Tickets::get_all_event_tickets( $post->ID );
    } else {
        $tickets_objs = [];
    }

    $tickets_data = [];
    foreach ( $tickets_objs as $ticket ) {
        $product_id = is_object( $ticket ) ? ( $ticket->ID ?? ($ticket->ticket_id ?? 0) ) : intval( $ticket );
        $title = is_object( $ticket ) ? ( $ticket->post_title ?? get_the_title($product_id) ) : get_the_title($product_id);
        $tickets_data[] = [
            'id' => $product_id,
            'title' => $title
        ];
    }

    // Output hidden field that holds the JSON
    ?>
    <input type="hidden" name="tbp_event_delivery_rules_json" id="tbp_event_delivery_rules_json" value="<?php echo esc_attr( json_encode( $rules ) ); ?>" />
    
    <!-- Inline Beautiful CSS for Premium Design -->
    <style>
        .tbp-rules-container {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-top: 10px;
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.02);
            border: 1px solid #e2e8f0;
        }
        .tbp-rules-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 12px;
        }
        .tbp-rules-title {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }
        .tbp-btn-add {
            background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);
            color: #ffffff !important;
            border: none;
            padding: 8px 16px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            box-shadow: 0 4px 6px -1px rgba(79, 70, 229, 0.2), 0 2px 4px -1px rgba(79, 70, 229, 0.1);
            transition: all 0.2s ease;
        }
        .tbp-btn-add:hover {
            transform: translateY(-1px);
            box-shadow: 0 10px 15px -3px rgba(79, 70, 229, 0.3), 0 4px 6px -2px rgba(79, 70, 229, 0.05);
            background: linear-gradient(135deg, #6366f1 0%, #4338ca 100%);
        }
        .tbp-rules-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 20px;
        }
        .tbp-rule-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            position: relative;
            transition: all 0.2s ease;
            overflow: hidden;
        }
        .tbp-rule-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-color: #cbd5e1;
        }
        .tbp-rule-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #6366f1, #3b82f6);
        }
        .tbp-rule-date-badge {
            background: #eff6ff;
            color: #1d4ed8;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 11px;
            font-weight: 700;
            display: inline-block;
            margin-bottom: 10px;
            border: 1px solid #bfdbfe;
        }
        .tbp-rule-type {
            font-size: 15px;
            font-weight: 700;
            color: #0f172a;
            margin: 0 0 6px 0;
        }
        .tbp-rule-channels {
            font-size: 11px;
            color: #64748b;
            margin-bottom: 12px;
            display: flex;
            gap: 12px;
        }
        .tbp-rule-channels span {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .tbp-rule-tickets-summary {
            background: #f8fafc;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 15px;
            border: 1px solid #f1f5f9;
        }
        .tbp-rule-tickets-title {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
            margin: 0 0 6px 0;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .tbp-rule-ticket-item {
            font-size: 12px;
            color: #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 4px;
        }
        .tbp-rule-ticket-item:last-child {
            margin-bottom: 0;
        }
        .tbp-rule-units-badge {
            background: #e2e8f0;
            color: #334155;
            padding: 1px 6px;
            border-radius: 4px;
            font-weight: 700;
            font-size: 10px;
        }
        .tbp-rule-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            border-top: 1px solid #f1f5f9;
            padding-top: 12px;
        }
        .tbp-btn-icon {
            background: none;
            border: 1px solid #cbd5e1;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .tbp-btn-edit {
            color: #2563eb;
            border-color: #bfdbfe;
            background: #eff6ff;
        }
        .tbp-btn-edit:hover {
            background: #dbeafe;
            border-color: #3b82f6;
        }
        .tbp-btn-delete {
            color: #dc2626;
            border-color: #fecaca;
            background: #fef2f2;
        }
        .tbp-btn-delete:hover {
            background: #fee2e2;
            border-color: #ef4444;
        }
        
        /* Modal Style */
        .tbp-modal {
            display: none;
            position: fixed;
            z-index: 100000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(4px);
            align-items: center;
            justify-content: center;
        }
        .tbp-modal-content {
            background-color: #ffffff;
            border-radius: 16px;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            animation: tbpModalShow 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            max-height: 85vh;
        }
        @keyframes tbpModalShow {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .tbp-modal-header {
            background: #f8fafc;
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .tbp-modal-title {
            font-size: 16px;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }
        .tbp-modal-close {
            font-size: 20px;
            font-weight: bold;
            color: #64748b;
            cursor: pointer;
            transition: color 0.15s ease;
        }
        .tbp-modal-close:hover {
            color: #0f172a;
        }
        .tbp-modal-body {
            padding: 24px;
            overflow-y: auto;
            flex-grow: 1;
        }
        .tbp-modal-footer {
            background: #f8fafc;
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Form Styles */
        .tbp-form-group {
            margin-bottom: 18px;
        }
        .tbp-form-group label {
            display: block;
            font-weight: 700;
            font-size: 12px;
            color: #334155;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .tbp-input-text {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            color: #334155;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .tbp-input-text:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .tbp-textarea {
            width: 100%;
            height: 90px;
            padding: 10px 12px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 13px;
            font-family: inherit;
            resize: vertical;
            box-sizing: border-box;
            transition: all 0.15s ease;
        }
        .tbp-textarea:focus {
            outline: none;
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }
        .tbp-checkbox-group {
            display: flex;
            gap: 20px;
            margin-top: 6px;
        }
        .tbp-checkbox-label {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 13px !important;
            color: #334155;
            text-transform: none !important;
            letter-spacing: normal !important;
            cursor: pointer;
        }
        .tbp-checkbox-label input {
            margin: 0;
        }
        .tbp-tickets-grid {
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            background: #fafafa;
        }
        .tbp-ticket-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        .tbp-ticket-row:last-child {
            border-bottom: none;
        }
        .tbp-ticket-info {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-grow: 1;
        }
        .tbp-ticket-title {
            font-size: 12px;
            font-weight: 600;
            color: #334155;
        }
        .tbp-units-control {
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .tbp-btn-qty {
            width: 24px;
            height: 24px;
            background: #e2e8f0;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #475569;
            transition: all 0.1s ease;
        }
        .tbp-btn-qty:hover {
            background: #cbd5e1;
            color: #0f172a;
        }
        .tbp-input-qty {
            width: 32px;
            text-align: center;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            padding: 2px;
            color: #1e293b;
        }
        
        .tbp-btn-save {
            background: #10b981;
            color: #ffffff;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .tbp-btn-save:hover {
            background: #059669;
        }
        .tbp-btn-cancel {
            background: #e2e8f0;
            color: #475569;
            border: none;
            padding: 8px 18px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 13px;
            cursor: pointer;
            transition: all 0.15s ease;
        }
        .tbp-btn-cancel:hover {
            background: #cbd5e1;
            color: #0f172a;
        }
        
        .tbp-empty-msg {
            grid-column: 1 / -1;
            background: #ffffff;
            border: 1px dashed #cbd5e1;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
        }
    </style>

    <div class="tbp-rules-container">
        <div class="tbp-rules-header">
            <h3 class="tbp-rules-title"><?php _e( 'Reglas de Entrega QR Activas', 'tbp-actividades' ); ?></h3>
            <button type="button" class="tbp-btn-add" id="tbp-btn-add-rule"><?php _e( '+ Agregar Regla', 'tbp-actividades' ); ?></button>
        </div>

        <div class="tbp-rules-list" id="tbp-rules-list-container">
            <!-- Rendered by JS -->
        </div>
    </div>

    <!-- Modal Form -->
    <div class="tbp-modal" id="tbp-rule-modal">
        <div class="tbp-modal-content">
            <div class="tbp-modal-header">
                <h4 class="tbp-modal-title" id="tbp-modal-title-text"><?php _e( 'Nueva Regla de Entrega', 'tbp-actividades' ); ?></h4>
                <span class="tbp-modal-close" id="tbp-modal-close-btn">&times;</span>
            </div>
            <div class="tbp-modal-body">
                <input type="hidden" id="tbp-form-rule-id" value="" />
                
                <div class="tbp-form-group">
                    <label for="tbp-form-rule-date"><?php _e( 'Fecha de Entrega Activa', 'tbp-actividades' ); ?></label>
                    <input type="date" id="tbp-form-rule-date" class="tbp-input-text" required value="<?php echo date('Y-m-d'); ?>" />
                </div>

                <div class="tbp-form-group">
                    <label for="tbp-form-rule-type"><?php _e( 'Tipo de Entrega', 'tbp-actividades' ); ?></label>
                    <input type="text" id="tbp-form-rule-type" class="tbp-input-text" placeholder="Ej. Cena de Gala / Paquete / Brazaletes" required />
                </div>

                <div class="tbp-form-group">
                    <label for="tbp-form-rule-message"><?php _e( 'Mensaje a Enviar', 'tbp-actividades' ); ?></label>
                    <textarea id="tbp-form-rule-message" class="tbp-textarea" placeholder="Ej. ¡Hola [nombre]! Tu [tipo_entrega] fue entregado exitosamente..."></textarea>
                    <p class="description" style="font-size:11px; margin-top:4px;">
                        Variables: <code>[nombre]</code>, <code>[pedido]</code>, <code>[ticket]</code>, <code>[unidades]</code>, <code>[evento]</code>, <code>[fecha]</code>, <code>[tipo_entrega]</code>
                    </p>
                </div>

                <div class="tbp-form-group">
                    <label><?php _e( 'Canales de Notificación', 'tbp-actividades' ); ?></label>
                    <div class="tbp-checkbox-group">
                        <label class="tbp-checkbox-label">
                            <input type="checkbox" id="tbp-form-channel-wc-note" value="wc_note" checked />
                            Nota de Pedido (WooCommerce)
                        </label>
                        <label class="tbp-checkbox-label">
                            <input type="checkbox" id="tbp-form-channel-email" value="email" checked />
                            Correo Electrónico (Alumno)
                        </label>
                    </div>
                </div>

                <div class="tbp-form-group">
                    <label class="tbp-checkbox-label" style="font-weight: 700; color: #334155; text-transform: uppercase;">
                        <input type="checkbox" id="tbp-form-reset-checkin" checked />
                        <?php _e( 'Resetear check-in tras escanear (Multiscan en diferentes fechas)', 'tbp-actividades' ); ?>
                    </label>
                </div>

                <div class="tbp-form-group">
                    <label><?php _e( 'Tickets / Productos válidos para esta entrega', 'tbp-actividades' ); ?></label>
                    <div class="tbp-tickets-grid" id="tbp-form-tickets-grid">
                        <?php if ( empty( $tickets_data ) ) : ?>
                            <div style="padding:15px; text-align:center; color:#94a3b8; font-size:12px;">
                                <?php _e( 'No hay tickets configurados para este evento.', 'tbp-actividades' ); ?>
                            </div>
                        <?php else : ?>
                            <?php foreach ( $tickets_data as $ticket ) : ?>
                                <div class="tbp-ticket-row" data-ticket-id="<?php echo esc_attr( $ticket['id'] ); ?>">
                                    <div class="tbp-ticket-info">
                                        <input type="checkbox" class="tbp-ticket-apply-cb" checked />
                                        <span class="tbp-ticket-title"><?php echo esc_html( $ticket['title'] ); ?></span>
                                    </div>
                                    <div class="tbp-units-control">
                                        <span style="font-size:11px; color:#64748b;"><?php _e( 'Unidades:', 'tbp-actividades' ); ?></span>
                                        <button type="button" class="tbp-btn-qty tbp-btn-qty-minus">-</button>
                                        <input type="text" class="tbp-input-qty tbp-ticket-units-input" value="1" readonly />
                                        <button type="button" class="tbp-btn-qty tbp-btn-qty-plus">+</button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="tbp-modal-footer">
                <button type="button" class="tbp-btn-cancel" id="tbp-btn-modal-cancel"><?php _e( 'Cancelar', 'tbp-actividades' ); ?></button>
                <button type="button" class="tbp-btn-save" id="tbp-btn-modal-save"><?php _e( 'Guardar Regla', 'tbp-actividades' ); ?></button>
            </div>
        </div>
    </div>

    <!-- JavaScript Engine inside Meta Box for Zero latency & absolute stability -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var inputJson = document.getElementById('tbp_event_delivery_rules_json');
            var containerList = document.getElementById('tbp-rules-list-container');
            var modal = document.getElementById('tbp-rule-modal');
            var btnAdd = document.getElementById('tbp-btn-add-rule');
            var btnCancel = document.getElementById('tbp-btn-modal-cancel');
            var btnSave = document.getElementById('tbp-btn-modal-save');
            var btnClose = document.getElementById('tbp-modal-close-btn');
            
            // Loaded tickets from PHP
            var eventTickets = <?php echo json_encode( $tickets_data ); ?>;
            var rules = [];
            
            try {
                rules = JSON.parse(inputJson.value || '[]');
            } catch (e) {
                rules = [];
            }
            
            function saveRulesToInput() {
                inputJson.value = JSON.stringify(rules);
            }
            
            function renderRules() {
                containerList.innerHTML = '';
                if (rules.length === 0) {
                    containerList.innerHTML = '<div class="tbp-empty-msg"><?php _e( '📝 No hay reglas de entrega configuradas. Haz clic en "+ Agregar Regla" para definir reglas por fecha.', 'tbp-actividades' ); ?></div>';
                    return;
                }
                
                rules.forEach(function(rule) {
                    var card = document.createElement('div');
                    card.className = 'tbp-rule-card';
                    
                    var dateBadge = document.createElement('div');
                    dateBadge.className = 'tbp-rule-date-badge';
                    dateBadge.textContent = '📅 ' + rule.delivery_date;
                    card.appendChild(dateBadge);
                    
                    var title = document.createElement('h4');
                    title.className = 'tbp-rule-type';
                    title.textContent = rule.delivery_type;
                    card.appendChild(title);
                    
                    var channels = document.createElement('div');
                    channels.className = 'tbp-rule-channels';
                    var chanHTML = '';
                    if (rule.channels.includes('wc_note')) {
                        chanHTML += '<span>📝 Nota WC</span>';
                    }
                    if (rule.channels.includes('email')) {
                        chanHTML += '<span>✉️ Correo</span>';
                    }
                    if (rule.reset_checkin) {
                        chanHTML += '<span style="color:#059669;">🔄 Auto-Reset</span>';
                    }
                    channels.innerHTML = chanHTML;
                    card.appendChild(channels);
                    
                    var tSummary = document.createElement('div');
                    tSummary.className = 'tbp-rule-tickets-summary';
                    
                    var tTitle = document.createElement('h5');
                    tTitle.className = 'tbp-rule-tickets-title';
                    tTitle.textContent = '<?php _e( 'Tickets Permitidos', 'tbp-actividades' ); ?>';
                    tSummary.appendChild(tTitle);
                    
                    var hasTickets = false;
                    for (var tid in rule.tickets) {
                        if (rule.tickets[tid] && rule.tickets[tid].apply) {
                            var tData = eventTickets.find(function(t) { return t.id == tid; });
                            var name = tData ? tData.title : 'Ticket #' + tid;
                            
                            var tRow = document.createElement('div');
                            tRow.className = 'tbp-rule-ticket-item';
                            tRow.innerHTML = '<span>' + name + '</span> <span class="tbp-rule-units-badge">' + rule.tickets[tid].units + ' u</span>';
                            tSummary.appendChild(tRow);
                            hasTickets = true;
                        }
                    }
                    
                    if (!hasTickets) {
                        tSummary.innerHTML += '<div style="font-size:11px; color:#94a3b8; font-style:italic;"><?php _e( 'Ninguno (No aplicará QR)', 'tbp-actividades' ); ?></div>';
                    }
                    
                    card.appendChild(tSummary);
                    
                    var actions = document.createElement('div');
                    actions.className = 'tbp-rule-actions';
                    
                    var btnEdit = document.createElement('button');
                    btnEdit.type = 'button';
                    btnEdit.className = 'tbp-btn-icon tbp-btn-edit';
                    btnEdit.textContent = '<?php _e( 'Editar', 'tbp-actividades' ); ?>';
                    btnEdit.addEventListener('click', function() {
                        openModal(rule);
                    });
                    actions.appendChild(btnEdit);
                    
                    var btnDel = document.createElement('button');
                    btnDel.type = 'button';
                    btnDel.className = 'tbp-btn-icon tbp-btn-delete';
                    btnDel.textContent = '<?php _e( 'Eliminar', 'tbp-actividades' ); ?>';
                    btnDel.addEventListener('click', function() {
                        if (confirm('¿Estás seguro de que deseas eliminar esta regla de entrega?')) {
                            rules = rules.filter(function(r) { return r.id !== rule.id; });
                            saveRulesToInput();
                            renderRules();
                        }
                    });
                    actions.appendChild(btnDel);
                    
                    card.appendChild(actions);
                    containerList.appendChild(card);
                });
            }
            
            function openModal(rule) {
                modal.style.display = 'flex';
                if (rule) {
                    // Edit Mode
                    document.getElementById('tbp-modal-title-text').textContent = '<?php _e( 'Editar Regla de Entrega', 'tbp-actividades' ); ?>';
                    document.getElementById('tbp-form-rule-id').value = rule.id;
                    document.getElementById('tbp-form-rule-date').value = rule.delivery_date;
                    document.getElementById('tbp-form-rule-type').value = rule.delivery_type;
                    document.getElementById('tbp-form-rule-message').value = rule.message || '';
                    document.getElementById('tbp-form-channel-wc-note').checked = rule.channels.includes('wc_note');
                    document.getElementById('tbp-form-channel-email').checked = rule.channels.includes('email');
                    document.getElementById('tbp-form-reset-checkin').checked = !!rule.reset_checkin;
                    
                    // Setup ticket checkboxes & units
                    var rows = document.querySelectorAll('#tbp-form-tickets-grid .tbp-ticket-row');
                    rows.forEach(function(row) {
                        var tid = row.getAttribute('data-ticket-id');
                        var cb = row.querySelector('.tbp-ticket-apply-cb');
                        var inputUnits = row.querySelector('.tbp-ticket-units-input');
                        
                        if (rule.tickets && rule.tickets[tid]) {
                            cb.checked = !!rule.tickets[tid].apply;
                            inputUnits.value = rule.tickets[tid].units || 1;
                        } else {
                            cb.checked = false;
                            inputUnits.value = 1;
                        }
                    });
                } else {
                    // New Mode
                    document.getElementById('tbp-modal-title-text').textContent = '<?php _e( 'Nueva Regla de Entrega', 'tbp-actividades' ); ?>';
                    document.getElementById('tbp-form-rule-id').value = '';
                    document.getElementById('tbp-form-rule-date').value = new Date().toISOString().split('T')[0];
                    document.getElementById('tbp-form-rule-type').value = '';
                    document.getElementById('tbp-form-rule-message').value = '';
                    document.getElementById('tbp-form-channel-wc-note').checked = true;
                    document.getElementById('tbp-form-channel-email').checked = true;
                    document.getElementById('tbp-form-reset-checkin').checked = true;
                    
                    var rows = document.querySelectorAll('#tbp-form-tickets-grid .tbp-ticket-row');
                    rows.forEach(function(row) {
                        if (row.querySelector('.tbp-ticket-apply-cb')) {
                            row.querySelector('.tbp-ticket-apply-cb').checked = true;
                        }
                        if (row.querySelector('.tbp-ticket-units-input')) {
                            row.querySelector('.tbp-ticket-units-input').value = 1;
                        }
                    });
                }
            }
            
            function closeModal() {
                modal.style.display = 'none';
            }
            
            // Wire modal events
            if (btnAdd) btnAdd.addEventListener('click', function() { openModal(null); });
            if (btnCancel) btnCancel.addEventListener('click', closeModal);
            if (btnClose) btnClose.addEventListener('click', closeModal);
            
            // Save Rule action
            if (btnSave) btnSave.addEventListener('click', function() {
                var ruleId = document.getElementById('tbp-form-rule-id').value;
                var date = document.getElementById('tbp-form-rule-date').value;
                var type = document.getElementById('tbp-form-rule-type').value;
                var message = document.getElementById('tbp-form-rule-message').value;
                var wcNote = document.getElementById('tbp-form-channel-wc-note').checked;
                var email = document.getElementById('tbp-form-channel-email').checked;
                var resetCheckin = document.getElementById('tbp-form-reset-checkin').checked;
                
                if (!date || !type) {
                    alert('Por favor completa los campos obligatorios (Fecha y Tipo de Entrega).');
                    return;
                }
                
                var channels = [];
                if (wcNote) channels.push('wc_note');
                if (email) channels.push('email');
                
                // Get tickets setup
                var ticketsSetup = {};
                var rows = document.querySelectorAll('#tbp-form-tickets-grid .tbp-ticket-row');
                rows.forEach(function(row) {
                    var tid = row.getAttribute('data-ticket-id');
                    var cb = row.querySelector('.tbp-ticket-apply-cb');
                    var inputUnits = row.querySelector('.tbp-ticket-units-input');
                    
                    ticketsSetup[tid] = {
                        apply: cb ? cb.checked : false,
                        units: inputUnits ? (parseInt(inputUnits.value) || 1) : 1
                    };
                });
                
                if (!ruleId) {
                    // Create
                    ruleId = 'rule_' + new Date().getTime();
                    rules.push({
                        id: ruleId,
                        delivery_date: date,
                        delivery_type: type,
                        message: message,
                        channels: channels,
                        reset_checkin: resetCheckin,
                        tickets: ticketsSetup
                    });
                } else {
                    // Update
                    var index = rules.findIndex(function(r) { return r.id === ruleId; });
                    if (index !== -1) {
                        rules[index] = {
                            id: ruleId,
                            delivery_date: date,
                            delivery_type: type,
                            message: message,
                            channels: channels,
                            reset_checkin: resetCheckin,
                            tickets: ticketsSetup
                        };
                    }
                }
                
                saveRulesToInput();
                renderRules();
                closeModal();
            });
            
            // Wire Qty selectors inside modal tickets grid
            document.querySelectorAll('#tbp-form-tickets-grid .tbp-ticket-row').forEach(function(row) {
                var btnMinus = row.querySelector('.tbp-btn-qty-minus');
                var btnPlus = row.querySelector('.tbp-btn-qty-plus');
                var inputUnits = row.querySelector('.tbp-ticket-units-input');
                
                if (btnMinus && inputUnits) {
                    btnMinus.addEventListener('click', function() {
                        var current = parseInt(inputUnits.value) || 1;
                        if (current > 1) {
                            inputUnits.value = current - 1;
                        }
                    });
                }
                if (btnPlus && inputUnits) {
                    btnPlus.addEventListener('click', function() {
                        var current = parseInt(inputUnits.value) || 1;
                        inputUnits.value = current + 1;
                    });
                }
            });
            
            // Initial Render
            renderRules();
        });
    </script>
    <?php
}

// 3. Save Meta Box Rules to CPT _tbp_event_delivery_rules
add_action( 'save_post_tribe_events', 'tbp_event_delivery_rules_save_meta_box' );
function tbp_event_delivery_rules_save_meta_box( $post_id ) {
    // Check nonce
    if ( ! isset( $_POST['tbp_event_delivery_rules_nonce'] ) || ! wp_verify_nonce( $_POST['tbp_event_delivery_rules_nonce'], 'tbp_event_delivery_rules_save' ) ) {
        return;
    }

    // Check auto-save
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check permissions
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    // Check and save JSON data
    if ( isset( $_POST['tbp_event_delivery_rules_json'] ) ) {
        $json = stripslashes( $_POST['tbp_event_delivery_rules_json'] );
        $rules = json_decode( $json, true );
        
        if ( is_array( $rules ) ) {
            update_post_meta( $post_id, '_tbp_event_delivery_rules', $rules );
            
            // Invalidate transient cache for this event's active rules
            delete_transient( 'tbp_delivery_rules_' . $post_id );
        }
    }
}

// 4. Expansión de Statuses Válidos para Check-In de ET Plus (Completado y Procesando "Pagado con Tarjeta")
add_filter( 'event_tickets_attendees_checkin_stati', 'tbp_expand_valid_etplus_checkin_stati', 999, 3 );
add_filter( 'event_tickets_attendees_woo_checkin_stati', 'tbp_expand_valid_etplus_checkin_stati', 999, 2 );
add_filter( 'event_tickets_attendees_wootickets_checkin_stati', 'tbp_expand_valid_etplus_checkin_stati', 999, 2 );
function tbp_expand_valid_etplus_checkin_stati( $statuses, $provider_slug = '', $order_id = 0 ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = (array) $statuses;
    }
    
    // WooCommerce Standard and Custom Renamed Card Payments
    $new_statuses = [ 'processing', 'completed', 'wc-processing', 'wc-completed', 'p-pagado', 'wc-p-pagado' ];
    
    foreach ( $new_statuses as $st ) {
        if ( ! in_array( $st, $statuses, true ) ) {
            $statuses[] = $st;
        }
    }
    
    return $statuses;
}

// 4.5 REVERSIÓN AUTOMÁTICA DE ENTREGAS FÍSICAS AL ANULAR EL CHECK-IN (Deshacer Check-In)
add_action( 'event_tickets_uncheckin', 'tbp_handle_attendee_uncheckin_delivery_revert', 10, 1 );
add_action( 'wootickets_uncheckin', 'tbp_handle_attendee_uncheckin_delivery_revert', 10, 1 );
add_action( 'eddtickets_uncheckin', 'tbp_handle_attendee_uncheckin_delivery_revert', 10, 1 );
add_action( 'rsvp_uncheckin', 'tbp_handle_attendee_uncheckin_delivery_revert', 10, 1 );

function tbp_handle_attendee_uncheckin_delivery_revert( $attendee_id ) {
    global $wpdb;
    $attendee_id = intval( $attendee_id );
    if ( ! $attendee_id ) {
        return;
    }

    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    
    // Buscar todos los logs de tipo qr_delivery asociados a este asistente/ticket
    $logs = $wpdb->get_results( $wpdb->prepare(
        "SELECT * FROM $table_logs WHERE staff_id = %d AND type = 'qr_delivery'",
        $attendee_id
    ) );

    if ( ! empty( $logs ) ) {
        foreach ( $logs as $log ) {
            // Borrar el log
            $wpdb->delete(
                $table_logs,
                array( 'id' => $log->id ),
                array( '%d' )
            );

            // Registrar nota en el pedido de WooCommerce
            $order = wc_get_order( $log->order_id );
            if ( $order ) {
                $attendee_name = get_the_title( $attendee_id );
                if ( empty( $attendee_name ) || is_numeric( $attendee_name ) ) {
                    $attendee_name = '#' . $attendee_id;
                }
                
                $rule_name = __( 'Entrega Física (QR)', 'tbp-actividades' );
                $rule = tbp_actividades_get_rule_by_hash( $log->order_id, $log->rifa_id );
                if ( $rule && ! empty( $rule['delivery_type'] ) ) {
                    $rule_name = $rule['delivery_type'];
                }

                $user = wp_get_current_user();
                $operator_name = ! empty( $user->display_name ) ? $user->display_name : __( 'Sistema / App', 'tbp-actividades' );

                $order->add_order_note( sprintf(
                    __( 'Anulación: Se deshizo el check-in del Asistente %s (#%d). La entrega del tipo "%s" fue revertida y borrada automáticamente (Operador: %s).', 'tbp-actividades' ),
                    $attendee_name,
                    $attendee_id,
                    $rule_name,
                    $operator_name
                ) );
            }
        }
    }
}

// 5. MOTOR DE DECISIÓN DE ENTREGA FÍSICA (Intercepta Check-Ins en Caliente)
add_action( 'event_tickets_checkin', 'tbp_process_qr_checkin_delivery_hook', 10, 3 );
add_action( 'wootickets_checkin', 'tbp_process_qr_checkin_delivery_hook', 10, 3 );
add_action( 'eddtickets_checkin', 'tbp_process_qr_checkin_delivery_hook', 10, 3 );
function tbp_process_qr_checkin_delivery_hook( $attendee_id, $qr = null, $event_id = null ) {
    $attendee_id = intval( $attendee_id );
    if ( ! $attendee_id ) {
        return;
    }

    tbp_log_delivery_debug( "Check-in interceptado para Asistente #{$attendee_id}.", [ 'qr' => $qr, 'passed_event_id' => $event_id ] );

    // 1. Get Event ID
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_event', true ) );
        if ( ! $event_id ) {
            $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_event', true ) );
        }
        if ( ! $event_id ) {
            $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_post_id', true ) );
        }
        if ( ! $event_id ) {
            $event_id = intval( get_post_meta( $attendee_id, '_event_id', true ) );
        }
        
        // Robust 7-layer fallback using order discovery if order ID is known
        if ( ! $event_id ) {
            $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
            if ( ! $order_id ) {
                $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
            }
            if ( $order_id && function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
                $event_id = tbp_actividades_discover_event_id_from_order( $order_id );
            }
        }
    }

    // Debug logging for tracing
    error_log( "[TBP Delivery Hook] Attendee ID: $attendee_id, Event ID: $event_id" );

    if ( ! $event_id ) {
        error_log( "[TBP Delivery Hook] Aborting: No Event ID found for Attendee $attendee_id" );
        tbp_log_delivery_debug( "Cancelado: No se pudo descubrir el ID del Evento para el Asistente #{$attendee_id}.", [ 'attendee_id' => $attendee_id ] );
        return;
    }

    // 2. Read Active Rules from Event (Hybrid Cache Transients for maximum performance under high concurrency)
    $cache_key = 'tbp_delivery_rules_' . $event_id;
    $rules = get_transient( $cache_key );
    if ( false === $rules ) {
        $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
        if ( ! is_array( $rules ) ) {
            $rules = [];
        }
        set_transient( $cache_key, $rules, HOUR_IN_SECONDS );
    }

    if ( empty( $rules ) ) {
        tbp_log_delivery_debug( "Ignorado: El Evento #{$event_id} no tiene reglas de entrega física configuradas.", [ 'event_id' => $event_id ] );
        return; // No rules configured, check-in operates as a standard event access pass
    }

    // 3. Retrieve Attendee Ticket Product ID & WC Order ID (Support all standard and newer TEC/Commerce versions)
    $product_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_product', true ) );
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_product', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_tpp_product', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_rsvp_product', true ) );
    }

    $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
    }
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tec_tickets_commerce_order', true ) );
    }
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_tpp_order', true ) );
    }
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_rsvp_order', true ) );
    }

    if ( ! $product_id || ! $order_id ) {
        tbp_log_delivery_debug( "Cancelado: Faltan metadatos críticos en el Asistente #{$attendee_id} (Pedido o Producto no vinculados).", [ 'product_id' => $product_id, 'order_id' => $order_id ] );
        return;
    }

    // 4. Find Rule active for TODAY (Server Timezone compliant + Timezone offset fallbacks)
    $today_wp    = date_i18n( 'Y-m-d' );
    $today_local = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
    $today_utc   = date( 'Y-m-d', current_time( 'timestamp', 1 ) );
    $today_mx    = date( 'Y-m-d', time() - 6 * HOUR_IN_SECONDS ); // Mexico (UTC-6) fallback

    $valid_dates = array_unique( [ $today_wp, $today_local, $today_utc, $today_mx ] );
    $today       = $today_wp; // Keep variable compatibility for subsequent duplicate check queries

    // Rich tracing logs for transparent debug visibility
    error_log( "[TBP Delivery Hook] Rules for Event $event_id: " . json_encode( $rules ) );
    error_log( "[TBP Delivery Hook] Today WP: $today_wp, Local: $today_local, UTC: $today_utc, MX: $today_mx" );

    $active_rule = null;
    foreach ( $rules as $rule ) {
        if ( in_array( $rule['delivery_date'], $valid_dates, true ) ) {
            $active_rule = $rule;
            break;
        }
    }

    $fallback_used = false;
    if ( ! $active_rule ) {
        // Fallback: If no rule matches today's date, search for a rule that contains this ticket product ID
        foreach ( $rules as $rule ) {
            $rule_tickets = $rule['tickets'] ?? [];
            if ( isset( $rule_tickets[ $product_id ] ) && ! empty( $rule_tickets[ $product_id ]['apply'] ) ) {
                $active_rule = $rule;
                $fallback_used = true;
                break;
            }
        }
    }

    if ( ! $active_rule ) {
        error_log( "[TBP Delivery Hook] Aborting: No active rule matching dates: " . json_encode( $valid_dates ) . " and no product fallback rule for ticket $product_id" );
        tbp_log_delivery_debug( "Ignorado: No hay una regla activa para hoy ni regla de respaldo para el Producto #{$product_id}. Fechas evaluadas: " . implode( ', ', $valid_dates ), [ 'event_id' => $event_id, 'rules' => $rules, 'product_id' => $product_id ] );
        return; // No active delivery rule for today and no fallback, operate as standard ticket access
    }

    if ( $fallback_used ) {
        tbp_log_delivery_debug( "Respaldo utilizado: No hay regla activa hoy para las fechas evaluadas. Canalizando entrega a la regla '{$active_rule['delivery_type']}' configurada para el Producto #{$product_id}.", [ 'event_id' => $event_id, 'rule_id' => $active_rule['id'], 'product_id' => $product_id ] );
    }

    error_log( "[TBP Delivery Hook] Found Active/Fallback Rule: " . json_encode( $active_rule ) );

    // 5. Check if this product is enabled in the active rule
    $rule_tickets = $active_rule['tickets'] ?? [];
    if ( ! isset( $rule_tickets[ $product_id ] ) || ! $rule_tickets[ $product_id ]['apply'] ) {
        tbp_log_delivery_debug( "Ignorado: El tipo de boleto (Producto #{$product_id}) no está seleccionado en la regla activa '{$active_rule['delivery_type']}'.", [ 'product_id' => $product_id, 'rule_tickets_setup' => $rule_tickets ] );
        return;
    }

    $allowed_units = intval( $rule_tickets[ $product_id ]['units'] );
    if ( $allowed_units <= 0 ) {
        tbp_log_delivery_debug( "Ignorado: Las unidades configuradas para el Producto #{$product_id} son 0 o menores.", [ 'product_id' => $product_id, 'units' => $allowed_units ] );
        return;
    }

    // 6. Anti-Duplicate Protection (Verify if this specific attendee/ticket has already registered delivery today for this rule ID)
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $rule_numeric_id = crc32( $active_rule['id'] ); // CRC32 hash fits perfectly into bigint(20) log column 'rifa_id'

    $delivered_today = intval( $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_logs 
         WHERE staff_id = %d 
         AND type = 'qr_delivery' 
         AND rifa_id = %d 
         AND DATE(created_at) = %s",
         $attendee_id,
         $rule_numeric_id,
         $today
    ) ) );

    if ( $delivered_today >= $allowed_units ) {
        tbp_log_delivery_debug( "Evitado por duplicidad: Ya se registraron todos los {$allowed_units} pases de '{$active_rule['delivery_type']}' para el Asistente #{$attendee_id}.", [ 'order_id' => $order_id, 'attendee_id' => $attendee_id, 'rule_id' => $active_rule['id'], 'duplicate_count' => $delivered_today ] );
        return;
    }

    // Detect if this check-in is manual from desktop admin panel
    $is_desktop_admin = is_admin() && ( defined( 'DOING_AJAX' ) && DOING_AJAX );

    if ( $is_desktop_admin ) {
        // Desktop Admin manual check-in delivers all remaining units at once!
        $units_to_deliver = $allowed_units - $delivered_today;
        
        tbp_log_delivery_debug( "ÉXITO (ESCRITORIO): Registrando todas las unidades restantes ({$units_to_deliver} de {$allowed_units}) para el Pedido #{$order_id} (Regla '{$active_rule['delivery_type']}').", [ 'order_id' => $order_id, 'rule_id' => $active_rule['id'], 'product_id' => $product_id, 'delivered_today' => $delivered_today, 'total' => $allowed_units, 'attendee_id' => $attendee_id ] );

        $run_synchronously = is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! function_exists( 'as_enqueue_async_action' );

        if ( $run_synchronously ) {
            tbp_async_register_delivery_handler( $order_id, $active_rule['id'], $event_id, $product_id, $units_to_deliver, $attendee_id );
        } else {
            as_enqueue_async_action( 'tbp_async_register_delivery', [
                'order_id'    => $order_id,
                'rule_id'     => $active_rule['id'],
                'event_id'    => $event_id,
                'product_id'  => $product_id,
                'units'       => $units_to_deliver,
                'attendee_id' => $attendee_id
            ], 'tbp_deliveries' );
        }

        // Auto-Reset check-in on the last scan if rule has reset_checkin enabled
        if ( ! empty( $active_rule['reset_checkin'] ) ) {
            if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
                $tickets_inst = Tribe__Tickets__Tickets::get_instance();
                if ( $tickets_inst ) {
                    $tickets_inst->uncheckin( $attendee_id );
                }
            }
        }
    } else {
        // QR Code scan delivers 1 unit per scan
        $current_scan = $delivered_today + 1;
        tbp_log_delivery_debug( "ÉXITO (QR): Registrando escaneo #{$current_scan} de {$allowed_units} para el Pedido #{$order_id} (Regla '{$active_rule['delivery_type']}').", [ 'order_id' => $order_id, 'rule_id' => $active_rule['id'], 'product_id' => $product_id, 'scan' => $current_scan, 'total' => $allowed_units, 'attendee_id' => $attendee_id ] );

        $run_synchronously = is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || ! function_exists( 'as_enqueue_async_action' );

        if ( $run_synchronously ) {
            tbp_async_register_delivery_handler( $order_id, $active_rule['id'], $event_id, $product_id, 1, $attendee_id );
        } else {
            as_enqueue_async_action( 'tbp_async_register_delivery', [
                'order_id'    => $order_id,
                'rule_id'     => $active_rule['id'],
                'event_id'    => $event_id,
                'product_id'  => $product_id,
                'units'       => 1,
                'attendee_id' => $attendee_id
            ], 'tbp_deliveries' );
        }

        // Auto-revert check-in if we haven't reached the allowed units limit yet
        if ( $current_scan < $allowed_units ) {
            if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
                $tickets_inst = Tribe__Tickets__Tickets::get_instance();
                if ( $tickets_inst ) {
                    $tickets_inst->uncheckin( $attendee_id );
                    
                    // Clear postmeta checkin markers to ensure clean status in WP-Admin tables
                    $checkin_keys = [
                        '_tribe_wooticket_checkedin',
                        '_tribe_eddticket_checkedin',
                        '_tribe_rsvp_checkedin',
                        '_tribe_tpp_checkedin'
                    ];
                    foreach ( $checkin_keys as $key ) {
                        update_post_meta( $attendee_id, $key, 0 );
                    }
                }
            }
        } else {
            // Auto-Reset check-in on the last scan if rule has reset_checkin enabled
            if ( ! empty( $active_rule['reset_checkin'] ) ) {
                if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
                    $tickets_inst = Tribe__Tickets__Tickets::get_instance();
                    if ( $tickets_inst ) {
                        $tickets_inst->uncheckin( $attendee_id );
                    }
                }
            }
        }
    }
}

// 6. ASYNCHRONOUS ENGINE: Background Processing, Logging, and Notifications via Action Scheduler
add_action( 'tbp_async_register_delivery', 'tbp_async_register_delivery_handler', 10, 6 );
function tbp_async_register_delivery_handler( $order_id, $rule_id, $event_id, $product_id, $units, $attendee_id ) {
    global $wpdb;
    $order_id    = intval( $order_id );
    $event_id    = intval( $event_id );
    $product_id  = intval( $product_id );
    $units       = intval( $units );
    $attendee_id = intval( $attendee_id );

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        return;
    }

    // Double check anti-duplicates inside asynchronous worker to prevent race conditions
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $rule_numeric_id = crc32( $rule_id );
    $today = date_i18n( 'Y-m-d' );

    $delivered_today = intval( $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_logs 
         WHERE staff_id = %d 
         AND type = 'qr_delivery' 
         AND rifa_id = %d 
         AND DATE(created_at) = %s",
        $attendee_id,
        $rule_numeric_id,
        $today
    ) ) );

    // Retrieve active rules to fetch custom notification message and allowed units
    $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
    $active_rule = null;
    if ( is_array( $rules ) ) {
        foreach ( $rules as $rule ) {
            if ( $rule['id'] === $rule_id ) {
                $active_rule = $rule;
                break;
            }
        }
    }

    if ( ! $active_rule ) {
        return;
    }

    $rule_tickets = $active_rule['tickets'] ?? [];
    $allowed_units = isset( $rule_tickets[ $product_id ] ) ? intval( $rule_tickets[ $product_id ]['units'] ) : 1;

    if ( $delivered_today >= $allowed_units ) {
        return; // Already fully processed today
    }

    $current_scan = $delivered_today + $units;

    // 1. Write delivery log in custom table (Save Attendee ID to prevent duplicate deliveries per-ticket)
    $wpdb->insert(
        $table_logs,
        [
            'staff_id'   => $attendee_id, 
            'order_id'   => $order_id,
            'rifa_id'    => $rule_numeric_id,
            'amount'     => $units, // Record dynamic units delivered in this transaction
            'type'       => 'qr_delivery',
            'created_at' => current_time( 'mysql' )
        ],
        [ '%d', '%d', '%d', '%d', '%s', '%s' ]
    );

    // Ensure the order is marked as delivered for the TBP Dashboard Reports
    update_post_meta( $order_id, '_tbp_entrega_paquetes', '1' );
    
    // Multi-phase tracking: Tag the order with the specific rule ID that was just completed
    add_post_meta( $order_id, '_tbp_delivery_rule_id', $rule_id, false );

    // 2. Compile custom notification message with dynamic variable tags
    $custom_message = $active_rule['message'] ?? '';
    if ( empty( $custom_message ) ) {
        // Fallback default message
        $custom_message = __( '¡Hola [nombre]! Tu entrega de [tipo_entrega] ([unidades] unidades) fue realizada con éxito para tu pedido [pedido] en el evento [evento].', 'tbp-actividades' );
    }

    // Personalized attendee name extraction (Tribe post title or meta fallback)
    $attendee_name = '';
    if ( $attendee_id ) {
        $attendee_post = get_post( $attendee_id );
        if ( $attendee_post && ! empty( $attendee_post->post_title ) ) {
            $attendee_name = trim( $attendee_post->post_title );
        }
        if ( ! $attendee_name ) {
            $meta_name = get_post_meta( $attendee_id, '_tribe_tickets_meta_name', true );
            if ( $meta_name ) {
                $attendee_name = trim( $meta_name );
            }
        }
    }
    if ( empty( $attendee_name ) || is_numeric( $attendee_name ) ) {
        $attendee_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    }

    $customer_email = $order->get_billing_email();
    $product_name   = get_the_title( $product_id );
    $event_title    = get_the_title( $event_id );
    $delivery_date  = $active_rule['delivery_date'];
    $delivery_type  = $active_rule['delivery_type'];

    // WooCommerce Financial parameters
    $total_val = (float) $order->get_total();
    $total_txt = wc_price( $total_val );
    
    $paid_val  = 0;
    if ( function_exists( 'wcmp_get_order_payments_total' ) ) {
        $wcmp_payments = wcmp_get_order_payments_total( $order_id );
        $ord_status    = $order->get_status();
        if ( in_array( $ord_status, array( 'processing', 'completed' ) ) && $wcmp_payments <= 0 ) {
            $paid_val = $total_val;
        } else {
            $paid_val = $wcmp_payments;
        }
    }
    
    $paid_txt    = wc_price( $paid_val );
    $balance_txt = wc_price( max( 0, $total_val - $paid_val ) );

    // Variable replacements
    $units_text = $current_scan . ' de ' . $allowed_units;
    $search  = [ '[nombre]', '[pedido]', '[ticket]', '[unidades]', '[evento]', '[fecha]', '[tipo_entrega]', '[monto]', '[pagado]', '[saldo]' ];
    $replace = [ $attendee_name, '#' . $order_id, $product_name, $units_text, $event_title, $delivery_date, $delivery_type, strip_tags($total_txt), strip_tags($paid_txt), strip_tags($balance_txt) ];

    $final_message = str_replace( $search, $replace, $custom_message );

    // 3. Channel: Note (WooCommerce Order Note)
    if ( in_array( 'wc_note', $active_rule['channels'], true ) ) {
        $order->add_order_note( $final_message, 1 ); // Customer note
    }

    // 4. Channel: Email (Billing Email)
    if ( in_array( 'email', $active_rule['channels'], true ) && ! empty( $customer_email ) ) {
        $subject = sprintf( __( 'Confirmación de Entrega: %s - Pedido #%d', 'tbp-actividades' ), $delivery_type, $order_id );
        
        ob_start();
        ?>
        <div style="font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; line-height: 1.6; max-width: 600px; margin: 0 auto; padding: 30px; background: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;">
            <div style="text-align: center; margin-bottom: 25px;">
                <h2 style="color: #4f46e5; margin: 0 0 10px 0; font-size: 24px; font-weight: 800;"><?php echo esc_html( $delivery_type ); ?></h2>
                <p style="color: #64748b; font-size: 14px; margin: 0;"><?php _e( 'Registro de Entrega Física Completado exitosamente', 'tbp-actividades' ); ?></p>
            </div>
            
            <div style="font-size: 15px; color: #334155; margin-bottom: 30px;">
                <?php echo nl2br( wp_kses_post( $final_message ) ); ?>
            </div>

            <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0 0 8px 0; font-weight: 700; font-size: 13px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;"><?php _e( 'Detalles del Registro', 'tbp-actividades' ); ?></p>
                <table style="width: 100%; border-collapse: collapse; font-size: 13px; color: #334155;">
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 0; font-weight: 600;"><?php _e( 'Evento:', 'tbp-actividades' ); ?></td>
                        <td style="padding: 8px 0; text-align: right; color: #0f172a;"><?php echo esc_html( $event_title ); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 0; font-weight: 600;"><?php _e( 'Fecha:', 'tbp-actividades' ); ?></td>
                        <td style="padding: 8px 0; text-align: right; color: #0f172a;"><?php echo esc_html( $delivery_date ); ?></td>
                    </tr>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding: 8px 0; font-weight: 600;"><?php _e( 'Boleto/Ticket:', 'tbp-actividades' ); ?></td>
                        <td style="padding: 8px 0; text-align: right; color: #0f172a;"><?php echo esc_html( $product_name ); ?></td>
                    </tr>
                    <tr>
                        <td style="padding: 8px 0; font-weight: 600;"><?php _e( 'Unidades Entregadas:', 'tbp-actividades' ); ?></td>
                        <td style="padding: 8px 0; text-align: right; font-weight: 700; color: #10b981; font-size: 14px;"><?php echo esc_html( $units_text ); ?></td>
                    </tr>
                </table>
            </div>

            <hr style="border: none; border-top: 1px solid #f1f5f9; margin-top: 40px; margin-bottom: 20px;">
            <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 0;"><?php _e( 'Este es un mensaje automático de confirmación de entrega física.', 'tbp-actividades' ); ?></p>
            <p style="font-size: 11px; color: #94a3b8; text-align: center; margin: 5px 0 0 0;"><strong>The Best Prom</strong> - El Ticketmaster de las Graduaciones.</p>
        </div>
        <?php
        $html_body = ob_get_clean();
        
        $headers = [ 'Content-Type: text/html; charset=UTF-8' ];
        
        if ( function_exists( 'WC' ) && WC()->mailer() ) {
            $mailer = WC()->mailer();
            $wrapped_message = $mailer->wrap_message( $subject, $html_body );
            wp_mail( $customer_email, $subject, $wrapped_message, $headers );
        } else {
            wp_mail( $customer_email, $subject, $html_body, $headers );
        }
    }
}

/**
 * Append debug logs in real time for diagnostic purposes.
 */
function tbp_log_delivery_debug( $message, $data = [] ) {
    $logs = get_option( 'tbp_delivery_debug_logs', [] );
    if ( ! is_array( $logs ) ) {
        $logs = [];
    }
    
    array_unshift( $logs, [
        'time'    => current_time( 'mysql' ),
        'message' => $message,
        'data'    => $data
    ] );
    
    // Keep last 30 logs max to avoid memory bloat
    $logs = array_slice( $logs, 0, 30 );
    update_option( 'tbp_delivery_debug_logs', $logs );
}

/**
 * Injects beautiful, descriptive diagnostic badges under each attendee's name in the admin list.
 * This tells the administrator exactly what IDs and metadata are active.
 */
add_filter( 'event_tickets_attendees_table_primary_info_column', 'tbp_actividades_inject_attendee_debug_badges', 10, 2 );
function tbp_actividades_inject_attendee_debug_badges( $output, $item ) {
    $attendee_id = intval( $item['attendee_id'] ?? 0 );
    $order_id    = intval( $item['order_id'] ?? 0 );
    $product_id  = intval( $item['product_id'] ?? 0 );
    $event_id    = intval( $item['event_id'] ?? 0 );
    
    if ( ! $attendee_id ) {
        return $output;
    }
    
    // Discover event ID if not set
    if ( ! $event_id && $order_id && function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
        $event_id = tbp_actividades_discover_event_id_from_order( $order_id );
    }
    
    // Check if there is an active delivery rule today
    $active_rule_status = '';
    if ( $event_id ) {
        $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
        if ( is_array( $rules ) && ! empty( $rules ) ) {
            $today_wp    = date_i18n( 'Y-m-d' );
            $today_local = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
            $today_utc   = date( 'Y-m-d', current_time( 'timestamp', 1 ) );
            $today_mx    = date( 'Y-m-d', time() - 6 * HOUR_IN_SECONDS );
            $valid_dates = array_unique( [ $today_wp, $today_local, $today_utc, $today_mx ] );
            
            $active_rule = null;
            foreach ( $rules as $rule ) {
                if ( in_array( $rule['delivery_date'], $valid_dates, true ) ) {
                    $active_rule = $rule;
                    break;
                }
            }
            
            $fallback_used = false;
            if ( ! $active_rule && $product_id ) {
                foreach ( $rules as $rule ) {
                    $rule_tickets = $rule['tickets'] ?? [];
                    if ( isset( $rule_tickets[ $product_id ] ) && ! empty( $rule_tickets[ $product_id ]['apply'] ) ) {
                        $active_rule = $rule;
                        $fallback_used = true;
                        break;
                    }
                }
            }
            
            if ( $active_rule ) {
                $rule_tickets = $active_rule['tickets'] ?? [];
                if ( isset( $rule_tickets[ $product_id ] ) && $rule_tickets[ $product_id ]['apply'] ) {
                    $units = intval( $rule_tickets[ $product_id ]['units'] );
                    $prefix = $fallback_used ? '🟢 Regla (Respaldo): ' : '🟢 Regla Activa: ';
                    $active_rule_status = '<span class="tbp-badge-debug" style="background:#e8f5e9; color:#2e7d32; border:1px solid #a5d6a7; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold; margin-left:5px; display:inline-block;">' . $prefix . esc_html( $active_rule['delivery_type'] ) . ' (' . $units . ' u)</span>';
                } else {
                    $active_rule_status = '<span class="tbp-badge-debug" style="background:#fff3e0; color:#e65100; border:1px solid #ffcc80; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold; margin-left:5px; display:inline-block;">🟡 Boleto No Habilitado Hoy</span>';
                }
            } else {
                $active_rule_status = '<span class="tbp-badge-debug" style="background:#fafafa; color:#78909c; border:1px solid #cfd8dc; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold; margin-left:5px; display:inline-block;">⚪ Sin Reglas Hoy</span>';
            }
        } else {
            $active_rule_status = '<span class="tbp-badge-debug" style="background:#fafafa; color:#90a4ae; border:1px solid #eceff1; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:normal; margin-left:5px; display:inline-block;">⚪ Sin Configuración</span>';
        }
    }
    
    // Render the beautiful debug layout
    $order_url = $order_id ? get_edit_post_link( $order_id ) : '#';
    
    $debug_html = '
    <div class="tbp-attendee-debug-wrapper" style="margin-top: 8px; font-family: sans-serif; display: flex; flex-wrap: wrap; gap: 4px; align-items: center; line-height: 1.2;">
        <span style="font-size:10px; color:#64748b; font-weight:bold; text-transform:uppercase;">🔍 Canalización a Pedido:</span>
        <a href="' . esc_url( $order_url ) . '" class="tbp-badge-debug" style="background:#e0f2fe; color:#0369a1; border:1px solid #bae6fd; padding:2px 6px; border-radius:3px; font-size:10px; font-weight:bold; text-decoration:none; display:inline-block; transition: background 0.2s;">Pedido #' . $order_id . ' 🔗</a>
        <span class="tbp-badge-debug" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:2px 6px; border-radius:3px; font-size:10px; display:inline-block;">Producto #' . $product_id . '</span>
        <span class="tbp-badge-debug" style="background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; padding:2px 6px; border-radius:3px; font-size:10px; display:inline-block;">Asistente #' . $attendee_id . '</span>
        ' . $active_rule_status . '
    </div>
    ';
    
    return $output . $debug_html;
}

/**
 * 6. MOTOR DE AUTORIZACIÓN Y BLOQUEO DE CHECK-INS
 */

/**
 * Check if an attendee is authorized for delivery check-in today
 * 
 * @param int $attendee_id
 * @return bool|null True if authorized, False if NOT authorized (should block check-in), or Null if standard check-in.
 */
function tbp_is_attendee_authorized_for_delivery_today( $attendee_id ) {
    $attendee_id = intval( $attendee_id );
    if ( ! $attendee_id ) {
        return false;
    }

    // 1. Get Event ID
    $event_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_event', true ) );
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_event', true ) );
    }
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_post_id', true ) );
    }
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_event_id', true ) );
    }
    
    // Robust fallback using order discovery
    if ( ! $event_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
        if ( ! $order_id ) {
            $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
        }
        if ( $order_id && function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
            $event_id = tbp_actividades_discover_event_id_from_order( $order_id );
        }
    }

    if ( ! $event_id ) {
        return null; // Cannot discover event, allow standard check-in
    }

    // 2. Read Active Rules from Event
    $cache_key = 'tbp_delivery_rules_' . $event_id;
    $rules = get_transient( $cache_key );
    if ( false === $rules ) {
        $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
        if ( ! is_array( $rules ) ) {
            $rules = [];
        }
        set_transient( $cache_key, $rules, HOUR_IN_SECONDS );
    }

    if ( empty( $rules ) ) {
        return null; // No rules configured, operate as standard ticket access
    }

    // 3. Retrieve Attendee Ticket Product ID & Order ID (Moved up for fallback rule discovery)
    $product_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_product', true ) );
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_product', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_tpp_product', true ) );
    }
    if ( ! $product_id ) {
        $product_id = intval( get_post_meta( $attendee_id, '_tribe_rsvp_product', true ) );
    }

    $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
    }

    // If product metadata is completely missing for this checkin
    if ( ! $product_id ) {
        return false; // Critical metadata missing, block unauthorized access
    }

    // 4. Find Rule active for TODAY
    $today_wp    = date_i18n( 'Y-m-d' );
    $today_local = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
    $today_utc   = date( 'Y-m-d', current_time( 'timestamp', 1 ) );
    $today_mx    = date( 'Y-m-d', time() - 6 * HOUR_IN_SECONDS ); // Mexico (UTC-6) fallback

    $valid_dates = array_unique( [ $today_wp, $today_local, $today_utc, $today_mx ] );
    $today       = $today_wp;

    $active_rule = null;
    foreach ( $rules as $rule ) {
        if ( in_array( $rule['delivery_date'], $valid_dates, true ) ) {
            $active_rule = $rule;
            break;
        }
    }

    if ( ! $active_rule ) {
        // Fallback: If no rule matches today's date, search for a rule that contains this ticket product ID
        foreach ( $rules as $rule ) {
            $rule_tickets = $rule['tickets'] ?? [];
            if ( isset( $rule_tickets[ $product_id ] ) && ! empty( $rule_tickets[ $product_id ]['apply'] ) ) {
                $active_rule = $rule;
                break;
            }
        }
    }

    if ( ! $active_rule ) {
        return null; // No active delivery rule today and no fallback, operate as standard ticket access
    }

    // 5. Check if this product is enabled in the active rule
    $rule_tickets = $active_rule['tickets'] ?? [];
    if ( ! isset( $rule_tickets[ $product_id ] ) || ! $rule_tickets[ $product_id ]['apply'] ) {
        return false; // Product not enabled for today's active/fallback delivery rule, block check-in!
    }

    $units = intval( $rule_tickets[ $product_id ]['units'] );
    if ( $units <= 0 ) {
        return false; // 0 units configured, block check-in!
    }

    // 6. Anti-Duplicate Protection
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $rule_numeric_id = crc32( $active_rule['id'] );

    $delivered_today = intval( $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_logs 
         WHERE staff_id = %d 
         AND type = 'qr_delivery' 
         AND rifa_id = %d 
         AND DATE(created_at) = %s",
         $attendee_id,
         $rule_numeric_id,
         $today
    ) ) );

    if ( $delivered_today >= $units ) {
        return false; // Already delivered all allowed units today, block duplicate check-in!
    }

    return true; // Fully authorized for today's physical delivery check-in (remaining units left)
}

/**
 * Helper to log WooCommerce order block notes once per request lifecycle
 */
function tbp_add_block_order_note_once( $attendee_id ) {
    static $note_added = [];
    if ( isset( $note_added[ $attendee_id ] ) ) {
        return;
    }
    $note_added[ $attendee_id ] = true;

    $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
    if ( ! $order_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
    }
    if ( $order_id ) {
        $order = wc_get_order( $order_id );
        if ( $order ) {
            $attendee_name = get_the_title( $attendee_id );
            if ( empty( $attendee_name ) || is_numeric( $attendee_name ) ) {
                $attendee_name = '#' . $attendee_id;
            }
            $order->add_order_note( sprintf( __( 'BLOQUEADO: Se evitó el check-in del Asistente %s (#%d) por no estar autorizado para registro/entrega hoy o por duplicado.', 'tbp-actividades' ), $attendee_name, $attendee_id ) );
        }
    }
}

/**
 * Filter event tickets check-in at application level
 */
add_filter( 'tec_tickets_attendee_checkin', 'tbp_authorize_attendee_checkin_filter', 10, 4 );
function tbp_authorize_attendee_checkin_filter( $checkin, $attendee_id, $event_id, $qr ) {
    $auth = tbp_is_attendee_authorized_for_delivery_today( $attendee_id );
    if ( $auth === false ) {
        tbp_log_delivery_debug( "BLOQUEADO (Filtro): El check-in del Asistente #{$attendee_id} fue bloqueado porque no está autorizado para entrega física el día de hoy o es duplicado.", [ 'attendee_id' => $attendee_id ] );
        tbp_add_block_order_note_once( $attendee_id );
        return false; // Block check-in
    }
    return $checkin;
}

/**
 * Filter post metadata additions/updates to prevent database check-in when unauthorized
 */
add_filter( 'add_post_metadata', 'tbp_prevent_unauthorized_checkin_meta', 10, 5 );
add_filter( 'update_post_metadata', 'tbp_prevent_unauthorized_checkin_meta', 10, 5 );
function tbp_prevent_unauthorized_checkin_meta( $check, $object_id, $meta_key, $meta_value, $prev_value = '' ) {
    $checkin_keys = [
        '_tribe_wooticket_checkedin',
        '_tribe_eddticket_checkedin',
        '_tribe_rsvp_checkedin',
        '_tribe_tpp_checkedin'
    ];

    if ( in_array( $meta_key, $checkin_keys, true ) && intval( $meta_value ) === 1 ) {
        $auth = tbp_is_attendee_authorized_for_delivery_today( $object_id );
        if ( $auth === false ) {
            tbp_log_delivery_debug( "BLOQUEADO (BD): El check-in del Asistente #{$object_id} fue bloqueado a nivel de base de datos porque no está autorizado para entrega física el día de hoy o es duplicado.", [ 'attendee_id' => $object_id ] );
            tbp_add_block_order_note_once( $object_id );
            return false; // Block check-in postmeta update
        }
    }
    return $check;
}

/**
 * Filter attendees table check-in column HTML to disable and grey out the button for unauthorized tickets
 */
add_filter( 'tec_tickets_attendees_table_column_check_in', 'tbp_disable_unauthorized_checkin_button', 20, 2 );
function tbp_disable_unauthorized_checkin_button( $html, $item ) {
    $attendee_id = intval( $item['attendee_id'] ?? 0 );
    if ( ! $attendee_id ) {
        return $html;
    }
    
    // Check if the attendee is authorized today
    $auth = tbp_is_attendee_authorized_for_delivery_today( $attendee_id );
    if ( $auth === false ) {
        // Disabling Check In button
        // 1. Add 'disabled' attribute and 'button-disabled' style class to the main check-in button
        $html = str_replace( 'class="components-button is-primary tickets_checkin', 'class="components-button is-primary tickets_checkin button-disabled', $html );
        
        // Remove click event handler class 'tickets_checkin' so Javascript won't trigger the AJAX call
        $html = str_replace( 'tickets_checkin', '', $html );
        
        // Inject disabled attribute if not already present
        if ( ! str_contains( $html, 'disabled' ) ) {
            $html = str_replace( 'class="', 'disabled="disabled" class="', $html );
        }
        
        // Add a nice visual indicator or change button text to show it is blocked
        $html = str_replace( 'Check In', 'No Autorizado', $html );
        $html = str_replace( 'Registrar', 'No Autorizado', $html );
        
        // Also let's style it with inline CSS just in case to look fully disabled (grey color, no pointer events)
        $html = str_replace( 'class="', 'style="background-color: #cbd5e1 !important; color: #64748b !important; border-color: #cbd5e1 !important; cursor: not-allowed !important; pointer-events: none !important;" class="', $html );
    }

    // 2. Add gorgeous multi-unit progression badge if there is an active rule today
    $event_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_event', true ) );
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_event', true ) );
    }
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_post_id', true ) );
    }
    if ( ! $event_id ) {
        $event_id = intval( get_post_meta( $attendee_id, '_event_id', true ) );
    }
    
    // Robust fallback using order discovery
    if ( ! $event_id ) {
        $order_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_order', true ) );
        if ( ! $order_id ) {
            $order_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_order', true ) );
        }
        if ( $order_id && function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
            $event_id = tbp_actividades_discover_event_id_from_order( $order_id );
        }
    }

    if ( $event_id ) {
        $cache_key = 'tbp_delivery_rules_' . $event_id;
        $rules = get_transient( $cache_key );
        if ( false === $rules ) {
            $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
            if ( ! is_array( $rules ) ) {
                $rules = [];
            }
            set_transient( $cache_key, $rules, HOUR_IN_SECONDS );
        }

        if ( ! empty( $rules ) ) {
            $product_id = intval( get_post_meta( $attendee_id, '_tribe_wooticket_product', true ) );
            if ( ! $product_id ) {
                $product_id = intval( get_post_meta( $attendee_id, '_tribe_tickets_product', true ) );
            }
            if ( ! $product_id ) {
                $product_id = intval( get_post_meta( $attendee_id, '_tec_tickets_commerce_ticket', true ) );
            }

            $today_wp    = date_i18n( 'Y-m-d' );
            $today_local = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
            $today_utc   = date( 'Y-m-d', current_time( 'timestamp', 1 ) );
            $today_mx    = date( 'Y-m-d', time() - 6 * HOUR_IN_SECONDS ); // Mexico (UTC-6) fallback
            $valid_dates = array_unique( [ $today_wp, $today_local, $today_utc, $today_mx ] );
            $today       = $today_wp;

            $active_rule = null;
            foreach ( $rules as $rule ) {
                if ( in_array( $rule['delivery_date'], $valid_dates, true ) ) {
                    $active_rule = $rule;
                    break;
                }
            }

            if ( ! $active_rule && $product_id ) {
                foreach ( $rules as $rule ) {
                    $rule_tickets = $rule['tickets'] ?? [];
                    if ( isset( $rule_tickets[ $product_id ] ) && ! empty( $rule_tickets[ $product_id ]['apply'] ) ) {
                        $active_rule = $rule;
                        break;
                    }
                }
            }

            if ( $active_rule ) {
                $rule_tickets = $active_rule['tickets'] ?? [];
                if ( isset( $rule_tickets[ $product_id ] ) && $rule_tickets[ $product_id ]['apply'] ) {
                    $allowed_units = intval( $rule_tickets[ $product_id ]['units'] );
                    if ( $allowed_units > 0 ) {
                        global $wpdb;
                        $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
                        $rule_numeric_id = crc32( $active_rule['id'] );

                        $delivered_today = intval( $wpdb->get_var( $wpdb->prepare(
                            "SELECT SUM(amount) FROM $table_logs 
                             WHERE staff_id = %d 
                             AND type = 'qr_delivery' 
                             AND rifa_id = %d 
                             AND DATE(created_at) = %s",
                            $attendee_id,
                            $rule_numeric_id,
                            $today
                        ) ) );

                        $remaining = $allowed_units - $delivered_today;

                        // Create a beautiful, premium visual badge next to the button
                        $badge_html = '';
                        if ( $delivered_today === 0 ) {
                            $badge_html = sprintf(
                                '<div style="display: inline-block; margin-left: 10px; vertical-align: middle; background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 11px; text-transform: uppercase; font-family: \'Segoe UI\', Arial, sans-serif; letter-spacing: 0.05em; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' . __( 'Entrega: %d u', 'tbp-actividades' ) . '</div>',
                                $allowed_units
                            );
                        } elseif ( $remaining > 0 ) {
                            $badge_html = sprintf(
                                '<div style="display: inline-block; margin-left: 10px; vertical-align: middle; background-color: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 11px; text-transform: uppercase; font-family: \'Segoe UI\', Arial, sans-serif; letter-spacing: 0.05em; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' . __( 'Restan: %d de %d u', 'tbp-actividades' ) . '</div>',
                                $remaining,
                                $allowed_units
                            );
                        } else {
                            $badge_html = sprintf(
                                '<div style="display: inline-block; margin-left: 10px; vertical-align: middle; background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; font-weight: 800; padding: 4px 10px; border-radius: 6px; font-size: 11px; text-transform: uppercase; font-family: \'Segoe UI\', Arial, sans-serif; letter-spacing: 0.05em; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">' . __( 'Entregado: %d u', 'tbp-actividades' ) . '</div>',
                                $allowed_units
                            );
                        }

                        // Append the badge to the button html!
                        $html .= $badge_html;
                    }
                }
            }
        }
    }

    return $html;
}

/**
 * ============================================================================
 * AUTOMATED TICKET GENERATION AND QR SCANNER DETECTABILITY FOR CUSTOM STATUSES
 * ============================================================================
 * 
 * WooCommerce: 'wc-processing' displays as '[Pagado con Tarjeta]'.
 * By default, Event Tickets REST APIs (which QR scanner apps use) only
 * download and validate attendees from 'wc-completed' orders.
 *
 * These filters register 'wc-processing' as a valid and public order status
 * so tickets are visible and scannable in the QR app automatically.
 *
 * NOTE: 'wc-p-pagado' (Pagado Parcialmente) is NOT included on purpose —
 * those orders still have a pending balance and must NOT be scannable!
 */

add_filter( 'tribe_tickets_repositories_order_public_statuses', 'tbp_register_custom_public_statuses_for_qr', 999 );
function tbp_register_custom_public_statuses_for_qr( $statuses ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = [];
    }
    $custom_paid = [ 'wc-processing', 'processing' ];
    return array_unique( array_merge( $statuses, $custom_paid ) );
}

add_filter( 'tribe_tickets_repositories_order_statuses', 'tbp_register_custom_order_statuses_for_qr', 999 );
function tbp_register_custom_order_statuses_for_qr( $statuses ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = [];
    }
    $custom_paid = [ 'wc-processing', 'processing' ];
    return array_unique( array_merge( $statuses, $custom_paid ) );
}

add_filter( 'wootickets_incomplete_order_states', 'tbp_remove_paid_statuses_from_incomplete_states', 999 );
function tbp_remove_paid_statuses_from_incomplete_states( $statuses ) {
    if ( is_array( $statuses ) ) {
        // Remove processing from incomplete list so ET+ treats it as completed and generates tickets
        $statuses = array_diff( $statuses, [ 'wc-processing' ] );
    }
    return $statuses;
}

/**
 * CRITICAL: Ensure tickets are actually GENERATED in the database when the order 
 * enters 'processing'. By default, Event Tickets Plus might only be configured to 
 * generate tickets on 'completed'. If the attendee post doesn't exist in the database, 
 * the QR app can never load it!
 */
add_filter( 'event_tickets_woo_ticket_generating_order_stati', 'tbp_generate_tickets_on_processing', 999 );
function tbp_generate_tickets_on_processing( $statuses ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = [];
    }
    $custom_paid = [ 'wc-processing', 'processing' ];
    return array_unique( array_merge( $statuses, $custom_paid ) );
}

add_filter( 'event_tickets_woo_complete_order_stati', 'tbp_dispatch_tickets_on_processing', 999 );
function tbp_dispatch_tickets_on_processing( $statuses ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = [];
    }
    $custom_paid = [ 'wc-processing', 'processing' ];
    return array_unique( array_merge( $statuses, $custom_paid ) );
}

// Modify the protected static $public_order_statuses inside Tribe__Tickets__Attendee_Repository
// using PHP Reflection to fully guarantee the scanning REST API returns these attendees!
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'Tribe__Tickets__Attendee_Repository' ) ) {
        try {
            $ref = new ReflectionClass( 'Tribe__Tickets__Attendee_Repository' );
            if ( $ref->hasProperty( 'public_order_statuses' ) ) {
                $prop = $ref->getProperty( 'public_order_statuses' );
                $prop->setAccessible( true );
                $statuses = $prop->getValue();
                if ( is_array( $statuses ) ) {
                    $statuses[] = 'wc-processing';
                    $prop->setValue( null, array_unique( $statuses ) );
                }
            }
        } catch ( Exception $e ) {
            // Suppress errors during boot
        }
    }
}, 99 );

/**
 * CRITICAL HACK FOR THE QR SCANNER APP:
 * The QR Scanner App might explicitly request `?order_status=wc-completed` when fetching attendees.
 * If it explicitly asks for completed, it ignores our `public_order_statuses` reflection fix.
 * We intercept the REST API request and FORCE 'wc-processing' into the requested statuses.
 */
add_action( 'rest_api_init', function() {
    if ( isset( $_GET['order_status'] ) && is_string( $_GET['order_status'] ) ) {
        if ( strpos( $_GET['order_status'], 'completed' ) !== false ) {
            $_GET['order_status'] .= ',wc-processing,processing';
            $_REQUEST['order_status'] .= ',wc-processing,processing';
        }
    }
    if ( isset( $_GET['status'] ) && is_string( $_GET['status'] ) ) {
        if ( strpos( $_GET['status'], 'completed' ) !== false ) {
            $_GET['status'] .= ',wc-processing,processing';
            $_REQUEST['status'] .= ',wc-processing,processing';
        }
    }
}, 1 );

/**
 * CRITICAL: Allow QR check-in for 'processing' orders.
 * 
 * Event Tickets Plus has a SEPARATE check at scan time (not just listing time).
 * CheckIn_Stati.php hardcodes ['completed'] as the only valid status for QR scanning.
 * Without this filter, the attendee would APPEAR in the app but scanning would FAIL
 * with a rejection because $order->get_status() === 'processing' is not in ['completed'].
 *
 * Filter: event_tickets_attendees_woo_checkin_stati
 * See: Tribe__Tickets_Plus__Commerce__WooCommerce__Main::checkin() line 2954
 */
add_filter( 'event_tickets_attendees_woo_checkin_stati', 'tbp_allow_processing_for_qr_checkin', 999 );
function tbp_allow_processing_for_qr_checkin( $stati ) {
    if ( ! is_array( $stati ) ) {
        $stati = [];
    }
    if ( ! in_array( 'processing', $stati, true ) ) {
        $stati[] = 'processing';
    }
    if ( ! in_array( 'wc-processing', $stati, true ) ) {
        $stati[] = 'wc-processing';
    }
    return $stati;
}

/**
 * CRITICAL HACK FOR QR SCANNER APP AUTHORIZATION:
 * The QR Scanner App explicitly hits a REST API endpoint (/tribe/tickets/v1/qr/checkin)
 * which pre-checks if the attendee's order status is in `get_completed_status_by_provider_name()`.
 * If it is NOT, it returns "Attendee Not Authorized" BEFORE even trying to check them in.
 * We MUST inject 'processing' and 'wc-processing' into this list so the REST API allows the scan.
 */
add_filter( 'tec_tickets_completed_status_by_provider_name', 'tbp_allow_processing_qr_authorization', 99, 2 );
function tbp_allow_processing_qr_authorization( $statuses, $provider_name ) {
    if ( ! is_array( $statuses ) ) {
        $statuses = (array) $statuses;
    }
    if ( ! in_array( 'wc-processing', $statuses, true ) ) {
        $statuses[] = 'wc-processing';
    }
    if ( ! in_array( 'processing', $statuses, true ) ) {
        $statuses[] = 'processing';
    }
    return $statuses;
}
