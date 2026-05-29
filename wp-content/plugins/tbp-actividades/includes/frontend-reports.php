<?php
/**
 * Frontend Reports for TBP Actividades
 * Implementa el shortcode [tbp_reporte_asistentes] (Versión 8.4.0 - Exclusive Partial Mode + Stats Banner)
 * 
 * ESTRATEGIA DEFINITIVA:
 * - La tabla tec_tickets_attendees tiene event_id y order_id exactos.
 * - Si existe, se usa para obtener los order_ids del evento directamente.
 * - Si no, fallback por product meta _tribe_tickets_event.
 * - Los detalles del asistente se obtienen con tbp_actividades_get_order_attendees_meta().
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Shortcode logic: [tbp_reporte_asistentes]
 */
add_shortcode( 'tbp_reporte_asistentes', 'tbp_actividades_attendee_report_shortcode' );

function tbp_actividades_attendee_report_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'event_id' => get_the_ID(),
        'limit'    => 15, 
    ), $atts, 'tbp_reporte_asistentes' );

    $event_id    = intval( $atts['event_id'] );
    $event_title = get_the_title( $event_id );

    // Verificar si existe el snapshot
    $upload_dir   = wp_upload_dir();
    $snapshot_rel = '/tbp-snapshots/event-' . $event_id . '.json';
    $snapshot_path = $upload_dir['basedir'] . $snapshot_rel;
    $snapshot_url  = file_exists($snapshot_path) ? ($upload_dir['baseurl'] . $snapshot_rel) : '';

    ob_start();
    ?>
    <div class="tbp-report-container" id="tbp-report-context" 
         data-event-id="<?php echo $event_id; ?>"
         data-snapshot-url="<?php echo esc_url($snapshot_url); ?>">
        <style>
            .tbp-report-container { font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; margin: 15px 0; background: #fff; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); overflow: hidden; border: 1px solid #e2e8f0; }
            .tbp-report-header { padding: 20px 25px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
            .tbp-report-header-info { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; flex-wrap: wrap; gap: 10px; }
            .tbp-report-header-info h3 { margin: 0; font-size: 1.1rem; color: #0f172a; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
            .tbp-report-header-info p { margin: 4px 0 0 0; font-size: 12px; color: #64748b; font-weight: 600; }
            .tbp-status-toggle { display: flex; align-items: center; background: #fff; padding: 8px 15px; border-radius: 25px; border: 1px solid #e2e8f0; cursor: pointer; transition: all 0.2s; user-select: none; }
            .tbp-status-toggle:hover { border-color: #3b82f6; background: #f0f9ff; }
            .tbp-status-toggle input { cursor: pointer; margin-right: 10px; width: 18px; height: 18px; }
            .tbp-status-toggle span { font-size: 13px; font-weight: 700; color: #334155; }
            .tbp-filters-grid { display: grid; grid-template-columns: 1fr auto; gap: 12px; align-items: center; }
            .tbp-filter-item { position: relative; }
            .tbp-filter-item input { width: 100%; padding: 12px 12px 12px 40px; border-radius: 10px; border: 2px solid #cbd5e1; font-size: 15px; outline: none; transition: all 0.2s; background: #fff; font-weight: 500; box-sizing: border-box; }
            .tbp-filter-item input:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(59,130,246,0.1); }
            .tbp-filter-item::before { content: '🔍'; position: absolute; left: 14px; top: 14px; font-size: 16px; opacity: 0.6; z-index: 1; }
            .tbp-btn-search { background: #0f172a; color: #fff; border: none; padding: 0 25px; border-radius: 10px; cursor: pointer; font-size: 13px; font-weight: 800; text-transform: uppercase; transition: all 0.2s; height: 50px; white-space: nowrap; }
            .tbp-btn-search:hover { background: #334155; transform: translateY(-1px); }

            /* Stats Banner */
            .tbp-stats-banner { display: flex; gap: 12px; padding: 15px 20px; background: #0f172a; flex-wrap: wrap; }
            .tbp-stat-card { flex: 1; min-width: 140px; background: rgba(255,255,255,0.07); border-radius: 10px; padding: 12px 15px; border: 1px solid rgba(255,255,255,0.1); }
            .tbp-stat-card .stat-number { font-size: 1.8rem; font-weight: 900; line-height: 1; }
            .tbp-stat-card .stat-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; opacity: 0.7; color: #fff; }
            .tbp-stat-confirmed .stat-number { color: #4ade80; }
            .tbp-stat-partial .stat-number { color: #fbbf24; }
            .tbp-stat-total .stat-number { color: #60a5fa; }

            /* Metadata Panel */
            .tbp-meta-panel { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 15px 25px; }
            .tbp-meta-panel-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; }
            .tbp-meta-panel-header h4 { margin: 0; font-size: 12px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
            .tbp-meta-panel-header span { font-size: 11px; color: #64748b; }
            .tbp-meta-groups { display: none; margin-top: 15px; display: flex; flex-wrap: wrap; gap: 15px; }
            .tbp-meta-group { flex: 1; min-width: 180px; }
            .tbp-meta-group-title { font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 8px; }
            .tbp-meta-chips { display: flex; flex-wrap: wrap; gap: 6px; }
            .tbp-meta-chip { display: inline-flex; align-items: center; gap: 5px; background: #fff; border: 1px solid #e2e8f0; border-radius: 20px; padding: 4px 10px; font-size: 11px; font-weight: 700; color: #334155; cursor: pointer; transition: all 0.2s; }
            .tbp-meta-chip:hover, .tbp-meta-chip.active { background: #0f172a; color: #fff; border-color: #0f172a; }
            .tbp-meta-chip .chip-count { background: rgba(0,0,0,0.1); border-radius: 20px; padding: 0 6px; font-size: 10px; font-weight: 900; }
            .tbp-meta-chip.active .chip-count { background: rgba(255,255,255,0.2); }
            .tbp-active-filter-bar { display: none; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 8px 12px; margin-top: 10px; font-size: 12px; color: #1e40af; font-weight: 700; align-items: center; justify-content: space-between; }
            .tbp-clear-meta-filter { cursor: pointer; color: #3b82f6; text-decoration: underline; font-size: 11px; }

            /* Pagination */
            .tbp-pagination { display: flex; align-items: center; justify-content: center; gap: 6px; padding: 20px 15px; flex-wrap: wrap; border-top: 1px solid #f1f5f9; }
            .tbp-page-btn { min-width: 36px; height: 36px; padding: 0 10px; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #334155; font-size: 13px; font-weight: 700; cursor: pointer; transition: all 0.15s; display: inline-flex; align-items: center; justify-content: center; }
            .tbp-page-btn:hover { border-color: #0f172a; background: #f8fafc; }
            .tbp-page-btn.active { background: #0f172a; color: #fff; border-color: #0f172a; }
            .tbp-page-btn:disabled { opacity: 0.4; cursor: default; }
            .tbp-page-info { font-size: 12px; color: #64748b; font-weight: 600; padding: 0 8px; }

            /* Table */
            .tbp-report-table-wrapper { overflow-x: auto; min-height: 200px; position: relative; background: #fff; }
            .tbp-report-table { width: 100%; border-collapse: collapse; table-layout: fixed; }
            .tbp-report-table th { background: #f8fafc; padding: 12px 15px; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; border-bottom: 2px solid #e2e8f0; }
            .tbp-report-table td { padding: 14px 15px; border-bottom: 1px solid #f1f5f9; font-size: 13px; color: #334155; vertical-align: middle; }
            .tbp-status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
            .status-completed { background: #dcfce7; color: #15803d; }
            .status-processing { background: #dbeafe; color: #1d4ed8; }
            .status-p-pagado { background: #fef9c3; color: #a16207; }
            .tbp-details-list { list-style: none; padding: 0; margin: 0; font-size: 11px; display: flex; flex-wrap: wrap; gap: 5px; }
            .tbp-details-list li { background: #f0f9ff; padding: 3px 8px; border-radius: 6px; border: 1px solid #bae6fd; color: #0369a1; }
            .tbp-details-list li strong { font-size: 9px; text-transform: uppercase; color: #0c4a6e; margin-right: 4px; }

            /* Loader */
            #tbp-report-loader { display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 10; align-items: center; justify-content: center; }
            .tbp-spinner { width: 40px; height: 40px; border: 4px solid #f3f3f3; border-top: 4px solid #0f172a; border-radius: 50%; animation: spin 1s linear infinite; }
            @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

            /* ===================== MOBILE CARDS ===================== */
            @media (max-width: 768px) {
                .tbp-report-header { padding: 15px; }
                .tbp-filters-grid { grid-template-columns: 1fr; }
                .tbp-btn-search { width: 100%; height: 48px; }
                .tbp-stats-banner { gap: 8px; padding: 12px 15px; }
                .tbp-stat-card { min-width: 100px; padding: 10px 12px; }
                .tbp-stat-card .stat-number { font-size: 1.5rem; }
                .tbp-meta-panel { padding: 12px 15px; }
                .tbp-meta-group { min-width: 100%; }

                /* Hide table header on mobile */
                .tbp-report-table thead { display: none; }

                /* Each row becomes a card */
                .tbp-report-table, .tbp-report-table tbody, .tbp-report-table tr, .tbp-report-table td {
                    display: block; width: 100%;
                }
                .tbp-report-table tr {
                    margin: 10px 15px;
                    width: calc(100% - 30px);
                    border-radius: 12px;
                    border: 1px solid #e2e8f0;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
                    overflow: hidden;
                    background: #fff;
                }
                .tbp-report-table td {
                    padding: 10px 15px;
                    border-bottom: 1px solid #f1f5f9;
                    display: flex;
                    align-items: flex-start;
                    gap: 10px;
                    font-size: 13px;
                }
                .tbp-report-table td:last-child { border-bottom: none; }
                .tbp-report-table td::before {
                    content: attr(data-label);
                    font-size: 9px;
                    font-weight: 800;
                    text-transform: uppercase;
                    color: #94a3b8;
                    letter-spacing: 0.5px;
                    min-width: 65px;
                    padding-top: 2px;
                    flex-shrink: 0;
                }
                /* First td (Pedido) = card header style */
                .tbp-report-table td:first-child {
                    background: #f8fafc;
                    font-size: 15px;
                    font-weight: 900;
                    border-bottom: 2px solid #e2e8f0;
                }
                .tbp-report-table td:first-child::before { content: '#'; min-width: auto; }
            }
        </style>

        <!-- Stats Banner -->
        <div class="tbp-stats-banner" id="tbp-stats-banner">
            <div class="tbp-stat-card tbp-stat-confirmed">
                <div class="stat-number" id="tbp-stat-confirmed">...</div>
                <div class="stat-label">✅ Confirmados (Completado + Pagado con Tarjeta)</div>
            </div>
            <div class="tbp-stat-card tbp-stat-partial">
                <div class="stat-number" id="tbp-stat-partial">...</div>
                <div class="stat-label">⏳ Pendientes (Pagados Parcialmente)</div>
            </div>
            <div class="tbp-stat-card tbp-stat-total">
                <div class="stat-number" id="tbp-stat-total">...</div>
                <div class="stat-label">📋 Total Asistentes en el Evento</div>
            </div>
        </div>

        <!-- Metadata Filter Panel: visible by default, chips collapsed -->
        <div class="tbp-meta-panel" id="tbp-meta-panel" style="display:none;">
            <div class="tbp-meta-panel-header" id="tbp-meta-panel-toggle">
                <h4>🔎 Filtrar por Datos del Asistente</h4>
                <span id="tbp-meta-toggle-icon">▼ Ver filtros</span>
            </div>
            <div id="tbp-meta-groups" style="display:none; margin-top:15px;"></div>
            <div class="tbp-active-filter-bar" id="tbp-active-filter-bar">
                <span id="tbp-active-filter-text"></span>
                <span class="tbp-clear-meta-filter" id="tbp-clear-meta-filter">✕ Quitar filtro</span>
            </div>
        </div>

        <div class="tbp-report-header">
            <div class="tbp-report-header-info">
                <div>
                    <h3><?php _e( 'Reporte de Asistentes', 'tbp-actividades' ); ?></h3>
                    <p id="tbp-report-count-label"><?php _e( 'Cargando...', 'tbp-actividades' ); ?></p>
                </div>
                <label class="tbp-status-toggle">
                    <input type="checkbox" id="tbp-toggle-partial" value="1">
                    <span><?php _e( 'Ver solo Pagados Parcialmente', 'tbp-actividades' ); ?></span>
                </label>
            </div>
            <div class="tbp-filters-grid">
                <div class="tbp-filter-item">
                    <input type="text" id="tbp-main-search" placeholder="<?php _e( 'Nombre, Apellido o # Pedido...', 'tbp-actividades' ); ?>">
                </div>
                <button class="tbp-btn-search" id="tbp-btn-trigger-search"><?php _e( 'BUSCAR AHORA', 'tbp-actividades' ); ?></button>
            </div>
        </div>

        <div class="tbp-report-table-wrapper">
            <div id="tbp-report-loader"><div class="tbp-spinner"></div></div>
            <table class="tbp-report-table">
                <thead>
                    <tr>
                        <th style="width: 10%;"><?php _e( 'Pedido', 'tbp-actividades' ); ?></th>
                        <th style="width: 15%;"><?php _e( 'Estado', 'tbp-actividades' ); ?></th>
                        <th style="width: 25%;"><?php _e( 'Titular', 'tbp-actividades' ); ?></th>
                        <th style="width: 20%;"><?php _e( 'Producto', 'tbp-actividades' ); ?></th>
                        <th style="width: 30%;"><?php _e( 'Detalles Asistente', 'tbp-actividades' ); ?></th>
                    </tr>
                </thead>
                <tbody id="tbp-report-results"></tbody>
            </table>
            <div class="tbp-pagination" id="tbp-pagination" style="display:none;"></div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        var context        = $('#tbp-report-context');
        var event_id       = context.data('event-id');
        var snapshotUrl    = context.data('snapshot-url');
        var nonce          = '<?php echo wp_create_nonce('tbp_report_nonce'); ?>';
        
        var fullData       = null; // All rows from JSON
        var filteredData   = [];   // Rows after search/filters
        
        var activeSearch   = '';
        var activeMetaKey  = '';
        var activeMetaVal  = '';
        var showPartialOnly = false;
        
        var currentPage    = 1;
        var perPage        = 25;
        var metaPanelOpen  = false;

        // ── LOAD ENGINE ──────────────────────────────
        if (snapshotUrl) {
            console.log('TBP: Loading from Static Snapshot...');
            $.getJSON(snapshotUrl, function(data) {
                fullData = data;
                initStaticMode();
            }).fail(function() {
                console.error('TBP: Snapshot failed, falling back to AJAX');
                initAjaxMode();
            });
        } else {
            console.log('TBP: Snapshot not found, using AJAX Mode (High CPU)');
            initAjaxMode();
        }

        // ── STATIC MODE (CLIENT-SIDE) ────────────────
        function initStaticMode() {
            // Fill stats
            $('#tbp-stat-confirmed').text(fullData.stats.confirmed);
            $('#tbp-stat-partial').text(fullData.stats.partial);
            $('#tbp-stat-total').text(fullData.stats.total);

            // Build filters
            var filterHtml = '';
            $.each(fullData.filters, function(label, values) {
                filterHtml += '<div class="tbp-meta-group">';
                filterHtml += '<div class="tbp-meta-group-title">' + label + '</div>';
                filterHtml += '<div class="tbp-meta-chips">';
                $.each(values, function(val, count) {
                    filterHtml += '<span class="tbp-meta-chip" data-field="' + escHtml(label) + '" data-value="' + escHtml(val) + '">';
                    filterHtml += escHtml(val) + ' <span class="chip-count">' + count + '</span></span>';
                });
                filterHtml += '</div></div>';
            });
            $('#tbp-meta-groups').html(filterHtml);
            if (Object.keys(fullData.filters).length > 0) $('#tbp-meta-panel').show();

            applyFilters(); // Initial render
        }

        function applyFilters() {
            if (!fullData) return;
            
            showPartialOnly = $('#tbp-toggle-partial').is(':checked');
            activeSearch = $('#tbp-main-search').val().trim().toLowerCase();

            filteredData = fullData.rows.filter(function(row) {
                // 1. Filtro por Estado (Pendientes vs Confirmados)
                if (showPartialOnly && row.status !== 'p-pagado') return false;
                if (!showPartialOnly && row.status === 'p-pagado') return false;

                // 2. Filtro de Búsqueda Principal (Caja de texto)
                if (activeSearch) {
                    var match = false;
                    var searchTarget = (
                        row.order_id.toString() + ' ' + 
                        row.titular + ' ' + 
                        row.producto + ' ' + 
                        (row.details ? row.details.join(' ') : '')
                    ).toLowerCase();

                    // Limpiar acentos para una búsqueda más amigable
                    searchTarget = searchTarget.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
                    var cleanSearch = activeSearch.normalize("NFD").replace(/[\u0300-\u036f]/g, "");

                    if (searchTarget.indexOf(cleanSearch) !== -1) {
                        match = true;
                    }
                    
                    if (!match) return false;
                }

                // 3. Filtro por Metadato (Chips)
                if (activeMetaVal) {
                    var metaMatch = false;
                    var cleanMetaVal = activeMetaVal.toLowerCase();
                    $.each(row.details, function(i, d) {
                        if (d.toLowerCase().indexOf(cleanMetaVal) !== -1) {
                            metaMatch = true;
                            return false; // break
                        }
                    });
                    if (!metaMatch) return false;
                }

                return true;
            });

            currentPage = 1;
            renderResults();
        }

        function renderResults() {
            var start = (currentPage - 1) * perPage;
            var end   = start + perPage;
            var slice = filteredData.slice(start, end);
            
            var html = '';
            if (slice.length === 0) {
                html = '<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;">No se encontraron resultados.</td></tr>';
            } else {
                $.each(slice, function(i, row) {
                    html += '<tr>';
                    html += '<td data-label="Pedido" style="font-weight:800;color:#0f172a;">#' + row.order_id + '</td>';
                    html += '<td data-label="Estado"><span class="tbp-status-badge status-' + row.status + '">' + row.status_label + '</span></td>';
                    html += '<td data-label="Titular">' + escHtml(row.titular) + '</td>';
                    html += '<td data-label="Producto" style="color:#334155;font-size:12px;line-height:1.6;">' + row.producto + '</td>';
                    html += '<td data-label="Detalles">';
                    if (row.details && row.details.length > 0) {
                        html += '<ul class="tbp-details-list">';
                        $.each(row.details, function(j, d) { html += '<li>' + d + '</li>'; });
                        html += '</ul>';
                    } else {
                        html += '<span style="color:#cbd5e1;font-size:10px;">Sin detalles</span>';
                    }
                    html += '</td></tr>';
                });
            }

            $('#tbp-report-results').html(html);
            $('#tbp-report-count-label').text('Se encontraron ' + filteredData.length + ' registro(s). ' + (snapshotUrl ? '(Snapshot: ' + fullData.last_updated + ')' : ''));
            
            renderStaticPagination();
            $('#tbp-report-loader').hide();
        }

        function renderStaticPagination() {
            var totalPages = Math.ceil(filteredData.length / perPage);
            var $pg = $('#tbp-pagination');
            if (totalPages <= 1) { $pg.hide(); return; }

            var from = ((currentPage - 1) * perPage) + 1;
            var to   = Math.min(currentPage * perPage, filteredData.length);
            var html = '<span class="tbp-page-info">' + from + '–' + to + ' de ' + filteredData.length + '</span>';
            
            html += '<button class="tbp-page-btn" id="tbp-static-prev"' + (currentPage <= 1 ? ' disabled' : '') + '>‹ Anterior</button>';
            
            var start = Math.max(1, currentPage - 2), end = Math.min(totalPages, currentPage + 2);
            for (var p = start; p <= end; p++) {
                html += '<button class="tbp-page-btn' + (p === currentPage ? ' active' : '') + '" data-page="' + p + '">' + p + '</button>';
            }
            
            html += '<button class="tbp-page-btn" id="tbp-static-next"' + (currentPage >= totalPages ? ' disabled' : '') + '>Siguiente ›</button>';
            $pg.html(html).show();
        }

        // ── AJAX MODE (FALLBACK) ──────────────────────
        function initAjaxMode() {
            // Keep previous AJAX logic here...
            fetchStatsAjax();
            fetchMetaFiltersAjax();
            fetchResultsAjax('');
        }

        function fetchStatsAjax() {
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'tbp_actividades_get_event_stats', event_id: event_id, nonce: nonce }, function(r) {
                if (r.success) { $('#tbp-stat-confirmed').text(r.data.confirmed); $('#tbp-stat-partial').text(r.data.partial); $('#tbp-stat-total').text(r.data.total); }
            });
        }
        function fetchMetaFiltersAjax() {
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', { action: 'tbp_actividades_get_meta_aggregates', event_id: event_id, nonce: nonce }, function(r) {
                if (!r.success || !r.data || Object.keys(r.data).length === 0) return;
                var html = '';
                $.each(r.data, function(f, vals) {
                    html += '<div class="tbp-meta-group"><div class="tbp-meta-group-title">' + f + '</div><div class="tbp-meta-chips">';
                    $.each(vals, function(v, c) { html += '<span class="tbp-meta-chip" data-field="' + f + '" data-value="' + v + '">' + v + ' <span class="chip-count">' + c + '</span></span>'; });
                    html += '</div></div>';
                });
                $('#tbp-meta-groups').html(html); $('#tbp-meta-panel').show();
            });
        }
        function fetchResultsAjax(q) {
            $('#tbp-report-loader').css('display', 'flex');
            $.post('<?php echo admin_url('admin-ajax.php'); ?>', {
                action: 'tbp_actividades_get_report_data', event_id: event_id, search: q,
                show_partial: $('#tbp-toggle-partial').is(':checked') ? 1 : 0, meta_filter_val: activeMetaVal, page: currentPage, nonce: nonce
            }, function(r) {
                $('#tbp-report-loader').hide();
                if (r.success) {
                    $('#tbp-report-results').html(r.data.html); $('#tbp-report-count-label').text(r.data.label);
                    // Minimal pagination for AJAX
                    var $pg = $('#tbp-pagination');
                    $pg.html('<button class="tbp-page-btn" id="tbp-ajax-prev">Anterior</button><button class="tbp-page-btn" id="tbp-ajax-next">Siguiente</button>').show();
                }
            });
        }

        // ── EVENTS ────────────────────────────────────
        $('#tbp-btn-trigger-search').on('click', function() {
            if (fullData) applyFilters(); else fetchResultsAjax($('#tbp-main-search').val());
        });
        $('#tbp-main-search').on('keypress', function(e) {
            if(e.which === 13) { if (fullData) applyFilters(); else fetchResultsAjax($(this).val()); }
        });
        $('#tbp-toggle-partial').on('change', function() {
            if (fullData) applyFilters(); else fetchResultsAjax($('#tbp-main-search').val());
        });

        $(document).on('click', '.tbp-page-btn[data-page]', function() {
            currentPage = parseInt($(this).data('page'));
            if (fullData) renderResults(); else fetchResultsAjax($('#tbp-main-search').val());
            $('html,body').animate({ scrollTop: $('#tbp-report-context').offset().top - 20 }, 300);
        });

        $(document).on('click', '#tbp-static-prev', function() { if (currentPage > 1) { currentPage--; renderResults(); } });
        $(document).on('click', '#tbp-static-next', function() { currentPage++; renderResults(); });

        $('#tbp-meta-panel-toggle').on('click', function() {
            metaPanelOpen = !metaPanelOpen;
            if (metaPanelOpen) { $('#tbp-meta-groups').slideDown(200); $('#tbp-meta-toggle-icon').text('▲ Ocultar filtros'); }
            else { $('#tbp-meta-groups').slideUp(200); $('#tbp-meta-toggle-icon').text('▼ Ver filtros'); }
        });

        $(document).on('click', '.tbp-meta-chip', function() {
            var $chip = $(this);
            if ($chip.hasClass('active')) { 
                activeMetaKey = ''; activeMetaVal = ''; $('.tbp-meta-chip').removeClass('active'); $('#tbp-active-filter-bar').hide();
            } else {
                $('.tbp-meta-chip').removeClass('active'); $chip.addClass('active');
                activeMetaKey = $chip.data('field'); activeMetaVal = $chip.data('value');
                $('#tbp-active-filter-text').text('🔎 Filtrando por ' + activeMetaKey + ': ' + activeMetaVal);
                $('#tbp-active-filter-bar').css('display', 'flex');
            }
            if (fullData) applyFilters(); else fetchResultsAjax($('#tbp-main-search').val());
        });

        $('#tbp-clear-meta-filter').on('click', function() {
            activeMetaKey = ''; activeMetaVal = ''; $('.tbp-meta-chip').removeClass('active'); $('#tbp-active-filter-bar').hide();
            if (fullData) applyFilters(); else fetchResultsAjax($('#tbp-main-search').val());
        });

        function escHtml(str) { return $('<div>').text(str).html(); }
    });
    </script>
    <?php
    return ob_get_clean();
}

/**
 * AJAX Handler v8.8.0 - Option C+D: SQL pagination + cursor-based meta filter
 *
 * OPTION C (no meta filter):
 *   - SQL COUNT to get total instantly
 *   - SQL LIMIT/OFFSET to get only page IDs
 *   - wc_get_order() called only for 25 orders per page
 *
 * OPTION D (with meta filter):
 *   - Cursor-based: JS passes raw_offset (position in candidate array)
 *   - Scans max 300 orders per request to find 25 matches
 *   - Returns next_offset so JS can continue to next page
 *   - No total count needed (avoids full scan)
 */
add_action( 'wp_ajax_tbp_actividades_get_report_data', 'tbp_actividades_get_report_data_ajax' );
add_action( 'wp_ajax_nopriv_tbp_actividades_get_report_data', 'tbp_actividades_get_report_data_ajax' );

function tbp_actividades_get_report_data_ajax() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'tbp_report_nonce' ) ) {
        wp_send_json_error('Nonce inválido.');
    }

    global $wpdb;
    $event_id        = intval( $_POST['event_id'] );
    $search          = sanitize_text_field( $_POST['search'] ?? '' );
    $show_partial    = intval( $_POST['show_partial'] ?? 0 );
    $meta_filter_val = sanitize_text_field( $_POST['meta_filter_val'] ?? '' );
    $page            = max(1, intval( $_POST['page'] ?? 1 ));
    $per_page        = 25;
    $raw_offset      = max(0, intval( $_POST['raw_offset'] ?? 0 ));

    if ( ! $event_id ) wp_send_json_error('event_id requerido.');

    // --- PARCHE DE OXÍGENO (CACHE 10 MIN) ---
    // Generamos una clave única según los parámetros de búsqueda
    $cache_key = 'tbp_report_cache_' . md5( $event_id . $search . $show_partial . $meta_filter_val . $page . $raw_offset );
    $cached_response = get_transient( $cache_key );

    if ( $cached_response !== false ) {
        // Añadimos una cabecera para saber que viene de cache (opcional)
        $cached_response['from_cache'] = true;
        wp_send_json_success( $cached_response );
    }
    // ----------------------------------------

    // ── Status filter ─────────────────────────────────────
    $valid_statuses = $show_partial ? [ 'wc-p-pagado' ] : [ 'wc-completed', 'wc-processing' ];
    $statuses_str   = "'" . implode("','", $valid_statuses) . "'";

    // ── PASO 1: Get event order IDs (event isolation) ─────
    $event_order_ids = tbp_report_get_event_order_ids( $event_id );

    if ( empty($event_order_ids) ) {
        $html = '<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">No se encontraron pedidos para este evento (ID: ' . $event_id . '). Verifica que el evento tenga tickets configurados.</td></tr>';
        wp_send_json_success([ 'html' => $html, 'label' => 'Sin pedidos en este evento.', 'total_count' => 0, 'total_pages' => 0, 'current_page' => 1, 'per_page' => $per_page ]);
        return;
    }

    $ids_str = implode(',', array_map('intval', $event_order_ids));

    // ── PASO 2: Apply search filter via SQL ───────────────
    if ( ! empty($search) ) {
        $clean_id = str_replace('#', '', $search);
        if ( is_numeric($clean_id) ) {
            $candidate_ids = in_array(intval($clean_id), $event_order_ids) ? [ intval($clean_id) ] : [];
        } else {
            $t = '%' . $wpdb->esc_like($search) . '%';
            $by_billing = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT pm.post_id FROM {$wpdb->postmeta} pm
                 WHERE pm.post_id IN ($ids_str)
                 AND pm.meta_key IN ('_billing_first_name','_billing_last_name','_billing_email')
                 AND pm.meta_value LIKE %s", $t
            ));
            $by_attendee = $wpdb->get_col( $wpdb->prepare(
                "SELECT DISTINCT pm_order.meta_value FROM {$wpdb->postmeta} pm_name
                 INNER JOIN {$wpdb->postmeta} pm_order ON pm_name.post_id = pm_order.post_id
                 WHERE pm_name.meta_value LIKE %s
                 AND pm_order.meta_key IN ('_tribe_wooticket_order','_tribe_tickets_order')
                 AND pm_order.meta_value IN ($ids_str)", $t
            ));
            $candidate_ids = array_values(array_unique(array_map('intval', array_merge($by_billing, $by_attendee))));
            $candidate_ids = array_values(array_intersect($candidate_ids, $event_order_ids));
        }
    } else {
        $candidate_ids = $event_order_ids;
    }

    if ( empty($candidate_ids) ) {
        $msg  = empty($search) ? 'No hay pedidos en este evento.' : 'No se encontró "' . esc_html($search) . '" en este evento.';
        $html = '<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">' . $msg . '</td></tr>';
        wp_send_json_success([ 'html' => $html, 'label' => $msg, 'total_count' => 0, 'total_pages' => 0, 'current_page' => 1, 'per_page' => $per_page ]);
        return;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // OPTION C: No meta filter — SQL COUNT + LIMIT/OFFSET, load only 25 objects
    // ──────────────────────────────────────────────────────────────────────────
    if ( empty($meta_filter_val) ) {

        $cand_str = implode(',', array_map('intval', $candidate_ids));

        // Fast SQL COUNT (no WC object loading)
        $total_count = intval( $wpdb->get_var(
            "SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             WHERE p.ID IN ($cand_str) AND p.post_status IN ($statuses_str)"
        ));

        $total_pages = $total_count > 0 ? max(1, ceil($total_count / $per_page)) : 1;
        $page        = min($page, $total_pages);
        $offset      = ($page - 1) * $per_page;

        // Get only the IDs for this page
        $page_ids = $wpdb->get_col(
            "SELECT p.ID FROM {$wpdb->posts} p
             WHERE p.ID IN ($cand_str) AND p.post_status IN ($statuses_str)
             ORDER BY p.ID DESC
             LIMIT $per_page OFFSET $offset"
        );

        $html = '';
        foreach ( $page_ids as $order_id ) {
            $order = wc_get_order( intval($order_id) );
            if ( ! $order ) continue;
            $html .= tbp_build_order_row( $order, $order_id );
        }

        if ( empty($html) ) {
            $html = '<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">No hay pedidos completados en este evento aún.</td></tr>';
        }

        $label = 'Se encontraron ' . number_format($total_count) . ' registro(s).';
        $response = [
            'html'         => $html,
            'label'        => $label,
            'total_count'  => $total_count,
            'total_pages'  => $total_pages,
            'current_page' => $page,
            'per_page'     => $per_page,
            'mode'         => 'paginated',
        ];

        set_transient( $cache_key, $response, 10 * MINUTE_IN_SECONDS );
        wp_send_json_success($response);
        return;
    }

    // ──────────────────────────────────────────────────────────────────────────
    // OPTION D: Meta filter active — cursor-based scan, max 300 orders/request
    // ──────────────────────────────────────────────────────────────────────────

    // Filter candidate_ids by status using SQL first (cheap)
    $cand_str       = implode(',', array_map('intval', $candidate_ids));
    $status_ids     = $wpdb->get_col(
        "SELECT p.ID FROM {$wpdb->posts} p
         WHERE p.ID IN ($cand_str) AND p.post_status IN ($statuses_str)
         ORDER BY p.ID DESC"
    );
    $status_ids = array_map('intval', $status_ids);

    $scan_window  = 300;  // max orders to scan per request
    $scan_slice   = array_slice($status_ids, $raw_offset, $scan_window);
    $found_rows   = [];
    $last_scanned = $raw_offset;

    foreach ( $scan_slice as $i => $order_id ) {
        $last_scanned = $raw_offset + $i + 1;
        $order = wc_get_order( $order_id );
        if ( ! $order ) continue;

        $attendees_meta = tbp_actividades_get_order_attendees_meta( $order_id, $order );
        if ( empty($attendees_meta) ) continue;

        $seen_key = [];
        foreach ( $attendees_meta as $source_id => $meta_groups ) {
            if ( ! empty($meta_groups) && ! is_array(current($meta_groups)) ) {
                $meta_groups = [ 0 => $meta_groups ];
            }
            foreach ( $meta_groups as $guest_data ) {
                if ( ! is_array($guest_data) ) continue;
                $row_key = md5($order_id . serialize($guest_data));
                if ( isset($seen_key[$row_key]) ) continue;
                $seen_key[$row_key] = true;

                $details = tbp_report_format_details($guest_data);

                // Apply meta filter
                $match = false;
                foreach ( $details as $d ) {
                    if ( stripos( strip_tags($d), $meta_filter_val ) !== false ) {
                        $match = true; break;
                    }
                }
                if ( ! $match ) continue;

                $found_rows[] = [
                    'order_id' => $order_id,
                    'order'    => $order,
                    'details'  => $details,
                ];

                if ( count($found_rows) >= $per_page ) break 2; // enough for this page
            }
        }

        if ( count($found_rows) >= $per_page ) break;
    }

    // Determine if there are more results after this window
    $has_more   = $last_scanned < count($status_ids);
    $next_offset = $has_more ? $last_scanned : -1;

    $html = '';
    foreach ( $found_rows as $row ) {
        $html .= tbp_build_order_row( $row['order'], $row['order_id'], $row['details'] );
    }

    if ( empty($html) ) {
        $html = '<tr><td colspan="5" style="padding:40px;text-align:center;color:#94a3b8;font-size:13px;">No se encontraron resultados con "' . esc_html($meta_filter_val) . '".</td></tr>';
    }

    $found = count($found_rows);
    $label = 'Mostrando ' . $found . ' resultado(s) con "' . esc_html($meta_filter_val) . '"' . ($has_more ? ' — hay más resultados' : ' — fin de resultados') . '.';

    $response = [
        'html'        => $html,
        'label'       => $label,
        'mode'        => 'cursor',
        'raw_offset'  => $raw_offset,
        'next_offset' => $next_offset,
        'has_more'    => $has_more,
        'found'       => $found,
    ];

    set_transient( $cache_key, $response, 10 * MINUTE_IN_SECONDS );
    wp_send_json_success($response);
}

/**
 * Helper: build a single table row from a WC order.
 * Accepts optional pre-fetched $details array (from meta filter scan).
 */
function tbp_build_order_row( $order, $order_id, $details = null ) {
    $html = '';

    $status_label  = wc_get_order_status_name( $order->get_status() );
    $customer_name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

    $prod_names = [];
    $total_qty  = 0;
    foreach ( $order->get_items() as $item ) {
        $qty         = intval( $item->get_quantity() );
        $total_qty  += $qty;
        $prod_names[] = $item->get_name() . ' <em style="color:#94a3b8;">(x' . $qty . ')</em>';
    }
    $prod_str = implode(', ', $prod_names);
    if ( $total_qty > 0 ) {
        $prod_str .= ' &nbsp;<strong style="background:#e2e8f0;color:#334155;padding:1px 7px;border-radius:20px;font-size:10px;white-space:nowrap;">Total: ' . $total_qty . '</strong>';
    }

    if ( $details === null ) {
        // Load attendees meta only if not pre-fetched
        $attendees_meta = tbp_actividades_get_order_attendees_meta( $order_id, $order );
        if ( empty($attendees_meta) ) {
            return tbp_report_render_row( $order_id, $order->get_status(), $status_label, $customer_name, $prod_str, [] );
        }
        $seen_key = [];
        foreach ( $attendees_meta as $source_id => $meta_groups ) {
            if ( ! empty($meta_groups) && ! is_array(current($meta_groups)) ) {
                $meta_groups = [ 0 => $meta_groups ];
            }
            foreach ( $meta_groups as $guest_data ) {
                if ( ! is_array($guest_data) ) continue;
                $row_key = md5($order_id . serialize($guest_data));
                if ( isset($seen_key[$row_key]) ) continue;
                $seen_key[$row_key] = true;
                $d = tbp_report_format_details($guest_data);
                $html .= tbp_report_render_row( $order_id, $order->get_status(), $status_label, $customer_name, $prod_str, $d );
            }
        }
    } else {
        $html = tbp_report_render_row( $order_id, $order->get_status(), $status_label, $customer_name, $prod_str, $details );
    }

    return $html;
}

/**
 * Obtiene todos los Order IDs vinculados a un evento (aislamiento central).
 * CLAVE REAL EN ESTE SERVIDOR: _tribe_wooticket_for_event (confirmado en admin-reports.php)
 */
function tbp_report_get_event_order_ids( $event_id ) {
    $cache_key = 'tbp_event_order_ids_' . $event_id;
    $cached = get_transient( $cache_key );
    if ( $cached !== false ) {
        return $cached;
    }

    global $wpdb;
    $order_ids = [];

    // Método 1: Tabla tec_tickets_attendees (ET+ moderno)
    $tec_table = $wpdb->prefix . 'tec_tickets_attendees';
    if ( $wpdb->get_var("SHOW TABLES LIKE '$tec_table'") === $tec_table ) {
        $rows = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT order_id FROM $tec_table WHERE event_id = %d AND order_id > 0",
            $event_id
        ));
        $order_ids = array_merge($order_ids, array_map('intval', $rows));
    }

    // Método 2: Clave CONFIRMADA en este servidor: _tribe_wooticket_for_event
    if ( empty($order_ids) ) {
        // Obtener IDs de productos/tickets vinculados al evento
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tribe_wooticket_for_event' AND meta_value = %d",
            $event_id
        ));

        if ( ! empty($product_ids) ) {
            $p_placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $rows = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT woi.order_id
                 FROM {$wpdb->prefix}woocommerce_order_items woi
                 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim ON woi.order_item_id = woim.order_item_id
                 WHERE woim.meta_key = '_product_id'
                 AND woim.meta_value IN ($p_placeholders)",
                ...$product_ids
            ));
            $order_ids = array_merge($order_ids, array_map('intval', $rows));
        }
    }

    // Método 3: Fallback con _tribe_tickets_event (ET+ legacy)
    if ( empty($order_ids) ) {
        $product_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_tribe_tickets_event' AND meta_value = %d",
            $event_id
        ));

        if ( ! empty($product_ids) ) {
            $p_placeholders = implode(',', array_fill(0, count($product_ids), '%d'));
            $rows = $wpdb->get_col($wpdb->prepare(
                "SELECT DISTINCT woi.order_id
                 FROM {$wpdb->prefix}woocommerce_order_items woi
                 INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta woim ON woi.order_item_id = woim.order_item_id
                 WHERE woim.meta_key = '_product_id'
                 AND woim.meta_value IN ($p_placeholders)",
                ...$product_ids
            ));
            $order_ids = array_merge($order_ids, array_map('intval', $rows));
        }
    }

    // Método 4: Attendee posts con _tribe_wooticket_for_event o _tribe_tickets_event
    if ( empty($order_ids) ) {
        $att_order_ids = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm_order.meta_value
            FROM {$wpdb->postmeta} pm_event
            INNER JOIN {$wpdb->postmeta} pm_order ON pm_event.post_id = pm_order.post_id
            WHERE pm_event.meta_key IN ('_tribe_wooticket_for_event', '_tribe_tickets_event')
            AND pm_event.meta_value = %d
            AND pm_order.meta_key IN ('_tribe_wooticket_order', '_tribe_tickets_order')
            AND pm_order.meta_value > 0
        ", $event_id));
        $order_ids = array_merge($order_ids, array_map('intval', $att_order_ids));
    }

    $final_ids = array_values(array_unique(array_filter($order_ids)));
    set_transient( $cache_key, $final_ids, 1 * HOUR_IN_SECONDS );

    return $final_ids;
}

/**
 * Formatea los detalles del asistente para renderizar.
 */
function tbp_report_format_details( $guest_data ) {
    $details = [];
    $label_map = [
        '0' => 'Grupo',
        '1' => 'Talla de Camisa',
        'group' => 'Grupo',
        'grupo' => 'Grupo',
        'shirt_size' => 'Talla de Camisa',
        'talla' => 'Talla de Camisa',
    ];

    foreach ( (array)$guest_data as $k => $v ) {
        $raw_label = is_array($v) ? ( $v['label'] ?? $k ) : $k;
        $val       = is_array($v) ? ( $v['value'] ?? '' ) : $v;

        if ( is_array($val) ) $val = implode(', ', $val);
        $val = trim( strval($val) );

        if ( empty($val) ) continue;
        if ( strpos($k, '_') === 0 ) continue;  // skip internal keys

        $key_lower = strtolower(str_replace(['-', '_', ' '], '', strval($raw_label)));
        $label     = $label_map[$key_lower] ?? $label_map[strval($raw_label)] ?? ucwords(str_replace(['-', '_'], ' ', strtolower(strval($raw_label))));

        $details[] = '<strong>' . esc_html($label) . ':</strong> ' . esc_html($val);
    }
    return $details;
}

/**
 * Genera el HTML de una fila de la tabla.
 */
function tbp_report_render_row( $order_id, $status_slug, $status_label, $customer_name, $prods, $details ) {
    $html  = '<tr>';
    $html .= '<td data-label="Pedido" style="font-weight:800;color:#0f172a;">#' . $order_id . '</td>';
    $html .= '<td data-label="Estado"><span class="tbp-status-badge status-' . esc_attr($status_slug) . '">' . esc_html($status_label) . '</span></td>';
    $html .= '<td data-label="Titular">' . esc_html($customer_name) . '</td>';
    $html .= '<td data-label="Producto" style="color:#334155;font-size:12px;line-height:1.6;">' . wp_kses_post($prods) . '</td>';
    $html .= '<td data-label="Detalles">';
    if ( ! empty($details) ) {
        $html .= '<ul class="tbp-details-list">';
        foreach ( $details as $d ) $html .= '<li>' . $d . '</li>';
        $html .= '</ul>';
    } else {
        $html .= '<span style="color:#cbd5e1;font-size:10px;">Sin detalles</span>';
    }
    $html .= '</td></tr>';
    return $html;
}

/**
 * AJAX Stats Handler (v8.4.0)
 * Devuelve conteos totales de asistentes del evento por status.
 */
add_action( 'wp_ajax_tbp_actividades_get_event_stats', 'tbp_actividades_get_event_stats_ajax' );
add_action( 'wp_ajax_nopriv_tbp_actividades_get_event_stats', 'tbp_actividades_get_event_stats_ajax' );

function tbp_actividades_get_event_stats_ajax() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'tbp_report_nonce' ) ) {
        wp_send_json_error('Nonce inválido.');
    }

    $event_id = intval( $_POST['event_id'] );
    if ( ! $event_id ) wp_send_json_error('event_id requerido.');

    // Obtener todos los order_ids del evento
    $all_order_ids = tbp_report_get_event_order_ids( $event_id );

    if ( empty($all_order_ids) ) {
        wp_send_json_success([ 'confirmed' => 0, 'partial' => 0, 'total' => 0 ]);
        return;
    }

    $confirmed = 0;
    $partial   = 0;

    foreach ( $all_order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) continue;
        $status = 'wc-' . $order->get_status();
        if ( in_array($status, ['wc-completed', 'wc-processing']) ) {
            $confirmed++;
        } elseif ( $status === 'wc-p-pagado' ) {
            $partial++;
        }
    }

    wp_send_json_success([
        'confirmed' => $confirmed,
        'partial'   => $partial,
        'total'     => $confirmed + $partial,
    ]);
}

/**
 * AJAX Meta Aggregates Handler (v8.6.0)
 * Returns all unique field/value pairs from attendee metadata for this event,
 * with counts, grouped by field label. Only counts confirmed orders.
 */
add_action( 'wp_ajax_tbp_actividades_get_meta_aggregates', 'tbp_actividades_get_meta_aggregates_ajax' );
add_action( 'wp_ajax_nopriv_tbp_actividades_get_meta_aggregates', 'tbp_actividades_get_meta_aggregates_ajax' );

function tbp_actividades_get_meta_aggregates_ajax() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'tbp_report_nonce' ) ) {
        wp_send_json_error('Nonce inválido.');
    }

    $event_id = intval( $_POST['event_id'] );
    if ( ! $event_id ) wp_send_json_error('event_id requerido.');

    $all_order_ids = tbp_report_get_event_order_ids( $event_id );
    if ( empty($all_order_ids) ) wp_send_json_success([]);

    $aggregates = []; // [ field_label => [ value => count ] ]

    foreach ( $all_order_ids as $order_id ) {
        $order = wc_get_order( $order_id );
        if ( ! $order ) continue;

        // Only aggregate confirmed orders
        $status = 'wc-' . $order->get_status();
        if ( ! in_array($status, ['wc-completed', 'wc-processing']) ) continue;

        $attendees_meta = tbp_actividades_get_order_attendees_meta( $order_id, $order );
        if ( empty($attendees_meta) ) continue;

        foreach ( $attendees_meta as $source_id => $meta_groups ) {
            if ( ! empty($meta_groups) && ! is_array(current($meta_groups)) ) {
                $meta_groups = [ 0 => $meta_groups ];
            }
            foreach ( $meta_groups as $guest_data ) {
                if ( ! is_array($guest_data) ) continue;
                $details = tbp_report_format_details($guest_data);
                foreach ( $details as $d ) {
                    $clean = strip_tags($d);
                    $parts = explode(':', $clean, 2);
                    if ( count($parts) < 2 ) continue;
                    $field = trim($parts[0]);
                    $value = trim($parts[1]);
                    if ( empty($field) || empty($value) ) continue;
                    if ( ! isset($aggregates[$field]) ) $aggregates[$field] = [];
                    if ( ! isset($aggregates[$field][$value]) ) $aggregates[$field][$value] = 0;
                    $aggregates[$field][$value]++;
                }
            }
        }
    }

    // Sort each field's values by count descending
    foreach ( $aggregates as $field => &$values ) {
        arsort($values);
    }

    wp_send_json_success( $aggregates );
}
