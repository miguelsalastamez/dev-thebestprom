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
        <?php if ( $config_id > 0 ) : ?>

        <!-- TOOLBAR HEADER -->
        <div style="display:flex; align-items:center; justify-content:space-between; background:linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); padding:16px 24px; border-radius:10px; margin-bottom:20px; box-shadow:0 4px 15px rgba(34,113,177,0.3);">
            <div style="display:flex; align-items:center; gap:15px;">
                <span style="font-size:24px;">📋</span>
                <div>
                    <h2 style="margin:0; color:#fff; font-size:18px; font-weight:700;">Listado de Pedidos Escaneados</h2>
                    <p style="margin:2px 0 0; color:rgba(255,255,255,0.7); font-size:12px;">Filtra, selecciona y gestiona los pedidos antes de asignar mesas.</p>
                </div>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <button type="button" id="btn_run_packing_v2" class="button button-large" style="background:#fff; color:#1e3a5f; border:none; font-weight:700; padding:8px 20px; border-radius:6px; cursor:pointer;">▶ Ejecutar Asignación Inteligente</button>
                <a href="?page=tbp-actividades-asientos&action=view&id=<?php echo $config_id; ?>" class="button button-large" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); font-weight:600; padding:8px 20px; border-radius:6px;">👀 Panel de Control</a>
            </div>
        </div>

        <!-- RESUMEN + FILTROS -->
        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap:12px; margin-bottom:20px;">
            <div id="stat_pedidos_card" style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:28px; font-weight:800; color:#2271b1;" id="stat_pedidos_filtrados">—</div>
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">Pedidos</div>
            </div>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:28px; font-weight:800; color:#16a34a;" id="stat_piezas_filtradas">—</div>
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">Piezas / Plazas</div>
            </div>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:28px; font-weight:800; color:#9333ea;" id="stat_grupos_filtrados">—</div>
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">Grupos</div>
            </div>
            <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; text-align:center; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
                <div style="font-size:28px; font-weight:800; color:#ea580c;" id="stat_seleccionados">0</div>
                <div style="font-size:11px; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; margin-top:4px;">Seleccionados</div>
            </div>
        </div>

        <!-- BARRA DE FILTROS -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:16px; margin-bottom:20px; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div style="display:flex; align-items:center; gap:8px; margin-bottom:12px;">
                <span style="font-size:16px;">🔍</span>
                <strong style="font-size:13px; color:#334155;">Filtros</strong>
                <button type="button" id="btn_clear_filters" class="button button-small" style="margin-left:auto; font-size:11px;">✕ Limpiar Filtros</button>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:12px;">
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:4px;">Grupo</label>
                    <select id="filter_grupo" style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#f8fafc;">
                        <option value="">-- Todos los Grupos --</option>
                    </select>
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:4px;"># Pedido</label>
                    <input type="text" id="filter_pedido" placeholder="Buscar por # de pedido..." style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#f8fafc; box-sizing:border-box;">
                </div>
                <div>
                    <label style="font-size:11px; font-weight:600; color:#64748b; text-transform:uppercase; letter-spacing:0.5px; display:block; margin-bottom:4px;">Nombre</label>
                    <input type="text" id="filter_nombre" placeholder="Buscar por nombre o apellido..." style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; background:#f8fafc; box-sizing:border-box;">
                </div>
            </div>
        </div>

        <!-- ACCIONES DE SELECCIÓN -->
        <div id="selection_toolbar" style="display:none; background:#fef3c7; border:1px solid #f59e0b; border-radius:10px; padding:12px 20px; margin-bottom:15px; display:none; align-items:center; justify-content:space-between;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-size:18px;">⚡</span>
                <span style="font-weight:600; color:#92400e; font-size:13px;"><span id="selection_count">0</span> pedido(s) seleccionado(s) — <span id="selection_piezas">0</span> piezas</span>
            </div>
            <div style="display:flex; gap:8px;">
                <button type="button" id="btn_action_selected" class="button button-primary button-small">🎯 Asignar a Mesas</button>
                <button type="button" id="btn_deselect_all" class="button button-small">✕ Deseleccionar</button>
            </div>
        </div>

        <!-- TABLA PRINCIPAL -->
        <div style="background:#fff; border:1px solid #e2e8f0; border-radius:10px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.06);">
            <div id="scan_table_loader" style="text-align:center; padding:60px 20px;">
                <div style="font-size:32px; margin-bottom:10px;">⏳</div>
                <p style="color:#64748b; font-size:14px;">Cargando datos del escaneo...</p>
            </div>
            <div id="scan_no_data" style="display:none; text-align:center; padding:60px 20px;">
                <div style="font-size:48px; margin-bottom:15px;">📭</div>
                <h3 style="color:#334155; margin:0 0 8px;">No hay datos de escaneo</h3>
                <p style="color:#64748b; font-size:14px; margin:0;">Ve a la <strong>Etapa 1</strong> y ejecuta <strong>"🔍 Escanear Asistentes"</strong> primero.</p>
            </div>
            <table id="tbp_scan_table" style="display:none; width:100%; border-collapse:collapse; font-size:13px;">
                <thead>
                    <tr style="background:#f1f5f9; border-bottom:2px solid #e2e8f0;">
                        <th style="padding:12px 16px; text-align:center; width:40px;">
                            <input type="checkbox" id="chk_select_all" title="Seleccionar / Deseleccionar todos los visibles" style="width:16px; height:16px; cursor:pointer;">
                        </th>
                        <th style="padding:12px 16px; text-align:left; font-weight:700; color:#334155; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer;" data-sort="order_id"># Pedido ↕</th>
                        <th style="padding:12px 16px; text-align:left; font-weight:700; color:#334155; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer;" data-sort="nombre">Nombre ↕</th>
                        <th style="padding:12px 16px; text-align:left; font-weight:700; color:#334155; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer;" data-sort="grupo">Grupo ↕</th>
                        <th style="padding:12px 16px; text-align:right; font-weight:700; color:#334155; font-size:11px; text-transform:uppercase; letter-spacing:0.5px; cursor:pointer;" data-sort="cantidad">Piezas ↕</th>
                    </tr>
                </thead>
                <tbody id="tbp_scan_tbody"></tbody>
            </table>
            <!-- PAGINACIÓN -->
            <div id="scan_pagination" style="display:none; padding:12px 16px; border-top:1px solid #e2e8f0; display:flex; align-items:center; justify-content:space-between; background:#f8fafc;">
                <span style="font-size:12px; color:#64748b;" id="pagination_info">Mostrando 1-50 de 612</span>
                <div style="display:flex; gap:4px;" id="pagination_buttons"></div>
            </div>
        </div>

        <?php else : ?>
            <div class="postbox"><div class="inside"><p><em>Debes guardar la configuración primero para acceder a esta etapa.</em></p></div></div>
        <?php endif; ?>
    </div>

    <!-- ============================================================= -->
    <!-- MODAL: ASIGNACIÓN MANUAL POR GRUPO                            -->
    <!-- ============================================================= -->
    <div id="tbp-assign-modal" style="display:none; position:fixed; top:0; left:0; width:100vw; height:100vh; z-index:999999; background:rgba(15,23,42,0.85); backdrop-filter:blur(4px);">
        <div style="display:flex; flex-direction:column; height:100vh;">
            <!-- MODAL HEADER -->
            <div style="background:linear-gradient(135deg, #1e3a5f 0%, #2271b1 100%); padding:12px 24px; display:flex; align-items:center; justify-content:space-between; flex-shrink:0;">
                <div style="display:flex; align-items:center; gap:12px;">
                    <span style="font-size:22px;">🎯</span>
                    <div>
                        <h2 style="margin:0; color:#fff; font-size:16px; font-weight:700;">Asignación Manual</h2>
                        <p style="margin:0; color:rgba(255,255,255,0.7); font-size:11px;" id="assign_modal_subtitle">Grupo: — | Pedidos: — | Piezas: —</p>
                    </div>
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <button type="button" id="btn_assign_undo" class="button" style="background:rgba(255,255,255,0.15); color:#fff; border:1px solid rgba(255,255,255,0.3); font-size:12px;" disabled>🔄 Deshacer última mesa</button>
                    <button type="button" id="btn_assign_confirm" class="button" style="background:#16a34a; color:#fff; border:none; font-weight:700; font-size:13px; padding:6px 20px;" disabled>💾 Confirmar Asignación</button>
                    <button type="button" id="btn_assign_cancel" class="button" style="background:#dc2626; color:#fff; border:none; font-size:12px; padding:6px 16px;">✕ Cancelar</button>
                </div>
            </div>

            <!-- PROGRESS BAR -->
            <div style="background:#0f172a; padding:0; flex-shrink:0;">
                <div id="assign_progress_bar" style="height:4px; background:linear-gradient(90deg, #22c55e, #16a34a); width:0%; transition:width 0.4s ease;"></div>
            </div>

            <!-- BODY: SPLIT SCREEN -->
            <div style="display:flex; flex:1; overflow:hidden;">
                <!-- LEFT PANEL: ORDER QUEUE -->
                <div style="width:380px; background:#fff; border-right:1px solid #e2e8f0; display:flex; flex-direction:column; flex-shrink:0;">
                    <!-- Stats -->
                    <div style="padding:16px; border-bottom:1px solid #e2e8f0; background:#f8fafc;">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:8px; text-align:center;">
                            <div>
                                <div style="font-size:20px; font-weight:800; color:#ea580c;" id="assign_stat_pending">0</div>
                                <div style="font-size:9px; color:#64748b; text-transform:uppercase;">⏳ Pendientes</div>
                            </div>
                            <div>
                                <div style="font-size:20px; font-weight:800; color:#16a34a;" id="assign_stat_done">0</div>
                                <div style="font-size:9px; color:#64748b; text-transform:uppercase;">✅ Asignados</div>
                            </div>
                            <div>
                                <div style="font-size:20px; font-weight:800; color:#2271b1;" id="assign_stat_piezas">0/0</div>
                                <div style="font-size:9px; color:#64748b; text-transform:uppercase;">📦 Piezas</div>
                            </div>
                        </div>
                    </div>

                    <!-- Order List -->
                    <div style="flex:1; overflow-y:auto; padding:8px;" id="assign_order_list">
                        <!-- Dynamic content -->
                    </div>
                </div>

                <!-- RIGHT PANEL: FLOOR PLAN -->
                <div style="flex:1; background:#e8ecf0; position:relative; overflow:hidden;">
                    <div id="assign_canvas_container" style="position:absolute; top:0; left:0; width:100%; height:100%; overflow:hidden;">
                        <div id="assign_canvas_grid" style="position:absolute; top:0; left:0; width:4000px; height:4000px; background-image:radial-gradient(#d1d5db 1px, transparent 1px); background-size:20px 20px;"></div>
                        <div id="assign_canvas_items" style="position:absolute; top:0; left:0; width:100%; height:100%; transform-origin:0 0;"></div>
                    </div>
                    <!-- Zoom control -->
                    <div style="position:absolute; bottom:16px; right:16px; background:rgba(255,255,255,0.95); border-radius:8px; padding:8px 12px; box-shadow:0 2px 10px rgba(0,0,0,0.15); display:flex; align-items:center; gap:8px; font-size:11px; z-index:10;">
                        <span>🔍</span>
                        <input type="range" id="assign_zoom" min="0.2" max="1.5" step="0.05" value="0.6" style="width:120px;">
                        <span id="assign_zoom_label">60%</span>
                    </div>
                    <!-- Legend -->
                    <div style="position:absolute; top:16px; right:16px; background:rgba(255,255,255,0.95); border-radius:8px; padding:10px 14px; box-shadow:0 2px 10px rgba(0,0,0,0.15); font-size:10px; z-index:10;">
                        <div style="margin-bottom:4px;"><span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#94a3b8; vertical-align:middle;"></span> Disponible</div>
                        <div style="margin-bottom:4px;"><span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#3b82f6; vertical-align:middle;"></span> Parcial</div>
                        <div style="margin-bottom:4px;"><span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#22c55e; vertical-align:middle;"></span> Llena</div>
                        <div><span style="display:inline-block; width:12px; height:12px; border-radius:50%; background:#ef4444; vertical-align:middle;"></span> Sin espacio</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .tbp-zona-box { border: 1px solid #ccd0d4; background: #fff; padding: 15px; margin-bottom: 15px; position: relative; box-shadow: 0 1px 1px rgba(0,0,0,.04); }
        .tbp-zona-box h4 { margin-top: 0; padding-bottom: 10px; border-bottom: 1px solid #eee; display:flex; justify-content:space-between; }
        .tbp-zona-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom:10px; }
        .btn-remove-zona { color: #a00; cursor: pointer; text-decoration:none; }
        .btn-remove-zona:hover { color: red; }
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
            if (rawZonas) {
                const parsed = JSON.parse(rawZonas);
                if (Array.isArray(parsed)) {
                    zonas = parsed;
                }
            }
        } catch(e) { console.error('Error parsing zonas', e); }

        function renderZonas() {
            const $container = $('#zonas-container');
            $container.empty();

            if (!Array.isArray(zonas)) {
                zonas = [];
            }

            zonas.forEach((zona, index) => {
                if (!zona) return;
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
            if (!Array.isArray(zonas)) {
                zonas = [];
            }
            $('#zonas-container .tbp-zona-box').each(function() {
                const idx = $(this).data('index');
                if (!zonas[idx]) {
                    zonas[idx] = {};
                }
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

        if ($('#event_id').val()) {
            refreshGroupFields($('#event_id').val());
        }

        $('#event_id').on('change', function() {
            refreshGroupFields($(this).val());
        });

        // =====================================================================
        // ETAPA 3: TABLA DE PEDIDOS ESCANEADOS
        // =====================================================================
        try { // try-catch para no romper handlers previos (scan, packing)
        var scanAllData = [];        // Todos los pedidos del escaneo
        var scanFilteredData = [];   // Pedidos tras aplicar filtros
        var scanSelectedIds = {};    // { order_id: true }
        var scanCurrentPage = 1;
        var scanPageSize = 50;
        var scanSortField = 'order_id';
        var scanSortDir = 'asc';
        var scanGruposStats = {};

        // Cargar datos cuando se muestre la Etapa 3
        var stage3Loaded = false;
        $('.stage-item[data-stage="3"]').on('click', function() {
            if (!stage3Loaded) {
                stage3Loaded = true;
                loadScanData();
            }
        });

        function loadScanData() {
            var configId = <?php echo $config_id; ?>;
            if (!configId) return;

            $('#scan_table_loader').show();
            $('#scan_no_data').hide();
            $('#tbp_scan_table').hide();

            $.post(ajaxurl, {
                action: 'tbp_asientos_get_scan_data',
                config_id: configId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(response) {
                $('#scan_table_loader').hide();

                if (!response.success) {
                    $('#scan_no_data').show();
                    // Poner guiones en stats
                    $('#stat_pedidos_filtrados, #stat_piezas_filtradas, #stat_grupos_filtrados').text('—');
                    return;
                }

                scanAllData = response.data.pedidos || [];
                scanGruposStats = response.data.grupos_stats || {};

                // Poblar dropdown de grupos
                var $gf = $('#filter_grupo');
                $gf.find('option:not(:first)').remove();
                $.each(scanGruposStats, function(grupo, stats) {
                    $gf.append('<option value="' + $('<div>').text(grupo).html() + '">' + grupo + ' (' + stats.piezas + ' pzas, ' + stats.pedidos + ' pedidos)</option>');
                });

                // Renderizar
                applyFilters();
                $('#tbp_scan_table').show();

            }).fail(function() {
                $('#scan_table_loader').hide();
                $('#scan_no_data').show();
            });
        }

        function applyFilters() {
            var fGrupo = $('#filter_grupo').val().toLowerCase();
            var fPedido = $('#filter_pedido').val().trim();
            var fNombre = $('#filter_nombre').val().trim().toLowerCase();

            scanFilteredData = scanAllData.filter(function(p) {
                if (fGrupo && (p.grupo || '').toLowerCase() !== fGrupo) return false;
                if (fPedido && String(p.order_id).indexOf(fPedido) === -1) return false;
                if (fNombre) {
                    var fullName = ((p.nombre || '') + ' ' + (p.apellidos || '')).toLowerCase();
                    if (fullName.indexOf(fNombre) === -1) return false;
                }
                return true;
            });

            // Aplicar ordenamiento
            sortData();

            // Reset a página 1
            scanCurrentPage = 1;

            // Actualizar stats
            updateFilteredStats();

            // Renderizar tabla
            renderTable();

            // Actualizar checkbox "seleccionar todos"
            updateSelectAllState();
        }

        function sortData() {
            var field = scanSortField;
            var dir = scanSortDir;

            scanFilteredData.sort(function(a, b) {
                var va = a[field], vb = b[field];

                if (field === 'order_id' || field === 'cantidad') {
                    va = parseInt(va) || 0;
                    vb = parseInt(vb) || 0;
                } else if (field === 'nombre') {
                    va = ((a.nombre || '') + ' ' + (a.apellidos || '')).toLowerCase();
                    vb = ((b.nombre || '') + ' ' + (b.apellidos || '')).toLowerCase();
                } else {
                    va = (va || '').toString().toLowerCase();
                    vb = (vb || '').toString().toLowerCase();
                }

                if (va < vb) return dir === 'asc' ? -1 : 1;
                if (va > vb) return dir === 'asc' ? 1 : -1;
                return 0;
            });
        }

        function updateFilteredStats() {
            var totalPedidos = scanFilteredData.length;
            var totalPiezas = 0;
            var gruposSet = {};
            scanFilteredData.forEach(function(p) {
                totalPiezas += parseInt(p.cantidad) || 0;
                gruposSet[p.grupo || 'Sin grupo'] = true;
            });

            $('#stat_pedidos_filtrados').text(totalPedidos.toLocaleString());
            $('#stat_piezas_filtradas').text(totalPiezas.toLocaleString());
            $('#stat_grupos_filtrados').text(Object.keys(gruposSet).length);
        }

        function renderTable() {
            var $tbody = $('#tbp_scan_tbody');
            $tbody.empty();

            var start = (scanCurrentPage - 1) * scanPageSize;
            var end = Math.min(start + scanPageSize, scanFilteredData.length);
            var pageData = scanFilteredData.slice(start, end);

            if (pageData.length === 0) {
                $tbody.append('<tr><td colspan="5" style="text-align:center; padding:40px; color:#94a3b8; font-size:14px;">No se encontraron pedidos con los filtros aplicados.</td></tr>');
                $('#scan_pagination').hide();
                return;
            }

            pageData.forEach(function(p, idx) {
                var isChecked = scanSelectedIds[p.order_id] ? 'checked' : '';
                var rowBg = idx % 2 === 0 ? '#fff' : '#f8fafc';
                var selectedBg = scanSelectedIds[p.order_id] ? '#eff6ff' : rowBg;

                var row = '<tr data-order-id="' + p.order_id + '" style="border-bottom:1px solid #f1f5f9; background:' + selectedBg + '; transition:background 0.15s;">';
                row += '<td style="padding:10px 16px; text-align:center;"><input type="checkbox" class="chk_row" value="' + p.order_id + '" ' + isChecked + ' style="width:15px; height:15px; cursor:pointer;"></td>';
                row += '<td style="padding:10px 16px; font-weight:600; color:#1e40af;">#' + p.order_id + '</td>';
                row += '<td style="padding:10px 16px; color:#334155;">' + escHtml(p.nombre || '') + ' ' + escHtml(p.apellidos || '') + '</td>';
                row += '<td style="padding:10px 16px;"><span style="background:#e0e7ff; color:#3730a3; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:600;">' + escHtml(p.grupo || 'Sin grupo') + '</span></td>';
                row += '<td style="padding:10px 16px; text-align:right; font-weight:700; color:#334155;">' + parseInt(p.cantidad) + '</td>';
                row += '</tr>';

                $tbody.append(row);
            });

            renderPagination(start, end);
        }

        function renderPagination(start, end) {
            var total = scanFilteredData.length;
            var totalPages = Math.ceil(total / scanPageSize);

            if (totalPages <= 1) {
                $('#scan_pagination').hide();
                return;
            }

            $('#scan_pagination').css('display', 'flex');
            $('#pagination_info').text('Mostrando ' + (start + 1) + '-' + end + ' de ' + total.toLocaleString());

            var $btns = $('#pagination_buttons');
            $btns.empty();

            // Calcular rango de páginas a mostrar
            var startPage = Math.max(1, scanCurrentPage - 3);
            var endPage = Math.min(totalPages, scanCurrentPage + 3);

            if (scanCurrentPage > 1) {
                $btns.append('<button type="button" class="button button-small pg-btn" data-page="' + (scanCurrentPage - 1) + '" style="min-width:32px;">‹</button>');
            }

            for (var i = startPage; i <= endPage; i++) {
                var active = i === scanCurrentPage ? 'background:#2271b1; color:#fff; border-color:#2271b1;' : '';
                $btns.append('<button type="button" class="button button-small pg-btn" data-page="' + i + '" style="min-width:32px; ' + active + '">' + i + '</button>');
            }

            if (scanCurrentPage < totalPages) {
                $btns.append('<button type="button" class="button button-small pg-btn" data-page="' + (scanCurrentPage + 1) + '" style="min-width:32px;">›</button>');
            }
        }

        // Pagination click
        $(document).on('click', '.pg-btn', function() {
            scanCurrentPage = parseInt($(this).data('page'));
            renderTable();
            updateSelectAllState();
            // Scroll to top of table
            $('html, body').animate({ scrollTop: $('#tbp_scan_table').offset().top - 50 }, 200);
        });

        // Column sorting
        $('#tbp_scan_table thead th[data-sort]').on('click', function() {
            var field = $(this).data('sort');
            if (scanSortField === field) {
                scanSortDir = scanSortDir === 'asc' ? 'desc' : 'asc';
            } else {
                scanSortField = field;
                scanSortDir = 'asc';
            }
            sortData();
            scanCurrentPage = 1;
            renderTable();
        });

        // Filters
        var filterTimeout;
        $('#filter_grupo').on('change', function() { applyFilters(); });
        $('#filter_pedido, #filter_nombre').on('input', function() {
            clearTimeout(filterTimeout);
            filterTimeout = setTimeout(function() { applyFilters(); }, 250);
        });
        $('#btn_clear_filters').on('click', function() {
            $('#filter_grupo').val('');
            $('#filter_pedido').val('');
            $('#filter_nombre').val('');
            applyFilters();
        });

        // ---- CHECKBOX LOGIC ----
        // Select all (visible page)
        $('#chk_select_all').on('change', function() {
            var isChecked = $(this).prop('checked');
            var start = (scanCurrentPage - 1) * scanPageSize;
            var end = Math.min(start + scanPageSize, scanFilteredData.length);

            for (var i = start; i < end; i++) {
                var oid = scanFilteredData[i].order_id;
                if (isChecked) {
                    scanSelectedIds[oid] = true;
                } else {
                    delete scanSelectedIds[oid];
                }
            }

            // Actualizar checkboxes de la página actual
            $('#tbp_scan_tbody .chk_row').prop('checked', isChecked);
            updateSelectionUI();
        });

        // Individual checkbox
        $(document).on('change', '.chk_row', function() {
            var oid = parseInt($(this).val());
            if ($(this).prop('checked')) {
                scanSelectedIds[oid] = true;
                $(this).closest('tr').css('background', '#eff6ff');
            } else {
                delete scanSelectedIds[oid];
                var idx = $(this).closest('tr').index();
                $(this).closest('tr').css('background', idx % 2 === 0 ? '#fff' : '#f8fafc');
            }
            updateSelectAllState();
            updateSelectionUI();
        });

        // Row hover highlight
        $(document).on('mouseenter', '#tbp_scan_tbody tr', function() {
            if (!scanSelectedIds[$(this).data('order-id')]) {
                $(this).css('background', '#f0f9ff');
            }
        });
        $(document).on('mouseleave', '#tbp_scan_tbody tr', function() {
            var oid = $(this).data('order-id');
            if (!scanSelectedIds[oid]) {
                var idx = $(this).index();
                $(this).css('background', idx % 2 === 0 ? '#fff' : '#f8fafc');
            }
        });

        // Deselect all
        $('#btn_deselect_all').on('click', function() {
            scanSelectedIds = {};
            $('#chk_select_all').prop('checked', false);
            $('#tbp_scan_tbody .chk_row').prop('checked', false);
            renderTable();
            updateSelectionUI();
        });

        function updateSelectAllState() {
            var start = (scanCurrentPage - 1) * scanPageSize;
            var end = Math.min(start + scanPageSize, scanFilteredData.length);
            var allChecked = true;
            var anyOnPage = false;

            for (var i = start; i < end; i++) {
                anyOnPage = true;
                if (!scanSelectedIds[scanFilteredData[i].order_id]) {
                    allChecked = false;
                    break;
                }
            }
            $('#chk_select_all').prop('checked', anyOnPage && allChecked);
        }

        function updateSelectionUI() {
            var count = Object.keys(scanSelectedIds).length;
            $('#stat_seleccionados').text(count);
            $('#selection_count').text(count);

            // Calcular piezas seleccionadas
            var piezas = 0;
            scanAllData.forEach(function(p) {
                if (scanSelectedIds[p.order_id]) {
                    piezas += parseInt(p.cantidad) || 0;
                }
            });
            $('#selection_piezas').text(piezas.toLocaleString());

            if (count > 0) {
                $('#selection_toolbar').css('display', 'flex');
            } else {
                $('#selection_toolbar').css('display', 'none');
            }
        }

        function escHtml(str) {
            return $('<span>').text(str).html();
        }

        } catch(e) { console.error('TBP Etapa 3 error:', e); }

        // =====================================================================
        // MODAL: ASIGNACIÓN MANUAL POR GRUPO
        // =====================================================================
        try {
        var assignQueue = [];           // Orders pending assignment
        var assignDone = [];            // Orders already assigned { order, mesa_id }
        var assignFloorTables = [];     // Floor plan tables from DB
        var assignFloorElements = [];   // Floor plan decorative elements
        var assignHistory = [];         // Undo stack: [ { mesa_id, orders: [...] } ]
        var assignZoom = 0.6;
        var assignTotalPiezas = 0;
        var assignDonePiezas = 0;
        var assignGroupName = '';

        // Wire up the action button
        $('#btn_action_selected').on('click', function() {
            openAssignModal();
        });

        function openAssignModal() {
            // Gather selected orders from scanAllData
            var selectedOrders = [];
            var grupos = {};
            scanAllData.forEach(function(p) {
                if (scanSelectedIds[p.order_id]) {
                    selectedOrders.push({
                        order_id: parseInt(p.order_id),
                        nombre: p.nombre || '',
                        apellidos: p.apellidos || '',
                        grupo: p.grupo || 'Sin grupo',
                        cantidad: parseInt(p.cantidad) || 0,
                        status: 'pending' // pending | assigned
                    });
                    grupos[p.grupo || 'Sin grupo'] = true;
                }
            });

            if (selectedOrders.length === 0) {
                alert('No hay pedidos seleccionados. Usa los checkboxes para seleccionar pedidos.');
                return;
            }

            // Sort by cantidad DESC (bigger orders first for optimal packing)
            selectedOrders.sort(function(a, b) { return b.cantidad - a.cantidad; });

            assignGroupName = Object.keys(grupos).join(', ');
            assignQueue = selectedOrders;
            assignDone = [];
            assignHistory = [];
            assignTotalPiezas = 0;
            assignDonePiezas = 0;
            assignQueue.forEach(function(o) { assignTotalPiezas += o.cantidad; });

            // Update subtitle
            $('#assign_modal_subtitle').text('Grupo: ' + assignGroupName + ' | Pedidos: ' + assignQueue.length + ' | Piezas: ' + assignTotalPiezas);

            // Enable action button text
            $('#btn_action_selected').prop('disabled', false).text('🎯 Asignar a Mesas');

            // Show modal
            $('#tbp-assign-modal').fadeIn(200);
            $('body').css('overflow', 'hidden');

            // Load floor plan data
            loadFloorPlanForAssign();
        }

        function loadFloorPlanForAssign() {
            var configId = <?php echo $config_id; ?>;
            $('#assign_canvas_items').html('<div style="text-align:center; padding:100px; color:#64748b;">⏳ Cargando plano...</div>');

            $.post(ajaxurl, {
                action: 'tbp_asientos_get_floor_data',
                config_id: configId,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(res) {
                if (!res.success) {
                    $('#assign_canvas_items').html('<div style="text-align:center; padding:100px; color:#ef4444;">❌ Error cargando plano</div>');
                    return;
                }

                assignFloorTables = res.data.tables || [];
                assignFloorElements = res.data.elements || [];

                renderAssignFloor();
                renderAssignQueue();
                updateAssignStats();
            });
        }

        function renderAssignFloor() {
            var $canvas = $('#assign_canvas_items');
            $canvas.empty();

            // Render decorative elements first (lower z-index)
            assignFloorElements.forEach(function(el) {
                var shapeClass = 'item-' + el.tipo;
                var $elem = $('<div>').css({
                    position: 'absolute',
                    left: el.pos_x + 'px',
                    top: el.pos_y + 'px',
                    width: el.width + 'px',
                    height: el.height + 'px',
                    background: el.color || '#334155',
                    borderRadius: el.tipo === 'area' ? '0' : '4px',
                    opacity: el.tipo === 'area' ? 0.25 : 0.7,
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    color: '#fff',
                    fontSize: '9px',
                    fontWeight: '700',
                    zIndex: 1,
                    pointerEvents: 'none'
                }).addClass(shapeClass).text(el.label);
                $canvas.append($elem);
            });

            // Render tables
            assignFloorTables.forEach(function(tbl) {
                var color = getTableColor(tbl);
                var borderRadius = (tbl.tipo === 'round' || tbl.tipo === 'bar') ? '50%' : '4px';
                var cursor = tbl.libre > 0 ? 'pointer' : 'not-allowed';
                var fontSize = tbl.width < 40 ? '7px' : '9px';

                var $el = $('<div>').css({
                    position: 'absolute',
                    left: tbl.pos_x + 'px',
                    top: tbl.pos_y + 'px',
                    width: tbl.width + 'px',
                    height: tbl.height + 'px',
                    background: color,
                    borderRadius: borderRadius,
                    border: '2px solid ' + color,
                    display: 'flex',
                    flexDirection: 'column',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: cursor,
                    userSelect: 'none',
                    zIndex: 5,
                    boxShadow: '0 2px 6px rgba(0,0,0,0.2)',
                    transition: 'transform 0.15s, box-shadow 0.15s'
                }).attr('data-table-id', tbl.id).addClass('assign-table-item');

                $el.append($('<div>').css({ color: '#fff', fontWeight: '800', fontSize: fontSize, textAlign: 'center', lineHeight: '1.1' }).text(tbl.numero));
                $el.append($('<div>').css({ color: 'rgba(255,255,255,0.8)', fontSize: '7px', fontWeight: '600' }).text(tbl.libre + '/' + tbl.capacidad));

                $canvas.append($el);
            });

            // Apply zoom
            $canvas.css('transform', 'scale(' + assignZoom + ')');
        }

        function getTableColor(tbl) {
            if (tbl.libre <= 0) return '#ef4444';       // Full / no space - red
            if (tbl.used > 0) return '#3b82f6';          // Partially filled - blue
            return '#94a3b8';                             // Empty / available - gray
        }

        function renderAssignQueue() {
            var $list = $('#assign_order_list');
            $list.empty();

            // First show assigned orders
            assignDone.forEach(function(item) {
                var o = item.order;
                var mesa = findTable(item.mesa_id);
                var mesaLabel = mesa ? mesa.numero : '?';
                $list.append(
                    '<div style="padding:8px 10px; margin-bottom:4px; background:#f0fdf4; border:1px solid #bbf7d0; border-radius:6px; font-size:12px; display:flex; justify-content:space-between; align-items:center;">' +
                        '<div><strong style="color:#16a34a;">✅</strong> #' + o.order_id + ' <span style="color:#64748b;">' + escHtml(o.nombre) + ' ' + escHtml(o.apellidos) + '</span></div>' +
                        '<div style="display:flex; align-items:center; gap:6px;"><span style="background:#dcfce7; color:#166534; padding:2px 8px; border-radius:10px; font-size:10px; font-weight:700;">Mesa ' + escHtml(mesaLabel) + '</span><span style="font-weight:700;">' + o.cantidad + ' pzas</span></div>' +
                    '</div>'
                );
            });

            // Then show pending orders
            var pendingOrders = assignQueue.filter(function(o) { return o.status === 'pending'; });
            pendingOrders.forEach(function(o) {
                $list.append(
                    '<div style="padding:8px 10px; margin-bottom:4px; background:#fff; border:1px solid #e2e8f0; border-radius:6px; font-size:12px; display:flex; justify-content:space-between; align-items:center;">' +
                        '<div><strong style="color:#ea580c;">⏳</strong> #' + o.order_id + ' <span style="color:#64748b;">' + escHtml(o.nombre) + ' ' + escHtml(o.apellidos) + '</span></div>' +
                        '<div style="font-weight:700; color:#334155;">' + o.cantidad + ' pzas</div>' +
                    '</div>'
                );
            });

            if (pendingOrders.length === 0 && assignDone.length > 0) {
                $list.append(
                    '<div style="text-align:center; padding:30px; color:#16a34a; font-size:14px; font-weight:700;">' +
                        '🎉 ¡Todos los pedidos han sido asignados!' +
                    '</div>'
                );
            }
        }

        function findTable(id) {
            for (var i = 0; i < assignFloorTables.length; i++) {
                if (assignFloorTables[i].id === id) return assignFloorTables[i];
            }
            return null;
        }

        function updateAssignStats() {
            var pending = assignQueue.filter(function(o) { return o.status === 'pending'; });
            var done = assignDone.length;
            assignDonePiezas = 0;
            assignDone.forEach(function(item) { assignDonePiezas += item.order.cantidad; });

            $('#assign_stat_pending').text(pending.length);
            $('#assign_stat_done').text(done);
            $('#assign_stat_piezas').text(assignDonePiezas + '/' + assignTotalPiezas);

            // Progress bar
            var pct = assignTotalPiezas > 0 ? Math.round((assignDonePiezas / assignTotalPiezas) * 100) : 0;
            $('#assign_progress_bar').css('width', pct + '%');

            // Enable/disable buttons
            $('#btn_assign_undo').prop('disabled', assignHistory.length === 0);
            $('#btn_assign_confirm').prop('disabled', assignDone.length === 0);
        }

        // ---- TABLE CLICK: ASSIGN ORDERS ----
        $(document).on('click', '.assign-table-item', function() {
            var tableId = parseInt($(this).data('table-id'));
            var tbl = findTable(tableId);
            if (!tbl || tbl.libre <= 0) return;

            var pendingOrders = assignQueue.filter(function(o) { return o.status === 'pending'; });
            if (pendingOrders.length === 0) return;

            // Fill this table with orders from the queue
            var remaining = tbl.libre;
            var assignedInThisClick = [];

            for (var i = 0; i < pendingOrders.length && remaining > 0; i++) {
                var order = pendingOrders[i];
                if (order.cantidad <= remaining) {
                    // Whole order fits
                    order.status = 'assigned';
                    remaining -= order.cantidad;
                    assignedInThisClick.push({ order: order, mesa_id: tableId });
                    assignDone.push({ order: order, mesa_id: tableId });
                }
            }

            if (assignedInThisClick.length === 0) {
                // Try the first pending order even if it's bigger (partial won't work with our model, so skip)
                // Show a message
                alert('⚠️ No hay pedidos pendientes que quepan en esta mesa (' + remaining + ' lugares libres). El pedido más pequeño pendiente necesita más espacio.');
                return;
            }

            // Update table availability
            var piecesAssigned = 0;
            assignedInThisClick.forEach(function(a) { piecesAssigned += a.order.cantidad; });
            tbl.used += piecesAssigned;
            tbl.libre = Math.max(0, tbl.capacidad - tbl.used);

            // Push to history for undo
            assignHistory.push({
                mesa_id: tableId,
                orders: assignedInThisClick,
                piecesAssigned: piecesAssigned
            });

            // Re-render
            renderAssignFloor();
            renderAssignQueue();
            updateAssignStats();

            // Scroll order list to bottom
            var $list = $('#assign_order_list');
            $list.scrollTop(0);
        });

        // Hover effect on tables
        $(document).on('mouseenter', '.assign-table-item', function() {
            var tableId = parseInt($(this).data('table-id'));
            var tbl = findTable(tableId);
            if (tbl && tbl.libre > 0) {
                $(this).css({ transform: 'scale(1.15)', boxShadow: '0 4px 20px rgba(0,0,0,0.35)', zIndex: 100 });
            }
        });
        $(document).on('mouseleave', '.assign-table-item', function() {
            $(this).css({ transform: 'scale(1)', boxShadow: '0 2px 6px rgba(0,0,0,0.2)', zIndex: 5 });
        });

        // ---- UNDO ----
        $('#btn_assign_undo').on('click', function() {
            if (assignHistory.length === 0) return;
            var last = assignHistory.pop();

            // Restore orders to pending
            last.orders.forEach(function(item) {
                item.order.status = 'pending';
                // Remove from assignDone
                assignDone = assignDone.filter(function(d) {
                    return !(d.order.order_id === item.order.order_id && d.mesa_id === item.mesa_id);
                });
            });

            // Restore table capacity
            var tbl = findTable(last.mesa_id);
            if (tbl) {
                tbl.used -= last.piecesAssigned;
                tbl.libre = Math.max(0, tbl.capacidad - tbl.used);
            }

            renderAssignFloor();
            renderAssignQueue();
            updateAssignStats();
        });

        // ---- CONFIRM: SAVE TO DB ----
        $('#btn_assign_confirm').on('click', function() {
            if (assignDone.length === 0) return;
            if (!confirm('¿Confirmar la asignación de ' + assignDone.length + ' pedidos a mesas?\n\nEsto guardará las asignaciones en la base de datos.')) return;

            var configId = <?php echo $config_id; ?>;
            var payload = assignDone.map(function(item) {
                return {
                    order_id: item.order.order_id,
                    mesa_id: item.mesa_id,
                    grupo: item.order.grupo,
                    cantidad: item.order.cantidad,
                    nombre: item.order.nombre,
                    apellidos: item.order.apellidos
                };
            });

            var $btn = $('#btn_assign_confirm');
            $btn.prop('disabled', true).text('⏳ Guardando...');

            $.post(ajaxurl, {
                action: 'tbp_asientos_manual_assign_batch',
                config_id: configId,
                assignments: payload,
                nonce: '<?php echo wp_create_nonce("tbp_asientos_nonce"); ?>'
            }, function(res) {
                if (res.success) {
                    var d = res.data;
                    alert('✅ Asignación completada.\n\n' +
                        '• Pedidos guardados: ' + d.saved + ' de ' + d.total + '\n' +
                        '• Piezas asignadas: ' + assignDonePiezas +
                        (d.errors.length > 0 ? '\n• Errores: ' + d.errors.join(', ') : ''));
                    closeAssignModal();
                    // Reload page to reflect changes
                    window.location.href = '?page=tbp-actividades-asientos&action=edit&id=' + configId;
                } else {
                    alert('❌ Error: ' + (res.data.message || res.data));
                    $btn.prop('disabled', false).text('💾 Confirmar Asignación');
                }
            }).fail(function() {
                alert('❌ Error de conexión.');
                $btn.prop('disabled', false).text('💾 Confirmar Asignación');
            });
        });

        // ---- CANCEL ----
        $('#btn_assign_cancel').on('click', function() {
            if (assignDone.length > 0) {
                if (!confirm('¿Descartar todas las asignaciones pendientes de guardar?')) return;
            }
            closeAssignModal();
        });

        function closeAssignModal() {
            $('#tbp-assign-modal').fadeOut(200);
            $('body').css('overflow', '');
            assignQueue = [];
            assignDone = [];
            assignHistory = [];
        }

        // ---- ZOOM ----
        $('#assign_zoom').on('input', function() {
            assignZoom = parseFloat($(this).val());
            $('#assign_zoom_label').text(Math.round(assignZoom * 100) + '%');
            $('#assign_canvas_items').css('transform', 'scale(' + assignZoom + ')');
        });

        // Mouse wheel zoom on canvas
        $(document).on('wheel', '#assign_canvas_container', function(e) {
            e.preventDefault();
            var delta = e.originalEvent.deltaY > 0 ? -0.05 : 0.05;
            assignZoom = Math.min(1.5, Math.max(0.2, assignZoom + delta));
            $('#assign_zoom').val(assignZoom);
            $('#assign_zoom_label').text(Math.round(assignZoom * 100) + '%');
            $('#assign_canvas_items').css('transform', 'scale(' + assignZoom + ')');
        });

        // ESC key to close modal
        $(document).on('keydown', function(e) {
            if (e.key === 'Escape' && $('#tbp-assign-modal').is(':visible')) {
                $('#btn_assign_cancel').click();
            }
        });

        } catch(e) { console.error('TBP Modal Asignación error:', e); }

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
        // Solo generar/sincronizar mesas desde zonas_config si hay zonas definidas.
        // Si está vacío, el usuario solo usa el plano visual (Etapa 2) y NO debemos borrar nada.
        if ( ! empty( $zonas_config ) ) {
            tbp_asientos_generate_tables( $saved_id, $zonas_config );
        }
        
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
