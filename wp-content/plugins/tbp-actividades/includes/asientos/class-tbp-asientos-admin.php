<?php
/**
 * UI Admin: Módulo de Asignación de Asientos
 *
 * Interfaz administrativa para crear y gestionar configuraciones de asientos,
 * definir zonas, subir planos y ejecutar el algoritmo.
 *
 * @package TBP_Actividades
 * @since   2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Cargar componentes visuales
require_once plugin_dir_path( __FILE__ ) . 'class-tbp-asientos-visual.php';

/**
 * Registra y renderiza la página principal del menú Asientos.
 */
function tbp_asientos_admin_page() {
    if ( ! current_user_can( 'manage_woocommerce' ) ) {
        wp_die( __( 'No tienes permisos suficientes para acceder a esta página.' ) );
    }

    // Procesar acciones de formulario si vienen por POST
    if ( isset( $_POST['tbp_asientos_action'] ) ) {
        if ( $_POST['tbp_asientos_action'] === 'save_config' ) {
            check_admin_referer( 'tbp_asientos_save_config' );
            tbp_asientos_handle_save_config();
        } elseif ( $_POST['tbp_asientos_action'] === 'delete_config' ) {
            check_admin_referer( 'tbp_asientos_delete_config' );
            tbp_asientos_handle_delete_config();
        }
    }

    $action = $_GET['action'] ?? 'list';
    $config_id = isset( $_GET['id'] ) ? (int) $_GET['id'] : 0;

    echo '<div class="wrap tbp-asientos-wrap">';
    echo '<h1 class="wp-heading-inline">🪑 Asignación de Asientos (Grupal)</h1>';

    switch ( $action ) {
        case 'edit':
        case 'new':
            tbp_asientos_render_edit_view( $config_id );
            break;
        case 'view':
            tbp_asientos_render_results_view( $config_id );
            break;
        case 'list':
        default:
            tbp_asientos_render_list_view();
            break;
    }

    echo '</div>';
}

/**
 * Vista 1: Lista de configuraciones existentes
 */
function tbp_asientos_render_list_view() {
    global $wpdb;

    echo '<a href="?page=tbp-actividades-asientos&action=new" class="page-title-action">Crear Nueva Configuración</a>';
    echo '<hr class="wp-header-end">';

    // Obtener todas las configuraciones
    $configs = $wpdb->get_results( "SELECT c.*, p.post_title as event_name FROM {$wpdb->prefix}tbp_seat_configurations c LEFT JOIN {$wpdb->posts} p ON p.ID = c.event_id ORDER BY c.created_at DESC" );

    if ( empty( $configs ) ) {
        echo '<p>No hay configuraciones de asientos creadas aún.</p>';
        return;
    }

    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead><tr>';
    echo '<th>ID</th>';
    echo '<th>Nombre</th>';
    echo '<th>Evento</th>';
    echo '<th>Estado</th>';
    echo '<th>Fecha Creación</th>';
    echo '<th>Acciones</th>';
    echo '</tr></thead>';
    echo '<tbody>';

    foreach ( $configs as $config ) {
        $status_badge = '';
        if ( $config->status === 'active' ) {
            $status_badge = '<span style="color:green;font-weight:bold;">✅ Asignado</span>';
        } else {
            $status_badge = '<span style="color:#888;">⏳ Borrador</span>';
        }

        echo '<tr>';
        echo '<td>' . esc_html( $config->id ) . '</td>';
        echo '<td><strong>' . esc_html( $config->nombre ) . '</strong></td>';
        echo '<td>' . esc_html( $config->event_name ? $config->event_name : 'Evento #' . $config->event_id ) . '</td>';
        echo '<td>' . $status_badge . '</td>';
        echo '<td>' . esc_html( date_i18n( get_option( 'date_format' ), strtotime( $config->created_at ) ) ) . '</td>';
        echo '<td>';
        echo '<div style="display:flex; gap: 5px; align-items:center;">';
        if ( $config->status === 'active' ) {
            echo '<a href="?page=tbp-actividades-asientos&action=view&id=' . $config->id . '" class="button button-primary button-small">Ver Asignaciones</a>';
            echo '<a href="?page=tbp-actividades-asientos&action=edit&id=' . $config->id . '" class="button button-small">Re-configurar</a>';
        } else {
            echo '<a href="?page=tbp-actividades-asientos&action=edit&id=' . $config->id . '" class="button button-primary button-small">Editar / Asignar</a>';
        }
        
        // Formulario de eliminación
        echo '<form method="post" action="" style="display:inline; margin:0; padding:0;" onsubmit="return confirm(\'⚠️ ¿ESTÁS SEGURO? Se eliminará la configuración y TODAS las mesas y asignaciones ligadas. Esto no se puede deshacer.\');">';
        wp_nonce_field( 'tbp_asientos_delete_config' );
        echo '<input type="hidden" name="tbp_asientos_action" value="delete_config">';
        echo '<input type="hidden" name="config_id" value="' . esc_attr( $config->id ) . '">';
        echo '<button type="submit" class="button button-small" style="color:#b32d2e; border-color:#b32d2e;" title="Eliminar">🗑️</button>';
        echo '</form>';
        
        echo '</div>';
        echo '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
}

/**
 * Vista 2: Formulario de Creación / Edición
 */
function tbp_asientos_render_edit_view( $config_id = 0 ) {
    $config = null;
    if ( $config_id > 0 ) {
        $config = tbp_asientos_get_config( $config_id );
        if ( ! $config ) {
            echo '<div class="notice notice-error"><p>Configuración no encontrada.</p></div>';
            return;
        }
    }

    // Valores por defecto
    $nombre       = $config ? $config->nombre : '';
    $event_id     = $config ? $config->event_id : 0;
    $proveedor_id = $config && isset($config->proveedor_id) ? $config->proveedor_id : 0;
    $group_field  = $config ? $config->group_field : 'Grupo';
    $zonas        = $config ? $config->zonas_config : array();

    // Obtener eventos próximos
    $eventos = get_posts( array(
        'post_type'      => 'tribe_events',
        'posts_per_page' => 50,
        'post_status'    => 'publish',
        'orderby'        => 'meta_value',
        'meta_key'       => '_EventStartDate',
        'order'          => 'DESC'
    ) );

    // Obtener proveedores
    $proveedores = get_posts( array(
        'post_type'      => 'tbp_proveedor',
        'posts_per_page' => -1,
        'post_status'    => 'publish',
        'orderby'        => 'title',
        'order'          => 'ASC'
    ) );

    ?>
    <hr class="wp-header-end">

    <!-- NAVEGACIÓN DE ETAPAS -->
    <div class="tbp-stage-nav" style="display:flex; gap:10px; margin: 20px 0; background:#fff; padding:10px; border-radius:8px; border:1px solid #ccd0d4; box-shadow:0 1px 1px rgba(0,0,0,0.04);">
        <div class="stage-item active" data-stage="1" style="flex:1; text-align:center; padding:15px; border-radius:6px; cursor:pointer; font-weight:800; background:#f0f6fb; color:#2271b1; border:1px solid #2271b1;">1. Configuración de Metadatos</div>
        <div class="stage-item" data-stage="2" style="flex:1; text-align:center; padding:15px; border-radius:6px; cursor:pointer; font-weight:800; color:#64748b;">2. Procesamiento del Plano</div>
        <div class="stage-item" data-stage="3" style="flex:1; text-align:center; padding:15px; border-radius:6px; cursor:pointer; font-weight:800; color:#64748b;">3. Generación y Asignación</div>
    </div>

    <div id="tbp-stage-1" class="tbp-stage-content">
    <form method="post" action="" id="tbp-asientos-form">
        <?php wp_nonce_field( 'tbp_asientos_save_config' ); ?>
        <input type="hidden" name="tbp_asientos_action" value="save_config">
        <input type="hidden" name="config_id" value="<?php echo esc_attr( $config_id ); ?>">
        <input type="hidden" name="zonas_json" id="zonas_json" value="<?php echo esc_attr( wp_json_encode( $zonas ) ); ?>">

        <div style="display:flex; gap: 20px;">
            <!-- COLUMNA IZQUIERDA: Settings -->
            <div style="flex: 1; max-width: 400px;">
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle">1. Configuración General</h2></div>
                    <div class="inside">
                        <p>
                            <label><strong>Nombre de la Configuración</strong></label><br>
                            <input type="text" name="nombre" value="<?php echo esc_attr( $nombre ); ?>" class="large-text" required placeholder="Ej. Prepa 15 Cintermex">
                        </p>
                        <p>
                            <label><strong>Evento</strong></label><br>
                            <select name="event_id" id="event_id" style="width:100%;" required>
                                <option value="">-- Seleccionar Evento --</option>
                                <?php foreach ( $eventos as $ev ) : ?>
                                    <option value="<?php echo $ev->ID; ?>" <?php selected( $event_id, $ev->ID ); ?>><?php echo esc_html( $ev->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </p>
                        <p>
                            <label><strong>Proveedor (Ligado a Egresos)</strong></label><br>
                            <select name="proveedor_id" id="proveedor_id" style="width:100%;" required>
                                <option value="">-- Seleccionar Proveedor --</option>
                                <?php foreach ( $proveedores as $prov ) : ?>
                                    <option value="<?php echo $prov->ID; ?>" <?php selected( $proveedor_id, $prov->ID ); ?>><?php echo esc_html( $prov->post_title ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <br><small>Los asientos se asignarán matemáticamente a la cantidad de platillos cobrados por este proveedor, ignorando el checkbox manual.</small>
                        </p>
                        <p>
                            <label><strong>Campo de Agrupación (Metadato)</strong></label><br>
                            <select name="group_field" id="group_field" style="width:100%;" required>
                                <option value="">-- Seleccionar Evento primero --</option>
                                <?php if ( $group_field ) : ?>
                                    <option value="<?php echo esc_attr( $group_field ); ?>" selected><?php echo esc_html( $group_field ); ?></option>
                                <?php endif; ?>
                            </select>
                            <br><small>Es el campo exacto del ticket que define el grupo.</small>
                        </p>
                    </div>
                </div>

                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle">Acciones</h2></div>
                    <div class="inside">
                        <p>
                            <button type="submit" class="button button-primary button-large" style="width:100%;">💾 Guardar Configuración y Mesas</button>
                        </p>
                        <?php if ( $config_id > 0 ) : ?>
                            <hr>
                            <div style="margin-bottom:15px;">
                                <button type="button" id="btn_scan_event" class="button button-large" style="width:100%; border-color:#2271b1; color:#2271b1;">🔍 1. Escanear Asistentes</button>
                                <div id="scan_results" style="display:none; background:#e8f5e9; border:1px solid #4caf50; padding:10px; margin-top:10px; border-radius:4px; font-size:13px;">
                                    <strong>✅ Escaneo Completo</strong><br>
                                    Asistentes: <strong id="scan_asistentes">0</strong><br>
                                    Grupos: <strong id="scan_grupos">0</strong><br>
                                    Pedidos: <strong id="scan_pedidos">0</strong>
                                </div>
                                <div id="scan_loader" style="display:none; text-align:center; padding:10px; font-size:12px;">⏳ Extrayendo metadatos (puede tardar minutos)...</div>
                            </div>
                            <p>
                                <button type="button" id="btn_run_packing" class="button button-large" style="width:100%; background:#2271b1; color:white; border-color:#2271b1;" disabled>▶ 2. Ejecutar Asignación</button>
                            </p>
                            <p style="text-align:center;"><small>Sobreescribirá las asignaciones actuales.</small></p>
                            <div id="packing_loader" style="display:none; text-align:center; padding:10px;">⏳ Asignando lugares...</div>
                            <hr>
                            <div style="background:#fff9e6; border:1px solid #ffcc00; padding:12px; border-radius:6px; margin-bottom:15px;">
                                <h4 style="margin:0 0 5px 0;">🛡️ Consulta Pública (Anti-Colapso)</h4>
                                <p style="margin:0 0 10px 0; font-size:11px;">El sistema genera un archivo JSON estático para que el sitio no se caiga cuando miles de alumnos consultan sus mesas.</p>
                                <button type="button" id="btn_regenerate_snapshot" class="button button-small" style="width:100%;">🔄 Regenerar Snapshot Ahora</button>
                                <div id="snapshot_loader" style="display:none; text-align:center; padding:8px; font-size:11px;">⏳ Generando JSON...</div>
                            </div>
                            <hr>
                            <p style="text-align:center;">
                                <button type="button" class="button button-link-delete" id="btn_delete_config" style="color:#b32d2e; text-decoration:underline;">🗑️ Eliminar Configuración</button>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            </div>
        </div>
    </form>
    </div>

    <div id="tbp-stage-2" class="tbp-stage-content" style="display:none;">
        <?php tbp_asientos_render_visual_editor( $config_id ); ?>
    </div>

    <div id="tbp-stage-3" class="tbp-stage-content" style="display:none;">
        <?php if ( $config_id > 0 && $config ) : 
            // Cargar datos necesarios para asignación manual
            $existing_elements = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tbp_seat_elements WHERE config_id = %d", $config_id ) );
            $existing_tables = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tbp_seat_tables WHERE config_id = %d", $config_id ) );
            
            // Usamos la obtención HPOS-safe
            $order_ids = tbp_asientos_get_orders_for_event( $config->event_id );
            
            $orders_data = [];
            $total_places_registered = 0;
            $total_places_assigned = 0;
            
            foreach ( $order_ids as $order_id ) {
                $order = wc_get_order( $order_id );
                if ( ! $order ) continue;
                $qty = tbp_get_order_seat_qty( $order_id, (int) $config->proveedor_id );
                if ( $qty <= 0 ) continue;

                $total_places_registered += $qty;

                $nombre = $order->get_billing_first_name();
                $apellidos = $order->get_billing_last_name();
                $grupo = tbp_asientos_get_order_group_value( $order_id, $config->group_field );
                if ( empty( $grupo ) ) {
                    $grupo = 'Sin grupo';
                }
                
                $assignment = $wpdb->get_row( $wpdb->prepare(
                    "SELECT a.id, a.mesa_id, t.numero, t.zona FROM {$wpdb->prefix}tbp_seat_assignments a
                     LEFT JOIN {$wpdb->prefix}tbp_seat_tables t ON t.id = a.mesa_id
                     WHERE a.config_id = %d AND a.order_id = %d",
                     $config_id, $order_id
                ) );

                if ( $assignment ) {
                    $total_places_assigned += $qty;
                }

                $orders_data[] = [
                    'id'         => $order_id,
                    'nombre'     => trim( $nombre . ' ' . $apellidos ),
                    'grupo'      => $grupo,
                    'qty'        => $qty,
                    'assigned'   => $assignment ? true : false,
                    'mesa_id'    => $assignment ? (int) $assignment->mesa_id : 0,
                    'mesa_label' => $assignment ? $assignment->zona . ' - Mesa ' . $assignment->numero : '',
                ];
            }

            // Agrupar por grupo para obtener conteo total de asientos de cada grupo
            $group_totals = [];
            foreach ( $orders_data as $od ) {
                $g = $od['grupo'];
                if ( ! isset( $group_totals[$g] ) ) {
                    $group_totals[$g] = 0;
                }
                $group_totals[$g] += $od['qty'];
            }
            arsort( $group_totals );
        ?>
            <!-- Sub-navegación dentro de Etapa 3 -->
            <div class="tbp-stage-3-nav" style="display:flex; border-bottom:2px solid #ddd; margin-bottom:20px; gap:5px;">
                <button type="button" class="tbp-stage3-tab-btn active" data-target="tbp-stage3-auto" style="padding:10px 20px; background:none; border:none; border-bottom:3px solid #2271b1; font-weight:bold; cursor:pointer; color:#2271b1;">🤖 Asignación Automática</button>
                <button type="button" class="tbp-stage3-tab-btn" data-target="tbp-stage3-manual" style="padding:10px 20px; background:none; border:none; font-weight:bold; cursor:pointer; color:#64748b;">✍️ Asignación Manual</button>
            </div>

            <!-- TAB 1: AUTO -->
            <div id="tbp-stage3-auto" class="tbp-stage3-tab-content">
                <div class="postbox">
                    <div class="postbox-header"><h2 class="hndle">1. Asignación Automática Inteligente</h2></div>
                    <div class="inside">
                        <p>El sistema tomará los grupos del listado y los acomodará en las mesas disponibles del plano utilizando el algoritmo de empaquetamiento (packing).</p>
                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top:15px;">
                            <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
                                <h3 style="margin-top:0;">🤖 Ejecutar Motor</h3>
                                <p>Presiona el botón para procesar automáticamente todos los pedidos elegibles para el proveedor seleccionado.</p>
                                <button type="button" id="btn_run_packing_v2" class="button button-primary button-large">Ejecutar Asignación Inteligente</button>
                            </div>
                            <div style="background:#f8fafc; padding:20px; border-radius:8px; border:1px solid #e2e8f0;">
                                <h3 style="margin-top:0;">👀 Panel de Control</h3>
                                <p>Ver el resultado de la asignación y realizar ajustes finos mediante la lista tradicional.</p>
                                <a href="?page=tbp-actividades-asientos&action=view&id=<?php echo $config_id; ?>" class="button button-large">Abrir Panel de Control del Evento</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: MANUAL -->
            <div id="tbp-stage3-manual" class="tbp-stage3-tab-content" style="display:none;">
                <div class="tbp-manual-workspace" style="display:flex; gap:20px; background:#fff; border:1px solid #ccd0d4; border-radius:8px; box-shadow:0 1px 1px rgba(0,0,0,0.04); overflow:hidden; min-height:650px;">
                    
                    <!-- SIDEBAR IZQUIERDO -->
                    <div class="tbp-manual-sidebar" style="width:400px; border-right:1px solid #ddd; background:#f8fafc; display:flex; flex-direction:column;">
                        <!-- Métricas del Evento -->
                        <div style="padding:15px; border-bottom:1px solid #eee; background:#fff;">
                            <h4 style="margin:0 0 10px 0; font-size:14px; display:flex; justify-content:space-between; align-items:center;">
                                <span>📊 Progreso Asignación</span>
                                <span id="manual-progress-pct" style="background:#e0f2fe; color:#0369a1; padding:2px 6px; border-radius:12px; font-size:11px;">0%</span>
                            </h4>
                            <div style="height:6px; background:#e2e8f0; border-radius:3px; overflow:hidden; margin-bottom:10px;">
                                <div id="manual-progress-bar" style="width:0%; height:100%; background:#2271b1; transition:width 0.3s;"></div>
                            </div>
                            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; font-size:11px; text-align:center;">
                                <div style="background:#f1f5f9; padding:6px; border-radius:4px;">
                                    <span style="color:#64748b; display:block;">Asignados</span>
                                    <strong id="manual-total-assigned" style="font-size:14px; color:#2271b1;"><?php echo $total_places_assigned; ?></strong> <span style="color:#64748b;">/ <span class="manual-total-registered-val"><?php echo $total_places_registered; ?></span></span>
                                </div>
                                <div style="background:#f1f5f9; padding:6px; border-radius:4px;">
                                    <span style="color:#64748b; display:block;">Pendientes</span>
                                    <strong id="manual-total-pending" style="font-size:14px; color:#ef4444;"><?php echo ($total_places_registered - $total_places_assigned); ?></strong>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div style="padding:15px; border-bottom:1px solid #eee; background:#f8fafc; display:flex; flex-direction:column; gap:10px;">
                            <div>
                                <label style="font-size:11px; font-weight:bold; display:block; margin-bottom:4px;">Filtrar por Grupo</label>
                                <select id="manual-filter-group" style="width:100%;">
                                    <option value="">-- Todos los Grupos --</option>
                                    <?php foreach ( $group_totals as $grp_name => $grp_total ) : ?>
                                        <option value="<?php echo esc_attr( $grp_name ); ?>"><?php echo esc_html( $grp_name ); ?> (<?php echo $grp_total; ?> lugares)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <div style="flex:1;">
                                    <input type="text" id="manual-search-order" placeholder="Buscar cliente o #pedido..." style="width:100%; height:30px;">
                                </div>
                                <select id="manual-filter-status" style="width:120px; height:30px; font-size:11px;">
                                    <option value="all">Todos</option>
                                    <option value="pending" selected>Pendientes</option>
                                    <option value="assigned">Asignados</option>
                                </select>
                            </div>
                        </div>

                        <!-- Listado de Pedidos -->
                        <div id="manual-orders-list-container" style="flex:1; overflow-y:auto; max-height:480px; padding:10px;">
                            <!-- Dynamic Content -->
                        </div>
                    </div>

                    <!-- AREA DERECHA: PLANO INTERACTIVO -->
                    <div class="tbp-manual-workspace-main" style="flex:1; display:flex; flex-direction:column; background:#f1f5f9; position:relative; overflow:hidden;">
                        <!-- Banner Superior de Instrucción / Colocación -->
                        <div id="manual-placement-banner" style="background:#0284c7; color:white; padding:10px 15px; font-weight:bold; display:none; justify-content:space-between; align-items:center; z-index:10;">
                            <div>
                                <span style="font-size:12px; background:rgba(255,255,255,0.2); padding:2px 6px; border-radius:3px; margin-right:8px;">Modo Colocación</span>
                                <span id="manual-placement-banner-text">Asignando pedido #123 (Juan Perez - 5 lugares)</span>
                            </div>
                            <button type="button" id="manual-cancel-placement-btn" style="background:rgba(255,255,255,0.2); border:none; color:white; padding:3px 8px; border-radius:4px; font-size:11px; cursor:pointer; font-weight:bold;">Cancelar</button>
                        </div>

                        <!-- Controles de Canvas -->
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:10px 15px; background:#fff; border-bottom:1px solid #e2e8f0; z-index:5;">
                            <div style="display:flex; align-items:center; gap:10px;">
                                <label style="font-size:11px; font-weight:bold;">Zoom:</label>
                                <input type="range" id="manual_canvas_zoom" style="width:100px;" min="0.2" max="2" step="0.1" value="0.7">
                                <button type="button" id="btn_manual_reset_view" class="button button-small">Restablecer Vista</button>
                            </div>
                            <div style="display:flex; gap:8px;">
                                <button type="button" id="btn_manual_regenerate_snapshot" class="button button-small">🔄 Snapshot Público</button>
                                <a href="?page=tbp-actividades-asientos&action=view&id=<?php echo $config_id; ?>" target="_blank" class="button button-small">👀 Ver Listado de Asignaciones</a>
                            </div>
                        </div>

                        <!-- LIENZO DE ASIGNACIÓN -->
                        <div id="tbp-manual-canvas-container" style="position:relative; width:100%; height:550px; overflow:hidden; cursor:grab;">
                            <div id="tbp-manual-canvas-grid" style="position:absolute; top:0; left:0; width:3000px; height:3000px; background-image: radial-gradient(#cbd5e1 1.2px, transparent 1.2px); background-size: 24px 24px;"></div>
                            <div id="tbp-manual-canvas-items" style="position:absolute; top:0; left:0; width:100%; height:100%; transform-origin: 0 0;">
                                <!-- Elementos dibujados dinámicamente -->
                            </div>
                        </div>

                        <!-- Tooltip Flotante para Mesas -->
                        <div id="tbp-manual-table-tooltip" style="position:absolute; display:none; background:#fff; border:1px solid #cbd5e1; box-shadow:0 10px 15px -3px rgba(0,0,0,0.1),0 4px 6px -2px rgba(0,0,0,0.05); padding:12px; border-radius:8px; width:280px; z-index:9999; font-size:11px; pointer-events:auto;">
                            <!-- Tooltip dynamic HTML -->
                        </div>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <p><em>Debes guardar la configuración primero para acceder a esta etapa.</em></p>
        <?php endif; ?>
    </div>

    <style>
        .tbp-zona-box { border: 1px solid #ccd0d4; background: #fff; padding: 15px; margin-bottom: 15px; position: relative; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .tbp-zona-box h4 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; }
        .tbp-zona-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom:10px; }
        .btn-remove-zona { color: #a00; cursor: pointer; text-decoration:none; }
        .btn-remove-zona:hover { color: red; }
        
        .tbp-stage3-tab-btn:focus { outline: none; box-shadow: none; }
        .tbp-stage3-tab-btn:hover { color: #2271b1 !important; }
        .manual-order-row:hover { background: #f1f5f9 !important; border-color: #cbd5e1 !important; }
        .manual-order-row.selected { background: #eff6ff !important; border-color: #3b82f6 !important; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important; }
        .manual-table-item { transition: transform 0.15s, box-shadow 0.15s; }
        .manual-table-item:hover { transform: scale(1.08); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05) !important; z-index: 50 !important; }
        .manual-canvas-element { user-select: none; pointer-events: none; }
    </style>

    <script>
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
            const configId = <?php echo $config_id; ?>;
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#btn_run_packing').prop('disabled', true);
            $('#scan_results').hide();
            $('#scan_loader').show().html('⏳ Iniciando escaneo...');

            // Paso 1: Init (Obtener todos los IDs)
            $.post(ajaxurl, {
                action: 'tbp_asientos_scan_init',
                config_id: configId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
                        nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
                        nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
            
            const configId = <?php echo $config_id; ?>;
            const $btn = $(this);
            const originalText = $btn.text();
            
            $('#btn_run_packing, #btn_run_packing_v2').prop('disabled', true);
            $btn.text('⏳ Asignando...');
            $('#packing_loader').show();

            $.post(ajaxurl, {
                action: 'tbp_asientos_run_packing',
                config_id: configId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
            $('#tbp-asientos-form').append('<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce("tbp_asientos_delete_config"); ?>">');
            $('input[name="tbp_asientos_action"]').val('delete_config');
            $('#tbp-asientos-form').submit();
        });

        // Regenerar Snapshot Manualmente
        $('#btn_regenerate_snapshot').on('click', function() {
            const configId = <?php echo $config_id; ?>;
            const $btn = $(this);
            $btn.prop('disabled', true);
            $('#snapshot_loader').show();

            $.post(ajaxurl, {
                action: 'tbp_asientos_regenerate_snapshot',
                config_id: configId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
        const currentGroupField = '<?php echo esc_js($group_field); ?>';

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
        <?php if ( $config_id > 0 ) : ?>
        let manualOrders = <?php echo wp_json_encode( $orders_data ) ?: '[]'; ?>;
        let manualTables = <?php echo wp_json_encode( $existing_tables ) ?: '[]'; ?>;
        let manualElements = <?php echo wp_json_encode( $existing_elements ) ?: '[]'; ?>;
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
                config_id: <?php echo (int) $config_id; ?>,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
            manualOrders.forEach(o => {
                if (o.assigned) assignedCount += o.qty;
            });
            
            const totalCount = <?php echo isset($total_places_registered) ? (int) $total_places_registered : 0; ?>;
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
                config_id: <?php echo (int) $config_id; ?>,
                order_id: orderId,
                mesa_id: mesaId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
                config_id: <?php echo (int) $config_id; ?>,
                order_id: orderId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
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
        <?php endif; ?>

        if ($('#event_id').val()) {
            refreshGroupFields($('#event_id').val());
        }

        $('#event_id').on('change', function() {
            refreshGroupFields($(this).val());
        });
    });
    </script>
    <?php
}

/**
 * Procesa el formulario de guardado
 */
function tbp_asientos_handle_save_config() {
    $config_id = isset( $_POST['config_id'] ) ? (int) $_POST['config_id'] : 0;
    
    $zonas_json = stripslashes( $_POST['zonas_json'] ?? '[]' );
    $zonas_config = json_decode( $zonas_json, true );

    if ( ! is_array( $zonas_config ) ) {
        $zonas_config = array();
    }

    $data = array(
        'event_id'     => (int) $_POST['event_id'],
        'proveedor_id' => (int) ($_POST['proveedor_id'] ?? 0),
        'nombre'       => sanitize_text_field( $_POST['nombre'] ),
        'group_field'  => sanitize_text_field( $_POST['group_field'] ),
        'zonas_config' => $zonas_config,
    );

    $saved_id = tbp_asientos_save_config( $data, $config_id > 0 ? $config_id : null );

    if ( $saved_id ) {
        // Generar mesas físicas en la BD
        tbp_asientos_generate_tables( $saved_id, $zonas_config );
        
        $url = admin_url( 'admin.php?page=tbp-actividades-asientos&action=edit&id=' . $saved_id . '&updated=1' );
        wp_safe_redirect( $url );
        exit;
    } else {
        global $wpdb;
        $err = $wpdb->last_error ? $wpdb->last_error : 'No se modificaron datos o error desconocido.';
        wp_die( '<h1>Error de Base de Datos</h1><p>Error al guardar la configuración: ' . esc_html($err) . '</p><a href="javascript:history.back()">Volver</a>' );
    }
}

/**
 * Procesa la eliminación de una configuración
 */
function tbp_asientos_handle_delete_config() {
    $config_id = isset( $_POST['config_id'] ) ? (int) $_POST['config_id'] : 0;
    if ( $config_id ) {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'tbp_seat_configurations', array( 'id' => $config_id ) );
        $wpdb->delete( $wpdb->prefix . 'tbp_seat_tables', array( 'config_id' => $config_id ) );
        $wpdb->delete( $wpdb->prefix . 'tbp_seat_assignments', array( 'config_id' => $config_id ) );
        $wpdb->delete( $wpdb->prefix . 'tbp_seat_group_zones', array( 'config_id' => $config_id ) );
        delete_transient( 'tbp_seat_scan_' . $config_id );

        $url = admin_url( 'admin.php?page=tbp-actividades-asientos&deleted=1' );
        wp_safe_redirect( $url );
        exit;
    }
}

/**
 * Vista 3: Resultados (Ver Asignaciones)
 */
function tbp_asientos_render_results_view( $config_id ) {
    $config = tbp_asientos_get_config( $config_id );
    if ( ! $config ) {
        echo '<div class="notice notice-error"><p>Configuración no encontrada.</p></div>';
        return;
    }

    $asignaciones = tbp_asientos_get_assignments( $config_id );
    $mesas = tbp_asientos_get_tables( $config_id );

    echo '<div style="display:flex; justify-content:space-between; align-items:center;">';
    echo '<a href="?page=tbp-actividades-asientos&action=edit&id=' . $config_id . '" class="page-title-action">← Volver a Configuración</a>';
    
    // Botones de exportación
    echo '<div>';
    echo '<a href="?page=tbp-actividades-asientos&tbp_export_asientos=asistentes&config_id=' . $config_id . '" class="button" style="margin-right:10px;">📊 Exportar Lista de Asistentes (CSV)</a>';
    echo '<a href="?page=tbp-actividades-asientos&tbp_export_asientos=mesas&config_id=' . $config_id . '" class="button">🪑 Exportar Estado de Mesas (CSV)</a>';
    echo '</div>';
    
    echo '</div>';
    echo '<hr class="wp-header-end">';
    
    echo '<h2>Resultados: ' . esc_html( $config->nombre ) . '</h2>';

    // Construir mapa de mesas para renderizar rápido
    $mesas_map = array();
    foreach ( $mesas as $m ) {
        if ( ! isset( $mesas_map[$m->zona] ) ) $mesas_map[$m->zona] = array();
        $m->asignaciones = array();
        $mesas_map[$m->zona][$m->numero] = $m;
    }

    foreach ( $asignaciones as $a ) {
        if ( isset( $mesas_map[$a->zona][$a->numero] ) ) {
            $mesas_map[$a->zona][$a->numero]->asignaciones[] = $a;
        }
    }

    echo '<div style="margin-top:20px;">';
    foreach ( $mesas_map as $zona_nombre => $mesas_zona ) {
        echo '<h3>' . esc_html( $zona_nombre ) . '</h3>';
        echo '<div style="display:flex; flex-wrap:wrap; gap:15px; margin-bottom:30px;">';
        
        foreach ( $mesas_zona as $numero => $mesa ) {
            $is_full = $mesa->capacidad_usada >= $mesa->capacidad;
            $bg_color = $is_full ? '#e8f5e9' : '#fff'; // verde claro si está llena
            $border = $is_full ? '#4caf50' : '#ccc';

            if ( $mesa->tipo === 'bloqueada' ) {
                $bg_color = '#ffebee';
                $border = '#f44336';
            }

            echo '<div style="border:1px solid ' . $border . '; background:' . $bg_color . '; border-radius:4px; padding:10px; width:250px; box-shadow:0 1px 2px rgba(0,0,0,0.05); position:relative;">';
            echo '<div style="border-bottom:1px solid #ddd; padding-bottom:5px; margin-bottom:5px; display:flex; justify-content:space-between; align-items:center;">';
            echo '<strong>Mesa ' . esc_html( $numero ) . '</strong>';
            echo '<span style="font-size:12px; font-weight:bold; color:' . ($is_full ? '#2e7d32' : '#666') . ';">' . $mesa->capacidad_usada . '/' . $mesa->capacidad . '</span>';
            echo '</div>';

            if ( $mesa->tipo === 'bloqueada' ) {
                echo '<p style="color:#d32f2f; text-align:center; margin:15px 0;"><strong>🚫 BLOQUEADA</strong><br><small>' . esc_html( $mesa->etiqueta_bloqueo ) . '</small></p>';
            } else {
                if ( empty( $mesa->asignaciones ) ) {
                    echo '<p style="color:#888; font-style:italic; font-size:11px; text-align:center;">Mesa vacía</p>';
                } else {
                    echo '<ul style="margin:0; padding-left:15px; font-size:11px;">';
                    foreach ( $mesa->asignaciones as $a ) {
                        echo '<li style="margin-bottom:6px; padding-bottom:4px; border-bottom:1px dashed #eee;">';
                        echo '<span style="background:#f0f0f0; padding:1px 4px; border-radius:3px; font-weight:bold; color:#333; margin-right:5px;">' . esc_html( $a->grupo ) . '</span>';
                        echo '<strong>' . esc_html( $a->cantidad ) . ' lug.</strong> ';
                        echo '<a href="#" class="btn-move-order" data-order="' . $a->order_id . '" data-qty="' . $a->cantidad . '" data-mesa-actual="' . $mesa->id . '" title="Mover a otra mesa" style="text-decoration:none;">🔄</a><br>';
                        echo '<span style="color:#666;">(#' . $a->order_id . ') ' . esc_html( $a->nombre . ' ' . $a->apellidos ) . '</span>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
            }

            echo '</div>';
        }
        echo '</div>'; // flex wrap
    }
    echo '</div>';

    // Modal para mover
    ?>
    <div id="tbp-move-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9999; justify-content:center; align-items:center;">
        <div style="background:#fff; padding:20px; border-radius:5px; width:400px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
            <h3>Mover Pedido a Otra Mesa</h3>
            <p>Selecciona la mesa destino (debe tener espacio suficiente).</p>
            <input type="hidden" id="move_order_id">
            <p>
                <select id="move_mesa_id" style="width:100%;">
                    <option value="">-- Seleccionar Mesa --</option>
                    <?php 
                    foreach ($mesas as $m) {
                        if ($m->tipo === 'bloqueada') continue;
                        $libre = $m->capacidad - $m->capacidad_usada;
                        if ($libre > 0) {
                            echo '<option value="' . $m->id . '" data-libre="' . $libre . '">' . esc_html($m->zona) . ' - Mesa ' . esc_html($m->numero) . ' (' . $libre . ' lugares libres)</option>';
                        }
                    }
                    ?>
                </select>
            </p>
            <div style="text-align:right; margin-top:20px;">
                <button type="button" class="button" id="btn_cancel_move">Cancelar</button>
                <button type="button" class="button button-primary" id="btn_confirm_move">Confirmar Movimiento</button>
            </div>
        </div>
    </div>

    <script>
    jQuery(document).ready(function($) {
        let currentQty = 0;

        $('.btn-move-order').on('click', function(e) {
            e.preventDefault();
            const orderId = $(this).data('order');
            currentQty = parseInt($(this).data('qty'));
            const currentMesa = $(this).data('mesa-actual');

            $('#move_order_id').val(orderId);
            
            // Ocultar mesas que no tienen capacidad para este pedido
            $('#move_mesa_id option').each(function() {
                if (!$(this).val()) return; // skip default
                if ($(this).val() == currentMesa) {
                    $(this).hide(); // No mover a la misma mesa
                    return;
                }
                const libre = parseInt($(this).data('libre'));
                if (libre < currentQty) {
                    $(this).prop('disabled', true).css('color', '#ccc');
                } else {
                    $(this).prop('disabled', false).css('color', '').show();
                }
            });

            $('#move_mesa_id').val('');
            $('#tbp-move-modal').css('display', 'flex');
        });

        $('#btn_cancel_move').on('click', function() {
            $('#tbp-move-modal').hide();
        });

        $('#btn_confirm_move').on('click', function() {
            const newMesaId = $('#move_mesa_id').val();
            const orderId = $('#move_order_id').val();

            if (!newMesaId) {
                alert('Selecciona una mesa destino válida.');
                return;
            }

            $(this).prop('disabled', true).text('Moviendo...');

            $.post(ajaxurl, {
                action: 'tbp_asientos_move_order',
                config_id: <?php echo $config_id; ?>,
                order_id: orderId,
                new_mesa_id: newMesaId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(response) {
                if (response.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + (response.data.message || response.data));
                    $('#btn_confirm_move').prop('disabled', false).text('Confirmar Movimiento');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * AJAX: Guarda el diseño del plano (Posiciones de mesas y elementos)
 */
add_action( 'wp_ajax_tbp_asientos_save_layout', 'tbp_asientos_ajax_save_layout' );
function tbp_asientos_ajax_save_layout() {
    if ( ! wp_verify_nonce( $_POST['nonce'] ?? '', 'tbp_asientos_nonce' ) ) {
        wp_send_json_error( 'Nonce inválido.' );
    }

    global $wpdb;
    $config_id   = intval( $_POST['config_id'] );
    $layout_data = json_decode( stripslashes( $_POST['layout_data'] ), true );

    if ( ! $config_id || ! is_array( $layout_data ) ) {
        wp_send_json_error( 'Datos insuficientes.' );
    }

    $table_seats    = $wpdb->prefix . 'tbp_seat_tables';
    $table_elements = $wpdb->prefix . 'tbp_seat_elements';

    // 1. Limpiar elementos previos (los elementos se recrean, las mesas se actualizan o recrean)
    $wpdb->delete( $table_elements, array( 'config_id' => $config_id ) );

    // Keep track of the active table IDs
    $active_table_ids = array();

    foreach ( $layout_data as $item ) {
        if ( $item['type'] === 'element' ) {
            $wpdb->insert( $table_elements, array(
                'config_id' => $config_id,
                'tipo'      => sanitize_key( $item['shape'] ),
                'label'     => sanitize_text_field( $item['label'] ),
                'pos_x'     => intval( $item['x'] ),
                'pos_y'     => intval( $item['y'] ),
                'width'     => intval( $item['w'] ),
                'height'    => intval( $item['h'] ),
                'color'     => sanitize_hex_color( $item['color'] ?? '#334155' )
            ));
        } else {
            // Mesa: Si tiene ID, actualizar. Si no, insertar.
            $table_payload = array(
                'config_id' => $config_id,
                'zona'      => sanitize_text_field( $item['zona'] ?? 'Sin Zona' ),
                'numero'    => sanitize_text_field( $item['label'] ),
                'capacidad' => intval( $item['pax'] ?? 10 ),
                'tipo'      => sanitize_key( $item['shape'] ?? 'round' ),
                'pos_x'     => intval( $item['x'] ),
                'pos_y'     => intval( $item['y'] ),
                'width'     => intval( $item['w'] ),
                'height'    => intval( $item['h'] ),
                'color'     => sanitize_hex_color( $item['color'] ?? '#2271b1' )
            );

            $existing_id = null;
            if ( ! empty($item['id']) ) {
                $existing_id = intval($item['id']);
            } else {
                // Buscar si ya existe por número y zona para no duplicar
                $existing_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM $table_seats WHERE config_id = %d AND zona = %s AND numero = %s",
                    $config_id, $table_payload['zona'], $table_payload['numero']
                ));
            }

            if ( $existing_id ) {
                $wpdb->update( $table_seats, $table_payload, array( 'id' => $existing_id ) );
                $active_table_ids[] = $existing_id;
            } else {
                $wpdb->insert( $table_seats, $table_payload );
                if ( $wpdb->insert_id ) {
                    $active_table_ids[] = intval($wpdb->insert_id);
                }
            }
        }
    }

    // 2. Eliminar mesas que no están en el layout activo
    $query_deleted = "SELECT id FROM $table_seats WHERE config_id = %d";
    $params_deleted = array( $config_id );
    if ( ! empty($active_table_ids) ) {
        $placeholders = implode(',', array_fill(0, count($active_table_ids), '%d'));
        $query_deleted .= " AND id NOT IN ($placeholders)";
        $params_deleted = array_merge($params_deleted, $active_table_ids);
    }
    $deleted_table_ids = $wpdb->get_col( $wpdb->prepare($query_deleted, ...$params_deleted) );

    if ( ! empty($deleted_table_ids) ) {
        $deleted_ids_str = implode(',', array_map('intval', $deleted_table_ids));
        
        $table_assignments = $wpdb->prefix . 'tbp_seat_assignments';
        $order_ids = $wpdb->get_col("SELECT order_id FROM $table_assignments WHERE mesa_id IN ($deleted_ids_str)");
        
        $wpdb->query("DELETE FROM $table_assignments WHERE mesa_id IN ($deleted_ids_str)");
        $wpdb->query("DELETE FROM $table_seats WHERE id IN ($deleted_ids_str)");

        if ( ! empty($order_ids) ) {
            foreach ( $order_ids as $oid ) {
                delete_post_meta(intval($oid), '_tbp_seat_assignment');
            }
        }
    }

    // Al guardar el layout, regeneramos el snapshot automáticamente
    tbp_asientos_generate_public_snapshot( $config_id );

    wp_send_json_success( 'Diseño guardado y Snapshot actualizado.' );
}
