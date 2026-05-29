<?php
/**
 * Plugin Name: The Best Prom - Actividades
 * Plugin URI: https://dev.thebestprom.com
 * Version: 11.9.50
 * Author: Antigravity Team
 * Text Domain: tbp-actividades
 *
 * Changelog:
 * 11.9.50 - Feature: Rediseñada la Etapa 3 (Generación y Asignación) dividiéndola en dos caminos: Asignación Automática y Asignación Manual. Se implementó un Workspace de Asignación Manual interactivo con filtros avanzados por grupo, buscador global, estadísticas de progreso en tiempo real y asignación point-and-click sobre el plano interactivo visual.
 * 11.9.49 - Fix: Modificada tbp_asientos_generate_tables() para realizar sincronización inteligente de mesas en la base de datos en lugar de eliminarlas y recrearlas. Esto evita que al hacer clic en "Guardar Cambios" en la Etapa 1 (Configuración de Metadatos) se pierdan las coordenadas, formas, colores y tamaños de las mesas diseñadas en la Etapa 2 (Procesamiento del Plano).
 * 11.9.48 - Critical Fix: Corregida la incompatibilidad con HPOS en tbp_asientos_get_orders_for_event() — la función consultaba wp_posts directamente (devolvía 0 pedidos con HPOS activo). Ahora delega a tbp_report_get_event_order_ids() (4 métodos de resolución HPOS-safe) y filtra estados con wc_get_order(). También se resetea capacidad_usada antes de re-asignar y se usa capacidad TOTAL en el algoritmo. Se añadió diagnóstico detallado cuando la asignación devuelve 0 resultados.
 * 11.9.47 - Fix: Corrección en la obtención de mesas del motor de packing (ahora soporta mesas con forma personalizada 'round' o 'rectangular' guardadas desde el plano visual en lugar de requerir estrictamente tipo 'normal').
 * 11.9.46 - Fix: Vinculación del evento click al botón "Ejecutar Asignación Inteligente" (btn_run_packing_v2) en la Etapa 3.
 * 11.9.45 - Fix: Sincronización al borrar mesas en el plano visual (ahora las elimina correctamente de la base de datos y libera asignaciones).
 * 11.9.44 - Fix: Corrección en la generación visual de mesas (lugares por mesa PAX) y rutina de auto-reparación para mesas con capacidad 0.
 * 11.9.43 - Feature: Segregación estricta de paquetes físicos y boletos de rifa en el reporte general y exportación CSV según filtro activo.
 * 11.9.42 - Feature: Base report package delivery status on WooCommerce order metadata to prevent ghost pending orders. Implement fallback rule matching by product ID during QR ticket check-ins on non-rule dates.
 * 11.9.41 - Feature: Base report package delivery status on WooCommerce order metadata to prevent ghost pending orders. Implement fallback rule matching by product ID during QR ticket check-ins on non-rule dates.
 * 11.9.40 - Robustness: Injected defensive DataTables page offset fallback in admin-reports.php to reset out-of-bounds offsets automatically when moving from larger to smaller filtered datasets, preventing blank datatable issues.
 * 11.9.39 - Bugfix: Bypassed wc_get_orders and implemented direct array slicing with wc_get_order to completely resolve HPOS compatibility bugs in datatables server-side pagination.
 * 11.9.38 - Bugfix: Fixed order query parameters using include instead of post__in in admin-reports.php to ensure compatibility with WooCommerce High-Performance Order Storage (HPOS) and resolve orders sneaking into the pending list.
 * 11.9.37 - Optimization: Optimized background database repair and migration routines during admin_init to run in batches using LEFT JOINs to prevent WSOD/504 errors on large sites.
 * 11.9.32 - Feature: Added Multi-Phase Delivery Tracking. The system now supports multiple delivery phases for the same order (e.g. Shirts vs Gala Passes) mapped to Delivery Rules. Added "Phase" dropdown filter to Admin Reports. Added "Phase" selection to Manual Delivery UI.
 * 11.9.31 - Feature: XLSX Export capability natively integrated into the admin report via DataTables. Hotfix: Injected _tbp_entrega_paquetes to the background Check-in webhook to resolve ghost discrepancies.
 * 11.9.30 - Feature: Added "Fast Scanner" (Modo Cadenero) Option B to the web scanner. It uses continuous scanning with visual/audio/haptic feedback, reads native Event Tickets QRs, validates them directly against WooCommerce Active Delivery Rules (hybrid mode), and seamlessly triggers the official check-in to preserve compatibility while establishing independence from Event Tickets Plus.
 * 11.9.29 - Feature: Added custom Web-Based QR Scanner directly into the Physical Delivery shortcode UI. Added capability to Auto Check-in Event Tickets attendees during physical item delivery.
 * 11.9.28 - Critical Fix: The QR Scanner App explicitly pre-checks if the attendee's order status is in `get_completed_status_by_provider_name()`. If it is NOT, the checkin API returns "Attendee Not Authorized" BEFORE even trying to check them in. We added a filter on `tec_tickets_completed_status_by_provider_name` to inject 'processing' and 'wc-processing' into this list so the REST API allows the scan.
 * 11.9.27 - Fix: Removed syntax error (unmatched bracket) introduced in 11.9.26.
 * 11.9.26 - Critical Fix: Intercept REST API GET requests for attendees. If the QR Scanner App explicitly requests '?order_status=wc-completed', it bypasses our public status reflection. We now forcefully inject 'wc-processing' into $_GET before the REST API boots, tricking the app into loading 'Pagado con Tarjeta' attendees.
 * 11.9.25 - Critical Fix: Added missing hooks for 'event_tickets_woo_ticket_generating_order_stati' and 'event_tickets_woo_complete_order_stati'. If ET+ settings were set to only generate on 'completed', the attendee records were NEVER created in the database for 'processing' orders, meaning they couldn't load in the app regardless of API filters. These hooks force physical ticket generation upon entering 'processing'.
 * 11.9.24 - Critical Fix: Added missing hook for 'event_tickets_attendees_woo_checkin_stati' filter. Without this, attendees from 'processing' orders would APPEAR in the QR app but scanning would FAIL because CheckIn_Stati.php hardcodes only 'completed' as valid. Now 'processing' is also accepted at scan time, completing the full chain: listing + scanning.
 * 11.9.23 - Fix: Corrected QR scanner status integration to ONLY include 'wc-processing' ('[Pagado con Tarjeta]') and 'wc-completed' (Completado). Explicitly EXCLUDES 'wc-p-pagado' (Pagado Parcialmente) since those orders have pending balances and must NOT be scannable.
 * 11.9.22 - Feature: Automated QR scanner support for custom order statuses. Hooked into Event Tickets and WooCommerce to dynamically register 'wc-processing' as valid and public order status via PHP Reflection.
 * 11.9.21 - Feature: Improved multi-scan workflow by splitting QR scanning and desktop check-in. Front-end QR scanning delivers 1 unit per scan and auto-reverts check-in to keep QR active. Desktop manual check-in from WP-Admin delivers ALL remaining units in a single click, allowing standard toggle to 'Cancelar el registro'. Added gorgeous high-contrast real-time badges next to desktop check-in buttons indicating delivery status (e.g. 'Entrega: 3 u', 'Restan: 2 de 3 u', 'Entregado: 3 u').
 * 11.9.20 - Feature: Added comprehensive multi-scan support for tickets containing multiple units/packages. A QR code (or manual check-in button click) can be scanned up to its allowed 'units' limit. Each scan increments the delivery log by 1 unit, updates WooCommerce notes, and compiles custom scan progression notifications (e.g. '1 de 3', '2 de 3'). The ticket's check-in status is automatically reset/reverted on ET Plus after each scan until the last configured unit is reached to keep the QR active for subsequent entries.
 * 11.9.19 - Feature: Added visual button disabling in the WP-Admin Attendees list for unauthorized check-ins. Hooked into `tec_tickets_attendees_table_column_check_in` to strip click classes, inject disabled attributes, set text to 'No Autorizado', and apply inline styling so that operators cannot physically click or trigger the check-in AJAX call.
 * 11.9.18 - Feature: Implemented real-time check-in authorization and automatic check-in prevention for unauthorized or duplicate tickets. Hooked into `tec_tickets_attendee_checkin` and WordPress database metadata hooks to block unauthorized check-ins, rendering failures to scanning operators and logging descriptive block notes on WooCommerce orders.
 * 11.9.17 - Feature: Implemented two-way symmetrical check-in synchronization. Undoing check-in for an attendee automatically deletes their physical delivery log from WooCommerce and updates post meta. Conversely, manually deleting a QR physical delivery log from WooCommerce order screen automatically reverts/undos the attendee's check-in status on Event Tickets to reactivate their ticket QR code.
 * 11.9.16 - Feature: Refactored duplicate checking and registration logging to operate per-attendee/ticket (using CPT post IDs) instead of per-order, allowing orders with multiple purchased packages to register each attendee independently. Personalized checkout messages using the specific attendee name.
 * 11.9.15 - Bugfix: Registered the physical delivery processing hook on WooCommerce and EDD specific ticket check-in hooks (`wootickets_checkin` and `eddtickets_checkin`) because WooCommerce/EDD sub-classes override Event Tickets core check-in method and bypass the standard `event_tickets_checkin` hook when checking in manually from WP-Admin attendees screen.
 * 11.9.14 - Feature: Added a beautiful real-time Diagnostic Badges line inside the WP-Admin "Tickets Attendees" list under each attendee name. This lists: Pedido ID (clickable to edit order), Product ID, Attendee ID, and live delivery rule status (Active, Inactive, Not enabled for this ticket post) to let admins preview how data flows at registration time.
 * 11.9.13 - Feature: Implemented a robust Real-time QR Scan Debugger Dashboard inside the WP-Admin Order "Actividades del Evento" metabox. Now admins can track and inspect every scan attempt in real time (successful, duplicate-blocked, timezone-ignored, or error state) with granular metadata inspection.
 * 11.9.12 - Bugfix: Implemented a timezone-proof delivery rule matching matrix in the check-in hook, supporting local, UTC, WP timezone, and Mexico UTC-6 fallback offsets to prevent rule matching failure when server operates on UTC timezone. Added rich verbose tracing logs.
 * 11.9.11 - Bugfix: Resolved check-in event ID discovery issue on WooCommerce tickets by scanning multiple meta keys and adding 7-layer fallback from order context. Created dedicated "Entregas Físicas (QR)" metabox section in WP-Admin and robust delete actions.
 * 11.9.10 - Feature: Added a robust hybrid execution model for QR physical deliveries, processing immediately (synchronously) for WP-Admin panel manually-triggered check-ins, while keeping background async queue for REST API high-concurrency app gateways.
 * 11.9.9 - Bugfix: Set priority 999 for check-in eligible statuses filters to prevent Event Tickets Plus from overriding them back to completed-only. Added manual payments custom status p-pagado/wc-p-pagado.
 * 11.9.8 - Mobile Design: Enhanced overall header card layout on mobile screens by implementing a responsive column query. The "Acceso Digital" badge now beautifully wraps and displays cleanly below the text, completely preventing any right-edge cropping or alignment overflow on cell phones.
 * 11.9.7 - Design: Repositioned QR active cards and official header block outside of the white boarding pass container using DOM script to achieve perfect horizontal symmetry matching the 2nd boarding pass image layout.
 * 11.9.6 - Design: Resolved boarding pass design squishing and tickets looking different on the order details page by refactoring CSS/header rendering to wp_head/wp_footer and implementing a robust check-in QR code generator fallback with Google Charts API.
 * 11.9.5 - Bugfix: Unified CSS grid selectors and added support for Event Tickets modern attendees list hooks to make the gorgeous Boarding Pass design fully responsive and perfectly styled on WooCommerce My Account details page.
 * 11.9.4 - Bugfix: Restructured hook logic to load Boarding Pass CSS grid unconditionally, ensuring beautiful styles apply to all tickets even if some or all do not have active QR codes yet.
 * 11.9.3 - Design: Transformed tickets list into a premium Boarding Pass card grid with overall official header and full mobile layout optimization.
 * 11.9.2 - Feature: Show QR code images directly on the WooCommerce buyer's My Account/Order details page.
 * 11.9.1 - Bugfix: Fix camera redirect QR scans and manual WP-Admin check-ins being ignored (by removing the strict $qr check and adding full support for newer flexible commerce metadata keys).
 * 11.8.15 - Feature: Selective Reset for Reports (Reset Packages vs Reset Raffles independently).
 * 11.8.14 - Bugfix: Fix Reset/Reminder buttons behavior in dynamic reports via event delegation.
 * 11.8.13 - Bugfix: Fix "Reset" button in reports to clear all types of deliveries (Packages/Raffles).
 * 11.8.12 - Security: Double-delivery prevention for packages with historical log display.
 * 11.8.11 - Bugfix: Final fix for statistics dashboard JavaScript synchronization.
 * 11.8.10 - Optimization: Ultra-fast SQL-based statistics engine and backward compatibility.
 * 11.8.9 - Bugfix: Fix statistics counters by using stable IDs for event filtering.
 * 11.8.8 - Feature: Dynamic Statistics Dashboard (Total, Delivered, Pending) in reports.
 * 11.8.7 - Optimization: Hybrid SQL/WC Engine for large-scale reporting (+13k orders).
 * 11.8.6 - Feature: Implementación de sistema de reportes del lado del servidor.
 * 9.0.9 - Fix: Corrección en el controlador de guardado que impedía almacenar el proveedor_id en la base de datos.
 * 9.0.8 - Feature/Fix: Anclaje de asientos al Proveedor. El escáner ahora iguala exactamente al Reporte de Egresos ignorando la casilla manual y usando el ID del proveedor (ej. Cintermex).
 * 9.0.7 - Fix: Alineada la lógica de lectura de "Cantidad" vacía. Ahora asume 1 (igual que el Reporte de Egresos) en lugar de 0. También se amplió la búsqueda de tickets a las 7 capas para evitar pérdida de boletos en versiones antiguas de ET+.
 * 9.0.6 - Fix: Eliminado un fallback de la fase 1 que asignaba asientos a boletos que NO tenían la casilla de asientos marcada en su configuración de egresos (ej. Boletos de After Party), lo que causaba discrepancias con el Reporte Maestro de Egresos.
 * 9.0.2 - Fix: Corrección en la extracción de metadatos de los asistentes en el algoritmo de packing.
 * 9.0.1 - UX: Cambio del campo de agrupación de texto libre a un select dinámico que carga los metadatos reales de los tickets del evento vía AJAX.
 * 9.0.0 - Major: Nuevo módulo de "Asignación Grupal de Asientos" (Fase 2). Incluye UI de configuración, definición de zonas, algoritmo de asignación automática (Bin-Packing), gestión de planos, y visibilidad de mesas en My Account, Pedidos y Tickets ET+.
 * 6.7.0 - Feature: Módulo MARKETING profesional para listas externas (XLSX/CSV) y envíos por lotes.
 * 6.6.1 - Feature: Etiquetas financieras [monto], [pagado], [saldo] integradas en el motor de mensajería.
 * 6.6.0 - Fix: Motor de detección de 7 capas para vincular pedido↔evento sin depender de asistentes (ET+).
 * 6.5.9 - Feature: Carga de nominados vía Excel (XLSX/CSV) en el editor de Premiaciones.
 * 6.5.5 - Feature: Soporte para 'event_id' manual en shortcode y URL para forzar visualización.
 * 6.5.4 - Debug: Volcado de llaves meta de ítems para análisis de pedidos conflictivos.
 * 6.5.3 - UI: Cuadro de información de shortcode e integración en el editor de Premiaciones.
 * 6.4.2 - Fix: El selector de imágenes de categorías ahora actualiza la categoría correcta (corrección de scope JS).
 * 6.4.1 - Fix: Mejora en la detección de campos de asistente usando la API de Tribe y búsquedas profundas.
 * 6.4.0 - UX Mejorada: Selección dinámica del campo de grupo basado en los tickets del evento.
 * 6.3.0 - Optimización de Premiaciones: Post único por evento, detección automática de grupos.
 *         Normalización de nombres de grupos (ej: 065 = 65).
 * 6.2.0 - Versión inicial de la funcionalidad de PREMIACIONES.
 * 6.1.1 - Corregido error de sintaxis en JavaScript.
 * 6.1.0 - Agregado control de tómbola y recepción de boletos vendidos.
 *         Reparación de funciones de mensajería (Email/WhatsApp) en reportes.
 *         Nueva columna "En tómbola" en el reporte administrative.
 * 6.0.1 - Versión inicial estable.
 * 6.0.0 - Versión inicial.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define constants
define( 'TBP_ACTIVIDADES_VERSION', '11.9.46' );
define( 'TBP_ACTIVIDADES_PATH', plugin_dir_path( __FILE__ ) );
define( 'TBP_ACTIVIDADES_URL', plugin_dir_url( __FILE__ ) );

// Include necessary files
require_once TBP_ACTIVIDADES_PATH . 'includes/database.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/cpt-rifas.php';
// Módulo de Premiaciones Re-activado
require_once TBP_ACTIVIDADES_PATH . 'includes/cpt-premiaciones.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/premiaciones-functions.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/eventbrite-webhook.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/physical-management.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/physical-delivery.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/event-delivery-rules.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/local-raffle-handler.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/frontend-integration.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/messaging.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/template-manager.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/admin-reports.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/admin-marketing.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/admin-analytics.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/frontend-reports.php';
require_once TBP_ACTIVIDADES_PATH . 'includes/frontend-floorplan.php';

// Módulo: Asignación de Asientos (Fase 2)
require_once TBP_ACTIVIDADES_PATH . 'includes/asientos/class-tbp-asientos.php';

function tbp_test_orders_endpoint() {
    if (isset($_GET['tbp_run_test'])) {
        global $wpdb;
        $orders_to_check = [33805, 32328, 30318, 30625];
        echo "<pre>";
        foreach ($orders_to_check as $oid) {
            echo "--- ANALYSIS FOR ORDER $oid ---\n";
            $paquete = get_post_meta($oid, '_tbp_entrega_paquetes', true);
            $fisica = get_post_meta($oid, '_tbp_entregas_fisicas', true);
            echo "TBP Entrega Paquetes: " . var_export($paquete, true) . "\n";
            echo "TBP Entregas Fisicas: " . var_export($fisica, true) . "\n";
            
            $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
            $logs = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_logs WHERE order_id = %d", $oid));
            echo "TBP Delivery Logs Count: " . count($logs) . "\n";
            foreach ($logs as $l) {
                echo "  - Log: type={$l->type}, created_at={$l->created_at}\n";
            }
            
            $attendees = $wpdb->get_results($wpdb->prepare("
                SELECT p.ID as ticket_id, pm.meta_value as checked_in
                FROM {$wpdb->posts} p 
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_tribe_tickets_has_checked_in'
                JOIN {$wpdb->postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_tribe_wooticket_order'
                WHERE p.post_type = 'tribe_wooticket' AND pm2.meta_value = %s
            ", $oid));
            
            echo "TEC Attendees Count: " . count($attendees) . "\n";
            foreach ($attendees as $att) {
                echo "  - Ticket {$att->ticket_id}: checked_in=" . var_export($att->checked_in, true) . "\n";
                $checkin_date = get_post_meta($att->ticket_id, '_tribe_tickets_checkin_date', true);
                if ($checkin_date) echo "    Checkin Date: $checkin_date\n";
            }
            echo "\n";
        }
        echo "</pre>";
        wp_die();
    }
}
add_action('init', 'tbp_test_orders_endpoint');

function tbp_actividades_repair_ghost_deliveries_v4() {
    if (get_option('tbp_ghost_deliveries_repaired_v4')) return;
    
    global $wpdb;
    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
    
    // 1. Repair missing _tbp_entrega_paquetes
    $orders = $wpdb->get_results("
        SELECT DISTINCT l.order_id 
        FROM {$table_logs} l
        LEFT JOIN {$wpdb->postmeta} pm ON l.order_id = pm.post_id AND pm.meta_key = '_tbp_entrega_paquetes'
        WHERE l.type IN ('qr_delivery', 'delivery_items') AND pm.post_id IS NULL
        LIMIT 50
    ");
    
    $repaired_count = 0;
    if (!empty($orders)) {
        foreach ($orders as $row) {
            update_post_meta(intval($row->order_id), '_tbp_entrega_paquetes', '1');
            $repaired_count++;
        }
    }
    
    // 2. Repair missing _tbp_delivery_rule_id
    $rules_missing = $wpdb->get_results("
        SELECT DISTINCT l.order_id, l.rifa_id
        FROM {$table_logs} l
        LEFT JOIN {$wpdb->postmeta} pm ON l.order_id = pm.post_id AND pm.meta_key = '_tbp_delivery_rule_id'
        WHERE l.type IN ('qr_delivery', 'delivery_items') AND l.rifa_id != 0 AND pm.post_id IS NULL
        LIMIT 50
    ");
    
    if (!empty($rules_missing)) {
        if (!function_exists('tbp_actividades_get_rule_by_hash')) {
            require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
        }
        foreach ($rules_missing as $row) {
            $order_id = intval($row->order_id);
            $rifa_id = intval($row->rifa_id);
            $rule = tbp_actividades_get_rule_by_hash($order_id, $rifa_id);
            if ($rule && !empty($rule['id'])) {
                add_post_meta($order_id, '_tbp_delivery_rule_id', $rule['id'], false);
                $repaired_count++;
            }
        }
    }
    
    if ($repaired_count === 0) {
        // No orders left to repair, complete migration
        update_option('tbp_ghost_deliveries_repaired_v4', true);
    }
}
add_action('admin_init', 'tbp_actividades_repair_ghost_deliveries_v4');

function tbp_asientos_repair_zero_capacity_tables() {
    global $wpdb;
    $table_configs = $wpdb->prefix . 'tbp_seat_configurations';
    $table_seats   = $wpdb->prefix . 'tbp_seat_tables';

    if ( $wpdb->get_var("SHOW TABLES LIKE '$table_configs'") !== $table_configs || $wpdb->get_var("SHOW TABLES LIKE '$table_seats'") !== $table_seats ) {
        return;
    }

    $zero_cap_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_seats} WHERE capacidad = 0");
    if ( ! $zero_cap_count ) {
        return;
    }

    $configs = $wpdb->get_results("SELECT id, zonas_config FROM {$table_configs}");
    foreach ( $configs as $config ) {
        $zonas = json_decode($config->zonas_config, true);
        if ( ! is_array($zonas) ) {
            continue;
        }

        foreach ( $zonas as $zona ) {
            $zona_nombre = sanitize_text_field( $zona['nombre'] ?? '' );
            $capacidad   = intval( $zona['capacidad'] ?? 10 );
            if ( $capacidad <= 0 ) {
                $capacidad = 10;
            }

            if ( ! empty($zona_nombre) ) {
                $wpdb->query( $wpdb->prepare(
                    "UPDATE {$table_seats} 
                     SET capacidad = %d 
                     WHERE config_id = %d AND zona = %s AND capacidad = 0",
                    $capacidad,
                    $config->id,
                    $zona_nombre
                ) );
            }
        }
    }
}
add_action('admin_init', 'tbp_asientos_repair_zero_capacity_tables');

function tbp_actividades_migrate_legacy_phases_v1() {
    if (get_option('tbp_legacy_phases_migrated_v1')) return;
    
    global $wpdb;
    
    // Find up to 20 orders that have the delivery flag but NO delivery rule ID assigned
    // We use a high performance LEFT JOIN instead of a slow NOT IN subquery
    $orders = $wpdb->get_col("
        SELECT pm1.post_id 
        FROM {$wpdb->postmeta} pm1
        LEFT JOIN {$wpdb->postmeta} pm2 ON pm1.post_id = pm2.post_id AND pm2.meta_key = '_tbp_delivery_rule_id'
        WHERE pm1.meta_key = '_tbp_entrega_paquetes' 
          AND pm1.meta_value IN ('1', 'yes') 
          AND pm2.post_id IS NULL
        LIMIT 20
    ");
    
    if (!empty($orders)) {
        if ( ! function_exists( 'tbp_actividades_discover_event_id_from_order' ) ) {
            require_once TBP_ACTIVIDADES_PATH . 'includes/order-integration.php';
        }
        
        foreach ($orders as $oid) {
            $event_id = tbp_actividades_discover_event_id_from_order($oid);
            $migrated = false;
            
            if ($event_id) {
                // Fetch the event's delivery rules
                $rules = get_post_meta($event_id, '_tbp_event_delivery_rules', true);
                if (is_array($rules) && !empty($rules)) {
                    // Assign to the FIRST available rule (most likely the one they just completed)
                    $first_rule_id = $rules[0]['id'];
                    add_post_meta($oid, '_tbp_delivery_rule_id', $first_rule_id, false);
                    
                    // Update the logs as well to point to the correct rule CRC32
                    $rule_numeric = crc32($first_rule_id);
                    $table_logs = $wpdb->prefix . 'tbp_actividades_logs';
                    $wpdb->update($table_logs, array('rifa_id' => $rule_numeric), array('order_id' => $oid, 'type' => 'delivery_items'));
                    $migrated = true;
                }
            }
            
            // If the order could not be migrated (no event/no rules), mark it as legacy_skipped
            // so that it won't be selected in the LEFT JOIN on subsequent requests.
            if (!$migrated) {
                add_post_meta($oid, '_tbp_delivery_rule_id', 'legacy_skipped', false);
            }
        }
    } else {
        // No orders left to migrate, mark migration as completed
        update_option('tbp_legacy_phases_migrated_v1', true);
    }
}
add_action('admin_init', 'tbp_actividades_migrate_legacy_phases_v1');

/**

 * Register Main Menu
 */
function tbp_actividades_register_menu() {
    add_menu_page(
        __( 'ACTIVIDADES', 'tbp-actividades' ),
        __( 'ACTIVIDADES', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades',
        'tbp_actividades_main_page',
        'dashicons-tickets-alt',
        25
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Rifas', 'tbp-actividades' ),
        __( 'Rifas', 'tbp-actividades' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_rifas'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Premiaciones', 'tbp-actividades' ),
        __( 'Premiaciones', 'tbp-actividades' ),
        'manage_woocommerce',
        'edit.php?post_type=tbp_premiaciones'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Reportes de Entrega', 'tbp-actividades' ),
        __( 'Reporte Entregas', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-reportes',
        'tbp_actividades_reportes_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Asignación de Asientos', 'tbp-actividades' ),
        __( 'Asientos (Fase 2)', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-asientos',
        'tbp_asientos_admin_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Toma de Fotografía', 'tbp-actividades' ),
        __( 'Fotografía (Fase 2)', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-fotografia',
        'tbp_actividades_placeholder_page'
    );
    add_submenu_page(
        'tbp-actividades',
        __( 'Configuración', 'tbp-actividades' ),
        __( 'Configuración', 'tbp-actividades' ),
        'manage_options', // Keep settings as admin only
        'tbp-actividades-settings',
        'tbp_actividades_settings_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'MARKETING (Listas externas)', 'tbp-actividades' ),
        __( 'MARKETING', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-marketing',
        'tbp_actividades_marketing_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Plantillas MKT', 'tbp-actividades' ),
        __( 'Plantillas MKT', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-plantillas',
        'tbp_actividades_plantillas_page'
    );

    add_submenu_page(
        'tbp-actividades',
        __( 'Reporte de Premiaciones', 'tbp-actividades' ),
        __( 'Reporte Premiaciones', 'tbp-actividades' ),
        'manage_woocommerce',
        'tbp-actividades-reporte-premiaciones',
        'tbp_actividades_premiaciones_report_page'
    );
}
add_action( 'admin_menu', 'tbp_actividades_register_menu' );
add_action( 'admin_init', 'tbp_actividades_register_settings' );

function tbp_actividades_register_settings() {
    register_setting( 'tbp_actividades_options', 'tbp_eventbrite_webhook_secret' );
    register_setting( 'tbp_actividades_options', 'tbp_eventbrite_api_token' );
    register_setting( 'tbp_actividades_options', 'tbp_stripe_secret_key' );
}

function tbp_actividades_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php _e( 'Configuración de Actividades', 'tbp-actividades' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'tbp_actividades_options' );
            do_settings_sections( 'tbp_actividades_options' );
            ?>
            <table class="form-table">
                <tr>
                    <th><label for="tbp_eventbrite_webhook_secret"><?php _e( 'Eventbrite Webhook Secret', 'tbp-actividades' ); ?></label></th>
                    <td><input type="text" id="tbp_eventbrite_webhook_secret" name="tbp_eventbrite_webhook_secret" value="<?php echo esc_attr( get_option( 'tbp_eventbrite_webhook_secret' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="tbp_eventbrite_api_token"><?php _e( 'Eventbrite API Token', 'tbp-actividades' ); ?></label></th>
                    <td><input type="password" id="tbp_eventbrite_api_token" name="tbp_eventbrite_api_token" value="<?php echo esc_attr( get_option( 'tbp_eventbrite_api_token' ) ); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th><label for="tbp_stripe_secret_key"><?php _e( 'Stripe Secret Key', 'tbp-actividades' ); ?></label></th>
                    <td><input type="password" id="tbp_stripe_secret_key" name="tbp_stripe_secret_key" value="<?php echo esc_attr( get_option( 'tbp_stripe_secret_key' ) ); ?>" class="regular-text" /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <p><strong><?php _e( 'Webhook URL para Eventbrite:', 'tbp-actividades' ); ?></strong> <code><?php echo esc_url( get_rest_url( null, 'tbp-actividades/v1/eventbrite' ) ); ?></code></p>
    </div>
    <?php
}

function tbp_actividades_main_page() {
    echo '<div class="wrap"><h1>' . __( 'Hub de Actividades - The Best Prom', 'tbp-actividades' ) . '</h1>';
    echo '<p>' . __( 'Bienvenido al centro de gestión de dinámicas para eventos.', 'tbp-actividades' ) . '</p></div>';
}

function tbp_actividades_placeholder_page() {
    echo '<div class="wrap"><h1>' . __( 'Módulo en Desarrollo', 'tbp-actividades' ) . '</h1>';
    echo '<p>' . __( 'Este módulo estará disponible en la Fase 2 del proyecto.', 'tbp-actividades' ) . '</p></div>';
}

/**
 * Activation Hook
 */
register_activation_hook( __FILE__, 'tbp_actividades_activate' );
function tbp_actividades_activate() {
    tbp_actividades_create_db_tables();
    update_option( 'tbp_actividades_db_version', TBP_ACTIVIDADES_VERSION );
    flush_rewrite_rules();
}

/**
 * Upgrade Routine
 */
add_action( 'plugins_loaded', 'tbp_actividades_check_upgrade' );
function tbp_actividades_check_upgrade() {
    $current_db_version = get_option( 'tbp_actividades_db_version', '0' );
    if ( version_compare( $current_db_version, TBP_ACTIVIDADES_VERSION, '<' ) ) {
        tbp_actividades_create_db_tables();

        // Parche robusto: dbDelta a veces ignora columnas nuevas en medio de la tabla
        global $wpdb;
        $table = $wpdb->prefix . 'tbp_seat_configurations';
        $col_exists = $wpdb->get_results("SHOW COLUMNS FROM {$table} LIKE 'proveedor_id'");
        if (empty($col_exists)) {
            $wpdb->query("ALTER TABLE {$table} ADD proveedor_id bigint(20) NOT NULL DEFAULT 0 AFTER event_id");
        }

        update_option( 'tbp_actividades_db_version', TBP_ACTIVIDADES_VERSION );
    }
}

/**
 * ============================================================================
 * SCRIPT TEMPORAL PARA DEV: ACTUALIZAR PEDIDOS A COMPLETADOS
 * ============================================================================
 */
add_action( 'admin_notices', function() {
    if ( ! isset($_GET['tbp_show_dev_force_complete']) ) return;
    if ( ! current_user_can('manage_options') ) return;
    
    // Contar cuántos hay
    $orders = wc_get_orders([
        'status' => 'processing',
        'limit' => -1,
        'return' => 'ids',
    ]);
    $count = count($orders);

    if ( $count > 0 ) {
        $url = add_query_arg( 'tbp_dev_force_complete_orders', '1', admin_url('admin.php?page=tbp-actividades') );
        echo '<div class="notice notice-warning is-dismissible" style="border-left-color: #f59e0b;">
            <p><strong>[DEV TBP] Tienes ' . $count . ' pedidos en estado "Pagado con Tarjeta" (Processing) que no generaron boleto antes del parche.</strong></p>
            <p>Para forzar la creación retroactiva de sus boletos en Event Tickets Plus, haz clic en el botón de abajo para pasarlos a "Completado".</p>
            <p><a href="' . esc_url($url) . '" class="button button-primary" style="background: #f59e0b; border-color: #d97706; text-shadow: none;">Convertir ' . $count . ' pedidos a Completados ahora</a></p>
        </div>';
    }
});

add_action( 'admin_init', function() {
    if ( isset($_GET['tbp_dev_force_complete_orders']) && current_user_can('manage_options') ) {
        $orders = wc_get_orders([
            'status' => 'processing',
            'limit' => -1
        ]);
        $count = 0;
        foreach ( $orders as $order ) {
            $order->update_status( 'completed', 'TBP DEV: Autocompletado para forzar la generación retroactiva de boletos QR.' );
            $count++;
        }
        
        add_action('admin_notices', function() use ($count) {
            echo '<div class="notice notice-success is-dismissible"><p>✅ <strong>¡Éxito!</strong> ' . $count . ' pedidos fueron pasados a Completado. Sus boletos han sido generados por Event Tickets Plus.</p></div>';
        });
    }
});
