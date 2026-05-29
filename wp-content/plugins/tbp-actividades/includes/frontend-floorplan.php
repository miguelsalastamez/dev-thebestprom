<?php
/**
 * Frontend Floor Plan for TBP Actividades
 * Implementa el shortcode [tbp_plano_evento]
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_shortcode( 'tbp_plano_evento', 'tbp_actividades_floorplan_shortcode' );

function tbp_actividades_floorplan_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'event_id' => get_the_ID(),
    ), $atts, 'tbp_plano_evento' );

    $event_id = intval( $atts['event_id'] );

    // Usar el mismo Snapshot JSON que el reporte
    $upload_dir   = wp_upload_dir();
    $snapshot_rel = '/tbp-snapshots/event-' . $event_id . '.json';
    $snapshot_path = $upload_dir['basedir'] . $snapshot_rel;
    $snapshot_url  = file_exists($snapshot_path) ? ($upload_dir['baseurl'] . $snapshot_rel) : '';

    if ( ! $snapshot_url ) {
        return '<p style="text-align:center; padding:20px; color:#666;">El plano de este evento aún no ha sido generado.</p>';
    }

    ob_start();
    ?>
    <div class="tbp-floorplan-container" id="tbp-floorplan-context" data-snapshot-url="<?php echo esc_url($snapshot_url); ?>">
        <div class="tbp-floorplan-header">
            <h3>📍 Plano Interactivo de Asientos</h3>
            <p>Pasa el cursor sobre una mesa para ver quién está asignado.</p>
        </div>

        <div class="tbp-map-viewport" style="position:relative; width:100%; height:600px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.05);">
            <div id="tbp-svg-loader" style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.8); z-index:10; display:flex; align-items:center; justify-content:center;">
                <div class="tbp-spinner"></div>
            </div>
            <div id="tbp-map-canvas" style="width:100%; height:100%;"></div>
            
            <!-- Tooltip Pro -->
            <div id="tbp-map-tooltip" style="display:none; position:absolute; z-index:100; background:#fff; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,0.15); padding:15px; width:220px; border:1px solid #e2e8f0; pointer-events:none;">
                <h4 id="tt-mesa-name" style="margin:0 0 5px 0; color:#0f172a; font-size:14px; font-weight:800;">Mesa A1</h4>
                <div id="tt-mesa-meta" style="font-size:11px; color:#64748b; margin-bottom:10px; font-weight:600;">Zona Dorada • 8/10 Asientos</div>
                <div id="tt-guests-list" style="max-height:150px; overflow-y:auto; border-top:1px solid #f1f5f9; padding-top:10px;">
                    <!-- Guests list -->
                </div>
            </div>
        </div>
    </div>

    <style>
        .tbp-floorplan-container { font-family: 'Segoe UI', Roboto, sans-serif; margin: 20px 0; }
        .tbp-floorplan-header { margin-bottom:15px; }
        .tbp-floorplan-header h3 { margin:0; font-weight:800; text-transform:uppercase; color:#0f172a; font-size:1.1rem; }
        .tbp-floorplan-header p { margin:5px 0 0 0; font-size:13px; color:#64748b; }
        
        #tbp-map-canvas svg { width:100%; height:100%; cursor:grab; }
        #tbp-map-canvas svg:active { cursor:grabbing; }
        
        .map-table { stroke:#fff; stroke-width:1; transition: all 0.2s; cursor:pointer; }
        .map-table:hover { stroke-width:3; filter: brightness(1.1); }
        .map-table.full { stroke:#22c55e; stroke-width:2; }
        
        .map-element { fill:#334155; opacity:0.8; }
        .map-text { font-size:10px; font-weight:800; fill:#0f172a; pointer-events:none; text-anchor:middle; }
        
        .guest-item { font-size:11px; margin-bottom:5px; color:#334155; display:flex; flex-direction:column; }
        .guest-item strong { font-size:12px; color:#0f172a; }
        .guest-item span { font-size:10px; color:#94a3b8; }

        .tbp-spinner { width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #0f172a; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>

    <script>
    jQuery(document).ready(function($) {
        const snapshotUrl = $('#tbp-floorplan-context').data('snapshot-url');
        if (!snapshotUrl) return;

        $.getJSON(snapshotUrl, function(data) {
            $('#tbp-svg-loader').hide();
            if (!data.map) return;

            renderSvgMap(data.map);
        });

        function renderSvgMap(map) {
            const $container = $('#tbp-map-canvas');
            const width = 1200; // Virtual width
            const height = 800; // Virtual height
            
            let svg = `<svg viewBox="0 0 ${width} ${height}" preserveAspectRatio="xMidYMid meet">`;
            
            // 1. Render Elements (Fixed)
            map.elements.forEach(el => {
                const color = el.color || '#334155';
                const opacity = el.type === 'area' ? 0.3 : 0.8;
                svg += `<rect x="${el.x}" y="${el.y}" width="${el.w}" height="${el.h}" fill="${color}" fill-opacity="${opacity}" rx="4" />`;
                if (el.type !== 'area') {
                    svg += `<text x="${el.x + el.w/2}" y="${el.y + el.h/2 + 4}" class="map-text" style="fill:white;">${el.lbl}</text>`;
                }
            });

            // 2. Render Tables
            map.tables.forEach(t => {
                const isFull = t.used >= t.cap;
                const isRound = t.type === 'round';
                const color = t.color || '#2271b1';
                
                if (isRound) {
                    const cx = t.x + t.w/2;
                    const cy = t.y + t.h/2;
                    svg += `<circle cx="${cx}" cy="${cy}" r="${t.w/2}" fill="${color}" class="map-table ${isFull ? 'full' : ''}" data-id="${t.id}" />`;
                    svg += `<text x="${cx}" y="${cy + 4}" class="map-text" style="fill:white;">${t.lbl}</text>`;
                } else {
                    svg += `<rect x="${t.x}" y="${t.y}" width="${t.w}" height="${t.h}" rx="4" fill="${color}" class="map-table ${isFull ? 'full' : ''}" data-id="${t.id}" />`;
                    svg += `<text x="${t.x + t.w/2}" y="${t.y + t.h/2 + 4}" class="map-text" style="fill:white;">${t.lbl}</text>`;
                }
            });

            svg += `</svg>`;
            $container.html(svg);

            // Hover Interactions
            const $tooltip = $('#tbp-map-tooltip');
            
            $('.map-table').on('mouseenter', function(e) {
                const id = $(this).data('id');
                const table = map.tables.find(t => t.id == id);
                if (!table) return;

                $('#tt-mesa-name').text('Mesa ' + table.lbl);
                $('#tt-mesa-meta').text(table.z + ' • ' + table.used + '/' + table.cap + ' Asientos');
                
                let guestsHtml = '';
                if (table.gs && table.gs.length > 0) {
                    table.gs.forEach(g => {
                        guestsHtml += `<div class="guest-item"><strong>${g.n}</strong><span>Pedido ${g.o} • Grupo ${g.g}</span></div>`;
                    });
                } else {
                    guestsHtml = '<p style="font-size:11px; color:#94a3b8; font-style:italic;">Sin asistentes asignados</p>';
                }
                $('#tt-guests-list').html(guestsHtml);

                $tooltip.show();
            }).on('mousemove', function(e) {
                const viewPortRect = $('.tbp-map-viewport')[0].getBoundingClientRect();
                let x = e.clientX - viewPortRect.left + 15;
                let y = e.clientY - viewPortRect.top + 15;
                
                // Keep tooltip inside viewport
                if (x + 220 > viewPortRect.width) x -= 250;
                if (y + 200 > viewPortRect.height) y -= 220;

                $tooltip.css({ left: x + 'px', top: y + 'px' });
            }).on('mouseleave', function() {
                $tooltip.hide();
            });
        }
    });
    </script>
    <?php
    return ob_get_clean();
}
