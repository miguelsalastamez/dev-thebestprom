<?php
/**
 * Physical Item Delivery Management for TBP Actividades
 * (Packages, Photos, Shirts, etc.)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode [tbp_gestion_entrega_fisica]
 */
add_shortcode( 'tbp_gestion_entrega_fisica', 'tbp_actividades_physical_delivery_shortcode' );
function tbp_actividades_physical_delivery_shortcode() {
    if ( ! function_exists('tbp_actividades_user_is_staff') || ! tbp_actividades_user_is_staff() ) {
        return '<p>' . __( 'Acceso restringido a personal autorizado.', 'tbp-actividades' ) . '</p>';
    }

    // Get Tribe Events
    $events = get_posts( array(
        'post_type' => 'tribe_events',
        'posts_per_page' => -1,
        'post_status' => array( 'publish', 'private' ),
        'orderby' => 'title',
        'order' => 'ASC'
    ) );

    ob_start();
    ?>
    <div id="tbp-physical-delivery-hub" class="tbp-actividades-container tbp-premium-ui">
        <div class="tbp-ui-header">
            <div class="tbp-ui-icon">📦</div>
            <h2><?php _e( 'Gestión de Entrega Física', 'tbp-actividades' ); ?></h2>
            <p class="tbp-ui-subtitle"><?php _e( 'Paquetes, Fotografías, Camisas y más', 'tbp-actividades' ); ?></p>
        </div>

        <div class="tbp-mode-tabs">
            <button class="tbp-tab-btn active" data-target="tbp-manual-mode">📋 <?php _e( 'Modo Manual', 'tbp-actividades' ); ?></button>
            <button class="tbp-tab-btn" data-target="tbp-fast-mode">⚡ <?php _e( 'Escáner Rápido', 'tbp-actividades' ); ?></button>
        </div>
        
        <!-- MODO MANUAL -->
        <div id="tbp-manual-mode" class="tbp-mode-container active">
            <div class="tbp-ui-steps">
            <!-- Step 1: Event Selection -->
            <div class="tbp-ui-step" id="step-1">
                <label class="tbp-label"><span class="step-num">1</span> <?php _e( 'Selecciona el Evento:', 'tbp-actividades' ); ?></label>
                <select id="tbp_pd_event_select" class="tbp-select">
                    <option value=""><?php _e( 'Seleccionar Evento...', 'tbp-actividades' ); ?></option>
                    <?php foreach ( $events as $event ) : ?>
                        <option value="<?php echo $event->ID; ?>"><?php echo esc_html( $event->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Step 1.5: Rule Selection (Dynamic) -->
            <div class="tbp-ui-step tbp-hidden" id="step-1-5">
                <label class="tbp-label"><span class="step-num">2</span> <?php _e( 'Selecciona la Fase de Entrega:', 'tbp-actividades' ); ?></label>
                <select id="tbp_pd_rule_select" class="tbp-select">
                    <option value=""><?php _e( 'Cargando fases...', 'tbp-actividades' ); ?></option>
                </select>
            </div>

            <!-- Step 2: Product Selection (Dynamic) -->
            <div class="tbp-ui-step tbp-hidden" id="step-2">
                <label class="tbp-label"><span class="step-num">3</span> <?php _e( 'Selecciona los productos a entregar:', 'tbp-actividades' ); ?></label>
                <div id="tbp_pd_product_list" class="tbp-checkbox-grid">
                    <!-- Loaded via AJAX -->
                </div>
            </div>

            <!-- Step 3: Order Search -->
            <div class="tbp-ui-step tbp-hidden" id="step-3">
                <label class="tbp-label"><span class="step-num">4</span> <?php _e( 'Detalles de la Entrega y Búsqueda:', 'tbp-actividades' ); ?></label>
                
                <div style="margin-bottom: 20px;">
                    <p style="font-size: 13px; color: #64748b; margin-bottom: 8px; font-weight: 600;"><?php _e( '¿Qué estás entregando en esta ocasión?', 'tbp-actividades' ); ?></p>
                    <textarea id="tbp_pd_delivery_details" class="tbp-input" style="min-height: 100px; resize: none; margin-bottom: 10px;" placeholder="<?php _e( 'Ej: Camisa conmemorativa tipo jersey, Fotogrupal 11x12, brazaletes de acceso...', 'tbp-actividades' ); ?>"></textarea>
                    <p style="font-size: 11px; color: #94a3b8; font-style: italic;"><?php _e( 'Este texto se incluirá en el mensaje automático al alumno.', 'tbp-actividades' ); ?></p>
                </div>

                <div class="tbp-search-box">
                    <input type="number" id="tbp_pd_order_search" class="tbp-input" placeholder="Ej. 35421" />
                    <button id="tbp_pd_btn_search" class="tbp-btn tbp-btn-primary">
                        <span class="btn-text"><?php _e( 'BUSCAR ALUMNO', 'tbp-actividades' ); ?></span>
                        <span class="btn-loader tbp-hidden"></span>
                    </button>
                </div>

                <div class="tbp-qr-divider">
                    <span><?php _e( 'O ESCANEA EL BOLETO', 'tbp-actividades' ); ?></span>
                </div>

                <div class="tbp-qr-section">
                    <button id="tbp_pd_btn_scan" class="tbp-btn tbp-btn-secondary" style="width: 100%;">
                        <span class="btn-icon">📷</span> <?php _e( 'ESCANEAR QR (CÁMARA)', 'tbp-actividades' ); ?>
                    </button>
                    <div id="tbp-qr-reader-container" class="tbp-hidden" style="margin-top: 20px; border-radius: 16px; overflow: hidden; border: 2px solid #e2e8f0;">
                        <div id="tbp-qr-reader" style="width: 100%;"></div>
                        <button id="tbp_pd_btn_stop_scan" class="tbp-btn tbp-btn-disabled" style="width: 100%; border-radius: 0; padding: 10px;">
                            <?php _e( 'Cerrar Cámara', 'tbp-actividades' ); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

            <div id="tbp_pd_search_results" class="tbp-results-container">
                <!-- Dynamic Content -->
            </div>
        </div>

        <!-- MODO FAST SCANNER -->
        <div id="tbp-fast-mode" class="tbp-mode-container tbp-hidden">
            <div class="tbp-fast-setup" id="fast-setup-panel">
                <label class="tbp-label"><span class="step-num">1</span> <?php _e( 'Selecciona el Evento a Escanear:', 'tbp-actividades' ); ?></label>
                <select id="tbp_fs_event_select" class="tbp-select" style="margin-bottom: 20px;">
                    <option value=""><?php _e( 'Seleccionar Evento...', 'tbp-actividades' ); ?></option>
                    <?php foreach ( $events as $event ) : ?>
                        <option value="<?php echo $event->ID; ?>"><?php echo esc_html( $event->post_title ); ?></option>
                    <?php endforeach; ?>
                </select>
                <button id="tbp_fs_btn_start" class="tbp-btn tbp-btn-primary tbp-btn-disabled" style="width: 100%;" disabled>
                    ⚡ <?php _e( 'INICIAR ESCÁNER', 'tbp-actividades' ); ?>
                </button>
            </div>

            <div id="tbp-fast-scanner-panel" class="tbp-hidden">
                <div class="tbp-fs-header">
                    <button id="tbp_fs_btn_back" class="tbp-btn-small">⬅ Volver</button>
                    <span id="tbp_fs_event_name" style="font-weight: 700;"></span>
                </div>
                
                <div class="tbp-fs-camera-wrapper">
                    <div id="tbp-fs-reader" style="width: 100%;"></div>
                    <div id="tbp-fs-overlay" class="tbp-fs-overlay">
                        <div class="tbp-fs-overlay-icon"></div>
                        <div class="tbp-fs-overlay-text"></div>
                    </div>
                </div>

                <div id="tbp-fs-last-scan" class="tbp-fs-last-scan">
                    <p style="color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase;">Último escaneo:</p>
                    <div id="tbp_fs_last_result" style="font-weight: 700; font-size: 15px;">Esperando código...</div>
                </div>
            </div>
        </div>

    </div>

    <!-- Audios for Fast Scanner -->
    <audio id="tbp_audio_success" preload="auto">
        <source src="data:audio/mp3;base64,//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq//NExAAQAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq" type="audio/mpeg">
    </audio>
    <audio id="tbp_audio_error" preload="auto">
        <source src="data:audio/mp3;base64,//NExAAAAANIAAAAAExBTUUzLjEwMKqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqqq" type="audio/mpeg">
    </audio>

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap');

        .tbp-premium-ui { 
            max-width: 900px; 
            margin: 40px auto; 
            padding: 40px; 
            background: #ffffff; 
            border-radius: 24px; 
            box-shadow: 0 20px 50px rgba(0,0,0,0.08); 
            font-family: 'Outfit', 'Inter', -apple-system, sans-serif;
            border: 1px solid #f1f5f9;
        }
        .tbp-ui-header { text-align: center; margin-bottom: 25px; }
        .tbp-ui-icon { font-size: 48px; margin-bottom: 15px; filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1)); }
        .tbp-ui-header h2 { margin: 0; color: #0f172a; font-size: 28px; font-weight: 800; letter-spacing: -0.025em; }
        .tbp-ui-subtitle { color: #64748b; margin-top: 8px; font-size: 16px; font-weight: 500; }
        
        .tbp-mode-tabs { display: flex; gap: 10px; margin-bottom: 30px; background: #f1f5f9; padding: 6px; border-radius: 14px; }
        .tbp-tab-btn { flex: 1; padding: 12px; font-size: 15px; font-weight: 700; border: none; background: transparent; color: #64748b; border-radius: 10px; cursor: pointer; transition: all 0.3s; }
        .tbp-tab-btn.active { background: #fff; color: #0f172a; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        
        .tbp-ui-steps { display: flex; flex-direction: column; gap: 30px; }
        .tbp-ui-step { 
            background: #f8fafc; 
            padding: 25px; 
            border-radius: 16px; 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); 
            border: 2px solid transparent; 
        }
        .tbp-ui-step:not(.tbp-hidden) { 
            border-color: #f1f5f9;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        }
        .tbp-hidden { display: none !important; }
        
        .tbp-label { display: block; font-weight: 700; color: #334155; margin-bottom: 20px; font-size: 16px; }
        .step-num { 
            background: linear-gradient(135deg, #f39c12, #e67e22); 
            color: #fff; 
            width: 28px; 
            height: 28px; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center; 
            border-radius: 50%; 
            font-size: 14px; 
            margin-right: 12px; 
            box-shadow: 0 2px 4px rgba(230, 126, 34, 0.3);
        }
        
        .tbp-select, .tbp-input { 
            width: 100%; 
            padding: 14px 18px; 
            border: 2px solid #e2e8f0; 
            border-radius: 12px; 
            font-size: 16px; 
            outline: none; 
            transition: all 0.2s;
            background: #f8fafc;
            color: #1e293b !important;
            height: auto;
            min-height: 52px;
            -webkit-appearance: none;
            -moz-appearance: none;
            appearance: none;
        }
        .tbp-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 18px center;
            background-size: 20px;
            padding-right: 45px;
        }
        .tbp-select:focus, .tbp-input:focus { 
            border-color: #f39c12; 
            background: #fff;
            box-shadow: 0 0 0 4px rgba(243, 156, 18, 0.15); 
        }
        
        .tbp-checkbox-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); 
            gap: 15px; 
            background: #f8fafc; 
            padding: 20px; 
            border-radius: 12px; 
            border: 2px solid #e2e8f0; 
        }
        .tbp-checkbox-item { 
            display: flex; 
            align-items: center; 
            gap: 12px; 
            font-size: 14px; 
            color: #475569; 
            cursor: pointer; 
            background: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        .tbp-checkbox-item:hover { border-color: #f39c12; background: #fffaf0; }
        .tbp-checkbox-item input { 
            width: 20px; 
            height: 20px; 
            cursor: pointer; 
            accent-color: #f39c12;
        }
        
        .tbp-search-box { display: flex; gap: 15px; }
        .tbp-btn { 
            padding: 14px 30px; 
            border-radius: 12px; 
            font-weight: 800; 
            cursor: pointer; 
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            border: none; 
            display: inline-flex; 
            align-items: center; 
            justify-content: center;
            min-width: 180px;
            font-size: 14px;
            letter-spacing: 0.025em;
        }
        .tbp-btn-primary { 
            background: linear-gradient(135deg, #f39c12, #e67e22); 
            color: #fff; 
            box-shadow: 0 4px 12px rgba(243, 156, 18, 0.3);
        }
        .tbp-btn-primary:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(243, 156, 18, 0.4);
        }
        .tbp-btn-success { 
            background: linear-gradient(135deg, #27ae60, #219150); 
            color: #fff; 
            box-shadow: 0 4px 12px rgba(39, 174, 96, 0.3);
            width: 100%;
        }
        .tbp-btn-success:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 6px 15px rgba(39, 174, 96, 0.4);
        }
        .tbp-btn-disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
            box-shadow: none;
        }
        .tbp-btn-disabled:hover { transform: none; box-shadow: none; }
        
        .tbp-btn-secondary {
            background: #fff;
            color: #0f172a;
            border: 2px solid #e2e8f0;
            box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        }
        .tbp-btn-secondary:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .tbp-qr-divider {
            text-align: center;
            margin: 25px 0;
            position: relative;
        }
        .tbp-qr-divider::before {
            content: "";
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: #e2e8f0;
            z-index: 1;
        }
        .tbp-qr-divider span {
            background: #ffffff;
            padding: 0 15px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 700;
            position: relative;
            z-index: 2;
        }
        
        .tbp-results-container { margin-top: 40px; }
        .tbp-result-card { 
            background: #fff; 
            border: 2px solid #f1f5f9; 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 10px 25px rgba(0,0,0,0.04); 
            animation: tbp-slide-up 0.4s ease-out;
        }
        @keyframes tbp-slide-up { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        .tbp-result-header { border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; }
        .tbp-result-header h3 { margin: 0; color: #0f172a; font-size: 20px; font-weight: 800; }
        
        .tbp-result-row { display: flex; justify-content: space-between; margin-bottom: 15px; font-size: 16px; align-items: center; }
        .tbp-result-label { color: #64748b; font-weight: 500; }
        .tbp-result-value { color: #1e293b; font-weight: 700; text-align: right; }
        .tbp-status-badge { 
            background: #f0fdf4; 
            color: #166534; 
            padding: 6px 14px; 
            border-radius: 9999px; 
            font-size: 13px; 
            font-weight: 800; 
            text-transform: uppercase; 
            border: 1px solid #dcfce7;
        }
        
        .tbp-error-msg { 
            background: #fff1f2; 
            border-left: 5px solid #f43f5e; 
            color: #9f1239; 
            padding: 20px; 
            border-radius: 12px; 
            margin-top: 25px; 
            font-size: 15px; 
            font-weight: 500;
        }
        .tbp-success-msg { 
            background: #f0fdf4; 
            border-left: 5px solid #22c55e; 
            color: #166534; 
            padding: 25px; 
            border-radius: 12px; 
            margin-top: 25px; 
            font-size: 16px; 
            font-weight: 600;
            text-align: center;
        }
        
        /* Fast Scanner Styles */
        .tbp-fs-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px; padding: 15px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0; }
        .tbp-btn-small { padding: 8px 15px; background: #e2e8f0; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; color: #475569; }
        .tbp-fs-camera-wrapper { position: relative; border-radius: 20px; overflow: hidden; background: #000; min-height: 300px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); border: 4px solid #1e293b; transition: border-color 0.3s; }
        .tbp-fs-camera-wrapper.success { border-color: #22c55e; }
        .tbp-fs-camera-wrapper.error { border-color: #ef4444; }
        
        .tbp-fs-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: rgba(0,0,0,0.8); z-index: 10; opacity: 0; pointer-events: none; transition: opacity 0.2s; }
        .tbp-fs-overlay.active { opacity: 1; }
        .tbp-fs-overlay.success { background: rgba(34, 197, 94, 0.9); }
        .tbp-fs-overlay.error { background: rgba(239, 68, 68, 0.9); }
        .tbp-fs-overlay-icon { font-size: 70px; margin-bottom: 10px; }
        .tbp-fs-overlay-text { color: #fff; font-size: 24px; font-weight: 800; text-align: center; padding: 0 20px; text-transform: uppercase; text-shadow: 0 2px 4px rgba(0,0,0,0.3); }
        
        .tbp-fs-last-scan { margin-top: 20px; padding: 20px; border-radius: 12px; border: 2px solid #e2e8f0; background: #f8fafc; text-align: center; }

        .btn-loader { width: 22px; height: 22px; border: 3px solid rgba(255,255,255,0.3); border-top-color: #fff; border-radius: 50%; animation: tbp-spin 0.6s linear infinite; }
    </style>

    <script src="https://unpkg.com/html5-qrcode"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
    jQuery(document).ready(function($) {
        var html5QrCode = null;
        var fastQrCode = null;
        var isFastScanning = false;
        var scanned_attendee_id = null;
        var scanned_security_code = null;

        // --- TAB SWITCHING LOGIC ---
        $('.tbp-tab-btn').on('click', function() {
            $('.tbp-tab-btn').removeClass('active');
            $(this).addClass('active');
            var target = $(this).data('target');
            $('.tbp-mode-container').addClass('tbp-hidden');
            $('#' + target).removeClass('tbp-hidden');

            // Stop fast scanner if switching to manual
            if (target === 'tbp-manual-mode' && fastQrCode) {
                stopFastScanner();
            }
        });

        // --- FAST SCANNER LOGIC ---
        $('#tbp_fs_event_select').on('change', function() {
            if ($(this).val()) {
                $('#tbp_fs_btn_start').removeClass('tbp-btn-disabled').prop('disabled', false);
            } else {
                $('#tbp_fs_btn_start').addClass('tbp-btn-disabled').prop('disabled', true);
            }
        });

        $('#tbp_fs_btn_start').on('click', function() {
            var eventId = $('#tbp_fs_event_select').val();
            var eventName = $('#tbp_fs_event_select option:selected').text();
            
            if (!eventId) return;

            $('#fast-setup-panel').addClass('tbp-hidden');
            $('#tbp-fast-scanner-panel').removeClass('tbp-hidden');
            $('#tbp_fs_event_name').text(eventName);

            startFastScanner(eventId);
        });

        $('#tbp_fs_btn_back').on('click', function() {
            stopFastScanner();
            $('#tbp-fast-scanner-panel').addClass('tbp-hidden');
            $('#fast-setup-panel').removeClass('tbp-hidden');
            $('#tbp_fs_last_result').html('Esperando código...').css('color', '#0f172a');
        });

        function playAudio(type) {
            try {
                var audio = document.getElementById('tbp_audio_' + type);
                if (audio) {
                    audio.currentTime = 0;
                    audio.play().catch(e => console.log('Audio autoplay blocked', e));
                }
                
                // Vibrate if supported
                if ("vibrate" in navigator) {
                    if (type === 'success') {
                        navigator.vibrate([100, 50, 100]);
                    } else {
                        navigator.vibrate(500);
                    }
                }
            } catch (e) {}
        }

        function triggerOverlay(type, message) {
            var overlay = $('#tbp-fs-overlay');
            var wrapper = $('.tbp-fs-camera-wrapper');
            var icon = type === 'success' ? '✅' : '❌';
            
            overlay.removeClass('success error').addClass(type);
            wrapper.removeClass('success error').addClass(type);
            
            overlay.find('.tbp-fs-overlay-icon').text(icon);
            overlay.find('.tbp-fs-overlay-text').text(message);
            
            overlay.addClass('active');

            setTimeout(function() {
                overlay.removeClass('active');
                wrapper.removeClass('success error');
            }, type === 'success' ? 1000 : 2500);
        }

        function processFastScan(qrUrl, eventId) {
            try {
                let url = new URL(qrUrl);
                let ticket_id = url.searchParams.get('ticket_id');
                let security_code = url.searchParams.get('security_code');

                if (!ticket_id) {
                    throw new Error("No ticket ID");
                }

                $('#tbp_fs_last_result').html('Validando... <span class="btn-loader" style="display:inline-block; border-color:#0f172a; width:14px; height:14px;"></span>');

                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'tbp_actividades_fast_scan',
                        ticket_id: ticket_id,
                        security_code: security_code,
                        event_id: eventId
                    },
                    success: function(response) {
                        if (response.success) {
                            playAudio('success');
                            triggerOverlay('success', response.data.message);
                            $('#tbp_fs_last_result').html('<span style="color:#22c55e;">✅ ' + response.data.message + '</span>');
                            // Resume scanning immediately
                            setTimeout(function(){ isFastScanning = true; }, 500);
                        } else {
                            playAudio('error');
                            triggerOverlay('error', response.data || 'Error desconocido');
                            $('#tbp_fs_last_result').html('<span style="color:#ef4444;">❌ ' + (response.data || 'Error') + '</span>');
                            // Pause longer on error
                            setTimeout(function(){ isFastScanning = true; }, 2500);
                        }
                    },
                    error: function() {
                        playAudio('error');
                        triggerOverlay('error', 'Error de Conexión');
                        $('#tbp_fs_last_result').html('<span style="color:#ef4444;">❌ Error de conexión</span>');
                        setTimeout(function(){ isFastScanning = true; }, 2500);
                    }
                });

            } catch (e) {
                playAudio('error');
                triggerOverlay('error', 'Código QR Inválido');
                $('#tbp_fs_last_result').html('<span style="color:#ef4444;">❌ QR Inválido</span>');
                setTimeout(function(){ isFastScanning = true; }, 2500);
            }
        }

        function startFastScanner(eventId) {
            if (!fastQrCode) {
                fastQrCode = new Html5Qrcode("tbp-fs-reader");
            }
            
            const config = { fps: 15, qrbox: { width: 250, height: 250 } };
            isFastScanning = true;

            fastQrCode.start({ facingMode: "environment" }, config, function(decodedText, decodedResult) {
                if (isFastScanning) {
                    isFastScanning = false; // Block further scans until processed
                    processFastScan(decodedText, eventId);
                }
            }, function(errorMessage) {
                // Ignore empty reads
            }).catch((err) => {
                alert('No se pudo iniciar la cámara. Verifica los permisos.');
                $('#tbp_fs_btn_back').click();
            });
        }

        function stopFastScanner() {
            isFastScanning = false;
            if (fastQrCode) {
                fastQrCode.stop().catch(e => console.log(e));
            }
        }

        // --- MANUAL MODE LOGIC ---
        // Step 1 -> Step 2
        $('#tbp_pd_event_select').on('change', function() {
            var event_id = $(this).val();
            if (!event_id) {
                $('#step-2, #step-3').addClass('tbp-hidden');
                return;
            }

            $('#tbp_pd_product_list').html('<div style="text-align:center; padding:20px; width:100%;"><div class="btn-loader" style="border-top-color:#f39c12; margin:0 auto;"></div><p style="margin-top:10px; color:#64748b; font-size:14px;">Cargando productos...</p></div>');
            $('#step-2').removeClass('tbp-hidden');
            $('#step-3').addClass('tbp-hidden');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_get_event_products',
                    event_id: event_id
                },
                success: function(response) {
                    if (response.success) {
                        $('#tbp_pd_product_list').html(response.data.products_html);
                        $('#tbp_pd_rule_select').html(response.data.rules_html);
                        
                        $('#step-1-5').removeClass('tbp-hidden');
                        
                        if (response.data.products_html.indexOf('checkbox') !== -1) {
                            $('#step-3').removeClass('tbp-hidden');
                        }
                    } else {
                        $('#tbp_pd_product_list').html('<div style="color:red; padding:10px;">' + (response.data || 'Error') + '</div>');
                    }
                }
            });
        });

        // QR Scanner Logic
        $('#tbp_pd_btn_scan').on('click', function() {
            $('#tbp-qr-reader-container').removeClass('tbp-hidden');
            $(this).addClass('tbp-hidden');
            
            if (!html5QrCode) {
                html5QrCode = new Html5Qrcode("tbp-qr-reader");
            }
            
            const config = { fps: 10, qrbox: { width: 250, height: 250 } };
            
            html5QrCode.start({ facingMode: "environment" }, config, function(decodedText, decodedResult) {
                // Success Callback
                html5QrCode.stop().then((ignore) => {
                    $('#tbp-qr-reader-container').addClass('tbp-hidden');
                    $('#tbp_pd_btn_scan').removeClass('tbp-hidden');
                    
                    try {
                        let url = new URL(decodedText);
                        let ticket_id = url.searchParams.get('ticket_id');
                        let security_code = url.searchParams.get('security_code');
                        
                        if (ticket_id) {
                            scanned_attendee_id = ticket_id;
                            scanned_security_code = security_code;
                            // Trigger the search automatically, but using ticket_id instead of order_id
                            trigger_search(ticket_id, true);
                        } else {
                            alert('El código QR no pertenece a un boleto de The Events Calendar válido.');
                        }
                    } catch (e) {
                        alert('Código QR no válido o URL incorrecta.');
                    }
                }).catch((err) => {
                    console.log(err);
                });
            }, function(errorMessage) {
                // Parse error, ignore
            }).catch((err) => {
                alert('No se pudo iniciar la cámara. Verifica los permisos.');
            });
        });

        $('#tbp_pd_btn_stop_scan').on('click', function() {
            if (html5QrCode) {
                html5QrCode.stop().then((ignore) => {
                    $('#tbp-qr-reader-container').addClass('tbp-hidden');
                    $('#tbp_pd_btn_scan').removeClass('tbp-hidden');
                }).catch((err) => {
                    console.log(err);
                });
            }
        });

        // Search Action
        $('#tbp_pd_btn_search').on('click', function() {
            var order_id = $('#tbp_pd_order_search').val();
            if (!order_id) {
                alert('Por favor ingresa un número de pedido.');
                return;
            }
            scanned_attendee_id = null; // Reset
            scanned_security_code = null;
            trigger_search(order_id, false);
        });

        function trigger_search(search_id, is_ticket_id) {
            var event_id = $('#tbp_pd_event_select').val();
            var product_ids = [];
            
            $('#tbp_pd_product_list input:checked').each(function() {
                product_ids.push($(this).val());
            });

            if (product_ids.length === 0) {
                alert('Por favor selecciona al menos un tipo de producto.');
                return;
            }

            var btn = $('#tbp_pd_btn_search');
            btn.find('.btn-text').addClass('tbp-hidden');
            btn.find('.btn-loader').removeClass('tbp-hidden');
            btn.prop('disabled', true);
            $('#tbp_pd_search_results').html('');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_search_order_physical',
                    search_id: search_id,
                    is_ticket_id: is_ticket_id ? 1 : 0,
                    event_id: event_id,
                    product_ids: product_ids
                },
                success: function(response) {
                    btn.find('.btn-text').removeClass('tbp-hidden');
                    btn.find('.btn-loader').addClass('tbp-hidden');
                    btn.prop('disabled', false);
                    $('#tbp_pd_search_results').html(response);
                },
                error: function() {
                    btn.find('.btn-text').removeClass('tbp-hidden');
                    btn.find('.btn-loader').addClass('tbp-hidden');
                    btn.prop('disabled', false);
                    alert('Error de conexión.');
                }
            });
        }

        // Delivery Action
        $(document).on('click', '#tbp_pd_btn_deliver', function() {
            var order_id = $(this).data('order-id');
            var product_names = $(this).data('products');
            var do_checkin = $('#tbp_pd_do_checkin').is(':checked') ? 1 : 0;

            if (!confirm('¿Confirmas que has entregado físicamente los productos a este alumno? Se enviará una notificación automática.')) return;

            var btn = $(this);
            btn.prop('disabled', true).html('<div class="btn-loader" style="margin:0 auto;"></div>');

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'tbp_actividades_record_delivery_physical',
                    order_id: order_id,
                    product_names: product_names,
                    delivery_details: $('#tbp_pd_delivery_details').val(),
                    do_checkin: do_checkin,
                    attendee_id: scanned_attendee_id,
                    security_code: scanned_security_code,
                    event_id: $('#tbp_pd_event_select').val(),
                    rule_id: $('#tbp_pd_rule_select').val()
                },
                success: function(response) {
                    if (response.success) {
                        $('#tbp_pd_search_results').html('<div class="tbp-success-msg">🎉 ' + response.data + '</div><div style="text-align:center; margin-top:20px;"><button onclick="location.reload()" class="tbp-btn" style="background:#f1f5f9; color:#475569; min-width:200px;">Nueva Búsqueda</button></div>');
                    } else {
                        alert(response.data || 'Error al registrar entrega.');
                        btn.prop('disabled', false).text('REGISTRAR ENTREGA');
                    }
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * AJAX: Get Event Products
 */
add_action( 'wp_ajax_tbp_actividades_get_event_products', 'tbp_actividades_get_event_products_ajax' );
function tbp_actividades_get_event_products_ajax() {
    $event_id = intval( $_POST['event_id'] );
    if ( ! $event_id ) wp_die( 'ID de evento inválido.' );

    if ( ! class_exists( 'Tribe__Tickets__Tickets' ) ) {
        wp_die( 'Error: Event Tickets Plus no está activo.' );
    }

    $tickets = Tribe__Tickets__Tickets::get_all_event_tickets( $event_id );
    
    if ( empty( $tickets ) ) {
        echo '<div style="text-align:center; padding:10px; width:100%; color:#e53e3e; font-weight:600;">' . __( '⚠️ No se encontraron tickets para este evento.', 'tbp-actividades' ) . '</div>';
        wp_die();
    }

    $html = '';
    foreach ( $tickets as $ticket ) {
        $product_id = is_object( $ticket ) ? ( $ticket->ID ?? ($ticket->ticket_id ?? 0) ) : intval( $ticket );
        $title = is_object( $ticket ) ? ( $ticket->post_title ?? get_the_title($product_id) ) : get_the_title($product_id);
        
        $html .= '<label class="tbp-checkbox-item">';
        $html .= '<input type="checkbox" name="pd_products[]" value="' . $product_id . '" checked> ';
        $html .= '<span>' . esc_html( $title ) . '</span>';
        $html .= '</label>';
    }

    // Fetch Delivery Rules
    $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
    $rules_html = '<option value="">' . __( 'Seleccionar Fase...', 'tbp-actividades' ) . '</option>';
    if ( is_array( $rules ) && ! empty( $rules ) ) {
        foreach ( $rules as $rule ) {
            $rules_html .= '<option value="' . esc_attr( $rule['id'] ) . '">' . esc_html( $rule['delivery_type'] . ' (' . $rule['delivery_date'] . ')' ) . '</option>';
        }
    } else {
        $rules_html .= '<option value="legacy_phase">' . __( 'Fase General (Sin Reglas Activas)', 'tbp-actividades' ) . '</option>';
    }

    wp_send_json_success(array(
        'products_html' => $html,
        'rules_html'    => $rules_html
    ));
}

/**
 * AJAX: Search Order
 */
add_action( 'wp_ajax_tbp_actividades_search_order_physical', 'tbp_actividades_search_order_physical_ajax' );
function tbp_actividades_search_order_physical_ajax() {
    $search_id = intval( $_POST['search_id'] );
    $is_ticket_id = intval( $_POST['is_ticket_id'] );
    $selected_event_id = intval( $_POST['event_id'] );
    $selected_product_ids = array_map( 'intval', (array) $_POST['product_ids'] );

    $order_id = $search_id;

    if ( $is_ticket_id === 1 ) {
        // Resolve order_id from the ticket (Attendee) post meta
        $attendee_post = get_post( $search_id );
        if ( ! $attendee_post || $attendee_post->post_type !== 'tribe_wooticket' ) {
            echo '<div class="tbp-error-msg">❌ ' . __( 'Código QR no válido. No se encontró el boleto en el sistema.', 'tbp-actividades' ) . '</div>';
            wp_die();
        }
        $order_id = intval( get_post_meta( $search_id, '_tribe_wooticket_order', true ) );
    }

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        echo '<div class="tbp-error-msg">❌ ' . __( 'Pedido no encontrado.', 'tbp-actividades' ) . '</div>';
        wp_die();
    }

    // 1. Validate Status
    $status = $order->get_status();
    $valid_statuses = array( 'completed', 'processing' );
    if ( ! in_array( $status, $valid_statuses ) ) {
        echo '<div class="tbp-error-msg">⚠️ ' . sprintf( __( 'El pedido tiene estado "%s". Debe estar Completado o Procesando (Pagado) para proceder.', 'tbp-actividades' ), wc_get_order_status_name($status) ) . '</div>';
        wp_die();
    }

    // 2. Validate Event (7-Layer Discovery)
    if ( ! function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
        require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
    }
    $discovered_event_id = tbp_actividades_discover_event_id_from_order( $order_id );
    
    if ( $discovered_event_id != $selected_event_id ) {
        echo '<div class="tbp-error-msg">🚫 ' . __( 'El pedido no pertenece al evento seleccionado.', 'tbp-actividades' ) . '</div>';
        if ( current_user_can('manage_options') ) {
            echo '<div style="font-size:11px; color:#64748b; margin-top:10px; background:#f1f5f9; padding:8px; border-radius:6px; border:1px dashed #cbd5e0;">';
            echo '<strong>Debug Admin:</strong><br>';
            echo 'ID Evento Seleccionado: ' . esc_html($selected_event_id) . ' (' . get_the_title($selected_event_id) . ')<br>';
            echo 'ID Evento Detectado: ' . esc_html($discovered_event_id) . ' (' . ($discovered_event_id ? get_the_title($discovered_event_id) : 'No detectado') . ')';
            echo '</div>';
        }
        wp_die();
    }

    // 3. Check for double delivery
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $delivery_log = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_logs WHERE order_id = %d AND type = 'delivery_items' ORDER BY created_at DESC LIMIT 1", $order_id ) );
    
    $is_already_delivered = ($delivery_log || get_post_meta($order_id, '_tbp_entrega_paquetes', true) === '1');

    // 4. Check for selected products and fetch metadata
    if ( ! function_exists( 'tbp_actividades_get_order_attendees_meta' ) ) {
        require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
    }
    $all_attendees = tbp_actividades_get_order_attendees_meta( $order_id, $order );
    
    $found_products = array();
    $metadata_to_display = array();

    foreach ( $order->get_items() as $item ) {
        $pid = $item->get_product_id();
        if ( in_array( $pid, $selected_product_ids ) ) {
            $found_products[] = $item->get_name();
        }
    }

    if ( empty( $found_products ) ) {
        echo '<div class="tbp-error-msg">📦 ' . __( 'El pedido no contiene ninguno de los productos seleccionados.', 'tbp-actividades' ) . '</div>';
        wp_die();
    }

    // Process metadata for display
    foreach ( $all_attendees as $source_id => $guests ) {
        // Find which product this source belongs to
        $p_id = false;
        if ( strpos($source_id, 'order_') === 0 ) {
             preg_match('/_prod_(\d+)/', $source_id, $m);
             if(!empty($m)) $p_id = intval($m[1]);
        } else if ( strpos($source_id, 'item_') === 0 ) {
             $item_id = str_replace('item_', '', $source_id);
             $item = $order->get_item($item_id);
             if($item) $p_id = $item->get_product_id();
        } else {
             $p_id = get_post_meta( $source_id, '_tribe_wooticket_product', true );
        }

        if ( $p_id && in_array( intval($p_id), $selected_product_ids ) ) {
            foreach ( $guests as $idx => $fields ) {
                $metadata_to_display[] = array(
                    'product_name' => get_the_title($p_id),
                    'fields' => $fields
                );
            }
        }
    }

    // Success! Render Result Card
    $student_name = $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();
    if ( empty( trim($student_name) ) ) {
        $student_name = $order->get_formatted_billing_full_name();
    }
    
    if ( $is_already_delivered ) {
        $staff_name = $delivery_log ? get_the_author_meta('display_name', $delivery_log->staff_id) : __('Personal Staff', 'tbp-actividades');
        $date = $delivery_log ? date_i18n(get_option('date_format') . ' H:i', strtotime($delivery_log->created_at)) : '';
        echo '<div class="tbp-error-msg" style="background: #fffbeb; border-left-color: #f59e0b; color: #92400e; margin-bottom: 20px;">';
        echo '<strong>⚠️ ' . __( 'ENTREGA YA REGISTRADA', 'tbp-actividades' ) . '</strong><br>';
        echo sprintf( __( 'Este pedido ya fue marcado como entregado por %s el %s.', 'tbp-actividades' ), '<strong>'.$staff_name.'</strong>', '<strong>'.$date.'</strong>' );
        echo '</div>';
    }
    ?>
    <div class="tbp-result-card">
        <div class="tbp-result-header">
            <h3>🎓 Alumno: <?php echo esc_html( strtoupper($student_name) ); ?></h3>
        </div>
        <div class="tbp-result-body">
            <div class="tbp-result-row">
                <span class="tbp-result-label">Estado del Pedido:</span>
                <span class="tbp-status-badge"><?php echo wc_get_order_status_name($status); ?></span>
            </div>
            <div class="tbp-result-row">
                <span class="tbp-result-label">Productos Comprados:</span>
                <span class="tbp-result-value"><?php echo esc_html( implode(', ', $found_products) ); ?></span>
            </div>
            
            <?php if ( ! empty($metadata_to_display) ) : ?>
                <div class="tbp-metadata-section" style="margin-top:20px; background:#f8fafc; padding:15px; border-radius:12px; border:1px solid #e2e8f0;">
                    <h4 style="margin:0 0 10px 0; font-size:14px; color:#475569; text-transform:uppercase; letter-spacing:0.05em;">Datos de Entrega (Metadatos):</h4>
                    <?php 
                    $has_any_field = false;
                    foreach ( $metadata_to_display as $att ) : 
                        $rendered_fields = 0;
                    ?>
                        <div class="tbp-att-meta-block" style="margin-bottom:12px; padding-bottom:8px; border-bottom:1px dashed #cbd5e0;">
                            <div style="font-size:11px; color:#94a3b8; margin-bottom:5px;"><?php echo esc_html($att['product_name']); ?></div>
                            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                                <?php foreach ( $att['fields'] as $label => $val ) : 
                                    $display_val = is_array($val) ? ($val['value'] ?? ($val[0] ?? '')) : $val;
                                    if ( empty($display_val) ) continue;
                                    $has_any_field = true;
                                    $rendered_fields++;
                                ?>
                                    <div style="font-size:13px;">
                                        <span style="color:#64748b; font-weight:500;"><?php echo esc_html($label); ?>:</span> 
                                        <span style="color:#1e293b; font-weight:700;"><?php echo esc_html($display_val); ?></span>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if ( $rendered_fields === 0 ) : ?>
                                    <div style="font-size:12px; color:#94a3b8; font-style:italic; grid-column: span 2;">
                                        <?php _e( 'Sin datos específicos para este ticket.', 'tbp-actividades' ); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if ( ! $has_any_field && current_user_can('manage_options') ) : ?>
                        <div style="font-size:10px; color:#94a3b8; margin-top:10px;">
                            <strong>Debug Raw Meta:</strong> <?php echo esc_html(wp_json_encode($metadata_to_display)); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="tbp-result-row" style="margin-top:20px; border-top:1px solid #f1f5f9; padding-top:15px;">
                <span class="tbp-result-label">Total Pedido:</span>
                <span class="tbp-result-value" style="color:#16a34a; font-size:18px;"><?php echo $order->get_formatted_order_total(); ?></span>
            </div>

            <?php if ( class_exists( 'Tribe__Tickets__Tickets' ) && ! $is_already_delivered ) : ?>
                <div style="margin-top:25px; padding: 15px; background: #f0f9ff; border: 1px solid #bae6fd; border-radius: 12px;">
                    <label class="tbp-checkbox-item" style="background: transparent; border: none; padding: 0; cursor: pointer;">
                        <input type="checkbox" id="tbp_pd_do_checkin" value="1" checked style="width: 24px; height: 24px; accent-color: #0284c7;"> 
                        <span style="font-size: 15px; color: #0369a1; font-weight: 700;">✅ Validar Acceso al Evento (Hacer Check-in)</span>
                    </label>
                    <p style="margin: 5px 0 0 36px; font-size: 12px; color: #0284c7;">Al entregar los productos, el alumno también será marcado como "Asistió" en The Events Calendar.</p>
                </div>
            <?php endif; ?>
            
            <div style="margin-top:30px;">
                <button id="tbp_pd_btn_deliver" class="tbp-btn <?php echo $is_already_delivered ? 'tbp-btn-disabled' : 'tbp-btn-success'; ?>" 
                        data-order-id="<?php echo esc_attr( $order_id ); ?>" 
                        <?php disabled($is_already_delivered); ?>
                        data-products="<?php echo esc_attr( implode(', ', $found_products) ); ?>">
                    <?php echo $is_already_delivered ? __( 'ENTREGA YA REGISTRADA', 'tbp-actividades' ) : __( 'REGISTRAR ENTREGA', 'tbp-actividades' ); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
    wp_die();
}

/**
 * AJAX: Record Delivery
 */
add_action( 'wp_ajax_tbp_actividades_record_delivery_physical', 'tbp_actividades_record_delivery_physical_ajax' );
function tbp_actividades_record_delivery_physical_ajax() {
    $order_id = intval( $_POST['order_id'] );
    $product_names = sanitize_text_field( $_POST['product_names'] );
    $do_checkin = isset( $_POST['do_checkin'] ) ? intval( $_POST['do_checkin'] ) : 0;
    $attendee_id = isset( $_POST['attendee_id'] ) ? intval( $_POST['attendee_id'] ) : 0;
    $event_id = isset( $_POST['event_id'] ) ? intval( $_POST['event_id'] ) : 0;
    $rule_id = isset( $_POST['rule_id'] ) ? sanitize_text_field( $_POST['rule_id'] ) : 'legacy_phase';

    $order = wc_get_order( $order_id );
    if ( ! $order ) {
        wp_send_json_error( 'Pedido no encontrado.' );
    }

    // 1. Log in DB
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $rule_numeric_id = ($rule_id === 'legacy_phase') ? 0 : crc32($rule_id);
    
    $wpdb->insert( $table_logs, array(
        'staff_id' => get_current_user_id(),
        'order_id' => $order_id,
        'rifa_id'  => $rule_numeric_id, // Store the phase ID CRC32
        'amount'   => 1,
        'type'     => 'delivery_items',
        'created_at' => current_time('mysql')
    ) );

    // 1.1 Tag order for reports
    update_post_meta( $order_id, '_tbp_entrega_paquetes', '1' );
    
    // 1.2 Tag order with specific Delivery Rule ID for multi-phase tracking
    add_post_meta( $order_id, '_tbp_delivery_rule_id', $rule_id, false );

    // 2. Perform Event Tickets Check-in if requested
    $checkin_msg = '';
    if ( $do_checkin === 1 && $event_id > 0 && class_exists( 'Tribe__Tickets__Tickets' ) ) {
        if ( $attendee_id > 0 ) {
            // We scanned a specific QR
            try {
                $ticket_provider = tribe('tickets.data_api')->get_ticket_provider($attendee_id);
                if ( $ticket_provider ) {
                    $success = $ticket_provider->checkin($attendee_id, true, $event_id);
                    if ($success) $checkin_msg = ' y Boleto Check-in ✅';
                }
            } catch (Exception $e) {}
        } else {
            // Manual search by Order ID: Check in ALL attendees of this order for this event
            $attendees = get_posts(array(
                'post_type' => 'tribe_wooticket',
                'posts_per_page' => -1,
                'meta_query' => array(
                    array( 'key' => '_tribe_wooticket_order', 'value' => $order_id ),
                    array( 'key' => '_tribe_wooticket_event', 'value' => $event_id )
                )
            ));
            $checked_count = 0;
            foreach ($attendees as $att) {
                try {
                    $ticket_provider = tribe('tickets.data_api')->get_ticket_provider($att->ID);
                    if ( $ticket_provider && $ticket_provider->checkin($att->ID, true, $event_id) ) {
                        $checked_count++;
                    }
                } catch (Exception $e) {}
            }
            if ($checked_count > 0) $checkin_msg = ' y ' . $checked_count . ' Boletos Check-in ✅';
        }
    }

    // 3. Add Order Note (Notification to Customer)
    $student_name = $order->get_billing_first_name();
    $delivery_details = isset($_POST['delivery_details']) ? sanitize_textarea_field($_POST['delivery_details']) : '';
    $details_segment = !empty($delivery_details) ? $delivery_details : 'paquete';
    
    $msg = sprintf( 
        "¡Hola %s! 👋 Confirmamos que hemos entregado tu %s satisfactoriamente de tu (%s). Muchas gracias por tu preferencia y confianza. ¡Disfruta de tu evento! 🎓✨\n\nAtte. The Best Prom - El Ticketmaster de las graduaciones.",
        $student_name,
        $details_segment,
        $product_names
    );
    
    // Note for customer (1 = is_customer_note)
    $order->add_order_note( $msg, 1 );

    wp_send_json_success( 'Entrega registrada exitosamente' . $checkin_msg . ' y notificación enviada al alumno.' );
}

/**
 * AJAX: Fast Scanner Endpoint (Option B)
 */
add_action( 'wp_ajax_tbp_actividades_fast_scan', 'tbp_actividades_fast_scan_ajax' );
function tbp_actividades_fast_scan_ajax() {
    $ticket_id = intval( $_POST['ticket_id'] );
    $security_code = sanitize_text_field( $_POST['security_code'] );
    $event_id = intval( $_POST['event_id'] );

    if ( ! $ticket_id || ! $event_id ) {
        wp_send_json_error( 'Faltan parámetros.' );
    }

    // 1. Validar el boleto nativo y código de seguridad
    $attendee = get_post( $ticket_id );
    if ( ! $attendee || $attendee->post_type !== 'tribe_wooticket' ) {
        wp_send_json_error( 'Boleto no encontrado o formato incorrecto.' );
    }

    $real_security_code = get_post_meta( $ticket_id, '_tribe_tickets_security_code', true );
    if ( $real_security_code && $real_security_code !== $security_code ) {
        wp_send_json_error( 'Código de seguridad inválido. Posible falsificación.' );
    }

    // 2. Obtener la regla de entrega activa para HOY (Copiado de la lógica de rules)
    $cache_key = 'tbp_delivery_rules_' . $event_id;
    $rules = get_transient( $cache_key );
    if ( false === $rules ) {
        $rules = get_post_meta( $event_id, '_tbp_event_delivery_rules', true );
        if ( ! is_array( $rules ) ) { $rules = []; }
        set_transient( $cache_key, $rules, HOUR_IN_SECONDS );
    }

    if ( empty( $rules ) ) {
        wp_send_json_error( 'No hay Reglas de Entrega configuradas para este evento.' );
    }

    $today_wp = date_i18n( 'Y-m-d' );
    $today_local = date( 'Y-m-d', current_time( 'timestamp', 0 ) );
    $today_utc = date( 'Y-m-d', current_time( 'timestamp', 1 ) );
    $today_mx = date( 'Y-m-d', time() - 6 * HOUR_IN_SECONDS );
    $valid_dates = array_unique( [ $today_wp, $today_local, $today_utc, $today_mx ] );

    $active_rule = null;
    foreach ( $rules as $rule ) {
        if ( in_array( $rule['delivery_date'], $valid_dates, true ) ) {
            $active_rule = $rule;
            break;
        }
    }

    if ( ! $active_rule ) {
        wp_send_json_error( 'No hay ninguna regla de entrega activa programada para el día de hoy.' );
    }

    // 3. Obtener Producto y Pedido
    $product_id = intval( get_post_meta( $ticket_id, '_tribe_wooticket_product', true ) );
    $order_id = intval( get_post_meta( $ticket_id, '_tribe_wooticket_order', true ) );

    if ( ! $product_id || ! $order_id ) {
        wp_send_json_error( 'Boleto corrupto: No tiene pedido o producto en WooCommerce asociado.' );
    }

    $rule_tickets = $active_rule['tickets'] ?? [];
    if ( ! isset( $rule_tickets[ $product_id ] ) || ! $rule_tickets[ $product_id ]['apply'] ) {
        wp_send_json_error( 'Este boleto no incluye acceso/entrega para: ' . $active_rule['delivery_type'] );
    }

    $allowed_units = intval( $rule_tickets[ $product_id ]['units'] );
    if ( $allowed_units <= 0 ) {
        wp_send_json_error( 'Las unidades permitidas en la regla son inválidas (0).' );
    }

    // 4. Protección contra duplicados (Consultar base de datos)
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    $rule_numeric_id = crc32( $active_rule['id'] );
    
    // We search by order_id or attendee_id? In event-delivery-rules, it saves it by attendee_id (staff_id column? Wait!)
    // Let's use the actual hook to do the delivery to ensure full compatibility.
    // BUT we need to check duplicate first.
    // In the hook: `staff_id = $attendee_id` AND `type = 'qr_delivery'` AND `rifa_id = $rule_numeric_id` AND `DATE(created_at) = $today_wp`
    $delivered_today = intval( $wpdb->get_var( $wpdb->prepare(
        "SELECT SUM(amount) FROM $table_logs 
         WHERE staff_id = %d 
         AND type = 'qr_delivery' 
         AND rifa_id = %d 
         AND DATE(created_at) = %s",
         $ticket_id,
         $rule_numeric_id,
         $today_wp
    ) ) );

    if ( $delivered_today >= $allowed_units ) {
        wp_send_json_error( 'YA ENTREGADO / ESCANEADO HOY' );
    }

    // 5. Todo OK, procedemos con el Check-in de Tribe (que disparará el hook automáticamente)
    // The hook in event-delivery-rules.php WILL run because we checkin through the official provider!
    $checkin_success = false;
    if ( class_exists( 'Tribe__Tickets__Tickets' ) ) {
        try {
            $ticket_provider = tribe('tickets.data_api')->get_ticket_provider($ticket_id);
            if ( $ticket_provider ) {
                $checkin_success = $ticket_provider->checkin($ticket_id, true, $event_id);
            }
        } catch (Exception $e) {}
    }

    // Si el hook de reglas no lo procesó (ej. por sync/async timeout), nosotros logueamos para prevenir errores
    // But usually the hook fires immediately on checkin.
    $product_title = get_the_title($product_id);
    $student_name = '';
    $order = wc_get_order( $order_id );
    if ($order) {
        $student_name = strtoupper( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );
    }

    wp_send_json_success( array(
        'message' => $student_name . ' (' . $allowed_units . 'x ' . $product_title . ')',
        'checkin' => $checkin_success
    ) );
}
