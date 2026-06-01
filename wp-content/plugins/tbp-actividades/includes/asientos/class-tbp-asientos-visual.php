<?php
/**
 * Visual Editor for TBP Asientos
 * Logic for Stage 2: Floor Plan and Zoning
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Render Stage 2: Visual Floor Plan Editor
 */
function tbp_asientos_render_visual_editor( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) return;

    global $wpdb;
    $table_elements = $wpdb->prefix . 'tbp_seat_elements';
    $table_seats = $wpdb->prefix . 'tbp_seat_tables';

    $existing_elements = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_elements WHERE config_id = %d", $config_id ) );
    $existing_tables = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM $table_seats WHERE config_id = %d", $config_id ) );

    ?>
    <div id="tbp-visual-editor-root" class="postbox" style="margin-top:20px;">
        <div class="postbox-header">
            <h2 class="hndle">Etapa 2: Procesamiento del Plano (Zonificación Masiva)</h2>
        </div>
        <div class="inside">
            <div class="tbp-editor-toolbar" style="display:flex; gap:10px; margin-bottom:20px; background:#f8f9fa; padding:15px; border-radius:8px; border:1px solid #e2e8f0;">
                <div style="flex:1;">
                    <h4 style="margin:0 0 10px 0;">🏗️ Generador Masivo</h4>
                    <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:10px;">
                        <div>
                            <label style="font-size:11px; display:block;">Nombre de Zona</label>
                            <input type="text" id="mass_zone_name" class="widefat" placeholder="Ej. Zona Dorada">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Líneas (Filas)</label>
                            <input type="number" id="mass_rows" class="widefat" value="4" min="1">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Mesas por Línea</label>
                            <input type="number" id="mass_cols" class="widefat" value="15" min="1">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Lugares por Mesa (PAX)</label>
                            <input type="number" id="mass_pax" class="widefat" value="10" min="1">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Tamaño Mesas (px)</label>
                            <input type="range" id="mass_size" style="width:100%;" min="20" max="100" value="45">
                            <span id="size_val" style="font-size:10px; color:#666;">45px</span>
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Espaciado (px)</label>
                            <input type="range" id="mass_spacing" style="width:100%;" min="5" max="100" value="15">
                            <span id="spacing_val" style="font-size:10px; color:#666;">15px</span>
                        </div>
                    </div>
                    <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:10px; margin-top:10px;">
                        <div>
                            <label style="font-size:11px; display:block;">Tipo de Mesa</label>
                            <select id="mass_shape" class="widefat">
                                <option value="round">Banquete Redonda</option>
                                <option value="square">Cuadrada</option>
                                <option value="rectangular">Rectangular</option>
                                <option value="bar">Mesa Bar / Coctel</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Nomenclatura</label>
                            <select id="mass_naming" class="widefat">
                                <option value="alpha">A1, A2, B1...</option>
                                <option value="numeric">1, 2, 3...</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Inicio Etiqueta</label>
                            <input type="text" id="mass_start" class="widefat" value="A" placeholder="A o 1">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Orientación</label>
                            <select id="mass_orient" class="widefat">
                                <option value="h">Horizontal (Filas)</option>
                                <option value="v">Vertical (Columnas)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Color Zona</label>
                            <input type="color" id="mass_color" class="widefat" value="#2271b1" style="height:30px; padding:0;">
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Patrón</label>
                            <select id="mass_pattern" class="widefat">
                                <option value="linear">Lineal</option>
                                <option value="snake">Serpiente (Zig-Zag)</option>
                            </select>
                        </div>
                        <div>
                            <label style="font-size:11px; display:block;">Sentido</label>
                            <select id="mass_direction" class="widefat">
                                <option value="normal">Normal (Izq-Der)</option>
                                <option value="reverse">Inverso (Der-Izq)</option>
                            </select>
                        </div>
                    </div>
                    <div style="margin-top:10px; display:flex; gap:10px; align-items:center;">
                        <button type="button" id="btn_generate_mass" class="button button-primary">Generar Cuadrícula</button>
                        <button type="button" id="btn_clear_canvas" class="button button-link-delete" style="color:#b32d2e;">Limpiar Plano</button>
                    </div>
                </div>

                <div style="border-left:1px solid #ddd; padding-left:20px; width:200px;">
                    <h4 style="margin:0 0 10px 0;">🔍 Visualización</h4>
                    <label style="font-size:11px;">Zoom del Lienzo</label>
                    <input type="range" id="canvas_zoom" style="width:100%;" min="0.2" max="2" step="0.1" value="0.8">
                    <button type="button" id="btn_reset_view" class="button button-small" style="width:100%; margin-top:5px;">Restablecer Vista</button>
                    <button type="button" id="btn_fullscreen" class="button button-secondary" style="width:100%; margin-top:5px;">🖥️ Pantalla Completa</button>
                    <button type="button" id="btn_clone" class="button button-secondary" style="width:100%; margin-top:5px;">👯 Clonar Selección</button>
                    <button type="button" id="btn_match_size" class="button button-secondary" style="width:100%; margin-top:5px;">📏 Igualar Tamaños</button>
                    <button type="button" id="btn_delete_selected" class="button button-link-delete" style="width:100%; margin-top:5px; color:#b32d2e;">🗑️ Eliminar Seleccionados</button>
                </div>
                
                <div style="border-left:1px solid #ddd; padding-left:20px; flex:1;">
                    <h4 style="margin:0 0 10px 0;">📊 Resumen de Zonas</h4>
                    <table class="wp-list-table widefat fixed striped" style="font-size:10px;">
                        <thead><tr><th>Zona</th><th>Mesas</th><th>Capacidad</th><th>Acción</th></tr></thead>
                        <tbody id="zone_summary_body">
                            <!-- JS dynamic -->
                        </tbody>
                    </table>
                </div>
                
                <div style="border-left:1px solid #ddd; padding-left:20px; width:200px;">
                    <h4 style="margin:0 0 10px 0;">🎨 Objetos</h4>
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap:5px;">
                        <button type="button" class="button btn-add-element" data-type="stage">🎭 Escenario</button>
                        <button type="button" class="button btn-add-element" data-type="dance_floor">💃 Pista</button>
                        <button type="button" class="button btn-add-element" data-type="area">🟦 Alfombra/Área</button>
                        <button type="button" class="button btn-add-element" data-type="wc">🚻 Baños</button>
                        <button type="button" class="button btn-add-element" data-type="exit">🚪 Salida</button>
                        <button type="button" class="button btn-add-element" data-type="bar">🍸 Barra</button>
                    </div>
                    <div style="margin-top:10px;">
                        <label style="font-size:11px;">Color Objeto</label>
                        <input type="color" id="obj_color" class="widefat" value="#334155" style="height:30px; padding:0;">
                    </div>
                </div>
            </div>

            <!-- CANVAS AREA -->
            <div id="tbp-canvas-container" style="position:relative; width:100%; height:600px; background:#f0f0f0; border:2px dashed #ccc; border-radius:8px; overflow:hidden; cursor:crosshair;">
                <div id="tbp-canvas-grid" style="position:absolute; top:0; left:0; width:3000px; height:3000px; background-image: radial-gradient(#d1d5db 1px, transparent 1px); background-size: 20px 20px;"></div>
                <div id="tbp-canvas-items" style="position:absolute; top:0; left:0; width:100%; height:100%;">
                    <!-- Rendered Items via JS -->
                </div>
            </div>
            
            <div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
                <div style="color:#666; font-size:12px;">
                    💡 Arrastra los elementos para posicionarlos. Usa la rueda del ratón para Zoom.
                </div>
                <button type="button" id="btn_save_layout" class="button button-primary button-large">💾 Guardar Diseño del Plano</button>
            </div>
        </div>
    </div>

    <style>
        .tbp-canvas-item { position:absolute; background:#fff; border:2px solid #2271b1; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:move; user-select:none; box-shadow:0 2px 4px rgba(0,0,0,0.1); transition:transform 0.1s; }
        .tbp-canvas-item:hover { transform: scale(1.05); border-color:#135e96; z-index:10; }
        .tbp-canvas-item.selected { border: 3px solid #f59e0b !important; box-shadow: 0 0 15px rgba(245, 158, 11, 0.6); z-index: 50; }
        .tbp-canvas-item .label { font-weight:800; font-size:10px; color:inherit; text-align:center; }
        .tbp-canvas-item .cap { position:absolute; bottom:-12px; left:0; width:100%; text-align:center; font-size:8px; color:#64748b; font-weight:700; white-space:nowrap; }
        
        .item-round { border-radius:50%; }
        .item-square { border-radius:4px; }
        .item-rectangular { border-radius:4px; }
        .item-bar { border-radius:50%; border-width:3px !important; }

        .item-stage { border-radius:4px; color:white; }
        .item-dance_floor { border-radius:4px; border-style:dashed; }
        .item-area { border-radius:0; border:none; z-index:1 !important; opacity:0.3; }
        .item-exit { border-radius:4px; }
        .item-wc { border-radius:4px; }
        .item-bar { border-radius:4px; }

        /* Resize handle */
        .tbp-resize-handle { position:absolute; right:0; bottom:0; width:12px; height:12px; background:#2271b1; cursor:nwse-resize; display:none; border-radius: 2px 0 0 0; z-index:60; border:1px solid #fff; }
        .tbp-canvas-item.selected .tbp-resize-handle { display:block; }

        /* Multi-select box */
        #tbp-selection-box { position:absolute; border:1px dashed #2271b1; background:rgba(34, 113, 177, 0.1); pointer-events:none; z-index:1000; display:none; }

        /* Zoom container */
        #tbp-canvas-items { transform-origin: 0 0; }

        /* Fullscreen Mode Aggressive */
        .tbp-editor-fullscreen { position:fixed !important; top:0 !important; left:0 !important; width:100vw !important; height:100vh !important; z-index:9999999 !important; background:#f0f0f0 !important; margin:0 !important; padding:40px !important; box-sizing:border-box; overflow-y: auto !important; }
        .tbp-editor-fullscreen #tbp-canvas-container { height: 800px !important; border: 1px solid #cbd5e1; }
        
        /* Force Hide WP Elements */
        body.tbp-hide-wp #adminmenuwrap, 
        body.tbp-hide-wp #adminmenuback, 
        body.tbp-hide-wp #wpadminbar, 
        body.tbp-hide-wp #wpfooter,
        body.tbp-hide-wp .update-nag,
        body.tbp-hide-wp .notice,
        body.tbp-hide-wp #screen-meta-links,
        body.tbp-hide-wp #screen-meta { display:none !important; visibility:hidden !important; height:0 !important; overflow:hidden !important; }
        
        body.tbp-hide-wp #wpcontent { margin-left: 0 !important; padding: 0 !important; }
        body.tbp-hide-wp #wpbody { padding-top: 0 !important; }
        body.tbp-hide-wp { overflow:hidden !important; }
    </style>

    <div id="tbp-selection-box"></div>

    <script>
    jQuery(document).ready(function($) {
        let items = <?php echo wp_json_encode( array_merge( 
            array_map(function($t){ return array('id'=>$t->id, 'type'=>'table', 'shape'=>$t->tipo, 'label'=>$t->numero, 'x'=>$t->pos_x, 'y'=>$t->pos_y, 'w'=>$t->width, 'h'=>$t->height, 'pax'=>$t->capacidad, 'zona'=>$t->zona, 'color'=>$t->color); }, $existing_tables),
            array_map(function($e){ return array('id'=>$e->id, 'type'=>'element', 'shape'=>$e->tipo, 'label'=>$e->label, 'x'=>$e->pos_x, 'y'=>$e->pos_y, 'w'=>$e->width, 'h'=>$e->height, 'color'=>$e->color); }, $existing_elements)
        ) ); ?>;

        const $canvasItems = $('#tbp-canvas-items');
        const $selectionBox = $('#tbp-selection-box');
        
        let zoom = 0.8;
        let isDragging = false;
        let isSelecting = false;
        let isResizing = false;
        let selectedItems = [];
        let startPos = { x: 0, y: 0 };
        let offset = { x: 0, y: 0 };
        let dragItem = null;
        let resizeItem = null;

        // History Stack
        const history = [];
        const maxHistory = 30;

        function saveHistory() {
            if (history.length >= maxHistory) history.shift();
            history.push(JSON.stringify(items));
        }

        function undo() {
            if (history.length <= 1) return;
            history.pop(); // Remove current state
            const lastState = history[history.length - 1];
            items = JSON.parse(lastState);
            selectedItems = [];
            renderItems();
        }

        function deleteSelected() {
            if (selectedItems.length === 0) return;
            if (!confirm(`¿Eliminar ${selectedItems.length} elementos seleccionados?`)) return;
            
            items = items.filter(it => !selectedItems.includes(it));
            selectedItems = [];
            saveHistory();
            renderItems();
        }

        function matchSizes() {
            if (selectedItems.length < 2) return;
            const target = selectedItems[0];
            selectedItems.forEach((it, idx) => {
                if (idx === 0) return;
                it.w = target.w;
                it.h = target.h;
            });
            saveHistory();
            renderItems();
        }

        function cloneSelected() {
            if (selectedItems.length === 0) return;
            const newClones = [];
            selectedItems.forEach(it => {
                const clone = { ...it, id: null, x: it.x + 100, y: it.y + 50 };
                items.push(clone);
                newClones.push(clone);
            });
            selectedItems = newClones;
            saveHistory();
            renderItems();
        }

        $(document).on('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'z') {
                e.preventDefault();
                undo();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
                e.preventDefault();
                cloneSelected();
            }
            if (e.key === 'Delete' || e.key === 'Backspace') {
                // Solo si no estamos escribiendo en un input
                if (!$(e.target).is('input, textarea')) {
                    e.preventDefault();
                    deleteSelected();
                }
            }
        });

        function updateZoom() {
            $canvasItems.css('transform', `scale(${zoom})`);
        }

        function renderSummary() {
            const summary = {};
            items.forEach(it => {
                if(it.type === 'table') {
                    const z = it.zona || 'Sin Zona';
                    if(!summary[z]) summary[z] = { tables: 0, pax: 0 };
                    summary[z].tables++;
                    const paxNum = parseInt(it.pax) || 0;
                    summary[z].pax += paxNum;
                }
            });

            const $body = $('#zone_summary_body');
            $body.empty();
            let totalT = 0, totalP = 0;
            
            Object.keys(summary).forEach(z => {
                totalT += summary[z].tables;
                totalP += summary[z].pax;
                $body.append(`<tr>
                    <td><strong>${z}</strong></td>
                    <td>${summary[z].tables}</td>
                    <td>${summary[z].pax} PAX</td>
                    <td><a href="#" class="btn-delete-zone" data-zone="${z}" style="color:#a00;">🗑️</a></td>
                </tr>`);
            });
            
            $body.append(`<tr style="background:#f0f6fb; font-weight:800;"><td>TOTAL</td><td>${totalT}</td><td>${totalP} PAX</td><td></td></tr>`);
        }

        function renderItems() {
            $canvasItems.empty();
            items.forEach((item, index) => {
                const isTable = item.type === 'table';
                const cssClass = isTable ? 'item-table' : 'item-' + item.shape;
                const color = item.color || (isTable ? '#2271b1' : '#334155');
                const border = isTable ? color : 'darken(' + color + ', 20%)';
                
                let style = `left:${item.x}px; top:${item.y}px; width:${item.w}px; height:${item.h}px; background:${color}; border-color:${color};`;
                
                if (item.shape === 'area') {
                    style += `z-index:1;`;
                }

                const fontSize = item.w < 40 ? '8px' : '10px';
                const capDisplay = (item.w < 40 || !isTable) ? 'none' : 'block';
                const labelColor = isTable ? '#fff' : '#fff';

                const isSelected = selectedItems.includes(item);

                const $el = $(`<div class="tbp-canvas-item ${cssClass} ${isSelected ? 'selected' : ''}" style="${style}" data-index="${index}">
                    <div class="label" style="font-size:${fontSize}; color:${labelColor}">${item.label}</div>
                    ${isTable ? `<div class="cap" style="display:${capDisplay}">${item.pax} PAX</div>` : ''}
                    <div class="tbp-resize-handle"></div>
                </div>`);
                
                $canvasItems.append($el);
            });
            renderSummary();
            updateZoom();
        }

        // Mouse Down: Selection, Drag or Resize
        $('#tbp-canvas-container').on('mousedown', function(e) {
            const $resizeHandle = $(e.target).closest('.tbp-resize-handle');
            const $target = $(e.target).closest('.tbp-canvas-item');
            const canvasRect = this.getBoundingClientRect();
            const clickX = (e.clientX - canvasRect.left) / zoom;
            const clickY = (e.clientY - canvasRect.top) / zoom;

            if ($resizeHandle.length) {
                // RESIZING
                e.stopPropagation();
                isResizing = true;
                const index = $resizeHandle.closest('.tbp-canvas-item').data('index');
                resizeItem = items[index];
                startPos = { x: clickX, y: clickY };
                resizeItem.origW = resizeItem.w;
                resizeItem.origH = resizeItem.h;
                return;
            }

            if ($target.length) {
                // DRAGGING
                const index = $target.data('index');
                dragItem = items[index];
                
                if (!selectedItems.includes(dragItem)) {
                    if (!e.shiftKey) selectedItems = [];
                    selectedItems.push(dragItem);
                }
                
                isDragging = true;
                startPos = { x: clickX, y: clickY };
                
                items.forEach(it => {
                    it.origX = parseInt(it.x) || 0;
                    it.origY = parseInt(it.y) || 0;
                });

                renderItems();
            } else {
                // SELECTING AREA
                isSelecting = true;
                if (!e.shiftKey) {
                    selectedItems = [];
                    $('.tbp-canvas-item').removeClass('selected');
                }
                
                // Coordenadas relativas al contenedor (sin zoom)
                startPos = { 
                    x: e.clientX - canvasRect.left, 
                    y: e.clientY - canvasRect.top 
                };
                
                $selectionBox.css({
                    left: startPos.x + 'px',
                    top: startPos.y + 'px',
                    width: '0px',
                    height: '0px'
                }).show();
            }
        });

        $(document).on('mousemove', function(e) {
            const canvasRect = $('#tbp-canvas-container')[0].getBoundingClientRect();

            if (isResizing && resizeItem) {
                const curX = (e.clientX - canvasRect.left) / zoom;
                const curY = (e.clientY - canvasRect.top) / zoom;
                
                const dw = Math.round((curX - startPos.x) / 10) * 10;
                const dh = Math.round((curY - startPos.y) / 10) * 10;

                resizeItem.w = Math.max(30, resizeItem.origW + dw);
                resizeItem.h = Math.max(30, resizeItem.origH + dh);
                
                const $el = $canvasItems.find(`[data-index="${items.indexOf(resizeItem)}"]`);
                $el.css({ width: resizeItem.w + 'px', height: resizeItem.h + 'px' });
                return;
            }

            if (isDragging && selectedItems.length > 0) {
                const currentX = (e.clientX - canvasRect.left) / zoom;
                const currentY = (e.clientY - canvasRect.top) / zoom;
                
                let dx = Math.round((currentX - startPos.x) / 10) * 10;
                let dy = Math.round((currentY - startPos.y) / 10) * 10;
                
                if(isNaN(dx)) dx = 0;
                if(isNaN(dy)) dy = 0;

                selectedItems.forEach(it => {
                    it.x = it.origX + dx;
                    it.y = it.origY + dy;
                    const $el = $canvasItems.find(`[data-index="${items.indexOf(it)}"]`);
                    $el.css({ left: it.x + 'px', top: it.y + 'px' });
                });
            }

            if (isSelecting) {
                const curX = e.clientX - canvasRect.left;
                const curY = e.clientY - canvasRect.top;
                
                const left = Math.min(startPos.x, curX);
                const top = Math.min(startPos.y, curY);
                const width = Math.abs(startPos.x - curX);
                const height = Math.abs(startPos.y - curY);

                $selectionBox.css({ left: left + 'px', top: top + 'px', width: width + 'px', height: height + 'px' });

                // Detectar intersección (convertimos el box a coordenadas con zoom para comparar con los items)
                const zoomLeft = left / zoom;
                const zoomTop = top / zoom;
                const zoomRight = (left + width) / zoom;
                const zoomBottom = (top + height) / zoom;
                
                items.forEach(it => {
                    const intersect = !(it.x + it.w < zoomLeft || 
                                        it.x > zoomRight || 
                                        it.y + it.h < zoomTop || 
                                        it.y > zoomBottom);
                    
                    const $el = $canvasItems.find(`[data-index="${items.indexOf(it)}"]`);
                    if (intersect) {
                        if (!selectedItems.includes(it)) selectedItems.push(it);
                        $el.addClass('selected');
                    } else {
                        if (!e.shiftKey) {
                            selectedItems = selectedItems.filter(i => i !== it);
                            $el.removeClass('selected');
                        }
                    }
                });
            }
        });

        $(document).on('mouseup', function() {
            if (isResizing) {
                isResizing = false;
                resizeItem = null;
                saveHistory();
            }
            if (isDragging) {
                isDragging = false;
                dragItem = null;
                saveHistory();
            }
            if (isSelecting) {
                isSelecting = false;
                $selectionBox.hide();
            }
        });

        // Zoom Control
        $('#canvas_zoom').on('input', function() {
            zoom = parseFloat($(this).val());
            updateZoom();
        });

        $('#btn_reset_view').on('click', function() {
            zoom = 0.8;
            $('#canvas_zoom').val(0.8);
            updateZoom();
        });

        $('#btn_fullscreen').on('click', function() {
            const $root = $('#tbp-visual-editor-root');
            $root.toggleClass('tbp-editor-fullscreen');
            $('body').toggleClass('tbp-hide-wp');
            
            if ($root.hasClass('tbp-editor-fullscreen')) {
                $(this).text('❌ Salir Pantalla Completa');
            } else {
                $(this).text('🖥️ Pantalla Completa');
            }
        });

        $('#btn_clone').on('click', function() {
            cloneSelected();
        });

        $('#btn_match_size').on('click', function() {
            matchSizes();
        });

        $('#btn_delete_selected').on('click', function() {
            deleteSelected();
        });

        $('#mass_size').on('input', function() {
            $('#size_val').text($(this).val() + 'px');
        });
        
        $('#mass_spacing').on('input', function() {
            $('#spacing_val').text($(this).val() + 'px');
        });

        // Mass Generation
        $('#btn_generate_mass').on('click', function() {
            const zone = $('#mass_zone_name').val() || 'Sin Zona';
            const rows = parseInt($('#mass_rows').val());
            const cols = parseInt($('#mass_cols').val());
            const pax  = parseInt($('#mass_pax').val()) || 10;
            const size = parseInt($('#mass_size').val());
            const spacing = parseInt($('#mass_spacing').val());
            const shape = $('#mass_shape').val();
            
            const naming = $('#mass_naming').val();
            const pattern = $('#mass_pattern').val();
            const direction = $('#mass_direction').val();
            const startVal = $('#mass_start').val().toUpperCase() || 'A';
            const orient = $('#mass_orient').val();
            const color  = $('#mass_color').val();

            if (!confirm(`¿Generar ${rows * cols} mesas para la zona "${zone}"?`)) return;

            const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split("");
            const spacingX = size + spacing;
            const spacingY = size + spacing + 20;
            
            // Buscar punto libre para empezar
            let startX = 50;
            let startY = 100;
            if(items.length > 0) {
                // Encontrar el punto más bajo actual para no encimar
                const max_y = Math.max(...items.map(it => parseInt(it.y) + parseInt(it.h)));
                startY = max_y + 80; 
            }

            let counter = naming === 'numeric' ? parseInt(startVal) || 1 : 0;
            let alphaIdx = naming === 'alpha' ? alphabet.indexOf(startVal) : 0;
            if (alphaIdx === -1) alphaIdx = 0;

            for (let r = 0; r < rows; r++) {
                const currentAlpha = alphabet[alphaIdx + r] || 'Z';
                
                // Determinamos si esta fila se invierte (por patrón serpiente)
                const isSnakeReverse = (pattern === 'snake' && r % 2 !== 0);
                
                for (let c = 0; c < cols; c++) {
                    // El índice visual real de la columna para la etiqueta
                    const visualCol = (isSnakeReverse || direction === 'reverse') ? (cols - 1 - c) : c;
                    
                    let label = '';
                    if (naming === 'numeric') {
                        label = counter.toString();
                        counter++;
                    } else {
                        label = currentAlpha + (visualCol + 1);
                    }

                    // Posicionamiento
                    let posX, posY;
                    if (orient === 'h') {
                        posX = startX + (c * spacingX);
                        posY = startY + (r * spacingY);
                    } else {
                        posX = startX + (r * spacingX);
                        posY = startY + (c * spacingY);
                    }

                    items.push({
                        type: 'table',
                        shape: shape,
                        label: label,
                        x: posX,
                        y: posY,
                        w: shape === 'rectangular' ? size * 1.5 : size,
                        h: size,
                        pax: pax,
                        zona: zone,
                        color: color
                    });
                }
            }
            saveHistory();
            renderItems();
        });

        $(document).on('click', '.btn-delete-zone', function(e) {
            e.preventDefault();
            const zone = $(this).data('zone');
            if(!confirm(`¿Eliminar todas las mesas de la zona "${zone}"?`)) return;
            items = items.filter(it => it.zona !== zone);
            saveHistory();
            renderItems();
        });

        $('#btn_clear_canvas').on('click', function() {
            if(!confirm('¿Eliminar TODOS los elementos del plano?')) return;
            items = [];
            saveHistory();
            renderItems();
        });

        $('.btn-add-element').on('click', function() {
            const type = $(this).data('type');
            const label = $(this).text();
            const color = $('#obj_color').val();
            
            items.push({
                type: 'element',
                shape: type,
                label: label,
                x: 100,
                y: 100,
                w: type === 'stage' ? 200 : (type === 'area' ? 400 : 80),
                h: type === 'stage' ? 100 : (type === 'area' ? 300 : 80),
                color: color
            });
            saveHistory();
            renderItems();
        });

        $('#btn_save_layout').on('click', function() {
            const configId = <?php echo $config_id; ?>;
            $(this).prop('disabled', true).text('⏳ Guardando...');
            
            $.post(ajaxurl, {
                action: 'tbp_asientos_save_layout',
                config_id: configId,
                layout_data: JSON.stringify(items),
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(response) {
                $('#btn_save_layout').prop('disabled', false).text('💾 Guardar Diseño del Plano');
                if (response.success) {
                    alert('✅ Plano guardado correctamente.');
                } else {
                    alert('❌ Error: ' + response.data);
                }
            });
        });

        saveHistory(); // Initial state
        renderItems();
    });
    </script>
    <?php
}

