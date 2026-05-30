
    jQuery(document).ready(function($) {
        // Navegación de Etapas
        $('.stage-item').on('click', function() {
            const stage = $(this).data('stage');
            $('.stage-item').removeClass('active').css({background:'none', color:'#64748b', border:'none'});
            $(this).addClass('active').css({background:'#f0f6fb', color:'#2271b1', border:'1px solid #2271b1'});
            
            $('.tbp-stage-content').hide();
            $('#tbp-stage-' + stage).show();
        });

        let zonas = [];
        try {
            const rawZonas = $('#zonas_json').val();
            if (rawZonas) zonas = JSON.parse(rawZonas);
        } catch(e) { console.error('Error parsing zonas', e); }

        function renderZonas() {
            const $container = $('#zonas-container');
            $container.empty();

            zonas.forEach((zona, index) => {
                const prioridad = index + 1;
                zona.prioridad = prioridad;

                const html = `
                    <div class="tbp-zona-box" data-index="${index}">
                        <h4>
                            <span>⭐ Prioridad #${prioridad}</span>
                            <a class="btn-remove-zona">× Eliminar</a>
                        </h4>
                        <div class="tbp-zona-grid">
                            <div>
                                <label>Nombre de la Zona</label>
                                <input type="text" class="widefat z-nombre" value="${zona.nombre || ''}" placeholder="Ej. Zona C">
                            </div>
                            <div>
                                <label>Cantidad de Mesas</label>
                                <input type="number" class="widefat z-mesas" value="${zona.mesas || 10}" min="1">
                            </div>
                            <div>
                                <label>Lugares por Mesa</label>
                                <input type="number" class="widefat z-capacidad" value="${zona.capacidad || 10}" min="1">
                            </div>
                            <div>
                                <label>Mínimo Grupo</label>
                                <input type="number" class="widefat z-min" value="${zona.grupo_min || 1}" min="1" title="Tamaño mínimo del grupo para entrar en esta zona">
                            </div>
                            <div>
                                <label>Máximo Grupo</label>
                                <input type="number" class="widefat z-max" value="${zona.grupo_max || 999}" min="1" title="Tamaño máximo del grupo para entrar en esta zona (999 = sin límite)">
                            </div>
                        </div>
                        <p style="margin-bottom:0;">
                            <small>Para bloquear mesas, guarda la configuración primero y usa la vista detallada.</small>
                        </p>
                    </div>
                `;
                $container.append(html);
            });

            updateJsonField();
        }

        function updateJsonField() {
            $('#zonas-container .tbp-zona-box').each(function() {
                const idx = $(this).data('index');
                zonas[idx].nombre = $(this).find('.z-nombre').val();
                zonas[idx].mesas = parseInt($(this).find('.z-mesas').val()) || 0;
                zonas[idx].capacidad = parseInt($(this).find('.z-capacidad').val()) || 0;
                zonas[idx].grupo_min = parseInt($(this).find('.z-min').val()) || 1;
                zonas[idx].grupo_max = parseInt($(this).find('.z-max').val()) || 999;
            });
            $('#zonas_json').val(JSON.stringify(zonas));
        }

        $('#add_zona_btn').on('click', function() {
            zonas.push({ nombre: '', mesas: 10, capacidad: 10, grupo_min: 1, grupo_max: 999 });
            renderZonas();
        });

        $('#zonas-container').on('click', '.btn-remove-zona', function() {
            const idx = $(this).closest('.tbp-zona-box').data('index');
            zonas.splice(idx, 1);
            renderZonas();
        });

        $('#zonas-container').on('input', 'input', function() {
            updateJsonField();
        });

        // Inicializar
        renderZonas();

        // Escanear asistentes en lotes (Batching para evitar 504)
        $('#btn_scan_event').on('click', function() {
            const configId = 0;
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#btn_run_packing').prop('disabled', true);
            $('#scan_results').hide();
            $('#scan_loader').show().html('⏳ Iniciando escaneo...');

            // Paso 1: Init (Obtener todos los IDs)
            $.post(ajaxurl, {
                action: 'tbp_asientos_scan_init',
                config_id: configId,
                nonce: '"dummy_nonce"'
            }, function(initRes) {
                if (!initRes.success) {
                    $('#scan_loader').hide();
                    $btn.prop('disabled', false);
                    alert('Error: ' + (initRes.data.message || initRes.data));
                    return;
                }

                const orderIds = initRes.data.order_ids;
                const totalOrders = orderIds.length;
                if (totalOrders === 0) {
                    $('#scan_loader').hide();
                    $btn.prop('disabled', false);
                    alert('No se encontraron pedidos con asientos para este evento.');
                    return;
                }

                const batchSize = 50;
                let currentIndex = 0;
                let allResults = [];

                function processNextBatch() {
                    if (currentIndex >= totalOrders) {
                        finishScan();
                        return;
                    }

                    const batch = orderIds.slice(currentIndex, currentIndex + batchSize);
                    const pct = Math.round((currentIndex / totalOrders) * 100);
                    $('#scan_loader').html('⏳ Escaneando pedidos... ' + pct + '% (' + currentIndex + ' de ' + totalOrders + ')');

                    $.post(ajaxurl, {
                        action: 'tbp_asientos_scan_batch',
                        config_id: configId,
                        order_ids: batch,
                        nonce: '"dummy_nonce"'
                    }, function(batchRes) {
                        if (batchRes.success) {
                            allResults = allResults.concat(batchRes.data);
                            currentIndex += batchSize;
                            processNextBatch();
                        } else {
                            $('#scan_loader').hide();
                            $btn.prop('disabled', false);
                            alert('Error en lote: ' + (batchRes.data.message || batchRes.data));
                        }
                    }).fail(function(xhr) {
                        $('#scan_loader').hide();
                        $btn.prop('disabled', false);
                        alert('Error de conexión en lote. Servidor (' + xhr.status + ').');
                    });
                }

                function finishScan() {
                    $('#scan_loader').html('⏳ Consolidando resultados...');
                    $.post(ajaxurl, {
                        action: 'tbp_asientos_scan_finish',
                        config_id: configId,
                        results: allResults,
                        nonce: '"dummy_nonce"'
                    }, function(finRes) {
                        $('#scan_loader').hide();
                        $btn.prop('disabled', false);
                        
                        if (finRes.success) {
                            $('#scan_asistentes').text(finRes.data.asistentes);
                            $('#scan_grupos').text(finRes.data.grupos);
                            $('#scan_pedidos').text(finRes.data.pedidos);
                            $('#scan_results').slideDown();
                            
                            if (finRes.data.pedidos > 0) {
                                $('#btn_run_packing').prop('disabled', false);
                            } else {
                                alert('No se encontraron pedidos con lugares por asignar.');
                            }
                        } else {
                            alert('Error al consolidar: ' + (finRes.data.message || finRes.data));
                        }
                    }).fail(function(xhr) {
                        $('#scan_loader').hide();
                        $btn.prop('disabled', false);
                        alert('Error al guardar datos (' + xhr.status + ').');
                    });
                }

                // Iniciar procesamiento en lotes
                processNextBatch();

            }).fail(function(xhr) {
                $('#scan_loader').hide();
                $btn.prop('disabled', false);
                alert('Error de conexión inicial. Servidor (' + xhr.status + ').');
            });
        });

        // Ejecutar algoritmo
        $('#btn_run_packing, #btn_run_packing_v2').on('click', function() {
            if(!confirm('¿Estás seguro? Esto calculará las asignaciones y sobrescribirá cualquier asignación manual previa.')) return;
            
            const configId = 0;
            const $btn = $(this);
            const originalText = $btn.text();
            
            $('#btn_run_packing, #btn_run_packing_v2').prop('disabled', true);
            $btn.text('⏳ Asignando...');
            $('#packing_loader').show();

            $.post(ajaxurl, {
                action: 'tbp_asientos_run_packing',
                config_id: configId,
                nonce: '"dummy_nonce"'
            }, function(response) {
                $('#packing_loader').hide();
                $('#btn_run_packing, #btn_run_packing_v2').prop('disabled', false);
                $btn.text(originalText);

                if (response.success) {
                    var stats = response.data.stats;
                    var debug = response.data.debug || {};
                    
                    if (stats.total_asignados === 0) {
                        // Mostrar diagnóstico detallado cuando hay 0 asignaciones
                        var diagMsg = '⚠️ Asignación completada con 0 resultados.\n\n';
                        diagMsg += '📊 DIAGNÓSTICO:\n';
                        diagMsg += '• Event ID: ' + (debug.event_id || 'N/A') + '\n';
                        diagMsg += '• Proveedor ID: ' + (debug.proveedor_id || 'N/A') + '\n';
                        diagMsg += '• Campo de Grupo: ' + (debug.group_field || 'N/A') + '\n';
                        diagMsg += '• Fuente de pedidos: ' + (debug.pedidos_source || 'N/A') + '\n';
                        diagMsg += '• Pedidos encontrados: ' + (debug.pedidos_raw_count || 0) + '\n';
                        diagMsg += '• Zonas configuradas: ' + (debug.zonas_count || 0) + '\n';
                        diagMsg += '• Nombres de zonas: ' + (debug.zonas_nombres ? debug.zonas_nombres.join(', ') : 'N/A') + '\n';
                        diagMsg += '• Grupos encontrados: ' + (debug.grupos_count || 0) + '\n';
                        
                        if (debug.mesas_por_zona) {
                            diagMsg += '• Mesas por zona:\n';
                            for (var z in debug.mesas_por_zona) {
                                diagMsg += '    - ' + z + ': ' + debug.mesas_por_zona[z] + ' mesas\n';
                            }
                        }
                        
                        diagMsg += '\n💡 Si "Pedidos encontrados" es 0, verifica que el evento tenga pedidos completados/procesando.';
                        diagMsg += '\nSi "Mesas por zona" muestra 0, regenera el plano visual.';
                        
                        alert(diagMsg);
                    } else {
                        alert('✅ Asignación completada.\n\nPedidos asignados: ' + stats.total_asignados + '\nLugares ocupados: ' + (stats.total_lugares || 0) + '\nEficiencia: ' + stats.eficiencia_pct + '%');
                    }
                    window.location.href = '?page=tbp-actividades-asientos&action=view&id=' + configId;
                } else {
                    alert('Error: ' + (response.data.message || response.data));
                }
            });
        });

        // Eliminar configuración
        $('#btn_delete_config').on('click', function(e) {
            e.preventDefault();
            if(!confirm('⚠️ ¿ESTÁS SEGURO? Se eliminará la configuración, todas las mesas y todas las asignaciones generadas. Esto no se puede deshacer.')) return;
            
            // Cambiar la acción del formulario y hacer submit
            $('#tbp-asientos-form').append('<input type="hidden" name="_wpnonce" value=""dummy_nonce"">');
            $('input[name="tbp_asientos_action"]').val('delete_config');
            $('#tbp-asientos-form').submit();
        });

        // Regenerar Snapshot Manualmente
        $('#btn_regenerate_snapshot').on('click', function() {
            const configId = 0;
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#snapshot_loader').show();

            $.post(ajaxurl, {
                action: 'tbp_asientos_regenerate_snapshot',
                config_id: configId,
                nonce: '"dummy_nonce"'
            }, function(response) {
                $('#snapshot_loader').hide();
                $btn.prop('disabled', false);
                if (response.success) {
                    alert('✅ ' + response.data);
                } else {
                    alert('❌ Error: ' + response.data);
                }
            }).fail(function() {
                $('#snapshot_loader').hide();
                $btn.prop('disabled', false);
                alert('❌ Error de red al generar snapshot.');
            });
        });
        // Lógica de carga de campos de metadatos (Reutilizando AJAX de premiaciones)
        const currentGroupField = '0';

        function refreshGroupFields(eventId) {
            const $field = $('#group_field');
            if (!eventId) {
                $field.html('<option value="">-- Seleccionar Evento primero --</option>');
                return;
            }

            $field.html('<option value="">⏳ Cargando campos...</option>');

            $.get(ajaxurl, {
                action: 'tbp_actividades_get_event_attendee_fields',
                event_id: eventId
            }, function(response) {
                let html = '<option value="">-- Seleccionar Campo --</option>';
                if (response.success && response.data && response.data.length > 0) {
                    response.data.forEach(function(label) {
                        const selected = (label === currentGroupField) ? 'selected' : '';
                        html += `<option value="${label}" ${selected}>${label}</option>`;
                    });
                } else {
                    html = '<option value="">❌ No se encontraron campos en los tickets de este evento</option>';
                    if (currentGroupField) {
                        html += `<option value="${currentGroupField}" selected>${currentGroupField} (Actual)</option>`;
                    }
                }
                $field.html(html);
            });
        }

        // ==========================================
        // JS para la Asignación Manual (Etapa 3 - Tab 2)
        // ==========================================
        let manualOrders = [];
        let manualTables = [];
        let manualElements = [];
        
        manualOrders = [];
        manualTables = [];
        manualElements = [];
        let selectedOrderId = null;
        let manualZoom = 0.7;
        let isPanning = false;
        let panStart = { x: 0, y: 0 };
        let panOffset = { x: 50, y: 50 }; // Un offset inicial descentrado pero visible

        // Manejar Tabs de la Etapa 3
        $('.tbp-stage3-tab-btn').on('click', function() {
            const target = $(this).data('target');
            $('.tbp-stage3-tab-btn').removeClass('active').css({borderBottom:'none', color:'#64748b'});
            $(this).addClass('active').css({borderBottom:'3px solid #2271b1', color:'#2271b1'});
            $('.tbp-stage3-tab-content').hide();
            $('#' + target).show();
            
            if (target === 'tbp-stage3-manual') {
                renderManualWorkspace();
            }
        });

        // Inicializar Panning
        $('#tbp-manual-canvas-container').on('mousedown', function(e) {
            if ($(e.target).closest('.manual-table-item, .manual-canvas-element, #tbp-manual-table-tooltip').length === 0) {
                isPanning = true;
                $(this).css('cursor', 'grabbing');
                panStart = { x: e.clientX - panOffset.x, y: e.clientY - panOffset.y };
            }
        });

        $(document).on('mousemove', function(e) {
            if (isPanning) {
                panOffset.x = e.clientX - panStart.x;
                panOffset.y = e.clientY - panStart.y;
                updateManualZoom();
            }
        });

        $(document).on('mouseup', function() {
            if (isPanning) {
                isPanning = false;
                $('#tbp-manual-canvas-container').css('cursor', 'grab');
            }
        });

        // Zoom Slider
        $('#manual_canvas_zoom').on('input', function() {
            manualZoom = parseFloat($(this).val());
            updateManualZoom();
        });

        $('#btn_manual_reset_view').on('click', function() {
            manualZoom = 0.7;
            panOffset = { x: 50, y: 50 };
            $('#manual_canvas_zoom').val(manualZoom);
            updateManualZoom();
        });

        // Regenerar Snapshot
        $('#btn_manual_regenerate_snapshot').on('click', function() {
            const $btn = $(this);
            $btn.prop('disabled', true).text('⏳ Generando...');
            $.post(ajaxurl, {
                action: 'tbp_asientos_regenerate_snapshot',
                config_id: 0,
                nonce: '"dummy_nonce"'
            }, function(response) {
                $btn.prop('disabled', false).text('🔄 Snapshot Público');
                if (response.success) {
                    alert('✅ Snapshot actualizado.');
                } else {
                    alert('❌ Error: ' + response.data);
                }
            }).fail(function() {
                $btn.prop('disabled', false).text('🔄 Snapshot Público');
                alert('❌ Error de red.');
            });
        });

        // Cerrar Tooltip al hacer clic fuera
        $(document).on('click', function(e) {
            if ($(e.target).closest('.manual-table-item, #tbp-manual-table-tooltip').length === 0) {
                $('#tbp-manual-table-tooltip').hide();
            }
        });

        // Cancelar colocación
        $('#manual-cancel-placement-btn').on('click', function() {
            cancelPlacement();
        });

        // Cambios en filtros
        $('#manual-filter-group, #manual-filter-status').on('change', function() {
            renderManualOrders();
        });
        $('#manual-search-order').on('keyup', function() {
            renderManualOrders();
        });

        function updateManualZoom() {
            $('#tbp-manual-canvas-items').css({
                transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${manualZoom})`
            });
            $('#tbp-manual-canvas-grid').css({
                transform: `translate(${panOffset.x}px, ${panOffset.y}px) scale(${manualZoom})`
            });
        }

        function cancelPlacement() {
            selectedOrderId = null;
            $('#manual-placement-banner').hide();
            $('.manual-order-row').removeClass('selected').css('borderColor', '#e2e8f0');
        }

        function updateStats() {
            let assignedCount = 0;
            if (Array.isArray(manualOrders)) {
                manualOrders.forEach(o => {
                    if (o.assigned) assignedCount += o.qty;
                });
            }
            
            const totalCount = 0;
            const pendingCount = totalCount - assignedCount;
            const pct = totalCount > 0 ? Math.round((assignedCount / totalCount) * 100) : 0;
            
            $('#manual-total-assigned').text(assignedCount);
            $('#manual-total-pending').text(pendingCount);
            $('#manual-progress-pct').text(pct + '%');
            $('#manual-progress-bar').css('width', pct + '%');
        }

        function renderManualOrders() {
            const groupFilter = $('#manual-filter-group').val();
            const searchFilter = $('#manual-search-order').val().toLowerCase();
            const statusFilter = $('#manual-filter-status').val();
            const $container = $('#manual-orders-list-container');
            if (!Array.isArray(manualOrders)) return;
            $container.empty();

            const filtered = manualOrders.filter(o => {
                if (groupFilter && o.grupo !== groupFilter) return false;
                if (statusFilter === 'pending' && o.assigned) return false;
                if (statusFilter === 'assigned' && !o.assigned) return false;
                if (searchFilter) {
                    const nameMatch = o.nombre.toLowerCase().includes(searchFilter);
                    const idMatch = o.id.toString().includes(searchFilter);
                    const groupMatch = o.grupo.toLowerCase().includes(searchFilter);
                    if (!nameMatch && !idMatch && !groupMatch) return false;
                }
                return true;
            });

            if (filtered.length === 0) {
                $container.html('<div style="text-align:center; padding:20px; color:#888; font-style:italic;">No se encontraron pedidos.</div>');
                updateStats();
                return;
            }

            filtered.forEach(o => {
                const isSelected = selectedOrderId === o.id;
                const statusHtml = o.assigned 
                    ? `<span class="unassign-btn" data-id="${o.id}" style="background:#e8f5e9; color:#2e7d32; border:1px solid #4caf50; padding:2px 6px; border-radius:12px; font-size:10px; cursor:pointer; font-weight:bold;" title="Desasignar de: ${o.mesa_label}">🟢 ${o.mesa_label} ×</span>` 
                    : `<span style="background:#fef2f2; color:#ef4444; border:1px solid #fca5a5; padding:2px 6px; border-radius:12px; font-size:10px; font-weight:bold;">🔴 Pendiente</span>`;

                const html = $(`
                    <div class="manual-order-row ${isSelected ? 'selected' : ''}" data-id="${o.id}" style="background:#fff; border:1px solid ${isSelected ? '#3b82f6' : '#e2e8f0'}; border-radius:6px; padding:10px; margin-bottom:8px; cursor:pointer; transition:all 0.2s; box-shadow: 0 1px 2px rgba(0,0,0,0.02); display:flex; flex-direction:column; gap:4px;">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <span style="font-weight:bold; font-size:12px; color:#334155;">#${o.id} - ${o.nombre}</span>
                            <span style="font-weight:800; font-size:11px; background:#f1f5f9; padding:2px 6px; border-radius:4px; color:#475569;">${o.qty} lug.</span>
                        </div>
                        <div style="display:flex; justify-content:space-between; align-items:center; font-size:10px; color:#64748b; margin-top:2px;">
                            <span>Grupo: <strong>${o.grupo}</strong></span>
                            <div>${statusHtml}</div>
                        </div>
                    </div>
                `);

                html.on('click', function(e) {
                    if ($(e.target).hasClass('unassign-btn')) {
                        e.stopPropagation();
                        unassignOrder(o.id);
                        return;
                    }
                    
                    selectedOrderId = o.id;
                    $('.manual-order-row').removeClass('selected').css('borderColor', '#e2e8f0');
                    html.addClass('selected').css('borderColor', '#3b82f6');
                    
                    $('#manual-placement-banner-text').text(`Asignando pedido #${o.id} (${o.nombre} - ${o.qty} lugares)`);
                    $('#manual-placement-banner').css('display', 'flex');
                });

                $container.append(html);
            });

            updateStats();
        }

        function renderManualPlan() {
            const $items = $('#tbp-manual-canvas-items');
            $items.empty();

            // Dibujar decoración
            manualElements.forEach(item => {
                const color = item.color || '#334155';
                const style = `left:${item.pos_x}px; top:${item.pos_y}px; width:${item.width}px; height:${item.height}px; background:${color}; border-color:${color}; z-index:1; position:absolute; display:flex; align-items:center; justify-content:center; border:2px solid; border-radius:4px; opacity:0.65; color:#fff; font-weight:800; font-size:11px;`;
                const $el = $(`<div class="manual-canvas-element" style="${style}">${item.label}</div>`);
                $items.append($el);
            });

            // Dibujar mesas
            manualTables.forEach(m => {
                const isBlocked = m.tipo === 'bloqueada';
                const isFull = parseInt(m.capacidad_usada) >= parseInt(m.capacidad);
                const isPartial = parseInt(m.capacidad_usada) > 0 && !isFull;
                
                let bg = '#eff6ff';
                let border = '#3b82f6';
                let text = '#1d4ed8';
                let statusText = `${m.capacidad_usada}/${m.capacidad} PAX`;
                
                if (isBlocked) {
                    bg = '#fee2e2';
                    border = '#ef4444';
                    text = '#991b1b';
                    statusText = `🚫 BLOQ.`;
                } else if (isFull) {
                    bg = '#d1fae5';
                    border = '#10b981';
                    text = '#065f46';
                } else if (isPartial) {
                    bg = '#ffedd5';
                    border = '#f97316';
                    text = '#9a3412';
                }
                
                const isRound = m.tipo === 'round' || m.tipo === 'normal' || m.tipo === 'bar';
                const borderRadius = isRound ? '50%' : '4px';

                const style = `left:${m.pos_x}px; top:${m.pos_y}px; width:${m.width}px; height:${m.height}px; background:${bg}; border:2px solid ${border}; color:${text}; border-radius:${borderRadius}; position:absolute; display:flex; flex-direction:column; align-items:center; justify-content:center; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,0.1); user-select:none; z-index:10; font-family:-apple-system,BlinkMacSystemFont,sans-serif;`;
                
                const $el = $(
                    `<div class="manual-table-item" data-id="${m.id}" style="${style}">
                        <div style="font-weight:900; font-size:10px;">${m.numero}</div>
                        <div style="font-size:7px; font-weight:700; margin-top:2px;">${statusText}</div>
                    </div>`
                );

                $el.on('click', function(e) {
                    e.stopPropagation();
                    if (selectedOrderId) {
                        assignOrderToTable(selectedOrderId, m.id);
                    } else {
                        showTooltip(m, e);
                    }
                });

                $el.on('mouseenter', function(e) {
                    if (!selectedOrderId) {
                        showTooltip(m, e);
                    }
                });

                $items.append($el);
            });

            updateManualZoom();
        }

        function showTooltip(m, e) {
            const $tooltip = $('#tbp-manual-table-tooltip');
            const tableAssignedOrders = manualOrders.filter(o => o.mesa_id == m.id);

            let ordersHtml = '';
            if (m.tipo === 'bloqueada') {
                ordersHtml = `<p style="color:#ef4444; font-weight:bold; margin:5px 0 0 0;">🚫 Bloqueada: ${m.etiqueta_bloqueo || 'Sin etiqueta'}</p>`;
            } else if (tableAssignedOrders.length === 0) {
                ordersHtml = '<p style="color:#94a3b8; font-style:italic; margin:5px 0 0 0;">Mesa vacía</p>';
            } else {
                ordersHtml = '<ul style="margin:5px 0 0 0; padding-left:15px; max-height:150px; overflow-y:auto;">';
                tableAssignedOrders.forEach(o => {
                    ordersHtml += `
                        <li style="margin-bottom:6px; padding-bottom:4px; border-bottom:1px dashed #e2e8f0; display:flex; justify-content:space-between; align-items:center;">
                            <div>
                                <span style="font-weight:bold;">#${o.id}</span> - ${o.nombre}<br>
                                <span style="color:#64748b; font-size:9px;">Grupo: ${o.grupo} | <strong>${o.qty} lug.</strong></span>
                            </div>
                            <button type="button" class="tooltip-unassign-btn" data-id="${o.id}" style="background:#fee2e2; border:none; color:#ef4444; padding:2px 6px; border-radius:4px; cursor:pointer; font-weight:bold;" title="Desasignar">🗑️</button>
                        </li>
                    `;
                });
                ordersHtml += '</ul>';
            }

            const html = `
                <div style="font-weight:bold; font-size:12px; border-bottom:1px solid #e2e8f0; padding-bottom:5px; margin-bottom:5px; display:flex; justify-content:space-between;">
                    <span>${m.zona} - Mesa ${m.numero}</span>
                    <span>${m.capacidad_usada}/${m.capacidad} PAX</span>
                </div>
                ${ordersHtml}
            `;

            $tooltip.html(html).show();

            const containerRect = $('#tbp-manual-canvas-container')[0].getBoundingClientRect();
            const x = e.clientX - containerRect.left + 15;
            const y = e.clientY - containerRect.top + 15;
            
            $tooltip.css({
                left: x + 'px',
                top: y + 'px'
            });

            $tooltip.find('.tooltip-unassign-btn').off('click').on('click', function(evt) {
                evt.stopPropagation();
                const orderId = $(this).data('id');
                $tooltip.hide();
                unassignOrder(orderId);
            });
        }

        function assignOrderToTable(orderId, mesaId) {
            const mesa = manualTables.find(t => t.id == mesaId);
            const order = manualOrders.find(o => o.id == orderId);
            if (!mesa || !order) return;

            $.post(ajaxurl, {
                action: 'tbp_asientos_manual_assign',
                config_id: 0,
                order_id: orderId,
                mesa_id: mesaId,
                nonce: '"dummy_nonce"'
            }, function(response) {
                if (response.success) {
                    response.data.mesas.forEach(updatedMesa => {
                        const m = manualTables.find(t => t.id == updatedMesa.id);
                        if (m) {
                            m.capacidad_usada = updatedMesa.capacidad_usada;
                        }
                    });
                    
                    order.assigned = true;
                    order.mesa_id = mesaId;
                    order.mesa_label = `${mesa.zona} - Mesa ${mesa.numero}`;
                    
                    // Auto-avanzar al siguiente pedido pendiente del grupo seleccionado o general
                    const currentGroup = $('#manual-filter-group').val();
                    let nextOrder = null;
                    if (currentGroup) {
                        nextOrder = manualOrders.find(o => o.grupo === currentGroup && !o.assigned && o.id !== orderId);
                    } else {
                        nextOrder = manualOrders.find(o => !o.assigned && o.id !== orderId);
                    }

                    if (nextOrder) {
                        selectedOrderId = nextOrder.id;
                        $('#manual-placement-banner-text').text(`Asignando pedido #${nextOrder.id} (${nextOrder.nombre} - ${nextOrder.qty} lugares)`);
                        $('#manual-placement-banner').css('display', 'flex');
                    } else {
                        cancelPlacement();
                    }

                    renderManualOrders();
                    renderManualPlan();
                } else {
                    alert('❌ Error: ' + response.data);
                }
            }).fail(function() {
                alert('❌ Error de red al asignar.');
            });
        }

        function unassignOrder(orderId) {
            if (!confirm('¿Desea desasignar este pedido de la mesa?')) return;
            
            $.post(ajaxurl, {
                action: 'tbp_asientos_manual_unassign',
                config_id: 0,
                order_id: orderId,
                nonce: '"dummy_nonce"'
            }, function(response) {
                if (response.success) {
                    const order = manualOrders.find(o => o.id === orderId);
                    if (order) {
                        order.assigned = false;
                        order.mesa_id = 0;
                        order.mesa_label = '';
                    }
                    const updatedMesa = response.data.mesa;
                    const m = manualTables.find(t => t.id == updatedMesa.id);
                    if (m) {
                        m.capacidad_usada = updatedMesa.capacidad_usada;
                    }
                    
                    if (selectedOrderId === orderId) {
                        cancelPlacement();
                    }
                    
                    renderManualOrders();
                    renderManualPlan();
                } else {
                    alert('❌ Error: ' + response.data);
                }
            }).fail(function() {
                alert('❌ Error de red al desasignar.');
            });
        }

        function renderManualWorkspace() {
            renderManualOrders();
            renderManualPlan();
            updateManualZoom();
        }
        

        if ($('#event_id').val()) {
            refreshGroupFields($('#event_id').val());
        }

        $('#event_id').on('change', function() {
            refreshGroupFields($(this).val());
        });
    });
    